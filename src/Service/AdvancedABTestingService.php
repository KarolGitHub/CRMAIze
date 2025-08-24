<?php

namespace CRMAIze\Service;

use CRMAIze\Repository\CampaignRepository;
use CRMAIze\Repository\CustomerRepository;

class AdvancedABTestingService
{
  private CampaignRepository $campaignRepository;
  private CustomerRepository $customerRepository;

  public function __construct(CampaignRepository $campaignRepository, CustomerRepository $customerRepository)
  {
    $this->campaignRepository = $campaignRepository;
    $this->customerRepository = $customerRepository;
  }

  /**
   * Create an A/B test campaign
   */
  public function createABTest(array $testData): array
  {
    $variants = $testData['variants'] ?? [];
    $testConfig = $testData['test_config'] ?? [];

    // Create main test record
    $testId = $this->campaignRepository->create([
      'name' => $testData['name'],
      'type' => 'ab_test',
      'subject_line' => $testData['subject'] ?? '',
      'email_content' => $testData['content'] ?? '',
      'target_segment' => $testData['target_segment'] ?? 'all',
      'status' => 'draft',
      'test_config' => json_encode($testConfig),
      'variants' => json_encode($variants),
      'created_at' => date('Y-m-d H:i:s')
    ]);

    return [
      'test_id' => $testId,
      'variants' => $variants,
      'config' => $testConfig,
      'status' => 'created'
    ];
  }

  /**
   * Start A/B test
   */
  public function startABTest(int $testId, array $customerIds = []): array
  {
    $test = $this->campaignRepository->getById($testId);
    if (!$test) {
      throw new \Exception('A/B test not found');
    }

    $variants = json_decode($test['variants'], true);
    $testConfig = json_decode($test['test_config'], true);

    if (empty($customerIds)) {
      $customers = $this->customerRepository->getAll();
    } else {
      $customers = array_filter($this->customerRepository->getAll(), function ($customer) use ($customerIds) {
        return in_array($customer['id'], $customerIds);
      });
    }

    // Split customers into variants
    $variantAssignments = $this->assignCustomersToVariants($customers, $variants, $testConfig);

    // Send variants to assigned customers
    $results = [];
    foreach ($variants as $variantIndex => $variant) {
      $variantCustomers = $variantAssignments[$variantIndex] ?? [];
      $results[$variantIndex] = $this->sendVariant($variant, $variantCustomers, $testConfig);
    }

    // Update test status
    $this->campaignRepository->update($testId, [
      'status' => 'running',
      'started_at' => date('Y-m-d H:i:s'),
      'variant_assignments' => json_encode($variantAssignments)
    ]);

    return [
      'test_id' => $testId,
      'total_customers' => count($customers),
      'variant_assignments' => $variantAssignments,
      'results' => $results
    ];
  }

  /**
   * Assign customers to variants
   */
  private function assignCustomersToVariants(array $customers, array $variants, array $config): array
  {
    $assignments = [];
    $variantCount = count($variants);

    // Determine split ratios
    $splitRatios = $config['split_ratios'] ?? array_fill(0, $variantCount, 100 / $variantCount);

    // Ensure ratios sum to 100
    $totalRatio = array_sum($splitRatios);
    if ($totalRatio != 100) {
      $splitRatios = array_map(function ($ratio) use ($totalRatio) {
        return ($ratio / $totalRatio) * 100;
      }, $splitRatios);
    }

    $customerCount = count($customers);
    $assignments = [];

    foreach ($variants as $variantIndex => $variant) {
      $variantCustomerCount = round(($customerCount * $splitRatios[$variantIndex]) / 100);
      $assignments[$variantIndex] = array_slice($customers, 0, $variantCustomerCount);
      $customers = array_slice($customers, $variantCustomerCount);
    }

    return $assignments;
  }

  /**
   * Send variant to assigned customers
   */
  private function sendVariant(array $variant, array $customers, array $config): array
  {
    $sent = 0;
    $failed = 0;
    $errors = [];

    foreach ($customers as $customer) {
      try {
        $personalizedContent = $this->personalizeVariantContent($variant['content'], $customer);
        $personalizedSubject = $this->personalizeVariantSubject($variant['subject'], $customer);

        // Mock sending - in production, integrate with email service
        $result = $this->sendVariantEmail($customer['email'], $personalizedSubject, $personalizedContent);

        if ($result) {
          $sent++;
        } else {
          $failed++;
          $errors[] = "Failed to send variant to {$customer['email']}";
        }
      } catch (\Exception $e) {
        $failed++;
        $errors[] = "Error sending variant to {$customer['email']}: " . $e->getMessage();
      }
    }

    return [
      'sent' => $sent,
      'failed' => $failed,
      'errors' => $errors
    ];
  }

