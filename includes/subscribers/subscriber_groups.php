<?php
	/**
	 * SMS Gateway Center Bulk SMS Sender - A Wordpress SMS plugin
	 */
	if (!defined('ABSPATH'))
		die('No direct access allowed');
	if (!function_exists('smsgatewaycenter_wp_subscriber_groups')) {

		/**
		 * Subscribers Group handler
		 * @global type $sgcoption
		 */
		function smsgatewaycenter_wp_subscriber_groups() {
			global $sgcoption;
			$headTitle = 'SGC WP Subscriber Groups';
			$groupPageUrlMain = admin_url('admin.php?page=wpSubscriberGroups');
			include_once SGC_SMS_PLUGIN_DIR_PATH . 'header.php';
			include_once SGC_SMS_PLUGIN_DIR_PATH . '/includes/class-sgcsms-subscribers-groups-table.php';
			$list_table = new sgcsms_wp_subscribers_groups_list_table();
			if (current_user_can('manage_options')) {
				switch ($sgcoption) {
					case 'delete':
						?>
						<div class="sgc-notify-button-group">
							<?php $list_table->sgcsms_get_add_group_btn($groupPageUrlMain); ?>
							<?php $list_table->sgcsms_get_manage_group_btn($groupPageUrlMain); ?>
						</div>
						<?php
						$grpID = !empty($_GET['ID']) ? sanitize_text_field(trim($_GET['ID'])) : 0;
						if ($grpID > 0) {
							$result = $list_table->delete_group($grpID);
							smsgatewaycenter_show_notice($result['result'], $result['message']);
						} else {
							smsgatewaycenter_show_html_error('Group ID not found.');
							smsgatewaycenter_show_go_back_html();
							die();
						}
						break;
					case 'addGroup':
						?>
						<div class="sgc-notify-button-group">
							<?php $list_table->sgcsms_get_add_group_btn($groupPageUrlMain); ?>
							<?php $list_table->sgcsms_get_manage_group_btn($groupPageUrlMain); ?>
						</div>
						<?php
						$sendSGCSMSSubGrpForm = !empty($_POST['sendSGCSMSSubGrpForm']) ? sanitize_text_field(trim($_POST['sendSGCSMSSubGrpForm'])) : 0;
						if (!empty($sendSGCSMSSubGrpForm) && $sendSGCSMSSubGrpForm === '1') {
							check_admin_referer('smsgatewaycenter_add_group_nonce');
							$grpName = !empty($_POST['sgrpName']) ? sanitize_text_field(trim($_POST['sgrpName'])) : '';
							if (strlen($grpName) > 20) {
								smsgatewaycenter_show_html_error('Group name length should not be more than 20 characters.');
								smsgatewaycenter_show_go_back_html();
								die();
							}
							$result = $list_table->add_group($grpName);
							smsgatewaycenter_show_notice($result['result'], $result['message']);
						}
						break;
					case 'add':
						?>
						<div class="sgc-notify-button-group">
							<?php $list_table->sgcsms_get_manage_group_btn($groupPageUrlMain); ?>
						</div>
						<h3 class="margt30"><?php esc_html_e('Add a unique group name.', 'sgcsms'); ?></h3>
						<div class="grid">
							<div class="w6">
								<form class="form-capsule" method=post action="<?php echo esc_url($groupPageUrlMain); ?>&sgcoption=addGroup">
									<?php wp_nonce_field('smsgatewaycenter_add_group_nonce'); ?>
									<div class="text">
										<label for="sgrpName"><?php esc_html_e('Subscriber Group Name', 'sgcsms'); ?></label>
										<input type="text" class="form-control" name="sgrpName" id="sgrpName" placeholder="<?php esc_html_e('Group Name. Example: Registered Users', 'sgcsms'); ?>" required="">
									</div>
									<p class="submit">
										<input class="button3" type="submit" name="submit" value="<?php esc_html_e('Submit', 'sgcsms'); ?>">
										<input type="hidden" name="sendSGCSMSSubGrpForm" value="1">
									</p>
								</form>
							</div>
							<div class="w6"></div>
						</div>
						<?php
						break;
					case 'editGroup':
						?>
						<div class="sgc-notify-button-group">
							<?php $list_table->sgcsms_get_add_group_btn($groupPageUrlMain); ?>
							<?php $list_table->sgcsms_get_manage_group_btn($groupPageUrlMain); ?>
						</div>
						<?php
						$sendSGCSMSSubGrpForm = !empty($_POST['sendSGCSMSSubGrpForm']) ? sanitize_text_field(trim($_POST['sendSGCSMSSubGrpForm'])) : 0;
						if (!empty($sendSGCSMSSubGrpForm) && $sendSGCSMSSubGrpForm === '1') {
							check_admin_referer('smsgatewaycenter_edit_group_nonce');
							$grpID = !empty($_GET['ID']) ? sanitize_text_field(trim($_GET['ID'])) : 0;
							$grpName = !empty($_POST['sgrpName']) ? sanitize_text_field(trim($_POST['sgrpName'])) : '';
							if (strlen($grpName) > 20) {
								smsgatewaycenter_show_html_error('Group name length should not be more than 20 characters.');
								smsgatewaycenter_show_go_back_html();
								die();
							}
							if ($grpID > 0) {
								$result = $list_table->update_group($grpID, $grpName);
								smsgatewaycenter_show_notice($result['result'], $result['message']);
							} else {
								smsgatewaycenter_show_notice(esc_html_e('error', 'sgcsms'), esc_html_e('Group ID not found.', 'sgcsms'));
								die();
							}
						}
						break;
					case 'edit':
						$result = $list_table->get_group($grpID);
						$grpID = !empty($_GET['ID']) ? sanitize_text_field(trim($_GET['ID'])) : 0;
						if ($grpID > 0) {
							$grpRs = $list_table->get_group($grpID);
							?>
							<div class="sgc-notify-button-group">
								<?php $list_table->sgcsms_get_add_group_btn($groupPageUrlMain); ?>
								<?php $list_table->sgcsms_get_manage_group_btn($groupPageUrlMain); ?>
							</div>
							<h3 class="margt30"><?php esc_html_e('Update group name.', 'sgcsms'); ?></h3>
							<div class="grid">
								<div class="w6">
									<form class="form-capsule" method=post action="<?php echo esc_url($groupPageUrlMain); ?>&sgcoption=editGroup&ID=<?php echo esc_attr($grpID); ?>">
										<?php wp_nonce_field('smsgatewaycenter_edit_group_nonce'); ?>
										<div class="text">
											<label for="sgrpName"><?php esc_html_e('Subscriber Group Name', 'sgcsms'); ?></label>
											<input type="text" class="form-control" name="sgrpName" id="sgrpName" value="<?php echo esc_attr($grpRs->name); ?>" placeholder="<?php esc_html_e('Group Name. Example: Registered Users', 'sgcsms'); ?>" required="">
										</div>
										<p class="submit">
											<input class="button3" type="submit" name="submit" value="<?php esc_html_e('Submit', 'sgcsms'); ?>">
											<input type="hidden" name="sendSGCSMSSubGrpForm" value="1">
										</p>
									</form>
								</div>
								<div class="w6"></div>
							</div>
							<?php
						} else {
							smsgatewaycenter_show_notice(esc_html_e('error', 'sgcsms'), esc_html_e('Group ID not found.', 'sgcsms'));
							die();
						}
						break;
					default:
						$list_table->prepare_items();
						?>
						<div class="sgc-notify-button-group">
							<?php $list_table->sgcsms_get_add_group_btn($groupPageUrlMain); ?>
						</div>
						<form id="subscribers-filter" method="get">
							<input type="hidden" name="page" value="<?php echo esc_attr($_REQUEST['page']) ?>"/>
							<?php $list_table->search_box(__('Search', 'sgcsms'), 'search_id'); ?>
							<?php $list_table->display(); ?>
						</form>
						<?php
						break;
				}
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

	}