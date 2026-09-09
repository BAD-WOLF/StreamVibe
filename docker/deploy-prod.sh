#!/usr/bin/env bash

# StreamVibe Production Deployment Script
# This script provides convenient commands for Docker production deployment

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

# Function to check environment variables
check_env() {
    local missing_vars=()

    if [[ -z "${POSTGRES_PASSWORD:-}" ]]; then
        missing_vars+=("POSTGRES_PASSWORD")
    fi

    if [[ -z "${APP_SECRET:-}" ]]; then
        missing_vars+=("APP_SECRET")
    fi

    if [[ -z "${TMDB_TOKEN:-}" ]]; then
        print_status "WARNING" "TMDB_TOKEN not set - movie functionality will be limited"
    fi

    if [[ ${#missing_vars[@]} -gt 0 ]]; then
        print_status "ERROR" "Missing required environment variables:"
        for var in "${missing_vars[@]}"; do
            echo "  - $var"
        done
        print_status "INFO" "Please set these variables in .env.prod.local or export them"
        exit 1
    fi

    print_status "SUCCESS" "Environment variables validated"
}

# Function to backup database
backup_database() {
    local backup_file="backup_$(date +%Y%m%d_%H%M%S).sql"
    print_status "STEP" "Creating database backup: $backup_file"

    if docker compose -f compose.prod.yaml ps database | grep -q "Up"; then
        docker compose -f compose.prod.yaml exec -T database pg_dump -U ${POSTGRES_USER:-streamvibe} ${POSTGRES_DB:-streamvibe_prod} > "./backups/$backup_file"
        print_status "SUCCESS" "Database backup created: ./backups/$backup_file"
    else
        print_status "WARNING" "Database container not running - skipping backup"
    fi
}

# Function to show usage
show_usage() {
    echo -e "${PURPLE}StreamVibe Production Deployment Script${NC}"
    echo ""
    echo "Usage: $0 [COMMAND]"
    echo ""
    echo "Commands:"
    echo "  deploy       Deploy to production (build + up)"
    echo "  up           Start all production services"
    echo "  down         Stop all services gracefully"
    echo "  restart      Restart all services"
    echo "  build        Build application container for production"
    echo "  rebuild      Force rebuild application container"
    echo "  logs         Show logs from all services"
    echo "  logs-app     Show logs from app service only"
    echo "  logs-nginx   Show logs from nginx service"
    echo "  status       Show status of all services"
    echo "  health       Run comprehensive health checks"
    echo "  backup       Create database backup"
    echo "  restore      Restore database from backup"
    echo "  migrate      Run database migrations"
    echo "  scale        Scale application instances"
    echo "  update       Update application (backup + rebuild + migrate)"
    echo "  rollback     Rollback to previous version"
    echo "  monitoring   Show resource usage"
    echo "  security     Run security checks"
    echo "  ssl          Setup SSL certificates"
    echo "  cleanup      Clean up old images and containers"
    echo ""
    echo "Examples:"
    echo "  $0 deploy           # Full production deployment"
    echo "  $0 update           # Update with backup and migrations"
    echo "  $0 scale app=3      # Scale app service to 3 replicas"
    echo "  $0 backup           # Create database backup"
    echo ""
    echo "Environment Variables Required:"
    echo "  POSTGRES_PASSWORD   # Database password"
    echo "  APP_SECRET          # Symfony app secret"
    echo "  TMDB_TOKEN          # TMDB API token (optional)"
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
    COMPOSE_CMD="docker-compose -f compose.prod.yaml"
else
    COMPOSE_CMD="docker compose -f compose.prod.yaml"
fi

# Create backup directory if it doesn't exist
mkdir -p ./backups

# Main command handler
case "${1:-}" in
    "deploy")
        print_status "HEADER" "Deploying StreamVibe to Production"
        check_env

        print_status "STEP" "Creating backup..."
        backup_database

        print_status "STEP" "Building application..."
        $COMPOSE_CMD build app

        print_status "STEP" "Starting services..."
        $COMPOSE_CMD up -d

        print_status "STEP" "Running migrations..."
        sleep 10  # Wait for services to be ready
        $COMPOSE_CMD exec app php bin/console doctrine:migrations:migrate --no-interaction

        print_status "STEP" "Running health checks..."
        sleep 5
        if curl -f -s http://localhost:8000/api/images/sizes > /dev/null; then
            print_status "SUCCESS" "Production deployment completed successfully!"
            print_status "INFO" "Application: http://localhost"
            print_status "INFO" "API Health: http://localhost/health"
        else
            print_status "ERROR" "Health check failed - please check logs"
        fi
        ;;

    "up")
        print_status "HEADER" "Starting StreamVibe Production Services"
        check_env
        $COMPOSE_CMD up -d
        print_status "SUCCESS" "Production services started"
        print_status "INFO" "Application: http://localhost"
        ;;

    "down")
        print_status "HEADER" "Stopping StreamVibe Production Services"
        print_status "STEP" "Gracefully stopping services..."
        $COMPOSE_CMD down --timeout 30
        print_status "SUCCESS" "Production services stopped"
        ;;

    "restart")
        print_status "HEADER" "Restarting StreamVibe Production Services"
        $COMPOSE_CMD down --timeout 30
        $COMPOSE_CMD up -d
        print_status "SUCCESS" "Production services restarted"
        ;;

    "build")
        print_status "HEADER" "Building StreamVibe Application for Production"
        check_env
        $COMPOSE_CMD build app
        print_status "SUCCESS" "Production build completed"
        ;;

    "rebuild")
        print_status "HEADER" "Force Rebuilding StreamVibe Application"
        check_env
        $COMPOSE_CMD build --no-cache app
        print_status "SUCCESS" "Production rebuild completed"
        ;;

    "logs")
        print_status "HEADER" "Showing All Production Service Logs"
        $COMPOSE_CMD logs -f --tail=100
        ;;

    "logs-app")
        print_status "HEADER" "Showing Application Logs"
        $COMPOSE_CMD logs -f --tail=100 app
        ;;

    "logs-nginx")
        print_status "HEADER" "Showing Nginx Logs"
        $COMPOSE_CMD logs -f --tail=100 nginx
        ;;

    "status")
        print_status "HEADER" "Production Service Status"
        $COMPOSE_CMD ps
        echo ""
        docker stats --no-stream --format "table {{.Name}}\t{{.CPUPerc}}\t{{.MemUsage}}\t{{.NetIO}}\t{{.BlockIO}}"
        ;;

    "health")
        print_status "HEADER" "Running Comprehensive Health Checks"

        # Check services
        services=("app" "database" "nginx" "mailer")
        for service in "${services[@]}"; do
            if $COMPOSE_CMD ps --services --filter "status=running" | grep -q "$service"; then
                print_status "SUCCESS" "$service service is running"
            else
                print_status "ERROR" "$service service is not running"
            fi
        done

        # Check endpoints
        endpoints=(
            "http://localhost/health:Health endpoint"
            "http://localhost/api/images/sizes:API endpoint"
        )

        for endpoint in "${endpoints[@]}"; do
            url=$(echo "$endpoint" | cut -d: -f1)
            desc=$(echo "$endpoint" | cut -d: -f2)

            if curl -f -s --max-time 10 "$url" > /dev/null; then
                print_status "SUCCESS" "$desc is responding"
            else
                print_status "ERROR" "$desc is not responding"
            fi
        done

        # Check database connectivity
        if $COMPOSE_CMD exec -T database pg_isready -U ${POSTGRES_USER:-streamvibe} > /dev/null 2>&1; then
            print_status "SUCCESS" "Database is accepting connections"
        else
            print_status "ERROR" "Database is not accepting connections"
        fi
        ;;

    "backup")
        print_status "HEADER" "Creating Production Database Backup"
        backup_database
        ;;

    "restore")
        if [[ -z "${2:-}" ]]; then
            print_status "ERROR" "Please specify backup file: $0 restore <backup_file>"
            exit 1
        fi

        backup_file="$2"
        if [[ ! -f "$backup_file" ]]; then
            print_status "ERROR" "Backup file not found: $backup_file"
            exit 1
        fi

        print_status "HEADER" "Restoring Database from Backup"
        print_status "WARNING" "This will overwrite the current database"
        read -p "Are you sure? (y/N): " -n 1 -r
        echo
        if [[ $REPLY =~ ^[Yy]$ ]]; then
            print_status "STEP" "Restoring from: $backup_file"
            cat "$backup_file" | $COMPOSE_CMD exec -T database psql -U ${POSTGRES_USER:-streamvibe} -d ${POSTGRES_DB:-streamvibe_prod}
            print_status "SUCCESS" "Database restored from backup"
        else
            print_status "INFO" "Restore cancelled"
        fi
        ;;

    "migrate")
        print_status "HEADER" "Running Production Database Migrations"
        $COMPOSE_CMD exec app php bin/console doctrine:migrations:migrate --no-interaction
        print_status "SUCCESS" "Migrations completed"
        ;;

    "scale")
        if [[ -z "${2:-}" ]]; then
            print_status "ERROR" "Please specify scale: $0 scale app=3"
            exit 1
        fi

        print_status "HEADER" "Scaling Production Services"
        $COMPOSE_CMD up -d --scale "$2"
        print_status "SUCCESS" "Scaling completed"
        ;;

    "update")
        print_status "HEADER" "Updating Production Application"
        check_env

        print_status "STEP" "Creating backup..."
        backup_database

        print_status "STEP" "Rebuilding application..."
        $COMPOSE_CMD build --no-cache app

        print_status "STEP" "Restarting services..."
        $COMPOSE_CMD up -d

        print_status "STEP" "Running migrations..."
        sleep 10
        $COMPOSE_CMD exec app php bin/console doctrine:migrations:migrate --no-interaction

        print_status "STEP" "Clearing cache..."
        $COMPOSE_CMD exec app php bin/console cache:clear --env=prod

        print_status "SUCCESS" "Production update completed"
        ;;

    "rollback")
        print_status "HEADER" "Rolling Back to Previous Version"
        print_status "WARNING" "This will restore from the latest backup"

        latest_backup=$(ls -t ./backups/*.sql 2>/dev/null | head -n1)
        if [[ -z "$latest_backup" ]]; then
            print_status "ERROR" "No backup files found"
            exit 1
        fi

        print_status "INFO" "Latest backup: $latest_backup"
        read -p "Proceed with rollback? (y/N): " -n 1 -r
        echo
        if [[ $REPLY =~ ^[Yy]$ ]]; then
            $0 restore "$latest_backup"
            print_status "SUCCESS" "Rollback completed"
        else
            print_status "INFO" "Rollback cancelled"
        fi
        ;;

    "monitoring")
        print_status "HEADER" "Production Resource Monitoring"
        echo ""
        print_status "INFO" "Container Resource Usage:"
        docker stats --no-stream --format "table {{.Name}}\t{{.CPUPerc}}\t{{.MemUsage}}\t{{.NetIO}}\t{{.BlockIO}}"

        echo ""
        print_status "INFO" "Disk Usage:"
        df -h

        echo ""
        print_status "INFO" "Service Uptime:"
        $COMPOSE_CMD ps --format "table {{.Name}}\t{{.Status}}\t{{.Ports}}"
        ;;

    "security")
        print_status "HEADER" "Running Security Checks"

        # Check for exposed ports
        print_status "STEP" "Checking exposed ports..."
        netstat -tlnp 2>/dev/null | grep -E ":(80|443|5432|1025|8000|8025)" || true

        # Check Docker security
        print_status "STEP" "Checking Docker security..."
        docker run --rm -it --name sec-check \
            -v /var/run/docker.sock:/var/run/docker.sock \
            aquasec/docker-bench-security:latest || true

        print_status "INFO" "Security check completed"
        ;;

    "ssl")
        print_status "HEADER" "Setting Up SSL Certificates"
        print_status "INFO" "Creating SSL directory..."
        mkdir -p ./docker/ssl

        print_status "WARNING" "Please place your SSL certificates in ./docker/ssl/"
        print_status "INFO" "Expected files:"
        echo "  - cert.pem (certificate)"
        echo "  - key.pem (private key)"
        echo "  - chain.pem (certificate chain, optional)"

        print_status "INFO" "Then uncomment HTTPS configuration in docker/nginx/default.conf"
        ;;

    "cleanup")
        print_status "HEADER" "Cleaning Up Docker Resources"
        print_status "WARNING" "This will remove unused images and containers"
        read -p "Proceed with cleanup? (y/N): " -n 1 -r
        echo
        if [[ $REPLY =~ ^[Yy]$ ]]; then
            docker system prune -a -f
            docker volume prune -f
            print_status "SUCCESS" "Cleanup completed"
        else
            print_status "INFO" "Cleanup cancelled"
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
