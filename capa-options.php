<?php
	load_plugin_textdomain('capa', false, dirname(plugin_basename(__FILE__)).'/lang' );

	add_action('admin_menu', 'capa_add_pages');
		add_action('the_post','_capa_filter_the_post');

		function _capa_filter_the_post($param){
			return capa_protect::_admin_the_post_parent($param);
		}

	function capa_add_pages() {

	// Rolle/User -> no manage options right -> No go 
		if (!current_user_can('manage_options')){
			return NULL;
		}

		global $menu;

	// Add CaPa CSS Style
		wp_enqueue_style( 'capa', plugins_url( $path = '/capa/css/capa-style.css'), array() );
		wp_enqueue_script( 'capa', plugins_url( $path = '/capa/js/capa.js'), array() );


	// DEV Variable
		define('CAPA_DBUG', FALSE);
		$menu_slug = (CAPA_DBUG) ? __FILE__ : 'capa/capa-options';

	/**
	TODO 
	- 26.0001 verhindert, dass ein anderes Plugin den Platz stiehlt. - Notloesung; Aendern
*/

	// Add Capa Separator
		$menu['26.0001'] = array( '', 'manage_options', 'separator-capa', '', 'wp-menu-separator' );

	// Add CaPa Menu ( after the CaPa Separator and before Appearance) :
		add_menu_page( __('CaPa','capa'), 'CaPa', 'manage_options', $menu_slug, 'capa_global_page', 'div', '26.0002');

		// Global Subpage
			$page['global'] =	add_submenu_page($menu_slug, __('CaPa &rsaquo; General Settings','capa'), __('CaPa Settings','capa'), 'manage_options', $menu_slug, 'capa_global_page');

		// Roles Subpage
			$page['roles'] =	add_submenu_page($menu_slug, __('CaPa &rsaquo; User Roles','capa'),	__('User Roles','capa'),	'manage_options', 'capa/capa-roles-page', 'capa_sublevel_roles');

		// Support Subpage
			$page['help'] =		add_submenu_page($menu_slug, __('CaPa &rsaquo; Support','capa'),		__('CaPa Support','capa'),	'manage_options', 'capa/capa-support-page', 'capa_sublevel_support');

	// Add Help 
		if (function_exists('add_contextual_help')) {

			add_contextual_help($page['global'],'<br />'.	@file_get_contents(WP_PLUGIN_DIR."/capa/help/global.".	((WPLANG == '') ? 'EN' : ((file_exists(WP_PLUGIN_DIR."/capa/help/global.".strtoupper(substr(WPLANG,0,2)))) ? strtoupper(substr(WPLANG,0,2)) : 'EN' )), "r"));
			add_contextual_help($page['roles'],'<br />'.	@file_get_contents(WP_PLUGIN_DIR."/capa/help/roles.".	((WPLANG == '') ? 'EN' : ((file_exists(WP_PLUGIN_DIR."/capa/help/roles.".strtoupper(substr(WPLANG,0,2)))) ? strtoupper(substr(WPLANG,0,2)) : 'EN' )), "r"));
			add_contextual_help($page['help'],'<br />'.		@file_get_contents(WP_PLUGIN_DIR."/capa/help/help.".	((WPLANG == '') ? 'EN' : ((file_exists(WP_PLUGIN_DIR."/capa/help/help.".strtoupper(substr(WPLANG,0,2)))) ? strtoupper(substr(WPLANG,0,2)) : 'EN' )), "r"));

		}
		
		
	}

