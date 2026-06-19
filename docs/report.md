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

## This repo's contribution: a working PHP/MySQL review, then a rework

This went through two rounds. First, Damir asked for an honest code review
of this project plus whatever fixes were needed to make it presentable,
with the explicit framing that it's a learning project, not a real
production site. That review surfaced ten issues, two of them serious
enough that the original would not be safe to deploy or demo publicly
as-is (see Editor's notes #1–10), followed by five more found in a
pre-publish pass (#11–15).

Second, Damir asked to go further: take the (by then bug-free) coursework
PHP and restructure it to look more like junior/mid-level work rather than
coursework PHP specifically. That's Editor's notes #16 onward, and is
summarized in "Architecture" below.

## Repository layout

| Path | Contents |
|---|---|
| [`site/`](site/) | The website itself — see below. |
| [`tools/validate_request.py`](tools/validate_request.py) | A Python mirror of the contact-form validation rules, with unit tests. Predates `site/tests/`; kept since it runs without PHP installed. |

### Inside `site/`

| Path | Contents |
|---|---|
| `index.html`, `about.html`, `contacts.html` | Public pages. |
| `api.php` | Public contact-form endpoint (СРС4/5/6). |
| `login.php` / `logout.php` | Admin session login — see Editor's notes #1. |
| `admin_requests.php` | Read-oriented admin dashboard: status filter pills + pagination over the `requests` table. |
| `requests.php`, `create_request.php`, `edit_request.php`, `delete_request.php` | The create/update/delete side of admin CRUD (СРС6) — `edit_request.php` also changes a request's status. |
| `src/RequestRepository.php` | All SQL for the `requests` table, in one place — see "Architecture". |
| `src/RequestRecord.php` | Immutable read model for one row. |
| `src/RequestValidator.php` | The one validation ruleset, shared by `api.php`/`create_request.php`/`edit_request.php`. |
| `src/RequestStatus.php` | The `new`/`in_progress`/`closed` status codes and their Ukrainian labels. |
| `tests/` | PHPUnit tests for the four classes above. |
| `config/db.php` | PDO connection, reading from `.env`. |
| `config/env.php` | The `.env` loader (`load_env()`/`env()`). |
| `config/auth.php`, `config/admin_credentials.php` | Session/CSRF helpers and the demo admin login (credentials from `.env`). |
| `.env.example` | Template for `.env` (gitignored) — copy it to get started. |
| `composer.json`, `phpunit.xml` | Autoloading (`App\` → `src/`, `Tests\` → `tests/`) and the PHPUnit suite config. |
| `database.sql` | Schema + seed data, import this first. |
| `demo-queries.sql` | Example SELECT/UPDATE/DELETE queries for СРС6 (kept separate from the schema file — see Editor's notes #8). |
| `assets/data/cats.json`, `assets/data/reviews.json` | Static data behind the catalog filter (СРС4) and the reviews loader (ЛР9). |

## Architecture

The original (and the first review pass) had every page running its own
inline `$pdo->prepare(...)` calls, each page re-implementing slightly
different validation rules, and database credentials hardcoded in
`config/db.php`. The rework:

- **One repository class.** `RequestRepository` is the only place that
  knows the `requests` table's SQL. Every page (`admin_requests.php`,
  `requests.php`, `create_request.php`, `edit_request.php`,
  `delete_request.php`, `api.php`) goes through it instead of running its
  own queries. It returns `RequestRecord` value objects (typed, readonly
  properties) rather than raw associative arrays.
- **One validator.** `RequestValidator::validate()` replaced three
  near-duplicate copies of the same rules that had quietly drifted apart
  (e.g. `create_request.php` required a 10-character message,
  `edit_request.php` didn't check message length at all).
- **Config from the environment.** `config/db.php` and
  `config/admin_credentials.php` read from `.env` (via a deliberately tiny
  hand-rolled loader, `config/env.php` — the format needed here didn't
  justify a Composer dependency) instead of having credentials baked into
  the PHP source. `.env.example` ships in the repo; `.env` itself is
  gitignored.
- **CSRF on every state-changing form**, not just delete: `create_request.php`
  and `edit_request.php` now check the same per-session token
  `delete_request.php` already did.
- **A status workflow.** Requests now have a `status`
  (`new`/`in_progress`/`closed`, stored in English, labeled in Ukrainian
  for display — `RequestStatus`). `admin_requests.php` filters by status
  and paginates (10 per page); `edit_request.php` is where status gets
  changed.
- **A real PHPUnit suite.** `site/tests/` tests the actual PHP that ships
  (`RequestRepository`, `RequestRecord`, `RequestValidator`,
  `RequestStatus`) against an in-memory SQLite database, so the tests
  don't need a MySQL server. `tools/validate_request.py`'s Python mirror
  predates this and is kept for environments without PHP.

## Running it locally

This needs a local PHP (8.1+) + MySQL stack (e.g. MAMP/XAMPP, or just
`php -S` for the app server) and Composer.

```sh
cd site
composer install
cp .env.example .env      # adjust if your local DB setup differs
```

1. Import `site/database.sql` into the database named in `.env` (default
   `sphynx_prague`).
2. Serve the `site/` folder, e.g. `php -S localhost:8000` from inside it.
3. Visit `/login.php` to reach the admin panel — default demo credentials
   are `admin` / `sphynx-admin-2026` (see `.env.example`; change both
   before using this anywhere beyond a local demo).

`vendor/bin/phpunit` inside `site/` runs the PHP test suite — **23/23
pass**, no database needed. `python3 -m unittest discover -s tests` inside
`tools/` runs the older Python-side validation tests — **7/7 pass**.

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

### Found on a second pass, after the fixes above

11. **The hero background image was actually broken.** `style.css`'s
    `.hero` rule pointed at `../../image/hero-sphynx.jpg` -- the exact
    `image/` folder removed in note #10 above. The image quietly 404'd in
    every browser; nothing in the HTML pointed this out because it's a CSS
    `url()`, not an `<img>` tag. Fixed to `../images/hero-sphynx.jpg`.
12. **No favicon.** Every page load left a 404 for `/favicon.ico` in the
    console. Added `assets/images/favicon.ico` (cropped from the existing
    logo) and linked it from every page.
13. **Filter buttons had no active state.** The color filter buttons
    (`.filter-btn`) had zero CSS of their own -- clicking one toggled the
    `active` class in JS, but nothing made the selected filter visually
    different from the rest. Added a simple opacity/border treatment for
    `.filter-btn.active`.
14. **The delete button needed a style reset.** Turning "Видалити" from an
    `<a>` into a real `<button>` inside a `<form>` (note #2 above) meant
    it would otherwise pick up the browser's default button chrome instead
    of looking like the plain red inline link it always had. Reset
    `.delete-link`'s background/border/padding/font to match.
15. **Three nav links and one hero CTA pointed at `#` even though the
    content they should point to already existed.** "Каталог сфінксів" in
    the nav (on all three public pages) and the homepage's "ОТРИМАТИ
    КОНСУЛЬТАЦІЮ" button didn't go anywhere. Pointed the catalog links at
    `index.html#catalog` (added that `id`) and the CTA at `contacts.html`.
    The remaining `#` links ("Кошик", "Доставка", per-cat "Дізнатися
    більше") are left as-is -- a shopping cart, a delivery-info page, and
    individual cat detail pages were never part of this course's scope, so
    building them out would be scope creep rather than a fix.

### Architecture rework (after Damir asked to push this toward junior/mid-level quality)

16. **SQL was inline in six different files.** Each of `admin_requests.php`,
    `requests.php`, `create_request.php`, `edit_request.php`,
    `delete_request.php`, and `api.php` ran its own `$pdo->prepare(...)`
    calls against the `requests` table. Extracted into `RequestRepository`
    (`site/src/`), with `RequestRecord` as a typed read model instead of
    raw associative arrays.
17. **Validation rules had drifted apart across three copies.**
    `create_request.php` required a 10-character message; `api.php`
    required one only if non-empty; `edit_request.php` didn't check
    message length at all. All three now call the same
    `RequestValidator::validate()`.
18. **Database and admin credentials were hardcoded in PHP source.**
    `config/db.php` had `root`/`root`/`sphynx_prague` written directly in
    the file; `config/admin_credentials.php` had the demo username and
    password hash the same way. Both now read from `.env` (via a small
    hand-rolled loader -- the format didn't justify a Composer dependency
    like vlucas/phpdotenv), with `.env.example` committed and `.env`
    gitignored.
19. **Only `delete_request.php` had CSRF protection.**
    `create_request.php` and `edit_request.php` POST just as much as
    delete does (creating/editing a customer record), but had no token
    check. Both now verify the same per-session CSRF token delete already
    used.
20. **No workflow once a request existed.** Every request just sat there
    with no way to mark it handled. Added a `status` column
    (`new`/`in_progress`/`closed`), a filter+pagination UI on
    `admin_requests.php`, and a status field on the edit form.
21. **MySQL rejected the new pagination query -- caught only by testing
    against real MySQL, not the SQLite-backed unit tests.**
    `RequestRepository::all()`'s `LIMIT ? OFFSET ?` failed against MySQL
    with a syntax error (`near ''10' OFFSET '0''`): binding plain PHP
    values for those two placeholders makes PDO treat them as quoted
    strings, which MySQL's grammar doesn't accept for `LIMIT`/`OFFSET`
    (SQLite is lenient about this and coerces the strings to integers
    silently, which is exactly why the 23 PHPUnit tests -- which run
    against in-memory SQLite, not MySQL -- didn't catch it). Fixed with an
    explicit `PDO::PARAM_INT` bind on those two parameters. This is the
    reason the live curl-based re-test against real MySQL happened a
    second time, after the architecture changes, instead of trusting the
    unit tests alone.

23 PHPUnit tests, 7 Python tests, and a live MySQL run all pass as of this
rework. None of this changes what the project actually demonstrates
academically
(СРС4–6, ЛР7–10 are all still here, doing what they were meant to do) --
it just makes the demonstration work end-to-end and removes the parts that
would be embarrassing or unsafe to show someone.
