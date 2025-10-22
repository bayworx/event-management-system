#!/bin/bash

# Event Management System Development Server
# Starts PHP built-in server with deprecation warnings suppressed for Symfony 6.1 + PHP 8.4 compatibility

echo "Starting Event Management System development server..."
echo "Server will be available at: http://localhost:8000"
echo "Press Ctrl+C to stop the server"
echo ""

# Start PHP built-in server with custom configuration
# - Suppress deprecation warnings completely
# - Don't display errors in browser
# - Suppress all deprecation output
PHP_INI_SCAN_DIR= php \
    -d error_reporting=22519 \
    -d display_errors=Off \
    -d log_errors=On \
    -d html_errors=Off \
    -d memory_limit=256M \
    -d auto_prepend_file= \
    -d auto_append_file= \
    -S localhost:8000 \
    -t public/
