#!/bin/sh
set -e

# Install dependencies if node_modules doesn't exist or is empty
if [ ! -d "node_modules" ] || [ ! "$(ls -A node_modules)" ]; then
    echo "Installing npm dependencies..."
    npm install
fi

# Start Expo development server
echo "Starting Expo development server..."
exec npx expo start --host tunnel

