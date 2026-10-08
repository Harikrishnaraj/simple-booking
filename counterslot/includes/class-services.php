<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// phpcs:disable WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- table names come from $wpdb->prefix; every value goes through $wpdb->prepare().

class CSlot_Services {

	private const FORMATS = [ '%s', '%s', '%d', '%f', '%f', '%d', '%s' ];

	private string $table_name;

	public function __construct() {
		global $wpdb;
		$this->table_name = $wpdb->prefix . 'cslot_services';
	}

	public function get_all( string $status = 'active' ): array {
		global $wpdb;
		if ( $status === 'all' ) {
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
		$updated = $wpdb->update( $this->table_name, $row, [ 'id' => $id ], self::FORMATS, [ '%d' ] );
		return false !== $updated;
	}

	/**
	 * Services with bookings are deactivated, not deleted, so booking history and emails keep working.
	 */
	public function delete( int $id ): bool {
		global $wpdb;
		$has_bookings = $wpdb->get_var(
			$wpdb->prepare( "SELECT 1 FROM {$wpdb->prefix}cslot_bookings WHERE service_id = %d LIMIT 1", $id )
		);
		if ( $has_bookings ) {
			return false !== $wpdb->update( $this->table_name, [ 'status' => 'inactive' ], [ 'id' => $id ], [ '%s' ], [ '%d' ] );
		}
		return (bool) $wpdb->delete( $this->table_name, [ 'id' => $id ], [ '%d' ] );
	}

	private function sanitize( array $data ): ?array {
		$name     = sanitize_text_field( $data['name'] ?? '' );
		$duration = absint( $data['duration'] ?? 0 );
		$status   = sanitize_key( $data['status'] ?? 'active' );
		if ( '' === $name || $duration < 1 ) {
			return null;
		}
		$category = absint( $data['category_id'] ?? 0 );
		$price    = max( 0, round( floatval( $data['price'] ?? 0 ), 2 ) );
		return [
			'name'        => $name,
			'description' => sanitize_textarea_field( $data['description'] ?? '' ),
			'duration'    => $duration,
			'price'       => $price,
			// Amount asked for in advance; never more than the price.
			'deposit'     => min( $price, max( 0, round( floatval( $data['deposit'] ?? 0 ), 2 ) ) ),
			'category_id' => $category && ( new CSlot_Categories() )->exists( $category ) ? $category : null,
			'status'      => in_array( $status, [ 'active', 'inactive' ], true ) ? $status : 'active',
		];
	}
}
