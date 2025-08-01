<?php

echo "CRMAIze Installation Script\n";
echo "==========================\n\n";

// Check PHP version
if (version_compare(PHP_VERSION, '7.4.0', '<')) {
  echo "Error: PHP 7.4 or higher is required. Current version: " . PHP_VERSION . "\n";
  exit(1);
}

echo "✓ PHP version: " . PHP_VERSION . "\n";

// Check required extensions
$required_extensions = ['pdo', 'pdo_sqlite', 'json'];
foreach ($required_extensions as $ext) {
  if (!extension_loaded($ext)) {
    echo "Error: Required PHP extension '{$ext}' is not loaded.\n";
    exit(1);
  }
  echo "✓ Extension '{$ext}' loaded\n";
}

// Create necessary directories
$directories = [
  'data',
  'cache',
  'cache/twig',
  'public/assets/css',
  'public/assets/js'
];

foreach ($directories as $dir) {
  if (!is_dir($dir)) {
    if (mkdir($dir, 0755, true)) {
      echo "✓ Created directory: {$dir}\n";
    } else {
      echo "Error: Failed to create directory: {$dir}\n";
      exit(1);
    }
  } else {
    echo "✓ Directory exists: {$dir}\n";
  }
}

// Copy environment file if it doesn't exist
if (!file_exists('.env')) {
  if (copy('env.example', '.env')) {
    echo "✓ Created .env file from env.example\n";
  } else {
    echo "Warning: Failed to create .env file. Please copy env.example to .env manually.\n";
  }
} else {
  echo "✓ .env file already exists\n";
}

echo "\nInstallation completed successfully!\n\n";
echo "Next steps:\n";
echo "1. Run 'composer install' to install dependencies\n";
echo "2. Run 'php scripts/import_data.php' to import sample data\n";
echo "3. Run 'php -S localhost:8000 -t public' to start the development server\n";
echo "4. Open http://localhost:8000 in your browser\n\n";
