<?php

namespace CRMAIze\Repository;

use CRMAIze\Model\CampaignTemplate;
use CRMAIze\Service\DatabaseService;

class CampaignTemplateRepository
{
    private DatabaseService $databaseService;

    public function __construct(DatabaseService $databaseService)
    {
        $this->databaseService = $databaseService;
    }

    public function getAll(): array
    {
        $sql = "SELECT * FROM campaign_templates ORDER BY created_at DESC";
        $results = $this->databaseService->query($sql);

        return array_map(fn($row) => new CampaignTemplate($row), $results);
    }

    public function getById(int $id): ?CampaignTemplate
    {
        $sql = "SELECT * FROM campaign_templates WHERE id = ?";
        $results = $this->databaseService->query($sql, [$id]);

        if (empty($results)) {
            return null;
        }

        return new CampaignTemplate($results[0]);
    }

    public function getByCategory(string $category): array
    {
        $sql = "SELECT * FROM campaign_templates WHERE category = ? ORDER BY usage_count DESC, rating DESC";
        $results = $this->databaseService->query($sql, [$category]);

        return array_map(fn($row) => new CampaignTemplate($row), $results);
    }

    public function getPublicTemplates(): array
    {
        $sql = "SELECT * FROM campaign_templates WHERE is_public = " . $this->databaseService->getBooleanValue(true) . " ORDER BY usage_count DESC, rating DESC";
        $results = $this->databaseService->query($sql);

        return array_map(fn($row) => new CampaignTemplate($row), $results);
    }

    public function getByType(string $type): array
    {
        $sql = "SELECT * FROM campaign_templates WHERE type = ? ORDER BY usage_count DESC, rating DESC";
        $results = $this->databaseService->query($sql, [$type]);

        return array_map(fn($row) => new CampaignTemplate($row), $results);
    }

    public function search(string $query): array
    {
        $sql = "SELECT * FROM campaign_templates WHERE
                name LIKE ? OR
                description LIKE ? OR
                tags LIKE ? OR
                subject_line LIKE ?
                ORDER BY usage_count DESC, rating DESC";

        $searchTerm = "%{$query}%";
        $results = $this->databaseService->query($sql, [$searchTerm, $searchTerm, $searchTerm, $searchTerm]);

        return array_map(fn($row) => new CampaignTemplate($row), $results);
    }

    public function create(CampaignTemplate $template): int
    {
        $sql = "INSERT INTO campaign_templates (
                    name, description, category, type, subject_line, email_content,
                    discount_percent, target_segment, is_public, created_by, tags, created_at, updated_at
                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, " .
                ($this->databaseService->isPostgreSQL() ? 'CURRENT_TIMESTAMP' : 'CURRENT_TIMESTAMP') . ", " .
                ($this->databaseService->isPostgreSQL() ? 'CURRENT_TIMESTAMP' : 'CURRENT_TIMESTAMP') . ")";

        $params = [
            $template->name,
            $template->description,
            $template->category,
            $template->type,
            $template->subject_line,
            $template->email_content,
            $template->discount_percent,
            $template->target_segment,
            $this->databaseService->getBooleanValue($template->is_public),
            $template->created_by,
            $template->tags
        ];

        $this->databaseService->execute($sql, $params);
        return (int)$this->databaseService->lastInsertId();
    }

    public function update(CampaignTemplate $template): bool
    {
        $sql = "UPDATE campaign_templates SET
                name = ?, description = ?, category = ?, type = ?, subject_line = ?,
                email_content = ?, discount_percent = ?, target_segment = ?, is_public = ?,
                tags = ?, updated_at = " . ($this->databaseService->isPostgreSQL() ? 'CURRENT_TIMESTAMP' : 'CURRENT_TIMESTAMP') . "
                WHERE id = ?";

        $params = [
            $template->name,
            $template->description,
            $template->category,
            $template->type,
            $template->subject_line,
            $template->email_content,
            $template->discount_percent,
            $template->target_segment,
            $this->databaseService->getBooleanValue($template->is_public),
            $template->tags,
            $template->id
        ];

        return $this->databaseService->execute($sql, $params);
    }

    public function delete(int $id): bool
    {
        $sql = "DELETE FROM campaign_templates WHERE id = ?";
        return $this->databaseService->execute($sql, [$id]);
    }

    public function incrementUsageCount(int $id): bool
    {
        $sql = "UPDATE campaign_templates SET usage_count = usage_count + 1 WHERE id = ?";
        return $this->databaseService->execute($sql, [$id]);
    }

    public function updateRating(int $id, float $rating): bool
    {
        $sql = "UPDATE campaign_templates SET rating = ? WHERE id = ?";
        return $this->databaseService->execute($sql, [$rating, $id]);
    }

    public function getPopularTemplates(int $limit = 10): array
    {
        $sql = "SELECT * FROM campaign_templates ORDER BY usage_count DESC, rating DESC LIMIT ?";
        $results = $this->databaseService->query($sql, [$limit]);

        return array_map(fn($row) => new CampaignTemplate($row), $results);
    }

    public function getTemplatesByUser(int $userId): array
    {
        $sql = "SELECT * FROM campaign_templates WHERE created_by = ? ORDER BY created_at DESC";
        $results = $this->databaseService->query($sql, [$userId]);

        return array_map(fn($row) => new CampaignTemplate($row), $results);
    }

    public function getCategories(): array
    {
        $sql = "SELECT DISTINCT category FROM campaign_templates ORDER BY category";
        $results = $this->databaseService->query($sql);

        return array_column($results, 'category');
    }
}
