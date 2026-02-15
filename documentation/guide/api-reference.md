---
title: API Reference
description: Complete API endpoint reference and response formats
---

# API Reference

Complete reference for all API endpoints, request/response formats, and error handling.

## Base URL

```
http://localhost:8000/api
```

## Authentication

All endpoints (except auth) require Bearer token authentication:

```bash
Authorization: Bearer {token}
```

## Response Format

### Success Response

```json
{
  "success": true,
  "message": "Operation successful",
  "code": 200,
  "data": {
    // Response data
  }
}
```

### Error Response

```json
{
  "success": false,
  "message": "Error message",
  "code": 400,
  "errors": {
    "field": ["Error message"]
  }
}
```

## Status Codes

| Code | Meaning |
|------|---------|
| 200 | OK - Request successful |
| 201 | Created - Resource created |
| 204 | No Content - Successful, no content |
| 400 | Bad Request - Invalid parameters |
| 401 | Unauthorized - Missing/invalid token |
| 403 | Forbidden - Insufficient permissions |
| 404 | Not Found - Resource not found |
| 422 | Unprocessable Entity - Validation error |
| 429 | Too Many Requests - Rate limited |
| 500 | Server Error - Internal error |

## Authentication Endpoints

### Login

```http
POST /login
Content-Type: application/json

{
  "email": "user@example.com",
  "password": "password123"
}
```

**Response:**
```json
{
  "success": true,
  "message": "Login successful",
  "data": {
    "user": {
      "id": 1,
      "name": "John Doe",
      "email": "user@example.com",
      "roles": ["admin"]
    },
    "token": "1|abc123xyz..."
  }
}
```

### Logout

```http
POST /logout
Authorization: Bearer {token}
```

### Refresh Token

```http
POST /refresh-token
Authorization: Bearer {token}
```

## User Endpoints

### List Users

```http
GET /users?page=1&per_page=15&is_active=true&sort_column=name&sort_direction=asc
Authorization: Bearer {token}
```

**Query Parameters:**
- `page` - Page number (default: 1)
- `per_page` - Items per page (default: 15)
- `is_active` - Filter by active status (true/false)
- `name` - Filter by name
- `email` - Filter by email
- `sort_column` - Sort column (default: id)
- `sort_direction` - Sort direction (asc/desc, default: desc)

**Response:**
```json
{
  "success": true,
  "data": {
    "data": [
      {
        "id": 1,
        "name": "John Doe",
        "email": "john@example.com",
        "is_active": true,
        "created_at": "2024-01-01T00:00:00Z"
      }
    ],
    "pagination": {
      "total": 100,
      "per_page": 15,
      "current_page": 1,
      "last_page": 7
    }
  }
}
```

### Get User

```http
GET /users/{id}
Authorization: Bearer {token}
```

### Create User

```http
POST /users
Authorization: Bearer {token}
Content-Type: application/json

{
  "name": "Jane Doe",
  "email": "jane@example.com",
  "password": "SecurePass123!",
  "password_confirmation": "SecurePass123!",
  "is_active": true
}
```

### Update User

```http
PUT /users/{id}
Authorization: Bearer {token}
Content-Type: application/json

{
  "name": "Jane Smith",
  "email": "jane.smith@example.com",
  "is_active": true
}
```

### Delete User

```http
DELETE /users/{id}
Authorization: Bearer {token}
```

### Toggle User Active Status

```http
POST /users/{id}/toggle-active
Authorization: Bearer {token}
```

## Role Endpoints

### List Roles

```http
GET /roles?page=1&per_page=15
Authorization: Bearer {token}
```

### Get Role

```http
GET /roles/{id}
Authorization: Bearer {token}
```

### Create Role

```http
POST /roles
Authorization: Bearer {token}
Content-Type: application/json

{
  "name": "editor",
  "display_name": {
    "en": "Editor",
    "ar": "محرر"
  },
  "permissions": [1, 2, 3]
}
```

### Update Role

```http
PUT /roles/{id}
Authorization: Bearer {token}
Content-Type: application/json

{
  "display_name": {
    "en": "Senior Editor",
    "ar": "محرر أول"
  },
  "permissions": [1, 2, 3, 4]
}
```

### Delete Role

```http
DELETE /roles/{id}
Authorization: Bearer {token}
```

## Permission Endpoints

### List Permissions

```http
GET /permissions?page=1&per_page=50
Authorization: Bearer {token}
```

### Get Permission

```http
GET /permissions/{id}
Authorization: Bearer {token}
```

## Settings Endpoints

### Get All Settings

```http
GET /settings
Authorization: Bearer {token}
```

**Response:**
```json
{
  "success": true,
  "data": {
    "general": {
      "site_name": "My App",
      "site_url": "https://example.com"
    },
    "email": {
      "from_address": "noreply@example.com",
      "from_name": "My App"
    }
  }
}
```

### Get Setting

```http
GET /settings/{key}
Authorization: Bearer {token}
```

### Update Setting

```http
PUT /settings/{key}
Authorization: Bearer {token}
Content-Type: application/json

{
  "value": "New Value"
}
```

## Error Handling

### Validation Error

```json
{
  "success": false,
  "message": "Validation failed",
  "code": 422,
  "errors": {
    "email": ["The email field is required"],
    "password": ["The password must be at least 8 characters"]
  }
}
```

### Authentication Error

```json
{
  "success": false,
  "message": "Unauthenticated",
  "code": 401
}
```

### Authorization Error

```json
{
  "success": false,
  "message": "This action is unauthorized",
  "code": 403
}
```

### Not Found Error

```json
{
  "success": false,
  "message": "Resource not found",
  "code": 404
}
```

## Rate Limiting

API requests are rate limited to prevent abuse:

- **Default:** 60 requests per minute per IP
- **Auth Endpoints:** 5 attempts per minute

**Rate Limit Headers:**
```
X-RateLimit-Limit: 60
X-RateLimit-Remaining: 45
X-RateLimit-Reset: 1640000000
```

## Pagination

List endpoints support pagination:

```http
GET /users?page=2&per_page=20
```

**Response:**
```json
{
  "data": [...],
  "pagination": {
    "total": 100,
    "per_page": 20,
    "current_page": 2,
    "last_page": 5,
    "from": 21,
    "to": 40
  }
}
```

## Filtering

Most list endpoints support filtering:

```http
GET /users?is_active=true&name=John&email=john
```

## Sorting

Sort results using `sort_column` and `sort_direction`:

```http
GET /users?sort_column=created_at&sort_direction=desc
```

## Localization

Set language using `Accept-Language` header:

```bash
Accept-Language: en
# or
Accept-Language: ar
```

## Best Practices

1. **Always Include Token** - Include Bearer token in Authorization header
2. **Handle Errors** - Check response status and error messages
3. **Respect Rate Limits** - Implement exponential backoff
4. **Use Pagination** - Don't fetch all records at once
5. **Validate Input** - Validate data before sending
6. **Use Appropriate Methods** - GET for retrieval, POST for creation, PUT for updates, DELETE for deletion
7. **Include Content-Type** - Always include `Content-Type: application/json` for JSON requests

## See Also

- [Authentication](/guide/authentication) — Authentication guide
- [Error Handling](/guide/error-handling) — Error handling
- [Services](/guide/features/services) — Business logic

