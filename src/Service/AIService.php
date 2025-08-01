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
}
