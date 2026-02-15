---
title: Guide Overview
---

# Admin Dashboard Kit Guide

Welcome to the complete documentation for **Admin Dashboard Kit** — a production-ready Laravel starter project with unified architecture, powerful development tools, and complete package ecosystem.

## What This Is

A fully-featured Laravel starter project that includes:

- Complete authentication system (Login, LDAP, OTP-based password recovery)
- Role-based access control (RBAC) with permissions
- User management with profiles and activity logging
- Six custom packages for common development tasks
- Real-time features with Reverb WebSocket server
- Background job processing with Laravel Queues
- Dynamic CRUD generation with consistent architecture
- Complete API documentation with examples

## Getting Started

### New to the Project?

1. **[Installation](/guide/installation)** — Set up locally (5 minutes)
2. **[Quick Start](/guide/quick-start)** — First API calls (5 minutes)
3. **[Architecture](/guide/architecture)** — Understand the structure

### Ready to Build?

4. **[Authentication](/guide/authentication)** — User login and auth flows
6. **[Help & Lookups](/guide/tools/lookup-manager)** — Dynamic metadata for UIs

## Documentation Structure

### 📚 Guides
- **[Installation](/guide/installation)** — Setup and environment configuration
- **[Quick Start](/guide/quick-start)** — Your first API requests
- **[Architecture](/guide/architecture)** — Project structure and patterns
- **[Authentication](/guide/authentication)** — Login, password reset, OTP
- **[Help & Lookups](/guide/tools/lookup-manager)** — Dynamic model metadata and enums
- **[Notifications](/guide/features/notifications)** — Email, SMS, real-time
- **[Activity Logging](/guide/features/activity-logging)** — Audit trail
- **[Authorization](/guide/authorization)** — Policies and permissions
- **[Settings](/guide/features/settings)** — Configuration management
- **[Media Manager](/guide/tools/media-manager)** — File uploads & management
- **[Media Manager](/guide/tools/media-manager)** — File uploads & management

### 🛠️ Tools & Packages
- **[Tools Overview](/guide/tools/)** — All six packages at a glance
- **[Dynamic CLI](/guide/tools/dynamic-cli)** — Auto-generate CRUD modules
- **[Lookup Manager](/guide/tools/lookup-manager)** — Model metadata & enums
- **[Export Builder](/guide/tools/export-builder)** — Data exports (Excel/CSV)
- **[Media Manager](/guide/tools/media-manager)** — File uploads & management
- **[Permission Manager](/guide/tools/permission-manager)** — Roles & permissions
- **[Report Builder](/guide/tools/report-builder)** — Dynamic reports

### 📖 API Reference
- **[API Reference](/guide/api-reference)** — Response format and endpoints summary

## Key Features

### Authentication
- Token-based API (Sanctum)
- LDAP/Active Directory support
- OTP-based password recovery
- Rate limiting on login attempts

### User Management
- Complete CRUD operations
- Role assignment
- Profile management
- Avatar uploads
- Last login tracking
- Soft delete support

### Authorization
- Role-based access control (RBAC)
- Permission checks in middleware
- Policy-based authorization
- Gate-based checks

### Development Tools
- **Dynamic CLI** — Generate CRUD in seconds
- **Lookup Manager** — Dynamic model metadata for UIs
- **Export Builder** — Async data exports
- **Media Manager** — Chunked file uploads
- **Permission Manager** — Role/permission CRUD
- **Report Builder** — Filterable reports

### Real-Time & Async
- Reverb WebSocket server
- Queued jobs
- Event broadcasting
- Email notifications

### Data Features
- Soft deletes (trash/restore)
- Activity logging (audit trail)
- Timestamps (created_at, updated_at)
- Relationships and eager loading
- Advanced filtering and sorting
- Pagination support

## Technology Stack

- **Laravel 11** — PHP framework
- **Sanctum** — API authentication
- **Spatie Permission** — Role/permission system
- **Reverb** — WebSocket server
- **Laravel Queue** — Background jobs
- **Activity Log** — Audit trail

## Community & Support

- 📖 [Full Documentation](/guide/installation)
- 🔗 [API Reference](/guide/api-reference)
- 💬 [Discussions](https://git.wakeb.tech/WEB-A/starter-backend)

## Next Steps

👉 Start with **[Installation](/guide/installation)** to get the project running locally.

Then follow **[Quick Start](/guide/quick-start)** for your first API requests.

Happy coding! 🚀
