# 🚀 Laravel Admin Dashboard Backend

> 📊 **Admin Dashboard Backend** - Production-ready backend exclusively for admin dashboards and internal management systems.

A **production-ready, fully-featured Laravel backend** designed exclusively for building admin dashboards and internal management systems. This starter provides everything you need to build scalable admin APIs with authentication, authorization, real-time features, and advanced data management.

**Project Type:** Single-Instance | **Architecture:** Monolithic | **User Type:** Admin Only | **Use Case:** Admin Dashboards, Management Systems, Internal Tools

---

## ✨ Key Highlights

- **🔐 Complete RBAC System** - Role-based access control with granular permissions for admin users
- **🔑 Multi-Auth Support** - Email/password, LDAP, and OTP authentication for admins
- **⚡ Real-Time Features** - WebSocket support via Laravel Reverb for live updates
- **⚙️ Dynamic Settings** - Multi-brand, template-based configuration system
- **🔍 Advanced Filtering** - Built-in query filters for search, sort, and pagination
- **📝 Activity Auditing** - Complete audit trail for all admin actions and model changes
- **📁 File Management** - Chunked uploads with media manager integration
- **🔔 Background Jobs** - Queue-based email and SMS notifications
- **🌍 Multi-Language** - Full i18n support (English/Arabic)
- **🔌 API-Ready** - RESTful API with Sanctum token authentication

---

## 🏗️ Architecture Overview

### Single Admin-Only System

This backend is designed **exclusively for admin users** with a unified, flat API structure:

```
┌──────────────────────────────────────────────────────────────────┐
│                    Single Database                               │
├──────────────────────────────────────────────────────────────────┤
│                                                                  │
│  ┌────────────────────────────────────────────────────────────┐  │
│  │              Admin Dashboard Backend                       │  │
│  │  • User Management                                         │  │
│  │  • Role & Permission Management                            │  │
│  │  • Settings & Configuration                                │  │
│  │  • Reports & Analytics                                     │  │
│  │  • Activity Logs & Audit Trail                             │  │
│  │  • Data Entry Management                                   │  │
│  │  • Notifications & Real-Time Updates                       │  │
│  └────────────────────────────────────────────────────────────┘  │
│                                                                  │
└──────────────────────────────────────────────────────────────────┘
```

### API Organization (Flat Structure)

All endpoints are at the root `/api` level with no namespace separation.

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

