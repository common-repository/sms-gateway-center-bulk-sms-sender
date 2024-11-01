<?php
	/**
	 * SMS Gateway Center Bulk SMS Sender - A Wordpress SMS plugin
	 */
	if (!defined('ABSPATH'))
		exit;  // if direct access
	if (!function_exists('smsgatewaycenter_fetch_incoming_sms')) {

		/**
		 * Incoming SMS
		 * @throws Exception
		 */
		function smsgatewaycenter_fetch_incoming_sms() {
			$headTitle = 'Incoming SMS Report';
			include_once SGC_SMS_PLUGIN_DIR_PATH . 'header.php';
			smsgatewaycenter_check_login_auth_exists();
			$url = SGC_SMS_BASE_URL . '/library/api/self/ShortcodeReport/';

			$param['userId'] = $GLOBALS['username'];
			$param['password'] = $GLOBALS['password'];
			$param['FromDate'] = date('Y-m-d', strtotime('-1 month'));
			$param['ToDate'] = date('Y-m-d', current_time('timestamp', 0));
			$param['format'] = 'json';

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
			$json = json_decode($smsgatewaycenter_api_response);
			?>
			<table class="responsive wp-list-table widefat fixed posts">
				<thead>
					<tr>
						<th>VMN</th>
						<th>Keyword</th>
						<th>Mobile</th>
						<th>Content</th>
						<th>Region</th>
						<th>Carrier</th>
						<th>Timestamp</th>
					</tr>
				</thead>
				<tbody>
					<?php
					if ($json->status != 'error') {
						$cnt = 0;
						foreach ($json->IncomingSMS as $sgcrow) {
							$cls = '';
							if ($cnt % 2 == 0) {
								$cls .= 'alternate';
							}
							?>
							<tr class="<?php echo esc_attr($cls); ?>">
								<td><?php echo esc_attr($sgcrow->VMN); ?></td>
								<td><?php echo esc_attr($sgcrow->Keyword); ?></td>
								<td><?php echo esc_attr($sgcrow->Mobile); ?></td>
								<td><?php echo esc_attr($sgcrow->Content); ?></td>
								<td><?php echo esc_attr($sgcrow->Region); ?></td>
								<td><?php echo esc_attr($sgcrow->Carrier); ?></td>
								<td><?php echo esc_attr($sgcrow->Timestamp); ?></td>
							</tr>
							<?php
							$cnt++;
						}
						if ($json->reason == 'No Records found.') {
							?>
							<tr><td colspan="7" style="text-align:center;color:red">No records found</td></tr>
							<?php
						}
					} else {
						?>
						<tr><td colspan="7" style="text-align:center;color:red">No records found</td></tr>
						<?php
					}
					?>
				</tbody>
			</table>
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