#!/bin/bash

###############################################################################
# Event Management System - Installation Script
# This script will guide you through the installation process
###############################################################################

set -e  # Exit on any error

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m' # No Color

# Function to print colored output
print_success() {
    echo -e "${GREEN}✓ $1${NC}"
}

print_error() {
    echo -e "${RED}✗ $1${NC}"
}

print_info() {
    echo -e "${BLUE}ℹ $1${NC}"
}

print_warning() {
    echo -e "${YELLOW}⚠ $1${NC}"
}

print_header() {
    echo -e "\n${BLUE}═══════════════════════════════════════════════════════${NC}"
    echo -e "${BLUE}  $1${NC}"
    echo -e "${BLUE}═══════════════════════════════════════════════════════${NC}\n"
}

# Check if running as root
if [ "$EUID" -eq 0 ]; then 
    print_error "Please do not run this script as root"
    exit 1
fi

print_header "Event Management System - Installation"

# Check PHP version
print_info "Checking PHP version..."
if ! command -v php &> /dev/null; then
    print_error "PHP is not installed. Please install PHP 8.1 or higher."
    exit 1
fi

PHP_VERSION=$(php -r 'echo PHP_VERSION;')
PHP_MAJOR=$(echo $PHP_VERSION | cut -d. -f1)
PHP_MINOR=$(echo $PHP_VERSION | cut -d. -f2)

if [ "$PHP_MAJOR" -lt 8 ] || ([ "$PHP_MAJOR" -eq 8 ] && [ "$PHP_MINOR" -lt 1 ]); then
    print_error "PHP 8.1 or higher is required. Current version: $PHP_VERSION"
    exit 1
fi

print_success "PHP version $PHP_VERSION detected"

# Check Composer
print_info "Checking for Composer..."
if ! command -v composer &> /dev/null; then
    print_error "Composer is not installed. Please install Composer first."
    echo "Visit: https://getcomposer.org/download/"
    exit 1
fi

print_success "Composer is installed"

# Check if .env file exists
print_info "Checking environment configuration..."
if [ ! -f ".env" ]; then
    if [ -f ".env.example" ]; then
        print_info "Creating .env file from .env.example..."
        cp .env.example .env
        print_success ".env file created"
    else
        print_error ".env.example file not found"
        exit 1
    fi
else
    print_warning ".env file already exists, skipping..."
fi

# Install dependencies
print_header "Installing Dependencies"
print_info "Running: composer install..."
composer install --no-interaction --optimize-autoloader

print_success "Dependencies installed"

# Configure environment
print_header "Environment Configuration"

echo ""
print_info "Please provide the following information:"
echo ""

# Database configuration
read -p "Database host [localhost]: " DB_HOST
DB_HOST=${DB_HOST:-localhost}

read -p "Database port [3306]: " DB_PORT
DB_PORT=${DB_PORT:-3306}

read -p "Database name: " DB_NAME
while [ -z "$DB_NAME" ]; do
    print_error "Database name is required"
    read -p "Database name: " DB_NAME
done

read -p "Database user: " DB_USER
while [ -z "$DB_USER" ]; do
    print_error "Database user is required"
    read -p "Database user: " DB_USER
done

read -sp "Database password: " DB_PASSWORD
echo ""
while [ -z "$DB_PASSWORD" ]; do
    print_error "Database password is required"
    read -sp "Database password: " DB_PASSWORD
    echo ""
done

# Generate APP_SECRET if not exists
if grep -q "APP_SECRET=your_secret_here" .env || ! grep -q "APP_SECRET=" .env; then
    APP_SECRET=$(openssl rand -hex 32)
    print_info "Generating APP_SECRET..."
else
    print_info "APP_SECRET already configured"
fi

# Update .env file
print_info "Updating .env file..."

# Update database URL
sed -i "s|DATABASE_URL=.*|DATABASE_URL=\"mysql://${DB_USER}:${DB_PASSWORD}@${DB_HOST}:${DB_PORT}/${DB_NAME}?serverVersion=11.8.3-MariaDB\"|g" .env

