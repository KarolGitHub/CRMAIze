<?php

namespace CRMAIze\Service;

class AIService
{
  public function segmentCustomers(array $customers): array
  {
    $segments = [
      'high_value' => [],
      'at_risk' => [],
      'loyal' => [],
      'new' => [],
      'inactive' => []
    ];

    foreach ($customers as $customer) {
      $segment = $this->determineSegment($customer);
      $segments[$segment][] = $customer;
    }

    return $segments;
  }

  private function determineSegment(array $customer): string
  {
    $totalSpent = $customer['total_spent'] ?? 0;
    $orderCount = $customer['order_count'] ?? 0;
    $lastOrderDate = $customer['last_order_date'] ?? null;
    $daysSinceLastOrder = $lastOrderDate ? (time() - strtotime($lastOrderDate)) / 86400 : 999;

    // High value customers
    if ($totalSpent > 1000 && $orderCount > 5) {
      return 'high_value';
    }

    // At risk customers
    if ($daysSinceLastOrder > 90 && $totalSpent > 100) {
      return 'at_risk';
    }

    // Loyal customers
    if ($orderCount > 3 && $daysSinceLastOrder < 30) {
      return 'loyal';
    }

    // New customers
    if ($orderCount <= 1 && $daysSinceLastOrder < 60) {
      return 'new';
    }

    // Inactive customers
    return 'inactive';
  }

  public function calculateChurnRisk(array $customer): float
  {
    $totalSpent = $customer['total_spent'] ?? 0;
    $orderCount = $customer['order_count'] ?? 0;
    $lastOrderDate = $customer['last_order_date'] ?? null;
    $daysSinceLastOrder = $lastOrderDate ? (time() - strtotime($lastOrderDate)) / 86400 : 999;

    $risk = 0.0;

    // Time since last order (higher weight)
    if ($daysSinceLastOrder > 180) {
      $risk += 0.4;
    } elseif ($daysSinceLastOrder > 90) {
      $risk += 0.3;
    } elseif ($daysSinceLastOrder > 30) {
      $risk += 0.1;
    }

    // Order frequency
    if ($orderCount <= 1) {
      $risk += 0.2;
    } elseif ($orderCount <= 3) {
      $risk += 0.1;
    }

    // Spending pattern
    if ($totalSpent < 50) {
      $risk += 0.2;
    } elseif ($totalSpent < 200) {
      $risk += 0.1;
    }

    return min(1.0, $risk);
  }

  public function generateSubjectLine(string $campaignType, array $customer): string
  {
    $firstName = $customer['first_name'] ?? 'there';
    $templates = [
      'email' => [
        "Hey {$firstName}, we miss you!",
        "{$firstName}, here's something special for you",
        "Don't miss out, {$firstName}!",
        "Exclusive offer for {$firstName}"
      ],
      'discount' => [
        "{$firstName}, your exclusive discount awaits!",
        "Special savings just for {$firstName}",
        "{$firstName}, don't miss these deals!",
        "Limited time offer for {$firstName}"
      ]
    ];

    $typeTemplates = $templates[$campaignType] ?? $templates['email'];
    return $typeTemplates[array_rand($typeTemplates)];
  }

  public function suggestDiscount(array $customer): int
  {
    $totalSpent = $customer['total_spent'] ?? 0;
    $churnRisk = $this->calculateChurnRisk($customer);

    // Higher discount for at-risk customers
    if ($churnRisk > 0.7) {
      return rand(20, 30);
    } elseif ($churnRisk > 0.5) {
      return rand(15, 25);
    } elseif ($totalSpent > 500) {
      return rand(10, 20);
    } else {
      return rand(5, 15);
    }
  }

  public function generateCouponCode(): string
  {
    $prefix = 'CRMAIZE';
    $timestamp = time();
    $random = rand(1000, 9999);
    return $prefix . $timestamp . $random;
  }

