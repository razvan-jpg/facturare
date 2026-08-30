# Facturare by DateConta (iOS / iPadOS)

Aplicație nativă SwiftUI care se sincronizează cu [factura.dateconta.ro](https://factura.dateconta.ro) prin API-ul `/api/v1` (Laravel Sanctum).

## Funcții

- Login / înregistrare pe același cont ca pe web
- Multi-societate + drepturi (`company_user`)
- Offline-first: clienți, produse, documente (ciorne), încasări în SwiftData + outbox
- Sync push/pull către server; emiterea cu număr de serie se finalizează pe server
- Facturi / proforme / avize / chitanțe, storno, notă de creditare, PDF
- e-Factura (trimitere + refresh; autorizare SPV în Safari)
- Recurente, rapoarte, setări firmă, utilizatori
- Abonament App Store: gratuit până la 31.03.2027, apoi 1 / 3 / 6 / 12 luni (StoreKit; separat de web)

## Credentiale demo

După `php artisan db:seed --class=MobileDemoSeeder` (sau `db:seed`):

| Câmp | Valoare |
|------|---------|
| Email | `demo@dateconta.ro` |
| Parolă | `DemoDateConta1!` |
| Firmă | Firma Demo DateConta (Client Demo SRL, facturi/proforme/recurente) |

## API

Base URL (producție): `https://factura.dateconta.ro/api/v1`

Header-e:
- `Authorization: Bearer {token}`
- `X-Company-Id: {company_id}`
- `X-Client: ios` (gate abonament iOS pe API)

## Abonament App Store

Vezi [APP_STORE_CONNECT.md](APP_STORE_CONNECT.md). Product IDs: `…monthly`, `…3months`, `…6months`, `…yearly`.

Test local: Scheme → Run → Options → StoreKit Configuration → `Products.storekit`.

Backend: `php artisan migrate` (câmpuri `ios_*` pe `users`) + cert `storage/certs/AppleRootCA-G3.pem`.

## Deschidere în Xcode

```bash
open "ios/Facturare by DateConta/Facturare by DateConta.xcodeproj"
```

Target: iPhone + iPad. Rulează pe simulator sau device cu team-ul de dezvoltare deja configurat în proiect.

## Deploy backend

1. Deploy codul Laravel (rute `api/v1`, middleware, seeder).
2. `php artisan migrate` (tabela `personal_access_tokens` dacă lipsește).
3. `php artisan db:seed --class=MobileDemoSeeder`
4. `php artisan config:clear`
