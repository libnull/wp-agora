<?php
defined('ABSPATH') or die("No script kiddies please!");

/**
 * Plugin Name: WP Agora
 * Plugin URI: http://github.com/libnull/wp-agora
 * Description: A plugin to vote.
 * Version: 1.0
 * Author: Eduardo Delgaldo
 * Author URI: http://github.com/libnull
 * License: GPL3
 */
include_once( 'wp-agora-toolbar.php' );

function agora_create_tables() {
    global $wpdb;

    $agora_voters = $wpdb->prefix . 'agora_voters';
    $agora_campaigns = $wpdb->prefix . 'agora_campaigns';

    $create_agora_voters_sql = "CREATE TABLE $agora_voters (
      voter_id bigint(20) NOT NULL AUTO_INCREMENT,
      user_id bigint(20) NOT NULL,
      PRIMARY KEY (voter_id),
      UNIQUE KEY user_id (user_id)
    );";

    $create_agora_campaigns_sql = "CREATE TABLE $agora_campaigns (
      camp_id bigint(20) NOT NULL AUTO_INCREMENT,
      vote_id bigint(20) NOT NULL,
      voters text DEFAULT NULL,
      vote_for text DEFAULT NULL,
      vote_against text DEFAULT NULL,
      vote_abstain text DEFAULT NULL,
      PRIMARY KEY (camp_id),
      UNIQUE KEY vote_id (vote_id)
    );";

    $wpdb->query( $create_agora_voters_sql );
    $wpdb->query( $create_agora_campaigns_sql );
}

function agora_drop_tables() {
    global $wpdb;

    $agora_tables = array(
        $wpdb->prefix . "agora_campaigns",
        $wpdb->prefix . "agora_voters"
    );

    foreach ( $agora_tables as $table ) {
        $wpdb->query( "DROP TABLE $table" );
    }
}

register_activation_hook( __FILE__, 'agora_create_tables' );

register_deactivation_hook( __FILE__, 'agora_drop_tables' );

add_action( 'init', 'create_vote' );

add_action( 'load-edit.php', 'agora_force_excerpt' );

function agora_force_excerpt() {
    $_REQUEST['mode'] = 'excerpt';
}

function agora_admin_init() {
    wp_register_style( 'agora_styles', plugins_url('css/agora.css', __FILE__) );
    wp_enqueue_style('agora_styles');
}

add_action( 'admin_init', 'agora_admin_init' );

function create_vote() {
    register_post_type( 'vote',
        array(
            'labels' => array(
                'name' => __( 'Votaciones' ),
                'singular_name' => __( 'Votación' ),
                'add_new_item' => __( 'Añadir nueva votación' ),
                'edit_item' => __( 'Editar votación' ),
                'new_item' => __( 'Nueva votación' ),
            ),
            'public' => true,
            'has_archive' => true,
            'exclude_from_search' => true,
            'publicly_quearyable' => false,
            'menu_icon' => 'dashicons-groups',
            'supports' => array( 'title', 'revisions' )
        )
    );
}

function agora_remove_meta_boxes() {
    remove_meta_box('slugdiv', 'vote', 'core');
}

add_action( 'admin_menu', 'agora_remove_meta_boxes' );

function agora_remove_actions( $actions, $post ) {
    if( $post->post_type == 'vote' ) {
        unset( $actions['inline hide-if-no-js'] );
        unset( $actions['view'] );
        unset( $actions['trash'] );
    }
    return $actions;
}
add_filter( 'post_row_actions', 'agora_remove_actions', 10, 2 );

function agora_custom_bulk_actions( $actions ){
    unset( $actions['edit'] );
    unset( $actions['trash'] );

    return $actions;
}

add_filter('bulk_actions-edit-vote','agora_custom_bulk_actions');

add_action( 'post_submitbox_misc_actions', 'article_or_box' );

