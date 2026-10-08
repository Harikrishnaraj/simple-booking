<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * First-run setup: industry presets and applying the wizard's answers (settings, first staff
 * member, services and a booking page). Safe to run again: existing services, staff and the
 * booking page are reused rather than duplicated.
 */
class SB_Setup {

	private const STATUS_KEY   = 'sb_setup_status';
	public const REDIRECT_KEY  = 'sb_setup_redirect';

	/**
	 * 'done', 'skipped' or 'pending'. Sites that already have services count as set up,
	 * so updating an existing install never sends anyone through the wizard.
	 */
	public static function status(): string {
		$status = get_option( self::STATUS_KEY, '' );
		if ( in_array( $status, [ 'done', 'skipped' ], true ) ) {
			return $status;
		}
		return ( new SB_Services() )->get_all( 'all' ) ? 'done' : 'pending';
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
				'label'       => __( 'Clinic or therapy', 'simple-booking' ),
				'description' => __( 'Doctors, dentists, physiotherapists, counsellors', 'simple-booking' ),
				'staff_label' => __( 'Doctor', 'simple-booking' ),
				'days'        => $mon_sat,
				'start'       => '09:00',
				'end'         => '18:00',
				'services'    => [
					[ __( 'Consultation', 'simple-booking' ), 20 ],
					[ __( 'Follow-up visit', 'simple-booking' ), 15 ],
					[ __( 'Therapy session', 'simple-booking' ), 45 ],
				],
			],
			'salon'      => [
				'label'       => __( 'Salon or spa', 'simple-booking' ),
				'description' => __( 'Hair, beauty, nails, massage', 'simple-booking' ),
				'staff_label' => __( 'Stylist', 'simple-booking' ),
				'days'        => [ 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday' ],
				'start'       => '10:00',
				'end'         => '20:00',
				'services'    => [
					[ __( 'Haircut', 'simple-booking' ), 30 ],
					[ __( 'Hair colour', 'simple-booking' ), 90 ],
					[ __( 'Manicure', 'simple-booking' ), 45 ],
					[ __( 'Facial', 'simple-booking' ), 60 ],
				],
			],
			'tutoring'   => [
				'label'       => __( 'Tutoring or coaching', 'simple-booking' ),
				'description' => __( 'Tuition, music, languages, personal training', 'simple-booking' ),
				'staff_label' => __( 'Tutor', 'simple-booking' ),
				'days'        => $mon_sat,
				'start'       => '15:00',
				'end'         => '20:00',
				'services'    => [
					[ __( 'Trial lesson', 'simple-booking' ), 30 ],
					[ __( 'One-to-one lesson', 'simple-booking' ), 60 ],
					[ __( 'Exam preparation', 'simple-booking' ), 90 ],
				],
			],
			'consulting' => [
				'label'       => __( 'Consulting or advice', 'simple-booking' ),
				'description' => __( 'Lawyers, accountants, advisers, astrologers', 'simple-booking' ),
				'staff_label' => __( 'Consultant', 'simple-booking' ),
				'days'        => $weekdays,
				'start'       => '10:00',
				'end'         => '18:00',
				'services'    => [
					[ __( 'Introductory call', 'simple-booking' ), 15 ],
					[ __( 'Consultation', 'simple-booking' ), 60 ],
					[ __( 'Follow-up meeting', 'simple-booking' ), 30 ],
				],
			],
			'repairs'    => [
				'label'       => __( 'Repairs or servicing', 'simple-booking' ),
				'description' => __( 'Car and bike service, phone repair, home services', 'simple-booking' ),
				'staff_label' => __( 'Technician', 'simple-booking' ),
				'days'        => $mon_sat,
				'start'       => '09:00',
				'end'         => '19:00',
				'services'    => [
					[ __( 'Inspection', 'simple-booking' ), 30 ],
					[ __( 'Standard service', 'simple-booking' ), 90 ],
					[ __( 'Repair', 'simple-booking' ), 120 ],
				],
			],
			'studio'     => [
				'label'       => __( 'Studio or classes', 'simple-booking' ),
				'description' => __( 'Yoga, dance, fitness, photography', 'simple-booking' ),
				'staff_label' => __( 'Instructor', 'simple-booking' ),
				'days'        => $mon_sat,
				'start'       => '07:00',
				'end'         => '20:00',
				'services'    => [
					[ __( 'Trial session', 'simple-booking' ), 30 ],
					[ __( 'Private session', 'simple-booking' ), 60 ],
				],
			],
			'other'      => [
				'label'       => __( 'Something else', 'simple-booking' ),
				'description' => __( 'Any business that takes appointments', 'simple-booking' ),
				'staff_label' => __( 'Staff member', 'simple-booking' ),
				'days'        => $weekdays,
				'start'       => '09:00',
				'end'         => '17:00',
				'services'    => [
					[ __( 'Appointment', 'simple-booking' ), 30 ],
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
			return new WP_Error( 'industry', __( 'Please choose what kind of business you run.', 'simple-booking' ) );
		}
		$name = sanitize_text_field( (string) ( $in['business_name'] ?? '' ) );
		if ( '' === $name ) {
			return new WP_Error( 'business_name', __( 'Please enter your business name.', 'simple-booking' ) );
		}
		$time  = '/^([01]\d|2[0-3]):[0-5]\d$/';
		$start = (string) ( $in['business_hours_start'] ?? '' );
		$end   = (string) ( $in['business_hours_end'] ?? '' );
		if ( ! preg_match( $time, $start ) || ! preg_match( $time, $end ) || $start >= $end ) {
			return new WP_Error( 'hours', __( 'Please enter an opening time before the closing time.', 'simple-booking' ) );
		}
		$days = array_values( array_intersect( SB_Settings::WEEK_DAYS, (array) ( $in['work_days'] ?? [] ) ) );
		if ( ! $days ) {
			return new WP_Error( 'days', __( 'Please choose at least one working day.', 'simple-booking' ) );
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
				return new WP_Error( 'duration', sprintf( __( 'Please enter a duration for "%s".', 'simple-booking' ), sanitize_text_field( (string) $row['name'] ) ) );
			}
			$services[] = $row;
		}
		if ( ! $services && ! ( new SB_Services() )->get_all( 'all' ) ) {
			return new WP_Error( 'services', __( 'Please keep at least one service. You can change or add more later.', 'simple-booking' ) );
		}

		$staff_name  = sanitize_text_field( (string) ( $in['staff_name'] ?? '' ) );
		$staff_email = sanitize_email( (string) ( $in['staff_email'] ?? '' ) );
		if ( '' !== $staff_name && ! is_email( $staff_email ) ) {
			return new WP_Error( 'staff_email', __( 'Please enter a valid email address for the first staff member.', 'simple-booking' ) );
		}

		// Empty currency or email keep what's set; an invalid email is ignored by update_settings().
		SB_Settings::update_settings( array_filter( [
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
		$catalog  = new SB_Services();
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
			$staff       = new SB_Staff();
			$known       = array_map( fn( $m ) => strtolower( $m['email'] ), $staff->get_all( 'all' ) );
			$staff_added = ! in_array( strtolower( $staff_email ), $known, true ) && (bool) $staff->create( [ 'name' => $staff_name, 'email' => $staff_email ] );
		}

		$page_url = SB_Manage::booking_page_url();
		if ( ! empty( $in['create_page'] ) && '' === $page_url ) {
			$title   = sanitize_text_field( (string) ( $in['page_title'] ?? '' ) ) ?: __( 'Book an appointment', 'simple-booking' );
			$page_id = wp_insert_post( [
				'post_type'    => 'page',
				'post_status'  => 'publish',
				'post_title'   => $title,
				'post_content' => "<!-- wp:shortcode -->\n[simple_booking]\n<!-- /wp:shortcode -->",
			], true );
			if ( ! is_wp_error( $page_id ) ) {
				SB_Settings::update_settings( [ 'booking_page_id' => $page_id ] );
				$page_url = (string) get_permalink( $page_id );
			}
		}

		self::set_status( 'done' );
		return [ 'page_url' => $page_url, 'services' => $created, 'staff' => $staff_added ];
	}
}
