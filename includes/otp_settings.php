<?php
	/**
	 * SMS Gateway Center Bulk SMS Sender - A Wordpress SMS plugin
	 */
	if (!defined('ABSPATH'))
		exit;  // if direct access

	if (!function_exists('smsgatewaycenter_otp_settings')) {

		/**
		 * Display OTP Settings content and call the api upon submission
		 * @global type $sgcoption
		 * @throws Exception
		 */
		function smsgatewaycenter_otp_settings() {
			global $sgcoption;
			$isIndia = get_option('sgcsms_default_routing')[0];
			$headTitle = 'OTP Settings';
			include_once SGC_SMS_PLUGIN_DIR_PATH . 'header.php';
			smsgatewaycenter_check_login_auth_exists();
			$wpoptions = $GLOBALS['wpoptions'];
			$allowed_html = array(
				'b' => array(
					'class' => []
				),
				'br' => array()
			);
			$dltText = '';
			if ($isIndia == esc_attr('India')) {
				$dltText .= '<br><b class="text-red">Make sure to input content in accordance with the DLT-approved message template.</b>';
			}
			switch ($sgcoption) {
				case 'config':
					$sgc_otp_settings_form = !empty($_POST['sgc_otp_settings_form']) ? sanitize_text_field(trim($_POST['sgc_otp_settings_form'])) : 0;
					if (!empty($sgc_otp_settings_form) && $sgc_otp_settings_form === '1') {
						check_admin_referer('smsgatewaycenter_otp_settings_nonce');
						$new_input = array();
						//general inputs
						$input = $_POST['sgcsms_otp_settings_option_name'];
						$otplength = !empty($input['otplength']) ? sanitize_text_field(trim($input['otplength'])) : '';
						$otpexpirytime = !empty($input['otpexpirytime']) ? sanitize_text_field(trim($input['otpexpirytime'])) : '';
						$otpretrytime = !empty($input['otpretrytime']) ? sanitize_text_field(trim($input['otpretrytime'])) : '';
						$maxattempts = !empty($input['maxattempts']) ? sanitize_text_field(trim($input['maxattempts'])) : '';
						$otptype = !empty($input['otptype']) ? sanitize_text_field(trim($input['otptype'])) : '';
						//registration otp related
						$enableregiotp = isset($input['enableregiotp']) ? '1' : '0'; //!empty($input['enableregiotp']) ? sanitize_text_field(trim($input['enableregiotp'])) : '';
						$regiMsgType = !empty($input['regiMsgType']) ? sanitize_text_field(trim($input['regiMsgType'])) : '';
						$regiMsgContent = !empty($input['regiMsgContent']) ? sanitize_text_field(trim($input['regiMsgContent'])) : '';
						$checkUnique = !empty($input['checkUnique']) ? sanitize_text_field(trim($input['checkUnique'])) : '';

						//general inputs
						$new_input['otplength'] = $otplength;
						$new_input['otpexpirytime'] = $otpexpirytime;
						$new_input['otpretrytime'] = $otpretrytime;
						$new_input['maxattempts'] = $maxattempts;
						$new_input['otptype'] = $otptype;
						//registration otp related
						$new_input['enableregiotp'] = $enableregiotp;
						$new_input['regiMsgType'] = $regiMsgType;
						$new_input['regiMsgContent'] = $regiMsgContent;
						$new_input['checkUnique'] = $checkUnique;
						if (!empty($new_input) && (empty($wpoptions[0]) || !is_array($wpoptions) || sizeof($wpoptions) <= 0)) {
							add_option('sgcsms_otp_settings_option_name', $new_input);
						}

						if (!empty($new_input) && isset($wpoptions) && is_array($wpoptions) && sizeof($wpoptions) > 0) {
							update_option('sgcsms_otp_settings_option_name', $new_input);
						}

						smsgatewaycenter_show_html_success('OTP related settings have been saved.');
						smsgatewaycenter_show_go_back_html();
					}
					break;
				default:
					?>
					<form class="form-capsule" style="margin-top:0" method=post action="<?php echo esc_url(sgc_get_admin_current_page_url()); ?>&sgcoption=config">
						<div class="grid">
							<div class="w6">
								<?php
								wp_nonce_field('smsgatewaycenter_otp_settings_nonce');

								$otplength = 6; // Default otplength value
								$otpexpirytime = 300; // Default otpexpirytime value
								$otpretrytime = 120; // Default otpretrytime value
								$maxattempts = 3; // Default maxattempts value
								$otptype = 'num'; // Default otp type value
								$regiMsgType = 'text'; // Default message type
								$regiMsgContent = ''; // Default otp template id value
								$checkUnique = 'yes'; // Default check unique id value

								if (isset($GLOBALS['wpoptions']) && is_array($GLOBALS['wpoptions'])) {
									$wpoptions = $GLOBALS['wpoptions']; // Assign to a local variable for easier access
									$otplength = smsgatewaycenter_default_fields($wpoptions, 'otplength', $otplength);
									$otpexpirytime = smsgatewaycenter_default_fields($wpoptions, 'otpexpirytime', $otpexpirytime);
									$otpretrytime = smsgatewaycenter_default_fields($wpoptions, 'otpretrytime', $otpretrytime);
									$maxattempts = smsgatewaycenter_default_fields($wpoptions, 'maxattempts', $maxattempts);
									$otptype = smsgatewaycenter_default_fields($wpoptions, 'otptype', $otptype, false);
									$regiMsgType = smsgatewaycenter_default_fields($wpoptions, 'regiMsgType', $regiMsgType, false);
									$regiMsgContent = smsgatewaycenter_default_fields($wpoptions, 'regiMsgContent', $regiMsgContent, false);
									$checkUnique = smsgatewaycenter_default_fields($wpoptions, 'checkUnique', $checkUnique, false);
								}
								?>
								<h3 class="h3-title">General OTP Settings</h3>
								<div class="postbox">
									<div class="inside">
										<div class="grid">
											<div class="w6">
												<div class="text">
													<label for="otplength"><?php esc_html_e('OTP Length', 'sgcsms'); ?></label>
													<div class="t-top t-xl full-width" data-tooltip="<?php esc_html_e('Set the length of the OTP code.', 'sgcsms'); ?>">
														<input type="text" class="form-control" name="sgcsms_otp_settings_option_name[otplength]" id="otplength" placeholder="<?php esc_html_e('OTP Length', 'sgcsms'); ?>" value="<?php echo trim(esc_attr($otplength)); ?>">
														<span class="help-block text-green text-sms"><small><?php esc_html_e('Enter value in integers. 6 = 6 digits/characters', 'sgcsms'); ?></small></span>
													</div>
												</div>
											</div>
											<div class="w6">
												<div class="text">
													<label for="otpexpirytime"><?php esc_html_e('OTP Expiry Time', 'sgcsms'); ?></label>
													<div class="t-top t-xl full-width" data-tooltip="<?php esc_html_e('Set the OTP expiration duration in seconds.', 'sgcsms'); ?>">
														<input type="text" class="form-control" name="sgcsms_otp_settings_option_name[otpexpirytime]" id="otpexpirytime" value="<?php echo trim(esc_attr($otpexpirytime)); ?>" placeholder="<?php esc_html_e('300', 'sgcsms'); ?>">
														<span class="help-block text-green text-sms"><small><?php esc_html_e('Enter value in seconds. 300 seconds = 5 minutes', 'sgcsms'); ?></small></span>
													</div>
												</div>
											</div>
											<div class="w6">
												<div class="text">
													<label for="otpretrytime"><?php esc_html_e('OTP Retry Time', 'sgcsms'); ?></label>
													<div class="t-top t-xl full-width" data-tooltip="<?php esc_html_e('Set the OTP retry duration in seconds.', 'sgcsms'); ?>">
														<input type="text" class="form-control" name="sgcsms_otp_settings_option_name[otpretrytime]" id="otpretrytime" value="<?php echo trim(esc_attr($otpretrytime)); ?>" placeholder="<?php esc_html_e('120', 'sgcsms'); ?>">
														<span class="help-block text-green text-sms"><small><?php esc_html_e('Enter value in seconds. 120 seconds = 2 minutes', 'sgcsms'); ?></small></span>
													</div>
												</div>
											</div>
											<div class="w6">
												<div class="text">
													<label for="maxattempts"><?php esc_html_e('Maximum Attempts', 'sgcsms'); ?></label>
													<div class="t-top t-xl full-width" data-tooltip="<?php esc_html_e('Define the maximum number of OTP attempts allowed..', 'sgcsms'); ?>">
														<input type="text" class="form-control" name="sgcsms_otp_settings_option_name[maxattempts]" id="maxattempts" value="<?php echo trim(esc_attr($maxattempts)); ?>" placeholder="<?php esc_html_e('3', 'sgcsms'); ?>">
														<span class="help-block text-green text-sms"><small><?php esc_html_e('Enter value in integers. 3 = 3 maximum attempts', 'sgcsms'); ?></small></span>
													</div>
												</div>
											</div>
											<div class="w6">
												<div class="form-group form-group-select">
													<label for="otptype"><?php esc_html_e('OTP Code Type', 'sgcsms'); ?></label>
													<div class="select-wrapper">
														<select class="form-control" name="sgcsms_otp_settings_option_name[otptype]" id="otptype">
															<option value="num"<?php
															if (isset($otptype) && trim($otptype) == 'num') {
																echo ' selected="selected"';
															}
															?>><?php esc_html_e('Numeric', 'sgcsms'); ?></option>
															<option value="alpha"<?php
															if (isset($otptype) && trim($otptype) == 'alpha') {
																echo ' selected="selected"';
															}
															?>><?php esc_html_e('Alphabetic', 'sgcsms'); ?></option>
															<option value="alphanum"<?php
															if (isset($otptype) && trim($otptype) == 'alphanum') {
																echo ' selected="selected"';
															}
															?>><?php esc_html_e('AlphaNumeric', 'sgcsms'); ?></option>
														</select>
														<span class="help-block text-green text-sms"><small><?php esc_html_e('Select OTP Code Type.', 'sgcsms'); ?></small></span>
													</div>
												</div>
											</div>
										</div>
									</div>
								</div>
							</div>
							<div class="w6">
								<h3 class="h3-title">Registration OTP Settings</h3>
								<div class="postbox">
									<div class="inside">
										<div class="grid">
											<div class="w6">
												<div class="checkbox margb20">
													<label for="enableregiotp" class="checkbox-inline"><?php esc_html_e('Enable OTP on Wordpress Registration', 'sgcsms'); ?>
														<input type="checkbox" class="check" name="sgcsms_otp_settings_option_name[enableregiotp]" id="enableregiotp"<?php if (!empty($wpoptions) && $wpoptions['enableregiotp'] == 1) { ?> checked="checked"<?php } ?>>
													</label>
												</div>
											</div>
											<div class="w6"></div>
											<div class="w6">
												<div class="text">
													<label for="checkUnique"><?php esc_html_e('Check for Unique Mobile', 'sgcsms'); ?></label>
													<div class="t-top t-xl full-width" data-tooltip="<?php esc_html_e('Check for Unique Mobile during registration', 'sgcsms'); ?>">
														<select class="form-control" name="sgcsms_otp_settings_option_name[checkUnique]" id="checkUnique">
															<option value="yes"<?php
															if (isset($checkUnique) && trim($checkUnique) == 'yes') {
																echo ' selected="selected"';
															}
															?>><?php esc_html_e('Yes', 'sgcsms'); ?></option>
															<option value="no"<?php
															if (isset($checkUnique) && trim($checkUnique) == 'no') {
																echo ' selected="selected"';
															}
															?>><?php esc_html_e('No', 'sgcsms'); ?></option>
														</select>
														<span class="help-block text-green text-sms"><small><?php esc_html_e('If you enable, then only unique mobile number will be accepted.', 'sgcsms'); ?></small></span>
													</div>
												</div>
											</div>
											<div class="w6">
												<div class="text">
													<label for="regMsgType"><?php esc_html_e('Message Type (required)', 'sgcsms'); ?></label>
													<div class="select-wrapper">
														<select class="form-control" name="sgcsms_otp_settings_option_name[regiMsgType]" id="regMsgType">
															<option value="text"<?php
															if (isset($regiMsgType) && trim($regiMsgType) == 'text') {
																echo ' selected="selected"';
															}
															?>><?php esc_html_e('Text', 'sgcsms'); ?></option>
															<option value="unicode"<?php
															if (isset($regiMsgType) && trim($regiMsgType) == 'unicode') {
																echo ' selected="selected"';
															}
															?>><?php esc_html_e('Unicode', 'sgcsms'); ?></option>
														</select>
													</div>
												</div>
											</div>
											<div class="w12">
												<div class="textarea">
													<label for="regiMsgContent"><?php esc_html_e('Message Content', 'sgcsms'); ?></label>
													<textarea class="form-control" rows="5" name="sgcsms_otp_settings_option_name[regiMsgContent]" id="regiMsgContent" placeholder="<?php esc_html_e('Enter your OTP registration message content in this area.', 'sgcsms'); ?>"><?php echo trim(esc_attr($regiMsgContent)); ?></textarea>
													<span class="help-block text-green text-sms"><small><?php echo wp_kses(__('For your registration OTP message template, please input your content here.<br><b class="text-red">Ensure to include the variable {OTPCODE} where the OTP code should appear.</b>' . $dltText . '<br>Example: <b class="text-purple">Hi there, Your registration OTP is: {OTPCODE} Enter this code to complete your registration. Thanks, My Blog Name</b>', 'sgcsms'), $allowed_html); ?></small></span>
												</div>
											</div>
										</div>
									</div>
								</div>
							</div>
						</div>
						<div class="grid">
							<div class="w4"><p>&nbsp;</p></div>
							<div class="w4">
								<p class="submit" style="text-align: center;">
									<input class="button3" type="submit" name="submit" value="<?php esc_html_e('Save OTP Settings', 'sgcsms'); ?>">
									<input type="hidden" name="sgc_otp_settings_form" value="1">
								</p>
							</div>
							<div class="w4"><p>&nbsp;</p></div>
						</div>
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

	if (!function_exists('smsgatewaycenter_default_fields')) {

		/**
		 * Set default field values based on WordPress options.
		 *
		 * @param array $wpoptions WordPress options array.
		 * @param string $field Field key.
		 * @param mixed $val Default value.
		 * @return mixed Updated value based on WordPress options.
		 */
		function smsgatewaycenter_default_fields($wpoptions, $field, $val, $isInt = true) {
			if (isset($wpoptions[$field]) && is_string($wpoptions[$field])) {
				if ($isInt) {
					$val = trim($wpoptions[$field]) !== '' ? intval(trim($wpoptions[$field])) : $val;
				} else {
					$val = trim($wpoptions[$field]) !== '' ? trim($wpoptions[$field]) : $val;
				}
			}
			return $val;
		}

	}