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
	 * Subscriber group class
	 */
	class sgcsms_wp_subscribers_groups_list_table extends WP_List_Table {

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
			$this->tb_name = "{$this->tb_prefix}smsgatewaycenter_wp_subscribers_group";

			//Set parent defaults
			parent::__construct(array(
				'singular' => 'ID', //singular name of the listed records
				'plural' => 'ID', //plural name of the listed records
				'ajax' => false    //does this table support ajax?
			));
			$this->data = $this->db->get_results("SELECT * FROM `{$this->tb_name}`", ARRAY_A);
		}

		/**
		 * Get subscriber count
		 * @param type $id
		 * @return type
		 */
		function smsgatewaycenter_get_subscribers_count($id) {
			$totalSubCount = $this->db->get_row($this->db->prepare("SELECT COUNT(`ID`) AS `CID` FROM `{$this->tb_prefix}smsgatewaycenter_wp_subscribers` WHERE `group_ID` = %d", $id), ARRAY_A);
			return $totalSubCount['CID'];
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
					return $item[$column_name];
				case 'total_subscribers':
					return $this->smsgatewaycenter_get_subscribers_count($item['ID']);
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
				/* $1%s */ $item['ID'],
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
				'total_subscribers' => __('Total subscribers', 'sgcsms'),
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
				'total_subscribers' => array('group_ID', false), //true means it's already sorted
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
				$this->data = $this->db->get_results($this->db->prepare("SELECT * FROM `{$this->tb_name}` WHERE `name` LIKE %s", '%' . $this->db->esc_like($_GET['s']) . '%'), ARRAY_A);
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
				$orderby = (!empty($_REQUEST['orderby']) ) ? $_REQUEST['orderby'] : 'ID';
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
		 * Add group
		 * @param type $name
		 * @return type
		 */
		public function add_group($name) {
			if (empty($name)) {
				return array('result' => 'error', 'message' => __('Name is empty!', 'sgcsms'));
			}
			if ($this->is_duplicate_group($name)) {
				return array('result' => 'error',
					'message' => __('The group already exists.', 'sgcsms')
				);
			}
			$result = $this->db->insert(
				$this->tb_name, array(
				'name' => $name,
				)
			);
			if ($result) {
				/**
				 * Run hook after adding group.
				 *
				 * @since 1.3.0
				 *
				 * @param string $result result query.
				 */
				do_action('sgcsms_subscriber_add_group', $result);
				return array('result' => 'update', 'message' => __('Group successfully added.', 'sgcsms'));
			}
		}

		/**
		 * Check the group is duplicate
		 * @param $name
		 * @return array|null|object|void
		 */
		private function is_duplicate_group($name) {
			$sql = $this->db->prepare("SELECT * FROM `{$this->tb_name}` WHERE `name` = %s", $name);
			$result = $this->db->get_row($sql);
			return $result;
		}

		/**
		 * Get Groups
		 * @param  Not param
		 * @return array|null|object
		 */
		public function get_groups() {
			$result = $this->db->get_results("SELECT * FROM `{$this->tb_name}`");
			if ($result) {
				return $result;
			}
		}

		/**
		 * Delete Group
		 * @param  Not param
		 * @return false|int|void
		 */
		public function delete_group($id) {
			if (empty($id)) {
				return;
			}
			$result = $this->db->delete(
				$this->tb_name, array(
				'ID' => $id,
				)
			);
			if ($result) {
				/**
				 * Run hook after deleting group.
				 *
				 * @since 1.3.0
				 *
				 * @param string $result result query.
				 */
				do_action('sgcsms_subscriber_delete_group', $result);
				return ['result' => 'update', 'message' => __('Group successfully deleted.', 'sgcsms')];
			}
		}

		/**
		 * Get Group
		 * @param  Not param
		 * @return array|null|object|void
		 */
		public function get_group($group_id) {
			$result = $this->db->get_row($this->db->prepare("SELECT * FROM `{$this->tb_name}` WHERE `ID` = %d", $group_id));
			if ($result) {
				return $result;
			}
		}

		/**
		 * Update Group
		 * @param $id
		 * @param $name
		 * @return array|void
		 * @internal param param $Not
		 */
		public function update_group($id, $name) {
			if (empty($id) or empty($name)) {
				return;
			}
			$result = $this->db->update(
				$this->tb_name, array(
				'name' => $name,
				), array(
				'ID' => $id
				)
			);
			if ($result) {
				/**
				 * Run hook after updating group.
				 *
				 * @since 1.3.0
				 *
				 * @param string $result result query.
				 */
				do_action('sgcsms_subscriber_update_group', $result);
				return array('result' => 'update', 'message' => __('Group successfully updated.', 'sgcsms'));
			}
		}

		/**
		 * Manage group button
		 * @param string $url
		 */
		public function sgcsms_get_manage_group_btn($url) {
			?>
			<a href="<?php echo esc_url($url); ?>" class="button">
				<span class="dashicons dashicons-groups"></span>
				<?php _e('Manage Group', 'sgcsms'); ?>
			</a>
			<?php
		}

		/**
		 * Add group button
		 * @param string $url
		 */
		public function sgcsms_get_add_group_btn($url) {
			?>
			<a href="<?php echo esc_url($url); ?>&sgcoption=add" class="button">
				<span class="dashicons dashicons-groups"></span>
				<?php _e('Add Group', 'sgcsms'); ?>
			</a>
			<?php
		}
	}
	