// capa_global_page() displays the page content for the custom Capa Toplevel menu
	function capa_global_page() {

	// Check if POST isnt empty
		($_POST) ? capa_handle_action() : NULL ;

		$private_message = capa_protect::get_private_message();

		echo '<div class="wrap">';
	// For WP < 27
		echo (function_exists('screen_icon')) ? screen_icon('options-general') : NULL;
		
			echo '<h2 style="margin-bottom:15px;">' . __('CaPa &raquo; General settings','capa') . '<br><span class="description">'. __('These settings define the display of the CaPa protected content on your blog.','capa'). '</span></h2>';

			echo '<form name="capa_protect" method="post">';
			wp_nonce_field('update-options');
		  // --------------------------------------------------------------

			echo '
				<table class="form-table capa-form-table">
				<tr>
					<th scope="row">'.__('Page display','capa').'</th>
					<td>
						<label>
							<input name="capa_protect_show_private_pages" type="checkbox" onClick="capa_enable_disable_form_elements()"' .
								(get_option('capa_protect_show_private_pages') ? " checked" : "") .
								'> ' . __('Show protected pages','capa') .'
						</label>
						<br>
						<span class="description" style="margin-left:10px;">'. __('Checking this option will show links to all the pages.','capa'). '</span>
						<br>
						<div style="margin-left:10px;">
						</div>
					</td>
				</tr>

				<tr>
					<th scope="row">'.__('The Category List','capa').'</th>
					<td>
						<label>
							<input name="capa_protect_show_private_categories" type="checkbox" onClick="capa_enable_disable_form_elements()"' .
								(get_option('capa_protect_show_private_categories') ? " checked" : "") .
								'> ' . __('Show private categories','capa') .'
						</label>
						<br>
						<span class="description" style="margin-left:10px;">'. __('Checking this option will show links to all the Categories.','capa'). '</span>

						<br>
						<label id="capa_protect_show_padlock_on_private_categories">
							<input name="capa_protect_show_padlock_on_private_categories" type="checkbox" '.
								(get_option('capa_protect_show_padlock_on_private_categories') ? " checked" : "" ) .
								'> ' . __('Show a padlock icon next to private categories','capa') .'
						</label>
					</td>
				</tr>

				<tr valign="top">
					<th scope="row">'.__('Posts and pages display.','capa').'
						<br>
						<span class="description">'. __('How do you want to display a protected post or page?','capa'). '</span>
					</th>
					<td>
						<label>
							<input type="radio" name="capa_protect_post_policy" onClick="capa_enable_disable_form_elements()" value="hide" ' .
								(get_option('capa_protect_post_policy') == 'hide' ||
										get_option('capa_protect_post_policy') == false &&
											get_option('capa_protect_show_private_message') == false ? ' checked' : '') .
								'> '. __('Hide everything','capa') .'
						</label>
							<br>
						<label>
							<input type="radio" name="capa_protect_post_policy" onClick="capa_enable_disable_form_elements()" value="show message"' .
							(get_option('capa_protect_post_policy') == 'show message' ||
									get_option('capa_protect_show_private_message') == true ? ' checked' : '') .
							'> '. __('Show everything','capa') .'
						</label>
							<br>
						<label>
							<input type="radio" name="capa_protect_post_policy" onClick="capa_enable_disable_form_elements()" value="show title"' .
								(get_option('capa_protect_post_policy') == 'show title' ? ' checked' : '') .
								'> '. __('Show title and the private message as content','capa') .'
								
						</label>
					</td>
				</tr>
				<tr>
					<th scope="row">'. __('The private message','capa') .'</th>
					<td>
						<label for="capa_protect_private_message" id="capa_protect_private_message">
								<input name="capa_protect_private_message" type="text" size="70"' .
								' value="'.$private_message.'" />
						</label>
					</td>
				</tr>

				<tr>
					<th scope="row">'.__('The Comments','capa').'</th>
					<td style="padding-left:20px;">
					<h4 style="margin: 5px 0px 10px -10px;">'. __('Public &amp; Allowed Comments','capa') .':</h4>

						<label>
							<input type="radio" name="capa_protect_comment_policy" onClick="capa_enable_disable_form_elements()" value="hide"' .
							(get_option('capa_protect_comment_policy') == 'hide' ||
									get_option('capa_protect_comment_policy') == false ? ' checked' : '') .
								'> '. __('Hide all comments.', 'capa') .'
						</label>
							<br>
						<label>
							<input type="radio" name="capa_protect_comment_policy" onClick="capa_enable_disable_form_elements()" value="show name"' .
								(get_option('capa_protect_comment_policy') == 'show name' ? ' checked' : '') .
								'> '. __('Show Author, but the private message for the content.', 'capa') .'
						</label>
							<br>
						<label>
							<input type="radio" name="capa_protect_comment_policy"	onClick="capa_enable_disable_form_elements()" value="show message"' .
								(get_option('capa_protect_comment_policy') == 'show message' ? ' checked' : '') .
								'> '. __('Show content, but no author.', 'capa') .'
						</label>
							<br>
						<label>
							<input type="radio" name="capa_protect_comment_policy" onClick="capa_enable_disable_form_elements()" value="all"' .
								(get_option('capa_protect_comment_policy') == 'all' ? ' checked' : '') .
								'> '. __('Show everything.', 'capa') .'
						</label>

					<h4 style="margin: 15px 0px 10px -10px;">'. __('Private Comments','capa') .':</h4>

						<label>
							<input name="capa_protect_show_comment_on_private_posts" type="checkbox" value="1" '.
								 (get_option('capa_protect_show_comment_on_private_posts') ? " checked" : "") .
								 '> '. __('Use the Settings for comments from protected Posts.','capa') .'
						</label>

					</td>
				</tr>

				<tr>
					<th scope="row">'.__('The Media Library','capa').'</th>
					<td>
						<label>
							<input name="capa_protect_show_only_allowed_attachments" type="checkbox" onClick="capa_enable_disable_form_elements()" ' .(get_option('capa_protect_show_only_allowed_attachments') ? " checked" : "") .
								'> ' . __('Show only allowed Attachments','capa') .'
						</label>
						<br>
						<span class="description" style="margin-left:10px;">'. __('Checking this option shows only attachments which were uploaded (not inserted) in posts from allowed categories','capa'). '</span>
						<br>
						<label id="capa_protect_show_unattached_files">
							<input name="capa_protect_show_unattached_files" type="checkbox" '.
								(get_option('capa_protect_show_unattached_files') ? " checked" : "" ) .
								'> ' . __('Show unattached files','capa') .'
						</label>
					</td>
				</tr>

				<tr>
					<th scope="row">' .__('Miscellaneous','capa'). '</th>
					<td>
						<label>
							<input type="checkbox" name="capa_protect_keep_options" value="on"' .
								(get_option('capa_protect_keep_options') == '1' ? ' checked' : '') .
								'> '. __('Keep CaPa Settings','capa') .'
						</label><br>
						<span class="description" style="margin-left:10px;">'. __('In Case CaPa is disabled but you wanna keep the Settings.','capa'). '</span>
					</td>
				</tr>
				</table>
			';

			echo '
				<p class="submit" style="float:left; margin-right:10px;">
					<button type="submit" name="submit" class="button-primary" value="Update general settings" >'. __('Update general settings','capa') .'</button> 
				</p>
				<p class="submit">
					<button type="submit" name="submit" class="button-secondary" value="reset defaults" >'. __('Reset defaults','capa').'</button>
				</p>
				
			';

				echo '<script type="text/javascript">capa_enable_disable_form_elements();</script>';
			echo '</form>';
		echo '</div>';
	}

