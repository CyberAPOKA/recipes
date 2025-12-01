#!/bin/sh
set -e

# Install dependencies if node_modules doesn't exist or is empty
if [ ! -d "node_modules" ] || [ ! "$(ls -A node_modules)" ]; then
    echo "Installing npm dependencies..."
    npm install
fi

# Start Expo development server with web support on port 8081
echo "Starting Expo development server..."
echo "App will be available at http://localhost:8081"

# Start Expo with web support, forcing web port to 8081
# Using PORT environment variable to set web server port
PORT=8081 WEB_PORT=8081 exec npx expo start --web --host 0.0.0.0 --port 8081

