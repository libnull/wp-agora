<?php
$agora_option_name = 'vote_options';

add_action( 'add_meta_boxes', 'agora_add_options' );

add_action( 'save_post', 'agora_save_options' );

function agora_add_options() {
    add_meta_box( 'agora_vote_options', __( 'Opciones', 'agora' ), 'agora_editor_box', 'vote' );
}

function agora_editor_box() {
    global $post, $agora_option_name;

    wp_nonce_field( plugin_basename( __FILE__ ), 'agora_option_noncename' ); ?>

    <div id="agora-vote-options-wrapper"><?php

        $options = get_post_meta( $post->ID, $agora_option_name, true );
        $c = 0;

        if ( is_array( $options ) ) :
            foreach( $options as $item ) : ?>
                <p>
                    <input type="text" name="<?php echo $agora_option_name ?>[<?php echo $c ?>]" value="<?php echo $item ?>" placeholder="Ingresa la opción" size="60" />
                    <a href="#" class="remove">Eliminar</a>
                </p><?php
                $c = $c +1;
            endforeach;
        endif; ?>

        <span id="here"></span>

        <a href="#" class="add button-secondary">Nueva opción</a>

        <script>
            jQuery(document).ready(function() {
                var count = <?php echo $c; ?>;

                jQuery(".add").on('click',function(e) {
                    e.preventDefault();

                    count = count + 1;

                    jQuery('#here').append('<p><input size="60" type="text" name="<?php echo $agora_option_name; ?>['+count+']" value="" placeholder="Ingresa la opción"> <a href="#" class="remove">Eliminar</a></p>');

                    return false;
                });

                jQuery("#agora-vote-options-wrapper").on('click', '.remove', function(e) {
                    e.preventDefault();

                    jQuery(this).parent().remove();
                });
            });
        </script>
    </div><?php
}

function agora_save_options( $post_id ) {
    global $agora_option_name;

    if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE )
        return;
    if ( !isset( $_POST['agora_option_noncename'] ) )
        return;
    if ( !wp_verify_nonce( $_POST['agora_option_noncename'], plugin_basename( __FILE__ ) ) )
        return;

    $data = $_POST[$agora_option_name];

    update_post_meta( $post_id, $agora_option_name, $data );
}
?>
