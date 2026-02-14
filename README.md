# 🚀 Laravel Landing Backend

> 📄 **Dual-User Backend** - Production-ready backend for public-facing applications with separate admin and user interfaces.

A **production-ready, fully-featured Laravel backend** designed for applications that serve both administrators and public users. This starter provides everything you need to build scalable APIs with dual user types, authentication, authorization, real-time features, and advanced data management.

**Project Type:** Single-Instance | **Architecture:** Monolithic | **User Types:** Admin + Public User | **Use Case:** Landing Pages, Public APIs, Community Platforms

---

## ✨ Key Highlights

- **👨‍💼 Dual User Architecture** - Separate Admin and Landing user types with isolated endpoints
- **🔐 Complete RBAC System** - Role-based access control with granular permissions
- **🔑 Multi-Auth Support** - Email/password, LDAP, and OTP authentication
- **⚡ Real-Time Features** - WebSocket support via Laravel Reverb
- **⚙️ Dynamic Settings** - Multi-brand, template-based configuration system
- **🔍 Advanced Filtering** - Built-in query filters for search, sort, and pagination
- **📝 Activity Auditing** - Complete audit trail for all model changes
- **📁 File Management** - Chunked uploads with media manager integration
- **🌍 Multi-Language** - Full i18n support (English/Arabic)
- **🔌 API-Ready** - RESTful API with Sanctum token authentication

---

## 🏗️ Architecture Overview

### Dual-User Model

This backend implements a **two-tier user system** with complete separation of concerns:

```
┌─────────────────────────────────────────────────────────────┐
│                    Single Database                          │
├─────────────────────────────────────────────────────────────┤
│                                                             │
│  ┌──────────────────┐          ┌──────────────────┐         │
│  │   Admin Users    │          │  Landing Users   │         │
│  │  (Admins Table)  │          │  (Users Table)   │         │
│  │                  │          │                  │         │
│  │ • Full Access    │          │ • Limited Access │         │
│  │ • Manage System  │          │ • View Public    │         │
│  │ • Settings       │          │ • Manage Profile │         │
│  │ • Users/Roles    │          │ • View Countries │         │
│  └──────────────────┘          └──────────────────┘         │
│                                                             │
│  ┌──────────────────────────────────────────────────────┐   │
│  │         Shared Resources (Roles, Permissions,        │   │
│  │         Countries, Settings, Notifications)          │   │
│  └──────────────────────────────────────────────────────┘   │
│                                                             │
└─────────────────────────────────────────────────────────────┘
```

### API Organization

```
/api
├── /admin/*                    # Admin-only endpoints
│   ├── /login                  # Admin login
│   ├── /users                  # Manage users
│   ├── /admins                 # Manage admins
│   ├── /roles                  # Manage roles
│   ├── /settings               # Manage settings
│   ├── /activity-logs          # View audit trail
│   └── /report, /export        # Reports & exports
│
└── /*                          # Public user endpoints
    ├── /login                  # User login
    ├── /me                     # User profile
    ├── /settings               # View settings (read-only)
    ├── /countries              # View countries
    └── /notifications          # User notifications
```

---

## 🧩 Powered by Custom Packages

This starter is built on a suite of **production-grade custom packages** designed for enterprise Laravel applications:

