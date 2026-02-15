---
title: Overview
---

# What this system is

**Landing Dashboard Kit** is a production-ready Laravel backend designed for applications with **dual-user architecture** — serving both administrators and public users. It combines Laravel's conventions with a clean architecture, local packages, and tooling to speed development, enforce consistency, and make features pluggable and testable.

## High-level architecture

- **Dual-user structure**: The application separates Admin and Landing user types with isolated endpoints (`/api/admin/*` and `/api/*`). Each user type has distinct permissions and access levels.
- **Core application**: The `app/` directory holds shared business logic (Services, Models, Policies, Observers, Events, Jobs). `routes/` separates admin and landing routes.
- **Persistence**: Migrations and seeders live in `database/`. The app uses a single database with role-based access control to separate admin and user data.
- **Background processing**: Queue jobs and scheduled tasks are implemented as Jobs under `app/Jobs` and run via `php artisan queue:work` and scheduler commands.
- **Event-driven pieces**: Events and listeners decouple side effects (see `app/Events` and `app/Listeners`) to make the system extensible.

## Key workflows & developer tools

- **Installation**: `php artisan app:install` automates initial setup — copies `.env.example`, generates app key, creates DB, runs migrations and seeders, links storage, and displays sample credentials.
- **Dual-user setup**: The system automatically creates both Admin and Landing user roles with appropriate permissions during seeding.
- **Scaffolding & CLI**: Use the Dynamic CLI (`php artisan dynamic:crud Name`) and other custom commands; list available commands with `php artisan list`.

## Integrations & services

- **Caching, queues, and sessions** can be configured through `config/*` and environment variables in `.env`.
- **Email and notifications** are handled via Laravel Mail / Notifications (see `app/Mail` and `app/Notifications`).

## Conventions & best practices

- Keep shared logic in `app/` and feature-specific code inside modules or packages.
- Write migrations and seeders with idempotency in mind so `migrate:fresh` and `db:seed` are safe during development.
- Use Events/Jobs for side effects and long-running tasks to keep controllers thin.