function article_or_box() {
    global $post;

    if (get_post_type( $post ) == 'vote') :
        $timezone = current_time( 'timestamp' );
        $vote_deadline  = get_post_meta( $post->ID, 'vote-deadline', true );
        $datetime = $vote_deadline != null ? new DateTime( $vote_deadline ) : date("Y-m-d H:i:s"); ?>

        <div class="misc-pub-section curtime misc-pub-curtime">

            <span id="timestamp">Cierra el</span>
            <div class="timestamp-wrap">
                <input type="text" id="vote-deadline-date-day" name="vote-deadline-day" min="1" max="31" size="2" value="<?php echo $datetime->format( 'd' ); ?>" />.
                <select id="sca-date-month" name="vote-deadline-month">
                <?php for ( $i = 1; $i <= 12; $i++ ) : ?>
                    <option <?php echo ( $datetime->format( 'm' ) == $i ? 'selected="selected"' : '' ); ?> value="<?php echo $i; ?>"><?php echo date_i18n( 'F', strtotime( '01.' . $i . '.2013' ) ); ?></option>
                <?php endfor; ?>
                </select>
                <input type="text" id="vote-deadline-date-year" name="vote-deadline-year" size="4" min="<?php echo date( 'Y' ); ?>" value="<?php echo $datetime->format( 'Y' ); ?>" />
                <input type="text" id="vote-deadline-date-hour" name="vote-deadline-hour" size="2" min="0" max="24" value="<?php echo $datetime->format( 'H' ); ?>" />:
                <input type="text" id="vote-deadline-date-min" name="vote-deadline-min" size="2" min="0" max="60" value="<?php echo $datetime->format( 'i' ); ?>" />
            </div>

        </div> <?php
    endif;
}

function agora_hide_publishing_actions() {
    $post_type = 'vote';

    global $post;

    if ( $post->post_type == $post_type ) {
    }
}
add_action('admin_head-post.php', 'agora_hide_publishing_actions');
add_action('admin_head-post-new.php', 'agora_hide_publishing_actions');

function wpse149143_edit_posts_views( $views ) {
    unset($views['publish']);
    unset($views['trash']);

    return $views;
}

function agora_register_voting( $post ) {
    global $wpdb;

    $wpdb->insert( $wpdb->prefix . "agora_campaigns", array(
        'vote_id' => $post->ID,
        'voters'  => maybe_serialize(array()),
        'vote_for' => maybe_serialize(array()),
        'vote_against' => maybe_serialize(array()),
        'vote_abstain' => maybe_serialize(array()),
    ) );
}

add_filter( 'views_edit-vote', 'wpse149143_edit_posts_views' );

add_action( 'add_meta_boxes', 'agora_add_meta_boxes' );

add_action( 'save_post', 'agora_save_vote_options' );

add_action( 'save_post', 'agora_save_deadline' );

add_action( 'draft_to_publish', 'agora_register_voting' );

function agora_add_meta_boxes() {
    add_meta_box(
        'vote_options',
        __( 'Opciones', 'agora' ),
        'agora_vote_options_inner_custom_box',
        'vote');

    add_meta_box(
        'vote_editor',
        __( 'Descripción', 'agora' ),
        'agora_vote_editor_box',
        'vote');
}

function agora_vote_editor_box() {
    global $post;

    wp_editor( $post->post_content, 'agora_vote_editor', array(
        'media_buttons' => false,
        'textarea_rows' => 6,
        'quicktags'     => false,
    ) );
}

function agora_vote_options_inner_custom_box() {
    global $post;

    wp_nonce_field( plugin_basename( __FILE__ ), 'agora_vote_options_noncename' ); ?>

    <div id="meta_inner"><?php

    $vote_options = get_post_meta( $post->ID,'vote_options', true );

    if ( count( $vote_options ) > 0 ) {
        $c = 0;

        foreach( $vote_options as $vote_option ) {
            if ( isset( $vote_option['title'] ) ) { ?>
                <p>Opción <input type="text" name="vote-options[<?php echo $c; ?>][title]" value="<?php echo $vote_option['title'] ?>" /><span class="remove"><?php echo __( 'Eliminar opción' ); ?></span></p><?php
                $c = $c +1;
            }
        }
    } ?>
    <span id="here"></span>
    <input type="button" value="<?php _e( 'Agregar opción' ) ?>" class="add button-secondary" />
    <script>
        var $ =jQuery.noConflict();
        $(document).ready(function() {
            var count = <?php echo $c; ?>;
            $(".add").click(function() {
                count = count + 1;

                $('#here').append('<p> Opción <input type="text" name="options['+count+'][title]" value="" /> - Texto: <input type="text" name="options['+count+'][option]" value="" /><span class="remove">Eliminar Opción</span></p>' );
                return false;
            });
            $(".remove").on('click', function() {
                $(this).parent().remove();
            });
        });
        </script>
    </div><?php
}

function agora_save_vote_options( $post_id ) {
    if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE )
        return;

    if ( !isset( $_POST['agora_vote_options_noncename'] ) )
        return;

    if ( !wp_verify_nonce( $_POST['agora_vote_options_noncename'], plugin_basename( __FILE__ ) ) )
        return;

    $vote_options = $_POST['vote-options'];

    update_post_meta( $post_id, 'vote-options', $vote_options );
}

