<?php
/**
 * @var array      $services
 * @var array|null $editing
 * @var string     $page_url
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>
<div class="wrap">
	<h1><?php esc_html_e( 'Services', 'simple-booking' ); ?></h1>

	<div id="col-container" class="wp-clearfix">
		<div id="col-left">
			<div class="col-wrap">
				<h2><?php echo $editing ? esc_html__( 'Edit Service', 'simple-booking' ) : esc_html__( 'Add Service', 'simple-booking' ); ?></h2>
				<form data-sb-action="sb_save_service" data-sb-redirect="<?php echo esc_url( $page_url ); ?>">
					<input type="hidden" name="id" value="<?php echo (int) ( $editing['id'] ?? 0 ); ?>">
					<div class="form-field form-required">
						<label for="sb-service-name"><?php esc_html_e( 'Name', 'simple-booking' ); ?></label>
						<input id="sb-service-name" name="name" type="text" required maxlength="191" autocomplete="off" value="<?php echo esc_attr( $editing['name'] ?? '' ); ?>">
					</div>
					<div class="form-field">
						<label for="sb-service-description"><?php esc_html_e( 'Description', 'simple-booking' ); ?></label>
						<textarea id="sb-service-description" name="description" rows="3"><?php echo esc_textarea( $editing['description'] ?? '' ); ?></textarea>
					</div>
					<div class="form-field form-required">
						<label for="sb-service-duration"><?php esc_html_e( 'Duration (minutes)', 'simple-booking' ); ?></label>
						<input id="sb-service-duration" name="duration" type="number" min="1" step="1" required value="<?php echo (int) ( $editing['duration'] ?? 30 ); ?>">
					</div>
					<div class="form-field">
						<label for="sb-service-price"><?php esc_html_e( 'Price', 'simple-booking' ); ?></label>
						<input id="sb-service-price" name="price" type="number" min="0" step="0.01" inputmode="decimal" value="<?php echo esc_attr( $editing['price'] ?? '0.00' ); ?>">
					</div>
					<div class="form-field">
						<label for="sb-service-status"><?php esc_html_e( 'Status', 'simple-booking' ); ?></label>
						<select id="sb-service-status" name="status">
							<option value="active" <?php selected( $editing['status'] ?? 'active', 'active' ); ?>><?php esc_html_e( 'Active', 'simple-booking' ); ?></option>
							<option value="inactive" <?php selected( $editing['status'] ?? '', 'inactive' ); ?>><?php esc_html_e( 'Inactive', 'simple-booking' ); ?></option>
						</select>
					</div>
					<?php submit_button( $editing ? __( 'Update Service', 'simple-booking' ) : __( 'Add Service', 'simple-booking' ) ); ?>
					<?php if ( $editing ) : ?>
						<a href="<?php echo esc_url( $page_url ); ?>"><?php esc_html_e( 'Cancel', 'simple-booking' ); ?></a>
					<?php endif; ?>
				</form>
			</div>
		</div>

		<div id="col-right">
			<div class="col-wrap">
				<table class="widefat striped">
					<thead>
						<tr>
							<th scope="col"><?php esc_html_e( 'Name', 'simple-booking' ); ?></th>
							<th scope="col"><?php esc_html_e( 'Duration', 'simple-booking' ); ?></th>
							<th scope="col"><?php esc_html_e( 'Price', 'simple-booking' ); ?></th>
							<th scope="col"><?php esc_html_e( 'Status', 'simple-booking' ); ?></th>
							<th scope="col"><span class="screen-reader-text"><?php esc_html_e( 'Actions', 'simple-booking' ); ?></span></th>
						</tr>
					</thead>
					<tbody>
						<?php if ( ! $services ) : ?>
							<tr><td colspan="5"><?php esc_html_e( 'No services yet. Add your first one.', 'simple-booking' ); ?></td></tr>
						<?php endif; ?>
						<?php foreach ( $services as $s ) : ?>
							<tr>
								<td><strong><?php echo esc_html( $s['name'] ); ?></strong></td>
								<td><?php echo esc_html( sprintf( _n( '%d minute', '%d minutes', (int) $s['duration'], 'simple-booking' ), (int) $s['duration'] ) ); ?></td>
								<td><?php echo esc_html( sb_price( $s['price'] ) ); ?></td>
								<td><?php echo 'active' === $s['status'] ? esc_html__( 'Active', 'simple-booking' ) : esc_html__( 'Inactive', 'simple-booking' ); ?></td>
								<td>
									<a class="button button-small" href="<?php echo esc_url( add_query_arg( 'edit', (int) $s['id'], $page_url ) ); ?>"><?php esc_html_e( 'Edit', 'simple-booking' ); ?></a>
									<button type="button" class="button button-small button-link-delete" data-sb-delete="sb_delete_service" data-id="<?php echo (int) $s['id']; ?>"><?php esc_html_e( 'Delete', 'simple-booking' ); ?></button>
								</td>
							</tr>
						<?php endforeach; ?>
					</tbody>
				</table>
			</div>
		</div>
	</div>
</div>
