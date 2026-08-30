#!/usr/bin/env python3
"""Write today's promo pack under marketing/campanie-lansare-15-aug/zilnic/."""

from __future__ import annotations

from datetime import date, datetime
from pathlib import Path
from zoneinfo import ZoneInfo

ROOT = Path(__file__).resolve().parent
OUT = ROOT / "zilnic"
LAUNCH = date(2026, 8, 15)
TZ = ZoneInfo("Europe/Bucharest")

POSTS = {
    date(2026, 8, 7): (
        "Hey! Ai văzut aplicația asta? 👀\n"
        "DateConta Facturare — facturi online pentru firme din România.\n"
        "Gratuit până la 31 martie 2027.\n"
        "Lansare oficială: 15 august, ora 10:00.\n"
        "👉 https://factura.dateconta.ro"
    ),
    date(2026, 8, 8): (
        "Mai stai cu facturile în Word / Excel?\n"
        "În DateConta Facturare emiti facturi, proforme, avize și chitanțe în câteva minute.\n"
        "PDF + email + încasări — totul într-un singur loc.\n"
        "Lansare: 15 august 2026, ora 10.\n"
        "https://factura.dateconta.ro"
    ),
    date(2026, 8, 9): (
        "Promisiune agresivă 💥\n"
        "Vrem să devenim cel mai bun și cel mai ieftin soft de facturare de pe piață.\n"
        "Acum: GRATUIT până la 31.03.2027.\n"
        "Apoi de la 1,99 EUR / lună + TVA.\n"
        "Lansare 15 august, ora 10 → https://factura.dateconta.ro"
    ),
    date(2026, 8, 10): (
        "Uite cum arată în aplicație: dashboard, facturi, clienți, încasări.\n"
        "Poți crea cont și testa chiar acum.\n"
        "iOS App Store: SOON (anunțăm pe site).\n"
        "Web live: https://factura.dateconta.ro\n"
        "15 august = lansarea oficială 🚀"
    ),
    date(2026, 8, 11): (
        "Prelansare în toi.\n"
        "Cine își face cont acum prinde perioada gratuită până la 31.03.2027.\n"
        "Lansarea oficială: 15 august, ora 10:00.\n"
        "https://factura.dateconta.ro"
    ),
    date(2026, 8, 12): (
        "Ai firmă? Ai prieteni cu firmă?\n"
        "În DateConta fiecare societate are cod promoțional.\n"
        "Ei +2 săptămâni · Tu +1 lună la fiecare 2 societăți aduse.\n"
        "Lansare 15 aug · https://factura.dateconta.ro"
    ),
    date(2026, 8, 13): (
        "SOON !!! 📱\n"
        "Lucrăm la app-ul DateConta pentru iPhone și iPad.\n"
        "Lansarea în App Store o anunțăm pe site.\n"
        "Până atunci, web-ul e gata.\n"
        "Lansare oficială 15 august, ora 10.\n"
        "https://factura.dateconta.ro"
    ),
    date(2026, 8, 14): (
        "MÂINE. ⏰\n"
        "15 august 2026, ora 10:00 — lansarea oficială DateConta Facturare.\n"
        "Cont gratuit până la 31.03.2027.\n"
        "https://factura.dateconta.ro"
    ),
    date(2026, 8, 15): (
        "E LIVE. 🚀\n"
        "DateConta Facturare — lansare oficială.\n"
        "Facturi online pentru firme din România.\n"
        "Gratuit până la 31.03.2027.\n"
        "Creează cont acum: https://factura.dateconta.ro"
    ),
}

HASHTAGS = (
    "#DateConta #Facturare #FacturiOnline #SRL #PFA "
    "#AntreprenoriRomania #SoftFacturare #Lansare #BusinessRomania"
)


def main() -> None:
    today = datetime.now(TZ).date()
    OUT.mkdir(parents=True, exist_ok=True)
    path = OUT / f"{today.isoformat()}.md"

    if today > LAUNCH:
        path.write_text(
            f"# Campanie încheiată ({today.isoformat()})\n\n"
            "Lansarea a trecut (15.08.2026). Oprește automatizarea programată.\n",
            encoding="utf-8",
        )
        print(path)
        return

    days_left = (LAUNCH - today).days
    caption = POSTS.get(today) or POSTS[date(2026, 8, 7)]
    body = f"""# DateConta — pachetul zilei ({today.isoformat()})

Zile până la lansare: **{days_left}** (15.08.2026, ora 10:00)

## De postat acum (organic, gratuit)
1. TikTok — video promo + caption
2. Instagram Reels + Story (link în bio)
3. Facebook Pagină
4. LinkedIn (opțional)
5. WhatsApp Status

## Caption (TikTok / Reels / FB)
{caption}

{HASHTAGS}

## Video
`/Users/razvanivan/Desktop/DateConta-Facturare-Lansare-Promo.mp4`

## Link
https://factura.dateconta.ro

## Checklist
- [ ] TikTok publicat
- [ ] Instagram Reels + Story
- [ ] Facebook
- [ ] Răspuns la comentarii (în ziua respectivă)
- [ ] Bio IG are linkul corect
"""
    path.write_text(body, encoding="utf-8")
    print(path)


if __name__ == "__main__":
    main()
