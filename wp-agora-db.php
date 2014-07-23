<?php
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
      voters longtext DEFAULT NULL,
      vote_for bigint(20) DEFAULT NULL,
      vote_against bigint(20) DEFAULT NULL,
      vote_abstain bigint(20) DEFAULT NULL,
      vote_options text DEFAULT NULL,
      open varchar(3) DEFAULT NULL,
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
?>
