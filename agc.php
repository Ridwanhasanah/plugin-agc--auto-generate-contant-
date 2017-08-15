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

		$url     = trim($_SERVER['REQUEST_URI'],'/');
		$slug    = end(explode('/',$url) );
		$title   = urldecode($slug);
		$title   = ucwords(str_replace('-', ' ', $title));
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

/*======== Menu start =======*/

function rh_agc_menu(){
	add_menu_page('AGC', 'AGC', 'manage_options', 'agc', 'rh_agc_options' );
}

add_action('admin_menu', 'rh_agc_menu' );

function rh_agc_options(){

	echo '<h2>Auto Generate Content</h2>';

	$dir = plugin_dir_path(__FILE__);
	$file = $dir.'keywords.txt';

	if (!file_exists($file)) {
		
		$keywords = 'Keyword';

		if (!is_writable($dir)) {
			chmod($dir, 744);

		}

		file_put_contents($file, $keywords);
	}

	if ($_POST['rh-agc-submit']) {
		if (!is_writeable($dir)) { //is_writeable() utk Cek apakah filename bisa ditulis
			chmod($dir,0744);   //chmod untuk mengakses izin file directory
		}

		if (!is_writeable($file)) { //is_writeable() utk Cek apakah filename bisa ditulis
			chmod($file, 0744); //chmod untuk mengakses izin file directory
		}

		file_put_contents($file, $_POST['rh-agc-keywords']);
		echo '<div class="updated"><p><strong>Options Saved</strong></p></div>';
	}

	$keywords = file_get_contents($file);
	?>
	<form method="post">
		<label for="rh-agc-keywords">Keywords: </label>
		<textarea rows="10" cols="100" id="rh-agc-keywords" name="rh-agc-keywords"><?php echo $keywords; ?></textarea>
		<input type="submit" name="rh-agc-submit" id="rh-agc-submit" class="button" value="Simpan">
	</form>
	<?php
}

/*======== Menu ENd =======*/



function rh_get_agc_keyword(){

	$dir = plugin_dir_path(__FILE__ );
	$file = $dir.'keywords.txt';

	$home_url = get_home_url();

	$result = '';

	if (file_exists($file)) {
		
		$keywords = explode(PHP_EOL, file_get_contents($file));
		shuffle($keywords);
		$arr = array_slice($keywords, 0, 5, true);
		foreach ($arr as $key => $value) {
			 $slug = sanitize_title($value );
			
			 $arr[$key] = '<li><a href="'.$home_url.'/'.$slug.'">'.$value.'</a></li>';
		}

		$result = implode('', $arr);

	}
	echo '<ul>'.$result.'</ul><br>';
	/*echo 'impode';
	echo '<b>'.implode('', $arr).'</b>';
	echo 'impode';
	echo get_home_url().'<br>';
	echo $_SERVER['REQUEST_URI'].'/<br>';
	echo '<a href="'.$home_url.'">zzz</a><br>';

	echo '<pre>';
	print_r($arr);
	echo "<pre>";*/
}



/*======== Widget Start =======*/
function rh_agc_widget($args){

	extract($args);
	echo $before_widget;
	echo $before_title;

	$options = get_option('rh-agc-widget');
	
	if (!is_array($options)) {
		$options = array(
			'judul-widget'=> 'Keyword AGC');
	}

	echo $options['judul-widget'];
	echo $after_title;
	rh_get_agc_keyword();
	echo $after_widget;
}
/*======== Widget End =======*/


/*======== INIT Start =======*/
function rh_agc_init(){
	wp_register_sidebar_widget(
		'rh_agc_plugin', 
		'Keyword AGC', 
		'rh_agc_widget', 
		array(
			'description' => 'Keyword List for AGC'
			) );
	wp_register_widget_control( 
		'rh_agc_plugin',
		'Keyword AGC',
		'rh_agc_control' );
}

add_action('plugins_loaded', 'rh_agc_init' );
/*======== INIT End =======*/


/*======== CONTROL WIDGET Start =======*/
function rh_agc_control(){
	$options = get_option('rh-agc-widget' );

	if (!is_array($options)) {
		$options = array(
			'judul-widget' => 'AGC');
	}

	if ($_POST['rh-agc-submit']) {
		$options['judul-widget'] = $_POST['judul-widget'];
		update_option("rh-agc-widget",$options );
	} ?>
		<p>
			<label for="judul-widget">Title : </label>
			<input type="text" name="judul-widget" id="judul-widget" value="<?php echo $options['judul-widget']; ?>"/><br>
			<input type="hidden" name="rh-agc-submit" id="rh-agc-submit" value="1">
		</p>
	<?php
}
/*======== CONTROL WIDGET End =======*/



?>