<?php
	/**
	 * SMS Gateway Center Bulk SMS Sender - A Wordpress SMS plugin
	 */
	if (!function_exists('smsgatewaycenter_api_credentials_settings')) {

		/**
		 * Save user API auth credentials
		 * @global type $sgcoption
		 */
		function smsgatewaycenter_api_credentials_settings() {
			global $sgcoption;
			$headTitle = 'Account Settings';
			include_once SGC_SMS_PLUGIN_DIR_PATH . 'header.php';
			if (current_user_can('manage_options')) {
				switch ($sgcoption) {
					case 'addnl_settings':
						$sendSGCSMSAddnlSettingsForm = !empty($_POST['sendSGCSMSAddnlSettingsForm']) ? sanitize_text_field(trim($_POST['sendSGCSMSAddnlSettingsForm'])) : 0;
						if (!empty($sendSGCSMSAddnlSettingsForm) && $sendSGCSMSAddnlSettingsForm === '1') {
							check_admin_referer('smsgatewaycenter_addnl_settings_nonce');

							$gdefSenderName = !empty($_POST['defSenderName']) ? sanitize_text_field(trim($_POST['defSenderName'])) : '';
							if ($gdefSenderName == '') {
								smsgatewaycenter_show_html_error('Select a default sender name.');
								smsgatewaycenter_show_go_back_html();
								die();
							}
							$gdefRouting = !empty($_POST['defRouting']) ? sanitize_text_field(trim($_POST['defRouting'])) : '';
							if ($gdefRouting == '') {
								smsgatewaycenter_show_html_error('Please select routing.');
								smsgatewaycenter_show_go_back_html();
								die();
							}
							if ($gdefSenderName !== '' && $gdefRouting !== '') {
								if (empty(get_option('sgcsms_default_sendername')[0]) || sizeof(get_option('sgcsms_default_sendername')) <= 0) {
									add_option('sgcsms_default_sendername', [$gdefSenderName]);
								}
								if (sizeof(get_option('sgcsms_default_sendername')) > 0) {
									update_option('sgcsms_default_sendername', [$gdefSenderName]);
								}
								if (sizeof(get_option('sgcsms_default_routing')) > 0) {
									update_option('sgcsms_default_routing', [$gdefRouting]);
								}
								smsgatewaycenter_show_html_success('Your default settings have been saved!');
								smsgatewaycenter_show_go_back_html();
							}
						}
						break;
					case 'smsgatewaycenter_logout':
						if (smsgatewaycenter_logout()) {
							smsgatewaycenter_show_html_success('You are successfully logged out!');
							smsgatewaycenter_show_go_back_html();
							die();
						} else {
							smsgatewaycenter_show_html_error('Something went wrong.');
							smsgatewaycenter_show_go_back_html();
							die();
						}
						smsgatewaycenter_show_go_back_html();
						break;
					case 'api_auth':
						$sendSGCSMSSettingsForm = !empty($_POST['sendSGCSMSSettingsForm']) ? sanitize_text_field(trim($_POST['sendSGCSMSSettingsForm'])) : 0;
						if (!empty($sendSGCSMSSettingsForm) && $sendSGCSMSSettingsForm === '1') {
							$userAuthenticated = true;
							check_admin_referer('smsgatewaycenter_api_auth_nonce');
							$gusername = !empty($_POST['username']) ? sanitize_text_field(trim($_POST['username'])) : '';
							$gpassword = !empty($_POST['password']) ? sanitize_text_field(trim($_POST['password'])) : '';
							$gapiname = !empty($_POST['apiName']) ? sanitize_text_field(trim($_POST['apiName'])) : '';
							$gdefRouting = !empty($_POST['defRouting']) ? sanitize_text_field(trim($_POST['defRouting'])) : '';

							if ($gusername == '') {
								smsgatewaycenter_show_html_error('Username cannot be blank.');
								smsgatewaycenter_show_go_back_html();
								die;
							}
							if ($gpassword == '') {
								smsgatewaycenter_show_html_error('Password cannot be blank.');
								smsgatewaycenter_show_go_back_html();
								die;
							}
							if ($gapiname == '') {
								smsgatewaycenter_show_html_error('Please select an API.');
								smsgatewaycenter_show_go_back_html();
								die;
							}
							if ($gdefRouting == '') {
								smsgatewaycenter_show_html_error('Please select routing.');
								smsgatewaycenter_show_go_back_html();
								die();
							}
							//verify api auth
							if (!smsgatewaycenter_authenticate_api_credentials_settings($gusername, $gpassword, $gapiname)) {
								$userAuthenticated = false;
								smsgatewaycenter_show_html_error('Invalid user credentials.');
								smsgatewaycenter_show_go_back_html();
								die;
							}
							if ($gusername !== '' && $gpassword !== '' && $gapiname !== '' && $userAuthenticated) {
								if (empty(get_option('sgcsms_username')[0]) || sizeof(get_option('sgcsms_username')) <= 0) {
									add_option('sgcsms_username', [$gusername]);
								}
								if (empty(get_option('sgcsms_password')[0]) || sizeof(get_option('sgcsms_password')) <= 0) {
									add_option('sgcsms_password', [$gpassword]);
								}
								if (empty(get_option('sgcsms_apiname')[0]) || sizeof(get_option('sgcsms_apiname')) <= 0) {
									add_option('sgcsms_apiname', [$gapiname]);
								}
								if (empty(get_option('sgcsms_default_routing')[0]) || sizeof(get_option('sgcsms_default_routing')) <= 0) {
									add_option('sgcsms_default_routing', [$gdefRouting]);
								}
								if (!smsgatewaycenter_is_loggedin()) {
									if (sizeof(get_option('sgcsms_username')) > 0) {
										update_option('sgcsms_username', [$gusername]);
									}
									if (sizeof(get_option('sgcsms_password')) > 0) {
										update_option('sgcsms_password', [$gpassword]);
									}
									if (sizeof(get_option('sgcsms_apiname')) > 0) {
										update_option('sgcsms_apiname', [$gapiname]);
									}
									if (sizeof(get_option('sgcsms_default_routing')) > 0) {
										update_option('sgcsms_default_routing', [$gdefRouting]);
									}
								}
								smsgatewaycenter_show_html_success('Your auth credentials have been saved! Go back and save default settings.');
								smsgatewaycenter_show_go_back_html();
							} else {
								smsgatewaycenter_show_html_error('Username, password or API name field is missing.');
								smsgatewaycenter_show_go_back_html();
							}
						}
						break;
					default:
						?>
						<!--<p><?php esc_html_e('Add your username and password.', 'sgcsms'); ?></p>-->
						<form class="form-capsule" style="margin-top:10px" method=post action="<?php echo esc_url(sgc_get_admin_current_page_url()); ?>&sgcoption=api_auth">
							<div class="grid">
								<div class="w6">
									<h3 class="h3-title"><?php esc_html_e('SMS API Account Settings', 'sgcsms'); ?></h3>
									<div class="postbox">
										<div class="inside">
											<?php wp_nonce_field('smsgatewaycenter_api_auth_nonce'); ?>
											<div class="text form-group form-group-select">
												<label for="apiName"><?php esc_html_e('Select an API (required)', 'sgcsms'); ?></label>
												<div class="select-wrapper">
													<select class="form-control" name="apiName" id="apiName" required="">
														<option value="" selected><?php esc_html_e('Please select', 'sgcsms'); ?></option>
														<option value="<?php esc_html_e('smsgateway.center', 'sgcsms'); ?>"<?php echo esc_attr(smsgatewaycenter_get_saved_apiname_selected('smsgateway.center')); ?>><?php esc_html_e('smsgateway.center', 'sgcsms'); ?></option>
														<option value="<?php esc_html_e('unify.smsgateway.center', 'sgcsms'); ?>"<?php echo esc_attr(smsgatewaycenter_get_saved_apiname_selected('unify.smsgateway.center')); ?>><?php esc_html_e('unify.smsgateway.center', 'sgcsms'); ?></option>
														?>
													</select>
													<span class="help-block text-green text-sms"><small><i>If you are not migrated to unified portal, then select smsgateway.center.</i></small></span>
												</div>
											</div>
											<?php if (!smsgatewaycenter_is_loggedin()) { ?>
												<div class="form-group form-group-select">
													<label for="defRouting"><?php esc_html_e('Select Routing (required)', 'sgcsms'); ?></label>
													<div class="select-wrapper">
														<select class="form-control" name="defRouting" id="defRouting" required="">
															<option value="<?php esc_html_e('India', 'sgcsms'); ?>"<?php echo esc_attr(smsgatewaycenter_get_default_routing_selected('India')); ?>><?php esc_html_e('India', 'sgcsms'); ?></option>
															<option value="<?php esc_html_e('International', 'sgcsms'); ?>"<?php echo esc_attr(smsgatewaycenter_get_default_routing_selected('International')); ?>><?php esc_html_e('International', 'sgcsms'); ?></option>
														</select>
														<span class="help-block text-green text-sms"><small><i><b>India</b> routing will be used when SMS sent. This will also help to show Indian DLT attributes.</i></small></span>
													</div>
												</div>
											<?php } ?>
											<div class="text">
												<label for="username"><?php esc_html_e('Username', 'sgcsms'); ?></label>
												<input type="text" class="form-control" name="username" id="username" value="<?php echo esc_attr($GLOBALS['username']); ?>" placeholder="<?php esc_html_e('Your username', 'sgcsms'); ?>" required="">
												<span class="help-block text-green text-sms"><small>Your registered username on smsgateway.center <a href="<?php echo esc_url('http://www.smsgateway.center'); ?>" target="_blank">get it here</a>.</small></span>
											</div>
											<?php if (!smsgatewaycenter_is_loggedin()) { ?>
												<div class="text">
													<label for="password"><?php esc_html_e('Password', 'sgcsms'); ?></label>
													<input type="password" class="form-control" name="password" id="password" value="<?php echo esc_attr($GLOBALS['password']); ?>" placeholder="<?php esc_html_e('Your password', 'sgcsms'); ?>" required="">
													<span class="help-block text-green text-sms"><small>Your password which you use to login at SMSGatewayCenter.com</small></span>
												</div>
												<p class="submit">
													<input class="button3" type="submit" name="submit" value="<?php esc_html_e('Authenticate', 'sgcsms'); ?>">
													<input type="hidden" name="sendSGCSMSSettingsForm" value="1">
												</p>
											<?php } ?>
										</div>
									</div>
								</div>
								<div class="w6">
									<!--include when customer needs it for 2nd account api_auth_setttings_intl.php-->
								</div>
							</div>
						</form>
						<?php
						if (smsgatewaycenter_is_loggedin()) {
							//smsgatewaycenter_check_login_auth_exists();
							$sidarr = smsgatewaycener_get_senderId_list();
							?>
							<form class="form-capsule" style="margin-top:0" method=post action="<?php echo esc_url(sgc_get_admin_current_page_url()); ?>&sgcoption=addnl_settings">
								<div class="grid">
									<div class="w6">
										<h3 class="h3-title"><?php esc_html_e('Additional Settings', 'sgcsms'); ?></h3>
										<div class="postbox">
											<div class="inside">
												<?php wp_nonce_field('smsgatewaycenter_addnl_settings_nonce'); ?>
												<div class="form-group form-group-select">
													<label for="defRouting"><?php esc_html_e('Select Routing (required)', 'sgcsms'); ?></label>
													<div class="select-wrapper">
														<select class="form-control" name="defRouting" id="defRouting" required="">
															<option value="<?php esc_html_e('India', 'sgcsms'); ?>"<?php echo esc_attr(smsgatewaycenter_get_default_routing_selected('India')); ?>><?php esc_html_e('India', 'sgcsms'); ?></option>
															<option value="<?php esc_html_e('International', 'sgcsms'); ?>"<?php echo esc_attr(smsgatewaycenter_get_default_routing_selected('International')); ?>><?php esc_html_e('International', 'sgcsms'); ?></option>
														</select>
														<span class="help-block text-green text-sms"><small><i><b>India</b> routing will be used when SMS sent. This will also help to show Indian DLT attributes.</i></small></span>
													</div>
												</div>
												<div class="form-group form-group-select">
													<label for="defSenderName"><?php esc_html_e('Select Default Sender Name (required)', 'sgcsms'); ?></label>
													<div class="select-wrapper">
														<select class="form-control" name="defSenderName" id="defSenderName" required="">
															<option value="" selected><?php esc_html_e('Please select', 'sgcsms'); ?></option>
															<?php
															foreach ($sidarr as $value) {
																?>
																<option value="<?php echo esc_attr($value); ?>"<?php echo esc_attr(smsgatewaycenter_get_default_sendername_selected($value)); ?>><?php echo esc_attr($value); ?></option>
																<?php
															}
															?>
														</select>
														<span class="help-block text-green text-sms"><small><i>Default sender name will be used when SMS sent in general.</i></small></span>
													</div>
												</div>
												<p class="submit">
													<input class="button3" type="submit" name="submit" value="<?php esc_html_e('Save Settings', 'sgcsms'); ?>">
													<a href="<?php echo esc_url(sgc_get_admin_current_page_url()); ?>&sgcoption=smsgatewaycenter_logout" class="button3" style="float:right"><?php esc_html_e('Logout', 'sgcsms'); ?></a>
													<input type="hidden" name="sendSGCSMSAddnlSettingsForm" value="1">
												</p>
											</div>
										</div>
									</div>
									<div class="w6"></div>
								</div>
							</form>
							<?php
						}
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
			} else {
				smsgatewaycenter_show_html_error('You are not authorised!');
				smsgatewaycenter_show_go_back_html();
			}
		}

	}

	if (!function_exists('smsgatewaycenter_get_default_sendername_selected')) {

		/**
		 * Select default sender name
		 * @param string $value
		 * @return string
		 */
		function smsgatewaycenter_get_default_sendername_selected($value) {
			$selected = '';
			$dsid_option = get_option('sgcsms_default_sendername');
			$sid_option = is_array($dsid_option) ? $dsid_option[0] : '';
			if ($sid_option == esc_attr($value)) {
				$selected = esc_html_e('selected ', 'sgcsms');
			}
			return $selected;
		}

	}

	if (!function_exists('smsgatewaycenter_get_saved_apiname_selected')) {

		/**
		 * Select saved api name
		 * @param string $value
		 * @return string
		 */
		function smsgatewaycenter_get_saved_apiname_selected($value) {
			$selected = '';
			if ($GLOBALS['apiname'] == esc_attr($value)) {
				$selected = esc_html_e('selected ', 'sgcsms');
			}
			return $selected;
		}

	}

	if (!function_exists('smsgatewaycenter_get_default_routing_selected')) {

		/**
		 * Select default routing
		 * @param string $value 
		 * @return string
		 */
		function smsgatewaycenter_get_default_routing_selected($value) {
			$selected = '';
			$drouting_option = get_option('sgcsms_default_routing');
			$routing_option = is_array($drouting_option) ? $drouting_option[0] : '';
			if ($routing_option == esc_attr($value)) {
				$selected = esc_html_e('selected ', 'sgcsms');
			}
			return $selected;
		}

	}

	if (!function_exists('smsgatewaycenter_authenticate_api_credentials_settings')) {

		/**
		 * Authenticate User Credentials
		 * @param string $username registered username of SMS Gateway Center
		 * @param string $password registered password of SMS Gateway Center
		 * @return boolean 
		 * @throws Exception
		 */
		function smsgatewaycenter_authenticate_api_credentials_settings($username, $password, $apiname) {
			if ($apiname === 'smsgateway.center') {
				$url = SGC_SMS_BASE_URL . '/library/api/self/auth/';

				$param['userId'] = $username;
				$param['password'] = $password;
				$param['format'] = 'json';
				$postUrl = add_query_arg($param, $url);
				$parsedParams = wp_parse_args([], [
					'method' => 'POST'
				]);
			} else {
				$postUrl = SGC_UNIFY_SMS_BASE_URL . '/me/';
				$base64encoded = base64_encode($username . ':' . $password);
				$headers = array(
					'Authorization' => 'Basic ' . $base64encoded,
					'Content-Type' => 'application/json'
				);
				$parsedParams = array(
					'method' => 'POST',
					'headers' => $headers,
				);
			}

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
			$jsonDecodeSGCResponse = json_decode($smsgatewaycenter_api_response);
			if (esc_attr($jsonDecodeSGCResponse->status) === 'success') {
				return true;
			} else {
				return false;
			}
		}

	}

	if (!function_exists('smsgatewaycenter_logout')) {

		/**
		 * lets log out user
		 * @return boolean
		 */
		function smsgatewaycenter_logout() {
			delete_option('sgcsms_default_sendername');
			delete_option('sgcsms_default_routing');
			if (delete_option('sgcsms_username') && delete_option('sgcsms_password')) {
				$GLOBALS['username'] = '';
				$GLOBALS['password'] = '';
				return true;
			}
			return false;
		}

	}

	if (!function_exists('smsgatewaycener_get_senderId_list')) {

		/**
		 * Get Sender Lists using API
		 * @return array
		 */
		function smsgatewaycener_get_senderId_list() {
			$jsonSid = smsgatewaycenter_senderNames_api();

			$sidarr = [];
			if ($GLOBALS['apiname'] === 'smsgateway.center') {
				if ($jsonSid->reason == 'Data Fetched.') {
					foreach ($jsonSid->SenderNames as $sgcrow) {
						if ($sgcrow->Status !== 'Approved') {
							continue;
						}
						$sidarr[] = $sgcrow->senderName;
					}
				}
			} else {
				if ($jsonSid->response->status == 'success') {
					if (!empty($jsonSid->response->senderidList) && is_array($jsonSid->response->senderidList)) {
						foreach ($jsonSid->response->senderidList as $item) {
							if (isset($item->senderid) && $item->senderid->isEnabled === 'Active') {
								$sidarr[] = $item->senderid->senderName;
							}
						}
					}
				}
			}
			return $sidarr;
		}

	}
	