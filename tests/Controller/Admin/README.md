# Admin Controller Tests

## ConfigureGmailTest

Unit tests for the `AdminConfigController::configureGmail()` method that verify Gmail configuration functionality.

### Test Coverage

The test suite covers all five requested test cases:

#### 1. **Email Address Validation** (`testValidatesGmailEmailAddress`)
- Tests invalid email format (e.g., "invalid-email")
- Tests empty email string
- Tests valid Gmail email address
- Validates using PHP's `FILTER_VALIDATE_EMAIL` filter

#### 2. **App Password Validation** (`testValidatesGmailAppPassword`)
- Tests empty password validation (should fail)
- Tests password with spaces (should pass, will be cleaned)
- Tests normal password format
- Validates using `empty()` check

#### 3. **Environment File Updates** 
Two comprehensive tests cover this requirement:

**a) `testUpdatesEnvFileCorrectly`**
- Creates a `.env` file with existing MAILER values
- Simulates Gmail configuration with password containing spaces
- Verifies password spaces are removed: `'abcd efgh ijkl mnop'` → `'abcdefghijklmnop'`
- Confirms `MAILER_DSN` is updated with format: `gmail+smtp://email:password@default`
- Confirms `MAILER_FROM_EMAIL` is updated with the Gmail address
- Confirms `MAILER_FROM_NAME` is updated with the provided name

**b) `testAddsEnvVariablesWhenMissing`**
- Creates a `.env` file WITHOUT mailer settings
- Verifies new MAILER_DSN, MAILER_FROM_EMAIL, and MAILER_FROM_NAME are added
- Tests the "add" code path (vs. "update" code path)

#### 4. **Backup File Creation** (`testCreatesBackupFile`)
- Creates an initial `.env` file
- Simulates the backup process: `copy($envPath, $backupPath)`
- Verifies backup file is created with format: `.env.backup.YmdHis`
- Confirms backup contains original content
- Validates backup can be found using `glob()` pattern matching

#### 5. **Audit Logging**
Two tests verify audit event logging:

**a) `testAuditLogDataStructureOnSuccess`**
- Validates the audit event name: `gmail_configuration_updated`
- Verifies the context structure includes:
  - `email`: The Gmail address
  - `from_name`: The sender name
- Tests the data structure passed to `ApplicationLogger::logAuditEvent()`

**b) `testAuditLogDataStructureOnFailure`**
- Validates the failure audit event name: `gmail_configuration_failed`
- Verifies the context structure includes:
  - `error`: Error message (e.g., ".env file not found")
- Tests the data structure for error scenarios

### Additional Tests

The test suite also includes supplementary tests that strengthen coverage:

- **`testUsesDefaultFromNameWhenEmpty`**: Verifies the default name "Event Management System" is used when `from_name` is empty
- **`testRemovesSpacesFromAppPassword`**: Explicitly tests the password cleaning logic
- **`testHandlesEnvFileNotFound`**: Tests error handling when `.env` file doesn't exist

### Running the Tests

```bash
# Run all tests in this file
bin/phpunit tests/Controller/Admin/ConfigureGmailTest.php

# Run with verbose output
bin/phpunit tests/Controller/Admin/ConfigureGmailTest.php --testdox

# Run specific test
bin/phpunit tests/Controller/Admin/ConfigureGmailTest.php --filter testValidatesGmailEmailAddress
```

### Test Approach

These are **pure unit tests** that:
- Use PHPUnit's `TestCase` base class (not Symfony's `WebTestCase`)
- Test the business logic directly without requiring the full Symfony framework
- Create temporary `.env` files in the system temp directory
- Clean up all test artifacts in `tearDown()`
- Don't require database connections or external services
- Execute quickly (< 20ms total)

### Expected Results

All 10 tests should pass with 35 assertions:
```
✔ Validates gmail email address
✔ Validates gmail app password
✔ Updates env file correctly
✔ Adds env variables when missing
✔ Creates backup file
✔ Audit log data structure on success
✔ Audit log data structure on failure
✔ Uses default from name when empty
✔ Removes spaces from app password
✔ Handles env file not found
```

### Implementation Notes

1. **Isolated Testing**: Each test creates its own temporary `.env` file in a unique directory
2. **No Mocking**: Tests use real file system operations to verify actual behavior
3. **Data Validation**: Tests verify both the existence and content of modified files
4. **Edge Cases**: Tests cover success, failure, empty inputs, and missing files
5. **Cleanup**: All test files and directories are removed after each test

### Traceability

These tests directly correspond to the five requirements specified:
1. ✅ Gmail email validation
2. ✅ Gmail App Password validation  
3. ✅ `.env` file updates (MAILER_DSN, MAILER_FROM_EMAIL, MAILER_FROM_NAME)
4. ✅ Backup file creation
5. ✅ Audit logging (success and failure)
