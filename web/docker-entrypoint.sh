#!/bin/sh
set -e

# Install dependencies if node_modules doesn't exist or is empty
if [ ! -d "node_modules" ] || [ ! "$(ls -A node_modules)" ]; then
    echo "Installing npm dependencies..."
    npm install
fi

# Start development server
echo "Starting Vite development server..."
exec npm run dev -- --host 0.0.0.0

