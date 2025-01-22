<?php

//*****
//*****  Check WP_List_Table exists
if ( ! class_exists( 'WP_List_Table' ) ) {
	require_once ABSPATH . 'wp-admin/includes/class-wp-list-table.php';
}

//*****
//*****  Define our new Table
class sdm_List_Table extends WP_List_Table {

	function __construct() {

		global $status, $page;

		//Set parent defaults
		parent::__construct(
			array(
				'singular' => __( 'Download', 'simple-download-monitor' ), //singular name of the listed records
				'plural'   => __( 'Downloads', 'simple-download-monitor' ), //plural name of the listed records
				'ajax'     => false,        //does this table support ajax?
			)
		);
	}

	function column_default( $item, $column_name ) {

		switch ( $column_name ) {
			case 'URL':
			case 'visitor_ip':
			case 'date':
				return $item[ $column_name ];
			case 'visitor_country':
				return $item[ $column_name ];
			case 'visitor_name':
				return $item[ $column_name ];
			case 'user_agent':
				return $item[ $column_name ];
			case 'referrer_url':
				return $item[ $column_name ];
			default:
				return print_r( $item, true ); //Show the whole array for troubleshooting purposes
		}
	}

	function column_title( $item ) {
		$delete_log_nonce = wp_create_nonce( 'sdm_delete_log_entry' );
		//Build row actions
		$actions = array(
			'edit'   => sprintf( '<a href="' . admin_url( 'post.php?post=' . $item['ID'] . '&action=edit' ) . '">' . __( 'Edit', 'simple-download-monitor' ) . '</a>' ),
			'delete' => sprintf( '<a href="?post_type=sdm_downloads&page=sdm-logs&action=%s&download=%s&row_id=%s&_wpnonce=%s" onclick="return confirm(\'Are you sure you want to delete this entry?\')">' . __( 'Delete', 'simple-download-monitor' ) . '</a>', 'delete', $item['ID'], $item['row_id'], $delete_log_nonce ),
		);

		//Return the title contents
		return sprintf(
			'%1$s <span style="color:silver">(id:%2$s)</span>%3$s',
			/* $1%s */ esc_html($item['title']),
			/* $2%s */ $item['ID'],
			/* $3%s */ $this->row_actions( $actions )
		);
	}

	function column_cb( $item ) {

		return sprintf(
			'<input type="checkbox" name="%1$s[]" value="%2$s" />',
			/* $1%s */ $this->_args['singular'], //Let's simply repurpose the table's singular label ("Download")
			/* $2%s */ $item['row_id'] //The value of the checkbox should be the record's id
		);
	}

	function get_columns() {

		$columns = array(
			'cb'              => '<input type="checkbox" />', //Render a checkbox instead of text
			'title'           => __( 'Title', 'simple-download-monitor' ),
			'URL'             => __( 'File', 'simple-download-monitor' ),
			'visitor_ip'      => __( 'Visitor IP', 'simple-download-monitor' ),
			'date'            => __( 'Date', 'simple-download-monitor' ),
			'visitor_country' => __( 'Country', 'simple-download-monitor' ),
			'visitor_name'    => __( 'Username', 'simple-download-monitor' ),
			'user_agent'      => __( 'User Agent', 'simple-download-monitor' ),
			'referrer_url'    => __( 'Referrer URL', 'simple-download-monitor' ),
		);
		return $columns;
	}

	function get_sortable_columns() {

		$sortable_columns = array(
			'title'           => array( 'post_title', false ), //true means it's already sorted
			'URL'             => array( 'file_url', false ),
			'visitor_ip'      => array( 'visitor_ip', false ),
			'date'            => array( 'date_time', false ),
			'visitor_country' => array( 'visitor_country', false ),
			'visitor_name'    => array( 'visitor_name', false ),
			'user_agent'      => array( 'user_agent', false ),
		);
		return $sortable_columns;
	}

	function get_bulk_actions() {

		$actions            = array();
		$actions['delete2'] = __( 'Delete Permanently', 'simple-download-monitor' );

		return $actions;
	}

