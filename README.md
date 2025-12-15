# B2B/B2C E-commerce Platform — Backend

Backend pentru o platformă e-commerce **B2B/B2C** (API-first), cu **front-office** (public + utilizatori logați) și **back-office/admin**, plus integrări (ERP, curieri, procesator plăți).

> Tech overview: proiectul este construit pe **Laravel (PHP)** și include resurse front-end (Vue/Vite) în repo. :contentReference[oaicite:1]{index=1}

---

## Cuprins
- [Scop](#scop)
- [Roluri & permisiuni](#roluri--permisiuni)
- [Funcționalități cheie](#funcționalități-cheie)
- [Arhitectură & module](#arhitectură--module)
- [Integrare ERP / Curieri / Plăți](#integrare-erp--curieri--plăți)
- [Setup local](#setup-local)
- [Configurare .env](#configurare-env)
- [Bază de date](#bază-de-date)
- [Rulare](#rulare)
- [Testare](#testare)
- [Securitate & audit](#securitate--audit)
- [Contribuții](#contribuții)
- [Licență](#licență)

---

## Scop

Acest proiect implementează backend-ul unei platforme de comerț electronic cu:
- **Front-office**: catalog, căutare, coș, checkout, cont client (B2B/B2C).
- **Back-office / Admin**: produse, categorii, branduri, promoții, clienți, comenzi, livrare, încasări, permisiuni.
- **B2B avansat**: credit/termeni de plată, multi-user pe companie, workflow aprobare comenzi, ofertare/negociere, comenzi recurente.
- **Integrare**: ERP (sync bidirecțional), curieri (AWB + tracking), procesator plăți.

---

## Roluri & permisiuni

Roluri principale:
- **Guest**
- **Client B2C**
- **Client B2B (companie, multi-user)**
- **Agent vânzări**
- **Director vânzări**
- **Operator birou / suport**
- **Marketer**
- **Administrator (super admin)**

Permisiuni pe module (RBAC), cu audit pentru acțiuni critice (prețuri, promoții, condiții comerciale, sold/credit etc.).

---

## Funcționalități cheie

### Autentificare & conturi
- Login: email/username + parolă, „Ține-mă minte”, resetare parolă, rate limiting / anti brute-force.
- Register B2C + Register B2B (cu flux „Devino partener”).
- Social login (Google/Facebook) — planificat.

### Catalog & produse
- Produse simple și cu variante (atribute de variație: culoare, dimensiune etc.).
- Categorii ierarhice + atribute per categorie + filtre avansate.
- Branduri + pagină brand (listare produse).
- Documente atașate produs (public / după achiziție / pe bază de cerere cu aprobare).
- Produse asociate: similare + complementare (cross-sell / upsell).
- Stocuri: în stoc / la comandă / stoc limitat + termen estimativ.

### Promoții & pricing avansat
- Discount procentual / valoric / X→Y gratuit / bundle.
- Promoții exclusive vs iterative (cumulabile într-o ordine controlată).
- Segmentare: B2B/B2C, guest/logat, grupuri clienți, clienți individuali, branduri/categorii/produse.
- Discounturi generice: pe client/grup, prețuri contractuale, reguli de marjă/adaos.

### Comenzi & checkout
- Coș standard + cupon/voucher + estimare transport.
- Quick order (tabel cantități, adăugare rapidă).
- Checkout pe pași: adrese → livrare → plată → confirmare.
- Plată: card (3DS), OP; pentru B2B se ține cont de termen contractual.
- RMA/retur la nivel de linie comandă (self-service).

### B2B specific
- Termeni de plată, limită credit, sold curent/restant; blocaj comenzi la depășire credit (notificări client/agent/director).
- Multi-user companie + roluri interne + (opțional) workflow aprobare comenzi.
- Ofertare/negociere: solicitare ofertă → agent → aprobare director → conversie ofertă în comandă.
- Impersonare controlată agent (plasare comenzi în numele clientului, trasabil).
- Mapare cod produs client ↔ cod intern.

### Ticketing & notificări
- Tichete client ↔ suport/agent (status, istoric mesaje).
- Notificări: comenzi, plăți, blocaj credit, oferte; canale: in-app + email (SMS/push opțional).

---

## Arhitectură & module

Proiectul este organizat ca aplicație Laravel (monolit modular / API-first), cu:
- **API** pentru front-office și admin
- **Jobs/Queues** pentru sincronizări ERP, import/export comenzi, statusuri curieri, reconciliere plăți
- **Events/Listeners** pentru notificări și audit
- **Policy/Permissions** pentru RBAC

Module recomandate (domain boundaries):
- Auth & Accounts
- Customers & Customer Groups
- Catalog (Products, Categories, Brands, Attributes, Media, Documents)
- Pricing (Price Lists, Contract Prices, Promotions Engine)
- Cart & Checkout
- Orders, Payments, Invoicing (inclusiv OP)
- Shipping (Rules Engine, AWB, Tracking)
- ERP Integration (sync bidirecțional)
- Ticketing & Notifications
- Audit Log

---

## Integrare ERP / Curieri / Plăți

### ERP (sync bidirecțional)
- Produse (inclusiv variante), prețuri, stocuri, liste de preț, comenzi, facturi, solduri, condiții comerciale.

### Curieri
- Generare AWB din platformă
- Tracking în cont client și (unde e cazul) în cont agent
- Sincronizare status livrare când API curier permite

### Plăți & încasări
- Card (redirect 3DS, tokenizare unde e permis)
- OP (proformă + status „plată în așteptare” + reminder)
- Încasări gestionate de agenți/directori: CHS/BO/CEC, cu validări interne + push în ERP.

---

## Setup local

### Cerințe
- PHP (versiune compatibilă cu Laravel din proiect)
- Composer
- Node.js + npm
- MySQL/MariaDB (recomandat)
- (Opțional) Redis pentru queue/cache

> Repo conține `composer.json`, `package.json`, `vite.config.js`, `phpunit.xml`. :contentReference[oaicite:2]{index=2}

### Instalare
```bash
git clone <repo>
cd b2b-ecommerce-backend

composer install
npm install
cp .env.example .env
php artisan key:generate


Configurare .env

Completează cel puțin:

APP_URL

DB_* (host, port, database, username, password)

MAIL_* (pentru resetare parolă, notificări)

QUEUE_CONNECTION (ex: database / redis)

Integrări (ERP/Curier/Plăți): chei, endpoint-uri, webhook secrets (când sunt introduse)

Bază de date

Ai două opțiuni:

Migrations/seeders
php artisan migrate
php artisan db:seed

Securitate & audit

Rate limiting la login + protecție brute-force

RBAC strict pe admin/agent/marketer/operator

Audit log pentru schimbări critice (prețuri, promoții, condiții comerciale, sold/credit)

Trasabilitate pentru impersonare agent (cine, când, pentru cine)