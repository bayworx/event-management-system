#!/bin/bash

# Setup log rotation for Event Management System
# This script creates logrotate configuration for application logs

LOGROTATE_CONFIG="/etc/logrotate.d/event-management-system"
LOG_DIR="/home/jhill/event-management-system/var/log"

# Check if running as root
if [ "$EUID" -ne 0 ]; then
    echo "This script must be run as root to configure system log rotation."
    echo "Run: sudo $0"
    exit 1
fi

# Create logrotate configuration
cat << EOF > "$LOGROTATE_CONFIG"
# Event Management System log rotation configuration
$LOG_DIR/*.log {
    daily
    rotate 30
    compress
    delaycompress
    missingok
    notifempty
    create 644 www-data www-data
    postrotate
        # Reload PHP-FPM if it's running to reopen log files
        if [ -f /var/run/php/php*-fpm.pid ]; then
            systemctl reload php*-fpm
        fi
    endscript
}

# Separate configuration for security and audit logs (keep longer)
$LOG_DIR/security.log
$LOG_DIR/audit.log {
    daily
    rotate 90
    compress
    delaycompress
    missingok
    notifempty
    create 600 www-data www-data
    postrotate
        if [ -f /var/run/php/php*-fpm.pid ]; then
            systemctl reload php*-fpm
        fi
    endscript
}
EOF

echo "Log rotation configured at $LOGROTATE_CONFIG"
echo "Logs will be rotated daily and compressed"
echo "Regular logs kept for 30 days, security/audit logs kept for 90 days"

# Test the configuration
if logrotate -d "$LOGROTATE_CONFIG" > /dev/null 2>&1; then
    echo "Log rotation configuration is valid"
else
    echo "Warning: Log rotation configuration may have issues"
    logrotate -d "$LOGROTATE_CONFIG"
fi