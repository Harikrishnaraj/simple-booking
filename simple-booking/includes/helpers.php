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
