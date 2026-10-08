<?php
// Stubbed-WordPress check for CSlot_Bookings slot logic + create_booking guards.
define( 'ABSPATH', __DIR__ );
define( 'MINUTE_IN_SECONDS', 60 );
define( 'ARRAY_A', 'ARRAY_A' );
date_default_timezone_set( 'UTC' );

function wp_timezone() { return new DateTimeZone( 'Europe/London' ); }
function wp_date( $f, $ts ) { return ( new DateTimeImmutable( '@' . $ts ) )->setTimezone( wp_timezone() )->format( $f ); }
function absint( $v ) { return abs( (int) $v ); }
function sanitize_text_field( $v ) { return trim( (string) $v ); }
function sanitize_textarea_field( $v ) { return (string) $v; }
function wp_generate_password( $n ) { return substr( md5( uniqid() ), 0, $n ); }
function get_option( $k, $d = [] ) { return $GLOBALS['opts'][ $k ] ?? $d; }
function update_option( $k, $v ) { $GLOBALS['opts'][ $k ] = $v; return true; }
function wp_parse_args( $a, $d ) { return array_merge( $d, (array) $a ); }
function get_bloginfo() { return 'x'; }
function wp_timezone_string() { return 'Europe/London'; }
function sanitize_email( $v ) { return trim( (string) $v ); }
function is_email( $v ) { return (bool) filter_var( $v, FILTER_VALIDATE_EMAIL ); }
function __( $s ) { return $s; }
class WP_Error { function __construct( public $code, public $msg ) {} function get_error_message() { return $this->msg; } function get_error_code() { return $this->code; } }
function is_wp_error( $v ) { return $v instanceof WP_Error; }
function rest_sanitize_boolean( $v ) { return in_array( strtolower( (string) $v ), [ '1', 'true', 'yes', 'on' ], true ) || true === $v; }

class CSlot_Services { function get_by_id( $id ) { return $GLOBALS['services'][ $id ] ?? null; } }
class CSlot_Email { function __call( $n, $a ) { $GLOBALS['mails'][] = $n; return true; } static function inside_reminder_window() { return false; } }
function esc_html( $v ) { return htmlspecialchars( (string) $v, ENT_QUOTES ); }
function wp_strip_all_tags( $v ) { return strip_tags( (string) $v ); }
function wpautop( $v ) { return $v; }
function wp_salt( $s = '' ) { return 'test-salt'; }
if ( ! defined( 'HOUR_IN_SECONDS' ) ) define( 'HOUR_IN_SECONDS', 3600 );
function sanitize_key( $v ) { return preg_replace( '/[^a-z0-9_\-]/', '', strtolower( (string) $v ) ); }

