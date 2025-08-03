<?php

/**
 * CRMAIze Installation Script
 * Sets up directories and environment for deployment
 */

echo "🚀 CRMAIze Installation Starting...\n\n";

// Check PHP version
if (version_compare(PHP_VERSION, '7.4.0') < 0) {
  die("❌ PHP 7.4 or higher is required. Current version: " . PHP_VERSION . "\n");
}

// Check required extensions
$requiredExtensions = ['pdo', 'pdo_sqlite', 'json', 'mbstring'];
$missingExtensions = [];

foreach ($requiredExtensions as $ext) {
  if (!extension_loaded($ext)) {
    $missingExtensions[] = $ext;
  }
}

if (!empty($missingExtensions)) {
  die("❌ Missing required PHP extensions: " . implode(', ', $missingExtensions) . "\n");
}

echo "✅ PHP version and extensions check passed\n";

// Create necessary directories
$directories = [
  'data',
  'cache',
  'cache/twig',
  'public/assets',
  'public/assets/css',
  'public/assets/js',
  'public/assets/icons',
  'templates/emails'
];

foreach ($directories as $dir) {
  if (!is_dir($dir)) {
    if (mkdir($dir, 0755, true)) {
      echo "✅ Created directory: $dir\n";
    } else {
      echo "⚠️  Could not create directory: $dir\n";
    }
  } else {
    echo "✅ Directory exists: $dir\n";
  }
}

// Copy .env file if it doesn't exist
if (!file_exists('.env')) {
  if (file_exists('env.example')) {
    if (copy('env.example', '.env')) {
      echo "✅ Created .env file from env.example\n";
    } else {
      echo "⚠️  Could not copy env.example to .env\n";
    }
  } else {
    // Create basic .env file
    $envContent = "APP_ENV=production
APP_DEBUG=false
DB_DSN=sqlite:" . __DIR__ . "/../data/crmaize.db
DB_USERNAME=
DB_PASSWORD=

# Email Configuration (optional)
SMTP_HOST=
SMTP_PORT=587
SMTP_USERNAME=
SMTP_PASSWORD=
SMTP_ENCRYPTION=tls
MAIL_FROM_ADDRESS=
MAIL_FROM_NAME=CRMAIze

# AI Configuration
AI_ENABLED=true
AI_MAX_SUGGESTIONS=5

# Campaign Configuration
CAMPAIGN_MAX_RECIPIENTS=1000
CAMPAIGN_RATE_LIMIT=100
CAMPAIGN_DEFAULT_TIMEZONE=UTC
";
    if (file_put_contents('.env', $envContent)) {
      echo "✅ Created basic .env file\n";
    } else {
      echo "⚠️  Could not create .env file\n";
    }
  }
}

// Load environment variables
if (file_exists('vendor/autoload.php')) {
  require_once 'vendor/autoload.php';

  try {
    $dotenv = Dotenv\Dotenv::createImmutable(__DIR__ . '/..');
    $dotenv->load();
    echo "✅ Environment variables loaded\n";
  } catch (Exception $e) {
    echo "⚠️  Could not load environment variables: " . $e->getMessage() . "\n";
  }
} else {
  echo "⚠️  Composer autoload not found. Run 'composer install' first.\n";
}

// Initialize database
try {
  require_once __DIR__ . '/../src/Service/DatabaseService.php';

  $database = new \CRMAIze\Service\DatabaseService();
  $database->createTables();
  echo "✅ Database tables created/verified\n";
} catch (Exception $e) {
  echo "⚠️  Database initialization failed: " . $e->getMessage() . "\n";
  echo "   This is normal if database credentials are not yet configured.\n";
}

// Set file permissions (Unix-like systems only)
if (PHP_OS_FAMILY !== 'Windows') {
  $filesToChmod = [
    'data' => 0755,
    'cache' => 0755,
    '.env' => 0644
  ];

  foreach ($filesToChmod as $file => $permission) {
    if (file_exists($file)) {
      if (chmod($file, $permission)) {
        echo "✅ Set permissions for $file\n";
      } else {
        echo "⚠️  Could not set permissions for $file\n";
      }
    }
  }
}

echo "\n🎉 Installation completed!\n\n";

echo "📋 Next Steps:\n";
echo "1. Configure your .env file with database credentials\n";
echo "2. Run 'php scripts/setup_users.php' to create demo users\n";
echo "3. Run 'php scripts/import_data.php' to import sample data\n";
echo "4. Start your web server or deploy to hosting platform\n";
echo "5. Visit your application in a web browser\n\n";

echo "🔐 Demo Login Credentials (after running setup_users.php):\n";
echo "   Admin: admin / admin123\n";
echo "   Marketer: marketer / marketer123\n\n";

echo "📚 For deployment help, see DEPLOYMENT.md\n";
