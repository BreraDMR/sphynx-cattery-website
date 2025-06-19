# Sphynx Cattery Website — PHP/MySQL Coursework Project

A small full-stack web project: a fictional Sphynx-cat cattery storefront
("Лисі Котики Прага", Prague) with static pages, a public contact/booking
form, and a session-gated admin panel doing full CRUD over a MySQL table —
built across a series of front-end/back-end web development lab
assignments at Y. O. Paton Vocational College of Welding and Electronics,
specialty 123 Computer Engineering.

This is a learning project, not a real business or a real website anyone
can book a cat through — see [`docs/report.md`](docs/report.md) for what
each part of the course it was built for, and for a full list of the bugs
found during review and fixed for this version (broken admin
authentication, a CSRF-able delete, a reflected XSS, two disconnected data
pipelines, and more).

## Repository layout

| Path | Contents |
|---|---|
| [`site/`](site/) | The website: public pages, the contact-form API, and the admin CRUD panel (PHP + MySQL via PDO). |
| [`docs/report.md`](docs/report.md) | What the project covers, how to run it, and the full editor's-notes list of bugs found and fixed. |
| [`tools/validate_request.py`](tools/validate_request.py) | A Python mirror of the PHP-side form validation rules, with unit tests. |

## What's verified vs. what isn't

- `python3 -m unittest discover -s tests` in `tools/` — **7/7 unit tests
  pass**, covering the contact-form validation rules (name length, email
  format, message length, consent).
- The PHP/MySQL site was run end-to-end against a real local PHP built-in
  server + MySQL during this review (import `database.sql`, `php -S`,
  exercised every route with `curl`): admin pages correctly redirect to
  `login.php` when logged out and show the seeded data once logged in,
  wrong credentials are rejected, deleting without a valid CSRF token
  returns 403, a submission through the public contact form lands in the
  same `requests` table the admin panel reads (confirmed it shows up
  there), a crafted `id` containing a script tag on `edit_request.php` is
  neutralized instead of reflected, and the static pages, catalog/reviews
  JSON, and images all load. That test database and server were torn down
  afterwards -- nothing from that run is in this repo. See
  `docs/report.md` for how to run it yourself.

## License

Licensed under [PolyForm Noncommercial 1.0.0](LICENSE) — free for personal,
educational, and other noncommercial use. For a commercial license,
contact Damir.
