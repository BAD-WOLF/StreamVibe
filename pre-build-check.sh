#!/usr/bin/env bash

# Pre-Build Check Script for StreamVibe Clean Architecture
# This script validates the codebase before Docker build

set -e

echo "🔍 StreamVibe Pre-Build Validation Started..."
echo "================================================"

# Color codes for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m' # No Color

# Function to print colored output
print_status() {
    local status=$1
    local message=$2
    case $status in
        "SUCCESS")
            echo -e "${GREEN}✅ $message${NC}"
            ;;
        "WARNING")
            echo -e "${YELLOW}⚠️  $message${NC}"
            ;;
        "ERROR")
            echo -e "${RED}❌ $message${NC}"
            ;;
        "INFO")
            echo -e "${BLUE}ℹ️  $message${NC}"
            ;;
    esac
}

# Check if we're in the right directory
if [ ! -f "composer.json" ] || [ ! -f "symfony.lock" ]; then
    print_status "ERROR" "Not in StreamVibe root directory"
    exit 1
fi

print_status "SUCCESS" "Found StreamVibe project files"

# Check PHP version
echo -e "\n${BLUE}📋 Checking PHP Environment...${NC}"
PHP_VERSION=$(php --version | head -n 1 | cut -d " " -f 2 | cut -d "." -f 1-2)
if [ "$(printf '%s\n' "8.4" "$PHP_VERSION" | sort -V | head -n1)" = "8.4" ]; then
    print_status "SUCCESS" "PHP $PHP_VERSION is compatible (>= 8.4 required)"
else
    print_status "ERROR" "PHP $PHP_VERSION is too old (>= 8.4 required)"
    exit 1
fi

# Check Composer
if ! command -v composer &> /dev/null; then
    print_status "ERROR" "Composer not found"
    exit 1
fi
print_status "SUCCESS" "Composer found"

# Check if vendor directory exists
if [ ! -d "vendor" ]; then
    print_status "WARNING" "Vendor directory not found, running composer install..."
    composer install --no-dev --optimize-autoloader
else
    print_status "SUCCESS" "Vendor directory exists"
fi

# Validate Composer dependencies
echo -e "\n${BLUE}📦 Validating Dependencies...${NC}"
if composer validate --no-check-publish --no-check-all; then
    print_status "SUCCESS" "Composer.json is valid"
else
    print_status "ERROR" "Composer.json validation failed"
    exit 1
fi

# Check critical PHP extensions
echo -e "\n${BLUE}🔧 Checking PHP Extensions...${NC}"
REQUIRED_EXTENSIONS=("pdo" "pdo_pgsql" "ctype" "iconv" "fileinfo" "sodium")

for ext in "${REQUIRED_EXTENSIONS[@]}"; do
    if php -m | grep -q "^$ext$"; then
        print_status "SUCCESS" "Extension $ext is loaded"
    else
        print_status "ERROR" "Required extension $ext is missing"
        exit 1
    fi
done

# Check Symfony requirements
echo -e "\n${BLUE}🎵 Checking Symfony Requirements...${NC}"
if [ -f "vendor/bin/requirements-checker" ]; then
    php vendor/bin/requirements-checker
else
    print_status "WARNING" "Symfony requirements checker not found"
fi

# Validate Clean Architecture structure
echo -e "\n${BLUE}🏗️  Validating Clean Architecture Structure...${NC}"
REQUIRED_DIRS=(
    "src/Domain"
    "src/Application"
    "src/Infrastructure"
    "src/Domain/User/Entity"
    "src/Domain/Authentication/Entity"
    "src/Application/Authentication/UseCase"
    "src/Application/Movie/UseCase"
    "src/Infrastructure/Http/Controller"
    "src/Infrastructure/Persistence/Repository"
)

for dir in "${REQUIRED_DIRS[@]}"; do
    if [ -d "$dir" ]; then
        print_status "SUCCESS" "Directory $dir exists"
    else
        print_status "ERROR" "Required directory $dir is missing"
        exit 1
    fi
done

