<?php
	/**
	 * SMS Gateway Center Bulk SMS Sender - A Wordpress SMS plugin
	 */
	if (!defined('ABSPATH'))
		exit;  // if direct access
	if (!function_exists('smsgatewaycenter_fetch_sender_names')) {

		/**
		 * Fetch sender names
		 */
		function smsgatewaycenter_fetch_sender_names() {
			$headTitle = 'Sender Names';
			include_once SGC_SMS_PLUGIN_DIR_PATH . 'header.php';
			smsgatewaycenter_check_login_auth_exists();
			$json = smsgatewaycenter_senderNames_api();
			?>
			<table class="responsive wp-list-table widefat fixed posts">
				<?php
				if ($GLOBALS['apiname'] === 'smsgateway.center') {
					?>
					<thead><tr><th>Sender Name</th><th>Status</th></tr></thead>
					<?php
					if (esc_attr($json->reason) === 'Data Fetched.') {
						$cnt = 0;
						foreach ($json->SenderNames as $sgcrow) {
							$cls = '';
							if ($cnt % 2 == 0) {
								$cls .= 'alternate';
							}
							?>
							<tr class="<?php echo esc_attr($cls); ?>">
								<td><?php echo esc_attr($sgcrow->senderName); ?></td>
								<td><?php echo esc_attr($sgcrow->Status); ?></td>
							</tr>
							<?php
							$cnt++;
						}
					} else {
						?>
						<tr><td colspan="2" style="text-align:center;color:red">No records found</td></tr>
						<?php
					}
				} else {
					if ($json->response->status == 'success') {
						?>
						<thead><tr><th>ID</th><th>Sender Name</th><th>Status</th><th>Timestamp</th></tr></thead>
						<?php
						if (!empty($json->response->senderidList) && is_array($json->response->senderidList)) {
							$cnt = 0;
							foreach ($json->response->senderidList as $item) {
								$cls = '';
								if ($cnt % 2 == 0) {
									$cls .= 'alternate';
								}
								?>
								<tr class="<?php echo esc_attr($cls); ?>">
									<td><?php echo esc_attr($item->senderid->sId); ?></td>
									<td><?php echo esc_attr($item->senderid->senderName); ?></td>
									<td><?php echo esc_attr($item->senderid->isEnabled); ?></td>
									<td><?php echo esc_attr(smsgatewaycenter_convert_epoch_to_date($item->senderid->addTime)); ?></td>
								</tr>
								<?php
								$cnt++;
							}
						}
					} else {
						?>
						<tr><td colspan="2" style="text-align:center;color:red">No records found</td></tr>
						<?php
					}
				}
				?>
			</table>
			<?php
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