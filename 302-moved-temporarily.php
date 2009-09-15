<?php
/*
Plugin Name: 302 Moved Temporarily
Plugin URI: 
Description: Redirects any unauthenticated users to another address by sending them a '302 Moved Temporarily' message.
Author: Jordan Patterson
Version: 1.0
Author URI: http://bluetreemedia.ca
License: GPL 2.0, @see http://www.gnu.org/licenses/gpl-2.0.html
*/

function create_redirect_header($status_header, $header, $text, $protocol) {
	if ( !is_user_logged_in() ) {
		return "$protocol 302 Moved Temporarily";
	}
}

function redirect_to() {
	if ( !is_user_logged_in() ) {
		header('location: '.get_option('where_to_send'));
		die;
	}
}

function redirected_feed() {
	if ( !is_user_logged_in() ) {
		die('<?xml version="1.0" encoding="UTF-8"?>'.
			'<status>Service temporarialy unavailable</status>');
	}
}

function feed_when_redirected() {
	$feeds = array ('rdf', 'rss', 'rss2', 'atom');
	foreach ($feeds as $feed) {
		add_action('do_feed_'.$feed, 'redirected_feed', 1, 1);		
	}
}

function add_redirect_menu(){
	register_setting('redirect_menu_options','where_to_send','redirect_validation');
	add_options_page('redirect_options_menu','302 Moved Temporarily',8,'redirectoptions','create_redirect_menu');
}

function create_redirect_menu(){
	?>
	<div class="wrap">
		<h2>302 Moved Temporarily</h2>
		<form method="post" action="options.php">
		<table class="form-table">
			<tr valign="top">
			<th scope="row">Redirect URL</th>
			<td><input type="text" size="50" name="where_to_send" value="<?php echo get_option('where_to_send'); ?>" /></td>
			</tr>
		</table>
		<?php settings_fields( 'redirect_menu_options' ); ?>
		<p class="submit">
		<input type="submit" class="button-primary" value="<?php _e('Save Changes') ?>" />
		</p>
		</form>
	</div>
	<?php
}

function redirect_validation($input){
	$input=filter_var($input,FILTER_SANITIZE_URL);
	if(!preg_match('%^(?:http://|https://|mailto://)%',$input)){
		$input='http://'.$input;
	}
	if($input&&filter_var($input,FILTER_VALIDATE_URL)){
		return $input;
	}else{
		return false;
	}
}
add_action('admin_menu','add_redirect_menu');
if (function_exists('add_filter') ){
	add_filter('status_header', 'create_redirect_header', 10, 4);
	add_action('get_header', 'redirect_to');
	feed_when_redirected();
}else{
	die('Unauthorized');
}
?>