// capa_sublevel_roles() displays the page content for the roles submenu
	function capa_sublevel_roles() {
	// Check if POST isnt empty
		($_POST) ? capa_handle_action() : NULL ;

		/**
			TODO wp_get_nav_menus( array('orderby' => 'name') ) Rights
		*/
		/*
		<ul class="subsubsub">
		<li><a href='upload.php' class="current">All <span class="count">(116)</span></a> |</li>
		<li><a href='upload.php?post_mime_type=image'>Images <span class="count">(102)</span></a> |</li>

		<li><a href='upload.php?post_mime_type=audio'>Audio <span class="count">(4)</span></a> |</li>
		<li><a href="upload.php?detached=1">Unattached <span class="count">(8)</span></a></li></ul>
		*/

		echo '<div class="wrap">';
		// For WP < 27
			echo (function_exists('screen_icon')) ? screen_icon('users') : NULL;
			echo '<h2 style="margin-bottom:15px;">' . __('CaPa &raquo; Users Roles','capa') . '</h2>';

			// --------------------------------------------------------------

									$categorys	= get_categories( array('sort_column' => 'menu_order','hide_empty'=>0, 'child_of'=>0, 'hierarchical'=>0) );;
			$category_check_role_editor			= get_option("capa_protect_cat_role_editor");
			$category_check_role_author			= get_option("capa_protect_cat_role_author");
			$category_check_role_contributor	= get_option("capa_protect_cat_role_contributor");
			$category_check_role_subscriber		= get_option("capa_protect_cat_role_subscriber");
			$category_check_role_visitor		= get_option("capa_protect_cat_anonymous");

									$pages	= get_pages(array('sort_column' => 'menu_order'));
			$page_check_role_editor			= get_option("capa_protect_pag_role_editor");
			$page_check_role_author			= get_option("capa_protect_pag_role_author");
			$page_check_role_contributor	= get_option("capa_protect_pag_role_contributor");
			$page_check_role_subscriber		= get_option("capa_protect_pag_role_subscriber");
			$page_check_role_visitor		= get_option("capa_protect_pag_anonymous");

			$odd[0] = '';
			$odd[1] = ' class="alternate"';

			echo '<ul class="subsubsub" style="float:none;">';
				echo '<li><a href="#capa-scroll-category">'._n('Category','Categories', count($categorys), 'capa').'</a> | </li>';
				echo '<li><a href="#capa-scroll-page">'._n('Page','Pages', count($pages), 'capa').'</a></li>'; #  |
#				echo '<li><a href="#capa-scroll-redirect">'._n('Redirect','Redirects', 0, 'capa').'</a></li>';
			echo '</ul>';


			echo '<form name="capa_protect" method="post">';

  // --------------------------------------------------------------
  // ---- Categories --- //
  // --------------------------------------------------------------

			echo '<a name="capa-scroll-category"></a>';
			echo '<h2 style="margin-bottom:15px;">' . _n('Category visibility','Categories visibility',count($categorys),'capa') . '<br><span class="description">'. __('Select which categories are available for each user role.','capa'). '</span></h2>';

			echo '
				<table class="widefat fixed capa-table">
					<thead>
						<tr>
							<th>&nbsp;</th>
							<th style="text-align:center;">'.__('Administrator','capa').'</th>
							<th style="text-align:center;">'.__('Editor','capa').'</th>
							<th style="text-align:center;">'.__('Author','capa').'</th>
							<th style="text-align:center;">'.__('Contributor','capa').'</th>
							<th style="text-align:center;">'.__('Subscriber','capa').'</th>
							<th style="text-align:center;">'.__('Visitor','capa').'</th>
						</tr>
					</thead>

					<tfoot>
						<tr>
							<th> '. __('check/uncheck','capa') .' </th>
							<th> &nbsp; </th>
							<th> <input type="checkbox" id="check-cat-editor" onclick="capa_check(\'capa_protect_cat[editor][]\',\'check-cat-editor\');"> </th>
							<th> <input type="checkbox" id="check-cat-author" onclick="capa_check(\'capa_protect_cat[author][]\',\'check-cat-author\');"> </th>
							<th> <input type="checkbox" id="check-cat-contributor" onclick="capa_check(\'capa_protect_cat[contributor][]\',\'check-cat-contributor\');"> </th>
							<th> <input type="checkbox" id="check-cat-subscriber" onclick="capa_check(\'capa_protect_cat[subscriber][]\',\'check-cat-subscriber\');"> </th>
							<th> <input type="checkbox" id="check-cat-visitor" onclick="capa_check(\'capa_protect_cat[visitor][]\',\'check-cat-visitor\');"> </th>
						</tr>
					</tfoot>

					<tbody class="capa-tbody">
				';

				foreach ( $categorys as $id=>$cat ) {
					$raw_order[$cat->term_id] = $id;
					$search[$cat->term_id] = $cat->category_parent;
				}

				// Get children-lvl
					foreach($categorys as $id=>$cat){
							$lvl['cat'][$cat->term_id] = capa_find_lvl($cat->term_id,$search);
					}

				// Get category menu_order
				ob_start();
					wp_category_checklist();
					$cat_output = ob_get_contents();
				ob_end_clean();

					preg_match_all("#category-(?P<id>(\d+))'( |>)#isU",$cat_output, $sort_order, PREG_PATTERN_ORDER);
					$sort_order = $sort_order['id'];
					$i = 0;

#str_repeat( '&#8212; ', $lvl['cat'][$categorys[$raw_order[$id]]->term_id] )
#'.(($lvl['cat'][$categorys[$raw_order[$id]]->term_id] > 0) ? 'style="padding-left: '.$lvl['cat'][$categorys[$raw_order[$id]]->term_id].'5px;"' : '').'

				foreach ($sort_order as $key=>$id){
					echo '<tr style="text-align:center;" '.$odd[($i%2)].'>';
						echo '<th><input type="hidden" id="empty-'.$categorys[$raw_order[$id]]->slug.'" name="empty" value="0" readonly><label onclick="capa_check_slug(\''.$categorys[$raw_order[$id]]->slug.'-'.$categorys[$raw_order[$id]]->term_id.'\',\'empty-'.$categorys[$raw_order[$id]]->slug.'\');">'. str_repeat( '<span style="color:#21759B;">&rsaquo;</span> ', $lvl['cat'][$categorys[$raw_order[$id]]->term_id] ). $categorys[$raw_order[$id]]->cat_name .'</label></th>';
						echo '<td><input class="category_id_role" type="checkbox" DISABLED></td>';
						echo '<td><input class="category_id_role '.$categorys[$raw_order[$id]]->slug.'-'.$categorys[$raw_order[$id]]->term_id.'"  type="checkbox" name="capa_protect_cat[editor][]" value="'.$categorys[$raw_order[$id]]->term_id.'" '.		((isset($category_check_role_editor[$categorys[$raw_order[$id]]->term_id])) ? 'checked' : NULL).'></td>';
						echo '<td><input class="category_id_role '.$categorys[$raw_order[$id]]->slug.'-'.$categorys[$raw_order[$id]]->term_id.'"  type="checkbox" name="capa_protect_cat[author][]" value="'.$categorys[$raw_order[$id]]->term_id.'" '.		((isset($category_check_role_author[$categorys[$raw_order[$id]]->term_id])) ? 'checked' : NULL).'></td>';
						echo '<td><input class="category_id_role '.$categorys[$raw_order[$id]]->slug.'-'.$categorys[$raw_order[$id]]->term_id.'"  type="checkbox" name="capa_protect_cat[contributor][]" value="'.$categorys[$raw_order[$id]]->term_id.'" '.((isset($category_check_role_contributor[$categorys[$raw_order[$id]]->term_id])) ? 'checked' : NULL).'></td>';
						echo '<td><input class="category_id_role '.$categorys[$raw_order[$id]]->slug.'-'.$categorys[$raw_order[$id]]->term_id.'"  type="checkbox" name="capa_protect_cat[subscriber][]" value="'.$categorys[$raw_order[$id]]->term_id.'" '. ((isset($category_check_role_subscriber[$categorys[$raw_order[$id]]->term_id])) ? 'checked' : NULL).'></td>';
						echo '<td><input class="category_id_role '.$categorys[$raw_order[$id]]->slug.'-'.$categorys[$raw_order[$id]]->term_id.'"  type="checkbox" name="capa_protect_cat[visitor][]" value="'.$categorys[$raw_order[$id]]->term_id.'" '.	((isset($category_check_role_visitor[$categorys[$raw_order[$id]]->term_id])) ? 'checked' : NULL).'></td>';
					echo '</tr>';
					$i++;
				}

			echo '
					</tbody>
				</table>
			';

		echo '<br>';


  // --------------------------------------------------------------
  // ---- Pages --- //
  // --------------------------------------------------------------

		if($pages){

			echo '<a name="capa-scroll-page"></a>';
			echo '<h2 style="margin-bottom:15px;">' . _n('Page visibility', 'Pages visibility', count($pages),'capa') . '<br><span class="description">'. __('Select which pages are available for each user role.','capa'). '</span></h2>';
			
			echo '
				<table class="widefat fixed capa-table">
					<thead>
						<tr>
							<th>&nbsp;</th>
							<th style="text-align:center;">'.__('Administrator','capa').'</th>
							<th style="text-align:center;">'.__('Editor','capa').'</th>
							<th style="text-align:center;">'.__('Author','capa').'</th>
							<th style="text-align:center;">'.__('Contributor','capa').'</th>
							<th style="text-align:center;">'.__('Subscriber','capa').'</th>
							<th style="text-align:center;">'.__('Visitor','capa').'</th>
						</tr>
					</thead>

					<tfoot>
						<tr>
							<th> '. __('check/uncheck','capa') .' </th>
							<th> &nbsp; </th>
							<th> <input type="checkbox" id="check-pag-editor" onclick="capa_check(\'capa_protect_pag[editor][]\',\'check-pag-editor\');"> </th>
							<th> <input type="checkbox" id="check-pag-author" onclick="capa_check(\'capa_protect_pag[author][]\',\'check-pag-author\');"> </th>
							<th> <input type="checkbox" id="check-pag-contributor" onclick="capa_check(\'capa_protect_pag[contributor][]\',\'check-pag-contributor\');"> </th>
							<th> <input type="checkbox" id="check-pag-subscriber" onclick="capa_check(\'capa_protect_pag[subscriber][]\',\'check-pag-subscriber\');"> </th>
							<th> <input type="checkbox" id="check-pag-visitor" onclick="capa_check(\'capa_protect_pag[visitor][]\',\'check-pag-visitor\');"> </th>
						</tr>
					</tfoot>


					<tbody class="capa-tbody">
				';
				$i = 0;

				foreach ( $pages as $id=>$page ) {
					$search[$page->ID] = $page->post_parent;
				}
					foreach($pages as $id=>$page){
						$lvl['page'][$page->ID] = capa_find_lvl($page->ID,$search);
					}

#str_repeat( '&rsaquo; ', $lvl['cat'][$categorys[$raw_order[$id]]->term_id] )
#'.(($lvl['page'][$page->ID] > 0) ? 'style="padding-left: '.$lvl['page'][$page->ID].'5px;"' : '').'

				foreach ($pages as $page){

					echo '<tr style="text-align:center;" '.$odd[($i%2)].'>';
						echo '<th><input type="hidden" id="empty-'.$page->post_name.'" name="empty" value="0" readonly><label onclick="capa_check_slug(\''.$page->post_name.'-'.$page->ID.'\',\'empty-'.$page->post_name.'\');">'. str_repeat( '<span style="color:#21759B;">&rsaquo;</span> ', $lvl['page'][$page->ID] ). $page->post_title .'</label></th>';
						echo '<td><input class="page_id_role" type="checkbox" DISABLED></td>';
						echo '<td><input class="page_id_role '.$page->post_name.'-'.$page->ID.'" type="checkbox" name="capa_protect_pag[editor][]" value="'.$page->ID.'" '.((isset($page_check_role_editor[$page->ID])) ? 'checked' : NULL).'></td>';
						echo '<td><input class="page_id_role '.$page->post_name.'-'.$page->ID.'" type="checkbox" name="capa_protect_pag[author][]" value="'.$page->ID.'" '.((isset($page_check_role_author[$page->ID])) ? 'checked' : NULL).'></td>';
						echo '<td><input class="page_id_role '.$page->post_name.'-'.$page->ID.'" type="checkbox" name="capa_protect_pag[contributor][]" value="'.$page->ID.'" '.((isset($page_check_role_contributor[$page->ID])) ? 'checked' : NULL).'></td>';
						echo '<td><input class="page_id_role '.$page->post_name.'-'.$page->ID.'" type="checkbox" name="capa_protect_pag[subscriber][]" value="'.$page->ID.'" '.((isset($page_check_role_subscriber[$page->ID])) ? 'checked' : NULL).'></td>';
						echo '<td><input class="page_id_role '.$page->post_name.'-'.$page->ID.'" type="checkbox" name="capa_protect_pag[visitor][]" value="'.$page->ID.'" '.((isset($page_check_role_visitor[$page->ID])) ? 'checked' : NULL).'></td>';
					echo '</tr>';
					$i++;
				}

			echo '
					</tbody>
				</table>
			';
		}

		echo '<br>';

  // --------------------------------------------------------------
  // ---- Redirect --- //
  // --------------------------------------------------------------

		if(TRUE == FALSE){

			echo '<a name="capa-scroll-redirect"></a>';
			echo '<h2 style="margin-bottom:15px;">' . _n('Redirect', 'Redirects', 0,'capa') . '<br><span class="description">'. __('Select which pages are available for each user role.','capa'). '</span></h2>';

			echo '
				<table class="widefat fixed capa-table">
					<thead>
						<tr>
							<th>&nbsp;</th>
							<th style="text-align:center;">'.__('Administrator','capa').'</th>
							<th style="text-align:center;">'.__('Editor','capa').'</th>
							<th style="text-align:center;">'.__('Author','capa').'</th>
							<th style="text-align:center;">'.__('Contributor','capa').'</th>
							<th style="text-align:center;">'.__('Subscriber','capa').'</th>
							<th style="text-align:center;">'.__('Visitor','capa').'</th>
						</tr>
					</thead>

					<tfoot>
						<tr>
							<th> '. __('check/uncheck','capa') .' </th>
							<th> &nbsp; </th>
							<th> <input type="checkbox" id="check-pag-editor" onclick="capa_check(\'capa_protect_pag[editor][]\',\'check-pag-editor\');"> </th>
							<th> <input type="checkbox" id="check-pag-author" onclick="capa_check(\'capa_protect_pag[author][]\',\'check-pag-author\');"> </th>
							<th> <input type="checkbox" id="check-pag-contributor" onclick="capa_check(\'capa_protect_pag[contributor][]\',\'check-pag-contributor\');"> </th>
							<th> <input type="checkbox" id="check-pag-subscriber" onclick="capa_check(\'capa_protect_pag[subscriber][]\',\'check-pag-subscriber\');"> </th>
							<th> <input type="checkbox" id="check-pag-visitor" onclick="capa_check(\'capa_protect_pag[visitor][]\',\'check-pag-visitor\');"> </th>
						</tr>
					</tfoot>


					<tbody class="capa-tbody">
				';
				$i = 0;

				foreach ( $pages as $id=>$page ) {
					$search[$page->ID] = $page->post_parent;
				}
					foreach($pages as $id=>$page){
						$lvl['page'][$page->ID] = capa_find_lvl($page->ID,$search);
					}

#str_repeat( '&rsaquo; ', $lvl['cat'][$categorys[$raw_order[$id]]->term_id] )
#'.(($lvl['page'][$page->ID] > 0) ? 'style="padding-left: '.$lvl['page'][$page->ID].'5px;"' : '').'

				foreach ($pages as $page){

					echo '<tr style="text-align:center;" '.$odd[($i%2)].'>';
						echo '<th><input type="hidden" id="empty-'.$page->post_name.'" name="empty" value="0" readonly><label onclick="capa_check_slug(\''.$page->post_name.'-'.$page->ID.'\',\'empty-'.$page->post_name.'\');">'. str_repeat( '<span style="color:#21759B;">&rsaquo;</span> ', $lvl['page'][$page->ID] ). $page->post_title .'</label></th>';
						echo '<td><input class="page_id_role" type="checkbox" DISABLED></td>';
						echo '<td><input class="page_id_role '.$page->post_name.'-'.$page->ID.'" type="checkbox" name="capa_protect_pag[editor][]" value="'.$page->ID.'" '.((isset($page_check_role_editor[$page->ID])) ? 'checked' : NULL).'></td>';
						echo '<td><input class="page_id_role '.$page->post_name.'-'.$page->ID.'" type="checkbox" name="capa_protect_pag[author][]" value="'.$page->ID.'" '.((isset($page_check_role_author[$page->ID])) ? 'checked' : NULL).'></td>';
						echo '<td><input class="page_id_role '.$page->post_name.'-'.$page->ID.'" type="checkbox" name="capa_protect_pag[contributor][]" value="'.$page->ID.'" '.((isset($page_check_role_contributor[$page->ID])) ? 'checked' : NULL).'></td>';
						echo '<td><input class="page_id_role '.$page->post_name.'-'.$page->ID.'" type="checkbox" name="capa_protect_pag[subscriber][]" value="'.$page->ID.'" '.((isset($page_check_role_subscriber[$page->ID])) ? 'checked' : NULL).'></td>';
						echo '<td><input class="page_id_role '.$page->post_name.'-'.$page->ID.'" type="checkbox" name="capa_protect_pag[visitor][]" value="'.$page->ID.'" '.((isset($page_check_role_visitor[$page->ID])) ? 'checked' : NULL).'></td>';
					echo '</tr>';
					$i++;
				}

			echo '
					</tbody>
				</table>
			';
		}


		echo '
			<p class="submit" style="float:left; margin-right:10px;">
				<button type="submit" name="submit" class="button-primary" value="Update Role Options" >'. __('Update user roles settings','capa') .'</button> 
			</p>
			<p class="submit">
				<button type="submit" name="submit" class="button" value="reset role options" >'. __('Reset user roles','capa').'</button>
			</p>
			</form>';
	}

