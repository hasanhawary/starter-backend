---
title: API Reference
description: Complete list of all API endpoints with minimal information
---

# API Reference

This page provides a complete list of all API endpoints in the Starter Backend system.

## Base URL

- All endpoints are prefixed with `/api/`. 
- For example: `http://starter-backend.test/api/login`

## Authentication

All endpoints except login, password reset, and captcha require a Bearer token in the Authorization header.

## Endpoints

### Authentication

| Method | Endpoint | Description |
|--------|----------|-------------|
| POST | /login | User login |
| POST | /logout | User logout |
| POST | /forget | Request password reset |
| POST | /verify-otp | Verify OTP for password reset |
| POST | /reset | Reset password |
| GET | /captcha | Get captcha image |
| POST | /captcha/verify | Verify captcha |

### Users

| Method | Endpoint | Description |
|--------|----------|-------------|
| GET | /users | List users |
| GET | /users/{id} | Get user details |
| POST | /users | Create user |
| PUT | /users/{id} | Update user |
| DELETE | /users/{id} | Delete user |
| POST | /users/{id}/change-status | Change user active status |

### Roles & Permissions

| Method | Endpoint | Description |
|--------|----------|-------------|
| GET | /roles | List roles |
| POST | /roles | Create role |
| PUT | /roles/{id} | Update role |
| DELETE | /roles/{id} | Delete role |
| GET | /permissions | List permissions |
| POST | /permissions | Create permission |
| DELETE | /permissions/{id} | Delete permission |

### Countries

| Method | Endpoint | Description |
|--------|----------|-------------|
| GET | /countries | List countries |
| GET | /countries/{id} | Get country details |

### Global Features

| Method | Endpoint | Description |
|--------|----------|-------------|
| GET | /help-models | Get help models |
| GET | /help-enums | Get help enums |
| POST | /chunk-file | Upload file in chunks |
| GET | /notifications | List notifications |
| PUT | /notifications | Mark notifications as read |
| GET | /settings | Get settings |
| POST | /set-settings | Update settings |
| GET | /get-activity-logs | List activity logs |
| GET | /get-activity-logs/{id} | Get activity log details |
| GET | /report | Generate report |
| POST | /export | Start export job |
| GET | /export/{job_id} | Get export status |

## Postman Collection

For detailed API testing with pre-configured requests, examples, and environment variables, download and import the [Postman Collection](/Starter_Backend.postman_collection.json).