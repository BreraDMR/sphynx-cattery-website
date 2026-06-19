# Sphynx Cattery Website — PHP/MySQL Coursework Project

A coursework project ("курсовий проєкт") built incrementally across a series
of lab assignments (ЛР7–ЛР10) and independent-study tasks (СРС4–СРС6) for a
front-end/back-end web development course at Y. O. Paton Vocational
College of Welding and Electronics, specialty 123 Computer Engineering.

- **Student:** Damir

The site is a fictional storefront for a (fictional) Sphynx-cat cattery
("Лисі Котики Прага" — "Hairless Kitties Prague") based in the Czech
Republic — there is no real business behind it. It demonstrates static
pages (HTML/CSS/JS), a public contact/booking form backed by PHP, and a
small admin CRUD panel backed by MySQL via PDO.

## What the original assignment covered

- **ЛР7** — a time-of-day greeting on the homepage (JS).
- **ЛР8** — a mobile nav menu toggle (JS).
- **СРС4** — a client-side cat catalog with search/filter.
- **ЛР9** — loading customer reviews via the Fetch API.
- **ЛР10** — a contact form submitted to a PHP endpoint via `fetch()`.
- **СРС5** — storing form data in a PHP session.
- **СРС6** — a MySQL `requests` table with full CRUD (create/read/update/
  delete) through an admin-style page set, accessed via PDO.

## This repo's contribution: a working PHP/MySQL review

Damir asked for an honest code review of this project plus whatever fixes
were needed to make it presentable, with the explicit framing that it's a
learning project, not a real production site. The review surfaced ten
issues, two of them serious enough that the original would not be safe to
deploy or demo publicly as-is. All ten are fixed in this version — see
"Editor's notes" below for the full list and what changed.

## Repository layout

| Path | Contents |
|---|---|
| [`site/`](site/) | The website itself — see below. |
| [`tools/validate_request.py`](tools/validate_request.py) | A Python mirror of the contact-form validation rules in `site/api.php`, with unit tests (this repo's environment doesn't have PHP installed, so this is how the validation logic gets an automated check). |

### Inside `site/`

