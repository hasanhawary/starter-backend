---
layout: home

title: Multi-Tenant Dashboard Kit
titleTemplate: Production-Ready Multi-Tenant Laravel Starter Project

hero:
  name: Multi-Tenant Dashboard Kit
  text: Complete Multi-Tenant Platform
  tagline: Production-ready Laravel starter with multi-tenancy, subscriptions, CRUD generation, file management, and real-time features
  image:
    src: /logo.svg
    alt: Multi-Tenant Dashboard Kit
  actions:
    - theme: brand
      text: Get Started
      link: /guide/installation
    - theme: alt
      text: Quick Start
      link: /guide/quick-start
    - theme: alt
      text: API Reference
      link: /guide/api-reference

features:
  - icon: 🚀
    title: Fast Setup
    details: Clone, install, and start building in minutes. Get a working multi-tenant API immediately with pre-configured database.
    link: /guide/installation
  
  - icon: 🏢
    title: Multi-Tenancy
    details: Complete multi-tenant architecture with isolated databases, tenant-aware models, and automatic tenant context switching.
    link: /guide/multitenancy/
  
  - icon: 💳
    title: Subscription System
    details: Built-in subscription management with multiple plans, billing cycles, and subscription status tracking.
    link: /guide/subscriptions/
  
  - icon: ⚡
    title: CRUD Generation
    details: Auto-generate complete CRUD modules with models, controllers, validations, filters, and API endpoints.
    link: /guide/tools/dynamic-cli
  
  - icon: 🔧
    title: 6 Custom Packages
    details: Dynamic CLI, Export Builder, Lookup Manager, Media Manager, Permission Manager, and Report Builder for rapid development.
    link: /guide/tools/
  
  - icon: 📤
    title: File Management
    details: Chunked uploads, safe delete, signed URLs, version tracking, and media organization all built-in.
    link: /guide/tools/media-manager
  
  - icon: 🔄
    title: Real-Time Features
    details: WebSocket server (Reverb) for instant notifications, live updates, and real-time data synchronization.
    link: /guide/features/reverb
  
  - icon: 🎯
    title: Best Practices
    details: Clean architecture, permission checks, validation, error handling, activity logging, and comprehensive documentation.
    link: /guide/architecture

---

## Multi-Tenancy

### Tenant Management
- **Isolated Databases** for each tenant with automatic provisioning
- **Tenant Status Tracking** (Pending, Provisioning, Ready, Failed)
- **Automatic Context Switching** based on request domain or header
- **Tenant-Aware Models** with automatic scoping
- **Database Migrations** per tenant for schema management

[Learn Multi-Tenancy →](/guide/multitenancy/)

### Tenant-Aware Models
- **Automatic Tenant Scoping** on all queries
- **Tenant Relationships** for data isolation
- **Tenant-Specific Attributes** and configurations
- **Cross-Tenant Safety** with built-in guards

[View Tenant Models →](/guide/multitenancy/models)

### Tenant Provisioning
- **Automated Setup** for new tenants
- **Database Creation** and migration
- **Initial Configuration** and seeding
- **Error Handling** and rollback on failure

[Explore Tenant Management →](/guide/multitenancy/tenants)

## Subscription System

### Subscription Management
- **Multiple Plans** with different features and pricing
- **Billing Cycles** (monthly, yearly, custom)
- **Subscription Status** tracking (Active, Expired, Cancelled)
- **Automatic Renewal** and expiration handling
- **Plan Upgrades/Downgrades** with prorated billing

[Learn Subscriptions →](/guide/subscriptions/)

### Billing Features
- **Invoice Generation** for each billing cycle
- **Payment Processing** integration ready
- **Usage Tracking** for metered billing
- **Refund Handling** and credit management

## Core Features

### Real-Time Capabilities
- **WebSocket Server** powered by Reverb
- **Instant Notifications** to connected clients
- **Live Data Updates** without page refresh
- **Presence Tracking** for user availability

[Explore Real-Time Features →](/guide/features/reverb)

### Notification System
- **Multi-Channel Delivery** (database, email, SMS, real-time)
- **Notification Templates** for consistent messaging
- **User Preferences** for notification control
- **Activity-Based Triggers** for automated notifications

[View Notification System →](/guide/features/notifications)

### Activity Logging
- **Automatic Change Tracking** on all models
- **User Attribution** for every change
- **Detailed Audit Trail** with before/after values
- **Searchable History** for compliance and debugging

