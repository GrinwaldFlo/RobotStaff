# RobotStaff - Application Installation Guide

This guide provides step-by-step instructions for deploying the RobotStaff application on a webserver.

## Server Requirements

- **PHP**: >= 8.4
- **Composer**: Latest version
- **Node.js**: >= 18.x
- **NPM**: >= 9.x
- **Database**: MySQL 8.0+ / PostgreSQL 13+ / SQLite 3.8.8+
- **Web Server**: Nginx or Apache with mod_rewrite

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

### 1. Clone the Application

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

### 2. Install Dependencies

#### Install PHP Dependencies
```bash
composer install --optimize-autoloader --no-dev
```

#### Install Node.js Dependencies
```bash
npm install
```

### 3. Environment Configuration

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

### 4. Database Setup

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

### 5. Build Frontend Assets

#### Production Build
```bash
npm run build
```

This will compile and optimize all Vue.js components and assets using Vite.

### 6. Set Directory Permissions

```bash
sudo chown -R www-data:www-data /var/www/RobotStaff
sudo chmod -R 755 /var/www/RobotStaff/storage
sudo chmod -R 755 /var/www/RobotStaff/bootstrap/cache
```

### 7. Optimization & Caching

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

### 8. Queue Worker Setup (Optional but Recommended)

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

### 9. Setup Cron Jobs

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
- Check PHP error logs: `sudo tail -f /var/log/php8.4-fpm.log`

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
3. **Disable debug mode**: Ensure `APP_DEBUG=false` in production
4. **Regular backups**: Backup database and uploaded files regularly
5. **Monitor logs**: Regularly check application and server logs
6. **Use HTTPS**: Always use SSL certificates in production

## Support

For issues and questions, please refer to:
- GitHub Repository: https://github.com/GrinwaldFlo/RobotStaff
- Laravel Documentation: https://laravel.com/docs
- Vue.js Documentation: https://vuejs.org/
- Inertia.js Documentation: https://inertiajs.com/

---

Last Updated: January 2025
