<?php

namespace CRMAIze\Model;

class CampaignTemplate
{
    public ?int $id;
    public string $name;
    public string $description;
    public string $category;
    public string $type;
    public ?string $subject_line;
    public ?string $email_content;
    public ?int $discount_percent;
    public ?string $target_segment;
    public bool $is_public;
    public ?int $created_by;
    public int $usage_count;
    public float $rating;
    public ?string $tags;
    public string $created_at;
    public string $updated_at;

    public function __construct(array $data = [])
    {
        $this->id = $data['id'] ?? null;
        $this->name = $data['name'] ?? '';
        $this->description = $data['description'] ?? '';
        $this->category = $data['category'] ?? '';
        $this->type = $data['type'] ?? '';
        $this->subject_line = $data['subject_line'] ?? null;
        $this->email_content = $data['email_content'] ?? null;
        $this->discount_percent = $data['discount_percent'] ?? null;
        $this->target_segment = $data['target_segment'] ?? null;
        $this->is_public = (bool)($data['is_public'] ?? true);
        $this->created_by = $data['created_by'] ?? null;
        $this->usage_count = (int)($data['usage_count'] ?? 0);
        $this->rating = (float)($data['rating'] ?? 0.0);
        $this->tags = $data['tags'] ?? null;
        $this->created_at = $data['created_at'] ?? '';
        $this->updated_at = $data['updated_at'] ?? '';
    }

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'description' => $this->description,
            'category' => $this->category,
            'type' => $this->type,
            'subject_line' => $this->subject_line,
            'email_content' => $this->email_content,
            'discount_percent' => $this->discount_percent,
            'target_segment' => $this->target_segment,
            'is_public' => $this->is_public,
            'created_by' => $this->created_by,
            'usage_count' => $this->usage_count,
            'rating' => $this->rating,
            'tags' => $this->tags,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }

    public function getTagsArray(): array
    {
        if (!$this->tags) {
            return [];
        }
        return array_filter(array_map('trim', explode(',', $this->tags)));
    }

    public function getCategoryDisplayName(): string
    {
        $categories = [
            'welcome' => 'Welcome Series',
            'abandoned_cart' => 'Abandoned Cart',
            're_engagement' => 'Re-engagement',
            'promotional' => 'Promotional',
            'seasonal' => 'Seasonal',
            'custom' => 'Custom'
        ];

        return $categories[$this->category] ?? $this->category;
    }

    public function getTypeDisplayName(): string
    {
        return $this->type === 'email' ? 'Email Campaign' : 'Discount Campaign';
    }
}

