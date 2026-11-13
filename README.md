# Agriculture Information System

A web-based system for managing farmers, farms, crops, planting records and harvest records, with reports,
role-based access and an audit trail. Built for the AP3 midterm project at Cebu Technological University.

**Stack:** PHP 8 (PDO), PostgreSQL (Supabase or local), Bootstrap 5.3, CSS, JavaScript.

## Features
- Login with two roles: **Administrator** and **Staff**. Passwords are stored hashed.
- Farmers, Farms, Crops, Planting and Harvest: add, view, edit and delete, with search, filters, sorting and pagination.
- Dashboard with live totals from the database.
- Reports with date filters, including estimated harvest value (quantity x selling price).
- Administrators can manage accounts and read the audit trail (who changed what, and when).
- Form validation, delete confirmation, success and error messages, responsive layout.

## Run it locally

### 1. Requirements
- PHP 8.0 or newer with the `pdo_pgsql` extension (check with `php -m`, and look for `pdo_pgsql`)
- PostgreSQL 12 or newer (a local install with pgAdmin, or a free Supabase project)

### 2. Create an empty database
In pgAdmin (or Supabase), create a new, empty database. For example: `agriculture_db`.

### 3. Add your settings
1. Copy `.env.example` to a new file named `.env` in the project root.
2. Fill in your database values. For a local PostgreSQL use `DB_HOST=localhost` and `DB_SSLMODE=disable`.
3. `.env` is ignored by Git. Never upload it or share it.

### 4. Run the installer
In a terminal in the project root:

    php install.php

It asks whether to load sample data, then asks for the first administrator's username and password
(at least 8 characters), and for any other accounts you want. It creates all 15 tables, the fixed lookup values
and the accounts. It refuses to run on a database that already has tables.

Prefer to do it by hand? In pgAdmin, open the Query Tool on the empty database and run `database/schema.sql`
(and `database/sample_data.sql` if you want the sample data). Then create the first administrator with:

    php create_user.php admin "choose-a-password" Administrator

If you set up by hand, use the same database user that ran `schema.sql` (for example `postgres`) in `.env`.
The tables have row level security turned on with no policies, so a different user would see no data.

More accounts can be created later from **Admin > Accounts** after signing in.

### 5. Start the server

    php -S localhost:8000

Open http://localhost:8000 and sign in.

## Before putting it on a server
- Do not upload `.env`, `install.php`, `test_connection.php` or `create_user.php`.
- Set the database values (`DB_HOST`, `DB_PORT`, `DB_NAME`, `DB_USER`, `DB_PASSWORD`) as environment variables in the host's dashboard.
- Do not set `APP_ENV` on the host. PHP errors are hidden unless `APP_ENV` is `local`.
- The host needs PHP 8+ with the `pdo_pgsql` extension.

## Folder overview
- `index.php`, `dashboard.php`, `logout.php`: sign-in, dashboard, sign-out
- `pages/`: one file per module (farmers, farms, crops, planting, harvest, reports, users, audit)
- `includes/`: shared logic (`crud.php`, `auth.php`, `audit.php`) and page templates in `includes/views/`
- `config/`: database connection and settings loader
- `database/schema.sql`: the database structure
- `assets/`: CSS and JavaScript