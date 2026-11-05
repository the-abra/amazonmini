# E-Commerce Platform

A lightweight e-commerce platform built with vanilla PHP 8.2 and MySQL. Features include product catalog, shopping cart, Stripe integration, order tracking, and role-based panels for customers, couriers, and administrators.

[![PHP](https://img.shields.io/badge/PHP-8.2-777bb4.svg)](https://www.php.net/)
[![License](https://img.shields.io/badge/License-MIT-blue.svg)](LICENSE)
[![Docker](https://img.shields.io/badge/Docker-ghcr.io-2496ED.svg?logo=docker)](https://ghcr.io/the-abra/amazonmini)
[![CI](https://github.com/the-abra/amazonmini/workflows/CI/badge.svg)](https://github.com/the-abra/amazonmini/actions)

<img width="1272" height="937" alt="image" src="https://github.com/user-attachments/assets/f4286fa3-4bee-4697-aa45-f54f31611ca2" />

---

## Overview

- **Stack:** Pure PHP 8.2 (MVC pattern, zero framework overhead), MySQL/MariaDB, Bootstrap 5.
- **Key Modules:** Product management, cart & checkout, Stripe Webhook processing, shipment tracking, and telemetry monitoring.
- **Security:** PDO prepared statements, CSRF protection, secure session handling, and restricted file access rules.

---

## Quick Start

### Using Docker Compose

```bash
docker compose -f docker/docker-compose.yml up -d

```

The application will be live at `http://localhost:8080`.

### Local Setup

**Requirements:** PHP 8.2+, MySQL / MariaDB, Apache (`mod_rewrite`).

```bash
# 1. Clone repository and setup config
git clone [https://github.com/the-abra/amazonmini.git](https://github.com/the-abra/amazonmini.git)
cd amazonmini
cp config/config.example.php config/config.local.php

# 2. Import database schema & seed data
mysql -u <user> -p <db_name> < database/schema.sql
mysql -u <user> -p <db_name> < database/seed.sql

# 3. Start PHP development server
php -S localhost:8080

```

---

## Demo Credentials

Default seeded test accounts (one-click login supported on `/login`):

| Role | Email | Password |
| --- | --- | --- |
| **Admin** | `admin@example.com` | `password` |
| **Customer** | `customer@example.com` | `password` |
| **Courier** | `courier@example.com` | `password` |

**Live Demo:** [btkodev121.42web.io](https://www.google.com/search?q=https://btkodev121.42web.io)

---

## Documentation

Comprehensive project guides are located in the [`docs/`](https://www.google.com/search?q=docs/) directory:

* [Setup Guide](https://www.google.com/search?q=docs/SETUP.md) — Detailed environment configuration and extension requirements.
* [Deployment Guide](https://www.google.com/search?q=docs/DEPLOYMENT.md) — Production setup and hosting instructions.
* [Deployment Checklist](https://www.google.com/search?q=docs/DEPLOYMENT_CHECKLIST.md) — Pre-flight release verification steps.
* [GitHub Actions](https://www.google.com/search?q=docs/GITHUB_ACTIONS.md) — CI pipeline and registry workflows.

---

## License

Distributed under the [MIT License](https://www.google.com/search?q=LICENSE).