| Path | Contents |
|---|---|
| `index.html`, `about.html`, `contacts.html` | Public pages. |
| `api.php` | Public contact-form endpoint (СРС4/5/6). |
| `login.php` / `logout.php` | Admin session login — new, see Editor's notes #1. |
| `admin_requests.php`, `requests.php`, `create_request.php`, `edit_request.php`, `delete_request.php` | Admin CRUD over the `requests` table (СРС6) — now behind the login above. |
| `config/db.php` | PDO connection (default local MAMP-style credentials — not a secret, this is meant to be run locally). |
| `config/auth.php`, `config/admin_credentials.php` | Session/CSRF helpers and the demo admin login. |
| `database.sql` | Schema + seed data, import this first. |
| `demo-queries.sql` | Example SELECT/UPDATE/DELETE queries for СРС6 (kept separate from the schema file — see Editor's notes #8). |
| `assets/data/cats.json`, `assets/data/reviews.json` | Static data behind the catalog filter (СРС4) and the reviews loader (ЛР9). |

## Running it locally

This needs a local PHP + MySQL stack (e.g. MAMP/XAMPP/`php -S` with a MySQL
server). With one running:

1. Import `site/database.sql` into MySQL (creates and seeds the `requests`
   table).
2. Point `site/config/db.php` at your database name if it isn't
   `sphynx_prague`.
3. Serve the `site/` folder (e.g. `php -S localhost:8000` from inside it).
4. Visit `/login.php` to reach the admin panel — default demo credentials
   are `admin` / `sphynx-admin-2026` (see `config/admin_credentials.php`;
   change both before using this anywhere beyond a local demo).

`python3 -m unittest discover -s tests` inside `tools/` runs the validation
unit tests — **7/7 pass**, no PHP or database needed for that part.

## Editor's notes — what was found and fixed

1. **Admin panel had no authentication.** `admin_requests.php` (and the
   rest of the CRUD pages) showed every customer's name/email/phone/message
   to anyone who opened the URL — it was even linked from the public nav
   bar as "Заявки". Fixed by adding a session-based login
   (`login.php`/`logout.php`, `config/auth.php`) and gating
   `admin_requests.php`, `requests.php`, `create_request.php`,
   `edit_request.php`, and `delete_request.php` behind it.
2. **Deleting a request worked over a plain GET link, with no CSRF
   protection.** `delete_request.php?id=5` deleted row 5 immediately; the
   `confirm()` dialog on the link only stops an accidental click in a real
   browser, not a forged request (e.g. an `<img>` tag on another page
   pointing at that URL). Fixed: deletion now requires a POST with a
   per-session CSRF token, submitted via a real form.
3. **Reflected XSS in `edit_request.php`.** The page header echoed
   `$_GET['id']` directly into the HTML. Because MySQL's loose
   string-to-int comparison matches a numeric-prefixed string against an
   `INT` column (`'5<script>...'` still matches `id = 5`), a crafted link
   to an existing row's id could have its payload reflected and executed.
   Fixed by casting `$id` to `int` immediately after reading it.
4. **The public contact form and the admin panel were two disconnected
   systems.** `contacts.html`'s form posted to `api.php`, which only wrote
   to a local log file (`data/data.txt`). The admin pages
   (`admin_requests.php`, `requests.php`) read from the MySQL `requests`
   table instead — so a real visitor's submission would never show up in
   the admin panel at all. Fixed: `api.php` now inserts into `requests`
   (in addition to the log file, which is kept for the original СРС4 "save
   to a file" exercise).
5. **Several form fields were silently dropped.** `contacts.html` collects
   phone (marked required), desired kitten age, desired color, and a
   personal-data consent checkbox, but the JS only ever sent `name`,
   `email`, and `message` to `api.php`. Fixed: the fetch payload now
   includes all of them, `api.php` validates and stores them, and
   `database.sql` gained `age`, `color`, and `consent` columns to match.
6. **Validation ran after escaping, not before.** `api.php` called
   `htmlspecialchars()` on the name/email *before* checking their length,
   so e.g. a name containing `<` would be inflated by HTML-entity encoding
   before the "longer than two characters" check ever saw it. Fixed by
   validating the raw trimmed input first and escaping only where a value
   is actually rendered into HTML (the log line; on-page output already
   went through `htmlspecialchars()` correctly in the admin views).
7. **Two interactive sections rendered UI with no logic behind it.** The
   "Всі кошенята в наявності" search/filter block and the "Відгуки
   клієнтів (Fetch API)" reviews loader both had working buttons and empty
   containers, but `script.js` had only a placeholder comment for each
   ("тут твій код..."). Fixed by adding `assets/data/cats.json` /
   `reviews.json` and the corresponding fetch/render/filter logic in
   `script.js`.
8. **`database.sql` deleted its own seed data.** The original file
   inserted three example rows, then ended with a `DELETE FROM requests
   WHERE id = 3` as an SQL demo — so following the file's own instructions
   ("імпортуй... щоб додати тестові записи") left only two of the three
   advertised rows. Fixed by moving the demo SELECT/UPDATE/DELETE queries
   into a separate `demo-queries.sql`, leaving `database.sql` as a clean
   schema+seed file.
9. **Manager photos didn't match their captions.** `manager1.jpg` was
   captioned "Анна Новак" (a female name) but showed a stock photo of a
   man; `manager2.jpg` ("Петро Свобода") was a stock photo of a four-person
   meeting, not a portrait. Both were stock photos with no connection to
   real people, but mismatched all the same. Replaced with simple
   generated initials avatars.
10. **Repo hygiene.** Removed an exact-duplicate `image/` folder (the site
    already had everything under `assets/images/`, just inconsistently
    referenced by both paths across different pages), three unused
    `chinaz*.ico` favicon files left over from an icon-generator tool, and
    local test-run log files (`data.txt`, `data/data.txt`) that had
    accumulated real-looking test submissions (including what looked like
    a personal email address) and should never have been committed.

None of this changes what the project actually demonstrates academically
(СРС4–6, ЛР7–10 are all still here, doing what they were meant to do) --
it just makes the demonstration work end-to-end and removes the parts that
would be embarrassing or unsafe to show someone.
