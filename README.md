# Simple Booking

WordPress appointment booking plugin. Add `[simple_booking]` to any page; manage bookings, services, staff and settings under **Bookings** in wp-admin.

- `simple-booking/` – the plugin (this folder is what gets installed)
- `tests/check_bookings.php` – slot/booking logic checks with WordPress stubbed out
- `build-zip.ps1` – builds `simple-booking-<version>.zip` for upload

## Test

```bash
php -d zend.assertions=1 -d assert.exception=1 tests/check_bookings.php
```

## Build

```powershell
./build-zip.ps1
```

Requires WordPress 6.0+ and PHP 8.0+.
