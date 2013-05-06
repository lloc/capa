<?php
/*
Plugin Name: CaPa Protect
Plugin URI: http://www.smatern.de/category/coding/capa/
Description: CaPa provides Category & Pages protection on a roles & user basis.
Version: 0.5.8.2
Author: S. Matern
Author URI: http://www.smatern.de
*/

load_plugin_textdomain('capa', false, dirname(plugin_basename(__FILE__)).'/lang');

include_once('capa-options.php');
include_once('capa-user-edit.php');

// --------------------------------------------------------------------
global $capa_protect_private_message;

$capa_protect_private_message 						= get_option("capa_protect_private_message");
$capa_protect_post_policy 							= get_option('capa_protect_post_policy');
$capa_protect_default_private_message 				= __('Sorry, you do not have sufficient privileges to view this post.', 'capa');
$capa_protect_show_private_message					= get_option('capa_protect_show_private_message');
$capa_protect_show_private_categories 				= get_option('capa_protect_show_private_categories');
$capa_protect_show_private_pages 					= get_option('capa_protect_show_private_pages');
$capa_protect_show_padlock_on_private_posts 		= get_option('capa_protect_show_padlock_on_private_posts');
$capa_protect_show_padlock_on_private_categories 	= get_option('capa_protect_show_padlock_on_private_categories');
$capa_protect_show_comment_on_private_posts			= get_option('capa_protect_show_comment_on_private_posts');
$capa_protect_show_only_allowed_attachments			= get_option('capa_protect_show_only_allowed_attachments');
$capa_protect_show_unattached_files					= get_option('capa_protect_show_unattached_files');
$capa_protect_show_title_in_feeds					= get_option('capa_protect_show_title_in_feeds');
$capa_protect_advance_policy						= get_option('capa_protect_advance_policy');
$capa_protect_comment_policy						= get_option('capa_protect_comment_policy');
$capa_protect_default_comment_author				= __('Unknown','capa');

$access_pag_anonymous	= get_option("capa_protect_pag_anonymous");
$access_pag_default		= get_option("capa_protect_pag_default");
$access_cat_anonymous	= get_option("capa_protect_cat_anonymous");
$access_cat_default		= get_option("capa_protect_cat_default");

// Decloration of WP Variables
$wpc_all_page_ids		= get_all_page_ids();
$wpc_siteurl 			= get_option('siteurl');

class capa_protect {

	function dev_info($params1,$params2=false,$params3=false){
		var_dump($params1);
		var_dump($params2);
		var_dump($params3);

		return $params1;
	}


	function filter_object_item($terms, $object_ids, $taxonomies){
		global $current_user;

			switch($taxonomies){
				case '\'category\'':

					// Gets the current Post Categories Cache
						$_categories = wp_cache_get($object_ids,'category_relationships');

						if(is_array($_categories)){
							foreach($_categories as $id=>$cat){
								if(!capa_protect::user_can_access($id,$current_user,'category')){
									unset($_categories[$id]);
								}
							}

							wp_cache_replace($object_ids,$_categories,'category_relationships');
						}

				break;
			}

		return $terms;
	}


	function filter_menu_list_item($params){
		global $current_user, $capa_protect_show_private_categories, $capa_protect_show_private_pages;

		foreach($params as $item=>$values){

			if(!capa_protect::user_can_access($values->object_id,$current_user,$values->object)){
				switch($values->object){
					case 'page':
						if(!$capa_protect_show_private_pages){
							unset($params[$item]);
						}
					break;

					case 'category':
						if(!$capa_protect_show_private_categories){
							unset($params[$item]);
						}
					break;
				}
			}

		}


		return $params;
	}

	/**
	 * Filter the category items and/or shows padlock
	 *
	 * @uses $capa_protect_show_private_categories
	 * @uses $capa_protect_show_padlock_on_private_categories
	 * @uses $current_user
	 * @uses $wpc_siteurl
	 *
	 * @uses trailingslashit()
	 * @uses capa_protect::user_can_access()
	 *
	 * @param string $text
	 *
	 * @return string
	 */
	function filter_category_list_item($text,$category = null){

		global $capa_protect_show_private_categories, $capa_protect_show_padlock_on_private_categories;
		global $current_user, $wpc_siteurl, $wp_version;

		if ($current_user && isset($current_user->allcaps['manage_categories']) && !isset($current_user->caps['editor'])){
			return $text;
		}

		$all_category_ids = capa_protect::_get_taxonomy_ids();
			$all_category_ids = $all_category_ids['category'];

	# Make the changes
		$site_root = parse_url($wpc_siteurl);
		$site_root = trailingslashit($site_root['path']);

		if($capa_protect_show_private_categories){
			if($capa_protect_show_padlock_on_private_categories){
					foreach ($all_category_ids as $taxo_id=>$term_id){
						if (!capa_protect::user_can_access($term_id, $current_user,'cat')){
							$tmp_catname = ((int) $wp_version < 2.8) ? get_catname($term_id) : get_cat_name($term_id);
								$search[$taxo_id] = "#>".$tmp_catname."<#";
								$replace[$taxo_id] = '><img src="' . $site_root .'wp-content/plugins/capa/img/padlock.gif" height="10" width="10" valign="center" border="0"/> '. $tmp_catname.'<';
						}
					}
				// In the case user see all categories but padlock is active
				// $search will be empty
					return (count($search) > 0) ? preg_replace($search,$replace,$text) : $text;


			}
			return $text;
		}
		return $text;
	}


	/**
	 * Filter the Page items
	 *
	 * @uses $access_pag_anonymous
	 * @uses $access_pag_default
	 * @uses $wpc_all_page_ids
	 * @uses $capa_protect_show_private_pages
	 *
	 * @uses wp_get_current_user()
	 * @uses get_option()
	 *
	 * @return array
	 */
	function filter_page_list_item(){
		global $access_pag_anonymous, $access_pag_default, $capa_protect_show_private_pages, $wpc_all_page_ids;

	// Show Private Pages
		if($capa_protect_show_private_pages){
			return array();
		}

		$current_user = wp_get_current_user();
		$current_role = implode('',$current_user->roles);

		$excludes_page = $wpc_all_page_ids;  // If the DB contain no Data. All Pages will be excludes
	
		if ($current_user && isset($current_user->allcaps['manage_categories']) && !isset($current_user->caps['editor'])){
			return array();
		}

			if ($current_user->id == 0){
				$user_access_page_check = $access_pag_anonymous;
			}else{
				$user_access_page_check = get_option("capa_protect_pag_user_".$current_user->id);
			}

			if(empty($user_access_page_check)){
				if($current_role){
					$user_access_page_check = get_option("capa_protect_pag_role_".$current_role);
				}else{
					$user_access_page_check	= $access_pag_default;
				}
			}

			if(is_array($user_access_page_check)){
				$tmp['all_pages'] = $wpc_all_page_ids;

				foreach($user_access_page_check as $check=>$id){
					$tmp_id = array_search($check,$tmp['all_pages']);
					if($tmp_id !== FALSE){
						unset($tmp['all_pages'][$tmp_id]);
					}
				}

				$tmp['all_pages'] = array_flip($tmp['all_pages']);
				$excludes_page = array_keys($tmp['all_pages']);
			}

			return $excludes_page;

	}


