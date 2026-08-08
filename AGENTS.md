# AGENTS.md

Real Estate ERP for Pakistan. Plain PHP + MySQLi, **no framework, no Composer, no npm, no build step, no tests**. Runs under XAMPP Apache/MySQL in the `property` subdirectory.

## Run / setup

- Serve from `http://localhost/property/`. `BASE_URL` is computed from `DOCUMENT_ROOT` in `includes/config.php`, so the app works in a subdirectory — always prefix absolute URLs/links with `<?= BASE_URL ?>`.
- Fresh install: open `install.php`, submit the DB form. It imports `database.sql` (schema + seed) and **regenerates `includes/config.php`** from a hardcoded template (DB creds: `localhost` / `root` / empty password / `property_erp`). `dummy_data.sql` is optional sample data, not loaded by the installer.
- Default login: `admin` / `admin123`.
- Lint with `C:\xampp\php\php.exe -l <file>` (php is not on PATH).

## Architecture

- File-per-page routing, no router. Root: `index.php` (dashboard), `login.php`, `logout.php`, `install.php`. Everything else lives in `pages/` (feature lists, `*_form.php` = add/edit, `*_view.php` = detail). `pages/reports/` holds report pages.
- Shared code in `includes/`: `config.php`, `database.php` (opens global `$mysqli`), `functions.php` (helpers), `auth.php` (loads `$user` + perms), `header.php` / `sidebar.php` / `footer.php` (layout; footer pulls Bootstrap/jQuery/Chart.js from CDN).
- DB layer in `includes/functions.php`: `db_get($sql, $params)`, `db_all`, `db_exec` (returns last insert id). Params are bound automatically by PHP type (int→`i`, float→`d`, else→`s`) — match SQL accordingly. Use `%`-prefixing carefully: e.g. `next_number('PRP','properties','property_no')` generates `PRP-0001` style numbers when auto-numbering is needed. NOTE: `next_number()` strips the prefix plus ONE more char (the `-`), so numbers like `BK-0001` → next `BK-0002` (it scans `SUBSTRING(column, LENGTH(prefix)+2)`).
- Transfers & Withdrawals module: `pages/transfers.php` (list) + `pages/transfer_form.php` (form), gated by `accounting.view`/`accounting.manage`, records go in the `transfers` table. 5 types: `customer_to_customer`, `bank_to_cash`, `bank_to_bank`, `customer_withdraw`, `owner_withdraw`. Bank/cash movements (`bank_to_cash`, `bank_to_bank`, `owner_withdraw`) create a `journal` voucher; customer types do NOT. `customer_to_customer` credits `from_customer_id` and debits `to_customer_id` on their computed ledgers.
- Vendors module: `pages/vendors.php` (list + modal add/edit/delete) + `pages/vendor_view.php` (profile + payments). Prefix `VEN` on `vendors.vendor_no`; vendor payments live in `vendor_payments` (FK to `vendors`, optional `bank_id` FK to `banks`). Gated by `vendors.view`/`vendors.manage`, sidebar link under **Parties** submenu.
- General Party module: `pages/general_parties.php` (list, balance = Payable − Paid − Receiving) + `pages/general_party_view.php` (stat cards + Add Entry + computed ledger). Entries in `general_party_entries` with `entry_type ENUM('payable','paid','receiving')`. Every entry posts a balanced `journal` voucher: payable = Debit selected account / Credit party COA `2000-<id>`; paid & receiving = Debit party COA / Credit Cash `1000` or bank `1001-<id>` (auto-created under parent `1000`/`1001`). Party prefix `GP`, entries `GPE`. Deleting an entry deletes its linked voucher. Helpers `general_party_account_id()`, `general_party_bank_account_id()`, `post_party_voucher()` live in `general_party_view.php`. Gated by `general_parties.view`/`.manage`, sidebar link under **Parties**.
- Sidebar (`includes/sidebar.php`) is grouped into accordion submenus (CRM, Real Estate, Parties, Sales, Rentals, Operations, Finance, Reports, System; System has nested Master Data). `submenu_state($keys)` (in `functions.php`) accepts an array of `$active` keys and returns `active` to open+highlight the parent — use it on the parent link when a page lives inside a submenu. Collapse logic in `assets/js/main.js` closes sibling submenus so nested ones stay open.

