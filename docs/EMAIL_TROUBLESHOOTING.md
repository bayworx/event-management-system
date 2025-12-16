# Email Troubleshooting Guide

## Test Email Failing Silently

### Problem
When sending test emails from the admin panel, emails appear to send successfully but never arrive in the inbox.

### Root Cause
Emails are being queued in Symfony Messenger's async transport (stored in the `messenger_messages` database table) but are not being processed because the messenger worker is not running.

### Quick Diagnosis
Check if emails are stuck in the queue:
```bash
bin/console messenger:stats
```

If you see messages in the `async` transport, they need to be processed.

### Solutions

#### Solution 1: Process Queued Emails (Quick Fix)
Consume the queue to send all pending emails:
```bash
# Process all messages in the queue
bin/console messenger:consume async --limit=100

# Or process with a time limit (stops after 60 seconds)
bin/console messenger:consume async --time-limit=60
```

#### Solution 2: Send Emails Synchronously (Development)
By default, the application is configured to send emails **synchronously** in development mode. This means test emails are sent immediately without requiring a messenger worker.

**Configuration:**
- **Development (`config/packages/messenger.yaml`)**: Emails sent synchronously (commented out)
- **Production (`config/packages/prod/messenger.yaml`)**: Emails sent asynchronously via queue

If emails are still being queued in development:
1. Check `config/packages/messenger.yaml` - ensure `SendEmailMessage` routing is commented out
2. Clear cache: `bin/console cache:clear`

#### Solution 3: Run Messenger Worker (Production)
In production, run a persistent messenger worker to process queued emails:

**Option A: Run manually (testing)**
```bash
bin/console messenger:consume async -vv
```

**Option B: Systemd service (recommended for production)**
Create `/etc/systemd/system/event-manager-messenger.service`:
```ini
[Unit]
Description=Event Management System Messenger Worker
After=network.target

[Service]
Type=simple
User=www-data
WorkingDirectory=/path/to/event-management-system
ExecStart=/usr/bin/php /path/to/event-management-system/bin/console messenger:consume async --time-limit=3600
Restart=always
RestartSec=10

[Install]
WantedBy=multi-user.target
```

Then enable and start:
```bash
sudo systemctl daemon-reload
sudo systemctl enable event-manager-messenger
sudo systemctl start event-manager-messenger
sudo systemctl status event-manager-messenger
```

**Option C: Supervisor (alternative for production)**
Create `/etc/supervisor/conf.d/messenger-worker.conf`:
```ini
[program:messenger-consume]
command=php /path/to/event-management-system/bin/console messenger:consume async --time-limit=3600
user=www-data
numprocs=2
autostart=true
autorestart=true
process_name=%(program_name)s_%(process_num)02d
```

Then reload supervisor:
```bash
sudo supervisorctl reread
sudo supervisorctl update
sudo supervisorctl start messenger-consume:*
```

**Option D: Cron job (simple but less reliable)**
Add to crontab:
```bash
* * * * * cd /path/to/event-management-system && php bin/console messenger:consume async --time-limit=50 >> /dev/null 2>&1
```

### Verifying Email Configuration

#### Check MAILER_DSN
Ensure your `.env` file has a valid MAILER_DSN:
```bash
# For Gmail
MAILER_DSN=gmail+smtp://your-email@gmail.com:your-app-password@default
MAILER_FROM_EMAIL=your-email@gmail.com
MAILER_FROM_NAME="Your Name"

# For SMTP server
MAILER_DSN=smtp://username:password@smtp.example.com:587
```

#### Test SMTP Connection
```bash
# Check if SMTP server is reachable
telnet smtp.gmail.com 587

# Or with openssl
openssl s_client -starttls smtp -connect smtp.gmail.com:587
```

#### View Logs
Check application logs for email errors:
```bash
# Development logs
tail -f var/log/dev.log | grep -i "email\|mailer"

# Messenger logs
tail -f var/log/dev.log | grep -i "messenger"
```

### Common Issues

#### Gmail App Passwords
If using Gmail:
1. Enable 2-factor authentication on your Google account
2. Generate an App Password: https://myaccount.google.com/apppasswords
3. Use the 16-character app password (remove spaces)
4. Format: `gmail+smtp://your-email@gmail.com:abcdefghijklmnop@default`

#### "Authentication Required" Errors
- Double-check your email and app password
- Ensure no spaces in the password
- Try regenerating the app password

#### Emails Going to Spam
- Configure SPF, DKIM, and DMARC records for your domain
- Use a dedicated email service (SendGrid, Mailgun, etc.)
- Avoid using Gmail for production

### Monitoring Email Queue

#### Check Queue Status
```bash
# View queue statistics
bin/console messenger:stats

# View failed messages
bin/console messenger:failed:show

# Retry failed messages
bin/console messenger:failed:retry
```

#### Clear Queue
```bash
# Remove all messages from queue
bin/console messenger:stop-workers
# Then truncate the messenger_messages table or delete from database
```

### Best Practices

1. **Development**: Use synchronous email sending for immediate feedback
2. **Production**: Use async email sending with a persistent worker
3. **Monitoring**: Set up alerts for failed messages and queue size
4. **Testing**: Always test email configuration after changes
5. **Backups**: Keep `.env.backup.*` files when updating email settings
6. **Security**: Never commit `.env` files with real credentials to version control

### Additional Resources

- Symfony Mailer: https://symfony.com/doc/current/mailer.html
- Symfony Messenger: https://symfony.com/doc/current/messenger.html
- Gmail App Passwords: https://support.google.com/accounts/answer/185833
