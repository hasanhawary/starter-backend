#!/bin/bash

set -e

PHP="/opt/homebrew/opt/php@8.4/bin/php"

echo "Running central migrations..."
$PHP artisan migrate --path=database/migrations/tenant

echo "Seeding central database..."
$PHP artisan db:seed --class=Database\\Seeders\\Tenant\\DatabaseSeeder

echo "Central setup completed successfully."
