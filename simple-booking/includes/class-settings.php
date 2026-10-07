<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class SB_Settings {

	private const OPTION_KEY = 'sb_settings';

	// Stored in English; displayed with the site's locale.
	public const WEEK_DAYS = [ 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday' ];

	public static function get_defaults(): array {
		return [
			'business_name'        => get_bloginfo( 'name' ),
			'time_zone'            => wp_timezone_string(),
			'slot_duration'        => 30,
			'business_hours_start' => '09:00',
			'business_hours_end'   => '17:00',
			'work_days'            => [ 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday' ],
			'admin_email'          => get_option( 'admin_email' ),
			'customer_notification'=> true,
			'admin_notification'   => true,
			'currency_symbol'      => '$',
			'delete_data_on_uninstall' => false,
			'ip_header'            => 'REMOTE_ADDR',
			'booking_page_id'      => 0,
			'customer_changes'     => true,
			'change_cutoff_hours'  => 24,
			'tax_name'             => '',
			'tax_rate'             => 0,
			'prices_include_tax'   => true,
		];
	}

	/**
	 * Where the visitor's IP comes from, for rate limiting. value => label.
	 */
	public static function ip_headers(): array {
		return [
			'REMOTE_ADDR'           => __( 'Direct connection (default)', 'simple-booking' ),
			'HTTP_CF_CONNECTING_IP' => __( 'Cloudflare (CF-Connecting-IP)', 'simple-booking' ),
			'HTTP_X_FORWARDED_FOR'  => __( 'Proxy or load balancer (X-Forwarded-For)', 'simple-booking' ),
			'HTTP_X_REAL_IP'        => __( 'Nginx proxy (X-Real-IP)', 'simple-booking' ),
		];
	}

	public static function get_settings(): array {
		$saved = get_option( self::OPTION_KEY, [] );
		return wp_parse_args( $saved, self::get_defaults() );
	}

	public static function update_settings( array $new_settings ): bool {
		$new_settings = array_intersect_key( $new_settings, self::get_defaults() );

		if ( isset( $new_settings['slot_duration'] ) ) {
			$new_settings['slot_duration'] = max( 5, absint( $new_settings['slot_duration'] ) );
		}
		foreach ( [ 'business_hours_start', 'business_hours_end' ] as $key ) {
			if ( isset( $new_settings[ $key ] ) && ! preg_match( '/^([01]\d|2[0-3]):[0-5]\d$/', (string) $new_settings[ $key ] ) ) {
				unset( $new_settings[ $key ] );
			}
		}
		foreach ( [ 'booking_page_id', 'change_cutoff_hours' ] as $key ) {
			if ( isset( $new_settings[ $key ] ) ) {
				$new_settings[ $key ] = min( 1000000, absint( $new_settings[ $key ] ) );
			}
		}
		if ( isset( $new_settings['tax_rate'] ) ) {
			$new_settings['tax_rate'] = min( 100, max( 0, round( (float) $new_settings['tax_rate'], 3 ) ) );
		}
		foreach ( [ 'customer_notification', 'admin_notification', 'delete_data_on_uninstall', 'customer_changes', 'prices_include_tax' ] as $key ) {
			if ( isset( $new_settings[ $key ] ) ) {
				$new_settings[ $key ] = rest_sanitize_boolean( $new_settings[ $key ] );
			}
		}
		foreach ( [ 'business_name', 'currency_symbol', 'time_zone', 'tax_name' ] as $key ) {
			if ( isset( $new_settings[ $key ] ) ) {
				$new_settings[ $key ] = sanitize_text_field( (string) $new_settings[ $key ] );
			}
		}
		if ( isset( $new_settings['admin_email'] ) ) {
			$email = sanitize_email( (string) $new_settings['admin_email'] );
			if ( is_email( $email ) ) {
				$new_settings['admin_email'] = $email;
			} else {
				unset( $new_settings['admin_email'] );
			}
		}
		if ( isset( $new_settings['ip_header'] ) && ! isset( self::ip_headers()[ $new_settings['ip_header'] ] ) ) {
			unset( $new_settings['ip_header'] );
		}
		if ( isset( $new_settings['work_days'] ) ) {
			$new_settings['work_days'] = array_values( array_intersect( self::WEEK_DAYS, (array) $new_settings['work_days'] ) );
		}

		$current = self::get_settings();
		$updated = wp_parse_args( $new_settings, $current );
		return update_option( self::OPTION_KEY, $updated );
	}
}
