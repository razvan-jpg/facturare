#!/usr/bin/env python3
"""Capture local DateConta UI screenshots + short clips for the promo video."""

from __future__ import annotations

import re
from pathlib import Path

from playwright.sync_api import sync_playwright

ROOT = Path(__file__).resolve().parents[1]
OUT = Path(__file__).resolve().parent / "promo-assets" / "screens"
BASE = "http://127.0.0.1:8765"


def load_seed_creds() -> tuple[str, str]:
    text = (ROOT / "database" / "seeders" / "DatabaseSeeder.php").read_text(encoding="utf-8")
    email = re.search(r"'email'\s*=>\s*'([^']+)'", text)
    password = re.search(r"Hash::make\('([^']+)'\)", text)
    if not email or not password:
        raise RuntimeError("Could not parse seed credentials from DatabaseSeeder.php")
    return email.group(1), password.group(1)


def main() -> None:
    OUT.mkdir(parents=True, exist_ok=True)
    clips_dir = OUT / "clips"
    clips_dir.mkdir(parents=True, exist_ok=True)
    for old in clips_dir.glob("*"):
        old.unlink()

    email, password = load_seed_creds()

    with sync_playwright() as p:
        browser = p.chromium.launch(channel="chrome", headless=True)
        context = browser.new_context(
            viewport={"width": 1280, "height": 800},
            device_scale_factor=2,
            record_video_dir=str(clips_dir),
            record_video_size={"width": 1280, "height": 800},
        )
        page = context.new_page()

        page.goto(BASE + "/", wait_until="networkidle", timeout=60000)
        page.screenshot(path=str(OUT / "01-landing.png"), full_page=False)
        print("landing", page.url)

        page.goto(BASE + "/login", wait_until="networkidle", timeout=60000)
        page.screenshot(path=str(OUT / "02-login.png"), full_page=False)

        page.fill('input[name="email"], #email, input[type="email"]', email)
        page.fill('input[name="password"], #password, input[type="password"]', password)
        page.locator('button[type="submit"]').first.click()
        page.wait_for_timeout(2500)
        page.screenshot(path=str(OUT / "03-after-login.png"), full_page=False)
        print("after_login", page.url)

        hrefs = page.eval_on_selector_all(
            "nav a[href], aside a[href], a[href]",
            "els => [...new Set(els.map(e => e.getAttribute('href')).filter(Boolean))]",
        )
        print("hrefs", hrefs[:40])

        candidates = []
        for h in hrefs:
            if not isinstance(h, str):
                continue
            if h.startswith("http") or h.startswith("#") or h.startswith("mailto:"):
                continue
            low = h.lower()
            if any(k in low for k in ("dashboard", "document", "client", "compan", "raport", "incasar", "factur")):
                candidates.append(h)

        # Always try common routes
        for h in ["/dashboard", "/documents", "/clients", "/companies", "/reports"]:
            if h not in candidates:
                candidates.append(h)

        seen = set()
        idx = 4
        for h in candidates:
            if h in seen:
                continue
            seen.add(h)
            url = h if h.startswith("http") else BASE + (h if h.startswith("/") else "/" + h)
            try:
                page.goto(url, wait_until="networkidle", timeout=30000)
                page.wait_for_timeout(700)
                # skip login redirect
                if "/login" in page.url:
                    print("skip_auth", h)
                    continue
                name = f"{idx:02d}-{re.sub(r'[^a-z0-9]+', '-', h.strip('/').lower())[:40] or 'page'}"
                page.screenshot(path=str(OUT / f"{name}.png"), full_page=False)
                print("shot", name, page.url)
                idx += 1
                if idx > 12:
                    break
            except Exception as e:
                print("fail", h, e)

        # Interaction pass for video motion
        for path in ["/documents", "/dashboard", "/clients"]:
            try:
                page.goto(BASE + path, wait_until="domcontentloaded", timeout=30000)
                page.wait_for_timeout(600)
                page.mouse.wheel(0, 350)
                page.wait_for_timeout(700)
                page.mouse.wheel(0, -180)
                page.wait_for_timeout(700)
            except Exception as e:
                print("interact_fail", path, e)

        # Prefer a "new document" CTA if present
        for label in ["Factură nouă", "Document nou", "Adaugă factură", "Adaugă", "Emite"]:
            loc = page.get_by_role("link", name=re.compile(label, re.I))
            if loc.count():
                loc.first.click()
                page.wait_for_timeout(1200)
                page.screenshot(path=str(OUT / "13-new-document.png"), full_page=False)
                print("cta", label)
                break

        page.wait_for_timeout(1000)
        context.close()
        browser.close()

    print("pngs", sorted(p.name for p in OUT.glob("*.png")))
    print("clips", sorted(p.name for p in clips_dir.glob("*")))


if __name__ == "__main__":
    main()
