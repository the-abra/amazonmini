# Setup Guide

Complete installation guide for local development and production deployment.

---

## Prerequisites

Before you begin, ensure you have the following installed:

- **PHP 8.0 or higher**
- **MySQL 5.7+ or MariaDB 10.4+**
- **Apache with mod_rewrite** (or Nginx with URL rewriting)
- **Git** (for cloning the repository)

### Required PHP Extensions

- `pdo`
- `pdo_mysql`
- `json`
- `mbstring`

Check your PHP version and extensions:

```bash
php -v
php -m | grep -E "pdo|mysql|json|mbstring"
```

---

## Local Development Setup

### Step 1: Clone the Repository

```bash
git clone https://github.com/yourusername/e-ticaret-php.git
cd e-ticaret-php
```

### Step 2: Create Database

Create a new MySQL database:

```bash
mysql -u root -p
```

```sql
CREATE DATABASE ecommerce_db CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE USER 'ecom_user'@'localhost' IDENTIFIED BY 'your_secure_password';
GRANT ALL PRIVILEGES ON ecommerce_db.* TO 'ecom_user'@'localhost';
FLUSH PRIVILEGES;
EXIT;
```

### Step 3: Import Database Schema

```bash
mysql -u ecom_user -p ecommerce_db < database/schema.sql
mysql -u ecom_user -p ecommerce_db < database/seed.sql
```

The `seed.sql` file creates demo accounts and sample products.

### Step 4: Configure Application

Create your local configuration file:

```bash
cp config/config.example.php config/config.local.php
```

Edit `config/config.local.php`:

```php
<?php
return [
    'app_url' => 'http://localhost:8080',
    'app_env' => 'development',

    'db' => [
        'host' => '127.0.0.1',
        'port' => 3306,
        'database' => 'ecommerce_db',
        'username' => 'ecom_user',
        'password' => 'your_secure_password',
        'charset' => 'utf8mb4',
    ],

    'stripe' => [
        'secret_key' => 'sk_test_YOUR_SECRET_KEY',
        'publishable_key' => 'pk_test_YOUR_PUBLISHABLE_KEY',
        'webhook_secret' => 'whsec_YOUR_WEBHOOK_SECRET',
    ],
];
```

### Step 5: Get Stripe Test Keys (Optional)

