<?php
function agora_add_editor() {
    add_meta_box( 'vote_editor', __( 'Descripción', 'agora' ), 'agora_vote_editor_box', 'vote' );
}

add_action( 'add_meta_boxes', 'agora_add_editor' );

function agora_vote_editor_box() {
    global $post;

    wp_editor( $post->post_content, 'agora_vote_editor', array(
        'media_buttons' => false,
        'textarea_rows' => 6,
        'quicktags'     => false,
    ) );
}

?>
