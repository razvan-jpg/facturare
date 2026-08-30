#!/usr/bin/env python3
"""Translate public-site JSON keys (EN values) into all UI locales.

Reads /tmp/all-public-i18n-keys.json (ro_key -> en_value).
Writes native translations into lang/{locale}.json for those keys.
Regional variants inherit from their base language when not translated separately.

Usage:
  PYTHONUNBUFFERED=1 python3 scripts/translate_public_i18n.py
  PYTHONUNBUFFERED=1 python3 scripts/translate_public_i18n.py --workers 4
"""

from __future__ import annotations

import argparse
import json
import re
import sys
import threading
import time
from concurrent.futures import ThreadPoolExecutor, as_completed
from pathlib import Path

from deep_translator import GoogleTranslator
from deep_translator.exceptions import (
    LanguageNotSupportedException,
    RequestError,
    TooManyRequests,
    TranslationNotFound,
)

ROOT = Path(__file__).resolve().parents[1]
LANG_DIR = ROOT / "lang"
KEYS_PATH = Path("/tmp/all-public-i18n-keys.json")
PROGRESS_PATH = Path("/tmp/public-i18n-translate-progress.json")
CACHE_PATH = Path("/tmp/public-i18n-translate-cache.json")

LOCK = threading.Lock()

GOOGLE_TARGET: dict[str, str | None] = {
    "am": "am",
    "ar": "ar",
    "az": "az",
    "ban": None,
    "be": "be",
    "bg": "bg",
    "bn": "bn",
    "cs": "cs",
    "da": "da",
    "de": "de",
    "el": "el",
    "en": None,
    "es": "es",
    "et": "et",
    "fa": "fa",
    "fi": "fi",
    "fil": "tl",
    "fr": "fr",
    "ga": "ga",
    "gd": "gd",
    "haw": "haw",
    "he": "iw",
    "hi": "hi",
    "hr": "hr",
    "hu": "hu",
    "hy": "hy",
    "id": "id",
    "it": "it",
    "ja": "ja",
    "ka": "ka",
    "km": "km",
    "ko": "ko",
    "ku": "ku",
    "lb": "lb",
    "lt": "lt",
    "mn": "mn",
    "ms": "ms",
    "nap": None,
    "nb": "no",
    "ne": "ne",
    "nl": "nl",
    "pl": "pl",
    "ps": "ps",
    "pt": "pt",
    "ru": "ru",
    "scn": None,
    "si": "si",
    "sk": "sk",
    "sl": "sl",
    "sm": "sm",
    "so": "so",
    "sq": "sq",
    "sr": "sr",
    "sv": "sv",
    "sw": "sw",
    "th": "th",
    "tr": "tr",
    "uk": "uk",
    "ur": "ur",
    "vi": "vi",
    "zh_CN": "zh-CN",
    "zh_Hans": "zh-CN",
    "zh_TW": "zh-TW",
}

PRIMARY_LOCALES = [
    "de", "fr", "it", "es", "pt", "nl", "pl", "hu", "cs", "sk", "bg", "hr", "sr", "sl",
    "el", "tr", "uk", "ru", "be", "lt", "et",
    "sv", "da", "nb", "fi",
    "ar", "he", "fa", "ur", "hi", "bn", "ne", "si", "th", "vi", "id", "ms", "fil",
    "zh_CN", "zh_TW", "ja", "ko", "ka", "hy", "az", "mn", "km", "ku",
    "sq", "am", "sw", "so", "ps", "sm", "ga", "gd", "haw", "lb",
]
PRIMARY_LOCALES = [c for c in PRIMARY_LOCALES if GOOGLE_TARGET.get(c)]

