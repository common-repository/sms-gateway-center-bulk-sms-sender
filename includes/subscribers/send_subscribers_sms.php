<?php
	/**
	 * SMS Gateway Center Bulk SMS Sender - A Wordpress SMS plugin
	 */
	if (!defined('ABSPATH'))
		exit;  // if direct access
	if (!function_exists('smsgatewaycenter_send_subscribers_sms')) {

		/**
		 * Send subscribers SMS
		 * @throws Exception
		 */
		function smsgatewaycenter_send_subscribers_sms() {
			global $sgcoption;
			include_once SGC_SMS_PLUGIN_DIR_PATH . '/includes/class-sgcsms-subscribers-groups-table.php';
			$group_table = new sgcsms_wp_subscribers_groups_list_table();
			$isIndia = get_option('sgcsms_default_routing')[0];
			switch ($sgcoption) {
				case 'sendSubscribersSMS':
					include_once SGC_SMS_PLUGIN_DIR_PATH . '/includes/class-sgcsms-subscribers-table.php';
					$subscriber_table = new sgcsms_wp_subscribers_list_table();

					$headTitle = 'Bulk SMS Sent Status';
					include_once SGC_SMS_PLUGIN_DIR_PATH . 'header.php';
					smsgatewaycenter_check_login_auth_exists();
					$sendSGCSMSForm = isset($_POST["sendSGCSMSForm"]) ? sanitize_text_field(trim($_POST["sendSGCSMSForm"])) : '';
					if (!empty($sendSGCSMSForm) && $sendSGCSMSForm === '1' && isset($_POST['_wpnonce']) && wp_verify_nonce($_POST['_wpnonce'], 'sgc_send_grp_sms_frm')) {
						$smscontent = sanitize_textarea_field(trim($_POST["smscontent"]));
						$mask = sanitize_text_field(trim($_POST["mask"]));
						$smstype = sanitize_text_field(trim($_POST["msgtype"]));
						if ($smscontent === '') {
							smsgatewaycenter_show_html_error('Text cannot be empty.');
							echo wp_kses_post('<p class="margt50">&nbsp;</p>');
							smsgatewaycenter_show_go_back_html();
							die();
						}
						$groupMobile = '';
						if (!empty($_POST['groupMobile']) && is_array($_POST['groupMobile'])) {
							$groupMobile = [];
							foreach ($_POST['groupMobile'] as $key => $value) {
								$groupMobile[] = sanitize_text_field(trim($value));
							}
							$groupMobile = implode(',', $groupMobile);
						}
						if ($groupMobile === '') {
							smsgatewaycenter_show_html_error('Select at least one group.');
							echo wp_kses_post('<p class="margt50">&nbsp;</p>');
							smsgatewaycenter_show_go_back_html();
							die();
						}
						if ($mask === '') {
							smsgatewaycenter_show_html_error('Sender Name not selected.');
							echo wp_kses_post('<p class="margt50">&nbsp;</p>');
							smsgatewaycenter_show_go_back_html();
							die();
						}
						$contactsList = $subscriber_table->get_subscribers_in($groupMobile);
						if (count($contactsList) > SGC_SMS_MAX_SMS_CUSTOM) {
							smsgatewaycenter_show_html_error('Maximum allowed numbers are ' . SGC_SMS_MAX_SMS_CUSTOM);
							echo wp_kses_post('<p class="margt50">&nbsp;</p>');
							smsgatewaycenter_show_go_back_html();
							die();
						}
						$mobile = [];
						foreach ($contactsList as $value) {
							$mobile[] = $value['mobile'];
						}
						$mobileNos = implode(',', $mobile);

						$jsonDecodeSGCResponse = smsgatewaycenter_send_sms_api("quick", $mobileNos, $mask, $smscontent, $smstype, $isIndia);
						$thead = '';
						$tbody = '';
						foreach ($jsonDecodeSGCResponse as $key => $value) {
							if ((!empty($jsonDecodeSGCResponse->status) && trim($jsonDecodeSGCResponse->status) === 'error') || (!empty($jsonDecodeSGCResponse->response->status) && trim($jsonDecodeSGCResponse->response->status) === 'error')) {
								$cls = ' style="background-color:red;color:white"';
							} else {
								$cls = ' style="background-color:green;color:white"';
							}
							$thead .= '<th>' . $key . '</th>';
							$tbody .= '<td>' . $value . '</td>';
						}
						$content = '';
						if ($tbody !== '') {
							$content .= '<tr>' . $tbody . '</tr>';
						}
						smsgatewaycenter_main_table_html($thead, $content);
						smsgatewaycenter_show_go_back_html();
					} else {
						smsgatewaycenter_show_html_error('Form verification failed.');
						echo wp_kses_post('<p class="margt50">&nbsp;</p>');
						smsgatewaycenter_show_go_back_html();
						die;
					}
					break;
				default:
					$headTitle = 'Send SMS to Subscribers';
					include_once SGC_SMS_PLUGIN_DIR_PATH . 'header.php';
					smsgatewaycenter_check_login_auth_exists();
					$sidarr = smsgatewaycener_get_senderId_list();
					$jsonGrp = $group_table->get_groups();
					$grparr = [];
					if (!empty($jsonGrp)) {
						foreach ($jsonGrp as $sgcrow) {
							$contactCnt = $group_table->smsgatewaycenter_get_subscribers_count($sgcrow->ID);
							if ($contactCnt == 0) {
								continue;
							}
							$sgcrow->totalContacts = $contactCnt;
							$grparr[] = $sgcrow;
						}
					}
					?>
					<form class="form-capsule" method=post action="<?php echo esc_url(sgc_get_admin_current_page_url()); ?>&sgcoption=sendSubscribersSMS">
						<div class="grid">
							<div class="w6">
								<div class="form-group form-group-select">
									<label for="mask"><?php esc_html_e('Sender Name (required)', 'sgcsms'); ?></label>
									<div class="select-wrapper">
										<select class="form-control" name="mask" id="mask"">
											<option value=""><?php esc_html_e('Please select', 'sgcsms'); ?></option>
											<?php
											foreach ($sidarr as $value) {
												?>
												<option value="<?php echo esc_attr($value); ?>" selected><?php echo esc_attr($value); ?></option>
												<?php
											}
											?>
										</select>
									</div>
								</div>
							</div>
							<div class="w6">
								<div class="form-group form-group-select">
									<label for="msgtype"><?php esc_html_e('Message Type (required)', 'sgcsms'); ?></label>
									<div class="select-wrapper">
										<select class="form-control" name="msgtype" id="msgtype">
											<option value="text" selected><?php esc_html_e('Text', 'sgcsms'); ?></option>
											<option value="unicode"><?php esc_html_e('Unicode', 'sgcsms'); ?></option>
										</select>
									</div>
								</div>
							</div>
						</div>
						<div class="grid">
							<div class="w6">
								<div class="">
									<label for="groupMobile" style="font-weight:700;font-size: .8em;padding: 0 10px;margin: 5px 0 10px;">Select Groups (required) - <small><i>Multiple Select allowed!</i></small></label>
									<select class="form-control" name="groupMobile[]" id="groupMobile" multiple="multiple">
										<?php
										foreach ($grparr as $grow) {
											?>
											<option value="<?php echo esc_attr($grow->ID); ?>"><?php echo esc_attr($grow->name); ?> (<?php echo esc_attr($grow->totalContacts); ?>)</option>
											<?php
										}
										?>
									</select>
								</div>
							</div>
							<?php
							if (get_option('sgcsms_default_routing')[0] == esc_attr('India')) {
								?>
								<div class="w6">
									<div class="text">
										<label for="dltTemplateId"><?php esc_html_e('DLT Template ID', 'sgcsms'); ?></label>
										<input type="text" class="form-control" name="dltTemplateId" id="dltTemplateId" placeholder="<?php esc_html_e('Enter DLT Template ID', 'sgcsms'); ?>">
									</div>
								</div>
							<?php } ?>
						</div>
						<div class="grid">
							<div class="w6">
								<div class="textarea margt20">
									<label for="smscontent"><?php esc_html_e('Message', 'sgcsms'); ?></label>
									<textarea class="form-control" rows="10" name="smscontent" id="smscontent" placeholder="Add your message content here."></textarea>
								</div>
							</div>
						</div>
						<p class="submit">
							<input class="button3" type="submit" name="submit" value="Send Group SMS">
							<input type="hidden" name="sendSGCSMSForm" value="1">
							<?php wp_nonce_field('sgc_send_grp_sms_frm'); ?>
						</p>
					</form>
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