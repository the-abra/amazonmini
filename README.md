# E-Commerce Platform

A modern, lightweight e-commerce platform built with vanilla PHP 8.0+ and MySQL. Features include product catalog, shopping cart, checkout with Stripe integration, order management, and a complete admin dashboard.

[![PHP](https://img.shields.io/badge/PHP-8.0%2B-777bb4.svg)](https://www.php.net/)
[![License](https://img.shields.io/badge/License-MIT-blue.svg)](LICENSE)
[![Docker](https://img.shields.io/badge/Docker-Available-2496ED.svg?logo=docker)](https://ghcr.io/the-abra/amazonmini)
[![CI](https://github.com/the-abra/amazonmini/workflows/CI/badge.svg)](https://github.com/the-abra/amazonmini/actions)

<img width="1272" height="937" alt="image" src="https://github.com/user-attachments/assets/f4286fa3-4bee-4697-aa45-f54f31611ca2" />


---

## 🚀 Features

### Customer Features
- 🛍️ Product browsing with categories and search
- 🛒 Shopping cart with real-time updates
- 💳 Secure checkout with Stripe payment integration
- 📦 Order tracking and history
- ⭐ Product reviews and ratings
- 💝 Wishlist functionality

### Admin Features
- 📊 Dashboard with sales analytics
- 📦 Product management (CRUD)
- 🏷️ Category management
- 👥 Customer management
- 📋 Order management and status updates
- 🚚 Shipment tracking

### Technical Features
- ✅ Vanilla PHP 8.0+ (no framework dependencies)
- 🔒 Prepared statements for SQL injection prevention
- 🎨 Responsive Bootstrap 5 UI
- 📱 Mobile-friendly design
- 🔐 Session-based authentication
- 📊 Built-in telemetry and performance monitoring
- 🌐 Clean URL routing with `.htaccess`

---

## 🎮 Demo Access

The application includes **one-click demo login** for easy testing:

| Role | Access |
|------|--------|
| 🛒 **Customer** | Click "Continue as Customer" on login page |
| 📦 **Admin** | Click "Continue as Admin" on login page |
| 🚚 **Courier** | Click "Continue as Courier" on login page |

**Live Demo:** [https://btkodev121.42web.io](https://btkodev121.42web.io)

**No email or password required!** Just click and explore.

---

## 📋 Requirements

- **PHP:** 8.0 or higher
- **Database:** MySQL 5.7+ or MariaDB 10.4+
- **Web Server:** Apache with mod_rewrite enabled
- **Extensions:** PDO, PDO_MySQL, JSON, mbstring
- **No Composer Required!** Pure PHP implementation

---

## 🛠️ Installation

### 🐳 Docker (Recommended)

The easiest way to run the application:

```bash
# Pull and run from GitHub Container Registry
docker compose up -d

# Access the application
open http://localhost:8080
```

**Using the pre-built image:**

```bash
docker run -d -p 8080:80 \
  -e DB_HOST=your-db-host \
  -e DB_NAME=ecommerce_db \
  -e DB_USER=ecom_user \
  -e DB_PASS=your-password \
  ghcr.io/the-abra/amazonmini:latest
```

### 📦 Manual Installation

1. **Clone the repository**
   ```bash
   git clone https://github.com/the-abra/amazonmini.git
   cd amazonmini
   ```

2. **Create local configuration**
   ```bash
   cp config/config.example.php config/config.local.php
   ```

3. **Configure database** in `config/config.local.php`:
   ```php
   'db' => [
       'host' => '127.0.0.1',
       'port' => 3306,
       'database' => 'your_database_name',
       'username' => 'your_username',
       'password' => 'your_password',
       'charset' => 'utf8mb4',
   ],
   ```

4. **Import database schema**
   ```bash
   mysql -u your_username -p your_database_name < database/schema.sql
   mysql -u your_username -p your_database_name < database/seed.sql
   ```

5. **Configure Stripe** (optional, for payment testing):
   - Get test keys from [Stripe Dashboard](https://dashboard.stripe.com/test/apikeys)
   - Update `stripe` section in `config/config.local.php`

6. **Start development server**
   ```bash
   php -S localhost:8080 -t public
   ```

7. **Access the application**
   - Frontend: http://localhost:8080
   - Admin: http://localhost:8080/?route=admin/dashboard

---

## 🌐 Deployment

### 🐳 Docker Deployment

**Pull from GitHub Container Registry:**

```bash
docker pull ghcr.io/the-abra/amazonmini:latest
```

**Run with Docker Compose:**

```yaml
version: '3.8'
services:
  app:
    image: ghcr.io/the-abra/amazonmini:latest
    ports:
      - "8080:80"
    environment:
      DB_HOST: db
      DB_NAME: ecommerce_db
      DB_USER: ecom_user
      DB_PASS: your_password
```

### InfinityFree Hosting

See [docs/DEPLOYMENT.md](docs/DEPLOYMENT.md) for detailed deployment instructions.

Quick steps:
1. Upload all files to `/htdocs/` directory via FTP
2. Create MySQL database in control panel
3. Configure `config/config.local.php` with production settings
4. Import database schema via phpMyAdmin
5. Set `app_env` to `'production'` in config

---

## 📁 Project Structure

```
e-ticaret-php/
├── app/
│   ├── Controllers/     # Request handlers
│   ├── Models/          # Database models
│   ├── Views/           # PHP templates
│   └── Core/            # Core framework classes
├── config/
│   ├── config.example.php   # Configuration template
│   ├── config.local.php     # Local config (git-ignored)
│   └── env.php              # Environment loader
├── database/
│   ├── schema.sql       # Database structure
│   └── seed.sql         # Sample data
├── docs/                # Documentation
├── public/
│   ├── assets/          # CSS, JS, images
│   ├── .htaccess        # URL rewriting rules
│   └── index.php        # Application entry point
└── README.md
```

---

## 🔧 Configuration

Configuration files are loaded in this order:

1. `config/config.example.php` - Base configuration
2. `config/config.local.php` - Local overrides (git-ignored)
3. Environment variables (Docker, OS)

### Key Configuration Options

| Option | Description | Default |
|--------|-------------|---------|
| `app_url` | Base URL of application | `http://localhost:8080` |
| `app_env` | Environment mode | `development` |
| `demo_payments` | Enable demo payment mode | `true` |
| `items_per_page` | Products per page | `12` |
| `currency` | Currency code | `USD` |

---

## 🔒 Security

- ✅ Prepared statements prevent SQL injection
- ✅ Password hashing with `password_hash()`
- ✅ CSRF protection on forms
- ✅ XSS prevention with `htmlspecialchars()`
- ✅ Session security settings
- ✅ `.htaccess` security headers
- ✅ Sensitive config files excluded from Git

**Important:** Never commit `config/config.local.php` or real API keys to version control.

---

## 🧪 Testing

### Demo Accounts

Default credentials after running `seed.sql`:

| Role     | Email                    | Password | One-Click Login |
|----------|--------------------------|----------|-----------------|
| Customer | customer@example.com     | password | ✅ Available    |
| Admin    | admin@example.com        | password | ✅ Available    |
| Courier  | courier@example.com      | password | ✅ Available    |

### Payment Testing (Stripe Test Mode)

Use these test card numbers:

- **Success:** `4242 4242 4242 4242`
- **Decline:** `4000 0000 0000 0002`
- **3D Secure:** `4000 0025 0000 3155`

**Expiry:** Any future date | **CVC:** Any 3 digits

---

## 📚 Documentation

- [Setup Guide](docs/SETUP.md) - Detailed installation instructions
- [Deployment Guide](docs/DEPLOYMENT.md) - Production deployment steps

---

## 🐳 Docker Hub & GitHub Container Registry

### Available Images

**GitHub Container Registry (Recommended):**
```bash
docker pull ghcr.io/the-abra/amazonmini:latest
```

### Image Tags

- `latest` - Latest stable release from main branch
- `main` - Latest build from main branch
- `v1.0.0` - Specific version tags
- `main-abc1234` - Specific commit SHA

### Quick Start with Docker

```bash
# Using docker-compose (includes MySQL)
docker compose up -d

# Or run standalone with existing database
docker run -d \
  --name amazonmini \
  -p 8080:80 \
  -e DB_HOST=your-mysql-host \
  -e DB_PORT=3306 \
  -e DB_NAME=ecommerce_db \
  -e DB_USER=ecom_user \
  -e DB_PASS=your-password \
  -e STRIPE_SECRET_KEY=sk_test_... \
  -e STRIPE_PUBLISHABLE_KEY=pk_test_... \
  ghcr.io/the-abra/amazonmini:latest

# Access at http://localhost:8080
```

### Environment Variables

| Variable | Description | Default |
|----------|-------------|---------|
| `APP_ENV` | Environment mode | `production` |
| `APP_URL` | Base URL | `http://localhost:8080` |
| `DB_HOST` | MySQL hostname | `mysql` |
| `DB_PORT` | MySQL port | `3306` |
| `DB_NAME` | Database name | `ecommerce_db` |
| `DB_USER` | Database user | `ecom_user` |
| `DB_PASS` | Database password | `ecom_pass` |
| `STRIPE_SECRET_KEY` | Stripe secret key | - |
| `STRIPE_PUBLISHABLE_KEY` | Stripe publishable key | - |

### Build Your Own Image

```bash
# Build production image
docker build -t amazonmini:local .

# Build development image
docker build --target development -t amazonmini:dev .

# Multi-platform build
docker buildx build --platform linux/amd64,linux/arm64 -t amazonmini:multi .
```

---

## 🤝 Contributing

1. Fork the repository
2. Create your feature branch (`git checkout -b feature/AmazingFeature`)
3. Commit your changes (`git commit -m 'Add some AmazingFeature'`)
4. Push to the branch (`git push origin feature/AmazingFeature`)
5. Open a Pull Request

---

## 📝 License

This project is open source and available under the [MIT License](LICENSE).

---

## 🙏 Acknowledgments

- Built with vanilla PHP for maximum compatibility
- Bootstrap 5 for responsive UI
- Stripe for payment processing
- InfinityFree for free hosting demo

---

## 📧 Support

For issues and questions:
- Open an [Issue](https://github.com/the-abra/amazonmini/issues)
- Check [Documentation](docs/)

---

**⭐ Star this repo if you find it helpful!**

**Made with ❤️ using Vanilla PHP**
