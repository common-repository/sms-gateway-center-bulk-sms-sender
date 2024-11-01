<?php
	if (!defined('ABSPATH'))
		exit;  // if direct access
	/**
	 * SMS Gateway Center Bulk SMS Sender - A Wordpress SMS plugin
	 */
	if (!function_exists('smsgatewaycenter_fetch_sms_delivery_report')) {

		/**
		 * Fetch SMS delivery report
		 * @global type $sgcoption
		 */
		function smsgatewaycenter_fetch_sms_delivery_report() {
			global $sgcoption;
			$headTitle = 'SMS Delivery Report';
			include_once SGC_SMS_PLUGIN_DIR_PATH . 'header.php';
			smsgatewaycenter_check_login_auth_exists();
			switch ($sgcoption) {
				case 'smsgatewaycenter_download_dlr':
					check_admin_referer('smsgatewaycenter_dlr_csv_exporter_nonce');
					break;
				default:
					$json = smsgatewaycenter_dlr_api_results();
					//sgc_print_array($json);
					if ($GLOBALS['apiname'] === 'smsgateway.center') {
						if ($json->reason == 'Data Fetched.') {
							$messagesData = [];
							foreach ($json->DLRReport as $sgcrow) {
								$messagesData[] = [$sgcrow->Phone, $sgcrow->SenderId, $sgcrow->Status, $sgcrow->Cause, $sgcrow->Message, $sgcrow->TransactionId, $sgcrow->MessageLength, $sgcrow->MessageCost, $sgcrow->ReceivedTime, $sgcrow->DeliveryTime];
							}
						}
					} else {
						if ($json->response->status == 'success') {
							$messagesData = [];
							foreach ($json->response->report_statusList->DLRReport as $sgcrow) {
								$messagesData[] = [$sgcrow->mobileNo, $sgcrow->senderName, $sgcrow->status, $sgcrow->cause, $sgcrow->text, $sgcrow->uuId, $sgcrow->length, $sgcrow->cost, $sgcrow->submitTime, $sgcrow->deliveryTime];
							}
						}
					}
					?>
					<p class="color-333">Delivery Report can be fetched for the current day only. You can download historical data in zip format from the left menu.</p>
					<table id="sgcSMSDLR" class="responsive wp-list-table widefat fixed posts">
					</table>
					<div class="clearfix"></div>
					<script type="text/javascript">
							var dataSet = <?php echo json_encode($messagesData); ?>;
							jQuery(document).ready(function () {
								jQuery('#sgcSMSDLR').DataTable({
									order: [[8, 'desc']],
									data: dataSet,
									dom: 'Blfrtip',
									columns: [
										{title: 'Mobile'},
										{title: 'SID'},
										{title: 'Status'},
										{title: 'Cause'},
										{title: 'Message'},
										{title: 'TransId'},
										{title: 'Length'},
										{title: 'Cost'},
										{title: 'Received Time'},
										{title: 'Delivered Time'}
									],
									pageLength: 50,
									aLengthMenu: [50, 100, 500, 1000],
									columnDefs: [
										{
											"searchable": false, "orderable": false, "targets": []
										}
									],
									language: {
										loadingRecords: 'Loading...'
									},
								});
							});
					</script>
					<?php
					break;
			}
			?>
			<div class="margt50"></div>
			<div class="container">
				<div class="col">
					<?php sgc_write_html_rateUs(); ?>
				</div>
			</div>
			<?php
		}

	}

	if (!function_exists('smsgatewaycenter_dlr_api_results')) {

		/**
		 * Get DLR Results from API
		 * @return type
		 * @throws Exception
		 */
		function smsgatewaycenter_dlr_api_results() {
			if ($GLOBALS['apiname'] === 'smsgateway.center') {
				$url = SGC_SMS_BASE_URL . '/library/api/self/SMSDlr/';
				$param['userId'] = $GLOBALS['username'];
				$param['password'] = $GLOBALS['password'];
				$param['FromDate'] = date("Y-m-d", current_time('timestamp', 0));
				$param['format'] = 'json';
			} else {
				$url = SGC_UNIFY_SMS_BASE_URL . '/SMSApi/dlr/smsdlr';
				$param['userid'] = $GLOBALS['username'];
				$param['password'] = $GLOBALS['password'];
				$param['date'] = date("Y-m-d");
				$param['perpagerecords'] = 10000;
				//$param['todate'] = date("Y-m-d 23:59:59", current_time('timestamp', 0));
				//$param['method'] = 'getDlr';
				//$param['pageLimit'] = '5000';
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
			$jsonDecodedData = json_decode($smsgatewaycenter_api_response);
			return $jsonDecodedData;
		}

	}
