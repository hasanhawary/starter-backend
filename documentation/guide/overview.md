---
title: Overview
---

# What this system is

**Multi-Tenant Dashboard Kit** is a Laravel 12 multi-tenant API backend designed for building medium-to-large web APIs and admin systems. It uses **Spatie Multitenancy** with database-per-tenant isolation, separating Central (landlord) and Tenant data. It combines Laravel's conventions with a clean architecture, local packages, and tooling to speed development, enforce consistency, and make features pluggable and testable.

## High-level architecture

- **Multi-tenant structure**: The application uses database-per-tenant isolation with Central (`database/migrations/central/`) and Tenant (`database/migrations/tenant/`) separation. Routes are split between `routes/central.php` (admin API) and `routes/tenant.php` (tenant API).
- **Core application**: The `app/` directory holds shared business logic (Services, Models, Policies, Observers, Events, Jobs). `routes/` separates API and web routes.
- **Persistence**: Migrations and seeders live in `database/`. The app supports multiple DB connections via `config/database.php` and uses Laravel's DB and Eloquent layers for data access.
- **Background processing**: Queue jobs and scheduled tasks are implemented as Jobs under `app/Jobs` and run via `php artisan queue:work` and scheduler commands.
- **Event-driven pieces**: Events and listeners decouple side effects (see `app/Events` and `app/Listeners`) to make the system extensible.

## Key workflows & developer tools

- **Installation**: Standard Laravel installation with multi-tenant database setup using `php artisan app:install` which handles central and tenant database creation, migrations, and seeding.
- **Tenant management**: Create new tenants with `php artisan tenant:create` and run tenant-specific migrations with `php artisan migrate --tenants`.
- **Scaffolding & CLI**: Use the Dynamic CLI (`php artisan dynamic:crud Name`) to auto-generate CRUD modules with consistent architecture.

## Integrations & services

- **Caching, queues, and sessions** can be configured through `config/*` and environment variables in `.env`.
- **Email and notifications** are handled via Laravel Mail / Notifications (see `app/Mail` and `app/Notifications`).

## Conventions & best practices

- Keep shared logic in `app/` and feature-specific code inside modules or packages.
- Write migrations and seeders with idempotency in mind so `migrate:fresh` and `db:seed` are safe during development.
- Use Events/Jobs for side effects and long-running tasks to keep controllers thin.
