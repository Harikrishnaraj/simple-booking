=== CounterSlot ===
Contributors: counterslot
Tags: booking, appointment, scheduling, salon, clinic
Requires at least: 6.0
Tested up to: 7.1
Requires PHP: 8.0
Stable tag: 3.0.0
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Appointment booking for businesses that get paid in person: clinics, salons, tutors, consultants, repair shops and studios. Free, no upsells.

== Description ==

Customers book on your website; you get paid at your counter, and every payment is recorded.

CounterSlot gives your business a booking page in a few minutes. A setup wizard asks what kind of business you run and fills in typical opening hours, services and wording, then creates the page for you.

Everything is free. There is no Pro version, no locked features, no limits on bookings, staff or services, and no account to sign up for.

= Booking =

* Booking form for any page with the `[counterslot]` shortcode: service, staff member (or "any available"), date and free times.
* Staff working hours per weekday and days off, so only real free times are offered.
* Locations: with two or more, customers pick where first.
* Custom questions on the booking form (text, dropdown, checkbox, date), per service if you like.
* Group events with a number of places, listed with `[counterslot_events]`.
* Recurring appointments (weekly, every 2 or 4 weeks) from the admin.

= Running your day =

* Dashboard with appointments, customers, occupancy and money received, compared with the previous period.
* Month calendar, bookings list with search and filters, and a customer list with notes.
* Book, confirm, reschedule or cancel appointments from the admin.
* Customers get a private link to see, cancel or move their own booking, up to a cut-off you choose.

= Emails =

* Booking received, confirmed, cancelled, rescheduled, follow-up and reminder emails, each with an on/off switch and editable text.
* Emails to the assigned staff member and to you for each new booking.
* Reminders a set number of hours before each appointment.

= Money, without online payments =

* Prices, add-on extras, coupons, tax (inclusive or exclusive) and deposits.
* Record payments taken at the counter: cash, UPI, card, bank transfer or other, including refunds.
* Printable invoices with sequential numbers, your address and tax number.
* Finance page with totals by payment method, an unpaid list and CSV export.

= Privacy =

CounterSlot stores bookings and customer details (name, email, phone and any answers to your questions) in your own WordPress database. It does not contact any external service, load remote scripts or fonts, or track visitors. Emails are sent through your site's normal WordPress mail.

Booking requests are limited to 5 per 10 minutes per visitor. For this, a hash of the visitor's IP address is kept for up to 10 minutes in a temporary WordPress transient; the address itself is not stored.

== Installation ==

1. In WordPress go to Plugins → Add New Plugin, search for "CounterSlot", then click Install Now and Activate.
2. The setup wizard opens. Choose your type of business, opening hours, first staff member and services, and it creates a booking page for you. You can run it again from CounterSlot → Settings.
3. To show the form somewhere else, add the shortcode `[counterslot]` to any page or post. Group events are listed with `[counterslot_events]`.

== Frequently Asked Questions ==

= How do I add the booking form to a page? =

Insert the shortcode `[counterslot]` into any page or post. The setup wizard creates a page with it for you.

= Can customers pay online? =

No. CounterSlot is made for businesses that get paid in person. The booking form shows the price and says payment is at the appointment. You record what was paid from the Bookings page.

= Is there a Pro version? =

No. Every feature is in this plugin, free.

= Does it work with page caching plugins? =

Yes. Pages with the booking form send no-cache headers and set `DONOTCACHEPAGE`, which most caching plugins (including LiteSpeed Cache) respect.

= My site is behind Cloudflare. What should I change? =

Under CounterSlot → Settings, set "Visitor IP comes from" to Cloudflare, so the booking limit counts each visitor separately.

= What happens to my data if I delete the plugin? =

It is kept, unless you tick "Delete all bookings, customers, services and staff when the plugin is deleted" under CounterSlot → Settings before deleting it.

= I used Simple Booking. What changes? =

Nothing you need to redo. Install and activate CounterSlot: it switches the old Simple Booking plugin off and moves its bookings, customers and settings over. Pages with the old `[simple_booking]` and `[simple_booking_events]` shortcodes keep working. Then delete the old plugin from the Plugins page.

== Screenshots ==

1. The booking form: customers pick a service, a day and a free time.
2. Dashboard with appointments, customers, occupancy and revenue for any period.
3. Month calendar of bookings, filterable by staff member.
4. Bookings list with search, filters, status changes and rescheduling.
5. The setup wizard fills in hours, services and wording for your type of business.
6. Finance: payments by method, unpaid bookings and CSV export.

== Changelog ==

