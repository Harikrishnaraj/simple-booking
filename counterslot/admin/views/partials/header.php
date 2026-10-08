<?php
/**
 * Page title bar shared by every plugin screen.
 *
 * @var string $title
 * @var string $theme 'dark' or 'light'
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
$is_dark = 'dark' === $theme;
?>
<header class="sb-header">
	<h1 class="sb-title"><?php echo esc_html( $title ); ?></h1>
	<button type="button" class="sb-icon-button" data-sb-theme-toggle
		aria-pressed="<?php echo $is_dark ? 'true' : 'false'; ?>"
		aria-label="<?php esc_attr_e( 'Dark theme', 'counterslot' ); ?>"
		title="<?php esc_attr_e( 'Switch between dark and light theme', 'counterslot' ); ?>">
		<span class="dashicons dashicons-<?php echo $is_dark ? 'lightbulb' : 'admin-appearance'; ?>" aria-hidden="true"></span>
	</button>
</header>
<hr class="wp-header-end"><?php // WordPress places admin notices after this marker. ?>
