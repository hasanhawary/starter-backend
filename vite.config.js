# 🚀 Laravel Starter Project

Welcome to the **Laravel Starter Project** — a fully featured, production-ready ecosystem designed to accelerate the development of modern Laravel applications.
    This starter is not just a simple boilerplate — it is a **complete development platform** powered by a suite of custom-made packages that provide standardized CRUD generation, file management, exporting, permissions, reporting, metadata fetching, and more.

    Whether you're building a small API or a large enterprise system, this starter gives you:

* **A unified architecture**
* **Powerful development tools**
* **Ready-made modules**
* **Real-time features using Reverb**
* **Clean, maintainable code structures**
* **Fast CRUD generation using Dynamic CLI**

---

# 💠 Powered by a Modular Package Ecosystem

This starter project depends on the following official packages, integrated deeply into its architecture:

    ### 📦 Core Packages

| Package                | Description                                                                                                 | Link                                                                       |
| ---------------------- | ----------------------------------------------------------------------------------------------------------- | -------------------------------------------------------------------------- |
| **Dynamic CLI**        | Auto-generate full CRUD modules (Models, Controllers, Requests, Resources, Filters) with unified standards. | [Packagist](https://packagist.org/packages/hasanhawary/dynamic-cli)        |
| **Export Builder**     | Build queued, chunked, and optimized exports (Excel/CSV) with signed URLs and notification support.         | [Packagist](https://packagist.org/packages/hasanhawary/export-builder)     |
| **Lookup Manager**     | Centralized retrieval of enums, metadata, and lookup tables with multi-table support and caching.           | [Packagist](https://packagist.org/packages/hasanhawary/lookup-manager)     |
| **Media Manager**      | Manage file uploads (including chunked uploads), safe delete, signed URLs, and organized storage layers.    | [Packagist](https://packagist.org/packages/hasanhawary/media-manager)      |
| **Permission Manager** | Full role and permission system with CRUD support, syncing, and API-ready middleware integration.           | [Packagist](https://packagist.org/packages/hasanhawary/permission-manager) |
| **Report Builder**     | Build dynamic, filterable, sortable reports with API formatting and export integration.                     | [Packagist](https://packagist.org/packages/hasanhawary/report-builder)     |

---

# 🧩 Package Details

### 🔧 **1. Dynamic CLI**

A powerful command-line tool for instantly generating complete modules:

    * Controllers, Models, Requests, Resources
* Automatic Filters for search & sorting
                        * Soft delete support
* Relationship-aware generation
* Modules follow the same architecture as the starter

---

### 📤 **2. Export Builder**

An enterprise export layer with:

* Chunked & queued exports
* Signed download URLs
* Progress tracking
* Notifications after export completion
* Excel & CSV output formats

---

### 🔍 **3. Lookup Manager**

A unified lookup and metadata system:

    * Fetch enums as arrays
* Retrieve multiple tables in a single request
* Caching layer
* Used by settings, forms, dropdowns, etc.

---

### 🗂 **4. Media Manager**

Advanced file upload system:

    * Chunked uploads for large files
* Safe delete system (soft delete for media)
    * Signed file URLs
* Auto folder organization
* Versioning support

---

### 🔐 **5. Permission Manager**

Role & Permission management with:

* CRUD for roles
           * CRUD for permissions
                      * Sync permissions to roles
* Route protection using permission middleware

---

### 📊 **6. Report Builder**

Create dynamic reports with:

* Custom filters
* Sorting
* Pagination
* Export integration
* API-ready response structure

---

# 🚀 Full Feature List

### 1. **User Management**

* Registration, login, logout
* Role-based access control
* Permission-level security
* Profile management

### 2. **Password Recovery**

* Secure email-based reset
* Token verification

### 3. **Dynamic Reporting System**

* Advanced reports
* Dynamic filtering and sorting
* Integration with Report Builder

### 4. **Export Functionality**

* Excel and CSV support
* Powered by Laravel Excel & Export Builder
* Queue + chunking support
* Notification on export completion

### 5. **Notifications**

* Real-time notifications
* Email notifications
* SMS-ready integration

### 6. **Real-Time Updates**

* Full WebSocket support using **Reverb**
* Live updates for dashboards, data tables, exports

### 7. **Dynamic Settings Management** ⭐

* **Multi-brand support** with isolated settings per brand
* **Template-based configuration** - single source of truth for all field definitions
* **Hierarchical organization** - settings grouped by section (general, properties, notifications, theme, config, mail_templates)
* **Type-aware fields** - text, textarea, select, switchbox, imageUploader
* **Multi-language support** - settings can be translated (ar/en)
* **Database-driven** - all settings stored in database for runtime modification
* **Environment-based brand selection** - switch brands via `DEFAULT_BRAND` env variable
* **Automatic seeding** - seeders handle template + brand-specific values

**Key Features:**
    - Settings organized by: `general.info`, `general.contact`, `general.social`, `properties`, `notifications`, `theme.colors`, `theme.font`, `mail_templates.otp`, `mail_templates.theme`, `config.mail`, `config.sms`, `config.ldap`, `config.reverb`
    - Each setting includes: key, group, value, type, label, placeholder, is_multi_lang
    - Brand-specific values override template defaults
    - Missing brand files gracefully fallback to template with null values

### 8. **Multi-Brand System** ⭐

* **Brand Configuration** - Each brand has isolated settings and configuration
    * **Template-Based Architecture** - `config/brands/template.php` defines all available settings
    * **Brand-Specific Values** - `database/seeders/brands/{brand}.php` contains brand-specific data
    * **Supported Brands** - wakeb, jervis, elhawary (easily extensible)
* **Dynamic Brand Switching** - Change active brand via `DEFAULT_BRAND` environment variable
    * **Seeder Integration** - Automatic database seeding with brand values

### 9. **OTP (One-Time Password) Cycle** ⭐

* **Secure OTP Generation** - Cryptographically secure random codes
    * **Configurable OTP Types** - Email OTP, SMS OTP, Push OTP
    * **OTP Lifecycle Management**:
    - Generate OTP with expiration time
    - Send via configured channel (email/SMS)
    - Verify OTP with attempt limiting
    - Auto-expire after configured duration
    - Prevent replay attacks
    * **Rate Limiting** - Prevent brute force attacks with attempt tracking
    * **Multi-Channel Support** - Email, SMS, Push notifications
    * **Audit Trail** - Log all OTP activities for security
                                                   * **Customizable Templates** - Brand-specific OTP email templates with styling
    * **Integration Points**:
    - User registration verification
    - Password reset confirmation
    - Two-factor authentication
    - Sensitive action verification

    **OTP Configuration:**
    - Expiration time (default: 10 minutes)
    - Max attempts (default: 5)
    - Resend cooldown (default: 60 seconds)
    - OTP length (default: 6 digits)
    - Channel selection (email, SMS, push)

### 10. **Help / Metadata System**

* Fetch enums
    * Fetch multiple tables in one request
    * Used to power dropdowns & dynamic forms

### 11. **Chunked File Upload**

* Supports large uploads
    * Resume-compatible
    * Uses Media Manager

    ---

# 🛠 Installation

### Requirements

    * PHP >= 8.0
    * Composer
    * Node.js & npm/yarn
    * MySQL
    * Redis (recommended)

### Steps

        ```bash
git clone <repository-url>
cd <project-directory>
composer install
cp .env.example .env
php artisan key:generate
php artisan migrate --seed
php artisan serve
php artisan queue:work
php artisan reverb:start
```

    ---

# 📑 Postman Collection

    Included at:

        ```
Base path "postman.json"
```

### How to Use

    1. Import into Postman
    2. Set base URL in environment
    3. Test API endpoints using the ready-made requests

    ---

# 📂 Project Structure

        ```
app
├── Console
├── Enum
├── Events
├── Exceptions
├── Filters
│   ├── Example
│   ├── Global
│   ├── Setting
│   └── User
├── Helpers
├── Http
│   ├── Controllers
│   ├── Middleware
│   ├── Requests
│   └── Resources
├── Jobs
```

    This structure ensures clean separation of concerns and scalability.

    ---

# 🌐 Real-Time Integration with Reverb

        This starter includes native support for **Laravel Reverb** to enable real-time communication such as:

        * Live dashboards
    * Real-time notifications
    * Export progress updates
    * Live activity feeds
    * Any WebSocket-based UI component

## 🔧 Required Environment Variables

    Add the following to your `.env` file:

        ```env
REVERB_APP_ID=1080194
REVERB_APP_KEY=bae3160ce349d284eace
REVERB_APP_SECRET=976e5b64127df42af8b6

REVERB_SCHEME=http
REVERB_HOST="127.0.0.1"
REVERB_PORT=9000

REVERB_SERVER_HOST="0.0.0.0"
REVERB_SERVER_PORT=9000

REVERB_SSL_LOCAL_CERT=""
REVERB_SSL_LOCAL_PK=""
```

## ✔ Enable Real-Time Mode

        ```env
REALTIME=true
```

## ▶️ Start Reverb Server

        ```bash
php artisan reverb:start
```

## ▶️ Run Queue Worker (required)

        ```bash
php artisan queue:work
```

    ---

# 📖 Documentation

    This starter includes a **built-in documentation system** to help you understand the project structure, packages, and features.

### Preview Documentation

    You can preview the docs locally by running:

        ```bash
npm run docs
```

    Open your browser at:

        ```
http://localhost:5173
```

    The docs cover the starter’s architecture, packages, real-time features, and usage examples.

    ---

## 📝 License

    This project is open-source and available under the **MIT License**.

    ---

## 📧 Support

    For questions or support, feel free to reach out to the project maintainer, **Hassan Elhawary**, via email at [hasanhawary1@gmail.com](mailto:hasanhawary1@gmail.com). Alternatively, you can open an issue directly on the repository for further assistance.

