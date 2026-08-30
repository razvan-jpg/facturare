# App Store Connect — abonament iOS

## Product IDs (identice în Xcode + backend)

| Perioadă | Product ID | Preț sugerat USD |
|---|---|---|
| 1 lună | `ro.dateconta.facturare.premium.monthly` | 0.99 |
| 3 luni | `ro.dateconta.facturare.premium.3months` | 2.99 |
| 6 luni | `ro.dateconta.facturare.premium.6months` | 5.99 |
| 1 an | `ro.dateconta.facturare.premium.yearly` | 9.99 |

Toate în **același Subscription Group**: `Facturare Premium`.

## Pași manuali (înainte de submit)

1. **Agreements, Tax, and Banking** — Paid Applications Agreement activ, cont bancar + tax.
2. **App** → **Subscriptions** → creează grupul `Facturare Premium`.
3. Adaugă cele **4** abonamente auto-renewable cu Product ID-urile de mai sus (durate 1 / 3 / 6 / 12 luni).
4. Levels în grup (de sus în jos): Yearly → 6 months → 3 months → Monthly.
5. Localizări RO/EN pentru fiecare.
6. **Sandbox Testers** (Users and Access).
7. **App Store Server Notifications V2** URL:  
   `https://factura.dateconta.ro/api/v1/ios/subscription/notifications`
8. Privacy Policy / Terms pe listing.

## Test local

Xcode: Scheme → Run → Options → StoreKit Configuration → `Products.storekit` (conține toate 4).

## Acces / trial

- Gratuit pentru toți până la **31.03.2027**.
- Din **01.04.2027**: conturi existente → abonament App Store; conturi noi → **1 lună trial** pe server (de la `created_at`), apoi IAP.
- Config: `dateconta.ios_subscription.trial_months_after_promo` (implicit 1). Web rămâne pe 6 luni (`trial_months_after_promo` global).

## Review notes (sugestie)

> App is free until 31 March 2027 for everyone. From 1 April 2027: existing accounts need an auto-renewable subscription in the Facturare Premium group (monthly / 3 months / 6 months / yearly); new accounts get 1 month free trial on iOS, then subscription. iOS IAP is separate from web billing on factura.dateconta.ro (web new accounts get 6 months trial).
