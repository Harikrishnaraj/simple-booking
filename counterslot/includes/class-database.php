<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// phpcs:disable WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- table names come from $wpdb->prefix; every value goes through $wpdb->prepare().

class CSlot_Database {

	private const TABLES  = [ 'services', 'staff', 'bookings', 'customers', 'categories', 'locations', 'coupons', 'payments', 'events', 'event_registrations' ];
	private const OPTIONS = [ 'settings', 'email_templates', 'custom_fields', 'extras', 'invoice_counter', 'setup_status' ];

	/**
	 * Before 3.0 the plugin was "Simple Booking" and named everything sb_*. Move that data to
	 * the cslot_* names: rename the tables, then the options and the per-user theme choice.
	 * Anything already under the new name is left alone, so this is safe to run repeatedly.
	 * Tables that can't be renamed are copied by copy_from_simple_booking() once they exist.
	 */
	public static function migrate_from_simple_booking(): void {
		global $wpdb;
		foreach ( self::TABLES as $name ) {
			$old = $wpdb->prefix . 'sb_' . $name;
			$new = $wpdb->prefix . 'cslot_' . $name;
			if ( self::table_exists( $old ) && ! self::table_exists( $new ) ) {
				// Identifiers can't be placeholders before WP 6.2; both names are built from $wpdb->prefix and constants.
				$wpdb->query( "ALTER TABLE `$old` RENAME TO `$new`" ); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.DirectDatabaseQuery
			}
		}
		foreach ( self::OPTIONS as $name ) {
			$value = get_option( 'sb_' . $name, null );
			if ( null !== $value ) {
				if ( null === get_option( 'cslot_' . $name, null ) ) {
					add_option( 'cslot_' . $name, $value, '', false );
				}
				delete_option( 'sb_' . $name );
			}
		}
		delete_option( 'sb_db_version' );
		$wpdb->update( $wpdb->usermeta, [ 'meta_key' => 'cslot_admin_theme' ], [ 'meta_key' => 'sb_admin_theme' ] ); // phpcs:ignore WordPress.DB.SlowDBQuery
		wp_clear_scheduled_hook( 'sb_send_reminders' );
	}

	/**
	 * Fallback for databases that can't rename tables (the SQLite driver used by WordPress
	 * Playground and some hosts): copy each old sb_* table into its new, still empty cslot_* table,
	 * column by column since their order can differ, and drop the old table once every row is in.
	 */
	private static function copy_from_simple_booking(): void {
		global $wpdb;
		foreach ( self::TABLES as $name ) {
			$old = $wpdb->prefix . 'sb_' . $name;
			$new = $wpdb->prefix . 'cslot_' . $name;
			if ( ! self::table_exists( $old ) || ! self::table_exists( $new ) ) {
				continue;
			}
			$rows = (int) $wpdb->get_var( "SELECT COUNT(*) FROM `$old`" );
			if ( $rows && (int) $wpdb->get_var( "SELECT COUNT(*) FROM `$new`" ) ) {
				continue; // Both have data: leave it for the admin rather than guess.
			}
			if ( $rows ) {
				$columns = '`' . implode( '`, `', array_intersect( $wpdb->get_col( "DESC `$old`", 0 ), $wpdb->get_col( "DESC `$new`", 0 ) ) ) . '`';
				$wpdb->query( "INSERT INTO `$new` ($columns) SELECT $columns FROM `$old`" ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared -- column names read from the tables themselves
				if ( (int) $wpdb->get_var( "SELECT COUNT(*) FROM `$new`" ) !== $rows ) {
					continue; // Keep the old table if anything is missing.
				}
			}
			$wpdb->query( "DROP TABLE `$old`" ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery.SchemaChange
		}
	}

	private static function table_exists( string $table ): bool {
		global $wpdb;
		return (bool) $wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', $wpdb->esc_like( $table ) ) );
	}

	/**
	 * Create or update database schema.
	 */
	public static function create_tables(): void {
		global $wpdb;
		self::migrate_from_simple_booking();

		$charset_collate = $wpdb->get_charset_collate();

		$table_services  = $wpdb->prefix . 'cslot_services';
		$table_staff     = $wpdb->prefix . 'cslot_staff';
		$table_bookings  = $wpdb->prefix . 'cslot_bookings';
		$table_customers = $wpdb->prefix . 'cslot_customers';
		$table_categories = $wpdb->prefix . 'cslot_categories';
		$table_locations  = $wpdb->prefix . 'cslot_locations';
		$table_coupons    = $wpdb->prefix . 'cslot_coupons';
		$table_payments   = $wpdb->prefix . 'cslot_payments';
		$table_events     = $wpdb->prefix . 'cslot_events';
		$table_event_regs = $wpdb->prefix . 'cslot_event_registrations';

		require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );

		// 1. Services Table
		$sql_services = "CREATE TABLE $table_services (
			id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
			name varchar(191) NOT NULL,
			description text NULL,
			duration int(11) NOT NULL DEFAULT 30,
			price decimal(10,2) NOT NULL DEFAULT 0.00,
			deposit decimal(10,2) NOT NULL DEFAULT 0.00,
			category_id bigint(20) UNSIGNED NULL,
			status varchar(20) NOT NULL DEFAULT 'active',
			created_at datetime DEFAULT CURRENT_TIMESTAMP NOT NULL,
			PRIMARY KEY  (id),
			KEY category_id (category_id)
		) $charset_collate;";

		// Locations (staff.location_id, bookings.location_id)
		$sql_locations = "CREATE TABLE $table_locations (
			id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
			name varchar(191) NOT NULL,
			address text NULL,
			phone varchar(50) NULL,
			status varchar(20) NOT NULL DEFAULT 'active',
			created_at datetime DEFAULT CURRENT_TIMESTAMP NOT NULL,
			PRIMARY KEY  (id)
		) $charset_collate;";

