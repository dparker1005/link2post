<?php
function l2p_add_jsfiddle_module($modules) {
    $modules["jsfiddle.php"] = array('host'=>'jsfiddle.net','callback'=>'l2p_jsfiddle_callback', 'create_cpt'=>'l2p_create_jsfiddle_cpt', 'quick_name'=>'jsfiddle','can_update'=>true);
    return $modules;
}
add_filter('l2p_modules', 'l2p_add_jsfiddle_module');

function l2p_jsfiddle_callback($url, $old_post_id=NULL, $return_result=false){
	global $current_user;
	//Set up selector
	require_once(L2P_DIR.'/lib/selector.php');
	$html = wp_remote_retrieve_body(wp_remote_get($url));

	//grab title from title element
	$title = l2p_SelectorDOM::select_element('#id_title', $html);
	if(!empty($title) && !empty($title['attributes']['value']))
				$title = sanitize_text_field($title['attributes']['value']);
	$objToReturn->title = $title;

	//scrape the description
	$description = l2p_SelectorDOM::select_element('#id_description', $html);
	if(!empty($description) && !empty($description['attributes']) && !empty($description['text']))
		$description = sanitize_text_field($description['text']);
	else{
		$description = "";
	}
		
	//get author name
	$author_name = l2p_SelectorDOM::select_element('.avatar', $html);
	if(!empty($author_name) && !empty($author_name['text']))
		$author_name = trim(sanitize_text_field($author_name['text']));
	$author_url = 'https://jsfiddle.net/user/'.$author_name;
	
	//add embed code to post body
	$embed_code = '<iframe width="100%" height="500px" src="'.esc_url_raw($url).'" allowfullscreen="allowfullscreen" frameborder="0"></iframe>';
	
	//format post content
	$break = " </br> ";
	$post_content = $description.$break."\n".$embed_code."\n".$break.__('This fiddle was made by','link2post').' <a href="'.$author_url.'">'.$author_name.'</a>.'.$break.__('Original Fiddle','link2post').': <a href="'.esc_url_raw($url).'">'.esc_url_raw($url).'</a>';
	$post_type = (post_type_exists( "jsfiddle" ) ? 'jsfiddle' : 'post');

	if(empty($old_post_id)){
		//insert a jsfiddle CPT
		$postarr = array(
				'post_type' => $post_type,
				'post_title' => $title,
				'post_content' => $post_content,
				'post_author' => $current_user->ID,
				'post_status' => 'publish',
				'meta_input' => array(
					'l2p_url' => esc_url_raw($url),
				)
			);
		$post_id = wp_insert_post($postarr);
		$post_url = get_permalink($post_id);
		if($return_result==true){
			return $post_url;
		}
		$objToReturn->url = $post_url;
		$JSONtoReturn = json_encode($objToReturn);
		echo $JSONtoReturn;
		exit;
	}
	else{
		//update existing post
		$postarr = array(
			'ID' => $old_post_id,
			'post_type' => $post_type,
			'post_title' => $title,
			'post_content' => $post_content,
			'post_author' => $current_user->ID
		);
		wp_update_post($postarr);
		$post_url = get_permalink($old_post_id);
		$objToReturn->url = $post_url;
		$JSONtoReturn = json_encode($objToReturn);
		echo $JSONtoReturn;
		exit;
	}
}

//add a Codepen CPT
function l2p_create_jsfiddle_cpt() {  
  register_post_type( 'jsfiddle',
    array(
      'labels' => array(
        'name' => __( 'Fiddles','link2post' ),
        'singular_name' => __( 'Fiddle','link2post' ),
        'add_new_item' => __('Add New Fiddle','link2post'),
        'edit_item' => __( 'Edit Fiddle','link2post' ),
        'new_item' => __( 'New Fiddle','link2post' ),
		'view_item' => __( 'View Fiddle','link2post' ),
		'search_items' => __( 'Search Fiddles','link2post' ),
		'not_found' => __( 'No Fiddles Found','link2post' ),
		'not_found_in_trash' => __( 'No Fiddles Found In Trash','link2post' ),
		'all_items' => __( 'All Fiddles','link2post' ),
      ),
      'public' => true,
      'has_archive' => true,
    )
  );
}

//handle search and archives

//widget for related posts
