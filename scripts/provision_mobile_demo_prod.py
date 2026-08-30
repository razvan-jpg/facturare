#!/usr/bin/env python3
"""Provision Firma Demo DateConta for demo@dateconta.ro on production API."""

from __future__ import annotations

import json
import sys
import urllib.error
import urllib.request
from datetime import date, timedelta

BASE = "https://factura.dateconta.ro/api/v1"
EMAIL = "demo@dateconta.ro"
PASSWORD = "DemoDateConta1!"


def req(method: str, path: str, token: str | None = None, company_id: int | None = None, body: dict | None = None):
    data = None if body is None else json.dumps(body).encode()
    headers = {"Accept": "application/json", "Content-Type": "application/json"}
    if token:
        headers["Authorization"] = f"Bearer {token}"
    if company_id is not None:
        headers["X-Company-Id"] = str(company_id)
    request = urllib.request.Request(f"{BASE}/{path.lstrip('/')}", data=data, headers=headers, method=method)
    try:
        with urllib.request.urlopen(request) as resp:
            raw = resp.read().decode()
            return resp.status, json.loads(raw) if raw else {}
    except urllib.error.HTTPError as e:
        raw = e.read().decode()
        try:
            payload = json.loads(raw) if raw else {}
        except json.JSONDecodeError:
            payload = {"raw": raw}
        raise RuntimeError(f"{method} {path} -> {e.code}: {payload}") from e


def d(days: int = 0) -> str:
    return (date.today() + timedelta(days=days)).isoformat()