class FakeWpdb {
	public $prefix = 'wp_'; public $coupons_by_code = []; public $insert_id = 0; public $rows = []; public $lock = 1; public $customers = [];
	public $staff = [ 7 => [ 'id' => 7, 'status' => 'active', 'services' => '' ], 8 => [ 'id' => 8, 'status' => 'inactive', 'services' => '' ], 9 => [ 'id' => 9, 'status' => 'active', 'services' => '[5]' ] ];
	function prepare( $q, ...$a ) { return [ $q, $a ]; }
	function get_var( $p ) {
		[ $q, $a ] = $p;
		if ( str_contains( $q, 'GET_LOCK' ) ) return (string) $this->lock;
		if ( str_contains( $q, 'cslot_customers' ) ) return array_search( $a[0], $this->customers, true ) ?: null;
	}
	function get_row( $p ) {
		[ $q, $a ] = $p;
		if ( str_contains( $q, 'cslot_staff' ) ) return $this->staff[ $a[0] ] ?? null;
		if ( str_contains( $q, 'cslot_bookings' ) ) return $this->rows[ $a[0] - 1 ] ?? null;
		if ( str_contains( $q, 'cslot_coupons' ) ) return $this->coupons_by_code[ $a[0] ] ?? null;
		return null;
	}
	function update( $t, $d, $w ) { $i = $w['id'] - 1; if ( ! isset( $this->rows[ $i ] ) ) return 0; $this->rows[ $i ] = $d + $this->rows[ $i ]; return 1; }
	function query( $p ) { return 1; }
	function get_results( $p ) {
		[ $q, $a ] = $p;
		if ( str_contains( $q, 'cslot_staff' ) ) return array_values( array_filter( $this->staff, fn( $m ) => $m['status'] === $a[0] ) );
		$out = [];
		foreach ( $this->rows as $r ) {
			if ( $r['booking_date'] !== $a[0] || ! in_array( $r['status'], [ 'pending', 'confirmed' ], true ) ) continue;
			$out[] = [ 'id' => (string) $r['id'], 'booking_time' => $r['booking_time'], 'end_time' => $r['end_time'], 'staff_id' => null === $r['staff_id'] ? null : (string) $r['staff_id'] ]; // like wpdb: strings
		}
		return $out;
	}
	function insert( $t, $d ) {
		if ( str_contains( $t, 'cslot_customers' ) ) { if ( in_array( $d['email'], $this->customers, true ) ) return false; $this->insert_id = count( $this->customers ) + 1; $this->customers[ $this->insert_id ] = $d['email']; return 1; }
		$d['id'] = count( $this->rows ) + 1; $this->rows[] = $d; $this->insert_id = $d['id']; return 1; }
}

$base = __DIR__ . '/../counterslot/includes/';
require $base . 'class-settings.php';
require $base . 'class-setup.php';
require $base . 'class-bookings.php';
require $base . 'class-staff.php';
require $base . 'class-validator.php';
require $base . 'class-customers.php';
require $base . 'class-reports.php';
require $base . 'class-notifications.php';
require $base . 'class-custom-fields.php';
require $base . 'class-manage.php';
require $base . 'class-pricing.php';
require $base . 'class-payments.php';

$wpdb     = new FakeWpdb();
$services = [ 1 => [ 'id' => 1, 'duration' => 60, 'status' => 'active' ], 2 => [ 'id' => 2, 'duration' => 30, 'status' => 'inactive' ] ];
$opts     = [ 'cslot_settings' => [ 'business_hours_start' => '09:00', 'business_hours_end' => '12:00', 'slot_duration' => 30 ] ];
$b        = new CSlot_Bookings();

$monday = ( new DateTimeImmutable( 'next monday', wp_timezone() ) )->format( 'Y-m-d' );
$sunday = ( new DateTimeImmutable( 'next sunday', wp_timezone() ) )->format( 'Y-m-d' );

// Slot generation
assert( $b->get_available_slots( 1, null, $monday ) === [ '09:00', '09:30', '10:00', '10:30', '11:00' ] );
assert( $b->get_available_slots( 1, null, $sunday ) === [], 'work_days respected' );
assert( $b->get_available_slots( 1, null, '2020-01-06' ) === [], 'past date' );
assert( $b->get_available_slots( 1, null, '2026-13-45' ) === [], 'invalid date' );
assert( $b->get_available_slots( 2, null, $monday ) === [], 'inactive service' );
assert( $b->get_available_slots( 1, 8, $monday ) === [], 'inactive staff' );

// slot_duration 0 must not hang
$opts['cslot_settings']['slot_duration'] = 0;
assert( count( $b->get_available_slots( 1, null, $monday ) ) > 0 );
$opts['cslot_settings']['slot_duration'] = 30;

