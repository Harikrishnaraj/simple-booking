<?php
/**
 * @var int $page
 * @var int $pages
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
// phpcs:disable WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound, WordPress.WP.GlobalVariablesOverride.Prohibited -- included inside cslot_view(), so these are local variables.
if ( $pages < 2 ) {
	return;
}
?>
<nav class="sb-pagination" aria-label="<?php esc_attr_e( 'Pages', 'counterslot' ); ?>">
	<?php
	echo wp_kses_post( paginate_links( [
		'base'    => add_query_arg( 'paged', '%#%' ),
		'format'  => '',
		'current' => $page,
		'total'   => $pages,
	] ) );
	?>
</nav>
