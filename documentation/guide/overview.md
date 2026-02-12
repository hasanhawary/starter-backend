---
title: Overview
---

# What this system is

**Starter Backend** is a Laravel-based, modular backend scaffold designed for building medium-to-large web APIs and admin systems. It is intended as the base starter for each new project — suitable for any scale or field (from prototypes to large enterprise applications). It combines Laravel's conventions with a modular architecture (using `nwidart/laravel-modules`), local packages, and tooling to speed development, enforce consistency, and make features pluggable and testable.

## High-level architecture

- **Modular structure**: Features are organized into modules (found in `Modules/` when using the Modules package and in `packages/` for local packages). Each module can contain its own controllers, models, migrations, routes, views, seeds, and tests to keep concerns isolated.
- **Core application**: The `app/` directory holds shared business logic (Services, Models, Policies, Observers, Events, Jobs). `routes/` separates API and web routes.
- **Persistence**: Migrations and seeders live in `database/`. The app supports multiple DB connections via `config/database.php` and uses Laravel's DB and Eloquent layers for data access.
- **Background processing**: Queue jobs and scheduled tasks are implemented as Jobs under `app/Jobs` and run via `php artisan queue:work` and scheduler commands.
- **Event-driven pieces**: Events and listeners decouple side effects (see `app/Events` and `app/Listeners`) to make the system extensible.

## Key workflows & developer tools

- **Installation**: `php artisan app:install` automates initial setup — copies `.env.example`, generates app key, creates DB, runs migrations and seeders, links storage, and activates modules.
- **Module setup**: `php artisan app:setup-module --name=ModuleName` lets you enable and run module-specific setup when modules exist.
- **Scaffolding & CLI**: Use the Dynamic CLI (`php artisan cli:crud Name`) and other custom commands; list available commands with `php artisan list`.

## Integrations & services

- **Caching, queues, and sessions** can be configured through `config/*` and environment variables in `.env`.
- **Email and notifications** are handled via Laravel Mail / Notifications (see `app/Mail` and `app/Notifications`).

## Conventions & best practices

- Keep shared logic in `app/` and feature-specific code inside modules or packages.
- Write migrations and seeders with idempotency in mind so `migrate:fresh` and `db:seed` are safe during development.
- Use Events/Jobs for side effects and long-running tasks to keep controllers thin.
