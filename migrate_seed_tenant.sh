#!/bin/bash

set -e

PHP="/opt/homebrew/opt/php@8.4/bin/php"

echo "Running migrations for all tenants..."
$PHP artisan tenants:artisan "migrate --path=database/migrations/tenant --database=tenant --force"

echo "Seeding all tenants..."
$PHP artisan tenants:artisan "db:seed --class=Database\\Seeders\\Tenant\\DatabaseSeeder --force"

echo "All migrations and seeders completed successfully."
