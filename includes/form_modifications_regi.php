<?php
	/**
	 * SMS Gateway Center Bulk SMS Sender - A Wordpress SMS plugin
	 */
	if (!defined('ABSPATH'))
		exit;  // if direct access

	if (!function_exists('sgcsms_start_session')) {

		/**
		 * Start session if not found
		 */
		function sgcsms_start_session() {
			if (!session_id()) {
				session_start();
			}
		}

	}

	add_action('init', 'sgcsms_start_session', 1);

	if (!function_exists('sgcsms_otp_add_mobile_field')) {

		/**
		 * Store User Mobile Number
		 *
		 * This function is responsible for storing the user's mobile number in user meta during user registration.
		 * It checks if the mobile number is set in the registration form and not empty. If conditions are met,
		 * it sanitizes the mobile number and updates the user meta with the sanitized value.
		 *
		 * @param int $user_id User ID of the registered user.
		 * @return void
		 */
		function sgcsms_store_user_mobile_number($user_id) {
			// Check if the mobile number is set and not empty
			if (isset($_POST['formattedSgcSmsOtpMno']) && !empty($_POST['formattedSgcSmsOtpMno'])) {
				// Store the mobile number in user meta
				update_user_meta($user_id, 'sgcsms_user_mobile_number', $_SESSION['sgcsmsMobileVerified']);
				//lets end the stored session once the registration is complete
				unset($_SESSION['sgcsmsMobileVerified']);
				unset($_SESSION['sgcsms_registration_token']);
			}
		}

	}

	// Hook into the user registration process
	add_action('user_register', 'sgcsms_store_user_mobile_number', 10, 1);

	if (!function_exists('sgcsms_otp_add_mobile_field')) {
		add_action('register_form', 'sgcsms_otp_add_mobile_field');

		/**
		 * Modify WordPress form and display mobile field for verification.
		 *
		 * This function checks if OTP registration is enabled, and if so, it adds a mobile field to the registration form.
		 * It includes a button to send an OTP and a hidden field for entering the OTP.
		 *
		 * @return void
		 */
		function sgcsms_otp_add_mobile_field() {

			// Check if $GLOBALS['wpoptions'] is set and is an array
			if (isset($GLOBALS['wpoptions']) && is_array($GLOBALS['wpoptions'])) {
				//if otp registration is not enabled then quit the functionality
				if ($GLOBALS['wpoptions']['enableregiotp'] !== '1') {
					return;
				}

				// Set a unique session variable for the registration page
				$_SESSION['sgcsms_registration_token'] = md5(uniqid(mt_rand(), true));

				if (isset($_SESSION['sgcsmsMobileVerified']) && !empty($_SESSION['sgcsmsMobileVerified'])) {
					// Render read-only field
					$mobinput = '<input type="text" maxlength="15" name="sgcsms_mobile_number" id="sgcsms_mobile_number" class="input" value="+' . esc_attr($_SESSION['sgcsmsMobileVerified']) . '" readonly />'
						. '<input type="hidden" id="sgcsm" value="1">';
				} else {
					// Render normal field
					$mobinput = '<input type="text" maxlength="15" name="sgcsms_mobile_number" id="sgcsms_mobile_number" class="input" />';
				}
				?>
				<div id="mobFieldDiv">
					<label for="sgcsms_mobile_number"><?php _e('Mobile Number', 'sgcsms'); ?></label>
					<?php _e($mobinput, 'sgcsms'); ?>
				</div>
				<div id="sgcsmswh">
					<div class="clearfix c1"></div>
					<p>
						<button id="send_sgcsms_otp_button" class="btn button-primary-sgcsms">Send OTP</button>
					</p>
					<div class="clearfix c2"></div>
					<div class="clearfix c3"></div>
					<div id="sgcsmsotpsucmsg"></div>
					<input type="text" maxlength="<?php echo trim(esc_attr($GLOBALS['wpoptions']['otplength'])); ?>" name="sgcsms_otp_code" id="sgcsms_otp_code" class="input" size="25" style="display:none;" placeholder="Enter OTP" />
					<span class="help-block text-gray displaynone fr" id="resendsgcsmsotp"><?php _e('Resend OTP', 'sgcsms'); ?></span>
					<div class="clearfix c4"></div>
					<span class="help-block text-red displaynone" id="sgcsmsOtpRemTime"></span>
					<div class="clearfix c5"></div>
				</div>
				<?php
			} else {
				return;
			}
		}

	}

	if (!function_exists('sgcsms_enqueue_otp_script')) {
		add_action('login_enqueue_scripts', 'sgcsms_enqueue_otp_script');

		/**
		 * Enqueue scripts and styles for OTP registration form.
		 *
		 * This function checks if OTP registration is enabled, and if so, it enqueues the necessary scripts and styles
		 * for the OTP registration form. The scripts include international telephone input functionality.
		 *
		 * @return void
		 */
		function sgcsms_enqueue_otp_script() {
			// Check if $GLOBALS['wpoptions'] is set and is an array
			if (isset($GLOBALS['wpoptions']) && is_array($GLOBALS['wpoptions'])) {
				//if otp registration is not enabled then quit the functionality
				if ($GLOBALS['wpoptions']['enableregiotp'] !== '1') {
					return;
				}
				$sessionMobile = 0;
				if (isset($_SESSION['sgcsmsMobileVerified']) && !empty($_SESSION['sgcsmsMobileVerified'])) {
					$sessionMobile = 1;
				}

				// Enqueue necessary styles and scripts
				wp_enqueue_style('sgcsms_css_sgcsms_guest', SGC_SMS_ADMIN_URL . 'assets/css/sgcsms_guest.css', true, SGC_SMS_PLUGIN_VERSION);
				wp_enqueue_style('sgcsms_otp_css', SGC_SMS_ADMIN_URL . '/assets/plugins/intl-tel-input/css/intlTelInput.min.css');
				wp_enqueue_script('sgcsms_otp_js', SGC_SMS_ADMIN_URL . '/assets/js/sgcsms_otp.js', array('jquery'), null, true);
				wp_enqueue_script('sgcsms_intltelinput_js', SGC_SMS_ADMIN_URL . '/assets/plugins/intl-tel-input/js/intlTelInput.min.js', array('jquery'), null, true);

				// Localize script for passing data to JavaScript
				wp_localize_script('sgcsms_otp_js', 'sgcsms_ajax_object', array(
					'ajaxurl' => admin_url('admin-ajax.php'),
					'expti' => esc_attr($GLOBALS['wpoptions']['otpexpirytime']),
					'rtry' => esc_attr($GLOBALS['wpoptions']['otpretrytime']),
					'sgcotplen' => esc_attr($GLOBALS['wpoptions']['otplength']),
					'sgcsm' => esc_attr($sessionMobile),
					'nonce' => wp_create_nonce('sgcsms_register_nonce'),
					'sgcsms_otp_base_url' => SGC_SMS_ADMIN_URL // base URL of the plugin
				));
			} else {
				return;
			}
		}

	}



	if (!function_exists('sgcsms_otp_validate_mobile_field')) {

		/**
		 * Validate the mobile number field during user registration.
		 *
		 * @param string $sanitized_user_login The submitted username after being sanitized.
		 * @param string $user_email The submitted email.
		 * @param WP_Error $errors WP_Error object containing any errors encountered during registration.
		 */
		function sgcsms_otp_validate_mobile_field($sanitized_user_login, $user_email, $errors) {
			// Check if $GLOBALS['wpoptions'] is set and is an array
			if (isset($GLOBALS['wpoptions']) && is_array($GLOBALS['wpoptions'])) {
				//if otp registration is not enabled then quit the functionality
				if ($GLOBALS['wpoptions']['enableregiotp'] !== '1') {
					return;
				}
				//sgc_print_array($_POST, true);exit;
				if (empty($_POST['sgcsms_mobile_number']) || !preg_match('/^[0-9]{7,15}$/', $_POST['sgcsms_mobile_number'])) {
					$errors->add('sgcsms_mobile_number_error', __('<strong>ERROR</strong>: Please enter a valid mobile number.', 'sgcsms'));
				}
				$mobile_number = sanitize_text_field($_POST['formattedSgcSmsOtpMno']);
				$format_mobile_number = str_replace('+', '', $mobile_number);
				$otp_validated = get_transient('sgcsmsotp_validated_' . $format_mobile_number);

				// Check if mobile number is already used by another user
				if (sgcsms_is_mobile_number_exists($format_mobile_number)) {
					$errors->add('sgcsms_mobile_number_exists', __('<strong>ERROR</strong>: This mobile number is already registered.', 'sgcsms'));
				}

				if (isset($_SESSION['sgcsmsMobileVerified']) && !empty($_SESSION['sgcsmsMobileVerified']) && $format_mobile_number !== $_SESSION['sgcsmsMobileVerified']) {
					$errors->add('sgcsms_mobile_number_error', __('<strong>ERROR</strong>: Mobile number have been changed.', 'sgcsms'));
				}

				if (isset($_SESSION['sgcsmsMobileVerified']) && !empty($_SESSION['sgcsmsMobileVerified'])) {
					//all well
				} else {
					$errors->add('sgcsms_otp_validation_error', __('<strong>ERROR</strong>: OTP has not been validated.', 'sgcsms'));
				}
			} else {
				return;
			}
		}

	}
	// Hook into the registration process for custom validation.
	add_action('register_post', 'sgcsms_otp_validate_mobile_field', 10, 3);
	