// create_booking: valid, then same slot again rejected, bad time rejected
$req = [ 'service_id' => 1, 'customer_id' => 3, 'booking_date' => $monday, 'booking_time' => '10:00' ];
assert( 1 === $b->create_booking( $req ) );
assert( '11:00:00' === $wpdb->rows[0]['end_time'] );
assert( false === $b->create_booking( $req ), 'double booking blocked' );
assert( false === $b->create_booking( [ 'booking_time' => '10:30' ] + $req ), 'overlap blocked' );
assert( false === $b->create_booking( [ 'booking_time' => '23:45' ] + $req ), 'outside hours blocked' );
assert( false === $b->create_booking( [ 'booking_time' => 'garbage' ] + $req ) );
assert( false === $b->create_booking( [ 'booking_date' => $sunday ] + $req ) );

// Staff/no-staff mixing: the no-staff 10:00 booking blocks staff 7 too
assert( ! in_array( '10:00', $b->get_available_slots( 1, 7, $monday ), true ) );

// Lock timeout -> reject
$wpdb->lock = 0;
assert( false === $b->create_booking( [ 'booking_time' => '09:00' ] + $req ) );
$wpdb->lock = 1;

// Settings sanitization
CSlot_Settings::update_settings( [ 'slot_duration' => '0', 'business_hours_end' => 'evil', 'junk' => 1, 'delete_data_on_uninstall' => 'false' ] );
$s = CSlot_Settings::get_settings();
assert( 5 === $s['slot_duration'] && '12:00' === $s['business_hours_end'] && ! isset( $s['junk'] ) && false === $s['delete_data_on_uninstall'] );

// Reschedule: own slot doesn't block it; can't move onto another booking; only active bookings move
$wpdb->rows = [];
$a = $b->create_booking( [ 'booking_time' => '09:00' ] + $req );
$c = $b->create_booking( [ 'booking_time' => '11:00' ] + $req );
assert( $a && $c );
assert( in_array( '09:30', $b->get_available_slots( 1, null, $monday, $a ), true ), 'own slot ignored when rescheduling' );
assert( true === $b->reschedule( $a, $monday, '09:30', null, false ) );
assert( '09:30:00' === $wpdb->rows[ $a - 1 ]['booking_time'] && '10:30:00' === $wpdb->rows[ $a - 1 ]['end_time'] );
assert( false === $b->reschedule( $a, $monday, '10:30', null, false ), 'overlaps the 11:00 booking' );
assert( false === $b->reschedule( $a, $sunday, '09:00', null, false ), 'closed day' );

// Reopening a cancelled booking re-checks its time
assert( true === $b->update_status( $a, 'cancelled' ) );
$d = $b->create_booking( [ 'booking_time' => '09:30' ] + $req ); // takes the freed slot
assert( (bool) $d );
assert( false === $b->reschedule( $a, $monday, '13:00', null, false ), 'cancelled bookings cannot be moved' );
$r = $b->update_status( $a, 'confirmed' );
assert( is_wp_error( $r ) && 'taken' === $r->code, 'reopen blocked when slot taken' );
assert( 'cancelled' === $wpdb->rows[ $a - 1 ]['status'] );
assert( true === $b->update_status( $d, 'cancelled' ) );
assert( true === $b->update_status( $a, 'pending' ), 'reopen allowed once free again' );
assert( is_wp_error( $b->update_status( $a, 'bogus' ) ) );

// Staff schedules: custom hours and days off; "any available" uses whoever works then
$wpdb->rows = [];
$opts['cslot_settings']['slot_duration'] = 30;
$wpdb->staff[7]['schedule'] = json_encode( [ 'Monday' => [ '10:00', '12:00' ] ] );
assert( $b->get_available_slots( 1, 7, $monday ) === [ '10:00', '10:30', '11:00' ], 'custom hours' );
assert( $b->get_available_slots( 1, 7, $tuesday = ( new DateTimeImmutable( $monday ) )->modify( '+1 day' )->format( 'Y-m-d' ) ) === [], 'not working Tuesday' );
$wpdb->staff[9]['schedule'] = json_encode( [ 'Monday' => [ '09:00', '10:00' ] ] );
$services[5] = [ 'id' => 5, 'duration' => 60, 'status' => 'active' ];
assert( $b->get_available_slots( 5, null, $monday ) === [ '09:00', '10:00', '10:30', '11:00' ], 'any available spans both schedules' );
$wpdb->staff[7]['days_off'] = json_encode( [ [ 'from' => $monday, 'to' => $monday ] ] );
assert( $b->get_available_slots( 1, 7, $monday ) === [] && $b->get_available_slots( 5, null, $monday ) === [ '09:00' ], 'day off' );
unset( $wpdb->staff[7]['schedule'], $wpdb->staff[7]['days_off'], $wpdb->staff[9]['schedule'] );
assert( count( $b->get_available_slots( 1, 7, $monday ) ) === 5, 'back to business hours' );

