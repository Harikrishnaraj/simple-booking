<?php
/**
 * @var array      $staff
 * @var array|null $editing
 * @var int[]      $editing_ids
 * @var array      $all_services
 * @var string     $page_url
 * @var string     $theme
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
$service_names = array_column( $all_services, 'name', 'id' );
$staff_mgr     = new SB_Staff();
?>
<div class="wrap sb-app">
	<?php sb_view( 'admin/views/partials/header', [ 'title' => __( 'Staff', 'simple-booking' ), 'theme' => $theme ] ); ?>

	<div class="sb-split">
		<section class="sb-card sb-split__form">
			<div>
				<h2 class="sb-card__title"><?php echo $editing ? esc_html__( 'Edit Staff Member', 'simple-booking' ) : esc_html__( 'Add Staff Member', 'simple-booking' ); ?></h2>
				<form data-sb-action="sb_save_staff" data-sb-redirect="<?php echo esc_url( $page_url ); ?>">
					<input type="hidden" name="id" value="<?php echo (int) ( $editing['id'] ?? 0 ); ?>">
					<div class="form-field form-required">
						<label for="sb-staff-name"><?php esc_html_e( 'Name', 'simple-booking' ); ?></label>
						<input id="sb-staff-name" name="name" type="text" required maxlength="191" autocomplete="off" value="<?php echo esc_attr( $editing['name'] ?? '' ); ?>">
					</div>
					<div class="form-field form-required">
						<label for="sb-staff-email"><?php esc_html_e( 'Email', 'simple-booking' ); ?></label>
						<input id="sb-staff-email" name="email" type="email" required maxlength="191" spellcheck="false" autocomplete="off" value="<?php echo esc_attr( $editing['email'] ?? '' ); ?>">
					</div>
					<?php $photo = $editing ? $staff_mgr->photo_url( $editing ) : ''; ?>
					<div class="form-field sb-photo-field" data-sb-photo>
						<span class="sb-label" id="sb-staff-photo-label"><?php esc_html_e( 'Photo', 'simple-booking' ); ?></span>
						<input type="hidden" name="photo_id" value="<?php echo (int) ( $editing['photo_id'] ?? 0 ); ?>">
						<div class="sb-photo-field__row">
							<img class="sb-photo-preview" src="<?php echo esc_url( $photo ); ?>" alt="" width="64" height="64" <?php echo $photo ? '' : 'hidden'; ?>>
							<span class="sb-photo-placeholder" aria-hidden="true" <?php echo $photo ? 'hidden' : ''; ?>><span class="dashicons dashicons-admin-users"></span></span>
							<button type="button" class="sb-button sb-button--secondary sb-button--small" data-sb-photo-choose aria-describedby="sb-staff-photo-label"><?php esc_html_e( 'Choose photo', 'simple-booking' ); ?></button>
							<button type="button" class="sb-button sb-button--danger sb-button--small" data-sb-photo-remove <?php echo $photo ? '' : 'hidden'; ?>><?php esc_html_e( 'Remove', 'simple-booking' ); ?></button>
						</div>
						<p class="description"><?php esc_html_e( 'Shown in the admin and next to their name on the booking form. A square image works best.', 'simple-booking' ); ?></p>
					</div>
					<div class="form-field">
						<label for="sb-staff-phone"><?php esc_html_e( 'Phone', 'simple-booking' ); ?></label>
						<input id="sb-staff-phone" name="phone" type="tel" maxlength="50" autocomplete="off" value="<?php echo esc_attr( $editing['phone'] ?? '' ); ?>">
					</div>
					<fieldset class="form-field">
						<legend><?php esc_html_e( 'Services offered', 'simple-booking' ); ?></legend>
						<p class="description"><?php esc_html_e( 'Leave all unticked to offer every service.', 'simple-booking' ); ?></p>
						<?php foreach ( $all_services as $s ) : ?>
							<label>
								<input type="checkbox" name="services[]" value="<?php echo (int) $s['id']; ?>" <?php checked( in_array( (int) $s['id'], $editing_ids, true ) ); ?>>
								<?php echo esc_html( $s['name'] ); ?>
							</label><br>
						<?php endforeach; ?>
					</fieldset>
					<?php sb_view( 'admin/views/partials/staff-schedule', [ 'editing' => $editing ] ); ?>
					<div class="form-field">
						<label for="sb-staff-status"><?php esc_html_e( 'Status', 'simple-booking' ); ?></label>
						<select id="sb-staff-status" name="status">
							<option value="active" <?php selected( $editing['status'] ?? 'active', 'active' ); ?>><?php esc_html_e( 'Active', 'simple-booking' ); ?></option>
							<option value="inactive" <?php selected( $editing['status'] ?? '', 'inactive' ); ?>><?php esc_html_e( 'Inactive', 'simple-booking' ); ?></option>
						</select>
					</div>
					<?php submit_button( $editing ? __( 'Update Staff Member', 'simple-booking' ) : __( 'Add Staff Member', 'simple-booking' ) ); ?>
					<?php if ( $editing ) : ?>
						<a class="sb-link" href="<?php echo esc_url( $page_url ); ?>"><?php esc_html_e( 'Cancel', 'simple-booking' ); ?></a>
					<?php endif; ?>
				</form>
			</div>
		</section>

		<section class="sb-card sb-split__list">
			<div class="sb-table-wrap">
				<table class="sb-table">
					<thead>
						<tr>
							<th scope="col"><?php esc_html_e( 'Name', 'simple-booking' ); ?></th>
							<th scope="col"><?php esc_html_e( 'Contact', 'simple-booking' ); ?></th>
							<th scope="col"><?php esc_html_e( 'Services', 'simple-booking' ); ?></th>
							<th scope="col"><?php esc_html_e( 'Status', 'simple-booking' ); ?></th>
							<th scope="col"><span class="screen-reader-text"><?php esc_html_e( 'Actions', 'simple-booking' ); ?></span></th>
						</tr>
					</thead>
					<tbody>
						<?php if ( ! $staff ) : ?>
							<tr><td colspan="5"><?php esc_html_e( 'No staff yet. Staff are optional: without them, customers book the business as a whole.', 'simple-booking' ); ?></td></tr>
						<?php endif; ?>
						<?php foreach ( $staff as $member ) : ?>
							<?php $ids = $staff_mgr->service_ids( $member ); ?>
							<tr>
								<td>
									<span class="sb-person">
										<?php echo sb_avatar( $member['name'], $staff_mgr->photo_url( $member ) ); // phpcs:ignore WordPress.Security.EscapeOutput -- escaped in sb_avatar() ?>
										<span>
											<strong><?php echo esc_html( $member['name'] ); ?></strong>
											<?php if ( null !== $staff_mgr->schedule( $member ) || $staff_mgr->days_off( $member ) ) : ?>
												<br><span class="sb-muted"><?php echo esc_html( sb_schedule_summary( $member ) ); ?></span>
											<?php endif; ?>
										</span>
									</span>
								</td>
								<td>
									<?php echo esc_html( $member['email'] ); ?>
									<?php if ( ! empty( $member['phone'] ) ) : ?><br><?php echo esc_html( $member['phone'] ); ?><?php endif; ?>
								</td>
								<td>
									<?php
									echo $ids
										? esc_html( implode( ', ', array_filter( array_map( fn( $id ) => $service_names[ $id ] ?? '', $ids ) ) ) )
										: esc_html__( 'All services', 'simple-booking' );
									?>
								</td>
								<td><?php sb_view( 'admin/views/partials/active-badge', [ 'active' => 'active' === $member['status'] ] ); ?></td>
								<td class="sb-actions">
									<a class="sb-button sb-button--ghost sb-button--small" href="<?php echo esc_url( admin_url( 'admin.php?page=sb-calendar&staff=' . (int) $member['id'] ) ); ?>"><?php esc_html_e( 'Calendar', 'simple-booking' ); ?></a>
									<a class="sb-button sb-button--ghost sb-button--small" href="<?php echo esc_url( add_query_arg( 'edit', (int) $member['id'], $page_url ) ); ?>"><?php esc_html_e( 'Edit', 'simple-booking' ); ?></a>
									<button type="button" class="sb-button sb-button--danger sb-button--small" data-sb-delete="sb_delete_staff" data-id="<?php echo (int) $member['id']; ?>"><?php esc_html_e( 'Delete', 'simple-booking' ); ?></button>
								</td>
							</tr>
						<?php endforeach; ?>
					</tbody>
				</table>
			</div>
		</section>
	</div>
</div>