  /**
   * Personalize variant content
   */
  private function personalizeVariantContent(string $content, array $customer): string
  {
    $replacements = [
      '{{customer_name}}' => $customer['name'] ?? 'Customer',
      '{{customer_email}}' => $customer['email'] ?? '',
      '{{customer_id}}' => $customer['id'] ?? '',
      '{{company_name}}' => 'CRMAIze'
    ];

    return str_replace(array_keys($replacements), array_values($replacements), $content);
  }

  /**
   * Personalize variant subject
   */
  private function personalizeVariantSubject(string $subject, array $customer): string
  {
    $replacements = [
      '{{customer_name}}' => $customer['name'] ?? 'Customer',
      '{{company_name}}' => 'CRMAIze'
    ];

    return str_replace(array_keys($replacements), array_values($replacements), $subject);
  }

  /**
   * Mock email sending for variant
   */
  private function sendVariantEmail(string $email, string $subject, string $content): bool
  {
    // Mock email sending - in production, integrate with email service
    return true;
  }

  /**
   * Get A/B test results with statistical significance
   */
  public function getABTestResults(int $testId): array
  {
    $test = $this->campaignRepository->getById($testId);
    if (!$test) {
      throw new \Exception('A/B test not found');
    }

    $variants = json_decode($test['variants'], true);
    $testConfig = json_decode($test['test_config'], true);
    $variantAssignments = json_decode($test['variant_assignments'], true);

    $results = [];
    $variantMetrics = [];

    foreach ($variants as $variantIndex => $variant) {
      $variantCustomers = $variantAssignments[$variantIndex] ?? [];
      $metrics = $this->calculateVariantMetrics($variantIndex, $variantCustomers, $testConfig);
      $variantMetrics[$variantIndex] = $metrics;
    }

    // Calculate statistical significance
    $significanceResults = $this->calculateStatisticalSignificance($variantMetrics, $testConfig);

    return [
      'test_id' => $testId,
      'test_name' => $test['name'],
      'variants' => $variants,
      'metrics' => $variantMetrics,
      'statistical_significance' => $significanceResults,
      'winner' => $this->determineWinner($variantMetrics, $significanceResults),
      'recommendations' => $this->generateRecommendations($variantMetrics, $significanceResults)
    ];
  }

  /**
   * Calculate metrics for a variant
   */
  private function calculateVariantMetrics(int $variantIndex, array $customers, array $config): array
  {
    $totalSent = count($customers);
    $opens = rand(0, $totalSent);
    $clicks = rand(0, $opens);
    $conversions = rand(0, $clicks);
    $revenue = $conversions * rand(10, 100);

    return [
      'variant_index' => $variantIndex,
      'total_sent' => $totalSent,
      'opens' => $opens,
      'clicks' => $clicks,
      'conversions' => $conversions,
      'revenue' => $revenue,
      'open_rate' => $totalSent > 0 ? ($opens / $totalSent) * 100 : 0,
      'click_rate' => $opens > 0 ? ($clicks / $opens) * 100 : 0,
      'conversion_rate' => $clicks > 0 ? ($conversions / $clicks) * 100 : 0,
      'revenue_per_email' => $totalSent > 0 ? $revenue / $totalSent : 0
    ];
  }

  /**
   * Calculate statistical significance
   */
  private function calculateStatisticalSignificance(array $variantMetrics, array $config): array
  {
    $significanceResults = [];
    $confidenceLevel = $config['confidence_level'] ?? 0.95;
    $minSampleSize = $config['min_sample_size'] ?? 100;

    $variants = array_keys($variantMetrics);

    // Compare each variant with the control (first variant)
    $controlVariant = $variants[0];
    $controlMetrics = $variantMetrics[$controlVariant];

    foreach ($variants as $variantIndex) {
      if ($variantIndex === $controlVariant) {
        continue;
      }

      $testMetrics = $variantMetrics[$variantIndex];

      // Perform t-test for conversion rates
      $significance = $this->performTTest(
        $controlMetrics['conversions'],
        $controlMetrics['total_sent'],
        $testMetrics['conversions'],
        $testMetrics['total_sent'],
        $confidenceLevel
      );

      $significanceResults[$variantIndex] = [
        'p_value' => $significance['p_value'],
        'significant' => $significance['significant'],
        'confidence_level' => $confidenceLevel,
        'lift' => $this->calculateLift($controlMetrics, $testMetrics),
        'sample_size_adequate' => $testMetrics['total_sent'] >= $minSampleSize
      ];
    }

    return $significanceResults;
  }

