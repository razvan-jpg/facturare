<?php

/**
 * Istoric versiuni — cele mai recente primele.
 * Prima intrare = versiunea curentă a aplicației (footer + config('dateconta.version')).
 * La fiecare bump: `php artisan app:bump-version --title="..." --notes="..." --notes="..."`
 */
return [
    [
        'version' => '1.0.262',
        'date' => '2026-08-30',
        'title' => 'Penalități detaliate pe fișa clientului',
        'changes' => [
            'Pe Fișă client (ecran și PDF) apar toate penalitățile: cele nefacturate cu roșu („Nefacturate”), cele facturate cu referință la factura pe care au fost puse.',
            'Manual Clienți actualizat.',
        ],
    ],
    [
        'version' => '1.0.261',
        'date' => '2026-08-30',
        'title' => 'Penalități sold: tranșe din abonamentul recurent',
        'changes' => [
            'Soldul inițial se împarte automat după valoarea lunară din recurente (ex. REVITECH 2420, ROMABCOM 2662); restul neînmulțit exact devine tranșă parțială.',
            'Penalitățile au fost recalculate pentru toți clienții cu procent setat.',
            'Manual Clienți actualizat.',
        ],
    ],
    [
        'version' => '1.0.260',
        'date' => '2026-08-30',
        'title' => 'Penalități pe sold inițial în tranșe lunare',
        'changes' => [
            'Soldul inițial poate fi configurat ca N tranșe lunare egale (ex. 10 × 242 RON), cu scadențe pe data de 11 și ultima la 11.08.2026.',
            'Plățile pe sold acoperă întâi cea mai veche tranșă; penalitățile se calculează pe fiecare tranșă de la scadența ei.',
            'Manual Clienți actualizat.',
        ],
    ],
    [
        'version' => '1.0.259',
        'date' => '2026-08-29',
        'title' => 'Toggle facturare penalități pe fișa clientului',
        'changes' => [
            'Pe Fișă client, comutatorul „Se calculeaza / factureaza” (ON/OFF) se poate schimba direct — fără a intra în Editează.',
            'Manual Clienți actualizat.',
        ],
    ],
    [
        'version' => '1.0.258',
        'date' => '2026-08-29',
        'title' => 'Dashboard: penalități nefacturate',
        'changes' => [
            'Widget nou pe Dashboard: clienții cu penalități calculate până azi și încă nefacturate, sortați descrescător (fără cei cu sumă 0).',
            'Manual Dashboard actualizat.',
        ],
    ],
    [
        'version' => '1.0.257',
        'date' => '2026-08-29',
        'title' => 'Penalități nefacturate în lista de clienți',
        'changes' => [
            'În Catalog → Clienți, coloana Penalități arată sumele calculate până azi și încă nefacturate, inclusiv când comutatorul „Se calculeaza / factureaza” e OFF.',
        ],
    ],
    [
        'version' => '1.0.256',
        'date' => '2026-08-29',
        'title' => 'Preview factură recurentă cu penalități',
        'changes' => [
            'Pe abonament: buton Preview factură — PDF al următorului document, inclusiv penalitățile nefacturate (dacă toggle-ul clientului e ON), fără salvare.',
            'Pe fișa abonamentului apare suma penalităților nefacturate care vor fi adăugate la următoarea factură.',
        ],
    ],
    [
        'version' => '1.0.255',
        'date' => '2026-08-29',
        'title' => 'Penalități: calcul și facturare pe următoarea factură',
        'changes' => [
            'Cu procent setat, penalitățile se calculează pe zi pe sold (scadență 11.08.2026) și facturi cu scadență ≥ 11.08.2026, fără compounding pe linii de tip penalizare.',
            'Comutator ON: liniile (fără TVA) apar pe următoarea factură emisă; OFF: calculul continuă, fără facturare. Pe Fișă client vezi nefacturate / facturate / încasate.',
        ],
    ],
    [
        'version' => '1.0.254',
        'date' => '2026-08-29',
        'title' => 'Penalități pe fișa de client',
        'changes' => [
            'Pe fișa / editarea clientului: câmp „Procent penalizare cf contract” și comutator „Se calculeaza / factureaza” (implicit OFF).',
            'Valorile apar pe Fișă client; pot fi modificate din Editează.',
        ],
    ],
    [
        'version' => '1.0.253',
        'date' => '2026-08-18',
        'title' => 'iOS: ștergere cont și review App Store',
        'changes' => [
            'În aplicația iPhone/iPad poți șterge contul din Setări (confirmare cu parolă), conform cerințelor App Store.',
            'Rutele de status abonament iOS și contul demo pentru review (abonament expirat) sunt pregătite pe server.',
        ],
    ],
    [
        'version' => '1.0.252',
        'date' => '2026-08-16',
        'title' => 'Banner Atrafic pe toate paginile',
        'changes' => [
            'Bannerul Atrafic apare pe întregul site (aplicație + pagini publice), tot după Accept toate la cookie-uri.',
        ],
    ],
    [
        'version' => '1.0.251',
        'date' => '2026-08-16',
        'title' => 'Consent Mode SEE + banner cookie',
        'changes' => [
            'Banner cookie: Accept toate / Doar esențiale; Google Consent Mode v2 (ad_storage etc. denied până la accept).',
            'Google Ads, Trafic.ro și Atrafic se activează doar după consimțământ marketing; Politica de confidențialitate actualizată.',
        ],
    ],
    [
        'version' => '1.0.250',
        'date' => '2026-08-16',
        'title' => 'Google Ads (gtag) pe site',
        'changes' => [
            'Tag Google Ads (AW-762310422) pe toate paginile — tracking page view pentru campanii.',
            'Evenimentele de conversie (ex. înregistrare) se pot adăuga ulterior cu label din Google Ads.',
        ],
    ],
    [
        'version' => '1.0.249',
        'date' => '2026-08-16',
        'title' => 'Trafic.ro pe toate paginile',
        'changes' => [
            'Contor Trafic.ro pe întregul site (aplicație autentificată + pagini publice), nu doar pentru vizitatori.',
            'Badge discret în footerul aplicației și pe layout-ul de autentificare.',
        ],
    ],
    [
        'version' => '1.0.248',
        'date' => '2026-08-16',
        'title' => 'Trafic.ro pe paginile publice',
        'changes' => [
            'Contor și badge Trafic.ro pe landing, autentificare, prețuri, lansare, FAQ/ghiduri și pagini legale (doar vizitatori).',
        ],
    ],
    [
        'version' => '1.0.247',
        'date' => '2026-08-16',
        'title' => 'Banner Atrafic pe landing și login',
        'changes' => [
            'Slot reclamă Atrafic (discreț) pe pagina principală (doar vizitatori) și pe autentificare, deasupra footerului / sub formular.',
        ],
    ],
    [
        'version' => '1.0.246',
        'date' => '2026-08-16',
        'title' => 'SEO meta, sitemap și viteză mobil',
        'changes' => [
            'OG/Twitter pe /preturi și /lansare; meta description pe paginile legale; sitemap cu /legal, /legal/livrare, /legal/anulare.',
            'Logo-uri UI mai mici (96/192 + OG JPEG), pause poll /comunitate-stats și fireworks când tab-ul e ascuns.',
        ],
    ],
    [
        'version' => '1.0.245',
        'date' => '2026-08-16',
        'title' => 'FAQ public și ghiduri SEO',
        'changes' => [
            'Pagini publice crawlable: Întrebări frecvente (/intrebari-frecvente), ghid Cum emiți e-Factura și Proformă vs factură.',
            'Linkuri din landing, prețuri și manualul Ajutor; sitemap actualizat (în afara /ajutor).',
        ],
    ],
    [
        'version' => '1.0.244',
        'date' => '2026-08-14',
        'title' => 'iOS: 1 lună test după promo',
        'changes' => [
            'Pe iPhone/iPad, după 31.03.2027: conturile noi primesc 1 lună de test, apoi abonament App Store; conturile existente trec pe abonament.',
            'Pe web rămân 6 luni de probă pentru conturile noi. Help actualizat.',
        ],
    ],
    [
        'version' => '1.0.243',
        'date' => '2026-08-14',
        'title' => 'Abonament iOS: 1 / 3 / 6 / 12 luni',
        'changes' => [
            'În aplicația iPhone/iPad poți alege abonament App Store pe 1 lună, 3 luni, 6 luni sau 1 an (după perioada gratuită până la 31.03.2027).',
            'Abonamentul iOS rămâne separat de abonamentul web (card/OP).',
        ],
    ],
    [
        'version' => '1.0.242',
        'date' => '2026-08-14',
        'title' => 'Link homepage în subsolul PDF',
        'changes' => [
            'Textul „Document generat cu DateConta Facturare” din subsolul PDF (facturi și alte documente) este acum un link către pagina principală a aplicației.',
        ],
    ],
    [
        'version' => '1.0.241',
        'date' => '2026-08-13',
        'title' => 'Storno: Achitată stabil pe ambele facturi',
        'changes' => [
            'După storno, factura originală și storno-ul apar ambele ca Achitată și rămân închise (chiar dacă se recalculează sau se șterg încasări).',
            'Corecție pentru storno-urile mai vechi: statusul de plată Achitată pe ambele documente.',
        ],
    ],
    [
        'version' => '1.0.240',
        'date' => '2026-08-13',
        'title' => 'Storno: achitat + XML e-Factura corect',
        'changes' => [
            'La emiterea storno, storno-ul și factura originală sunt marcate achitate automat.',
            'XML e-Factura pentru storno/NC: PayableAmount negativ corect (BR-CO-16), fără PrepaidAmount greșit; preț unitar pozitiv.',
        ],
    ],
    [
        'version' => '1.0.239',
        'date' => '2026-08-13',
        'title' => 'e-Factura auto-retry și pe storno / NC',
        'changes' => [
            'Reconcilierea automată (poll până Acceptată, corectări, retrimitere) acoperă explicit facturile storno și notele de creditare, ca pe facturi.',
            'Catch-up pentru documente automate rămase netrimise (none) și urmărire după emiterea storno/NC.',
        ],
    ],
    [
        'version' => '1.0.238',
        'date' => '2026-08-13',
        'title' => 'e-Factura pe liste storno și NC',
        'changes' => [
            'În Facturi storno și Note de creditare apar coloana e-Factura, selectarea și butoanele Trimite / XML, ca la facturi.',
            'Trimiterea în bulk și exportul XML acceptă și notele de creditare.',
        ],
    ],
    [
        'version' => '1.0.237',
        'date' => '2026-08-13',
        'title' => 'Traduceri native pe site-ul public',
        'changes' => [
            'Site-ul public (pagina principală, prețuri, lansare, autentificare/înregistrare, documente legale) are acum texte native pentru toate limbile din selector — nu doar fallback în engleză.',
            'Variantele regionale (es_*, ar_*, pt_*, fr_* etc.) moștenesc traducerile din limba de bază.',
        ],
    ],
    [
        'version' => '1.0.236',
        'date' => '2026-08-13',
        'title' => 'Traduceri native pe site-ul public',
        'changes' => [
            'Traduceri native pe site-ul public (toate limbile din selector).',
            'Limbile din selector (inclusiv variante regionale) au fișiere de traducere complete pentru pagina principală, prețuri, lansare, autentificare și documente legale.',
        ],
    ],
    [
        'version' => '1.0.235',
        'date' => '2026-08-13',
        'title' => 'Banner limbi pe pagina principală',
        'changes' => [
            'Pe pagina principală, sub selectorul de limbă, apare bannerul „Noi vorbim pe limba ta!” cu butonul Alege limba ta care deschide selectorul.',
            'Site-ul public se traduce pentru oaspeți în toate limbile din listă — bannerul atrage atenția asupra steagului din dreapta sus.',
        ],
    ],
    [
        'version' => '1.0.234',
        'date' => '2026-08-13',
        'title' => 'Site public tradus integral',
        'changes' => [
            'Selectorul de limbă (inclusiv pentru vizitatori nelogați) traduce integral pagina principală, prețuri, lansare, autentificare/înregistrare și documentele legale.',
            'Textele site-ului public sunt în chei de traducere; limba aleasă se păstrează în sesiune pe toate paginile.',
        ],
    ],
    [
        'version' => '1.0.233',
        'date' => '2026-08-13',
        'title' => 'Selector limbă pe site-ul public',
        'changes' => [
            'Pe pagina principală (colț dreapta sus) și pe paginile publice (prețuri, login/register, legal) apare selectorul de limbă cu steaguri.',
            'Limba aleasă se păstrează în sesiune și pentru vizitatori nelogați.',
        ],
    ],
    [
        'version' => '1.0.232',
        'date' => '2026-08-13',
        'title' => 'Mailuri reclamă/recomandare în limba UI',
        'changes' => [
            'Mailurile de reclamă (fără cod) și de recomandare cu cod promo urmează limba de lucru: destinatarul dacă e utilizator, altfel expeditorul.',
            'Textele emailurilor sunt traduse; limba aleasă în aplicație se aplică și la aceste invitații.',
        ],
    ],
    [
        'version' => '1.0.231',
        'date' => '2026-08-13',
        'title' => 'e-Factura: verificare până Acceptată + auto-retry',
        'changes' => [
            'După orice trimitere e-Factura, sistemul verifică starea până la Acceptată ANAF.',
            'La respingere/eroare: corectări automate (adresă/sector etc.), retrimitere (max. 5/zi) și alertă dacă rămâne blocată.',
            'Respinsă / eroare = netrimisă pentru automatizare; notele de creditare intră în același flux.',
        ],
    ],
    [
        'version' => '1.0.230',
        'date' => '2026-08-13',
        'title' => 'PDF First Consulting: ștampila peste „Ștampilă”',
        'changes' => [
            'Pe facturile First Consulting, imaginea de ștampilă/semnătură se afișează peste eticheta ȘTAMPILĂ (nu sub SEMNĂTURĂ).',
        ],
    ],
    [
        'version' => '1.0.229',
        'date' => '2026-08-13',
        'title' => 'Recurente: verificare email + raport după retry',
        'changes' => [
            'După fereastra de emitere (~10:25): verifică emailurile către beneficiari, alertează cauzele la razvan@dateconta.ro și reîncearcă de până la 3 ori.',
            'Emailurile din recurente merg cu CC facturare@fly-david.ro; raportul zilnic PDF include starea email + e-Factura și se trimite doar după aceste încercări.',
        ],
    ],
    [
        'version' => '1.0.228',
        'date' => '2026-08-13',
        'title' => 'Raport zilnic recurente: două adrese',
        'changes' => [
            'Raportul PDF de emitere recurente se trimite către razvan@fly-david.ro și razvan@dateconta.ro.',
            'Programat zilnic după intervalul de emitere (~10:30); se poate reconfigura prin RECURRING_DAILY_REPORT_EMAILS.',
        ],
    ],
    [
        'version' => '1.0.227',
        'date' => '2026-08-13',
        'title' => 'e-Factura: adresă Călărași / Str. București',
        'changes' => [
            'Preluarea ANAF nu mai confunda străzile denumite «București» cu Municipiul București (ex. sedii din Călărași).',
            'Pentru București fără Sector 1–6, trimiterea e-Factura semnalează clar eroarea înainte de respingerea ANAF (BR-RO-100).',
        ],
    ],
    [
        'version' => '1.0.226',
        'date' => '2026-08-13',
        'title' => 'Limbi UI: variante regionale și limbi noi',
        'changes' => [
            'Adăugate zeci de limbi/variante: elvețiană, latino-americane, braziliană, coreeană (Nord/Sud), indoneziană, hindi, urdu, persană, swahili, croată, slovenă, georgiană, armeană și altele.',
            'Selectorul rămâne: Română → English (US) → English (UK) → restul alfabetic, cu steag; UI și emailurile urmează limba aleasă.',
        ],
    ],
    [
        'version' => '1.0.225',
        'date' => '2026-08-13',
        'title' => 'Limbi UI extinse + ordine RO / EN / alfabetic',
        'changes' => [
            'Selectorul de limbă: Română prima, English (US) și English (UK) pe locurile 2–3, restul limbilor alfabetic.',
            'Limbi noi: portugheză, lituaniană, belarusă, norvegiană, finlandeză, suedeză, irlandeză, scoțiană, cehă, slovacă, austriacă, japoneză, arabă (inclusiv Egipt/Siria/Iordania/Tunisia/Maroc), nepaleză, vietnameză, thailandeză, olandeză, daneză, ebraică.',
            'Interfața și emailurile din aplicație urmează limba de lucru aleasă.',
        ],
    ],
    [
        'version' => '1.0.224',
        'date' => '2026-08-13',
        'title' => 'Emailuri în limba UI + recurente 04–10 + raport zilnic',
        'changes' => [
            'Emailurile din aplicație (documente, invitații, reminder-e) se trimit în limba de lucru a utilizatorului (limba UI).',
            'Emiterea automată a facturilor/proformelor recurente rulează doar între 04:00 și 10:00 (ora României).',
            'După emitere, se trimite un raport PDF detaliat (toate firmele) cu tipuri, societăți, cantități și stadiu e-Factura.',
        ],
    ],
    [
        'version' => '1.0.223',
        'date' => '2026-08-13',
        'title' => 'Limbi UI: steaguri + English US/UK și limbi noi',
        'changes' => [
            'Selectorul de limbă afișează steagul fiecărei limbi; English a devenit English (US), cu English (UK) imediat sub ea.',
            'Limbi noi în interfață: Türkçe, 中文, Български, Srpski, Українська, Polski, Русский, Ελληνικά (plus cele existente).',
            'La alegerea limbii, meniurile și ecranele traduse din aplicație se afișează în limba selectată (limba PDF rămâne separată).',
        ],
    ],
    [
        'version' => '1.0.222',
        'date' => '2026-08-12',
        'title' => 'Liste: paginare sus/jos + revenire pe aceeași pagină',
        'changes' => [
            'Când o listă are mai multe pagini, selectorul de pagini apare sus și jos (facturi, clienți, produse, încasări, recurente).',
            'La editarea unui abonament de pe pagina 2 (sau alta), după salvare revii pe aceeași pagină din listă.',
        ],
    ],
    [
        'version' => '1.0.221',
        'date' => '2026-08-12',
        'title' => 'Fix: salvare abonament inactiv',
        'changes' => [
            'Debifezi Abonament activ și salvezi: revii în listă cu status Inactiv, fără dată de emitere/scadență.',
            'Liniile goale din formular nu mai blochează salvarea; erorile apar clar dacă totuși ceva eșuează.',
        ],
    ],
    [
        'version' => '1.0.220',
        'date' => '2026-08-12',
        'title' => 'Abonamente inactive: salvare + status pe listă',
        'changes' => [
            'Debifezi Abonament activ: se salvează corect, fără dată de emitere și fără scadență, și revii în listă cu status Inactiv.',
            'În listă: Activ (albastru, bold) / Inactiv (roșu, bold).',
        ],
    ],
    [
        'version' => '1.0.219',
        'date' => '2026-08-12',
        'title' => 'Abonamente: pauză golește următoarea emitere',
        'changes' => [
            'Debifezi Abonament activ (sau Pauzează): nu se mai emit facturi automat, iar data următoarei emiteri se golește.',
            'Corectat salvarea bifelor Activ / Emite automat (debifarea se păstrează la salvare).',
        ],
    ],
    [
        'version' => '1.0.218',
        'date' => '2026-08-12',
        'title' => 'Liste: acțiuni ca butoane',
        'changes' => [
            'În liste (facturi, recurente, clienți, produse, încasări, societăți, utilizatori): acțiunile apar ca butoane, nu ca link-uri subliniate.',
        ],
    ],
    [
        'version' => '1.0.217',
        'date' => '2026-08-12',
        'title' => 'Abonamente: scadență față de următoarea factură',
        'changes' => [
            'Pe formularul de abonament, preview-ul scadenței (termen de plată) se calculează față de data emiterii următoarei facturi, nu față de data primei facturi.',
        ],
    ],
    [
        'version' => '1.0.216',
        'date' => '2026-08-12',
        'title' => 'Abonamente: data următoarei facturi pe formular',
        'changes' => [
            'Pe formularul de creare/editare abonament: câmp vizibil „Data emiterii următoarei facturi” (în plus față de data primei facturi).',
        ],
    ],
    [
        'version' => '1.0.215',
        'date' => '2026-08-12',
        'title' => 'Abonamente: Înapoi la listă după salvare',
        'changes' => [
            'Pe pagina de recapitulare a abonamentului (după creare/editare): buton „Înapoi la lista de abonamente”.',
        ],
    ],
    [
        'version' => '1.0.214',
        'date' => '2026-08-12',
        'title' => 'Fix invitație SPV: link invalid imediat',
        'changes' => [
            'Corectat bug MySQL: după trimiterea emailului, invitația SPV părea expirată („Invitație invalidă”). Linkurile noi rămân valabile 7 zile.',
            'Invitațiile deschise afectate au fost reparate; dacă tot vezi invalid, trimite o invitație nouă din Setări → e-Factura.',
        ],
    ],
    [
        'version' => '1.0.213',
        'date' => '2026-08-12',
        'title' => 'Ajutor: autorizare SPV și pagina hangup ANAF',
        'changes' => [
            'Clarificat în Ajutor / FAQ ce înseamnă pagina BIG-IP („session finished”) după parola semnăturii și cum reiei autorizarea SPV.',
            'Pe fila e-Factura: scurt ghid înainte de Autorizează SPV (certificat pe CUI, fără tab ANAF vechi, invitație contabil).',
        ],
    ],
    [
        'version' => '1.0.212',
        'date' => '2026-08-12',
        'title' => 'Mesaje autentificare în română',
        'changes' => [
            'Mesajele de eroare la login (parolă greșită, prea multe încercări) apar în română, nu ca chei tehnice (ex. auth.failed).',
            'Poți fi autentificat pe mai multe dispozitive în același timp; o sesiune pe un calculator nu blochează loginul pe altul.',
        ],
    ],
    [
        'version' => '1.0.211',
        'date' => '2026-08-11',
        'title' => 'Recurente: tip document și serie în listă',
        'changes' => [
            'În lista de abonamente recurente: tipul de document (factură / proformă) și seria, cu preview la următorul număr (se rezervă la emitere).',
        ],
    ],
    [
        'version' => '1.0.210',
        'date' => '2026-08-11',
        'title' => 'Landing: COMUNITATE fix pe bandă',
        'changes' => [
            'Pe banda de jos: eticheta COMUNITATE rămâne fixă stânga (fond alb/portocaliu); mesajul rulează în continuare.',
            'Preview: factura.dateconta.ro/?preview_comunitate=1',
        ],
    ],
    [
        'version' => '1.0.209',
        'date' => '2026-08-11',
        'title' => 'Landing: bandă comunitate pe o linie',
        'changes' => [
            'Din septembrie, bannerul de jos e o bandă îngustă pe un rând, cu text care rulează dreapta→stânga; click → login (fără buton separat).',
            'Preview: factura.dateconta.ro/?preview_comunitate=1',
        ],
    ],
    [
        'version' => '1.0.208',
        'date' => '2026-08-11',
        'title' => 'Landing: comunitate live + dock din septembrie',
        'changes' => [
            'Bannerul „Mulțumim…” actualizează utilizatori, societăți și vizitatori activi la fiecare minut.',
            'Din 1 septembrie 2026 (când dispare bannerul de lansare de jos), mulțumirile trec jos, pe tot ecranul.',
            'Preview acum: factura.dateconta.ro/?preview_comunitate=1',
        ],
    ],
    [
        'version' => '1.0.207',
        'date' => '2026-08-11',
        'title' => 'Landing: banner comunitate → login',
        'changes' => [
            'Click pe bannerul „Mulțumim…” de pe pagina de start duce la autentificare.',
        ],
    ],
    [
        'version' => '1.0.206',
        'date' => '2026-08-11',
        'title' => 'Landing: bun venit vizitatorilor activi',
        'changes' => [
            'Bannerul „Mulțumim…” include și numărul de vizitatori activi (online) în momentul afișării.',
        ],
    ],
    [
        'version' => '1.0.205',
        'date' => '2026-08-11',
        'title' => 'Login și parolă uitată: ecrane branduite',
        'changes' => [
            'Pagini de autentificare și „Parolă uitată” cu fundal/panou DateConta, texte în română și linkuri utile (acasă, prețuri, cont nou).',
        ],
    ],
    [
        'version' => '1.0.204',
        'date' => '2026-08-11',
        'title' => 'Landing: mulțumiri sub facturare',
        'changes' => [
            'Mesajul sticky „Mulțumim…” e coborât ca să nu acopere cuvântul „facturare” din promisiune.',
        ],
    ],
    [
        'version' => '1.0.203',
        'date' => '2026-08-11',
        'title' => 'Landing: poziție mulțumiri',
        'changes' => [
            'Mesajul sticky „Mulțumim…” e coborât sub cuvântul „facturare” din bannerul Promisiunea noastră.',
        ],
    ],
    [
        'version' => '1.0.202',
        'date' => '2026-08-11',
        'title' => 'Landing: mulțumiri sticky rotite',
        'changes' => [
            'Mesajul „Mulțumim…” rămâne dreapta sus, rotit ~10° și fix pe ecran la derulare.',
        ],
    ],
    [
        'version' => '1.0.201',
        'date' => '2026-08-11',
        'title' => 'Landing: mulțumiri sub butoane',
        'changes' => [
            'Mesajul sticky „Mulțumim…” stă sub butoanele Prețuri / Intră în aplicație, rotit ~10° spre dreapta, fix la derulare.',
        ],
    ],
    [
        'version' => '1.0.200',
        'date' => '2026-08-11',
        'title' => 'Landing: mulțumiri comunității',
        'changes' => [
            'Pe pagina de start, dreapta sus: mesaj sticky cu numărul de utilizatori activi și de societăți.',
        ],
    ],
    [
        'version' => '1.0.199',
        'date' => '2026-08-11',
        'title' => 'Notă factură din proformă: toate încasările',
        'changes' => [
            'Pe factura din proformă: metoda reală de încasare (chitanță / card / OP); la plăți fracționate sunt listate toate încasările.',
        ],
    ],
    [
        'version' => '1.0.198',
        'date' => '2026-08-11',
        'title' => 'Notă pe factura din proformă',
        'changes' => [
            'Factura emisă din proformă are jos nota cu nr./data proformei și metoda + data încasării (chitanță / card / OP).',
        ],
    ],
    [
        'version' => '1.0.197',
        'date' => '2026-08-11',
        'title' => 'De încasat: și proformele',
        'changes' => [
            'Lista „De încasat” (încasare + dashboard) include și proformele neîncasate, pe lângă facturi.',
            'La încasarea integrală a unei proforme din formularul de încasare se emite automat factura fiscală.',
        ],
    ],
    [
        'version' => '1.0.196',
        'date' => '2026-08-11',
        'title' => 'Abonament factură/proformă + factură la încasare',
        'changes' => [
            'La abonamente alegi tipul emis: factură fiscală sau proformă (cu seria aferentă).',
            'La încasarea integrală a unei proforme se emite automat factura fiscală; e-Factura urmează setările firmei.',
            'Help actualizat (Recurente, Alte documente).',
        ],
    ],
    [
        'version' => '1.0.195',
        'date' => '2026-08-11',
        'title' => 'Cotă TVA pe documente: listă cu 4 cote',
        'changes' => [
            'La emitere documente (și abonamente), cota TVA pe linie e listă: 21%, 11%, 5%, 0%.',
            'Help actualizat la Emitere factură.',
        ],
    ],
    [
        'version' => '1.0.194',
        'date' => '2026-08-10',
        'title' => 'Landing: banner live până pe 31.08',
        'changes' => [
            'După cele 8 ore de fireworks/confetti, bannerul «S-A LANSAT!» rămâne afișat (cu 0·0·0·0) până pe 31 august 2026 inclusiv.',
            'Din 1 septembrie bannerul de jos dispare automat de pe pagina de start.',
        ],
    ],
    [
        'version' => '1.0.193',
        'date' => '2026-08-10',
        'title' => 'Landing: sărbătoare la lansare',
        'changes' => [
            'La 15.08.2026 10:00, bannerul de jos rămâne cu 0·0·0·0 și mesajul «S-A LANSAT! Aplicația este live!».',
            'Timp de 8 ore după lansare: fireworks colorate și confetti pe întreaga pagină de start.',
            'Preview: factura.dateconta.ro/?preview_lansare=1',
        ],
    ],
    [
        'version' => '1.0.192',
        'date' => '2026-08-10',
        'title' => 'Dashboard: facturat/încasat azi + defalcare',
        'changes' => [
            'Pe dashboard: Facturat azi și Încasat azi, alături de indicatorii pe lună.',
            'Încasat azi și Încasat luna aceasta apar defalcate pe Cash, Card și OP (chitanța intră la Cash).',
            'Aceleași indicatori sunt disponibili și în API / aplicația iOS, pentru societatea activă (orice firmă).',
        ],
    ],
    [
        'version' => '1.0.191',
        'date' => '2026-08-10',
        'title' => 'NETOPIA: sync la return',
        'changes' => [
            'La întoarcerea din plata NETOPIA (abonament și facturi), statusul se sincronizează ca la Mollie: payload pe return + așteptare scurtă pentru IPN.',
            'Pagina de succes reîncarcă automat câteva secunde dacă plata e încă în așteptare.',
            'Return NETOPIA acceptă GET/POST (fără CSRF) ca să nu se piardă confirmarea.',
        ],
    ],
    [
        'version' => '1.0.190',
        'date' => '2026-08-10',
        'title' => 'UM live + mapare e-Factura',
        'changes' => [
            'Unități de măsură: listă live pe firmă; poți scrie o UM nouă (se creează automat în catalog).',
            'La generarea XML e-Factura, UM se mapează pe codul UNECE (H87, KGM…) și catalogul se actualizează cu corespondența.',
        ],
    ],
    [
        'version' => '1.0.189',
        'date' => '2026-08-10',
        'title' => 'Serii: primul număr DateConta + Adaugă linie',
        'changes' => [
            'La serii: Prefix, Primul număr folosit în DateConta și Următorul număr de emis; golurile se caută doar de la primul număr în sus (nu se mai alocă SM-0001 când seria începe de la 306).',
            'Fix buton „Adaugă linie” blocat la emitere factură (eroare JS unități de măsură).',
        ],
    ],
    [
        'version' => '1.0.188',
        'date' => '2026-08-09',
        'title' => 'Abonament App Store (iOS)',
        'changes' => [
            'Aplicația iPhone/iPad rămâne gratuită până la 31.03.2027; din 01.04.2027 accesul în app necesită abonament App Store 0,99 USD/lună (StoreKit).',
            'Abonamentul iOS este separat de abonamentul web (card/OP): unul nu îl înlocuiește pe celălalt.',
            'În app: Setări → Abonament aplicație (status, abonare, restaurare, gestionare în App Store).',
        ],
    ],
    [
        'version' => '1.0.187',
        'date' => '2026-08-09',
        'title' => 'Goluri libere în serie',
        'changes' => [
            'La emitere se preferă automat cel mai mic număr liber din serie, inclusiv golurile rămase după renunțări sau rezervări expirate.',
            'În formular poți vedea și alege un număr liber din listă (gol sau următorul).',
        ],
    ],
    [
        'version' => '1.0.186',
        'date' => '2026-08-09',
        'title' => 'Rezervare numere serie',
        'changes' => [
            'La deschiderea formularului de emitere, numărul din serie se rezervă pe server (web și app), ca să nu apară duplicate între sesiuni.',
            'Rezervarea expiră după ~60 de minute fără activitate sau la renunțarea la o ciornă goală; la emitere se folosește numărul rezervat.',
        ],
    ],
    [
        'version' => '1.0.185',
        'date' => '2026-08-08',
        'title' => 'Renunță la editare document',
        'changes' => [
            'La editarea facturilor, proformelor și a celorlalte documente: buton Renunță — revii la fișă fără a salva.',
        ],
    ],
    [
        'version' => '1.0.184',
        'date' => '2026-08-08',
        'title' => 'Încasare pe sold inițial',
        'changes' => [
            'Emite → Încasare: apare soldul inițial; se încasează întâi soldul, apoi facturile (poți încasa doar soldul, fără facturi).',
            'Soldul real al clientului scade la încasările pe sold inițial (listă, fișă, rapoarte, dashboard).',
        ],
    ],
    [
        'version' => '1.0.183',
        'date' => '2026-08-08',
        'title' => 'Filtre balanță parteneri',
        'changes' => [
            'Balanță parteneri: bifă „Ascunde clienții cu sold 0” și „Ascunde liniile integral pe 0” (preview + PDF).',
        ],
    ],
    [
        'version' => '1.0.182',
        'date' => '2026-08-08',
        'title' => 'Balanță: toți clienții',
        'changes' => [
            'Balanță parteneri listează toți clienții, inclusiv fără sold inițial sau mișcări în perioadă.',
        ],
    ],
    [
        'version' => '1.0.181',
        'date' => '2026-08-08',
        'title' => 'Fix preview + balanță clienți',
        'changes' => [
            'Preview rapoarte: nu se mai închide singur și nu mai descarcă PDF automat; PDF doar la Export PDF.',
            'Balanță parteneri: fiecare client pe rând separat; soldul inițial din perioadă intră corect în rulaje.',
        ],
    ],
    [
        'version' => '1.0.180',
        'date' => '2026-08-08',
        'title' => 'Rapoarte parteneri fără popup',
        'changes' => [
            'Fișă de partener și Balanță parteneri se deschid într-un panou peste aplicație (fără fereastră popup / mesaj blocked).',
            'Export PDF și Print rămân în panou.',
        ],
    ],
    [
        'version' => '1.0.179',
        'date' => '2026-08-08',
        'title' => 'Previzualizare rapoarte parteneri',
        'changes' => [
            'Fișă de partener și Balanță parteneri: se afișează mai întâi pe ecran (fereastră nouă).',
            'În fereastră: butoane Export PDF, Print și Închide.',
        ],
    ],
    [
        'version' => '1.0.178',
        'date' => '2026-08-08',
        'title' => 'Dată sold inițial = creare client',
        'changes' => [
            'Sold inițial necompletat: sumă 0, dată implicită = data creării clientului (formular, solduri în masă, rapoarte PDF).',
        ],
    ],
    [
        'version' => '1.0.177',
        'date' => '2026-08-08',
        'title' => 'Sold inițial implicit 0',
        'changes' => [
            'Soldul inițial necompletat este 0 (formular client, solduri în masă, calcule și rapoarte PDF).',
            '„Toată perioada”: fără sold inițial setat, intervalul pornește de la prima factură.',
        ],
    ],
    [
        'version' => '1.0.176',
        'date' => '2026-08-08',
        'title' => 'Perioadă PDF parteneri',
        'changes' => [
            'Fișă de partener și Balanță parteneri: implicit 1 ale lunii curente → azi; date editabile (inclusiv calendar).',
            'Checkbox „Toată perioada (de la sold inițial până azi)” pe ambele rapoarte.',
        ],
    ],
    [
        'version' => '1.0.175',
        'date' => '2026-08-08',
        'title' => 'Balanță parteneri PDF',
        'changes' => [
            'Rapoarte → Clienți: generare Balanță parteneri / BALANTA TERTI (PDF) pe interval, cont 4111-Clienți, după model contabil.',
        ],
    ],
    [
        'version' => '1.0.174',
        'date' => '2026-08-08',
        'title' => 'Fișă de partener PDF',
        'changes' => [
            'Rapoarte → Clienți: generare Fișă de partener (PDF) pe perioadă, după model contabil (debit / credit / sold, cont 4111-Clienți).',
        ],
    ],
    [
        'version' => '1.0.173',
        'date' => '2026-08-08',
        'title' => 'Raport solduri clienți + total pe Dashboard',
        'changes' => [
            'Rapoarte → Clienți (solduri): sold la o dată (implicit azi), pentru toți clienții sau unul selectat.',
            'Dashboard: card „De încasat de la clienți” cu totalul soldurilor la data de azi.',
        ],
    ],
    [
        'version' => '1.0.172',
        'date' => '2026-08-08',
        'title' => 'Solduri inițiale clienți',
        'changes' => [
            'Catalog → Clienți: buton Solduri inițiale pentru actualizare în masă (sumă + dată pe fiecare client).',
            'Sold real = sold inițial + rest facturi deschise; coloană Sold pe listă și Fișă client (web + PDF).',
            'Raportul Neîncasat include și soldurile inițiale ale clienților.',
        ],
    ],
    [
        'version' => '1.0.171',
        'date' => '2026-08-08',
        'title' => 'Renunță la editarea clientului',
        'changes' => [
            'Pe fișa Editează client, lângă Actualizează există butonul Renunță — revii la listă fără a salva.',
        ],
    ],
    [
        'version' => '1.0.170',
        'date' => '2026-08-08',
        'title' => 'Statistici după Actualizare ANAF',
        'changes' => [
            'După actualizarea în masă din ANAF apare o fereastră cu: actualizați cu succes, fișe modificate și ignorați (cu motive); se închide automat în 30 de secunde.',
        ],
    ],
    [
        'version' => '1.0.169',
        'date' => '2026-08-08',
        'title' => 'Actualizare ANAF: omitere fără eroare',
        'changes' => [
            'La Actualizare ANAF din listă, persoanele fizice (CNP) și CUI-urile negăsite sunt omise silențios — fără listă de erori.',
        ],
    ],
    [
        'version' => '1.0.168',
        'date' => '2026-08-08',
        'title' => 'Actualizare ANAF pentru toți clienții',
        'changes' => [
            'Pe lista Clienți: buton Actualizare ANAF pentru societatea curentă (toți cu CUI).',
            'Actualizează denumirea, CUI, Reg. Com. și adresa; email, IBAN și notele rămân neschimbate.',
            'Persoanele fizice fără CUI sunt omise; poți corecta individual pe fișă după sync.',
        ],
    ],
    [
        'version' => '1.0.167',
        'date' => '2026-08-08',
        'title' => 'Preluare ANAF și la editarea clientului',
        'changes' => [
            'La Editează client apare aceeași zonă „Caută după CUI (ANAF)” / Preluare date ca la client nou; CUI-ul existent e precompletat.',
        ],
    ],
    [
        'version' => '1.0.166',
        'date' => '2026-08-08',
        'title' => 'Ștergere serii implicite (FCT, PRF…)',
        'changes' => [
            'Poți șterge seriile create automat (FCT, PRF, AVZ, CHT, NC) după ce ai adăugat propria serie pe același tip.',
            'Rămâne obligatoriu minim o serie pe tip de document și an; ultima serie nu se șterge.',
            'Seriile implicite nu mai sunt recreate automat dacă ai deja o serie pe tipul respectiv.',
        ],
    ],
    [
        'version' => '1.0.165',
        'date' => '2026-08-08',
        'title' => 'NETOPIA: curs BNR + 2% pentru RON',
        'changes' => [
            'Plățile cu card NETOPIA (abonament) se convertesc și se facturează în RON la cursul BNR + 2%.',
        ],
    ],
    [
        'version' => '1.0.164',
        'date' => '2026-08-07',
        'title' => 'Detectare automată user existent la adăugare',
        'changes' => [
            'La Adaugă utilizator, după email: dacă adresa există deja, formularul afișează numele din cont și dezactivează parola (mod invitație).',
        ],
    ],
    [
        'version' => '1.0.163',
        'date' => '2026-08-07',
        'title' => 'Invitare admin și clarificări tipuri de utilizatori',
        'changes' => [
            'Poți invita un administrator pe societățile tale: rămâne cu drepturi complete de admin; odată alocat pe o firmă, nu mai poate fi scos de pe ea.',
            'Manualul Ajutor explică tipurile: proprietar, subuser creat, utilizator invitat și admin invitat.',
        ],
    ],
    [
        'version' => '1.0.162',
        'date' => '2026-08-07',
        'title' => 'Email la creare/invitare subuser și reguli de ștergere',
        'changes' => [
            'La salvarea drepturilor, subuserul nou primit email cu datele de login, firmele și drepturile; utilizatorul existent primit invitație pe societățile tale (fără parolă nouă).',
            'Poți invita un cont deja înregistrat ca colaborator pe firmele tale; revocarea îi scoate doar accesul, fără a-i șterge contul.',
            'Nimeni nu își poate șterge singur contul din Contul meu. Poți închide doar subuserii pe care i-ai creat.',
            'Din 01.04.2027, locurile (1 EUR/lună) acoperă atât subuserii creați, cât și invitații — cumpărate de owner.',
        ],
    ],
    [
        'version' => '1.0.161',
        'date' => '2026-08-07',
        'title' => 'Manual Ajutor: Utilizatori cu capturi',
        'changes' => [
            'Capitolul Utilizatori (subuseri) din Ajutor are acum capturi din aplicație: listă, creare, drepturi pe categorii, abonament locuri și Contul meu.',
        ],
    ],
    [
        'version' => '1.0.160',
        'date' => '2026-08-07',
        'title' => 'Drepturi: fără bifă = fără acces',
        'changes' => [
            'La drepturile pe categorie, dacă nu e bifat nici Vizualizare, nici Creare/editare, subuserul nu are acces în acea categorie.',
        ],
    ],
    [
        'version' => '1.0.159',
        'date' => '2026-08-07',
        'title' => 'Profil subuser și închiderea contului',
        'changes' => [
            'Subuserii au același profil (Contul meu): se autentifică cu email/parolă, își pot schimba parola; și creatorul le poate reseta parola din Setări → Utilizatori.',
            'Subuserii nu își pot șterge singuri contul și nu văd meniul Utilizatori / Abonament utilizatori. La închiderea contului principal, datele firmelor rămân în baza de date.',
        ],
    ],
    [
        'version' => '1.0.158',
        'date' => '2026-08-07',
        'title' => 'Drepturi subuser: vizualizare și creare/editare',
        'changes' => [
            'Pe fiecare categorie de drepturi (documente, clienți, produse, încasări, recurente, rapoarte, e-Factura, setări) există acum două bifuri: Vizualizare și Creare/editare.',
            'Creare/editare implică automat vizualizarea. Drepturile vechi rămân compatibile.',
        ],
    ],
    [
        'version' => '1.0.157',
        'date' => '2026-08-07',
        'title' => 'Abonament locuri subuser (1 EUR/lună)',
        'changes' => [
            'Setări → Abonament utilizatori: proprietarul cumpără locuri pentru subuseri (1 EUR / loc / lună + TVA), cu card sau OP.',
            'Locurile intră în vigoare de la 01.04.2027; până atunci sunt gratuite. Subuserii văd meniul, dar nu pot comanda.',
        ],
    ],
    [
        'version' => '1.0.156',
        'date' => '2026-08-07',
        'title' => 'Setări: Utilizatori și drepturi pe societate',
        'changes' => [
            'În Setări → Utilizatori poți crea subuseri (nume, email, parolă) pentru firmele pe care le administrezi.',
            'Pe fiecare societate bifezi accesul și drepturile: emitere, liste, clienți, produse, încasări, recurente, rapoarte, e-Factura, setări firmă.',
        ],
    ],
    [
        'version' => '1.0.155',
        'date' => '2026-08-07',
        'title' => 'NETOPIA abonament: plată și factură în RON',
        'changes' => [
            'Comenzile cu card NETOPIA se trimit și se facturează în RON (curs BNR + 1%), pe aceleași sume.',
            'Corectat emiterea automată a facturii fiscale după plată (era după return și nu rula).',
        ],
    ],
    [
        'version' => '1.0.154',
        'date' => '2026-08-07',
        'title' => 'NETOPIA IPN: fix IV / openssl_open',
        'changes' => [
            'Decriptare IPN: IV nu mai e trimis ca null (eroare PHP 8 + AES-256-CBC); se respectă cipher-ul din POST.',
        ],
    ],
    [
        'version' => '1.0.153',
        'date' => '2026-08-07',
        'title' => 'Facturi: retrimitere email în masă',
        'changes' => [
            'În lista de facturi: buton „Retrimite pe email” pentru facturile selectate (ca la e-Factura).',
        ],
    ],
    [
        'version' => '1.0.152',
        'date' => '2026-08-07',
        'title' => 'Admin Utilizatori: activitate din societăți + login',
        'changes' => [
            'Statusul nu mai e „fără activitate” dacă utilizatorul are societăți/documente, chiar fără sesiune web înregistrată.',
            'La login se marchează imediat ultima activitate.',
        ],
    ],
    [
        'version' => '1.0.151',
        'date' => '2026-08-07',
        'title' => 'NETOPIA: sandbox FLY DAVID are prioritate',
        'changes' => [
            'Checkout abonament folosește mai întâi NETOPIA de pe FLY DAVID (inclusiv mod sandbox), nu setările live din platformă/.env.',
            'În Admin → Integrări se vede sursa efectivă, sandbox/live și URL-ul de plată.',
        ],
    ],
    [
        'version' => '1.0.150',
        'date' => '2026-08-07',
        'title' => 'Admin: clienți doar FLY DAVID',
        'changes' => [
            'Lista Clienți din Statistici arată doar clienții FLY DAVID, nu clienții societăților de pe platformă.',
            'Coloane separate: facturi emise de FLY DAVID către client + facturi emise de societatea lor pe platformă.',
        ],
    ],
    [
        'version' => '1.0.149',
        'date' => '2026-08-07',
        'title' => 'Admin Utilizatori: status activitate corect',
        'changes' => [
            'Statusul din listă urmărește sesiunile autentificate (online / offline / fără activitate), nu doar vizitele anonime.',
        ],
    ],
    [
        'version' => '1.0.148',
        'date' => '2026-08-07',
        'title' => 'Admin: ștergere utilizatori',
        'changes' => [
            'Pe Statistici → Utilizatori: buton Șterge (nu pentru admin / propriul cont); confirmare dacă are societăți.',
        ],
    ],
    [
        'version' => '1.0.147',
        'date' => '2026-08-07',
        'title' => 'PDF factură: echivalent RON',
        'changes' => [
            'Pe facturile în valută (PDF / email): curs + echivalent total/subtotal/TVA în RON.',
        ],
    ],
    [
        'version' => '1.0.146',
        'date' => '2026-08-07',
        'title' => 'Admin Statistici: cod promo pe clienți',
        'changes' => [
            'În lista de clienți: coloană cu codul promoțional al societății emitente.',
        ],
    ],
    [
        'version' => '1.0.145',
        'date' => '2026-08-07',
        'title' => 'Admin Statistici: listă utilizatori',
        'changes' => [
            'Pe Statistici: listă utilizatori cu plan, acces, societăți, status online și ultima activitate.',
        ],
    ],
    [
        'version' => '1.0.144',
        'date' => '2026-08-07',
        'title' => 'Admin Statistici: listă clienți',
        'changes' => [
            'Pe pagina Statistici: listă cu clienții din platformă (emitent, contact, facturi emise).',
        ],
    ],
    [
        'version' => '1.0.143',
        'date' => '2026-08-07',
        'title' => 'e-Factura: PrepaidAmount + retrimitere',
        'changes' => [
            'XML: la facturi achitate se trimite PrepaidAmount (BT-113) — corectează BR-CO-16.',
            'Retrimitere e-Factura permisă oricând statusul nu e OK, pentru toate firmele.',
        ],
    ],
    [
        'version' => '1.0.142',
        'date' => '2026-08-07',
        'title' => 'e-Factura EUR: BT-6 / TVA în RON',
        'changes' => [
            'XML e-Factura: la facturi în valută se trimit TaxCurrencyCode=RON și TaxTotal TVA în RON (BR-RO-030 / BR-53).',
            'Facturile respinse ANAF pot fi retrimise (buton Retrimite e-Factura).',
        ],
    ],
    [
        'version' => '1.0.141',
        'date' => '2026-08-07',
        'title' => 'Factură automată la plata abonamentului',
        'changes' => [
            'La plată card OK sau confirmare OP: se emite factura fiscală pe FLY DAVID și se trimite pe email clientului.',
            'e-Factura urmează setările FLY DAVID (la emitere / delay / manual).',
            'Admin → Comenzi: buton „Emite facturi lipsă” pentru plățile deja confirmate.',
        ],
    ],
    [
        'version' => '1.0.140',
        'date' => '2026-08-07',
        'title' => 'NETOPIA abonament via FLY DAVID',
        'changes' => [
            'Checkout abonament: dacă NETOPIA e activă pe firma FLY DAVID, e selectabilă și la plata abonamentului.',
            'Nu mai e nevoie de o a doua configurare separat în Admin când cheile sunt pe FLY DAVID.',
        ],
    ],
    [
        'version' => '1.0.139',
        'date' => '2026-08-07',
        'title' => 'NETOPIA pentru plăți facturi clienți',
        'changes' => [
            'Setări → Integrări: status clar dacă NETOPIA e gata pentru clienți + pașii următori.',
            'IPN facturi: decriptare cu toate cheile private ale firmelor (nu doar checkout pending).',
            'Sandbox firmă: implicit debifat (plăți reale); mesaj după salvare dacă e activă.',
        ],
    ],
    [
        'version' => '1.0.138',
        'date' => '2026-08-07',
        'title' => 'NETOPIA selectabilă la checkout',
        'changes' => [
            'Un enabled=0 incomplet din Admin nu mai anulează configurația NETOPIA din .env.',
            'La comandă abonament: selecție procesator mai stabilă; NETOPIA e implicită când e gata.',
            'Admin Integrări: listă clară cu ce lipsește pentru NETOPIA.',
        ],
    ],
    [
        'version' => '1.0.137',
        'date' => '2026-08-07',
        'title' => 'Pagina Prețuri pe site',
        'changes' => [
            'Buton Prețuri pe pagina principală.',
            'Pagină /preturi cu abonamentele post-promo din config (1/3/6/12 luni + bonusuri).',
        ],
    ],
    [
        'version' => '1.0.136',
        'date' => '2026-08-07',
        'title' => 'Prelansare pe landing + limbi',
        'changes' => [
            'Eliminată limba țigănească din limbi UI și document.',
            'Banner transparent jos pe prima pagină: prelansare, contor până la 15.08.2026 10:00, testare intensivă până la 01.10.2026.',
        ],
    ],
    [
        'version' => '1.0.135',
        'date' => '2026-08-07',
        'title' => 'Card pe proforme/recurente + mail reclamă admin',
        'changes' => [
            'Linkuri de plată în PDF și email doar pentru procesatoarele active ale firmei.',
            'Proformă plătită cu cardul → emitere automată factură fiscală cu data încasării; card disponibil și pe facturi recurente.',
            'Admin: Trimite mail reclamă (max. 20 adrese) de la Razvan Ivan — FLY DAVID SRL, fără cod promo.',
        ],
    ],
    [
        'version' => '1.0.134',
        'date' => '2026-08-07',
        'title' => 'Separare clară: abonament vs facturi',
        'changes' => [
            'Cheile FLY DAVID (Admin → Abonament DateConta) rămân doar pentru abonamente platformă.',
            'Fiecare firmă configurează NETOPIA / Eu Plătesc / Mollie / Stripe în Setări → Integrări, cu URL-uri /plata/… pentru încasarea facturilor.',
        ],
    ],
    [
        'version' => '1.0.133',
        'date' => '2026-08-07',
        'title' => 'Stripe în Integrări',
        'changes' => [
            'Stripe apare în Integrări abonament (admin) și în Setări → Integrări (firmă), ca NETOPIA / Eu Plătesc / Mollie.',
            'Plată facturi clienți cu Stripe Checkout (chei per firmă) + confirmare la return/webhook.',
        ],
    ],
    [
        'version' => '1.0.132',
        'date' => '2026-08-07',
        'title' => 'Stripe live pe abonament',
        'changes' => [
            'Chei Stripe live (pk/sk) + webhook live pe factura.dateconta.ro; plățile de abonament trec din test în producție.',
        ],
    ],
    [
        'version' => '1.0.131',
        'date' => '2026-08-07',
        'title' => 'Stripe pe abonament platformă',
        'changes' => [
            'Plată abonament cu Stripe Checkout (test): procesator nou pe pagina Comandă, alături de NETOPIA / Mollie / Eu Plătesc.',
            'Webhook /billing/stripe/webhook + return sync; prețuri create automat; plată recurentă Stripe Billing.',
        ],
    ],
    [
        'version' => '1.0.130',
        'date' => '2026-08-07',
        'title' => 'Mail recomandare cu cod promo',
        'changes' => [
            'Buton „Trimite mail recomandare” în meniul societății și în Date generale.',
            'Introduci adresele; se trimite email personalizat cu codul promo mare și instrucțiuni de folosire la înregistrare.',
        ],
    ],
    [
        'version' => '1.0.129',
        'date' => '2026-08-07',
        'title' => 'Help: sincronizare cu funcțiile recente',
        'changes' => [
            'Completat Ajutorul pentru UM e-Factura, întocmit/delegat, PDF (machete, +/− imagini, semnătură/ștampilă), e-Factura (ID-uri, refresh 30s, respingeri), clienți (bancă/admin), navigare Integrări și FAQ (fus orar, redirect după salvare).',
        ],
    ],
    [
        'version' => '1.0.128',
        'date' => '2026-08-07',
        'title' => 'Help: plată cu cardul online',
        'changes' => [
            'Secțiune nouă în Ajutor: configurare NETOPIA / Eu Plătesc / Mollie per firmă și folosirea pe factură.',
            'Actualizări în Încasări, Emitere factură, Liste și Preferințe (redirect după salvare, documente pe pagină).',
        ],
    ],
    [
        'version' => '1.0.127',
        'date' => '2026-08-07',
        'title' => 'După salvare → lista documentelor',
        'changes' => [
            'După salvare/emitere revii în lista tipului (facturi, proforme etc.), nu pe ecranul de încasare.',
            'Listele sunt ordonate cu cele mai noi sus; în Preferințe poți seta câte documente apar pe pagină.',
        ],
    ],
    [
        'version' => '1.0.126',
        'date' => '2026-08-07',
        'title' => 'Fus orar București',
        'changes' => [
            'Aplicația folosește fusul orar Europe/Bucharest — data facturii noi nu mai rămâne cu o zi în urmă.',
        ],
    ],
    [
        'version' => '1.0.125',
        'date' => '2026-08-07',
        'title' => 'UM cu coduri e-Factura',
        'changes' => [
            'Unitatea de măsură se alege din listă (buc=H87, m³=MTQ, kg=KGM etc.) și se scrie corect în XML e-Factura.',
        ],
    ],
    [
        'version' => '1.0.124',
        'date' => '2026-08-07',
        'title' => 'Delegat — listă din istoric',
        'changes' => [
            'Numele de delegat completate pe facturi se păstrează și apar la alegere data viitoare (cu buletin, dacă a fost salvat).',
        ],
    ],
    [
        'version' => '1.0.123',
        'date' => '2026-08-07',
        'title' => 'Cantitate/preț fără săgeți',
        'changes' => [
            'La facturi, proforme, recurente: cantitate și preț se completează liber, fără săgeți de incrementare.',
        ],
    ],
    [
        'version' => '1.0.122',
        'date' => '2026-08-07',
        'title' => 'e-Factura UBL + ID-uri pe listă',
        'changes' => [
            'XML aliniat CIUS-RO (prefix RO, scheme 9947, SECTOR pentru București, scadență/termeni plată).',
            'În lista de facturi: ID încărcare și ID descărcare; refresh automat la 30s când aștepți status SPV.',
        ],
    ],
    [
        'version' => '1.0.121',
        'date' => '2026-08-07',
        'title' => 'e-Factura: status ANAF corect',
        'changes' => [
            'Citire corectă a stării din răspunsul ANAF (atribute XML) — nu mai rămâne blocat pe „așteaptă validare”.',
            'La respingere se descarcă și se afișează mesajul de eroare ANAF.',
            'XML e-Factura: prefix RO pe CUI TVA + scadență obligatorie când există sumă de plată (BR-CO-09 / BR-CO-25).',
        ],
    ],
    [
        'version' => '1.0.120',
        'date' => '2026-08-07',
        'title' => 'Meniu scroll + semnătură/ștampilă pe PDF',
        'changes' => [
            'Submeniurile lungi (ex. Setări) se derulează ca să vezi toate opțiunile.',
            'Pe factură: Semnătură și Ștampilă unul sub altul, cu poza dedesubt și linia centrată sub imagine.',
        ],
    ],
    [
        'version' => '1.0.119',
        'date' => '2026-08-07',
        'title' => 'Întocmit de — text liber',
        'changes' => [
            'La factură poți scrie liber cine a întocmit documentul sau alege din sugestii (utilizatori / valori folosite anterior).',
        ],
    ],
    [
        'version' => '1.0.118',
        'date' => '2026-08-07',
        'title' => 'Integrări card per firmă',
        'changes' => [
            'Fiecare societate își configurează NETOPIA / Eu Plătesc / Mollie în Setări → Integrări.',
            'Credențialele din Admin rămân doar pentru plata abonamentului DateConta.',
        ],
    ],
    [
        'version' => '1.0.117',
        'date' => '2026-08-07',
        'title' => 'Întocmit de = administrator',
        'changes' => [
            'Pe factură, „Întocmit de” folosește numele administratorului din decizia de inseriere serii.',
        ],
    ],
    [
        'version' => '1.0.116',
        'date' => '2026-08-07',
        'title' => 'Machetă PDF pe tot A4',
        'changes' => [
            'Factura umple pagina A4 (coloană laterală + semnătură/totaluri jos), fără pagină goală.',
        ],
    ],
    [
        'version' => '1.0.115',
        'date' => '2026-08-07',
        'title' => 'PDF fără pagini goale la început',
        'changes' => [
            'Eliminat size:A4 din CSS @page (conflict cu setPaper) — evita pagini albe înaintea facturii.',
            'Download PDF fără cache în browser.',
        ],
    ],
    [
        'version' => '1.0.114',
        'date' => '2026-08-07',
        'title' => 'PDF fără pagină goală',
        'changes' => [
            'Eliminat forțarea pe înălțime A4 (min-height / absolute / height:100%) care genera pagină goală în DomPDF.',
            'Machetele revin la flux normal, o singură pagină când încap liniile.',
        ],
    ],
    [
        'version' => '1.0.113',
        'date' => '2026-08-07',
        'title' => 'Machetă PDF pe tot A4',
        'changes' => [
            'Factura umple pagina A4 pe înălțime (semnătură/totaluri jos), indiferent câte linii are.',
        ],
    ],
    [
        'version' => '1.0.112',
        'date' => '2026-08-07',
        'title' => 'Scară doar pe logo/semnătură/ștampilă',
        'changes' => [
            '+/− redimensionează exclusiv imaginile de branding pe PDF, cu plafon — nu macheta și nu liniile facturii.',
            'Machetele Swiss / Split / Stripe readuse la layout-ul original.',
        ],
    ],
    [
        'version' => '1.0.111',
        'date' => '2026-08-07',
        'title' => 'Scară imagini 25%–200% pas 25',
        'changes' => [
            'Logo / semnătură / ștampilă: − până la 25%, + până la 200%, din 25 în 25.',
        ],
    ],
    [
        'version' => '1.0.110',
        'date' => '2026-08-06',
        'title' => 'Dimensiune imagini: butoane + / −',
        'changes' => [
            'În Personalizare, logo / semnătură / ștampilă se măresc sau micșorează cu + și −.',
        ],
    ],
    [
        'version' => '1.0.109',
        'date' => '2026-08-06',
        'title' => 'PDF A4 stabil + scară imagini reală',
        'changes' => [
            'Machetele PDF evită paginile goale (layout fără înălțimi forțate / tabele full-page).',
            'Logo, semnătură și ștampilă: dimensiune prin width/height reale (25% / 33% / 50% / 100%).',
        ],
    ],
    [
        'version' => '1.0.108',
        'date' => '2026-08-06',
        'title' => 'Scară logo / semnătură / ștampilă pe factură',
        'changes' => [
            'În Personalizare: dimensiune afișată 1/4, 1/3, 1/2 sau 1/1 pentru logo, semnătură și ștampilă.',
        ],
    ],
    [
        'version' => '1.0.107',
        'date' => '2026-08-06',
        'title' => 'PDF fără pagini goale',
        'changes' => [
            'Eliminate min-height și footer absolut care forțau o pagină goală la generarea PDF pe toate machetele.',
        ],
    ],
    [
        'version' => '1.0.106',
        'date' => '2026-08-06',
        'title' => '8 machete PDF noi pentru facturi',
        'changes' => [
            'Adăugate template-uri: Nord, Ledger, Studio, Frame, Swiss, Folio, Split, Ticket.',
            'Selectabile în Setări → Personalizare PDF, alături de cele 6 existente.',
        ],
    ],
    [
        'version' => '1.0.105',
        'date' => '2026-08-06',
        'title' => 'Plată cu card pe facturi / proforme',
        'changes' => [
            'Bifa „Permite plata cu cardul online” e activă doar dacă există cel puțin un procesator configurat în Integrări.',
            'Pe PDF apar linkuri semnate către NETOPIA / Eu Plătesc / Mollie (procesatoarele active).',
            'Încasarea online marchează automat documentul ca achitat.',
        ],
    ],
    [
        'version' => '1.0.104',
        'date' => '2026-08-06',
        'title' => 'Setări Integrări: NETOPIA, Eu Plătesc, Mollie',
        'changes' => [
            'Categorie Integrări (admin) în Setări pentru configurarea încasării cu cardul.',
            'Setări platformă în DB pentru NETOPIA, Eu Plătesc și Mollie.',
            'Checkout: opțiune Eu Plătesc alături de NETOPIA și Mollie.',
        ],
    ],
    [
        'version' => '1.0.103',
        'date' => '2026-08-06',
        'title' => 'Fix PDF: sintaxă Blade company-block',
        'changes' => [
            'Eroarea 500 la PDF draft: @if consecutive pe aceeași linie în company-block; rescris template-ul.',
        ],
    ],
    [
        'version' => '1.0.102',
        'date' => '2026-08-06',
        'title' => 'Fix PDF draft (Dompdf autoload)',
        'changes' => [
            'Eroarea 500 la PDF era cauzată de un fișier duplicat Dompdf în autoload; regenerat și redeploy.',
        ],
    ],
    [
        'version' => '1.0.101',
        'date' => '2026-08-06',
        'title' => 'Etichetă CNP administrator pe fișa client',
        'changes' => [
            'Câmpul CNP de pe fișa clientului este etichetat CNP administrator.',
        ],
    ],
    [
        'version' => '1.0.100',
        'date' => '2026-08-06',
        'title' => 'Nume/prenume administrator pe fișa client',
        'changes' => [
            'Pe fișa clientului, înainte de CNP: Nume administrator și Prenume administrator.',
        ],
    ],
    [
        'version' => '1.0.099',
        'date' => '2026-08-06',
        'title' => 'Bancă pe fișa clientului',
        'changes' => [
            'Pe Catalog → Clienți, după IBAN/cont există câmpul Bancă (autocompletare din IBAN).',
        ],
    ],
    [
        'version' => '1.0.098',
        'date' => '2026-08-06',
        'title' => 'ANPC pe același rând cu Comandă, aliniat dreapta',
        'changes' => [
            'Logo ANPC în același chenar cu butonul Comandă, pe același rând, aliniat la dreapta.',
        ],
    ],
    [
        'version' => '1.0.097',
        'date' => '2026-08-06',
        'title' => 'ANPC în dreapta chenarului de comandă',
        'changes' => [
            'Logo ANPC mutat în coloana din dreapta formularului, nu lângă butonul Comandă.',
        ],
    ],
    [
        'version' => '1.0.096',
        'date' => '2026-08-06',
        'title' => 'Comandă: logo Netopia oficial + ANPC lângă buton',
        'changes' => [
            'Sigla NETOPIA din card e badge-ul oficial (nu banda roșie).',
            'Logo ANPC mutat în dreapta butonului Comandă.',
        ],
    ],
    [
        'version' => '1.0.095',
        'date' => '2026-08-06',
        'title' => 'Mollie: plată recurentă',
        'changes' => [
            'La card, opțiunea Plată recurentă e disponibilă și pentru Mollie (customer + mandat).',
            'Reînnoire automată zilnică: subscriptions:charge-mollie-recurring.',
        ],
    ],
    [
        'version' => '1.0.094',
        'date' => '2026-08-06',
        'title' => 'Fix prelungire: de la promo, inclusiv admin',
        'changes' => [
            'Prelungirea la plată pornește de la max(31.03.2027, access_until), inclusiv pentru conturi admin — nu de la data plății.',
        ],
    ],
    [
        'version' => '1.0.093',
        'date' => '2026-08-06',
        'title' => 'Prelungire abonament de la data promo (31.03.2027)',
        'changes' => [
            'La plată, perioada cumpărată se adaugă după data efectivă de acces (inclusiv gratuit până la 31.03.2027), nu de la data plății.',
        ],
    ],
    [
        'version' => '1.0.092',
        'date' => '2026-08-06',
        'title' => 'Procesatori card: Netopia | Mollie pe același rând',
        'changes' => [
            'NETOPIA și Mollie apar unul lângă altul; fiecare are siglă vizibilă.',
        ],
    ],
    [
        'version' => '1.0.091',
        'date' => '2026-08-06',
        'title' => 'Fix redirect plată Mollie (CSP)',
        'changes' => [
            'După Comandă cu Mollie, browserul bloca redirectul din cauza CSP form-action; acum trece prin pagină intermediară ca la Netopia.',
        ],
    ],
    [
        'version' => '1.0.090',
        'date' => '2026-08-06',
        'title' => 'Plată card: alegere Netopia sau Mollie',
        'changes' => [
            'La plata cu cardul se alege procesatorul (NETOPIA sau Mollie) înainte de finalizarea comenzii.',
            'Integrare Mollie (mollie-api-php): checkout redirect + webhook /billing/mollie/webhook.',
        ],
    ],
    [
        'version' => '1.0.089',
        'date' => '2026-08-06',
        'title' => 'Politici legale extinse (v2)',
        'changes' => [
            'Cele 5 pagini Legal (Termeni, Confidențialitate, Livrare, Anulare, GDPR) au fost rescrise mult mai detaliat — secțiuni, tabele, fluxuri și excepții.',
        ],
    ],
    [
        'version' => '1.0.088',
        'date' => '2026-08-06',
        'title' => 'Meniu Legal + politici complete',
        'changes' => [
            'Meniu Legal: Termeni și condiții, Confidențialitate, Livrare comandă, Anulare comandă, GDPR — pagini detaliate, accesibile și public.',
        ],
    ],
    [
        'version' => '1.0.087',
        'date' => '2026-08-06',
        'title' => 'Notificare expirare și în aplicație',
        'changes' => [
            'La 10 și 5 zile înainte de expirare: pe lângă email, apare notificare in-app (banner + clopoțel) cu buton Comandă.',
        ],
    ],
    [
        'version' => '1.0.086',
        'date' => '2026-08-06',
        'title' => 'Buton Comandă în reminder și fereastra cont',
        'changes' => [
            'În emailul de expirare abonament și în fereastra info de la login există butonul Comandă (către pagina de abonament).',
        ],
    ],
    [
        'version' => '1.0.085',
        'date' => '2026-08-06',
        'title' => 'Reminder expirare + fereastră cont la login',
        'changes' => [
            'Email automat cu 10 și 5 zile înainte de expirarea abonamentului (cron zilnic).',
            'La autentificare: fereastră centrată cu nume, email, societăți active (cod promo copiabil + dată expirare); se închide la ESC, buton sau după 1 minut.',
        ],
    ],
    [
        'version' => '1.0.084',
        'date' => '2026-08-06',
        'title' => '6 luni gratuite după 31.03.2027',
        'changes' => [
            'Conturile noi create după 31.03.2027 primesc automat 6 luni gratuite de la înregistrare (config trial_months_after_promo).',
            'Mesaj clar la înregistrare și în ajutor despre perioada gratuită / 6 luni.',
        ],
    ],
    [
        'version' => '1.0.083',
        'date' => '2026-08-06',
        'title' => 'Logo ANPC SAL lângă Netopia',
        'changes' => [
            'Pe pagina de comandă (plata cu cardul): logo ANPC SAL lângă Netopia, cu link la reclamatiisal.anpc.ro.',
        ],
    ],
    [
        'version' => '1.0.082',
        'date' => '2026-08-06',
        'title' => 'Logo Netopia la plata cu cardul',
        'changes' => [
            'Pe pagina de comandă abonament, la opțiunea «Cu cardul», apare badge-ul oficial NETOPIA Payments.',
        ],
    ],
    [
        'version' => '1.0.081',
        'date' => '2026-08-06',
        'title' => 'Factură către persoană fizică (CNP / „-”)',
        'changes' => [
            'La emitere: tastează CNP (13 cifre) sau „-” + Enter — creezi / selectezi o persoană fizică; la „-” se creează mereu o persoană nouă (nu se refolosește aceeași).',
            'CIF firmă + Enter preia în continuare datele din ANAF; pe PDF/factură, pentru PF apare eticheta CNP.',
        ],
    ],
    [
        'version' => '1.0.080',
        'date' => '2026-08-06',
        'title' => 'Fix redirect plată Netopia',
        'changes' => [
            'CSP permite acum trimiterea formularului de plată către gateway-ul Netopia (auto-redirect + buton Continuă către plată).',
        ],
    ],
    [
        'version' => '1.0.079',
        'date' => '2026-08-06',
        'title' => 'Bonus la abonamente mai lungi',
        'changes' => [
            'La comandă: 3 luni → +1 săptămână bonus, 6 luni → +2 săptămâni, 1 an → +1 lună; bonusul se aplică automat la confirmarea plății.',
        ],
    ],
    [
        'version' => '1.0.078',
        'date' => '2026-08-06',
        'title' => 'Societate activă vizibilă în listă',
        'changes' => [
            'În Societățile mele, societatea activă e marcată clar (badge Activă); dacă nu exista alegere, se setează automat prima firmă.',
        ],
    ],
    [
        'version' => '1.0.077',
        'date' => '2026-08-06',
        'title' => 'Admin: confirmare OP automată',
        'changes' => [
            'În Admin → Comenzi OP / abonament poți confirma plățile prin bancă; la confirmare abonamentul se activează automat (access_until + plan paid).',
        ],
    ],
    [
        'version' => '1.0.076',
        'date' => '2026-08-06',
        'title' => 'Comandă abonament + Netopia',
        'changes' => [
            'În Societățile mele: coloană «Abon. expiră la» (data + promoții) și buton Comandă.',
            'Pagină comandă: perioade 1/3/6 luni și 1 an, facturare pe firma aleasă, plată OP sau card Netopia.',
            'IPN Netopia la /billing/netopia/confirm; chei NETOPIA_* și PLATFORM_IBAN în .env.',
        ],
    ],
    [
        'version' => '1.0.075',
        'date' => '2026-08-06',
        'title' => 'Meniu: Societățile mele',
        'changes' => [
            'În Setări, „Toate societățile” a devenit „Societățile mele”.',
        ],
    ],
    [
        'version' => '1.0.074',
        'date' => '2026-08-06',
        'title' => 'Societate activă obligatorie',
        'changes' => [
            'Dacă utilizatorul are societăți, una e mereu activă: cea aleasă, sau prima (cea mai veche) dacă nu a ales încă; cu o singură firmă, ea e implicit activă.',
            'Preferința e salvată pe cont (current_company_id) și în sesiune.',
        ],
    ],
    [
        'version' => '1.0.073',
        'date' => '2026-08-06',
        'title' => 'Landing: notă perioadă de testare',
        'changes' => [
            'Pe prima pagină: scuze pentru eventuale erori din testare și invitație de a trimite capturi + descriere la contact.facturare@dateconta.ro.',
        ],
    ],
    [
        'version' => '1.0.072',
        'date' => '2026-08-06',
        'title' => 'Admin: perioade promo +/− săptămâni',
        'changes' => [
            'În Societăți & promoții poți adăuga sau scădea perioada în multipli de săptămâni (1–104).',
            'Preset-uri rapide mai mari: +1/+2/+4 săptămâni, +1/+3/+6 luni, +1 an.',
        ],
    ],
    [
        'version' => '1.0.071',
        'date' => '2026-08-06',
        'title' => 'Ajutor: captură meniu cont',
        'changes' => [
            'Figura 1 din capitolul Cod promoțional arată clar meniul contului deschis, cu codul vizibil.',
        ],
    ],
    [
        'version' => '1.0.070',
        'date' => '2026-08-06',
        'title' => 'Ajutor: cod promoțional',
        'changes' => [
            'Capitol nou în manual: unde găsești codul promoțional, cum funcționează recompensele, cu capturi de ecran.',
            'Link în meniul Ajutor și întrebări frecvente.',
        ],
    ],
    [
        'version' => '1.0.069',
        'date' => '2026-08-06',
        'title' => 'Admin: copiere cod promoțional',
        'changes' => [
            'În Societăți & promoții, click pe un cod promoțional îl copiază în clipboard (inclusiv codul firmei care a recomandat).',
        ],
    ],
    [
        'version' => '1.0.068',
        'date' => '2026-08-06',
        'title' => 'Admin: click dreapta permis',
        'changes' => [
            'Utilizatorii admin pot folosi meniul contextual (click dreapta); protecția rămâne pentru restul utilizatorilor.',
        ],
    ],
    [
        'version' => '1.0.067',
        'date' => '2026-08-06',
        'title' => 'Sticker recomandări pe stânga',
        'changes' => [
            'Stickerul flotant „Recomandă & câștigă” e pe stânga, sub stickerul de promisiune.',
        ],
    ],
    [
        'version' => '1.0.066',
        'date' => '2026-08-06',
        'title' => 'Admin: perioade societăți și promoții manuale',
        'changes' => [
            'În Admin → Societăți & promoții vezi perioada de acces a fiecărei firme (pe contul proprietarului).',
            'Poți acorda rapid +1 săptămână, +2 săptămâni sau +1 lună oricărei societăți.',
        ],
    ],
    [
        'version' => '1.0.065',
        'date' => '2026-08-06',
        'title' => 'Sticker promoție recomandări pe landing',
        'changes' => [
            'Pe prima pagină: bandă + sticker flotant pentru promoția „Recomandă & câștigă”, plus secțiune dedicată pe pagină.',
            'Mesajul apare și în emailurile de brand.',
        ],
    ],
    [
        'version' => '1.0.064',
        'date' => '2026-08-06',
        'title' => 'Recomandare cu cod promoțional la creare societate',
        'changes' => [
            'La adăugarea unei societăți poți introduce un cod promoțional valid: tu primești +2 săptămâni la abonament.',
            'Firma care a recomandat primește +1 lună la fiecare 2 societăți create cu codul ei.',
        ],
    ],
    [
        'version' => '1.0.063',
        'date' => '2026-08-06',
        'title' => 'Copiere rapidă cod promoțional',
        'changes' => [
            'Click pe codul promoțional (meniu societate, Date firmă, listă societăți) îl copiază în clipboard pentru trimis pe email, WhatsApp etc.',
        ],
    ],
    [
        'version' => '1.0.062',
        'date' => '2026-08-06',
        'title' => 'Meniu societate / cont în antet',
        'changes' => [
            'Numele societății din antet are săgeată și deschide un panou: cod promoțional, promoții, zile rămase din abonament, schimbare/adăugare firmă.',
            'Deconectarea s-a mutat în acest panou — meniul principal e mai aerisit.',
        ],
    ],
    [
        'version' => '1.0.061',
        'date' => '2026-08-06',
        'title' => 'Cod promoțional unic pe societate',
        'changes' => [
            'Fiecare societate primește automat un cod promoțional unic de forma XXXX-XXXX-XXXX (litere majuscule și cifre).',
            'Societățile existente au primit coduri la migrare; codul apare în Date firmă și în lista de societăți.',
        ],
    ],
    [
        'version' => '1.0.060',
        'date' => '2026-08-06',
        'title' => 'Factură storno și notă de creditare',
        'changes' => [
            'În Emite și Document nou apar Factură storno și Notă de creditare — alegi factura emisă și se creează documentul cu linii negative.',
            'Liste separate: Facturi storno și Note de creditare; serie NC pentru note de creditare; e-Factura (cod 384 / 381).',
            'Storno și nota de creditare se exclud reciproc pe aceeași factură.',
        ],
    ],
    [
        'version' => '1.0.059',
        'date' => '2026-08-06',
        'title' => 'Ecrane centrate în browser',
        'changes' => [
            'Panoul aplicației (meniu + conținut) e centrat pe ecrane late, indiferent de browser.',
            'Formularele cu lățime limitată (clienți, produse, societăți, încasare, login) stau pe mijloc.',
        ],
    ],
    [
        'version' => '1.0.058',
        'date' => '2026-08-06',
        'title' => 'Încasare: chitanță / OP pe facturi',
        'changes' => [
            'Formular tip încasare: după alegerea clientului apar facturile neîncasate, cu bifare, sumă și „reprezentând” automate.',
            'Chitanță maxim 5000 RON / client / zi; peste limită tipul trece automat pe OP. Fără selecție, încasarea e liberă.',
            '„Chitanță nouă” redenumită „Încasare nouă” (dashboard și meniu Emite).',
        ],
    ],
    [
        'version' => '1.0.057',
        'date' => '2026-08-06',
        'title' => 'Document nou pe dashboard',
        'changes' => [
            'Pe dashboard, butonul „Factură nouă” devine „Document nou” cu meniu: factură, proformă, abonament, chitanță, aviz.',
        ],
    ],
    [
        'version' => '1.0.056',
        'date' => '2026-08-06',
        'title' => 'Istoric complet de la 1.0.000',
        'changes' => [
            'În „Ce este nou…” apar toate versiunile, de la 1.0.000 până la cea curentă.',
        ],
    ],
    [
        'version' => '1.0.055',
        'date' => '2026-08-06',
        'title' => 'Fix Abonament nou (500)',
        'changes' => [
            'Pagina „Abonament nou” nu mai dă eroare 500 când nu există abonament existent (acces nullsafe pe formular).',
        ],
    ],
    [
        'version' => '1.0.054',
        'date' => '2026-08-06',
        'title' => 'Performanță maximă (TTFB)',
        'changes' => [
            'Tracking vizitatori după răspuns (fără GeoIP pe hot path); sesiune fără criptare inutilă; locale fără dirty session.',
            'Interogări mai slabe pe dashboard, documente și rapoarte; indexuri DB pe facturi/plăți/clienți/produse.',
            'Fonturi non-blocking, CompanyContext memoizat, Livewire fără auto-discover.',
        ],
    ],
    [
        'version' => '1.0.053',
        'date' => '2026-08-06',
        'title' => 'Stabilitate sub încărcare (server)',
        'changes' => [
            'Sesiuni și cozi în baza de date (mai puține lock-uri pe disc); worker de coadă din cron.',
            'Mai puțină muncă pe fiecare click: fără procesare recurente/e-Factura pe dashboard/pagini; tracking vizitatori cu debounce.',
            'Cache asset-uri statice, limite PHP (.user.ini), endpoint /health și proxy trust pentru LiteSpeed.',
        ],
    ],
    [
        'version' => '1.0.052',
        'date' => '2026-08-06',
        'title' => 'Produse cu variabile în nomenclator',
        'changes' => [
            'La salvarea abonamentului, produsele cu #luna#, #an# etc. în denumire/descriere se creează și ele în nomenclator.',
        ],
    ],
    [
        'version' => '1.0.051',
        'date' => '2026-08-06',
        'title' => 'Avertizare client fără cont bancar',
        'changes' => [
            'La salvarea sau emiterea unei facturi, dacă clientul nu are IBAN/cont bancar, apare o avertizare cu link către fișa clientului.',
        ],
    ],
    [
        'version' => '1.0.050',
        'date' => '2026-08-06',
        'title' => 'Linii factură recurentă: produs / descriere',
        'changes' => [
            'Pe abonamente: aceleași reguli ca pe factură — produs obligatoriu, descriere opțională, autocomplete live; produsul lipsă se creează la salvare.',
            'Câmpul „Număr abonament” e mutat sub zona produs/descriere.',
        ],
    ],
    [
        'version' => '1.0.049',
        'date' => '2026-08-06',
        'title' => 'Subsol comun factură / proformă / recurentă',
        'changes' => [
            'Subsolul cu date adiționale (contract, aviz, întocmit de, delegat, mențiuni, email) se aplică la factură, proformă și factură recurentă.',
            'Etichetele se adaptează după tipul documentului; antetul de proformă rămâne același ca la factură.',
        ],
    ],
    [
        'version' => '1.0.048',
        'date' => '2026-08-06',
        'title' => 'Cap formular factură recurentă',
        'changes' => [
            'Antetul abonamentului e aliniat cu facturile: client, dată prima factură, termen plată, frecvență, nr. documente, serie, monedă, limbă, nr. abonament.',
            'Poți limita numărul de facturi generate (-1 / gol = nelimitat); seria și limba se aplică la emiterea automată.',
        ],
    ],
    [
        'version' => '1.0.047',
        'date' => '2026-08-06',
        'title' => 'Export XML e-Factura (SPV manual)',
        'changes' => [
            'În lista de facturi: buton „Generare / Salvare fișiere .xml” pentru facturile selectate (UBL compatibil ANAF).',
            'La mai multe facturi, poți alege folderul de salvare (Chrome/Edge) sau descarci o arhivă ZIP.',
        ],
    ],
    [
        'version' => '1.0.046',
        'date' => '2026-08-06',
        'title' => 'Subsol factură: date adiționale',
        'changes' => [
            'La emitere facturi și abonamente: contract (BT-12), aviz (BT-16), întocmit de, delegat, auto, mențiuni, email automat.',
            'Câmpurile apar pe PDF; BT-12/BT-16 sunt incluse în e-Factura.',
        ],
    ],
    [
        'version' => '1.0.045',
        'date' => '2026-08-06',
        'title' => 'Emitere document pe toată lățimea',
        'changes' => [
            'Formularele de emitere și editare document (și abonamente) folosesc toată lățimea ferestrei.',
        ],
    ],
    [
        'version' => '1.0.044',
        'date' => '2026-08-06',
        'title' => 'Alertă facturi netrimise în e-Factura',
        'changes' => [
            'În lista de facturi, cele emise de peste 5 zile calendaristice și nedepuse în e-Factura apar cu roșu bold.',
            'Dacă există astfel de facturi, sus pe pagină apare avertismentul de trimitere sau anulare/ștergere imediată.',
        ],
    ],
    [
        'version' => '1.0.043',
        'date' => '2026-08-06',
        'title' => 'Email: variabile și SMTP propriu',
        'changes' => [
            'În Configurare → Email poți personaliza subiectul și mesajul cu termeni variabili (#tip document#, #total document#, #nume client# etc.).',
            'Opțional: server SMTP propriu (host, port, TLS, utilizator, parolă) pentru trimiterea documentelor din contul firmei.',
        ],
    ],
    [
        'version' => '1.0.042',
        'date' => '2026-08-06',
        'title' => 'Variabile pe facturi recurente',
        'changes' => [
            'În abonamente poți folosi #luna#, #an#, #luna+1#, #luna-2# etc. în denumire, descriere și observații.',
            'Variabilele se înlocuiesc automat la emiterea facturii, după data de emitere.',
        ],
    ],
    [
        'version' => '1.0.041',
        'date' => '2026-08-06',
        'title' => 'Fix factură recurentă (500)',
        'changes' => [
            'Corectat eroare Server Error la deschiderea formularului de factură recurentă.',
        ],
    ],
    [
        'version' => '1.0.040',
        'date' => '2026-08-06',
        'title' => 'CUI cu RO și județe București',
        'changes' => [
            'În Configurare → Generale, CUI-ul se afișează cu prefix RO pentru plătitori TVA și fără RO pentru neplătitori.',
            'Sectoarele București apar în listă ca „București - Sector 1” … „Sector 6”.',
        ],
    ],
    [
        'version' => '1.0.039',
        'date' => '2026-08-06',
        'title' => 'Blocare emitere fără serie activă',
        'changes' => [
            'Dacă un tip de document nu are nicio serie activă, butonul de adăugare din listă e inactiv și apare un mesaj roșu clar pe pagină.',
        ],
    ],
    [
        'version' => '1.0.038',
        'date' => '2026-08-06',
        'title' => 'Logo → pagina principală',
        'changes' => [
            'Logo-ul din stânga meniului deschide pagina principală a site-ului (fără delogare); „Acasă” rămâne pe dashboard.',
            'De pe pagina principală, utilizatorii autentificați revin în aplicație cu „Intră în aplicație”.',
        ],
    ],
    [
        'version' => '1.0.037',
        'date' => '2026-08-06',
        'title' => 'Tab-uri Configurare pe un rând',
        'changes' => [
            'Meniul de tab-uri din Configurare societate este redimensionat să stea pe un singur rând.',
        ],
    ],
    [
        'version' => '1.0.036',
        'date' => '2026-08-06',
        'title' => 'Submeniuri tip dropdown',
        'changes' => [
            'Submeniurile din navigare se deschid ca dropdown sub fiecare secțiune (Emite, Liste, Catalog etc.).',
        ],
    ],
    [
        'version' => '1.0.035',
        'date' => '2026-08-06',
        'title' => 'Meniu responsive fără overlap',
        'changes' => [
            'Bara de navigare nu mai suprapune meniul peste limbă / firmă / user / ieșire.',
            'Pe ferestre mai înguste: meniul și acțiunile din dreapta se comprimă la iconițe, cu text la hover (tooltip).',
            'Submeniurile se așază pe mai multe rânduri când e nevoie, fără să se acopere între ele.',
        ],
    ],
    [
        'version' => '1.0.034',
        'date' => '2026-08-06',
        'title' => 'Trimitere email mai fiabilă',
        'changes' => [
            'Emailurile (inclusiv invitația SPV pentru contabil) se trimit cu failover SMTP 465 → 587 → sendmail și reîncercări.',
            'După „Trimite invitația” apare confirmarea cu destinatar, dată și oră, plus linkul de autorizare de rezervă.',
        ],
    ],
    [
        'version' => '1.0.033',
        'date' => '2026-08-06',
        'title' => 'Prelungire și revocare SPV',
        'changes' => [
            'Când firma e autorizată SPV: buton „Prelungește conectarea” (+90 zile de la apăsare) și „Revocă conectarea”.',
            'În status se afișează data până la care e valabilă conectarea.',
        ],
    ],
    [
        'version' => '1.0.032',
        'date' => '2026-08-06',
        'title' => 'Protecție click dreapta',
        'changes' => [
            'La click dreapta în aplicație apare mesajul „Nimic interesant aici!!! GO BACK!” și meniul contextual este blocat.',
        ],
    ],
    [
        'version' => '1.0.031',
        'date' => '2026-08-06',
        'title' => 'Securitate aplicație',
        'changes' => [
            'Headere de securitate (CSP, HSTS, X-Frame-Options, nosniff) pe toate răspunsurile.',
            'Forțare HTTPS, protecție .htaccess pentru fișiere sensibile și blocare execuție PHP în uploads.',
            'Limitare încercări login/register/reset și sesiuni criptate cu cookie securizat.',
            'Codul PHP/Blade rămâne pe server — nu este servit public; fișiere periculoase de deploy eliminate.',
        ],
    ],
    [
        'version' => '1.0.030',
        'date' => '2026-08-06',
        'title' => 'Versiune din istoric',
        'changes' => [
            'Versiunea curentă din footer se ia automat din prima (cea mai recentă) intrare din „Ce este nou…”.',
            'Nu mai există o versiune separată de sincronizat manual — istoricul este sursa unică.',
        ],
    ],
    [
        'version' => '1.0.029',
        'date' => '2026-08-06',
        'title' => 'Ce este nou…',
        'changes' => [
            'Meniu Ajutor → „Ce este nou…” cu istoricul cronologic al versiunilor.',
            'Istoricul se actualizează automat la fiecare increment de versiune.',
        ],
    ],
    [
        'version' => '1.0.028',
        'date' => '2026-08-06',
        'title' => 'Manual de utilizare',
        'changes' => [
            'Meniu Ajutor cu manual detaliat pe capitole (emitere, clienți, e-Factura, limbi etc.).',
            'Capturi de ecran din aplicație în fiecare capitol.',
            'Regulă internă: help-ul se actualizează odată cu modificările de produs.',
        ],
    ],
    [
        'version' => '1.0.027',
        'date' => '2026-08-06',
        'title' => 'Versiune în footer',
        'changes' => [
            'Numărul de versiune afișat în colțul din dreapta jos (footer).',
            'Incrementare automată a versiunii la îmbunătățiri majore.',
        ],
    ],
    [
        'version' => '1.0.026',
        'date' => '2026-08-06',
        'title' => 'Limbă UI și factură',
        'changes' => [
            'Limbă interfață (întreaga aplicație) separată de limba documentului PDF.',
            'Selector rapid de limbă în antet și în Preferințe personale.',
            'Limba de pe factură afectează doar PDF-ul acelui document.',
        ],
    ],
    [
        'version' => '1.0.025',
        'date' => '2026-08-06',
        'title' => 'Linii produs / descriere',
        'changes' => [
            'Produs și Descriere pe coloane separate; produs obligatoriu, descriere opțională.',
            'Listă live mai mare, deschisă din câmpul Produs sau Descriere.',
            'Adresa din ANAF fără județ/oraș (câmpuri separate) pentru firmă și clienți.',
        ],
    ],
    [
        'version' => '1.0.024',
        'date' => '2026-08-05',
        'title' => 'Meniu orizontal și PDF A4',
        'changes' => [
            'Meniu și submeniuri pe orizontală, în partea de sus a ecranului.',
            'PDF factură mereu A4; totaluri, semnătură și ștampilă pe ultima pagină.',
            'Nume fișier PDF: număr document + nume client; pe document doar serie + număr (fără an în plus).',
        ],
    ],
    [
        'version' => '1.0.023',
        'date' => '2026-08-05',
        'title' => 'Formular emitere tip document',
        'changes' => [
            'Formular de emitere aliniat la macheta tip factură: calendar dată, termene plată, chenare.',
            'Enter pe linie adaugă rând nou; produs/descriere 60%/40% cu listă live restrânsă.',
            'Previzualizare serie și număr care urmează la emitere; alegere între serii active.',
        ],
    ],
    [
        'version' => '1.0.022',
        'date' => '2026-08-05',
        'title' => 'Liste documente și serii',
        'changes' => [
            'În listă: selectare multiplă pentru e-Factura; status/plată în română; editare/anulare/ștergere/stornare pe draft.',
            'Serii: ștergere (doar fără documente emise), reutilizare număr la anulare/ștergere, decizie de înscriere pe documentele active.',
            'Solo două zecimale peste tot în aplicație.',
        ],
    ],
    [
        'version' => '1.0.021',
        'date' => '2026-08-05',
        'title' => 'Client și produs din factură',
        'changes' => [
            'La creare factură poți adăuga client după CUI (ANAF) și produse noi direct din linii.',
            'Email client: mai multe adrese separate prin virgulă.',
            'IBAN înaintea băncii, completare automată bancă din IBAN; majuscule pe IBAN/bancă, minuscule pe email.',
        ],
    ],
    [
        'version' => '1.0.020',
        'date' => '2026-08-05',
        'title' => 'Personalizare document',
        'changes' => [
            'Logo, semnătură și ștampilă (JPEG) sau text în loc de semnătură/ștampilă.',
            'Text precompletat: factură valabilă fără semnătură și ștampilă (art. 319 alin. 29).',
            'Alegere machetă/culoare factură din Configurare → Personalizare.',
        ],
    ],
    [
        'version' => '1.0.019',
        'date' => '2026-08-05',
        'title' => 'Date, TVA și ANAF',
        'changes' => [
            'Format dată zz/ll/aaaa în toată aplicația.',
            'Opțiune neplătitor TVA (obligatoriu plătitor sau neplătitor); culori butoane active mai vizibile.',
            'La import ANAF: județ/localitate în câmpuri dedicate; fără data înființării în localitate.',
        ],
    ],
    [
        'version' => '1.0.018',
        'date' => '2026-08-05',
        'title' => 'Configurare societate pe tab-uri',
        'changes' => [
            'Editare societăți cu tab-uri tip SmartBill (Generale, Serii, Bănci, Email, e-Factura etc.).',
            'Casa de marcat marcată „În curând”.',
        ],
    ],
    [
        'version' => '1.0.017',
        'date' => '2026-08-05',
        'title' => 'Statistici detaliate',
        'changes' => [
            'Refresh automat la 30s; browsere, sisteme de operare, utilizatori creați/logați (inclusiv admin).',
            'Corecție increment greșit la vizitatori unici la refresh.',
        ],
    ],
    [
        'version' => '1.0.016',
        'date' => '2026-08-05',
        'title' => 'Landing: reclamă sticky',
        'changes' => [
            'Siglă în chenarul de reclamă; chenar oblic, poziționat la mijlocul viewport-ului, urmărește scroll-ul fluid.',
        ],
    ],
    [
        'version' => '1.0.015',
        'date' => '2026-08-05',
        'title' => 'Email-uri branduite',
        'changes' => [
            'Toate emailurile compuse (autorizare SPV etc.) au fundal grafic cu siglă și mesaj promo.',
        ],
    ],
    [
        'version' => '1.0.014',
        'date' => '2026-08-05',
        'title' => 'Lansare pe email și geo vizitatori',
        'changes' => [
            'Reclama de pe site inclusă și în mailul de lansare.',
            'În statistici: țara de origine a fiecărui vizitator.',
        ],
    ],
    [
        'version' => '1.0.013',
        'date' => '2026-08-05',
        'title' => 'Notificări restanțe',
        'changes' => [
            'Setare frecvență notificări pentru facturi restante către clienți, cu email configurabil.',
        ],
    ],
    [
        'version' => '1.0.012',
        'date' => '2026-08-05',
        'title' => 'Favicon și titlu tab',
        'changes' => [
            'Logo-ul aplicației apare ca favicon în tab-urile browserului.',
        ],
    ],
    [
        'version' => '1.0.011',
        'date' => '2026-08-05',
        'title' => 'Mesaj promo pe landing',
        'changes' => [
            'Promisiunea DateConta pe prima pagină; după perioada de grație, abonamente de la 1,99 Eur/lună + TVA.',
        ],
    ],
    [
        'version' => '1.0.010',
        'date' => '2026-08-05',
        'title' => 'Facturi recurente (abonamente)',
        'changes' => [
            'Emitere automată cu frecvență săptămânală, lunară, trimestrială, semestrială și anuală.',
            'Gestionare abonamente: activ/pauză, generare acum, istoric documente.',
        ],
    ],
    [
        'version' => '1.0.009',
        'date' => '2026-08-05',
        'title' => 'Bănci și IBAN multiple',
        'changes' => [
            'La societate: mai multe bănci, fiecare cu unul sau mai multe IBAN-uri.',
            'Până la 3 conturi bifate pentru afișare pe factură.',
        ],
    ],
    [
        'version' => '1.0.008',
        'date' => '2026-08-05',
        'title' => 'Statistici: unici și total',
        'changes' => [
            'Pe pagina de statistici: vizitatori unici și total, afișați în fiecare chenar relevant.',
        ],
    ],
    [
        'version' => '1.0.007',
        'date' => '2026-08-05',
        'title' => 'Logo DateConta Facturare',
        'changes' => [
            'Logo photorealist pe tot site-ul, în aplicație și în emailuri.',
        ],
    ],
    [
        'version' => '1.0.006',
        'date' => '2026-08-05',
        'title' => 'Autorizare SPV prin contabil',
        'changes' => [
            'Trimitere invitație pe email către contabil, cu mesaj clar și link de autorizare SPV.',
        ],
    ],
    [
        'version' => '1.0.005',
        'date' => '2026-08-05',
        'title' => 'e-Factura și SPV ANAF',
        'changes' => [
            'Autorizare aplicație în SPV ANAF (direct sau prin contabil).',
            'Trimitere facturi emise în e-Factura din aplicație.',
        ],
    ],
    [
        'version' => '1.0.004',
        'date' => '2026-08-05',
        'title' => 'Landing marketing',
        'changes' => [
            'Prima pagină îmbogățită cu texte, imagini din aplicație și secțiuni colorate de prezentare.',
        ],
    ],
    [
        'version' => '1.0.003',
        'date' => '2026-08-05',
        'title' => 'Statistici vizitatori',
        'changes' => [
            'În meniul administrator: statistici cu număr de vizitatori de la lansare.',
        ],
    ],
    [
        'version' => '1.0.002',
        'date' => '2026-08-05',
        'title' => 'Cont admin protejat',
        'changes' => [
            'Contul de administrator nu poate fi șters din aplicație.',
        ],
    ],
    [
        'version' => '1.0.001',
        'date' => '2026-08-05',
        'title' => 'Cont admin și email platformă',
        'changes' => [
            'Cont administrator configurat; căsuță contact.facturare@dateconta.ro pentru emailurile aplicației.',
        ],
    ],
    [
        'version' => '1.0.000',
        'date' => '2026-08-05',
        'title' => 'Lansare DateConta Facturare',
        'changes' => [
            'Versiunea inițială: multi-firmă, clienți, produse, serii, facturi/proforme/avize/chitanțe, PDF, încasări, rapoarte.',
            'Gratuită pentru toți utilizatorii până la 31.03.2027; deploy pe factura.dateconta.ro.',
        ],
    ]
];
