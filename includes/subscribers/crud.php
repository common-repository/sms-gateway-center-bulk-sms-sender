<?php
	/**
	 * SMS Gateway Center Bulk SMS Sender - A Wordpress SMS plugin
	 */
	if (!defined('ABSPATH'))
		die('No direct access allowed');
	if (!function_exists('smsgatewaycenter_wp_subscribers')) {

		/**
		 * Subscribers handler
		 * @global type $sgcoption
		 */
		function smsgatewaycenter_wp_subscribers() {
			global $sgcoption;
			$headTitle = 'SGC WP Subscribers';
			$subscriberPageUrlMain = admin_url('admin.php?page=wpSubscribers');
			include_once SGC_SMS_PLUGIN_DIR_PATH . 'header.php';
			include_once SGC_SMS_PLUGIN_DIR_PATH . '/includes/class-sgcsms-subscribers-table.php';
			$list_table = new sgcsms_wp_subscribers_list_table();
			if (current_user_can('manage_options')) {
				switch ($sgcoption) {
					case 'addSubscriber':
						?>
						<div class="sgc-notify-button-group">
							<?php $list_table->sgcsms_get_add_subscriber_btn($subscriberPageUrlMain); ?>
							<?php $list_table->sgcsms_get_manage_subscriber_btn($subscriberPageUrlMain); ?>
						</div>
						<?php
						$sendSGCSMSSubForm = !empty($_POST['sendSGCSMSSubForm']) ? sanitize_text_field(trim($_POST['sendSGCSMSSubForm'])) : 0;
						if (!empty($sendSGCSMSSubForm) && $sendSGCSMSSubForm === '1') {
							check_admin_referer('smsgatewaycenter_add_subscriber_nonce');
							$subName = !empty($_POST['subName']) ? sanitize_text_field(trim($_POST['subName'])) : '';
							$subMobile = !empty($_POST['subMobile']) ? sanitize_text_field(trim($_POST['subMobile'])) : '';
							$sGrpName = !empty($_POST['sGrpName']) ? sanitize_text_field(trim($_POST['sGrpName'])) : '';
							if (strlen($subName) > 20) {
								smsgatewaycenter_show_html_error('Subscriber name length should not be more than 20 characters.');
								smsgatewaycenter_show_go_back_html();
								die();
							}
							if (!is_numeric($subMobile)) {
								smsgatewaycenter_show_html_error('Subscriber Mobile number has to be numeric.');
								smsgatewaycenter_show_go_back_html();
								die();
							}
							if (!is_numeric($sGrpName)) {
								smsgatewaycenter_show_html_error('Selected group has to be numeric in value.');
								smsgatewaycenter_show_go_back_html();
								die();
							}
							if ($sGrpName == '' || $sGrpName == 0) {
								smsgatewaycenter_show_html_error('Please select a group.');
								smsgatewaycenter_show_go_back_html();
								die();
							}
							$result = $list_table->add_subscriber($subName, $subMobile, $sGrpName);
							smsgatewaycenter_show_notice($result['result'], $result['message']);
						}
						break;
					case 'delete':
						?>
						<div class="sgc-notify-button-group">
							<?php $list_table->sgcsms_get_add_subscriber_btn($subscriberPageUrlMain); ?>
							<?php $list_table->sgcsms_get_manage_subscriber_btn($subscriberPageUrlMain); ?>
						</div>
						<?php
						$subscrbrID = !empty($_GET['ID']) ? sanitize_text_field(trim($_GET['ID'])) : 0;
						if ($subscrbrID > 0) {
							$result = $list_table->delete_subscriber($subscrbrID);
							smsgatewaycenter_show_notice($result['result'], $result['message']);
						} else {
							smsgatewaycenter_show_notice(esc_html_e('error', 'sgcsms'), esc_html_e('Subscriber ID not found.', 'sgcsms'));
							die();
						}
						break;
					case 'editSubscriber':
						?>
						<div class="sgc-notify-button-group">
							<?php $list_table->sgcsms_get_add_subscriber_btn($subscriberPageUrlMain); ?>
							<?php $list_table->sgcsms_get_manage_subscriber_btn($subscriberPageUrlMain); ?>
						</div>
						<?php
						$sendSGCSMSSubForm = !empty($_POST['sendSGCSMSSubForm']) ? sanitize_text_field(trim($_POST['sendSGCSMSSubForm'])) : 0;
						if (!empty($sendSGCSMSSubForm) && $sendSGCSMSSubForm === '1') {
							check_admin_referer('smsgatewaycenter_edit_subscriber_nonce');
							$subscrbrID = !empty($_GET['ID']) ? sanitize_text_field(trim($_GET['ID'])) : 0;
							$subName = !empty($_POST['subName']) ? sanitize_text_field(trim($_POST['subName'])) : '';
							$subMobile = !empty($_POST['subMobile']) ? sanitize_text_field(trim($_POST['subMobile'])) : '';
							$sGrpName = !empty($_POST['sGrpName']) ? sanitize_text_field(trim($_POST['sGrpName'])) : '';
							if (strlen($subName) > 20) {
								smsgatewaycenter_show_html_error('Subscriber name length should not be more than 20 characters.');
								smsgatewaycenter_show_go_back_html();
								die();
							}
							if (!is_numeric($subMobile)) {
								smsgatewaycenter_show_html_error('Subscriber Mobile number has to be numeric.');
								smsgatewaycenter_show_go_back_html();
								die();
							}
							if (!is_numeric($sGrpName)) {
								smsgatewaycenter_show_html_error('Selected group has to be numeric in value.');
								smsgatewaycenter_show_go_back_html();
								die();
							}
							if ($sGrpName == '' || $sGrpName == 0) {
								smsgatewaycenter_show_html_error('Please select a group.');
								smsgatewaycenter_show_go_back_html();
								die();
							}
							if ($subscrbrID > 0) {
								$result = $list_table->update_subscriber($subscrbrID, $subName, $subMobile, $sGrpName);
								smsgatewaycenter_show_notice($result['result'], $result['message']);
							} else {
								smsgatewaycenter_show_notice(esc_html_e('error', 'sgcsms'), esc_html_e('Subscriber ID not found.', 'sgcsms'));
								die();
							}
						}
						break;
					case 'edit':
						include_once SGC_SMS_PLUGIN_DIR_PATH . '/includes/class-sgcsms-subscribers-groups-table.php';
						$groupTable = new sgcsms_wp_subscribers_groups_list_table();
						$groups = $groupTable->get_groups();
						$subscrbrID = !empty($_GET['ID']) ? sanitize_text_field(trim($_GET['ID'])) : 0;
						if ($subscrbrID > 0) {
							$sbrRs = $list_table->get_subscriber($subscrbrID);
							?>
							<div class="grid">
								<div class="w6">
									<div class="sgc-notify-button-group">
										<?php $list_table->sgcsms_get_manage_subscriber_btn($subscriberPageUrlMain); ?>
										<?php $list_table->sgcsms_get_import_subscriber_btn($subscriberPageUrlMain); ?>
										<?php $groupTable->sgcsms_get_manage_group_btn(admin_url('admin.php?page=wpSubscriberGroups')); ?>
									</div>
									<h3 class="margt30"><?php _e('Subscriber Info:', 'sgcsms'); ?></h3>
									<p><?php esc_html_e('Update subscriber.', 'sgcsms'); ?></p>
									<form class="form-capsule" method=post action="<?php echo esc_url($subscriberPageUrlMain); ?>&sgcoption=editSubscriber&ID=<?php echo esc_attr($subscrbrID); ?>">
										<?php wp_nonce_field('smsgatewaycenter_edit_subscriber_nonce'); ?>
										<div class="text">
											<label for="subName"><?php esc_html_e('Subscriber Name', 'sgcsms'); ?></label>
											<input type="text" class="form-control" value="<?php echo esc_attr($sbrRs->name); ?>" name="subName" id="subName" placeholder="<?php esc_html_e('Subscriber Name. Example: Jon Doe', 'sgcsms'); ?>" required="">
										</div>
										<div class="text">
											<label for="subMobile"><?php esc_html_e('Subscriber Mobile', 'sgcsms'); ?></label>
											<input type="text" class="form-control" value="<?php echo esc_attr($sbrRs->mobile); ?>" name="subMobile" id="subMobile" placeholder="<?php esc_html_e('Subscriber Mobile. Example: 919999912345', 'sgcsms'); ?>" required="">
										</div>
										<div class="form-group form-group-select margt20">
											<label for="sGrpName"><?php esc_html_e('Select Group (required)', 'sgcsms'); ?></label>
											<div class="select-wrapper">
												<select class="form-control" name="sGrpName" id="sGrpName" required="">
													<option value="" selected><?php esc_html_e('Please select', 'sgcsms'); ?></option>
													<?php
													foreach ($groups as $value) {
														$selected = '';
														if ($value->ID == $sbrRs->group_ID) {
															$selected = ' selected';
														}
														?>
														<option value="<?php echo esc_attr($value->ID); ?>"<?php echo esc_attr($selected); ?>><?php echo esc_attr($value->name); ?></option>
														<?php
													}
													?>
												</select>
												<blockquote><small><i>Select a group to add a subscriber.</i></small></blockquote>
											</div>
										</div>
										<p class="submit">
											<input class="button3" type="submit" name="submit" value="<?php esc_html_e('Submit', 'sgcsms'); ?>">
											<input type="hidden" name="sendSGCSMSSubForm" value="1">
										</p>
									</form>
								</div>
								<div class="w6"></div>
							</div>
							<?php
						} else {
							smsgatewaycenter_show_notice(esc_html_e('error', 'sgcsms'), esc_html_e('Subscriber ID not found.', 'sgcsms'));
							die();
						}
						break;
					case 'import':
						?>
						<div class="grid">
							<div class="w6">
								<div class="sgc-notify-button-group">
									<?php $list_table->sgcsms_get_manage_subscriber_btn($subscriberPageUrlMain); ?>
								</div>
								<h3 class="margt30"><?php _e('Import subscribers', 'sgcsms'); ?></h3>
								<p class="color-333">To Import subscribers, you can install a free word plugin, <a href="https://wordpress.org/plugins/wp-csv-to-database/" class="text-bold" target="_blank">here</a>.</p>
								<p>Or go to Add New Plugins, search for <b>WP CSV TO DB Pluign</b> then install and activate the plugin.</p>
								<p>Upon activating, go to Sidebar Menu -> Settings -> <b>WP CSV/DB.</b></p>
								<p><b>Step 1</b>: On the settings tab, Select Database table, Select our table <b><?php echo esc_attr($GLOBALS['wpdb']->prefix);?>sgc_sms_alerts_subscribers</b> from the dropdown.</p>
								<p><b>Step 2</b>: Now, <b>Select Input File</b> and upload your CSV file. You can see the sample screenshot of CSV file below. Fig. 1.1</p>
								<figure class="wp-block-image"><img src="<?php echo SGC_SMS_ADMIN_URL; ?>/assets/images/csv_screenshot.png" alt="CSV Screenshot" class="wp-image-73" sizes="(max-width: 1024px) 100vw, 1024px"><figcaption>Fig. 1.1</figcaption></figure>
								<p><b>Step 3</b>: Here, <b>Select Starting Row</b>, Enter <b>2</b> in the text input, so that our 1st row heading wont get inserted into database.</p>
								<p><b>Step 4</b>: Check <b>Disable "auto_increment" Column</b> as we dont want to use this column.</p>
								<p><b>Step 5</b>: Check <b>Update Database Rows</b> too as if you are inserting any duplicate let it overwrite in the database table.</p>
								<p><b>Final Step:</b> Click on <b>Import to DB</b> button and you are all done!</p>
								<p>&nbsp;</p>
								<p><strong>Important Note:</strong> Restrict to maximum 1000 Subscribers per group to send SMS to listed Subscribers. Import Plugin does not validate mobile numbers, hence before uploading just add proper mobile numbers.</p>
							</div>
						</div>
						<?php
						break;
					case 'add':
						include_once SGC_SMS_PLUGIN_DIR_PATH . '/includes/class-sgcsms-subscribers-groups-table.php';
						$groupTable = new sgcsms_wp_subscribers_groups_list_table();
						$groups = $groupTable->get_groups();
						//sgc_print_array($groups);
						?>
						<div class="grid">
							<div class="w6">
								<div class="sgc-notify-button-group">
									<?php $list_table->sgcsms_get_manage_subscriber_btn($subscriberPageUrlMain); ?>
									<?php $list_table->sgcsms_get_import_subscriber_btn($subscriberPageUrlMain); ?>
									<?php $groupTable->sgcsms_get_manage_group_btn(admin_url('admin.php?page=wpSubscriberGroups')); ?>
								</div>
								<h3 class="margt30"><?php _e('Subscriber Info:', 'sgcsms'); ?></h3>
								<p><?php esc_html_e('Add a unique subscriber.', 'sgcsms'); ?></p>
								<form class="form-capsule" method=post action="<?php echo esc_url($subscriberPageUrlMain); ?>&sgcoption=addSubscriber">
									<?php wp_nonce_field('smsgatewaycenter_add_subscriber_nonce'); ?>
									<div class="text">
										<label for="subName"><?php esc_html_e('Subscriber Name', 'sgcsms'); ?></label>
										<input type="text" class="form-control" name="subName" id="subName" placeholder="<?php esc_html_e('Subscriber Name. Example: Jon Doe', 'sgcsms'); ?>" required="">
									</div>
									<div class="text">
										<label for="subMobile"><?php esc_html_e('Subscriber Mobile', 'sgcsms'); ?></label>
										<input type="text" class="form-control" name="subMobile" id="subMobile" placeholder="<?php esc_html_e('Subscriber Mobile. Example: 919999912345', 'sgcsms'); ?>" required="">
									</div>
									<div class="form-group form-group-select margt20">
										<label for="sGrpName"><?php esc_html_e('Select Group (required)', 'sgcsms'); ?></label>
										<div class="select-wrapper">
											<select class="form-control" name="sGrpName" id="sGrpName" required="">
												<option value="" selected><?php esc_html_e('Please select', 'sgcsms'); ?></option>
												<?php
												foreach ($groups as $value) {
													?>
													<option value="<?php echo esc_attr($value->ID); ?>"><?php echo esc_attr($value->name); ?></option>
													<?php
												}
												?>
											</select>
											<blockquote><small><i>Select a group to add a subscriber.</i></small></blockquote>
										</div>
									</div>
									<p class="submit">
										<input class="button3" type="submit" name="submit" value="<?php esc_html_e('Submit', 'sgcsms'); ?>">
										<input type="hidden" name="sendSGCSMSSubForm" value="1">
									</p>
								</form>
							</div>
							<div class="w6"></div>
						</div>
						<?php
						break;
					default:
						$list_table->prepare_items();
						?>
						<div class="sgc-notify-button-group">
							<?php $list_table->sgcsms_get_add_subscriber_btn($subscriberPageUrlMain); ?>
							<?php $list_table->sgcsms_get_import_subscriber_btn($subscriberPageUrlMain); ?>
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