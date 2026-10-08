<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Group events with a limited number of places (workshops, group sessions).
 * Registrations are separate from appointment bookings; an event's host staff member
 * is treated as busy for appointments while it runs (see SB_Bookings::active_on()).
 */
class SB_Events {

	private string $events;
	private string $registrations;

	public function __construct() {
		global $wpdb;
		$this->events        = $wpdb->prefix . 'sb_events';
		$this->registrations = $wpdb->prefix . 'sb_event_registrations';
	}

	/**
	 * Events with 'taken' (registered places). $upcoming: active events that haven't started yet.
	 */
	public function get_all( bool $upcoming = false ): array {
		global $wpdb;
		$rows = $wpdb->get_results(
			"SELECT e.*, COALESCE(r.taken, 0) AS taken, l.name AS location_name, l.address AS location_address, st.name AS staff_name
			 FROM {$this->events} e
			 LEFT JOIN (SELECT event_id, SUM(spots) AS taken FROM {$this->registrations} WHERE status = 'registered' GROUP BY event_id) r ON r.event_id = e.id
			 LEFT JOIN {$wpdb->prefix}sb_locations l ON l.id = e.location_id
			 LEFT JOIN {$wpdb->prefix}sb_staff st ON st.id = e.staff_id
			 ORDER BY e.event_date DESC, e.start_time DESC",
			ARRAY_A
		);
		if ( ! $upcoming ) {
			return $rows;
		}
		$now = wp_date( 'Y-m-d H:i:s' );
		return array_reverse( array_values( array_filter( $rows, fn( $e ) => 'active' === $e['status'] && $e['event_date'] . ' ' . $e['start_time'] > $now ) ) );
	}

	public function get_by_id( int $id ): ?array {
		foreach ( $this->get_all() as $event ) {
			if ( (int) $event['id'] === $id ) {
				return $event;
			}
		}
		return null;
	}

	/**
	 * Active events on a date (for the calendar and for blocking the host's time).
	 */
	public function on( string $from, string $to ): array {
		global $wpdb;
		return $wpdb->get_results(
			$wpdb->prepare( "SELECT * FROM {$this->events} WHERE status = 'active' AND event_date BETWEEN %s AND %s ORDER BY event_date, start_time", $from, $to ),
			ARRAY_A
		);
	}

	/**
	 * Create ($id = 0) or update an event. Returns '' or an error message.
	 */
	public function save( int $id, array $in ): string {
		global $wpdb;
		$date  = sanitize_text_field( $in['event_date'] ?? '' );
		$start = sanitize_text_field( $in['start_time'] ?? '' );
		$end   = sanitize_text_field( $in['end_time'] ?? '' );
		$day   = DateTimeImmutable::createFromFormat( '!Y-m-d', $date );
		$time  = '/^([01]\d|2[0-3]):[0-5]\d$/';
		$row   = [
			'name'        => sanitize_text_field( $in['name'] ?? '' ),
			'description' => sanitize_textarea_field( $in['description'] ?? '' ),
			'event_date'  => $date,
			'start_time'  => $start . ':00',
			'end_time'    => $end . ':00',
			'capacity'    => max( 1, absint( $in['capacity'] ?? 1 ) ),
			'price'       => max( 0, round( (float) ( $in['price'] ?? 0 ), 2 ) ),
			'location_id' => absint( $in['location_id'] ?? 0 ) ?: null,
			'staff_id'    => absint( $in['staff_id'] ?? 0 ) ?: null,
			'status'      => in_array( $in['status'] ?? '', [ 'active', 'inactive' ], true ) ? $in['status'] : 'active',
		];
		if ( '' === $row['name'] || mb_strlen( $row['name'] ) > 191 ) {
			return __( 'Please enter a name.', 'bellbook' );
		}
		if ( ! $day || $day->format( 'Y-m-d' ) !== $date || ! preg_match( $time, $start ) || ! preg_match( $time, $end ) || $start >= $end ) {
			return __( 'Please enter a date and a start time before the end time.', 'bellbook' );
		}
		if ( $id ) {
			$event = $this->get_by_id( $id );
			if ( $event && $row['capacity'] < (int) $event['taken'] ) {
				/* translators: %d: places already taken */
				return sprintf( __( '%d places are already taken, so the capacity can\'t be lower than that.', 'bellbook' ), (int) $event['taken'] );
			}
		}
		$formats = [ '%s', '%s', '%s', '%s', '%s', '%d', '%f', '%d', '%d', '%s' ];
		$ok      = $id
			? false !== $wpdb->update( $this->events, $row, [ 'id' => $id ], $formats, [ '%d' ] )
			: (bool) $wpdb->insert( $this->events, $row, $formats );
		return $ok ? '' : __( 'Could not save the event.', 'bellbook' );
	}

