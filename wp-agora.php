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
require_once( 'wp-agora-db.php' );

require_once( 'wp-agora-metabox-options.php' );

require_once( 'wp-agora-metabox-editor.php' );

require_once( 'wp-agora-table.php' );

register_activation_hook( __FILE__, 'agora_create_tables' );

register_deactivation_hook( __FILE__, 'agora_drop_tables' );

register_deactivation_hook( __FILE__, 'agora_delete_schedules' );

add_action( 'init', 'create_vote' );

function agora_delete_schedules() {
    wp_clear_scheduled_hook( 'close_voting' );
}

function agora_count_voters() {
    $count_users = count_users();

    return $count_users['avail_roles']['subscriber'];
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
                'not_found' => __( 'No se encontraron votaciones' )
            ),
            'public' => true,
            'has_archive' => true,
            'exclude_from_search' => true,
            'publicly_quearyable' => false,
            'menu_icon' => 'dashicons-groups',
            'supports' => array( 'title', 'revisions' ),
            'map_meta_cap' => true,
            'capabilities' => array(
                'edit_post' => 'edit_vote',
                'edit_posts' => 'edit_votes',
                'edit_others_posts' => 'edit_other_votes',
                'publish_posts' => 'publish_votes',
                'edit_publish_posts' => 'edit_publish_votes',
                'read_post' => 'read_votes',
                'read_private_posts' => 'read_private_votes',
                'delete_posts' => 'delete_votes',
                'delete_others_posts' => 'delete_others_votes',
                'delete_published_posts' => 'delete_published_votes'
            ),
            'capability_type' => array( 'vote', 'votes' )
        )
    );
}

function agora_subscriber_capabilities() {
    global $current_user;

    $admin = get_role( 'administrator' );
    $subscriber = get_role( 'subscriber' );
    $caps = array(
        'edit_vote',
        'edit_votes',
        'edit_other_votes',
        'publish_votes',
        'edit_published_votes',
        'read_votes',
        'read_private_votes',
        'delete_votes',
        'delete_others_votes',
        'delete_published_votes'
    );

    foreach ($caps as $cap) {
        $admin->add_cap( $cap );
    }

    $subscriber->add_cap( 'edit_vote' );
    $subscriber->add_cap( 'edit_votes' );
    $subscriber->add_cap( 'read_votes' );
    $subscriber->remove_cap( 'publish_votes' );
    $subscriber->remove_cap( 'edit_published_votes' );

    if ( in_array( 'subscriber', $current_user->roles ) ) {
        $user_meta = get_user_meta( $current_user->ID, 'submited_votes_count', true );

        if ( $user_meta == "" ) {
            add_user_meta( $current_user->ID, 'submited_votes_count', 0, true );
        }
        remove_submenu_page( 'edit.php?post_type=vote', 'post-new.php?post_type=vote' );
    }
}

add_action( 'admin_init', 'agora_subscriber_capabilities');

function agora_remove_meta_boxes() {
    remove_meta_box('slugdiv', 'vote', 'core');
}

add_action( 'admin_menu', 'agora_remove_meta_boxes' );

function agora_remove_actions( $actions, $post ) {
    if( $post->post_type == 'vote' ) {
        unset( $actions['inline hide-if-no-js'] );
        unset( $actions['view'] );
    }
    return $actions;
}
add_filter( 'post_row_actions', 'agora_remove_actions', 10, 2 );

function agora_custom_bulk_actions( $actions ){
    return array();
}

add_filter('bulk_actions-edit-vote','agora_custom_bulk_actions');

