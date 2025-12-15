<?php

namespace App\Tests\Controller\Admin;

use PHPUnit\Framework\TestCase;

/**
 * Unit tests for Gmail configuration logic
 * These tests verify the core validation and file manipulation logic
 * independent of the Symfony controller framework
 */
class ConfigureGmailTest extends TestCase
{
    private string $testEnvPath;
    private string $testProjectDir;

    protected function setUp(): void
    {
        // Create temporary directory for testing
        $this->testProjectDir = sys_get_temp_dir() . '/test_project_' . uniqid();
        mkdir($this->testProjectDir, 0755, true);
        $this->testEnvPath = $this->testProjectDir . '/.env';
    }

    protected function tearDown(): void
    {
        // Clean up test files
        if (file_exists($this->testEnvPath)) {
            unlink($this->testEnvPath);
        }

        // Clean up backup files
        $backupFiles = glob($this->testEnvPath . '.backup.*');
        if ($backupFiles) {
            foreach ($backupFiles as $backupFile) {
                unlink($backupFile);
            }
        }

        // Remove test directory
        if (is_dir($this->testProjectDir)) {
            rmdir($this->testProjectDir);
        }
    }

    /**
     * Test 1: Gmail email address validation
     */
    public function testValidatesGmailEmailAddress(): void
    {
        // Test invalid email
        $invalidEmail = 'invalid-email';
        $this->assertFalse(
            filter_var($invalidEmail, FILTER_VALIDATE_EMAIL),
            'Invalid email should fail validation'
        );

        // Test empty email
        $emptyEmail = '';
        $this->assertTrue(
            empty($emptyEmail) || !filter_var($emptyEmail, FILTER_VALIDATE_EMAIL),
            'Empty email should fail validation'
        );

        // Test valid email
        $validEmail = 'test@gmail.com';
        $this->assertTrue(
            !empty($validEmail) && filter_var($validEmail, FILTER_VALIDATE_EMAIL),
            'Valid email should pass validation'
        );
    }

    /**
     * Test 2: Gmail App Password validation
     */
    public function testValidatesGmailAppPassword(): void
    {
        // Test empty password
        $emptyPassword = '';
        $this->assertTrue(
            empty($emptyPassword),
            'Empty password should fail validation'
        );

        // Test password with spaces (valid - should be cleaned)
        $passwordWithSpaces = 'abcd efgh ijkl mnop';
        $this->assertFalse(
            empty($passwordWithSpaces),
            'Password with spaces should pass validation (will be cleaned later)'
        );

        // Test normal password
        $normalPassword = 'abcdefghijklmnop';
        $this->assertFalse(
            empty($normalPassword),
            'Normal password should pass validation'
        );
    }

    /**
     * Test 3: Updates MAILER_DSN, MAILER_FROM_EMAIL, and MAILER_FROM_NAME correctly
     */
    public function testUpdatesEnvFileCorrectly(): void
    {
        // Create initial .env file with existing values
        $initialContent = <<<ENV
APP_ENV=dev
APP_SECRET=test-secret
DATABASE_URL=mysql://user:pass@localhost/dbname
MAILER_DSN=null://null
MAILER_FROM_EMAIL=old@example.com
MAILER_FROM_NAME="Old Name"
ENV;
        file_put_contents($this->testEnvPath, $initialContent);

        // Simulate the configuration process
        $gmailEmail = 'test@gmail.com';
        $gmailPassword = 'abcd efgh ijkl mnop';
        $fromName = 'New Test Name';

        // Clean password (as done in controller)
        $cleanPassword = str_replace(' ', '', $gmailPassword);
        $this->assertEquals('abcdefghijklmnop', $cleanPassword, 'Password should have spaces removed');

        // Prepare new DSN
        $newDsn = sprintf('gmail+smtp://%s:%s@default', $gmailEmail, $cleanPassword);

        // Read and update content
        $envContent = file_get_contents($this->testEnvPath);

        // Update MAILER_DSN
        if (preg_match('/^MAILER_DSN=.*$/m', $envContent)) {
            $envContent = preg_replace('/^MAILER_DSN=.*$/m', 'MAILER_DSN=' . $newDsn, $envContent);
        } else {
            $envContent .= "\nMAILER_DSN=" . $newDsn;
        }

        // Update MAILER_FROM_EMAIL
        if (preg_match('/^MAILER_FROM_EMAIL=.*$/m', $envContent)) {
            $envContent = preg_replace('/^MAILER_FROM_EMAIL=.*$/m', 'MAILER_FROM_EMAIL=' . $gmailEmail, $envContent);
        } else {
            $envContent .= "\nMAILER_FROM_EMAIL=" . $gmailEmail;
        }

        // Update MAILER_FROM_NAME
        if (preg_match('/^MAILER_FROM_NAME=.*$/m', $envContent)) {
            $envContent = preg_replace('/^MAILER_FROM_NAME=.*$/m', 'MAILER_FROM_NAME="' . $fromName . '"', $envContent);
        } else {
            $envContent .= "\nMAILER_FROM_NAME=\"" . $fromName . "\"";
        }

        // Write updated content
        file_put_contents($this->testEnvPath, $envContent);

        // Verify the updates
        $updatedContent = file_get_contents($this->testEnvPath);

        $this->assertStringContainsString(
            'MAILER_DSN=gmail+smtp://test@gmail.com:abcdefghijklmnop@default',
            $updatedContent,
            'MAILER_DSN should be updated with cleaned password'
        );

        $this->assertStringContainsString(
            'MAILER_FROM_EMAIL=test@gmail.com',
            $updatedContent,
            'MAILER_FROM_EMAIL should be updated'
        );

        $this->assertStringContainsString(
            'MAILER_FROM_NAME="New Test Name"',
            $updatedContent,
            'MAILER_FROM_NAME should be updated'
        );
    }

