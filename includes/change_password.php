<?php
	/**
	 * SMS Gateway Center Bulk SMS Sender - A Wordpress SMS plugin
	 */
	if (!defined('ABSPATH'))
		exit;  // if direct access
	if (!function_exists('smsgatewaycenter_change_password')) {

		/**
		 * Display change password content and call the api upon submission
		 * @global type $sgcoption
		 * @throws Exception
		 */
		function smsgatewaycenter_change_password() {
			global $sgcoption;
			$headTitle = 'Change Password';
			include_once SGC_SMS_PLUGIN_DIR_PATH . 'header.php';
			smsgatewaycenter_check_login_auth_exists();
			switch ($sgcoption) {
				case 'sgc_passcode_change':
					$sendSGCSMSChangePasswordForm = !empty($_POST['sendSGCSMSChangePasswordForm']) ? sanitize_text_field(trim($_POST['sendSGCSMSChangePasswordForm'])) : 0;
					if (!empty($sendSGCSMSChangePasswordForm) && $sendSGCSMSChangePasswordForm === '1') {
						check_admin_referer('smsgatewaycenter_change_pass_nonce');
						$newpass = !empty($_POST['newpass']) ? sanitize_text_field(trim($_POST["newpass"])) : '';
						$confirmpass = !empty($_POST['confirmpass']) ? sanitize_text_field(trim($_POST["confirmpass"])) : '';
						if ($newpass == '') {
							smsgatewaycenter_show_html_error('New Password cannot be blank.');
							smsgatewaycenter_show_go_back_html();
							die;
						} elseif ($confirmpass == '') {
							smsgatewaycenter_show_html_error('Confirm Password cannot be blank.');
							smsgatewaycenter_show_go_back_html();
							die;
						} else {
							if ($newpass == $confirmpass) {
								$jsonDecodeSGCResponse = smsgatewaycenter_change_password_api_call($newpass, $confirmpass);
								$thead = '';
								$tbody = '';
								if ($GLOBALS['apiname'] === 'smsgateway.center') {
									foreach ($jsonDecodeSGCResponse as $key => $value) {
										if ('noofRecords' == $key) {
											continue;
										}
										$thead .= '<th>' . esc_attr($key) . '</th>';
										$tbody .= '<td>' . esc_attr($value) . '</td>';
									}
									$content = '';
									if ($tbody !== '') {
										$content .= '<tr>' . $tbody . '</tr>';
									}
								} else {
									foreach ($jsonDecodeSGCResponse->response as $key => $value) {
										if ($jsonDecodeSGCResponse->response->status == 'error') {
											$cls = ' style="background:red"';
										}
										$thead .= '<th style="background-color:green;color:white">' . esc_attr($key) . '</th>';
										$tbody .= '<td>' . esc_attr($value) . '</td>';
									}
									$content = '';
									if ($tbody !== '') {
										$content .= '<tr>' . $tbody . '</tr>';
									}
								}
								?>
								<h3>API Response</h3>
								<?php
								if ((!empty($jsonDecodeSGCResponse->status) && trim($jsonDecodeSGCResponse->status) === 'error') || (!empty($jsonDecodeSGCResponse->response->status) && trim($jsonDecodeSGCResponse->response->status) === 'error')) {
									$thead = '';
									$tbody = '';
									if ($GLOBALS['apiname'] === 'smsgateway.center') {
										$errorJson = $jsonDecodeSGCResponse;
									} else {
										$errorJson = $jsonDecodeSGCResponse->response;
									}
									foreach ($errorJson as $key => $value) {
										$thead .= '<th style="background-color:red;color:white">' . $key . '</th>';
										$tbody .= '<td>' . $value . '</td>';
									}
									$content = '';
									if ($tbody !== '') {
										$content .= '<tr>' . $tbody . '</tr>';
									}
								}
								smsgatewaycenter_main_table_html($thead, $content);

								if ((!empty($jsonDecodeSGCResponse->status) && trim($jsonDecodeSGCResponse->status) === 'success') || (!empty($jsonDecodeSGCResponse->response->status) && trim($jsonDecodeSGCResponse->response->status) === 'success')) {
									update_option('sgcsms_password', [$newpass]);
								} else {
									smsgatewaycenter_show_go_back_html();
									die;
								}
							} else {
								smsgatewaycenter_show_html_error('Password do not match.');
								smsgatewaycenter_show_go_back_html();
								die;
							}
						}
					}
					break;
				default:
					?>
					<div class="grid">
						<div class="w6">
							<form class="form-capsule" method=post action="<?php echo esc_url(sgc_get_admin_current_page_url()); ?>&sgcoption=sgc_passcode_change">
								<?php wp_nonce_field('smsgatewaycenter_change_pass_nonce'); ?>
								<div class="text">
									<label for="newpass"><?php esc_html_e('New Password', 'sgcsms'); ?></label>
									<input type="password" class="form-control" name="newpass" id="newpass" placeholder="<?php esc_html_e('New password', 'sgcsms'); ?>" required="">
								</div>
								<div class="text">
									<label for="username"><?php esc_html_e('Confirm New Password', 'sgcsms'); ?></label>
									<input type="password" class="form-control" name="confirmpass" id="confirmpass" placeholder="<?php esc_html_e('Confirm password', 'sgcsms'); ?>" required="">
								</div>
								<p class="submit">
									<input class="button3" type="submit" name="submit" value="<?php esc_html_e('Change Password', 'sgcsms'); ?>">
									<input type="hidden" name="sendSGCSMSChangePasswordForm" value="1">
								</p>
							</form>
						</div>
					</div>
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

	if (!function_exists('smsgatewaycenter_change_password_api_call')) {

		/**
		 * Make the API call to change password
		 * @param string $newpass new password of user
		 * @param string $confirmpassword confirm password of user
		 * @return array
		 * @throws Exception
		 */
		function smsgatewaycenter_change_password_api_call($newpass, $confirmpassword) {
			if ($GLOBALS['apiname'] === 'smsgateway.center') {
				$url = SGC_SMS_BASE_URL . '/library/api/self/ChangePassword/';
				$param['userId'] = $GLOBALS['username'];
				$param['password'] = urlencode($GLOBALS['password']);
				$param['NewPassword'] = urlencode($newpass);
				$param['format'] = 'json';
			} else {
				$url = SGC_UNIFY_SMS_BASE_URL . '/SMSApi/password/change';
				$param['userid'] = $GLOBALS['username'];
				$param['password'] = $GLOBALS['password'];
				$param['newpassword'] = $newpass;
				$param['confirmpassword'] = $confirmpassword;
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