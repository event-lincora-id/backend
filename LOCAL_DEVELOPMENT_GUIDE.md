# Local Development Guide - Event Connect API

Quick guide to get the Event Connect Laravel backend running on your local machine.

## Prerequisites

Make sure you have installed:
- ✅ **PHP 8.3+** - [Download PHP](https://www.php.net/downloads)
- ✅ **Composer** - [Download Composer](https://getcomposer.org/download/)
- ✅ **MySQL/MariaDB** or **MAMP/XAMPP** - For database
- ✅ **Git** - [Download Git](https://git-scm.com/downloads)

You already have:
- ✅ PHP 8.3.10 installed
- ✅ Composer 2.6.5 installed

---

## Quick Start (5 Steps)

### Step 1: Install Dependencies
```bash
cd c:/Study/Kuliah/Semester-7/CP/be/event-connect
composer install
```

### Step 2: Setup Environment
```bash
# Copy environment file
cp .env.example .env

# Generate application key
php artisan key:generate
```

### Step 3: Configure Database

Edit the `.env` file and update database settings:

**For MAMP/XAMPP:**
```env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=8889
DB_DATABASE=event_connect
DB_USERNAME=root
DB_PASSWORD=root
```

**For standard MySQL:**
```env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=event_connect
DB_USERNAME=root
DB_PASSWORD=your_password
```

### Step 4: Create Database

**Option A: Using phpMyAdmin**
1. Open phpMyAdmin (usually at `http://localhost/phpmyadmin` or `http://localhost:8888/phpmyadmin`)
2. Click "New" to create database
3. Database name: `event_connect`
4. Collation: `utf8mb4_unicode_ci`
5. Click "Create"

**Option B: Using Command Line**
```bash
# Login to MySQL
mysql -u root -p

# Create database
CREATE DATABASE event_connect CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
exit;
```

### Step 5: Run Migrations & Start Server
```bash
# Run database migrations
php artisan migrate

# Seed database with sample data
php artisan db:seed

# Start development server
php artisan serve --port=8003
```

**🎉 Done!** Your API is now running at: `http://127.0.0.1:8003`

---

## Verify Installation

### Test API Endpoints

**1. Visit API Documentation:**
```
http://127.0.0.1:8003/api-docs
```

**2. Test Categories Endpoint:**
```bash
curl http://127.0.0.1:8003/api/categories
```

**3. Test in Browser:**
Open: `http://127.0.0.1:8003/api/categories`

You should see JSON response with sample categories.

---

## Common Commands

### Development Server
```bash
# Start server (default port 8000)
php artisan serve

# Start on specific port
php artisan serve --port=8003

# Start on specific host
php artisan serve --host=0.0.0.0 --port=8003
```

### Database Operations
```bash
# Run migrations
php artisan migrate

# Rollback last migration
php artisan migrate:rollback

# Fresh migration (drops all tables)
php artisan migrate:fresh

# Fresh migration with seeders
php artisan migrate:fresh --seed

# Run specific seeder
php artisan db:seed --class=CategorySeeder
```

### Cache Management
```bash
# Clear all cache
php artisan optimize:clear

# Clear specific cache
php artisan cache:clear
php artisan config:clear
php artisan route:clear
php artisan view:clear

# Rebuild cache
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

### Code Quality
```bash
# Run tests
php artisan test

# Format code with Laravel Pint
./vendor/bin/pint

# View logs in real-time
php artisan pail
```

---

## Project Structure

```
event-connect/
├── app/
│   ├── Http/
│   │   ├── Controllers/     # API Controllers
│   │   ├── Middleware/      # Custom Middleware
│   │   └── Requests/        # Form Requests
│   ├── Models/              # Eloquent Models
│   └── Services/            # Business Logic
├── config/                  # Configuration Files
├── database/
│   ├── migrations/          # Database Migrations
│   └── seeders/             # Database Seeders
├── public/                  # Public Assets & Entry Point
├── routes/
│   └── api.php              # API Routes
├── storage/
│   ├── app/                 # Application Files
│   ├── framework/           # Framework Cache
│   └── logs/                # Application Logs
├── .env                     # Environment Variables
└── composer.json            # PHP Dependencies
```

---

## Testing the API

### Using cURL

**Register User:**
```bash
curl -X POST "http://127.0.0.1:8003/api/auth/register" \
  -H "Content-Type: application/json" \
  -d '{
    "full_name": "Test User",
    "email": "test@example.com",
    "password": "password123",
    "password_confirmation": "password123"
  }'
```

**Login:**
```bash
curl -X POST "http://127.0.0.1:8003/api/auth/login" \
  -H "Content-Type: application/json" \
  -d '{
    "email": "test@example.com",
    "password": "password123"
  }'
```

**Get Categories (No Auth Required):**
```bash
curl "http://127.0.0.1:8003/api/categories"
```

**Get Profile (Auth Required):**
```bash
curl "http://127.0.0.1:8003/api/profile" \
  -H "Authorization: Bearer YOUR_TOKEN_HERE"
```

### Using Postman

1. **Import Collection:**
   - File → Import
   - Select `Event_Connect_API_Collection.postman_collection.json`

2. **Import Environment:**
   - Click Environments → Import
   - Select `Event_Connect_Environment.postman_environment.json`

3. **Set Environment:**
   - Select "Event Connect Environment" from dropdown
   - Update `base_url` to `http://127.0.0.1:8003`

4. **Test Endpoints:**
   - Start with "Register" or "Login"
   - Token will be automatically saved
   - Try other endpoints

---

## Troubleshooting

### Issue: `No application encryption key has been specified`
```bash
php artisan key:generate
```

### Issue: `SQLSTATE[HY000] [2002] Connection refused`
- Check if MySQL/MAMP/XAMPP is running
- Verify database credentials in `.env`
- Test connection: `php artisan tinker` then `DB::connection()->getPdo();`

### Issue: `Class "Facade\Ignition\IgnitionServiceProvider" not found`
```bash
composer install
```

### Issue: Permission denied on storage/logs
```bash
chmod -R 775 storage bootstrap/cache
```

### Issue: `composer install` fails with memory limit
```bash
php -d memory_limit=-1 composer install
```

### Issue: Port 8003 already in use
```bash
# Use different port
php artisan serve --port=8004

# Or find and kill process using port
# Windows:
netstat -ano | findstr :8003
taskkill /PID <PID> /F

# Mac/Linux:
lsof -ti:8003 | xargs kill -9
```

---

## Development Workflow

### 1. Pull Latest Changes
```bash
git pull origin main
composer install
php artisan migrate
```

### 2. Make Changes
- Edit controllers, models, routes, etc.
- Changes are reflected immediately (no need to restart server)

### 3. Database Changes
```bash
# Create new migration
php artisan make:migration create_something_table

# Edit migration file, then run
php artisan migrate
```

### 4. Clear Cache (if needed)
```bash
php artisan optimize:clear
```

### 5. Test Changes
```bash
php artisan test
```

---

## Environment Variables Reference

Key `.env` variables for local development:

```env
# Application
APP_NAME="Event Connect"
APP_ENV=local
APP_DEBUG=true
APP_URL=http://127.0.0.1:8003

# Database
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=8889                    # 3306 for standard MySQL, 8889 for MAMP
DB_DATABASE=event_connect
DB_USERNAME=root
DB_PASSWORD=root                # Your MySQL password

# Mail (use 'log' for development - emails saved to log file)
MAIL_MAILER=log

# Cache & Session
CACHE_STORE=file
SESSION_DRIVER=database
QUEUE_CONNECTION=database

# Laravel Sanctum
SANCTUM_STATEFUL_DOMAINS=127.0.0.1:8003
```

---

## Next Steps

1. ✅ **API is Running** - Explore the [API Documentation](http://127.0.0.1:8003/api-docs)
2. 📚 **Read Documentation** - Check [README.md](./README.md) for API endpoints
3. 🧪 **Test with Postman** - Import the collection and try endpoints
4. 🚀 **Deploy to Staging** - Follow [AAPANEL_STAGING_DEPLOYMENT_GUIDE.md](./AAPANEL_STAGING_DEPLOYMENT_GUIDE.md)

---

## Useful Links

- **API Docs**: http://127.0.0.1:8003/api-docs
- **Laravel Documentation**: https://laravel.com/docs
- **Laravel Sanctum**: https://laravel.com/docs/sanctum

---

**Happy Coding! 🎉**
