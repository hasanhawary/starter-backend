---
title: Configuration
description: Application configuration files and settings
---

# Configuration Guide

This page outlines the main configuration files and where to find common settings for the project.

## Important configuration files

- `config/app.php` — App name, timezone, locale, providers
- `config/database.php` — Database connections
- `config/mail.php` — Mailer settings
- `config/reverb.php` — Reverb (WebSocket) configuration
- `config/queue.php` — Queue drivers and retry settings
- `config/cache.php` — Cache stores and drivers

## Environment variables

Use the `.env` file for environment-specific values. Common variables include:

- `APP_ENV` — environment
- `APP_DEBUG` — debug mode
- `DB_CONNECTION`, `DB_HOST`, `DB_DATABASE`, `DB_USERNAME`, `DB_PASSWORD`
- `MAIL_MAILER`, `MAIL_HOST`, `MAIL_USERNAME`, `MAIL_PASSWORD`
- `REVERB_APP_ID`, `REVERB_APP_KEY`, `REVERB_APP_SECRET`

## See Also

- [Settings & Configuration](/guide/features/settings)
- [Installation](/guide/installation)