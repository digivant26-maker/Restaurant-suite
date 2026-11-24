# Restaurant Suite — website, online ordering and reservations for restaurants and sports bars (white-label)

A complete system for restaurants, bars and sports bars, by **DIGIVANT**. It's a public website with the menu, **online pickup ordering**, **table reservations**, events, specials and the **games on your screens**, plus the staff side with a **kitchen screen**. It runs on ordinary PHP hosting (PHP 8.1+ and MySQL/MariaDB, e.g. Hostinger) with nothing to install and no build step.

> **Status: version 1.** Built on the same foundation as Body Shop Suite: branding, demo builder, separate data per demo, staff IDs. "Restaurant Suite" is a working name.

## What a restaurant gets

**Website:** home page with tonight's games, featured dishes, events and specials; full menu with photos, tags and sold-out marks; events and specials; games schedule; gallery; private event requests; careers; contact; privacy.

**Online ordering (pickup, pay at pickup):**
- Customers add items to their order, choose a pickup time the kitchen can make (opening hours + "ready in" minutes) and leave notes.
- They get an email receipt and a live order page, and another email when the food is ready.
- Item names and prices are copied onto the order, so editing the menu never changes a placed order.

**Kitchen screen:** New → Preparing → Ready → Picked up, with big tickets, notes and allergies highlighted, late orders in red, and a refresh every 20 seconds. The kitchen role sees only this screen.

**Reservations:**
- Guests pick a day, party size and an open time. Capacity is counted in **seats per time slot**, so a busy slot fills up instead of overbooking.
- Requests can be confirmed by staff or automatically. Guests can cancel from their link.
- Staff can take phone bookings, and mark tables seated or no-show.

**Staff side:**
- **Today:** new orders, table requests, guests booked, today's sales, games, sold-out items.
- **Day to day:** orders, reservations, a menu editor (categories, items, prices, photos, tags, sold out), events and specials, games schedule, gallery and messages.
- **Admin only:** activity log, staff and settings.
- **Roles:** Admin, Manager, Front of house and Kitchen. Each signs in with a **Staff ID** (restaurant prefix + number, e.g. `RT001`).

## Branding: Admin → Settings

- **Restaurant details:** name, tagline, home page headline, about text, phone, email, address, hours, kitchen hours, happy hour line and social links.
- **Brand colour:** one colour in, a full palette out. It's checked so text stays readable (WCAG AA 4.5:1) on both the dark website and the light staff side (`tests/run.php`).
- **Logo:** PNG, JPG or WebP. Without one, the restaurant's initials are used.
- **Ordering and reservation rules:** hours, days, lead times, tax, seats per slot, largest party online and auto-confirm.

Photos are re-encoded on upload, which removes hidden data such as GPS location.

## Live demo builder (DIGIVANT's demo site)

Set `'demo_builder' => true` on a separate install. The front page becomes `/demo`. A visitor enters a name, colour and logo (with a live preview) and gets their own branded restaurant with fictional sample data:
- a full menu, with one item sold out;
- orders in every kitchen column;
- table requests waiting for an answer;
- weekly events and specials;
- a week of games between invented teams;
- messages.

They're signed in as the Admin. Each demo has its own tables, files and sign-in, never sends real email (an Outbox shows what it would have sent), and is deleted after `demo_hours` (48). Leads are emailed to `demo_sales_email` and listed under **Admin → Demo requests**. Add an hourly cron job: `php …/deploy/demo-cleanup.php`.

## Install for one restaurant

1. Create a website and a MySQL database in the hosting panel, and upload the files.
2. Copy `config.example.php` to `config.php`. Fill in the database, `site_url`, `timezone`, email (SMTP) and a long random `setup_key`. Never commit `config.php`.
3. Open `/admin/setup` and create the Admin account (tables are created automatically). Then remove `setup_key`.
4. In Settings, fill in the profile, colour, logo and ordering/reservation rules. Then build the menu and add staff.

## Local development

```bash
cp config.example.php config.php   # point it at a local database
php -S 127.0.0.1:3600 tests/dev-router.php
php tests/run.php                  # unit checks
```
