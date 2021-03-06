<?php 

/*
*  acf_pro_get_view
*
*  This function will load in a file from the 'admin/views' folder and allow variables to be passed through
*
*  @type	function
*  @date	28/09/13
*  @since	5.0.0
*
*  @param	$view_name (string)
*  @param	$args (array)
*  @return	n/a
*/

function acf_pro_get_view( $view_name = '', $args = array() ) {
	
	// vars
	$path = acf_get_path("pro/admin/views/{$view_name}.php");
	
	
	if( file_exists($path) )
	{
		include( $path );
	}
}


/*
*  acf_pro_get_remote_url
*
*  description
*
*  @type	function
*  @date	16/01/2014
*  @since	5.0.0
*
*  @param	$post_id (int)
*  @return	$post_id (int)
*/

function acf_pro_get_remote_url( $action = '', $args = array() ) {
	
	// defaults
	$args['a'] = $action;
	$args['p'] = 'pro';
	
	
	// vars
	$url = "http://connect.advancedcustomfields.com/index.php?" . build_query($args);
	
	
	// return
	return $url;
}


/*
*  acf_pro_get_remote_response
*
*  description
*
*  @type	function
*  @date	16/01/2014
*  @since	5.0.0
*
*  @param	$post_id (int)
*  @return	$post_id (int)
*/

function acf_pro_get_remote_response( $action = '', $post = array() ) {
	
	// vars
	$url = acf_pro_get_remote_url( $action );
	
	
	// connect
	$request = wp_remote_post( $url, array(
		'body' => $post
	));
	

    if( !is_wp_error($request) || wp_remote_retrieve_response_code($request) === 200)
    {
        return $request['body'];
    }
    
    
    // return
    return false;
}



/*
*  get_info
*
*  description
*
*  @type	function
*  @date	16/01/2014
*  @since	5.0.0
*
*  @param	$post_id (int)
*  @return	$post_id (int)
*/

function acf_pro_get_remote_info() {
	
	// check for transient
	$transient = get_transient( 'acf_pro_get_remote_info' );
	
	if( !empty($transient) )
	{
		return $transient;
	}

	
	// vars
	$info = acf_pro_get_remote_response('get-info');
	
	
    // validate
    if( empty($info) )
    {
        return false;
    }
    
	
	// decode and return
	$info = json_decode($info, true);
	
	
	// update transient
	set_transient('acf_pro_get_remote_info', $info, 1 * HOUR_IN_SECONDS );
	
	
	return $info;
}

function acf_pro_is_license_active() {
	
	// vars
	$data = acf_pro_get_license( true );
	$url = get_bloginfo('url');
	

	if( !empty($data['url']) && !empty($data['key']) && $data['url'] == $url )
	{
		return true;
	}
	
	
	return false;
	
}

function acf_pro_get_license( $all = false ) {
	
	// get option
	$data = get_option('acf_pro_license');
	
	
	// decode
	$data = base64_decode($data);
	
	
	// attempt deserialize
	if( is_serialized( $data ) )
	{
		$data = maybe_unserialize($data);
		
		// $all
		if( !$all )
		{
			$data = $data['key'];
		}
		
		return $data;
	}
	
	
	// return
	return false;
}



function acf_pro_update_license( $license ) {
	
	$save = array(
		'key'	=> $license,
		'url'	=> get_bloginfo('url')
	);
	
	
	$save = maybe_serialize($save);
	$save = base64_encode($save);
	
	
	return update_option('acf_pro_license', $save);
	
}


/*
*  acf_get_valid_options_page
*
*  description
*
*  @type	function
*  @date	24/02/2014
*  @since	5.0.0
*
*  @param	$post_id (int)
*  @return	$post_id (int)
*/

function acf_get_valid_options_page( $page = '' ) {
	
	// allow for string
	if( is_string($page) ) {
	
		$page_title = $page;
		
		$page = array(
			'page_title' => $page_title,
			'menu_title' => $page_title
		);
	}
	
	
	// defaults
	$page = acf_parse_args($page, array(
		'page_title' 	=> '',
		'menu_title'	=> '',
		'menu_slug' 	=> '',
		'capability'	=> 'edit_posts',
		'parent_slug'	=> '',
		'position'		=> false,
		'icon_url'		=> false,
		'redirect'		=> ''
	));
	
	
	// ACF4 compatibility
	$migrate = array(
		'title' 	=> 'page_title',
		'menu'		=> 'menu_title',
		'slug'		=> 'menu_slug',
		'parent'	=> 'parent_slug'
	);
	
	foreach( $migrate as $old => $new ) {
		
		if( !empty($page[ $old ]) ) {
			
			$page[ $new ] = acf_extract_var( $page, $old );
			
		}
		
	}
	
	
	// slug
	if( empty($page['menu_slug']) ) {
	
		$page['menu_slug'] = 'acf-options-' . sanitize_title( $page['menu_title'] );
		
	}
	
	
	// return
	return $page;
	
}


/*
*  acf_pro_get_option_page
*
*  description
*
*  @type	function
*  @date	24/02/2014
*  @since	5.0.0
*
*  @param	$post_id (int)
*  @return	$post_id (int)
*/

function acf_get_options_page( $slug ) {
	
	// bail early if page doens't exist
	if( empty($GLOBALS['acf_options_pages'][ $slug ]) ) {
		
		return false;
		
	}
	
	
	// vars
	$page = $GLOBALS['acf_options_pages'][ $slug ];
	
	
	// filter for 3rd party customization
	$page = apply_filters('acf/get_options_page', $page, $slug);
	
	
	// return
	return $page;
	
}


/*
*  acf_pro_get_option_pages
*
*  description
*
*  @type	function
*  @date	24/02/2014
*  @since	5.0.0
*
*  @param	$post_id (int)
*  @return	$post_id (int)
*/

