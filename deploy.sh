#!/bin/bash

###############################################################################
# Event Management System - Quick Deploy Script
# This script deploys code to your production server
###############################################################################

set -e

# Configuration
REMOTE_USER="your_username"
REMOTE_HOST="events.bayworx.com"
REMOTE_PATH="/var/www/events.bayworx.com"

# Colors for output
GREEN='\033[0;32m'
BLUE='\033[0;34m'
YELLOW='\033[1;33m'
NC='\033[0m'

echo -e "${BLUE}═══════════════════════════════════════${NC}"
echo -e "${BLUE}  Event Management System - Deploy${NC}"
echo -e "${BLUE}═══════════════════════════════════════${NC}\n"

# Check if we have uncommitted changes
if [[ -n $(git status -s) ]]; then
    echo -e "${YELLOW}⚠ Warning: You have uncommitted changes${NC}"
    read -p "Continue anyway? [y/N]: " continue
    if [[ ! $continue =~ ^[Yy]$ ]]; then
        echo "Deployment cancelled"
        exit 0
    fi
fi

echo -e "${BLUE}→ Deploying to ${REMOTE_HOST}...${NC}\n"

# Use rsync to deploy
rsync -avz --delete \
    --exclude='.git' \
    --exclude='var/cache/*' \
    --exclude='var/log/*' \
    --exclude='var/sessions/*' \
    --exclude='.env.local' \
    --exclude='node_modules' \
    --exclude='*.tar.gz' \
    ./ ${REMOTE_USER}@${REMOTE_HOST}:${REMOTE_PATH}/

echo -e "\n${GREEN}✓ Files synced${NC}"

# Run post-deployment commands on server
echo -e "\n${BLUE}→ Running post-deployment tasks...${NC}\n"

ssh ${REMOTE_USER}@${REMOTE_HOST} << 'EOF'
cd /var/www/events.bayworx.com

echo "Installing dependencies..."
composer install --no-dev --optimize-autoloader --no-interaction

echo "Running migrations..."
php bin/console doctrine:migrations:migrate --no-interaction

echo "Clearing cache..."
php bin/console cache:clear --env=prod
php bin/console cache:warmup --env=prod

echo "Setting permissions..."
sudo chown -R www-data:www-data var/cache var/log public/uploads
chmod -R 775 var/cache var/log public/uploads

echo "Deployment complete!"
EOF

echo -e "\n${GREEN}✓ Deployment successful!${NC}"
echo -e "${BLUE}→ Visit: https://events.bayworx.com${NC}\n"