// Locations: "any available" limited to staff at the chosen location; booking stores it
$wpdb->rows = [];
$wpdb->staff[7]['location_id'] = 1;
$wpdb->staff[9]['location_id'] = 2;
$wpdb->staff[9]['schedule'] = json_encode( [ 'Monday' => [ '09:00', '10:00' ] ] );
assert( [ '09:00' ] === $b->get_available_slots( 5, null, $monday, null, 2 ), 'only staff 9 works at location 2' );
assert( 5 === count( $b->get_available_slots( 5, null, $monday, null, 1 ) ), 'staff 7 at location 1' );
assert( [] === $b->get_available_slots( 5, null, $monday, null, 3 ), 'nobody at location 3' );
$loc = $b->create_booking( [ 'service_id' => 5, 'location_id' => 2, 'booking_time' => '09:00' ] + $req );
assert( $loc && 9 === $wpdb->rows[ $loc - 1 ]['staff_id'] && 2 === $wpdb->rows[ $loc - 1 ]['location_id'] );
unset( $wpdb->staff[7]['location_id'], $wpdb->staff[9]['location_id'], $wpdb->staff[9]['schedule'] );

// Recurring: every 2 weeks x4; a blocked week is skipped and reported; blocked first session = nothing
$wpdb->rows = [];
$wk = fn( $n ) => ( new DateTimeImmutable( $monday ) )->modify( "+$n weeks" )->format( 'Y-m-d' );
$b->create_booking( [ 'booking_date' => $wk( 4 ), 'booking_time' => '10:00' ] + $req ); // blocks the 3rd session
$mails = [];
$series = $b->create_series( [ 'booking_date' => $monday, 'booking_time' => '10:00' ] + $req, 2, 4 );
assert( 3 === count( $series['ids'] ) && [ $wk( 4 ) ] === $series['missed'] );
assert( [ $monday, $wk( 2 ), $wk( 6 ) ] === array_map( fn( $id ) => $wpdb->rows[ $id - 1 ]['booking_date'], $series['ids'] ) );
$sid = $wpdb->rows[ $series['ids'][0] - 1 ]['series_id'];
assert( $sid && $sid === $wpdb->rows[ $series['ids'][2] - 1 ]['series_id'] && null === $wpdb->rows[0]['series_id'] );
assert( [ 'booking_created' ] === $mails, 'one set of emails for the series' );
assert( [ 'ids' => [], 'missed' => [] ] === $b->create_series( [ 'booking_date' => $wk( 4 ), 'booking_time' => '10:00' ] + $req, 1, 3 ), 'first session taken' );

// Dashboard comparison period: same length, ending the day before
assert( CSlot_Reports::previous_range( '2026-10-01', '2026-10-31' ) === [ '2026-08-31', '2026-09-30' ] );
assert( CSlot_Reports::previous_range( '2026-03-01', '2026-03-01' ) === [ '2026-02-28', '2026-02-28' ] );
assert( CSlot_Reports::change( 77, 105 ) === -27 && CSlot_Reports::change( 5, 0 ) === null && CSlot_Reports::change( 0, 4 ) === -100 );

