<?php

namespace CRMAIze\Repository;

use CRMAIze\Service\DatabaseService;

class CampaignRepository
{
  private $db;

  public function __construct(DatabaseService $db)
  {
    $this->db = $db;
  }

  public function getAll(): array
  {
    return $this->db->query("SELECT * FROM campaigns ORDER BY created_at DESC");
  }

  public function getById(int $id): ?array
  {
    $result = $this->db->query("SELECT * FROM campaigns WHERE id = ?", [$id]);
    return $result[0] ?? null;
  }

  public function getRecent(int $limit = 5): array
  {
    return $this->db->query("
            SELECT * FROM campaigns
            ORDER BY created_at DESC
            LIMIT ?
        ", [$limit]);
  }

  public function create(array $data): int
  {
    $this->db->execute("
            INSERT INTO campaigns (name, type, target_segment, discount_percent, subject_line, email_content)
            VALUES (?, ?, ?, ?, ?, ?)
        ", [
      $data['name'],
      $data['type'],
      $data['target_segment'] ?? null,
      $data['discount_percent'] ?? null,
      $data['subject_line'] ?? null,
      $data['email_content'] ?? null
    ]);

    return (int) $this->db->lastInsertId();
  }

  public function update(int $id, array $data): bool
  {
    return $this->db->execute("
            UPDATE campaigns
            SET name = ?, type = ?, target_segment = ?, discount_percent = ?,
                subject_line = ?, email_content = ?, status = ?
            WHERE id = ?
        ", [
      $data['name'],
      $data['type'],
      $data['target_segment'] ?? null,
      $data['discount_percent'] ?? null,
      $data['subject_line'] ?? null,
      $data['email_content'] ?? null,
      $data['status'] ?? 'draft',
      $id
    ]);
  }

  public function updateStatus(int $id, string $status): bool
  {
    return $this->db->execute("
            UPDATE campaigns
            SET status = ?
            WHERE id = ?
        ", [$status, $id]);
  }

  public function incrementSentCount(int $id): bool
  {
    return $this->db->execute("
            UPDATE campaigns
            SET sent_count = sent_count + 1
            WHERE id = ?
        ", [$id]);
  }

  public function getByStatus(string $status): array
  {
    return $this->db->query("
            SELECT * FROM campaigns
            WHERE status = ?
            ORDER BY created_at DESC
        ", [$status]);
  }

  public function getBySegment(string $segment): array
  {
    return $this->db->query("
            SELECT * FROM campaigns
            WHERE target_segment = ?
            ORDER BY created_at DESC
        ", [$segment]);
  }

  public function delete(int $id): bool
  {
    return $this->db->execute("DELETE FROM campaigns WHERE id = ?", [$id]);
  }
}
