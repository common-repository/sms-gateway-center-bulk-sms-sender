<?php
	if (!defined('ABSPATH'))
		exit;  // if direct access
	/**
	 * SMS Gateway Center Bulk SMS Sender - A Wordpress SMS plugin
	 */
	if (!defined('ABSPATH'))
		die('No direct access allowed');

	if (!class_exists('WP_List_Table')) {
		require_once( ABSPATH . 'wp-admin/includes/class-wp-list-table.php' );
	}

	/**
	 * Subscribers class
	 */
	class sgcsms_wp_subscribers_list_table extends WP_List_Table {

		var $data;

		/**
		 * Wordpress Dates
		 * @var string
		 */
		public $date;

		/**
		 * Wordpress Database
		 * @var string
		 */
		protected $db;

		/**
		 * Wordpress Table prefix
		 * @var string
		 */
		protected $tb_prefix;
		protected $tb_name;

		/**
		 * Construct
		 * @global type $status
		 * @global type $page
		 * @global type $wpdb
		 */
		function __construct() {
			global $status, $page, $wpdb;

			$this->date = SGC_SMS_CURR_DATE;
			$this->db = $wpdb;
			$this->tb_prefix = $this->db->prefix;
			$this->tb_name = "{$this->tb_prefix}smsgatewaycenter_wp_subscribers";

			//Set parent defaults
			parent::__construct(array(
				'singular' => 'ID', //singular name of the listed records
				'plural' => 'ID', //plural name of the listed records
				'ajax' => false    //does this table support ajax?
			));

			$this->data = $this->db->get_results("SELECT * FROM `{$this->tb_name}`", ARRAY_A);
		}

		/**
		 * Default column
		 * @param type $item
		 * @param type $column_name
		 * @return type
		 */
		function column_default($item, $column_name) {
			switch ($column_name) {
				case 'name':
				case 'mobile':
					return $item[$column_name];
				case 'group_ID':
					$getGroup = $this->db->get_row($this->db->prepare("SELECT `name` FROM `{$this->db->prefix}smsgatewaycenter_wp_subscribers_group` WHERE `ID` = %d", $item[$column_name]));
					return $getGroup->name;
				case 'date':
					return sprintf(__('%s <span class="sgcsms-time">Time: %s</span>', 'sgcsms'), date_i18n('Y-m-d', strtotime($item[$column_name])), date_i18n('H:i:s', strtotime($item[$column_name])));
				default:
					return print_r($item, true); //Show the whole array for troubleshooting purposes
			}
		}

		/**
		 * Column name
		 * @param type $item
		 * @return type
		 */
		function column_name($item) {
			//Build row actions
			$actions = array(
				'edit' => sprintf('<a href="?page=%s&sgcoption=%s&ID=%s">' . __('Edit', 'sgcsms') . '</a>', esc_attr($_REQUEST['page']), 'edit', $item['ID']),
				'delete' => sprintf('<a href="?page=%s&sgcoption=%s&ID=%s">' . __('Delete', 'sgcsms') . '</a>', esc_attr($_REQUEST['page']), 'delete', $item['ID']),
			);
			//Return the title contents
			return sprintf('%1$s %3$s',
				/* $1%s */ $item['name'],
				/* $2%s */ $item['ID'],
				/* $2%s */ $this->row_actions($actions)
			);
		}

		/**
		 * Column callback
		 * @param type $item
		 * @return type
		 */
		function column_cb($item) {
			return sprintf(
				'<input type="checkbox" name="%1$s[]" value="%2$s" />',
				/* $1%s */ $this->_args['singular'], //Let's simply repurpose the table's singular label ("movie")
				/* $2%s */ $item['ID']  //The value of the checkbox should be the record's id
			);
		}

		/**
		 * Get columns
		 * @return type
		 */
		function get_columns() {
			$columns = array(
				'cb' => '<input type="checkbox" />', //Render a checkbox instead of text
				'name' => __('Name', 'sgcsms'),
				'mobile' => __('Mobile', 'sgcsms'),
				'group_ID' => __('Group', 'sgcsms'),
				'date' => __('Date', 'sgcsms'),
			);

			return $columns;
		}

		/**
		 * Get sortable columns
		 * @return array
		 */
		function get_sortable_columns() {
			$sortable_columns = array(
				'ID' => array('ID', true), //true means it's already sorted
				'name' => array('name', false), //true means it's already sorted
				'mobile' => array('mobile', false), //true means it's already sorted
				'group_ID' => array('group_ID', false), //true means it's already sorted
				'date' => array('date', false),
			);

			return $sortable_columns;
		}

		/**
		 * Get bulk actions
		 * @return type
		 */
		function get_bulk_actions() {
			$actions = array(
				'bulk_delete' => __('Delete', 'sgcsms')
			);

			return $actions;
		}

		/**
		 * Process bulk action
		 */
		function process_bulk_action() {
			// Search action
			if (isset($_GET['s'])) {
				$this->data = $this->db->get_results($this->db->prepare("SELECT * from `{$this->tb_name}` WHERE `name` LIKE %s OR `mobile` LIKE %s;", '%' . $this->db->esc_like($_GET['s']) . '%', '%' . $this->db->esc_like($_GET['s']) . '%'), ARRAY_A);
			}
			// Bulk delete action
			if ('bulk_delete' == $this->current_action()) {
				foreach ($_GET['id'] as $id) {
					$this->db->delete($this->tb_name, array('ID' => $id));
				}

				$this->data = $this->db->get_results("SELECT * FROM `{$this->tb_name}`", ARRAY_A);
				echo '<div class="updated notice is-dismissible below-h2"><p>' . __('Items removed.', 'sgcsms') . '</p></div>';
			}
			// Single delete action
			if ('delete' == $this->current_action()) {
				$this->db->delete($this->tb_name, array('ID' => $_GET['ID']));
				$this->data = $this->db->get_results("SELECT * FROM `{$this->tb_name}`", ARRAY_A);
				echo '<div class="updated notice is-dismissible below-h2"><p>' . __('Item removed.', 'sgcsms') . '</p></div>';
			}
		}

		/**
		 * Prepare table items
		 */
		function prepare_items() {
			$per_page = 10;
			$columns = $this->get_columns();
			$hidden = array();
			$sortable = $this->get_sortable_columns();
			$this->_column_headers = array($columns, $hidden, $sortable);
			$this->process_bulk_action();
			$data = $this->data;

			function usort_reorder($a, $b) {
				$orderby = (!empty($_REQUEST['orderby']) ) ? $_REQUEST['orderby'] : 'date';
				$order = (!empty($_REQUEST['order']) ) ? $_REQUEST['order'] : 'desc';
				$result = strcmp($a[$orderby], $b[$orderby]);
				return ( $order === 'asc' ) ? $result : - $result;
			}

			usort($data, 'usort_reorder');
			$current_page = $this->get_pagenum();
			$total_items = count($data);
			$data = array_slice($data, ( ( $current_page - 1 ) * $per_page), $per_page);
			$this->items = $data;
			$this->set_pagination_args(array(
				'total_items' => $total_items,
				'per_page' => $per_page,
				'total_pages' => ceil($total_items / $per_page)
			));
		}

		/**
		 * Add New Subscriber
		 * @param $name
		 * @param $mobile
		 * @param string $group_id
		 * @param string $status
		 * @param $key
		 * @return array
		 * @internal param param $Not
		 */
		public function add_subscriber($name, $mobile, $group_id, $status = '1', $key = null) {
			if ($this->is_duplicate($mobile, $group_id)) {
				return array('result' => 'error',
					'message' => __('The mobile number already exists.', 'sgcsms')
				);
			}

			$result = $this->db->insert(
				$this->tb_name, array(
				'date' => $this->date,
				'name' => $name,
				'mobile' => $mobile,
				'group_ID' => $group_id,
				)
			);

			if ($result) {
				/**
				 * Run hook after adding subscribe.
				 *
				 * @since 1.3.0
				 *
				 * @param string $name name.
				 * @param string $mobile mobile.
				 */
				do_action('sgcsms_subscriber_add_subscriber', $name, $mobile);

				return array('result' => 'update', 'message' => __('Subscriber successfully added.', 'sgcsms'));
			}
		}

		/**
		 * Check the mobile number is duplicate
		 * @param $mobile_number
		 * @param null $group_id
		 * @param null $id
		 * @return array|null|object|void
		 */
		private function is_duplicate($mobile_number, $group_id = null, $id = null) {
			$sql = "SELECT * FROM `{$this->tb_name}` WHERE `mobile` = " . esc_attr($mobile_number);
			if ($group_id) {
				$sql .= " AND `group_ID` = " . esc_attr($group_id);
			}

			if ($id) {
				$sql .= " AND `ID` != " . esc_attr($id);
			}
			$result = $this->db->get_row($sql);
			return $result;
		}

		/**
		 * Delete Subscriber
		 * @param int $id primary key
		 * @return array
		 */
		public function delete_subscriber($id) {
			$result = $this->db->delete(
				$this->tb_name, array(
				'ID' => $id,
				)
			);
			if ($result) {
				/**
				 * Run hook after deleting subscribe.
				 *
				 * @since 1.3.0
				 *
				 * @param string $result result query.
				 */
				do_action('sgcsms_subscriber_delete_subscriber', $result);
				return array('result' => 'update', 'message' => __('Subscriber successfully deleted.', 'sgcsms'));
			}
		}

		/**
		 * Get Subscriber
		 * @param  Not param
		 * @return array|null|object|void
		 */
		public function get_subscriber($id) {
			$result = $this->db->get_row("SELECT * FROM `{$this->tb_name}` WHERE `ID` = '" . $id . "'");
			if ($result) {
				return $result;
			}
		}

		/**
		 * Update Subscriber
		 * @param $id
		 * @param $name
		 * @param $mobile
		 * @param string $group_id
		 * @param string $status
		 * @return array|void
		 * @internal param param $Not
		 */
		public function update_subscriber($id, $name, $mobile, $group_id = '', $status = '1') {
			if (empty($id) or empty($name) or empty($mobile)) {
				return;
			}
			if ($this->is_duplicate($mobile, $group_id, $id)) {
				return array('result' => 'error',
					'message' => __('The mobile numbers already exists.', 'sgcsms')
				);
			}
			$result = $this->db->update(
				$this->tb_name, array(
				'name' => $name,
				'mobile' => $mobile,
				'group_ID' => $group_id,
				), array(
				'ID' => $id
				)
			);

			if ($result) {
				/**
				 * Run hook after updating subscribe.
				 *
				 * @since 1.3.0
				 *
				 * @param string $result result query.
				 */
				do_action('sgcsms_subscriber_update_subscriber', $result);
				return array('result' => 'update', 'message' => __('Subscriber successfully updated.', 'sgcsms'));
			}
		}

		/**
		 * Get Subscribers
		 * @param  Not param
		 * @return array|null|object|void
		 */
		public function get_subscribers_in($id) {
			$result = $this->db->get_results("SELECT `mobile` FROM `{$this->tb_name}` WHERE `group_ID` IN (" . $id . ")", ARRAY_A);
			if ($result) {
				return $result;
			}
		}

		/**
		 * Manage Subscriber Button
		 * @param type $url
		 */
		public function sgcsms_get_manage_subscriber_btn($url) {
			?>
			<a href="<?php echo esc_url($url); ?>" class="button">
				<span class="dashicons dashicons-groups"></span>
			<?php _e('Manage Subscribers', 'sgcsms'); ?>
			</a>
			<?php
		}

		/**
		 * Add Subscriber Button
		 * @param type $url
		 */
		public function sgcsms_get_add_subscriber_btn($url) {
			?>
			<a href="<?php echo esc_url($url); ?>&sgcoption=add" class="button">
				<span class="dashicons dashicons-admin-users"></span>
			<?php _e('Add Subscriber', 'sgcsms'); ?>
			</a>
			<?php
		}

		/**
		 * Import Subscriber Button
		 * @param string $url
		 */
		public function sgcsms_get_import_subscriber_btn($url) {
			?>
			<a href="<?php echo esc_url($url); ?>&sgcoption=import" class="button">
				<span class="dashicons dashicons-yes"></span>
			<?php _e('Import Subscribers', 'sgcsms'); ?>
			</a>
			<?php
		}
	}
	