<?php
/**
 * Plugin Name:       Simple Booking
 * Plugin URI:        https://simplebookingplugin.com/
 * Description:       A lightweight, commercial-grade WordPress booking plugin for salons, clinics, consultants, and service providers.
 * Version:           1.1.0
 * Author:            Simple Booking Team
 * Author URI:        https://simplebookingplugin.com/
 * License:           GPL-2.0+
 * License URI:       http://www.gnu.org/licenses/gpl-2.0.txt
 * Text Domain:       simple-booking
 * Domain Path:       /languages
 * Requires at least: 6.0
 * Requires PHP:      8.0
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}

/**
 * Define Plugin Constants.
 */
define( 'SB_VERSION', '1.1.0' );
define( 'SB_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'SB_PLUGIN_URL', plugin_dir_url( __FILE__ ) );
define( 'SB_PLUGIN_BASENAME', plugin_basename( __FILE__ ) );

/**
 * Autoload or require core class files.
 */
require_once SB_PLUGIN_DIR . 'includes/helpers.php';
require_once SB_PLUGIN_DIR . 'includes/class-loader.php';
require_once SB_PLUGIN_DIR . 'includes/class-activator.php';
require_once SB_PLUGIN_DIR . 'includes/class-database.php';
require_once SB_PLUGIN_DIR . 'includes/class-security.php';
require_once SB_PLUGIN_DIR . 'includes/class-validator.php';
require_once SB_PLUGIN_DIR . 'includes/class-services.php';
require_once SB_PLUGIN_DIR . 'includes/class-staff.php';
require_once SB_PLUGIN_DIR . 'includes/class-customers.php';
require_once SB_PLUGIN_DIR . 'includes/class-bookings.php';
require_once SB_PLUGIN_DIR . 'includes/class-settings.php';
require_once SB_PLUGIN_DIR . 'includes/class-email.php';
require_once SB_PLUGIN_DIR . 'includes/class-reports.php';
require_once SB_PLUGIN_DIR . 'admin/controllers/class-admin-controller.php';
require_once SB_PLUGIN_DIR . 'public/controllers/class-public-controller.php';

/**
 * Main Singleton Plugin Class.
 */
final class Simple_Booking {

	/**
	 * Single instance holder.
	 * @var Simple_Booking|null
	 */
	private static ?Simple_Booking $instance = null;

	/**
	 * Loader instance.
	 * @var SB_Loader
	 */
	public SB_Loader $loader;

	/**
	 * Main instance getter (Singleton pattern).
	 */
	public static function get_instance(): Simple_Booking {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	/**
	 * Constructor. Initializes loader and hooks.
	 */
	private function __construct() {
		$this->loader = new SB_Loader();
		$this->set_locale();
		$this->maybe_upgrade_db();
		$this->define_admin_hooks();
		$this->define_public_hooks();
		$this->loader->run();
	}

	/**
	 * Load text domain for localization.
	 */
	private function set_locale(): void {
		add_action( 'plugins_loaded', function() {
			load_plugin_textdomain(
				'simple-booking',
				false,
				dirname( SB_PLUGIN_BASENAME ) . '/languages/'
			);
		});
	}

	/**
	 * Re-run schema setup after a plugin update (activation hooks don't fire on update).
	 */
	private function maybe_upgrade_db(): void {
		add_action( 'plugins_loaded', function() {
			if ( get_option( 'sb_db_version' ) !== SB_VERSION ) {
				SB_Database::create_tables();
			}
		});
	}

	/**
	 * Register admin menu and asset hooks.
	 */
	private function define_admin_hooks(): void {
		$admin = new SB_Admin_Controller();
		$this->loader->add_action( 'admin_menu', $admin, 'register_admin_menu' );
		$this->loader->add_action( 'admin_enqueue_scripts', $admin, 'enqueue_styles_and_scripts' );
		$this->loader->add_action( 'admin_body_class', $admin, 'admin_body_class' ); // add_action registers filters too
		
		// Admin AJAX Actions
		$this->loader->add_action( 'wp_ajax_sb_save_service', $admin, 'ajax_save_service' );
		$this->loader->add_action( 'wp_ajax_sb_delete_service', $admin, 'ajax_delete_service' );
		$this->loader->add_action( 'wp_ajax_sb_save_staff', $admin, 'ajax_save_staff' );
		$this->loader->add_action( 'wp_ajax_sb_delete_staff', $admin, 'ajax_delete_staff' );
		$this->loader->add_action( 'wp_ajax_sb_update_booking_status', $admin, 'ajax_update_booking_status' );
		$this->loader->add_action( 'wp_ajax_sb_save_settings', $admin, 'ajax_save_settings' );
		$this->loader->add_action( 'wp_ajax_sb_save_customer', $admin, 'ajax_save_customer' );
		$this->loader->add_action( 'wp_ajax_sb_save_theme', $admin, 'ajax_save_theme' );
	}

	/**
	 * Register public shortcode and AJAX hooks.
	 */
	private function define_public_hooks(): void {
		$public = new SB_Public_Controller();
		$this->loader->add_action( 'init', $public, 'register_shortcodes' );
		$this->loader->add_action( 'wp_enqueue_scripts', $public, 'enqueue_styles_and_scripts' );

		// Frontend Booking AJAX (Nopriv + Logged in)
		$this->loader->add_action( 'wp_ajax_sb_get_available_slots', $public, 'ajax_get_available_slots' );
		$this->loader->add_action( 'wp_ajax_nopriv_sb_get_available_slots', $public, 'ajax_get_available_slots' );
		
		$this->loader->add_action( 'wp_ajax_sb_submit_booking', $public, 'ajax_submit_booking' );
		$this->loader->add_action( 'wp_ajax_nopriv_sb_submit_booking', $public, 'ajax_submit_booking' );
	}
}

/**
 * Activation hook. Nothing needs cleaning up on deactivation; data removal lives in uninstall.php.
 */
register_activation_hook( __FILE__, array( 'SB_Activator', 'activate' ) );

/**
 * Initialize Plugin.
 */
function run_simple_booking(): Simple_Booking {
	return Simple_Booking::get_instance();
}
run_simple_booking();
