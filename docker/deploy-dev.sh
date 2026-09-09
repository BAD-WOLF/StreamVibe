#!/usr/bin/env bash

# StreamVibe Development Deployment Script
# This script provides convenient commands for Docker development workflow

set -e

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
PURPLE='\033[0;35m'
CYAN='\033[0;36m'
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
        "HEADER")
            echo -e "${PURPLE}🚀 $message${NC}"
            ;;
        "STEP")
            echo -e "${CYAN}📋 $message${NC}"
            ;;
    esac
}

# Function to check if Docker is running
check_docker() {
    if ! docker info > /dev/null 2>&1; then
        print_status "ERROR" "Docker is not running. Please start Docker first."
        exit 1
    fi
    print_status "SUCCESS" "Docker is running"
}

# Function to check if docker-compose is available
check_compose() {
    if ! command -v docker-compose > /dev/null 2>&1 && ! docker compose version > /dev/null 2>&1; then
        print_status "ERROR" "Docker Compose is not available."
        exit 1
    fi
    print_status "SUCCESS" "Docker Compose is available"
}

# Function to show usage
show_usage() {
    echo -e "${PURPLE}StreamVibe Development Deployment Script${NC}"
    echo ""
    echo "Usage: $0 [COMMAND]"
    echo ""
    echo "Commands:"
    echo "  up           Start all services in development mode"
    echo "  down         Stop all services"
    echo "  restart      Restart all services"
    echo "  build        Build application container"
    echo "  rebuild      Force rebuild application container"
    echo "  logs         Show logs from all services"
    echo "  logs-app     Show logs from app service only"
    echo "  logs-db      Show logs from database service only"
    echo "  shell        Open shell in application container"
    echo "  db-shell     Open PostgreSQL shell"
    echo "  status       Show status of all services"
    echo "  clean        Clean up containers, networks and volumes"
    echo "  health       Run health checks"
    echo "  migrate      Run database migrations"
    echo "  fixtures     Load database fixtures"
    echo "  cache-clear  Clear application cache"
    echo "  test         Run tests"
    echo "  install      Install/update composer dependencies"
    echo "  reset        Full reset - stop, clean, and restart"
    echo ""
    echo "Examples:"
    echo "  $0 up        # Start development environment"
    echo "  $0 logs-app  # View application logs"
    echo "  $0 shell     # Open shell in app container"
    echo "  $0 reset     # Full environment reset"
}

# Get the directory of this script
SCRIPT_DIR="$( cd "$( dirname "${BASH_SOURCE[0]}" )" &> /dev/null && pwd )"
PROJECT_DIR="$(dirname "$SCRIPT_DIR")"

cd "$PROJECT_DIR" || exit 1

# Check prerequisites
check_docker
check_compose

# Determine compose command
if command -v docker-compose > /dev/null 2>&1; then
    COMPOSE_CMD="docker-compose"
else
    COMPOSE_CMD="docker compose"
fi

