<?php
/**
 * Plugin Name:       CounterSlot
 * Plugin URI:        https://counterslot.com/
 * Description:       Online appointment booking for businesses that get paid in person: clinics, salons, tutors, consultants, repair shops and studios. Formerly "Simple Booking".
 * Version:           3.0.0
 * Author:            CounterSlot
 * Author URI:        https://counterslot.com/
 * License:           GPL-2.0-or-later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:       counterslot
 * Requires at least: 6.0
 * Requires PHP:      8.0
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}

/**
 * Deleting the old "Simple Booking" plugin runs its uninstaller, which drops its sb_* tables when
 * "Delete data on uninstall" is ticked in its sb_settings option. CounterSlot moves that data to
 * cslot_* (see CSlot_Database::migrate_from_simple_booking()), but until that has happened the
 * data still lives there, so switch the old option off while the old copy is installed.
 */
function counterslot_protect_data_from_old_plugin(): void {
	if ( ! file_exists( WP_PLUGIN_DIR . '/simple-booking/simple-booking.php' ) ) {
		return;
	}
	$settings = get_option( 'sb_settings' );
	if ( is_array( $settings ) && ! empty( $settings['delete_data_on_uninstall'] ) ) {
		$settings['delete_data_on_uninstall'] = false;
		update_option( 'sb_settings', $settings );
	}
}

/**
 * CounterSlot was called "Simple Booking" (folder simple-booking/, constant SB_VERSION). If that
 * copy is still active, don't run both: stop here, and switch it off when CounterSlot is
 * activated. On the next page load CounterSlot moves its data over.
 */
if ( defined( 'SB_VERSION' ) ) {
	register_activation_hook( __FILE__, function () {
		deactivate_plugins( 'simple-booking/simple-booking.php', true );
		counterslot_protect_data_from_old_plugin();
	} );
	add_action( 'admin_notices', function () {
		if ( current_user_can( 'activate_plugins' ) ) {
			echo '<div class="notice notice-warning"><p>' . esc_html__( 'CounterSlot is the new name of Simple Booking. Deactivate the old Simple Booking plugin to finish switching; your bookings and settings carry over.', 'counterslot' ) . '</p></div>';
		}
	} );
	return;
}

/**
 * Define Plugin Constants.
 */