function agora_deadline_box() {
    global $post;

    if (get_post_type( $post ) == 'vote') :
        $timezone = current_time( 'timestamp' );
        $vote_deadline  = get_post_meta( $post->ID, 'vote_deadline', true );

        if ( $vote_deadline != null && $vote_deadline != "" ) {
            $datetime = new DateTime( $vote_deadline );
        }
        $date_day = isset( $datetime) ? $datetime->format( 'd' ) : date( 'd' );
        $date_month = isset( $datetime) ? $datetime->format( 'm' ) : date( 'm' );
        $date_year = isset( $datetime) ? $datetime->format( 'Y' ) : date( 'Y' );
        $date_hour = isset( $datetime) ? $datetime->format( 'H' ) : date( 'H', $timezone );
        $date_min = isset( $datetime) ? $datetime->format( 'i' ) : date( 'i', $timezone ); ?>

        <div class="misc-pub-section curtime misc-pub-curtime">

            <span id="timestamp">Cierra el</span>
            <div class="timestamp-wrap">
                <input type="text" id="vote-deadline-date-day" name="vote-deadline-day" min="1" max="31" size="2" value="<?php echo $date_day; ?>" />.
                <select id="sca-date-month" name="vote-deadline-month">
                <?php for ( $i = 1; $i <= 12; $i++ ) : ?>
                    <option <?php echo ( $date_month == $i ? 'selected="selected"' : '' ); ?> value="<?php echo $i; ?>"><?php echo date_i18n( 'F', strtotime( '01.' . $i . '.2013' ) ); ?></option>
                <?php endfor; ?>
                </select>
                <input type="text" id="vote-deadline-date-year" name="vote-deadline-year" size="4" min="<?php echo date( 'Y' ); ?>" value="<?php echo $date_year; ?>" />
                <input type="text" id="vote-deadline-date-hour" name="vote-deadline-hour" size="2" min="0" max="24" value="<?php echo $date_hour; ?>" />:
                <input type="text" id="vote-deadline-date-min" name="vote-deadline-min" size="2" min="0" max="60" value="<?php echo $date_min; ?>" />
            </div>

        </div> <?php
    endif;
}

add_action( 'post_submitbox_misc_actions', 'agora_deadline_box' );

function agora_close_voting( $vote_id ) {
    global $wpdb;

    $agora_campaigns_table = $wpdb->prefix . "agora_campaigns";

    $wpdb->update( $agora_campaigns_table, array( 'open' => "no" ), array( 'vote_id' => $vote_id ) );
}

add_action( 'close_voting', 'agora_close_voting', 10, 1 );


function agora_register_voting( $post_id, $post ) {
    global $wpdb;

    $agora_campaigns_table = $wpdb->prefix . "agora_campaigns";
    $check_vote_existence = $wpdb->get_results( "SELECT vote_id FROM $agora_campaigns_table WHERE vote_id=$post->ID" );
    $is_new_vote = $check_vote_existence == null ? true : false;

    if ( $is_new_vote ) {
        $wpdb->insert( $agora_campaigns_table, array(
            'vote_id' => $post->ID,
            'open'    => "yes"
        ) );
    }
    wp_schedule_single_event( time() + 10, 'close_voting', array( $post->ID ) );
}

add_action( 'save_post', 'agora_save_deadline' );

add_action( 'publish_vote', 'agora_register_voting', 10, 2 );

add_filter( 'content_save_pre', 'agora_save_description', 10, 1 );

function agora_save_description( $content ) {
    if ( isset( $_POST['agora_vote_editor'] ) ) {
        return wpautop( $_POST['agora_vote_editor'] );
    } else {
        return $content;
    }
}

function agora_save_deadline( $post_id ) {
    if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE )
        return;

    if ( isset( $_POST['vote-deadline-day'] ) && isset( $_POST['vote-deadline-month'] ) && isset( $_POST['vote-deadline-year'] )
        && isset( $_POST['vote-deadline-hour'] ) && isset( $_POST['vote-deadline-min'] ) ) {
        $deadline_day  = $_POST['vote-deadline-day'];
        $deadline_month = $_POST['vote-deadline-month'];
        $deadline_year = $_POST['vote-deadline-year'];
        $deadline_hour = $_POST['vote-deadline-hour'];
        $deadline_min  = $_POST['vote-deadline-min'];
        $vote_deadline = $deadline_year . "-" . $deadline_month . "-" . $deadline_day . " " . $deadline_hour . ":" . $deadline_min . ":00";

        update_post_meta( $post_id, 'vote_deadline', $vote_deadline );
    }
}

function agora_columns( $columns ) {
    unset( $columns['cb'] );
    return $columns;
}
add_filter('manage_edit-vote_columns' , 'agora_columns', 10, 1);

add_action( 'admin_enqueue_scripts', 'agora_admin_scripts');

