<?php
if ( !class_exists( 'WP_List_Table' ) ){
    require_once( ABSPATH . 'wp-admin/includes/class-wp-list-table.php' );
}

class Agora_Table extends WP_List_Table {
    var $votes_data;

    function __construct(){
        global $status, $wpdb, $page;

        $this->votes_data = $wpdb->get_results("SELECT ID, post_title, post_content, post_date_gmt FROM $wpdb->posts WHERE post_type='vote' AND post_status='publish'", ARRAY_A);

        parent::__construct( array(
            'singular'  => 'vote',
            'plural'    => 'votes',
            'ajax'      => false
        ) );
    }

    function column_default($item, $column_name){
        switch($column_name){
            case "post_date_gmt":
                return $item[$column_name];
            default:
                return print_r($item,true);
        }
    }

    function column_title($item){
        return sprintf('<strong><a href="%1$s" class="row-title vote-title">%2$s</a></strong><p>%3$s</p>',
            "post=" . $item['ID'],
            $item['post_title'],
            wp_trim_words( $item['post_content'] )
        );
    }

    function column_cb($item){
        return sprintf( '<input type="checkbox" name="%1$s[]" value="%2$s" />', $this->_args['singular'], $item['ID'] );
    }

    function get_columns(){
        $columns = array(
            'cb'           => '<input type="checkbox" />',
            'title'        => 'Propuesta',
            'post_date_gmt' => 'Fecha',
        );
        return $columns;
    }

    function get_sortable_columns() {
        $sortable_columns = array(
            'title'     => array( 'post_title',false ),
            'post_date_gmt'    => array( 'post_date_gmt', false ),
        );
        return $sortable_columns;
    }

    function prepare_items() {
        $per_page = 5;
        $columns = $this->get_columns();
        $hidden = array();
        $sortable = $this->get_sortable_columns();
        $this->_column_headers = array( $columns, $hidden, $sortable );
        $data = $this->votes_data;

        function usort_reorder($a,$b){
            $orderby = ( !empty( $_REQUEST['orderby'] ) ) ? $_REQUEST['orderby'] : 'post_date_gmt';
            $order = ( !empty($_REQUEST['order'] ) ) ? $_REQUEST['order'] : 'desc';
            $result = strcmp( $a[$orderby], $b[$orderby] );
            return ($order==='asc') ? $result : -$result;
        }
        usort($data, 'usort_reorder');

        $current_page = $this->get_pagenum();
        $total_items = count($data);
        $data = array_slice( $data,( ( $current_page-1)*$per_page ),$per_page);
        $this->items = $data;
        $this->set_pagination_args( array(
            'total_items' => $total_items,
            'per_page'    => $per_page,
            'total_pages' => ceil( $total_items/$per_page )
        ) );
    }
}

function render_page() {

    $testListTable = new Agora_Table();
    $testListTable->prepare_items(); ?>

    <div id="vote-detail"></div>

    <div class="wrap">
        <h2>Votaciones</h2>

        <form id="votes-filter" method="get">
            <input type="hidden" name="page" value="<?php echo $_REQUEST['page'] ?>" />
            <?php $testListTable->display(); ?>
        </form>
    </div><?php
}
