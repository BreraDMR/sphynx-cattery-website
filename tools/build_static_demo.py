#!/usr/bin/env python3
"""Snapshot the PHP site into a plain static folder that any static host
(Vercel, GitHub Pages, ...) can serve without PHP or MySQL.

How it works, roughly:
  1. spin up a throwaway MySQL (own datadir + port, deleted afterwards),
     load site/database.sql into it and start `php -S` on top of it;
  2. crawl every public page in every locale (?lang=en|cs|uk) plus each
     kitten/treat page, and save the JSON the catalog script fetches;
  3. rewrite links so the result works as files: cat.php?slug=x -> cat-x.html,
     English at the root, Czech under /cs/, Ukrainian under /uk/;
  4. pages that need a server (login, cart, admin...) become a short
     "this is a static demo" page so nothing in the menu leads to a 404.

The normal PHP site isn't touched at all, everything happens on the copy.

Usage (from the repo root):
    python3 tools/build_static_demo.py                # does everything itself
    python3 tools/build_static_demo.py --base-url http://127.0.0.1:8090
                                                      # reuse a site you already run
Output goes to dist-static/ (git-ignored).
"""

from __future__ import annotations

import argparse
import json
import os
import re
import shutil
import socket
import subprocess
import sys
import tempfile
import time
import urllib.error
import urllib.parse
import urllib.request
from pathlib import Path

REPO = Path(__file__).resolve().parent.parent
SITE = REPO / "site"
REPO_URL = "https://github.com/BreraDMR/sphynx-cattery-website"

LOCALES = ["en", "cs", "uk"]
# English sits at the root so the bare domain just works
PREFIX = {"en": "/", "cs": "/cs/", "uk": "/uk/"}

PUBLIC_PAGES = ["index", "about", "delivery", "contacts", "treats"]
# everything here needs a session or a database write, so in the static copy
# it turns into the "static demo" notice instead of a dead link
SERVER_PAGES = [
    "login", "register", "logout", "account", "cart", "oauth",
    "admin_requests", "requests", "create_request", "edit_request",
    "delete_request",
]

TEXT = {
    "en": {
        "banner": "Static demo: browsing works, but sign-in, the cart, the contact form and the admin panel are switched off.",
        "banner_link": "Source & screenshots",
        "title": "This part needs the full version",
        "body": "You're looking at a static copy of the site, made for a quick look around. Accounts, the cart, booking requests and the role-gated admin panel need the PHP + MySQL backend, so they're switched off here.",
        "more": "The full version, with screenshots of the admin panel and instructions to run it locally, is on GitHub:",
        "back": "← Back to the catalog",
        "form": "This is a static demo, so the form doesn't send anything. In the full version the request lands in the admin panel and pings the team in Telegram.",
        "nf_title": "Page not found",
    },
    "cs": {
        "banner": "Statické demo: procházení funguje, ale přihlášení, košík, kontaktní formulář a administrace jsou vypnuté.",
        "banner_link": "Zdrojový kód a snímky",
        "title": "Tahle část potřebuje plnou verzi",
        "body": "Díváte se na statickou kopii webu určenou k rychlému prohlédnutí. Účty, košík, rezervace a administrace s rolemi potřebují backend v PHP + MySQL, proto jsou tady vypnuté.",
        "more": "Plná verze se snímky administrace a návodem na lokální spuštění je na GitHubu:",
        "back": "← Zpět do katalogu",
        "form": "Tohle je statické demo, formulář nic neodesílá. V plné verzi žádost přistane v administraci a tým dostane upozornění v Telegramu.",
        "nf_title": "Stránka nenalezena",
    },
    "uk": {
        "banner": "Статичне демо: переглядати можна, але вхід, кошик, форма заявки й адмінка вимкнені.",
        "banner_link": "Код і скриншоти",
        "title": "Ця частина працює лише в повній версії",
        "body": "Це статична копія сайту для швидкого перегляду. Акаунти, кошик, заявки й адмінка з ролями потребують бекенду на PHP + MySQL, тому тут вони вимкнені.",
        "more": "Повна версія зі скриншотами адмінки та інструкцією, як запустити локально, — на GitHub:",
        "back": "← Назад до каталогу",
        "form": "Це статичне демо, форма нічого не надсилає. У повній версії заявка потрапляє в адмінку, а команда отримує сповіщення в Telegram.",
        "nf_title": "Сторінку не знайдено",
    },
}