function acf_get_options_pages() {
	
	// bail early if empty
	if( empty($GLOBALS['acf_options_pages']) ) {
		
		return false;
		
	}
	
	
	// vars
	$pages = array();
	$redirect = array();
	$slugs = array_keys($GLOBALS['acf_options_pages']);
	
	
	// get pages
	foreach( $slugs as $slug ) {
		
		$pages[] = acf_get_options_page( $slug );
		
	}
	
	
	// get redirects
	if( !empty($pages) ) {
		
		foreach( $pages as $page ) {
			
			// append redirect
			if( !empty($page['redirect']) ) {
				
				$redirect[ $page['menu_slug'] ] = $page['redirect'];
				
			}
			
		}
		
	}
	
	
	// loop through $pages and update redirect slugs
	if( !empty($redirect) ) {
		
		foreach( $pages as $k => $page ) {
			
			if( !empty($page['parent_slug']) ) {
				
				if( array_key_exists($page['parent_slug'], $redirect) ) {
					
					$pages[ $k ]['parent_slug'] = $redirect[ $page['parent_slug'] ];
					
				}
				
			} else {
				
				if( array_key_exists($page['menu_slug'], $redirect) ) {
					
					$pages[ $k ]['menu_slug'] = $redirect[ $page['menu_slug'] ];
					
				}
				
			}
			
		}
		
	}
		
	
	// return
	return $pages;
	
}


/*
*  acf_update_options_page
*
*  description
*
*  @type	function
*  @date	1/05/2014
*  @since	5.0.0
*
*  @param	$post_id (int)
*  @return	$post_id (int)
*/

function acf_update_options_page( $data ) {
	
	// bail early if no menu_slug
	if( empty($data['menu_slug']) ) {
		
		return false;
		
	}
	
	// vars
	$slug = $data['menu_slug'];
	
	
	// bail early if no page found
	if( empty($GLOBALS['acf_options_pages'][ $slug ]) ) {
	
		return false;
		
	}
	
	
	// vars
	$page = $GLOBALS['acf_options_pages'][ $slug ];
	
	
	// merge in data
	$page = array_merge($page, $data);
	
	
	// update
	$GLOBALS['acf_options_pages'][ $slug ] = $page;
	
	
	// return
	return $page;
	
}


/*
*  acf_add_options_page
*
*  description
*
*  @type	function
*  @date	24/02/2014
*  @since	5.0.0
*
*  @param	$post_id (int)
*  @return	$post_id (int)
*/

function acf_add_options_page( $page = '' ) {
	
	// validate
	$page = acf_get_valid_options_page( $page );
	
	
	// instantiate globals
	if( empty($GLOBALS['acf_options_pages']) ) {
	
		$GLOBALS['acf_options_pages'] = array();
		
	}
	
	
	// update if already exists
	if( acf_get_options_page($page['menu_slug']) ) {
		
		return acf_update_options_page( $page );
		
	}
	
	
	// append
	$GLOBALS['acf_options_pages'][ $page['menu_slug'] ] = $page;
	
	
	// return
	return $page;
	
}


/*
*  acf_add_options_page
*
*  description
*
*  @type	function
*  @date	24/02/2014
*  @since	5.0.0
*
*  @param	$post_id (int)
*  @return	$post_id (int)
*/

function acf_add_options_sub_page( $page = '' ) {
	
	// validate
	$page = acf_get_valid_options_page( $page );
	
	
	// parent
	if( empty($page['parent_slug']) ) {
		
		// set parent slug
		$page['parent_slug'] = 'acf-options';
		
		
		// get parent
		$parent = acf_get_options_page($page['parent_slug']);
		
		
		// redirect parent to child
		if( empty($parent['redirect']) ) {
			
			// update parent
			$parent = acf_update_options_page(array(
				'menu_slug'	=> $page['parent_slug'],
				'redirect'	=> $page['menu_slug']
			));
				
		}
		
	}
	
	
	// return
	return acf_add_options_page( $page );
	
}


/*
*  acf_set_options_page_title
*
*  This function is used to customize the options page admin menu title
*
*  @type	function
*  @date	13/07/13
*  @since	4.0.0
*
*  @param	$title (string)
*  @return	n/a
*/

function acf_set_options_page_title( $title = 'Options' ) {
	
	acf_update_options_page(array(
		'menu_slug'		=> 'acf-options',
		'page_title'	=> $title
	));
	
}


/*
*  acf_set_options_page_menu
*
*  This function is used to customize the options page admin menu name
*
*  @type	function
*  @date	13/07/13
*  @since	4.0.0
*
*  @param	$title (string)
*  @return	n/a
*/

function acf_set_options_page_menu( $title = 'Options' ) {
	
	acf_update_options_page(array(
		'menu_slug'		=> 'acf-options',
		'menu_title'	=> $title
	));
	
}


/*
*  acf_set_options_page_capability
*
*  This function is used to customize the options page capability. Defaults to 'edit_posts'
*
*  @type	function
*  @date	13/07/13
*  @since	4.0.0
*
*  @param	$title (string)
*  @return	n/a
*/

function acf_set_options_page_capability( $capability = 'edit_posts' ) {
	
	acf_update_options_page(array(
		'menu_slug'		=> 'acf-options',
		'capability'	=> $capability
	));
	
}


/*
*  register_options_page()
*
*  This is an old function which is now referencing the new 'acf_add_options_sub_page' function
*
*  @type	function
*  @since	3.0.0
*  @date	29/01/13
*
*  @param	{string}	$title
*  @return	N/A
*/

if( !function_exists('register_options_page') ) {

function register_options_page( $title = false ) {

	acf_add_options_sub_page( $title );
	
}

}

?>