**All packages by Hassan Elhawary:** [View All Packages](https://packagist.org/users/hasanhawary/packages/)

---

## 🎯 Core Features

### 🔐 Authentication & Authorization
- **Login System** - Email/password with rate limiting (5 attempts, 180s lockout)
- **LDAP Integration** - Active Directory & OpenLDAP support with fallback to default auth
- **OTP System** - Multi-purpose OTP for login, password reset, email verification
  - Configurable length (default 6), type (numeric/alpha/alphanumeric)
  - Expiry time (default 10 minutes), max attempts (5) with progressive locking
  - Resend delay (30s), IP-based rate limiting
- **Session Management** - Multiple active sessions per user with token tracking
- **Role-Based Access** - Granular permission control with dynamic operations (create, read, update, delete, view-all, view-own, restore, force-delete, toggle-active)

### 👥 User Management
- Complete CRUD with soft delete & restoration
- User profiles with avatar management (Media Manager integration)
- Role & permission assignment with syncing
- LDAP user sync with auto-provisioning
- Activity tracking on all user changes
- User filtering by name, email, phone, status
- Pagination with configurable page size (default 15, max 100)
- User credentials notification on creation/update

### ⚙️ Settings & Configuration
- **Template-Based System** - Single source of truth for all settings
- **Multi-Brand Support** - Isolated settings per brand (wakeb, elhawary)
- **Type-Aware Fields** - Text, textarea, image uploader, checkbox, radio, select
- **Multi-Language Values** - Translatable settings (ar/en) with JSON storage
- **Environment Sync** - Sync settings to .env file for runtime configuration
- **Caching Layer** - Automatic cache invalidation on updates
- **Public/Private Settings** - Distinguish between public and environment settings
- **Settings Grouping** - Hierarchical organization (general, properties, notifications, theme, config, mail_templates)

### 📊 Reporting & Analytics
- Dynamic report generation with custom filters
- HighChart integration for visualizations
- Export to Excel/CSV with chunked processing
- Signed download URLs for secure access
- Real-time export progress tracking
- Filterable, sortable reports with pagination
- Report caching for performance

### 🔔 Notifications
- **Multi-Channel** - Email, SMS, in-app, real-time (WebSocket via Reverb)
- **Notification Queue** - Background processing for reliability
- **Status Tracking** - Mark as open/read with pagination
- **Real-Time Push** - Instant updates via Reverb WebSocket
- **Localized Templates** - Multi-language notification messages
- **Notification Count** - Unread notification counter
- **Notification Events** - Trigger on user actions (creation, updates, exports)

### 📝 Activity Logging
- Automatic audit trail for all model changes
- Track who changed what, when, and what changed
- Filterable activity history with date range support
- Permission-based access control (read-log permission)
- Activity log export capabilities

### 📁 File Management
- **Chunked Uploads** - Resume-compatible large file transfers (Media Manager)
- **Media Manager** - Organized file storage with versioning
- **Signed URLs** - Secure file access links with expiration
- **Avatar Management** - User profile pictures with auto-resize
- **Safe Deletion** - Soft delete with restoration
- **File Organization** - Auto-folder organization by type/date
- **Supported Formats** - jpg, jpeg, png, gif, pdf, docx

### 🌍 Data Entry
- Country master data with phone codes
- Translatable fields (name, nationality)
- Flag image uploads (Media Manager integration)
- Active/inactive status management
- Soft delete with restoration
- Force delete for permanent removal
- Country filtering and sorting

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
- ✅ Creates MySQL database with UTF8MB4 encoding
- ✅ Runs all migrations
- ✅ Seeds initial data (roles, permissions, countries, settings)
- ✅ Links storage directory
- ✅ Sets up Laravel Modules (if installed)
- ✅ Displays sample user credentials

**With custom options:**

```bash
php artisan app:install \
  --brand=wakeb \
  --db-host=localhost \
  --db-port=3306 \
  --db-database=my_app_db \
  --db-driver=mysql \
  --db-username=root \
  --db-password=secret
```

**Sample credentials after installation:**
```
Admin Email: root@{brand_name}.com
Admin Password: 123456
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

### Switch Brands

```bash
DEFAULT_BRAND=wakeb
php artisan db:seed --class=SettingTableSeeder
```

---

## 📮 Postman Collection

**File:** `postman.json` (in project root)

### Setup Environment
- `url` = `http://localhost:8000/api`
- `token` = (your API token after login)

### Collection Structure
- **Auth** - Login, OTP, Password Reset, Logout
- **Users** - User CRUD operations
- **Roles & Permissions** - Role and permission management
- **Settings** - Application settings
- **Notifications** - Notification management
- **Activity Logs** - Activity tracking
- **Reports & Exports** - Reporting and data export
- **Help & Metadata** - Enum and model lookups
- **Countries** - Country data management

---

## 📚 API Overview

### Public Routes (Guest Accessible)

```http
GET    /captcha                  # Generate captcha
POST   /captcha/verify           # Verify captcha

POST   /login                    # Admin login
POST   /reset-password           # Password reset
POST   /send-otp                 # Send OTP
POST   /check-otp                # Check OTP
POST   /verify-otp               # Verify OTP
```

### Protected Routes (Authenticated)

#### Profile Management
```http
GET    /me                       # Get user profile
POST   /update-profile           # Update profile
POST   /destroy-avatar           # Remove avatar
POST   /logout                   # Logout
```

#### Permissions & Roles
```http
GET    /permissions              # List permissions

GET    /roles                    # List roles
POST   /roles                    # Create role
GET    /roles/{id}               # Get role
PUT    /roles/{id}               # Update role
DELETE /roles/delete             # Soft delete role
```

#### User Management
```http
GET    /users                    # List users
POST   /users                    # Create user
GET    /users/{id}               # Get user
PUT    /users/{id}               # Update user
PUT    /users/toggle-active      # Toggle user status
DELETE /users/delete             # Soft delete user
POST   /users/restore            # Restore user
DELETE /users/force-delete       # Permanent delete user
```

#### Data Entry
```http
GET    /countries                # List countries
POST   /countries                # Create country
GET    /countries/{id}           # Get country
PUT    /countries/{id}           # Update country
PUT    /countries/toggle-active  # Toggle country status
DELETE /countries/delete         # Soft delete country
POST   /countries/restore        # Restore country
DELETE /countries/force-delete   # Permanent delete country
```

#### Settings & Configuration
```http
GET    /settings                 # Get all settings
PUT    /settings                 # Update settings
POST   /send-test-mail           # Test email configuration
```

#### Reports & Exports
```http
GET    /report                   # Generate report
GET    /export                   # Export data
```

#### Activity & Notifications
```http
GET    /activity-logs            # List activity logs
GET    /activity-logs/{id}       # Get activity detail

GET    /notifications            # List notifications
PUT    /notifications            # Mark notifications as read/open
```

#### Help & Metadata
```http
GET    /help-enums               # Get enum values
GET    /help-models              # Get model metadata
GET    /help-configs             # Get config values
```

#### File Management
```http
POST   /chunk-file               # Upload chunked file
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

---

## 📊 Database Schema

### Core Tables
- **users** - User accounts with LDAP support
- **roles** - Role definitions with translations
- **permissions** - Permission definitions with translations
- **countries** - Country master data
- **settings** - Application settings
- **notifications** - User notifications
- **activity_log** - Audit trail

### Relationship Tables
- **model_has_roles** - User-role relationships
- **model_has_permissions** - User-permission relationships
- **role_has_permissions** - Role-permission relationships

---

## 📦 Project Structure

```
starter-backend-dashboard/
├── app/
│   ├── Console/                 # Artisan commands
│   ├── Enum/                    # System enums
│   ├── Events/                  # Event classes
│   ├── Exceptions/              # Custom exceptions
│   ├── Filters/                 # Query filters
│   ├── Guards/                  # Custom guards
│   ├── Helpers/                 # Helper functions
│   ├── Http/
│   │   ├── Controllers/API/     # API controllers
│   │   ├── Middleware/          # HTTP middleware
│   │   ├── Requests/            # Form requests
│   │   └── Resources/           # API resources
│   ├── Jobs/                    # Queue jobs
│   ├── Mail/                    # Mailable classes
│   ├── Models/                  # Eloquent models
│   ├── Notifications/           # Notification classes
│   ├── Scopes/                  # Query scopes
│   ├── Services/                # Business logic
│   └── Traits/                  # Reusable traits
├── config/                      # Configuration files
├── database/
│   ├── migrations/              # Database migrations
│   ├── seeders/                 # Database seeders
│   └── factories/               # Model factories
├── routes/                      # API routes
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