  public function generateABTestVariants(string $campaignType, array $customerData, int $variantCount = 2): array
  {
    $variants = [];

    for ($i = 0; $i < $variantCount; $i++) {
      $variants[] = [
        'variant_name' => $i === 0 ? 'Control' : "Variant " . ($i + 1),
        'subject_line' => $this->generateSubjectLine($campaignType, $customerData, $i),
        'email_content' => $this->generateEmailContent($campaignType, $customerData, $i),
        'discount_percent' => $campaignType === 'discount' ? $this->suggestDiscount($customerData) : null,
        'is_control' => $i === 0 ? 1 : 0
      ];
    }

    return $variants;
  }

  public function generateEmailContent(string $campaignType, array $customerData, int $variantIndex = 0): string
  {
    $customerName = $customerData['first_name'] ?? 'Valued Customer';
    $segment = $customerData['segment'] ?? 'loyal';

    $templates = [
      'email' => [
        "Dear {{customer_name}},\n\nWe hope this email finds you well! We wanted to reach out with some exciting news and updates.\n\nBest regards,\nThe Team",
        "Hi {{customer_name}},\n\nThank you for being part of our community. We have something special just for you!\n\nWarm regards,\nYour Friends",
        "Hello {{customer_name}},\n\nWe appreciate your continued support. Here's what's new and exciting for you!\n\nCheers,\nThe Team"
      ],
      'discount' => [
        "Dear {{customer_name}},\n\nAs a valued {{customer_segment}} customer, we're offering you an exclusive {{discount_percent}}% discount on your next purchase!\n\nUse code: SAVE{{discount_percent}}\n\nBest regards,\nThe Team",
        "Hi {{customer_name}},\n\nSpecial offer just for you! Enjoy {{discount_percent}}% off your next order.\n\nPromo code: SPECIAL{{discount_percent}}\n\nWarm regards,\nYour Friends",
        "Hello {{customer_name}},\n\nWe've got a fantastic deal for our {{customer_segment}} customers: {{discount_percent}}% off!\n\nCode: DEAL{{discount_percent}}\n\nCheers,\nThe Team"
      ]
    ];

    $template = $templates[$campaignType][$variantIndex % count($templates[$campaignType])] ?? $templates[$campaignType][0];

    return str_replace(
      ['{{customer_name}}', '{{customer_segment}}', '{{discount_percent}}'],
      [$customerName, $segment, $customerData['discount_percent'] ?? 15],
      $template
    );
  }

  public function suggestOptimalSendTime(array $customerData): string
  {
    $segment = $customerData['segment'] ?? 'loyal';
    $lastOrderDate = $customerData['last_order_date'] ?? null;

    // AI logic for optimal send time
    $now = new \DateTime();

    if ($segment === 'at_risk') {
      // Send immediately for at-risk customers
      return $now->format('Y-m-d H:i:s');
    } elseif ($segment === 'high_value') {
      // Send during business hours for high-value customers
      $now->setTime(10, 0); // 10 AM
      return $now->format('Y-m-d H:i:s');
    } else {
      // Send in the evening for general customers
      $now->setTime(18, 0); // 6 PM
      return $now->format('Y-m-d H:i:s');
    }
  }

  public function analyzeCampaignPerformance(array $campaignData): array
  {
    $sentCount = $campaignData['sent_count'] ?? 0;
    $openRate = $campaignData['open_rate'] ?? 0;
    $clickRate = $campaignData['click_rate'] ?? 0;

    $analysis = [
      'performance_score' => 0,
      'recommendations' => [],
      'next_actions' => []
    ];

    // Calculate performance score
    $analysis['performance_score'] = ($openRate * 0.4) + ($clickRate * 0.6);

    // Generate recommendations
    if ($openRate < 0.15) {
      $analysis['recommendations'][] = 'Subject line needs improvement - consider A/B testing';
    }

    if ($clickRate < 0.02) {
      $analysis['recommendations'][] = 'Email content may need optimization - test different CTAs';
    }

    if ($analysis['performance_score'] > 0.8) {
      $analysis['next_actions'][] = 'Campaign performing well - consider scaling to larger audience';
    } else {
      $analysis['next_actions'][] = 'Run A/B test to improve performance';
    }

    return $analysis;
  }
}