[Learn about Activity Logging →](/guide/features/activity-logging)

### Settings Management
- **Global Settings** for application configuration
- **Tenant-Specific Settings** for customization
- **User-Specific Settings** for personalization
- **Settings Groups** for organization
- **Type-Safe Values** with automatic casting

[Explore Settings Management →](/guide/features/settings)

## Development Tools

### Dynamic CLI
Auto-generate complete CRUD modules with a single command. Includes models, migrations, controllers, requests, resources, filters, and API routes.

[Learn Dynamic CLI →](/guide/tools/dynamic-cli)

### Export Builder
Create powerful data exports in multiple formats (Excel, CSV, PDF) with filtering, sorting, and custom formatting.

[Explore Export Builder →](/guide/tools/export-builder)

### Lookup Manager
Fetch dynamic metadata about your models including structure, enums, and relationships for building dynamic UIs.

[View Lookup Manager →](/guide/tools/lookup-manager)

### Media Manager
Handle file uploads with chunking, versioning, signed URLs, and safe deletion. Perfect for large files and media management.

[Learn Media Manager →](/guide/tools/media-manager)

### Permission Manager
Manage roles and permissions with a flexible, database-driven system. Supports resource-level permissions and policies.

[Explore Permission Manager →](/guide/tools/permission-manager)

### Report Builder
Generate comprehensive reports with custom queries, formatting, and export options for business intelligence.

[View Report Builder →](/guide/tools/report-builder)

## Code Organization

### Services & Business Logic
Organized service classes for authentication, OTP handling, password resets, throttling, settings, and notifications.

[View Services →](/guide/features/services)

### Filters & Query Scopes
Reusable query filters for common operations like filtering by active status, name search, and ordering.

[Learn Filters & Scopes →](/guide/features/filters-scopes)

### Custom Validation Rules
7 custom validation rules including strong password validation, uniqueness checks, file size validation, and translatable field validation.

[View Custom Rules →](/guide/features/custom-rules)

### Useful Traits
6 reusable traits for common functionality: CreatedByObserver, ApplyNotification, LogsActivityOptions, HasDeleteMethods, HasToggleActiveMethods, and HasOrder.

[Explore Traits →](/guide/features/useful-traits)

### Enums & Constants
Type-safe enums for ActiveTypeEnum, OtpTypeEnum, TenantStatusEnum, and SubscriptionStatusEnum with helper methods.

[View Enums →](/guide/features/enums)

### Mail Classes
Pre-built mail classes for sending emails with templates, attachments, and queue support.

[Learn Mail Classes →](/guide/features/mail-classes)

## Configuration

### Multi-Tenancy Configuration
Configure tenant identification, database setup, and tenant-specific behavior.

[View Multi-Tenancy Config →](/guide/configuration/multitenancy)

### Project Settings
Configure application-wide settings and defaults.

[Learn Project Settings →](/guide/configuration/project)

### Roles Configuration
Set up roles and permissions for your multi-tenant application.

[View Roles Configuration →](/guide/configuration/roles)

### Brand Configuration
Customize your application's branding including logo, colors, company name, and other visual elements.

[View Brand Configuration →](/guide/features/brand-configuration)

## Database & Models

### Database Models
Complete model structure with relationships, scopes, casts, and attributes for User, Role, Permission, Setting, Notification, Tenant, Subscription, and more.

[View Database Models →](/guide/database-models)

### Middleware
Request middleware for language detection, authentication, tenant context, and request processing.

[Learn Middleware →](/guide/middleware)

### Error Handling
Comprehensive exception handling with custom exceptions for common scenarios and proper HTTP responses.

[Explore Error Handling →](/guide/error-handling)

## Getting Started

1. **[Install the project](/guide/installation)** - Clone and set up your development environment
2. **[Quick Start Guide](/guide/quick-start)** - Create your first CRUD module
3. **[Architecture Overview](/guide/architecture)** - Understand the project structure
4. **[Multi-Tenancy Guide](/guide/multitenancy/)** - Set up your first tenant
5. **[API Reference](/guide/api-reference)** - Explore all available endpoints

## Deployment

Ready to go live? Check out our deployment guide for production setup, environment configuration, and best practices.

[View Deployment Guide →](/guide/deployment)

## Troubleshooting

Encountering issues? Our troubleshooting guide covers common problems and solutions.

[View Troubleshooting →](/guide/troubleshooting)