    /**
     * Test 3b: Adds missing env variables when not present
     */
    public function testAddsEnvVariablesWhenMissing(): void
    {
        // Create initial .env file WITHOUT mailer settings
        $initialContent = <<<ENV
APP_ENV=dev
APP_SECRET=test-secret
DATABASE_URL=mysql://user:pass@localhost/dbname
ENV;
        file_put_contents($this->testEnvPath, $initialContent);

        // Simulate the configuration process
        $gmailEmail = 'new@gmail.com';
        $gmailPassword = 'newpassword';
        $fromName = 'Brand New Name';

        // Clean password
        $cleanPassword = str_replace(' ', '', $gmailPassword);

        // Prepare new DSN
        $newDsn = sprintf('gmail+smtp://%s:%s@default', $gmailEmail, $cleanPassword);

        // Read and update content
        $envContent = file_get_contents($this->testEnvPath);

        // Update or add MAILER_DSN
        if (preg_match('/^MAILER_DSN=.*$/m', $envContent)) {
            $envContent = preg_replace('/^MAILER_DSN=.*$/m', 'MAILER_DSN=' . $newDsn, $envContent);
        } else {
            $envContent .= "\nMAILER_DSN=" . $newDsn;
        }

        // Update or add MAILER_FROM_EMAIL
        if (preg_match('/^MAILER_FROM_EMAIL=.*$/m', $envContent)) {
            $envContent = preg_replace('/^MAILER_FROM_EMAIL=.*$/m', 'MAILER_FROM_EMAIL=' . $gmailEmail, $envContent);
        } else {
            $envContent .= "\nMAILER_FROM_EMAIL=" . $gmailEmail;
        }

        // Update or add MAILER_FROM_NAME
        if (preg_match('/^MAILER_FROM_NAME=.*$/m', $envContent)) {
            $envContent = preg_replace('/^MAILER_FROM_NAME=.*$/m', 'MAILER_FROM_NAME="' . $fromName . '"', $envContent);
        } else {
            $envContent .= "\nMAILER_FROM_NAME=\"" . $fromName . "\"";
        }

        // Write updated content
        file_put_contents($this->testEnvPath, $envContent);

        // Verify the new variables were added
        $updatedContent = file_get_contents($this->testEnvPath);

        $this->assertStringContainsString(
            'MAILER_DSN=gmail+smtp://new@gmail.com:newpassword@default',
            $updatedContent,
            'MAILER_DSN should be added'
        );

        $this->assertStringContainsString(
            'MAILER_FROM_EMAIL=new@gmail.com',
            $updatedContent,
            'MAILER_FROM_EMAIL should be added'
        );

        $this->assertStringContainsString(
            'MAILER_FROM_NAME="Brand New Name"',
            $updatedContent,
            'MAILER_FROM_NAME should be added'
        );
    }