function agora_save_deadline( $post_id ) {
    if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE )
        return;

    if ( !isset( $_POST['agora_vote_options_noncename'] ) )
        return;

    $deadline_day  = $_POST['vote-deadline-day'];
    $deadline_month = $_POST['vote-deadline-month'];
    $deadline_year = $_POST['vote-deadline-year'];
    $deadline_hour = $_POST['vote-deadline-hour'];
    $deadline_min  = $_POST['vote-deadline-min'];
    $vote_deadline = $deadline_year . "-" . $deadline_month . "-" . $deadline_day . " " . $deadline_hour . ":" . $deadline_min . ":00";

    update_post_meta( $post_id, 'vote-deadline', $vote_deadline );
}

function agora_columns( $columns ) {
    unset( $columns['cb'] );
    return $columns;
}
add_filter('manage_edit-vote_columns' , 'agora_columns', 10, 1);

add_action( 'admin_enqueue_scripts', 'agora_admin_scripts');

function agora_admin_scripts() {
    wp_register_script( 'highcharts', plugins_url( 'js/highcharts.js', __FILE__ ) );
    wp_register_script( 'moment', plugins_url( 'js/moment.js', __FILE__ ) );
    wp_register_script( 'countdown', plugins_url( 'js/countdown.min.js', __FILE__ ) );
    wp_register_script( 'moment-countdown', plugins_url( 'js/moment-countdown.min.js', __FILE__ ), array( 'moment', 'countdown' ) );
    wp_enqueue_script ( 'agora', plugins_url('js/agora.js', __FILE__), array( 'moment-countdown', 'jquery-ui-dialog', 'highcharts' ) );
    wp_enqueue_style ( 'wp-jquery-ui-dialog' );
}

add_filter('views_edit-vote', function($args) { ?>
    <div id="vote-detail"> </div><?php

    return $args;
});

function agora_show_vote() {
    global $wpdb;

    preg_match("/post=(\d+)/", $_POST['href'], $vote_id);

    $vote_id = intval($vote_id[1]);
    $user_id = sha1( get_current_user_id() );
    $vote = get_post($vote_id);
    $agora_campaigns = $wpdb->prefix . "agora_campaigns";
    $voters_registry = maybe_unserialize( $wpdb->get_var( "SELECT voters FROM $agora_campaigns WHERE vote_id=$vote_id" ) );
    $countdown = $_POST['countdown'];
    $has_voted = in_array( $user_id, $voters_registry ); ?>

<div class="vote-desc">
    <h1><?php echo $vote->post_title; ?></h1>
    <?php echo wpautop($vote->post_content); ?>
</div>
<div class="vote-tools">
    <div id="vote-chart" style="width:300px;height:300px;"></div>
    <?php if ( $has_voted ) : ?>
    <p>Ya has votado</p>
    <?php else : ?>
    <div id="vote-action-buttons">
        <a href="#" class="vote-action vote-yes dashicons dashicons-yes" data-vote="<?php echo $vote_id; ?>" data-decision="for"></a>
        <a href="#" class="vote-action vote-no dashicons dashicons-no" data-vote="<?php echo $vote_id; ?>" data-decision="against"></a>
        <a href="#" class="vote-action vote-abstain dashicons dashicons-minus" data-vote="<?php echo $vote_id; ?>" data-decision="abstain"></a>
    </div>
    <?php endif; ?>
</div>
<?php
die();
}

add_action( 'wp_ajax_show_vote', 'agora_show_vote');

function agora_submit_vote() {
    global $wpdb;

    $agora_campaigns   = $wpdb->prefix . "agora_campaigns";
    $vote_id           = $_POST['vote_id'];
    $vote_decision     = "vote_" . $_POST['vote_decision'];
    $voter_hashed_id   = sha1( get_current_user_id() );
    $votes_row         = $wpdb->get_results( "SELECT voters, vote_for, vote_against, vote_abstain FROM $agora_campaigns WHERE vote_id=$vote_id" );
    $votes_row         = $votes_row[0];
    $voters_unserialized = maybe_unserialize( $votes_row->voters );
    $votes_decision_unserialized = maybe_unserialize( $votes_row->$vote_decision );

    array_push($voters_unserialized, $voter_hashed_id );
    array_push($votes_decision_unserialized, $voter_hashed_id );

    $voters_serialized = maybe_serialize( $voters_unserialized );
    $votes_decision_serialized = maybe_serialize( $votes_decision_unserialized );

    $wpdb->update( 'wp_agora_campaigns', array(
        'voters' => $voters_serialized,
        $vote_decision => $votes_decision_serialized
    ), array(
        'vote_id' => $vote_id
    ) );

    ?><div class="success-msg">Tu elección fue guardada</div><?php

    die();
}

add_action( 'wp_ajax_submit_vote', 'agora_submit_vote');
?>
