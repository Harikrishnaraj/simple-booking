<?php
/**
 * @var array  $fields   SB_Custom_Fields::all()
 * @var array  $services All services
 * @var string $theme
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
$types         = SB_Custom_Fields::types();
$service_names = array_column( $services, 'name', 'id' );
$last          = count( $fields ) - 1;
?>
<div class="wrap sb-app">
	<?php sb_view( 'admin/views/partials/header', [ 'title' => __( 'Custom Fields', 'counterslot' ), 'theme' => $theme ] ); ?>

	<section class="sb-card">
		<div class="sb-toolbar sb-toolbar--split">
			<p class="sb-muted sb-toolbar__text"><?php esc_html_e( 'Extra questions customers answer when they book, e.g. date of birth or "Is this your first visit?". Answers appear on the booking and in emails via {custom_fields}.', 'counterslot' ); ?></p>
			<button type="button" class="sb-button" data-sb-open="sb-field-dialog">
				<span class="dashicons dashicons-plus-alt2" aria-hidden="true"></span><?php esc_html_e( 'Field', 'counterslot' ); ?>
			</button>
		</div>

		<?php if ( $fields ) : ?>
			<div class="sb-table-wrap">
				<table class="sb-table">
					<thead>
						<tr>
							<th scope="col"><?php esc_html_e( 'Question', 'counterslot' ); ?></th>
							<th scope="col"><?php esc_html_e( 'Type', 'counterslot' ); ?></th>
							<th scope="col"><?php esc_html_e( 'Required', 'counterslot' ); ?></th>
							<th scope="col"><?php esc_html_e( 'Shown for', 'counterslot' ); ?></th>
							<th scope="col"><span class="screen-reader-text"><?php esc_html_e( 'Actions', 'counterslot' ); ?></span></th>
						</tr>
					</thead>
					<tbody>
						<?php foreach ( $fields as $i => $field ) : ?>
							<tr>
								<td>
									<strong><?php echo esc_html( $field['label'] ); ?></strong>
									<?php if ( $field['options'] ) : ?>
										<br><span class="sb-muted"><?php echo esc_html( implode( ' · ', $field['options'] ) ); ?></span>
									<?php endif; ?>
								</td>
								<td><?php echo esc_html( $types[ $field['type'] ] ?? $field['type'] ); ?></td>
								<td><?php echo $field['required'] ? esc_html__( 'Yes', 'counterslot' ) : '<span class="sb-muted">' . esc_html__( 'No', 'counterslot' ) . '</span>'; ?></td>
								<td>
									<?php
									echo $field['services']
										? esc_html( implode( ', ', array_filter( array_map( fn( $id ) => $service_names[ $id ] ?? '', $field['services'] ) ) ) )
										: esc_html__( 'All services', 'counterslot' );
									?>
								</td>
								<td class="sb-actions">
									<button type="button" class="sb-icon-button sb-icon-button--small" data-sb-move-field="<?php echo esc_attr( $field['id'] ); ?>" data-direction="-1" <?php disabled( 0, $i ); ?> aria-label="<?php echo esc_attr( sprintf( __( 'Move %s up', 'counterslot' ), $field['label'] ) ); ?>"><span class="dashicons dashicons-arrow-up-alt2" aria-hidden="true"></span></button>
									<button type="button" class="sb-icon-button sb-icon-button--small" data-sb-move-field="<?php echo esc_attr( $field['id'] ); ?>" data-direction="1" <?php disabled( $last, $i ); ?> aria-label="<?php echo esc_attr( sprintf( __( 'Move %s down', 'counterslot' ), $field['label'] ) ); ?>"><span class="dashicons dashicons-arrow-down-alt2" aria-hidden="true"></span></button>
									<button type="button" class="sb-button sb-button--ghost sb-button--small" data-sb-open="sb-field-dialog"
										data-sb-fill="<?php echo esc_attr( wp_json_encode( [ 'id' => $field['id'], 'label' => $field['label'], 'type' => $field['type'], 'options' => implode( "\n", $field['options'] ), 'required' => $field['required'], 'services' => $field['services'] ] ) ); ?>">
										<?php esc_html_e( 'Edit', 'counterslot' ); ?>
									</button>
									<button type="button" class="sb-button sb-button--danger sb-button--small" data-sb-delete="sb_delete_field" data-id="<?php echo esc_attr( $field['id'] ); ?>"
										data-sb-confirm="<?php esc_attr_e( 'Delete this question? Answers already given stay on their bookings.', 'counterslot' ); ?>"><?php esc_html_e( 'Delete', 'counterslot' ); ?></button>
								</td>
							</tr>
						<?php endforeach; ?>
					</tbody>
				</table>
			</div>
		<?php else : ?>
			<p class="sb-empty"><?php esc_html_e( 'No custom fields yet.', 'counterslot' ); ?></p>
		<?php endif; ?>
	</section>

	<dialog id="sb-field-dialog" class="sb-dialog" aria-labelledby="sb-field-dialog-title">
		<form data-sb-action="sb_save_field" data-sb-redirect="" data-sb-field-form>
			<div class="sb-dialog__head">
				<h2 id="sb-field-dialog-title" data-new="<?php esc_attr_e( 'Add field', 'counterslot' ); ?>" data-edit="<?php esc_attr_e( 'Edit field', 'counterslot' ); ?>"><?php esc_html_e( 'Add field', 'counterslot' ); ?></h2>
				<button type="button" class="sb-icon-button" data-sb-close aria-label="<?php esc_attr_e( 'Close', 'counterslot' ); ?>"><span class="dashicons dashicons-no-alt" aria-hidden="true"></span></button>
			</div>
			<input type="hidden" name="id" value="">
			<p class="sb-field">
				<label for="sb-f-label"><?php esc_html_e( 'Question', 'counterslot' ); ?></label>
				<input id="sb-f-label" class="sb-input" type="text" name="label" required maxlength="191">
			</p>
			<p class="sb-field">
				<label for="sb-f-type"><?php esc_html_e( 'Type', 'counterslot' ); ?></label>
				<select id="sb-f-type" class="sb-input" name="type">
					<?php foreach ( $types as $value => $label ) : ?>
						<option value="<?php echo esc_attr( $value ); ?>"><?php echo esc_html( $label ); ?></option>
					<?php endforeach; ?>
				</select>
			</p>
			<p class="sb-field" data-sb-options hidden>
				<label for="sb-f-options"><?php esc_html_e( 'Choices (one per line)', 'counterslot' ); ?></label>
				<textarea id="sb-f-options" class="sb-input" name="options" rows="4"></textarea>
			</p>
			<p class="sb-field sb-field--check">
				<label><input type="checkbox" name="required" value="1"> <?php esc_html_e( 'Customers must answer', 'counterslot' ); ?></label>
			</p>
			<fieldset class="sb-field">
				<legend class="sb-label"><?php esc_html_e( 'Show for', 'counterslot' ); ?></legend>
				<p class="sb-hint"><?php esc_html_e( 'Leave all unticked to ask for every service.', 'counterslot' ); ?></p>
				<?php foreach ( $services as $s ) : ?>
					<label class="sb-check-label"><input type="checkbox" name="services[]" value="<?php echo (int) $s['id']; ?>"> <?php echo esc_html( $s['name'] ); ?></label>
				<?php endforeach; ?>
			</fieldset>
			<div class="sb-dialog__foot">
				<button type="button" class="sb-button sb-button--secondary" data-sb-close><?php esc_html_e( 'Cancel', 'counterslot' ); ?></button>
				<button type="submit" class="sb-button"><?php esc_html_e( 'Save', 'counterslot' ); ?></button>
			</div>
		</form>
	</dialog>
</div>