BANNER_STYLE = (
    "background:#2b2b2b;color:#fff;text-align:center;font-size:14px;"
    "padding:8px 16px;line-height:1.4"
)


# ── throwaway backend ──────────────────────────────────────────────────────
def free_port() -> int:
    with socket.socket() as s:
        s.bind(("127.0.0.1", 0))
        return s.getsockname()[1]


def wait_for(check, what: str, timeout: float = 40) -> None:
    deadline = time.time() + timeout
    while time.time() < deadline:
        if check():
            return
        time.sleep(0.5)
    sys.exit(f"gave up waiting for {what}")


class Backend:
    """Temp MySQL + php -S. Everything lives in one temp dir that's wiped on exit."""

    def __init__(self) -> None:
        # short path on purpose: mysqld refuses socket paths over ~100 chars
        self.tmp = tempfile.TemporaryDirectory(prefix="sph", dir="/tmp")
        self.procs: list[subprocess.Popen] = []
        self.base_url = ""

    def __enter__(self) -> "Backend":
        for tool in ("mysqld", "mysql", "php"):
            if not shutil.which(tool):
                sys.exit(f"{tool} not found on PATH")
        if not (SITE / "vendor" / "autoload.php").exists():
            subprocess.run(["composer", "install", "-q", "--no-dev"], cwd=SITE, check=True)

        tmp = Path(self.tmp.name)
        datadir = tmp / "data"
        db_port = free_port()
        subprocess.run(
            ["mysqld", "--no-defaults", "--initialize-insecure", f"--datadir={datadir}"],
            check=True, capture_output=True,
        )
        log = open(tmp / "mysqld.log", "w")
        self.procs.append(subprocess.Popen(
            ["mysqld", "--no-defaults", f"--datadir={datadir}", f"--port={db_port}",
             "--bind-address=127.0.0.1", f"--socket={tmp / 'my.sock'}", "--mysqlx=OFF",
             f"--pid-file={tmp / 'mysqld.pid'}"],
            stdout=log, stderr=log,
        ))
        mysql = ["mysql", "--no-defaults", "-uroot", "-h127.0.0.1", f"-P{db_port}"]
        wait_for(lambda: subprocess.run(mysql + ["-e", "select 1"], capture_output=True).returncode == 0,
                 "mysqld")
        subprocess.run(mysql + ["-e", "CREATE DATABASE sphynx_prague CHARACTER SET utf8mb4 "
                                      "COLLATE utf8mb4_unicode_ci"], check=True)
        with open(SITE / "database.sql", "rb") as dump:
            subprocess.run(mysql + ["sphynx_prague"], stdin=dump, check=True)

        web_port = free_port()
        env = dict(os.environ)
        # the DSN is built as "mysql:host={DB_HOST};dbname=..." so the port can ride along here,
        # and real env vars win over .env, so a developer's own .env doesn't get in the way
        env.update(DB_HOST=f"127.0.0.1;port={db_port}", DB_NAME="sphynx_prague",
                   DB_USER="root", DB_PASSWORD="")
        self.procs.append(subprocess.Popen(
            ["php", "-S", f"127.0.0.1:{web_port}", "-t", str(SITE)],
            env=env, stdout=subprocess.DEVNULL, stderr=subprocess.DEVNULL,
        ))
        self.base_url = f"http://127.0.0.1:{web_port}"
        wait_for(lambda: fetch_status(self.base_url + "/about.php") == 200, "php -S")
        return self

    def __exit__(self, *exc) -> None:
        for proc in reversed(self.procs):
            proc.terminate()
            try:
                proc.wait(timeout=30)
            except subprocess.TimeoutExpired:
                proc.kill()
        self.tmp.cleanup()


# ── crawling ───────────────────────────────────────────────────────────────
def fetch(url: str) -> bytes:
    with urllib.request.urlopen(url, timeout=30) as resp:
        return resp.read()


