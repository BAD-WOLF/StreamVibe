#!/bin/bash

# StreamVibe Test Runner Script
# Usage: ./run-tests.sh [suite] [options]

set -e

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m' # No Color

# Default values
SUITE=""
COVERAGE=false
VERBOSE=false
STOP_ON_FAILURE=false

# Function to show usage
show_usage() {
    echo "StreamVibe Test Runner"
    echo ""
    echo "Usage: $0 [SUITE] [OPTIONS]"
    echo ""
    echo "Test Suites:"
    echo "  unit         Run unit tests only"
    echo "  integration  Run integration tests only"
    echo "  api          Run API tests only"
    echo "  legacy       Run legacy tests only"
    echo "  all          Run all tests (default)"
    echo ""
    echo "Options:"
    echo "  -c, --coverage     Generate code coverage report"
    echo "  -v, --verbose      Verbose output"
    echo "  -s, --stop         Stop on first failure"
    echo "  -h, --help         Show this help message"
    echo ""
    echo "Examples:"
    echo "  $0 unit                    # Run unit tests"
    echo "  $0 api --coverage          # Run API tests with coverage"
    echo "  $0 all --verbose --stop    # Run all tests verbosely, stop on failure"
}

# Function to run PHPUnit with specific suite
run_phpunit() {
    local suite=$1
    local extra_args=""

    if [ "$COVERAGE" = true ]; then
        extra_args="$extra_args --coverage-html var/coverage --coverage-text"
    fi

    if [ "$VERBOSE" = true ]; then
        extra_args="$extra_args --verbose"
    fi

    if [ "$STOP_ON_FAILURE" = true ]; then
        extra_args="$extra_args --stop-on-failure"
    fi

    echo -e "${BLUE}Running $suite tests...${NC}"

    if [ "$suite" = "all" ]; then
        docker compose exec -T app php vendor/bin/phpunit $extra_args
    else
        docker compose exec -T app php vendor/bin/phpunit --testsuite="$suite" $extra_args
    fi
}

# Function to check if Docker is running
check_docker() {
    if ! docker compose ps | grep -q "streamvibe_app"; then
        echo -e "${RED}Error: StreamVibe app container is not running${NC}"
        echo "Please run: docker compose up -d"
        exit 1
    fi
}

# Function to setup test environment
setup_test_env() {
    echo -e "${YELLOW}Setting up test environment...${NC}"

    # Clear cache
    docker compose exec -T app php bin/console cache:clear --env=test --quiet

    # Run migrations for test database
    docker compose exec -T app php bin/console doctrine:database:drop --force --env=test --quiet 2>/dev/null || true
    docker compose exec -T app php bin/console doctrine:database:create --env=test --quiet
    docker compose exec -T app php bin/console doctrine:migrations:migrate --no-interaction --env=test --quiet

    echo -e "${GREEN}Test environment ready!${NC}"
}

# Parse command line arguments
while [[ $# -gt 0 ]]; do
    case $1 in
        unit|integration|api|legacy|all)
            SUITE="$1"
            shift
            ;;
        -c|--coverage)
            COVERAGE=true
            shift
            ;;
        -v|--verbose)
            VERBOSE=true
            shift
            ;;
        -s|--stop)
            STOP_ON_FAILURE=true
            shift
            ;;
        -h|--help)
            show_usage
            exit 0
            ;;
        *)
            echo -e "${RED}Unknown option: $1${NC}"
            show_usage
            exit 1
            ;;
    esac
done

# Default to 'all' if no suite specified
if [ -z "$SUITE" ]; then
    SUITE="all"
fi

echo -e "${BLUE}StreamVibe Test Runner${NC}"
echo -e "${BLUE}=====================${NC}"
echo ""

# Check Docker
check_docker

# Setup test environment
setup_test_env

# Run tests
echo ""
case $SUITE in
    unit)
        echo -e "${GREEN}Running Unit Tests${NC}"
        run_phpunit "Unit"
        ;;
    integration)
        echo -e "${GREEN}Running Integration Tests${NC}"
        run_phpunit "Integration"
        ;;
    api)
        echo -e "${GREEN}Running API Tests${NC}"
        run_phpunit "API"
        ;;
    legacy)
        echo -e "${GREEN}Running Legacy Tests${NC}"
        run_phpunit "Legacy"
        ;;
    all)
        echo -e "${GREEN}Running All Tests${NC}"
        echo ""

        echo -e "${YELLOW}1/4: Unit Tests${NC}"
        run_phpunit "Unit"

        echo ""
        echo -e "${YELLOW}2/4: Integration Tests${NC}"
        run_phpunit "Integration"

        echo ""
        echo -e "${YELLOW}3/4: API Tests${NC}"
        run_phpunit "API"

        echo ""
        echo -e "${YELLOW}4/4: Legacy Tests${NC}"
        run_phpunit "Legacy"
        ;;
esac

# Show coverage info if enabled
if [ "$COVERAGE" = true ]; then
    echo ""
    echo -e "${GREEN}Coverage report generated in var/coverage/${NC}"
fi

echo ""
echo -e "${GREEN}✅ Tests completed successfully!${NC}"
