<?php
	/**
	 * SMS Gateway Center Bulk SMS Sender - A Wordpress SMS plugin
	 */
	if (!defined('ABSPATH'))
		exit;  // if direct access
	if (!function_exists('smsgatewaycenter_fetch_groups')) {

		/**
		 * Fetch groups
		 * @throws Exception
		 */
		function smsgatewaycenter_fetch_groups() {
			$headTitle = 'Groups';
			include_once SGC_SMS_PLUGIN_DIR_PATH . 'header.php';
			smsgatewaycenter_check_login_auth_exists();
			$grparr = smsgatewaycener_get_group_list();
			?>
			<p>This group is loaded from your SMS Gateway Center portal.</p>
			<table class="responsive wp-list-table widefat fixed posts">
				<thead>
					<tr>
						<th>Group ID</th>
						<th>Group Name</th>
						<th>Total Contacts</th>
					</tr>
				</thead>
				<?php
				if (sizeof($grparr) > 0) {
					$cnt = 0;
					foreach ($grparr as $grow) {
						$cls = '';
						if ($cnt % 2 == 0) {
							$cls .= 'alternate';
						}
						if ($GLOBALS['apiname'] === 'smsgateway.center') {
							$contactCount = $grow->totalContacts;
							$groupId = $grow->groupId;
							$groupName = $grow->groupName;
						} else {
							$contactCount = $grow->groupname->count;
							$groupId = $grow->groupname->groupId;
							$groupName = $grow->groupname->groupName;
						}
						?>
						<tr class="<?php echo esc_attr($cls); ?>">
							<td><?php echo esc_attr($groupId); ?></td>
							<td><?php echo esc_attr($groupName); ?></td>
							<td><?php echo esc_attr($contactCount); ?></td>
						</tr>
						<?php
						$cnt++;
					}
				} else {
					?>
					<tr><td colspan="3" style="text-align:center;color:red">No records found</td></tr>
					<?php
				}
				?>
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

	if (!function_exists('smsgatewaycenter_fetch_group_api')) {

		/**
		 * Fetch group API
		 * @return type
		 * @throws Exception
		 */
		function smsgatewaycenter_fetch_group_api() {
			if ($GLOBALS['apiname'] === 'smsgateway.center') {
				$url = SGC_SMS_BASE_URL . '/library/api/self/Group/';

				$param['userId'] = $GLOBALS['username'];
				$param['password'] = $GLOBALS['password'];
				$param['do'] = 'list';
				$param['format'] = 'json';
			} else {
				$url = SGC_UNIFY_SMS_BASE_URL . '/SMSApi/group/read';
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
			$json = json_decode($smsgatewaycenter_api_response);
			return $json;
		}

	}