	/**
	 * Cancel an event and its registrations. Returns the registrations that were active, to email.
	 */
	public function cancel( int $id ): array {
		global $wpdb;
		$attendees = $this->registrations( $id, true );
		$wpdb->update( $this->events, [ 'status' => 'cancelled' ], [ 'id' => $id ], [ '%s' ], [ '%d' ] );
		$wpdb->update( $this->registrations, [ 'status' => 'cancelled' ], [ 'event_id' => $id, 'status' => 'registered' ], [ '%s' ], [ '%d', '%s' ] );
		return $attendees;
	}

	/**
	 * Registrations with customer details, newest first.
	 */
	public function registrations( int $event_id, bool $active_only = false ): array {
		global $wpdb;
		return $wpdb->get_results(
			$wpdb->prepare(
				"SELECT r.*, c.name AS customer_name, c.email AS customer_email, c.phone AS customer_phone
				 FROM {$this->registrations} r LEFT JOIN {$wpdb->prefix}sb_customers c ON c.id = r.customer_id
				 WHERE r.event_id = %d" . ( $active_only ? " AND r.status = 'registered'" : '' ) . ' ORDER BY r.id DESC',
				$event_id
			),
			ARRAY_A
		);
	}

	/**
	 * Register $spots places, if they're still free (checked under a per-event lock).
	 *
	 * @return int|string the registration id, or an error message
	 */
	public function register( int $event_id, int $customer_id, int $spots ): int|string {
		global $wpdb;
		$spots = max( 1, $spots );
		$lock  = 'sb_event_' . md5( $wpdb->prefix . $event_id );
		if ( 1 !== (int) $wpdb->get_var( $wpdb->prepare( 'SELECT GET_LOCK(%s, 5)', $lock ) ) ) {
			return __( 'Please try again in a moment.', 'bellbook' );
		}
		try {
			$event = $this->get_by_id( $event_id );
			if ( ! $event || 'active' !== $event['status'] || $event['event_date'] . ' ' . $event['start_time'] <= wp_date( 'Y-m-d H:i:s' ) ) {
				return __( 'This event is no longer open for registration.', 'bellbook' );
			}
			$left = (int) $event['capacity'] - (int) $event['taken'];
			if ( $spots > $left ) {
				return $left > 0
					/* translators: %d: places left */
					? sprintf( _n( 'Only %d place is left.', 'Only %d places are left.', $left, 'bellbook' ), $left )
					: __( 'Sorry, this event is full.', 'bellbook' );
			}
			$total = round( (float) $event['price'] * $spots, 2 );
			for ( $attempt = 0, $ok = false; ! $ok && $attempt < 3; $attempt++ ) {
				$ok = $wpdb->insert(
					$this->registrations,
					[ 'event_id' => $event_id, 'customer_id' => $customer_id, 'spots' => $spots, 'code' => 'EV-' . strtoupper( wp_generate_password( 6, false ) ), 'total' => $total, 'status' => 'registered' ],
					[ '%d', '%d', '%d', '%s', '%f', '%s' ]
				);
			}
			return $ok ? (int) $wpdb->insert_id : __( 'Your registration could not be saved. Please try again.', 'bellbook' );
		} finally {
			$wpdb->query( $wpdb->prepare( 'SELECT RELEASE_LOCK(%s)', $lock ) );
		}
	}

	public function cancel_registration( int $id ): bool {
		global $wpdb;
		return (bool) $wpdb->update( $this->registrations, [ 'status' => 'cancelled' ], [ 'id' => $id, 'status' => 'registered' ], [ '%s' ], [ '%d', '%s' ] );
	}

	public function registration( int $id ): ?array {
		global $wpdb;
		$row = $wpdb->get_row(
			$wpdb->prepare(
				"SELECT r.*, c.name AS customer_name, c.email AS customer_email, c.phone AS customer_phone
				 FROM {$this->registrations} r LEFT JOIN {$wpdb->prefix}sb_customers c ON c.id = r.customer_id WHERE r.id = %d",
				$id
			),
			ARRAY_A
		);
		return $row ?: null;
	}
}
