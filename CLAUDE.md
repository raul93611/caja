# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Project

**Caja** — Financial management web app for a Bolivian grocery store. PHP + MySQL, no frameworks. All UI text is in Spanish. Currency is Bolivian boliviano (Bs).

## Running the app

Requires a PHP server (XAMPP, Laragon, or similar) pointing to this directory and a MySQL database.

```bash
# Import the schema (first time only)
mysql -u root -p caja < caja.sql

# If the DB already exists and you need to add a new column, run the ALTER TABLE
# statements at the bottom of caja.sql manually in phpMyAdmin or MySQL CLI.
```

Configure the database connection in `.env` (copy from `.env.example`):
```
DB_HOST=localhost
DB_NAME=caja
DB_USER=root
DB_PASS=
```

Test credentials: `admin@caja.com` / `admin123`

## Architecture

### Request flow

Every protected page follows this pattern:
1. `require_once 'config.php'` — loads `.env`, defines DB constants, registers helpers
2. `requireLogin()` — checks `$_SESSION['usuario_id']`, redirects to `login.php` if absent
3. PHP logic + DB queries using `getDB()` (singleton PDO)
4. `require '_layout.php'` — opens `<html>`, sidebar, topbar, bottom nav
5. Page-specific HTML
6. `require '_layout_end.php'` — closes Bootstrap JS tags and `</body></html>`

### Layout system

`_layout.php` / `_layout_end.php` are bookend includes, not a full template engine. Every page opens its own `<style>` blocks if it needs page-specific CSS on top of `assets/css/app.css`.

The layout renders **two navs**: a fixed sidebar (`#sidebar`) for desktop (≥768px) and a bottom tab bar (`#bottom-nav`) for mobile. Both are driven by the same `$nav` array in `_layout.php`. The active item is determined by `basename($_SERVER['PHP_SELF'])`.

### AJAX pattern

`api.php` is the single AJAX endpoint. It requires an active session and dispatches on `$_POST['action']`. All responses are `{ ok: bool, data?: ..., error?: string }`.

Client-side, `assets/js/app.js` exports:
- `apiRequest(action, data)` — wraps `fetch('api.php', ...)` returning parsed JSON
- `showToast(message, type)` — Bootstrap toast notification
- `confirmDelete(id, desc)` / `openEditModal(id)` — wire the delete/edit buttons in `transacciones.php`

The edit modal (`#modalEdit`) renders its form entirely from JS (`renderEditForm`) after fetching transaction data via `apiRequest('obtener', {id})`.

### Global helpers (`config.php`)

| Function | Purpose |
|---|---|
| `getDB()` | Singleton PDO connection (utf8mb4, exceptions on, emulated prepares off) |
| `requireLogin()` | Session guard — call at the top of every protected page |
| `h($val)` | `htmlspecialchars` wrapper — use on all user-supplied output |
| `moneda($float)` | Formats as `Bs 1,234.56` |

### Database schema key points

- `categorias.tipo` — `ENUM('ingreso','egreso')` — categories are pre-seeded; the UI never creates them
- `transacciones.cantidad` — `DECIMAL(10,3)` supports fractional units (e.g. `0.750` kg)
- `transacciones.detalles` — `TEXT NULL` — optional free-text description
- All queries use PDO prepared statements; `usuario_id` is always taken from `$_SESSION`, never from user input

### Front-end stack

- **Bootstrap 5.3** (CDN) — layout grid, modals, collapse, pagination
- **Bootstrap Icons 1.11** (CDN) — icons via `<i class="bi bi-*">`
- **Manrope** (Google Fonts CDN) — sole typeface
- **Chart.js 4.4** (CDN, `reportes.php` only) — bar + doughnut charts
- **`assets/css/app.css`** — custom theme on top of Bootstrap (CSS variables, sidebar, KPI cards, responsive table helpers)
- **`assets/js/app.js`** — shared JS; loaded via `_layout_end.php` on every protected page

### Responsive breakpoints

- **< 576px** — `.col-mobile-hide` hides Fecha and Cantidad columns in tables
- **< 768px** — sidebar hidden off-canvas (toggled by `#btn-sidebar-toggle`), bottom nav shown, topbar shown
- **≥ 768px** — sidebar fixed at 240px, topbar and bottom nav hidden, content offset by `margin-left: var(--sidebar-w)`

## Key conventions

- **No frameworks** — pure PHP, no Composer, no npm
- **All user output** must go through `h()` to prevent XSS
- **`registro.php`** is a temporary user-creation utility — it should be deleted from the server after use; it is listed in `.gitignore`
- When adding a new column to `transacciones`, update: `caja.sql` CREATE TABLE + add an ALTER TABLE comment, `nueva-transaccion.php` form + INSERT, `api.php` `obtener` SELECT + `actualizar` UPDATE, `assets/js/app.js` `renderEditForm`, and `transacciones.php` SELECT query
