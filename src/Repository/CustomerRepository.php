<?php

namespace CRMAIze\Repository;

use CRMAIze\Service\DatabaseService;

class CustomerRepository
{
  private $db;

  public function __construct(DatabaseService $db)
  {
    $this->db = $db;
  }

  public function getAll(): array
  {
    return $this->db->query("SELECT * FROM customers ORDER BY created_at DESC");
  }

  public function getTotalCount(): int
  {
    $result = $this->db->query("SELECT COUNT(*) as count FROM customers");
    return (int) $result[0]['count'];
  }

  public function getTotalRevenue(): float
  {
    $result = $this->db->query("SELECT SUM(total_spent) as total FROM customers");
    return (float) ($result[0]['total'] ?? 0);
  }

  public function getSegmentCounts(): array
  {
    return $this->db->query("
            SELECT segment, COUNT(*) as count
            FROM customers
            WHERE segment IS NOT NULL
            GROUP BY segment
        ");
  }

  public function getAtRiskCustomers(int $limit = 10): array
  {
    return $this->db->query("
            SELECT * FROM customers
            WHERE churn_risk > 0.5
            ORDER BY churn_risk DESC
            LIMIT ?
        ", [$limit]);
  }

  public function getChurnRate(): float
  {
    $result = $this->db->query("
            SELECT AVG(churn_risk) as avg_risk
            FROM customers
        ");
    return (float) ($result[0]['avg_risk'] ?? 0);
  }

  public function getBySegment(string $segment): array
  {
    return $this->db->query("
            SELECT * FROM customers
            WHERE segment = ?
            ORDER BY churn_risk DESC
        ", [$segment]);
  }

  public function updateChurnRisk(int $customerId, float $risk): bool
  {
    return $this->db->execute("
            UPDATE customers
            SET churn_risk = ?
            WHERE id = ?
        ", [$risk, $customerId]);
  }

  public function updateSegment(int $customerId, string $segment): bool
  {
    return $this->db->execute("
            UPDATE customers
            SET segment = ?
            WHERE id = ?
        ", [$segment, $customerId]);
  }

  public function create(array $data): int
  {
    $this->db->execute("
            INSERT INTO customers (email, first_name, last_name, total_spent, order_count, last_order_date)
            VALUES (?, ?, ?, ?, ?, ?)
        ", [
      $data['email'],
      $data['first_name'] ?? '',
      $data['last_name'] ?? '',
      $data['total_spent'] ?? 0,
      $data['order_count'] ?? 0,
      $data['last_order_date'] ?? null
    ]);

    return (int) $this->db->lastInsertId();
  }

  public function findByEmail(string $email): ?array
  {
    $result = $this->db->query("SELECT * FROM customers WHERE email = ?", [$email]);
    return $result ? $result[0] : null;
  }

  public function findById(int $id): ?array
  {
    $result = $this->db->query("SELECT * FROM customers WHERE id = ?", [$id]);
    return $result ? $result[0] : null;
  }
}
