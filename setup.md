# RobotStaff - Server Installation Guide

This guide provides step-by-step instructions for installing and deploying the RobotStaff application on a remote webserver.

## Prerequisites

Before you begin, ensure your server meets the following requirements:

- **PHP**: >= 8.2
- **Composer**: Latest version
- **Node.js**: >= 18.x
- **NPM**: >= 9.x
- **Database**: MySQL 8.0+ / PostgreSQL 13+ / SQLite 3.8.8+
- **Web Server**: Nginx or Apache with mod_rewrite
- **Git**: For cloning the repository

## System Requirements

### Required PHP Extensions
- BCMath
- Ctype
- Fileinfo
- JSON
- Mbstring
- OpenSSL
- PDO
- PDO_MySQL (or PDO_PGSQL for PostgreSQL)
- Tokenizer
- XML
- cURL

### Server Permissions
- Read/write access to `storage` directory
- Read/write access to `bootstrap/cache` directory

## Installation Steps

### 1. Server Setup

#### Update System Packages
```bash
sudo apt update && sudo apt upgrade -y
```

#### Install PHP 8.2 and Required Extensions
```bash
sudo apt install -y php8.2 php8.2-fpm php8.2-cli php8.2-common \
    php8.2-mysql php8.2-xml php8.2-mbstring php8.2-curl \
    php8.2-zip php8.2-bcmath php8.2-gd php8.2-intl \
    php8.2-sqlite3
```

#### Install Composer
```bash
curl -sS https://getcomposer.org/installer | php
sudo mv composer.phar /usr/local/bin/composer
sudo chmod +x /usr/local/bin/composer
```

#### Install Node.js and NPM
```bash
curl -fsSL https://deb.nodesource.com/setup_18.x | sudo -E bash -
sudo apt install -y nodejs
```

### 2. Clone the Application

Navigate to your web directory and clone the repository:

```bash
cd /var/www
sudo git clone https://github.com/GrinwaldFlo/RobotStaff.git
cd RobotStaff
```

Set proper ownership:
```bash
sudo chown -R www-data:www-data /var/www/RobotStaff
sudo chmod -R 755 /var/www/RobotStaff
```

### 3. Install Dependencies

#### Install PHP Dependencies
```bash
composer install --optimize-autoloader --no-dev
```

#### Install Node.js Dependencies
```bash
npm install
```

### 4. Environment Configuration

#### Create Environment File
```bash
cp .env.example .env
```

#### Edit Environment Variables
Open the `.env` file and configure the following:

```bash
nano .env
```

**Essential Configuration:**
```env
APP_NAME=RobotStaff
APP_ENV=production
APP_KEY=
APP_DEBUG=false
APP_URL=https://yourdomain.com

# Database Configuration (MySQL Example)
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=robotstaff
DB_USERNAME=your_db_user
DB_PASSWORD=your_db_password

# Session & Cache
SESSION_DRIVER=database
CACHE_STORE=database
QUEUE_CONNECTION=database

# Mail Configuration (Optional but recommended)
MAIL_MAILER=smtp
MAIL_HOST=smtp.mailtrap.io
MAIL_PORT=2525
MAIL_USERNAME=your_username
MAIL_PASSWORD=your_password
MAIL_FROM_ADDRESS="noreply@yourdomain.com"
MAIL_FROM_NAME="${APP_NAME}"
```

#### Generate Application Key
```bash
php artisan key:generate
```

### 5. Database Setup

#### Create Database
For MySQL:
```bash
mysql -u root -p
CREATE DATABASE robotstaff CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE USER 'robotstaff_user'@'localhost' IDENTIFIED BY 'secure_password';
GRANT ALL PRIVILEGES ON robotstaff.* TO 'robotstaff_user'@'localhost';
FLUSH PRIVILEGES;
EXIT;
```

For SQLite (simpler setup):
```bash
touch database/database.sqlite
```

#### Run Migrations
```bash
php artisan migrate --force
```

#### (Optional) Seed Database
```bash
php artisan db:seed --force
```

### 6. Build Frontend Assets

#### Production Build
```bash
npm run build
```

This will compile and optimize all Vue.js components and assets using Vite.

### 7. Set Directory Permissions

```bash
sudo chown -R www-data:www-data /var/www/RobotStaff
sudo chmod -R 755 /var/www/RobotStaff/storage
sudo chmod -R 755 /var/www/RobotStaff/bootstrap/cache
```

### 8. Web Server Configuration

#### Option A: Nginx Configuration

Create a new Nginx configuration file:
```bash
sudo nano /etc/nginx/sites-available/robotstaff
```

Add the following configuration:
```nginx
server {
    listen 80;
    listen [::]:80;
    server_name yourdomain.com www.yourdomain.com;
    root /var/www/RobotStaff/public;

    add_header X-Frame-Options "SAMEORIGIN";
    add_header X-Content-Type-Options "nosniff";

    index index.php;

    charset utf-8;

    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    location = /favicon.ico { access_log off; log_not_found off; }
    location = /robots.txt  { access_log off; log_not_found off; }

    error_page 404 /index.php;

    location ~ \.php$ {
        fastcgi_pass unix:/var/run/php/php8.2-fpm.sock;
        fastcgi_param SCRIPT_FILENAME $realpath_root$fastcgi_script_name;
        include fastcgi_params;
        fastcgi_hide_header X-Powered-By;
    }

    location ~ /\.(?!well-known).* {
        deny all;
    }
}
```

