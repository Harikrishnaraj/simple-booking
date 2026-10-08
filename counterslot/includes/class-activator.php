<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class CSlot_Activator {

	public static function activate(): void {
		CSlot_Database::create_tables();
		counterslot_protect_data_from_old_plugin();
		// Open the setup wizard on the next admin page load (see CSlot_Admin_Controller::maybe_redirect_to_setup).
		set_transient( CSlot_Setup::REDIRECT_KEY, 1, 60 );
	}
}
