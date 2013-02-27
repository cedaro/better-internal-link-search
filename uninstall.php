<?php
if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	exit;
}

global $wpdb;

delete_option( 'better_internal_link_search' );
delete_option( 'better_internal_link_search_version' );