## Page conventions (follow exactly)

- Every page begins: `require_once '../includes/auth.php'; require_login(); require_permission('<module>.view');` then set `$title` and `$active`, then `include '../includes/header.php';` and end with `include '../includes/footer.php';`.
- Permission slugs are `<module>.view` / `<module>.manage` (e.g. `properties.view`, `properties.manage`). Super admin bypasses all. Add new slugs to `database.sql` seed only if needed — the `permissions`/`role_permissions` tables are pre-seeded.
- POST handling pattern: `if (is_post()) { csrf_check(); ...; flash('success', '...'); redirect('relative_page.php'); }`. Every form must render `<?= csrf_field() ?>`.
- Escape all output with `e()` (alias of `h`). Format with `fmt_money()`, `fmt_date()`, `status_badge()` (badge colors keyed off lowercase snake_case status strings).
- Every table carries `id, created_date, created_time, updated_date, updated_time` (DATE/TIME, not DATETIME). New inserts must set `created_date=CURDATE(), created_time=CURTIME(), updated_date=CURDATE(), updated_time=CURTIME()`; updates set `updated_date=CURDATE(), updated_time=CURTIME()`.
- Statuses are lowercase snake_case strings (e.g. `available`, `booked`, `pending`, `partial`, `paid`). Reuse existing values; if you add a new status, extend the map in `status_badge()`.

## Gotchas

- **Passwords are stored in plaintext** (`admin123` in `database.sql`; `login.php` checks via `hash_equals($row['password'], $password)`). Do not silently "upgrade" to `password_hash` — it requires coordinated changes to the seed data and login.
- `includes/config.php` is machine-generated by `install.php` from a template inside that file. Don't add logic there expecting it to survive a reinstall.
- `install.php` splits `database.sql` on `;` + newline, so SQL statements must each end with `;` followed by a newline. No triggers/procedures with internal semicolons.
- File uploads go through `upload_file()` into `assets/uploads/<subdir>/` and are stored as relative paths (`documents/2026..._xxx.png`), rendered as `BASE_URL/assets/<path>`. Upload dirs are created by `install.php`; `assets/uploads/` is untracked — don't commit uploaded files.
- Cascading dropdowns (blocks/roads/streets by project, areas/societies by city, etc.) are fetched via `pages/ajax.php?action=...&id=...`, which returns JSON. New dependent selects should reuse this file.
- Timezone is `Asia/Karachi`; currency comes from `setting('currency', 'Rs.')` (settings table).
- **`database.sql` only runs on a fresh install** (`install.php`). Schema changes to an already-installed DB must be applied to the live `property_erp` DB too — e.g. via `C:\xampp\mysql\bin\mysql.exe -u root property_erp` (PowerShell can't use `<`; pipe the file in). Keep both in sync (`transfers` table was added this way).
- Bank transfers auto-create chart-of-accounts sub-accounts `1001-<3-digit bank id>` under parent code `1001` (Bank Accounts). Dashboard bank balance aggregates codes `LIKE '1001%'`, so keep bank COA codes prefixed `1001`.
- Customer ledger (`pages/customer_view.php`) is computed, not stored: debit = non-cancelled bookings (`total_price - discount`), credit = receipts, plus `customer_to_customer` transfers. Cancelled bookings drop out automatically — that's how a booking withdraw adjusts the ledger. No per-customer AR accounts in the COA.
- **Sale types on bookings** (`bookings.sale_type ENUM('cash','installment','cash_installment')`): cash = full payment now → property `sold`, booking `completed`, no installment plan; installment / cash_installment = token+booking paid now → property `booked`. **On booking create (not edit), the upfront cash (token+booking) auto-creates a `receipts` row** linked to the paid `booking` installment — this keeps the customer ledger correct. Receipt gets payment method/bank/reference from the Upfront Payment card on the form.
- `tour_guide.php` (root) is a public, login-free user guide (Bootstrap CDN, no header/footer includes) linked from `login.php`.
- No git branching conventions beyond a single commit; commit only when asked.