def main() -> int:
    _, auth = req("POST", "login", body={"email": EMAIL, "password": PASSWORD, "device_name": "provision"})
    token = auth["token"]
    companies = {c["name"]: c for c in auth.get("companies", [])}
    print("login companies:", [(c["id"], c["name"]) for c in auth.get("companies", [])])

    if "Firma Demo DateConta" in companies:
        company_id = companies["Firma Demo DateConta"]["id"]
        print(f"reusing company #{company_id}")
    else:
        _, created = req(
            "POST",
            "companies",
            token=token,
            body={
                "name": "Firma Demo DateConta",
                "cui": "90000001",
                "reg_com": "J40/DEMO/2026",
                "address": "Str. Demo nr. 10",
                "city": "București",
                "county": "București",
                "country": "România",
                "iban": "RO49AAAA1B31007593840000",
                "bank_name": "Banca Demo",
                "vat_payer": True,
                "default_vat_rate": 21,
            },
        )
        company_id = created["data"]["id"]
        print(f"created company #{company_id}")

    req("POST", f"companies/{company_id}/switch", token=token, company_id=company_id)
    cid = company_id

    _, clients = req("GET", "clients", token=token, company_id=cid)
    client = next((c for c in clients.get("data", []) if c.get("name") == "Client Demo SRL"), None)
    if not client:
        _, client_resp = req(
            "POST",
            "clients",
            token=token,
            company_id=cid,
            body={
                "name": "Client Demo SRL",
                "type": "company",
                "cui": "12345678",
                "reg_com": "J40/1234/2020",
                "address": "Str. Exemplu nr. 1",
                "city": "București",
                "county": "București",
                "country": "România",
                "email": "client@example.com",
                "iban": "RO49AAAA1B31007593840001",
                "bank_name": "Banca Client",
            },
        )
        client = client_resp["data"]
    client_id = client["id"]
    print("client", client_id)

    _, products_resp = req("GET", "products", token=token, company_id=cid)
    products = {p["name"]: p for p in products_resp.get("data", [])}

    def ensure_product(name: str, unit: str, price: float) -> dict:
        if name in products:
            return products[name]
        _, resp = req(
            "POST",
            "products",
            token=token,
            company_id=cid,
            body={"name": name, "unit": unit, "price": price, "vat_rate": 21, "type": "service"},
        )
        products[name] = resp["data"]
        return resp["data"]

    consulting = ensure_product("Servicii consultanță", "ore", 250)
    hosting = ensure_product("Abonament hosting", "lună", 120)
    support = ensure_product("Suport tehnic", "ore", 180)

    _, docs = req("GET", "documents?type=invoice", token=token, company_id=cid)
    invoices = [d for d in docs.get("data", []) if d.get("type") == "invoice" and d.get("status") == "issued"]
    if invoices:
        print(f"company already has {len(invoices)} issued invoices — skipping document seed")
    else:
        def issue(doc_type: str, issue_date: str, due_date: str, notes: str, items: list) -> dict:
            _, resp = req(
                "POST",
                "documents",
                token=token,
                company_id=cid,
                body={
                    "type": doc_type,
                    "client_id": client_id,
                    "issue_date": issue_date,
                    "due_date": due_date,
                    "currency": "RON",
                    "notes": notes,
                    "action": "issue",
                    "items": items,
                },
            )
            return resp["data"]

        inv1 = issue(
            "invoice",
            d(-45),
            d(-30),
            "Consultanță Q1 — plată parțială card",
            [{
                "product_id": consulting["id"],
                "name": consulting["name"],
                "unit": "ore",
                "quantity": 10,
                "unit_price": 250,
                "vat_rate": 21,
            }],
        )
        req(
            "POST",
            "payments",
            token=token,
            company_id=cid,
            body={
                "document_id": inv1["id"],
                "method": "card",
                "paid_at": d(-40),
                "amount": round(float(inv1["total"]) * 0.4, 2),
                "reference": "CARD-DEMO-001",
                "notes": "Plată parțială cu card",
            },
        )

        inv2 = issue(
            "invoice",
            d(-28),
            d(-13),
            "Hosting + suport — avans OP",
            [
                {
                    "product_id": hosting["id"],
                    "name": hosting["name"],
                    "unit": "lună",
                    "quantity": 3,
                    "unit_price": 120,
                    "vat_rate": 21,
                },
                {
                    "product_id": support["id"],
                    "name": support["name"],
                    "unit": "ore",
                    "quantity": 4,
                    "unit_price": 180,
                    "vat_rate": 21,
                },
            ],
        )
        req(
            "POST",
            "payments",
            token=token,
            company_id=cid,
            body={
                "document_id": inv2["id"],
                "method": "op",
                "paid_at": d(-20),
                "amount": round(float(inv2["total"]) * 0.5, 2),
                "reference": "OP-DEMO-001",
                "notes": f"Avans factură {inv2.get('number_full')}",
            },
        )

        inv3 = issue(
            "invoice",
            d(-18),
            d(-3),
            "Servicii punctuale — chitanță parțială",
            [{
                "product_id": consulting["id"],
                "name": consulting["name"],
                "unit": "ore",
                "quantity": 6,
                "unit_price": 250,
                "vat_rate": 21,
            }],
        )
        _, series = req("GET", "series", token=token, company_id=cid)
        receipt_series = next((s["prefix"] for s in series.get("data", []) if s.get("type") == "receipt" and s.get("active")), None)
        if not receipt_series:
            raise RuntimeError(f"no receipt series: {series}")
        req(
            "POST",
            "payments/collect",
            token=token,
            company_id=cid,
            body={
                "client_id": client_id,
                "instrument": "receipt",
                "series": receipt_series,
                "paid_at": d(-10),
                "amount": round(float(inv3["total"]) * 0.35, 2),
                "currency": "RON",
                "document_language": "ro",
                "reprezentand": f"Încasare parțială {inv3.get('number_full')}",
                "invoice_ids": [inv3["id"]],
                "include_opening": False,
            },
        )

        inv4 = issue(
            "invoice",
            d(-12),
            d(3),
            "Pachet mix — card + OP",
            [
                {
                    "product_id": consulting["id"],
                    "name": consulting["name"],
                    "unit": "ore",
                    "quantity": 8,
                    "unit_price": 250,
                    "vat_rate": 21,
                },
                {
                    "product_id": hosting["id"],
                    "name": hosting["name"],
                    "unit": "lună",
                    "quantity": 1,
                    "unit_price": 120,
                    "vat_rate": 21,
                },
            ],
        )
        req(
            "POST",
            "payments",
            token=token,
            company_id=cid,
            body={
                "document_id": inv4["id"],
                "method": "card",
                "paid_at": d(-8),
                "amount": round(float(inv4["total"]) * 0.25, 2),
                "reference": "CARD-DEMO-002",
            },
        )
        req(
            "POST",
            "payments",
            token=token,
            company_id=cid,
            body={
                "document_id": inv4["id"],
                "method": "op",
                "paid_at": d(-5),
                "amount": round(float(inv4["total"]) * 0.3, 2),
                "reference": "OP-DEMO-002",
            },
        )

        issue(
            "invoice",
            d(-4),
            d(11),
            "Factură recentă — neîncasată",
            [{
                "product_id": support["id"],
                "name": support["name"],
                "unit": "ore",
                "quantity": 5,
                "unit_price": 180,
                "vat_rate": 21,
            }],
        )

        for notes, days_ago, due_in, items in [
            (
                "Proformă consultanță proiect nou",
                20,
                10,
                [{
                    "product_id": consulting["id"],
                    "name": consulting["name"],
                    "unit": "ore",
                    "quantity": 12,
                    "unit_price": 250,
                    "vat_rate": 21,
                }],
            ),
            (
                "Proformă abonament anual hosting + suport",
                7,
                23,
                [
                    {
                        "product_id": hosting["id"],
                        "name": hosting["name"],
                        "unit": "lună",
                        "quantity": 12,
                        "unit_price": 120,
                        "vat_rate": 21,
                    },
                    {
                        "product_id": support["id"],
                        "name": support["name"],
                        "unit": "ore",
                        "quantity": 2,
                        "unit_price": 180,
                        "vat_rate": 21,
                    },
                ],
            ),
            (
                "Proformă draft emisă recent",
                2,
                28,
                [{
                    "product_id": consulting["id"],
                    "name": consulting["name"],
                    "unit": "ore",
                    "quantity": 4,
                    "unit_price": 250,
                    "vat_rate": 21,
                }],
            ),
        ]:
            issue("proforma", d(-days_ago), d(due_in), notes, items)

    _, recurring = req("GET", "recurring", token=token, company_id=cid)
    if recurring.get("data"):
        print(f"already has {len(recurring['data'])} recurring")
    else:
        for payload in [
            {
                "client_id": client_id,
                "title": "Hosting lunar Client Demo",
                "subscription_number": "ABO-DEMO-001",
                "frequency": "monthly",
                "start_date": d(-60),
                "next_run_date": (date.today().replace(day=1) + timedelta(days=32)).replace(day=1).isoformat(),
                "due_days": 15,
                "currency": "RON",
                "auto_issue": True,
                "active": True,
                "notes": "Factură recurentă hosting lunar",
                "items": [{
                    "product_id": hosting["id"],
                    "name": hosting["name"],
                    "unit": "lună",
                    "quantity": 1,
                    "unit_price": 120,
                    "vat_rate": 21,
                }],
            },
            {
                "client_id": client_id,
                "title": "Suport trimestrial Client Demo",
                "subscription_number": "ABO-DEMO-002",
                "frequency": "quarterly",
                "start_date": d(-30),
                "next_run_date": (date.today().replace(day=1) + timedelta(days=70)).replace(day=1).isoformat(),
                "due_days": 30,
                "currency": "RON",
                "auto_issue": False,
                "active": True,
                "notes": "Pachet suport trimestrial",
                "items": [{
                    "product_id": support["id"],
                    "name": support["name"],
                    "unit": "ore",
                    "quantity": 10,
                    "unit_price": 180,
                    "vat_rate": 21,
                }],
            },
            {
                "client_id": client_id,
                "title": "Pachet consultanță + hosting",
                "subscription_number": "ABO-DEMO-003",
                "frequency": "monthly",
                "start_date": date.today().replace(day=1).isoformat(),
                "next_run_date": (date.today().replace(day=1) + timedelta(days=32)).replace(day=1).isoformat(),
                "due_days": 10,
                "currency": "RON",
                "auto_issue": True,
                "active": True,
                "notes": "Abonament mixt lunar",
                "items": [
                    {
                        "product_id": hosting["id"],
                        "name": hosting["name"],
                        "unit": "lună",
                        "quantity": 1,
                        "unit_price": 120,
                        "vat_rate": 21,
                    },
                    {
                        "product_id": support["id"],
                        "name": "Retainer suport",
                        "unit": "ore",
                        "quantity": 2,
                        "unit_price": 180,
                        "vat_rate": 21,
                    },
                ],
            },
        ]:
            req("POST", "recurring", token=token, company_id=cid, body=payload)

    _, me = req("GET", "me", token=token, company_id=cid)
    print("current_company_id", me["user"].get("current_company_id"))
    print("companies", [(c["id"], c["name"]) for c in me.get("companies", [])])
    print("done")
    return 0


if __name__ == "__main__":
    try:
        raise SystemExit(main())
    except Exception as exc:  # noqa: BLE001
        print(f"ERROR: {exc}", file=sys.stderr)
        raise SystemExit(1)