// Email templates: placeholders filled and escaped; lines whose placeholders are all empty dropped
$email = CSlot_Notifications::render(
	[ 'subject' => "Hi {customer_name}\r\nBcc: x@y.z <b>", 'body' => "Hi {customer_name},\nWith: {staff_name}\nCode: {booking_code} {notes}\nNo placeholder line\n{unknown}" ],
	[ '{customer_name}' => 'Ann <script>', '{staff_name}' => '', '{booking_code}' => 'SB-1', '{notes}' => '' ]
);
assert( false === strpos( $email['subject'], "\n" ) && false === strpos( $email['subject'], '<' ), 'subject is one plain line' );
assert( str_contains( $email['html'], 'Hi Ann &lt;script&gt;,' ) );
assert( ! str_contains( $email['html'], 'With:' ), 'empty staff line dropped' );
assert( str_contains( $email['html'], 'Code: SB-1 ' ), 'line kept when one placeholder has a value' );
assert( str_contains( $email['html'], 'No placeholder line' ) && str_contains( $email['html'], '{unknown}' ) );

// Custom fields: per-service, required, dropdown choices, dates, checkbox, label kept with the answer
CSlot_Custom_Fields::save( [ 'label' => 'Date of birth', 'type' => 'date', 'required' => 1 ] );
CSlot_Custom_Fields::save( [ 'label' => 'First visit?', 'type' => 'checkbox' ] );
CSlot_Custom_Fields::save( [ 'label' => 'Skin type', 'type' => 'select', 'options' => "Dry\nOily\nOily", 'services' => [ 2 ] ] );
assert( '' !== CSlot_Custom_Fields::save( [ 'label' => 'Bad', 'type' => 'select', 'options' => 'One' ] ), 'dropdown needs 2 choices' );
assert( '' !== CSlot_Custom_Fields::save( [ 'label' => 'Bad', 'type' => 'evil' ] ) );
[ $dob, $first, $skin ] = array_column( CSlot_Custom_Fields::all(), 'id' );
assert( [ 'Dry', 'Oily' ] === CSlot_Custom_Fields::all()[2]['options'], 'duplicate choices removed' );
assert( 2 === count( CSlot_Custom_Fields::for_service( 1 ) ) && 3 === count( CSlot_Custom_Fields::for_service( 2 ) ) );
assert( is_wp_error( CSlot_Custom_Fields::answers( [ 'custom' => [] ], 1 ) ), 'required missing' );
assert( is_wp_error( CSlot_Custom_Fields::answers( [ 'custom' => [ $dob => '2020-02-31' ] ], 1 ) ), 'invalid date counts as missing' );
assert( [] === CSlot_Custom_Fields::answers( [ 'custom' => [] ], 1, false ), 'admin may skip' );
$ans = CSlot_Custom_Fields::answers( [ 'custom' => [ $dob => '1990-05-01', $first => '1', $skin => 'Hacked' ] ], 2 );
assert( [ 'Date of birth', 'First visit?' ] === array_column( $ans, 'label' ) && 'Yes' === $ans[1]['value'], 'unknown dropdown value dropped' );
$ans = CSlot_Custom_Fields::answers( [ 'custom' => [ $dob => '1990-05-01', $skin => 'Oily' ] ], 2 );
assert( "Date of birth: 1990-05-01\nSkin type: Oily" === CSlot_Custom_Fields::as_text( json_encode( $ans ) ) );
CSlot_Custom_Fields::move( $skin, -1 );
assert( $skin === CSlot_Custom_Fields::all()[1]['id'] );
CSlot_Custom_Fields::delete( $first );
assert( 2 === count( CSlot_Custom_Fields::all() ) );

