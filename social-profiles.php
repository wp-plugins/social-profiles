<?php
/*
Plugin Name: Social Profiles
Version: 0.3
Plugin URI: http://sugarrae.com/wordpress/social-profiles/
Description: This plugin allows you to give your registered community members the option to show links to their social networking profile next to their comments on your site and the administrator has the ability to choose which social networks are shown.
Author: Joost de Valk
Author URI: http://yoast.com/
*/

// Pre-2.6 compatibility
if ( !defined('WP_CONTENT_URL') )
    define( 'WP_CONTENT_URL', get_option('siteurl') . '/wp-content');
if ( !defined('WP_CONTENT_DIR') )
    define( 'WP_CONTENT_DIR', ABSPATH . 'wp-content' );
 
// Guess the location
$cyc2_pluginpath = WP_CONTENT_URL.'/plugins/'.plugin_basename(dirname(__FILE__)).'/';

$cyc2_networks = Array(
	'Delicious' => Array(
		'heading' => 'Delicious',
		'name' => 'delicious',
		'pattern' => Array(
			'minlength' => 1,
			'maxlength' => 63,
		),
		'image' => $cyc2_pluginpath.'delicious.png',
		'url' => 'http://delicious.com/username',
	),
	'Digg' => Array(
		'heading' => 'Digg',
		'name' => 'digg',
		'pattern' => Array(
			'minlength' => 4,
			'maxlength' => 15,
		),
		'image' => $cyc2_pluginpath.'digg.png',
		'url' => 'http://digg.com/users/username/',
	),
	'Flickr' => Array(
		'heading' => 'Flickr',
		'name' => 'flickr',
		'pattern' => Array(
			'minlength' => 4,
			'maxlength' => 32,
		),
		'image' => $cyc2_pluginpath.'flickr.png',
		'url' => 'http://www.flickr.com/people/username/',
	),
	'Furl' => Array(
		'heading' => 'Furl',
		'name' => 'furl',
		'pattern' => Array(
			'minlength' => 1,
			'maxlength' => 25,
		),
		'image' => $cyc2_pluginpath.'furl.png',
		'url' => 'http://www.furl.net/member/username',
	),
	'MySpace' => Array(
		'heading' => 'MySpace',
		'name' => 'myspace',
		'pattern' => Array(
			'minlength' => 1,
			'maxlength' => 100,
		),
		'image' => $cyc2_pluginpath.'myspace.png',
		'url' => 'http://www.myspace.com/username',
	),
	'Newsvine' => Array(
		'heading' => 'Newsvine',
		'name' => 'newsvine',
		'pattern' => Array(
			'minlength' => 1,
			'maxlength' => 29,
		),
		'image' => $cyc2_pluginpath.'newsvine.png',
		'url' => 'http://username.newsvine.com/',
	),
	'Reddit' => Array(
		'heading' => 'Reddit',
		'name' => 'reddit',
		'pattern' => Array(
			'minlength' => 1,
			'maxlength' => 255,
		),
		'image' => $cyc2_pluginpath.'reddit.png',
		'url' => 'http://www.reddit.com/user/username/',
	),
	'StumbleUpon' => Array(
		'heading' => 'StumbleUpon',
		'name' => 'stumbleupon',
		'pattern' => Array(
			'minlength' => 1,
			'maxlength' => 16,
		),
		'image' => $cyc2_pluginpath.'stumbleupon.png',
		'url' => 'http://username.stumbleupon.com/',
	),
	'Technorati' => Array(
		'heading' => 'Technorati',
		'name' => 'technorati',
		'pattern' => Array(
			'minlength' => 4,
			'maxlength' => 30,
		),
		'image' => $cyc2_pluginpath.'technorati.png',
		'url' => 'http://technorati.com/people/technorati/username',
	),
	'Twitter' => Array(
		'heading' => 'Twitter',
		'name' => 'twitter',
		'pattern' => Array(
			'minlength' => 1,
			'maxlength' => 15,
		),
		'image' => $cyc2_pluginpath.'twitter.png',
		'url' => 'http://twitter.com/username',
	),
);

// Load additional networks
if (file_exists(WP_CONTENT_DIR.'/plugins/social-profiles-include.php')) {
	include(WP_CONTENT_DIR.'/plugins/social-profiles-include.php');
}

ksort($cyc2_networks);

