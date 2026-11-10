# Barangay Lost and Found Information Management System

## 1. Tools to install
- XAMPP (Apache, PHP 8.1 or newer, MySQL/MariaDB, phpMyAdmin): https://www.apachefriends.org
- A code editor: VS Code
- Git (optional, for sharing code with groupmates)

## 2. Setup
1. Copy this whole `lostfound` folder to `C:\xampp\htdocs\` (Mac/Linux: the `htdocs` folder of XAMPP).
2. Open the XAMPP Control Panel and start **Apache** and **MySQL**.
3. Open http://localhost/phpmyadmin, click **Import**, choose `database/schema.sql`, click **Go**.
   This creates the `barangay_lostfound` database, all tables, and the item categories.
4. Open `includes/config.php` and set `BARANGAY_NAME`. Check `DB_USER` and `DB_PASS` if you changed MySQL credentials.
5. Open http://localhost/lostfound/install.php and create the first staff account.
6. **Delete `install.php`** after creating the account.
7. Open http://localhost/lostfound/ and log in. Register a resident account in another browser to test the full flow.

If PHP says `finfo` or `pdo_mysql` is missing, open `php.ini` in XAMPP and enable `extension=fileinfo` and `extension=pdo_mysql`, then restart Apache.

## 3. File map
| File | Purpose |
|---|---|
| `database/schema.sql` | Tables: users, categories, items, claims, messages, activity_log |
| `includes/` | config, DB connection, helpers (security, uploads), shared header/footer |
| `index.php` | Browse and search listed items, filters, pagination |
| `register.php`, `login.php`, `logout.php` | Resident accounts with consent checkbox |
| `report.php` | Submit lost/found report with photo |
| `item.php` | Item details, claim request, messaging |
| `my.php` | Resident's reports and claims |
| `admin/` | Staff: dashboard, notifications, review reports, verify claims, records, reports and CSV |
| `install.php` | One-time first staff account (delete afterwards) |

## 4b. Staff notifications
Staff are alerted in the app when a resident posts a report or sends a claim request: a banner at the top of every page, a bell link with an unread count in the sidebar, count badges on "Review reports" and "Claim requests", and a Notifications page. Clicking a notification marks it read and opens the page where staff act on it.
If you imported an older `schema.sql`, run `database/migration_notifications.sql` once in phpMyAdmin.

## 5. Status flow
`pending` (resident submits) → `listed` (staff approve) or `rejected`
→ `claim_pending` (a resident sends a claim) → `claimed` (staff approve) → `closed` (item handed over).
A rejected claim returns the item to `listed` when no other claim is waiting.

## 6. Security built in
- Passwords hashed with `password_hash` (bcrypt). Login lockout after 5 failed tries for 5 minutes.
- SQL injection blocked with PDO prepared statements.
- XSS blocked by escaping all output with `e()`.
- CSRF token on every POST form.
- Photo uploads checked by real MIME type, size limit, random file names, and PHP execution disabled in `uploads/`.
- Only staff can open `admin/` pages; messages are visible only to the reporter, claimants, and staff.
- Data Privacy Act consent recorded at registration (`users.consent_at`); actions are written to `activity_log`.

## 7. Ideas for extending
Email or SMS notifications, residents list and deactivate/verify accounts, edit/withdraw own reports, auto-matching of lost vs found items, multi-photo upload.
