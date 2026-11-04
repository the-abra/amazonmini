# GitHub Setup Guide

Quick guide to push this project to GitHub safely with CI/CD and Docker support.

---

## ✅ Pre-Push Security Checklist

The following items have been verified and secured:

### 1. Sensitive Files Excluded ✅

The following sensitive files are properly gitignored:

- `config/config.local.php` - Contains database credentials and API keys
- `*.sql` files (except database templates)
- `.env` files
- `vendor/` and `node_modules/`
- Log files and temporary files

### 2. Safe Files to Commit ✅

These files are safe and ready to commit:

- `config/config.example.php` - Template with placeholders
- `database/schema.sql` - Database structure only
- `database/seed.sql` - Demo data with hashed passwords
- `.gitignore` - Properly configured
- All application code in `app/`
- All documentation in `docs/`
- README.md, LICENSE, and other docs

### 3. No Sensitive Data in Repository ✅

- ✅ No plain-text passwords
- ✅ No production database credentials
- ✅ No real API keys (Stripe keys are gitignored)
- ✅ All demo passwords are bcrypt hashed
- ✅ Database seed uses demo accounts only

---

## 🚀 Push to GitHub

### Step 1: Create GitHub Repository

1. Go to [GitHub](https://github.com)
2. Click **"New repository"**
3. Enter repository name: `amazonmini`
4. Set visibility: **Public** (for GHCR and Actions)
5. **DO NOT** initialize with README (we already have one)
6. Click **"Create repository"**

### Step 2: Initialize Git (if not already done)

```bash
cd /home/abra/Desktop/e-ticaret-php
git init
git add .
git commit -m "Initial commit: E-commerce platform with CI/CD and Docker support"
```

### Step 3: Add Remote and Push

**For user `the-abra` and repo `amazonmini`:**

```bash
git remote add origin https://github.com/the-abra/amazonmini.git
git branch -M main
git push -u origin main
```

**Alternative with SSH:**

```bash
git remote add origin git@github.com:the-abra/amazonmini.git
git branch -M main
git push -u origin main
```

---

## 📝 After Pushing

### Add GitHub Topics

Improve discoverability by adding topics to your repository:

1. Go to https://github.com/the-abra/amazonmini
2. Click **Settings** → **About** (⚙️ icon)
3. Add topics:
   - `php`
   - `ecommerce`
   - `stripe`
   - `mysql`
   - `vanilla-php`
   - `shopping-cart`
   - `docker`
   - `github-actions`
   - `ci-cd`
   - `infinityfree`

### Enable GitHub Actions (Automatic)

GitHub Actions will automatically run after first push:

1. **CI Workflow** - Runs on every push/PR
   - PHP syntax validation
   - Database testing
   - Security checks
   
2. **Docker Workflow** - Builds and publishes images
   - Multi-platform builds (amd64, arm64)
   - Publishes to `ghcr.io/the-abra/amazonmini`
   - Auto-tags with versions

**View workflows:** https://github.com/the-abra/amazonmini/actions

### Enable GitHub Container Registry (GHCR)

After first Docker build:

1. Go to your profile → **Packages**
2. Find `amazonmini` package
3. Click **Package settings**
4. Set visibility to **Public**
5. Optionally link to repository

**Package URL:** https://github.com/the-abra/amazonmini/pkgs/container/amazonmini

### Enable GitHub Pages (Optional)

If you want to host documentation:

1. Go to **Settings** → **Pages**
2. Source: `main` branch, `/docs` folder
3. Save

---

## 🔒 Security Best Practices

### What to NEVER Commit

❌ **Never commit these files:**
- `config/config.local.php`
- `.env` or `.env.local`
- Database backups with real data
- Production API keys
- Private SSH keys
- User uploaded files

### What's Safe to Commit

✅ **Safe to commit:**
- All application code
- Configuration templates (`.example` files)
- Database schema and seed files (with demo data)
- Documentation
- Tests
- Public assets (CSS, JS, images)

### If You Accidentally Commit Secrets

If you accidentally commit sensitive data:

1. **Don't panic!** But act quickly
2. **Rotate credentials immediately:**
   - Change database passwords
   - Regenerate API keys
   - Update Stripe keys
3. **Remove from Git history:**
   ```bash
   git filter-branch --force --index-filter \
     "git rm --cached --ignore-unmatch config/config.local.php" \
     --prune-empty --tag-name-filter cat -- --all
   
   git push origin --force --all
   ```
4. **Use BFG Repo-Cleaner** for complex cases:
   ```bash
   java -jar bfg.jar --delete-files config.local.php
   ```

---

## 📊 Repository Structure

```
e-ticaret-php/
├── .github/             # GitHub workflows (if any)
├── app/
│   ├── Controllers/
│   ├── Models/
│   ├── Views/
│   └── Core/
├── config/
│   ├── config.example.php   ✅ Committed
│   └── config.local.php     ❌ Gitignored
├── database/
│   ├── schema.sql           ✅ Committed
│   └── seed.sql             ✅ Committed
├── docs/
│   ├── SETUP.md
│   └── DEPLOYMENT.md
├── public/
│   ├── assets/
│   ├── .htaccess
│   └── index.php
├── .gitignore               ✅ Committed
├── LICENSE                  ✅ Committed
└── README.md                ✅ Committed
```

---

## 🔄 Regular Git Workflow

### Making Changes

```bash
# Check status
git status

# Add specific files
git add app/Controllers/ProductController.php

# Or add all changes
git add .

# Commit with message
git commit -m "feat: Add product filtering feature"

# Push to GitHub
git push origin main
```

### Branching Strategy

For larger features:

```bash
# Create feature branch
git checkout -b feature/payment-gateway

# Make changes and commit
git add .
git commit -m "Add PayPal integration"

# Push branch
git push origin feature/payment-gateway

# Create Pull Request on GitHub
# After merge, delete branch
git checkout main
git pull origin main
git branch -d feature/payment-gateway
```

---

## 📋 Commit Message Convention

Use clear, descriptive commit messages:

```bash
# Feature
git commit -m "feat: Add wishlist functionality"

# Bug fix
git commit -m "fix: Resolve checkout cart total calculation"

# Documentation
git commit -m "docs: Update installation guide"

# Styling
git commit -m "style: Improve mobile responsive layout"

# Refactoring
git commit -m "refactor: Simplify database query logic"

# Performance
git commit -m "perf: Optimize product image loading"
```

---

## 🎯 Verification Commands

Before pushing, verify everything is secure:

```bash
# Check what will be committed
git status

# Verify config.local.php is NOT tracked
git ls-files | grep config.local.php
# (should return nothing)

# Check gitignore is working
git check-ignore -v config/config.local.php
# (should show it's ignored)

# Preview files to be pushed
git ls-tree -r main --name-only

# Check for accidentally staged secrets
git diff --cached
```

---

## ✅ Final Checklist

Before pushing to GitHub:

- [ ] Reviewed all files with `git status`
- [ ] Verified `config/config.local.php` is gitignored
- [ ] Updated README.md with your GitHub username
- [ ] Removed any TODO comments with sensitive info
- [ ] Tested locally one final time
- [ ] Created GitHub repository
- [ ] Added remote: `git remote add origin ...`
- [ ] Pushed: `git push -u origin main`
- [ ] Verified files on GitHub web interface
- [ ] Added repository topics/tags
- [ ] Updated repository description

---

## 🆘 Troubleshooting

### "Permission denied (publickey)"

**Solution:** Use HTTPS instead of SSH:
```bash
git remote set-url origin https://github.com/yourusername/e-commerce-php.git
```

### "Repository not found"

**Solution:** Check repository name and username:
```bash
git remote -v
git remote set-url origin https://github.com/CORRECT_USERNAME/CORRECT_REPO.git
```

### "Config file still showing up"

**Solution:** Remove from cache:
```bash
git rm --cached config/config.local.php
git commit -m "Remove config.local.php from tracking"
```

---

## 📚 Additional Resources

- [GitHub Documentation](https://docs.github.com)
- [Git Basics](https://git-scm.com/book/en/v2/Getting-Started-Git-Basics)
- [Gitignore Templates](https://github.com/github/gitignore)
- [BFG Repo-Cleaner](https://rtyley.github.io/bfg-repo-cleaner/)

---

**✅ You're ready to push to GitHub!**

**Repository will be public-friendly and secure.** 🔒🚀