// Set defaults, this will not override saved settings.
$cyc2_options['activeprofiles'] = array("flickr" => true, "digg" => true, "twitter" => true);
$cyc2_options['images'] 		= false;
$cyc2_options['nofollow'] 		= true;
$cyc2_options['prefix'] 		= "";
$cyc2_options['suffix'] 		= "";
add_option("cyc2_options",$cyc2_options);

// $cyc2_options['activeprofiles'] = array();
// update_option('cyc2_options',$cyc2_options);

$cyc2_options = get_option('cyc2_options');

if ( ! class_exists( 'SocialProfiles_Admin' ) ) {

	class SocialProfiles_Admin {
		
		function add_config_page() {
			global $wpdb;
			if ( function_exists('add_submenu_page') ) {
				add_options_page('Social Profiles Configuration', 'Social Profiles', 10, basename(__FILE__), array('SocialProfiles_Admin','config_page'));
				add_filter( 'plugin_action_links', array( 'SocialProfiles_Admin', 'filter_plugin_actions'), 10, 2 );
				add_filter( 'ozh_adminmenu_icon', array( 'SocialProfiles_Admin', 'add_ozh_adminmenu_icon' ) );
			}
		}
		
		function add_ozh_adminmenu_icon( $hook ) {
			if ($hook == 'social-profiles.php') {
				$hook = WP_CONTENT_URL . '/plugins/' . plugin_basename(dirname(__FILE__)). '/user_orange.png';
			}
			return $hook;
		}

		function filter_plugin_actions( $links, $file ){
			//Static so we don't call plugin_basename on every plugin row.
			static $this_plugin;
			if ( ! $this_plugin ) $this_plugin = plugin_basename(__FILE__);
			
			if ( $file == $this_plugin ){
				$settings_link = '<a href="options-general.php?page=social-profiles.php">' . __('Settings') . '</a>';
				array_unshift( $links, $settings_link ); // before other links
			}
			return $links;
		}
		
		function config_page() {
			global $cyc2_networks;
			
			$cyc2_options = get_option('cyc2_options');
			
			if ( isset($_POST['submit']) ) {
				if (!current_user_can('manage_options')) die(__('You cannot edit the Social Profiles options.'));
				check_admin_referer('socialprofiles-config');

				foreach (array('prefix','suffix') as $option_name) {
					if (isset($_POST[$option_name])) {
						$cyc2_options[$option_name] = stripslashes(html_entity_decode($_POST[$option_name]));
					}
				}

				foreach (array('images','nofollow') as $option_name) {
					if (isset($_POST[$option_name])) {
						$cyc2_options[$option_name] = true;
					} else {
						$cyc2_options[$option_name] = false;
					}
				}
								
				foreach ($cyc2_networks as $network) {
					$networkname = $network['name'];
					if (isset($_POST[$networkname])) {
						$cyc2_options['activeprofiles'][$networkname] = true;
					} else {
						$cyc2_options['activeprofiles'][$networkname] = false;
					}		
				}
				update_option('cyc2_options', $cyc2_options);
			}
			
			$cyc2_options = get_option('cyc2_options');
			
			if ($error != "") {
				echo "<div id=\"message\" class=\"error\">$error</div>\n";
			} elseif ($message != "") {
				echo "<div id=\"message\" class=\"updated fade\">$message</div>\n";
			}
			?>
			<div class="updated">
				<p>
					If you like this plugin, please help us keeping it up to date by <a href="https://www.paypal.com/cgi-bin/webscr?cmd=_s-xclick&amp;hosted_button_id=117063">donating a small token of your appreciation through PayPal</a>! 
				</p>
			</div>
			
			<div class="wrap">
				<h2>Social Profiles options</h2>
				<form action="" method="post" id="socialprofiles-conf">
					<?php
					if ( function_exists('wp_nonce_field') )
						wp_nonce_field('socialprofiles-config');
					?>
					<table class="form-table" style="width: 100%;">
						<tr valign="top">
							<th>Nofollow profile links</th>
							<td><input type="checkbox" name="nofollow" <?php if($cyc2_options['nofollow']) { echo 'checked="checked"'; } ?>/></td>
						</tr>
						<tr valign="top">
							<th>Use images</th>
							<td><input type="checkbox" name="images" <?php if($cyc2_options['images']) { echo 'checked="checked"'; } ?>/></td>
						</tr>
						<tr>
							<th>Active profiles</th>
							<td>
					<?php
					foreach ($cyc2_networks as $network) {
						$checked = '';
						if ($cyc2_options['activeprofiles'][$network["name"]]) {
							$checked = 'checked="checked"';
						} 
						echo "<input type=\"checkbox\" $checked name=\"".$network['name']."\"/> ".$network['heading']."<br/>";
					}
					?>
							</td>
						</tr>
						<tr valign="top">
							<th>Prefix output</th>
							<td><input type="text" name="prefix" value="<?php echo htmlentities($cyc2_options['prefix']); ?>"/></td>
						</tr>
						<tr valign="top">
							<th>Suffix output</th>
							<td><input type="text" name="suffix" value="<?php echo htmlentities($cyc2_options['suffix']); ?>"/></td>
						</tr>
					</table>
					<p class="submit"><input type="submit" name="submit" value="Update Settings &raquo;" /></p>
				</form>
			</div>
<?php		}	
	}
}

