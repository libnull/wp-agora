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

add_action( 'init', 'create_vote' );

add_action( 'load-edit.php', 'wpse34956_force_excerpt' );

function wpse34956_force_excerpt() {
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

/*add_action( 'post_submitbox_misc_actions', 'article_or_box' );
function article_or_box() {
    global $post;
    if (get_post_type($post) == 'vote') {
        ?>
        <div class="misc-pub-section curtime misc-pub-curtime">
        <?php
        wp_nonce_field( plugin_basename(__FILE__), 'article_or_box_nonce' );
        $val = get_post_meta( $post->ID, '_article_or_box', true ) ? get_post_meta( $post->ID, '_article_or_box', true ) : 'article';
        ?>

	<span id="timestamp">Pautar fecha de cierre</span>
</div>

        <?php
    }
}*/

function agora_hide_publishing_actions(){
    $post_type = 'vote';

    global $post;

    if($post->post_type == $post_type){
    }
}
add_action('admin_head-post.php', 'agora_hide_publishing_actions');
add_action('admin_head-post-new.php', 'agora_hide_publishing_actions');

function wpse149143_edit_posts_views( $views ) {
    unset($views['publish']);
    unset($views['trash']);

    return $views;
}

add_filter( 'views_edit-vote', 'wpse149143_edit_posts_views' );

add_action( 'add_meta_boxes', 'agora_dynamic_add_custom_box' );

add_action( 'save_post', 'agora_dynamic_save_postdata' );

function agora_dynamic_add_custom_box() {
    add_meta_box(
        'dynamic_options',
        __( 'Opciones', 'agora' ),
        'agora_dynamic_inner_custom_box',
        'vote');
}

function agora_dynamic_inner_custom_box() {
    global $post;

    wp_nonce_field( plugin_basename( __FILE__ ), 'agora_dynamic_noncename' );
    ?>
    <div id="meta_inner">
    <?php

    $options = get_post_meta($post->ID,'options',true);

    $c = 0;
    if ( count( $options ) > 0 ) {
        foreach( $options as $option ) {
            if ( isset( $option['title'] ) || isset( $option['option'] ) ) {
                printf( '<p>Opción <input type="text" name="options[%1$s][title]" value="%2$s" /> - Texto: <input type="text" name="options[%1$s][option]" value="%3$s" /><span class="remove">%4$s</span></p>', $c, $option['title'], $option['option'], __( 'Eliminar opción' ) );
                $c = $c +1;
            }
        }
    }

    ?>
<span id="here"></span>
<input type="button" value="<?php _e( 'Agregar opción' ) ?>" class="add button-secondary" />
<script>
    var $ =jQuery.noConflict();
    $(document).ready(function() {
        var count = <?php echo $c; ?>;
        $(".add").click(function() {
            count = count + 1;

            $('#here').append('<p> Song Title <input type="text" name="options['+count+'][title]" value="" /> - Texto: <input type="text" name="options['+count+'][option]" value="" /><span class="remove">Eliminar Opción</span></p>' );
            return false;
        });
        $(".remove").live('click', function() {
            $(this).parent().remove();
        });
    });
    </script>
</div><?php
}

function agora_dynamic_save_postdata( $post_id ) {
    if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE )
        return;

    if ( !isset( $_POST['agora_dynamic_noncename'] ) )
        return;

    if ( !wp_verify_nonce( $_POST['agora_dynamic_noncename'], plugin_basename( __FILE__ ) ) )
        return;

    $options = $_POST['options'];

    update_post_meta( $post_id, 'options', $options );
}

function agora_columns( $columns ) {
    unset( $columns['cb'] );
    return $columns;
}
add_filter('manage_edit-vote_columns' , 'agora_columns', 10, 1);

add_action( 'admin_enqueue_scripts', 'agora_admin_scripts');

function agora_admin_scripts() {
    wp_enqueue_script ( 'agora', plugins_url('js/agora.js', __FILE__), array( 'jquery-ui-dialog') );
    wp_enqueue_style ( 'wp-jquery-ui-dialog' );
}
?>
