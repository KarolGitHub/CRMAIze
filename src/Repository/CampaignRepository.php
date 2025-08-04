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

  public function updateScheduledAt(int $id, string $scheduledAt): bool
  {
    return $this->db->execute(
      "UPDATE campaigns SET scheduled_at = ? WHERE id = ?",
      [$scheduledAt, $id]
    );
  }

  public function updateSentAt(int $id, string $sentAt): bool
  {
    return $this->db->execute(
      "UPDATE campaigns SET sent_at = ? WHERE id = ?",
      [$sentAt, $id]
    );
  }

  public function createSchedule(array $data): bool
  {
    return $this->db->execute(
      "INSERT INTO campaign_schedules (campaign_id, schedule_type, scheduled_at, timezone, is_active) VALUES (?, ?, ?, ?, ?)",
      [
        $data['campaign_id'],
        $data['schedule_type'],
        $data['scheduled_at'] ?? null,
        $data['timezone'],
        $data['is_active']
      ]
    );
  }

  public function getScheduledCampaigns(string $currentTime): array
  {
    $isActive = $this->db->isPostgreSQL() ? true : 1;
    return $this->db->query(
      "SELECT c.* FROM campaigns c
       INNER JOIN campaign_schedules cs ON c.id = cs.campaign_id
       WHERE c.status = 'scheduled' AND cs.scheduled_at <= ? AND cs.is_active = ?",
      [$currentTime, $isActive]
    );
  }

  public function getUpcomingScheduledCampaigns(): array
  {
    $isActive = $this->db->isPostgreSQL() ? true : 1;
    $nowFunction = $this->db->isPostgreSQL() ? 'NOW()' : "datetime('now')";

    return $this->db->query(
      "SELECT c.*, cs.scheduled_at, cs.timezone FROM campaigns c
       INNER JOIN campaign_schedules cs ON c.id = cs.campaign_id
       WHERE c.status = 'scheduled' AND cs.scheduled_at > {$nowFunction} AND cs.is_active = ?
       ORDER BY cs.scheduled_at ASC",
      [$isActive]
    );
  }

  public function deactivateSchedule(int $campaignId): bool
  {
    $isActive = $this->db->isPostgreSQL() ? false : 0;
    return $this->db->execute(
      "UPDATE campaign_schedules SET is_active = ? WHERE campaign_id = ?",
      [$isActive, $campaignId]
    );
  }

  public function logCampaignSend(int $campaignId, int $customerId): bool
  {
    return $this->db->execute(
      "INSERT INTO campaign_logs (campaign_id, customer_id, status) VALUES (?, ?, 'sent')",
      [$campaignId, $customerId]
    );
  }

  public function createVariant(array $data): int
  {
    $this->db->execute(
      "INSERT INTO campaign_variants (campaign_id, variant_name, subject_line, email_content, discount_percent, is_control) VALUES (?, ?, ?, ?, ?, ?)",
      [
        $data['campaign_id'],
        $data['variant_name'],
        $data['subject_line'] ?? null,
        $data['email_content'] ?? null,
        $data['discount_percent'] ?? null,
        $data['is_control'] ?? 0
      ]
    );

    return (int) $this->db->lastInsertId();
  }

  public function getVariants(int $campaignId): array
  {
    return $this->db->query(
      "SELECT * FROM campaign_variants WHERE campaign_id = ? ORDER BY is_control DESC, created_at ASC",
      [$campaignId]
    );
  }
}
