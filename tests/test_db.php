<?php

require_once __DIR__ . '/vendor/autoload.php';

// Load environment variables
$dotenv = Dotenv\Dotenv::createImmutable(__DIR__);
$dotenv->load();

echo "Current directory: " . __DIR__ . "\n";
echo "DB_DSN from env: " . ($_ENV['DB_DSN'] ?? 'NOT SET') . "\n";

$dataDir = __DIR__ . '/data';
echo "Data directory: " . $dataDir . "\n";
echo "Data directory exists: " . (is_dir($dataDir) ? 'YES' : 'NO') . "\n";

$dbFile = $dataDir . '/crmaize.db';
echo "Database file: " . $dbFile . "\n";
echo "Database file exists: " . (file_exists($dbFile) ? 'YES' : 'NO') . "\n";
echo "Database file writable: " . (is_writable($dbFile) ? 'YES' : 'NO') . "\n";

try {
  $pdo = new PDO($_ENV['DB_DSN']);
  echo "Database connection SUCCESS!\n";
} catch (Exception $e) {
  echo "Database connection FAILED: " . $e->getMessage() . "\n";
}
