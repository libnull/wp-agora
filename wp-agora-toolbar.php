<?php
add_action( 'admin_bar_menu', 'toolbar_link_to_mypage', 999 );

function toolbar_link_to_mypage( $wp_admin_bar ) {
	$args = array(
		'id'    => 'my_page',
		'title' => '<span class="ab-icon"></span>',
		'href'  => 'http://mysite.com/my-Page/',
		'meta'  => array( 'class' => 'my-toolbar-page' )
	);
	$wp_admin_bar->add_node( $args );
}
?>
