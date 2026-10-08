<?php
/**
 * @var string $tab      extras | coupons | tax
 * @var array  $extras   SB_Pricing::extras()
 * @var array  $coupons  SB_Pricing::coupons()
 * @var array  $services All services
 * @var array  $settings
 * @var string $theme
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
$page_url      = admin_url( 'admin.php?page=sb-pricing' );
$service_names = array_column( $services, 'name', 'id' );
$names_for     = static fn( array $ids ) => $ids ? implode( ', ', array_filter( array_map( fn( $id ) => $service_names[ $id ] ?? '', $ids ) ) ) : __( 'All services', 'bellbook' );
$tabs          = [
	'extras'  => __( 'Extras', 'bellbook' ),
	'coupons' => __( 'Coupons', 'bellbook' ),
	'tax'     => __( 'Tax', 'bellbook' ),
];
$service_boxes = static function () use ( $services ): void {
	?>
	<fieldset class="sb-field">
		<legend class="sb-label"><?php esc_html_e( 'Services', 'bellbook' ); ?></legend>
		<p class="sb-hint"><?php esc_html_e( 'Leave all unticked for every service.', 'bellbook' ); ?></p>
		<?php foreach ( $services as $s ) : ?>
			<label class="sb-check-label"><input type="checkbox" name="services[]" value="<?php echo (int) $s['id']; ?>"> <?php echo esc_html( $s['name'] ); ?></label>
		<?php endforeach; ?>
	</fieldset>
	<?php
};
?>
<div class="wrap sb-app">
	<?php sb_view( 'admin/views/partials/header', [ 'title' => __( 'Pricing', 'bellbook' ), 'theme' => $theme ] ); ?>

	<nav class="sb-tabs" aria-label="<?php esc_attr_e( 'Pricing sections', 'bellbook' ); ?>">
		<?php foreach ( $tabs as $key => $label ) : ?>
			<a class="sb-tab <?php echo $tab === $key ? 'is-current' : ''; ?>" href="<?php echo esc_url( add_query_arg( 'tab', $key, $page_url ) ); ?>" <?php echo $tab === $key ? 'aria-current="page"' : ''; ?>><?php echo esc_html( $label ); ?></a>
		<?php endforeach; ?>
	</nav>

	<section class="sb-card">
	<?php if ( 'extras' === $tab ) : ?>
		<div class="sb-toolbar sb-toolbar--split">
			<p class="sb-muted sb-toolbar__text"><?php esc_html_e( 'Paid add-ons customers can tick when booking, e.g. "Hair wash" or "Take-home kit".', 'bellbook' ); ?></p>
			<button type="button" class="sb-button" data-sb-open="sb-extra-dialog"><span class="dashicons dashicons-plus-alt2" aria-hidden="true"></span><?php esc_html_e( 'Extra', 'bellbook' ); ?></button>
		</div>
		<?php if ( $extras ) : ?>
			<div class="sb-table-wrap">
				<table class="sb-table">
					<thead><tr>
						<th scope="col"><?php esc_html_e( 'Name', 'bellbook' ); ?></th>
						<th scope="col" class="sb-num"><?php esc_html_e( 'Price', 'bellbook' ); ?></th>
						<th scope="col"><?php esc_html_e( 'Offered with', 'bellbook' ); ?></th>
						<th scope="col"><span class="screen-reader-text"><?php esc_html_e( 'Actions', 'bellbook' ); ?></span></th>
					</tr></thead>
					<tbody>
						<?php foreach ( $extras as $e ) : ?>
							<tr>
								<td><strong><?php echo esc_html( $e['name'] ); ?></strong></td>
								<td class="sb-num"><?php echo esc_html( sb_price( $e['price'] ) ); ?></td>
								<td><?php echo esc_html( $names_for( $e['services'] ) ); ?></td>
								<td class="sb-actions">
									<button type="button" class="sb-button sb-button--ghost sb-button--small" data-sb-open="sb-extra-dialog" data-sb-fill="<?php echo esc_attr( wp_json_encode( $e ) ); ?>"><?php esc_html_e( 'Edit', 'bellbook' ); ?></button>
									<button type="button" class="sb-button sb-button--danger sb-button--small" data-sb-delete="sb_delete_extra" data-id="<?php echo esc_attr( $e['id'] ); ?>" data-sb-confirm="<?php esc_attr_e( 'Delete this extra? Past bookings keep what they paid for.', 'bellbook' ); ?>"><?php esc_html_e( 'Delete', 'bellbook' ); ?></button>
								</td>
							</tr>
						<?php endforeach; ?>
					</tbody>
				</table>
			</div>
		<?php else : ?>
			<p class="sb-empty"><?php esc_html_e( 'No extras yet.', 'bellbook' ); ?></p>
		<?php endif; ?>

		<dialog id="sb-extra-dialog" class="sb-dialog" aria-labelledby="sb-extra-dialog-title">
			<form data-sb-action="sb_save_extra" data-sb-redirect="">
				<div class="sb-dialog__head">
					<h2 id="sb-extra-dialog-title" data-new="<?php esc_attr_e( 'Add extra', 'bellbook' ); ?>" data-edit="<?php esc_attr_e( 'Edit extra', 'bellbook' ); ?>"><?php esc_html_e( 'Add extra', 'bellbook' ); ?></h2>
					<button type="button" class="sb-icon-button" data-sb-close aria-label="<?php esc_attr_e( 'Close', 'bellbook' ); ?>"><span class="dashicons dashicons-no-alt" aria-hidden="true"></span></button>
				</div>
				<input type="hidden" name="id" value="">
				<p class="sb-field"><label for="sb-x-name"><?php esc_html_e( 'Name', 'bellbook' ); ?></label><input id="sb-x-name" class="sb-input" type="text" name="name" required maxlength="191"></p>
				<p class="sb-field"><label for="sb-x-price"><?php esc_html_e( 'Price', 'bellbook' ); ?></label><input id="sb-x-price" class="sb-input sb-input--short" type="number" name="price" min="0" step="0.01" required value="0"></p>
				<?php $service_boxes(); ?>
				<div class="sb-dialog__foot">
					<button type="button" class="sb-button sb-button--secondary" data-sb-close><?php esc_html_e( 'Cancel', 'bellbook' ); ?></button>
					<button type="submit" class="sb-button"><?php esc_html_e( 'Save', 'bellbook' ); ?></button>
				</div>
			</form>
		</dialog>

	<?php elseif ( 'coupons' === $tab ) : ?>
		<div class="sb-toolbar sb-toolbar--split">
			<p class="sb-muted sb-toolbar__text"><?php esc_html_e( 'Codes customers enter on the booking form for a discount.', 'bellbook' ); ?></p>
			<button type="button" class="sb-button" data-sb-open="sb-coupon-dialog"><span class="dashicons dashicons-plus-alt2" aria-hidden="true"></span><?php esc_html_e( 'Coupon', 'bellbook' ); ?></button>
		</div>
		<?php if ( $coupons ) : ?>
			<div class="sb-table-wrap">
				<table class="sb-table">
					<thead><tr>
						<th scope="col"><?php esc_html_e( 'Code', 'bellbook' ); ?></th>
						<th scope="col"><?php esc_html_e( 'Discount', 'bellbook' ); ?></th>
						<th scope="col"><?php esc_html_e( 'Valid', 'bellbook' ); ?></th>
						<th scope="col"><?php esc_html_e( 'Used', 'bellbook' ); ?></th>
						<th scope="col"><?php esc_html_e( 'Services', 'bellbook' ); ?></th>
						<th scope="col"><?php esc_html_e( 'Status', 'bellbook' ); ?></th>
						<th scope="col"><span class="screen-reader-text"><?php esc_html_e( 'Actions', 'bellbook' ); ?></span></th>
					</tr></thead>
					<tbody>
						<?php foreach ( $coupons as $c ) : ?>
							<?php $c_services = array_map( 'intval', (array) json_decode( (string) $c['services'], true ) ); ?>
							<tr>
								<td><code translate="no"><?php echo esc_html( $c['code'] ); ?></code></td>
								<td><?php echo esc_html( 'fixed' === $c['type'] ? sb_price( $c['value'] ) : (float) $c['value'] . '%' ); ?></td>
								<td>
									<?php
									$fmt = static fn( $d ) => $d ? mysql2date( get_option( 'date_format' ), $d ) : '…';
									echo $c['valid_from'] || $c['valid_to'] ? esc_html( $fmt( $c['valid_from'] ) . ' – ' . $fmt( $c['valid_to'] ) ) : '<span class="sb-muted">' . esc_html__( 'Always', 'bellbook' ) . '</span>';
									?>
								</td>
								<td><?php echo esc_html( (int) $c['used'] . ( $c['max_uses'] ? ' / ' . (int) $c['max_uses'] : '' ) ); ?></td>
								<td><?php echo esc_html( $names_for( $c_services ) ); ?></td>
								<td><?php sb_view( 'admin/views/partials/active-badge', [ 'active' => 'active' === $c['status'] ] ); ?></td>
								<td class="sb-actions">
									<button type="button" class="sb-button sb-button--ghost sb-button--small" data-sb-open="sb-coupon-dialog"
										data-sb-fill="<?php echo esc_attr( wp_json_encode( [ 'id' => (int) $c['id'], 'code' => $c['code'], 'type' => $c['type'], 'value' => (float) $c['value'], 'valid_from' => (string) $c['valid_from'], 'valid_to' => (string) $c['valid_to'], 'max_uses' => (int) $c['max_uses'], 'status' => $c['status'], 'services' => $c_services ] ) ); ?>"><?php esc_html_e( 'Edit', 'bellbook' ); ?></button>
									<button type="button" class="sb-button sb-button--danger sb-button--small" data-sb-delete="sb_delete_coupon" data-id="<?php echo (int) $c['id']; ?>" data-sb-confirm="<?php esc_attr_e( 'Delete this coupon? Bookings that used it keep their discount.', 'bellbook' ); ?>"><?php esc_html_e( 'Delete', 'bellbook' ); ?></button>
								</td>
							</tr>
						<?php endforeach; ?>
					</tbody>
				</table>
			</div>
		<?php else : ?>
			<p class="sb-empty"><?php esc_html_e( 'No coupons yet.', 'bellbook' ); ?></p>
		<?php endif; ?>

		<dialog id="sb-coupon-dialog" class="sb-dialog" aria-labelledby="sb-coupon-dialog-title">
			<form data-sb-action="sb_save_coupon" data-sb-redirect="">
				<div class="sb-dialog__head">
					<h2 id="sb-coupon-dialog-title" data-new="<?php esc_attr_e( 'Add coupon', 'bellbook' ); ?>" data-edit="<?php esc_attr_e( 'Edit coupon', 'bellbook' ); ?>"><?php esc_html_e( 'Add coupon', 'bellbook' ); ?></h2>
					<button type="button" class="sb-icon-button" data-sb-close aria-label="<?php esc_attr_e( 'Close', 'bellbook' ); ?>"><span class="dashicons dashicons-no-alt" aria-hidden="true"></span></button>
				</div>
				<input type="hidden" name="id" value="0">
				<p class="sb-field"><label for="sb-c-code"><?php esc_html_e( 'Code', 'bellbook' ); ?></label><input id="sb-c-code" class="sb-input" type="text" name="code" required maxlength="50" autocomplete="off" spellcheck="false"></p>
				<div class="sb-field-row">
					<p class="sb-field"><label for="sb-c-type"><?php esc_html_e( 'Type', 'bellbook' ); ?></label>
						<select id="sb-c-type" class="sb-input sb-input--wide" name="type">
							<option value="percent"><?php esc_html_e( 'Percentage', 'bellbook' ); ?></option>
							<option value="fixed"><?php esc_html_e( 'Fixed amount', 'bellbook' ); ?></option>
						</select></p>
					<p class="sb-field"><label for="sb-c-value"><?php esc_html_e( 'Discount', 'bellbook' ); ?></label><input id="sb-c-value" class="sb-input sb-input--wide" type="number" name="value" min="0.01" step="0.01" required></p>
				</div>
				<div class="sb-field-row">
					<p class="sb-field"><label for="sb-c-from"><?php esc_html_e( 'Valid from (optional)', 'bellbook' ); ?></label><input id="sb-c-from" class="sb-input sb-input--wide" type="date" name="valid_from"></p>
					<p class="sb-field"><label for="sb-c-to"><?php esc_html_e( 'Valid until (optional)', 'bellbook' ); ?></label><input id="sb-c-to" class="sb-input sb-input--wide" type="date" name="valid_to"></p>
				</div>
				<p class="sb-hint sb-field-hint"><?php esc_html_e( 'Dates are the appointment date, not the day of booking.', 'bellbook' ); ?></p>
				<div class="sb-field-row">
					<p class="sb-field"><label for="sb-c-max"><?php esc_html_e( 'Max uses (0 = unlimited)', 'bellbook' ); ?></label><input id="sb-c-max" class="sb-input sb-input--wide" type="number" name="max_uses" min="0" step="1" value="0"></p>
					<p class="sb-field"><label for="sb-c-status"><?php esc_html_e( 'Status', 'bellbook' ); ?></label>
						<select id="sb-c-status" class="sb-input sb-input--wide" name="status">
							<option value="active"><?php esc_html_e( 'Active', 'bellbook' ); ?></option>
							<option value="inactive"><?php esc_html_e( 'Inactive', 'bellbook' ); ?></option>
						</select></p>
				</div>
				<?php $service_boxes(); ?>
				<div class="sb-dialog__foot">
					<button type="button" class="sb-button sb-button--secondary" data-sb-close><?php esc_html_e( 'Cancel', 'bellbook' ); ?></button>
					<button type="submit" class="sb-button"><?php esc_html_e( 'Save', 'bellbook' ); ?></button>
				</div>
			</form>
		</dialog>

	<?php else : ?>
		<form data-sb-action="sb_save_settings" class="sb-narrow">
			<p class="sb-field">
				<label for="sb-tax-name"><?php esc_html_e( 'Tax name', 'bellbook' ); ?></label>
				<input id="sb-tax-name" class="sb-input" type="text" name="settings[tax_name]" maxlength="50" placeholder="<?php esc_attr_e( 'e.g. GST', 'bellbook' ); ?>" value="<?php echo esc_attr( $settings['tax_name'] ); ?>">
			</p>
			<p class="sb-field">
				<label for="sb-tax-rate"><?php esc_html_e( 'Tax rate (%)', 'bellbook' ); ?></label>
				<input id="sb-tax-rate" class="sb-input sb-input--short" type="number" name="settings[tax_rate]" min="0" max="100" step="0.001" value="<?php echo esc_attr( (float) $settings['tax_rate'] ); ?>">
				<span class="sb-hint"><?php esc_html_e( '0 for no tax.', 'bellbook' ); ?></span>
			</p>
			<fieldset class="sb-field">
				<legend class="sb-label"><?php esc_html_e( 'Your prices', 'bellbook' ); ?></legend>
				<label class="sb-check-label"><input type="radio" name="settings[prices_include_tax]" value="1" <?php checked( ! empty( $settings['prices_include_tax'] ) ); ?>> <?php esc_html_e( 'already include tax (tax is shown as part of the total)', 'bellbook' ); ?></label>
				<label class="sb-check-label"><input type="radio" name="settings[prices_include_tax]" value="0" <?php checked( empty( $settings['prices_include_tax'] ) ); ?>> <?php esc_html_e( 'exclude tax (tax is added on top)', 'bellbook' ); ?></label>
			</fieldset>
			<button type="submit" class="sb-button"><?php esc_html_e( 'Save', 'bellbook' ); ?></button>
		</form>
	<?php endif; ?>
	</section>
</div>
