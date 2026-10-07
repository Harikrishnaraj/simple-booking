<?php
/**
 * Fired when the plugin is uninstalled.
 */

// If uninstall not triggered from WordPress, exit.
if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	exit;
}

global $wpdb;

// Keep all data unless the admin explicitly opted in to scrubbing it.
$settings = get_option( 'sb_settings', [] );
if ( empty( $settings['delete_data_on_uninstall'] ) ) {
	return;
}

// Delete plugin options
delete_option( 'sb_db_version' );
delete_option( 'sb_settings' );

// Drop tables if configured to scrub data
$tables = [
	$wpdb->prefix . 'sb_bookings',
	$wpdb->prefix . 'sb_staff',
	$wpdb->prefix . 'sb_services',
	$wpdb->prefix . 'sb_customers',
	$wpdb->prefix . 'sb_settings', // no longer created; dropped for installs from 1.0.0
];

foreach ( $tables as $table ) {
	$wpdb->query( "DROP TABLE IF EXISTS {$table}" );
}
