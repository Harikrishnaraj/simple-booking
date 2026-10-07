<?php
/**
 * @var int $page
 * @var int $pages
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
if ( $pages < 2 ) {
	return;
}
?>
<nav class="sb-pagination" aria-label="<?php esc_attr_e( 'Pages', 'simple-booking' ); ?>">
	<?php
	echo wp_kses_post( paginate_links( [
		'base'    => add_query_arg( 'paged', '%#%' ),
		'format'  => '',
		'current' => $page,
		'total'   => $pages,
	] ) );
	?>
</nav>
