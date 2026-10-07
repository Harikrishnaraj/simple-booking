=== Simple Booking ===
Contributors: simplebooking
Tags: booking, appointment, salon, clinic, scheduling, shortcode
Requires at least: 6.0
Tested up to: 6.6
Requires PHP: 8.0
Stable tag: 1.2.0
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Lightweight, modern, commercial-grade appointment booking plugin for WordPress.

== Description ==
Simple Booking allows business owners (salons, clinics, consultants, personal trainers, agencies) to accept customer appointments online with an intuitive frontend shortcode.

== Installation ==
1. Upload the `simple-booking` folder to `/wp-content/plugins/` directory.
2. Activate the plugin through the 'Plugins' menu in WordPress.
3. Add the shortcode `[simple_booking]` to any page or post.

== Frequently Asked Questions ==
= How do I embed the booking form? =
Simply insert the shortcode `[simple_booking]` into any page, post, or widget area.

== Changelog ==
= 1.2.0 =
* Service categories: add, rename and delete them on the Services page, filter the list by category, and pick a category per service. The booking form groups services under their category ("Other" for the rest). Deleting a category keeps its services.
* Staff photos from the Media Library, shown in the admin lists and next to the chosen staff member on the booking form.
* Fix: on block themes (e.g. Twenty Twenty-Four) the booking form's script ran without its settings, so no times loaded.
* New table `sb_categories`, new columns `services.category_id` and `staff.photo_id` (added automatically on update).

= 1.1.0 =
* New Dashboard: appointments, customers, occupancy and revenue for any date range, compared with the previous period, plus bookings per day and upcoming appointments.
* New Calendar: month view of bookings, filterable by staff member.
* New Customers page: search, booking totals, last booking, admin notes, add and edit.
* Bookings page: search and filters for date range, status and staff.
* Redesigned admin pages with a dark and a light theme (switch with the button at the top right; remembered per user).
* Customers table gains a `note` column (added automatically on update).
