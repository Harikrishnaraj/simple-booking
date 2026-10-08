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
	<?php sb_view( 'admin/views/partials/header', [ 'title' => __( 'Locations', 'bellbook' ), 'theme' => $theme ] ); ?>

	<section class="sb-card">
		<div class="sb-toolbar sb-toolbar--split">
			<p class="sb-muted sb-toolbar__text"><?php esc_html_e( 'Where appointments happen. Assign each staff member to a location on the Staff page. With two or more active locations, the booking form asks customers to choose one first.', 'bellbook' ); ?></p>
			<button type="button" class="sb-button" data-sb-open="sb-location-dialog">
				<span class="dashicons dashicons-plus-alt2" aria-hidden="true"></span><?php esc_html_e( 'Location', 'bellbook' ); ?>
			</button>
		</div>

		<?php if ( $locations ) : ?>
			<div class="sb-table-wrap">
				<table class="sb-table">
					<thead>
						<tr>
							<th scope="col"><?php esc_html_e( 'Name', 'bellbook' ); ?></th>
							<th scope="col"><?php esc_html_e( 'Address', 'bellbook' ); ?></th>
							<th scope="col"><?php esc_html_e( 'Phone', 'bellbook' ); ?></th>
							<th scope="col" class="sb-num"><?php esc_html_e( 'Staff', 'bellbook' ); ?></th>
							<th scope="col"><?php esc_html_e( 'Status', 'bellbook' ); ?></th>
							<th scope="col"><span class="screen-reader-text"><?php esc_html_e( 'Actions', 'bellbook' ); ?></span></th>
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
										data-sb-fill="<?php echo esc_attr( wp_json_encode( array_intersect_key( $l, array_flip( [ 'id', 'name', 'address', 'phone', 'status' ] ) ) ) ); ?>"><?php esc_html_e( 'Edit', 'bellbook' ); ?></button>
									<button type="button" class="sb-button sb-button--danger sb-button--small" data-sb-delete="sb_delete_location" data-id="<?php echo (int) $l['id']; ?>"
										data-sb-confirm="<?php esc_attr_e( 'Delete this location? Its staff become unassigned. Locations with bookings are deactivated instead.', 'bellbook' ); ?>"><?php esc_html_e( 'Delete', 'bellbook' ); ?></button>
								</td>
							</tr>
						<?php endforeach; ?>
					</tbody>
				</table>
			</div>
		<?php else : ?>
			<p class="sb-empty"><?php esc_html_e( 'No locations yet. They are optional: without them, everything happens at your business address.', 'bellbook' ); ?></p>
		<?php endif; ?>
	</section>

	<dialog id="sb-location-dialog" class="sb-dialog" aria-labelledby="sb-location-dialog-title">
		<form data-sb-action="sb_save_location" data-sb-redirect="">
			<div class="sb-dialog__head">
				<h2 id="sb-location-dialog-title" data-new="<?php esc_attr_e( 'Add location', 'bellbook' ); ?>" data-edit="<?php esc_attr_e( 'Edit location', 'bellbook' ); ?>"><?php esc_html_e( 'Add location', 'bellbook' ); ?></h2>
				<button type="button" class="sb-icon-button" data-sb-close aria-label="<?php esc_attr_e( 'Close', 'bellbook' ); ?>"><span class="dashicons dashicons-no-alt" aria-hidden="true"></span></button>
			</div>
			<input type="hidden" name="id" value="0">
			<p class="sb-field">
				<label for="sb-l-name"><?php esc_html_e( 'Name', 'bellbook' ); ?></label>
				<input id="sb-l-name" class="sb-input" type="text" name="name" required maxlength="191">
			</p>
			<p class="sb-field">
				<label for="sb-l-address"><?php esc_html_e( 'Address', 'bellbook' ); ?></label>
				<textarea id="sb-l-address" class="sb-input" name="address" rows="3"></textarea>
			</p>
			<p class="sb-field">
				<label for="sb-l-phone"><?php esc_html_e( 'Phone', 'bellbook' ); ?></label>
				<input id="sb-l-phone" class="sb-input" type="tel" name="phone" maxlength="50">
			</p>
			<p class="sb-field">
				<label for="sb-l-status"><?php esc_html_e( 'Status', 'bellbook' ); ?></label>
				<select id="sb-l-status" class="sb-input" name="status">
					<option value="active"><?php esc_html_e( 'Active', 'bellbook' ); ?></option>
					<option value="inactive"><?php esc_html_e( 'Inactive', 'bellbook' ); ?></option>
				</select>
			</p>
			<div class="sb-dialog__foot">
				<button type="button" class="sb-button sb-button--secondary" data-sb-close><?php esc_html_e( 'Cancel', 'bellbook' ); ?></button>
				<button type="submit" class="sb-button"><?php esc_html_e( 'Save', 'bellbook' ); ?></button>
			</div>
		</form>
	</dialog>
</div>
