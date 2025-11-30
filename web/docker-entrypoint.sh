#!/bin/sh
set -e

# Always install/update dependencies to ensure they're in sync with package.json
echo "Installing npm dependencies..."
npm install

# Start development server
echo "Starting Vite development server..."
exec npm run dev -- --host 0.0.0.0

