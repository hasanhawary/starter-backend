#!/bin/bash

set -e

PHP="/opt/homebrew/opt/php@8.4/bin/php"

echo "Running central (main) migrations..."

$PHP artisan migrate:fresh --path=database/migrations --database=mysql --force

echo "Seeding central database..."
$PHP artisan db:seed --class=Database\\Seeders\\DatabaseSeeder --force

echo "Central setup completed successfully."
