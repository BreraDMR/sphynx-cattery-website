# Static demo build

A copy of the site that runs without PHP or MySQL, so it can sit on any static
host (Vercel, GitHub Pages, Netlify) and people can click around the catalog.

What's in it: home page with the kitten catalog, filters and search, treats,
every kitten and treat page, About, Delivery, Contacts — in English (root),
Czech (`/cs/`) and Ukrainian (`/uk/`), with the language switcher working.

What's not: anything that needs a session or writes to the database — sign-in,
registration, OAuth, the cart, the booking form and the admin panel. Those
links open a short "this needs the full version" page instead of a 404, the
contact form says nothing was sent, and every page has a thin banner saying
it's a static demo.

## Build

Needs `php`, `mysqld`/`mysql` (Homebrew is fine) and Composer on the PATH.

```bash
python3 tools/build_static_demo.py   # spins up a throwaway MySQL + php -S, crawls, cleans up
python3 tools/check_static_demo.py   # serves dist-static/ locally and checks every link
```

The result lands in `dist-static/` (git-ignored). Already running the site
yourself? Point the script at it: `--base-url http://127.0.0.1:8080`.

## Deploy

```bash
cd dist-static && vercel deploy --prod
```

It's plain files, no build step and no framework preset needed.