# Check critical files
echo -e "\n${BLUE}📄 Checking Critical Files...${NC}"
CRITICAL_FILES=(
    "config/services.yaml"
    "config/packages/doctrine.yaml"
    "config/routes.yaml"
    "src/Kernel.php"
    "public/index.php"
)

for file in "${CRITICAL_FILES[@]}"; do
    if [ -f "$file" ]; then
        print_status "SUCCESS" "File $file exists"
    else
        print_status "ERROR" "Critical file $file is missing"
        exit 1
    fi
done

# Syntax check on PHP files
echo -e "\n${BLUE}🔍 Running PHP Syntax Check...${NC}"
PHP_FILES=$(find src -name "*.php" -type f)
SYNTAX_ERRORS=0

for file in $PHP_FILES; do
    if ! php -l "$file" > /dev/null 2>&1; then
        print_status "ERROR" "Syntax error in $file"
        SYNTAX_ERRORS=$((SYNTAX_ERRORS + 1))
    fi
done

if [ $SYNTAX_ERRORS -eq 0 ]; then
    print_status "SUCCESS" "All PHP files have valid syntax"
else
    print_status "ERROR" "$SYNTAX_ERRORS PHP files have syntax errors"
    exit 1
fi

# Check Doctrine entities
echo -e "\n${BLUE}🗄️  Validating Doctrine Entities...${NC}"
if php bin/console doctrine:schema:validate --skip-sync 2>/dev/null; then
    print_status "SUCCESS" "Doctrine entity mapping is valid"
else
    print_status "WARNING" "Doctrine entity mapping has issues (may need database)"
fi

# Check environment files
echo -e "\n${BLUE}🌍 Checking Environment Configuration...${NC}"
if [ -f ".env" ]; then
    print_status "SUCCESS" "Environment file .env found"
else
    print_status "WARNING" "No .env file found"
fi

# Check Docker files
echo -e "\n${BLUE}🐳 Validating Docker Configuration...${NC}"
DOCKER_FILES=("compose.yaml" "docker/Dockerfile" "docker/scripts/entrypoint.sh")

for file in "${DOCKER_FILES[@]}"; do
    if [ -f "$file" ]; then
        print_status "SUCCESS" "Docker file $file exists"
    else
        print_status "ERROR" "Docker file $file is missing"
        exit 1
    fi
done

# Check if entrypoint script is executable
if [ -x "docker/scripts/entrypoint.sh" ]; then
    print_status "SUCCESS" "Entrypoint script is executable"
else
    print_status "WARNING" "Entrypoint script may need execute permissions"
fi

# Test autoloader
echo -e "\n${BLUE}🔄 Testing Autoloader...${NC}"
if php -r "require 'vendor/autoload.php'; echo 'Autoloader works';" > /dev/null 2>&1; then
    print_status "SUCCESS" "Composer autoloader is working"
else
    print_status "ERROR" "Composer autoloader failed"
    exit 1
fi

# Check for potential security issues
echo -e "\n${BLUE}🔒 Basic Security Check...${NC}"
if [ -f ".env" ] && grep -q "APP_SECRET.*changeme" .env 2>/dev/null; then
    print_status "WARNING" "Default APP_SECRET detected - change in production"
fi

# Summary
echo -e "\n${GREEN}================================================${NC}"
echo -e "${GREEN}🎉 Pre-Build Validation Completed Successfully!${NC}"
echo -e "${GREEN}================================================${NC}"

echo -e "\n${BLUE}📋 Validation Summary:${NC}"
echo "   ✅ PHP Environment: Compatible"
echo "   ✅ Dependencies: Valid"
echo "   ✅ Clean Architecture: Structure OK"
echo "   ✅ Syntax: No errors found"
echo "   ✅ Docker Config: Present"
echo "   ✅ Autoloader: Working"

echo -e "\n${GREEN}🚀 Ready for Docker build with:${NC}"
echo "   docker compose up --build -d"

echo -e "\n${YELLOW}💡 Tip: Run this script before every deployment${NC}"
