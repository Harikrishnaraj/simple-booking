<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class SB_Database {

	/**
	 * Create or update database schema.
	 */
	public static function create_tables(): void {
		global $wpdb;

		$charset_collate = $wpdb->get_charset_collate();

		$table_services  = $wpdb->prefix . 'sb_services';
		$table_staff     = $wpdb->prefix . 'sb_staff';
		$table_bookings  = $wpdb->prefix . 'sb_bookings';
		$table_customers = $wpdb->prefix . 'sb_customers';
		$table_categories = $wpdb->prefix . 'sb_categories';

		require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );

		// 1. Services Table
		$sql_services = "CREATE TABLE $table_services (
			id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
			name varchar(191) NOT NULL,
			description text NULL,
			duration int(11) NOT NULL DEFAULT 30,
			price decimal(10,2) NOT NULL DEFAULT 0.00,
			category_id bigint(20) UNSIGNED NULL,
			status varchar(20) NOT NULL DEFAULT 'active',
			created_at datetime DEFAULT CURRENT_TIMESTAMP NOT NULL,
			PRIMARY KEY  (id),
			KEY category_id (category_id)
		) $charset_collate;";

		// Service categories (services.category_id; NULL = uncategorized)
		$sql_categories = "CREATE TABLE $table_categories (
			id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
			name varchar(191) NOT NULL,
			created_at datetime DEFAULT CURRENT_TIMESTAMP NOT NULL,
			PRIMARY KEY  (id)
		) $charset_collate;";

		// 2. Staff Table
		$sql_staff = "CREATE TABLE $table_staff (
			id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
			name varchar(191) NOT NULL,
			email varchar(191) NOT NULL,
			phone varchar(50) NULL,
			services text NULL,
			photo_id bigint(20) UNSIGNED NULL,
			schedule text NULL,
			days_off text NULL,
			status varchar(20) NOT NULL DEFAULT 'active',
			created_at datetime DEFAULT CURRENT_TIMESTAMP NOT NULL,
			PRIMARY KEY  (id)
		) $charset_collate;";

		// 3. Customers Table
		$sql_customers = "CREATE TABLE $table_customers (
			id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
			name varchar(191) NOT NULL,
			email varchar(191) NOT NULL,
			phone varchar(50) NULL,
			note text NULL,
			created_at datetime DEFAULT CURRENT_TIMESTAMP NOT NULL,
			PRIMARY KEY  (id),
			UNIQUE KEY email (email)
		) $charset_collate;";

		// 4. Bookings Table
		$sql_bookings = "CREATE TABLE $table_bookings (
			id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
			booking_code varchar(50) NOT NULL,
			service_id bigint(20) UNSIGNED NOT NULL,
			staff_id bigint(20) UNSIGNED NULL,
			customer_id bigint(20) UNSIGNED NOT NULL,
			booking_date date NOT NULL,
			booking_time time NOT NULL,
			end_time time NOT NULL,
			status varchar(20) NOT NULL DEFAULT 'pending',
			notes text NULL,
			reminder_sent datetime NULL,
			created_at datetime DEFAULT CURRENT_TIMESTAMP NOT NULL,
			PRIMARY KEY  (id),
			UNIQUE KEY booking_code (booking_code),
			KEY service_id (service_id),
			KEY staff_id (staff_id),
			KEY customer_id (customer_id),
			KEY date_staff (booking_date,staff_id)
		) $charset_collate;";

		dbDelta( $sql_services );
		dbDelta( $sql_categories );
		dbDelta( $sql_staff );
		dbDelta( $sql_customers );
		dbDelta( $sql_bookings );

		update_option( 'sb_db_version', SB_VERSION );
	}
}
