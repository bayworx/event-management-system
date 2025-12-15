# Event Management System - Deployment Guide

This guide will help you deploy the Event Management System to your production server (events.bayworx.com).

## Prerequisites

- **Server**: Ubuntu/Debian Linux server with SSH access
- **PHP**: Version 8.1 or higher
- **Database**: MariaDB 11.8.3 or MySQL 8.0+
- **Web Server**: Apache or Nginx
- **Composer**: PHP dependency manager
- **Node.js**: (optional, for asset compilation if needed)
- **SSL Certificate**: For HTTPS (Let's Encrypt recommended)

## Required PHP Extensions

Ensure these PHP extensions are installed:

```bash
sudo apt update
sudo apt install -y php8.1-cli php8.1-fpm php8.1-mysql php8.1-xml php8.1-mbstring \
    php8.1-curl php8.1-gd php8.1-zip php8.1-intl php8.1-bcmath
```

## Deployment Steps

### 1. Prepare the Server

Connect to your server:

```bash
ssh user@events.bayworx.com
```

Create application directory:

```bash
sudo mkdir -p /var/www/events.bayworx.com
sudo chown $USER:$USER /var/www/events.bayworx.com
cd /var/www/events.bayworx.com
```

### 2. Upload the Application

**Option A: Using Git (Recommended)**

If you have a Git repository:

```bash
git clone https://github.com/yourusername/event-management-system.git .
```

**Option B: Using rsync**

From your local machine:

```bash
rsync -avz --exclude='var/cache/*' --exclude='var/log/*' --exclude='.git' \
    /home/jhill/event-management-system/ user@events.bayworx.com:/var/www/events.bayworx.com/
```

**Option C: Using SCP**

From your local machine:

```bash
cd /home/jhill/event-management-system
tar czf event-system.tar.gz --exclude='var/cache/*' --exclude='var/log/*' --exclude='.git' .
scp event-system.tar.gz user@events.bayworx.com:/var/www/events.bayworx.com/
```

Then on the server:

```bash
cd /var/www/events.bayworx.com
tar xzf event-system.tar.gz
rm event-system.tar.gz
```

### 3. Run the Installation Script

Make the installation script executable:

```bash
chmod +x install.sh
```

Run the installer:

```bash
./install.sh
```

The installer will prompt you for:
- Database credentials
- Admin user details

### 4. Configure Web Server

#### For Apache

Create virtual host configuration:

```bash
sudo nano /etc/apache2/sites-available/events.bayworx.com.conf
```

Add this configuration:

```apache
<VirtualHost *:80>
    ServerName events.bayworx.com
    ServerAlias www.events.bayworx.com
    
    DocumentRoot /var/www/events.bayworx.com/public
    
    <Directory /var/www/events.bayworx.com/public>
        AllowOverride All
        Require all granted
        
        # Rewrite rules for Symfony
        <IfModule mod_rewrite.c>
            Options -MultiViews
            RewriteEngine On
            RewriteCond %{REQUEST_FILENAME} !-f
            RewriteRule ^(.*)$ index.php [QSA,L]
        </IfModule>
    </Directory>
    
    # Logging
    ErrorLog ${APACHE_LOG_DIR}/events.bayworx.com-error.log
    CustomLog ${APACHE_LOG_DIR}/events.bayworx.com-access.log combined
    
    # Security headers
    Header set X-Content-Type-Options "nosniff"
    Header set X-Frame-Options "SAMEORIGIN"
    Header set X-XSS-Protection "1; mode=block"
</VirtualHost>
```

Enable the site and required modules:

```bash
sudo a2enmod rewrite headers
sudo a2ensite events.bayworx.com.conf
sudo systemctl reload apache2
```

#### For Nginx

Create server block:

```bash
sudo nano /etc/nginx/sites-available/events.bayworx.com
```

Add this configuration:

```nginx
server {
    listen 80;
    listen [::]:80;
    
    server_name events.bayworx.com www.events.bayworx.com;
    root /var/www/events.bayworx.com/public;
    
    index index.php;
    
    # Security headers
    add_header X-Frame-Options "SAMEORIGIN" always;
    add_header X-Content-Type-Options "nosniff" always;
    add_header X-XSS-Protection "1; mode=block" always;
    
    location / {
        try_files $uri /index.php$is_args$args;
    }
    
    location ~ ^/index\.php(/|$) {
        fastcgi_pass unix:/var/run/php/php8.1-fpm.sock;
        fastcgi_split_path_info ^(.+\.php)(/.*)$;
        include fastcgi_params;
        fastcgi_param SCRIPT_FILENAME $realpath_root$fastcgi_script_name;
        fastcgi_param DOCUMENT_ROOT $realpath_root;
        internal;
    }
    
    location ~ \.php$ {
        return 404;
    }
    
    # Logging
    access_log /var/log/nginx/events.bayworx.com-access.log;
    error_log /var/log/nginx/events.bayworx.com-error.log;
}
```

Enable the site:

```bash
sudo ln -s /etc/nginx/sites-available/events.bayworx.com /etc/nginx/sites-enabled/
sudo nginx -t
sudo systemctl reload nginx
```

### 5. Set Up SSL Certificate (Let's Encrypt)

Install Certbot:

```bash
sudo apt install certbot
```

**For Apache:**

```bash
sudo apt install python3-certbot-apache
sudo certbot --apache -d events.bayworx.com -d www.events.bayworx.com
```

**For Nginx:**

```bash
sudo apt install python3-certbot-nginx
sudo certbot --nginx -d events.bayworx.com -d www.events.bayworx.com
```

### 6. Configure Email Settings

Edit the `.env` file to add email configuration:

```bash
nano .env
```

Update the MAILER_DSN line with your SMTP settings:

```env
# For Gmail
MAILER_DSN=gmail+smtp://username:password@default

# For SendGrid
MAILER_DSN=sendgrid+smtp://apikey:YOUR_API_KEY@default

# For standard SMTP
MAILER_DSN=smtp://username:password@smtp.example.com:587
```

Also update the FROM email:

```env
MAILER_FROM_EMAIL=noreply@bayworx.com
MAILER_FROM_NAME="Bayworx Events"
```

### 7. Set File Permissions

Ensure proper permissions:

```bash
cd /var/www/events.bayworx.com

# Set ownership
sudo chown -R www-data:www-data var/cache var/log public/uploads
sudo chown -R $USER:www-data .

# Set permissions
chmod -R 775 var/cache var/log public/uploads
chmod -R 755 public

# Set ACLs if available
sudo setfacl -dR -m u:www-data:rwX -m u:$USER:rwX var
sudo setfacl -R -m u:www-data:rwX -m u:$USER:rwX var
```

### 8. Set Up Cron Jobs (Optional)

For automated tasks like log cleanup:

```bash
crontab -e
```

Add:

```cron
# Clean up logs daily at 3 AM
0 3 * * * cd /var/www/events.bayworx.com && php bin/console app:log-cleanup

# Clear cache weekly
0 2 * * 0 cd /var/www/events.bayworx.com && php bin/console cache:clear --env=prod
```

## Post-Deployment

### 1. Test the Application

Visit your domain:
- Homepage: https://events.bayworx.com
- Admin: https://events.bayworx.com/admin

### 2. Configure Application Settings

Log in to the admin panel and go to `/admin/config` to configure:
- Company information
- Email settings
- Event defaults
- Footer content

### 3. Create Test Event

Create a test event to ensure all features work:
1. Create event with details
2. Add agenda items
3. Add presenters
4. Upload files
5. Configure WiFi (optional)
6. Test registration flow

### 4. Monitor Logs

Keep an eye on application logs:

```bash
# Application logs
tail -f /var/www/events.bayworx.com/var/log/prod.log

# Web server logs (Apache)
tail -f /var/log/apache2/events.bayworx.com-error.log

# Web server logs (Nginx)
tail -f /var/log/nginx/events.bayworx.com-error.log
```

## Updating the Application

To update the application after pushing new code:

```bash
cd /var/www/events.bayworx.com

# Pull latest code
git pull origin main

# Install/update dependencies
composer install --no-dev --optimize-autoloader

# Run migrations
php bin/console doctrine:migrations:migrate --no-interaction

# Clear cache
php bin/console cache:clear --env=prod
php bin/console cache:warmup --env=prod

# Set permissions
sudo chown -R www-data:www-data var/cache var/log
chmod -R 775 var/cache var/log
```

## Troubleshooting

### Issue: 500 Internal Server Error

1. Check PHP error logs
2. Verify `.env` configuration
3. Check file permissions
4. Clear cache: `php bin/console cache:clear`

### Issue: Database Connection Failed

1. Verify database credentials in `.env`
2. Check database server is running
3. Test connection: `php bin/console app:test-connection`

### Issue: Upload Errors

1. Check `public/uploads` directory exists
2. Verify write permissions
3. Check PHP `upload_max_filesize` and `post_max_size` settings

### Issue: Email Not Sending

1. Verify MAILER_DSN in `.env`
2. Check firewall allows SMTP port
3. Test email configuration with your provider

## Security Checklist

- [ ] SSL certificate installed and auto-renewal configured
- [ ] `.env` file is not publicly accessible
- [ ] File upload directory has proper restrictions
- [ ] Strong admin passwords
- [ ] Database credentials are secure
- [ ] APP_DEBUG=0 in production
- [ ] Regular backups configured
- [ ] Security headers enabled
- [ ] Keep PHP and dependencies updated

## Backup Strategy

### Database Backup

Create automated daily backups:

```bash
#!/bin/bash
# backup-database.sh

BACKUP_DIR="/var/backups/event-system"
DATE=$(date +%Y%m%d_%H%M%S)
DB_NAME="your_database_name"

mkdir -p $BACKUP_DIR
mysqldump $DB_NAME | gzip > $BACKUP_DIR/backup_$DATE.sql.gz

# Keep only last 30 days
find $BACKUP_DIR -name "backup_*.sql.gz" -mtime +30 -delete
```

### File Backup

Backup uploaded files:

```bash
#!/bin/bash
# backup-files.sh

BACKUP_DIR="/var/backups/event-system"
DATE=$(date +%Y%m%d_%H%M%S)
APP_DIR="/var/www/events.bayworx.com"

mkdir -p $BACKUP_DIR
tar czf $BACKUP_DIR/files_$DATE.tar.gz -C $APP_DIR public/uploads

# Keep only last 30 days
find $BACKUP_DIR -name "files_*.tar.gz" -mtime +30 -delete
```

Add to crontab:

```cron
# Database backup daily at 2 AM
0 2 * * * /path/to/backup-database.sh

# File backup daily at 3 AM
0 3 * * * /path/to/backup-files.sh
```

## Support

For issues or questions:
- Check application logs in `var/log/`
- Review Symfony documentation: https://symfony.com/doc
- Check the WARP.md file for system details

## License

See LICENSE file for details.
