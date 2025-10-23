#!/bin/bash
# CI test runner for Linux/Unix systems
# - Sets env vars required for test endpoints
# - Verifies database connection and schema
# - Runs PHP test scripts and fails on any error

set -e  # Exit on any error

BASE_URL=${1:-"http://localhost/backoffice_system/public"}

echo "Running CI tests against $BASE_URL"

# Export environment variables for PHP CLI and web server tests
export TEST_ALLOW_LOGIN=true
export APP_ENV=testing

# Use defaults if database env vars not set
if [ -z "$TEST_DB_HOST" ]; then
    export TEST_DB_HOST="127.0.0.1"
    export TEST_DB_NAME="travel_backoffice_test"
    export TEST_DB_USER="root"
    export TEST_DB_PASS=""
fi

# Check if PHP is available
if ! command -v php &> /dev/null; then
    echo "Error: php not found in PATH" >&2
    exit 2
fi

# Save current directory and change to project root
SCRIPT_DIR="$( cd "$( dirname "${BASH_SOURCE[0]}" )" &> /dev/null && pwd )"
cd "$SCRIPT_DIR/.."

# Check database connection and schema
echo "Checking database..."
if ! php scripts/check_test_db.php; then
    echo "Error: Database check failed" >&2
    exit $?
fi

# Run role tests
echo "Running role tests..."
if ! php scripts/role_test.php; then
    echo "Error: Role tests failed" >&2
    exit $?
fi

# Run simple test
echo "Running simple test..."
if ! php scripts/simple_test.php; then
    echo "Error: Simple test failed" >&2
    exit $?
fi

# Check for stale sessions
echo "Checking session cleanup..."
STALE_SESSIONS=$(mysql -N -h "$TEST_DB_HOST" -u "$TEST_DB_USER" -p"$TEST_DB_PASS" "$TEST_DB_NAME" \
    -e "SELECT COUNT(*) FROM sessions WHERE last_activity < DATE_SUB(NOW(), INTERVAL 24 HOUR)")

if [ "$STALE_SESSIONS" -gt 0 ]; then
    echo "Warning: Found $STALE_SESSIONS stale sessions" >&2
fi

echo "All CI tests passed"