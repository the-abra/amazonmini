# GitHub Actions Guide

This project uses GitHub Actions for Continuous Integration (CI) and Docker image building/publishing.

---

## 📋 Available Workflows

### 1. CI Workflow (`.github/workflows/ci.yml`)

**Triggers:**
- Push to `main` or `develop` branches
- Pull requests to `main` or `develop` branches

**Jobs:**
- ✅ PHP syntax validation
- ✅ Database schema import and testing
- ✅ Security checks (no sensitive files)
- ✅ Required files verification
- ✅ Database connection testing

**Status:** [![CI](https://github.com/the-abra/amazonmini/workflows/CI/badge.svg)](https://github.com/the-abra/amazonmini/actions)

---

### 2. Docker Build & Publish (`.github/workflows/docker.yml`)

**Triggers:**
- Push to `main` branch
- Version tags (`v*.*.*`)
- Pull requests (build only, no push)
- Manual trigger via workflow_dispatch

**Jobs:**
- 🐳 Build multi-platform Docker images (amd64, arm64)
- 📦 Push to GitHub Container Registry (ghcr.io)
- 🏷️ Tag with version, branch, and SHA
- 📊 Generate SBOM (Software Bill of Materials)

**Registry:** `ghcr.io/the-abra/amazonmini`

---

## 🚀 Setup Instructions

### 1. Fork the Repository

```bash
git clone https://github.com/the-abra/amazonmini.git
cd amazonmini
```

### 2. Enable GitHub Actions

GitHub Actions are automatically enabled for public repositories. For private repos:

1. Go to **Settings** → **Actions** → **General**
2. Enable "Allow all actions and reusable workflows"

### 3. Configure Secrets (Optional)

For custom deployments, add secrets in **Settings** → **Secrets and variables** → **Actions**:

| Secret Name | Description | Required |
|-------------|-------------|----------|
| `STRIPE_SECRET_KEY` | Stripe test secret key | No |
| `STRIPE_PUBLISHABLE_KEY` | Stripe test publishable key | No |

**Note:** Docker publishing uses `GITHUB_TOKEN` automatically (no setup needed).

---

## 🐳 Docker Image Publishing

### Automatic Publishing

Images are automatically published to GitHub Container Registry when:

1. **Push to `main` branch:**
   - Tag: `ghcr.io/the-abra/amazonmini:latest`
   - Tag: `ghcr.io/the-abra/amazonmini:main`

2. **Create version tag:**
   ```bash
   git tag v1.0.0
   git push origin v1.0.0
   ```
   - Tag: `ghcr.io/the-abra/amazonmini:1.0.0`
   - Tag: `ghcr.io/the-abra/amazonmini:1.0`
   - Tag: `ghcr.io/the-abra/amazonmini:1`

3. **Commit SHA:**
   - Tag: `ghcr.io/the-abra/amazonmini:main-abc1234`

### Manual Trigger

Trigger Docker build manually:

1. Go to **Actions** tab
2. Select "Docker Build & Publish"
3. Click "Run workflow"
4. Select branch
5. Click "Run workflow"

---

## 📦 Using Published Images

### Pull from GHCR

```bash
# Pull latest
docker pull ghcr.io/the-abra/amazonmini:latest

# Pull specific version
docker pull ghcr.io/the-abra/amazonmini:v1.0.0

# Pull specific commit
docker pull ghcr.io/the-abra/amazonmini:main-abc1234
```

### Authentication (for private images)

```bash
# Login with GitHub token
echo $GITHUB_TOKEN | docker login ghcr.io -u USERNAME --password-stdin

# Or use Personal Access Token
docker login ghcr.io -u USERNAME
```

---

## 🔧 Customizing Workflows

### Modify CI Checks

Edit `.github/workflows/ci.yml`:

```yaml
- name: Run unit tests
  run: |
    php vendor/bin/phpunit tests/
```

### Add Code Quality Tools

```yaml
- name: PHP CodeSniffer
  run: |
    composer require --dev squizlabs/php_codesniffer
    vendor/bin/phpcs --standard=PSR12 app/
```

### Add Security Scanning

```yaml
- name: Security scan
  uses: aquasecurity/trivy-action@master
  with:
    image-ref: ghcr.io/the-abra/amazonmini:latest
    format: 'sarif'
    output: 'trivy-results.sarif'
```

---

## 📊 Workflow Status & Badges

### Add Badges to README

```markdown
[![CI](https://github.com/the-abra/amazonmini/workflows/CI/badge.svg)](https://github.com/the-abra/amazonmini/actions)
[![Docker](https://github.com/the-abra/amazonmini/workflows/Docker%20Build%20%26%20Publish/badge.svg)](https://github.com/the-abra/amazonmini/actions)
```

### View Workflow Runs

1. Go to **Actions** tab
2. Select workflow
3. View logs and artifacts

---

## 🐛 Troubleshooting

### CI Fails on Database Connection

**Issue:** MySQL service not ready

**Solution:** Increase health check retries in `ci.yml`:

```yaml
options: >-
  --health-retries=5
```

### Docker Build Fails

**Issue:** Multi-platform build fails

**Solution:** Use single platform:

```yaml
platforms: linux/amd64
```

### Image Push Permission Denied

**Issue:** Cannot push to GHCR

**Solution:** 
1. Check repository **Settings** → **Actions** → **Workflow permissions**
2. Enable "Read and write permissions"

### Image Not Public

**Issue:** Cannot pull image without authentication

**Solution:**
1. Go to **Packages** (your profile or organization)
2. Select `amazonmini` package
3. Click **Package settings**
4. Change visibility to **Public**

---

## 🎯 Best Practices

### 1. Semantic Versioning

Use semantic versioning for tags:

```bash
git tag v1.0.0   # Major release
git tag v1.1.0   # Minor update
git tag v1.1.1   # Patch/bugfix
```

### 2. Branch Protection

Enable branch protection for `main`:

1. **Settings** → **Branches** → **Add rule**
2. Branch name pattern: `main`
3. Enable:
   - ✅ Require status checks to pass
   - ✅ Require branches to be up to date
   - ✅ Require linear history

### 3. Pull Request Workflow

```bash
# Create feature branch
git checkout -b feature/new-feature

# Make changes and commit
git add .
git commit -m "feat: Add new feature"

# Push and create PR
git push origin feature/new-feature
```

### 4. Testing Before Merge

- ✅ Wait for CI to pass
- ✅ Review code changes
- ✅ Test Docker image locally
- ✅ Check security scan results

---

## 📝 Example: Complete Release Flow

### 1. Develop Feature

```bash
git checkout -b feature/payment-update
# Make changes
git commit -am "feat: Update payment flow"
git push origin feature/payment-update
```

### 2. Create Pull Request

- CI runs automatically
- Review changes
- Merge to `main`

### 3. Tag Release

```bash
git checkout main
git pull origin main
git tag v1.2.0
git push origin v1.2.0
```

### 4. Verify Published Image

```bash
docker pull ghcr.io/the-abra/amazonmini:v1.2.0
docker run -d -p 8080:80 ghcr.io/the-abra/amazonmini:v1.2.0
```

---

## 🔄 Continuous Deployment (Optional)

### Auto-deploy to Server

Add deployment job to `docker.yml`:

```yaml
deploy:
  needs: build-and-push
  runs-on: ubuntu-latest
  if: github.ref == 'refs/heads/main'
  steps:
    - name: Deploy to server
      uses: appleboy/ssh-action@master
      with:
        host: ${{ secrets.DEPLOY_HOST }}
        username: ${{ secrets.DEPLOY_USER }}
        key: ${{ secrets.DEPLOY_KEY }}
        script: |
          docker pull ghcr.io/the-abra/amazonmini:latest
          docker compose up -d
```

---

## 📚 Additional Resources

- [GitHub Actions Documentation](https://docs.github.com/en/actions)
- [Docker Build Push Action](https://github.com/docker/build-push-action)
- [GitHub Container Registry](https://docs.github.com/en/packages/working-with-a-github-packages-registry/working-with-the-container-registry)
- [Semantic Versioning](https://semver.org/)

---

**✅ GitHub Actions configured and ready!**

**Automatic CI/CD pipeline with Docker publishing to GHCR** 🚀