	/**
	 * Checks the right of an signle post
	 *
	 * @uses $current_user
	 * @uses $capa_protect_post_policy
	 *
	 * @uses wp_get_post_categories()
	 * @uses get_post()
	 * @uses capa_protect::user_can_access()
	 *
	 * @param string $postid
	 *
	 * @return bool
	 */
	function post_should_be_hidden($postid){
		if (!isset($postid))
			return true;

		global $current_user, $capa_protect_post_policy;

		// Bad! To much queries
		// find a better way
		$post_categories = wp_get_post_categories($postid);
		$post_val		 = get_post($postid);

	// in the case there is no post (null)
		if(is_null($post_val))
			return true;

		$post_cat_pa	 = $post_val->post_parent;

		switch($post_val->post_type){
			case 'nav_menu_item':
				return FALSE;
			break;

			case 'page':
			default:
			
				// CATEGORY
					if ($capa_protect_post_policy != 'hide'){
					// Page
						if ($post_val->post_type == "page"){
							if (capa_protect::user_can_access($postid, $current_user,'pag')){
								return false;
							}
						}

					// Show a Patlock
						foreach ($post_categories as $post_category_id){
							if (capa_protect::user_can_access($post_category_id, $current_user,'cat')){
								return false;
							}
						}

						return true;
					} else {
					// Bastion ( Show or not Show )
					// Check Up ~ Provsional Info: There is a previous Entry (?) //
						if ($post_val->post_type == "page"){
							if (!capa_protect::user_can_access($postid, $current_user,'pag')){
								return true;
							}
						}else{
						// Category Post
							return !capa_protect::user_can_access($post_categories, $current_user,'cat',$post_cat_pa);
						}

					}

			break;
		}



	}


	/**
	 * Gives TRUE/FALSE for users rights for spezific request
	 *
	 * @uses $post
	 * @uses $access_pag_anonymous
	 * @uses $access_pag_default
	 * @uses $access_cat_anonymous
	 * @uses $access_cat_default
	 *
	 * @uses get_option()
	 * @uses wp_get_post_cats()
	 *
	 * @param string $val_id
	 * @param array object $user
	 * @param string $kind Select between 'pag' / 'cat'
	 * @param string $parent_id
	 *
	 * @return bool
	 */
	function user_can_access($val_id, $user, $kind, $parent_id=''){
		global $post;

		if ($user && isset($user->allcaps['manage_categories'])  && !isset($user->caps['editor']))
			return true;

		global $access_pag_anonymous, $access_pag_default, $access_cat_anonymous, $access_cat_default;

		switch($kind) {

		case 'pag':
		case 'page':

			if ($user->id == 0){
				$user_access_page_check = $access_pag_anonymous;
			}else{
				$user_access_page_check = get_option("capa_protect_pag_user_{$user->id}");
			}

			if (empty($user_access_page_check)){
				// Group Settings
					$tmp_caps = implode('', array_keys($user->caps));
					$user_access_page_check = get_option("capa_protect_pag_role_{$tmp_caps}");
			}


			// Default Setting
			if (empty($user_access_page_check)){
				$user_access_page_check	= $access_pag_default;
			}

			if (isset($user_access_page_check[$val_id])){
				return true;
			}else{
				return false;
			}
		
			$user_access_pag_check = "";
			break 1;	

		case 'cat':
		case 'category':

		// parent id check for attachment
			if( is_attachment() ){
				$access_post	= wp_get_post_cats(1,$parent_id);
				$val_id			= $access_post[0];
			}

			if($user->id == 0){
				$user_access_category_check	= $access_cat_anonymous;
			}else{
				$user_access_category_check = get_option("capa_protect_cat_user_{$user->id}");
			}

		// User Settings
				if (empty($user_access_category_check)){
					// Group Settings
						$tmp_caps = implode('',array_keys($user->caps));
						$user_access_category_check = get_option("capa_protect_cat_role_{$tmp_caps}");
				}

				// Default Setting
					if(empty($user_access_category_check)){
						$user_access_category_check = $access_cat_default;
					}

			$return = FALSE;

			if($user_access_category_check !== FALSE){
				if(is_array($val_id)){
					foreach($val_id as $key=>$id){
						if(array_key_exists($id,$user_access_category_check)){
							$return = TRUE;
						}
					}
				}else{
					if(array_key_exists($val_id,$user_access_category_check)){
						$return = TRUE;
					}
				}
			}

			return $return;

			$user_access_category_check = "";
			break 1;
		}

	}


	/**
	 * get/shows the private message ( custome message )
	 *
	 * @uses $post
	 * @uses $capa_protect_private_message
	 * @uses $capa_protect_default_private_message
	 *
	 * @return string
	 */
	function get_private_message() {
		global $post;
		global $capa_protect_private_message, $capa_protect_default_private_message;

		$message = $capa_protect_private_message;

		if ($message == null){
			$message = $capa_protect_default_private_message;
		}
		
		return $message;
	}


	/**
	 * gets users capa settings
	 *
	 * @uses $access_cat_anonymous
	 * @uses $access_cat_default
	 *
	 * @uses get_option
	 *
	 * @param array object $current_user
	 *
	 * @return array
	 */
	function get_capa_protect_for_user($current_user){

		global $access_cat_anonymous, $access_cat_default;

		if ($current_user && isset($current_user->allcaps['manage_categories']) && !isset($current_user->caps['editor'])){
			return true;
		}

		$user_id = ($current_user) ? $current_user->id : 0;

		if($user_id == 0){
			return $access_cat_anonymous;
		}

		$visible = get_option("capa_protect_cat_user_${user_id}");

		if (empty($visible)){

			$visible = get_option("capa_protect_cat_role_".implode('',$current_user->roles));

			if(empty($visible)){
				$visible = $access_cat_default;	
			}
		}


		return $visible;
	}