define( 'CSLOT_VERSION', '3.0.0' );
define( 'CSLOT_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'CSLOT_PLUGIN_URL', plugin_dir_url( __FILE__ ) );
define( 'CSLOT_PLUGIN_BASENAME', plugin_basename( __FILE__ ) );

/**
 * Autoload or require core class files.
 */
require_once CSLOT_PLUGIN_DIR . 'includes/helpers.php';
require_once CSLOT_PLUGIN_DIR . 'includes/class-loader.php';
require_once CSLOT_PLUGIN_DIR . 'includes/class-activator.php';
require_once CSLOT_PLUGIN_DIR . 'includes/class-database.php';
require_once CSLOT_PLUGIN_DIR . 'includes/class-security.php';
require_once CSLOT_PLUGIN_DIR . 'includes/class-validator.php';
require_once CSLOT_PLUGIN_DIR . 'includes/class-services.php';
require_once CSLOT_PLUGIN_DIR . 'includes/class-categories.php';
require_once CSLOT_PLUGIN_DIR . 'includes/class-locations.php';
require_once CSLOT_PLUGIN_DIR . 'includes/class-pricing.php';
require_once CSLOT_PLUGIN_DIR . 'includes/class-payments.php';
require_once CSLOT_PLUGIN_DIR . 'includes/class-events.php';
require_once CSLOT_PLUGIN_DIR . 'includes/class-staff.php';
require_once CSLOT_PLUGIN_DIR . 'includes/class-customers.php';
require_once CSLOT_PLUGIN_DIR . 'includes/class-bookings.php';
require_once CSLOT_PLUGIN_DIR . 'includes/class-settings.php';
require_once CSLOT_PLUGIN_DIR . 'includes/class-setup.php';
require_once CSLOT_PLUGIN_DIR . 'includes/class-custom-fields.php';
require_once CSLOT_PLUGIN_DIR . 'includes/class-notifications.php';
require_once CSLOT_PLUGIN_DIR . 'includes/class-email.php';
require_once CSLOT_PLUGIN_DIR . 'includes/class-manage.php';
require_once CSLOT_PLUGIN_DIR . 'includes/class-reports.php';
require_once CSLOT_PLUGIN_DIR . 'admin/controllers/class-admin-controller.php';
require_once CSLOT_PLUGIN_DIR . 'public/controllers/class-public-controller.php';

/**
 * Main Singleton Plugin Class.
 */
final class CounterSlot {

	/**
	 * Single instance holder.
	 * @var CounterSlot|null
	 */
	private static ?CounterSlot $instance = null;

	/**
	 * Loader instance.
	 * @var CSlot_Loader
	 */
	public CSlot_Loader $loader;

	/**
	 * Main instance getter (Singleton pattern).
	 */
	public static function get_instance(): CounterSlot {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	/**
	 * Constructor. Initializes loader and hooks.
	 */
	private function __construct() {
		$this->loader = new CSlot_Loader();
		$this->maybe_upgrade_db();
		$this->define_admin_hooks();
		$this->define_public_hooks();
		$this->define_cron_hooks();
		$this->loader->run();
	}

	/**
	 * Re-run schema setup after a plugin update (activation hooks don't fire on update).
	 */
	private function maybe_upgrade_db(): void {
		add_action( 'plugins_loaded', function() {
			if ( get_option( 'cslot_db_version' ) !== CSLOT_VERSION ) {
				CSlot_Database::create_tables();
			}
		});
	}

	/**
	 * Register admin menu and asset hooks.
	 */
	private function define_admin_hooks(): void {
		$admin = new CSlot_Admin_Controller();
		$this->loader->add_action( 'admin_menu', $admin, 'register_admin_menu' );
		$this->loader->add_action( 'admin_enqueue_scripts', $admin, 'enqueue_styles_and_scripts' );
		$this->loader->add_action( 'admin_body_class', $admin, 'admin_body_class' ); // add_action registers filters too
		
		// Admin AJAX Actions
		$this->loader->add_action( 'wp_ajax_cslot_save_service', $admin, 'ajax_save_service' );
		$this->loader->add_action( 'wp_ajax_cslot_delete_service', $admin, 'ajax_delete_service' );
		$this->loader->add_action( 'wp_ajax_cslot_save_staff', $admin, 'ajax_save_staff' );
		$this->loader->add_action( 'wp_ajax_cslot_delete_staff', $admin, 'ajax_delete_staff' );
		$this->loader->add_action( 'wp_ajax_cslot_update_booking_status', $admin, 'ajax_update_booking_status' );
		$this->loader->add_action( 'wp_ajax_cslot_save_settings', $admin, 'ajax_save_settings' );
		$this->loader->add_action( 'wp_ajax_cslot_save_customer', $admin, 'ajax_save_customer' );
		$this->loader->add_action( 'wp_ajax_cslot_save_theme', $admin, 'ajax_save_theme' );
		$this->loader->add_action( 'wp_ajax_cslot_save_category', $admin, 'ajax_save_category' );
		$this->loader->add_action( 'wp_ajax_cslot_delete_category', $admin, 'ajax_delete_category' );
		$this->loader->add_action( 'wp_ajax_cslot_save_template', $admin, 'ajax_save_template' );
		$this->loader->add_action( 'wp_ajax_cslot_save_field', $admin, 'ajax_save_field' );
		$this->loader->add_action( 'wp_ajax_cslot_save_location', $admin, 'ajax_save_location' );
		$this->loader->add_action( 'wp_ajax_cslot_save_extra', $admin, 'ajax_save_extra' );
		$this->loader->add_action( 'wp_ajax_cslot_add_payment', $admin, 'ajax_add_payment' );
		$this->loader->add_action( 'wp_ajax_cslot_save_event', $admin, 'ajax_save_event' );
		$this->loader->add_action( 'wp_ajax_cslot_cancel_event', $admin, 'ajax_cancel_event' );
		$this->loader->add_action( 'wp_ajax_cslot_cancel_registration', $admin, 'ajax_cancel_registration' );
		$this->loader->add_action( 'wp_ajax_cslot_delete_payment', $admin, 'ajax_delete_payment' );
		$this->loader->add_action( 'admin_post_cslot_export_payments', $admin, 'export_payments' );
		$this->loader->add_action( 'wp_ajax_cslot_delete_extra', $admin, 'ajax_delete_extra' );
		$this->loader->add_action( 'wp_ajax_cslot_save_coupon', $admin, 'ajax_save_coupon' );
		$this->loader->add_action( 'wp_ajax_cslot_delete_coupon', $admin, 'ajax_delete_coupon' );
		$this->loader->add_action( 'wp_ajax_cslot_delete_location', $admin, 'ajax_delete_location' );
		$this->loader->add_action( 'wp_ajax_cslot_delete_field', $admin, 'ajax_delete_field' );
		$this->loader->add_action( 'wp_ajax_cslot_move_field', $admin, 'ajax_move_field' );
		$this->loader->add_action( 'wp_ajax_cslot_admin_slots', $admin, 'ajax_admin_slots' );
		$this->loader->add_action( 'wp_ajax_cslot_admin_create_booking', $admin, 'ajax_admin_create_booking' );
		$this->loader->add_action( 'wp_ajax_cslot_admin_reschedule', $admin, 'ajax_admin_reschedule' );
		$this->loader->add_action( 'wp_ajax_cslot_cancel_series', $admin, 'ajax_cancel_series' );
		$this->loader->add_action( 'wp_ajax_cslot_test_template', $admin, 'ajax_test_template' );
		$this->loader->add_action( 'wp_ajax_cslot_run_setup', $admin, 'ajax_run_setup' );
		$this->loader->add_action( 'wp_ajax_cslot_skip_setup', $admin, 'ajax_skip_setup' );
		$this->loader->add_action( 'admin_init', $admin, 'maybe_redirect_to_setup' );
		$this->loader->add_action( 'admin_notices', $admin, 'setup_notice' );
	}

	/**
	 * Hourly reminder emails. Scheduled here as well as on activation, because
	 * activation hooks don't run when the plugin is updated.
	 */
	private function define_cron_hooks(): void {
		add_action( CSlot_Email::REMINDER_HOOK, fn() => ( new CSlot_Email() )->send_reminders() );
		add_action( 'init', function() {
			if ( ! wp_next_scheduled( CSlot_Email::REMINDER_HOOK ) ) {
				wp_schedule_event( time() + MINUTE_IN_SECONDS, 'hourly', CSlot_Email::REMINDER_HOOK );
			}
		} );
	}

	/**
	 * Register public shortcode and AJAX hooks.
	 */
	private function define_public_hooks(): void {
		$public = new CSlot_Public_Controller();
		$this->loader->add_action( 'init', $public, 'register_shortcodes' );
		$this->loader->add_action( 'wp_enqueue_scripts', $public, 'enqueue_styles_and_scripts' );
		$this->loader->add_action( 'template_redirect', $public, 'maybe_prevent_caching' );

		// Frontend Booking AJAX (Nopriv + Logged in)
		$this->loader->add_action( 'wp_ajax_cslot_get_available_slots', $public, 'ajax_get_available_slots' );
		$this->loader->add_action( 'wp_ajax_nopriv_cslot_get_available_slots', $public, 'ajax_get_available_slots' );
		
		$this->loader->add_action( 'wp_ajax_cslot_submit_booking', $public, 'ajax_submit_booking' );
		$this->loader->add_action( 'wp_ajax_cslot_quote', $public, 'ajax_quote' );
		$this->loader->add_action( 'wp_ajax_cslot_event_register', $public, 'ajax_event_register' );
		$this->loader->add_action( 'wp_ajax_nopriv_cslot_event_register', $public, 'ajax_event_register' );
		// Invoice: admins (nonce) or the customer (signed link).
		$this->loader->add_action( 'admin_post_cslot_invoice', $public, 'render_invoice' );
		$this->loader->add_action( 'admin_post_nopriv_cslot_invoice', $public, 'render_invoice' );
		$this->loader->add_action( 'wp_ajax_nopriv_cslot_quote', $public, 'ajax_quote' );
		foreach ( [ 'cslot_manage_slots' => 'ajax_manage_slots', 'cslot_manage_cancel' => 'ajax_manage_cancel', 'cslot_manage_reschedule' => 'ajax_manage_reschedule' ] as $action => $method ) {
			$this->loader->add_action( "wp_ajax_$action", $public, $method );
			$this->loader->add_action( "wp_ajax_nopriv_$action", $public, $method );
		}
		$this->loader->add_action( 'wp_ajax_nopriv_cslot_submit_booking', $public, 'ajax_submit_booking' );
	}
}

/**
 * Activation hook. Deactivation only stops the reminder cron; data removal lives in uninstall.php.
 */
register_activation_hook( __FILE__, array( 'CSlot_Activator', 'activate' ) );
register_deactivation_hook( __FILE__, fn() => wp_clear_scheduled_hook( CSlot_Email::REMINDER_HOOK ) );

/**
 * Initialize Plugin.
 */
function counterslot_run(): CounterSlot {
	return CounterSlot::get_instance();
}
counterslot_run();
