<?php

namespace CRMAIze\Service;

use CRMAIze\Model\CampaignTemplate;
use CRMAIze\Repository\CampaignTemplateRepository;

class CampaignTemplateService
{
    private CampaignTemplateRepository $templateRepository;

    public function __construct(CampaignTemplateRepository $templateRepository)
    {
        $this->templateRepository = $templateRepository;
    }

    public function getAllTemplates(): array
    {
        return $this->templateRepository->getAll();
    }

    public function getTemplateById(int $id): ?CampaignTemplate
    {
        return $this->templateRepository->getById($id);
    }

    public function getTemplatesByCategory(string $category): array
    {
        return $this->templateRepository->getByCategory($category);
    }

    public function getPublicTemplates(): array
    {
        return $this->templateRepository->getPublicTemplates();
    }

    public function searchTemplates(string $query): array
    {
        return $this->templateRepository->search($query);
    }

    public function createTemplate(array $data): int
    {
        $template = new CampaignTemplate($data);
        return $this->templateRepository->create($template);
    }

    public function updateTemplate(int $id, array $data): bool
    {
        $template = $this->templateRepository->getById($id);
        if (!$template) {
            return false;
        }

        // Update template properties
        foreach ($data as $key => $value) {
            if (property_exists($template, $key)) {
                $template->$key = $value;
            }
        }

        return $this->templateRepository->update($template);
    }

    public function deleteTemplate(int $id): bool
    {
        return $this->templateRepository->delete($id);
    }

    public function useTemplate(int $id): bool
    {
        return $this->templateRepository->incrementUsageCount($id);
    }

    public function rateTemplate(int $id, float $rating): bool
    {
        return $this->templateRepository->updateRating($id, $rating);
    }

    public function getPopularTemplates(int $limit = 10): array
    {
        return $this->templateRepository->getPopularTemplates($limit);
    }

    public function getTemplatesByUser(int $userId): array
    {
        return $this->templateRepository->getTemplatesByUser($userId);
    }

    public function getCategories(): array
    {
        return $this->templateRepository->getCategories();
    }

    public function createFromTemplate(int $templateId, array $customizations = []): array
    {
        $template = $this->templateRepository->getById($templateId);
        if (!$template) {
            throw new \InvalidArgumentException('Template not found');
        }

        // Increment usage count
        $this->templateRepository->incrementUsageCount($templateId);

        // Create campaign data from template
        $campaignData = [
            'name' => $customizations['name'] ?? $template->name,
            'type' => $template->type,
            'target_segment' => $customizations['target_segment'] ?? $template->target_segment,
            'discount_percent' => $customizations['discount_percent'] ?? $template->discount_percent,
            'subject_line' => $customizations['subject_line'] ?? $template->subject_line,
            'email_content' => $customizations['email_content'] ?? $template->email_content,
        ];

        return $campaignData;
    }

