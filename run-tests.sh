#!/bin/bash

# StreamVibe Test Runner Script
# Usage: ./run-tests.sh [suite] [options]
#
# NOTA (out/2026): roda no stack de teste isolado (compose.test.yaml), que
# sobe junto com o de dev sem conflito. Antes o script:
# - rodava no contêiner de dev (docker compose exec app) e apagava/recriava
#   o banco app_test no Postgres de dev a cada execução;
# - chamava as suítes "API" e "Legacy", que não existem no
#   phpunit.dist.xml (as suítes são Unit, Integration e Application) —
#   "all" parava no passo 3 com erro;
# - passava --verbose, opção que o PHPUnit 12 não tem mais.
# No stack de teste o banco roda em memória e começa vazio a cada subida
# (o entrypoint aplica as migrations), e o DAMA desfaz o que cada teste
# grava, então não há preparação de banco aqui.

set -e

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m' # No Color

# Always run from the project root (where compose.test.yaml is)
cd "$(dirname "${BASH_SOURCE[0]}")"

COMPOSE=(docker compose -f compose.test.yaml)

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
    echo "  api          Run API (Application) tests only"
    echo "  all          Run all tests (default)"
    echo ""
    echo "Options:"
    echo "  -c, --coverage     Generate code coverage report (var/coverage in the container)"
    echo "  -v, --verbose      Readable output, one line per test (--testdox)"
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
    local extra_args=()

    if [ "$COVERAGE" = true ]; then
        extra_args+=(--coverage-html var/coverage --coverage-text)
    fi

    if [ "$VERBOSE" = true ]; then
        extra_args+=(--testdox)
    fi

    if [ "$STOP_ON_FAILURE" = true ]; then
        extra_args+=(--stop-on-failure)
    fi

    echo -e "${BLUE}Running $suite tests...${NC}"

    "${COMPOSE[@]}" exec -T app vendor/bin/phpunit --testsuite="$suite" "${extra_args[@]}"
}

# Function to check if Docker is running
check_docker() {
    if ! docker info > /dev/null 2>&1; then
        echo -e "${RED}Error: Docker is not running${NC}"
        exit 1
    fi
}

# Function to start the isolated test stack (no-op when it is already up).
# --wait returns only when the app is healthy, i.e. after the entrypoint
# finished the migrations and the cache:clear: running PHPUnit before that
# races with the cache being rebuilt. The image is built only when it does
# not exist yet; after changing phpunit.dist.xml, .env* or composer.*:
#   docker compose -f compose.test.yaml up --build -d --wait
start_test_stack() {
    echo -e "${YELLOW}Starting the test stack (compose.test.yaml)...${NC}"
    "${COMPOSE[@]}" up -d --wait
    echo -e "${GREEN}Test environment ready!${NC}"
}

# Parse command line arguments
while [[ $# -gt 0 ]]; do
    case $1 in
        unit|integration|api|all)
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

# Start the test stack
start_test_stack

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
        run_phpunit "Application"
        ;;
    all)
        echo -e "${GREEN}Running All Tests${NC}"
        echo ""

        echo -e "${YELLOW}1/3: Unit Tests${NC}"
        run_phpunit "Unit"

        echo ""
        echo -e "${YELLOW}2/3: Integration Tests${NC}"
        run_phpunit "Integration"

        echo ""
        echo -e "${YELLOW}3/3: API Tests${NC}"
        run_phpunit "Application"
        ;;
esac

# Show coverage info if enabled
if [ "$COVERAGE" = true ]; then
    echo ""
    echo -e "${GREEN}Coverage report generated in var/coverage/ (inside the test container)${NC}"
fi

echo ""
echo -e "${GREEN}✅ Tests completed successfully!${NC}"
