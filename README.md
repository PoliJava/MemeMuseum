# MemeMuseum

A 4chan-style imageboard and archive dedicated to internet meme history.  
Built as a Single-Page Application: **Vue 3 + TypeScript** frontend, **Laravel 13** REST API backend, **PostgreSQL** database.

---

## Tech stack

| Layer    | Technology                                             |
| -------- | ------------------------------------------------------ |
| Frontend | Vue 3, TypeScript, Vite, Vue Router                    |
| Backend  | PHP 8.3, Laravel 13, Laravel Sanctum (SPA cookie auth) |
| Database | PostgreSQL 14+                                         |
| Testing  | Playwright (end-to-end)                                |

---

## Prerequisites

| Tool       | Version  | Verify with      |
| ---------- | -------- | ---------------- |
| PHP        | ≥ 8.3    | `php -v`         |
| Composer   | ≥ 2.x    | `composer -V`    |
| Node.js    | ≥ 18 LTS | `node -v`        |
| PostgreSQL | ≥ 14     | `psql --version` |

---

## Quick start

```bash
# ── Backend ────────────────────────────────────────────────
cd server
composer install
cp .env.example .env          # then edit DB_PASSWORD in .env
php artisan key:generate
php artisan app:setup         # creates DB, migrates, seeds, links storage
php artisan serve             # → http://localhost:8000

# ── Frontend (new terminal) ────────────────────────────────
cd client
npm install
cp .env.example .env
npm run dev                   # → http://localhost:5173
```

Open **http://localhost:5173** in your browser.

---

## Backend — detailed setup

```bash
cd server
```

### 1 · Install dependencies

```bash
composer install
```

### 2 · Environment file

```bash
cp .env.example .env
```

Set your PostgreSQL credentials in `.env`:

```dotenv
DB_CONNECTION=pgsql
DB_HOST=127.0.0.1
DB_PORT=5432
DB_DATABASE=MemeMuseum
DB_USERNAME=postgres
DB_PASSWORD=your_password
```

The remaining Sanctum / CORS values are already correct for local development:

```dotenv
APP_URL=http://localhost:8000
SESSION_DOMAIN=localhost
SANCTUM_STATEFUL_DOMAINS=localhost:5173
FRONTEND_URL=http://localhost:5173
```

### 3 · Generate application key

```bash
php artisan key:generate
```

### 4 · Database, migrations, seed, storage

```bash
php artisan app:setup
```

This single command:

- Creates the `MemeMuseum` PostgreSQL database (skipped if it already exists)
- Runs all migrations
- Seeds the six default boards — `/cls/` `/bate/` `/dank/` `/oc/` `/mdn/` `/meta/`
- Creates the `public/storage` symlink for image uploads

### 5 · Start the server

```bash
php artisan serve             # http://localhost:8000
```

---

## Frontend — detailed setup

```bash
cd client
```

### 1 · Install dependencies

```bash
npm install
```

### 2 · Environment file

```bash
cp .env.example .env
```

The default `.env` points to the Laravel dev server on `:8000`. If you run the backend on a different port, update `VITE_API_URL` accordingly.

### 3 · Start the dev server

```bash
npm run dev                   # http://localhost:5173
```

Both servers must run simultaneously. The SPA communicates with the backend exclusively via the JSON API — no page reloads.

---

## End-to-end tests (Playwright)

Both dev servers must be running before executing the tests.

```bash
# First time only — download browsers
cd client && npx playwright install --with-deps

# Run headless
npm run test:e2e

# Run with interactive Playwright UI
npm run test:e2e:ui
```

Tests register temporary users and create threads through the UI; they clean up after themselves.

---