	/**
	 * sets users capa settings
	 *
	 * @uses update_option
	 *
	 * @param string $user_id
	 * @param array $value
	 * @param string $kind 
	 *
	 * @return none
	 */
	function set_access_for_user($user_id, $value, $kind) {
	
		switch($kind) {
			case "cat":
				if ($value){
					update_option("capa_protect_cat_user_${user_id}", $value);
				}else{
					update_option("capa_protect_cat_user_${user_id}", '0');
				}
			break;

			case "pag":
				if ($value){
					update_option("capa_protect_pag_user_${user_id}", $value);
				}else{
					update_option("capa_protect_pag_user_${user_id}", '0');
				}
			break;
		}

	}

	/**
	 * Add SQL Addition to show only posts
	 *
	 * @uses current_user
	 * @uses capa_protect_show_title_in_feeds
	 * @uses capa_protect_post_policy
	 *
	 * @uses capa_protect::get_value_categeories()
	 * @uses capa_protect::get_valid_pages()
	 *
	 * @param string $sql
	 *
	 * @return string SQL Addiction
	 */
	function filter_posts($sql=FALSE){
		global $capa_protect_show_only_allowed_attachments, $capa_protect_show_unattached_files;

		if(strpos($sql,'attachment') && !$capa_protect_show_only_allowed_attachments && !$capa_protect_show_unattached_files )
			return $sql;

		global $current_user;
			if ($current_user && isset($current_user->allcaps['manage_categories']) && !isset($current_user->caps['editor'])){
				return $sql;
			}

		global $capa_protect_show_title_in_feeds, $capa_protect_post_policy;

	// Is Feed or User/Visitor got the right to see the titel / message
		if ((is_feed() && $capa_protect_post_policy != 'hide' && $capa_protect_post_policy ) || ( $capa_protect_post_policy == 'show title' || $capa_protect_post_policy == 'show message')){
		// Only Frontend, Backend(wp-admin) no visual change
			if(!strpos($_SERVER['REQUEST_URI'], '/wp-admin/')){
				return $sql;
			}
		}

		global $wpdb;

		$inclusive		= capa_protect::get_value_categories(TRUE);
		$inclusive_page	= capa_protect::get_valid_pages();

		(sizeof($inclusive) <= 0) ? $inclusive = array(0) : $inclusive = $inclusive;

		$query = " SELECT ID FROM $wpdb->posts INNER JOIN" .
			" $wpdb->term_relationships ON ( $wpdb->posts.ID = $wpdb->term_relationships.object_id )" .
			" WHERE 0 = 1";

			$query .= " OR $wpdb->term_relationships.term_taxonomy_id in ( ".implode(',',$inclusive)." ) AND $wpdb->posts.post_type NOT IN ('revision','page')";

		$res = mysql_query($query) or die(mysql_error());
		$ids = array();

		while ($row = mysql_fetch_assoc($res)){
			$ids[] = "'" . $row['ID'] . "'";
		}

	# Array Post Check ~ Include already the ID of Pages?
		if(is_array($inclusive_page) && sizeof($inclusive_page) > 0){
			$tmp_count = count($inclusive_page)-1;	

			for($i=0;$i<=$tmp_count;$i++){
				$tmp_var = "'".$inclusive_page[$i]."'";
				if(array_search($tmp_var,$ids) != 1){
					array_push($ids,"'".$inclusive_page[$i]."'");
				}
			}
		}

	// In case sql is for the attachments
		$fld_id = (strpos($sql, "post_type = 'attachment'")) ? 'post_parent' : 'ID';

		if(sizeof($ids) > 0){
			$sql .= " AND ".$fld_id." IN (".implode(",",$ids).( ($capa_protect_show_only_allowed_attachments && $capa_protect_show_unattached_files) ? ', 0' : ', -1' ).")";
		}else{
			$sql .= " AND ".$fld_id." IN ('".(($capa_protect_show_unattached_files) ? "0" : "-1")."')";
		}

		return $sql;
	}


	/**
	 * To show or not to show the post title
	 *
	 * @uses post
	 * @uses capa_protect_post_policy
	 * @uses current_user
	 *
	 * @uses capa_protect::post_should_be_hidden()
	 *
	 * @param string $param
	 *
	 * @return string
	 */
	function filter_post_title($param,$post=FALSE){

		if(!$post){
			global $post;
				if(is_null($post)){
					return $param;
				}
		}else{
			$post = (object) array('ID'=>$post);
		}

		global $capa_protect_post_policy, $current_user;

		if ($current_user && isset($current_user->allcaps['manage_categories'])  && !isset($current_user->caps['editor'])){
			return $param;
		}

		if(capa_protect::post_should_be_hidden($post->ID)){
			if($capa_protect_post_policy !== FALSE && $capa_protect_post_policy != 'hide'){
				return $param;
			}else{
				return __('No Title','capa');
			}
		}else{
			return $param;
		}

	}


	/**
	 * To show or not to show the post content
	 *
	 * @uses post
	 * @uses capa_protect_post_policy
	 *
	 * @uses capa_protect::post_should_be_hidden()
	 * @uses capa_protect::get_private_message()
	 *
	 * @param string $text
	 *
	 * @return string
	 */
	function filter_content($text){
		global $post, $capa_protect_post_policy;

		if($capa_protect_post_policy != 'show message'){

			if(capa_protect::post_should_be_hidden($post->ID)){
				if($capa_protect_post_policy !== FALSE && $capa_protect_post_policy == 'show title'){
					$text = capa_protect::get_private_message();
				}
			}
		}

		return $text;
	}


	/**
	 * To show or not to show the post comment
	 *
	 * @uses current_user
	 * @uses capa_protect_comment_policy
	 * @uses capa_protect_show_comment_on_private_posts
	 *
	 * @uses capa_protect::post_should_be_hidden()
	 *
	 * @param string $text
	 *
	 * @return string
	 */
	function filter_comment($params, $postID=FALSE, $return=0){
		global $current_user;
			if ($current_user && isset($current_user->allcaps['manage_categories']) && !isset($current_user->caps['editor'])){
				return $params;
			}

		$return		= (is_array($params)) ? array() : $return;
		$post_id	= (is_numeric($postID)) ? $postID : ((isset($params->comment_post_ID) && is_numeric($params->comment_post_ID)) ? $params->comment_post_ID : FALSE);

		if(!$post_id){
			global $post;
			$post_id = (is_null($post)) ? 0 : $post->ID;
		}

		global $capa_protect_comment_policy, $capa_protect_show_comment_on_private_posts;

		if($capa_protect_comment_policy == 'hide' && !$capa_protect_show_comment_on_private_posts)
			return $return;

// params ist ein array, einzelne comment abfrage
// hoere Performens abfrage?
// SQL Query und Cache kontrollieren

	// Post ID exists?
		if((int) $postID > 0){
			if(capa_protect::post_should_be_hidden($post_id)){
				if(is_numeric($capa_protect_show_comment_on_private_posts)){
					if($capa_protect_comment_policy != 'hide'){
						return $params;
					}else{
						return $return;
					}
				}else{
					return $return;
				}
			}else{
				if($capa_protect_comment_policy != 'hide' && $capa_protect_comment_policy !== FALSE){
					return $params;
				}else{
					return $return;
				}
			}
		}else{

			if(is_array($params)){
				$_posts = FALSE;

				foreach($params as $obj){
					$_posts[] = $obj->comment_post_ID;
					$_comments[$obj->comment_post_ID][] = $obj;
				}

				$params = array();

					if(is_array($_posts)){
						foreach($_posts as $obj){
							if(!capa_protect::post_should_be_hidden($obj)){
								$params = $params + $_comments[$obj];
							}
						}
					}
			}

		}

		return $params;
	}



