#!/bin/bash

# FotoFix Deployment Script for Debian Linux Server

echo "Starting FotoFix deployment..."

# Set variables
PROJECT_DIR="/var/www/fotofix"
BACKUP_DIR="/var/backups/fotofix"
DATE=$(date +%Y%m%d_%H%M%S)

# Create backup directory
mkdir -p $BACKUP_DIR

# Backup existing installation if it exists
if [ -d "$PROJECT_DIR" ]; then
    echo "Backing up existing installation..."
    tar -czf "$BACKUP_DIR/fotofix_backup_$DATE.tar.gz" -C /var/www fotofix
fi

# Create project directory
mkdir -p $PROJECT_DIR

# Copy files (assuming we're running from the project root)
echo "Copying files..."
cp -r . $PROJECT_DIR/

# Set proper permissions
echo "Setting permissions..."
chown -R www-data:www-data $PROJECT_DIR
chmod -R 755 $PROJECT_DIR
chmod -R 777 $PROJECT_DIR/images

# Create required directories
echo "Creating required directories..."
mkdir -p $PROJECT_DIR/images/{temp,enhanced,preview}
chown -R www-data:www-data $PROJECT_DIR/images
chmod -R 777 $PROJECT_DIR/images

# Install PHP dependencies
echo "Installing PHP dependencies..."
cd $PROJECT_DIR
if command -v composer &> /dev/null; then
    composer install --no-dev --optimize-autoloader
else
    echo "Composer not found. Please install Composer first."
    exit 1
fi

# Configure PHP settings
echo "Configuring PHP..."
cat > /etc/php/$(php -v | head -n1 | cut -d' ' -f2 | cut -d'.' -f1,2)/apache2/conf.d/99-fotofix.ini << EOF
upload_max_filesize = 10M
post_max_size = 100M
max_execution_time = 300
max_input_time = 300
memory_limit = 256M
EOF

# Restart Apache
echo "Restarting Apache..."
systemctl restart apache2

# Set up cron job for cleanup
echo "Setting up cleanup cron job..."
(crontab -l 2>/dev/null; echo "0 2 * * * find $PROJECT_DIR/images/temp -type f -mtime +1 -delete") | crontab -

# Create systemd service for cleanup (optional)
cat > /etc/systemd/system/fotofix-cleanup.service << EOF
[Unit]
Description=FotoFix Cleanup Service
After=network.target

[Service]
Type=oneshot
ExecStart=/usr/bin/find $PROJECT_DIR/images/temp -type f -mtime +1 -delete
User=www-data
Group=www-data

[Install]
WantedBy=multi-user.target
EOF

# Enable cleanup service
systemctl enable fotofix-cleanup.service

echo "Deployment completed successfully!"
echo "Please remember to:"
echo "1. Update API keys in $PROJECT_DIR/api/config.php"
echo "2. Configure your web server virtual host"
echo "3. Set up SSL certificate for secure payments"
echo "4. Test the application thoroughly"

# Display next steps
echo ""
echo "Next steps:"
echo "1. Edit $PROJECT_DIR/api/config.php with your API keys"
echo "2. Configure Apache virtual host to point to $PROJECT_DIR"
echo "3. Set up SSL certificate"
echo "4. Test the application at your domain"
