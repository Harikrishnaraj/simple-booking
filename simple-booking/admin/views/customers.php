<?php
/**
 * @var array  $customers rows with total_bookings and last_booking
 * @var string $search
 * @var int    $page
 * @var int    $pages
 * @var string $theme
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
$page_url = admin_url( 'admin.php?page=sb-customers' );
?>
<div class="wrap sb-app">
	<?php sb_view( 'admin/views/partials/header', [ 'title' => __( 'Customers', 'simple-booking' ), 'theme' => $theme ] ); ?>

	<section class="sb-card">
		<div class="sb-toolbar sb-toolbar--split">
			<form method="get" class="sb-search" role="search">
				<input type="hidden" name="page" value="sb-customers">
				<label class="screen-reader-text" for="sb-customer-search"><?php esc_html_e( 'Search customers', 'simple-booking' ); ?></label>
				<span class="dashicons dashicons-search" aria-hidden="true"></span>
				<input id="sb-customer-search" class="sb-input" type="search" name="s" value="<?php echo esc_attr( $search ); ?>" placeholder="<?php esc_attr_e( 'Search name, email or phone…', 'simple-booking' ); ?>">
			</form>
			<button type="button" class="sb-button" data-sb-open="sb-customer-dialog">
				<span class="dashicons dashicons-plus-alt2" aria-hidden="true"></span><?php esc_html_e( 'Customer', 'simple-booking' ); ?>
			</button>
		</div>

		<?php if ( $customers ) : ?>
			<div class="sb-table-wrap">
				<table class="sb-table">
					<thead>
						<tr>
							<th scope="col"><?php esc_html_e( 'ID', 'simple-booking' ); ?></th>
							<th scope="col"><?php esc_html_e( 'Name', 'simple-booking' ); ?></th>
							<th scope="col"><?php esc_html_e( 'Phone', 'simple-booking' ); ?></th>
							<th scope="col"><?php esc_html_e( 'Email', 'simple-booking' ); ?></th>
							<th scope="col"><?php esc_html_e( 'Note', 'simple-booking' ); ?></th>
							<th scope="col"><?php esc_html_e( 'Last booking', 'simple-booking' ); ?></th>
							<th scope="col" class="sb-num"><?php esc_html_e( 'Bookings', 'simple-booking' ); ?></th>
							<th scope="col"><span class="screen-reader-text"><?php esc_html_e( 'Actions', 'simple-booking' ); ?></span></th>
						</tr>
					</thead>
					<tbody>
						<?php foreach ( $customers as $c ) : ?>
							<tr>
								<td class="sb-muted"><?php echo (int) $c['id']; ?></td>
								<td>
									<span class="sb-person">
										<span class="sb-avatar" aria-hidden="true"><?php echo esc_html( mb_strtoupper( mb_substr( $c['name'], 0, 1 ) ) ); ?></span>
										<strong><?php echo esc_html( $c['name'] ); ?></strong>
									</span>
								</td>
								<td><?php echo $c['phone'] ? '<a class="sb-link" href="' . esc_url( 'tel:' . $c['phone'] ) . '">' . esc_html( $c['phone'] ) . '</a>' : '<span class="sb-muted">—</span>'; ?></td>
								<td><a class="sb-link" href="<?php echo esc_url( 'mailto:' . $c['email'] ); ?>"><?php echo esc_html( $c['email'] ); ?></a></td>
								<td class="sb-note"><?php echo $c['note'] ? esc_html( wp_trim_words( $c['note'], 12 ) ) : '<span class="sb-muted">—</span>'; ?></td>
								<td><?php echo $c['last_booking'] ? esc_html( mysql2date( get_option( 'date_format' ), $c['last_booking'] ) ) : '<span class="sb-muted">—</span>'; ?></td>
								<td class="sb-num"><?php echo (int) $c['total_bookings']; ?></td>
								<td class="sb-actions">
									<a class="sb-button sb-button--ghost sb-button--small" href="<?php echo esc_url( admin_url( 'admin.php?page=sb-bookings&s=' . rawurlencode( $c['email'] ) ) ); ?>"><?php esc_html_e( 'Bookings', 'simple-booking' ); ?></a>
									<button type="button" class="sb-button sb-button--ghost sb-button--small" data-sb-open="sb-customer-dialog"
										data-sb-fill="<?php echo esc_attr( wp_json_encode( array_intersect_key( $c, array_flip( [ 'id', 'name', 'email', 'phone', 'note' ] ) ) ) ); ?>"
										aria-label="<?php echo esc_attr( sprintf( __( 'Edit %s', 'simple-booking' ), $c['name'] ) ); ?>">
										<?php esc_html_e( 'Edit', 'simple-booking' ); ?>
									</button>
								</td>
							</tr>
						<?php endforeach; ?>
					</tbody>
				</table>
			</div>
			<?php sb_view( 'admin/views/partials/pagination', [ 'page' => $page, 'pages' => $pages ] ); ?>
		<?php elseif ( '' !== $search ) : ?>
			<p class="sb-empty"><?php esc_html_e( 'No customers match your search.', 'simple-booking' ); ?> <a class="sb-link" href="<?php echo esc_url( $page_url ); ?>"><?php esc_html_e( 'Clear search', 'simple-booking' ); ?></a></p>
		<?php else : ?>
			<p class="sb-empty"><?php esc_html_e( 'No customers yet. They are added automatically when someone books.', 'simple-booking' ); ?></p>
		<?php endif; ?>
	</section>

	<dialog id="sb-customer-dialog" class="sb-dialog" aria-labelledby="sb-customer-dialog-title">
		<form data-sb-action="sb_save_customer" data-sb-redirect="<?php echo esc_url( $page_url ); ?>">
			<div class="sb-dialog__head">
				<h2 id="sb-customer-dialog-title" data-new="<?php esc_attr_e( 'Add customer', 'simple-booking' ); ?>" data-edit="<?php esc_attr_e( 'Edit customer', 'simple-booking' ); ?>"><?php esc_html_e( 'Add customer', 'simple-booking' ); ?></h2>
				<button type="button" class="sb-icon-button" data-sb-close aria-label="<?php esc_attr_e( 'Close', 'simple-booking' ); ?>"><span class="dashicons dashicons-no-alt" aria-hidden="true"></span></button>
			</div>
			<input type="hidden" name="id" value="0">
			<p class="sb-field">
				<label for="sb-c-name"><?php esc_html_e( 'Name', 'simple-booking' ); ?></label>
				<input id="sb-c-name" class="sb-input" name="name" type="text" required maxlength="191" autocomplete="off">
			</p>
			<p class="sb-field">
				<label for="sb-c-email"><?php esc_html_e( 'Email', 'simple-booking' ); ?></label>
				<input id="sb-c-email" class="sb-input" name="email" type="email" required maxlength="191" spellcheck="false" autocomplete="off">
			</p>
			<p class="sb-field">
				<label for="sb-c-phone"><?php esc_html_e( 'Phone', 'simple-booking' ); ?></label>
				<input id="sb-c-phone" class="sb-input" name="phone" type="tel" maxlength="50" autocomplete="off">
			</p>
			<p class="sb-field">
				<label for="sb-c-note"><?php esc_html_e( 'Note', 'simple-booking' ); ?></label>
				<textarea id="sb-c-note" class="sb-input" name="note" rows="4"></textarea>
				<span class="sb-hint"><?php esc_html_e( 'Only visible to admins.', 'simple-booking' ); ?></span>
			</p>
			<div class="sb-dialog__foot">
				<button type="button" class="sb-button sb-button--secondary" data-sb-close><?php esc_html_e( 'Cancel', 'simple-booking' ); ?></button>
				<button type="submit" class="sb-button"><?php esc_html_e( 'Save', 'simple-booking' ); ?></button>
			</div>
		</form>
	</dialog>
</div>
