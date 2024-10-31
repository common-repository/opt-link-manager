<?php
// If uninstall is not called from WordPress, exit
if ( !defined( 'WP_UNINSTALL_PLUGIN' ) ) {
    exit();
}
global $wpdb;


$wpdb->query( "DELETE FROM $wpdb->options WHERE option_name LIKE 'olm\_%';" );

$wpdb->query( "DELETE FROM {$wpdb->posts} WHERE post_type IN ( 'olm-link' );" );

?>