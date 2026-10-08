<?php
/**
 * @var array      $staff
 * @var array|null $editing
 * @var int[]      $editing_ids
 * @var array      $all_services
 * @var array      $locations
 * @var string     $page_url
 * @var string     $theme
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
// phpcs:disable WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound, WordPress.WP.GlobalVariablesOverride.Prohibited -- included inside cslot_view(), so these are local variables.
$service_names  = array_column( $all_services, 'name', 'id' );
$location_names = array_column( $locations, 'name', 'id' );
$staff_mgr     = new CSlot_Staff();
?>
<div class="wrap sb-app">
	<?php cslot_view( 'admin/views/partials/header', [ 'title' => __( 'Staff', 'counterslot' ), 'theme' => $theme ] ); ?>

	<div class="sb-split">
		<section class="sb-card sb-split__form">
			<div>
				<h2 class="sb-card__title"><?php echo $editing ? esc_html__( 'Edit Staff Member', 'counterslot' ) : esc_html__( 'Add Staff Member', 'counterslot' ); ?></h2>
				<form data-sb-action="cslot_save_staff" data-sb-redirect="<?php echo esc_url( $page_url ); ?>">
					<input type="hidden" name="id" value="<?php echo (int) ( $editing['id'] ?? 0 ); ?>">
					<div class="form-field form-required">
						<label for="sb-staff-name"><?php esc_html_e( 'Name', 'counterslot' ); ?></label>
						<input id="sb-staff-name" name="name" type="text" required maxlength="191" autocomplete="off" value="<?php echo esc_attr( $editing['name'] ?? '' ); ?>">
					</div>
					<div class="form-field form-required">
						<label for="sb-staff-email"><?php esc_html_e( 'Email', 'counterslot' ); ?></label>
						<input id="sb-staff-email" name="email" type="email" required maxlength="191" spellcheck="false" autocomplete="off" value="<?php echo esc_attr( $editing['email'] ?? '' ); ?>">
					</div>
					<?php $photo = $editing ? $staff_mgr->photo_url( $editing ) : ''; ?>
					<div class="form-field sb-photo-field" data-sb-photo>
						<span class="sb-label" id="sb-staff-photo-label"><?php esc_html_e( 'Photo', 'counterslot' ); ?></span>
						<input type="hidden" name="photo_id" value="<?php echo (int) ( $editing['photo_id'] ?? 0 ); ?>">
						<div class="sb-photo-field__row">
							<img class="sb-photo-preview" src="<?php echo esc_url( $photo ); ?>" alt="" width="64" height="64" <?php echo $photo ? '' : 'hidden'; ?>>
							<span class="sb-photo-placeholder" aria-hidden="true" <?php echo $photo ? 'hidden' : ''; ?>><span class="dashicons dashicons-admin-users"></span></span>
							<button type="button" class="sb-button sb-button--secondary sb-button--small" data-sb-photo-choose aria-describedby="sb-staff-photo-label"><?php esc_html_e( 'Choose photo', 'counterslot' ); ?></button>
							<button type="button" class="sb-button sb-button--danger sb-button--small" data-sb-photo-remove <?php echo $photo ? '' : 'hidden'; ?>><?php esc_html_e( 'Remove', 'counterslot' ); ?></button>
						</div>
						<p class="description"><?php esc_html_e( 'Shown in the admin and next to their name on the booking form. A square image works best.', 'counterslot' ); ?></p>
					</div>
					<div class="form-field">
						<label for="sb-staff-phone"><?php esc_html_e( 'Phone', 'counterslot' ); ?></label>
						<input id="sb-staff-phone" name="phone" type="tel" maxlength="50" autocomplete="off" value="<?php echo esc_attr( $editing['phone'] ?? '' ); ?>">
					</div>
					<fieldset class="form-field">
						<legend><?php esc_html_e( 'Services offered', 'counterslot' ); ?></legend>
						<p class="description"><?php esc_html_e( 'Leave all unticked to offer every service.', 'counterslot' ); ?></p>
						<?php foreach ( $all_services as $s ) : ?>
							<label>
								<input type="checkbox" name="services[]" value="<?php echo (int) $s['id']; ?>" <?php checked( in_array( (int) $s['id'], $editing_ids, true ) ); ?>>
								<?php echo esc_html( $s['name'] ); ?>
							</label><br>
						<?php endforeach; ?>
					</fieldset>
					<?php if ( $locations ) : ?>
						<div class="form-field">
							<label for="sb-staff-location"><?php esc_html_e( 'Location', 'counterslot' ); ?></label>
							<select id="sb-staff-location" name="location_id">
								<option value="0"><?php esc_html_e( 'None', 'counterslot' ); ?></option>
								<?php foreach ( $locations as $location ) : ?>
									<option value="<?php echo (int) $location['id']; ?>" <?php selected( (int) ( $editing['location_id'] ?? 0 ), (int) $location['id'] ); ?>><?php echo esc_html( $location['name'] ); ?></option>
								<?php endforeach; ?>
							</select>
							<p class="description"><?php esc_html_e( 'With two or more locations, customers pick one first and only see staff working there.', 'counterslot' ); ?></p>
						</div>
					<?php endif; ?>
					<?php cslot_view( 'admin/views/partials/staff-schedule', [ 'editing' => $editing ] ); ?>
					<div class="form-field">
						<label for="sb-staff-status"><?php esc_html_e( 'Status', 'counterslot' ); ?></label>
						<select id="sb-staff-status" name="status">
							<option value="active" <?php selected( $editing['status'] ?? 'active', 'active' ); ?>><?php esc_html_e( 'Active', 'counterslot' ); ?></option>
							<option value="inactive" <?php selected( $editing['status'] ?? '', 'inactive' ); ?>><?php esc_html_e( 'Inactive', 'counterslot' ); ?></option>
						</select>
					</div>
					<?php submit_button( $editing ? __( 'Update Staff Member', 'counterslot' ) : __( 'Add Staff Member', 'counterslot' ) ); ?>
					<?php if ( $editing ) : ?>
						<a class="sb-link" href="<?php echo esc_url( $page_url ); ?>"><?php esc_html_e( 'Cancel', 'counterslot' ); ?></a>
					<?php endif; ?>
				</form>
			</div>
		</section>

		<section class="sb-card sb-split__list">
			<div class="sb-table-wrap">
				<table class="sb-table">
					<thead>
						<tr>
							<th scope="col"><?php esc_html_e( 'Name', 'counterslot' ); ?></th>
							<th scope="col"><?php esc_html_e( 'Contact', 'counterslot' ); ?></th>
							<th scope="col"><?php esc_html_e( 'Services', 'counterslot' ); ?></th>
							<th scope="col"><?php esc_html_e( 'Status', 'counterslot' ); ?></th>
							<th scope="col"><span class="screen-reader-text"><?php esc_html_e( 'Actions', 'counterslot' ); ?></span></th>
						</tr>
					</thead>
					<tbody>
						<?php if ( ! $staff ) : ?>
							<tr><td colspan="5"><?php esc_html_e( 'No staff yet. Staff are optional: without them, customers book the business as a whole.', 'counterslot' ); ?></td></tr>
						<?php endif; ?>
						<?php foreach ( $staff as $member ) : ?>
							<?php $ids = $staff_mgr->service_ids( $member ); ?>
							<tr>
								<td>
									<span class="sb-person">
										<?php echo cslot_avatar( $member['name'], $staff_mgr->photo_url( $member ) ); // phpcs:ignore WordPress.Security.EscapeOutput -- escaped in cslot_avatar() ?>
										<span>
											<strong><?php echo esc_html( $member['name'] ); ?></strong>
											<?php if ( null !== $staff_mgr->schedule( $member ) || $staff_mgr->days_off( $member ) ) : ?>
												<br><span class="sb-muted"><?php echo esc_html( cslot_schedule_summary( $member ) ); ?></span>
											<?php endif; ?>
										</span>
									</span>
								</td>
								<td>
									<?php echo esc_html( $member['email'] ); ?>
									<?php if ( ! empty( $member['phone'] ) ) : ?><br><?php echo esc_html( $member['phone'] ); ?><?php endif; ?>
									<?php if ( isset( $location_names[ $member['location_id'] ?? 0 ] ) ) : ?>
										<br><span class="sb-muted"><span class="dashicons dashicons-location" aria-hidden="true"></span><?php echo esc_html( $location_names[ $member['location_id'] ] ); ?></span>
									<?php endif; ?>
								</td>
								<td>
									<?php
									echo $ids
										? esc_html( implode( ', ', array_filter( array_map( fn( $id ) => $service_names[ $id ] ?? '', $ids ) ) ) )
										: esc_html__( 'All services', 'counterslot' );
									?>
								</td>
								<td><?php cslot_view( 'admin/views/partials/active-badge', [ 'active' => 'active' === $member['status'] ] ); ?></td>
								<td class="sb-actions">
									<a class="sb-button sb-button--ghost sb-button--small" href="<?php echo esc_url( admin_url( 'admin.php?page=sb-calendar&staff=' . (int) $member['id'] ) ); ?>"><?php esc_html_e( 'Calendar', 'counterslot' ); ?></a>
									<a class="sb-button sb-button--ghost sb-button--small" href="<?php echo esc_url( add_query_arg( 'edit', (int) $member['id'], $page_url ) ); ?>"><?php esc_html_e( 'Edit', 'counterslot' ); ?></a>
									<button type="button" class="sb-button sb-button--danger sb-button--small" data-sb-delete="cslot_delete_staff" data-id="<?php echo (int) $member['id']; ?>"><?php esc_html_e( 'Delete', 'counterslot' ); ?></button>
								</td>
							</tr>
						<?php endforeach; ?>
					</tbody>
				</table>
			</div>
		</section>
	</div>
</div>
