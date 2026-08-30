# Facturare by DateConta — Android

Aplicație Android nativă (Kotlin + Jetpack Compose), echivalentă cu app-ul iOS, sincronizată cu **factura.dateconta.ro**.

## Cerințe

- Android Studio Ladybug (2024.2+) sau mai nou
- JDK 17 (inclus în Android Studio)
- Android SDK 35
- minSdk 26, targetSdk 35

## Deschidere proiect

1. Deschide Android Studio → **Open** → selectează folderul `android/`
2. Așteaptă sync Gradle (descarcă dependențele automat)
3. Rulează pe emulator sau device: **Run ▶**

## Autentificare

- API: `https://factura.dateconta.ro/api/v1`
- Header client: `X-Client: android`
- Cont demo: `demo@dateconta.ro` / `demo1234`

## Funcționalități (paritate iOS)

| Modul | Descriere |
|-------|-----------|
| **Autentificare** | Login, înregistrare, token în EncryptedSharedPreferences |
| **Abonament** | Acces web (AccessGate) + perioadă gratuită; paywall cu link către billing web |
| **Acasă** | Dashboard cu statistici din API |
| **Emite** | Facturi, proforme, avize, chitanțe, încasări, recurente |
| **Liste** | Documente filtrate pe tip |
| **Catalog** | Clienți + produse (offline-first) |
| **Rapoarte** | Sumar vânzări/încasări, sold parteneri |
| **Ajutor / Legal** | HTML din API |
| **Setări** | Profil, sync, e-Factura, firmă nouă, ștergere cont |
| **Admin** | Statistici + căutare firme (doar admin) |

## Arhitectură

- **UI**: Jetpack Compose, Material 3
- **Local DB**: Room (clienți, produse, documente, plăți, serii, outbox)
- **Sync**: outbox push + pull incremental (identic iOS)
- **API**: OkHttp + kotlinx.serialization
- **Navigare**: tabs pe telefon, sidebar pe tabletă (≥600dp)

## Abonament Android vs iOS

- **iOS** folosește App Store IAP (`ios/subscription/*`)
- **Android** folosește **abonamentul web** (middleware `EnsureApiSubscription` verifică `AccessGate` când `X-Client != ios`)
- Google Play Billing este pregătit ca dependență (`billing-ktx`) pentru viitor

## Versiune

- Android: `1.0.001` (build 1) — în `app/build.gradle.kts`
- Web: afișată din API (`app_version`)

## Build release

```bash
cd android
./gradlew assembleRelease
```

APK/AAB în `app/build/outputs/`.

## Note dezvoltare

- La schimbări de schema Room, `fallbackToDestructiveMigration()` șterge datele locale — se resincronizează de pe server (ca pe iOS cu SwiftData reset).
- Pentru Parallels/VM: deschide proiectul din `~/Developer/00 - projects/Facturare/android` (nu din Dropbox).
