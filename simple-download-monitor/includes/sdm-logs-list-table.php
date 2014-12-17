<?php

//*****
//*****  Check WP_List_Table exists
if (!class_exists('WP_List_Table')) {
    require_once( ABSPATH . 'wp-admin/includes/class-wp-list-table.php' );
}

//*****
//*****  Define our new Table
class sdm_List_Table extends WP_List_Table {

    function __construct() {

        global $status, $page;

        //Set parent defaults
        parent::__construct(array(
            'singular' => __('Download', 'sdm_lang'), //singular name of the listed records
            'plural' => __('Downloads', 'sdm_lang'), //plural name of the listed records
            'ajax' => false        //does this table support ajax?
        ));
    }

    function column_default($item, $column_name) {

        switch ($column_name) {
            case 'URL':
            case 'visitor_ip':
            case 'date':
                return $item[$column_name];
            case 'visitor_country':
                return $item[$column_name];
            case 'visitor_name':
                return $item[$column_name];
            default:
                return print_r($item, true); //Show the whole array for troubleshooting purposes
        }
    }

    function column_title($item) {

        //Build row actions
        $actions = array(
            'edit' => sprintf('<a href="' . admin_url('post.php?post=' . $item['ID'] . '&action=edit') . '">' . __('Edit', 'sdm_lang') . '</a>'),
            'delete' => sprintf('<a href="?post_type=sdm_downloads&page=%s&action=%s&download=%s&datetime=%s">' . __('Delete', 'sdm_lang') . '</a>', $_REQUEST['page'], 'delete', $item['ID'], $item['date'])
        );

        //Return the title contents
        return sprintf('%1$s <span style="color:silver">(id:%2$s)</span>%3$s',
                /* $1%s */ $item['title'],
                /* $2%s */ $item['ID'],
                /* $3%s */ $this->row_actions($actions)
        );
    }

    function column_cb($item) {

        return sprintf(
                '<input type="checkbox" name="%1$s[]" value="%2$s" />',
                /* $1%s */ $this->_args['singular'], //Let's simply repurpose the table's singular label ("Download")
                /* $2%s */ $item['ID'] . '|' . $item['date']            //The value of the checkbox should be the record's id
        );
    }

    function get_columns() {

        $columns = array(
            'cb' => '<input type="checkbox" />', //Render a checkbox instead of text
            'title' => __('Title', 'sdm_lang'),
            'URL' => __('File', 'sdm_lang'),
            'visitor_ip' => __('Visitor IP', 'sdm_lang'),
            'date' => __('Date', 'sdm_lang'),
            'visitor_country' => __('Country', 'sdm_lang'),
            'visitor_name' => __('Username', 'sdm_lang')
        );
        return $columns;
    }

    function get_sortable_columns() {

        $sortable_columns = array(
            'title' => array('title', false), //true means it's already sorted
            'URL' => array('URL', false),
            'visitor_ip' => array('visitor_ip', false),
            'date' => array('date', false),
            'visitor_country' => array('visitor_country', false),
            'visitor_name' => array('visitor_name', false)
        );
        return $sortable_columns;
    }

    function get_bulk_actions() {

        $actions = array();
        $actions['delete2'] = __('Delete Permanently', 'sdm_lang');
        $actions['export_all'] = __('Export All as Excel', 'sdm_lang');
        //$actions['export-selected'] = __( 'Export Selected', 'sdm_lang' );

        return $actions;
    }

