# Test Environment Setup

This document describes the environment variables and setup required for running the test suite.

## Required Environment Variables

### Test Access Control

- `TEST_ALLOW_LOGIN`: Set to "true" to enable test endpoints
- `APP_ENV`: Set to "testing" for test mode

### Database Configuration

- `TEST_DB_HOST`: Test database host (default: "127.0.0.1")
- `TEST_DB_NAME`: Test database name (default: "travel_backoffice_test")
- `TEST_DB_USER`: Test database user (default: "root")
- `TEST_DB_PASS`: Test database password (default: "")

## Local Development Setup

1. Create test database:

```sql
CREATE DATABASE travel_backoffice_test;
```

2. Import schema:

```bash
mysql -u root travel_backoffice_test < db/schema.sql
```

3. Set environment variables:

```powershell
# PowerShell
$env:TEST_ALLOW_LOGIN = "true"
$env:APP_ENV = "testing"
```

```bash
# Bash
export TEST_ALLOW_LOGIN=true
export APP_ENV=testing
```

4. Run tests:

```powershell
.\scripts\run_ci_tests.ps1
```

## CI Environment

The GitHub Actions workflow automatically:

1. Sets up MySQL 8.0
2. Creates test database
3. Imports schema
4. Sets required environment variables
5. Runs database checks
6. Executes test suite

See `.github/workflows/ci.yml` for the complete CI setup.