# Main command handler
case "${1:-}" in
    "up")
        print_status "HEADER" "Starting StreamVibe Development Environment"
        print_status "STEP" "Starting services..."
        $COMPOSE_CMD up -d
        print_status "SUCCESS" "Services started successfully"
        print_status "INFO" "Application: http://localhost:8000"
        print_status "INFO" "Mailpit UI: http://localhost:8025"
        print_status "INFO" "Database: localhost:5432 (app/app/Gk7xRz92wM)"
        ;;

    "down")
        print_status "HEADER" "Stopping StreamVibe Services"
        $COMPOSE_CMD down
        print_status "SUCCESS" "Services stopped"
        ;;

    "restart")
        print_status "HEADER" "Restarting StreamVibe Services"
        $COMPOSE_CMD down
        $COMPOSE_CMD up -d
        print_status "SUCCESS" "Services restarted"
        ;;

    "build")
        print_status "HEADER" "Building StreamVibe Application"
        $COMPOSE_CMD build app
        print_status "SUCCESS" "Build completed"
        ;;

    "rebuild")
        print_status "HEADER" "Force Rebuilding StreamVibe Application"
        $COMPOSE_CMD build --no-cache app
        print_status "SUCCESS" "Rebuild completed"
        ;;

    "logs")
        print_status "HEADER" "Showing All Service Logs"
        $COMPOSE_CMD logs -f
        ;;

    "logs-app")
        print_status "HEADER" "Showing Application Logs"
        $COMPOSE_CMD logs -f app
        ;;

    "logs-db")
        print_status "HEADER" "Showing Database Logs"
        $COMPOSE_CMD logs -f database
        ;;

    "shell")
        print_status "HEADER" "Opening Shell in Application Container"
        $COMPOSE_CMD exec app bash
        ;;

    "db-shell")
        print_status "HEADER" "Opening PostgreSQL Shell"
        $COMPOSE_CMD exec database psql -U app -d app
        ;;

    "status")
        print_status "HEADER" "Service Status"
        $COMPOSE_CMD ps
        ;;

    "clean")
        print_status "HEADER" "Cleaning Up Docker Resources"
        print_status "WARNING" "This will remove containers, networks and volumes"
        read -p "Are you sure? (y/N): " -n 1 -r
        echo
        if [[ $REPLY =~ ^[Yy]$ ]]; then
            $COMPOSE_CMD down -v --remove-orphans
            docker system prune -f
            print_status "SUCCESS" "Cleanup completed"
        else
            print_status "INFO" "Cleanup cancelled"
        fi
        ;;

    "health")
        print_status "HEADER" "Running Health Checks"

        # Check if services are running
        if $COMPOSE_CMD ps --services --filter "status=running" | grep -q app; then
            print_status "SUCCESS" "App service is running"
        else
            print_status "ERROR" "App service is not running"
        fi

        if $COMPOSE_CMD ps --services --filter "status=running" | grep -q database; then
            print_status "SUCCESS" "Database service is running"
        else
            print_status "ERROR" "Database service is not running"
        fi

        # Test API endpoint
        if curl -f -s http://localhost:8000/api/images/sizes > /dev/null; then
            print_status "SUCCESS" "API endpoint is responding"
        else
            print_status "ERROR" "API endpoint is not responding"
        fi
        ;;

    "migrate")
        print_status "HEADER" "Running Database Migrations"
        $COMPOSE_CMD exec app php bin/console doctrine:migrations:migrate --no-interaction
        print_status "SUCCESS" "Migrations completed"
        ;;

    "fixtures")
        print_status "HEADER" "Loading Database Fixtures"
        $COMPOSE_CMD exec app php bin/console doctrine:fixtures:load --no-interaction
        print_status "SUCCESS" "Fixtures loaded"
        ;;

    "cache-clear")
        print_status "HEADER" "Clearing Application Cache"
        $COMPOSE_CMD exec app php bin/console cache:clear
        print_status "SUCCESS" "Cache cleared"
        ;;

    "test")
        print_status "HEADER" "Running Tests"
        $COMPOSE_CMD exec app php bin/phpunit
        ;;

    "install")
        print_status "HEADER" "Installing/Updating Composer Dependencies"
        $COMPOSE_CMD exec app composer install
        print_status "SUCCESS" "Dependencies updated"
        ;;

    "reset")
        print_status "HEADER" "Full Environment Reset"
        print_status "WARNING" "This will stop services, clean up, rebuild and restart"
        read -p "Are you sure? (y/N): " -n 1 -r
        echo
        if [[ $REPLY =~ ^[Yy]$ ]]; then
            print_status "STEP" "Stopping services..."
            $COMPOSE_CMD down -v --remove-orphans

            print_status "STEP" "Cleaning up..."
            docker system prune -f

            print_status "STEP" "Rebuilding application..."
            $COMPOSE_CMD build --no-cache app

            print_status "STEP" "Starting services..."
            $COMPOSE_CMD up -d

            print_status "SUCCESS" "Environment reset completed"
            print_status "INFO" "Application: http://localhost:8000"
            print_status "INFO" "Mailpit UI: http://localhost:8025"
        else
            print_status "INFO" "Reset cancelled"
        fi
        ;;

    "help"|"-h"|"--help"|"")
        show_usage
        ;;

    *)
        print_status "ERROR" "Unknown command: $1"
        echo ""
        show_usage
        exit 1
        ;;
esac
