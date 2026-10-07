<?php
/**
 * @var array      $services      The services shown (filtered by $category)
 * @var int        $total         All services
 * @var int        $uncategorized Services without a category
 * @var array      $categories    Each with 'service_count'
 * @var int|string $category      0 = all, 'none' = uncategorized, or a category id
 * @var array|null $editing
 * @var string     $page_url
 * @var string     $theme
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
$category_names = array_column( $categories, 'name', 'id' );
$filter_url     = static fn( $value ) => $value ? add_query_arg( 'category', $value, $page_url ) : $page_url;
$list_title     = ! $category ? __( 'All services', 'simple-booking' ) : ( 'none' === $category ? __( 'Uncategorized', 'simple-booking' ) : ( $category_names[ $category ] ?? '' ) );
// New services start in the category being viewed.
$form_category = (int) ( $editing['category_id'] ?? ( is_int( $category ) ? $category : 0 ) );
?>
<div class="wrap sb-app">
	<?php sb_view( 'admin/views/partials/header', [ 'title' => __( 'Services', 'simple-booking' ), 'theme' => $theme ] ); ?>

	<div class="sb-split">
		<div class="sb-split__form">
			<section class="sb-card">
				<div class="sb-card__head">
					<h2 class="sb-card__title"><?php esc_html_e( 'Categories', 'simple-booking' ); ?></h2>
					<button type="button" class="sb-button sb-button--small" data-sb-open="sb-category-dialog">
						<span class="dashicons dashicons-plus-alt2" aria-hidden="true"></span><?php esc_html_e( 'Category', 'simple-booking' ); ?>
					</button>
				</div>
				<ul class="sb-categories">
					<li class="<?php echo ! $category ? 'is-current' : ''; ?>">
						<a href="<?php echo esc_url( $page_url ); ?>" <?php echo ! $category ? 'aria-current="page"' : ''; ?>>
							<?php esc_html_e( 'All services', 'simple-booking' ); ?> <span class="sb-muted"><?php echo (int) $total; ?></span>
						</a>
					</li>
					<?php foreach ( $categories as $cat ) : ?>
						<?php $current = (int) $cat['id'] === $category; ?>
						<li class="<?php echo $current ? 'is-current' : ''; ?>">
							<a href="<?php echo esc_url( $filter_url( (int) $cat['id'] ) ); ?>" <?php echo $current ? 'aria-current="page"' : ''; ?>>
								<?php echo esc_html( $cat['name'] ); ?> <span class="sb-muted"><?php echo (int) $cat['service_count']; ?></span>
							</a>
							<span class="sb-categories__actions">
								<button type="button" class="sb-icon-button sb-icon-button--small" data-sb-open="sb-category-dialog"
									data-sb-fill="<?php echo esc_attr( wp_json_encode( [ 'id' => (int) $cat['id'], 'name' => $cat['name'] ] ) ); ?>"
									aria-label="<?php echo esc_attr( sprintf( __( 'Rename %s', 'simple-booking' ), $cat['name'] ) ); ?>"><span class="dashicons dashicons-edit" aria-hidden="true"></span></button>
								<button type="button" class="sb-icon-button sb-icon-button--small sb-icon-button--danger" data-sb-delete="sb_delete_category" data-id="<?php echo (int) $cat['id']; ?>"
									data-sb-confirm="<?php esc_attr_e( 'Delete this category? Its services are kept and become uncategorized.', 'simple-booking' ); ?>"
									data-sb-redirect="<?php echo esc_url( $current ? $page_url : '' ); ?>"
									aria-label="<?php echo esc_attr( sprintf( __( 'Delete %s', 'simple-booking' ), $cat['name'] ) ); ?>"><span class="dashicons dashicons-trash" aria-hidden="true"></span></button>
							</span>
						</li>
					<?php endforeach; ?>
					<?php if ( $categories && $uncategorized ) : ?>
						<li class="<?php echo 'none' === $category ? 'is-current' : ''; ?>">
							<a href="<?php echo esc_url( $filter_url( 'none' ) ); ?>" <?php echo 'none' === $category ? 'aria-current="page"' : ''; ?>>
								<?php esc_html_e( 'Uncategorized', 'simple-booking' ); ?> <span class="sb-muted"><?php echo (int) $uncategorized; ?></span>
							</a>
						</li>
					<?php endif; ?>
				</ul>
				<?php if ( ! $categories ) : ?>
					<p class="sb-hint"><?php esc_html_e( 'Group services, e.g. "Skin Care" or "Therapy". The booking form lists them under these headings.', 'simple-booking' ); ?></p>
				<?php endif; ?>
			</section>

			<section class="sb-card">
				<div>
					<h2 class="sb-card__title"><?php echo $editing ? esc_html__( 'Edit Service', 'simple-booking' ) : esc_html__( 'Add Service', 'simple-booking' ); ?></h2>
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
							<label for="sb-service-category"><?php esc_html_e( 'Category', 'simple-booking' ); ?></label>
							<select id="sb-service-category" name="category_id">
								<option value="0"><?php esc_html_e( 'None', 'simple-booking' ); ?></option>
								<?php foreach ( $categories as $cat ) : ?>
									<option value="<?php echo (int) $cat['id']; ?>" <?php selected( $form_category, (int) $cat['id'] ); ?>><?php echo esc_html( $cat['name'] ); ?></option>
								<?php endforeach; ?>
							</select>
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
							<a class="sb-link" href="<?php echo esc_url( $page_url ); ?>"><?php esc_html_e( 'Cancel', 'simple-booking' ); ?></a>
						<?php endif; ?>
					</form>
				</div>
			</section>
		</div>

		<section class="sb-card sb-split__list">
			<h2 class="sb-card__title"><?php echo esc_html( $list_title ); ?></h2>
			<div class="sb-table-wrap">
				<table class="sb-table">
					<thead>
						<tr>
							<th scope="col"><?php esc_html_e( 'Name', 'simple-booking' ); ?></th>
							<th scope="col"><?php esc_html_e( 'Category', 'simple-booking' ); ?></th>
							<th scope="col"><?php esc_html_e( 'Duration', 'simple-booking' ); ?></th>
							<th scope="col"><?php esc_html_e( 'Price', 'simple-booking' ); ?></th>
							<th scope="col"><?php esc_html_e( 'Status', 'simple-booking' ); ?></th>
							<th scope="col"><span class="screen-reader-text"><?php esc_html_e( 'Actions', 'simple-booking' ); ?></span></th>
						</tr>
					</thead>
					<tbody>
						<?php if ( ! $services ) : ?>
							<tr><td colspan="6"><?php echo $total ? esc_html__( 'No services in this category yet.', 'simple-booking' ) : esc_html__( 'No services yet. Add your first one.', 'simple-booking' ); ?></td></tr>
						<?php endif; ?>
						<?php foreach ( $services as $s ) : ?>
							<tr>
								<td><strong><?php echo esc_html( $s['name'] ); ?></strong></td>
								<td><?php echo isset( $category_names[ $s['category_id'] ?? 0 ] ) ? esc_html( $category_names[ $s['category_id'] ] ) : '<span class="sb-muted">—</span>'; ?></td>
								<td><?php echo esc_html( sprintf( _n( '%d minute', '%d minutes', (int) $s['duration'], 'simple-booking' ), (int) $s['duration'] ) ); ?></td>
								<td><?php echo esc_html( sb_price( $s['price'] ) ); ?></td>
								<td><?php sb_view( 'admin/views/partials/active-badge', [ 'active' => 'active' === $s['status'] ] ); ?></td>
								<td class="sb-actions">
									<a class="sb-button sb-button--ghost sb-button--small" href="<?php echo esc_url( add_query_arg( 'edit', (int) $s['id'], $page_url ) ); ?>"><?php esc_html_e( 'Edit', 'simple-booking' ); ?></a>
									<button type="button" class="sb-button sb-button--danger sb-button--small" data-sb-delete="sb_delete_service" data-id="<?php echo (int) $s['id']; ?>"><?php esc_html_e( 'Delete', 'simple-booking' ); ?></button>
								</td>
							</tr>
						<?php endforeach; ?>
					</tbody>
				</table>
			</div>
		</section>
	</div>

	<dialog id="sb-category-dialog" class="sb-dialog" aria-labelledby="sb-category-dialog-title">
		<form data-sb-action="sb_save_category" data-sb-redirect="<?php echo esc_url( $filter_url( $category ) ); ?>">
			<div class="sb-dialog__head">
				<h2 id="sb-category-dialog-title" data-new="<?php esc_attr_e( 'Add category', 'simple-booking' ); ?>" data-edit="<?php esc_attr_e( 'Rename category', 'simple-booking' ); ?>"><?php esc_html_e( 'Add category', 'simple-booking' ); ?></h2>
				<button type="button" class="sb-icon-button" data-sb-close aria-label="<?php esc_attr_e( 'Close', 'simple-booking' ); ?>"><span class="dashicons dashicons-no-alt" aria-hidden="true"></span></button>
			</div>
			<input type="hidden" name="id" value="0">
			<p class="sb-field">
				<label for="sb-category-name"><?php esc_html_e( 'Name', 'simple-booking' ); ?></label>
				<input id="sb-category-name" class="sb-input" name="name" type="text" required maxlength="191" autocomplete="off">
			</p>
			<div class="sb-dialog__foot">
				<button type="button" class="sb-button sb-button--secondary" data-sb-close><?php esc_html_e( 'Cancel', 'simple-booking' ); ?></button>
				<button type="submit" class="sb-button"><?php esc_html_e( 'Save', 'simple-booking' ); ?></button>
			</div>
		</form>
	</dialog>
</div>