	function process_bulk_action() {

		// if bulk 'Delete Permanently' was clicked
		if ( 'delete2' === $this->current_action() ) {

			//Check bulk delete nonce
            $nonce = isset( $_POST['_wpnonce'] ) ? sanitize_text_field( stripslashes ( $_POST['_wpnonce'] ) ) : '';
			$action = 'bulk-' . $this->_args['plural'];

			if ( ! wp_verify_nonce( $nonce, $action ) ) {
				wp_die( __( 'Nope! Security check failed!', 'simple-download-monitor' ) );
			}

			if ( ! isset( $_POST['download'] ) || $_POST['download'] == null ) {
				echo '<div id="message" class="updated fade"><p><strong>' . __( 'No entries were selected.', 'simple-download-monitor' ) . '</strong></p><p><em>' . __( 'Click to Dismiss', 'simple-download-monitor' ) . '</em></p></div>';
				return;
			}

			foreach ( $_POST['download'] as $item ) {
				$row_id = sanitize_text_field( $item );
				if ( ! is_numeric( $row_id ) ) {
					wp_die( __( 'Error! The row id value of a log entry must be numeric.', 'simple-download-monitor' ) );
				}

				$row_id = intval( $row_id );
				if ( ! is_numeric( $row_id ) ) {
					wp_die( __( 'Error! The row id value of a log entry must be numeric.', 'simple-download-monitor' ) );
				}

				global $wpdb;
				$del_row = $wpdb->query( $wpdb->prepare( 'DELETE FROM ' . $wpdb->prefix . 'sdm_downloads WHERE id = %d', $row_id ));
			}
			if ( $del_row ) {
				echo '<div id="message" class="updated fade"><p><strong>' . __( 'Entries Deleted!', 'simple-download-monitor' ) . '</strong></p><p><em>' . __( 'Click to Dismiss', 'simple-download-monitor' ) . '</em></p></div>';
			} else {
				echo '<div id="message" class="updated fade"><p><strong>' . __( 'Error', 'simple-download-monitor' ) . '</strong></p><p><em>' . __( 'Click to Dismiss', 'simple-download-monitor' ) . '</em></p></div>';
			}
		}

		// If single entry 'Delete' was clicked
		if ( 'delete' === $this->current_action() ) {

			//Check single entry delete nonce
            $nonce = isset( $_GET['_wpnonce'] ) ? sanitize_text_field( stripslashes ( $_GET['_wpnonce'] ) ) : '';
			$action = 'sdm_delete_log_entry';
			if ( ! wp_verify_nonce( $nonce, $action ) ) {
				wp_die( __( 'Nope! Security check failed!', 'simple-download-monitor' ) );
			}

			//Grab the row id
			$row_id = isset( $_GET['row_id'] ) ? sanitize_text_field( stripslashes ( $_GET['row_id'] ) ) : '';
			$row_id = intval( $row_id );	
			if ( ! is_numeric( $row_id ) ) {
				wp_die( __( 'Error! The row id value of a log entry must be numeric.', 'simple-download-monitor' ) );
			}

			global $wpdb;
			$del_row = $wpdb->query( $wpdb->prepare('DELETE FROM ' . $wpdb->prefix . 'sdm_downloads WHERE id = %d', $row_id) );
			if ( $del_row ) {
				echo '<div id="message" class="updated fade"><p><strong>' . __( 'Entry Deleted!', 'simple-download-monitor' ) . '</strong></p><p><em>' . __( 'Click to Dismiss', 'simple-download-monitor' ) . '</em></p></div>';
			} else {
				echo '<div id="message" class="updated fade"><p><strong>' . __( 'Error', 'simple-download-monitor' ) . '</strong></p><p><em>' . __( 'Click to Dismiss', 'simple-download-monitor' ) . '</em></p></div>';
			}
		}
	}