    public function seedDefaultTemplates(): void
    {
        $defaultTemplates = [
            [
                'name' => 'Welcome Series - Day 1',
                'description' => 'Welcome new customers with a friendly introduction and special offer',
                'category' => 'welcome',
                'type' => 'email',
                'subject_line' => 'Welcome to [Company Name]! Here\'s Your Special Gift',
                'email_content' => '<h2>Welcome to [Company Name]!</h2>
<p>Hi {{first_name}},</p>
<p>Welcome to the [Company Name] family! We\'re thrilled to have you on board.</p>
<p>As a special welcome gift, here\'s a <strong>15% discount</strong> on your first order:</p>
<div style="background: #f8f9fa; padding: 20px; text-align: center; margin: 20px 0;">
    <h3>Use Code: WELCOME15</h3>
    <p>Valid for 7 days</p>
</div>
<p>Start exploring our amazing products and enjoy your discount!</p>
<p>Best regards,<br>The [Company Name] Team</p>',
                'target_segment' => 'new',
                'is_public' => true,
                'tags' => 'welcome, new customer, discount',
                'created_by' => 1
            ],
            [
                'name' => 'Abandoned Cart Recovery',
                'description' => 'Recover abandoned carts with a compelling offer',
                'category' => 'abandoned_cart',
                'type' => 'email',
                'subject_line' => 'Don\'t Forget Your Cart - 10% Off!',
                'email_content' => '<h2>Your Cart is Waiting!</h2>
<p>Hi {{first_name}},</p>
<p>We noticed you left some items in your cart. Don\'t let them get away!</p>
<p>Complete your purchase now and save <strong>10%</strong>:</p>
<div style="background: #f8f9fa; padding: 20px; text-align: center; margin: 20px 0;">
    <h3>Use Code: CART10</h3>
    <p>Valid for 24 hours</p>
</div>
<p>Your items are still available and ready to ship!</p>
<p>Best regards,<br>The [Company Name] Team</p>',
                'target_segment' => 'inactive',
                'is_public' => true,
                'tags' => 'abandoned cart, recovery, discount',
                'created_by' => 1
            ],
            [
                'name' => 'Re-engagement Campaign',
                'description' => 'Bring back inactive customers with exclusive offers',
                'category' => 're_engagement',
                'type' => 'email',
                'subject_line' => 'We Miss You! Here\'s 20% Off',
                'email_content' => '<h2>We Miss You!</h2>
<p>Hi {{first_name}},</p>
<p>It\'s been a while since we\'ve seen you, and we miss you!</p>
<p>Come back and enjoy <strong>20% off</strong> your next order:</p>
<div style="background: #f8f9fa; padding: 20px; text-align: center; margin: 20px 0;">
    <h3>Use Code: MISSYOU20</h3>
    <p>Valid for 14 days</p>
</div>
<p>We\'ve got some exciting new products waiting for you!</p>
<p>Best regards,<br>The [Company Name] Team</p>',
                'target_segment' => 'inactive',
                'is_public' => true,
                'tags' => 're-engagement, inactive, exclusive',
                'created_by' => 1
            ],
            [
                'name' => 'Flash Sale Alert',
                'description' => 'Announce limited-time flash sales',
                'category' => 'promotional',
                'type' => 'email',
                'subject_line' => '🚨 Flash Sale: 50% Off - Ends Tonight!',
                'email_content' => '<h2>🚨 Flash Sale Alert!</h2>
<p>Hi {{first_name}},</p>
<p>Don\'t miss out on our biggest sale of the year!</p>
<p><strong>50% OFF</strong> everything - but only until midnight!</p>
<div style="background: #ff6b6b; color: white; padding: 20px; text-align: center; margin: 20px 0;">
    <h3>FLASH50</h3>
    <p>Ends Tonight at Midnight!</p>
</div>
<p>Shop now before it\'s gone!</p>
<p>Best regards,<br>The [Company Name] Team</p>',
                'target_segment' => 'loyal',
                'is_public' => true,
                'tags' => 'flash sale, limited time, urgent',
                'created_by' => 1
            ],
            [
                'name' => 'Holiday Special',
                'description' => 'Seasonal holiday promotions',
                'category' => 'seasonal',
                'type' => 'email',
                'subject_line' => '🎄 Holiday Special: 25% Off Everything',
                'email_content' => '<h2>🎄 Holiday Special</h2>
<p>Hi {{first_name}},</p>
<p>The holiday season is here, and we\'re spreading joy with amazing deals!</p>
<p>Enjoy <strong>25% off</strong> everything in our holiday collection:</p>
<div style="background: #28a745; color: white; padding: 20px; text-align: center; margin: 20px 0;">
    <h3>HOLIDAY25</h3>
    <p>Valid until December 31st</p>
</div>
<p>Perfect gifts for everyone on your list!</p>
<p>Happy Holidays,<br>The [Company Name] Team</p>',
                'target_segment' => 'high_value',
                'is_public' => true,
                'tags' => 'holiday, seasonal, gift',
                'created_by' => 1
            ],
            [
                'name' => 'VIP Customer Exclusive',
                'description' => 'Exclusive offers for high-value customers',
                'category' => 'promotional',
                'type' => 'discount',
                'subject_line' => 'VIP Exclusive: 30% Off Just for You',
                'email_content' => '<h2>VIP Exclusive Offer</h2>
<p>Hi {{first_name}},</p>
<p>As one of our valued VIP customers, you\'re getting exclusive access to our biggest discount yet!</p>
<p>Enjoy <strong>30% off</strong> your entire order:</p>
<div style="background: #6f42c1; color: white; padding: 20px; text-align: center; margin: 20px 0;">
    <h3>VIP30</h3>
    <p>VIP Exclusive - Valid for 7 days</p>
</div>
<p>Thank you for being such a loyal customer!</p>
<p>Best regards,<br>The [Company Name] Team</p>',
                'discount_percent' => 30,
                'target_segment' => 'high_value',
                'is_public' => true,
                'tags' => 'vip, exclusive, high-value',
                'created_by' => 1
            ]
        ];

        foreach ($defaultTemplates as $templateData) {
            // Check if template already exists
            $existing = $this->templateRepository->search($templateData['name']);
            if (empty($existing)) {
                $this->templateRepository->create(new CampaignTemplate($templateData));
            }
        }
    }
}

