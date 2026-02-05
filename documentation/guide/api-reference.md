---
title: API Reference
description: Complete list of all API endpoints
---

# API Reference

Complete reference of all API endpoints in the Starter Backend system.

## Base URL

All endpoints are prefixed with `/api/`:
```
http://starter-backend.test/api/
```

## Authentication

All endpoints except public routes require a Bearer token:

```http
Authorization: Bearer {token}
```

## Public Endpoints

### Captcha

| Method | Endpoint | Description |
|--------|----------|-------------|
| GET | `/captcha` | Generate captcha image |
| POST | `/captcha/verify` | Verify captcha response |

### Authentication

| Method | Endpoint | Description |
|--------|----------|-------------|
| POST | `/login` | User login |
| POST | `/reset-password` | Reset password with token |

### OTP (One-Time Password)

| Method | Endpoint | Description |
|--------|----------|-------------|
| POST | `/send-otp` | Send OTP to email/phone |
| POST | `/check-otp` | Check if OTP is valid |
| POST | `/verify-otp` | Verify OTP and get token |

## Authenticated Endpoints

### Profile

| Method | Endpoint | Description |
|--------|----------|-------------|
| GET | `/me` | Get current user profile |
| POST | `/update-profile` | Update profile information |
| POST | `/destroy-avatar` | Remove user avatar |
| POST | `/logout` | Logout current session |

### Users

| Method | Endpoint | Description |
|--------|----------|-------------|
| GET | `/users` | List all users (paginated) |
| GET | `/users/{user}` | Get user details |
| POST | `/users` | Create new user |
| PUT | `/users/{user}` | Update user |
| DELETE | `/users/delete` | Soft delete users (batch) |
| DELETE | `/users/force-delete` | Permanently delete users (batch) |
| POST | `/users/restore` | Restore soft-deleted users |
| PUT | `/users/toggle-active` | Toggle user active status |

### Roles

| Method | Endpoint | Description |
|--------|----------|-------------|
| GET | `/roles` | List all roles |
| GET | `/roles/{role}` | Get role details |
| POST | `/roles` | Create new role |
| PUT | `/roles/{role}` | Update role |
| DELETE | `/roles/delete` | Delete roles (batch) |

### Permissions

| Method | Endpoint | Description |
|--------|----------|-------------|
| GET | `/permissions` | List all permissions |

### Countries (Data Entry)

| Method | Endpoint | Description |
|--------|----------|-------------|
| GET | `/countries` | List all countries |
| GET | `/countries/{country}` | Get country details |
| POST | `/countries` | Create new country |
| PUT | `/countries/{country}` | Update country |
| DELETE | `/countries/delete` | Soft delete countries (batch) |
| DELETE | `/countries/force-delete` | Permanently delete countries |
| POST | `/countries/restore` | Restore soft-deleted countries |

## Global Features

### Settings

| Method | Endpoint | Description |
|--------|----------|-------------|
| GET | `/settings` | Get all settings |
| PUT | `/settings/{setting}` | Update a setting |
| POST | `/send-test-mail` | Test email credentials |

### Notifications

| Method | Endpoint | Description |
|--------|----------|-------------|
| GET | `/notifications` | List user notifications |
| PUT | `/notifications` | Mark notifications as read |

### Help (Lookup Manager)

| Method | Endpoint | Description |
|--------|----------|-------------|
| GET | `/help-configs` | Get configuration values |
| GET | `/help-models` | Get model lookups for dropdowns |
| GET | `/help-enums` | Get enum values for selects |

### Reports & Exports

| Method | Endpoint | Description |
|--------|----------|-------------|
| GET | `/report` | Generate report (PDF/Excel) |
| GET | `/export` | Export data to file |

### Activity Logs

| Method | Endpoint | Description |
|--------|----------|-------------|
| GET | `/activity-logs` | List activity logs |
| GET | `/activity-logs/{activity}` | Get activity log details |

### File Uploads

| Method | Endpoint | Description |
|--------|----------|-------------|
| POST | `/chunk-file` | Upload file in chunks |

## Request/Response Format

### Standard Success Response

```json
{
  "success": true,
  "message": "Operation completed successfully",
  "data": { ... }
}
```

### Paginated Response

```json
{
  "success": true,
  "data": {
    "data": [ ... ],
    "meta": {
      "current_page": 1,
      "last_page": 10,
      "per_page": 15,
      "total": 150
    }
  }
}
```

### Error Response

```json
{
  "success": false,
  "message": "Validation failed",
  "errors": {
    "email": ["The email field is required."]
  }
}
```

## Query Parameters

### Pagination

| Parameter | Type | Default | Description |
|-----------|------|---------|-------------|
| `page` | integer | 1 | Page number |
| `pageSize` | integer | 15 | Items per page (0 = all) |

### Filtering

| Parameter | Type | Description |
|-----------|------|-------------|
| `search` | string | Search term |
| `is_active` | boolean | Filter by active status |
| `trashed` | string | `only`, `with`, or omit for no trashed |
| `order_by` | string | Column to sort by |
| `order_dir` | string | `asc` or `desc` |
| `from_date` | date | Filter from date |
| `to_date` | date | Filter to date |

### Example Request

```http
GET /api/users?page=1&pageSize=20&search=john&is_active=true&order_by=created_at&order_dir=desc
Authorization: Bearer {token}
```

## Authentication Flow

### Login

```http
POST /api/login
Content-Type: application/json

{
  "email": "user@example.com",
  "password": "password123",
  "captcha_key": "abc123"  // if captcha enabled
}
```

**Response:**
```json
{
  "success": true,
  "data": {
    "token": "1|abc123...",
    "user": {
      "id": 1,
      "name": "John Doe",
      "email": "user@example.com"
    }
  }
}
```

### OTP Flow (Password Reset)

1. **Send OTP:**
```http
POST /api/send-otp
{ "email": "user@example.com", "type": "password_reset" }
```

2. **Verify OTP:**
```http
POST /api/verify-otp
{ "email": "user@example.com", "otp": "123456" }
```

3. **Reset Password:**
```http
POST /api/reset-password
{ "email": "user@example.com", "token": "...", "password": "newpass123" }
```

## Postman Collection

For detailed API testing with pre-configured requests and examples:

`file path`: ~\postman.json

Import into Postman and configure your environment variables:
- `base_url`: Your API base URL
- `token`: Your authentication token


## See Also

- [Authentication](/guide/features/authentication) — Auth flow details
- [Configuration](/guide/configuration) — API configuration options
- [Filters & Scopes](/guide/features/filters-scopes) — Query filtering