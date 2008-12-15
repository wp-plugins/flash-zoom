<?php
/*
Plugin Name: Flash Zoom
Plugin URI: http://rocksun.cn/flash-zoom/
Description: zoom the flash in homepage.
Version: 0.8
Author: Rock Sun
Author URI: http://rocksun.cn/
*/

function fz_is_hash_valid($form_hash) {
	$ret = false;
	$saved_hash = fz_retrieve_hash();
	if ($form_hash === $saved_hash) {
		$ret = true;
	}
	return $ret;
}

function fz_generate_hash() {
	return md5(uniqid(rand(), TRUE));
}

function fz_store_hash($generated_hash) {
	return update_option('feedsmith_token',$generated_hash,'FeedSmith Security Hash');
}

function fz_retrieve_hash() {
	$ret = get_option('feedsmith_token');
	return $ret;
}

add_option('flashzoom_settings',$data,'FlashZoom Options');
$flashzoom_settings = get_option('flashzoom_settings');

function isInteger($input){
  return preg_match('@^[-]?[0-9]+$@',trim($input)) === 1;
}

function flash_zoom_options_page() {
		add_options_page('FlashZoom', 'FlashZoom', 8, basename(__FILE__), 'flash_zoom_options_subpanel');
}

function flash_zoom_options_subpanel() {
	global $ol_flash, $flashzoom_settings, $_POST, $wp_rewrite;
	
	if (current_user_can('activate_plugins')) {
		// Easiest test to see if we have been submitted to
		if(isset($_POST['target_width']) || isset($_POST['target_height'])) {
		  if(!isInteger($_POST['target_width'])||!isInteger($_POST['target_height'])){
		    $ol_flash = "Please input the number!";
		  }else{
			// Now we check the hash, to make sure we are not getting CSRF
			  if(fz_is_hash_valid($_POST['token'])) {
				  if (isset($_POST['target_width'])) { 
					  $flashzoom_settings['target_width'] = $_POST['target_width'];
					  update_option('flashzoom_settings',$flashzoom_settings);
					  $ol_flash = "Your settings have been saved.";
				  }
				  if (isset($_POST['target_height'])) { 
					  $flashzoom_settings['target_height'] = $_POST['target_height'];
					  update_option('flashzoom_settings',$flashzoom_settings);
					  $ol_flash = "Your settings have been saved.";
				  } 
			  } else {
				  // Invalid form hash, possible CSRF attempt
				  $ol_flash = "Security hash missing.";
			  } // endif fz_is_hash_valid
			} //endif isInteger
		} // endif isset(feedburner_url)
	} else {
		$ol_flash = "You don't have enough access rights.";
	}
	if ($ol_flash != '') echo '<div id="message" class="updated fade"><p>' . $ol_flash . '</p></div>';

	if (current_user_can('activate_plugins')) {
	  $temp_hash = fz_generate_hash();
		fz_store_hash($temp_hash);
		echo '<div class="wrap">';
		echo '<h2>Set Up Flash Zoom Size</h2>';
		echo '<form action="" method="post">
		<input type="hidden" name="redirect" value="true" />
		<input type="hidden" name="token" value="' . fz_retrieve_hash() . '" />
		
		<table class="form-table">
		<tbody>
		<tr valign="top">
    <th scope="row"><label for="target_width">Target Width</label></th>
      <td><input name="target_width" id="target_width" value="' . htmlentities($flashzoom_settings['target_width']) . '" size="4"  maxlength="4" type="text"></td>
    </tr>
    <tr valign="top">
      <th scope="row"><label for="target_height">Target Height</label></th>
      <td><input name="target_height" id="target_height" value="' . htmlentities($flashzoom_settings['target_height']) . '" size="4" maxlength="4" type="text"></td>
    </tr>
    </tbody>
    </table>

		<p><input type="submit" value="Save" /></p></form>';
		echo '</div>';
	} else {
		echo '<div class="wrap"><p>Sorry, you are not allowed to access this page.</p></div>';
	}

}

function replaceSize($matches)
{
  global $flashzoom_settings;
  $replaceStr = ' width="'.$flashzoom_settings['target_width'].'" height="'.$flashzoom_settings['target_height'].'" ';
  return $matches[1].$replaceStr.$matches[3].$replaceStr.$matches[5];
}



function filter_shrink($content) {
    global $flashzoom_settings;    
    if(is_home()){
      $content = preg_replace_callback(
           '|(<object.*)(width="\d*"\s*height="\d*")(.*)(width="\d*"\s*height="\d*")(.*<\/object>)|',
            "replaceSize",
            $content);      
    }
    
    return $content;
}

add_filter('the_content','filter_shrink');
add_action('admin_menu', 'flash_zoom_options_page');

?>