Enable the site:
```bash
sudo ln -s /etc/nginx/sites-available/robotstaff /etc/nginx/sites-enabled/
sudo nginx -t
sudo systemctl reload nginx
```

#### Option B: Apache Configuration

Create a new Apache virtual host:
```bash
sudo nano /etc/apache2/sites-available/robotstaff.conf
```

Add the following configuration:
```apache
<VirtualHost *:80>
    ServerName yourdomain.com
    ServerAlias www.yourdomain.com
    DocumentRoot /var/www/RobotStaff/public

    <Directory /var/www/RobotStaff/public>
        AllowOverride All
        Require all granted
    </Directory>

    ErrorLog ${APACHE_LOG_DIR}/robotstaff-error.log
    CustomLog ${APACHE_LOG_DIR}/robotstaff-access.log combined
</VirtualHost>
```

Enable required modules and site:
```bash
sudo a2enmod rewrite
sudo a2ensite robotstaff.conf
sudo systemctl reload apache2
```

### 9. SSL Certificate Setup (Recommended)

#### Install Certbot
```bash
sudo apt install -y certbot
```

#### For Nginx:
```bash
sudo apt install -y python3-certbot-nginx
sudo certbot --nginx -d yourdomain.com -d www.yourdomain.com
```

#### For Apache:
```bash
sudo apt install -y python3-certbot-apache
sudo certbot --apache -d yourdomain.com -d www.yourdomain.com
```

### 10. Optimization & Caching

```bash
# Cache configuration
php artisan config:cache

# Cache routes
php artisan route:cache

# Cache views
php artisan view:cache

# Optimize autoloader
composer dump-autoload --optimize
```

### 11. Queue Worker Setup (Optional but Recommended)

Create a supervisor configuration for the queue worker:

```bash
sudo nano /etc/supervisor/conf.d/robotstaff-worker.conf
```

Add the following:
```ini
[program:robotstaff-worker]
process_name=%(program_name)s_%(process_num)02d
command=php /var/www/RobotStaff/artisan queue:work --sleep=3 --tries=3 --max-time=3600
autostart=true
autorestart=true
stopasgroup=true
killasgroup=true
user=www-data
numprocs=1
redirect_stderr=true
stdout_logfile=/var/www/RobotStaff/storage/logs/worker.log
stopwaitsecs=3600
```

Start the worker:
```bash
sudo supervisorctl reread
sudo supervisorctl update
sudo supervisorctl start robotstaff-worker:*
```

### 12. Setup Cron Jobs

Add Laravel's scheduler to crontab:

```bash
sudo crontab -e -u www-data
```

Add this line:
```cron
* * * * * cd /var/www/RobotStaff && php artisan schedule:run >> /dev/null 2>&1
```

## Post-Installation

### Verify Installation

1. Visit your domain in a web browser
2. Check that the application loads correctly
3. Test user registration and login
4. Verify database connections

### Check Logs

```bash
tail -f /var/www/RobotStaff/storage/logs/laravel.log
```

### Clear Cache (if needed)

```bash
php artisan cache:clear
php artisan config:clear
php artisan route:clear
php artisan view:clear
```

## Updating the Application

### Pull Latest Changes
```bash
cd /var/www/RobotStaff
git pull origin master
```

### Update Dependencies
```bash
composer install --optimize-autoloader --no-dev
npm install
npm run build
```

### Run Migrations
```bash
php artisan migrate --force
```

### Clear and Recache
```bash
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

### Restart Services
```bash
sudo systemctl reload nginx  # or apache2
sudo supervisorctl restart robotstaff-worker:*
```

## Troubleshooting

### Permission Issues
```bash
sudo chown -R www-data:www-data /var/www/RobotStaff
sudo chmod -R 755 /var/www/RobotStaff/storage
sudo chmod -R 755 /var/www/RobotStaff/bootstrap/cache
```

### 500 Internal Server Error
- Check `storage/logs/laravel.log` for errors
- Ensure `.env` file exists and is properly configured
- Verify database credentials
- Check PHP error logs: `sudo tail -f /var/log/php8.2-fpm.log`

### Assets Not Loading
- Ensure `npm run build` completed successfully
- Check `APP_URL` in `.env` matches your domain
- Verify web server is serving from the `public` directory

### Database Connection Failed
- Verify database credentials in `.env`
- Ensure database service is running: `sudo systemctl status mysql`
- Check firewall rules if using remote database

## Security Recommendations

1. **Keep software updated**: Regularly update PHP, Composer, Node.js, and dependencies
2. **Use strong passwords**: For database and application users
3. **Enable firewall**: `sudo ufw enable` and allow only necessary ports (80, 443, 22)
4. **Disable debug mode**: Ensure `APP_DEBUG=false` in production
5. **Regular backups**: Backup database and uploaded files regularly
6. **Monitor logs**: Regularly check application and server logs
7. **Use HTTPS**: Always use SSL certificates in production

## Support

For issues and questions, please refer to:
- GitHub Repository: https://github.com/GrinwaldFlo/RobotStaff
- Laravel Documentation: https://laravel.com/docs
- Vue.js Documentation: https://vuejs.org/
- Inertia.js Documentation: https://inertiajs.com/

---

Last Updated: January 2025