	/**
	 * To show or not to show the comment body(content)
	 *
	 * @uses current_user
	 * @uses post
	 * @uses capa_protect_comment_policy
	 * @uses capa_protect_show_comment_on_private_posts
	 *
	 * @uses capa_protect::post_should_be_hidden()
	 * @uses capa_protect::get_private_message()
	 *
	 * @param string $param
	 *
	 * @return string
	 */
	function filter_comment_body($param){
		global $current_user, $post;
		global $capa_protect_comment_policy, $capa_protect_show_comment_on_private_posts;

		// Hm, POst oder Comemnt
		// wird post hier gebraucht?
		if(!$post){
			global $comment;
			$ID = $comment->ID;
		}else{
			$ID = $post->ID;
		}

		if ($current_user && isset($current_user->allcaps['manage_categories'])  && !isset($current_user->caps['editor'])){
			return $param;
		}

		if(capa_protect::post_should_be_hidden($ID)){
			if(is_numeric($capa_protect_show_comment_on_private_posts)){
				if($capa_protect_comment_policy == 'show message' or $capa_protect_comment_policy == 'all'){
					return $param;
				}else{
					return capa_protect::get_private_message();
				}
			}else{
				return capa_protect::get_private_message();
			}
		}else{
			if($capa_protect_comment_policy == 'show message' or $capa_protect_comment_policy == 'all'){
				return $param;
			}else{
				return capa_protect::get_private_message();
			}
		}
	}


	/**
	 * To show or not to show the comment author
	 *
	 * @uses capa_protect_show_comment_on_private_posts
	 * @uses capa_protect_default_comment_author
	 * @uses capa_protect_comment_policy
	 * @uses post
	 * @uses $post
	 *
	 * @uses capa_protect::post_should_be_hidden()
	 *
	 * @param string $param
	 *
	 * @return string
	 */
	function filter_comment_author($param){
		global $capa_protect_show_comment_on_private_posts, $capa_protect_default_comment_author, $capa_protect_comment_policy;
		global $post;

		// ADMIN AREA QUESTION
		global $current_user;

		if ($current_user && isset($current_user->allcaps['manage_categories'])  && !isset($current_user->caps['editor'])){
			return $param;
		}

		if($post && capa_protect::post_should_be_hidden($post->ID)){
			if(is_numeric($capa_protect_show_comment_on_private_posts)){
				if($capa_protect_comment_policy == 'show name' or $capa_protect_comment_policy == 'all'){
					return $param;
				}else{
					return $capa_protect_default_comment_author;
				}
			}else{
				return $capa_protect_default_comment_author;
			}
		}else{
			if($capa_protect_comment_policy == 'show name' or $capa_protect_comment_policy == 'all'){
				return $param;
			}else{
				return $capa_protect_default_comment_author;
			}
		}

/**
	TODO Editor Comment auch unsichtbar machen?
*/

	}


	/**
	 * To show or not to show the page
	 *
	 * @uses capa_protect::post_should_be_hidden()
	 *
	 * @param array $param
	 *
	 * @return array
	 */	
	function filter_pages($params){
		global $capa_protect_show_private_pages;

		if($capa_protect_show_private_pages)
			return $params;

		foreach($params as $id=>$page){
			if(capa_protect::post_should_be_hidden($page->ID)){
				unset($params[$id]);
			}
		}

		return (isset($params)) ? $params : array();
	}


	/**
	 * get the allows/disallow ( depends on modus ) categories
	 *
	 * @uses current_user
	 *
	 * @uses capa_protect::_get_taxonomy_ids()
	 * @uses capa_protect::get_capa_protect_for_user()
	 *
	 * @param bool $moduls
	 * @param string $typ
	 *
	 * @return array
	 */
	function get_value_categories($modus=TRUE, $typ='taxo', $inclusions=array(0), $capa_all_category_ids = array()) {
		global $current_user;

		$all_category_tax_ids = capa_protect::_get_taxonomy_ids();
		$all_category_tax_ids = (isset($all_category_tax_ids['category'])) ? array_flip($all_category_tax_ids['category']) : array();

		if ($current_user && isset($current_user->allcaps['manage_categories']) && !isset($current_user->caps['editor'])){
			return $all_category_tax_ids;
		}

			$get_valid_category_check = capa_protect::get_capa_protect_for_user($current_user);

		if(is_array($get_valid_category_check)){

				switch($modus){
					case TRUE:
						foreach($get_valid_category_check as $key=>$value){
							$tmp[$all_category_tax_ids[$key]] = $key;
						}
					break;

					case FALSE:
						foreach($get_valid_category_check as $key=>$value){
							unset($all_category_tax_ids[$key]);
						}

						$tmp = array_flip($all_category_tax_ids);
					break;
				}

			// Last Check remove TRUE Values
			// But, it shouldn't happen -.-;
#				foreach($tmp as $obj=>$value){
#					if(is_bool($value)){
#						unset($tmp[$obj]);
#					}
#				}

			// Taxonomy ID oder Term ID verlangt? Default 'taxo'
				$tmp = ($typ != 'taxo') ? $tmp : array_flip($tmp);

				$inclusions = $tmp;

				(empty($inclusions) || in_array('',$inclusions)) ? $inclusions = array(0) : NULL ;
		}

		return	$inclusions;

	}