def fetch_status(url: str) -> int:
    try:
        with urllib.request.urlopen(url, timeout=5) as resp:
            return resp.status
    except urllib.error.HTTPError as err:
        return err.code
    except OSError:
        return 0


def static_name(path: str, query: dict[str, list[str]]) -> tuple[str, str]:
    """Map a PHP URL to (file name, leftover query string)."""
    page = path.rsplit("/", 1)[-1] or "index.php"
    page = page.removesuffix(".php")
    if page in ("cat", "treat") and query.get("slug"):
        return f"{page}-{query['slug'][0]}.html", ""
    if page == "contacts" and query.get("cat"):
        # the contact page reads ?cat= in JS to prefill the message, keep it
        return "contacts.html", "?" + urllib.parse.urlencode({"cat": query["cat"][0]})
    return f"{page}.html", ""


def rewrite_url(url: str, locale: str) -> str:
    if url.startswith(("http://", "https://", "mailto:", "tel:", "#", "data:", "javascript:")):
        return url
    # assets and the favicon are shared by all locales, so point at the root copy
    if url.startswith("assets/"):
        return "/" + url
    parts = urllib.parse.urlsplit(url)
    if not parts.path.endswith(".php"):
        return url
    query = urllib.parse.parse_qs(parts.query)
    target = query.pop("lang", [locale])[0]
    name, rest = static_name(parts.path, query)
    if name == "index.html":
        name = ""
    fragment = f"#{parts.fragment}" if parts.fragment else ""
    if target != locale or parts.path.startswith("/"):
        # language switcher links come out absolute, keep them that way
        return f"{PREFIX[target]}{name}{rest}{fragment}"
    return f"{name or './'}{rest}{fragment}"


ATTR_RE = re.compile(r'(\s(?:href|src|action)=")([^"]*)(")')


def banner_html(locale: str) -> str:
    t = TEXT[locale]
    return (f'<div class="static-demo-banner" style="{BANNER_STYLE}">{t["banner"]} '
            f'<a href="{REPO_URL}#screenshots" style="color:#ffcc80" target="_blank" '
            f'rel="noopener">{t["banner_link"]}</a></div>')


# Swallows contact-form submits before script.js sees them (capture phase on
# document runs before the form's own listener) and says why nothing was sent.
FORM_GUARD = """<script>
document.addEventListener('submit', function (e) {
    if (e.target && e.target.id === 'contactForm') {
        e.preventDefault();
        e.stopPropagation();
        var box = document.getElementById('formMessage');
        if (box) { box.textContent = 'ℹ️ ' + %s; box.style.color = '#1565c0'; }
    }
}, true);
</script>"""


def postprocess(html: str, locale: str) -> str:
    html = ATTR_RE.sub(lambda m: m.group(1) + rewrite_url(m.group(2).replace("&amp;", "&"), locale)
                       .replace("&", "&amp;") + m.group(3), html)
    html = html.replace("<body>", "<body>\n" + banner_html(locale), 1)
    guard = FORM_GUARD % json.dumps(TEXT[locale]["form"], ensure_ascii=False)
    html = html.replace("</body>", guard + "\n</body>", 1)
    return html


def notice_page(template: str, locale: str, title: str, body_html: str) -> str:
    """Reuse a rendered page's header/footer, swap the main content."""
    start = template.index('<main class="container">') + len('<main class="container">')
    end = template.rindex("</main>")
    main = f"""
<section class="hero">
    <div class="container">
        <h1>{title}</h1>
    </div>
</section>
<section>
    <div class="container">
        <article class="card">
            {body_html}
        </article>
    </div>
</section>
"""
    html = template[:start] + main + template[end:]
    # the template's <title> belongs to the About page
    return re.sub(r"<title>.*?</title>", f"<title>{title}</title>", html, count=1, flags=re.S)


def notice_body(locale: str) -> str:
    t = TEXT[locale]
    return (f"<p>{t['body']}</p>\n"
            f'            <p>{t["more"]} <a href="{REPO_URL}#screenshots">{REPO_URL}</a></p>\n'
            f'            <p><a class="button" href="./#catalog">{t["back"]}</a></p>')


