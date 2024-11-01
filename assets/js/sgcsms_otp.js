	/**
	 * SMS Gateway Center Bulk SMS Sender - A Wordpress SMS plugin
	 */
	jQuery(function () {
		send_sgcsms_otp();
		var baseUrl = sgcsms_ajax_object.sgcsms_otp_base_url;
		initializeIntlTelInput(baseUrl);
		jQuery('#wp-submit').prop('disabled', true);
		if (sgcsms_ajax_object.sgcsm === jQuery("#sgcsm").val()) {
			jQuery('#wp-submit').prop('disabled', false);
			jQuery('#sgcsmswh').empty();
			jQuery('#mobFieldDiv').append(sgcsms_def_otp_success_div('OTP verified successfully!'));
		}
		jQuery('#send_sgcsms_otp_button').hide();
		sgcsms_show_hide_otp_btn('sgcsms_mobile_number', 7, 'send_sgcsms_otp_button');
		jQuery('.c1,.c2,.c3,.c4,.c5,.c6,.c7').hide();

		// Call validateOTP when the OTP field is changed
		jQuery('#sgcsms_otp_code').on('input', function (e) {
			e.preventDefault();
			var id = jQuery(this).val();
			if (id.length >= sgcsms_ajax_object.sgcotplen) {
				validate_sgcsms_otp();
			}
		});
		//fail safe if lenght validate does not run
		jQuery('#send_sgcsms_verify_otp_button').click(function (e) {
			e.preventDefault();
			validate_sgcsms_otp();
		});
	});

	function sgcsms_def_otp_success_div(text) {
		// Create a new div element with an ID and text
		var alertDiv = jQuery('<div>', {
			id: 'sgcsmsotpsucmsg',
			class: 'text-green text-sm',
			text: text
		});
		return alertDiv;
	}

	/**
	 * Show hide div for otp code field
	 * @param {type} id
	 * @param {type} len
	 * @param {type} btn
	 * @returns {undefined}
	 */
	function sgcsms_show_hide_otp_btn(id, len, btn) {
		jQuery('#' + id).on('input', function () {
			var id = jQuery(this).val();
			// Check if the length of input is at least "len" digits
			if (id.length >= len) {
				jQuery('#' + btn).show();
				jQuery('.c1,.c2,.c3').show();
			} else {
				jQuery('#' + btn).hide();
				jQuery('.c1,.c2,.c3,.c4,.c5,.c6,.c7').hide();
			}
		});
	}

	/**
	 * Send OTP SMS
	 * @returns {undefined}
	 */
	function send_sgcsms_otp() {
		jQuery('#send_sgcsms_otp_button,#resendsgcsmsotp').click(function (e) {
			e.preventDefault();
			var mno = jQuery('input[name="sgcsms_mobile_number"]').val();
			var mobile = jQuery('input[name="formattedSgcSmsOtpMno"]').val();
			var country = jQuery('input[name="sgcsms_country_name"]').val();
			//var sgcsms_register_nonce = jQuery('input[name="sgcsms_register_nonce"]').val();
			if (mobile === '') {
				alert('Input valid mobile number.');
				return false;
			}
			if (country === '') {
				alert('Input country.');
				return false;
			}
			var clickedButtonId = jQuery(this).attr('id'); // Get the ID of the clicked button
			var data = {
				action: 'send_sgcsms_otp',
				sgcsms_mobile_number: mobile,
				sgcsms_country_name: country,
				csrfToken: sgcsms_ajax_object.nonce,
				mno: mno
			};

			// Add resend: 1 to the data object if #resendsgcsmsotp button was clicked
			if (clickedButtonId === 'resendsgcsmsotp') {
				data.sgcsms_resend = 1;
			}
			jQuery("#sgcsms_mobile_number").attr('readonly', 'readonly');
			jQuery("#resendsgcsmsotp").css({"pointer-events": "none", color: "#adb5bd", "text-decoration": "none"});
			jQuery.ajax({
				url: sgcsms_ajax_object.ajaxurl,
				type: 'post',
				data: data,
				success: function (response) {
					if (response.success) {
						jQuery('.c1,.c2,.c3').hide();
						jQuery('.c3,.c4,.c5,.c6,.c7').show();
						jQuery('#send_sgcsms_otp_button').hide();
						jQuery('#sgcsmsotpsucmsg').removeClass('text-red text-sm').text('An OTP has been sent to your mobile number; please verify by entering the OTP.').addClass('text-green text-sm');
						jQuery('#sgcsms_otp_code').show();
						jQuery('#send_sgcsms_verify_otp_button').show();

						var counter = sgcsms_ajax_object.rtry;
						var interval = setInterval(function () {
							counter--;
							if (counter <= 0) {
								clearInterval(interval);
								jQuery("#resendsgcsmsotp");
								jQuery('#resendsgcsmsotp').show().removeClass('disabled text-gray').css({"pointer-events": "", "cursor": "pointer", color: "", "text-decoration": "underline"}).addClass('text-blue');
								jQuery('#sgcsmsOtpRemTime').hide();
								jQuery('#sgcsms_otp_code').hide();
								jQuery('#sgcsms_otp_code').hide();
								jQuery('#send_sgcsms_verify_otp_button').hide();
							} else {
								jQuery('#resendsgcsmsotp').show().addClass('disabled text-gray').css({"pointer-events": "none", "cursor": "", color: "#adb5bd", "text-decoration": "none"}).removeClass("text-blue");
								jQuery('#sgcsmsOtpRemTime').show().removeClass('text-red text-sm').addClass('text-green text-sm').text('Your OTP is on its way! If you don\'t receive it, you can request a new one in ' + counter + ' seconds');
							}
						}, 1000);

						setTimeout(function () {
							jQuery('#sgcsmsotpsucmsg').fadeOut('fast');
						}, 4000);
					} else if (response.data === "OTP is already verified.") {
						jQuery('#sgcsmswh').empty();
						jQuery('#mobFieldDiv').append(sgcsms_def_otp_success_div('OTP verified successfully'));
						//jQuery('#sgcsmsotpsucmsg').text(response.data).addClass('text-green text-sm');
//						setTimeout(function () {
//							jQuery('.c1').empty().removeClass('text-red text-sm');
//						}, 3000);
					} else {
						jQuery('.c4,.c5').show();
						jQuery('.c1').text(response.data).addClass('text-red text-sm');
						setTimeout(function () {
							jQuery('.c1').empty().removeClass('text-red text-sm');
						}, 3000);
						jQuery("#sgcsms_mobile_number").removeAttr('readonly');
					}
				},
				error: function (xhr, textStatus, errorThrown) {
					// Parse the JSON response
					var response = JSON.parse(xhr.responseText);
					if (xhr.status === '400') {
						// Check if the response has a data attribute with the error message
						if (response && response.data) {
							jQuery('.c4, .c5').show();
							jQuery('#sgcsmsotpsucmsg').text(response.data).addClass('text-red text-sm');
						} else if (response && response.success === 'false') {
							jQuery('.c4, .c5').show();
							jQuery('#sgcsmsotpsucmsg').text(response.data).addClass('text-red text-sm');
						} else {
							// Fallback error message if response.data is not available
							jQuery('#sgcsmsotpsucmsg').text('An error occurred. Please try again.').addClass('text-red text-sm');
						}
					} else {
						// Handle other status codes or generic error
						jQuery('#sgcsmsotpsucmsg').text('An unexpected error occurred.').addClass('text-red text-sm');
					}
					setTimeout(function () {
						jQuery('#sgcsmsotpsucmsg').empty().removeClass('text-red text-sm');
						jQuery('.c4, .c5').hide();
					}, 4000);
					jQuery("#sgcsms_mobile_number").removeAttr('readonly');
				}

			});
		});
	}

	/**
	 * Validate OTP SMS
	 * @returns {undefined}
	 */
	function validate_sgcsms_otp() {
		var otp = jQuery('#sgcsms_otp_code').val();
		var mno = jQuery('input[name="sgcsms_mobile_number"]').val();
		var mobile = jQuery('input[name="formattedSgcSmsOtpMno"]').val();
		var country = jQuery('input[name="sgcsms_country_name"]').val();
		jQuery.ajax({
			url: sgcsms_ajax_object.ajaxurl,
			type: 'post',
			dataType: 'json',
			data: {
				action: 'validate_sgcsms_otp',
				otp: otp,
				mobile: mobile,
				mno: mno,
				csrfToken: sgcsms_ajax_object.nonce,
			},
			success: function (response) {
				if (response.data.valid || response.success) {
					jQuery('#sgcsmswh').empty();
					jQuery("#send_sgcsms_verify_otp_button").hide();
					jQuery("#sgcsms_otp_code").hide();
					jQuery("#sgcsmsOtpRemTime").hide().empty();
					jQuery('#wp-submit').prop('disabled', false); // Enable the register button
					jQuery('#sgcsmsotpsucmsg').removeClass('text-red text-sm').text('OTP has been Verified Successfully.').addClass('text-green text-sm');
					jQuery('#mobFieldDiv').append(sgcsms_def_otp_success_div('OTP verified successfully'));
				} else {
					jQuery('#sgcsmsotpsucmsg').show().text('Invalid OTP. Please try again.').addClass('text-red text-sm');
					setTimeout(function () {
						jQuery('#sgcsmsotpsucmsg').empty().removeClass('text-red text-sm').hide();
						jQuery('.c4, .c5').hide();
					}, 4000);
				}
			},
			error: function (xhr, textStatus, errorThrown) {
				// Parse the JSON response
				var response = JSON.parse(xhr.responseText);
				if (xhr.status === '400') {
					// Check if the response has a data attribute with the error message
					if (response && response.data) {
						jQuery('.c4, .c5').show();
						jQuery('#sgcsmsotpsucmsg').show().text(response.data).addClass('text-red text-sm');
					} else if (response && response.success === 'false') {
						jQuery('.c4, .c5').show();
						jQuery('#sgcsmsotpsucmsg').show().text(response.data).addClass('text-red text-sm');
					} else {
						// Fallback error message if response.data is not available
						jQuery('#sgcsmsotpsucmsg').show().text('An error occurred. Please try again.').addClass('text-red text-sm');
					}
				} else {
					// Handle other status codes or generic error
					//jQuery('#sgcsmsotpsucmsg').text('An unexpected error occurred.').addClass('text-red text-sm');
					if (response && response.data) {
						jQuery('.c4, .c5').show();
						jQuery('#sgcsmsotpsucmsg').show().text(response.data).addClass('text-red text-sm');
					}
				}
				setTimeout(function () {
					jQuery('#sgcsmsotpsucmsg').empty().removeClass('text-red text-sm').hide();
					jQuery('.c4, .c5').hide();
				}, 4000);
			}
		});
	}

	/**
	 * Initialize intel input
	 * @param {type} baseUrl
	 * @returns {undefined}
	 */
	function initializeIntlTelInput(baseUrl) {
		var input = document.querySelector("#sgcsms_mobile_number");
		var iti = window.intlTelInput(input, {utilsScript: baseUrl + "/assets/plugins/intl-tel-input/js/utils.js",
			preferredCountries: ['in', "us"],
			defaultCountry: 'in',
			nationalMode: false,
			preventInvalidDialCodes: true,
			hiddenInput: "formattedSgcSmsOtpMno",
			autoHideDialCode: false,
			formatOnDisplay: false,
			separateDialCode: true});
		// Create or update hidden input for country name
		var countryInput = document.getElementById('sgcsms_country_name');
		if (!countryInput) {
			countryInput = document.createElement('input');
			countryInput.type = 'hidden';
			countryInput.name = 'sgcsms_country_name';
			countryInput.id = 'sgcsms_country_name';
			input.form.appendChild(countryInput); // Assuming the input is inside a form
		}

		// Function to update country name
		function updateCountryName() {
			var countryData = iti.getSelectedCountryData();
			//countryInput.value = countryData.name || ''; // Set country name or empty string if not available
			countryInput.value = countryData.dialCode + '|' + countryData.iso2 + '|' + countryData.name || ''; // Set country attribute or empty string if not available
		}

		// Initialize country name
		updateCountryName();

		// Listen for country change
		input.addEventListener('countrychange', function () {
			updateCountryName();
		});
		jQuery(input).closest('form').find('input[name="formattedSgcSmsOtpMno"]').attr('id', 'formattedSgcSmsOtpMno');
		var e = iti.getSelectedCountryData().dialCode, t = input.value, t = (e.length, t.substr(1));
		jQuery("#sgcsms_mobile_number").val(t);
		jQuery("#formattedSgcSmsOtpMno").val(iti.getNumber());
		jQuery("#sgcsms_mobile_number").on("keyup", function () {
			jQuery("#formattedSgcSmsOtpMno").val(iti.getNumber());
		});
		jQuery('#sgcsms_mobile_number').on('input', function () {
			var sanitized = jQuery(this).val().replace(/[^0-9]/g, '');
			jQuery(this).val(sanitized);
		});
	}