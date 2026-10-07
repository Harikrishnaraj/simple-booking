<?php
/**
 * @var array  $locations   All locations
 * @var array  $staff_count location id => number of staff
 * @var string $theme
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>
<div class="wrap sb-app">
	<?php sb_view( 'admin/views/partials/header', [ 'title' => __( 'Locations', 'simple-booking' ), 'theme' => $theme ] ); ?>

	<section class="sb-card">
		<div class="sb-toolbar sb-toolbar--split">
			<p class="sb-muted sb-toolbar__text"><?php esc_html_e( 'Where appointments happen. Assign each staff member to a location on the Staff page. With two or more active locations, the booking form asks customers to choose one first.', 'simple-booking' ); ?></p>
			<button type="button" class="sb-button" data-sb-open="sb-location-dialog">
				<span class="dashicons dashicons-plus-alt2" aria-hidden="true"></span><?php esc_html_e( 'Location', 'simple-booking' ); ?>
			</button>
		</div>

		<?php if ( $locations ) : ?>
			<div class="sb-table-wrap">
				<table class="sb-table">
					<thead>
						<tr>
							<th scope="col"><?php esc_html_e( 'Name', 'simple-booking' ); ?></th>
							<th scope="col"><?php esc_html_e( 'Address', 'simple-booking' ); ?></th>
							<th scope="col"><?php esc_html_e( 'Phone', 'simple-booking' ); ?></th>
							<th scope="col" class="sb-num"><?php esc_html_e( 'Staff', 'simple-booking' ); ?></th>
							<th scope="col"><?php esc_html_e( 'Status', 'simple-booking' ); ?></th>
							<th scope="col"><span class="screen-reader-text"><?php esc_html_e( 'Actions', 'simple-booking' ); ?></span></th>
						</tr>
					</thead>
					<tbody>
						<?php foreach ( $locations as $l ) : ?>
							<tr>
								<td><strong><?php echo esc_html( $l['name'] ); ?></strong></td>
								<td class="sb-note"><?php echo nl2br( esc_html( $l['address'] ) ); ?></td>
								<td><?php echo $l['phone'] ? esc_html( $l['phone'] ) : '<span class="sb-muted">—</span>'; ?></td>
								<td class="sb-num"><?php echo (int) ( $staff_count[ $l['id'] ] ?? 0 ); ?></td>
								<td><?php sb_view( 'admin/views/partials/active-badge', [ 'active' => 'active' === $l['status'] ] ); ?></td>
								<td class="sb-actions">
									<button type="button" class="sb-button sb-button--ghost sb-button--small" data-sb-open="sb-location-dialog"
										data-sb-fill="<?php echo esc_attr( wp_json_encode( array_intersect_key( $l, array_flip( [ 'id', 'name', 'address', 'phone', 'status' ] ) ) ) ); ?>"><?php esc_html_e( 'Edit', 'simple-booking' ); ?></button>
									<button type="button" class="sb-button sb-button--danger sb-button--small" data-sb-delete="sb_delete_location" data-id="<?php echo (int) $l['id']; ?>"
										data-sb-confirm="<?php esc_attr_e( 'Delete this location? Its staff become unassigned. Locations with bookings are deactivated instead.', 'simple-booking' ); ?>"><?php esc_html_e( 'Delete', 'simple-booking' ); ?></button>
								</td>
							</tr>
						<?php endforeach; ?>
					</tbody>
				</table>
			</div>
		<?php else : ?>
			<p class="sb-empty"><?php esc_html_e( 'No locations yet. They are optional: without them, everything happens at your business address.', 'simple-booking' ); ?></p>
		<?php endif; ?>
	</section>

	<dialog id="sb-location-dialog" class="sb-dialog" aria-labelledby="sb-location-dialog-title">
		<form data-sb-action="sb_save_location" data-sb-redirect="">
			<div class="sb-dialog__head">
				<h2 id="sb-location-dialog-title" data-new="<?php esc_attr_e( 'Add location', 'simple-booking' ); ?>" data-edit="<?php esc_attr_e( 'Edit location', 'simple-booking' ); ?>"><?php esc_html_e( 'Add location', 'simple-booking' ); ?></h2>
				<button type="button" class="sb-icon-button" data-sb-close aria-label="<?php esc_attr_e( 'Close', 'simple-booking' ); ?>"><span class="dashicons dashicons-no-alt" aria-hidden="true"></span></button>
			</div>
			<input type="hidden" name="id" value="0">
			<p class="sb-field">
				<label for="sb-l-name"><?php esc_html_e( 'Name', 'simple-booking' ); ?></label>
				<input id="sb-l-name" class="sb-input" type="text" name="name" required maxlength="191">
			</p>
			<p class="sb-field">
				<label for="sb-l-address"><?php esc_html_e( 'Address', 'simple-booking' ); ?></label>
				<textarea id="sb-l-address" class="sb-input" name="address" rows="3"></textarea>
			</p>
			<p class="sb-field">
				<label for="sb-l-phone"><?php esc_html_e( 'Phone', 'simple-booking' ); ?></label>
				<input id="sb-l-phone" class="sb-input" type="tel" name="phone" maxlength="50">
			</p>
			<p class="sb-field">
				<label for="sb-l-status"><?php esc_html_e( 'Status', 'simple-booking' ); ?></label>
				<select id="sb-l-status" class="sb-input" name="status">
					<option value="active"><?php esc_html_e( 'Active', 'simple-booking' ); ?></option>
					<option value="inactive"><?php esc_html_e( 'Inactive', 'simple-booking' ); ?></option>
				</select>
			</p>
			<div class="sb-dialog__foot">
				<button type="button" class="sb-button sb-button--secondary" data-sb-close><?php esc_html_e( 'Cancel', 'simple-booking' ); ?></button>
				<button type="submit" class="sb-button"><?php esc_html_e( 'Save', 'simple-booking' ); ?></button>
			</div>
		</form>
	</dialog>
</div>