INHERIT_MAP = {
    "de_AT": "de",
    "de_CH": "de",
    "pt_BR": "pt",
    "pt_AO": "pt",
    "fr_BE": "fr",
    "fr_CD": "fr",
    "fr_CM": "fr",
    "fr_SN": "fr",
    "ko_KP": "ko",
    "sw_TZ": "sw",
    "zh_Hans": "zh_CN",
    "ar_DZ": "ar",
    "ar_EG": "ar",
    "ar_IQ": "ar",
    "ar_JO": "ar",
    "ar_KW": "ar",
    "ar_LB": "ar",
    "ar_MA": "ar",
    "ar_PS": "ar",
    "ar_SD": "ar",
    "ar_SY": "ar",
    "ar_TN": "ar",
    "es_AR": "es",
    "es_BO": "es",
    "es_CL": "es",
    "es_CO": "es",
    "es_CR": "es",
    "es_CU": "es",
    "es_EC": "es",
    "es_MX": "es",
    "es_PA": "es",
    "es_PE": "es",
    "es_UY": "es",
    "es_VE": "es",
    "en_AU": "en",
    "en_GB": "en",
    "en_NG": "en",
    "en_NZ": "en",
    "en_TAS": "en",
    "en_US": "en",
}

TAG_RE = re.compile(r"</?[^>]+>")
PLACEHOLDER_RE = re.compile(r":[A-Za-z_][A-Za-z0-9_]*")


def log(msg: str) -> None:
    print(msg, flush=True)


def protect(text: str) -> tuple[str, list[str]]:
    tokens: list[str] = []

    def stash(m: re.Match[str]) -> str:
        tokens.append(m.group(0))
        return f"⟦{len(tokens) - 1}⟧"

    out = TAG_RE.sub(stash, text)
    out = PLACEHOLDER_RE.sub(stash, out)
    return out, tokens


def restore(text: str, tokens: list[str]) -> str:
    out = text
    for i, tok in enumerate(tokens):
        for marker in (f"⟦{i}⟧", f"[[{i}]]", f"[{i}]", f"({i})"):
            if marker in out:
                out = out.replace(marker, tok)
    out = re.sub(r"\s+</", "</", out)
    return out


def load_json(path: Path) -> dict:
    return json.loads(path.read_text(encoding="utf-8"))


def save_json(path: Path, data: dict) -> None:
    path.write_text(json.dumps(data, ensure_ascii=False, indent=2) + "\n", encoding="utf-8")


def translate_one(target: str, text: str, retries: int = 6) -> str:
    if not text.strip():
        return text
    translator = GoogleTranslator(source="en", target=target)
    delay = 1.0
    last_err: Exception | None = None
    for _ in range(retries):
        try:
            if len(text) > 4500:
                parts = []
                buf = ""
                for sentence in re.split(r"(?<=[.!?])\s+", text):
                    if len(buf) + len(sentence) + 1 > 4200 and buf:
                        parts.append(translator.translate(buf))
                        buf = sentence
                    else:
                        buf = f"{buf} {sentence}".strip()
                if buf:
                    parts.append(translator.translate(buf))
                return " ".join(parts)
            return translator.translate(text)
        except LanguageNotSupportedException:
            raise
        except (TooManyRequests, RequestError, TranslationNotFound) as e:
            last_err = e
            time.sleep(delay)
            delay = min(delay * 1.8, 40)
        except Exception as e:  # noqa: BLE001
            last_err = e
            time.sleep(delay)
            delay = min(delay * 1.8, 40)
    raise RuntimeError(f"translate_one failed for {target}: {last_err}")


