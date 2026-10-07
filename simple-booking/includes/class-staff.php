<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class SB_Staff {

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
		$inserted = $wpdb->insert( $this->table_name, $row, [ '%s', '%s', '%s', '%s', '%s' ] );
		return $inserted ? $wpdb->insert_id : false;
	}

	public function update( int $id, array $data ): bool {
		global $wpdb;
		$row = $this->sanitize( $data );
		if ( ! $row ) {
			return false;
		}
		return false !== $wpdb->update( $this->table_name, $row, [ 'id' => $id ], [ '%s', '%s', '%s', '%s', '%s' ], [ '%d' ] );
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

	private function sanitize( array $data ): ?array {
		$name   = sanitize_text_field( $data['name'] ?? '' );
		$email  = sanitize_email( $data['email'] ?? '' );
		$status = sanitize_key( $data['status'] ?? 'active' );
		if ( '' === $name || ! is_email( $email ) ) {
			return null;
		}
		return [
			'name'     => $name,
			'email'    => $email,
			'phone'    => sanitize_text_field( $data['phone'] ?? '' ),
			'services' => wp_json_encode( array_values( array_unique( array_filter( array_map( 'absint', (array) ( $data['services'] ?? [] ) ) ) ) ) ),
			'status'   => in_array( $status, [ 'active', 'inactive' ], true ) ? $status : 'active',
		];
	}
}
