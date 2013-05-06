<?php

load_plugin_textdomain('capa', false, dirname(plugin_basename(__FILE__)).'/lang' );

if (!class_exists("capa_protect_user_edit")) {

	class capa_protect_user_edit {

		function update() {
			if (strpos($_SERVER['REQUEST_URI'], '/wp-admin/user-edit.php') === false || isset($_POST['action']) && $_POST['action'] != 'update')
				return NULL;

			$user_id = empty($_POST['user_id']) ? $_GET['user_id'] : $_POST['user_id'];

			if(isset($_POST['capa_protect_user_reset']) && $_POST['capa_protect_user_reset'] == 'on'){
				delete_option("capa_protect_cat_user_${user_id}");
				delete_option("capa_protect_pag_user_${user_id}");
				return NULL;
			}

			$tmp['capa_protect_cat'] = NULL;
			$tmp['capa_protect_pag'] = NULL;

				if(isset($_POST['capa_protect_cat']) && is_array($_POST['capa_protect_cat'])){
					foreach($_POST['capa_protect_cat'] as $id=>$value){
						$tmp['capa_protect_cat'][$value] = TRUE;
					}
						capa_protect::set_access_for_user($user_id, $tmp['capa_protect_cat'],'cat');
				}else{
					delete_option("capa_protect_cat_user_${user_id}");
				}
	
				if(isset($_POST['capa_protect_pag']) && is_array($_POST['capa_protect_pag'])){
					foreach($_POST['capa_protect_pag'] as $id=>$value){
						$tmp['capa_protect_pag'][$value] = TRUE;
					}
						capa_protect::set_access_for_user($user_id, $tmp['capa_protect_pag'],'pag');
				}else{
					delete_option("capa_protect_pag_user_${user_id}");
				}
		}

		function print_html() {
			echo '<br>';

		// For WP < 27
			echo (function_exists('screen_icon')) ? screen_icon('plugins') : NULL;

			echo '<h2 style="margin-bottom:30px;">'. __('CaPa &raquo; User Settings','capa').'<br><span class="description" style="line-height:0.5em;">'. __('Here you can set categories and pages only for this users.','capa'). '</span></h2>';

			$user_id = empty($_POST['user_id']) ? $_GET['user_id'] : $_POST['user_id'];
			$user = new WP_User($user_id);

			if ($user && isset($user->allcaps['manage_categories']) && !isset($user->caps['editor'])) {

				echo '<h3>'. __('Access','capa') .' &amp; </h3>';
				echo '<p class="desc">'. __('As a manager, this user see all categories &amp; pages.','capa').'</p>';

			}else{
					echo '<div class="wrap">';
						echo '<form name="capa_protect" method="post">';

							$categorys	= & get_categories( array('sort_column' => 'menu_order','hide_empty'=>0, 'child_of'=>0, 'hierarchical'=>0) );
							$pages		= & get_pages( array('sort_column' => 'menu_order') );

							// -- @BEGINN CATEGORIE ORDER --
								foreach ( $categorys as $id=>$cat ) {
									$raw_order[$cat->term_id] = $id;
									$search[$cat->term_id] = $cat->category_parent;
								}

									foreach($categorys as $id=>$cat){
											$lvl['cat'][$cat->term_id] = capa_find_lvl($cat->term_id,$search);
									}

								// Get cat menu_order
								ob_start();
									wp_category_checklist();
									$cat_output = ob_get_contents();
								ob_end_clean();

									preg_match_all("#category-(?P<id>(\d+))'( |>)#isU",$cat_output, $sort_order, PREG_PATTERN_ORDER);
									$sort_order = $sort_order['id'];
							// -- @END --


								// -- @BEGINN Page Order --
									foreach ( $pages as $id=>$pag ) {
										$search[$pag->ID] = $pag->post_parent;
									}
										foreach($pages as $id=>$pag){
											$lvl['page'][$pag->ID] = capa_find_lvl($pag->ID,$search);
										}
								// -- @END --


							// CATEGORIES
								echo '
									<table class="widefat fixed capa-table" cellspacing="0">
										<thead>
										<tr>
											<th scope="col" id="name" class="manage-column column-name">'. _n('Category visibility','Categories visibility',count($categorys),'capa') .'</th>
											<th scope="col" id="name" class="manage-column column-name">'. _n('Page visibility','Pages visibility',count($pages),'capa') .'</th>
										</tr>
										</thead>

										<tfoot>
											<tr>
												<th>
													<label for="check-cat">
														<input type="checkbox" id="check-cat" name="check-cat" onclick="capa_check(\'capa_protect_cat[]\',\'check-cat\');"> '. __('check/uncheck all Categories','capa') .'
													</label>
												</th>
												<th style="border-left:1px solid #FFF;">
													<label for="check-pag">
														<input type="checkbox" id="check-pag" name="check-pag" onClick="capa_check(\'capa_protect_pag[]\',\'check-pag\');"> '. __('check/uncheck all Pages','capa') .'
													</label>
												</th>
											</tr>
										</tfoot>

										<tbody>
										<tr><td style="padding:0px;">
										<table class="widefat" style="border:0px; ">';

							// CATEGORYS LIST
								$i				= 0;
								$odd[0]			= '';
								$odd[1]			= 'class="alternate"';
								$category_check	= get_option("capa_protect_cat_user_${user_id}");

									foreach ($sort_order as $key=>$id) {
										echo '<tr><td '.$odd[($i%2)].'><label class="selectit" ><input type="checkbox" name="capa_protect_cat[]" value="'.$categorys[$raw_order[$id]]->term_id.'"';
											echo ( isset($category_check[$categorys[$raw_order[$id]]->term_id]) ) ? ' checked ' : '';
										echo '>&nbsp;'.str_repeat('<span style="color:#21759B;">&rsaquo;</span> ',$lvl['cat'][$categorys[$raw_order[$id]]->term_id]).$categorys[$raw_order[$id]]->cat_name . '</label></td></tr>';

										$i++;
									}

								echo '	</table>
										</td>';

						// PAGES LIST
								echo '
									<td style="border-left:1px solid #DFDFDF; padding:0px;">
										<table class="widefat" style="border:0px;">
										';

									$i				= 0;
									$odd[1]			= 'class="alternate"';
									$pages_check	= get_option("capa_protect_pag_user_${user_id}");

										foreach ($pages as $page) {
											echo '<tr><td '.$odd[($i%2)].' ><label class="selectit" ><input type="checkbox" name="capa_protect_pag[]" value="'.$page->ID.'"';
												echo (isset($pages_check[$page->ID])) ? ' checked ' : '';
											echo '>&nbsp;' .str_repeat('<span style="color:#21759B;">&rsaquo;</span> ',$lvl['page'][$page->ID]). $page->post_title . '</label></td></tr>';

											$i++;
										}

								echo '</table>
									</td>
									</tr>
									</tbody>
									</table>
								';

							echo '</td>';

						echo '</tr>';
					echo '</table>';

					echo '<br>';

					echo '<input type="checkbox" name="capa_protect_user_reset"> '. __('Reset current CaPa user settings','capa');
					echo '<br><br><hr>';

				echo '</div>';
	
			//--------------------

			}

		}

	}

}

// --------------------------------------------------------------------

// We'll use a very low priority so that our plugin will run after everyone
// else's. That way we won't interfere with other plugins.
// ADD SM: Sound Good ^_^

add_action('edit_user_profile',			array('capa_protect_user_edit','print_html'));
add_action('edit_user_profile_update',	array('capa_protect_user_edit','update'));
#add_action('init',						array('capa_protect_user_edit','update'));

?>
