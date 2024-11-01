<?php
	/**
	 * SMS Gateway Center Bulk SMS Sender - A Wordpress SMS plugin
	 */
	if (!defined('ABSPATH'))
		exit;  // if direct access
	if (!function_exists('smsgatewaycenter_show_html_error')) {

		/**
		 * Show error box with message
		 * @param type $msgStr
		 */
		function smsgatewaycenter_show_html_error($msgStr) {
			?>
			<div class="showAlert">
				<div class="alert alert-danger alert-dismissable">
					<strong>Oh snap!</strong> <?php echo esc_attr($msgStr); ?>
				</div>
			</div>
			<?php
		}

	}

	if (!function_exists('smsgatewaycenter_show_html_success')) {

		/**
		 * Show success box with message
		 * @param string $msgStr Message string
		 */
		function smsgatewaycenter_show_html_success($msgStr) {
			?>
			<div class="showAlert">
				<div class="alert alert-success alert-dismissable">
					<strong><?php esc_html_e('Well done!', 'sgcsms'); ?></strong> <?php echo esc_attr($msgStr); ?>
				</div>
			</div>
			<?php
		}

	}

	if (!function_exists('smsgatewaycenter_show_go_back_html')) {

		/**
		 * HTML code to go back to referer page
		 */
		function smsgatewaycenter_show_go_back_html() {
			?>
			<p><a class="button3" href="javascript:history.back(-1)"><?php esc_html_e('Go Back', 'sgcsms'); ?></a></p>
			<?php
		}

	}

	if (!function_exists('smsgatewaycenter_main_table_html')) {

		/**
		 * Print table for API records
		 * @param type $thead
		 * @param type $content
		 */
		function smsgatewaycenter_main_table_html($thead, $content) {
			$theadAllowedHtml = ['th' => ['style'=> [] ]];
			$tbodyAllowedHtml = ['tr' => [], 'td' => []];
			?>
			<table class="responsive wp-list-table widefat fixed posts mb-50">
				<thead><tr><?php echo wp_kses($thead, $theadAllowedHtml); ?></tr></thead>
				<tbody><?php echo wp_kses($content, $tbodyAllowedHtml); ?></tbody>
			</table>
			<?php
		}

	}

	if (!function_exists('sgc_print_array')) {

		/**
		 * Print array for debug
		 * @param type $array
		 */
		function sgc_print_array($array) {
			?>
			<pre>
				<?php
				print_r($array);
				?>
			</pre>
			<?php
		}

	}
	if (!function_exists('sgc_get_admin_current_page_url')) {

		function sgc_get_admin_current_page_url() {
			global $wp;
			return add_query_arg($_SERVER['QUERY_STRING'], '', home_url($wp->request) . '/wp-admin/admin.php');
		}

	}

	if (!function_exists('smsgatewaycenter_check_login_auth_exists')) {

		/**
		 * Check whether user has entered auth credentials, if not then kill it
		 */
		function smsgatewaycenter_check_login_auth_exists() {
			if (empty($GLOBALS['username']) && empty($GLOBALS['password'])) {
				smsgatewaycenter_show_html_error('Please add username and password in Settings page.');
				die();
			}
		}

	}

	if (!function_exists('smsgatewaycenter_is_loggedin')) {

		/**
		 * check user login auth exists in wordpress DB
		 * @return boolean
		 */
		function smsgatewaycenter_is_loggedin() {
			$ret = false;
			if (!empty($GLOBALS['username']) && !empty($GLOBALS['password'])) {
				$ret = true;
			}
			return $ret;
		}

	}

	if (!function_exists('smsgatewaycenter_show_admin_notice_success')) {

		/**
		 * Check user entered settings details else show admin notice
		 */
		function smsgatewaycenter_show_admin_notice_success() {
			if (empty($GLOBALS['username']) && empty($GLOBALS['password'])) {
				?>
				<div class="notice notice-success is-dismissible">
					<p>
						<?php
						echo wp_kses_post(sprintf(__('<a href="%s" target="_blank">Set up your SMS Gateway Center account</a> to send SMS', 'sgcsms'), 'admin.php?page=sgcsms_settings'));
						?>
					</p>
				</div>
				<?php
			}
		}

	}

	if (!function_exists('smsgatewaycenter_get_user_role_data')) {

		/**
		 * Get user data by role
		 * @param string $role 
		 * @return array
		 */
		function smsgatewaycenter_get_user_role_data($role = 'Subscriber') {
			$DBRecord = array();
			$args = array(
				'role' => $role,
				'orderby' => 'ID',
				'order' => 'DESC'
			);
			$users = get_users($args);
			$i = 0;
			foreach ($users as $user) {
				$DBRecord[$i]['role'] = "Subscriber";
				$DBRecord[$i]['WPId'] = $user->ID;
				$DBRecord[$i]['FirstName'] = $user->first_name;
				$DBRecord[$i]['LastName'] = $user->last_name;
				$DBRecord[$i]['RegisteredDate'] = $user->user_registered;
				$DBRecord[$i]['Email'] = $user->user_email;

				$UserData = get_user_meta($user->ID);
				$DBRecord[$i]['Company'] = $UserData['billing_company'][0];
				$DBRecord[$i]['Address'] = $UserData['billing_address_1'][0];
				$DBRecord[$i]['City'] = $UserData['billing_city'][0];
				$DBRecord[$i]['State'] = $UserData['billing_state'][0];
				$DBRecord[$i]['PostCode'] = $UserData['billing_postcode'][0];
				$DBRecord[$i]['Country'] = $UserData['billing_country'][0];
				$DBRecord[$i]['Phone'] = $UserData['billing_phone'][0];
				$i++;
			}
			return $DBRecord;
		}

	}

	if (!function_exists('smsgatewaycenter_send_sms_custom')) {

		/**
		 * Send Simple Message in POST method
		 * @param string $textMsg
		 * @param mixed $phones
		 * @return boolean
		 */
		function smsgatewaycenter_send_sms_custom($textMsg, $phones = array()) {
			if (sizeof(get_option('sgcsms_default_sendername')) <= 0) {
				throw new Exception(_e('Sender ID not found from Settings.', 'sgcsms'));
			}
			$params['userId'] = $GLOBALS['username'];
			$params['password'] = $GLOBALS['password'];
			$params['senderId'] = get_option('sgcsms_default_sendername')[0];
			$params['sendMethod'] = 'simpleMsg';
			$params['msgType'] = 'dynamic';
			$params['msg'] = $textMsg;
			$params['format'] = 'json';

			$sendSMSUrl = SGC_SMS_BASE_URL . '/SMSApi/rest/send';
			$ret = '<ul>';
			$bool = false;
			foreach ($phones as $batchCounter => $lres) {
				if ($batchCounter % SGC_SMS_MAX_SMS_CUSTOM == 0) {
					$params['mobile'] = implode(',', $lres);
					$response = wp_remote_post($sendSMSUrl, array('body' => $params));
					$jsonResponse = wp_remote_retrieve_body($response);
					$jsonDecodedResponse = json_decode($jsonResponse);
					$ret .= '<li>' . $jsonDecodedResponse->reason . '</li>';
				}
			}
			$ret .= '</ul>';
			return [$bool, $ret];
		}

	}

	if (!function_exists('sgc_write_html_rateUs')) {

		/**
		 * HTML content to show admin users to get ratings for plugin
		 * @return string
		 */
		function sgc_write_html_rateUs() {
			$wpPluginUrl = 'https://wordpress.org/support/plugin/sms-gateway-center-bulk-sms-sender/reviews/';
			?>
			<div class="grid">
				<div class="w6"></div>
				<div class="w6">
					<div class="sgc-sms-alerts-rate-div text-center bg-blue">
						<a href="<?php echo esc_url($wpPluginUrl); ?>" target="_blank">
							<div class="rating-stars">
								<div class="star-rating">
									<input type="radio" id="5-stars" name="rating" value="5" />
									<label for="5-stars" class="star">&#9733;</label>
									<input type="radio" id="4-stars" name="rating" value="4" />
									<label for="4-stars" class="star">&#9733;</label>
									<input type="radio" id="3-stars" name="rating" value="3" />
									<label for="3-stars" class="star">&#9733;</label>
									<input type="radio" id="2-stars" name="rating" value="2" />
									<label for="2-stars" class="star">&#9733;</label>
									<input type="radio" id="1-star" name="rating" value="1" />
									<label for="1-star" class="star">&#9733;</label>
								</div>
							</div>
							<div class="clearfix"></div>
							<div class="sgc-sms-alerts-rateUs"><?php echo esc_attr('Rate Us Now!'); ?></div>
						</a>
					</div>
				</div>
			</div>
			<?php
		}

	}

	if (!function_exists('smsgatewaycenter_show_notice')) {

		/**
		 * Display notice
		 * @param type $result
		 * @param type $message
		 * @return type
		 */
		function smsgatewaycenter_show_notice($result, $message) {
			if (empty($result)) {
				return;
			}
			if ($result == 'error') {
				?>
				<div class="showAlert margb50">
					<div class="alert alert-danger alert-dismissable">
						<?php echo esc_attr($message); ?>
					</div>
				</div>
				<?php
			}
			if ($result == 'update') {
				?>
				<div class="showAlert margb50">
					<div class="alert alert-success alert-dismissable">
						<?php echo esc_attr($message); ?>
					</div>
				</div>
				<?php
			}
			smsgatewaycenter_show_go_back_html();
		}

	}

	if (!function_exists('smsgatewaycenter_convert_epoch_to_date')) {

		/**
		 * Converts epoch time to readable date time
		 * @param int $epoch_with_milliseconds
		 * @return string
		 */
		function smsgatewaycenter_convert_epoch_to_date($epoch_with_milliseconds) {
			// Separate the seconds and milliseconds
			$seconds = floor($epoch_with_milliseconds / 1000);
			$milliseconds = $epoch_with_milliseconds % 1000;

			// Create a DateTime object from the seconds
			$date = new DateTime();
			$date->setTimestamp($seconds);

			// Add milliseconds (if necessary)
			// Note: DateTime in PHP does not handle milliseconds, so this is optional
			$formatted_date = $date->format('Y-m-d H:i:s');
			if ($milliseconds > 0) {
				//$formatted_date .= '.' . str_pad($milliseconds, 3, '0', STR_PAD_LEFT);
			}

			return $formatted_date;
		}

	}