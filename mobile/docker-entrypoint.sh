#!/bin/sh
set -e

# Always check and install dependencies to ensure all packages are installed
echo "Checking npm dependencies..."
if [ ! -d "node_modules" ] || [ ! "$(ls -A node_modules)" ] || [ ! -d "node_modules/@react-native-async-storage" ]; then
    echo "Installing npm dependencies..."
    npm install
else
    echo "Dependencies already installed, checking for missing packages..."
    # Check if async-storage is installed, if not install it
    if [ ! -d "node_modules/@react-native-async-storage/async-storage" ]; then
        echo "Installing @react-native-async-storage/async-storage..."
        npm install @react-native-async-storage/async-storage
    fi
fi

# Start Expo development server with web support
echo "Starting Expo development server with web support..."
echo "Metro Bundler will be available at http://localhost:8081"
echo "Web version will be available at http://localhost:${WEB_PORT:-19006}"

# Start Expo with web support
# Using --web flag enables web support
# --host lan allows access from local network (works in Docker)
CI=1 exec npx expo start --web --host lan

