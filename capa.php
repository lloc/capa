<?php
/*
Plugin Name: CaPa Protect Reloaded
Plugin URI: https://github.com/lloc/capa
Description: CaPa Protect Reloaded provides protection for categories & pages on a roles & user basis. The plugin based on the work by S. Matern (http://www.smatern.de/category/coding/capa/).
Version: 0.1
Author: realloc, S. Matern
Author URI: http://lloc.de/
*/

require_once 'capa-options.php';
require_once 'capa-user-edit.php';

class Capa_Protect {

	protected
		$options,
		$user;

	private function __construct() {
		$this->options = Capa_Protect_Options_Handler::getInstance();
		$this->user    = wp_get_current_user();
	}

	private function __clone() {
	}

	private function __wakeup() {
	}

	/**
	 * Init filters
	 */
	public static function getInstance() {
		static $instance;
		if ( null === $instance ) {
			$instance = new self();

			//---- Diff Filters --//
			add_filter( 'query',					array( $instance, 'filter_wpdb_query' ), 10);

			add_filter( 'wp_get_object_terms',		array( $instance, 'filter_object_item' ), 10, 3 );

			add_filter( 'wp_list_pages_excludes',	array( $instance, 'filter_page_list_item' ), 10 );

			add_filter( 'wp_list_categories',		array( $instance, 'filter_category_list_item' ), 10 );

			//---- FRONTEND & BACKEND ----//

			// BACKEND
			add_filter( 'contextual_help',			array( $instance, 'cleanup_capa_help' ), 10 );

			add_filter( 'get_pages',				array( $instance, 'filter_pages' ), 10 );

			if ( is_admin() ) {
				add_filter( 'list_terms_exclusions',	array( $instance, 'sql_terms_exclusions' ), 10 );
			}

			// FRONTEND & BACKEND
			add_filter( 'get_term',					array( $instance, 'filter_terms' ), 10, 2 );
			add_filter( 'get_terms',				array( $instance, 'filter_terms' ), 10 );

			add_filter( 'posts_where',				array( $instance, 'filter_posts' ), 10 );

			add_filter( 'wp_get_nav_menu_items',	array( $instance, 'filter_menu_list_item' ), 10 );

			//---- POST FILTERS ----//
			add_filter( 'the_content',				array( $instance, 'filter_content' ), 10 );
			add_filter( 'the_title',				array( $instance, 'filter_post_title' ) , 10, 2 );

			add_filter( 'get_previous_post_where',	array( $instance, 'filter_posts' ), 10 );
			add_filter( 'get_next_post_where', 		array( $instance, 'filter_posts' ), 10 );
			add_filter( 'getarchives_where',		array( $instance, 'filter_posts' ), 10 );

			//---- COMMENT FILTERS ----//
			add_filter( 'comment_feed_where',		array( $instance, 'filter_comment_feed' ), 10 );
			add_filter( 'comments_array',			array( $instance, 'filter_comment' ), 10, 2 );

			add_filter( 'get_comments_number',		array( $instance, 'filter_comment' ), 10 );
			add_filter( 'the_comments',				array( $instance, 'filter_comment' ), 10 );

			add_filter( 'comment_author',			array( $instance, 'filter_comment_author' ), 10 );
			add_filter( 'get_comment_author',		array( $instance, 'filter_comment_author' ), 10 );
			add_filter( 'get_comment_author_link',	array( $instance, 'filter_comment_author' ), 10 );

			add_filter( 'comment_text',				array( $instance, 'filter_comment_body' ), 10, 3 );
			add_filter( 'get_comment_excerpt',		array( $instance, 'filter_comment_body' ), 10, 3 );
		}
		return $instance;
	}

	public function check_user( $editor = true ) {
		if ( $editor && isset( $this->user->caps['editor'] ) )
			return false;
		return(
			$this->user &&
			isset( $this->user->allcaps['manage_categories'] )
		);
	}

	function filter_object_item( $terms, $object_ids, $taxonomies ) {
		switch ( $taxonomies ) {
			case '\'category\'':
				// Gets the current Post Categories Cache
				$_categories = wp_cache_get( $object_ids, 'category_relationships' );
				if ( is_array( $_categories ) ) {
					foreach( $_categories as $id => $cat ) {
						if ( ! $this->user_can_access( $id, 'category' ) ) {
							unset($_categories[$id]);
						}
					}
					wp_cache_replace($object_ids,$_categories,'category_relationships');
				}
				break;
		}
		return $terms;
	}