	function prepare_items() {

		global $wpdb; //This is used only if making any database queries
		$per_page     = apply_filters( 'sdm_download_logs_menu_items_per_page', 50 );
		$columns      = $this->get_columns();
		$hidden       = array();
		$sortable     = $this->get_sortable_columns();
		$current_page = $this->get_pagenum();

		$this->_column_headers = array( $columns, $hidden, $sortable );
		$this->process_bulk_action();

		// Grab the sort inputs then sanitize the values before using it in the query. Use a whitelist approach to sanitize it.
		$orderby_column = isset( $_GET['orderby'] ) ? sanitize_text_field( $_GET['orderby'] ) : '';
		$sort_order     = isset( $_GET['order'] ) ? sanitize_text_field( $_GET['order'] ) : '';
		if ( empty( $orderby_column ) ) {
			$orderby_column = 'date_time';
			$sort_order     = 'DESC';
		}
		$orderby_column = sdm_sanitize_value_by_array(
			$orderby_column,
			array(
				'post_title'      => '1',
				'file_url'        => '1',
				'visitor_ip'      => '1',
				'date_time'       => '1',
				'visitor_country' => '1',
				'visitor_name'    => '1',
				'user_agent'      => '1',
				'referrer_url'    => '1',
			)
		);
		$sort_order     = sdm_sanitize_value_by_array(
			$sort_order,
			array(
				'DESC' => '1',
				'ASC'  => '1',
			)
		);

		//Do a query to find the total number of rows then calculate the query limit
		$table_name = $wpdb->prefix . 'sdm_downloads';

		if ( isset( $_REQUEST['sdm_logs_dl_id'] ) && ! empty( $_REQUEST['sdm_logs_dl_id'] ) ) {
			//For specific download logs
			$dl_id = sanitize_text_field( $_REQUEST['sdm_logs_dl_id'] );
			$dl_id = intval( $dl_id );

			if ( ! is_numeric( $dl_id ) ) {
				wp_die( __( 'Error! The download id value of a log entry must be numeric.', 'simple-download-monitor' ) );
			}

			$query       = $wpdb->prepare("SELECT COUNT(*) FROM $table_name WHERE post_id = %d", $dl_id);
			$total_items = $wpdb->get_var( $query );//For pagination requirement
			$query       = $wpdb->prepare("SELECT * FROM $table_name WHERE post_id = %d ORDER BY $orderby_column $sort_order", $dl_id);
		} else if ( isset( $_REQUEST['s'] ) && !empty( $_REQUEST['s'] ) ) {
			$search_text = sanitize_text_field($_REQUEST['s']);

			$columns = ['post_title', 'post_id', 'visitor_name', 'visitor_ip' , 'visitor_country'];
			$like_query = [];
			foreach ($columns as $column) {
				$like_query[] = "$column LIKE '%" . esc_sql($search_text) . "%'";
			}
			
			$where_clause = implode(' OR ', $like_query);

			$query       = "SELECT COUNT(*) FROM $table_name WHERE $where_clause";
			$total_items = $wpdb->get_var( $query );//For pagination requirement
			$query       = "SELECT * FROM $table_name WHERE $where_clause ORDER BY $orderby_column $sort_order";

		} else {
			//For all download logs
			$query       = "SELECT COUNT(*) FROM $table_name";
			$total_items = $wpdb->get_var( $query );//For pagination requirement

			$query = "SELECT * FROM $table_name ORDER BY $orderby_column $sort_order";
		}

		$offset = ( $current_page - 1 ) * $per_page;
		$query .= ' LIMIT ' . (int) $offset . ',' . (int) $per_page;//Limit to query to only load a limited number of records

		$data_results = $wpdb->get_results( $query );

		//Prepare the array with the correct index names that the table is expecting.
		$data = array();
		foreach ( $data_results as $data_result ) {
			$data[] = array(
				'row_id'          => $data_result->id,
				'ID'              => $data_result->post_id,
				'title'           => $data_result->post_title,
				'URL'             => $data_result->file_url,
				'visitor_ip'      => $data_result->visitor_ip,
				'date'            => $data_result->date_time,
				'visitor_country' => $data_result->visitor_country,
				'visitor_name'    => $data_result->visitor_name,
				'user_agent'      => $data_result->user_agent,
				'referrer_url'    => $data_result->referrer_url,
			);
		}

		// Now we add our *sorted* data to the items property, where it can be used by the rest of the class.
		$this->items = $data;

		$this->set_pagination_args(
			array(
				'total_items' => $total_items, //WE have to calculate the total number of items
				'per_page'    => $per_page, //WE have to determine how many items to show on a page
				'total_pages' => ceil( $total_items / $per_page ),   //WE have to calculate the total number of pages
			)
		);
	}

	protected function extra_tablenav( $which ) {
		if ( $which == 'top' ) {
			
			if ( empty( $_REQUEST['s'] ) && ! $this->has_items() ) {
				return;
			}

			if ( ! empty( $_REQUEST['orderby'] ) ) {
				echo '<input type="hidden" name="orderby" value="' . esc_attr( $_REQUEST['orderby'] ) . '" />';
			}
			if ( ! empty( $_REQUEST['order'] ) ) {
				echo '<input type="hidden" name="order" value="' . esc_attr( $_REQUEST['order'] ) . '" />';
			}

			$search_btn_text = __('Search', 'simple-download-monitor');
			?>

			<!-- Search logs -->
			<div class="alignleft actions searchactions" style="margin-right: 8px;">
				<label class="screen-reader-text" for="sdm-search-input"><?php echo $search_btn_text; ?>:</label>
				<input type="search" id="sdm-search-input" name="s" value="<?php _admin_search_query(); ?>"/>
				<?php submit_button( $search_btn_text, '', '', false, array( 'id' => 'search-submit' ) ); ?>
			</div>
			
			<!-- Export logs -->
			<div class="alignleft actions exportactions">
				<fieldset id="sdm-export-logs-fieldset">
					<button type="button" id="sdm-export-logs-submit" class="button">
						<?php _e('Export Logs', 'simple-download-monitor') ?>
					</button>
					<input type="hidden" id="sdm-export-logs-search" value="<?php _admin_search_query(); ?>"/>
					<input type="hidden" id="sdm-export-logs-order" value="<?php isset($_REQUEST['order']) ? esc_attr_e( $_REQUEST['order'] ) : '' ?>" />
					<input type="hidden" id="sdm-export-logs-orderby" value="<?php isset($_REQUEST['orderby']) ? esc_attr_e( $_REQUEST['orderby'] ) : '' ?>" />
					<input type="hidden" id="sdm-export-logs-nonce" value="<?php echo wp_create_nonce( 'sdm-export-logs-nonce-action' ) ?>" />
				</fieldset>
			</div>
			<?php
		}
	}
}
