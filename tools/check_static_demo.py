#!/usr/bin/env python3
"""Link check for dist-static/: serves it locally and requests every page,
every same-site href/src found in them, the catalog JSON and the photos and
detail pages the catalog script builds from that JSON. Exits 1 on any miss.

    python3 tools/check_static_demo.py [dist-static]
"""

from __future__ import annotations

import functools
import http.server
import json
import re
import sys
import threading
import urllib.error
import urllib.parse
import urllib.request
from pathlib import Path

REPO = Path(__file__).resolve().parent.parent
ATTR_RE = re.compile(r'\s(?:href|src)="([^"]*)"')


class QuietHandler(http.server.SimpleHTTPRequestHandler):
    def log_message(self, *args) -> None:  # one line per request is just noise here
        pass


def main() -> None:
    root = Path(sys.argv[1] if len(sys.argv) > 1 else REPO / "dist-static").resolve()
    handler = functools.partial(QuietHandler, directory=str(root))
    server = http.server.ThreadingHTTPServer(("127.0.0.1", 0), handler)
    threading.Thread(target=server.serve_forever, daemon=True).start()
    base = f"http://127.0.0.1:{server.server_address[1]}"

    pages = sorted(p.relative_to(root).as_posix() for p in root.rglob("*.html"))
    targets: dict[str, str] = {}
    for page in pages:
        page_url = f"{base}/{page}"
        targets.setdefault(page_url, "(page)")
        html = (root / page).read_text(encoding="utf-8")
        for raw in ATTR_RE.findall(html):
            url = raw.replace("&amp;", "&")
            if url.startswith(("http://", "https://", "mailto:", "tel:", "#", "data:", "javascript:")):
                continue
            full = urllib.parse.urljoin(page_url, url).split("#")[0]
            targets.setdefault(full, page)

    # what script.js renders client-side from the JSON
    for kind, prefix in (("cats", "cat"), ("treats", "treat")):
        targets.setdefault(f"{base}/api/{kind}.json", "(script.js)")
        for item in json.loads((root / "api" / f"{kind}.json").read_text(encoding="utf-8"))[kind]:
            targets.setdefault(urllib.parse.urljoin(base + "/", item["photo"]), f"api/{kind}.json")
            for loc in ("", "cs/", "uk/"):
                targets.setdefault(f"{base}/{loc}{prefix}-{item['slug']}.html", "(script.js card)")
    targets.setdefault(f"{base}/assets/data/reviews.json", "(script.js)")

    bad = []
    for url, source in sorted(targets.items()):
        try:
            with urllib.request.urlopen(url, timeout=10) as resp:
                code = resp.status
        except urllib.error.HTTPError as err:
            code = err.code
        if code != 200:
            bad.append((code, url.removeprefix(base), source))
    server.shutdown()

    print(f"{len(pages)} pages, {len(targets)} unique URLs checked, {len(bad)} broken")
    for code, url, source in bad:
        print(f"  {code} {url}  <- {source}")
    sys.exit(1 if bad else 0)


if __name__ == "__main__":
    main()
