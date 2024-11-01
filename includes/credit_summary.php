<?php
	if (!defined('ABSPATH'))
		exit;  // if direct access
	/**
	 * SMS Gateway Center Bulk SMS Sender - A Wordpress SMS plugin
	 */
	if (!function_exists('smsgatewaycenter_fetch_credit_summary')) {

		/**
		 * Fetch Credit Summary
		 * @throws Exception
		 */
		function smsgatewaycenter_fetch_credit_summary() {
			$headTitle = 'Credit Summary Report';
			include_once SGC_SMS_PLUGIN_DIR_PATH . 'header.php';
			smsgatewaycenter_check_login_auth_exists();
			//fetch api result
			$json = smsgatewaycenter_get_credt_summary_list();
			if ($GLOBALS['apiname'] === 'smsgateway.center') {
				if ($json->reason == 'User Summary Fetched.') {
					$messagesData = [];
					foreach ($json->UserCredits as $sgcrow) {
						$messagesData[] = [$sgcrow->Product, $sgcrow->{'Transaction Type'}, $sgcrow->{'Credit Type'}, $sgcrow->{'Credits Before'}, $sgcrow->Credits, $sgcrow->{'Credits After'}, $sgcrow->Medium, $sgcrow->Comments, $sgcrow->Timestamp];
					}
				}
			} else {
				if ($json->response->status == 'success') {
					if (!empty($json->response->historyList) && is_array($json->response->historyList)) {
						foreach ($json->response->historyList as $item) {
							$messagesData[] = [$item->history->product, $item->history->transactionType, $item->history->type, $item->history->creditsBefore, $item->history->credits, $item->history->creditsAfter, 'NA', $item->history->creditComments, smsgatewaycenter_convert_epoch_to_date($item->history->addedTime)];
						}
					}
				}
			}
			?>
			<table id="sgcCredSum" class="responsive wp-list-table widefat fixed posts">
			</table>
			<script type="text/javascript">
					var dataSet = <?php echo json_encode($messagesData); ?>;
					jQuery(document).ready(function () {
						jQuery('#sgcCredSum').DataTable({
							order: [[8, 'desc']],
							data: dataSet,
							dom: 'Blfrtip',
							columns: [
								{title: 'Product'},
								{title: 'Transaction Type'},
								{title: 'Credit Type'},
								{title: 'Credits Before'},
								{title: 'Credits'},
								{title: 'Credits After'},
								{title: 'Medium'},
								{title: 'Comments'},
								{title: 'Timestamp'}
							],
							pageLength: 50,
							aLengthMenu: [50, 100, 500, 1000],
							language: {
								loadingRecords: 'Loading...'
							},
						});
					});
			</script>
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

	if (!function_exists('smsgatewaycenter_get_credt_summary_list')) {

		/**
		 * list all credit summary using API
		 * @return array
		 * @throws Exception
		 */
		function smsgatewaycenter_get_credt_summary_list() {
			if ($GLOBALS['apiname'] === 'smsgateway.center') {
				$url = SGC_SMS_BASE_URL . '/library/api/self/CreditHistory/v2/';
				$param['userId'] = $GLOBALS['username'];
				$param['password'] = $GLOBALS['password'];
				$param['do'] = 'list';
				$param['format'] = 'json';
			} else {
				$url = SGC_UNIFY_SMS_BASE_URL . '/SMSApi/account/readcredithistory';
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