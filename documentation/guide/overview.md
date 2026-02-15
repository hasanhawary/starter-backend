---
title: Overview
---

# What this system is

**Admin Dashboard Kit for Dashboard** is a production-ready Laravel backend designed **exclusively for admin dashboards and internal management systems**. It combines Laravel's conventions with a clean architecture, local packages, and tooling to speed development, enforce consistency, and make features pluggable and testable.

## High-level architecture

- **Admin-only structure**: The application is designed exclusively for admin users with a unified, flat API structure at `/api/*`. All endpoints require admin authentication.
- **Core application**: The `app/` directory holds shared business logic (Services, Models, Policies, Observers, Events, Jobs). `routes/` contains API routes for admin operations.
- **Persistence**: Migrations and seeders live in `database/`. The app uses a single database with admin-only access control.
- **Background processing**: Queue jobs and scheduled tasks are implemented as Jobs under `app/Jobs` and run via `php artisan queue:work` and scheduler commands.
- **Event-driven pieces**: Events and listeners decouple side effects (see `app/Events` and `app/Listeners`) to make the system extensible.

## Key workflows & developer tools

- **Installation**: `php artisan app:install` automates initial setup — copies `.env.example`, generates app key, creates DB, runs migrations and seeders, links storage, and displays sample admin credentials.
- **Admin setup**: The system automatically creates admin roles and permissions during seeding.
- **Scaffolding & CLI**: Use the Dynamic CLI (`php artisan dynamic:crud Name`) and other custom commands; list available commands with `php artisan list`.

## Integrations & services

- **Caching, queues, and sessions** can be configured through `config/*` and environment variables in `.env`.
- **Email and notifications** are handled via Laravel Mail / Notifications (see `app/Mail` and `app/Notifications`).

## Conventions & best practices

- Keep shared logic in `app/` and feature-specific code inside modules or packages.
- Write migrations and seeders with idempotency in mind so `migrate:fresh` and `db:seed` are safe during development.
- Use Events/Jobs for side effects and long-running tasks to keep controllers thin.