// capa_subleve_debug() displays information for an better help
	function capa_sublevel_support() {
	// Check if POST isnt empty
		($_POST) ? capa_handle_action() : NULL ;

		echo '<div class="wrap">';
		// For WP < 27
			echo (function_exists('screen_icon')) ? screen_icon('edit-comments') : NULL;
				echo '<h2 style="margin-bottom:15px;">' . __('CaPa &raquo; CaPa Support','capa') . '</h2>';

				echo '<h3>'.__('Information to provide a faster way to help you.','capa').'</h3>';
				echo __('Information will be send per e-mail. A Copy goes to the Sender E-Mail.','capa');
				echo '<br><br>';

			$all_plugins	= get_plugins();
			$active_plugins = array();
			$categorys		= get_categories( array('sort_column' => 'menu_order','hide_empty'=>0, 'child_of'=>0, 'hierarchical'=>0) );
			$pages		= & get_pages( array('sort_column' => 'menu_order') );

				foreach($categorys as $cat){
					$lvl['cat'][$cat->category_parent][] = $cat->term_id;
				}
					$lvl['cat'] = serialize($lvl['cat']);

				foreach ( $pages as $id=>$pag ) {
					$lvl['pag'][$pag->post_parent][] = $pag->ID;
				}
					$lvl['pag'] = serialize($lvl['pag']);

			foreach ( (array)$all_plugins as $plugin_file => $plugin_data) {

				//Translate, Apply Markup, Sanitize HTML
				$plugin_data = _get_plugin_data_markup_translate($plugin_file, $plugin_data, false, true);
				$all_plugins[ $plugin_file ] = $plugin_data;

				//Filter into individual sections
				if ( is_plugin_active($plugin_file) ) {
					$active_plugins[ $plugin_file ] = $plugin_data;
				} 

			}
			echo '<form name="capa_protect" method="post">';

			echo '
				<table class="form-table capa-form-table">
				<tr valign="top">
					<th scope="row" style="font-size:11px;">';
				echo '<b>'. __('@Active Plugins','capa').'</b><br>'.__('To avoid Problems between CaPa and others Plugins. All active plugins will be displayed.','capa');
				echo '<br><br>';
				echo '<b>'. __('@Serialize Categories ID&#39;s','capa').'</b><br>'.__('Serialize Categories contains only the Categories ID&#39;s in menu order','capa');
				echo '<br><br>';
				echo '<b>'. __('@Serialize Pages ID&#39;s','capa').'</b><br>'.__('Serialize Pages contains only the Pages ID&#39;s in menu order','capa');
				echo '<br><br>';
				echo '<b>'. __('@CaPa Settings','capa').'</b><br>'.__('All CaPa Settings','capa');
				echo '<br><br>';
				echo	__('In the case you don&#39;t want send all Information, just remove the part&#39;s.','capa');

			echo	'</th>
					<td><textarea class="large-text code" rows="20" name="information">';

				echo __( '@Wordpress','capa')."\n\n";

				echo __( 'Version:', 'capa' ) . ' ' . get_bloginfo('version') . "\n";
				echo __( 'PHP Version:','capa'). ' '. phpversion() . "\n";
				echo "\n\n";
				echo __( '@Active Plugins','capa')."\n\n";

				$i = 1;
				foreach($active_plugins as $plugin){
					echo '['.$i.'] '.$plugin['Name']." (".$plugin['Version'].")\n".$plugin['PluginURI']."\n\n";
					$i++;
				}
				echo "\n";

				echo __( '@Serialize Categories ID&#39;s','capa')."\n\n";
				echo $lvl['cat'];
				echo "\n\n\n";
				echo __( '@Serialize Pages ID&#39;s','capa')."\n\n";
				echo $lvl['pag'];
				echo "\n\n\n";

				echo __( '@CaPa Settings','capa')."\n\n";

					global $wpdb;

					$_capa_settings = $wpdb->get_results( "SELECT option_name, option_value FROM $wpdb->options WHERE option_name LIKE 'capa%'" );

					foreach($_capa_settings as $setting){
						echo $setting->option_name.":\n '";
						echo $setting->option_value."'\n\n";
					}


			echo	'</textarea>
					</td>
				</tr>
				<tr>
					<th style="font-size:11px;">';
					echo	'<b>'.__('Title','capa').'</b>';

			echo'	</th>
					<td valign="top">';
						echo '<input type="text" name="titel" value="Help Info from '.get_option('blogname').'" class="regular-text">';
						echo '<span class="description"> '. __('You can change the Title as you wish.','capa') .'</span>';
			echo'	</td>
				</tr>

				<tr>
					<th style="font-size:11px;">';
					echo	'<b>'.__('Sender','capa').'</b>';
				
			echo'	</th>
					<td valign="top">';
						echo '<input type="text" name="sender" value="'.get_option('admin_email').'" class="regular-text">';
						echo '<span class="description"> '. __('You can change the Sender E-Mail.','capa') .'</span>';
			echo'	</td>
				</tr>
				<tr>
					<th style="font-size:11px;">';
					echo	'<b>'.__('Comment (Optional)','capa').'</b>';

			echo'	</th>
					<td valign="top">';
						echo '<textarea class="large-text code" name="comment" rows="5"></textarea>';
			echo'	</td>
				</tr>
				</table>';

			echo '
				<p class="submit" style="float:left; margin-right:10px;">
					<button type="submit" name="submit" class="button-primary" value="Send Help">'. __('Send Help','capa') .'</button>
				</p>
				</form>';

		echo '</div>';

	}


	function capa_handle_action() {
		global $_POST;

		if ($_POST['submit'] == 'reset defaults') {
			delete_option('capa_protect_private_message');
			delete_option('capa_protect_post_policy');
			delete_option('capa_protect_show_padlock_on_private_posts');

			delete_option('capa_protect_show_title_in_feeds');

			delete_option("capa_protect_comment_policy");
			delete_option("capa_protect_show_comment_on_private_posts");

			delete_option('capa_protect_show_private_categories');
			delete_option('capa_protect_show_padlock_on_private_categories');

			delete_option('capa_protect_show_private_pages');
			update_option('capa_protect_show_only_allowed_attachments',	TRUE);
			delete_option('capa_protect_show_unattached_files');

			delete_option('capa_protect_keep_options');

			delete_option("capa_protect_advance_policy");

		// Show Update Message
			echo '<div id="message" class="updated fade"><p><strong>'.__('Global Settings are reset.','capa').'</strong></p></div>';
		}

		if($_POST['submit'] == 'reset role options'){
			delete_option("capa_protect_cat_role_editor");
			delete_option("capa_protect_cat_role_author");
			delete_option("capa_protect_cat_role_contributor");
			delete_option("capa_protect_cat_role_subscriber");
			delete_option("capa_protect_cat_anonymous");

			delete_option("capa_protect_pag_role_editor");
			delete_option("capa_protect_pag_role_author");
			delete_option("capa_protect_pag_role_contributor");
			delete_option("capa_protect_pag_role_subscriber");
			delete_option("capa_protect_pag_anonymous");

		// Show Update Message
			echo '<div id="message" class="updated fade"><p><strong>'.__('Role Settings are reset.','capa').'</strong></p></div>';

		}


		if($_POST['submit'] == 'Update general settings'){

			// Comment Update
			if(!isset($_POST['capa_protect_post_policy'])){
				delete_option('capa_protect_post_policy');
			}else{
				update_option('capa_protect_post_policy',					$_POST['capa_protect_post_policy']);
			}

			if(!isset($_POST['capa_protect_comment_policy'])){
				delete_option('capa_protect_comment_policy');
			}else{
				update_option('capa_protect_comment_policy',				$_POST['capa_protect_comment_policy']);
			}

			if(!isset($_POST['capa_protect_show_comment_on_private_posts'])){
				delete_option('capa_protect_show_comment_on_private_posts');
			}else{
				update_option('capa_protect_show_comment_on_private_posts', $_POST['capa_protect_show_comment_on_private_posts']);
			}

			if(!isset($_POST['capa_protect_private_message'])){
				delete_option('capa_protect_private_message');
			}else{
				update_option('capa_protect_private_message',				$_POST['capa_protect_private_message']);
			}


			if (isset($_POST['capa_protect_show_private_message']) && $_POST['capa_protect_show_private_message'] == 'on'){
				update_option('capa_protect_show_private_message', true);
			}else{
				update_option('capa_protect_show_private_message', false);
			}

			if (isset($_POST['capa_protect_show_title_in_feeds']) && $_POST['capa_protect_show_title_in_feeds'] == 'on'){
				update_option('capa_protect_show_title_in_feeds', true);
			}else{
				update_option('capa_protect_show_title_in_feeds', false);
			}


		// Remove Option from old Capa Versions
#			delete_option('capa_protect_show_private_message',false);
			delete_option('capa_protect_advance_policy',	FALSE);
			delete_option('capa_protect_cat_default',		FALSE);
			delete_option('capa_protect_pag_default',		FALSE);

			if (isset($_POST['capa_protect_show_private_categories']) && $_POST['capa_protect_show_private_categories'] == 'on'){
				update_option('capa_protect_show_private_categories', TRUE);
			}else{
				delete_option('capa_protect_show_private_categories');
			}

			if (isset($_POST['capa_protect_show_padlock_on_private_categories']) && $_POST['capa_protect_show_padlock_on_private_categories'] == 'on'){
				update_option('capa_protect_show_padlock_on_private_categories', TRUE);
			}else{
				delete_option('capa_protect_show_padlock_on_private_categories');
			}

			if (isset($_POST['capa_protect_show_private_pages']) && $_POST['capa_protect_show_private_pages'] == 'on'){
				update_option('capa_protect_show_private_pages', TRUE);
			}else{
				delete_option('capa_protect_show_private_pages');
			}


			if(isset($_POST['capa_protect_show_only_allowed_attachments']) && $_POST['capa_protect_show_only_allowed_attachments'] == 'on'){
				update_option('capa_protect_show_only_allowed_attachments', TRUE);
			}else{
				delete_option('capa_protect_show_only_allowed_attachments');
			}

				if(isset($_POST['capa_protect_show_unattached_files']) && $_POST['capa_protect_show_unattached_files'] == 'on'){
					update_option('capa_protect_show_unattached_files', TRUE);
				}else{
					delete_option('capa_protect_show_unattached_files');
				}


			if(isset($_POST['capa_protect_keep_options']) && $_POST['capa_protect_keep_options'] == 'on'){
				update_option('capa_protect_keep_options', TRUE);
			}else{
				delete_option('capa_protect_keep_options');
			}

		// Show Update Message
			echo '<div id="message" class="updated fade"><p><strong>'.__('Global Settings saved.','capa').'</strong></p></div>';
		}

		if($_POST['submit'] == 'Update Role Options') {

			if(isset($_POST['capa_protect_cat']) && is_array($_POST['capa_protect_cat'])){
				foreach($_POST['capa_protect_cat'] as $user=>$value){
					foreach($value as $id=>$wert){
						$tmp['cat'][$user][$wert] = TRUE;
					}
				}

				(!isset($tmp['cat']['editor']))		? delete_option('capa_protect_cat_role_editor')		: update_option('capa_protect_cat_role_editor',			$tmp['cat']['editor']);
				(!isset($tmp['cat']['author']))		? delete_option('capa_protect_cat_role_author')		: update_option('capa_protect_cat_role_author',			$tmp['cat']['author']);
				(!isset($tmp['cat']['contributor']))? delete_option('capa_protect_cat_role_contributor'): update_option('capa_protect_cat_role_contributor',	$tmp['cat']['contributor']);
				(!isset($tmp['cat']['subscriber'])) ? delete_option('capa_protect_cat_role_subscriber') : update_option('capa_protect_cat_role_subscriber',		$tmp['cat']['subscriber']);
				(!isset($tmp['cat']['visitor']))	? delete_option('capa_protect_cat_anonymous')		: update_option('capa_protect_cat_anonymous',			$tmp['cat']['visitor']);

			}else{
				delete_option('capa_protect_cat_role_editor');
				delete_option('capa_protect_cat_role_author');
				delete_option('capa_protect_cat_role_contributor');
				delete_option('capa_protect_cat_role_subscriber');
				delete_option('capa_protect_cat_anonymous');
			}

			if(isset($_POST['capa_protect_pag']) && is_array($_POST['capa_protect_pag'])){

				foreach($_POST['capa_protect_pag'] as $user=>$value){
					foreach($value as $id=>$wert){
						$tmp['pag'][$user][$wert] = TRUE;
					}
				}

				(!isset($tmp['pag']['editor']))		?	delete_option('capa_protect_pag_role_editor')		:	update_option('capa_protect_pag_role_editor'		,$tmp['pag']['editor']);
				(!isset($tmp['pag']['author']))		?	delete_option('capa_protect_pag_role_author')		:	update_option('capa_protect_pag_role_author'		,$tmp['pag']['author']);
				(!isset($tmp['pag']['contributor']))?	delete_option('capa_protect_pag_role_contributor')	:	update_option('capa_protect_pag_role_contributor'	,$tmp['pag']['contributor']);
				(!isset($tmp['pag']['subscriber'])) ?	delete_option('capa_protect_pag_role_subscriber')	:	update_option('capa_protect_pag_role_subscriber'	,$tmp['pag']['subscriber']);
				(!isset($tmp['pag']['visitor'])) 	?	delete_option('capa_protect_pag_anonymous')			:	update_option('capa_protect_pag_anonymous'			,$tmp['pag']['visitor']);
			}else{
				delete_option('capa_protect_pag_role_editor');
				delete_option('capa_protect_pag_role_author');
				delete_option('capa_protect_pag_role_contributor');
				delete_option('capa_protect_pag_role_subscriber');
				delete_option('capa_protect_pag_anonymous');
			}

		// Show Update Message
			echo '<div id="message" class="updated fade"><p><strong>'.__('Role Settings saved.','capa').'</strong></p></div>';

		}

		if($_POST['submit'] == 'Update User Options'){

			if(isset($_POST['capa_protect_cat_anonymous']) && is_array($_POST['capa_protect_cat_anonymous'])){
				foreach($_POST['capa_protect_cat_anonymous'] as $id=>$value){
					$tmp['capa_protect_cat_anonymous'][$value] = TRUE;
				}

				update_option('capa_protect_cat_anonymous',$tmp['capa_protect_cat_anonymous']);
			}else{
				delete_option("capa_protect_cat_anonymous");
			}

			if(isset($_POST['capa_protect_pag_anonymous']) && is_array($_POST['capa_protect_pag_anonymous'])){
				foreach($_POST['capa_protect_pag_anonymous'] as $id=>$value){
					$tmp['capa_protect_pag_anonymous'][$value] = TRUE;
				}

				update_option('capa_protect_pag_anonymous',$tmp['capa_protect_pag_anonymous']);
			}else{
				delete_option("capa_protect_pag_anonymous");
			}

			
			if(isset($_POST['capa_protect_show_padlock_on_private_posts']) && $_POST['capa_protect_show_padlock_on_private_posts'] == 'on'){
				update_option('capa_protect_show_padlock_on_private_posts', true);
			}else{
				update_option('capa_protect_show_padlock_on_private_posts', false);
			}

		// Show Update Message
			echo '<div id="message" class="updated fade"><p><strong>'.__('User Settings saved.','capa').'</strong></p></div>';

		}


		if($_POST['submit'] == 'Send Help'){

			if(function_exists('wp_mail')){
			// For Debug
				$headers  = 'From: '.get_option('blogname').' <'.$_POST['sender'].'>' . "\r\n";
				$headers .= 'Cc: '.$_POST['sender']. "\r\n";

				$message = $_POST['information']."\n\n".$_POST['comment'];

				$return = wp_mail('debug-capa@smatern.de', $_POST['titel'], $message, $headers);
			}else{
				$return = FALSE;
			}

				if($return){
					// Show Sent Message
						echo '<div id="message" class="capa-sent updated fade"><p><strong>'.__('Message sent.','capa').'</strong></p></div>';
				}else{
					// Show Error Message
						echo '<div id="message" class="capa-error updated fade"><p><strong>'.__('Message wasn&#39;t sent. Please try again or send by yourself.','capa').'</strong></p></div>';
				}
		}


	}

	function capa_find_lvl($id,$array){
		$lvl = -1;

		while($id != 0){
			foreach($array as $key => $value){
				if($key == $id){
					$lvl++;
					$id = $value;
				}
			}
		}

		return $lvl;
	}
?>