		// Coupons (bookings keep their own price breakdown in bookings.pricing)
		$sql_coupons = "CREATE TABLE $table_coupons (
			id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
			code varchar(50) NOT NULL,
			type varchar(10) NOT NULL DEFAULT 'percent',
			value decimal(10,2) NOT NULL DEFAULT 0.00,
			services text NULL,
			valid_from date NULL,
			valid_to date NULL,
			max_uses int(11) NOT NULL DEFAULT 0,
			used int(11) NOT NULL DEFAULT 0,
			status varchar(20) NOT NULL DEFAULT 'active',
			created_at datetime DEFAULT CURRENT_TIMESTAMP NOT NULL,
			PRIMARY KEY  (id),
			UNIQUE KEY code (code)
		) $charset_collate;";

		// Payments recorded by hand; negative amounts are refunds.
		$sql_payments = "CREATE TABLE $table_payments (
			id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
			booking_id bigint(20) UNSIGNED NOT NULL,
			amount decimal(10,2) NOT NULL,
			method varchar(20) NOT NULL,
			note varchar(191) NULL,
			paid_at datetime NOT NULL,
			created_by bigint(20) UNSIGNED NULL,
			created_at datetime DEFAULT CURRENT_TIMESTAMP NOT NULL,
			PRIMARY KEY  (id),
			KEY booking_id (booking_id),
			KEY paid_at (paid_at)
		) $charset_collate;";

		// Group events with limited places, and their registrations
		$sql_events = "CREATE TABLE $table_events (
			id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
			name varchar(191) NOT NULL,
			description text NULL,
			event_date date NOT NULL,
			start_time time NOT NULL,
			end_time time NOT NULL,
			capacity int(11) NOT NULL DEFAULT 1,
			price decimal(10,2) NOT NULL DEFAULT 0.00,
			location_id bigint(20) UNSIGNED NULL,
			staff_id bigint(20) UNSIGNED NULL,
			status varchar(20) NOT NULL DEFAULT 'active',
			created_at datetime DEFAULT CURRENT_TIMESTAMP NOT NULL,
			PRIMARY KEY  (id),
			KEY event_date (event_date)
		) $charset_collate;";

		$sql_event_regs = "CREATE TABLE $table_event_regs (
			id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
			event_id bigint(20) UNSIGNED NOT NULL,
			customer_id bigint(20) UNSIGNED NOT NULL,
			spots int(11) NOT NULL DEFAULT 1,
			code varchar(50) NOT NULL,
			total decimal(10,2) NOT NULL DEFAULT 0.00,
			status varchar(20) NOT NULL DEFAULT 'registered',
			created_at datetime DEFAULT CURRENT_TIMESTAMP NOT NULL,
			PRIMARY KEY  (id),
			UNIQUE KEY code (code),
			KEY event_id (event_id)
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
			location_id bigint(20) UNSIGNED NULL,
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
			location_id bigint(20) UNSIGNED NULL,
			booking_date date NOT NULL,
			booking_time time NOT NULL,
			end_time time NOT NULL,
			status varchar(20) NOT NULL DEFAULT 'pending',
			notes text NULL,
			custom_fields text NULL,
			series_id varchar(20) NULL,
			pricing text NULL,
			total decimal(10,2) NULL,
			invoice_number varchar(30) NULL,
			invoice_date date NULL,
			reminder_sent datetime NULL,
			created_at datetime DEFAULT CURRENT_TIMESTAMP NOT NULL,
			PRIMARY KEY  (id),
			UNIQUE KEY booking_code (booking_code),
			KEY service_id (service_id),
			KEY staff_id (staff_id),
			KEY customer_id (customer_id),
			KEY date_staff (booking_date,staff_id),
			KEY series_id (series_id)
		) $charset_collate;";

		dbDelta( $sql_services );
		dbDelta( $sql_categories );
		dbDelta( $sql_locations );
		dbDelta( $sql_coupons );
		dbDelta( $sql_payments );
		dbDelta( $sql_events );
		dbDelta( $sql_event_regs );
		dbDelta( $sql_staff );
		dbDelta( $sql_customers );
		dbDelta( $sql_bookings );

		self::copy_from_simple_booking();
		update_option( 'cslot_db_version', CSLOT_VERSION );
	}
}
