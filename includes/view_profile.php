<?php
	/**
	 * SMS Gateway Center Bulk SMS Sender - A Wordpress SMS plugin
	 */
	if (!defined('ABSPATH'))
		exit;  // if direct access
	if (!function_exists('smsgatewaycenter_view_profile')) {

		/**
		 * View profile
		 * @throws Exception
		 */
		function smsgatewaycenter_view_profile() {
			smsgatewaycenter_check_login_auth_exists();
			$headTitle = 'View Profile';
			include_once SGC_SMS_PLUGIN_DIR_PATH . 'header.php';
			if (current_user_can('manage_options')) {
				$json = smsgatewaycenter_view_profile_api_call();
				if ($GLOBALS['apiname'] === 'smsgateway.center') {
					$userFullName = $json->UserProfile[0]->FullName;
					$userEmail = $json->UserProfile[0]->Email;
				} else {
					$userFullName = $json->response->account->fullName;
					$userEmail = '';
				}
				?>
				<div class="grid">
					<div class="w12">
						<div class="profile">
							<figure>
								<img src="<?php echo esc_url(plugins_url('assets/images/avatar.png', dirname(__FILE__))); ?>" alt="Profile" />
							</figure>
							<header>
								<h1><?php echo esc_attr($userFullName); ?><small><?php echo esc_attr($userEmail); ?></small></h1>
							</header>
							<div class="toggle"></div>
							<main>
								<?php
								if ($GLOBALS['apiname'] === 'smsgateway.center') {
									if (esc_attr($json->reason) !== 'No Records found.') {
										$cnt = 0;
										foreach ($json->UserProfile as $sgcrows) {
											foreach ($sgcrows as $key => $sgcrow) {
												if (in_array($key, ['Email', 'FullName'])) {
													continue;
												}
												?>
												<dl>
													<dt><?php echo esc_attr($key); ?></dt>
													<dd><?php echo esc_attr($sgcrow); ?></dd>
												</dl>
												<?php
											}
										}
									} else {
										?>
										<h2 style="text-align:center;color:red">No records found</h2>
										<?php
									}
								} else {
									if (esc_attr($json->response->status) === 'success') {
										$cnt = 0;
										foreach ($json->response->account as $key => $sgcrow) {
											if (in_array($key, ['fullName','profilePic'])) {
												continue;
											}
											if($key === esc_attr('enableCMS')){
												$key = esc_attr('White Label Enabled?');
												$sgcrow = $sgcrow == 1 ? esc_attr('Yes') : esc_attr('No');
											}
											?>
											<dl>
												<dt><?php echo esc_attr($key); ?></dt>
												<dd><?php echo esc_attr($sgcrow); ?></dd>
											</dl>
											<?php
										}
									} else {
										?>
										<h2 style="text-align:center;color:red">No records found</h2>
										<?php
									}
								}
								?>
							</main>
						</div>
					</div>
				</div>
				<?php
			} else {
				smsgatewaycenter_show_html_error('You are not authorised!');
				smsgatewaycenter_show_go_back_html();
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

	if (!function_exists('smsgatewaycenter_view_profile_api_call')) {

		/**
		 * Fetch API for User Profile
		 */
		function smsgatewaycenter_view_profile_api_call() {
			if ($GLOBALS['apiname'] === 'smsgateway.center') {
				$url = SGC_SMS_BASE_URL . '/library/api/self/ViewProfile/';
				$param['userId'] = $GLOBALS['username'];
				$param['password'] = $GLOBALS['password'];
				$param['format'] = 'json';
			} else {
				$url = SGC_UNIFY_SMS_BASE_URL . '/SMSApi/account/readprofile';
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