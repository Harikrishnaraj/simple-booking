<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Places where appointments happen. Each staff member works at one location; a booking
 * takes its staff member's location (or the location the customer chose).
 */
class SB_Locations {

	private string $table_name;

	public function __construct() {
		global $wpdb;
		$this->table_name = $wpdb->prefix . 'sb_locations';
	}

	public function get_all( string $status = 'active' ): array {
		global $wpdb;
		if ( 'all' === $status ) {
			return $wpdb->get_results( "SELECT * FROM {$this->table_name} ORDER BY name ASC", ARRAY_A );
		}
		return $wpdb->get_results( $wpdb->prepare( "SELECT * FROM {$this->table_name} WHERE status = %s ORDER BY name ASC", $status ), ARRAY_A );
	}

	public function get_by_id( int $id ): ?array {
		global $wpdb;
		$row = $id ? $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$this->table_name} WHERE id = %d", $id ), ARRAY_A ) : null;
		return $row ?: null;
	}

	/**
	 * Create ($id = 0) or update. Returns '' on success, or an error message.
	 */
	public function save( int $id, array $data ): string {
		global $wpdb;
		$status = sanitize_key( $data['status'] ?? 'active' );
		$row    = [
			'name'    => sanitize_text_field( $data['name'] ?? '' ),
			'address' => sanitize_textarea_field( $data['address'] ?? '' ),
			'phone'   => sanitize_text_field( $data['phone'] ?? '' ),
			'status'  => in_array( $status, [ 'active', 'inactive' ], true ) ? $status : 'active',
		];
		if ( '' === $row['name'] || mb_strlen( $row['name'] ) > 191 ) {
			return __( 'Please enter a name.', 'simple-booking' );
		}
		$ok = $id
			? false !== $wpdb->update( $this->table_name, $row, [ 'id' => $id ], [ '%s', '%s', '%s', '%s' ], [ '%d' ] )
			: (bool) $wpdb->insert( $this->table_name, $row, [ '%s', '%s', '%s', '%s' ] );
		return $ok ? '' : __( 'Could not save the location.', 'simple-booking' );
	}

	/**
	 * Locations with bookings are deactivated so booking history keeps them; otherwise deleted.
	 * Staff working there become unassigned.
	 */
	public function delete( int $id ): bool {
		global $wpdb;
		$wpdb->update( "{$wpdb->prefix}sb_staff", [ 'location_id' => null ], [ 'location_id' => $id ], null, [ '%d' ] );
		$used = $wpdb->get_var( $wpdb->prepare( "SELECT 1 FROM {$wpdb->prefix}sb_bookings WHERE location_id = %d LIMIT 1", $id ) );
		if ( $used ) {
			return false !== $wpdb->update( $this->table_name, [ 'status' => 'inactive' ], [ 'id' => $id ], [ '%s' ], [ '%d' ] );
		}
		return (bool) $wpdb->delete( $this->table_name, [ 'id' => $id ], [ '%d' ] );
	}
}
