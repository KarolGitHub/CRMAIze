# 🚀 Deploy CRMAIze on Render

This guide will walk you through deploying CRMAIze on Render's free tier with PostgreSQL database.

## 🎯 Why Render?

- ✅ **750 hours/month free** for web services
- ✅ **Free PostgreSQL database** (90 days, then $7/month)
- ✅ **Automatic deployments** from GitHub
- ✅ **Built-in SSL/HTTPS**
- ✅ **Environment variables** management
- ✅ **Great PHP support**

## 📋 Prerequisites

1. **GitHub Account** - Your code needs to be in a GitHub repository
2. **Render Account** - Sign up at [render.com](https://render.com)

## 🚀 Step-by-Step Deployment

### Step 1: Push Your Code to GitHub

If you haven't already, push your CRMAIze project to GitHub:

```bash
# Initialize git repository (if not done)
git init
git add .
git commit -m "Initial CRMAIze commit"

# Add your GitHub repository as remote
git remote add origin https://github.com/yourusername/crmaize.git
git branch -M main
git push -u origin main
```

### Step 2: Create PostgreSQL Database

1. **Login to Render** at [render.com](https://render.com)
2. **Click "New +"** in the dashboard
3. **Select "PostgreSQL"**
4. **Configure database:**
   - **Name**: `crmaize-db`
   - **Database**: `crmaize`
   - **User**: `crmaize` (or keep default)
   - **Region**: Choose closest to your users
   - **Plan**: Free (90 days)
5. **Click "Create Database"**
6. **Wait for database to be ready** (takes ~2-3 minutes)

### Step 3: Create Web Service

1. **Click "New +" again**
2. **Select "Web Service"**
3. **Connect your GitHub repository:**
   - Click "Connect account" if first time
   - Select your CRMAIze repository
4. **Configure web service:**
   - **Name**: `crmaize-app` (or your preferred name)
   - **Region**: Same as your database
   - **Branch**: `main`
   - **Runtime**: `PHP`

### Step 4: Configure Build & Deploy Settings

In the web service configuration:

1. **Build Command**:

   ```bash
   composer install --no-dev --optimize-autoloader && chmod +x build.sh && ./build.sh
   ```

2. **Start Command**:
   ```bash
   php -S 0.0.0.0:$PORT -t public
   ```

### Step 5: Set Environment Variables

In your web service settings, go to the **Environment** tab and add:

#### Required Variables

```bash
APP_ENV=production
APP_DEBUG=false
```

#### Database Variables

1. **Go to your PostgreSQL database** in Render dashboard
2. **Copy the "External Database URL"** (starts with `postgres://`)
3. **Add to your web service environment variables:**
   ```bash
   DATABASE_URL=postgres://crmaize:password@dpg-xxxxx-a.oregon-postgres.render.com/crmaize
   ```

#### Optional Email Variables (for real email sending)

```bash
SMTP_HOST=smtp.gmail.com
SMTP_PORT=587
SMTP_USERNAME=your_email@gmail.com
SMTP_PASSWORD=your_app_password
SMTP_ENCRYPTION=tls
MAIL_FROM_ADDRESS=your_email@gmail.com
MAIL_FROM_NAME=CRMAIze
```

### Step 6: Deploy!

1. **Click "Create Web Service"**
2. **Watch the build logs** - it will:
   - Install PHP dependencies
   - Run the installation script
   - Create database tables
   - Set up demo users
   - Import sample data
3. **Wait for deployment** (usually 3-5 minutes)
4. **Your app will be live** at `https://your-app-name.onrender.com`

## 🎉 Post-Deployment

### Test Your Deployment

1. **Visit your app URL**
2. **Login with demo credentials:**
   - **Admin**: `admin` / `admin123`
   - **Marketer**: `marketer` / `marketer123`
3. **Test key features:**
   - Dashboard loads
   - Customer data displays
   - Campaigns can be created
   - Analytics work
   - PWA installation prompt appears on mobile

### Configure Email (Optional)

1. **Go to Email Settings** in your deployed app
2. **Configure SMTP settings** if you want to send real emails
3. **Test the connection**

### Security Checklist

- [ ] `APP_DEBUG=false` is set
- [ ] Database credentials are secure
- [ ] HTTPS is working (automatic on Render)
- [ ] Demo users passwords are changed or disabled

## 🔧 Troubleshooting

### Common Issues

#### 1. Build Fails

**Error**: `composer: command not found`
**Solution**: Render should auto-detect PHP. If not, ensure `composer.json` is in your root directory.

#### 2. Database Connection Error

**Error**: `Database connection failed`
**Solutions**:

- Verify `DATABASE_URL` environment variable is set correctly
- Ensure database is running (check Render dashboard)
- Check database credentials

#### 3. Missing Tables

**Error**: `Table 'users' doesn't exist`
**Solutions**:

- Check build logs to see if `build.sh` ran successfully
- Manually trigger database setup by visiting `/install.php` (then delete it)
- Check PostgreSQL logs in Render dashboard

#### 4. File Permissions

**Error**: `Permission denied` for cache/data directories
**Solution**: The `build.sh` script should handle this, but you can add to build command:

```bash
mkdir -p data cache && chmod 755 data cache
```

### Debug Tips

1. **Check build logs** in Render dashboard
2. **Check runtime logs** for PHP errors
3. **Use Render's shell access** to debug issues:
   ```bash
   ls -la  # Check file permissions
   php -v  # Check PHP version
   composer --version  # Check Composer
   ```

## 📊 Monitoring & Maintenance

### Free Tier Limitations

- **750 hours/month** web service (about 31 days)
- **Free PostgreSQL for 90 days**, then $7/month
- **Automatic sleep** after 15 minutes of inactivity
- **Cold starts** when waking up from sleep

### Upgrading

If you need more resources:

- **Starter Plan**: $7/month - no sleep, faster builds
- **PostgreSQL**: $7/month after free period
- **Custom domains**: Available on paid plans

### Backups

Render automatically backs up PostgreSQL databases. You can also:

1. **Export data** using the built-in data export feature
2. **Download database dumps** from Render dashboard

## 🌟 Advanced Configuration

### Custom Domain

1. **Upgrade to paid plan**
2. **Add custom domain** in Render dashboard
3. **Update DNS records** as instructed

### Environment-Specific Settings

Create different services for staging/production:

- `crmaize-staging` (connected to `develop` branch)
- `crmaize-production` (connected to `main` branch)

### Scaling

Render can automatically scale your app:

- **Horizontal scaling**: Multiple instances
- **Vertical scaling**: More CPU/RAM
- **Load balancing**: Built-in

## 🎊 You're Live!

Congratulations! Your CRMAIze application is now live on Render with:

- ✅ **Professional hosting** with automatic HTTPS
- ✅ **PostgreSQL database** with automatic backups
- ✅ **Automatic deployments** from GitHub
- ✅ **PWA capabilities** for mobile users
- ✅ **Production-ready** environment

Your app is accessible at: `https://your-app-name.onrender.com`

## 📞 Support

- **Render Documentation**: [render.com/docs](https://render.com/docs)
- **Render Community**: [community.render.com](https://community.render.com)
- **CRMAIze Issues**: Create issues in your GitHub repository

Happy deploying! 🚀
