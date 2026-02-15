---
title: Quick Start
description: Get started with the Admin Dashboard Kit API in 5 minutes
---

# Quick Start

Learn the fundamentals of the Admin Dashboard Kit API with real, practical examples.

## 1. Authentication

All API requests (except auth endpoints) require a valid token. Let's start by logging in.

### Login

```bash
curl -X POST http://starter-backend.test/api/login \
  -H "Content-Type: application/json" \
  -d '{
    "email": "root@wakeb.com",
    "password": "password"
  }'
```

**Response:**

```json
{
  "success": true,
  "message": "Login successful",
  "data": {
    "user": {
      "id": 1,
      "name": "Admin User",
      "email": "root@wakeb.com",
      "phone": "+1234567890",
      "is_active": true,
      "roles": []
    },
    "token": "1|abc123xyz789..."
  }
}
```

Copy the `token` — you'll use it for subsequent requests.

### Using the Token

Include the token in the `Authorization` header:

```bash
curl http://starter-backend.test/api/me \
  -H "Authorization: Bearer 1|abc123xyz789..."
```

## 2. Get Current User Profile

```bash
curl http://starter-backend.test/api/me \
  -H "Authorization: Bearer YOUR_TOKEN"
```

**Response:**

```json
{
  "success": true,
  "message": null,
  "data": {
    "id": 1,
    "name": "Admin User",
    "email": "root@wakeb.com",
    "avatar": "https://example.com/storage/avatars/user-1.jpg",
    "roles": ["Admin"],
    "created_at": "2024-01-15T10:30:00Z"
  }
}
```

## 3. Get Model Help (Metadata)

Fetch available models and their properties for building dynamic forms.

```bash
curl "http://starter-backend.test/api/help-models?models=User,Role" \
  -H "Authorization: Bearer YOUR_TOKEN"
```

**Response:**

```json
{
  "success": true,
  "data": {
    "User": {
      "table": "users",
      "fillable": ["name", "email", "phone", "is_active"],
      "relations": {
        "roles": {
          "type": "BelongsToMany",
          "model": "Role"
        },
        "nationality": {
          "type": "BelongsTo",
          "model": "Country"
        }
      },
      "columns": {
        "id": "integer",
        "name": "string",
        "email": "string",
        "phone": "string",
        "is_active": "boolean",
        "created_at": "timestamp"
      }
    },
    "Role": {
      "table": "roles",
      "columns": { ... }
    }
  }
}
```

## 4. Get Enums

Fetch enum values for dropdowns and selects.

```bash
curl "http://starter-backend.test/api/help-enums?enums=Gender,UserStatus" \
  -H "Authorization: Bearer YOUR_TOKEN"
```

**Response:**

```json
{
  "success": true,
  "data": {
    "Gender": {
      "male": "Male",
      "female": "Female",
      "other": "Other"
    },
    "UserStatus": {
      "active": "Active",
      "inactive": "Inactive",
      "pending": "Pending"
    }
  }
}
```

## 5. Upload a File (Chunked)

Upload large files in chunks with progress tracking.

```bash
curl -X POST http://starter-backend.test/api/chunk-file \
  -H "Authorization: Bearer YOUR_TOKEN" \
  -F "file=@large-document.pdf" \
  -F "chunk_index=0" \
  -F "total_chunks=5"
```

**Response:**

```json
{
  "success": true,
  "message": "Chunk uploaded successfully",
  "data": {
    "upload_id": "upload_123abc",
    "chunk_index": 0,
    "total_chunks": 5,
    "status": "uploading"
  }
}
```

## 6. Get Notifications

Fetch user notifications with pagination.

```bash
curl "http://starter-backend.test/api/notifications?per_page=10" \
  -H "Authorization: Bearer YOUR_TOKEN"
```

**Response:**

```json
{
  "success": true,
  "data": {
    "data": [
      {
        "id": "notification-id-1",
        "type": "UserCreated",
        "title": "New User Added",
        "message": "Admin added a new user: Jane Smith",
        "read_at": null,
        "created_at": "2024-01-20T15:30:00Z"
      }
    ],
    "unread_count": 3
  }
}
```

## Response Format

All API responses follow a consistent format:

```json
{
  "success": boolean,
  "message": "string or null",
  "data": object | array | null,
  "errors": object | null
}
```

### Success Response

```json
{
  "success": true,
  "message": "Operation completed successfully",
  "data": { ... }
}
```

### Error Response

```json
{
  "success": false,
  "message": "Validation error",
  "errors": {
    "email": ["Email is required", "Email must be unique"]
  }
}
```

## Common Status Codes

- **200 OK** — Request succeeded
- **201 Created** — Resource created successfully
- **400 Bad Request** — Invalid request data
- **401 Unauthorized** — Missing or invalid token
- **403 Forbidden** — Permission denied
- **404 Not Found** — Resource not found
- **422 Unprocessable Entity** — Validation failed
- **500 Server Error** — Internal server error

## Next Steps

- [Authentication](/guide/authentication) — Learn about LDAP, password reset, and OTP
- [API Reference](/guide/api-reference) — Complete endpoint documentation
- [Help & Lookups](/guide/tools/lookup-manager) — Dynamic metadata fetching