	/**
	 * get the allows/disallow ( depends on modus ) tags
	 *
	 * @uses wpdb
	 *
	 * @uses capa_protect::get_value_categories()
	 *
	 * @param bool $moduls
	 *
	 * @return array
	 */
	function get_value_tags($modus=TRUE, $ids=array()) {
		global $wpdb;

		switch($modus){
			case TRUE:
				$categories = capa_protect::get_value_categories(TRUE,'taxo');
				$dis_cat	= capa_protect::get_value_categories(FALSE,'taxo');

				$where_in	= ' IN ('.implode(',',$categories).') ';
				$where_not	= ' AND '.$wpdb->term_relationships.'.term_taxonomy_id NOT IN ('.implode(',',$dis_cat).') ';
			break;

			case FALSE:
				$categories = capa_protect::get_value_categories(FALSE,'taxo');

				$where_in	= ' NOT IN ('.implode(',',$categories).') ';
				$where_not	= ' AND '.$wpdb->term_relationships.'.term_taxonomy_id NOT IN ('.implode(',',$categories).') ';
			break;
		}

		$sql	= '
			SELECT '.$wpdb->term_relationships.'.object_id, '.$wpdb->term_relationships.'.term_taxonomy_id
			FROM '.$wpdb->term_relationships.' 
			WHERE 
				'.$wpdb->term_relationships.'.object_id IN ( 
					SELECT '.$wpdb->term_relationships.'.object_id
					FROM '.$wpdb->term_relationships.' 
					WHERE '.$wpdb->term_relationships.'.term_taxonomy_id '.$where_in.' )
				'.$where_not.'
			GROUP BY '.$wpdb->term_relationships.'.term_taxonomy_id';

		$tags	= $wpdb->get_results($sql);

			foreach($tags as $tag){
				$ids[] = $tag->term_taxonomy_id;
			}

		return $ids;
	}


	/**
	 * get the allows pages
	 *
	 * @uses access_pag_anonymous
	 * @uses access_pag_default
	 *
	 * @uses wp_get_current_user()
	 * @uses get_option()
	 *
	 * @return array
	 */
	function get_valid_pages($inclusions=FALSE){

		global $access_pag_anonymous, $access_pag_default;

		$current_user = wp_get_current_user();

		if ($current_user && isset($current_user->allcaps['manage_categories']) && !isset($current_user->caps['editor'])){
			return false;
		}

			if ($current_user->id == 0){
				$user_access_page_check = $access_pag_anonymous;
			}
			else{
				$current_user_role = 'capa_protect_pag_role_'.implode('',$current_user->roles);
				$user_access_page_check = get_option("capa_protect_pag_user_{$current_user->id}");
				$user_access_page_check = ($user_access_page_check) ? $user_access_page_check : get_option($current_user_role);
			}

			if (empty($user_access_page_check)){
				$user_access_page_check	= $access_pag_default;
			}

			if(is_array($user_access_page_check)){
				$inclusions = array_keys($user_access_page_check,TRUE);
			}

			return $inclusions;
	}


	// --------------------------------------------------------------------
	// If the user saves a post in a category or categories from which they are
	// restricted, remove the post from the restricted category(ies).  If there
	// are no categories left, save it as Uncategorized with status 'Saved'.
	/**
		TODO Hm, necessary?
	*/
	function verify_category($post_ID) {
		global $wpdb;
	
		$postcats = $wpdb->get_col("SELECT term_taxonomy_id FROM $wpdb->term_relationships WHERE object_id = $post_ID ORDER BY term_taxonomy_id");
		$exclusions = capa_protect::get_value_categories(FALSE);

		if (count($exclusions) && $exclusions != TRUE) {
			$exclusions = implode(", ", $exclusions);
			$wpdb->query("DELETE FROM $wpdb->term_relationships WHERE object_id = $post_ID AND term_taxonomy_id IN ($exclusions)");
			$good_cats = $wpdb->get_var("SELECT COUNT(*) FROM $wpdb->term_relationships WHERE object_id = $post_ID");

			if (0 == $good_cats) {
				$wpdb->query("INSERT INTO $wpdb->term_relationships (`object_id`, `term_taxonomy_id`) VALUES ($post_ID, 1)");
				$wpdb->query("UPDATE $wpdb->posts SET post_status = 'draft' WHERE ID = $post_ID");
			}
		}
	}


	// List of Category at Admin Area ( Edit / New Post )
	function filter_wp_admin_category_list($category){

		global $capa_protect_advance_policy;

		if($capa_protect_advance_policy){
			global $current_user;

			if ($current_user && isset($current_user->allcaps['manage_categories'])){
				return $category;
			}
	
			global $current_user;
			$count = count($category);

			for($e=0;$e<=$count;$e++){
				if(!capa_protect::user_can_access($category[$e]['cat_ID'],$current_user,'cat')){	
					if(count($category[$e]['children']) > 0){
						$count_children = count($category[$e]['children']);
						for($f=0;$f<=$count_children;$f++){
							if(!empty($category[$e]['children'][$f]['cat_ID'])){
								if(capa_protect::user_can_access($category[$e]['children'][$f]['cat_ID'],$current_user,'cat')){
									$tmp_main = array(	'children'	=>array(),
														'cat_ID'	=>$category[$e]['children'][$f]['cat_ID'],
														'checked'	=>$category[$e]['children'][$f]['checked'],
														'cat_name'	=>$category[$e]['children'][$f]['cat_name']);
									array_push($category,$tmp_main);
								}
							}
						}					
					}
					unset($category[$e]);
				}
			}
		}

		return $category;
	}

	// --------------------------------------------------------------------
	function filter_terms($_terms,$_taxonomy=FALSE){

		global $current_user, $capa_protect_show_private_categories;	

			if ($current_user && isset($current_user->allcaps['manage_categories']) && !isset($current_user->caps['editor'])){
				return $_terms;
			}

			if ($capa_protect_show_private_categories){
				return $_terms;
			}

			$allow_tags		= capa_protect::get_value_tags(TRUE);
			$category_taxo	= capa_protect::get_value_categories(TRUE);

			if(!$_taxonomy){
							sort($_terms);
							reset($_terms);
				$count	=	count($_terms);

				for($e=0;$e<=$count;$e++){
				// Remove Only taxonomy kind Category
					if(isset($_terms[$e])){
						if($_terms[$e]->taxonomy == 'category' && strpos($_SERVER['REQUEST_URI'], '/wp-admin/') != true){
							if(!in_array($_terms[$e]->term_taxonomy_id, $category_taxo)){
								unset($_terms[$e]);
							}
						}elseif($_terms[$e]->taxonomy == 'post_tag'){
							if(!in_array($_terms[$e]->term_taxonomy_id, $allow_tags)){
								unset($_terms[$e]);
							}
						}
					}
				}
			}else{
				if($_terms->parent > 0){
					if(!array_key_exists($_terms->parent, $category_taxo)){
						$_terms->parent = 0;
					}
				}
			}

		return $_terms;
	}


