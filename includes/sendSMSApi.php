<?php

	/**
	 * SMS Gateway Center Bulk SMS Sender - A Wordpress SMS plugin
	 */
	if (!defined('ABSPATH'))
		exit;  // if direct access
	
	if (!function_exists('smsgatewaycenter_send_sms_api')) {

		/**
		 * Send SMS API
		 * @param int $to mobile number(s)
		 * @param string $mask sender name
		 * @param string $smscontent message content
		 * @param string $smstype message type
		 * @param string $isIndia domestic/international type
		 * @return array
		 * @throws Exception
		 */
		// function smsgatewaycenter_send_sms_api($sendMethod, $to, $mask, $smscontent, $smstype, $isIndia, $group = '', $file = '') {
		// 	if ($GLOBALS['apiname'] === 'smsgateway.center') {
		// 		$url = SGC_SMS_BASE_URL . '/SMSApi/rest/send';
		// 		$param['userId'] = $GLOBALS['username'];
		// 		$param['password'] = urlencode($GLOBALS['password']);
		// 		$param['senderId'] = $mask;
		// 		$param['msg'] = $smscontent;
		// 		$param['msgType'] = $smstype;
		// 		$param['format'] = 'json';
		// 	} else {
		// 		$url = SGC_UNIFY_SMS_BASE_URL . '/SMSApi/send';
		// 		$param['userid'] = $GLOBALS['username'];
		// 		$param['password'] = urlencode($GLOBALS['password']);
		// 		$param['senderid'] = $mask;
		// 		$param['msg'] = $smscontent;
		// 		$param['msgType'] = $smstype;
		// 		$param['output'] = 'json';
		// 	}
		// 	switch ($sendMethod) {
		// 		case 'quick':
		// 			$param['sendMethod'] = $GLOBALS['apiname'] === 'smsgateway.center' ? "simpleMsg" : "quick";
		// 			$param['mobile'] = $to;
		// 			break;
		// 		case 'group':
		// 			$param['sendMethod'] = $GLOBALS['apiname'] === 'smsgateway.center' ? "groupMsg" : "group";
		// 			$param['group'] = $group;
		// 			break;
		// 		case 'file':
		// 			$param['sendMethod'] = $GLOBALS['apiname'] === 'smsgateway.center' ? "excelMsg" : "bulkupload";
		// 			$param['file'] = $file;
		// 			break;
		// 		default:
		// 			break;
		// 	}
		// 	if ($isIndia == esc_attr('India')) {
		// 		$dltTemplateId = isset($_POST["dltTemplateId"]) ? sanitize_text_field(trim($_POST["dltTemplateId"])) : '';
		// 		$param['dltTemplateId'] = $dltTemplateId;
		// 	}


		// 	$postUrl = add_query_arg($param, $url);
		// 	$parsedParams = wp_parse_args([], [
		// 		'method' => 'POST'
		// 	]);
		// 	$sgc_api_response = wp_remote_request($postUrl, $parsedParams);
		// 	if (is_wp_error($sgc_api_response)) {
		// 		throw new Exception($sgc_api_response->get_error_message());
		// 	}
		// 	$smsgatewaycenter_api_response_code = wp_remote_retrieve_response_code($sgc_api_response);
		// 	if (in_array($smsgatewaycenter_api_response_code, [200, 201, 202]) === false) {
		// 		$smsgatewaycenter_api_response_error = json_decode($smsgatewaycenter_api_response, true);
		// 		throw new Exception(sprintf(__('Failed to get success response, %s', 'sgcsms'), print_r($smsgatewaycenter_api_response_error, 1)));
		// 	}
		// 	$smsgatewaycenter_api_response = wp_remote_retrieve_body($sgc_api_response);
		// 	$jsonDecodeSGCResponse = json_decode($smsgatewaycenter_api_response);
		// 	return $jsonDecodeSGCResponse;
		// }
		function smsgatewaycenter_send_sms_api($sendMethod, $to, $mask, $smscontent, $smstype, $isIndia, $group = '', $file = '') {
			if ($GLOBALS['apiname'] === 'smsgateway.center') {
				$url = SGC_SMS_BASE_URL . '/SMSApi/rest/send';
				$param['userId'] = $GLOBALS['username'];
				$param['password'] = urlencode($GLOBALS['password']);
				$param['senderId'] = $mask;
				$param['msg'] = $smscontent;
				$param['msgType'] = $smstype;
				$param['format'] = 'json';
			} else {
				$url = SGC_UNIFY_SMS_BASE_URL . '/SMSApi/send';
				$param['userid'] = $GLOBALS['username'];
				$param['password'] = urlencode($GLOBALS['password']);
				$param['senderid'] = $mask;
				$param['msg'] = $smscontent;
				$param['msgType'] = $smstype;
				$param['output'] = 'json';
			}

			switch ($sendMethod) {
				case 'quick':
					$param['sendMethod'] = $GLOBALS['apiname'] === 'smsgateway.center' ? "simpleMsg" : "quick";
					$param['mobile'] = $to;
					break;
				case 'group':
					$param['sendMethod'] = $GLOBALS['apiname'] === 'smsgateway.center' ? "groupMsg" : "group";
					$param['group'] = $group;
					break;
				case 'file':
					$param['sendMethod'] = $GLOBALS['apiname'] === 'smsgateway.center' ? "excelMsg" : "bulkupload";
					$param['file'] = $file;
					break;
				default:
					break;
			}

			if ($isIndia == esc_attr('India')) {
				$dltTemplateId = isset($_POST["dltTemplateId"]) ? sanitize_text_field(trim($_POST["dltTemplateId"])) : '';
				$param['dltTemplateId'] = $dltTemplateId;
			}

			// Send request using wp_remote_post
			$sgc_api_response = wp_remote_post($url, [
				'method' => 'POST',
				'body' => $param,
				'timeout' => 45,
				'headers' => [
					'Content-Type' => 'application/x-www-form-urlencoded',
				],
			]);

			// Handle errors
			if (is_wp_error($sgc_api_response)) {
				throw new Exception($sgc_api_response->get_error_message());
			}

			$smsgatewaycenter_api_response_code = wp_remote_retrieve_response_code($sgc_api_response);
			if (!in_array($smsgatewaycenter_api_response_code, [200, 201, 202])) {
				$smsgatewaycenter_api_response_error = json_decode(wp_remote_retrieve_body($sgc_api_response), true);
				throw new Exception(sprintf(__('Failed to get success response, %s', 'sgcsms'), print_r($smsgatewaycenter_api_response_error, 1)));
			}

			$smsgatewaycenter_api_response = wp_remote_retrieve_body($sgc_api_response);

			$jsonDecodeSGCResponse = json_decode($smsgatewaycenter_api_response);
			return $jsonDecodeSGCResponse;
		}

	}
	
	if (!function_exists('smsgatewaycenter_send_otp_sms_api')) {

		/**
		 * Send OTP SMS API
		 * @param int $to mobile number(s)
		 * @param string $mask sender name
		 * @param string $smscontent message content
		 * @param string $smstype message type
		 * @param string $isIndia domestic/international type
		 * @return array
		 * @throws Exception
		 */
		function smsgatewaycenter_send_otp_sms_api($otpType, $to, $mask, $smscontent, $smsmsgtype, $codeType, $codeExpiry, $codeLength, $retryExpiry, $code='') {
			if ($GLOBALS['apiname'] === 'smsgateway.center') {
				$url = SGC_SMS_BASE_URL . '/OTPApi/send';
				$param['userId'] = $GLOBALS['username'];
				$param['password'] = urlencode($GLOBALS['password']);
				$param['format'] = 'json';
			} else {
				$url = SGC_UNIFY_SMS_BASE_URL . '/SMSApi/otp';
				$param['userid'] = $GLOBALS['username'];
				$param['password'] = urlencode($GLOBALS['password']);
				$param['format'] = 'json';
			}
			$param['mobile'] = $to;
			$param['sendMethod'] = strtolower($otpType);
			switch (strtolower($otpType)) {
				case 'generate':
					$param['msgType'] = $smsmsgtype;
					$param['msg'] = $smscontent;
					$param['medium'] = 'sms';
					$param['codeType'] = $codeType;
					$param['codeExpiry'] = $codeExpiry;
					$param['codeLength'] = $codeLength;
					$param['retryExpiry'] = $retryExpiry;
					if ($GLOBALS['apiname'] === 'smsgateway.center') {
						$param['senderId'] = $mask;
					} else {
						$param['senderid'] = $mask;
					}
					break;
				case 'verify':
					$param['otp'] = $code;
					break;
				default:
					break;
			}

			$postUrl = add_query_arg($param, $url);
			$parsedParams = wp_parse_args([], [
				'method' => 'POST'
			]);
			$sgc_api_response = wp_remote_request($postUrl, $parsedParams);
			//sgc_print_array($sgc_api_response);
			if (is_wp_error($sgc_api_response)) {
				throw new Exception($sgc_api_response->get_error_message());
			}
			$smsgatewaycenter_api_response_code = wp_remote_retrieve_response_code($sgc_api_response);
			if (in_array($smsgatewaycenter_api_response_code, [200, 201, 202]) === false) {
				$smsgatewaycenter_api_response_error = json_decode($smsgatewaycenter_api_response, true);
				throw new Exception(sprintf(__('Failed to get success response, %s', 'sgcsms'), print_r($smsgatewaycenter_api_response_error, 1)));
			}
			$smsgatewaycenter_api_response = wp_remote_retrieve_body($sgc_api_response);
			$jsonDecodeSGCResponse = json_decode($smsgatewaycenter_api_response);
			return $jsonDecodeSGCResponse;
		}

	}