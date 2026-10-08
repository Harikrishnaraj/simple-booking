<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * First-run setup: industry presets and applying the wizard's answers (settings, first staff
 * member, services and a booking page). Safe to run again: existing services, staff and the
 * booking page are reused rather than duplicated.
 */
class CSlot_Setup {

	private const STATUS_KEY   = 'cslot_setup_status';
	public const REDIRECT_KEY  = 'cslot_setup_redirect';

	/**
	 * 'done', 'skipped' or 'pending'. Sites that already have services count as set up,
	 * so updating an existing install never sends anyone through the wizard.
	 */
	public static function status(): string {
		$status = get_option( self::STATUS_KEY, '' );
		if ( in_array( $status, [ 'done', 'skipped' ], true ) ) {
			return $status;
		}
		return ( new CSlot_Services() )->get_all( 'all' ) ? 'done' : 'pending';
	}

	public static function set_status( string $status ): void {
		update_option( self::STATUS_KEY, in_array( $status, [ 'done', 'skipped' ], true ) ? $status : 'pending', false );
	}

	public static function url(): string {
		return admin_url( 'admin.php?page=sb-setup' );
	}

	/**
	 * Industry presets. Durations are in minutes; prices are left to the owner because they
	 * depend on the currency and the business.
	 *
	 * @return array<string, array{label: string, description: string, staff_label: string, days: string[], start: string, end: string, services: array<int, array{0: string, 1: int}>}>
	 */
	public static function presets(): array {
		$weekdays = [ 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday' ];
		$mon_sat  = array_merge( $weekdays, [ 'Saturday' ] );
		return [
			'clinic'     => [
				'label'       => __( 'Clinic or therapy', 'counterslot' ),
				'description' => __( 'Doctors, dentists, physiotherapists, counsellors', 'counterslot' ),
				'staff_label' => __( 'Doctor', 'counterslot' ),
				'days'        => $mon_sat,
				'start'       => '09:00',
				'end'         => '18:00',
				'services'    => [
					[ __( 'Consultation', 'counterslot' ), 20 ],
					[ __( 'Follow-up visit', 'counterslot' ), 15 ],
					[ __( 'Therapy session', 'counterslot' ), 45 ],
				],
			],
			'salon'      => [
				'label'       => __( 'Salon or spa', 'counterslot' ),
				'description' => __( 'Hair, beauty, nails, massage', 'counterslot' ),
				'staff_label' => __( 'Stylist', 'counterslot' ),
				'days'        => [ 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday' ],
				'start'       => '10:00',
				'end'         => '20:00',
				'services'    => [
					[ __( 'Haircut', 'counterslot' ), 30 ],
					[ __( 'Hair colour', 'counterslot' ), 90 ],
					[ __( 'Manicure', 'counterslot' ), 45 ],
					[ __( 'Facial', 'counterslot' ), 60 ],
				],
			],
			'tutoring'   => [
				'label'       => __( 'Tutoring or coaching', 'counterslot' ),
				'description' => __( 'Tuition, music, languages, personal training', 'counterslot' ),
				'staff_label' => __( 'Tutor', 'counterslot' ),
				'days'        => $mon_sat,
				'start'       => '15:00',
				'end'         => '20:00',
				'services'    => [
					[ __( 'Trial lesson', 'counterslot' ), 30 ],
					[ __( 'One-to-one lesson', 'counterslot' ), 60 ],
					[ __( 'Exam preparation', 'counterslot' ), 90 ],
				],
			],
			'consulting' => [
				'label'       => __( 'Consulting or advice', 'counterslot' ),
				'description' => __( 'Lawyers, accountants, advisers, astrologers', 'counterslot' ),
				'staff_label' => __( 'Consultant', 'counterslot' ),
				'days'        => $weekdays,
				'start'       => '10:00',
				'end'         => '18:00',
				'services'    => [
					[ __( 'Introductory call', 'counterslot' ), 15 ],
					[ __( 'Consultation', 'counterslot' ), 60 ],
					[ __( 'Follow-up meeting', 'counterslot' ), 30 ],
				],
			],
			'repairs'    => [
				'label'       => __( 'Repairs or servicing', 'counterslot' ),
				'description' => __( 'Car and bike service, phone repair, home services', 'counterslot' ),
				'staff_label' => __( 'Technician', 'counterslot' ),
				'days'        => $mon_sat,
				'start'       => '09:00',
				'end'         => '19:00',
				'services'    => [
					[ __( 'Inspection', 'counterslot' ), 30 ],
					[ __( 'Standard service', 'counterslot' ), 90 ],
					[ __( 'Repair', 'counterslot' ), 120 ],
				],
			],
			'studio'     => [
				'label'       => __( 'Studio or classes', 'counterslot' ),
				'description' => __( 'Yoga, dance, fitness, photography', 'counterslot' ),
				'staff_label' => __( 'Instructor', 'counterslot' ),
				'days'        => $mon_sat,
				'start'       => '07:00',
				'end'         => '20:00',
				'services'    => [
					[ __( 'Trial session', 'counterslot' ), 30 ],
					[ __( 'Private session', 'counterslot' ), 60 ],
				],
			],
			'other'      => [
				'label'       => __( 'Something else', 'counterslot' ),
				'description' => __( 'Any business that takes appointments', 'counterslot' ),
				'staff_label' => __( 'Staff member', 'counterslot' ),
				'days'        => $weekdays,
				'start'       => '09:00',
				'end'         => '17:00',
				'services'    => [
					[ __( 'Appointment', 'counterslot' ), 30 ],
				],
			],
		];
	}

	/**
	 * Apply the wizard's answers.
	 *
	 * Expects: industry, business_name, admin_email, currency_symbol, slot_duration, work_days[],
	 * business_hours_start/end, staff_label, staff_name, staff_email,
	 * services[i][on|name|duration|price], create_page, page_title.
	 *
	 * @return array{page_url: string, services: int, staff: bool}|WP_Error
	 */
	public static function run( array $in ): array|WP_Error {
		$presets  = self::presets();
		$industry = sanitize_key( (string) ( $in['industry'] ?? '' ) );
		if ( ! isset( $presets[ $industry ] ) ) {
			return new WP_Error( 'industry', __( 'Please choose what kind of business you run.', 'counterslot' ) );
		}
		$name = sanitize_text_field( (string) ( $in['business_name'] ?? '' ) );
		if ( '' === $name ) {
			return new WP_Error( 'business_name', __( 'Please enter your business name.', 'counterslot' ) );
		}
		$time  = '/^([01]\d|2[0-3]):[0-5]\d$/';
		$start = (string) ( $in['business_hours_start'] ?? '' );
		$end   = (string) ( $in['business_hours_end'] ?? '' );
		if ( ! preg_match( $time, $start ) || ! preg_match( $time, $end ) || $start >= $end ) {
			return new WP_Error( 'hours', __( 'Please enter an opening time before the closing time.', 'counterslot' ) );
		}
		$days = array_values( array_intersect( CSlot_Settings::WEEK_DAYS, (array) ( $in['work_days'] ?? [] ) ) );
		if ( ! $days ) {
			return new WP_Error( 'days', __( 'Please choose at least one working day.', 'counterslot' ) );
		}

		$services = [];
		foreach ( (array) ( $in['services'] ?? [] ) as $key => $row ) {
			$row = (array) $row;
			// Rows are keyed by industry ("salon0"); only the chosen industry's rows count.
			if ( ! preg_match( '/^' . $industry . '\d+$/', (string) $key ) || empty( $row['on'] ) || '' === trim( (string) ( $row['name'] ?? '' ) ) ) {
				continue;
			}
			if ( absint( $row['duration'] ?? 0 ) < 1 ) {
				/* translators: %s: service name */
				return new WP_Error( 'duration', sprintf( __( 'Please enter a duration for "%s".', 'counterslot' ), sanitize_text_field( (string) $row['name'] ) ) );
			}
			$services[] = $row;
		}
		if ( ! $services && ! ( new CSlot_Services() )->get_all( 'all' ) ) {
			return new WP_Error( 'services', __( 'Please keep at least one service. You can change or add more later.', 'counterslot' ) );
		}

		$staff_name  = sanitize_text_field( (string) ( $in['staff_name'] ?? '' ) );
		$staff_email = sanitize_email( (string) ( $in['staff_email'] ?? '' ) );
		if ( '' !== $staff_name && ! is_email( $staff_email ) ) {
			return new WP_Error( 'staff_email', __( 'Please enter a valid email address for the first staff member.', 'counterslot' ) );
		}

		// Empty currency or email keep what's set; an invalid email is ignored by update_settings().
		CSlot_Settings::update_settings( array_filter( [
			'admin_email'     => trim( (string) ( $in['admin_email'] ?? '' ) ),
			'currency_symbol' => trim( (string) ( $in['currency_symbol'] ?? '' ) ),
		] ) + [
			'industry'             => $industry,
			'business_name'        => $name,
			'slot_duration'        => $in['slot_duration'] ?? 30,
			'work_days'            => $days,
			'business_hours_start' => $start,
			'business_hours_end'   => $end,
			'staff_label'          => (string) ( $in['staff_label'] ?? '' ),
		] );

		// Services, skipping names that already exist so running the wizard again doesn't duplicate them.
		$catalog  = new CSlot_Services();
		$existing = array_map( fn( $s ) => mb_strtolower( $s['name'] ), $catalog->get_all( 'all' ) );
		$created  = 0;
		foreach ( $services as $row ) {
			$service_name = sanitize_text_field( (string) $row['name'] );
			if ( in_array( mb_strtolower( $service_name ), $existing, true ) ) {
				continue;
			}
			if ( $catalog->create( [ 'name' => $service_name, 'duration' => $row['duration'], 'price' => $row['price'] ?? 0 ] ) ) {
				$existing[] = mb_strtolower( $service_name );
				++$created;
			}
		}

		// First staff member (offers every service), unless one with that email exists.
		$staff_added = false;
		if ( '' !== $staff_name ) {
			$staff       = new CSlot_Staff();
			$known       = array_map( fn( $m ) => strtolower( $m['email'] ), $staff->get_all( 'all' ) );
			$staff_added = ! in_array( strtolower( $staff_email ), $known, true ) && (bool) $staff->create( [ 'name' => $staff_name, 'email' => $staff_email ] );
		}

		$page_url = CSlot_Manage::booking_page_url();
		if ( ! empty( $in['create_page'] ) && '' === $page_url ) {
			$title   = sanitize_text_field( (string) ( $in['page_title'] ?? '' ) ) ?: __( 'Book an appointment', 'counterslot' );
			$page_id = wp_insert_post( [
				'post_type'    => 'page',
				'post_status'  => 'publish',
				'post_title'   => $title,
				'post_content' => "<!-- wp:shortcode -->\n[counterslot]\n<!-- /wp:shortcode -->",
			], true );
			if ( ! is_wp_error( $page_id ) ) {
				CSlot_Settings::update_settings( [ 'booking_page_id' => $page_id ] );
				$page_url = (string) get_permalink( $page_id );
			}
		}

		self::set_status( 'done' );
		return [ 'page_url' => $page_url, 'services' => $created, 'staff' => $staff_added ];
	}
}