	/**
	 * restrict the total number of Categories, Tags for the Widget "Right now"
	 *
	 * @uses $wpdb
	 * @uses $current_user
	 * @uses $capa_protect_show_private_categories
	 *
	 * @uses capa_protect::get_value_categories()
	 * @uses capa_protect::get_value_tags()
	 *
	 * @return string
	 */
	function sql_terms_exclusions($return=''){
		global $current_user, $capa_protect_show_private_categories;

		if($capa_protect_show_private_categories && strpos($_SERVER['REQUEST_URI'], '/wp-admin/') != true){
			return "";
		}

		if($current_user && isset($current_user->allcaps['manage_categories']) && !isset($current_user->caps['editor'])){
			return "";
		}

		$cats	= capa_protect::get_value_categories();
		$tags	= capa_protect::get_value_tags();

			$objects = array_merge($cats,$tags);

				$return .= (array_sum($objects) <= 0) ? ' AND tt.term_taxonomy_id IN (0) ' : ' AND tt.term_taxonomy_id IN ('.implode(',',$objects).')';

		return $return;
	}


	function cleanup_capa_help($params){
		$search_string = '_<h5>' . __('Other Help','capa').'(.*)</div>{1}_';
	// Remove Other Help from Capa Wordpress Adminpages
		return preg_replace($search_string, '', $params);
	}
	
	// --------------------------------------------------------------------
	/**
		CaPa INTERN FUNCTIONS
	**/
	// --------------------------------------------------------------------


	/**
	 * Create an Array with term_id and term_taxonomy_id ( prevent mix up with term/taxonomy IDs )
	 *
	 * @uses $wpdb
	 *
	 * @return array
	 */
	function _get_taxonomy_ids($taxonomy_ids = array()){
		global $wpdb;

			$query = " SELECT term_id,term_taxonomy_id,taxonomy FROM $wpdb->term_taxonomy ";
			$res = mysql_query($query) or die(mysql_error());

				while ($row = mysql_fetch_assoc($res)){
					$taxonomy_ids[$row['taxonomy']][$row['term_taxonomy_id']] = $row['term_id'];
				}

		return $taxonomy_ids;
	}


	/**
	 * Add SQL Addition to show only comments of allow post
	 *
	 * @uses $wpdb
	 * @uses capa_protect::get_value_categories()
	 *
	 * @return string
	 */
	function filter_comment_feed($param){
		global $capa_protect_comment_policy;

		// In the Case comments are hidden
			if($capa_protect_comment_policy == 'hide' || $capa_protect_comment_policy === FALSE){
				return ' WHERE comment_post_ID IN (0)';
			}

		global $wpdb;

			$valid_categories = capa_protect::get_value_categories(TRUE);

			$query	= '	SELECT p.ID, p.post_type, tr.object_id, tr.term_taxonomy_id 
						FROM '.$wpdb->posts.'  as p
							LEFT JOIN '.$wpdb->term_relationships.' as tr ON tr.object_id = p.ID
						WHERE p.post_type = "post" AND tr.term_taxonomy_id IN ('.implode(',',$valid_categories).')';

			$res	= mysql_query($query) or die(mysql_error());

			while ($row = mysql_fetch_assoc($res)){
				$post_ids[] = $row['ID'];
			}

		if(is_array($post_ids)){
			$return = ' IN ("'.implode(',',$post_ids).'")';
		}elseif(is_numeric($post_id)){
			$return = ' = '.$post_id;
		}else{
			$return = ' = 0';
		}

		$return = ' WHERE comment_post_ID '.$return;

		return $return;
	}

/**
	TESTAREA FOR NEW FUNCTION
**/
	function filter_get_com($param){
		if($param && capa_protect::post_should_be_hidden($param->comment_post_ID)){
#			var_dump($param);
#			echo "<br><br>";
#			$param = (object) array('comment_post_ID'=>NULL);
			return NULL;
		}
		return $param;
	}


	function filter_manage($param){

#		global $current_user;

#			if ($current_user && isset($current_user->allcaps['manage_categories']) && !isset($current_user->caps['editor'])){
#				return $param;
#			}

#		global $capa_protect_comment_policy;
#		global $capa_protect_show_comment_on_private_posts;
#		global $comment;

#			foreach ( $param as $column_name => $column_display_name ) {
#				if($column_name =='comment'){
#					if(capa_protect::post_should_be_hidden($comment->comment_post_ID)){
#						if(!is_null($comment)){
#							unset($param);
#						}
#					}
#				}
#			}

#			return $param;
#		echo $comment->comment_post_ID."<br>";
#		var_dump($param);
	}


	function filter_role_has_cap($params){
#		var_dump($params);
#		return FALSE;
	}
	

	function filter_user_has_cap($params,$caps,$args){

#		if(in_array('6',$args)){
#			$params['editor'] = FALSE;
#		}
#		var_dump($params);
		return $params;
	}


	function _admin_the_post_parent($post){
		$allow_pages = capa_protect::get_valid_pages();

		if(is_array($allow_pages)){
			// Filter disallowed Pages
				if(!in_array($post->post_parent,$allow_pages)){
					$post->post_parent = 0;
				}
		}

		return $post;
	}

/**
	TODO Check mit Rope Scoper
*/

