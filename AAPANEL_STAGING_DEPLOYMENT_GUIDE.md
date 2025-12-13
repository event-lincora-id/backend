# aaPanel Staging Deployment Guide - Event Connect API

This guide will walk you through deploying the Event Connect Laravel backend to a staging environment using aaPanel.

## Prerequisites

Before starting, ensure you have:
- ✅ aaPanel installed on your server (Ubuntu/CentOS recommended)
- ✅ Root or sudo access to your server
- ✅ Domain or subdomain pointed to your server (e.g., `staging-api.yourdomain.com`)
- ✅ Basic knowledge of SSH and command line

## Table of Contents
1. [Server Requirements](#1-server-requirements)
2. [aaPanel Initial Setup](#2-aapanel-initial-setup)
3. [Install Required Software](#3-install-required-software)
4. [Database Setup](#4-database-setup)
5. [Upload Project Files](#5-upload-project-files)
6. [Configure Laravel](#6-configure-laravel)
7. [Web Server Configuration](#7-web-server-configuration)
8. [SSL Certificate Setup](#8-ssl-certificate-setup)
9. [Final Steps](#9-final-steps)
10. [Troubleshooting](#10-troubleshooting)

---

## 1. Server Requirements

### Minimum Specifications:
- **CPU**: 2 cores
- **RAM**: 2GB minimum, 4GB recommended
- **Storage**: 20GB SSD
- **OS**: Ubuntu 20.04/22.04 or CentOS 7/8

### Required Software:
- PHP 8.2 or higher (8.3 recommended)
- MySQL 5.7+ or MariaDB 10.3+
- Composer
- Nginx or Apache

---

## 2. aaPanel Initial Setup

### Install aaPanel (if not already installed)

**For Ubuntu/Debian:**
```bash
wget -O install.sh http://www.aapanel.com/script/install-ubuntu_6.0_en.sh && sudo bash install.sh aapanel
```

**For CentOS:**
```bash
wget -O install.sh http://www.aapanel.com/script/install_6.0_en.sh && sudo bash install.sh aapanel
```

After installation, note down:
- aaPanel URL (e.g., `http://your-server-ip:7800/xxxxxxxx`)
- Username
- Password

### Access aaPanel
1. Open your browser and go to the aaPanel URL
2. Login with your credentials
3. Complete the initial setup wizard

---

## 3. Install Required Software

### Step 1: Install Software Stack
1. In aaPanel dashboard, go to **App Store**
2. Install the following (one-click install):
   - **Nginx** (recommended) or Apache
   - **MySQL** 5.7+ or **MariaDB** 10.3+
   - **PHP 8.2** or **PHP 8.3**
   - **phpMyAdmin** (optional, for database management)

### Step 2: Install PHP Extensions
1. Go to **App Store** → **Installed**
2. Click on **PHP 8.3** → **Settings**
3. Go to **Install Extensions** tab
4. Install the following extensions:
   - ✅ `opcache` (performance)
   - ✅ `redis` (caching)
   - ✅ `fileinfo` (file operations)
   - ✅ `imagemagick` (image processing)
   - ✅ `exif` (image metadata)
   - ✅ `intl` (internationalization)
   - ✅ `zip` (compression)
   - ✅ `bcmath` (precision math)

### Step 3: Configure PHP Settings
1. In PHP Settings, go to **Configuration File**
2. Update these values:
```ini
upload_max_filesize = 50M
post_max_size = 50M
max_execution_time = 300
memory_limit = 256M
```
3. Click **Save** and restart PHP-FPM

### Step 4: Install Composer
1. Go to **App Store** → **System Tools**
2. Find and install **Composer**
3. Or manually via SSH:
```bash
cd /www/server
curl -sS https://getcomposer.org/installer | php
mv composer.phar /usr/local/bin/composer
chmod +x /usr/local/bin/composer
```

---

## 4. Database Setup

### Step 1: Create Database
1. In aaPanel, go to **Database** → **Add Database**
2. Fill in the details:
   - **Database Name**: `event_connect_staging`
   - **Username**: `event_connect_user` (or custom)
   - **Password**: Generate a strong password
   - **Access Permission**: `localhost` (for security)
3. Click **Submit**
4. **Important**: Save your database credentials securely!

### Step 2: Configure Database (Optional)
1. Click **phpMyAdmin** to access the database
2. Or use the **Management** button next to your database

---

## 5. Upload Project Files

### Method 1: Via aaPanel File Manager (Recommended for first-time)

#### Step 1: Create Website Directory
1. Go to **Website** → **Add Site**
2. Fill in:
   - **Domain**: `staging-api.yourdomain.com`
   - **Root Directory**: `/www/wwwroot/event-connect-staging`
   - **PHP Version**: PHP-8.3
   - **Database**: Select the database you created
3. Click **Submit**

#### Step 2: Upload Files
1. Go to **Files** → Navigate to `/www/wwwroot/event-connect-staging`
2. Delete the default files (index.html, 404.html, etc.)
3. Click **Upload** → Upload your project as a ZIP file
4. Or use **Remote Download** if your code is on GitHub:
   - Click **Remote Download**
   - Enter your repository URL (e.g., `https://github.com/yourusername/event-connect/archive/refs/heads/main.zip`)
5. Extract the ZIP file
6. Move all files from the extracted folder to the root directory

### Method 2: Via Git (Recommended for updates)

#### Step 1: SSH into your server
```bash
ssh root@your-server-ip
```

#### Step 2: Clone Repository
```bash
cd /www/wwwroot
git clone https://github.com/yourusername/event-connect.git event-connect-staging
cd event-connect-staging
```

#### Step 3: Set Correct Permissions
```bash
chown -R www:www /www/wwwroot/event-connect-staging
chmod -R 755 /www/wwwroot/event-connect-staging
chmod -R 775 /www/wwwroot/event-connect-staging/storage
chmod -R 775 /www/wwwroot/event-connect-staging/bootstrap/cache
```

---

## 6. Configure Laravel

### Step 1: Install Dependencies
SSH into your server and run:
```bash
cd /www/wwwroot/event-connect-staging
composer install --no-dev --optimize-autoloader
```

### Step 2: Setup Environment File
```bash
cp .env.example .env
nano .env
```

### Step 3: Configure .env File
Update the following values in `.env`:

```env
APP_NAME="Event Connect Staging"
APP_ENV=staging
APP_KEY=
APP_DEBUG=false
APP_URL=https://staging-api.yourdomain.com

# Database Configuration (use credentials from Step 4)
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=event_connect_staging
DB_USERNAME=event_connect_user
DB_PASSWORD=your_database_password_here

# Laravel Sanctum (IMPORTANT for API authentication)
SANCTUM_STATEFUL_DOMAINS=staging-api.yourdomain.com,yourdomain.com
SESSION_DOMAIN=.yourdomain.com

# Cache & Queue
CACHE_STORE=file
QUEUE_CONNECTION=database

# Mail Configuration (optional for staging)
MAIL_MAILER=log
MAIL_FROM_ADDRESS="noreply@yourdomain.com"
MAIL_FROM_NAME="${APP_NAME}"

# Logging
LOG_CHANNEL=stack
LOG_LEVEL=error
```

**Save the file**: Press `CTRL+X`, then `Y`, then `Enter`

### Step 4: Generate Application Key
```bash
php artisan key:generate
```

### Step 5: Run Migrations and Seeders
```bash
php artisan migrate --force
php artisan db:seed --force
```

### Step 6: Optimize Laravel
```bash
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan optimize
```

### Step 7: Create Storage Link
```bash
php artisan storage:link
```

### Step 8: Set Final Permissions
```bash
chown -R www:www /www/wwwroot/event-connect-staging
chmod -R 755 /www/wwwroot/event-connect-staging
chmod -R 775 /www/wwwroot/event-connect-staging/storage
chmod -R 775 /www/wwwroot/event-connect-staging/bootstrap/cache
```

---

## 7. Web Server Configuration

### For Nginx (Recommended)

#### Step 1: Configure Site Settings
1. In aaPanel, go to **Website** → Find your site → Click **Settings**
2. Go to **Site Directory** tab:
   - **Running Directory**: Select `/public`
   - **Enable**: Anti-leech, PHP, Index
3. Click **Save**

#### Step 2: Configure Nginx Rewrite Rules
1. Still in **Settings** → Go to **Rewrite** tab
2. Select **Laravel 5** from the dropdown
3. Or manually add:
```nginx
location / {
    try_files $uri $uri/ /index.php?$query_string;
}

location ~ /\.(?!well-known).* {
    deny all;
}
```
4. Click **Save**

#### Step 3: Configure PHP
1. Go to **PHP** tab in site settings
2. Ensure **PHP Version** is set to PHP-8.3
3. Enable **PHP Fast-CGI Cache** (optional, for performance)

### For Apache

If using Apache:
1. Ensure `.htaccess` file exists in `/public` directory
2. Enable `mod_rewrite`:
```bash
a2enmod rewrite
systemctl restart apache2
```

---

## 8. SSL Certificate Setup

### Option 1: Free SSL with Let's Encrypt (Recommended)

1. In aaPanel, go to **Website** → Your site → **Settings**
2. Go to **SSL** tab
3. Click **Let's Encrypt**
4. Check your domain name
5. Enter your email
6. Click **Apply**
7. Wait for certificate to be issued (1-2 minutes)
8. Enable **Force HTTPS** redirect

### Option 2: Upload Custom SSL Certificate

1. If you have your own SSL certificate:
2. Go to **SSL** tab → **Other Certificate**
3. Paste your:
   - Certificate Key (Private Key)
   - Certificate (PEM format)
4. Click **Save**

---

## 9. Final Steps

### Step 1: Test API Endpoints

Visit in your browser or use curl:
```bash
# Test base URL
curl https://staging-api.yourdomain.com/api

# Test health check
curl https://staging-api.yourdomain.com/api/health

# Test categories endpoint
curl https://staging-api.yourdomain.com/api/categories
```

### Step 2: Setup Cron Jobs (for scheduled tasks)

1. In aaPanel, go to **Cron**
2. Click **Add Cron**
3. Fill in:
   - **Name**: Laravel Scheduler
   - **Type**: Shell Script
   - **Execution Cycle**: Every minute (N Minutes, Period: 1)
   - **Script**:
```bash
cd /www/wwwroot/event-connect-staging && php artisan schedule:run >> /dev/null 2>&1
```
4. Click **Submit**

### Step 3: Setup Queue Worker (for background jobs)

Create a supervisor configuration:
```bash
nano /etc/supervisor/conf.d/event-connect-worker.conf
```

Add:
```ini
[program:event-connect-worker]
process_name=%(program_name)s_%(process_num)02d
command=php /www/wwwroot/event-connect-staging/artisan queue:work --sleep=3 --tries=3 --max-time=3600
autostart=true
autorestart=true
stopasgroup=true
killasgroup=true
user=www
numprocs=1
redirect_stderr=true
stdout_logfile=/www/wwwroot/event-connect-staging/storage/logs/worker.log
stopwaitsecs=3600
```

Reload supervisor:
```bash
supervisorctl reread
supervisorctl update
supervisorctl start event-connect-worker:*
```

### Step 4: Configure CORS (if needed for frontend)

Edit `config/cors.php` or add to `.env`:
```env
CORS_ALLOWED_ORIGINS=https://yourdomain.com,https://staging.yourdomain.com
```

Then run:
```bash
php artisan config:cache
```

---

## 10. Troubleshooting

### Issue 1: 500 Internal Server Error

**Solution:**
```bash
# Check Laravel logs
tail -f /www/wwwroot/event-connect-staging/storage/logs/laravel.log

# Check permissions
chmod -R 775 storage bootstrap/cache
chown -R www:www /www/wwwroot/event-connect-staging

# Clear cache
php artisan cache:clear
php artisan config:clear
php artisan route:clear
php artisan view:clear
```

### Issue 2: Database Connection Error

**Solution:**
1. Verify database credentials in `.env`
2. Test database connection:
```bash
php artisan tinker
DB::connection()->getPdo();
```
3. Check if MySQL is running in aaPanel

### Issue 3: Permission Denied Errors

**Solution:**
```bash
cd /www/wwwroot/event-connect-staging
chown -R www:www .
chmod -R 755 .
chmod -R 775 storage bootstrap/cache
```

### Issue 4: Composer Memory Limit

**Solution:**
```bash
php -d memory_limit=-1 /usr/local/bin/composer install --no-dev
```

### Issue 5: API Returns 404 for all routes

**Solution:**
1. Check if `.htaccess` exists in `/public`
2. Ensure running directory is set to `/public`
3. Verify Nginx rewrite rules are set to Laravel 5

### Issue 6: CORS Errors

**Solution:**
Add to `.env`:
```env
SANCTUM_STATEFUL_DOMAINS=staging-api.yourdomain.com,yourdomain.com
SESSION_DOMAIN=.yourdomain.com
```

Clear config:
```bash
php artisan config:cache
```

---

## Deployment Checklist

Before going live, ensure:
- [ ] Database is created and credentials are correct
- [ ] `.env` file is configured properly
- [ ] `APP_DEBUG=false` in production
- [ ] SSL certificate is installed and HTTPS is forced
- [ ] Storage and cache directories are writable
- [ ] Migrations and seeders have run successfully
- [ ] Cron job is set up for scheduled tasks
- [ ] Queue worker is running (if using queues)
- [ ] API endpoints are accessible and responding
- [ ] CORS is configured for your frontend domain
- [ ] Backups are configured in aaPanel
- [ ] Monitoring is set up (optional but recommended)

---

## Maintenance Commands

### Update Application
```bash
cd /www/wwwroot/event-connect-staging
git pull origin main
composer install --no-dev --optimize-autoloader
php artisan migrate --force
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan optimize
```

### View Logs
```bash
tail -f storage/logs/laravel.log
```

### Clear All Cache
```bash
php artisan optimize:clear
```

### Restart Services
```bash
# In aaPanel: App Store → Installed → Restart PHP, Nginx, MySQL
```

---

## Security Best Practices

1. **Firewall**: Only open ports 80 (HTTP), 443 (HTTPS), and 22 (SSH)
2. **SSH**: Use key-based authentication, disable root login
3. **Database**: Only allow localhost connections
4. **SSL**: Always use HTTPS, enable HSTS
5. **Backups**: Enable automated backups in aaPanel (Database + Files)
6. **Updates**: Keep aaPanel, PHP, and all software updated
7. **Monitoring**: Set up uptime monitoring (UptimeRobot, Pingdom)

---

## Additional Resources

- [aaPanel Official Documentation](https://www.aapanel.com/new/index.html)
- [Laravel Deployment Documentation](https://laravel.com/docs/deployment)
- [Event Connect API Documentation](./README.md)

---

## Support

For issues specific to:
- **aaPanel**: Contact aaPanel support or check their forum
- **Event Connect API**: Refer to project documentation or contact development team

---

**Last Updated**: December 2025
**Version**: 1.0
