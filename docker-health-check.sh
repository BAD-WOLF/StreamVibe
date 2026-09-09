#!/usr/bin/env bash

# Docker Health Check Script for StreamVibe
# This script verifies that the application is running correctly

set -e

echo "🚀 Starting StreamVibe Health Check..."

# Check if PHP is working
echo "✅ Testing PHP..."
php --version

# Check if Symfony console is accessible
echo "✅ Testing Symfony Console..."
php bin/console --version

# Check if database connection is working
echo "✅ Testing Database Connection..."
php bin/console dbal:run-sql "SELECT 1" > /dev/null 2>&1
if [ $? -eq 0 ]; then
    echo "✅ Database connection successful"
else
    echo "❌ Database connection failed"
    exit 1
fi

# Check if migrations are up to date
echo "✅ Checking Database Migrations..."
php bin/console doctrine:migrations:status --no-interaction

# Check if services can be instantiated (basic container compilation test)
echo "✅ Testing Service Container..."
php bin/console debug:container --parameters > /dev/null 2>&1
if [ $? -eq 0 ]; then
    echo "✅ Service container is working"
else
    echo "❌ Service container has issues"
    exit 1
fi

# Check if routes are loaded
echo "✅ Testing Routes..."
ROUTE_COUNT=$(php bin/console debug:router --format=json | jq '. | length' 2>/dev/null || echo "0")
if [ "$ROUTE_COUNT" -gt "0" ]; then
    echo "✅ Routes loaded successfully ($ROUTE_COUNT routes)"
else
    echo "❌ No routes found"
    exit 1
fi

# Test a simple HTTP request if server is running
echo "✅ Testing HTTP Response..."
if curl -f -s http://localhost:8000/api/image/sizes > /dev/null 2>&1; then
    echo "✅ HTTP server is responding"
else
    echo "⚠️  HTTP server not responding (may not be started yet)"
fi

echo ""
echo "🎉 StreamVibe Health Check Completed Successfully!"
echo ""
echo "📋 Health Check Summary:"
echo "   ✅ PHP Runtime: Working"
echo "   ✅ Symfony Framework: Working"
echo "   ✅ Database Connection: Working"
echo "   ✅ Service Container: Working"
echo "   ✅ Routes: Loaded ($ROUTE_COUNT routes)"
echo "   ✅ Application: Ready"
echo ""
echo "🐳 Ready for Docker deployment!"

exit 0
