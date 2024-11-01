<?php

	/**
	 * SMS Gateway Center Bulk SMS Sender - A Wordpress SMS plugin
	 */
	if (!defined('ABSPATH'))
		exit;  // if direct access


	if (!function_exists('sgcsms_is_mobile_number_exists')) {

		/**
		 * Checks if a mobile number exists in the WordPress user database.
		 *
		 * @param string $mobile_number The mobile number to check.
		 * @return bool True if the mobile number exists, false otherwise.
		 */
		function sgcsms_is_mobile_number_exists($mobile_number) {
			$users = get_users(array(
				'meta_key' => 'sgcsms_user_mobile_number',
				'meta_value' => $mobile_number,
				'count_total' => true,
				'fields' => 'ID'
			));

			return count($users) > 0;
		}

	}

	if (!function_exists('sgcsms_otp_ajax_send_otp')) {

		/**
		 * Handles an AJAX request to send an OTP (One Time Password).
		 *
		 * This function is hooked to both logged-in (wp_ajax_) and non-logged-in (wp_ajax_nopriv_) AJAX actions.
		 * It expects a 'mobile' parameter in the POST request, representing the user's mobile number.
		 * 
		 * Steps:
		 * 1. Sanitizes the mobile number received from the AJAX request.
		 * 2. Validates the mobile number format (expects 10 to 15 digits).
		 * 3. Generates a random 4-digit OTP.
		 * 4. Stores the OTP in a transient associated with the mobile number for 2 minutes.
		 * 5. (To-Do) Initiates the process to send the OTP via SMS to the mobile number.
		 * 
		 * Responds with a success or error message and terminates the execution.
		 */
		function sgcsms_otp_ajax_send_otp($sanitized_user_login) {
			// Check if $GLOBALS['wpoptions'] is set and is an array
			if (isset($GLOBALS['wpoptions']) && is_array($GLOBALS['wpoptions'])) {

				//if otp registration is not enabled then quit the functionality
				if ($GLOBALS['wpoptions']['enableregiotp'] !== '1') {
					return;
				}

				// Check for the session token
				if (!isset($_SESSION['sgcsms_registration_token'])) {
					wp_die(__('Unauthorized access'), __('Unauthorized access'), 403);
				}

				//check for doing ajax
				if (!defined('DOING_AJAX') || !DOING_AJAX) {
					wp_die(__('Invalid request'), __('Invalid Request'), 403);
				}

				//check for http referer
				$referer = wp_get_referer();
				if (!$referer || !wp_http_validate_url($referer)) {
					wp_die(__('Invalid referer'), __('Invalid Referer'), 403);
				}

				// Verify the nonce
				$nonce_valid = check_ajax_referer('sgcsms_register_nonce', 'csrfToken', false);
				if (!$nonce_valid) {
					wp_die(__('Security check failed'), __('Security check failed'), 403);
				}

				// Sanitize the mobile number input
				$mobile_number = isset($_POST['sgcsms_mobile_number']) ? sanitize_text_field($_POST['sgcsms_mobile_number']) : '';
				$format_mobile_number = str_replace('+', '', $mobile_number);

				$mno = isset($_POST['mno']) ? sanitize_text_field($_POST['mno']) : '';

				// Add additional validation for the mobile number format here
				if (!preg_match('/^[0-9]{10,15}$/', $mno)) {
					// Handle the error appropriately
					status_header(400); // Set HTTP status to 400
					wp_send_json_error(__('Invalid mobile number.', 'sgcsms'));
					wp_die();
				}

				// Check if mobile number is already used by another user
				$checkUnique = $GLOBALS['wpoptions']['checkUnique'];
				if (isset($checkUnique) && $checkUnique === 'yes') {
					if (sgcsms_is_mobile_number_exists($format_mobile_number)) {
						status_header(400); // Set HTTP status to 400
						wp_send_json_error(__('This mobile number is already registered.', 'sgcsms'));
						wp_die();
					}
				}

				// Check if the number of attempts has exceeded the maximum allowed attempts
				$attempts = get_transient('sgcsmsotpattempts_' . $format_mobile_number);
				$maxAttemptsAllowed = $GLOBALS['wpoptions']['maxattempts'];
				if ($attempts !== false && $attempts >= $maxAttemptsAllowed) {
					status_header(400); // Set HTTP status to 400
					wp_send_json_error(__('You have exceeded the maximum number of attempts.', 'sgcsms'));
					wp_die();
				}

				//country data for future updates
				$countryData = isset($_POST['sgcsms_country_name']) ? sanitize_text_field($_POST['sgcsms_country_name']) : '';
				if (!empty($countryData)) {
					$countryArray = explode('|', $countryData);
				}

				//lets analyse if user has india route or international and take further action
				$dialCode = $countryArray[0];
				$iso2 = $countryArray[1];
				$countryName = $countryArray[2];

				//if user clicking on resend, then lets not initiate new OTP, lets resend if OTP is not delivered
				$sgcsms_resend = isset($_POST['sgcsms_resend']) ? sanitize_text_field($_POST['sgcsms_resend']) : '';

				//lets add the following attributes and attach it to the API
				$codeLength = $GLOBALS['wpoptions']['otplength'];
				$codeExpiry = $GLOBALS['wpoptions']['otpexpirytime'];
				$retryExpiry = $GLOBALS['wpoptions']['otpretrytime'];
				$codeType = $GLOBALS['wpoptions']['otptype'];
				$regiMsgType = $GLOBALS['wpoptions']['regiMsgType'];
				$regiMsgContent = $GLOBALS['wpoptions']['regiMsgContent'];
				$smscontent = str_replace('{USERNAME}', 'member', $regiMsgContent);
				$otpsms = str_replace('{OTPCODE}', '$otp$', $smscontent);
				$mask = get_option('sgcsms_default_sendername')[0];

				// Lets initiate SMS function here
				$jsonDecodeSGCResponse = smsgatewaycenter_send_otp_sms_api('generate', $format_mobile_number, $mask, $otpsms, $regiMsgType, $codeType, $codeExpiry, $codeLength, $retryExpiry, '');

				//check API response and print response for ajax
				if ((!empty($jsonDecodeSGCResponse->status) && trim($jsonDecodeSGCResponse->status) === 'error') || (!empty($jsonDecodeSGCResponse->response->status) && trim($jsonDecodeSGCResponse->response->status) === 'error')) {
					//sgc_print_array($jsonDecodeSGCResponse);
					status_header(400); // Set HTTP status to 400
					wp_send_json_error(__($jsonDecodeSGCResponse->reason, 'sgcsms'));
					wp_die();
				} else {
					// Check if the transient for OTP attempts exists and increment its value
					$attempts = get_transient('sgcsmsotpattempts_' . $format_mobile_number);
					if ($attempts !== false) {
						// Increment the number of attempts
						$attempts++;
					} else {
						// First attempt
						$attempts = 1;
					}

					// Store the updated number of attempts in the transient
					set_transient('sgcsmsotpattempts_' . $format_mobile_number, $attempts, 5 * MINUTE_IN_SECONDS);

					wp_send_json_success(['valid' => true]);
					wp_die();
				}
				wp_die();
				return;
			} else {
				return;
			}
		}

		add_action('wp_ajax_send_sgcsms_otp', 'sgcsms_otp_ajax_send_otp');
		add_action('wp_ajax_nopriv_send_sgcsms_otp', 'sgcsms_otp_ajax_send_otp');
	}

	if (!function_exists('sgcsms_otp_ajax_validate_otp')) {

		/**
		 * Handles an AJAX request to validate an OTP (One Time Password).
		 *
		 * Checks if the OTP functionality is enabled in the plugin's global options.
		 * Validates the received OTP against the stored OTP in the transient.
		 * Responds with a JSON indicating success or failure of validation.
		 */
		function sgcsms_otp_ajax_validate_otp() {
			// Check if $GLOBALS['wpoptions'] is set and is an array
			if (isset($GLOBALS['wpoptions']) && is_array($GLOBALS['wpoptions'])) {
				//if otp registration is not enabled then quit the functionality
				if ($GLOBALS['wpoptions']['enableregiotp'] !== '1') {
					return;
				}

				// Check for the session token
				if (!isset($_SESSION['sgcsms_registration_token'])) {
					wp_die(__('Unauthorized access'), __('Unauthorized access'), 403);
				}

				//check for doing ajax
				if (!defined('DOING_AJAX') || !DOING_AJAX) {
					wp_die(__('Invalid request'), __('Invalid Request'), 403);
				}

				//check for http referer
				$referer = wp_get_referer();
				if (!$referer || !wp_http_validate_url($referer)) {
					wp_die(__('Invalid referer'), __('Invalid Referer'), 403);
				}

				// Retrieve OTP and mobile number from POST data
				$otp = isset($_POST['otp']) ? sanitize_text_field($_POST['otp']) : '';
				$mobile_number = isset($_POST['mobile']) ? sanitize_text_field($_POST['mobile']) : '';
				$format_mobile_number = str_replace('+', '', $mobile_number);
				$mno = isset($_POST['mno']) ? sanitize_text_field($_POST['mno']) : '';

				$codeLength = $GLOBALS['wpoptions']['otplength'];
				if ($otp == '') {
					status_header(400); // Set HTTP status to 400
					wp_send_json_error(__('OTP code value cannot be blank.', 'sgcsms'));
					wp_die();
				}

				if (strlen($otp) !== $codeLength) {
					status_header(400); // Set HTTP status to 400
					wp_send_json_error(__('OTP length does not match.', 'sgcsms'));
					wp_die();
				}
				// Retrieve the saved OTP from transient
				//$saved_otp = get_transient('sgcsmsotp_' . $mobile_number);
				// Lets initiate SMS function here
				$jsonDecodeSGCResponse = smsgatewaycenter_send_otp_sms_api('verify', $format_mobile_number, '', '', '', '', '', '', '', $otp);
				//sgc_print_array($jsonDecodeSGCResponse);
				// Validate and send response
				//check API response and print response for ajax
				if ((!empty($jsonDecodeSGCResponse->status) && trim($jsonDecodeSGCResponse->status) === 'error') || (!empty($jsonDecodeSGCResponse->response->status) && trim($jsonDecodeSGCResponse->response->status) === 'error')) {
					//sgc_print_array($jsonDecodeSGCResponse);

					if ('OTP token is already verified.' === $jsonDecodeSGCResponse->reason) {
						status_header(200); // Set HTTP status to 200
						wp_send_json_success(['valid' => true, 'message' => 'OTP token is already verified.']);
					} else {
						status_header(400); // Set HTTP status to 400
						wp_send_json_error(__($jsonDecodeSGCResponse->reason, 'sgcsms'));
					}
					wp_die();
				} else {
					//set transient for 15 minutes
					set_transient('sgcsmsotp_validated_' . $format_mobile_number, 'validated', 15 * MINUTE_IN_SECONDS);
					$_SESSION['sgcsmsMobileVerified'] = $format_mobile_number;
					wp_send_json_success(['valid' => true]);
					wp_die();
				}
				wp_die();
				return;
			} else {
				// Respond with error if OTP validation is disabled or global options are not set correctly
				wp_send_json_error(['message' => 'OTP validation is disabled']);
				wp_die();
				return;
			}
		}

		add_action('wp_ajax_nopriv_validate_sgcsms_otp', 'sgcsms_otp_ajax_validate_otp');
		add_action('wp_ajax_validate_sgcsms_otp', 'sgcsms_otp_ajax_validate_otp');
	}
	