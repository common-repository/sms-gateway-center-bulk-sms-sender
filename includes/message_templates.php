<?php
	/**
	 * SMS Gateway Center Bulk SMS Sender - A Wordpress SMS plugin
	 */
	if (!defined('ABSPATH'))
		exit;  // if direct access
	if (!function_exists('smsgatewaycenter_fetch_message_templates')) {

		/**
		 * Fetch Templates
		 * @throws Exception
		 */
		function smsgatewaycenter_fetch_message_templates() {
			$headTitle = 'Message Templates';
			include_once SGC_SMS_PLUGIN_DIR_PATH . 'header.php';
			smsgatewaycenter_check_login_auth_exists();
			$json = smsgatewaycenter_message_templates_list();
			$templatesList = [];
			if ($GLOBALS['apiname'] === 'smsgateway.center') {
				foreach ($json->TemplatesList as $sgcrow) {
					$templatesList[] = [
						'id' => $sgcrow->ID,
						'identifier' => $sgcrow->identifier,
						'messageContent' => $sgcrow->messageContent,
						'dltTemplateId' => $sgcrow->dltTemplateId,
						'dltTemplateType' => $sgcrow->dltTemplateType,
						'senderIds' => $sgcrow->senderIds,
						'msgType' => $sgcrow->msgType,
						'status' => $sgcrow->status,
						'timestamp' => $sgcrow->timestamp,
					];
				}
			} else {
				if ($json->response->status == 'success') {
					if (!empty($json->response->templateList) && is_array($json->response->templateList)) {
						foreach ($json->response->templateList as $item) {
							$templatesList[] = [
								'id' => $item->template->mtId,
								'identifier' => $item->template->identifier,
								'messageContent' => $item->template->template,
								'dltTemplateId' => $item->template->dltTemplateId,
								'dltTemplateType' => $item->template->dltTemplateType,
								'senderIds' => $item->template->senderIds,
								'msgType' => $item->template->msgType,
								'status' => $item->template->status,
								'timestamp' => smsgatewaycenter_convert_epoch_to_date($item->template->lastUpdated),
							];
						}
					}
				}
			}
			?>
			<table class="responsive wp-list-table widefat fixed posts">
				<thead>
					<tr>
						<th>ID</th>
						<th>Identifier</th>
						<th>Message Content</th>
						<th>DLT Template ID</th>
						<th>Type</th>
						<th>Mapped Sender Names</th>
						<th>Message Type</th>
						<th>Status</th>
						<th>Timestamp</th>
					</tr>
				</thead>
				<?php
				if (!empty($templatesList)) {
					$cnt = 0;
					foreach ($templatesList as $sgcrow) {
						$cls = '';
						if ($cnt % 2 == 0) {
							$cls .= 'alternate';
						}
						?>
						<tr class="<?php echo esc_attr($cls); ?>">
							<td><?php echo esc_attr($sgcrow['id']); ?></td>
							<td><?php echo esc_attr($sgcrow['identifier']); ?></td>
							<td><?php echo esc_attr($sgcrow['messageContent']); ?></td>
							<td><?php echo esc_attr($sgcrow['dltTemplateId']); ?></td>
							<td><?php echo esc_attr($sgcrow['dltTemplateType']); ?></td>
							<td><?php echo esc_attr($sgcrow['senderIds']); ?></td>
							<td><?php echo esc_attr($sgcrow['msgType']); ?></td>
							<td><?php echo esc_attr($sgcrow['status']); ?></td>
							<td><?php echo esc_attr($sgcrow['timestamp']); ?></td>
						</tr>
						<?php
						$cnt++;
					}
				} else {
					?>
					<tr><td colspan="9" style="text-align:center;color:red">No records found</td></tr>
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

	if (!function_exists('smsgatewaycenter_message_templates_list')) {

		/**
		 * list all message template using API
		 * @return array
		 * @throws Exception
		 */
		function smsgatewaycenter_message_templates_list() {
			if ($GLOBALS['apiname'] === 'smsgateway.center') {
				$url = SGC_SMS_BASE_URL . '/library/api/self/Templates/';
				$param['userId'] = $GLOBALS['username'];
				$param['password'] = $GLOBALS['password'];
				$param['do'] = 'list';
				$param['format'] = 'json';
			} else {
				$url = SGC_UNIFY_SMS_BASE_URL . '/SMSApi/template/read';
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