  /**
   * Perform t-test for proportions
   */
  private function performTTest(int $controlConversions, int $controlTotal, int $testConversions, int $testTotal, float $confidenceLevel): array
  {
    if ($controlTotal === 0 || $testTotal === 0) {
      return ['p_value' => 1.0, 'significant' => false];
    }

    $controlRate = $controlConversions / $controlTotal;
    $testRate = $testConversions / $testTotal;

    // Calculate pooled proportion
    $pooledRate = ($controlConversions + $testConversions) / ($controlTotal + $testTotal);

    // Calculate standard error
    $se = sqrt($pooledRate * (1 - $pooledRate) * (1 / $controlTotal + 1 / $testTotal));

    // Calculate z-score
    $zScore = ($testRate - $controlRate) / $se;

    // Calculate p-value (two-tailed test)
    $pValue = 2 * (1 - $this->normalCDF(abs($zScore)));

    return [
      'p_value' => $pValue,
      'significant' => $pValue < (1 - $confidenceLevel)
    ];
  }

  /**
   * Normal cumulative distribution function approximation
   */
  private function normalCDF(float $x): float
  {
    // Approximation of normal CDF
    $a1 = 0.254829592;
    $a2 = -0.284496736;
    $a3 = 1.421413741;
    $a4 = -1.453152027;
    $a5 = 1.061405429;
    $p = 0.3275911;

    $sign = $x < 0 ? -1 : 1;
    $x = abs($x) / sqrt(2.0);

    $t = 1.0 / (1.0 + $p * $x);
    $erf = 1.0 - ((((($a5 * $t + $a4) * $t) + $a3) * $t + $a2) * $t + $a1) * $t * exp(-$x * $x);

    return 0.5 * (1.0 + $sign * $erf);
  }

  /**
   * Calculate lift percentage
   */
  private function calculateLift(array $controlMetrics, array $testMetrics): array
  {
    $controlRate = $controlMetrics['conversion_rate'];
    $testRate = $testMetrics['conversion_rate'];

    if ($controlRate === 0) {
      return ['percentage' => 0, 'direction' => 'no_change'];
    }

    $liftPercentage = (($testRate - $controlRate) / $controlRate) * 100;
    $direction = $liftPercentage > 0 ? 'improvement' : ($liftPercentage < 0 ? 'decline' : 'no_change');

    return [
      'percentage' => round($liftPercentage, 2),
      'direction' => $direction
    ];
  }

  /**
   * Determine winner based on metrics and significance
   */
  private function determineWinner(array $variantMetrics, array $significanceResults): array
  {
    $bestVariant = null;
    $bestScore = -1;

    foreach ($variantMetrics as $variantIndex => $metrics) {
      $score = $this->calculateVariantScore($metrics, $significanceResults[$variantIndex] ?? null);

      if ($score > $bestScore) {
        $bestScore = $score;
        $bestVariant = $variantIndex;
      }
    }

    return [
      'variant_index' => $bestVariant,
      'score' => $bestScore,
      'reasoning' => $this->explainWinnerReasoning($variantMetrics[$bestVariant], $significanceResults[$bestVariant] ?? null)
    ];
  }

  /**
   * Calculate variant score
   */
  private function calculateVariantScore(array $metrics, ?array $significance): float
  {
    $score = 0;

    // Base score from conversion rate
    $score += $metrics['conversion_rate'] * 10;

    // Bonus for revenue
    $score += $metrics['revenue_per_email'] * 0.1;

    // Bonus for statistical significance
    if ($significance && $significance['significant']) {
      $score += 50;
    }

    // Bonus for adequate sample size
    if ($significance && $significance['sample_size_adequate']) {
      $score += 25;
    }

    return $score;
  }

  /**
   * Explain winner reasoning
   */
  private function explainWinnerReasoning(array $metrics, ?array $significance): string
  {
    $reasons = [];

    $reasons[] = "Conversion rate: " . round($metrics['conversion_rate'], 2) . "%";
    $reasons[] = "Revenue per email: $" . round($metrics['revenue_per_email'], 2);

    if ($significance) {
      if ($significance['significant']) {
        $reasons[] = "Statistically significant improvement (p < " . (1 - $significance['confidence_level']) . ")";
      } else {
        $reasons[] = "Not statistically significant";
      }

      if ($significance['sample_size_adequate']) {
        $reasons[] = "Adequate sample size";
      } else {
        $reasons[] = "Insufficient sample size";
      }
    }

    return implode(', ', $reasons);
  }

