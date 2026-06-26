> 🌐 **Live demo (auto-updated each restart):** https://reliance-around-kelkoo-cure.trycloudflare.com  <!-- live-url -->

# Sphynx Cattery Website — PHP/MySQL

A full-stack web project: a fictional Sphynx-cat cattery storefront
("Лисі Котики Прага", Prague) with a multilingual front end, two MySQL-backed
catalogs (kittens and treats), user accounts, a shopping cart, a public
contact/booking form, and a role-gated admin panel doing full CRUD over the
database. New catalog cards are added from a phone through a companion
Telegram bot, [sphynx-cats-crm-bot](https://github.com/BreraDMR/sphynx-cats-crm-bot).

It started as a series of front-end/back-end lab assignments at
Y. O. Paton Vocational College of Welding and Electronics (specialty 123,
Computer Engineering) and was then grown into something closer to a real
product, end to end. It's a **learning/portfolio project**, not a real
business — you can't actually buy a cat here.

## The problems it solves

The interesting part of this project isn't the cat storefront — it's the set
of concrete problems each feature was added to fix.

- **Listings were written by non-native, non-copywriter admins → typos and
  clumsy phrasing on a public page.** Every kitten/treat description added
  through the bot is first run past a **local AI model (Ollama)** for a
  grammar/style pass. The admin sees their text and the AI's suggestion
  **side by side and chooses** — the AI never silently overwrites anything.
  Cheap, private (runs on our own hardware, no cloud), and it keeps the
  catalog readable without hiring an editor.
- **The "speed vs. quality" tradeoff for that AI check.** A small model is
  instant but plain; a bigger model writes better but is slower on CPU. So
  the bot lets each admin **pick the model per-account** (`/model`): a fast
  `qwen2.5:3b` or a smarter `qwen2.5:14b`. Both stay loaded at once, so
  switching is free.
- **Updating the catalog required a laptop and the web admin panel.** Now a
  card — photo included — is added straight from Telegram on a phone, by any
  **owner-approved** admin, with no shared password to leak.
- **The audience is in three countries.** The whole UI is localized in
  **English (default), Czech and Ukrainian**, switchable with one click; the
  choice sticks across pages and visits.
- **Customer leads were visible to everyone.** The contact-form/booking
  submissions used to sit behind a link in the public menu. They're now
  behind real **user accounts with roles** — only an `admin` sees the
  "Requests" tab and pages at all.
- **Visitors had no way to collect what they liked.** Logged-in users get a
  **cart** (kittens + treats), and "checkout" turns the cart into a booking
  request that lands in the admin panel and pings the team in Telegram —
  reusing the existing request pipeline instead of inventing a new one.
- **Two product lines, one pattern.** Treats ("вкусняшки") are a second
  catalog that deliberately mirrors the kitten one (same repository/validator
  shape, same bot-driven workflow, same public read API), showing the design
  scales to more than one thing without copy-paste drift.

## How sign-in works

- **Email + password** registration/login is the always-on path (bcrypt,
  CSRF-protected, session-based).
- **"Sign in with Google / GitHub"** is implemented as a real OAuth 2.0
  authorization-code flow (no SDK, just a few cURL calls in `oauth.php`). The
  buttons are **feature-flagged**: they only appear once a provider's
  client id/secret are configured, because real OAuth needs a **stable public
  URL** to register its redirect. On the demo's throwaway tunnel URL they stay
  hidden and email/password is used instead.

## Tech

PHP 8.3 + MySQL/MariaDB (PDO), no framework — one file per route, with SQL
isolated in repository classes (`src/`) and a flat-key i18n layer
(`config/i18n.php` + `lang/{en,cs,uk}.php`). Catalogs render client-side from
JSON APIs (`api/cats.php`, `api/treats.php`); the cart is a small JSON API
(`api/cart.php`). Runs as Docker containers (PHP-Apache + MariaDB) behind a
Cloudflare tunnel.

## Repository layout

| Path | Contents |
|---|---|
| [`site/`](site/) | The website. `src/` — repository/validator/record classes (Cat, Treat, User, Cart, Request). `config/` — bootstrap, db, auth (sessions+roles), i18n, oauth, labels. `lang/` — EN/CS/UK dictionaries. `api/` — public catalog + cart JSON endpoints (catalog writes are bot-only, gated by `X-API-Key`). `tests/` — PHPUnit suite. |
| [`docs/report.md`](docs/report.md) | What each part of the course it covers, plus the editor's-notes log of bugs found and fixed during review. |
| [`tools/validate_request.py`](tools/validate_request.py) | A Python mirror of the form-validation rules (predates the PHPUnit suite; kept because it needs no PHP). |

## Running it locally

```sh
cd site
composer install
cp .env.example .env          # defaults match a local MAMP/XAMPP MySQL install
# import database.sql into the database named in .env, then:
php -S localhost:8000
vendor/bin/phpunit            # 61 tests, in-memory SQLite, no MySQL needed
```

Demo admin login at `/login.php`: **`admin@sphynx.local` / `sphynx-admin-2026`**
(seeded by `database.sql`; change it before any real exposure). Regular
visitors just register at `/register.php`. OAuth sign-in stays hidden until
`SITE_BASE_URL` + provider credentials are set in `.env` (see `.env.example`).

## What's verified

- **`vendor/bin/phpunit` — 61 tests pass**, covering the Request/Cat/Treat
  repositories (CRUD, slug generation, publish/draft visibility), the cart
  (quantity rules, per-user counts), the User repository (email/OAuth lookup,
  linking) and every validator, against in-memory SQLite.
- The full stack (i18n, accounts+roles, cart add/checkout, treats, admin-only
  requests) was run end-to-end against the live Docker deployment and passed
  a scripted in-container smoke test; the strong AI model was verified fixing
  a Ukrainian listing over the same endpoint the bot uses.

## License

Licensed under [PolyForm Noncommercial 1.0.0](LICENSE) — free for personal,
educational, and other noncommercial use. For a commercial license, contact
Damir.