function agora_admin_scripts() {
    global $current_user;

    wp_register_script( 'chart', plugins_url( 'js/chart.js', __FILE__ ) );
    wp_register_script( 'moment', plugins_url( 'js/moment.js', __FILE__ ) );
    wp_enqueue_script ( 'agora', plugins_url('js/agora.js', __FILE__), array( 'moment', 'jquery-ui-dialog', 'chart' ) );
    wp_enqueue_style ( 'wp-jquery-ui-dialog' );

    $is_admin = in_array( 'administrator', $current_user->roles ) ? "yes" : "no";

    wp_localize_script( 'agora', 'is_admin', $is_admin );
}

add_filter('views_edit-vote', 'agora_add_custom_containers', 10, 1 );

function agora_add_custom_containers( $args ) { ?>
    <div id="vote-detail"></div>

    <?php if ( ! agora_check_if_allowed() ) : ?>
        <div class="agora-error error below-h2">
            <p><span class="not-allowed-to-vote dashicons dashicons-info"></span> No tienes permisos para votar</p>
        </div><?php
    endif;

    return $args;

}

function agora_show_vote() {
    global $wpdb;

    $vote_id = intval( $_POST['post_id'] );
    $user_id = sha1( get_current_user_id() );
    $vote = get_post($vote_id);
    $agora_campaigns = $wpdb->prefix . "agora_campaigns";
    $voters_registry = maybe_unserialize( $wpdb->get_var( "SELECT voters FROM $agora_campaigns WHERE vote_id=$vote_id" ) );
    $voters_counter  = sizeof( $voters_registry );
    $vote_options    = get_post_meta( $vote_id, 'vote_options', true );
    $vote_deadline   = get_post_meta( $vote_id, 'vote_deadline', true );
    $has_vote_ended  = agora_check_if_ended( $vote_deadline );
    $countdown = $_POST['countdown'];
    $is_poll   = is_array( $vote_options ) ? "true" : "false";

    if ( $voters_registry != null )
        $has_voted = in_array( $user_id, $voters_registry ) ? "true" : "false";
    else
        $has_voted = "false";

    $is_invalid_campaign = agora_campaign_is_valid( $voters_counter );
    ?>

    <?php if ( $has_vote_ended && $is_invalid_campaign ) : ?>
    <div class="agora-error error invalid">
        <p><span class="not-allowed-to-vote dashicons dashicons-info"></span> La votación no alcanzó el cuórum requerido.</p>
    </div>
    <?php endif; ?>
    <div class="vote-desc">
        <h1><?php echo $vote->post_title; ?></h1>
        <?php if ( is_array( $vote_options ) ) : ?>
            <div class="vote-options">
                <form id="agora-options-form" method="post" action="<?php echo admin_url( 'admin-ajax.php' ); ?>" name="agora-options-form">
                    <input id="agora_form_action" type="hidden" name="action" value="submit_options" />
                    <fieldset>
                        <?php for ( $i = 1; $i <= sizeof( $vote_options ); $i++ ) : ?>
                        <label title="<?php echo $vote_options[$i]; ?>">
                            <input type='radio' name='vote_chosen_option' value="<?php echo $i; ?>" /> <span><?php echo $vote_options[$i]; ?></span>
                        </label><br />
                        <?php endfor; ?>
                    </fieldset>
                </form>
            </div>
        <?php endif;
        if ( $vote->post_content != null && $vote->post_content != "" ) : ?>
            <div id="agora-vote-desc-wrapper">
                <?php echo wpautop($vote->post_content); ?>
            </div>
        <?php endif; ?>
    </div>

    <div class="vote-tools">
        <?php if ( $has_vote_ended ) : ?>
            <canvas id="voting_chart" width="250" height="250"></canvas>
        <?php endif; ?>
        <div id="agora-vote-dates-counter">
            <div class="vote-deadline">
                <h3>
                    <?php if ( $has_vote_ended ) : ?>
                    La votación ha finalizado
                    <?php else : ?>
                    Finaliza <?php echo $countdown; ?>
                    <?php endif; ?>
                </h3>
            </div>
            <ul>
                <li><span class="dashicons dashicons-calendar"></span> Publicada el <strong><?php echo $vote->post_date_gmt; ?></strong></li>
                <li><span class="dashicons dashicons-clock"></span> Abierta hasta el <strong><?php echo $vote_deadline; ?></strong></li>
                <li><span class="dashicons dashicons-groups"></span> Han votado <strong><?php echo $voters_counter; ?> de <?php echo agora_count_voters(); ?> personas</strong></li>
            </ul>
        </div>
    </div> <?php

    die();
}

