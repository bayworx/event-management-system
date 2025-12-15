# Quick Start - Deploy to events.bayworx.com

This is a simplified guide to get your Event Management System deployed quickly.

## Prerequisites Checklist

On your server (events.bayworx.com), ensure you have:

- [ ] Ubuntu/Debian Linux
- [ ] PHP 8.1+ installed
- [ ] MariaDB/MySQL installed and running
- [ ] Apache or Nginx installed
- [ ] Composer installed
- [ ] SSH access
- [ ] Domain DNS pointed to server

## 5-Minute Deployment

### Step 1: Upload Files to Server

From your local machine, run:

```bash
cd /home/jhill/event-management-system

# Edit deploy.sh with your server username
nano deploy.sh
# Change REMOTE_USER="your_username" to your actual username

# Deploy
./deploy.sh
```

**OR** manually upload:

```bash
# Create archive
tar czf event-system.tar.gz --exclude='var/cache/*' --exclude='var/log/*' --exclude='.git' .

# Upload to server
scp event-system.tar.gz user@events.bayworx.com:/tmp/

# SSH to server and extract
ssh user@events.bayworx.com
sudo mkdir -p /var/www/events.bayworx.com
sudo chown $USER:$USER /var/www/events.bayworx.com
cd /var/www/events.bayworx.com
tar xzf /tmp/event-system.tar.gz
rm /tmp/event-system.tar.gz
```

### Step 2: Run Installation Script

On the server:

```bash
cd /var/www/events.bayworx.com
chmod +x install.sh
./install.sh
```

The installer will ask for:
- Database credentials
- Admin email/name/password

### Step 3: Configure Apache

```bash
sudo nano /etc/apache2/sites-available/events.bayworx.com.conf
```

Paste this configuration:

```apache
<VirtualHost *:80>
    ServerName events.bayworx.com
    DocumentRoot /var/www/events.bayworx.com/public
    
    <Directory /var/www/events.bayworx.com/public>
        AllowOverride All
        Require all granted
    </Directory>
    
    ErrorLog ${APACHE_LOG_DIR}/events-error.log
    CustomLog ${APACHE_LOG_DIR}/events-access.log combined
</VirtualHost>
```

Enable and restart:

```bash
sudo a2enmod rewrite
sudo a2ensite events.bayworx.com.conf
sudo systemctl reload apache2
```

### Step 4: Set Up SSL

```bash
sudo apt install certbot python3-certbot-apache -y
sudo certbot --apache -d events.bayworx.com
```

Follow the prompts. Certbot will automatically configure HTTPS.

### Step 5: Test the Site

Visit: https://events.bayworx.com

- Homepage should load
- Go to https://events.bayworx.com/admin
- Log in with the admin credentials you created

## Post-Installation

### Configure Email (Important!)

Edit `.env` on the server:

```bash
cd /var/www/events.bayworx.com
nano .env
```

Update email settings:

```env
# Example for Gmail
MAILER_DSN=gmail+smtp://your-email@gmail.com:your-app-password@default

# Or for SendGrid
MAILER_DSN=sendgrid+smtp://apikey:YOUR_SENDGRID_API_KEY@default

# Update from email
MAILER_FROM_EMAIL=noreply@bayworx.com
MAILER_FROM_NAME="Bayworx Events"
```

Clear cache after changes:

```bash
php bin/console cache:clear --env=prod
```

### Configure Application Settings

1. Log in to admin panel: https://events.bayworx.com/admin
2. Go to: Configuration (top menu)
3. Update:
   - Company Name: "Bayworx" (or your company name)
   - Company Website: "https://bayworx.com"
   - Support Email: "support@bayworx.com"
   - Other settings as needed

### Create Your First Event

1. In admin panel, click "Events" → "Create Event"
2. Fill in event details:
   - Title, Description, Date/Time, Location
   - Upload banner image
   - Set max attendees (optional)
3. Add agenda items (schedule)
4. Add presenters/speakers
5. Upload event files (PDFs, presentations)
6. Configure WiFi (optional but recommended)
7. Publish the event

## Common Issues & Fixes

### Issue: 500 Error

```bash
# Check permissions
cd /var/www/events.bayworx.com
sudo chown -R www-data:www-data var/cache var/log public/uploads
chmod -R 775 var/cache var/log public/uploads

# Clear cache
php bin/console cache:clear --env=prod
```

### Issue: Can't upload files

```bash
# Create uploads directory
mkdir -p public/uploads
sudo chown -R www-data:www-data public/uploads
chmod -R 775 public/uploads
```

### Issue: Database connection failed

```bash
# Test database connection
php bin/console app:test-connection

# If failed, check .env file database credentials
nano .env
```

### Issue: Emails not sending

1. Check `.env` has correct MAILER_DSN
2. Verify firewall allows SMTP port (587 or 465)
3. Test with a simple event registration

## Updating the Application

When you make changes locally and want to deploy:

```bash
# From local machine
cd /home/jhill/event-management-system
./deploy.sh
```

Or manually:

```bash
# SSH to server
ssh user@events.bayworx.com
cd /var/www/events.bayworx.com

# Pull changes (if using git)
git pull

# Or upload new files

# Then run:
composer install --no-dev --optimize-autoloader
php bin/console doctrine:migrations:migrate --no-interaction
php bin/console cache:clear --env=prod
sudo chown -R www-data:www-data var/cache var/log
```

## Useful Commands

```bash
# View application logs
tail -f /var/www/events.bayworx.com/var/log/prod.log

# View web server logs
sudo tail -f /var/log/apache2/events-error.log

# Test site
curl -I https://events.bayworx.com

# Restart web server
sudo systemctl restart apache2

# Check PHP version
php -v

# Check database
mysql -u username -p
```

## Need More Help?

- Full guide: See [DEPLOYMENT.md](DEPLOYMENT.md)
- System docs: See [WARP.md](WARP.md)
- Symfony docs: https://symfony.com/doc

## Quick Reference

- **Site**: https://events.bayworx.com
- **Admin**: https://events.bayworx.com/admin
- **Server Path**: /var/www/events.bayworx.com
- **Document Root**: /var/www/events.bayworx.com/public
- **Logs**: /var/www/events.bayworx.com/var/log/
- **Uploads**: /var/www/events.bayworx.com/public/uploads/

That's it! Your event management system should now be live at events.bayworx.com! 🎉
