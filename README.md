# 🚀 Laravel Multi-Tenant Backend

> 🏢 **Enterprise SaaS Backend** - Production-ready multi-tenant architecture with complete tenant isolation, central management, and subscription system.

A **production-ready, fully-featured Laravel multi-tenant backend** with enterprise-grade architecture, built on a modular package ecosystem. This starter provides everything you need to build scalable multi-tenant SaaS applications with authentication, authorization, real-time features, and advanced data management.

**Project Type:** Multi-Tenant | **Architecture:** Distributed | **User Types:** Central Admin + Tenant Admin + Tenant User | **Use Case:** SaaS Platforms, Multi-Tenant Applications, White-Label Solutions

---

## ✨ Key Highlights

- **🏢 Multi-Tenant Architecture** - Complete tenant isolation with central and tenant databases
- **🔐 Complete RBAC System** - Role-based access control with granular permissions
- **🔑 Multi-Auth Support** - Email/password, LDAP, and OTP authentication
- **💳 Subscription Management** - Plans, features, subscriptions, and usage tracking
- **⚡ Real-Time Features** - WebSocket support via Laravel Reverb
- **⚙️ Dynamic Settings** - Multi-brand, template-based configuration system per tenant
- **🔍 Advanced Filtering** - Built-in query filters for search, sort, and pagination
- **📝 Activity Auditing** - Complete audit trail for all model changes
- **📁 File Management** - Chunked uploads with tenant-isolated storage
- **🌍 Multi-Language** - Full i18n support (English/Arabic)
- **🔌 API-Ready** - RESTful API with Sanctum token authentication

---

## 🏗️ Architecture Overview

### Three-Tier User System

This backend implements a **three-tier user hierarchy** with complete separation and isolation:

```
┌──────────────────────────────────────────────────────────────────┐
│                    Central Database                              │
├──────────────────────────────────────────────────────────────────┤
│  ┌────────────────────────────────────────────────────────────┐  │
│  │              Central Admin Management                      │  │
│  │  • Manage Tenants  • Manage Plans  • Track Subscriptions   │  │
│  │  • Global Settings  • Central Activity Logs                │  │
│  └────────────────────────────────────────────────────────────┘  │
└──────────────────────────────────────────────────────────────────┘
                              ↓
        ┌─────────────────────────────────────────┐
        │   Automatic Tenant Detection            │
        │   (Domain/Subdomain/X-Tenant-Id)        │
        └─────────────────────────────────────────┘
                              ↓
┌──────────────────────────────────────────────────────────────────┐
│              Tenant Database (Per Tenant)                        │
├──────────────────────────────────────────────────────────────────┤
│  ┌──────────────────────┐      ┌──────────────────────┐          │
│  │  Tenant Admin Users  │      │   Tenant Users       │          │
│  │ • Manage Users       │      │ • Limited Access     │          │
│  │ • Manage Roles       │      │ • Manage Profile     │          │
│  │ • Manage Settings    │      │ • View Data          │          │
│  │ • View Reports       │      │ • Manage Own Data    │          │
│  └──────────────────────┘      └──────────────────────┘          │
│  ┌──────────────────────────────────────────────────────────┐    │
│  │    Tenant-Scoped Resources (Users, Roles, Permissions,   │    │
│  │    Countries, Settings, Notifications, Activity Logs)    │    │
│  └──────────────────────────────────────────────────────────┘    │
└──────────────────────────────────────────────────────────────────┘
```

### API Organization

```
/api
├── /central/*                  # Central management (SaaS admin)
│   ├── /login                  # Central admin login
│   ├── /admins                 # Manage central admins
│   ├── /tenants                # Manage tenants
│   ├── /plans                  # Manage subscription plans
│   ├── /subscriptions          # Manage subscriptions
│   ├── /settings               # Central settings
│   └── /activity-logs          # Central audit trail
│
└── /*                          # Tenant-scoped endpoints (auto-isolated)
    ├── /login                  # Tenant user login
    ├── /me                     # User profile
    ├── /users                  # Manage tenant users
    ├── /roles                  # Manage tenant roles
    ├── /settings               # Tenant settings
    ├── /activity-logs          # Tenant audit trail
    └── /notifications          # Tenant notifications
```

