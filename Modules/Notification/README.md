# 🔔 Notification Module

A Laravel module for managing system notifications, notification events, channels, templates, reminders, and scheduled events.

---

## 📦 Module Info

| Key         | Value                    |
|-------------|--------------------------|
| Module Name | `Notification`           |
| Alias       | `notification`           |
| Priority    | `0`                      |

---

## 📬 Postman Collection

Test all Notification module endpoints directly via Postman:

[🔗 Open Notification Module in Postman](https://bold-meadow-231837.postman.co/workspace/My-Workspace~afc71a69-8583-4608-8fb8-eab23baec5fa/folder/41164032-beb8b8ae-4c42-4998-9caa-e6460902101e?action=share&source=copy-link&creator=41164032&ctx=documentation)

---

## 🚀 Backend Setup Steps

### 1. Run Migrations

Run the module's database migrations:

```bash
php artisan module:migrate Notification
```

### 2. Run Seeders

Seed the initial data (system events & variables):

```bash
php artisan db:seed --class="Modules\Notification\database\seeders\NotificationDatabaseSeeder"
```

> This will run the following seeders:
> - `SystemEventSeeder` — seeds predefined system events
> - `VariablesSeeder` — seeds notification variables

---

## 🖥️ Frontend Steps

### Overview

The frontend interacts with the Notification Module through a **multi-step form** or **settings page** per System Event. Each System Event can have one or more Notification Events attached to it, each with its own templates, recipients, type, and optional reminder settings.

---

### Step 1 — Load System Events Page

On page load, call:

```http
GET /user/system-events
```

The response is grouped by **module**. Render them as a list or accordion grouped by `module_name`.

Each system event card/row shows:
- `name` — display label
- `event_slug` — identifier
- `events` — array of existing notification events already linked

> 💡 This is the **entry point**. The user picks a system event to configure notifications for.

---

### Step 2 — Load Supporting Data (on form open)

Before rendering the create/edit form, prefetch:

| Data            | Endpoint                        | Used For                                   |
|-----------------|---------------------------------|--------------------------------------------|
| Variables       | `GET /user/variables`          | Multi-select for template placeholders     |
| Channels        | `GET /user/notification-channels` | Select available delivery channels      |
| Roles / Users   | your existing users/roles API   | Multi-select for recipient targeting       |

---

### Step 3 — Create Notification Event Form

Show a **form/modal** attached to the selected system event. The form has the following fields:

#### 3.1 — Basic Info

| UI Element        | Field            | Notes                                                      |
|-------------------|------------------|------------------------------------------------------------|
| Hidden / pre-fill | `system_event_id`| Auto-filled from the selected system event                 |
| Toggle / Checkbox | `is_reminder`    | "Is this a reminder?" — shows reminder config if `true`    |
| Multi-select      | `variables`      | Pick variable IDs from `GET /user/variables`              |

#### 3.2 — Recipients

| UI Element   | Field          | Notes                                                                   |
|--------------|----------------|-------------------------------------------------------------------------|
| Multi-select | `role_ids`     | Pick roles to receive the notification                                  |
| Multi-select | `user_ids`     | Pick specific users                                                      |
| Multi-select | `relation_ids` | Pick model relation IDs from the system event's `model_relations` array |

> ℹ️ At least one of `role_ids`, `user_ids`, or `relation_ids` should be provided.

#### 3.3 — Templates (one per channel)

Render a **dynamic list** of template blocks. Each block has:

| UI Element        | Field                      | Notes                                                             |
|-------------------|----------------------------|-------------------------------------------------------------------|
| Select            | `channel`                  | `sms` · `email` · `push` · `notification` · `reminder` · `calendar` |
| Translatable input| `title`                    | `{ "en": "...", "ar": "..." }` — optional for some channels      |
| Translatable textarea | `body`                 | `{ "en": "...", "ar": "..." }`                                    |
| Text input        | `date_column`              | **Shown only** when channel is `reminder` or `calendar`           |

> 💡 The user can use variable placeholders like `{{user_name}}` in title/body, based on the selected `variables`.

**Add template button** → appends a new template block for a different channel.

#### 3.4 — Submit

```http
POST /user/notification-events
```

**Payload shape:**
```json
{
  "system_event_id": 1,
  "is_reminder": false,
  "variables": [1, 2],
  "role_ids": [1],
  "user_ids": [],
  "relation_ids": [],
  "templates": [
    {
      "channel": "email",
      "title": { "en": "Hello!", "ar": "مرحبا!" },
      "body":  { "en": "Dear {{user_name}}, ...", "ar": "عزيزي {{user_name}}, ..." }
    },
    {
      "channel": "sms",
      "title": null,
      "body":  { "en": "You have a new notification." }
    }
  ]
}
```

On success → append the returned notification event into the system event's `events` list in the UI.

---

### Step 4 — Update Notification Event

When the user clicks **Edit** on an existing notification event:

1. Call `GET /user/notification-events/{id}` to prefill the form
2. Show the same form as Step 3 with all fields populated
3. On submit:

```http
PUT /user/notification-events/{id}
```

> Same payload structure as `POST`. All templates are **replaced** on update (send full list every time).

---

### Step 5 — Delete Notification Event

When the user clicks **Delete** on a notification event:

1. Show a confirmation dialog
2. On confirm:

```http
DELETE /user/notification-events/{id}
```

On success → remove the event from the system event's `events` list in the UI.

---

### Step 6 — (Conditional) Configure Reminder Settings

**Shown only when** `is_reminder = true` or `type` is `reminder` / `calendar`.

After creating/updating the event, show a **reminder settings section** with a list of offset rules:

| UI Element | Field                      | Notes                                                       |
|------------|----------------------------|-------------------------------------------------------------|
| Select     | `offset_type`              | `before` · `after`                                         |
| Number input | `offset_value`           | e.g. `2`                                                    |
| Select     | `offset_unit`              | `minute` · `hour` · `day`                                   |
| Text input | `reminder_based_on_column` | DB column name to base the reminder on (e.g. `due_date`)   |

**Add rule button** → appends a new offset row.

On submit:

```http
PUT /user/notification-events/{id}/reminder-setting
```

**Payload:**
```json
{
  "reminder_setting": [
    {
      "offset_type": "before",
      "offset_value": 2,
      "offset_unit": "day",
      "reminder_based_on_column": "due_date"
    }
  ]
}
```

---

### Frontend Flow Summary

```
Page Load
   └─ GET /user/system-events
         └─ Render system events grouped by module
               │
               ▼
         [+ Add Notification Event] button on each system event
               │
               ├─ GET /user/variables
               ├─ GET /user/notification-channels
               │
               ▼
         Create Form
         ├── system_event_id   (auto-filled)
         ├── is_reminder       (toggle)
         ├── variables         (multi-select)
         ├── role_ids          (multi-select)
         ├── user_ids          (multi-select)
         ├── relation_ids      (multi-select)
         └── templates[]
               ├── channel
               ├── title  { en, ar }
               ├── body   { en, ar }
               └── date_column  (only if channel = reminder / calendar)
               │
               ▼
         POST /user/notification-events
               │
               ├── [Edit]   → GET /user/notification-events/{id}
               │              PUT /user/notification-events/{id}
               │
               ├── [Delete] → DELETE /user/notification-events/{id}
               │
               └── [Reminder Settings] (if is_reminder = true)
                     └── PUT /user/notification-events/{id}/reminder-setting
```

---

## 🗄️ Database Migrations

| Migration File                                     | Description                             |
|----------------------------------------------------|-----------------------------------------|
| `create_system_events_table`                       | Stores system-level notification events |
| `create_channels_table`                            | Notification delivery channels          |
| `create_variables_table`                           | Dynamic variables for templates         |
| `create_notification_events_table`                 | Custom notification events              |
| `create_event_channel_table`                       | Pivot: events ↔ channels                |
| `create_event_model_relation_table`                | Pivot: events ↔ models                  |
| `create_variable_assignments_table`                | Variable assignments to events          |
| `create_notification_recipients_table`             | Notification recipients                 |
| `create_notification_templates_table`              | Notification content templates          |
| `create_reminders_setting_table`                   | Reminder scheduling settings            |
| `create_schedule_events_table`                     | Scheduled event triggers                |
| `create_system_notifications_table`                | System-level notifications              |
| `create_notifications_table`                       | General notifications                   |
| `create_notification_logs_table`                   | Logs for sent notifications             |

---

## 🌐 API Routes

All routes are prefixed with `/user` and protected by `auth:sanctum`.

| Method | Endpoint                                                    | Controller                      | Description                      |
|--------|-------------------------------------------------------------|---------------------------------|----------------------------------|
| GET    | `/user/variables`                                          | `VariablesController@index`     | List all notification variables  |
| GET    | `/user/system-events`                                      | `SystemEventController@index`   | List all system events           |
| PUT    | `/user/system-events/{id}`                                 | `SystemEventController@update`  | Update a system event            |
| GET    | `/user/notification-channels`                              | `ChannelController@index`       | List all notification channels   |
| POST   | `/user/notification-events`                                | `NotificationEventController@store`   | Create a notification event      |
| GET    | `/user/notification-events/{id}`                           | `NotificationEventController@show`    | Get a notification event         |
| PUT    | `/user/notification-events/{id}`                           | `NotificationEventController@update`  | Update a notification event      |
| DELETE | `/user/notification-events/{id}`                           | `NotificationEventController@destroy` | Delete a notification event      |
| PUT    | `/user/notification-events/{id}/reminder-setting`          | `NotificationEventController@updateReminderSettings` | Update reminder settings |

---

## 📂 Module Structure

```
Modules/Notification/
├── app/
│   ├── Console/
│   ├── Enum/
│   │   ├── NotificationChannelEnum.php       # sms, email, push, reminder, notification, calendar
│   │   ├── NotificationEventTypesEnum.php    # reminder, notification, calendar
│   │   ├── ReminderSettingTypesEnum.php
│   │   ├── ReminderSettingUnitsEnum.php
│   │   ├── ScheduleEventTypeEnum.php
│   │   ├── SystemEventModuleEnum.php         # country, role
│   │   ├── SystemEventSlugEnum.php
│   │   └── VariableTypeEnum.php
│   ├── Events/
│   ├── Http/
│   │   ├── Controllers/Api/user/
│   │   │   ├── ChannelController.php
│   │   │   ├── NotificationController.php
│   │   │   ├── NotificationEventController.php
│   │   │   ├── SystemEventController.php
│   │   │   └── VariablesController.php
│   │   ├── Requests/
│   │   └── Resources/
│   ├── Listeners/
│   ├── Models/
│   │   ├── Channel.php
│   │   ├── EventModelRelation.php
│   │   ├── NotificationEvent.php
│   │   ├── NotificationLog.php
│   │   ├── NotificationRecipient.php
│   │   ├── NotificationTemplate.php
│   │   ├── RemindersSetting.php
│   │   ├── ScheduleEvent.php
│   │   ├── SystemEvent.php
│   │   ├── SystemNotification.php
│   │   ├── Variable.php
│   │   └── VariableAssignment.php
│   ├── Tools/
│   └── Traits/
├── database/
│   ├── migrations/
│   └── seeders/
│       ├── NotificationDatabaseSeeder.php
│       ├── SystemEventSeeder.php
│       └── VariablesSeeder.php
├── routes/
│   └── api.php
├── Providers/
└── module.json
```

---

## 🔔 Notification Channels

| Channel        | Value          |
|----------------|----------------|
| SMS            | `sms`          |
| Email          | `email`        |
| Push           | `push`         |
| Reminder       | `reminder`     |
| Notification   | `notification` |
| Calendar       | `calendar`     |

---

## 📋 Notification Event Types

| Type           | Value          |
|----------------|----------------|
| Reminder       | `reminder`     |
| Notification   | `notification` |
| Calendar       | `calendar`     |

