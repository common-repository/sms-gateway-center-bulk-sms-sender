<?php
	/**
	  Plugin Name: SMS Gateway Center Bulk SMS Sender
	  Plugin URI: https://www.smsgatewaycenter.com
	  Description: Wordpress plugin to send bulk SMS from Wordpress sites. Send Bulk SMS from any WP site. Verify Mobile OTP for new registrations on your Wordpress site. Register at https://unify.smsgateway.center
	  Version: 1.3.1
	  Author: SMS Gateway Center
	  Author URI: https://www.smsgatewaycenter.com
	  License: GPL3
	  Text Domain: SGCSMS
	 */
	/**
	  Copyright 2010-2023  SMS Gateway Center  (E-mail: contact@smsgatewaycenter.com)
	  This program is free software; you can redistribute it and/or modify
	  it under the terms of the GNU General Public License, version 3, as
	  published by the Free Software Foundation.
	  This program is distributed in the hope that it will be useful,
	  but WITHOUT ANY WARRANTY; without even the implied warranty of
	  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
	  GNU General Public License for more details.
	  You should have received a copy of the GNU General Public License
	  along with this program; If not, see <http://www.gnu.org/licenses/>.
	 */
	if (!defined('ABSPATH'))
		die('No direct access allowed');

	/**
	 * Define constants for the SGC SMS plugin.
	 *
	 * This section sets up various constants used throughout the plugin.
	 */
	if (!defined('SGC_SMS_PLUGIN_VERSION')) {
		// Define the plugin version.
		define('SGC_SMS_PLUGIN_VERSION', '1.3.1');
	}

	if (!defined('SGC_SMS_ADMIN_URL')) {
		// Define the plugin's admin URL.
		define('SGC_SMS_ADMIN_URL', plugin_dir_url(__FILE__));
	}

	if (!defined('SGC_SMS_PLUGIN_DIR_PATH')) {
		// Define the plugin directory path.
		define('SGC_SMS_PLUGIN_DIR_PATH', plugin_dir_path(__FILE__));
	}

	if (!defined('SGC_SMS_BASE_URL')) {
		// Define the base URL for the SGC SMS service.
		define('SGC_SMS_BASE_URL', 'https://www.smsgateway.center');
	}

	if (!defined('SGC_UNIFY_SMS_BASE_URL')) {
		// Define the base URL for the SGC Unify SMS service.
		define('SGC_UNIFY_SMS_BASE_URL', 'https://unify.smsgateway.center');
	}

	if (!defined('SGC_SMS_MAX_SMS_CUSTOM')) {
		// Define the maximum number of custom SMS.
		define('SGC_SMS_MAX_SMS_CUSTOM', 2000);
	}

	if (!defined('SGC_SMS_CURR_DATE')) {
		// Define the current date in the specified format.
		define('SGC_SMS_CURR_DATE', date('Y-m-d H:i:s', current_time('timestamp')));
	}

	global $wpdb;
	$sgcoption = isset($_REQUEST['sgcoption']) ? sanitize_text_field(trim($_REQUEST['sgcoption'])) : '';
	//set username
	$username_option = get_option('sgcsms_username');
	$GLOBALS['username'] = is_array($username_option) ? $username_option[0] : '';

	//set password
	$password_option = get_option('sgcsms_password');
	$GLOBALS['password'] = is_array($password_option) ? $password_option[0] : '';

	//set api name
	$apiname_option = get_option('sgcsms_apiname');
	$GLOBALS['apiname'] = is_array($apiname_option) ? $apiname_option[0] : '';
	if ($GLOBALS['apiname'] === '') {
		$GLOBALS['apiname'] = 'smsgateway.center';
	}

	$wpoptions = get_option('sgcsms_otp_settings_option_name');
	$GLOBALS['wpoptions'] = $wpoptions;
	
	//include required files
	require_once SGC_SMS_PLUGIN_DIR_PATH . 'includes/functions.php';
	require_once SGC_SMS_PLUGIN_DIR_PATH . 'includes/api_auth_settings.php';
	require_once SGC_SMS_PLUGIN_DIR_PATH . 'includes/change_password.php';
	require_once SGC_SMS_PLUGIN_DIR_PATH . 'includes/check_balance.php';
	require_once SGC_SMS_PLUGIN_DIR_PATH . 'includes/credit_summary.php';
	require_once SGC_SMS_PLUGIN_DIR_PATH . 'includes/delivery_report.php';
	require_once SGC_SMS_PLUGIN_DIR_PATH . 'includes/groups.php';
	require_once SGC_SMS_PLUGIN_DIR_PATH . 'includes/incoming_sms.php';
	require_once SGC_SMS_PLUGIN_DIR_PATH . 'includes/message_templates.php';
	require_once SGC_SMS_PLUGIN_DIR_PATH . 'includes/send_group_sms.php';
	require_once SGC_SMS_PLUGIN_DIR_PATH . 'includes/sender_names_list.php';
	require_once SGC_SMS_PLUGIN_DIR_PATH . 'includes/subscribers/send_subscribers_sms.php';
	require_once SGC_SMS_PLUGIN_DIR_PATH . 'includes/subscribers/subscriber_groups.php';
	require_once SGC_SMS_PLUGIN_DIR_PATH . 'includes/subscribers/crud.php';
	require_once SGC_SMS_PLUGIN_DIR_PATH . 'includes/view_profile.php';
	require_once SGC_SMS_PLUGIN_DIR_PATH . 'includes/form_modifications_regi.php';
	require_once SGC_SMS_PLUGIN_DIR_PATH . 'includes/ajax_handlers_regi.php';
	require_once SGC_SMS_PLUGIN_DIR_PATH . 'includes/sendSMSApi.php';
	require_once SGC_SMS_PLUGIN_DIR_PATH . 'includes/otp_settings.php';
	require_once SGC_SMS_PLUGIN_DIR_PATH . 'includes/user_profile.php';

	add_action('admin_enqueue_scripts', 'smsgatewaycenter_enqueue_script');
	add_action('admin_menu', 'smsgatewaycenter_dot_com_sms_menu');
	add_filter('http_request_timeout', function ($timeout) {
		return 60;
	});

	if (!function_exists('smsgatewaycenter_enqueue_script')) {

		/**
		 * Wordpress menu
		 */
		function smsgatewaycenter_dot_com_sms_menu() {
			add_menu_page('SMSGatewayCenter.com', 'SGCSMS', '', 'sgcsms', 'smsgatewaycenter_api_credentials_settings', plugins_url('favicon.png', __FILE__), '10');
			add_submenu_page('sgcsms', 'Send Bulk SMS', 'Send Bulk SMS', 'manage_options', 'sgcsms_send', 'smsgatewaycenter_bulk_sms_sender');
			add_submenu_page('sgcsms', 'Send Group SMS', 'Send Group SMS', 'manage_options', 'sgcsms_send_group_sms', 'smsgatewaycenter_send_group_sms');
			add_submenu_page('sgcsms', 'Send SMS to Subscribers', 'Send SMS to Subscribers', 'manage_options', 'sgcsms_send_subscribers_sms', 'smsgatewaycenter_send_subscribers_sms');
			add_submenu_page('sgcsms', 'Groups', 'Groups', 'manage_options', 'sgcsms_groups', 'smsgatewaycenter_fetch_groups');
			add_submenu_page('sgcsms', 'Delivery Report', 'Delivery Report', 'manage_options', 'sgcsms_dlr', 'smsgatewaycenter_fetch_sms_delivery_report');
			add_submenu_page('sgcsms', 'Message Templates', 'Message Templates', 'manage_options', 'sgcsms_msgTemplates', 'smsgatewaycenter_fetch_message_templates');
			add_submenu_page('sgcsms', 'Incoming SMS', 'Incoming SMS', 'manage_options', 'sgcsms_shortcode', 'smsgatewaycenter_fetch_incoming_sms');
			add_submenu_page('sgcsms', 'WP Subscriber Groups', 'WP Subscriber Groups', 'manage_options', 'wpSubscriberGroups', 'smsgatewaycenter_wp_subscriber_groups');
			add_submenu_page('sgcsms', 'WP Subscribers', 'WP Subscribers', 'manage_options', 'wpSubscribers', 'smsgatewaycenter_wp_subscribers');
			add_submenu_page('sgcsms', 'Sender Names', 'Sender Names', 'manage_options', 'sgcsms_sid', 'smsgatewaycenter_fetch_sender_names');
			add_submenu_page('sgcsms', 'Credit Summary', 'Credit Summary', 'manage_options', 'sgcsms_credSumm', 'smsgatewaycenter_fetch_credit_summary');
			add_submenu_page('sgcsms', 'Check Balance', 'Check Balance', 'manage_options', 'sgcsms_checkbalance', 'smsgatewaycenter_check_balance');
			add_submenu_page('sgcsms', 'View Profile', 'View Profile', 'manage_options', 'sgcsms_viewprofile', 'smsgatewaycenter_view_profile');
			add_submenu_page('sgcsms', 'Change Password', 'Change Password', 'manage_options', 'sgcsms_changepass', 'smsgatewaycenter_change_password');
			add_submenu_page('sgcsms', 'SMS Account Settings', 'SMS Account Settings', 'manage_options', 'sgcsms_settings', 'smsgatewaycenter_api_credentials_settings');
			add_submenu_page('sgcsms', 'OTP Settings', 'OTP Settings', 'manage_options', 'sgcsms_otp_settings', 'smsgatewaycenter_otp_settings');
		}

	}

	if (!function_exists('smsgatewaycenter_enqueue_script')) {

		/**
		 * Enqueue Script for Wordpress
		 */
		function smsgatewaycenter_enqueue_script() {
			wp_enqueue_style('sgcsms_css_sgcsms', SGC_SMS_ADMIN_URL . 'assets/css/sgcsms.css', true, '1.0.92'); //admin css
			wp_enqueue_script('sgcsms_js_sgcsms', SGC_SMS_ADMIN_URL . 'assets/js/sgcsms_js.js', array('jquery'), '1.0.02', true); //admin js
			//check whether we can get current screen object if not then load on all admin pages
			if (function_exists('get_current_screen')) {
				if (in_array(get_current_screen()->base, ['sgcsms_page_sgcsms_dlr', 'sgcsms_page_sgcsms_credSumm'])) {
					wp_enqueue_style('sgcsms_css_Datatab;e', SGC_SMS_ADMIN_URL . 'assets/DataTables/datatables.min.css', true, '0.0.10');
					wp_enqueue_script('sgcsms_js_Datatable', SGC_SMS_ADMIN_URL . 'assets/DataTables/datatables.min.js');
				}
			}
		}

	}

	if (!function_exists('smsgatewaycenter_bulk_sms_sender')) {

		/**
		 * Send Bulk SMS
		 * @global type $sgcoption
		 * @throws Exception
		 */
		function smsgatewaycenter_bulk_sms_sender() {
			global $sgcoption;
			$isIndia = get_option('sgcsms_default_routing')[0];
			switch ($sgcoption) {
				case 'sendbulksms':
					$headTitle = 'Bulk SMS Sent Status';
					include_once 'header.php';
					smsgatewaycenter_check_login_auth_exists();
					$sendSGCSMSForm = isset($_POST["sendSGCSMSForm"]) ? sanitize_text_field(trim($_POST["sendSGCSMSForm"])) : '';
					if (!empty($sendSGCSMSForm) && $sendSGCSMSForm === '1' && isset($_POST['_wpnonce']) && wp_verify_nonce($_POST['_wpnonce'], 'sgc_send_bl_sms_frm')) {
						$smscontent = sanitize_textarea_field(trim($_POST["smscontent"]));
						$to = sanitize_text_field(trim($_POST["To"]));
						$mask = sanitize_text_field(trim($_POST["mask"]));
						$smstype = sanitize_text_field(trim($_POST["msgtype"]));
						if ($smscontent === '') {
							smsgatewaycenter_show_html_error('Text cannot be empty.');
							echo wp_kses_post('<p class="margt50">&nbsp;</p>');
							smsgatewaycenter_show_go_back_html();
							die();
						}
						if ($to === '') {
							smsgatewaycenter_show_html_error('Mobile Numbers not entered.');
							echo wp_kses_post('<p class="margt50">&nbsp;</p>');
							smsgatewaycenter_show_go_back_html();
							die();
						}
						if ($mask === '') {
							smsgatewaycenter_show_html_error('Sender Name not selected.');
							echo wp_kses_post('<p class="margt50">&nbsp;</p>');
							smsgatewaycenter_show_go_back_html();
							die();
						}
						$jsonDecodeSGCResponse = smsgatewaycenter_send_sms_api("quick", $to, $mask, $smscontent, $smstype, $isIndia);
						$thead = '';
						$tbody = '';
						foreach ($jsonDecodeSGCResponse as $key => $value) {
							if ((!empty($jsonDecodeSGCResponse->status) && trim($jsonDecodeSGCResponse->status) === 'error') || (!empty($jsonDecodeSGCResponse->response->status) && trim($jsonDecodeSGCResponse->response->status) === 'error')) {
								$cls = ' style="background-color:red;color:white"';
							} else {
								$cls = ' style="background-color:green;color:white"';
							}
							$thead .= '<th' . $cls . '>' . $key . '</th>';
							$tbody .= '<td>' . $value . '</td>';
						}
						$content = '';
						if ($tbody !== '') {
							$content .= '<tr>' . $tbody . '</tr>';
						}
						smsgatewaycenter_main_table_html($thead, $content);
						smsgatewaycenter_show_go_back_html();
					} else {
						smsgatewaycenter_show_html_error('Form verification failed.');
						echo wp_kses_post('<p class="margt50">&nbsp;</p>');
						smsgatewaycenter_show_go_back_html();
						die;
					}
					break;
				default:
					$headTitle = 'Bulk SMS Sender';
					include_once 'header.php';
					smsgatewaycenter_check_login_auth_exists();
					$sidarr = smsgatewaycener_get_senderId_list();
					?>
					<form class="form-capsule" method=post action="<?php echo esc_url(sgc_get_admin_current_page_url()); ?>&sgcoption=sendbulksms">
						<div class="grid">
							<div class="w6">
								<div class="form-group form-group-select">
									<label for="mask"><?php esc_html_e('Sender Name (required)', 'sgcsms'); ?></label>
									<div class="select-wrapper">
										<select class="form-control" name="mask" id="mask">
											<option value=""><?php esc_html_e('Please select', 'sgcsms'); ?></option>
					<?php
					foreach ($sidarr as $value) {
						?>
												<option value="<?php echo esc_attr($value); ?>" selected><?php echo esc_attr($value); ?></option>
												<?php
											}
											?>
										</select>
									</div>
								</div>
							</div>
							<div class="w6">
								<div class="form-group form-group-select">
									<label for="msgtype"><?php esc_html_e('Message Type (required)', 'sgcsms'); ?></label>
									<div class="select-wrapper">
										<select class="form-control" name="msgtype" id="msgtype">
											<option value="text" selected><?php esc_html_e('Text', 'sgcsms'); ?></option>
											<option value="unicode"><?php esc_html_e('Unicode', 'sgcsms'); ?></option>
										</select>
									</div>
								</div>
							</div>
						</div>
					<?php
					if ($isIndia == esc_attr('India')) {
						$placeholder = '9999999991,919999999992, 09999999993';
						$blockquotetip = 'Example: 9999999991,919999999992, 09999999993';
					} else {
						$placeholder = 'Prefix phone number with country code';
						$blockquotetip = 'Example: 2124567890,1124567890';
					}
					?>
						<div class="grid">
							<div class="w6">
								<div class="textarea">
									<label for="To"><?php esc_html_e('Mobile Numbers', 'sgcsms'); ?></label>
									<textarea class="form-control" rows="10" name="To" id="To" placeholder="<?php esc_html_e($placeholder, 'sgcsms'); ?>"></textarea>
									<blockquote><small>-Separate numbers with comma, <i><?php esc_html_e($blockquotetip, 'sgcsms'); ?></i><br>- <i>Use maximum 1000 numbers at once.</i></small></blockquote>
								</div>
							</div>
					<?php
					if ($isIndia == esc_attr('India')) {
						?>
								<div class="w6">
									<div class="text">
										<label for="dltTemplateId"><?php esc_html_e('DLT Template ID', 'sgcsms'); ?></label>
										<input type="text" class="form-control" name="dltTemplateId" id="dltTemplateId" placeholder="<?php esc_html_e('Enter DLT Template ID', 'sgcsms'); ?>">
									</div>
								</div>
					<?php } ?>
						</div>
						<div class="grid">
							<div class="w6">
								<div class="textarea">
									<label for="smscontent"><?php esc_html_e('Message', 'sgcsms'); ?></label>
									<textarea class="form-control" rows="10" name="smscontent" id="smscontent" placeholder="Add your message content here."></textarea>
								</div>
							</div>
						</div>

						<p class="submit">
							<input class="button3" type="submit" name="submit" value="Send Bulk SMS">
							<input type="hidden" name="sendSGCSMSForm" value="1">
					<?php wp_nonce_field('sgc_send_bl_sms_frm'); ?>
						</p>
					</form>
				<?php
			}
			?>
			<div class="margt50"></div>
			<div class="container">
				<div class="row">
					<div class="col">
			<?php sgc_write_html_rateUs(); ?>
					</div>
				</div>
			</div>
			<?php
		}

	}

	if (!function_exists('smsgatewaycenter_senderNames_api')) {

		/**
		 * Fetch Sender Names from API
		 * @return type
		 * @throws Exception
		 */
		function smsgatewaycenter_senderNames_api() {
			if ($GLOBALS['apiname'] === 'smsgateway.center') {
				$url = SGC_SMS_BASE_URL . '/library/api/self/SenderName/';
				$param['userId'] = $GLOBALS['username'];
				$param['password'] = $GLOBALS['password'];
				$param['do'] = 'list';
				$param['format'] = 'json';
			} else {
				$url = SGC_UNIFY_SMS_BASE_URL . '/SMSApi/senderid/read';
				$param['userid'] = $GLOBALS['username'];
				$param['password'] = $GLOBALS['password'];
				$param['output'] = 'json';
			}
			$postUrl = add_query_arg($param, $url);
			$parsedParams = wp_parse_args([], [
				'method' => 'POST'
			]);
			$sgc_api_response = wp_remote_request($postUrl, $parsedParams);
			if (is_wp_error($sgc_api_response)) {
				throw new Exception($sgc_api_response->get_error_message());
			}
			$smsgatewaycenter_api_response_code = wp_remote_retrieve_response_code($sgc_api_response);
			if (in_array($smsgatewaycenter_api_response_code, [200, 201, 202]) === false) {
				$smsgatewaycenter_api_response_error = json_decode($smsgatewaycenter_api_response, true);
				throw new Exception(sprintf(__('Failed to get success response, %s', 'sgcsms'), print_r($smsgatewaycenter_api_response_error, 1)));
			}
			$smsgatewaycenter_api_response = wp_remote_retrieve_body($sgc_api_response);
			return json_decode($smsgatewaycenter_api_response);
		}

	}

	if (is_admin()) {
		add_filter('plugin_action_links', 'smsgatewaycenter_plugin_page_settings_link', 10, 5);
		add_action('admin_notices', 'smsgatewaycenter_show_admin_notice_success');
	}

	if (!function_exists('smsgatewaycenter_plugin_page_settings_link')) {

		/**
		 * Plugin page settings 
		 * @staticvar type $plugin
		 * @param type $actions
		 * @param type $plugin_file
		 * @return type
		 */
		function smsgatewaycenter_plugin_page_settings_link($actions, $plugin_file) {
			static $plugin;

			if (!isset($plugin))
				$plugin = plugin_basename(__FILE__);
			if ($plugin == $plugin_file) {

				$wpsettingsurl = esc_url(add_query_arg(
						'page', 'sgcsms_settings', get_admin_url() . 'admin.php'
				));
				$sgcsitesurl = esc_url(add_query_arg(
						'', '', 'https://www.smsgateway.center'
				));
				$settings_link = "<a href='$wpsettingsurl'>" . __('Settings') . '</a>';

				$settings = array('settings' => $settings_link);
				$site_link = array('support' => '<a href="' . $sgcsitesurl . '" target="_blank">' . __('Support') . '</a>');

				$actions = array_merge($settings, $actions);
				$actions = array_merge($site_link, $actions);
			}
			return $actions;
		}

	}

	if (!function_exists('smsgatewaycenter_subscriber_table_install')) {

		/**
		 * Create subscriber related tables
		 * @global type $wpdb
		 */
		function smsgatewaycenter_subscriber_table_install() {
			global $wpdb;
			$charset_collate = $wpdb->get_charset_collate();
			$table_prefix = $wpdb->prefix;

			$sql_subscribe = "CREATE TABLE IF NOT EXISTS {$table_prefix}smsgatewaycenter_wp_subscribers(
					ID int(10) unsigned NOT NULL auto_increment,
					date DATETIME,
					name VARCHAR(20),
					mobile BIGINT(20) unsigned NOT NULL,
					group_ID int(10) unsigned NOT NULL,
					PRIMARY KEY(ID)) CHARSET=utf8";

			$sql_group = "CREATE TABLE IF NOT EXISTS {$table_prefix}smsgatewaycenter_wp_subscribers_group(
					ID int(10) unsigned NOT NULL auto_increment,
					name VARCHAR(20),
					PRIMARY KEY(ID)) CHARSET=utf8";
			require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
			dbDelta($sql_subscribe);
			dbDelta($sql_group);
		}

	}

	if (!function_exists('smsgatewaycenter_subscriber_data_uninstall')) {

		/**
		 * remove tables from DB 
		 * @global type $wpdb
		 */
		function smsgatewaycenter_subscriber_data_uninstall() {
			global $wpdb;
			$wpdb->query('DROP TABLE IF EXISTS ' . $wpdb->prefix . 'sgc_sms_alerts_subscribers');
			$wpdb->query('DROP TABLE IF EXISTS ' . $wpdb->prefix . 'sgc_sms_alerts_subscribers_group');
			delete_option('sgcsms_username');
			delete_option('sgcsms_password');
			delete_option('sgcsms_default_sendername');
			delete_option('sgcsms_default_routing');
		}

	}

	register_activation_hook(__FILE__, 'smsgatewaycenter_subscriber_table_install');
	register_uninstall_hook(__FILE__, 'smsgatewaycenter_subscriber_data_uninstall');

	//delete the test user but comment out before sending to wordpress prod
//	function delete_user_from_all_tables($user_id) {
//		global $wpdb;
//
//		// Delete user meta
//		$wpdb->delete($wpdb->usermeta, array('user_id' => $user_id));
//
//		// Delete user posts and related data
//		$user_posts = $wpdb->get_col("SELECT ID FROM $wpdb->posts WHERE post_author = $user_id");
//		foreach ($user_posts as $post_id) {
//			wp_delete_post($post_id, true);
//		}
//
//		// Delete user from users table
//		$wpdb->delete($wpdb->users, array('ID' => $user_id));
//
//		// Additional tables and data deletion can be added as needed
//		// Note: Be cautious about deleting from custom tables or plugins that might store user-related data.
//		// Optionally, you can perform additional actions after deletion
//		do_action('user_deleted_from_all_tables', $user_id);
//	}
//
//	// Example usage
//	$user_id_to_delete = 4;
//	delete_user_from_all_tables($user_id_to_delete);
	