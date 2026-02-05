---
title: API Reference
description: Complete list of all API endpoints with examples
---

# API Reference

This page provides a complete list of all API endpoints in the Starter Backend system.

## Base URLs

The API has two route groups:

- **Admin API**: `http://starter-backend.test/api/admin/` — Full admin functionality
- **Landing API**: `http://starter-backend.test/api/` — Public/user-facing endpoints

## Authentication

All endpoints except login, password reset, and captcha require a Bearer token:

```bash
Authorization: Bearer 1|abc123xyz789...
```

---

## Admin Routes (`/api/admin/`)

### Authentication

| Method | Endpoint | Description |
|--------|----------|-------------|
| POST | /api/admin/login | Admin login |
| POST | /api/admin/logout | Admin logout (auth required) |
| POST | /api/admin/reset-password | Reset password with token |
| POST | /api/admin/send-otp | Request OTP code |
| POST | /api/admin/check-otp | Verify OTP is valid |
| POST | /api/admin/verify-otp | Complete OTP verification |

### Captcha

| Method | Endpoint | Description |
|--------|----------|-------------|
| GET | /api/admin/captcha | Generate captcha image |
| POST | /api/admin/captcha/verify | Verify captcha response |

### Profile (Auth Required)

| Method | Endpoint | Description |
|--------|----------|-------------|
| GET | /api/admin/me | Get current admin profile |
| POST | /api/admin/update-profile | Update profile |
| POST | /api/admin/destroy-avatar | Remove avatar |

### Roles & Permissions (Auth Required)

| Method | Endpoint | Description |
|--------|----------|-------------|
| GET | /api/admin/permissions | List all permissions |
| GET | /api/admin/roles | List roles |
| POST | /api/admin/roles | Create role |
| GET | /api/admin/roles/{role} | Get role details |
| PUT | /api/admin/roles/{role} | Update role |
| DELETE | /api/admin/roles/delete | Bulk delete roles |

### Countries (Auth Required)

| Method | Endpoint | Description |
|--------|----------|-------------|
| GET | /api/admin/countries | List countries |
| POST | /api/admin/countries | Create country |
| GET | /api/admin/countries/{country} | Get country details |
| PUT | /api/admin/countries/{country} | Update country |
| DELETE | /api/admin/countries/delete | Bulk soft delete |
| DELETE | /api/admin/countries/force-delete | Bulk force delete |
| POST | /api/admin/countries/restore | Bulk restore |

### Admins Management (Auth Required)

| Method | Endpoint | Description |
|--------|----------|-------------|
| GET | /api/admin/admins | List admins |
| POST | /api/admin/admins | Create admin |
| GET | /api/admin/admins/{admin} | Get admin details |
| PUT | /api/admin/admins/{admin} | Update admin |
| DELETE | /api/admin/admins/delete | Bulk soft delete |
| DELETE | /api/admin/admins/force-delete | Bulk force delete |
| POST | /api/admin/admins/restore | Bulk restore |
| PUT | /api/admin/admins/toggle-active | Toggle active status |

### Users Management (Auth Required)

| Method | Endpoint | Description |
|--------|----------|-------------|
| GET | /api/admin/users | List users |
| POST | /api/admin/users | Create user |
| GET | /api/admin/users/{user} | Get user details |
| PUT | /api/admin/users/{user} | Update user |
| DELETE | /api/admin/users/delete | Bulk soft delete |
| DELETE | /api/admin/users/force-delete | Bulk force delete |
| POST | /api/admin/users/restore | Bulk restore |
| PUT | /api/admin/users/toggle-active | Toggle active status |

### Settings (Auth Required)

| Method | Endpoint | Description |
|--------|----------|-------------|
| GET | /api/admin/settings | Get all settings |
| PUT | /api/admin/settings/{setting} | Update setting |
| POST | /api/admin/send-test-mail | Test email configuration |

### Reports & Exports (Auth Required)

| Method | Endpoint | Description |
|--------|----------|-------------|
| GET | /api/admin/report | Generate report (with filters) |
| GET | /api/admin/export | Export data (returns file) |

### Activity Logs (Auth Required)

| Method | Endpoint | Description |
|--------|----------|-------------|
| GET | /api/admin/activity-logs | List activity logs |
| GET | /api/admin/activity-logs/{activity} | Get log details |

### Help & Lookups (Auth Required)

| Method | Endpoint | Description |
|--------|----------|-------------|
| GET | /api/admin/help-configs | Get configuration options |
| GET | /api/admin/help-models | Get model metadata |
| GET | /api/admin/help-enums | Get enum values |

### Notifications (Auth Required)

| Method | Endpoint | Description |
|--------|----------|-------------|
| GET | /api/admin/notifications | List notifications |
| PUT | /api/admin/notifications | Mark as read |

### File Upload (Auth Required)

| Method | Endpoint | Description |
|--------|----------|-------------|
| POST | /api/admin/chunk-file | Upload file chunk |

---

## Landing Routes (`/api/`)

### Authentication

| Method | Endpoint | Description |
|--------|----------|-------------|
| POST | /api/login | User login |
| POST | /api/logout | User logout (auth required) |
| POST | /api/reset-password | Reset password |
| POST | /api/send-otp | Request OTP |
| POST | /api/check-otp | Verify OTP |
| POST | /api/verify-otp | Complete verification |

### Captcha

| Method | Endpoint | Description |
|--------|----------|-------------|
| GET | /api/captcha | Generate captcha |
| POST | /api/captcha/verify | Verify captcha |

### Profile (Auth Required)

| Method | Endpoint | Description |
|--------|----------|-------------|
| GET | /api/me | Get current user profile |
| POST | /api/update-profile | Update profile |
| POST | /api/destroy-avatar | Remove avatar |

### Help & Lookups (Auth Required)

| Method | Endpoint | Description |
|--------|----------|-------------|
| GET | /api/help-configs | Get configs |
| GET | /api/help-models | Get model metadata |
| GET | /api/help-enums | Get enum values |

### Other (Auth Required)

| Method | Endpoint | Description |
|--------|----------|-------------|
| GET | /api/notifications | List notifications |
| PUT | /api/notifications | Mark as read |
| GET | /api/settings | Get public settings |
| GET | /api/countries | List countries |
| POST | /api/chunk-file | Upload file chunk |

---

## Common Query Parameters

### Pagination

```bash
GET /api/admin/users?pageSize=20&page=1
```

### Filtering

```bash
GET /api/admin/users?search=john&is_active=1
```

### Sorting

```bash
GET /api/admin/users?sortColumn=name&sortDirection=asc
```

### Date Range

```bash
GET /api/admin/users?start=2024-01-01&end=2024-12-31
```

### Soft Deleted Records

```bash
GET /api/admin/users?is_trashed=1
```

---

## Response Format

All responses follow this structure:

```json
{
  "status": true,
  "code": 200,
  "message": "Success",
  "data": { ... }
}
```

Error responses:

```json
{
  "status": false,
  "code": 400,
  "message": "Error message",
  "data": []
}
```

---

## Postman Collection

For detailed API testing with pre-configured requests, import the Postman collection located at `/postman.json` in the project root.

## See Also

- [Authentication](/guide/authentication) — Login flows
- [Quick Start](/guide/quick-start) — First API calls
- [Filters & Scopes](/guide/features/filters-scopes) — Query parameters