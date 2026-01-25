# LMS Deployment Guide (Hostinger)

## 1. Environment Configuration
Ensure `.env` matches production requirements:
```ini
APP_NAME="LMS Production"
APP_ENV=production
APP_KEY=base64:... (Run php artisan key:generate)
APP_DEBUG=false
APP_URL=https://your-domain.com

DB_CONNECTION=mysql
DB_HOST=127.0.0.1 (or Hostinger DB IP)
DB_PORT=3306
DB_DATABASE=u12345_lms
DB_USERNAME=u12345_user
DB_PASSWORD=strong_password

FILESYSTEM_DISK=public
SESSION_SECURE_COOKIE=true
```

## 2. Hostinger Shared Hosting Deployment
Since Node.js/Composer may be limited, we recommend the "Build & Upload" strategy.

### Step 1: Local Build
Run these commands on your local machine:
```bash
# Install PHP dependencies optimized
composer install --no-dev --optimize-autoloader

# Build Frontend Assets
npm ci
npm run build
```

### Step 2: Prepare Files
1. Compress the entire project (except `node_modules`, `.git`, `tests`) into `project.zip`.
   - Include `public/build` folder!
   - Include `vendor` folder.

### Step 3: Upload & Extract
1. Log in to Hostinger File Manager.
2. Upload `project.zip` to a folder *above* `public_html` (e.g., `/home/u123/domains/domain.com/lms_app`).
3. Extract the zip.

### Step 4: Public Directory Setup
1. Copy everything inside `lms_app/public/` to `public_html/`.
2. Edit `public_html/index.php`:
   ```php
   // Update paths to point to lms_app folder
   require __DIR__.'/../lms_app/vendor/autoload.php';
   $app = require __DIR__.'/../lms_app/bootstrap/app.php';
   ```

### Step 5: Database
1. Create MySQL Database/User in Hostinger Dashboard.
2. Import `storage/database.sql` (if you exported it) OR run migrations via SSH if allowed.
3. Update `.env` in `lms_app/` with credentials.

### Step 6: Symlink Storage (Critical)
If SSH is available:
```bash
ln -s /home/u123/domains/domain.com/lms_app/storage/app/public /home/u123/domains/domain.com/public_html/storage
```
If NO SSH:
Use a PHP script to create symlink:
```php
<?php
symlink('/home/u123/domains/domain.com/lms_app/storage/app/public', '/home/u123/domains/domain.com/public_html/storage');
echo "Linked";
?>
```

## 3. Hostinger VPS Deployment
Prerequisites: PHP 8.2, Nginx/Apache, MySQL, Composer.

```bash
# 1. Clone Repo
git clone https://github.com/your/repo.git /var/www/lms
cd /var/www/lms

# 2. Install Deps
composer install --no-dev
npm ci
npm run build

# 3. Setup Env
cp .env.example .env
nano .env # (Fill DB details)
php artisan key:generate

# 4. Migrate & Seed
php artisan migrate --force
php artisan db:seed --class=BadgeSeeder

# 5. Permissions
chown -R www-data:www-data storage bootstrap/cache
chmod -R 775 storage bootstrap/cache

# 6. Storage Link
php artisan storage:link
```

## 4. Limits
Ensure PHP settings allow large uploads (for MP4):
`php.ini`:
```ini
upload_max_filesize = 512M
post_max_size = 512M
memory_limit = 256M
```
