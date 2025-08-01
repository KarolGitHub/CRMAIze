<?php

require_once __DIR__ . '/vendor/autoload.php';

// Load environment variables
$dotenv = Dotenv\Dotenv::createImmutable(__DIR__);
$dotenv->load();

echo "=== Database Connection Debug ===\n";
echo "Current directory: " . __DIR__ . "\n";
echo "Working directory: " . getcwd() . "\n";
echo "DB_DSN from env: " . ($_ENV['DB_DSN'] ?? 'NOT SET') . "\n";

$rootDir = dirname(__DIR__, 2);
echo "Root directory (from src): " . $rootDir . "\n";

$dataDir = $rootDir . '/data';
echo "Data directory: " . $dataDir . "\n";
echo "Data directory exists: " . (is_dir($dataDir) ? 'YES' : 'NO') . "\n";

$dbFile = $dataDir . '/crmaize.db';
echo "Database file: " . $dbFile . "\n";
echo "Database file exists: " . (file_exists($dbFile) ? 'YES' : 'NO') . "\n";
echo "Database file writable: " . (is_writable($dbFile) ? 'YES' : 'NO') . "\n";

// Test with the exact same logic as DatabaseService
$dsn = $_ENV['DB_DSN'] ?? 'sqlite:' . $rootDir . '/data/crmaize.db';
echo "Final DSN: " . $dsn . "\n";

try {
  $pdo = new PDO($dsn);
  echo "Database connection SUCCESS!\n";
} catch (Exception $e) {
  echo "Database connection FAILED: " . $e->getMessage() . "\n";
}