function cyc2_social_network($network, $user_id) {
	$field = 'cyc2_'.$network['name'];
	$value = get_usermeta($user_id, $field);
	echo '<tr>';
	echo '<th>'.$network['heading'].' username</th>';
	echo '<td><input type="text" name="'.$field.'" size="50" value="'.$value.'"/></td>';
	echo '</tr>';
}

function cyc2_profile_addon() {
	global $cyc2_options, $cyc2_networks, $user_id;
	if ( !$user_id ) {
		$current_user = wp_get_current_user();
		$user_id = $current_user->ID;
	}
	echo '<h3>Social Networks</h3>';
	echo '<p>Put the username you use at each of the following networks into the corresponding box. You only need to put the username and not the full url. If you don\'t have a username for the network listed, please leave the box blank.</p>';
	echo '<table class="form-table">';
	foreach ($cyc2_networks as $network) {
		// Check whether the social network is set to active.
		if ($cyc2_options['activeprofiles'][$network['name']])
			cyc2_social_network($network, $user_id);
	}
	echo '</table>';
}

function cyc2_update_profile() {
	global $cyc2_networks, $user_id;
	if ( isset($_GET['user_id']) ) {
		$user_id = $_GET['user_id'];
	}
	if ( !$user_id ) {
		$current_user = wp_get_current_user();
		$user_id = $current_user->ID;
	}
	foreach ($cyc2_networks as $network) {
		$field = 'cyc2_'.$network['name'];
		if (isset($_POST[$field])) {
			$value = trim(strip_tags($_POST[$field]));
			if (strlen($value) >= $network['pattern']['minlength'] && strlen($value) <= $network['pattern']['maxlength']) {
				update_usermeta($user_id, $field, $value);
			} else {
				update_usermeta($user_id, $field, "");
			}
		}
	}
}

function cyc2_show_profiles($user_id) {
	global $cyc2_networks, $cyc2_options, $cyc2_pluginpath;

	if ($cyc2_options['nofollow'])
		$nofollow = 'rel="nofollow"';
	else
		$nofollow = '';

	$i = 1;
	foreach ($cyc2_networks as $network) {
		if ($cyc2_options['activeprofiles'][$network['name']]) {
			$field 		= 'cyc2_'.$network['name'];
			$username 	= get_usermeta($user_id, $field);
			if ($username && $username != "") {
				if ($i == 1) {
					echo $cyc2_options['prefix'];
				}
				if ($i > 1 && !$cyc2_options['images']) {
					echo "|";
				} 

				$url = str_replace("username",$username,$network['url']);
				
				echo " <a $nofollow href=\"$url\">";
				if ($network['image'] && $cyc2_options['images']) {
					echo "<img src=\"".$network['image']."\" alt=\"".$network['heading']."\"/>";
				} else {
					echo $network['heading'];
				}
				echo "</a> " ;
				$i++;
			}
		}
	}
	if ($i > 1) {
		echo $cyc2_options['suffix'];
	}
}

add_action('show_user_profile','cyc2_profile_addon',10,1);
add_action('edit_user_profile','cyc2_profile_addon',10,1);
add_action('personal_options_update','cyc2_update_profile',10,1);
add_action('profile_update','cyc2_update_profile',10,1);
add_action('admin_menu', array('SocialProfiles_Admin','add_config_page'));

?>