# CounterSlot

WordPress appointment booking plugin for businesses that get paid in person (formerly "Simple Booking"). Add `[counterslot]` to any page; manage bookings, services, staff and settings under **CounterSlot** in wp-admin.

- `counterslot/` – the plugin (this folder is what gets installed)
- `tests/check_bookings.php` – slot/booking logic checks with WordPress stubbed out
- `build-zip.ps1` – builds `counterslot-<version>.zip` for upload
- `.wordpress-org/` – WordPress.org listing assets (screenshots and the Playground blueprint for Live Preview); these go in the SVN `assets/` folder, not in the plugin

## Test

```bash
php -d zend.assertions=1 -d assert.exception=1 tests/check_bookings.php
```

## Build

```powershell
./build-zip.ps1
```

Requires WordPress 6.0+ and PHP 8.0+. Tested up to WordPress 7.1.
