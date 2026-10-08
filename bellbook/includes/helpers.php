<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Render a view file relative to the plugin root, e.g. 'admin/views/services'.
 * Only ever called with hard-coded view names.
 */
function sb_view( string $view, array $vars = [] ): void {
	extract( $vars, EXTR_SKIP ); // phpcs:ignore WordPress.PHP.DontExtract
	include SB_PLUGIN_DIR . $view . '.php';
}

function sb_price( $amount ): string {
	$amount = (float) $amount;
	// Minus sign before the currency symbol: −₹7.00, not ₹-7.00.
	return ( $amount < 0 ? '−' : '' ) . SB_Settings::get_settings()['currency_symbol'] . number_format_i18n( abs( $amount ), 2 );
}

/**
 * Round avatar: the photo when there is one, otherwise the first letter of the name.
 */
function sb_avatar( string $name, string $photo_url = '' ): string {
	if ( $photo_url ) {
		return '<img class="sb-avatar" src="' . esc_url( $photo_url ) . '" alt="" width="30" height="30" loading="lazy">';
	}
	return '<span class="sb-avatar" aria-hidden="true">' . esc_html( mb_strtoupper( mb_substr( $name, 0, 1 ) ) ) . '</span>';
}

/**
 * One line about a staff member's hours for the staff list, e.g. "Mon, Wed 10:00–14:00 · Off 3 Nov".
 */
function sb_schedule_summary( array $member ): string {
	global $wp_locale;
	$staff_mgr = new SB_Staff();
	$parts     = [];

	$schedule = $staff_mgr->schedule( $member );
	if ( null !== $schedule ) {
		// Group days that share the same hours.
		$groups = [];
		foreach ( $schedule as $day => $range ) {
			$groups[ implode( '–', $range ) ][] = $wp_locale->get_weekday_abbrev( $wp_locale->get_weekday( (int) gmdate( 'w', strtotime( $day ) ) ) );
		}
		foreach ( $groups as $hours => $days ) {
			$parts[] = implode( ', ', $days ) . ' ' . $hours;
		}
		if ( ! $schedule ) {
			$parts[] = __( 'No working days', 'bellbook' );
		}
	}

	$today    = wp_date( 'Y-m-d' );
	$upcoming = array_filter( $staff_mgr->days_off( $member ), fn( $off ) => $off['to'] >= $today );
	if ( $upcoming ) {
		$next    = reset( $upcoming );
		$format  = static fn( $d ) => wp_date( 'j M', strtotime( $d . ' 12:00' ) );
		$parts[] = sprintf(
			/* translators: %s: a date or date range */
			__( 'Off %s', 'bellbook' ),
			$next['from'] === $next['to'] ? $format( $next['from'] ) : $format( $next['from'] ) . '–' . $format( $next['to'] )
		);
	}
	return implode( ' · ', $parts );
}