    function process_bulk_action() {

        // security check!
        if (isset($_POST['_wpnonce']) && !empty($_POST['_wpnonce'])) {

            $nonce = filter_input(INPUT_POST, '_wpnonce', FILTER_SANITIZE_STRING);
            $action = 'bulk-' . $this->_args['plural'];

            if (!wp_verify_nonce($nonce, $action))
                wp_die(__('Nope! Security check failed!', 'sdm_lang'));
        }

        $action = $this->current_action();

        // If bulk 'Export All' was clicked
        if ('export_all' === $this->current_action()) {

            echo '<div id="message" class="updated"><p><strong><a id="sdm_download_export" href="?post_type=sdm_downloads&page=logs&download_log">' . __('Download Export File', 'sdm_lang') . '</a></strong></p></div>';
        }

        // if bulk 'Delete Permanently' was clicked
        if ('delete2' === $this->current_action()) {

            if (!isset($_POST['download']) || $_POST['download'] == null) {
                echo '<div id="message" class="updated fade"><p><strong>' . __('No entries were selected.', 'sdm_lang') . '</strong></p><p><em>' . __('Click to Dismiss', 'sdm_lang') . '</em></p></div>';
                return;
            }

            foreach ($_POST['download'] as $item) {
                $str_tok_id = substr($item, 0, strpos($item, '|'));
                $str_tok_datetime = substr($item, strpos($item, '|') + 1);

                global $wpdb;
                $del_row = $wpdb->query(
                        'DELETE FROM ' . $wpdb->prefix . 'sdm_downloads
									WHERE post_id = "' . $str_tok_id . '"
									AND date_time = "' . $str_tok_datetime . '"'
                );
            }
            if ($del_row) {
                echo '<div id="message" class="updated fade"><p><strong>' . __('Entries Deleted!', 'sdm_lang') . '</strong></p><p><em>' . __('Click to Dismiss', 'sdm_lang') . '</em></p></div>';
            } else {
                echo '<div id="message" class="updated fade"><p><strong>' . __('Error', 'sdm_lang') . '</strong></p><p><em>' . __('Click to Dismiss', 'sdm_lang') . '</em></p></div>';
            }
        }

        // If single entry 'Delete' was clicked
        if ('delete' === $this->current_action()) {

            $item_id = isset($_GET['download']) ? strtok($_GET['download'], '|') : '';
            $item_datetime = isset($_GET['datetime']) ? $_GET['datetime'] : '';

            global $wpdb;
            $del_row = $wpdb->query(
                    'DELETE FROM ' . $wpdb->prefix . 'sdm_downloads
								WHERE post_id = "' . $item_id . '"
								AND date_time = "' . $item_datetime . '"'
            );
            if ($del_row) {
                echo '<div id="message" class="updated fade"><p><strong>' . __('Entry Deleted!', 'sdm_lang') . '</strong></p><p><em>' . __('Click to Dismiss', 'sdm_lang') . '</em></p></div>';
            } else {
                echo '<div id="message" class="updated fade"><p><strong>' . __('Error', 'sdm_lang') . '</strong></p><p><em>' . __('Click to Dismiss', 'sdm_lang') . '</em></p></div>';
            }
        }
    }

    function prepare_items() {

        global $wpdb; //This is used only if making any database queries
        $per_page = 30;
        $columns = $this->get_columns();
        $hidden = array();
        $sortable = $this->get_sortable_columns();
        $current_page = $this->get_pagenum();

        $this->_column_headers = array($columns, $hidden, $sortable);
        $this->process_bulk_action();

        function usort_reorder($a, $b) {
            $orderby = (!empty($_REQUEST['orderby'])) ? $_REQUEST['orderby'] : 'title'; //If no sort, default to title
            $order = (!empty($_REQUEST['order'])) ? $_REQUEST['order'] : 'asc'; //If no order, default to asc
            $result = strcmp($a[$orderby], $b[$orderby]); //Determine sort order
            return ($order === 'asc') ? $result : -$result; //Send final sort direction to usort
        }

        //Do a query to find the total number of rows then calculate the query limit
        $table_name = $wpdb->prefix . 'sdm_downloads';
        $query = "SELECT COUNT(*) FROM $table_name";
        $total_items = $wpdb->get_var($query);
        $offset = ($current_page - 1) * $per_page;
        $query_limit =' LIMIT ' . (int) $offset . ',' . (int) $per_page;
        
        $data_results = $wpdb->get_results('SELECT * FROM ' . $wpdb->prefix . 'sdm_downloads'. $query_limit);
        $data = array();
        foreach ($data_results as $data_result) {
            $data[] = array('ID' => $data_result->post_id, 'title' => $data_result->post_title, 'URL' => $data_result->file_url, 'visitor_ip' => $data_result->visitor_ip, 'date' => $data_result->date_time, 'visitor_country' => $data_result->visitor_country, 'visitor_name' => $data_result->visitor_name);
        }


        usort($data, 'usort_reorder'); 
        
        //The following is not needed as it comes from the extra query and the query limit stuff
        //$total_items = count($data);
        //$data = array_slice($data, (($current_page - 1) * $per_page), $per_page);

        $this->items = $data;
        $this->set_pagination_args(array(
            'total_items' => $total_items, //WE have to calculate the total number of items
            'per_page' => $per_page, //WE have to determine how many items to show on a page
            'total_pages' => ceil($total_items / $per_page)   //WE have to calculate the total number of pages
        ));
    }

}
