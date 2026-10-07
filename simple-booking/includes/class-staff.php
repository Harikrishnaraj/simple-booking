<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class SB_Staff {

	private const FORMATS = [ '%s', '%s', '%s', '%s', '%d', '%s', '%s', '%s' ];

	private string $table_name;

	public function __construct() {
		global $wpdb;
		$this->table_name = $wpdb->prefix . 'sb_staff';
	}

	public function get_all( string $status = 'active' ): array {
		global $wpdb;
		if ( 'all' === $status ) {
			return $wpdb->get_results( "SELECT * FROM {$this->table_name} ORDER BY name ASC", ARRAY_A );
		}
		return $wpdb->get_results(
			$wpdb->prepare( "SELECT * FROM {$this->table_name} WHERE status = %s ORDER BY name ASC", $status ),
			ARRAY_A
		);
	}

	public function get_by_id( int $id ): ?array {
		global $wpdb;
		$row = $wpdb->get_row(
			$wpdb->prepare( "SELECT * FROM {$this->table_name} WHERE id = %d", $id ),
			ARRAY_A
		);
		return $row ?: null;
	}

	public function create( array $data ): int|false {
		global $wpdb;
		$row = $this->sanitize( $data );
		if ( ! $row ) {
			return false;
		}
		$inserted = $wpdb->insert( $this->table_name, $row, self::FORMATS );
		return $inserted ? $wpdb->insert_id : false;
	}

	public function update( int $id, array $data ): bool {
		global $wpdb;
		$row = $this->sanitize( $data );
		if ( ! $row ) {
			return false;
		}
		return false !== $wpdb->update( $this->table_name, $row, [ 'id' => $id ], self::FORMATS, [ '%d' ] );
	}

	/**
	 * Staff with bookings are deactivated, not deleted, so booking history stays intact.
	 */
	public function delete( int $id ): bool {
		global $wpdb;
		$has_bookings = $wpdb->get_var(
			$wpdb->prepare( "SELECT 1 FROM {$wpdb->prefix}sb_bookings WHERE staff_id = %d LIMIT 1", $id )
		);
		if ( $has_bookings ) {
			return false !== $wpdb->update( $this->table_name, [ 'status' => 'inactive' ], [ 'id' => $id ], [ '%s' ], [ '%d' ] );
		}
		return (bool) $wpdb->delete( $this->table_name, [ 'id' => $id ], [ '%d' ] );
	}

	/**
	 * Service ids this staff member offers. Empty means they offer every service.
	 *
	 * @return int[]
	 */
	public function service_ids( array $staff ): array {
		return array_values( array_filter( array_map( 'absint', (array) json_decode( (string) ( $staff['services'] ?? '' ), true ) ) ) );
	}

	/**
	 * Ids of active staff who offer this service.
	 *
	 * @return int[]
	 */
	public function qualified_ids( int $service_id ): array {
		$ids = [];
		foreach ( $this->get_all() as $member ) {
			$offers = $this->service_ids( $member );
			if ( ! $offers || in_array( $service_id, $offers, true ) ) {
				$ids[] = (int) $member['id'];
			}
		}
		return $ids;
	}

	public function can_perform( int $staff_id, int $service_id ): bool {
		$staff = $this->get_by_id( $staff_id );
		if ( ! $staff || 'active' !== $staff['status'] ) {
			return false;
		}
		$ids = $this->service_ids( $staff );
		return ! $ids || in_array( $service_id, $ids, true );
	}

	/**
	 * Working hours on a date as [ "H:i", "H:i" ], or null when they don't work that day.
	 * Without a custom schedule they work the business hours and days. Days off always win.
	 */
	public function hours_on( array $staff, string $date ): ?array {
		foreach ( $this->days_off( $staff ) as $off ) {
			if ( $date >= $off['from'] && $date <= $off['to'] ) {
				return null;
			}
		}
		$weekday  = gmdate( 'l', strtotime( $date . ' 12:00 UTC' ) );
		$schedule = $this->schedule( $staff );
		if ( null === $schedule ) {
			return self::business_hours_on( $weekday );
		}
		return $schedule[ $weekday ] ?? null;
	}

	/**
	 * The business's own hours on a weekday (English name), or null when closed.
	 */
	public static function business_hours_on( string $weekday ): ?array {
		$settings = SB_Settings::get_settings();
		return in_array( $weekday, (array) $settings['work_days'], true )
			? [ $settings['business_hours_start'], $settings['business_hours_end'] ]
			: null;
	}

	/**
	 * Custom weekly hours: weekday => [ start, end ] for working days only. Null = uses business hours.
	 */
	public function schedule( array $staff ): ?array {
		$schedule = json_decode( (string) ( $staff['schedule'] ?? '' ), true );
		return is_array( $schedule ) ? $schedule : null;
	}

	/**
	 * @return array<int, array{from: string, to: string}> date ranges, inclusive
	 */
	public function days_off( array $staff ): array {
		$days = json_decode( (string) ( $staff['days_off'] ?? '' ), true );
		return is_array( $days ) ? $days : [];
	}

	/**
	 * URL of the staff member's photo at a registered image size, or '' when there is none.
	 */
	public function photo_url( array $staff, string $size = 'thumbnail' ): string {
		$id = absint( $staff['photo_id'] ?? 0 );
		return $id ? (string) wp_get_attachment_image_url( $id, $size ) : '';
	}

	/**
	 * From the form: schedule_custom=1 and schedule[Monday][on|start|end]. Days with a bad
	 * or empty time range count as off. Returns JSON, or null to use the business hours.
	 */
	private static function sanitize_schedule( array $data ): ?string {
		if ( empty( $data['schedule_custom'] ) ) {
			return null;
		}
		$time = '/^([01]\d|2[0-3]):[0-5]\d$/';
		$out  = [];
		foreach ( SB_Settings::WEEK_DAYS as $day ) {
			$row   = (array) ( $data['schedule'][ $day ] ?? [] );
			$start = (string) ( $row['start'] ?? '' );
			$end   = (string) ( $row['end'] ?? '' );
			if ( ! empty( $row['on'] ) && preg_match( $time, $start ) && preg_match( $time, $end ) && $start < $end ) {
				$out[ $day ] = [ $start, $end ];
			}
		}
		return wp_json_encode( $out );
	}

	/**
	 * From the form: days_off[from][] and days_off[to][] (an empty "to" means a single day).
	 */
	private static function sanitize_days_off( array $data ): ?string {
		$from = array_values( (array) ( $data['days_off']['from'] ?? [] ) );
		$to   = array_values( (array) ( $data['days_off']['to'] ?? [] ) );
		$out  = [];
		foreach ( $from as $i => $start ) {
			$start = (string) $start;
			$end   = (string) ( $to[ $i ] ?? '' ) ?: $start;
			$valid = static fn( $d ) => ( $x = DateTimeImmutable::createFromFormat( '!Y-m-d', $d ) ) && $x->format( 'Y-m-d' ) === $d;
			if ( $valid( $start ) && $valid( $end ) ) {
				$out[] = $start <= $end ? [ 'from' => $start, 'to' => $end ] : [ 'from' => $end, 'to' => $start ];
			}
		}
		usort( $out, fn( $a, $b ) => strcmp( $a['from'], $b['from'] ) );
		return $out ? wp_json_encode( $out ) : null;
	}

	private function sanitize( array $data ): ?array {
		$name   = sanitize_text_field( $data['name'] ?? '' );
		$email  = sanitize_email( $data['email'] ?? '' );
		$status = sanitize_key( $data['status'] ?? 'active' );
		$photo  = absint( $data['photo_id'] ?? 0 );
		if ( '' === $name || ! is_email( $email ) ) {
			return null;
		}
		return [
			'name'     => $name,
			'email'    => $email,
			'phone'    => sanitize_text_field( $data['phone'] ?? '' ),
			'services' => wp_json_encode( array_values( array_unique( array_filter( array_map( 'absint', (array) ( $data['services'] ?? [] ) ) ) ) ) ),
			'photo_id' => $photo && wp_attachment_is_image( $photo ) ? $photo : null,
			'status'   => in_array( $status, [ 'active', 'inactive' ], true ) ? $status : 'active',
			'schedule' => self::sanitize_schedule( $data ),
			'days_off' => self::sanitize_days_off( $data ),
		];
	}
}
