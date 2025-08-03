# 🚀 CRMAIze Deployment Guide

This guide will help you deploy CRMAIze to various hosting platforms for free.

## 🎯 Recommended: Railway Deployment

Railway offers the best free tier for PHP applications with automatic deployments.

### Step 1: Prepare Your Repository

1. **Push to GitHub** (if not already done):
   ```bash
   git add .
   git commit -m "Ready for deployment"
   git push origin main
   ```

### Step 2: Deploy to Railway

1. **Sign up for Railway**: Visit [railway.app](https://railway.app) and sign up with GitHub
2. **Create New Project**: Click "New Project" → "Deploy from GitHub repo"
3. **Select Repository**: Choose your CRMAIze repository
4. **Add Database**: Click "New" → "Database" → "Add PostgreSQL" (or MySQL)
5. **Configure Environment Variables**:
   ```
   APP_ENV=production
   APP_DEBUG=false
   DB_DSN=mysql:host=mysql.railway.internal;port=3306;dbname=railway
   DB_USERNAME=root
   DB_PASSWORD=[auto-generated]
   ```

### Step 3: Configure Database

Railway will provide database credentials. Update your `.env` or set environment variables:

```bash
# In Railway dashboard, go to Variables tab and add:
DB_DSN=mysql:host=mysql.railway.internal;port=3306;dbname=railway
DB_USERNAME=root
DB_PASSWORD=your_generated_password

# Email settings (optional)
SMTP_HOST=smtp.gmail.com
SMTP_PORT=587
SMTP_USERNAME=your_email@gmail.com
SMTP_PASSWORD=your_app_password
MAIL_FROM_ADDRESS=your_email@gmail.com
MAIL_FROM_NAME=CRMAIze
```

### Step 4: Deploy

Railway will automatically deploy your app. The build process will:

1. Install PHP dependencies with Composer
2. Set up directories and permissions
3. Initialize the database
4. Create demo users

Your app will be available at: `https://your-app-name.up.railway.app`

---

## 🌟 Alternative: Render Deployment

### Step 1: Create Web Service

1. Sign up at [render.com](https://render.com)
2. Connect your GitHub repository
3. Create a "Web Service"
4. Configure:
   - **Build Command**: `composer install --no-dev --optimize-autoloader`
   - **Start Command**: `php -S 0.0.0.0:$PORT -t public`
   - **Environment**: `production`

### Step 2: Add Database

1. Create a PostgreSQL database on Render
2. Get connection details and update environment variables

---

## 🏠 Traditional Hosting (000webhost, InfinityFree)

### Step 1: Prepare Files

1. **Download your project** as ZIP or use FTP
2. **Upload to public_html** or equivalent directory
3. **Extract files** in the web root

### Step 2: Database Setup

1. **Create MySQL database** in hosting control panel
2. **Import database schema** using phpMyAdmin:
   ```sql
   -- Run the SQL commands from src/Service/DatabaseService.php
   -- Or use the web installer at yoursite.com/install.php
   ```

### Step 3: Configure

1. **Update .env file** with hosting database credentials:

   ```
   DB_DSN=mysql:host=localhost;dbname=your_db_name
   DB_USERNAME=your_db_user
   DB_PASSWORD=your_db_password
   ```

2. **Set permissions**:
   ```bash
   chmod 755 data/
   chmod 755 cache/
   chmod 644 data/crmaize.db
   ```

---

## 🔧 Environment Variables Reference

### Required Variables

```bash
APP_ENV=production
APP_DEBUG=false
DB_DSN=your_database_connection_string
DB_USERNAME=your_db_username
DB_PASSWORD=your_db_password
```

### Optional Email Variables

```bash
SMTP_HOST=smtp.gmail.com
SMTP_PORT=587
SMTP_USERNAME=your_email@gmail.com
SMTP_PASSWORD=your_app_password
SMTP_ENCRYPTION=tls
MAIL_FROM_ADDRESS=your_email@gmail.com
MAIL_FROM_NAME=CRMAIze
```

### Optional AI/Campaign Variables

```bash
AI_ENABLED=true
CAMPAIGN_MAX_RECIPIENTS=1000
CAMPAIGN_RATE_LIMIT=100
```

---

## 📱 PWA Configuration

After deployment, your PWA will be available with:

- **Install prompts** on mobile devices
- **Offline functionality** with service worker
- **Push notifications** (configure in browser)
- **App shortcuts** on home screen

---

## 🐛 Troubleshooting

### Common Issues

1. **Database Connection Error**:

   - Check DB_DSN format
   - Verify database credentials
   - Ensure database server is accessible

2. **File Permissions**:

   ```bash
   chmod 755 data/ cache/
   chmod 644 .env
   ```

3. **Composer Dependencies**:

   ```bash
   composer install --no-dev --optimize-autoloader
   ```

4. **Missing Tables**:
   - Run `php scripts/install.php`
   - Or visit `/install.php` in browser

### Railway-Specific Issues

1. **Build Fails**: Check `nixpacks.toml` configuration
2. **Database Connection**: Use internal hostname (e.g., `mysql.railway.internal`)
3. **Environment Variables**: Set in Railway dashboard, not `.env` file

### Performance Optimization

1. **Enable OPcache** (if available):

   ```php
   opcache.enable=1
   opcache.memory_consumption=128
   ```

2. **Optimize Composer**:

   ```bash
   composer install --no-dev --optimize-autoloader --classmap-authoritative
   ```

3. **Configure Caching**:
   - Enable Twig template caching
   - Use Redis/Memcached if available

---

## 🎉 Post-Deployment

1. **Test the application**: Visit your deployed URL
2. **Login with demo credentials**:
   - Admin: `admin` / `admin123`
   - Marketer: `marketer` / `marketer123`
3. **Configure email settings** (if using SMTP)
4. **Import sample data** or add your own customers
5. **Test PWA installation** on mobile devices

---

## 🔒 Security Checklist

- [ ] Set `APP_DEBUG=false` in production
- [ ] Use strong database passwords
- [ ] Enable HTTPS (automatic on Railway/Render)
- [ ] Configure proper file permissions
- [ ] Regular database backups
- [ ] Monitor application logs

---

Your CRMAIze application should now be live and accessible! 🎊