	function filter_menu_list_item( $params ) {
		foreach ( $params as $item => $values ) {
			if ( ! $this->user_can_access( $values->object_id, $values->object ) ) {
				switch($values->object){
					case 'page':
						if(!$this->options->capa_protect_show_private_pages){
							unset($params[$item]);
						}
					break;

					case 'category':
						if(!$this->options->capa_protect_show_private_categories){
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
	 * @param string $text
	 * @return string
	 */
	function filter_category_list_item( $text, $category = null ) {
		if ( $this->check_user() )
			return $text;

		$all_category_ids = $this->get_taxonomy_ids( 'category' );

		# Make the changes
		$site_root = trailingslashit( parse_url( get_option( siteurl ), PHP_URL_PATH ) );

		if ( $this->options->capa_protect_show_private_categories ) {
			if ( $this->options->capa_protect_show_padlock_on_private_categories ) {
				foreach ( $all_category_ids as $taxo_id => $term_id ) {
					if ( ! $this->user_can_access( $term_id, 'cat' ) ) {
						$tmp_catname       = get_cat_name( $term_id );
						$search[$taxo_id]  = "#>" . $tmp_catname . "<#";
						$replace[$taxo_id] = '><img src="' . $site_root .'wp-content/plugins/capa/img/padlock.gif" height="10" width="10" valign="center" border="0"/> '. $tmp_catname . '<';
					}
				}
				// In the case user see all categories but padlock is active
				// $search will be empty
				if ( count( $search ) > 0 )
					return preg_replace( $search, $replace, $text );
			}
		}
		return $text;
	}


	/**
	 * Filter the Page items
	 * @return array
	 */
	function filter_page_list_item() {
		// Show Private Pages
		if ( $this->options->capa_protect_show_private_pages ) {
			return array();
		}

		$current_role = implode( '', $this->user->roles );

		if ( $this->check_user() )
			return array();

		if ( $this->user->id == 0 ) {
			$user_access_page_check = $this->options->capa_protect_pag_anonymous;
		}else{
			$user_access_page_check = get_option("capa_protect_pag_user_".$this->user->id);
		}

		if ( empty( $user_access_page_check ) ) {
			$user_access_page_check = (
				$current_role ?
				get_option( "capa_protect_pag_role_{$current_role}" ) :
				$this->options->capa_protect_pag_default
			);
		}

		// If the DB contains no data all pages will be excluded
		$excludes_page = get_all_page_ids();
		if ( is_array( $user_access_page_check ) ) {
			$tmp['all_pages'] = $excludes_page;
			foreach ( $user_access_page_check as $check => $id ) {
				$tmp_id = array_search( $check, $tmp['all_pages'] );
				if ( false !== $tmp_id ) {
					unset( $tmp['all_pages'][$tmp_id] );
				}
			}
			$tmp['all_pages'] = array_flip( $tmp['all_pages'] );
			$excludes_page = array_keys( $tmp['all_pages'] );
		}
		return $excludes_page;
	}


	/**
	 * Checks the right of an signle post
	 * @param string $postid
	 * @return bool
	 */
	function post_should_be_hidden( $postid ) {
		$postid = intval( $postid );
		if ( 0 === $postid )
			return true;

		// Bad! To much queries
		// find a better way
		$post_categories = wp_get_post_categories( $postid );
		$post_val		 = get_post( $postid );

	    // in the case there is no post (null)
		if ( is_null( $post_val ) )
			return true;

		$post_cat_pa = $post_val->post_parent;

		switch($post_val->post_type){
			case 'nav_menu_item':
				return FALSE;
			break;

			case 'page':
			default:

				// CATEGORY
					if ($this->options->capa_protect_post_policy != 'hide'){
					// Page
						if ($post_val->post_type == "page"){
							if ($this->user_can_access( $postid, 'pag' ) ) {
								return false;
							}
						}

					// Show a Patlock
						foreach ($post_categories as $post_category_id){
							if ($this->user_can_access( $post_category_id, 'cat')){
								return false;
							}
						}

						return true;
					} else {
					// Bastion ( Show or not Show )
					// Check Up ~ Provsional Info: There is a previous Entry (?) //
						if ($post_val->post_type == "page"){
							if (!$this->user_can_access( $postid, 'pag')){
								return true;
							}
						}else{
							// Category Post
							return !$this->user_can_access( $post_categories, 'cat', $post_cat_pa );
						}

					}

			break;
		}



	}

	/**
	 * Gives TRUE/FALSE for users rights for spezific request
	 * @param string $val_id
	 * @param array object $user
	 * @param string $kind Select between 'pag' / 'cat'
	 * @param string $parent_id
	 * @return bool
	 */
	public function user_can_access( $val_id, $kind, $parent_id = '' ) {
		global $post;

		if ( $this->check_user() )
			return true;

		switch ( $kind ) {
			case 'pag':
			case 'page':
				$user_access_page_check = (
					0 == $this->user->id ?
					$this->options->capa_protect_pag_anonymous :
					$user_access_page_check = get_option( "capa_protect_pag_user_{$this->user->id}" )
				);
				// User Settings
				if ( empty( $user_access_page_check ) ) {
					// Group Settings
					$tmp_caps = implode( '', array_keys( $user->caps ) );
					$user_access_page_check = get_option( "capa_protect_pag_role_{$tmp_caps}" );
				}
				// Default Setting
				if ( empty( $user_access_page_check ) ) {
					$user_access_page_check	= $this->options->capa_protect_pag_default;
				}
				return( isset( $user_access_page_check[$val_id] ) );
				break;
			case 'cat':
			case 'category':
				// parent id check for attachment
				if ( is_attachment() ) {
					$access_post = wp_get_post_cats( 1, $parent_id );
					$val_id      = $access_post[0];
				}
				$user_access_category_check	= (
					0 == $this->user->id ?
					$this->options->capa_protect_cat_anonymous :
					get_option( "capa_protect_cat_user_{$this->user->id}" )
				);
				// User Settings
				if ( empty( $user_access_category_check ) ) {
					// Group Settings
					$tmp_caps = implode( '', array_keys( $this->user->caps ) );
					$user_access_category_check = get_option( "capa_protect_cat_role_{$tmp_caps}" );
				}
				// Default Setting
				if ( empty( $user_access_category_check ) ) {
					$user_access_category_check = $this->option->capa_protect_cat_default;
				}
				$return = false;
				if ( false !== $user_access_category_check ) {
					if ( is_array( $val_id ) ) {
						foreach ( $val_id as $key => $id ) {
							if ( array_key_exists( $id, $user_access_category_check ) )
								$return = true;
						}
					}
					else {
						if ( array_key_exists( $val_id, $user_access_category_check ) )
							$return = true;
					}
				}
				return $return;
				break;
			}
		}

		/**
		 * Gets the 'private' message string
		 * @return string
		 */
		function get_private_message() {
			return(
				$this->options->capa_protect_private_message ?
				$this->options->capa_protect_private_message :
				$this->options->capa_protect_default_private_message
			);
		}

		/**
		 * Gets users capa settings
		 * @param array object $current_user
		 * @return array
		 */
		function get_capa_protect_for_user() {
			if ( $this->check_user() )
				return true;

			if ( 0 == $this->user->id )
				return $this->options->capa_protect_cat_anonymous;

			$visible = get_option( "capa_protect_cat_user_{$this->user->id}" );
			if ( empty( $visible ) ) {
				$visible = get_option( 'capa_protect_cat_role_' . implode( '', $current_user->roles ) );
				if ( empty( $visible ) )
					$visible = $this->option->capa_protect_cat_default;
			}
			return $visible;
		}

		/**
		 * Sets users capa settings
		 * @param string $user_id
		 * @param array $value
		 * @param string $kind
		 * @return none
		 */
		function set_access_for_user( $user_id, $value, $kind ) {
			if ( in_array( $kind, array( 'cat', 'pag' ) ) ) {
				$option = "capa_protect_{$kind}_user_{$user_id}";
				if ( $value )
					update_option( $option, $value );
				else
					delete_option( $option );
			}
		}

		/**
		 * Add SQL Addition to show only posts
		 * @param string $sql
		 * @return string SQL Addiction
		 */
		function filter_posts( $sql = false ) {
			if (
				! $this->options->capa_protect_show_only_allowed_attachments &&
				! $this->options->capa_protect_show_unattached_files &&
				strpos( $sql,'attachment' )
			)
				return $sql;

			if ( $this->check_user() )
				return $sql;

			// Is Feed or User/Visitor got the right to see the titel / message
			if (
				( is_feed() && $this->options->capa_protect_post_policy && 'hide' != $this->options->capa_protect_post_policy ) ||
				( in_array( $this->options->capa_protect_post_policy, array( 'show title', 'show message' ) ) )
			) {
				// Only Frontend, Backend(wp-admin) no visual change
				if ( ! strpos( $_SERVER['REQUEST_URI'], '/wp-admin/' ) ) {
					return $sql;
				}
			}

			$inclusive_page	= (array) $this->get_valid_pages();

			$inclusive = $this->get_value_categories( true );
			if ( 0 == count( $inclusive ) )
				$inclusive = array( 0 );

			global $wpdb;
			$ids = $wpdb->get_col(
				$wpdb->prepare(
					"SELECT ID FROM {$wpdb->posts} p INNER JOIN {$wpdb->term_relationships} t ON ( p.ID = t.object_id ) WHERE t.term_taxonomy_id in ( %s ) AND p.post_type NOT IN ( 'revision', 'page' )",
					implode( ',', $inclusive )
				)
			);

			$ids = array_unique( array_merge( $ids, $inclusive_page ) );
			if ( 0 < count( $ids ) ) {
				$ids[] = (
					$this->options->capa_protect_show_only_allowed_attachments && $this->options->capa_protect_show_unattached_files ?
					'0' :
					'-1'
				);
			}
			else {
				$ids[] = (
					$this->options->capa_protect_show_unattached_files ?
					'0' :
					'-1'
				);
			}

			// In case sql is for the attachments
			$fld_id = ( strpos( $sql, "post_type = 'attachment'" ) ) ? 'post_parent' : 'ID';
			return $sql . $wpdb->prepare( " AND {$fld_id} IN ( %s )", implode( ', ', $ids ) );
		}

		/**
		 * To show or not to show the post title
		 * @param string $param
		 * @return string
		 */
		function filter_post_title( $param, $post_id ) {
			if ( $this->check_user() )
				return $param;

			if (
				false !== $this->options->capa_protect_post_policy &&
				'hide' != $this->options->capa_protect_post_policy &&
				$this->post_should_be_hidden( $post_id )
			) {
				return $param;
			}
			return __( 'No Title', 'capa' );
		}

		/**
		 * To show or not to show the post content
		 * @param string $text
		 * @return string
		 */
		function filter_content( $text ) {
			global $post;
			if (
				false !== $this->options->capa_protect_post_policy &&
				'show title' == $this->options->capa_protect_post_policy &&
				$this->post_should_be_hidden( $post->ID )
			) {
				$text = $this->get_private_message();
			}
			return $text;
		}

		/**
		 * To show or not to show the post comment
		 * @param string $text
		 * @return string
		 */
		function filter_comment( $params, $post_id = false, $return = 0 ) {
			if ( $this->check_user() )
				return $params;

			$return	 = ( is_array( $params ) ) ? array() : $return;
			if (
				'hide' == $this->options->capa_protect_comment_policy &&
				! $this->options->capa_protect_show_comment_on_private_posts
			) {
				return $return;
			}

			if (
				! is_numeric( $post_id ) &&
				isset( $params->comment_post_ID ) &&
				is_numeric( $params->comment_post_ID )
			) {
				$post_id = $params->comment_post_ID;
			}

			if ( ! $post_id ) {
				global $post;
				$post_id = ( is_null( $post ) ) ? 0 : $post->ID;
			}

			if ( $post_id > 0 ) {
				if ( $this->post_should_be_hidden( $post_id ) ) {
					if (
						is_numeric( $this->options->capa_protect_show_comment_on_private_posts ) &&
						'hide' != $this->options->capa_protect_comment_policy
					) {
						return $params;
					}
				}
				if (
					'hide'  != $this->options->capa_protect_comment_policy &&
					false !== $this->options->capa_protect_comment_policy
				) {
					return $params;
				}
				return $return;
			}

			if ( is_array( $params ) ) {
				$_posts = array();

				foreach ( $params as $obj ) {
					$_posts[] = $obj->comment_post_ID;
					$_comments[$obj->comment_post_ID][] = $obj;
				}

				$params = array();
				foreach ( $_posts as $obj ) {
					if ( ! $this->post_should_be_hidden( $obj ) ) {
						$params[] = $_comments[$obj];
					}
				}
			}
			return $params;
		}

		/**
		 * To show or not to show the comment body(content)
		 * @param string $param
		 * @return string
		 */
		function filter_comment_body( $param, $comment, $args ) {
			if ( $this->check_user() )
				return $param;

			if (
				$this->post_should_be_hidden( $comment->ID ) &&
				is_numeric( $this->options->capa_protect_show_comment_on_private_posts ) &&
				in_array( $this->options->capa_protect_comment_policy, array( 'show message', 'all' ) )
			) {
				return $param;
			}
			return(
				in_array( $this->options->capa_protect_comment_policy, array( 'show message', 'all' ) ) ?
				$param :
				$this->get_private_message()
			);
		}

		/**
		 * To show or not to show the comment author
		 * @todo Editor Comment auch unsichtbar machen?
		 * @param string $param
		 * @return string
		 */
		function filter_comment_author( $param ) {
			if ( $this->check_user() )
				return $param;

			global $post;
			if (
				$post && $this->post_should_be_hidden( $post->ID ) &&
				is_numeric( $this->options->capa_protect_show_comment_on_private_posts ) &&
				in_array( $this->options->capa_protect_comment_policy, array( 'show name', 'all' ) )
			) {
				return $param;
			}
			return(
				in_array( $this->options->capa_protect_comment_policy, array( 'show message', 'all' ) ) ?
				$param :
				$this->options->capa_protect_default_comment_author
			);
		}

		/**
		 * To show or not to show the page
		 * @param array $param
		 * @return array
		 */
		function filter_pages( $params ) {
			$params = (array) $params;
			if ( $this->options->capa_protect_show_private_pages )
				return $params;

			foreach ( $params as $id => $page ) {
				if( $this->post_should_be_hidden( $page->ID ) )
					unset( $params[$id] );
			}
			return $params;
		}

	/**
	 * get the allows/disallow ( depends on modus ) categories
	 * @param bool $moduls
	 * @param string $typ
	 * @return array
	 */
	function get_value_categories( $modus = true, $typ = 'taxo', $inclusions = array( 0 ), $capa_all_category_ids = array() ) {
		$all_category_tax_ids = array_flip( $this->get_taxonomy_ids( 'category' ) );

		if ( $this->check_user() )
			return $all_category_tax_ids;

		$get_valid_category_check = $this->get_capa_protect_for_user();

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
	 * @param bool $moduls
	 * @return array
	 */
	function get_value_tags($modus=TRUE, $ids=array()) {
		global $wpdb;

		switch($modus){
			case TRUE:
				$categories = $this->get_value_categories(TRUE,'taxo');
				$dis_cat	= $this->get_value_categories(FALSE,'taxo');

				$where_in	= ' IN ('.implode(',',$categories).') ';
				$where_not	= ' AND '.$wpdb->term_relationships.'.term_taxonomy_id NOT IN ('.implode(',',$dis_cat).') ';
			break;

			case FALSE:
				$categories = $this->get_value_categories(FALSE,'taxo');

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
	 * @return array
	 */
	function get_valid_pages($inclusions=FALSE){
		if ( $this->check_user() )
			return false;

		if ($this->user->id == 0){
			$user_access_page_check = $this->options->capa_protect_pag_anonymous;
		}
			else{
				$current_user_role      = 'capa_protect_pag_role_' . implode( '', $this->user->roles );
				$user_access_page_check = get_option( "capa_protect_pag_user_{$this->user->id}" );
				$user_access_page_check = ($user_access_page_check) ? $user_access_page_check : get_option( $current_user_role );
			}

			if (empty($user_access_page_check)){
				$user_access_page_check	= $this->options->capa_protect_pag_default;
			}

			if(is_array($user_access_page_check)){
				$inclusions = array_keys($user_access_page_check,TRUE);
			}

			return $inclusions;
	}

		/**
	 	 * If the user saves a post in a category or categories from which they are
		 * restricted, remove the post from the restricted category(ies).  If there
		 * are no categories left, save it as Uncategorized with status 'Saved'.
		 * @todo Hm, necessary?
		 */
		function verify_category($post_ID) {
			global $wpdb;

			$postcats = $wpdb->get_col("SELECT term_taxonomy_id FROM $wpdb->term_relationships WHERE object_id = $post_ID ORDER BY term_taxonomy_id");
			$exclusions = $this->get_value_categories(FALSE);

			if (count($exclusions) && $exclusions != TRUE) {
				$exclusions = implode(", ", $exclusions);
				$wpdb->query("DELETE FROM $wpdb->term_relationships WHERE object_id = $post_ID AND term_taxonomy_id IN ($exclusions)");
				$good_cats = $wpdb->get_var("SELECT COUNT(*) FROM $wpdb->term_relationships WHERE object_id = $post_ID");

				if (0 == $good_cats) {
					$wpdb->query( "INSERT INTO {$wpdb->term_relationships} (`object_id`, `term_taxonomy_id`) VALUES ($post_ID, 1)");
					$wpdb->query( "UPDATE {$wpdb->posts} SET post_status = 'draft' WHERE ID = $post_ID");
				}
			}
		}

		// List of Category at Admin Area ( Edit / New Post )
		function filter_wp_admin_category_list( $category ) {
			if ( $this->options->capa_protect_advance_policy ) {
				if ( $this->check_user( false ) )
					return $category;

				$count = count( $category );
				for ( $e = 0; $e <= $count; $e++ ) {
					if ( ! $this->user_can_access( $category[$e]['cat_ID'], 'cat' ) ) {
						if ( count( $category[$e]['children'] ) > 0 ) {
							$count_children = count( $category[$e]['children'] );
							for ( $f = 0; $f <= $count_children; $f++ ) {
								if ( !empty($category[$e]['children'][$f]['cat_ID'] ) ) {
									if ( $this->user_can_access( $category[$e]['children'][$f]['cat_ID'], 'cat' ) ) {
										$tmp_main = array(
											'children' => array(),
											'cat_ID'   => $category[$e]['children'][$f]['cat_ID'],
											'checked'  => $category[$e]['children'][$f]['checked'],
											'cat_name' => $category[$e]['children'][$f]['cat_name'],
										);
										array_push( $category, $tmp_main );
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

		function filter_terms( $_terms, $_taxonomy = false ) {
			if ( $this->check_user() )
				return $_terms;

			if ( $this->options->capa_protect_show_private_categories )
				return $_terms;

			$allow_tags	   = $this->get_value_tags( true );
			$category_taxo = $this->get_value_categories( true );

			if ( ! $_taxonomy ) {
				sort( $_terms );
				reset( $_terms );
				$count = count( $_terms );
				for ( $e = 0; $e <= $count; $e++ ) {
				// Remove Only taxonomy kind Category
					if ( isset( $_terms[$e] ) ) {
						if (
							$_terms[$e]->taxonomy == 'category' &&
							true != strpos( $_SERVER['REQUEST_URI'], '/wp-admin/' )
						) {
							if ( ! in_array( $_terms[$e]->term_taxonomy_id, $category_taxo ) ) {
								unset( $_terms[$e] );
							}
						}
						elseif ( $_terms[$e]->taxonomy == 'post_tag' ) {
							if ( !in_array( $_terms[$e]->term_taxonomy_id, $allow_tags ) ) {
								unset( $_terms[$e] );
							}
						}
					}
				}
			}
			else {
				if ( $_terms->parent > 0 ) {
					if ( ! array_key_exists( $_terms->parent, $category_taxo ) )
						$_terms->parent = 0;
				}
			}

		return $_terms;
	}


	/**
	 * restrict the total number of Categories, Tags for the Widget "Right now"
	 * @return string
	 */
	function sql_terms_exclusions( $return = '' ) {
		if (
			$this->options->capa_protect_show_private_categories &&
			true != strpos( $_SERVER['REQUEST_URI'], '/wp-admin/' )
		) {
			return '';
		}

		if ( $this->check_user() )
			return '';

		$cats = $this->get_value_categories();
		$tags = $this->get_value_tags();

		$objects = array_merge( $cats, $tags );

		$return .= ( array_sum( $objects ) <= 0 ) ? ' AND tt.term_taxonomy_id IN (0) ' : ' AND tt.term_taxonomy_id IN (' . implode( ',', $objects ) . ')';

		return $return;
	}

	function cleanup_capa_help( $params ) {
		$search_string = '_<h5>' . __( 'Other Help', 'capa' ) . '(.*)</div>{1}_';
		// Remove Other Help from Capa Wordpress Adminpages
		return preg_replace( $search_string, '', $params );
	}

	/**
	 * Create an Array with term_id and term_taxonomy_id ( prevent mix up with term/taxonomy IDs )
	 *
	 * @uses $wpdb
	 * @param array $taxonomy_ids
	 * @return array
	 */
	function get_taxonomy_ids( $taxonomy, $taxonomy_ids = array() ) {
		global $wpdb;

		$results = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT term_id, term_taxonomy_id FROM {$wpdb->term_taxonomy} WHERE taxonomy = %s",
				esc_sql( $taxonomy )
			)
		);
		if ( $results ) {
			foreach ( $results as $row ){
				$taxonomy_ids[$row->term_taxonomy_id] = $row->term_id;
			}
		}
		return $taxonomy_ids;
	}

	/**
	 * Add SQL Addition to show only comments of allow post
	 *
	 * @uses $wpdb
	 * @uses $this->get_value_categories()
	 *
	 * @return string
	 */
	function filter_comment_feed( $param ) {
		$return = ' WHERE comment_post_ID ';
		// In the Case comments are hidden
		if ( false === $this->options->capa_protect_comment_policy || 'hide' == $this->options->capa_protect_comment_policy ) {
			$return .= 'IN (0)';
		}
		else {
			global $wpdb;

			$valid_categories = $this->get_value_categories( true );

			$post_ids = $wpdb->get_col(
				$wpdb->prepare(
					"SELECT p.ID FROM {$wpdb->posts} p LEFT JOIN {$wpdb->term_relationships} t ON t.object_id = p.ID WHERE p.post_type = 'post' AND t.term_taxonomy_id IN ( %s )",
					implode( ', ', $valid_categories )
				)
			);

			if ( !empty( $post_ids ) ) {
				if ( count( $post_ids ) == 1 )
					$return .= '= ' . $post_ids[0];
				else {
					$return .= "IN ('" . implode( ', ', $post_ids ) . "')";
				}
			}
			else {
				$return .= '= 0';
			}
		}
		return ' WHERE comment_post_ID ' . $return;
	}

/**
	TESTAREA FOR NEW FUNCTION
**/
	function filter_get_com( $param ) {
		if ( $param && $this->post_should_be_hidden( $param->comment_post_ID ) ) {
			return null;
		}
		return $param;
	}

	function filter_manage( $param ) {}

	function filter_role_has_cap( $params ) {}

	function filter_user_has_cap( $params, $caps, $args ) {
		return $params;
	}

	function _admin_the_post_parent( $post ) {
		$allow_pages = $this->get_valid_pages();

		if ( is_array( $allow_pages ) ) {
			// Filter disallowed Pages
			if ( ! in_array( $post->post_parent, $allow_pages ) )
				$post->post_parent = 0;
		}
		return $post;
	}

	/**
		TODO Check mit Rope Scoper
	*/

	/**
	 * Alternate diverse SQL Queries
	 * @return string
	 */
	function filter_wpdb_query( $param ) {
		if ( $this->check_user() )
			return $param;

		global $wpdb;

		// SQLFILTER::function _wp_get_comment_list
		#	SELECT *
		#	FROM $wpdb->comments c
		#	LEFT JOIN $wpdb->posts p ON c.comment_post_ID = p.ID
		#	WHERE p.post_status != 'trash'
		if ( false !== strpos( $param, " FROM $wpdb->comments c LEFT JOIN $wpdb->posts p ON c.comment_post_ID = p.ID" ) ) {
			if ( false !== strpos( $param, 'WHERE' ) && false === strpos( substr( $param, strpos( $param, 'WHERE' ) ), 'comment_post_ID' ) ) {
				// In Case Public/Allowed Comments are hidden
				if ( 'hide' == $this->options->capa_protect_comment_policy ) {
					$allow_posts = ' comment_post_ID IN (0) AND';
				}
				else {
					$allow_posts = str_replace( 'AND ID',' comment_post_ID', $this->filter_posts() ) . ' AND';
				}
				$param = str_replace( 'WHERE', 'WHERE' . $allow_posts, $param );
			}
		}

		// SQLFILTER::function wp_count_comments
		#	SELECT comment_approved, COUNT( * ) AS num_comments
		#	FROM sm_blog_comments
		#	GROUP BY comment_approved
		if ( false !== strpos( $param, 'SELECT comment_approved, COUNT' ) && false === strpos( $param, 'WHERE' ) ) {
			if ( $this->options->capa_protect_comment_policy == 'hide' ) {
				$allow_posts = ' WHERE comment_post_ID IN (0) ';
			}
			else {
				$allow_posts = str_replace( 'AND ID', ' WHERE comment_post_ID', $this->filter_posts() ) . ' ';
			}
			$param = str_replace( 'FROM ' . $wpdb->comments, 'FROM ' . $wpdb->comments . ' ' . $allow_posts, $param );
		}

		// SQLFILTER::function wp_get_recent_posts
		#	SELECT *
		#	FROM $wpdb->posts
		#	WHERE post_type = 'post' AND post_status IN ( 'draft', 'publish', 'future', 'pending', 'private' ) ORDER BY post_date DESC $limit
		if ( false !== strpos( $param, "SELECT * FROM $wpdb->posts WHERE post_type = 'post' AND post_status IN ( 'draft', 'publish', 'future', 'pending', 'private' ) ORDER BY post_date DESC" ) ) {
			$recent_posts = substr( $param, ( strpos( $param, 'LIMIT ' ) ) + 6 );
			$allow_posts  = $this->filter_posts();
			return str_replace( 'AND post_status', $allow_posts . ' AND post_status', $param );
		}

		// SQLFILTER::function wp_count_attachments
		if ( false !== strpos( $param, 'SELECT post_mime_type, COUNT( * ) AS num_posts' ) ) {
			if ( ! $this->options->capa_protect_show_only_allowed_attachments )
				return $param;
			$allow_posts = str_replace( 'AND ID', ' AND post_parent', $this->filter_posts() );
			return str_replace( 'AND post_status', $allow_posts . ' AND post_status', $param );
		}

		// SQLFILTER::function get_available_post_mime_types
		if ( false !== strpos( $param, "SELECT DISTINCT post_mime_type FROM $wpdb->posts WHERE post_type = 'attachment'" ) ) {
			if ( ! $this->options = capa_protect_show_only_allowed_attachments )
				return $param;
			return $param . str_replace( ' AND ID', ' AND post_parent', $this->filter_posts() );
		}

		// SQLFILTER::function wp_count_posts
		if ( false !== strpos( $param, 'SELECT post_status, COUNT(' ) ) {
			$allow_posts = str_replace( 'AND ID', ' ID', $this->filter_posts() );
			if ( $allow_posts != '')
				$allow_posts = $allow_posts . ' AND ';

			if ( false !== strpos( $param, 'WHERE' ) && false === strpos( $param, 'ID' ) ) {
				$param = str_replace( 'WHERE', 'WHERE' . $allow_posts, $param );
			}
			return $param;
		}

		// SQLFILTER::function &get_terms ( case of count )
		#	SELECT COUNT(*) FROM $wpdb->terms AS t
		#	INNER JOIN $wpdb->term_taxonomy AS tt ON t.term_id = tt.term_id
		#	WHERE tt.taxonomy IN (string) AND tt.term_taxonomy_id IN (int)
		//	DEV
		if ( ! isset( $GLOBALS['wp_filter']['list_terms_exclusions'] ) && false !== strpos( $param, "SELECT COUNT(*) FROM $wpdb->terms AS t INNER JOIN $wpdb->term_taxonomy AS tt ON t.term_id = tt.term_id WHERE" ) ) {
			switch ( $param ) {
				case ( false !== strpos( $param, 'category' ) ):
					if ( false === strpos( $param, 'term_taxonomy_id' ) ) {
						$disallow_cat = $this->get_value_categories( false );
						$param       .= ' AND term_taxonomy_id NOT IN (' . implode( ',', $disallow_cat ) .')';
					}
					else {
						$disallow_cat = $this->get_value_categories( true );
						$param        = str_replace( 'WHERE','WHERE tt.term_taxonomy_id IN (' . implode( ',', $disallow_cat ) . ') AND', $param );
					}
					break;
				case ( false !== strpos( $param, 'post_tag' ) ):
					if ( false === strpos( $param, 'term_taxonomy_id' ) ) {
						$allow_tags	= $this->get_value_tags();
						$param     .= ' AND term_taxonomy_id IN (' . implode( ',', $allow_tags ) . ') ';
					}
					else {
						$allow_tags	= $this->get_value_tags();
						$param	    = str_replace( 'WHERE', 'WHERE t.term_id IN (' . implode( ',', $allow_tags ) .') AND', $param );
					}
					break;
			}
			return $param;
		}

		// SQLFILTER::function get_var ( case of unattached file count )
		#	SELECT COUNT( * ) FROM $wpdb->posts
		#	WHERE post_type = 'attachment' AND post_status != 'trash' AND post_parent < 1
		//	DEV
		if ( strpos( $param, "SELECT COUNT( * ) FROM $wpdb->posts WHERE post_type = 'attachment' AND post_status != 'trash' AND post_parent < 1" ) !== false ) {
			if ( ! $this->options->capa_protect_show_unattached_files ) {
				$param .= ' AND post_parent NOT IN (0)';
			}
		}
		return $param;
	}

}

/**
 * Load translation
 */
function capa_plugin_init() {
	load_plugin_textdomain(
		'capa',
		false,
		dirname( plugin_basename( __FILE__ ) ) . '/lang/'
	);
	capa_protect::getInstance();
}
add_action( 'plugins_loaded', 'capa_plugin_init' );

/**
 * Callback function for activation hook
 * @todo Set option tp true if there exits no option with the key 'capa_protect_show_only_allowed_attachments'
 */
function capa_activation() {
	update_option( 'capa_protect_show_only_allowed_attachments', true );
}
register_activation_hook( __FILE__, 'capa_activation' );

/**
 * Callback function for uninstall hook
 * - cleanup options if not protected
 */
function capa_uninstall(){
	global $wpdb;
	$wpdb->query(
		$wpdb->prepare(
			"DELETE FROM {$wpdb->options} WHERE option_name LIKE %s",
			'capa_protect%'
		)
	);
}
register_uninstall_hook( __FILE__, 'capa_uninstall' );
