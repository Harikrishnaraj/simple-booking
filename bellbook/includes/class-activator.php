<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class SB_Activator {

	public static function activate(): void {
		SB_Database::create_tables();
		bellbook_protect_data_from_old_plugin();
		// Open the setup wizard on the next admin page load (see SB_Admin_Controller::maybe_redirect_to_setup).
		set_transient( SB_Setup::REDIRECT_KEY, 1, 60 );
	}
}
