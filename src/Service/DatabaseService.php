<?php

namespace CRMAIze\Service;

use PDO;
use PDOException;

class DatabaseService
{
  private $pdo;

  public function __construct()
  {
    $this->connect();
    $this->createTables();
  }

  /**
   * Get the appropriate boolean value for the current database type
   */
  public function getBooleanValue(bool $value): string
  {
    $isPostgreSQL = $this->pdo->getAttribute(PDO::ATTR_DRIVER_NAME) === 'pgsql';

    if ($isPostgreSQL) {
      return $value ? 'TRUE' : 'FALSE';
    } else {
      return $value ? '1' : '0';
    }
  }

  /**
   * Check if the current database is PostgreSQL
   */
  public function isPostgreSQL(): bool
  {
    return $this->pdo->getAttribute(PDO::ATTR_DRIVER_NAME) === 'pgsql';
  }

  private function connect(): void
  {
    // Handle Render.com DATABASE_URL or fallback to DB_DSN
    $databaseUrl = $_ENV['DATABASE_URL'] ?? null;

    if ($databaseUrl) {
      // Parse Render's DATABASE_URL format: postgres://user:pass@host:port/dbname
      $parsed = parse_url($databaseUrl);
      $dsn = sprintf(
        'pgsql:host=%s;port=%d;dbname=%s',
        $parsed['host'],
        $parsed['port'] ?? 5432,
        ltrim($parsed['path'], '/')
      );
      $username = $parsed['user'];
      $password = $parsed['pass'];
    } else {
      // Fallback to traditional DSN format
      $dsn = $_ENV['DB_DSN'] ?? 'sqlite:' . __DIR__ . '/../../data/crmaize.db';
      $username = $_ENV['DB_USERNAME'] ?? '';
      $password = $_ENV['DB_PASSWORD'] ?? '';

      // Ensure the data directory exists for SQLite
      if (strpos($dsn, 'sqlite:') === 0) {
        $dataDir = dirname(__DIR__, 2) . '/data';
        if (!is_dir($dataDir)) {
          mkdir($dataDir, 0755, true);
        }
      }
    }

    try {
      $this->pdo = new PDO($dsn, $username, $password, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
      ]);
    } catch (PDOException $e) {
      throw new \Exception("Database connection failed: " . $e->getMessage());
    }
  }

  public function createTables(): void
  {
    // Detect database type
    $isPostgreSQL = $this->pdo->getAttribute(PDO::ATTR_DRIVER_NAME) === 'pgsql';

    // Use appropriate SQL syntax for each database type
    if ($isPostgreSQL) {
      $autoIncrement = 'SERIAL PRIMARY KEY';
      $booleanType = 'BOOLEAN';
      $timestampDefault = 'TIMESTAMP DEFAULT CURRENT_TIMESTAMP';
    } else {
      $autoIncrement = 'INTEGER PRIMARY KEY AUTOINCREMENT';
      $booleanType = 'BOOLEAN';
      $timestampDefault = 'TIMESTAMP DEFAULT CURRENT_TIMESTAMP';
    }

    $sql = "
            CREATE TABLE IF NOT EXISTS users (
                id $autoIncrement,
                username VARCHAR(50) UNIQUE NOT NULL,
                email VARCHAR(255) UNIQUE NOT NULL,
                password_hash VARCHAR(255) NOT NULL,
                role VARCHAR(20) DEFAULT 'marketer' CHECK (role IN ('admin', 'marketer')),
                is_active $booleanType DEFAULT " . ($isPostgreSQL ? 'TRUE' : '1') . ",
                last_login TIMESTAMP,
                created_at $timestampDefault
            );

            CREATE TABLE IF NOT EXISTS customers (
                id $autoIncrement,
                email VARCHAR(255) UNIQUE NOT NULL,
                first_name VARCHAR(100),
                last_name VARCHAR(100),
                total_spent DECIMAL(10,2) DEFAULT 0,
                order_count INTEGER DEFAULT 0,
                last_order_date DATE,
                segment VARCHAR(50),
                churn_risk DECIMAL(3,2) DEFAULT 0,
                created_at $timestampDefault
            );

            CREATE TABLE IF NOT EXISTS campaigns (
                id $autoIncrement,
                name VARCHAR(255) NOT NULL,
                type VARCHAR(20) NOT NULL CHECK (type IN ('email', 'discount')),
                target_segment VARCHAR(50),
                discount_percent INTEGER,
                subject_line VARCHAR(255),
                email_content TEXT,
                status VARCHAR(20) DEFAULT 'draft' CHECK (status IN ('draft', 'scheduled', 'sent', 'cancelled')),
                sent_count INTEGER DEFAULT 0,
                created_by INTEGER,
                scheduled_at TIMESTAMP,
                sent_at TIMESTAMP,
                created_at $timestampDefault,
                FOREIGN KEY (created_by) REFERENCES users(id)
            );

            CREATE TABLE IF NOT EXISTS campaign_variants (
                id $autoIncrement,
                campaign_id INTEGER,
                variant_name VARCHAR(50) NOT NULL,
                subject_line VARCHAR(255),
                email_content TEXT,
                discount_percent INTEGER,
                is_control $booleanType DEFAULT " . ($isPostgreSQL ? 'FALSE' : '0') . ",
                created_at $timestampDefault,
                FOREIGN KEY (campaign_id) REFERENCES campaigns(id)
            );

            CREATE TABLE IF NOT EXISTS campaign_schedules (
                id $autoIncrement,
                campaign_id INTEGER,
                schedule_type VARCHAR(20) NOT NULL CHECK (schedule_type IN ('immediate', 'scheduled', 'recurring')),
                scheduled_at TIMESTAMP,
                timezone VARCHAR(50) DEFAULT 'UTC',
                recurrence_pattern VARCHAR(100),
                is_active $booleanType DEFAULT " . ($isPostgreSQL ? 'TRUE' : '1') . ",
                created_at $timestampDefault,
                FOREIGN KEY (campaign_id) REFERENCES campaigns(id)
            );

            CREATE TABLE IF NOT EXISTS campaign_logs (
                id $autoIncrement,
                campaign_id INTEGER,
                customer_id INTEGER,
                status VARCHAR(20) DEFAULT 'sent' CHECK (status IN ('sent', 'opened', 'clicked', 'bounced')),
                sent_at $timestampDefault,
                FOREIGN KEY (campaign_id) REFERENCES campaigns(id),
                FOREIGN KEY (customer_id) REFERENCES customers(id)
            );
        ";

    $this->pdo->exec($sql);
  }

  public function getPdo(): PDO
  {
    return $this->pdo;
  }

  public function query(string $sql, array $params = []): array
  {
    $stmt = $this->pdo->prepare($sql);
    $stmt->execute($params);
    return $stmt->fetchAll();
  }

  public function execute(string $sql, array $params = []): bool
  {
    $stmt = $this->pdo->prepare($sql);
    return $stmt->execute($params);
  }

  public function lastInsertId(): string
  {
    return $this->pdo->lastInsertId();
  }
}