---

## 🧩 Powered by Custom Packages

This starter is built on a suite of **production-grade custom packages**:

| Package | Purpose | Link |
|---------|---------|------|
| **Dynamic CLI** | Auto-generate CRUD modules with unified standards | [View](https://packagist.org/packages/hasanhawary/dynamic-cli) |
| **Export Builder** | Queued, chunked exports (Excel/CSV) with signed URLs | [View](https://packagist.org/packages/hasanhawary/export-builder) |
| **Lookup Manager** | Centralized enum and metadata retrieval with caching | [View](https://packagist.org/packages/hasanhawary/lookup-manager) |
| **Media Manager** | File uploads, chunked transfers, signed URLs, versioning | [View](https://packagist.org/packages/hasanhawary/media-manager) |
| **Permission Manager** | Complete RBAC with role/permission syncing & middleware | [View](https://packagist.org/packages/hasanhawary/permission-manager) |
| **Report Builder** | Dynamic, filterable reports with export integration | [View](https://packagist.org/packages/hasanhawary/report-builder) |
| **Spatie Multitenancy** | Complete multi-tenant support with database switching | [View](https://packagist.org/packages/spatie/laravel-multitenancy) |

**All packages by Hassan Elhawary:** [View All Packages](https://packagist.org/users/hasanhawary/packages/)

---

## 🎯 Core Features

### 🏢 Multi-Tenant Architecture
- **🏢 Central Database** - Shared tenant registry and global settings
- **🏢 Tenant Databases** - Isolated data per tenant with automatic switching
- **🏢 Tenant Middleware** - Automatic tenant detection and context switching
- **🏢 Tenant Scoping** - All queries automatically scoped to current tenant
- **🏢 Tenant Isolation** - Complete data separation between tenants
- **🏢 Subdomain/Domain Routing** - Support for tenant.app.com or custom domains
- **🏢 Automatic Database Switching** - Seamless tenant database selection

### 💳 Subscription Management
- **Plan Management** - Create and manage subscription plans
- **Plan Features** - Define features per plan (usage limits, capabilities)
- **Subscription Lifecycle** - Create, renew, cancel, change status
- **Usage Tracking** - Track feature usage per subscription
- **Subscription Validation** - Automatic validation on every request
- **Billing Cycles** - Support for monthly, yearly, and custom cycles
- **Subscription Status** - Active, expired, cancelled, pending states
- **Automatic Expiration** - Auto-expire subscriptions when due

### 👨‍💼 Central Admin Management
- **Central Admin CRUD** - Create, read, update, delete central admins
- **Central Admin Profiles** - Avatar management and profile customization
- **Central Admin Roles & Permissions** - Granular access control
- **Central Admin Activity** - Audit trail for all central admin actions
- **Central Admin Credentials** - Notification on admin creation/update

### 🏢 Tenant Management
- **Tenant CRUD** - Create, read, update, delete tenants
- **Tenant Domains** - Map custom domains to tenants
- **Tenant Status** - Active/inactive tenant management
- **Tenant Activation** - Control tenant access
- **Tenant Restoration** - Soft delete with restoration capability
- **Tenant Isolation** - Complete data separation per tenant

### 👨‍💼 Tenant Admin Management
- **Tenant Admin CRUD** - Create, read, update, delete tenant admins
- **Tenant Admin Profiles** - Avatar management and profile customization
- **Tenant Admin Roles & Permissions** - Granular access control within tenant
- **Tenant Admin Activity** - Audit trail for all tenant admin actions
- **🏢 Tenant-Scoped** - Admins isolated per tenant

### 👤 Tenant User Management
- **User CRUD** - Create, read, update, delete tenant users
- **User Profiles** - Avatar management and profile customization
- **User Roles** - Role assignment within tenant
- **User Activity** - Track user actions and changes
- **User Filtering** - Search by name, email, phone, status
- **User Restoration** - Soft delete with restoration capability
- **🏢 Tenant-Scoped** - Users isolated per tenant

### 🔐 Authentication & Authorization
- **Dual Auth System** - Separate authentication for Central and Tenant
- **Email/Password Auth** - Standard email and password authentication
- **LDAP Integration** - Active Directory & OpenLDAP support with fallback
- **OTP System** - Multi-purpose OTP for login, password reset, email verification
- **Session Management** - Multiple active sessions per user with token tracking
- **Role-Based Access** - Granular permission control with dynamic operations
- **🏢 Tenant-Scoped Auth** - Authentication isolated per tenant

### ⚙️ Settings & Configuration
- **Template-Based System** - Single source of truth for all settings
- **Multi-Brand Support** - Isolated settings per brand (wakeb, jervis, elhawary)
- **Type-Aware Fields** - Text, textarea, image uploader, checkbox, radio, select
- **Multi-Language Values** - Translatable settings (ar/en) with JSON storage
- **Environment Sync** - Sync settings to .env file for runtime configuration
- **Caching Layer** - Automatic cache invalidation on updates
- **Public/Private Settings** - Distinguish between public and environment settings
- **Settings Grouping** - Hierarchical organization (general, properties, notifications, theme, config, mail_templates)
- **🏢 Tenant-Specific Settings** - Each tenant can customize their settings independently
- **🏢 Central Settings** - Global settings for all tenants

### 📊 Reporting & Analytics
- Dynamic report generation with custom filters
- HighChart integration for visualizations
- Export to Excel/CSV with chunked processing
- Signed download URLs for secure access
- Real-time export progress tracking
- Filterable, sortable reports with pagination
- Report caching for performance
- **🏢 Tenant-Scoped Reporting** - Reports isolated per tenant
- **Central Reporting** - Cross-tenant analytics for SaaS admin

### 🔔 Notifications
- **Multi-Channel** - Email, SMS, in-app, real-time (WebSocket via Reverb)
- **Notification Queue** - Background processing for reliability
- **Status Tracking** - Mark as open/read with pagination
- **Real-Time Push** - Instant updates via Reverb WebSocket
- **Localized Templates** - Multi-language notification messages
- **Notification Count** - Unread notification counter
- **Notification Events** - Trigger on user actions (creation, updates, exports)
- **🏢 Tenant-Scoped Notifications** - Isolated per tenant with tenant-specific channels
- **Central Notifications** - System-wide notifications for central admins

### 📝 Activity Logging
- Automatic audit trail for all model changes
- Track who changed what, when, and what changed
- Filterable activity history with date range support
- Permission-based access control (read-log permission)
- Detailed change tracking with before/after values
- Activity log export capabilities
- **🏢 Tenant-Scoped Activity Logs** - Audit trail isolated per tenant
- **Central Activity Logs** - Central admin actions tracked separately

### 📁 File Management
- **Chunked Uploads** - Resume-compatible large file transfers (Media Manager)
- **Media Manager** - Organized file storage with versioning
- **Signed URLs** - Secure file access links with expiration
- **Avatar Management** - User profile pictures with auto-resize
- **Safe Deletion** - Soft delete with restoration
- **File Organization** - Auto-folder organization by type/date
- **Supported Formats** - jpg, jpeg, png, gif, pdf, docx
- **🏢 Tenant-Isolated Storage** - Separate storage per tenant with automatic scoping

### 🌍 Data Entry
- Country master data with phone codes
- Translatable fields (name, nationality)
- Flag image uploads (Media Manager integration)
- Active/inactive status management
- Soft delete with restoration
- Force delete for permanent removal
- Country filtering and sorting
- **🏢 Tenant-Scoped Countries** - Each tenant can manage their own countries

### 💡 Help & Metadata
- Enum lookup for all system enums
- Model metadata for form generation
- Config lookup for whitelisted values
- Multi-table fetch in single request
- Caching for performance optimization
- Used by frontend for dynamic forms and dropdowns

### 🎨 Captcha System
- Random 5-character captcha generation
- 10-minute expiry with cache storage
- Token-based verification
- Protection against automated attacks

### 🌐 Real-Time Features
- **Reverb Integration** - Native WebSocket support
- **Live Dashboards** - Real-time data updates
- **Real-Time Notifications** - Instant push notifications
- **Export Progress** - Live export status tracking
- **Activity Feeds** - Real-time activity log updates
- **Presence Tracking** - Know who's online
- **🏢 Tenant-Isolated Channels** - WebSocket channels per tenant with automatic isolation

### 🔒 Security Features
- **Password Hashing** - Bcrypt with automatic salting
- **API Authentication** - Sanctum token-based auth with expiration
- **Rate Limiting** - Login attempt throttling with IP tracking
- **Soft Deletes** - Logical deletion with restoration capability
- **Activity Auditing** - Complete change tracking for compliance
- **Permission-Based Access** - Fine-grained control at route level
- **LDAP Integration** - Enterprise directory support
- **OTP Verification** - Multi-factor authentication support
- **Data Encryption** - Optional field encryption for sensitive data
- **CORS Support** - Configurable cross-origin requests
- **🏢 Tenant Isolation** - Complete data separation and security with automatic tenant context
- **🏢 Subscription Validation** - Automatic subscription check on every request
- **🏢 Tenant Detection** - Automatic tenant detection and validation

### 🌍 Multi-Language Support
Full internationalization with English (en) and Arabic (ar):
- Translatable models (Roles, Permissions, Countries, Settings)
- Language middleware for auto-detection from Accept-Language header
- Fallback locale support (English as fallback)
- Localized API responses and error messages
- Translatable enums and metadata

---

## 🚀 Quick Start

### ⚡ One-Command Installation

```bash
php artisan app:install
```

This command automatically handles everything:
- ✅ Copies `.env.example` to `.env`
- ✅ Generates application key
- ✅ Creates MySQL databases (central + tenant)
- ✅ Runs all migrations (central and tenant)
- ✅ Seeds initial data (roles, permissions, countries, settings, plans)
- ✅ Links storage directory
- ✅ Sets up Laravel Modules (if installed)
- ✅ Displays sample user credentials

**With custom options:**

```bash
php artisan app:install \
  --brand=jervis \
  --db-host=localhost \
  --db-port=3306 \
  --db-database=my_app_db \
  --db-driver=mysql \
  --db-username=root \
  --db-password=secret
```

**Sample credentials after installation:**
```
Central Admin Email: root@{brand_name}.com
Central Admin Password: 123456

Tenant Admin Email: admin@tenant.com
Tenant Admin Password: 123456

Tenant User Email: user@tenant.com
Tenant User Password: 123456
```

### Manual Installation

```bash
git clone <repository-url>
cd <project-directory>
composer install
npm install

cp .env.example .env
php artisan key:generate

php artisan migrate
php artisan db:seed

php artisan serve
php artisan queue:work
php artisan reverb:start
```

### Add New Tenant

```bash
php artisan tenant:create \
  --name="Acme Corp" \
  --domain="acme.app.com" \
  --database="acme_db"

php artisan migrate --tenants
```

---

## 📮 Postman Collection

**File:** `postman.json` (in project root)

### Setup Environment
- `url` = `http://localhost:8000/api`
- `central_token` = (your central admin API token)
- `tenant_token` = (your tenant user API token)
- `tenant_id` = (your tenant ID)

### Collection Structure
- **Central Auth** - Central admin login, OTP, Password Reset, Logout
- **Central Management** - Tenants, Admins, Plans, Subscriptions
- **Central Settings** - Central settings management
- **Central Activity Logs** - Central audit trail
- **Tenant Auth** - Tenant user login, OTP, Password Reset, Logout
- **Tenant Users** - Tenant user CRUD operations
- **Tenant Roles & Permissions** - Role and permission management
- **Tenant Settings** - Tenant settings management
- **Tenant Activity Logs** - Tenant audit trail
- **Help & Metadata** - Enum and model lookups
- **Countries** - Country data management

---

## 📚 API Overview

### Central Routes (`/api/central/*`)

```http
POST   /central/login                    # Central admin login
POST   /central/reset-password           # Password reset
POST   /central/send-otp                 # Send OTP
POST   /central/verify-otp               # Verify OTP
POST   /central/logout                   # Logout

GET    /central/admins                   # List central admins
POST   /central/admins                   # Create admin
GET    /central/admins/{id}              # Get admin
PUT    /central/admins/{id}              # Update admin
PUT    /central/admins/toggle-active     # Toggle status
DELETE /central/admins/delete            # Soft delete
POST   /central/admins/restore           # Restore
DELETE /central/admins/force-delete      # Permanent delete

GET    /central/tenants                  # List tenants
POST   /central/tenants                  # Create tenant
GET    /central/tenants/{id}             # Get tenant
PUT    /central/tenants/{id}             # Update tenant
PUT    /central/tenants/toggle-active    # Toggle status
DELETE /central/tenants/delete           # Soft delete
POST   /central/tenants/restore          # Restore
DELETE /central/tenants/force-delete     # Permanent delete

GET    /central/plans                    # List plans
POST   /central/plans                    # Create plan
PUT    /central/plans/{id}               # Update plan
DELETE /central/plans/{id}               # Delete plan

GET    /central/subscriptions            # List subscriptions
POST   /central/subscriptions            # Create subscription
POST   /central/subscriptions/{id}/change-status  # Change status
POST   /central/subscriptions/{id}/cancel         # Cancel subscription
POST   /central/subscriptions/{id}/renew          # Renew subscription

GET    /central/settings                 # Get settings
PUT    /central/settings                 # Update settings
POST   /central/send-test-mail           # Test email

GET    /central/activity-logs            # List activity
GET    /central/activity-logs/{id}       # Get activity detail
```

### Tenant Routes (`/api/*`)

```http
POST   /login                            # Tenant user login
POST   /reset-password                   # Password reset
POST   /send-otp                         # Send OTP
POST   /verify-otp                       # Verify OTP
POST   /logout                           # Logout

GET    /users                            # List users
POST   /users                            # Create user
GET    /users/{id}                       # Get user
PUT    /users/{id}                       # Update user
PUT    /users/toggle-active              # Toggle status
DELETE /users/delete                     # Soft delete
POST   /users/restore                    # Restore
DELETE /users/force-delete               # Permanent delete

GET    /roles                            # List roles
POST   /roles                            # Create role
PUT    /roles/{id}                       # Update role
DELETE /roles/{id}                       # Delete role
GET    /permissions                      # List permissions

GET    /settings                         # Get settings
PUT    /settings                         # Update settings
POST   /send-test-mail                   # Test email

GET    /activity-logs                    # List activity
GET    /activity-logs/{id}               # Get activity detail

GET    /countries                        # List countries
POST   /countries                        # Create country
PUT    /countries/{id}                   # Update country
DELETE /countries/delete                 # Soft delete
POST   /countries/restore                # Restore
DELETE /countries/force-delete           # Permanent delete

GET    /notifications                    # Get notifications
PUT    /notifications                    # Mark as read/open

GET    /help-enums                       # Get enum values
GET    /help-models                      # Get model metadata
```

---

## 🌐 Real-Time Features

```bash
php artisan reverb:start
php artisan queue:work
```

Supports:
- Live dashboards
- Real-time notifications
- Export progress updates
- Live activity feeds
- WebSocket-based UI components
- Tenant-isolated channels

---

## 📊 Database Schema

### Central Database Tables
- **admins** - Central admin accounts
- **tenants** - Tenant registry
- **domains** - Tenant domain mappings
- **plans** - Subscription plans
- **plan_features** - Plan features
- **subscriptions** - Tenant subscriptions
- **subscription_usage** - Usage tracking
- **countries** - Master data
- **settings** - Central settings
- **notifications** - Central notifications
- **activity_log** - Central audit trail
- **personal_access_tokens** - Central API tokens

### Tenant Database Tables (Per Tenant)
- **users** - Tenant user accounts
- **roles** - Tenant role definitions
- **permissions** - Tenant permission definitions
- **model_has_roles** - User-role relationships
- **model_has_permissions** - User-permission relationships
- **role_has_permissions** - Role-permission relationships
- **countries** - Tenant master data
- **settings** - Tenant settings
- **notifications** - Tenant notifications
- **activity_log** - Tenant audit trail
- **personal_access_tokens** - Tenant API tokens

---

## 📦 Project Structure

```
starter-backend-tenant/
├── app/
│   ├── Console/                 # Artisan commands
│   ├── Enum/                    # System enums
│   ├── Events/                  # Event classes
│   ├── Exceptions/              # Custom exceptions
│   ├── Filters/                 # Query filters
│   ├── Guards/                  # Custom guards
│   ├── Helpers/                 # Helper functions
│   ├── Http/
│   │   ├── Controllers/API/
│   │   │   ├── Central/         # Central management controllers
│   │   │   ├── Tenant/          # Tenant-scoped controllers
│   │   │   └── Shared/          # Shared controllers
│   │   ├── Middleware/          # HTTP middleware
│   │   ├── Requests/            # Form requests
│   │   └── Resources/           # API resources
│   ├── Jobs/                    # Queue jobs
│   ├── Mail/                    # Mailable classes
│   ├── Models/
│   │   ├── Central/             # Central models
│   │   └── Tenant/              # Tenant models
│   ├── Notifications/           # Notification classes
│   ├── Scopes/                  # Query scopes
│   ├── Services/                # Business logic
│   └── Traits/                  # Reusable traits
├── config/                      # Configuration files
├── database/
│   ├── migrations/
│   │   ├── central/             # Central migrations
│   │   └── tenant/              # Tenant migrations
│   ├── seeders/
│   │   ├── Central/             # Central seeders
│   │   └── Tenant/              # Tenant seeders
│   └── factories/               # Model factories
├── routes/
│   ├── central.php              # Central routes
│   ├── tenant.php               # Tenant routes
│   └── api.php                  # Route registration
├── storage/                     # File storage
├── tests/                       # Test files
└── public/                      # Public assets
```

---

## 📖 Documentation

```bash
npm run docs
```

Starts local server at `http://localhost:5173` with:
- Complete API reference
- Feature deep-dives
- Configuration guides
- Code examples
- Best practices
- Troubleshooting
- Multi-tenant architecture guide
- Subscription management guide
- Tenant isolation guide

---

## 🤝 Contributing

1. Fork the repository
2. Create a feature branch
3. Make your changes
4. Submit a pull request

---

## 📝 License

This project is open-source and available under the **MIT License**.

---

## 📧 Support

For questions or support:
- **Email:** [hasanhawary1@gmail.com](mailto:hasanhawary1@gmail.com)
- **Issues:** Open an issue on the repository

---

## 🎉 What's Included

✅ Complete multi-tenant architecture  
✅ Central and tenant database separation  
✅ Automatic tenant detection and switching  
✅ Subscription management system  
✅ Complete authentication system (email, LDAP, OTP)  
✅ Role-based access control with permissions  
✅ Multi-brand settings management  
✅ Real-time notifications via WebSocket  
✅ Activity auditing and logging  
✅ Advanced reporting and exports  
✅ File management with chunked uploads  
✅ Multi-language support (en/ar)  
✅ API rate limiting and security  
✅ Background job processing  
✅ Comprehensive error handling  
✅ Production-ready code structure  

---

**Built with ❤️ for developers who value clean, scalable code.**
