<?php
/*
Plugin Name: 302 Moved Temporarily
Plugin URI: 
Description: Redirects any unauthenticated users to another address by sending them a '302 Moved Temporarily' message.
Author: WP Status
Version: 1.2
Author URI: http://www.wpstatus.com
License: GPL 2.0, @see http://www.gnu.org/licenses/gpl-2.0.html
*/

if(!class_exists('WP_302_Redirect'))
{
	class WP_302_Redirect
	{
		public $plugin;

		public function __construct()
		{
			add_action('admin_init', array(&$this, 'admin_init'));
			add_action('admin_menu', array(&$this, 'add_menu'));
			add_action('wp', array(&$this, 'init'));
			$this->plugin = plugin_basename(__FILE__); 
		}

		public static function activate()
		{

		}

		public function admin_init()
		{
			$this->init_settings();
			add_filter("plugin_action_links_$this->plugin", array(&$this, 'settings_link'));
		}

		public function add_menu()
		{
			add_options_page('302 Moved Temporarily', '302 Moved Temporarily', 'manage_options', 'wp_redirect_options', array(&$this, 'create_redirect_menu'));
		}

		public function init()
		{
			$enabled = get_option('enabled');

			if(!is_user_logged_in() && $enabled != FALSE)
			{
				add_filter('status_header', array(&$this, 'create_redirect_header'), 10, 4);
				$this->feed_when_redirected();
				$this->redirect();
			}
		}

		public function settings_link($links) 
		{ 
		  $settings_link = '<a href="options-general.php?page=wp_redirect_options">Settings</a>'; 
		  array_unshift($links, $settings_link); 
		  return $links; 
		}

		public function redirect()
		{
			header('location: '.get_option('redirect_to'));
			die;
		}

		public function create_redirect_header($status_header, $header, $text, $protocol) 
		{
			return "$protocol 302 Moved Temporarily";
		}

		public function feed_when_redirected()
		{
			$feeds = array ('rdf', 'rss', 'rss2', 'atom');
			foreach ($feeds as $feed) 
			{
				add_action('do_feed_'.$feed, array(&$this, 'redirected_feed'), 1, 1);		
			}
		}

		public function redirect_feed()
		{
			die('<?xml version="1.0" encoding="UTF-8"?>'.
				'<status>Service temporarialy unavailable</status>');
		}

		public function init_settings()
		{
			// print_r($_POST); die;
			register_setting('wp_redirect_options','redirect_to', array(&$this, 'redirect_validation'));
			register_setting('wp_redirect_options', 'enabled', 'intval');
		}

		public function redirect_validation($input)
		{
			$input = filter_var($input, FILTER_SANITIZE_URL);
			if(!preg_match('%^(?:http://|https://|mailto://)%', $input))
			{
				$input = 'http://'.$input;
			}

			if($input && filter_var($input, FILTER_VALIDATE_URL))
			{
				return $input;
			}
			else
			{
				return false;
			}
		}

		public function create_redirect_menu()
		{
			if(!current_user_can('manage_options')) 
			{ 
				wp_die(__('You do not have sufficient permissions to access this page.')); 
			}
			?>
			<div class="wrap">
				<h2>302 Moved Temporarily</h2>
				<form method="post" action="options.php">
				<?php @settings_fields('wp_redirect_options'); ?> 
				<?php @do_settings_fields('wp_redirect_options'); ?>
				<table class="form-table">
					<tr valign="top">
						<th scope="row">Redirect URL</th>
						<td><input type="text" size="50" name="redirect_to" value="<?php echo get_option('redirect_to'); ?>" /></td>
					</tr>
					<tr>
						<th scope="row">Enabled</th>
						<td><input type="checkbox" id="enabled" name="enabled" value="1" <?php echo get_option('enabled') != FALSE ? 'checked' : ''; ?> /></td>
					</tr>
					</tr>
				</table>
				<?php @submit_button(); ?>
				</form>
			</div>
			<?php
		}
	}
}

if(class_exists('WP_302_Redirect'))
{
	register_activation_hook(__FILE__, array('WP_302_Redirect', 'activate'));
	new WP_302_Redirect();
}
