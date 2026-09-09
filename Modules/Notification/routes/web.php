<?php

/*
|--------------------------------------------------------------------------
| Notification Module — Web Routes
|--------------------------------------------------------------------------
|
| This is an API-only backend; the module is served entirely from
| routes/api.php.
|
| A one-shot `GET notification/sync-events` route used to live here. It ran
| `db:seed --force` with no authentication, so anyone who could reach the host
| could rewrite the notification tables. Its own comment marked it for deletion
| once it had run. Reseed from the CLI instead:
|
|   php artisan db:seed --class="Modules\Notification\database\seeders\NotificationDatabaseSeeder"
|
*/
