<?php
/**
 * Fired when the plugin is uninstalled.
 */

// If uninstall not triggered from WordPress, exit.
if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	exit;
}

global $wpdb;

wp_clear_scheduled_hook( 'cslot_send_reminders' );

// Keep all data unless the admin explicitly opted in to scrubbing it.
$cslot_settings = get_option( 'cslot_settings', [] );
if ( empty( $cslot_settings['delete_data_on_uninstall'] ) ) {
	return;
}

// Delete plugin options
delete_option( 'cslot_db_version' );
delete_option( 'cslot_settings' );
delete_option( 'cslot_email_templates' );
delete_option( 'cslot_custom_fields' );
delete_option( 'cslot_extras' );
delete_option( 'cslot_invoice_counter' );
delete_option( 'cslot_setup_status' );
delete_metadata( 'user', 0, 'cslot_admin_theme', '', true );

// Drop tables if configured to scrub data
$cslot_tables = [
	$wpdb->prefix . 'cslot_bookings',
	$wpdb->prefix . 'cslot_staff',
	$wpdb->prefix . 'cslot_services',
	$wpdb->prefix . 'cslot_customers',
	$wpdb->prefix . 'cslot_categories',
	$wpdb->prefix . 'cslot_locations',
	$wpdb->prefix . 'cslot_coupons',
	$wpdb->prefix . 'cslot_payments',
	$wpdb->prefix . 'cslot_events',
	$wpdb->prefix . 'cslot_event_registrations',
	$wpdb->prefix . 'sb_settings', // table from Simple Booking 1.0.0, no longer created
];

foreach ( $cslot_tables as $cslot_table ) {
	$wpdb->query( "DROP TABLE IF EXISTS {$cslot_table}" ); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.DirectDatabaseQuery.SchemaChange -- names built from $wpdb->prefix above
}
