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
	return SB_Settings::get_settings()['currency_symbol'] . number_format_i18n( (float) $amount, 2 );
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
