# DateConta Facturare

Aplicație web de facturare pentru firme din România.

- **Brand:** DateConta Facturare  
- **Producție:** https://factura.dateconta.ro  
- **Operator platformă:** FLY DAVID SRL (CUI 38254880)

## Funcționalități V1

- Conturi + gratuit până la 31.03.2027, apoi trial 6 luni pentru conturi noi
- Societăți multiple / switch firmă activă
- Clienți, produse/servicii, lookup CUI ANAF
- Facturi, proforme, avize, chitanțe (draft / emis / anulat)
- Serii, PDF, email, încasări
- Rapoarte (vânzări, încasări, neplatite, pe client, CSV)
- e-Factura: autorizare SPV OAuth (direct sau invitație contabil), UBL CIUS-RO, upload + stare ANAF (manual/auto per firmă)

## Cerințe locale

- PHP 8.1+
- Composer
- Node 20+ (asset-uri)

```bash
composer install
cp .env.example .env
php artisan key:generate
touch database/database.sqlite
php artisan migrate --seed
npm install && npm run build
php artisan serve
```

## Hosting (cPanel)

- Subdomeniu: `factura.dateconta.ro` → `~/factura.dateconta.ro/public`
- PHP 8.1 (LiteSpeed handler `application/x-lsphp81` în `public/.htaccess`)
- MySQL: baza dedicată din cPanel
- `.env` doar pe server (nu în git)

## Monetizare

Config: `config/dateconta.php`

- până la `2027-03-31`: acces full (`free_promo`)
- după `2027-04-01`: trial 6 luni de la crearea contului

## e-Factura / SPV ANAF

1. Înregistrează aplicația OAuth pe portalul ANAF (Dezvoltatori).
2. Callback URL: `https://factura.dateconta.ro/anaf/oauth/callback`
3. Activează dreptul pentru RO e-Factura.
4. În `.env` pe server:

```env
ANAF_ENV=prod
ANAF_CLIENT_ID=...
ANAF_CLIENT_SECRET=...
ANAF_CALLBACK_URL=https://factura.dateconta.ro/anaf/oauth/callback
```

5. În aplicație: Setări societate → Autorizează SPV (certificat) sau Invită contabilul pe email.
6. Pe factură emisă: Trimite e-Factura (sau automat dacă modul e `auto`).
7. Statusul ANAF (`ok` / `nok` / în prelucrare) se actualizează la deschiderea facturii, la buton sau via `php artisan efactura:refresh-statuses`.