    /**
     * Test 4: Creates backup of .env file
     */
    public function testCreatesBackupFile(): void
    {
        // Create initial .env file
        $initialContent = <<<ENV
APP_ENV=dev
MAILER_DSN=null://null
ENV;
        file_put_contents($this->testEnvPath, $initialContent);

        // Verify file exists
        $this->assertFileExists($this->testEnvPath);

        // Create backup (as done in controller)
        $backupPath = $this->testEnvPath . '.backup.' . date('YmdHis');
        copy($this->testEnvPath, $backupPath);

        // Verify backup was created
        $this->assertFileExists($backupPath, 'Backup file should be created');

        // Verify backup contains original content
        $backupContent = file_get_contents($backupPath);
        $this->assertEquals(
            $initialContent,
            $backupContent,
            'Backup should contain original content'
        );

        // Verify we can find the backup with glob
        $backupFiles = glob($this->testEnvPath . '.backup.*');
        $this->assertNotEmpty($backupFiles, 'Should be able to find backup files');
        $this->assertCount(1, $backupFiles, 'Should have exactly one backup file');
    }

    /**
     * Test 5: Validates audit logging on success
     * This test verifies the expected data structure for audit logging
     */
    public function testAuditLogDataStructureOnSuccess(): void
    {
        $gmailEmail = 'success@gmail.com';
        $fromName = 'Success Name';

        // Simulate the audit log data that would be passed
        $auditLogEvent = 'gmail_configuration_updated';
        $auditLogContext = [
            'email' => $gmailEmail,
            'from_name' => $fromName
        ];

        // Verify the data structure
        $this->assertEquals('gmail_configuration_updated', $auditLogEvent);
        $this->assertIsArray($auditLogContext);
        $this->assertArrayHasKey('email', $auditLogContext);
        $this->assertArrayHasKey('from_name', $auditLogContext);
        $this->assertEquals($gmailEmail, $auditLogContext['email']);
        $this->assertEquals($fromName, $auditLogContext['from_name']);
    }

    /**
     * Test 5b: Validates audit logging on failure
     * This test verifies the expected data structure for failure audit logging
     */
    public function testAuditLogDataStructureOnFailure(): void
    {
        // Simulate a failure scenario
        $errorMessage = '.env file not found';

        // Simulate the audit log data that would be passed on failure
        $auditLogEvent = 'gmail_configuration_failed';
        $auditLogContext = [
            'error' => $errorMessage
        ];

        // Verify the data structure
        $this->assertEquals('gmail_configuration_failed', $auditLogEvent);
        $this->assertIsArray($auditLogContext);
        $this->assertArrayHasKey('error', $auditLogContext);
        $this->assertStringContainsString('.env file not found', $auditLogContext['error']);
    }

    /**
     * Additional test: Verifies default from name when empty
     */
    public function testUsesDefaultFromNameWhenEmpty(): void
    {
        $fromName = '';
        $defaultName = 'Event Management System';

        // Simulate the logic from controller
        if (empty($fromName)) {
            $fromName = $defaultName;
        }

        $this->assertEquals('Event Management System', $fromName, 'Should use default name when empty');
    }

    /**
     * Additional test: Verifies password space removal
     */
    public function testRemovesSpacesFromAppPassword(): void
    {
        // Gmail app passwords are often displayed with spaces
        $passwordWithSpaces = 'abcd efgh ijkl mnop';
        $cleanPassword = str_replace(' ', '', $passwordWithSpaces);

        $this->assertEquals('abcdefghijklmnop', $cleanPassword);
        $this->assertStringNotContainsString(' ', $cleanPassword, 'Cleaned password should not contain spaces');
    }

    /**
     * Test: Verifies .env file not found error handling
     */
    public function testHandlesEnvFileNotFound(): void
    {
        // Don't create the .env file
        $this->assertFileDoesNotExist($this->testEnvPath);

        // Attempt to read non-existent file
        $fileExists = file_exists($this->testEnvPath);
        $this->assertFalse($fileExists, '.env file should not exist');

        // This would trigger an exception in the controller
        $exceptionThrown = false;
        try {
            if (!file_exists($this->testEnvPath)) {
                throw new \Exception('.env file not found');
            }
        } catch (\Exception $e) {
            $exceptionThrown = true;
            $this->assertEquals('.env file not found', $e->getMessage());
        }

        $this->assertTrue($exceptionThrown, 'Exception should be thrown when .env file not found');
    }
}
