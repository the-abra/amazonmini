# Deployment Guide

Step-by-step guide for deploying to InfinityFree and other hosting providers.

---

## 🌐 InfinityFree Deployment (Free Hosting)

InfinityFree offers free PHP hosting with MySQL database, perfect for testing and small projects.

### Prerequisites

- InfinityFree account (free)
- FTP client (FileZilla recommended)
- Database SQL files ready

---

### Step 1: Create InfinityFree Account

1. Go to [InfinityFree.net](https://infinityfree.net)
2. Click **Sign Up** (completely free, no credit card)
3. Verify your email address
4. Create a new hosting account:
   - Choose a subdomain: `yourname.42web.io` or custom domain
   - Select server location
   - Complete CAPTCHA

---

### Step 2: Get FTP Credentials

1. Go to **Control Panel** → **FTP Details**
2. Note down:
   - **FTP Hostname:** `ftpupload.net`
   - **FTP Username:** `if0_xxxxxxxx`
   - **FTP Password:** (shown once, save it!)
   - **FTP Port:** `21`

---

### Step 3: Create MySQL Database

1. Go to **Control Panel** → **MySQL Databases**
2. Click **Create Database**
3. Enter database name (e.g., `shop`)
4. Click **Create**
5. **Save these credentials:**
   - **MySQL Host:** `sql210.infinityfree.com` (or similar)
   - **Database Name:** `if0_xxxxxxxx_shop`
   - **Username:** `if0_xxxxxxxx`
   - **Password:** (shown once)
   - **Port:** `3306`

---

### Step 4: Import Database

1. Go to **Control Panel** → **phpMyAdmin**
2. Select your database from left sidebar
3. Click **Import** tab
4. Choose file: `database/schema.sql`
5. Click **Go** (wait for completion)
6. Repeat for `database/seed.sql`

**Verify:** You should see tables like `users`, `products`, `orders`, etc.

---

### Step 5: Upload Files via FTP

#### Using FileZilla

1. Download [FileZilla Client](https://filezilla-project.org/)
2. Open FileZilla and connect:
   - **Host:** `ftpupload.net`
   - **Username:** `if0_xxxxxxxx`
   - **Password:** (from Step 2)
   - **Port:** `21`
3. Navigate to `/htdocs/` directory (right panel)
4. Upload these folders/files from local project:

**Required Files:**
```
✅ app/                  (entire directory)
✅ config/               (entire directory)
✅ public/               (entire directory)
✅ .htaccess             (from public/ folder)
✅ index.php             (from public/ folder)
```

**DO NOT Upload:**
```
❌ docker/
❌ database/
❌ docs/
❌ .git/
❌ .gitignore
❌ README.md
❌ cleanup_ctf.sql
❌ Makefile
```

**Important:** Upload the **contents** of `public/` directory directly to `/htdocs/`, not the folder itself!

Final structure on server:
```
/htdocs/
├── app/
├── config/
├── assets/
├── .htaccess
└── index.php
```

---

### Step 6: Configure Application

Create `config/config.local.php` on the server with your InfinityFree credentials:

**Option A: Upload via FTP**

1. Edit local `config/config.local.php`:

```php
<?php
return [
    'app_url' => 'https://yourname.42web.io',
    'app_env' => 'production',

    'db' => [
        'host' => 'sql210.infinityfree.com',  // Your MySQL host
        'port' => 3306,
        'database' => 'if0_xxxxxxxx_shop',    // Your database name
        'username' => 'if0_xxxxxxxx',          // Your username
        'password' => 'YOUR_DB_PASSWORD',      // Your password
        'charset' => 'utf8mb4',
    ],

    'stripe' => [
        'secret_key' => 'sk_test_YOUR_KEY',
        'publishable_key' => 'pk_test_YOUR_KEY',
        'webhook_secret' => 'whsec_YOUR_SECRET',
    ],

    // Production settings
    'demo_payments' => true,  // Keep test mode for safety
];
```

2. Upload to `/htdocs/config/config.local.php`

**Option B: Create directly in File Manager**

1. Go to **Control Panel** → **Online File Manager**
2. Navigate to `/htdocs/config/`
3. Create new file: `config.local.php`
4. Paste the configuration above
5. Save

---

### Step 7: Enable SSL (HTTPS)

1. Go to **Control Panel** → **SSL Certificates**
2. Click **Install SSL Certificate**
3. Select your domain/subdomain
4. Wait 5-10 minutes for activation
5. Verify: Visit `https://yourname.42web.io`

---

### Step 8: Test Your Site

Visit your site:
```
https://yourname.42web.io
```

**Test Checklist:**
- ✅ Homepage loads without errors
- ✅ Products display correctly
- ✅ Navigation works
- ✅ Login page accessible
- ✅ Demo login buttons work
- ✅ Admin panel accessible (after login)
- ✅ Cart functionality works

---

### Step 9: Clean Up CTF Tables (Optional)

If you imported old database files, remove CTF tables:

1. Go to **phpMyAdmin**
2. Select your database
3. Click **SQL** tab
4. Run this query:

```sql
SET FOREIGN_KEY_CHECKS = 0;
DROP TABLE IF EXISTS ctf_flags;
DROP TABLE IF EXISTS ctf_participants;
DROP TABLE IF EXISTS ctf_submissions;
SET FOREIGN_KEY_CHECKS = 1;
```

---

## 🔧 Troubleshooting

### Database Connection Error

**Error:** `Database connection error`

**Solutions:**
1. Verify database credentials in `config/config.local.php`
2. Check that `'port' => 3306` is included
3. Confirm database exists in phpMyAdmin
4. Wait 5 minutes (InfinityFree needs time to activate)

### 500 Internal Server Error

**Error:** White page or "500 Error"

**Solutions:**
1. Check `.htaccess` is uploaded to `/htdocs/`
2. Verify PHP version in Control Panel (should be 8.0+)
3. Check error logs in Control Panel → Error Logs
4. Ensure all files have correct permissions (644 for files, 755 for directories)

### 404 on All Pages

**Error:** Only homepage works, other pages show 404

**Solutions:**
1. Verify `.htaccess` is in `/htdocs/` (not in subdirectory)
2. Check mod_rewrite is enabled (usually default on InfinityFree)
3. Clear browser cache

### CSS/JS Not Loading

**Error:** Page loads but looks broken

**Solutions:**
1. Verify `assets/` folder is uploaded to `/htdocs/`
2. Check `app_url` in config matches your actual URL
3. Hard refresh browser: `Ctrl+Shift+R` (Windows) or `Cmd+Shift+R` (Mac)
4. Check browser console for errors (F12)

### Stripe Payment Fails

**Error:** Payment doesn't work

**Solutions:**
1. Keep `'demo_payments' => true` for testing
2. Use test keys (start with `sk_test_` and `pk_test_`)
3. InfinityFree may block some external APIs
4. Check Stripe dashboard for webhook errors

### Login Redirect Loop

**Error:** Keeps redirecting to login

**Solutions:**
1. Clear browser cookies
2. Check session settings in PHP
3. Verify database has user records (run seed.sql)

---

## 🌍 Other Hosting Providers

### Shared Hosting (cPanel)

Similar to InfinityFree but may have different paths:

1. Upload to `public_html/` instead of `/htdocs/`
2. Database host might be `localhost`
3. Follow same configuration steps

### VPS / Cloud (AWS, DigitalOcean, etc.)

1. Install LAMP stack (Linux, Apache, MySQL, PHP 8.0+)
2. Clone repository to `/var/www/html/`
3. Configure virtual host
4. Set up SSL with Let's Encrypt
5. Import database
6. Configure `config/config.local.php`

Example Apache virtual host:

```apache
<VirtualHost *:80>
    ServerName yourdomain.com
    DocumentRoot /var/www/html/public

    <Directory /var/www/html/public>
        Options -Indexes +FollowSymLinks
        AllowOverride All
        Require all granted
    </Directory>
</VirtualHost>
```

### Docker Deployment

Use the included Docker configuration:

```bash
docker-compose -f docker/docker-compose.yml up -d
```

---

## 🔒 Production Security Checklist

Before going live:

- ✅ Set `'app_env' => 'production'` in config
- ✅ Use strong database password
- ✅ Enable HTTPS/SSL
- ✅ Keep Stripe in test mode initially
- ✅ Remove `config.local.php` from version control
- ✅ Disable error display in production
- ✅ Set up regular database backups
- ✅ Update default demo account passwords
- ✅ Remove unused tables (CTF tables)
- ✅ Test all critical features

---

## 📊 Performance Optimization

### Enable Caching

The `.htaccess` file already includes:
- Gzip compression
- Browser caching headers
- ETags for static files

### Database Optimization

1. Add indexes to frequently queried columns
2. Use `EXPLAIN` to analyze slow queries
3. Enable query cache in MySQL

### CDN Setup (Optional)

For production sites, consider using a CDN:
- Cloudflare (free tier available)
- AWS CloudFront
- StackPath

---

## 🔄 Updating the Site

### Via FTP

1. Make changes locally
2. Test locally
3. Upload changed files via FTP
4. Clear browser cache
5. Test production site

### Via Git (VPS only)

```bash
cd /var/www/html
git pull origin main
sudo systemctl reload apache2
```

---

## 📧 Support

If you encounter issues:

1. Check [InfinityFree Community Forum](https://forum.infinityfree.net/)
2. Review server error logs in Control Panel
3. Check browser console (F12) for JavaScript errors
4. Verify database connection in phpMyAdmin

---

## ✅ Deployment Complete!

Your e-commerce platform is now live! 🚀

**Next Steps:**
- Test all features thoroughly
- Add your own products
- Customize branding
- Set up email notifications (if needed)
- Monitor error logs regularly

---

**Live Demo:** [https://btkodev121.42web.io](https://btkodev121.42web.io)

**Made with ❤️ and deployed successfully!**