add_action( 'wp_ajax_show_vote', 'agora_show_vote');

add_action( 'wp_ajax_get_vote_status', 'agora_get_vote_status');

function agora_check_if_ended( $vote_deadline ) {
    $diff_time =  strtotime( $vote_deadline ) - strtotime( current_time( 'Y-m-d H:i:s' ) );

    return $diff_time <= 0 ? true : false;
}

function agora_get_vote_status() {
    global $wpdb;

    $vote_id         = intval( $_POST['vote_id'] );
    $user_id         = sha1( get_current_user_id() );
    $agora_campaigns = $wpdb->prefix . "agora_campaigns";
    $voters_registry = maybe_unserialize( $wpdb->get_var( "SELECT voters FROM $agora_campaigns WHERE vote_id=$vote_id" ) );
    $vote_options    = get_post_meta( $vote_id, 'vote_options', true );
    $vote_deadline   = get_post_meta( $vote_id, 'vote_deadline', true );
    $is_poll         = is_array( $vote_options ) ? true : false;
    $is_allowed      = agora_check_if_allowed();
    $has_ended       = agora_check_if_ended( $vote_deadline );
    $count_for       = $wpdb->get_var( "SELECT vote_for FROM $agora_campaigns WHERE vote_id=$vote_id" );
    $count_against   = $wpdb->get_var( "SELECT vote_against FROM $agora_campaigns WHERE vote_id=$vote_id" );
    $count_abstain   = $wpdb->get_var( "SELECT vote_abstain FROM $agora_campaigns WHERE vote_id=$vote_id" );
    $vote_status     = array(
        'is_allowed' => $is_allowed,
        'has_voted' => "",
        'is_poll' => "",
        'has_ended' => $has_ended,
        'deadline' => $vote_deadline,
        'count_for' => intval( $count_for ),
        'count_against' => intval( $count_against ),
        'count_abstain' => intval( $count_abstain )
    );

    if ( is_array( $voters_registry ) ) {
        $vote_status['has_voted'] = in_array( $user_id, $voters_registry ) ? true : false;
    }

    $vote_status['is_poll'] = $is_poll ? true : false;

    echo json_encode( $vote_status );

    die();
}

function agora_check_if_allowed( $user_id = null ) {
    $user_id        = $user_id != null ? $user_id : get_current_user_id();
    $user_object    = get_user_by( 'id', $user_id );
    $is_admin_user  = in_array( "administrator", $user_object->roles ) ? true : false;

    if ( $is_admin_user )
        return false;

    $voter_registry = get_user_meta( $user_id, 'submited_votes_count', true );
    $signup_time    = $user_object->user_registered;
    $signup_date    = new DateTime( $signup_time );
    $current_date   = new DateTime( current_time( 'Y-m-d H:i:s' ) );
    $interval       = $signup_date->diff( $current_date );
    $has_req_time   = $interval->days >= 60 ? true : false;
    $has_req_count  = false;
    $voter_meta     = get_user_meta( $user_id, 'can_vote', true );
    $can_vote       = $voter_meta == "yes" ? true : false;

    if ( $voter_registry )
        $has_req_count = $voter_registry >= 3 ? true : true;

    if ( $can_vote ) {
        return true;
    } else {
        return $has_req_time && $has_req_count ? true : false;
    }
}

function agora_submit_vote() {
    global $wpdb;

    $agora_campaigns   = $wpdb->prefix . "agora_campaigns";
    $voter_user_id     = get_current_user_id();
    $vote_id           = $_POST['vote_id'];
    $vote_deadline     = get_post_meta( $vote_id, 'vote_deadline', true );
    $is_allowed_to_vote = agora_check_if_allowed();

    if ( $is_allowed_to_vote ) {
        $vote_decision     = "vote_" . $_POST['vote_decision'];
        $voter_hashed_id   = sha1( $voter_user_id );
        $votes_row         = $wpdb->get_results( "SELECT voters, vote_for, vote_against, vote_abstain FROM $agora_campaigns WHERE vote_id=$vote_id" );
        $votes_row         = $votes_row[0];
        $voters_unserialized = maybe_unserialize( $votes_row->voters );

        array_push($voters_unserialized, $voter_hashed_id );

        $voters_serialized = maybe_serialize( $voters_unserialized );
        $voters_count = sizeof( $votes_row->$vote_decision );

        $wpdb->update( 'wp_agora_campaigns', array(
            'voters' => $voters_serialized,
            $vote_decision => $voters_count
        ), array(
            'vote_id' => $vote_id
        ) );

        $voter_registry = get_user_meta( $voter_user_id, 'submited_votes_count', true );
        $has_registry = $voter_registry != null ? true : false;

        if ( $has_registry ) {
            $votes_count = $voter_registry + 1;

            update_user_meta( $voter_user_id, 'submited_votes_count', $votes_count );
        } else {
            add_user_meta( $voter_user_id, 'submited_votes_count', 1, true );
        }
    }

    die();
}

