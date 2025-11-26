#!/bin/sh
set -e

# Install/update Composer dependencies
if [ ! -d "vendor" ] || [ ! -f "vendor/autoload.php" ]; then
    echo "Installing Composer dependencies..."
    composer install --no-interaction --prefer-dist --optimize-autoloader
fi

# Generate application key if not exists in .env file
if ! grep -q "APP_KEY=base64:" .env 2>/dev/null; then
    echo "Generating application key..."
    php artisan key:generate --force || true
fi

# Run migrations
echo "Running migrations..."
php artisan migrate --force || true

# Clear and cache config
php artisan config:clear || true
php artisan cache:clear || true

# Start the server
echo "Starting Laravel development server..."
exec php artisan serve --host=0.0.0.0 --port=8000