| Package | Purpose | Link |
|---------|---------|------|
| **Dynamic CLI** | Auto-generate CRUD modules with unified standards | [View Package](https://packagist.org/packages/hasanhawary/dynamic-cli) |
| **Export Builder** | Queued, chunked exports (Excel/CSV) with signed URLs | [View Package](https://packagist.org/packages/hasanhawary/export-builder) |
| **Lookup Manager** | Centralized enum and metadata retrieval with caching | [View Package](https://packagist.org/packages/hasanhawary/lookup-manager) |
| **Media Manager** | File uploads, chunked transfers, signed URLs, versioning | [View Package](https://packagist.org/packages/hasanhawary/media-manager) |
| **Permission Manager** | Complete RBAC with role/permission syncing & middleware | [View Package](https://packagist.org/packages/hasanhawary/permission-manager) |
| **Report Builder** | Dynamic, filterable reports with export integration | [View Package](https://packagist.org/packages/hasanhawary/report-builder) |

**All packages by Hassan Elhawary:** [View All Packages](https://packagist.org/users/hasanhawary/packages/)

---

## 🎯 Core Features

### �‍💼 Admin User Management
- **Admin CRUD** - Create, read, update, delete admin users
- **Admin Profiles** - Avatar management and profile customization
- **Admin Roles & Permissions** - Granular access control for admins
- **Admin Activity Tracking** - Audit trail for all admin actions
- **Admin Credentials** - Notification on admin creation/update
- **Admin Filtering** - Search by name, email, phone, status
- **Admin Restoration** - Soft delete with restoration capability

### 👤 Landing User Management
- **User Registration** - Self-service user registration
- **User Profiles** - Avatar management and profile customization
- **User Roles** - Limited role assignment for landing users
- **User Activity** - Track user actions and changes
- **User Filtering** - Search by name, email, phone, status
- **User Restoration** - Soft delete with restoration capability
- **Read-Only Access** - Limited permissions for public users

### 🔐 Authentication & Authorization
- **Dual Login System** - Separate authentication for Admin and Landing users
- **Email/Password Auth** - Standard email and password authentication
- **LDAP Integration** - Active Directory & OpenLDAP support with fallback
- **OTP System** - Multi-purpose OTP for login, password reset, email verification
  - Configurable length (default 6), type (numeric/alpha/alphanumeric)
  - Expiry time (default 10 minutes), max attempts (5) with progressive locking
  - Resend delay (30s), IP-based rate limiting
- **Session Management** - Multiple active sessions per user with token tracking
- **Role-Based Access** - Granular permission control with dynamic operations

### ⚙️ Settings & Configuration
- **Template-Based System** - Single source of truth for all settings
- **Multi-Brand Support** - Isolated settings per brand (wakeb, jervis, elhawary)
- **Type-Aware Fields** - Text, textarea, image uploader, checkbox, radio, select
- **Multi-Language Values** - Translatable settings (ar/en) with JSON storage
- **Environment Sync** - Sync settings to .env file for runtime configuration
- **Caching Layer** - Automatic cache invalidation on updates
- **Public/Private Settings** - Distinguish between public and environment settings
- **Settings Grouping** - Hierarchical organization (general, properties, notifications, theme, config, mail_templates)
- **Admin-Only Updates** - Only admins can modify settings

### 📊 Reporting & Analytics
- Dynamic report generation with custom filters
- HighChart integration for visualizations
- Export to Excel/CSV with chunked processing
- Signed download URLs for secure access
- Real-time export progress tracking
- Filterable, sortable reports with pagination
- Report caching for performance
- **Admin-Only Access** - Reports restricted to admin users

### 🔔 Notifications
- **Multi-Channel** - Email, SMS, in-app, real-time (WebSocket via Reverb)
- **Notification Queue** - Background processing for reliability
- **Status Tracking** - Mark as open/read with pagination
- **Real-Time Push** - Instant updates via Reverb WebSocket
- **Localized Templates** - Multi-language notification messages
- **Notification Count** - Unread notification counter
- **Notification Events** - Trigger on user actions (creation, updates, exports)
- **User-Scoped Notifications** - Each user sees only their notifications

### 📝 Activity Logging
- Automatic audit trail for all model changes
- Track who changed what, when, and what changed
- Filterable activity history with date range support
- Permission-based access control (read-log permission)
- Detailed change tracking with before/after values
- Activity log export capabilities
- **Admin-Only Access** - Activity logs restricted to admin users

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
- **Admin-Only Management** - Only admins can modify countries

### 💡 Help & Metadata
- Enum lookup for all system enums (ActiveType, OtpType, ReportChartType, UserGender, etc.)
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
- **Route Isolation** - Admin and Landing routes completely separated

### 🌍 Multi-Language Support
Full internationalization with English (en) and Arabic (ar):
- Translatable models (Roles, Permissions, Countries, Settings)
- Language middleware for auto-detection from Accept-Language header
- Fallback locale support (English as fallback)
- Localized API responses and error messages
- Translatable enums and metadata

---

## 🚀 Quick Start

### ⚡ One-Command Installation (Recommended)

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
# Specify brand, database credentials
php artisan app:install \
  --brand=jervis \
  --db-host=localhost \
  --db-port=3306 \
  --db-database=my_app_db \
  --db-driver=mysql \
  --db-username=root \
  --db-password=secret

# Skip seeding
php artisan app:install --no-seed
```

**Sample credentials after installation:**
```
Admin Email: root@{brand_name}.com
Admin Password: 123456

Landing User Email: user@{brand_name}.com
Landing User Password: 123456
```

### Manual Installation

```bash
# Clone and setup
git clone <repository-url>
cd <project-directory>
composer install
npm install

# Environment setup
cp .env.example .env
php artisan key:generate

# Database
php artisan migrate
php artisan db:seed

# Start services
php artisan serve
php artisan queue:work
php artisan reverb:start
```

### Switch Brands

```bash
# Update .env
DEFAULT_BRAND=wakeb

# Re-seed settings
php artisan db:seed --class=SettingTableSeeder
```

---

## 📮 Postman Collection

A complete **Postman collection** is included in the project for easy API testing:

**File:** `postman.json` (in project root)

### How to Use

1. **Import into Postman:**
   - Open Postman
   - Click `Import` → Select `postman.json`
   - Collection will be imported with all endpoints

2. **Setup Environment:**
   - Create a new environment in Postman
   - Add variable: `url` = `http://localhost:8000/api`
   - Add variable: `admin_token` = (your admin API token after login)
   - Add variable: `user_token` = (your user API token after login)

3. **Test Endpoints:**
   - All endpoints are organized by feature and user type
   - Pre-configured request bodies
   - Ready-to-use examples

### Collection Structure

- **Admin Auth** - Admin login, OTP, Password Reset, Logout
- **Admin Users** - Admin user CRUD operations
- **Admin Roles & Permissions** - Role and permission management
- **Admin Settings** - Application settings management
- **Admin Activity Logs** - Activity tracking
- **Admin Reports & Exports** - Reporting and data export
- **Landing Auth** - User login, OTP, Password Reset, Logout
- **Landing Profile** - User profile management
- **Landing Notifications** - Notification management
- **Help & Metadata** - Enum and model lookups
- **Countries** - Country data management

---

## 📚 API Overview

### Admin Routes (`/api/admin/*`)

#### Authentication
```http
POST   /admin/login                    # Admin login
POST   /admin/reset-password           # Password reset
POST   /admin/send-otp                 # Send OTP
POST   /admin/verify-otp               # Verify OTP
POST   /admin/logout                   # Logout
```

#### Admin Management
```http
GET    /admin/admins                   # List admins
POST   /admin/admins                   # Create admin
GET    /admin/admins/{id}              # Get admin
PUT    /admin/admins/{id}              # Update admin
PUT    /admin/admins/toggle-active     # Toggle status
DELETE /admin/admins/delete            # Soft delete
POST   /admin/admins/restore           # Restore
DELETE /admin/admins/force-delete      # Permanent delete
```

#### User Management
```http
GET    /admin/users                    # List users
POST   /admin/users                    # Create user
GET    /admin/users/{id}               # Get user
PUT    /admin/users/{id}               # Update user
PUT    /admin/users/toggle-active      # Toggle status
DELETE /admin/users/delete             # Soft delete
POST   /admin/users/restore            # Restore
DELETE /admin/users/force-delete       # Permanent delete
```

#### Roles & Permissions
```http
GET    /admin/roles                    # List roles
POST   /admin/roles                    # Create role
PUT    /admin/roles/{id}               # Update role
DELETE /admin/roles/{id}               # Delete role
GET    /admin/permissions              # List permissions
```

#### Settings & Configuration
```http
GET    /admin/settings                 # Get all settings
PUT    /admin/settings                 # Update settings
POST   /admin/send-test-mail           # Test email configuration
```

#### Reports & Exports
```http
GET    /admin/report                   # Generate report
GET    /admin/export                   # Export data
GET    /admin/activity-logs            # List activity
GET    /admin/activity-logs/{id}       # Get activity detail
```

#### Data Management
```http
GET    /admin/countries                # List countries
POST   /admin/countries                # Create country
PUT    /admin/countries/{id}           # Update country
DELETE /admin/countries/delete         # Soft delete
POST   /admin/countries/restore        # Restore
DELETE /admin/countries/force-delete   # Permanent delete
```

### Landing Routes (`/api/*`)

#### Authentication
```http
POST   /login                          # User login
POST   /reset-password                 # Password reset
POST   /send-otp                       # Send OTP
POST   /verify-otp                     # Verify OTP
POST   /logout                         # Logout
```

#### User Profile
```http
GET    /me                             # Get user profile
POST   /update-profile                 # Update profile
POST   /destroy-avatar                 # Remove avatar
```

#### Public Data
```http
GET    /settings                       # Get settings (read-only)
GET    /countries                      # Get countries (read-only)
GET    /notifications                  # Get notifications
PUT    /notifications                  # Mark as read/open
```

#### Help & Metadata
```http
GET    /help-enums                     # Get enum values
GET    /help-models                    # Get model metadata
GET    /help-configs                   # Get config values
```

---

## 🌐 Real-Time Features

This starter includes native **Laravel Reverb** support for:
- Live dashboards
- Real-time notifications
- Export progress updates
- Live activity feeds
- WebSocket-based UI components

```bash
# Start Reverb server
php artisan reverb:start

# Run queue worker (required)
php artisan queue:work
```

---

## 📊 Database Schema

### Core Tables
- **admins** - Admin user accounts
- **users** - Landing user accounts
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
starter-backend-landing/
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
│   │   │   ├── Admin/           # Admin-only controllers
│   │   │   ├── Landing/         # Landing user controllers
│   │   │   └── Global/          # Shared controllers
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
├── routes/
│   ├── admin.php                # Admin routes
│   ├── landing.php              # Landing user routes
│   └── api.php                  # Route registration
├── storage/                     # File storage
├── tests/                       # Test files
└── public/                      # Public assets
```

---

## 📖 Documentation

To view the documentation locally, run:

```bash
npm run docs
```

This will start a local server (usually at `http://localhost:5173`) where you can explore the docs.

### 📚 **Features Covered**

The documentation includes:

* Complete API reference
* Feature deep-dives
* Configuration guides
* Code examples
* Best practices
* Troubleshooting
* Dual-user architecture guide

---

## 🤝 Contributing

Contributions are welcome! Please follow these guidelines:
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

✅ Dual-user architecture (Admin + Landing User)  
✅ Separate admin and public endpoints  
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
