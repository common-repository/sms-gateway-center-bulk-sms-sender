<?php
	/**
	 * SMS Gateway Center Bulk SMS Sender - A Wordpress SMS plugin
	 */
	if (!defined('ABSPATH'))
		exit;  // if direct access
?>

<h3 class="h3-title">International Routing API Authentication</h3>
<div class="postbox">
	<div class="inside">
		<div class="form-group form-group-select">
			<label for="defRouting"><?php esc_html_e('International Routing (required)', 'sgcsms'); ?></label>
			<div class="select-wrapper">
				<select class="form-control" name="defRouting" id="defRouting" required="">
					<option value="<?php esc_html_e('International', 'sgcsms'); ?>"<?php echo esc_attr(smsgatewaycenter_get_default_routing_selected('International')); ?>><?php esc_html_e('International', 'sgcsms'); ?></option>
				</select>
				<blockquote><small><i><b>International</b> routing will be used when SMS sent. Indian DLT attributes will not be required.</i></small></blockquote>
			</div>
		</div>
		<div class="text">
			<label for="username"><?php esc_html_e('Username', 'sgcsms'); ?></label>
			<input type="text" class="form-control" name="username" id="username" value="<?php echo esc_attr($GLOBALS['username']); ?>" placeholder="<?php esc_html_e('Your username', 'sgcsms'); ?>" required="">
			<blockquote><small>Your registered username on smsgateway.center <a href="<?php echo esc_url('http://www.smsgateway.center'); ?>" target="_blank">get it here</a>.</small></blockquote>
		</div>
		<?php if (!smsgatewaycenter_is_loggedin()) { ?>
				<div class="text">
					<label for="password"><?php esc_html_e('Password', 'sgcsms'); ?></label>
					<input type="password" class="form-control" name="password" id="password" value="<?php echo esc_attr($GLOBALS['password']); ?>" placeholder="<?php esc_html_e('Your password', 'sgcsms'); ?>" required="">
					<blockquote><small>Your password which you use to login at SMSGatewayCenter.com</small></blockquote>
				</div>
				<p class="submit">
					<input class="button3" type="submit" name="submit" value="<?php esc_html_e('Authenticate', 'sgcsms'); ?>">
					<input type="hidden" name="sendSGCSMSSettingsForm" value="1">
				</p>
			<?php } ?>
	</div>
</div>