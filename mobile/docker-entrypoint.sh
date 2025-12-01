#!/bin/sh
set -e

# Install dependencies if node_modules doesn't exist or is empty
if [ ! -d "node_modules" ] || [ ! "$(ls -A node_modules)" ]; then
    echo "Installing npm dependencies..."
    npm install
fi

# Start Expo development server with web support
echo "Starting Expo development server with web support..."
echo "Metro Bundler will be available at http://localhost:8081"
echo "Web version will be available at http://localhost:${WEB_PORT:-19006}"

# Start Expo with web support
# --web flag enables web support
# --host 0.0.0.0 makes it accessible from outside the container
# CI=1 prevents interactive prompts (required for Docker)
CI=1 exec npx expo start --web --host 0.0.0.0

