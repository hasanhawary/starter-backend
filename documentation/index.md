---
layout: home

title: Landing Dashboard Kit
titleTemplate: Production-Ready Laravel Starter Project

hero:
  name: Landing Dashboard Kit
  text: Complete Landing Page Platform
  tagline: Production-ready Laravel starter with dual-user system, CRUD generation, file management, and real-time features
  image:
    src: /logo.svg
    alt: Landing Dashboard Kit
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
    details: Clone, install, and start building in minutes. Get a working API immediately with pre-configured database and authentication.
    link: /guide/installation
  
  - icon: 👥
    title: Dual-User System
    details: Support for both admin and regular users with separate authentication flows, permissions, and user management.
    link: /guide/authentication
  
  - icon: ⚡
    title: CRUD Generation
    details: Auto-generate complete CRUD modules with models, controllers, validations, filters, and API endpoints.
    link: /guide/tools/dynamic-cli
  
  - icon: 🔧
    title: 6 Custom Packages
    details: Dynamic CLI, Export Builder, Lookup Manager, Media Manager, Permission Manager, and Report Builder for rapid development.
    link: /guide/tools/
  
  - icon: 📊
    title: Dynamic Metadata
    details: Fetch model structure and enum values to build completely dynamic UIs without hardcoding any data.
    link: /guide/tools/lookup-manager
  
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

## Core Features

### Dual-User System
- **Admin Users** with full system access and management capabilities
- **Regular Users** with limited permissions and self-service features
- **Separate Authentication** flows for different user types
- **Role-Based Access Control** with granular permissions
- **User Gender Enum** for user profile customization

[Learn more about Dual-User System →](/guide/authentication)

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
Type-safe enums for ActiveTypeEnum, OtpTypeEnum, and UserGenderEnum with helper methods for validation and UI generation.

[View Enums →](/guide/features/enums)

### Mail Classes
Pre-built mail classes for sending emails with templates, attachments, and queue support.

[Learn Mail Classes →](/guide/features/mail-classes)

## Configuration

### Brand Configuration
Customize your application's branding including logo, colors, company name, and other visual elements.

[View Brand Configuration →](/guide/features/brand-configuration)

### Helper Functions
Utility functions for common operations like formatting, validation, and data transformation.

[Learn Helper Functions →](/guide/helpers)

## Database & Models

### Database Models
Complete model structure with relationships, scopes, casts, and attributes for User, Role, Permission, Setting, Notification, and more.

[View Database Models →](/guide/database-models)

### Middleware
Request middleware for language detection, authentication, and request processing.

[Learn Middleware →](/guide/middleware)

### Error Handling
Comprehensive exception handling with custom exceptions for common scenarios and proper HTTP responses.

[Explore Error Handling →](/guide/error-handling)

## Getting Started

1. **[Install the project](/guide/installation)** - Clone and set up your development environment
2. **[Quick Start Guide](/guide/quick-start)** - Create your first CRUD module
3. **[Architecture Overview](/guide/architecture)** - Understand the project structure
4. **[API Reference](/guide/api-reference)** - Explore all available endpoints

## Deployment

Ready to go live? Check out our deployment guide for production setup, environment configuration, and best practices.

[View Deployment Guide →](/guide/deployment)

## Troubleshooting

Encountering issues? Our troubleshooting guide covers common problems and solutions.

[View Troubleshooting →](/guide/troubleshooting)