add_action( 'wp_ajax_submit_vote', 'agora_submit_vote');

function agora_calculate_quorum() {
    $user_count = new WP_User_Query( array( 'role' => "Subscriber", 'meta_key' => 'can_vote', 'meta_value' => 'yes' ) );

    return $user_count->get_total() / 3;
}

function agora_campaign_is_valid( $votes_submitted ) {
    $cuorum_required = agora_calculate_quorum();

    return $cuorum_required >= $votes_submitted ? true : false;

}

add_action( 'admin_footer-users.php', 'agora_add_bulk_user_actions' );
add_action( 'admin_action_disable_voting', 'agora_disable_right_to_vote' );
add_action( 'admin_action_enable_voting', 'agora_enable_right_to_vote' );

function agora_add_bulk_user_actions() { ?>
    <script type="text/javascript">
        jQuery(document).ready(function($) {
            jQuery('<option>').val('enable_voting').text('Habilitar para votar')
                .appendTo("select[name='action'], select[name='action2']");
            jQuery('<option>').val('disable_voting').text('Deshabilitar para votar')
                .appendTo("select[name='action'], select[name='action2']");
        });
    </script><?php
}

function agora_disable_right_to_vote() {
    foreach ( $_REQUEST['users'] as $user ) {
        $user_id = intval( $user );
        $user_meta = get_user_meta( $user_id, "can_vote", true );
        $user_object    = get_user_by( 'id', $user_id );
        $is_admin_user  = in_array( "administrator", $user_object->roles ) ? true : false;

        if ( ! $is_admin_user ) {
            if ( $user_meta )
                update_post_meta( $user_id, 'can_vote', "no" );
            else
                add_user_meta( $user_id, "can_vote", "no", true );
        }
    }
}

function agora_enable_right_to_vote() {
    foreach ( $_REQUEST['users'] as $user ) {
        $user_id = intval( $user );
        $user_meta = get_user_meta( $user_id, "can_vote", true );
        $user_object    = get_user_by( 'id', $user_id );
        $is_admin_user  = in_array( "administrator", $user_object->roles ) ? true : false;

        if ( ! $is_admin_user ) {
            if ( $user_meta )
                update_post_meta( $user_id, 'can_vote', "yes" );
            else
                add_user_meta( $user_id, "can_vote", "yes", true );
        }
    }
}

function agora_add_rights_profile_field( $user ) {
    $user_meta = get_user_meta( $user->ID, 'can_vote', true );
    $can_vote_text = $user_meta == "yes" ? "Puede votar" : "No puede votar"; ?>
    <h3>Votaciones</h3>
    <p><strong><?php echo $can_vote_text; ?></strong></p><?php
}

add_action( 'profile_personal_options', 'agora_add_rights_profile_field' );

add_action( 'edit_user_profile', 'agora_add_rights_profile_field' );

add_filter('manage_users_columns', 'agora_add_votes_count_status');

function agora_add_votes_count_status($columns) {
    $columns['votes_count'] = 'Votaciones';
    $columns['can_vote'] = "Puede votar";
    return $columns;
}

add_action('manage_users_custom_column',  'agora_show_votes_count_column_status', 10, 3);

function agora_show_votes_count_column_status($value, $column_name, $user_id) {
    $votes_count = get_user_meta( $user_id, 'submited_votes_count', true );
    $votes_count = $votes_count == "" ? "0" : $votes_count;
    $can_vote    = get_user_meta( $user_id, 'can_vote', true );
    $can_vote    = $can_vote == "no" || $can_vote == "" ? "No" : "Sí";

	if ( 'votes_count' == $column_name )
		return $votes_count;

	if ( 'can_vote' == $column_name )
		return $can_vote;

    return $value;
}
?>