	/**
	 * Alternate diverse SQL Queries
	 *
	 * @uses $current_user
	 * @uses $capa_protect_comment_policy
	 * @uses $wpdb
	 *
	 * @uses capa_protect::filter_posts()
	 * @uses capa_protect::get_valid_categories()
	 * @uses capa_protect::get_valid_tags()
	 *
	 * @return string
	 */
	function filter_wpdb_query($param){

		global $current_user;

			if ($current_user && isset($current_user->allcaps['manage_categories']) && !isset($current_user->caps['editor'])){
				return $param;
			}

		global $wpdb, $capa_protect_comment_policy;

	// Code fuer WP < 3
		if( (int) $GLOBALS['wp_version'] != '3'){

			// SQLFILTER::function _wp_get_comment_list
			#	FROM $wpdb->comments USE INDEX (comment_date_gmt) WHERE
				if(strpos($param, "FROM $wpdb->comments USE INDEX (comment_date_gmt) WHERE") !== FALSE){

					if(strpos($param,'WHERE') !== FALSE && strpos(substr($param, strpos($param,'WHERE')),'comment_post_ID') === FALSE){
					// In Case Public/Allowed Comments are hidden 
						if($capa_protect_comment_policy == 'hide'){
							$allow_posts = ' comment_post_ID IN (0) AND';
						}else{
							$allow_posts = capa_protect::filter_posts();
							$allow_posts = str_replace('AND ID',' comment_post_ID',$allow_posts)." AND";
						}

						$param = str_replace('WHERE', 'WHERE'.$allow_posts, $param);

						return $param;
					}
				}

			// SQLFILTER::function wp_count_terms
			#	SELECT COUNT(*) 
			#	FROM $wpdb->term_taxonomy WHERE taxonomy = %s
				if(strpos($param, "SELECT COUNT(*) FROM $wpdb->term_taxonomy WHERE taxonomy") !== FALSE){
					$taxo = substr($param, strpos($param, 'taxonomy =')+12, -2);

					switch($taxo){
						case 'category':
							if(strpos($param,'term_taxonomy_id') === FALSE){
								$disallow_cat = capa_protect::get_value_categories(FALSE);
								$param .= ' AND term_taxonomy_id NOT IN ('.implode(',',$disallow_cat).')';
							}
						break;

						case 'post_tag':
							if(strpos($param,'term_taxonomy_id') === FALSE){
								$allow_tags	= capa_protect::get_value_tags();
								$param .= ' AND term_taxonomy_id IN ('.implode(',',$allow_tags).') ';
							}
						break;
					}


					return $param;

				}


			// SQLFILTER::function wp_dashboard_recent_comments
			// Code von WP < 3
			#	SELECT * 
			#	FROM $wpdb->comments 
			#	ORDER BY comment_date_gmt DESC LIMIT $start, 50
				if(strpos($param, "SELECT * FROM $wpdb->comments ORDER BY comment_date_gmt DESC LIMIT") !== FALSE){

					if(strpos($param,'ORDER') !== FALSE && strpos(substr($param, strpos($param,'WHERE')),'comment_post_ID') === FALSE){
					// In Case Public/Allowed Comments are hidden 
						if($capa_protect_comment_policy == 'hide'){
							$allow_posts = ' comment_post_ID IN (0)';
						}else{
							$allow_posts = capa_protect::filter_posts();
							$allow_posts = str_replace('AND ID',' comment_post_ID',$allow_posts);
						}

						$param = str_replace('ORDER', 'WHERE '.$allow_posts.' ORDER', $param);
					}

					return $param;
				}

		}



	// SQLFILTER::function _wp_get_comment_list
	#	SELECT *
	#	FROM $wpdb->comments c
	#	LEFT JOIN $wpdb->posts p ON c.comment_post_ID = p.ID 
	#	WHERE p.post_status != 'trash'
		if(strpos($param," FROM $wpdb->comments c LEFT JOIN $wpdb->posts p ON c.comment_post_ID = p.ID") !== FALSE){

			if(strpos($param,'WHERE') !== FALSE && strpos(substr($param, strpos($param,'WHERE')),'comment_post_ID') === FALSE){
			// In Case Public/Allowed Comments are hidden 
				if($capa_protect_comment_policy == 'hide'){
					$allow_posts = ' comment_post_ID IN (0) AND';
				}else{
					$allow_posts = capa_protect::filter_posts();
					$allow_posts = str_replace('AND ID',' comment_post_ID',$allow_posts)." AND";
				}

				$param = str_replace('WHERE', 'WHERE'.$allow_posts, $param);
			}

		}


	// SQLFILTER::function wp_count_comments
	#	SELECT comment_approved, COUNT( * ) AS num_comments
	#	FROM sm_blog_comments 
	#	GROUP BY comment_approved 
		if(strpos($param,'SELECT comment_approved, COUNT') !== FALSE && strpos($param,'WHERE') === FALSE){
			if($capa_protect_comment_policy == 'hide'){
				$allow_posts = ' WHERE comment_post_ID IN (0) ';
			}else{
				$allow_posts = capa_protect::filter_posts();
				$allow_posts = str_replace('AND ID',' WHERE comment_post_ID',$allow_posts)." ";
			}

			$param = str_replace('FROM '.$wpdb->comments, 'FROM '.$wpdb->comments.' '.$allow_posts, $param);
		}


	// SQLFILTER::function wp_get_recent_posts
	#	SELECT * 
	#	FROM $wpdb->posts 
	#	WHERE post_type = 'post' AND post_status IN ( 'draft', 'publish', 'future', 'pending', 'private' ) ORDER BY post_date DESC $limit
		if(strpos($param,"SELECT * FROM $wpdb->posts WHERE post_type = 'post' AND post_status IN ( 'draft', 'publish', 'future', 'pending', 'private' ) ORDER BY post_date DESC") !== FALSE){
			$recent_posts	= substr($param, (strpos($param,'LIMIT '))+6);
			$allow_posts	= capa_protect::filter_posts();

			$param = str_replace('AND post_status', $allow_posts.' AND post_status',$param);

			return $param;
		}


	// SQLFILTER::function wp_count_attachments
		if(strpos($param,'SELECT post_mime_type, COUNT( * ) AS num_posts') !== FALSE){
			global $capa_protect_show_only_allowed_attachments;

			if(!$capa_protect_show_only_allowed_attachments)
				return $param;

			$allow_posts = capa_protect::filter_posts();
			$allow_posts = str_replace('AND ID',' AND post_parent', $allow_posts);

			$param = str_replace('AND post_status', $allow_posts.' AND post_status',$param);

			return $param;
		}


	// SQLFILTER::function get_available_post_mime_types
		if(strpos($param, "SELECT DISTINCT post_mime_type FROM $wpdb->posts WHERE post_type = 'attachment'") !== FALSE){
			global $capa_protect_show_only_allowed_attachments;

			if(!$capa_protect_show_only_allowed_attachments)
				return $param;

			$allow_posts = capa_protect::filter_posts();
			$allow_posts = str_replace(' AND ID',' AND post_parent', $allow_posts);

			$param = $param.$allow_posts;

			return $param;
		}

	// SQLFILTER::function wp_count_posts
		if(strpos($param,'SELECT post_status, COUNT(') !== FALSE){
			$allow_posts = capa_protect::filter_posts();
			$allow_posts = str_replace('AND ID',' ID', $allow_posts);
							($allow_posts != '') ? $allow_posts = $allow_posts.' AND ' : NULL;

			if(strpos($param,'WHERE') !== FALSE && strpos($param,'ID') === FALSE){
				$param = str_replace('WHERE', 'WHERE'.$allow_posts,$param);
			}

			return $param;
		}


	// SQLFILTER::function &get_terms ( case of count )
	#	SELECT COUNT(*) FROM $wpdb->terms AS t 
	#	INNER JOIN $wpdb->term_taxonomy AS tt ON t.term_id = tt.term_id 
	#	WHERE tt.taxonomy IN (string) AND tt.term_taxonomy_id IN (int)
	//	DEV
		if(!isset($GLOBALS['wp_filter']['list_terms_exclusions']) && strpos($param, "SELECT COUNT(*) FROM $wpdb->terms AS t INNER JOIN $wpdb->term_taxonomy AS tt ON t.term_id = tt.term_id WHERE") !== FALSE){

			switch($param){
				case ( strpos($param,'category') !== FALSE):
					if(strpos($param,'term_taxonomy_id') === FALSE){
						$disallow_cat = capa_protect::get_value_categories(FALSE);
						$param .= ' AND term_taxonomy_id NOT IN ('.implode(',',$disallow_cat).')';
					}else{
						$disallow_cat = capa_protect::get_value_categories(TRUE);
						$param = str_replace('WHERE','WHERE tt.term_taxonomy_id IN ('.implode(',',$disallow_cat).') AND', $param);
					}
				break;

				case ( strpos($param,'post_tag') !== FALSE):
					if(strpos($param,'term_taxonomy_id') === FALSE){
						$allow_tags	= capa_protect::get_value_tags();
						$param .= ' AND term_taxonomy_id IN ('.implode(',',$allow_tags).') ';
					}else{
						$allow_tags	= capa_protect::get_value_tags();
						$param	= str_replace('WHERE','WHERE t.term_id IN ('.implode(',',$allow_tags).') AND', $param);
					}
				break;
			}

			return $param;
		}


	// SQLFILTER::function get_var ( case of unattached file count )
	#	SELECT COUNT( * ) FROM $wpdb->posts 
	#	WHERE post_type = 'attachment' AND post_status != 'trash' AND post_parent < 1
	//	DEV
		if(strpos($param, "SELECT COUNT( * ) FROM $wpdb->posts WHERE post_type = 'attachment' AND post_status != 'trash' AND post_parent < 1") !== FALSE){
			global $capa_protect_show_unattached_files;

			if(!$capa_protect_show_unattached_files){
				$param .= ' AND post_parent NOT IN (0)';
			}
		}


		return $param;
	}


}