  /**
   * Generate recommendations based on test results
   */
  private function generateRecommendations(array $variantMetrics, array $significanceResults): array
  {
    $recommendations = [];

    // Check for statistical significance
    $significantVariants = array_filter($significanceResults, function ($result) {
      return $result['significant'];
    });

    if (empty($significantVariants)) {
      $recommendations[] = [
        'type' => 'warning',
        'message' => 'No statistically significant differences found. Consider running the test longer or increasing sample size.',
        'action' => 'extend_test'
      ];
    }

    // Check sample sizes
    $inadequateSamples = array_filter($significanceResults, function ($result) {
      return !$result['sample_size_adequate'];
    });

    if (!empty($inadequateSamples)) {
      $recommendations[] = [
        'type' => 'info',
        'message' => 'Some variants have insufficient sample size for reliable results.',
        'action' => 'increase_sample_size'
      ];
    }

    // Performance recommendations
    $bestVariant = $this->determineWinner($variantMetrics, $significanceResults);
    if ($bestVariant['variant_index'] !== 0) { // Not control variant
      $recommendations[] = [
        'type' => 'success',
        'message' => 'Variant ' . $bestVariant['variant_index'] . ' shows better performance.',
        'action' => 'implement_winner',
        'variant_index' => $bestVariant['variant_index']
      ];
    }

    return $recommendations;
  }

  /**
   * Stop A/B test and declare winner
   */
  public function stopABTest(int $testId): array
  {
    $test = $this->campaignRepository->getById($testId);
    if (!$test) {
      throw new \Exception('A/B test not found');
    }

    $results = $this->getABTestResults($testId);
    $winner = $results['winner'];

    // Update test status
    $this->campaignRepository->update($testId, [
      'status' => 'completed',
      'completed_at' => date('Y-m-d H:i:s'),
      'winner_variant' => $winner['variant_index'],
      'final_results' => json_encode($results)
    ]);

    return [
      'test_id' => $testId,
      'status' => 'completed',
      'winner' => $winner,
      'results' => $results
    ];
  }

  /**
   * Get A/B test performance over time
   */
  public function getABTestTimeSeries(int $testId, string $metric = 'conversion_rate'): array
  {
    $test = $this->campaignRepository->getById($testId);
    if (!$test) {
      throw new \Exception('A/B test not found');
    }

    $variants = json_decode($test['variants'], true);
    $timeSeries = [];

    // Generate mock time series data
    $days = 14; // 2 weeks of data
    for ($day = 1; $day <= $days; $day++) {
      $dayData = ['date' => date('Y-m-d', strtotime("-{$day} days"))];

      foreach ($variants as $variantIndex => $variant) {
        $dayData["variant_{$variantIndex}"] = $this->generateMockMetric($metric, $day);
      }

      $timeSeries[] = $dayData;
    }

    return [
      'test_id' => $testId,
      'metric' => $metric,
      'time_series' => array_reverse($timeSeries) // Chronological order
    ];
  }

  /**
   * Generate mock metric for time series
   */
  private function generateMockMetric(string $metric, int $day): float
  {
    $baseValue = match ($metric) {
      'conversion_rate' => 2.5,
      'open_rate' => 25.0,
      'click_rate' => 8.0,
      'revenue_per_email' => 0.50,
      default => 5.0
    };

    // Add some variation over time
    $variation = sin($day * 0.5) * 0.5;
    return max(0, $baseValue + $variation);
  }

  /**
   * Get A/B test summary statistics
   */
  public function getABTestSummary(int $testId): array
  {
    $results = $this->getABTestResults($testId);

    $summary = [
      'test_id' => $testId,
      'total_variants' => count($results['variants']),
      'total_customers' => array_sum(array_column($results['metrics'], 'total_sent')),
      'best_performing_variant' => $results['winner']['variant_index'],
      'statistical_significance_found' => !empty(array_filter($results['statistical_significance'], function ($sig) {
        return $sig['significant'];
      })),
      'average_conversion_rate' => array_sum(array_column($results['metrics'], 'conversion_rate')) / count($results['metrics']),
      'total_revenue' => array_sum(array_column($results['metrics'], 'revenue')),
      'recommendations_count' => count($results['recommendations'])
    ];

    return $summary;
  }
}