def patch_script(js: str) -> str:
    """The catalog script uses page-relative URLs; make them work from /cs/ and /uk/ too."""
    swaps = [
        ("fetch('api/cats.php')", "fetch('/api/cats.json')"),
        ("fetch('api/treats.php')", "fetch('/api/treats.json')"),
        ("fetch('assets/data/reviews.json')", "fetch('/assets/data/reviews.json')"),
        ("cat.php?slug=${encodeURIComponent(cat.slug)}", "cat-${encodeURIComponent(cat.slug)}.html"),
        ("treat.php?slug=${encodeURIComponent(treat.slug)}",
         "treat-${encodeURIComponent(treat.slug)}.html"),
        ('href="login.php"', 'href="login.html"'),
    ]
    for old, new in swaps:
        if old not in js:
            # script.js changed under us -- better to fail loudly than ship dead links
            sys.exit(f"script.js no longer contains {old!r}, update patch_script()")
        js = js.replace(old, new)
    return js


def absolute_photos(payload: dict) -> dict:
    for key in ("cats", "treats"):
        for item in payload.get(key, []):
            if isinstance(item.get("photo"), str) and item["photo"].startswith("assets/"):
                item["photo"] = "/" + item["photo"]
    return payload


def build(base_url: str, out: Path) -> None:
    if out.exists():
        shutil.rmtree(out)
    out.mkdir(parents=True)

    shutil.copytree(SITE / "assets", out / "assets")
    script = out / "assets" / "js" / "script.js"
    script.write_text(patch_script(script.read_text(encoding="utf-8")), encoding="utf-8")

    (out / "api").mkdir()
    slugs: dict[str, list[str]] = {}
    for kind in ("cats", "treats"):
        payload = absolute_photos(json.loads(fetch(f"{base_url}/api/{kind}.php")))
        if payload.get("status") != "success":
            sys.exit(f"api/{kind}.php answered {payload}")
        slugs[kind] = [item["slug"] for item in payload[kind]]
        (out / "api" / f"{kind}.json").write_text(
            json.dumps(payload, ensure_ascii=False), encoding="utf-8")

    pages = 0
    for locale in LOCALES:
        target = out / PREFIX[locale].strip("/")
        target.mkdir(parents=True, exist_ok=True)

        urls = {f"{p}.html": f"/{p}.php" for p in PUBLIC_PAGES}
        urls |= {f"cat-{s}.html": f"/cat.php?slug={urllib.parse.quote(s)}" for s in slugs["cats"]}
        urls |= {f"treat-{s}.html": f"/treat.php?slug={urllib.parse.quote(s)}" for s in slugs["treats"]}

        for name, path in urls.items():
            sep = "&" if "?" in path else "?"
            html = fetch(f"{base_url}{path}{sep}lang={locale}").decode("utf-8")
            (target / name).write_text(postprocess(html, locale), encoding="utf-8")
            pages += 1

        about = fetch(f"{base_url}/about.php?lang={locale}").decode("utf-8")
        for page in SERVER_PAGES:
            html = notice_page(about, locale, TEXT[locale]["title"], notice_body(locale))
            # postprocess turns the switcher's /about.php?lang=xx into the About page;
            # point it at the same notice page in the other language instead
            html = postprocess(html, locale)
            for other in LOCALES:
                html = html.replace(f'href="{PREFIX[other]}about.html"', f'href="{PREFIX[other]}{page}.html"')
            (target / f"{page}.html").write_text(html, encoding="utf-8")
            pages += 1

        if locale == "en":
            html = notice_page(about, locale, TEXT[locale]["nf_title"], notice_body(locale))
            (out / "404.html").write_text(postprocess(html, locale), encoding="utf-8")
            pages += 1

    print(f"done: {pages} pages in {out}")


def main() -> None:
    parser = argparse.ArgumentParser(description=__doc__.splitlines()[0])
    parser.add_argument("--base-url", help="use an already running copy of the PHP site")
    parser.add_argument("--out", default=str(REPO / "dist-static"))
    args = parser.parse_args()
    out = Path(args.out).resolve()

    if args.base_url:
        build(args.base_url.rstrip("/"), out)
    else:
        with Backend() as backend:
            build(backend.base_url, out)


if __name__ == "__main__":
    main()