1. Sign up at [Stripe](https://stripe.com)
2. Go to [Dashboard → Developers → API Keys](https://dashboard.stripe.com/test/apikeys)
3. Copy your **test** keys (they start with `sk_test_` and `pk_test_`)
4. Update the `stripe` section in `config/config.local.php`

**Note:** You can skip this step and use demo payment mode for testing.

### Step 6: Start Development Server

```bash
php -S localhost:8080
```

### Step 7: Access the Application

Open your browser and navigate to:

- **Frontend:** http://localhost:8080
- **Admin Panel:** http://localhost:8080/?route=admin/dashboard

---

## Demo Accounts

After importing `seed.sql`, you can use these accounts:

| Role     | Email                    | Password | Access Method           |
|----------|--------------------------|----------|-------------------------|
| Customer | customer@example.com     | password | One-click or email/pass |
| Admin    | admin@example.com        | password | One-click or email/pass |
| Courier  | courier@example.com      | password | One-click or email/pass |

### One-Click Demo Login

The fastest way to test the application:

1. Go to login page: http://localhost:8080/?route=auth/login
2. Click one of the demo buttons:
   - **Continue as Customer** - Browse and shop
   - **Continue as Admin** - Access admin dashboard
   - **Continue as Courier** - Manage deliveries

No email or password required!

---

## Apache Configuration

If you're using Apache, ensure `mod_rewrite` is enabled:

```bash
sudo a2enmod rewrite
sudo systemctl restart apache2
```

### Virtual Host Example

Create `/etc/apache2/sites-available/ecommerce.conf`:

```apache
<VirtualHost *:80>
    ServerName ecommerce.local
    DocumentRoot /path/to/e-ticaret-php/public

    <Directory /path/to/e-ticaret-php/public>
        Options -Indexes +FollowSymLinks
        AllowOverride All
        Require all granted
    </Directory>

    ErrorLog ${APACHE_LOG_DIR}/ecommerce-error.log
    CustomLog ${APACHE_LOG_DIR}/ecommerce-access.log combined
</VirtualHost>
```

Enable the site:

```bash
sudo a2ensite ecommerce
sudo systemctl reload apache2
```

Add to `/etc/hosts`:

```
127.0.0.1   ecommerce.local
```

Access at: http://ecommerce.local

---

## Nginx Configuration

If you're using Nginx, create `/etc/nginx/sites-available/ecommerce`:

```nginx
server {
    listen 80;
    server_name ecommerce.local;
    root /path/to/e-ticaret-php/public;
    index index.php;

    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    location ~ \.php$ {
        fastcgi_pass unix:/var/run/php/php8.0-fpm.sock;
        fastcgi_index index.php;
        fastcgi_param SCRIPT_FILENAME $document_root$fastcgi_script_name;
        include fastcgi_params;
    }

    location ~ /\.ht {
        deny all;
    }
}
```

Enable the site:

```bash
sudo ln -s /etc/nginx/sites-available/ecommerce /etc/nginx/sites-enabled/
sudo nginx -t
sudo systemctl reload nginx
```

---

## Environment Variables (Alternative Configuration)

Instead of `config.local.php`, you can use environment variables:

```bash
export DB_HOST=127.0.0.1
export DB_PORT=3306
export DB_NAME=ecommerce_db
export DB_USER=ecom_user
export DB_PASS=your_password
export STRIPE_SECRET_KEY=sk_test_...
export STRIPE_PUBLISHABLE_KEY=pk_test_...
export APP_ENV=development
export APP_URL=http://localhost:8080
```

The application automatically reads these variables via `config/env.php`.

---

## Troubleshooting

### Database Connection Error

**Error:** `Database connection error. Please verify database credentials`

**Solutions:**
1. Check database credentials in `config/config.local.php`
2. Verify MySQL is running: `sudo systemctl status mysql`
3. Test connection manually:
   ```bash
   mysql -h 127.0.0.1 -u ecom_user -p ecommerce_db
   ```
4. Check if port 3306 is specified in config

### 404 Errors on Pages

**Error:** All pages except homepage return 404

**Solutions:**
1. Enable Apache mod_rewrite:
   ```bash
   sudo a2enmod rewrite
   sudo systemctl restart apache2
   ```
2. Check `.htaccess` exists in `public/` directory
3. Verify `AllowOverride All` in Apache config

### Blank Page / White Screen

**Error:** Page loads but shows nothing

**Solutions:**
1. Check PHP error logs:
   ```bash
   tail -f /var/log/apache2/error.log
   ```
2. Enable error display temporarily in `public/index.php`:
   ```php
   ini_set('display_errors', 1);
   error_reporting(E_ALL);
   ```
3. Check file permissions:
   ```bash
   chmod -R 755 /path/to/e-ticaret-php
   ```

### Stripe Payment Fails

**Error:** Payment doesn't process

**Solutions:**
1. Verify test keys are used (start with `sk_test_` and `pk_test_`)
2. Check Stripe dashboard for errors
3. Enable demo payment mode in `config/config.local.php`:
   ```php
   'demo_payments' => true,
   ```

### Permission Denied Errors

**Error:** Cannot write to directory

**Solutions:**
1. Check ownership:
   ```bash
   sudo chown -R www-data:www-data /path/to/e-ticaret-php
   ```
2. Set proper permissions:
   ```bash
   chmod -R 755 /path/to/e-ticaret-php
   ```

---

## Testing the Installation

### Quick Health Check

1. **Homepage loads:** http://localhost:8080
2. **Products display:** http://localhost:8080/?route=product/index
3. **Login works:** http://localhost:8080/?route=auth/login
4. **Admin accessible:** http://localhost:8080/?route=admin/dashboard
5. **Demo login buttons work:** Click "Continue as Customer"

### Test Payment Flow

1. Add product to cart
2. Go to checkout
3. Use Stripe test card: `4242 4242 4242 4242`
4. Verify order appears in admin panel

---

## Next Steps

- 📖 Read [DEPLOYMENT.md](DEPLOYMENT.md) for production deployment
- 🎨 Customize templates in `app/Views/`
- 🔧 Configure email settings (if needed)
- 📊 Set up analytics tracking
- 🔒 Configure SSL certificate for production

---

## Development Tips

### File Structure

- **Controllers:** `app/Controllers/` - Handle HTTP requests
- **Models:** `app/Models/` - Database operations
- **Views:** `app/Views/` - HTML templates
- **Core:** `app/Core/` - Framework components
- **Assets:** `public/assets/` - CSS, JS, images

### Database Queries

All queries use prepared statements:

```php
$stmt = Database::query(
    "SELECT * FROM products WHERE category_id = ?",
    [$categoryId]
);
$products = $stmt->fetchAll();
```

### Routing

Routes are defined in `app/Core/Router.php`:

```php
$router->add('product/view', 'ProductController@view');
```

Access via: `?route=product/view&id=123`

---

## Getting Help

- 📝 Check existing [GitHub Issues](https://github.com/yourusername/e-ticaret-php/issues)
- 📚 Read the documentation in `docs/`
- 💬 Open a new issue for bugs or questions

---

**Happy coding! 🚀**
