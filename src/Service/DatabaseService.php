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

  private function connect(): void
  {
    $dsn = $_ENV['DB_DSN'] ?? 'sqlite:' . __DIR__ . '/../../data/crmaize.db';
    $username = $_ENV['DB_USERNAME'] ?? '';
    $password = $_ENV['DB_PASSWORD'] ?? '';

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

  private function createTables(): void
  {
    $sql = "
            CREATE TABLE IF NOT EXISTS customers (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                email VARCHAR(255) UNIQUE NOT NULL,
                first_name VARCHAR(100),
                last_name VARCHAR(100),
                total_spent DECIMAL(10,2) DEFAULT 0,
                order_count INTEGER DEFAULT 0,
                last_order_date DATE,
                segment VARCHAR(50),
                churn_risk DECIMAL(3,2) DEFAULT 0,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
            );

            CREATE TABLE IF NOT EXISTS campaigns (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                name VARCHAR(255) NOT NULL,
                type ENUM('email', 'discount') NOT NULL,
                target_segment VARCHAR(50),
                discount_percent INTEGER,
                subject_line VARCHAR(255),
                email_content TEXT,
                status ENUM('draft', 'sent', 'cancelled') DEFAULT 'draft',
                sent_count INTEGER DEFAULT 0,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
            );

            CREATE TABLE IF NOT EXISTS campaign_logs (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                campaign_id INTEGER,
                customer_id INTEGER,
                status ENUM('sent', 'opened', 'clicked', 'bounced') DEFAULT 'sent',
                sent_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
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