// Customer manage link: token bound to id + code; changes allowed until the cut-off
$wpdb->rows = [];
$opts['cslot_settings']['change_cutoff_hours'] = 24;
$far = $b->create_booking( [ 'booking_date' => ( new DateTimeImmutable( $monday ) )->modify( '+7 days' )->format( 'Y-m-d' ), 'booking_time' => '10:00' ] + $req );
$row = $wpdb->rows[ $far - 1 ];
$tok = CSlot_Manage::token( $row );
assert( 32 === strlen( $tok ) && CSlot_Manage::verify( $far, $tok ) );
assert( null === CSlot_Manage::verify( $far, strrev( $tok ) ) && null === CSlot_Manage::verify( $far + 1, $tok ), 'wrong token or id' );
assert( CSlot_Manage::can_change( $row ) );
assert( ! CSlot_Manage::can_change( [ 'status' => 'cancelled' ] + $row ) );
$soon = ( new DateTimeImmutable( '+3 hours', wp_timezone() ) );
assert( ! CSlot_Manage::can_change( [ 'booking_date' => $soon->format( 'Y-m-d' ), 'booking_time' => $soon->format( 'H:i:s' ) ] + $row ), 'inside cut-off' );
$opts['cslot_settings']['customer_changes'] = false;
assert( ! CSlot_Manage::can_change( $row ), 'switched off' );
$opts['cslot_settings']['customer_changes'] = true;
assert( ! CSlot_Manage::allowed_start( $soon->format( 'Y-m-d' ), $soon->format( 'H:i' ) ) && CSlot_Manage::allowed_start( $row['booking_date'], '10:00' ), 'new time must be past the cut-off' );

// Pricing: extras per service, coupons (percent/fixed, rules), tax included or added
$services[1]['price'] = 1000;
CSlot_Pricing::save_extra( [ 'name' => 'Hair wash', 'price' => 200, 'services' => [ 1 ] ] );
CSlot_Pricing::save_extra( [ 'name' => 'Other-service extra', 'price' => 50, 'services' => [ 2 ] ] );
[ $wash, $other ] = array_column( CSlot_Pricing::extras(), 'id' );
$opts['cslot_settings'] = [ 'tax_name' => 'GST', 'tax_rate' => 18, 'prices_include_tax' => true ] + $opts['cslot_settings'];
$q = CSlot_Pricing::quote( 1, [ $wash, $other, 'bogus' ], '', $monday );
assert( 1200.0 === $q['subtotal'] && 1 === count( $q['extras'] ) && 1200.0 === $q['total'] && 183.05 === $q['tax'], 'tax included: 1200 - 1200/1.18' );
$opts['cslot_settings']['prices_include_tax'] = false;
$q = CSlot_Pricing::quote( 1, [ $wash ], '', $monday );
assert( 216.0 === $q['tax'] && 1416.0 === $q['total'], 'tax added on top' );
$wpdb->coupons_by_code = [
	'TEN'  => [ 'id' => 1, 'code' => 'TEN', 'type' => 'percent', 'value' => 10, 'services' => '[]', 'valid_from' => null, 'valid_to' => null, 'max_uses' => 0, 'used' => 0, 'status' => 'active' ],
	'BIG'  => [ 'id' => 2, 'code' => 'BIG', 'type' => 'fixed', 'value' => 5000, 'services' => '[]', 'valid_from' => null, 'valid_to' => null, 'max_uses' => 0, 'used' => 0, 'status' => 'active' ],
	'S2'   => [ 'id' => 3, 'code' => 'S2', 'type' => 'fixed', 'value' => 50, 'services' => '[2]', 'valid_from' => null, 'valid_to' => null, 'max_uses' => 0, 'used' => 0, 'status' => 'active' ],
	'OLD'  => [ 'id' => 4, 'code' => 'OLD', 'type' => 'fixed', 'value' => 50, 'services' => '[]', 'valid_from' => null, 'valid_to' => '2020-01-01', 'max_uses' => 0, 'used' => 0, 'status' => 'active' ],
	'GONE' => [ 'id' => 5, 'code' => 'GONE', 'type' => 'fixed', 'value' => 50, 'services' => '[]', 'valid_from' => null, 'valid_to' => null, 'max_uses' => 3, 'used' => 3, 'status' => 'active' ],
];
$q = CSlot_Pricing::quote( 1, [ $wash ], ' ten ', $monday );
assert( 120.0 === $q['discount'] && 194.4 === $q['tax'] && 1274.4 === $q['total'], 'percent coupon, code normalised' );
$q = CSlot_Pricing::quote( 1, [], 'BIG', $monday );
assert( 1000.0 === $q['discount'] && 0.0 === $q['total'], 'discount capped at subtotal' );
foreach ( [ 'S2', 'OLD', 'GONE', 'NOPE' ] as $code ) {
	$q = CSlot_Pricing::quote( 1, [], $code, $monday );
	assert( '' !== $q['coupon_error'] && 0.0 === $q['discount'] && null === $q['coupon'], "coupon $code rejected" );
}
$stored = CSlot_Pricing::to_store( CSlot_Pricing::quote( 1, [ $wash ], 'TEN', $monday ) );
assert( 'TEN' === $stored['coupon_code'] && ! isset( $stored['coupon'] ) );
$lines  = CSlot_Pricing::lines( $stored );
assert( [ 'Service', '+ Hair wash', 'Coupon TEN', 'GST (18%)', 'Total' ] === array_column( $lines, 0 ) && -120.0 === $lines[2][1] );
$opts['cslot_settings']['tax_rate'] = 0;

