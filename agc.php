<?php
/*
  Plugin Name: AGC
  Plugin URI: https://www.facebook.com/ridwan.hasanah3
  Description: Auto Generate Content
  Version: 1.0
  Author: Ridwan Hasanah
  Author URI: https://www.facebook.com/ridwan.hasanah3
*/


add_filter('the_posts', 'rh_virtual_page' );

function rh_virtual_page( $posts){

	global $wp_query;

	if (count($posts) == 0 && !is_home()) { 

		$post  = new stdClass; //membuat object stdClass

		$url   = trim($_SERVER['REQUEST_URI'],'/');
		$slug  = end(explode('/',$url) );
		$title = urldecode($slug);
		$title = ucwords(str_replace('-', ' ', $title));
		$keyword = str_replace(' ', '+', $title);

		$data  = unserialize(rh_get_wiki($keyword));
		
		if (!empty($data['description'])) {

			$post->ID             = -999;
			$post->post_title     = $title; //menampilakn title post
			$post->post_content   = $data['description']; //menampilkan content
			$post->post_author    = 1;
			$post->comment_status = 'closed';
			$post->comment_count  = '0';
			$post->post_type      = 'page';
			$post->post_name      = $url;

			$posts = array($post);

			$wp_query->is_page     = TRUE;
			$wp_query->is_singular = TRUE;
			$wp_query->is_home     = FALSE;
			$wp_query->is_archive  = FALSE;
			$wp_query->is_category = FALSE;
			unset($wp_query->query['error']);
			$wp_query->query_vars['error'] = '';
			$wp_query->is_404 = FALSE;
	
		}else{
			$wp_query->is_404= TRUE;
			
		}
		

	}

	return $posts;
}

function rh_get_wiki($keyword){

	$url = 'https://en.wikipedia.org/w/api.php?'.
	'format=json&action=query&prop=extracts'.
	'&exintro=&explaintext=&titles='.$keyword;

	$contents    =  json_decode(file_get_contents($url) );

	$title       = '';
	$description = '';

	foreach ($contents->query->pages as $content) {
		$title       = $content->{'title'};
		$description = $content->{'extract'};
	}

	if (strpos(strtolower($description), 'this is a redirect') !== false) {
		
		$description = '';

	}elseif ($description !=''){

		$paragraph = explode("\n", $description);
		foreach ($paragraph as $key => $value) {
			$paragraph[$key] = '<p>'.$value.'</p>';

		}

		$description = implode('', $paragraph);

		
		
	}

	$data['title']       = $title;
	$data['description'] = $description;
	return serialize($data);

}
?>