# Update APP_SECRET if generated
if [ ! -z "$APP_SECRET" ]; then
    sed -i "s|APP_SECRET=.*|APP_SECRET=${APP_SECRET}|g" .env
fi

# Set APP_ENV to prod
sed -i "s|APP_ENV=.*|APP_ENV=prod|g" .env
sed -i "s|APP_DEBUG=.*|APP_DEBUG=0|g" .env

print_success "Environment configuration updated"

# Database setup
print_header "Database Setup"

print_info "Creating database if it doesn't exist..."
php bin/console doctrine:database:create --if-not-exists

print_info "Running migrations..."
php bin/console doctrine:migrations:migrate --no-interaction

print_success "Database setup complete"

# Initialize configuration
print_header "Application Configuration"

print_info "Initializing application configuration..."
php bin/console app:init-config

print_success "Application configuration initialized"

# Create admin user
print_header "Admin User Setup"

echo ""
read -p "Create an admin user now? [Y/n]: " CREATE_ADMIN
CREATE_ADMIN=${CREATE_ADMIN:-Y}

if [[ $CREATE_ADMIN =~ ^[Yy]$ ]]; then
    read -p "Admin email: " ADMIN_EMAIL
    while [ -z "$ADMIN_EMAIL" ]; do
        print_error "Admin email is required"
        read -p "Admin email: " ADMIN_EMAIL
    done

    read -p "Admin name: " ADMIN_NAME
    while [ -z "$ADMIN_NAME" ]; do
        print_error "Admin name is required"
        read -p "Admin name: " ADMIN_NAME
    done

    read -sp "Admin password: " ADMIN_PASSWORD
    echo ""
    while [ -z "$ADMIN_PASSWORD" ]; do
        print_error "Admin password is required"
        read -sp "Admin password: " ADMIN_PASSWORD
        echo ""
    done

    php bin/console app:create-admin "$ADMIN_EMAIL" --name="$ADMIN_NAME" --password="$ADMIN_PASSWORD" --super-admin

    print_success "Admin user created"
else
    print_warning "Skipping admin user creation"
    print_info "You can create an admin later with:"
    print_info "php bin/console app:create-admin email@example.com --name=\"Admin Name\" --password=\"password\" --super-admin"
fi

# Cache and permissions
print_header "Finalizing Installation"

print_info "Clearing cache..."
php bin/console cache:clear --env=prod

print_info "Warming up cache..."
php bin/console cache:warmup --env=prod

print_info "Setting file permissions..."
chmod -R 755 var/cache var/log
chmod -R 775 public/uploads

if command -v setfacl &> /dev/null; then
    print_info "Setting ACLs for web server..."
    HTTPDUSER=$(ps axo user,comm | grep -E '[a]pache|[h]ttpd|[_]www|[w]ww-data|[n]ginx' | grep -v root | head -1 | cut -d\  -f1)
    if [ ! -z "$HTTPDUSER" ]; then
        sudo setfacl -dR -m u:"$HTTPDUSER":rwX -m u:$(whoami):rwX var
        sudo setfacl -R -m u:"$HTTPDUSER":rwX -m u:$(whoami):rwX var
        print_success "ACLs configured for user: $HTTPDUSER"
    fi
fi

print_success "Installation complete!"

# Final instructions
print_header "Next Steps"

echo ""
print_info "Your Event Management System is now installed!"
echo ""
print_info "Document root should point to: $(pwd)/public"
echo ""
print_info "For production deployment:"
echo "  1. Configure your web server (Apache/Nginx)"
echo "  2. Set up SSL certificate"
echo "  3. Configure email settings in .env"
echo "  4. Review security settings"
echo ""
print_info "For development server:"
echo "  Run: ./serve.sh"
echo "  Or: php -S localhost:8000 -t public/"
echo ""
print_info "Access your application:"
echo "  - Homepage: http://your-domain.com"
echo "  - Admin: http://your-domain.com/admin"
echo ""
print_success "Installation completed successfully!"
echo ""
