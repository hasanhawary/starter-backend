#!/bin/bash

set -e

PHP="/opt/homebrew/opt/php@8.4/bin/php"

echo "Running migrations..."

$PHP artisan migrate:fresh --path=database/migrations --database=mysql --force

echo "Seeding database..."
$PHP artisan db:seed --class=Database\\Seeders\\DatabaseSeeder --force

echo "Setup completed successfully."
