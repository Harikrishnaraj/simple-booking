<?php
/**
 * @var bool $active
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>
<span class="sb-badge sb-badge--<?php echo $active ? 'active' : 'inactive'; ?>">
	<span class="dashicons dashicons-<?php echo $active ? 'visibility' : 'hidden'; ?>" aria-hidden="true"></span>
	<?php echo $active ? esc_html__( 'Active', 'counterslot' ) : esc_html__( 'Inactive', 'counterslot' ); ?>
</span>