function init_capa(){
// Standard Settings
	update_option('capa_protect_show_only_allowed_attachments',	TRUE);
}

// Clean Up Option DB
function unset_capa_protect_options(){
	if(!get_option('capa_protect_keep_options')){
		global $wpdb;

		$wpdb->query( "DELETE FROM $wpdb->options WHERE option_name LIKE 'capa_protect%'" );
	}
}

register_activation_hook(__FILE__, 'init_capa');
register_deactivation_hook(__FILE__, 'unset_capa_protect_options');


// --------------------------------------------------------------------
// DEV Fn & FILTERS
// --------------------------------------------------------------------

#	add_filter('role_has_cap',		array('capa_protect','dev_has'),10);

// any use for this?
	#add_filter('query', array('capa_protect','dev_info'));

#add_action('init', 'init_capa');

// Tags filtern - noetig?
#add_filter('the_tags',	array('capa_protect','dev_info'));

// Categorien ?
#do_action_ref_array('pre_get_posts', array(&$this));


// --------------------------------------------------------------------
// Deprecate & old & unknown FILTERS
// --------------------------------------------------------------------

	// Deprecated - replaced with 'list_terms_exclusions'
	##add_action('save_post',					array('capa_protect','verify_category'), 10);


	// Andere Loesung finden
	// Hm, vergessen wofuer das war ...
	###
	##	add_filter('role_has_cap',				array('capa_protect','filter_role_has_cap'),10,2);
	##	add_filter('user_has_cap',				array('capa_protect','filter_user_has_cap'),10,3);

	#	add_filter('the_tags',					array('capa-protect','filter_terms'),10);

	// Diese Funktion gibt ein html string. 
	// Dies zu schuetzen durch abfrage der categorie namen ist unsicher
	//	add_filter('the_category',				array('capa_protect','filter_category'),10);

	#		add_filter('get_comment',				array('capa_protect','filter_get_com'),10);



// --------------------------------------------------------------------
// FILTERS
// --------------------------------------------------------------------

//---- Diff Filters --//
add_filter('query',	array('capa_protect','filter_wpdb_query'),10);

	add_filter('wp_get_object_terms',		array('capa_protect','filter_object_item'),10,3);
	add_filter('wp_list_pages_excludes',	array('capa_protect','filter_page_list_item'),10);
	add_filter('wp_list_categories',		array('capa_protect','filter_category_list_item'),10);

//---- FRONTEND & BACKEND ----// 

	// BACKEND
		add_filter('contextual_help',		array('capa_protect','cleanup_capa_help'),10);
		add_filter('get_pages',				array('capa_protect','filter_pages'),10);

		if(strpos($_SERVER['REQUEST_URI'], '/wp-admin/')){
			add_filter('list_terms_exclusions',		array('capa_protect','sql_terms_exclusions'),10);
		}

	// FRONTEND & BACKEND
		add_filter('get_term',					array('capa_protect','filter_terms'),10,2);
		add_filter('get_terms',					array('capa_protect','filter_terms'),10);
		add_filter('posts_where',				array('capa_protect','filter_posts'),10);
		add_filter('wp_get_nav_menu_items',		array('capa_protect','filter_menu_list_item'),10);	


//---- POST FILTERS ----//
	add_filter('the_content',				array('capa_protect','filter_content'),10);
	add_filter('the_title',					array('capa_protect','filter_post_title'),10,2);

		add_filter('get_previous_post_where',	array('capa_protect','filter_posts'),10);
		add_filter('get_next_post_where', 		array('capa_protect','filter_posts'),10);
		add_filter('getarchives_where',			array('capa_protect','filter_posts'),10);

//---- COMMENT FILTERS ----//
	add_filter('comment_feed_where',		array('capa_protect','filter_comment_feed'),10);
	add_filter('comments_array',				array('capa_protect','filter_comment'),10,2);
		add_filter('get_comments_number',		array('capa_protect','filter_comment'),10);
		add_filter('the_comments',				array('capa_protect','filter_comment'),10);

		add_filter('comment_author',			array('capa_protect','filter_comment_author'),10);
		add_filter('get_comment_author',		array('capa_protect','filter_comment_author'),10);
		add_filter('get_comment_author_link',	array('capa_protect','filter_comment_author'),10);

		add_filter('comment_text',				array('capa_protect','filter_comment_body'),10);
		add_filter('get_comment_excerpt',		array('capa_protect','filter_comment_body'),10);

?>