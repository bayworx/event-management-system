# Gmail Integration Setup Guide

This guide will help you configure Gmail for sending emails from your Event Management System.

## Option 1: Gmail with App Password (Recommended)

This is the most secure method for production use.

### Step 1: Enable 2-Factor Authentication on Gmail

1. Go to your Google Account: https://myaccount.google.com/
2. Click "Security" in the left sidebar
3. Under "Signing in to Google", click "2-Step Verification"
4. Follow the prompts to enable 2FA

### Step 2: Generate App Password

1. Go to: https://myaccount.google.com/apppasswords
2. Select "Mail" for app type
3. Select "Other (Custom name)" for device
4. Enter a name like "Event Management System"
5. Click "Generate"
6. **Copy the 16-character password** (you won't see it again)

### Step 3: Configure .env File

On your server, edit the `.env` file:

```bash
ssh user@events.bayworx.com
cd /var/www/events.bayworx.com
nano .env
```

Update the following lines:

```env
# Gmail configuration with App Password
MAILER_DSN=gmail+smtp://your-email@gmail.com:your-app-password@default

# From address (should match your Gmail)
MAILER_FROM_EMAIL=your-email@gmail.com
MAILER_FROM_NAME="Bayworx Events"
```

**Important:** Replace:
- `your-email@gmail.com` with your actual Gmail address
- `your-app-password` with the 16-character app password (no spaces)

Example:
```env
MAILER_DSN=gmail+smtp://john@bayworx.com:abcdwxyzefghijkl@default
MAILER_FROM_EMAIL=john@bayworx.com
MAILER_FROM_NAME="Bayworx Events"
```

### Step 4: Clear Cache

```bash
php bin/console cache:clear --env=prod
```

### Step 5: Test Email

Test by registering for an event or using the test command:

```bash
php bin/console app:test-email your-test-email@example.com
```

## Option 2: Gmail with OAuth2 (Advanced)

For better security and higher sending limits, you can use OAuth2.

### Prerequisites

Install the OAuth2 transport:

```bash
composer require symfony/google-mailer
```

### Configuration

```env
MAILER_DSN=gmail+smtp://USERNAME:PASSWORD@default
```

Or use the API transport for OAuth2:

```env
MAILER_DSN=gmail://USERNAME:PASSWORD@default
```

## Option 3: G Suite / Google Workspace

If you're using Google Workspace (formerly G Suite):

```env
# Standard SMTP
MAILER_DSN=smtp://username@yourdomain.com:app-password@smtp.gmail.com:587

# Or Gmail transport
MAILER_DSN=gmail+smtp://username@yourdomain.com:app-password@default
```

## Email Configuration in Admin Panel

After configuring the `.env` file, update settings in the admin panel:

1. Log in to: https://events.bayworx.com/admin
2. Go to "Configuration"
3. Update Email Settings:
   - **From Email**: your-email@gmail.com
   - **From Name**: Bayworx Events
   - **Support Email**: support@bayworx.com

## Troubleshooting

### Error: "Username and Password not accepted"

**Solution:**
1. Make sure 2FA is enabled on your Google account
2. Use an App Password, not your regular Gmail password
3. Remove any spaces from the app password

### Error: "Less secure app access"

**Solution:**
- Google no longer allows "less secure apps"
- You **must** use 2FA + App Password
- Or use OAuth2 authentication

### Error: "Daily sending quota exceeded"

**Gmail limits:**
- Free Gmail: 500 emails/day
- Google Workspace: 2,000 emails/day

**Solutions:**
1. Use Google Workspace for higher limits
2. Implement rate limiting
3. Use a dedicated email service (SendGrid, Mailgun)

### Error: "Connection timeout"

**Check firewall:**
```bash
# Allow outbound SMTP
sudo ufw allow out 587/tcp
sudo ufw allow out 465/tcp
```

### Verify SMTP connectivity

Test connection from server:

```bash
# Install telnet if needed
sudo apt install telnet

# Test connection
telnet smtp.gmail.com 587
```

Should show: `220 smtp.gmail.com ESMTP`

## Testing Email Functionality

### 1. Test Registration Email

1. Create a test event
2. Register as an attendee with a test email
3. Check that verification email is received

### 2. Test Password Reset Email

1. Go to admin forgot password
2. Enter your email
3. Check that reset email is received

### 3. Test Attendee Messaging

1. Log in as an attendee
2. Send a message to organizers
3. Check that admin receives notification

## Email Templates

The system sends emails for:

1. **Attendee Verification** (`templates/emails/verification.html.twig`)
   - Sent when someone registers for an event
   - Contains verification link and WiFi QR code

2. **Password Reset** (`templates/admin/security/reset_email.html.twig`)
   - Sent when admin requests password reset
   - Contains reset link (valid 1 hour)

3. **Login Links** 
   - Sent when attendee requests login link
   - Passwordless authentication

## Customizing Email Templates

Email templates are in `templates/emails/` and `templates/admin/security/`:

```bash
# Edit verification email
nano templates/emails/verification.html.twig

# Edit password reset email
nano templates/admin/security/reset_email.html.twig
```

After editing templates:
```bash
php bin/console cache:clear --env=prod
```

## Alternative Email Services

If you hit Gmail's sending limits or need more features:

### SendGrid (Recommended for Production)

```bash
composer require symfony/sendgrid-mailer
```

```env
MAILER_DSN=sendgrid+smtp://apikey:YOUR_SENDGRID_API_KEY@default
```

### Mailgun

```bash
composer require symfony/mailgun-mailer
```

```env
MAILER_DSN=mailgun+smtp://USERNAME:PASSWORD@default?region=us
```

### Amazon SES

```bash
composer require symfony/amazon-mailer
```

```env
MAILER_DSN=ses+smtp://ACCESS_KEY:SECRET_KEY@default?region=us-east-1
```

## Best Practices

1. **Use App Passwords** - Never use your actual Gmail password
2. **Enable 2FA** - Required for app passwords
3. **Monitor Sending Limits** - Gmail has daily limits
4. **Use Professional Email** - Consider custom domain (you@bayworx.com)
5. **Test Thoroughly** - Test all email flows before going live
6. **Check Spam Folders** - Ensure emails aren't marked as spam
7. **Set Up SPF/DKIM** - For custom domains, configure DNS records

## Production Checklist

- [ ] 2FA enabled on Gmail account
- [ ] App password generated
- [ ] `.env` configured with correct credentials
- [ ] From email and name set correctly
- [ ] Cache cleared after configuration
- [ ] Test email sent successfully
- [ ] Verification emails working
- [ ] Password reset emails working
- [ ] All emails arriving (not in spam)
- [ ] Email templates customized (optional)

## Getting Help

If you encounter issues:

1. Check application logs:
   ```bash
   tail -f /var/www/events.bayworx.com/var/log/prod.log
   ```

2. Enable debug mode temporarily:
   ```bash
   # In .env
   APP_ENV=dev
   APP_DEBUG=1
   ```

3. Test with swiftmailer test command:
   ```bash
   php bin/console debug:container | grep mailer
   ```

## Security Notes

- **Never commit** `.env` files with real credentials
- **Rotate app passwords** periodically
- **Monitor email logs** for suspicious activity
- **Use HTTPS** to protect credentials in transit
- **Limit access** to `.env` file (chmod 600)

## Support

- Symfony Mailer: https://symfony.com/doc/current/mailer.html
- Gmail App Passwords: https://support.google.com/accounts/answer/185833
- Google Workspace: https://support.google.com/a/answer/176600
