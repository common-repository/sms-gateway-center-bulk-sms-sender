<?php
	/**
	 * SMS Gateway Center Bulk SMS Sender - A Wordpress SMS plugin
	 */
	if (!defined('ABSPATH'))
		exit;  // if direct access

	if (!function_exists('sgcsms_display_mobile_number_field')) {

		/**
		 * Displays the mobile number field in the user profile.
		 *
		 * @param WP_User $user The user object.
		 */
		function sgcsms_display_mobile_number_field($user) {
			$mobile_number = get_user_meta($user->ID, 'sgcsms_user_mobile_number', true);
			?>
			<h3><?php _e('Mobile Number', 'sgcsms'); ?></h3>
			<table class="form-table">
				<tr>
					<th><label for="sgcsms_user_mobile_number"><?php _e('Mobile Number', 'sgcsms'); ?></label></th>
					<td>
						<input type="text" name="sgcsms_user_mobile_number" id="sgcsms_user_mobile_number" value="<?php echo esc_attr($mobile_number); ?>" readonly class="regular-text" />
					</td>
				</tr>
			</table>
			<?php
		}

	}

	add_action('show_user_profile', 'sgcsms_display_mobile_number_field'); // For editing own profile
	add_action('edit_user_profile', 'sgcsms_display_mobile_number_field'); // For editing another user's profile

	if (!function_exists('sgcsms_add_mobile_number_column')) {

		/**
		 * Adds a mobile number column to the WordPress users table.
		 *
		 * @param array $columns The existing columns.
		 * @return array The modified columns.
		 */
		function sgcsms_add_mobile_number_column($columns) {
			$columns['sgcsms_user_mobile_number'] = __('Mobile Number', 'sgcsms');
			return $columns;
		}

	}

	add_filter('manage_users_columns', 'sgcsms_add_mobile_number_column');

	if (!function_exists('sgcsms_show_mobile_number_column_content')) {

		/**
		 * Displays the content of the mobile number column in the WordPress users table.
		 *
		 * @param string $value      The column value.
		 * @param string $column_name The column name.
		 * @param int    $user_id     The user ID.
		 * @return string The modified column value.
		 */
		function sgcsms_show_mobile_number_column_content($value, $column_name, $user_id) {
			if ('sgcsms_user_mobile_number' == $column_name) {
				return get_user_meta($user_id, 'sgcsms_user_mobile_number', true);
			}
			return $value;
		}

	}

	add_action('manage_users_custom_column', 'sgcsms_show_mobile_number_column_content', 10, 3);

	