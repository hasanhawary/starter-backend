---
title: Installation
description: Get the Admin Dashboard Kit project up and running on your local machine
---

# Installation

This guide walks you through setting up the Admin Dashboard Kit project locally for development.

## Prerequisites

Before getting started, ensure you have the following installed on your system:

- **PHP 8.3+** — [Download](https://www.php.net/downloads)
- **Composer** — [Download](https://getcomposer.org/download/)
- **MySQL 8.0+** or **PostgreSQL 14+** — Database server
- **Git** — [Download](https://git-scm.com/)

## Clone the Repository

```bash
git clone https://git.wakeb.tech/WEB-A/starter-backend
cd starter-Backend
```
### One-Command Installation (Recommended)

Install the entire application with a single command:

```bash
php artisan app:install
```

This command automatically:
- ✅ Copies `.env.example` to `.env` (if not exists)
- ✅ Generates application key
- ✅ Creates MySQL database
- ✅ Runs all migrations
- ✅ Seeds initial data (roles, permissions, countries, settings)
- ✅ Links storage directory
- ✅ Displays sample user credentials

### Installation with Custom Options

Customize database and brand settings:

```bash
php artisan app:install \
  --brand=wakeb \
  --db-host=localhost \
  --db-port=3306 \
  --db-database=starter_backend_db \
  --db-driver=mysql \
  --db-username=root \
  --db-password=secret
```

**Available Options:**

| Option | Default | Description |
|--------|---------|-------------|
| `--brand` | (auto-generated) | Brand name for settings |
| `--db-host` | `localhost` | Database host |
| `--db-port` | `3306` | Database port |
| `--db-database` | (auto-generated) | Database name |
| `--db-driver` | `mysql` | Database driver (mysql, pgsql) |
| `--db-username` | `root` | Database username |
| `--db-password` | `root` | Database password |
| `--no-seed` | (flag) | Skip database seeding |

**Notes:**
- If `--db-database` is omitted, a random name is generated
- If `--brand` is omitted, it defaults to app name
- Use `--no-seed` to skip seeding (useful for production)
- The command creates the database automatically

# Alternatively, you can install dependencies manually:

## Additional Artisan Commands

### List All Available Commands

```bash
php artisan list
```

### Database Commands

```bash
# Run migrations
php artisan migrate

# Rollback migrations
php artisan migrate:rollback

# Fresh migration (drop all tables and re-run)
php artisan migrate:fresh

# Seed database
php artisan db:seed

# Migrate and seed
php artisan migrate --seed
```

### Cache & Storage

```bash
# Link storage directory
php artisan storage:link

# Clear all caches
php artisan cache:clear

# Clear config cache
php artisan config:clear

# Clear route cache
php artisan route:clear
```

### Development

```bash
# Start development server
php artisan serve

# Start queue worker
php artisan queue:work

# Start Reverb WebSocket server
php artisan reverb:start
```

## Install PHP Dependencies

Install all PHP dependencies using Composer:

```bash
composer install
```

## Environment Configuration

### Copy Environment File

Copy the example environment file to create your own `.env` file:

```bash
cp .env.example .env
```

### Generate Application Key

Generate a unique application key for encryption:

```bash
php artisan key:generate
```

### Configure Database

Edit the `.env` file and update your database credentials:

```env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=starter_Backend
DB_USERNAME=root
DB_PASSWORD=
```

### Configure Mail & Services

Update mail configuration for sending emails:

```env
MAIL_MAILER=smtp
MAIL_HOST=smtp.mailtrap.io
MAIL_PORT=465
MAIL_USERNAME=your_username
MAIL_PASSWORD=your_password
MAIL_FROM_ADDRESS=noreply@example.com
```

Configure any external services (e.g., LDAP, Reverb):

```env
# LDAP Configuration (optional)
LDAP_HOST=
LDAP_USERNAME=
LDAP_PASSWORD=
LDAP_PORT=
LDAP_BASE_DN=
LDAP_TIMEOUT=
LDAP_LOCAL=
LDAP_ACTIVE=

# Reverb Configuration (optional)
REVERB_APP_ID=your_app_id
REVERB_APP_KEY=your_app_key
REVERB_APP_SECRET=your_app_secret
```

## Database Setup

### Create Database

Create a database for the project:

```bash
mysql -u root -p -e "CREATE DATABASE starter_Backend;"
```

### Run Migrations

Execute database migrations:

```bash
php artisan migrate
```

### Seed Database (Optional)

Seed the database with sample data:

```bash
php artisan migrate --seed
```

This will create system tables and put the seed data into

## Build & Serve

### Start Development Server

Start the Laravel development server:

```bash
php artisan serve
```

The API will be available at your local development server.

## Verify Installation

Test that everything is working correctly:

### 1. Check API Health

```bash
curl http://starter-backend.test/api
```

### 2. Test Login Endpoint

```bash
curl -X POST http://starter-backend.test/api/login \
  -H "Content-Type: application/json" \
  -d '{
    "email": "root@wakeb.com",
    "password": "password"
  }'
```

Expected response:

```json
{
  "success": true,
  "message": "Login successful",
  "data": {
    "user": {
      "id": 1,
      "name": "Admin",
      "email": "root@wakeb.com",
      "roles": []
    },
    "token": "eyJ0eXAiOiJKV1QiLCJhbGc..."
  }
}
```

## Recommended Setup Tools

### Postman Collection

Import the <a href="/postman.json" download>Postman collection</a> to easily test all API endpoints.
<!-- Import the <a href="/postman.json" download>Postman collection</a> to easily test all API endpoints. -->

## Next Steps

After installation, proceed to:

1. [Quick Start](/guide/quick-start) — Learn the basic API workflow
2. [Architecture](/guide/architecture) — Understand the project structure
3. [Authentication](/guide/authentication) — Set up user authentication

## Additional Resources

- [Laravel Documentation](https://laravel.com/docs)
- [API Reference](/guide/api-reference)
- [Quick Start](/guide/quick-start) — Get started with examples