def process_locale(
    locale: str,
    keys: dict[str, str],
    protected: list[tuple[str, str, list[str]]],
) -> str:
    gtarget = GOOGLE_TARGET[locale]
    assert gtarget
    log(f"== start {locale} -> {gtarget} ==")

    with LOCK:
        cache = load_json(CACHE_PATH) if CACHE_PATH.exists() else {}
        locale_cache = dict(cache.get(gtarget, {}))
        progress = load_json(PROGRESS_PATH) if PROGRESS_PATH.exists() else {"done": [], "failed": {}}
        if locale in progress.get("done", []):
            log(f"skip done {locale}")
            return f"skip {locale}"

    pending = [(ro, p, toks) for ro, p, toks in protected if p not in locale_cache]
    log(f"  {locale}: pending {len(pending)} / {len(protected)}")

    for n, (ro, p, toks) in enumerate(pending, 1):
        try:
            tr = translate_one(gtarget, p)
        except LanguageNotSupportedException as e:
            with LOCK:
                progress = load_json(PROGRESS_PATH) if PROGRESS_PATH.exists() else {"done": [], "failed": {}}
                progress.setdefault("failed", {})[locale] = str(e)
                save_json(PROGRESS_PATH, progress)
            log(f"  unsupported {locale}: {e}")
            return f"fail {locale}"
        except Exception as e:  # noqa: BLE001
            log(f"  {locale} item fail: {e}")
            tr = p  # keep protected EN; restore later still yields EN
        locale_cache[p] = tr
        if n % 20 == 0 or n == len(pending):
            with LOCK:
                cache = load_json(CACHE_PATH) if CACHE_PATH.exists() else {}
                cache[gtarget] = locale_cache
                save_json(CACHE_PATH, cache)
            log(f"  {locale}: cached {n}/{len(pending)}")
        time.sleep(0.08)

    path = LANG_DIR / f"{locale}.json"
    if not path.exists():
        with LOCK:
            progress = load_json(PROGRESS_PATH) if PROGRESS_PATH.exists() else {"done": [], "failed": {}}
            progress.setdefault("failed", {})[locale] = "missing file"
            save_json(PROGRESS_PATH, progress)
        return f"missing {locale}"

    with LOCK:
        cache = load_json(CACHE_PATH) if CACHE_PATH.exists() else {}
        locale_cache = cache.get(gtarget, locale_cache)
        data = load_json(path)
        for ro, p, toks in protected:
            tr = locale_cache.get(p, keys[ro])
            data[ro] = restore(tr, toks)
        save_json(path, data)
        progress = load_json(PROGRESS_PATH) if PROGRESS_PATH.exists() else {"done": [], "failed": {}}
        if locale not in progress.setdefault("done", []):
            progress["done"].append(locale)
        save_json(PROGRESS_PATH, progress)

    log(f"  wrote {path.name}")
    return f"ok {locale}"


def inherit_variants(keys: dict[str, str]) -> None:
    for dest, src in INHERIT_MAP.items():
        src_path = LANG_DIR / f"{src}.json"
        dest_path = LANG_DIR / f"{dest}.json"
        if not src_path.exists() or not dest_path.exists():
            continue
        src_data = load_json(src_path)
        dest_data = load_json(dest_path)
        for ro in keys:
            if ro in src_data:
                dest_data[ro] = src_data[ro]
        save_json(dest_path, dest_data)
        log(f"inherit {src} -> {dest}")


def main() -> int:
    parser = argparse.ArgumentParser()
    parser.add_argument("--workers", type=int, default=4)
    parser.add_argument("--locales", type=str, default="", help="Comma-separated locale subset")
    args = parser.parse_args()

    if not KEYS_PATH.exists():
        print("Missing", KEYS_PATH, file=sys.stderr)
        return 1

    keys: dict[str, str] = load_json(KEYS_PATH)
    protected: list[tuple[str, str, list[str]]] = []
    for ro, en in keys.items():
        p, toks = protect(en)
        protected.append((ro, p, toks))

    locales = PRIMARY_LOCALES
    if args.locales.strip():
        locales = [x.strip() for x in args.locales.split(",") if x.strip()]

    progress = load_json(PROGRESS_PATH) if PROGRESS_PATH.exists() else {"done": [], "failed": {}}
    todo = [loc for loc in locales if loc not in progress.get("done", [])]
    log(f"Keys: {len(protected)}; todo locales: {len(todo)}; workers: {args.workers}")

    if not todo:
        inherit_variants(keys)
        log("DONE (nothing pending)")
        return 0

    workers = max(1, min(args.workers, len(todo)))
    with ThreadPoolExecutor(max_workers=workers) as pool:
        futures = [pool.submit(process_locale, loc, keys, protected) for loc in todo]
        for fut in as_completed(futures):
            try:
                log(fut.result())
            except Exception as e:  # noqa: BLE001
                log(f"worker error: {e}")

    inherit_variants(keys)
    log("DONE")
    return 0


if __name__ == "__main__":
    raise SystemExit(main())
