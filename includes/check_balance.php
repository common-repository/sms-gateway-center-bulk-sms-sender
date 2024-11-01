<?php
	if (!defined('ABSPATH'))
		exit;  // if direct access
	/**
	 * SMS Gateway Center Bulk SMS Sender - A Wordpress SMS plugin
	 */
	if (!function_exists('smsgatewaycenter_check_balance')) {

		/**
		 * Check balance
		 * @throws Exception
		 */
		function smsgatewaycenter_check_balance() {
			$headTitle = 'Check SMS Balance';
			include_once SGC_SMS_PLUGIN_DIR_PATH . 'header.php';
			smsgatewaycenter_check_login_auth_exists();

			$jsonDecodeSGCResponse = smsgatewaycenter_fetch_api_check_balance();
			if ($GLOBALS['apiname'] === 'smsgateway.center') {
				$thead = '';
				$tbody = '';
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
				$thead = '';
				$tbody = '';
				if ($jsonDecodeSGCResponse->response->status == 'success') {
					foreach ($jsonDecodeSGCResponse->response->account as $key => $value) {
						if ($key === 'userCreditType' && $value === '1') {
							$value = 'Credit';
						} elseif ($key === 'userCreditType' && $value === '2') {
							$value = 'Wallet';
						}
						if ($value === '-1') {
							$value = '24 hours';
						}
						$thead .= '<th>' . esc_attr($key) . '</th>';
						$tbody .= '<td>' . esc_attr($value) . '</td>';
					}
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
				smsgatewaycenter_show_html_error(esc_attr($jsonDecodeSGCResponse->reason ?? $jsonDecodeSGCResponse->response->status));
			}
			smsgatewaycenter_main_table_html($thead, $content);
			smsgatewaycenter_show_go_back_html();
			?>
			<div class="margt50"></div>
			<div class="container">
				<div class="row">
					<div class="col">
						<?php echo sgc_write_html_rateUs(); ?>
					</div>
				</div>
			</div>
			<?php
		}

	}

	if (!function_exists('smsgatewaycenter_fetch_api_check_balance')) {

		/**
		 * Fetch Balance API 
		 * @return array
		 * @throws Exception
		 */
		function smsgatewaycenter_fetch_api_check_balance() {
			if ($GLOBALS['apiname'] === 'smsgateway.center') {
				$url = SGC_SMS_BASE_URL . '/SMSApi/rest/balanceValidityCheck';
				$param['userId'] = $GLOBALS['username'];
				$param['password'] = $GLOBALS['password'];
				$param['format'] = 'json';
			} else {
				$url = SGC_UNIFY_SMS_BASE_URL . '/SMSApi/account/readstatus';
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
	
	