// Payment status
assert( 'free' === CSlot_Payments::status( 0.0, 0.0 ) && 'free' === CSlot_Payments::status( null, 0.0 ) );
assert( 'unpaid' === CSlot_Payments::status( 63.0, 0.0 ) && 'partial' === CSlot_Payments::status( 63.0, 20.0 ) );
assert( 'paid' === CSlot_Payments::status( 63.0, 63.0 ) && 'paid' === CSlot_Payments::status( 63.0, 62.999 ) && 'overpaid' === CSlot_Payments::status( 63.0, 70.0 ) );
assert( 'unpaid' === CSlot_Payments::status( 63.0, -5.0 ), 'refund only' );

// Setup wizard: industry and staff label settings, presets, and the wizard's input checks
CSlot_Settings::update_settings( [ 'industry' => 'salon', 'staff_label' => str_repeat( 'x', 60 ) ] );
assert( 'salon' === CSlot_Settings::get_settings()['industry'] && 40 === mb_strlen( CSlot_Settings::staff_label() ) );
CSlot_Settings::update_settings( [ 'industry' => 'evil', 'staff_label' => '' ] );
assert( '' === CSlot_Settings::get_settings()['industry'] && 'Staff member' === CSlot_Settings::staff_label() );
foreach ( CSlot_Setup::presets() as $key => $p ) {
	assert( $p['services'] && $p['start'] < $p['end'] && ! array_diff( $p['days'], CSlot_Settings::WEEK_DAYS ), "preset $key" );
}
$ok_setup = [ 'industry' => 'clinic', 'business_name' => 'Clinic', 'business_hours_start' => '09:00', 'business_hours_end' => '18:00', 'work_days' => [ 'Monday' ] ];
assert( 'industry' === CSlot_Setup::run( [ 'industry' => 'nope' ] + $ok_setup )->get_error_code() );
assert( 'business_name' === CSlot_Setup::run( [ 'business_name' => ' ' ] + $ok_setup )->get_error_code() );
assert( 'hours' === CSlot_Setup::run( [ 'business_hours_end' => '08:00' ] + $ok_setup )->get_error_code() );
assert( 'days' === CSlot_Setup::run( [ 'work_days' => [ 'Funday' ] ] + $ok_setup )->get_error_code() );
assert( 'duration' === CSlot_Setup::run( [ 'services' => [ 'clinic0' => [ 'on' => 1, 'name' => 'X', 'duration' => 0 ] ] ] + $ok_setup )->get_error_code() );

echo "all checks passed\n";