= 3.0.0 =
* Simple Booking is now **CounterSlot**. The plugin folder is `counterslot/` and the text domain is `counterslot`.
* New shortcodes `[counterslot]` and `[counterslot_events]`; the old `[simple_booking]` and `[simple_booking_events]` keep working.
* New booking codes start with CS- (existing SB- codes stay as they are).
* Database tables, options and hooks now use the `cslot_` prefix. Existing `sb_` tables and settings are moved over automatically on the first page load after updating.
* Switching over: activating CounterSlot turns the old Simple Booking plugin off and keeps all data. While the old copy is still installed, "Delete data on uninstall" is switched off so deleting it can't remove your bookings.
* Ready for WordPress.org: escaping, translator comments and coding-standards fixes.

= 2.3.0 =
* Setup wizard: opens after activation on new sites and gives you a working booking page in five short steps (type of business, opening hours, first staff member, services, booking page). Skip it, or run it again from Settings.
* Industry presets for clinics, salons, tutors, consultants, repair services and studios fill in typical hours, services and wording.
* New setting "Staff are called": the word customers see on the booking form, e.g. "Doctor" or "Stylist".
* Sites that already have services are never sent to the wizard after updating.

= 2.2.0 =
* Events: group sessions with a date, times, number of places, price per place, optional location and host (CounterSlot → Events).
* New [simple_booking_events] shortcode lists upcoming events with places left and a registration form (1–20 places; never overbooked).
* An event's host isn't bookable for appointments while it runs. Events show on the Calendar.
* Attendee list per event; cancel one registration or the whole event (attendees are emailed).
* New emails: Event registration, Event cancelled, and Event registration (admin).
* New tables `sb_events` and `sb_event_registrations` (created automatically on update).

= 2.1.0 =
* Record payments (cash, UPI, card, bank transfer, other; negative for refunds) from the Bookings page. Each booking shows Unpaid / Part paid / Paid.
* Deposits: set an advance amount per service; shown to customers and available as {deposit}.
* Printable invoices with sequential numbers (prefix in Settings), your business address and tax number. Admins open them from the Payments dialog; customers from their booking link.
* Finance page: payments in a date range with totals by method and CSV export, plus an Unpaid list of bookings with money still due.
* Dashboard shows payments received in the period. New email placeholders {amount_paid} and {balance}.
* New table `sb_payments`, new columns `services.deposit`, `bookings.invoice_number`, `bookings.invoice_date` (added automatically on update).

= 2.0.0 =
* New Pricing page with three tabs:
  * Extras: paid add-ons per service that customers tick on the booking form.
  * Coupons: percentage or fixed codes, optional dates, usage limit and services.
  * Tax: name and rate, with prices including or excluding tax.
* The booking form shows a live price summary (service, extras, coupon, tax, total) and says payment is at the appointment.
* Each booking keeps its own price breakdown; the Bookings page shows the total, and emails get {price} (total) and {price_details}.
* The admin booking dialog takes extras and a coupon too (one coupon use per series).
* Dashboard revenue uses each booking's own total.
* New table `sb_coupons`, new columns `bookings.pricing` and `bookings.total` (added automatically on update).

= 1.9.0 =
* Recurring appointments: in the admin "Book appointment" dialog choose Repeat (every week, 2 weeks or 4 weeks) and the number of sessions. Dates that aren't free are skipped and listed.
* The customer gets one email for the whole series; {recurring_details} lists all sessions (included in the default booking and confirmation emails).
* Bookings page: a "Series" link on each session shows the whole series, and "Cancel series" cancels its upcoming sessions.
* New column `bookings.series_id` (added automatically on update).

= 1.8.0 =
* Locations (CounterSlot → Locations): name, address and phone. Assign each staff member to a location.
* With two or more active locations the booking form asks "Location" first and only offers staff (and "any available" times) at that location.
* Bookings remember their location: shown on the Bookings page, on the customer's manage page, and in emails via {location_name} and {location_address} (a "Where:" line in the default emails).
* New table `sb_locations`, new columns `staff.location_id` and `bookings.location_id` (added automatically on update).

= 1.7.0 =
* Customer self-service: the new {manage_link} placeholder gives each customer a private link to see their booking and, up to a cut-off you set (24 hours by default), cancel it or move it to another free time. Added to the default booking, confirmation, rescheduled and reminder emails.
* New admin email "Customer cancelled or moved" tells you when a customer uses the link.
* Settings: booking page (found automatically if not set), allow customer changes, and the cut-off.

= 1.6.0 =
* Custom fields (CounterSlot → Custom Fields): add your own questions to the booking form (short or long text, dropdown, checkbox, date), make them required, limit them to certain services, and reorder them.
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

== Upgrade Notice ==

= 3.0.0 =
Simple Booking is now CounterSlot. Your bookings, customers and settings move over automatically.
