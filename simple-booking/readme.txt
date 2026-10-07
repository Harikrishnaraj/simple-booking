=== Simple Booking ===
Contributors: simplebooking
Tags: booking, appointment, salon, clinic, scheduling, shortcode
Requires at least: 6.0
Tested up to: 6.6
Requires PHP: 8.0
Stable tag: 1.6.0
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
= 1.6.0 =
* Custom fields (Simple Booking → Custom Fields): add your own questions to the booking form (short or long text, dropdown, checkbox, date), make them required, limit them to certain services, and reorder them.
* Answers are saved with each booking, shown on the Bookings page and in the admin booking dialog, and available in emails as {custom_fields} (included in the staff and admin new-booking emails by default).
* New column `bookings.custom_fields` (added automatically on update).

= 1.5.1 =
* Booking pages are no longer stored by page-cache plugins (no-cache headers and DONOTCACHEPAGE; LiteSpeed supported), so visitors never get an expired form or yesterday's days.
* New setting "Visitor IP comes from" for sites behind Cloudflare or a proxy, so the booking rate limit counts each visitor separately.
* A booking request for a time that's already taken no longer saves a customer record.

= 1.5.0 =
* Staff working hours: each staff member can follow the business hours or have custom hours per weekday.
* Staff days off (single days or ranges) for holidays and leave.
* Available times, the booking form's open days, and the dashboard's occupancy all use each person's own hours.
* New columns `staff.schedule` and `staff.days_off` (added automatically on update).

= 1.4.0 =
* Book appointments from the admin (Bookings → "+ Booking"): pick an existing customer or enter a new one, choose service, staff, date and a free time, set Confirmed or Pending, and choose whether to email the customer.
* Reschedule pending or confirmed bookings to another free date, time or staff member. New "Rescheduled" customer email.
* Fix: changing a cancelled or completed booking back to Pending or Confirmed now checks its time is still free, so it can no longer double-book.

= 1.3.0 =
* New Notifications page: seven emails (booking received, confirmed, cancelled, completed follow-up and reminder to the customer; new booking to the assigned staff member and to the admin), each with an on/off switch, editable subject and message, placeholders and a "send test" button.
* Reminder emails: sent once, a set number of hours (default 24) before each pending or confirmed appointment, by an hourly WordPress cron job.
* The two email switches move from Settings to the Notifications page; existing choices carry over. The staff email starts switched off.
* New column `bookings.reminder_sent` (added automatically on update).

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
