<?php

namespace CRMAIze\Controller;

use CRMAIze\Core\Application;
use CRMAIze\Core\Request;
use CRMAIze\Core\Response;
use CRMAIze\Service\AdvancedABTestingService;
use CRMAIze\Repository\CustomerRepository;
use CRMAIze\Repository\CampaignRepository;

class AdvancedABTestingController
{
  private Application $app;
  private AdvancedABTestingService $abTestingService;
  private CustomerRepository $customerRepository;
  private CampaignRepository $campaignRepository;

  public function __construct(Application $app)
  {
    $this->app = $app;
    $this->customerRepository = $app->getCustomerRepository();
    $this->campaignRepository = $app->getCampaignRepository();
    $this->abTestingService = new AdvancedABTestingService($this->campaignRepository, $this->customerRepository);
  }

  /**
   * Show A/B testing dashboard
   */
  public function index(): Response
  {
    $abTests = $this->campaignRepository->getByType('ab_test');

    // Get summary statistics
    $summary = $this->calculateABTestSummary($abTests);

    $content = $this->app->getTwig()->render('advanced_ab_testing.twig', [
      'ab_tests' => $abTests,
      'summary' => $summary,
      'total_tests' => count($abTests)
    ]);

    return new Response($content);
  }

  /**
   * Show A/B test creation form
   */
  public function create(): Response
  {
    $customers = $this->customerRepository->getAll();
    $segments = $this->getCustomerSegments($customers);

    $content = $this->app->getTwig()->render('ab_test_form.twig', [
      'segments' => $segments,
      'total_customers' => count($customers)
    ]);

    return new Response($content);
  }

  /**
   * Create new A/B test
   */
  public function store(Request $request): Response
  {
    $testData = [
      'name' => $request->getInput('name'),
      'subject' => $request->getInput('subject'),
      'content' => $request->getInput('content'),
      'target_segment' => $request->getInput('target_segment', 'all'),
      'variants' => $this->parseVariantsFromRequest($request),
      'test_config' => [
        'confidence_level' => (float) $request->getInput('confidence_level', 0.95),
        'min_sample_size' => (int) $request->getInput('min_sample_size', 100),
        'split_ratios' => $this->parseSplitRatios($request),
        'test_duration' => (int) $request->getInput('test_duration', 14)
      ]
    ];

    try {
      $result = $this->abTestingService->createABTest($testData);

      return new Response(json_encode([
        'success' => true,
        'message' => 'A/B test created successfully',
        'test_id' => $result['test_id']
      ]), 200, ['Content-Type' => 'application/json']);
    } catch (\Exception $e) {
      return new Response(json_encode([
        'success' => false,
        'error' => $e->getMessage()
      ]), 400, ['Content-Type' => 'application/json']);
    }
  }

  /**
   * Start A/B test
   */
  public function start(Request $request): Response
  {
    $testId = (int) $request->getInput('test_id');
    $customerIds = $request->getInput('customer_ids', []);

    try {
      $result = $this->abTestingService->startABTest($testId, $customerIds);

      return new Response(json_encode([
        'success' => true,
        'message' => 'A/B test started successfully',
        'data' => $result
      ]), 200, ['Content-Type' => 'application/json']);
    } catch (\Exception $e) {
      return new Response(json_encode([
        'success' => false,
        'error' => $e->getMessage()
      ]), 400, ['Content-Type' => 'application/json']);
    }
  }

  /**
   * Get A/B test results
   */
  public function results(Request $request): Response
  {
    $testId = (int) $request->getInput('test_id');

    try {
      $results = $this->abTestingService->getABTestResults($testId);

      return new Response(json_encode([
        'success' => true,
        'data' => $results
      ]), 200, ['Content-Type' => 'application/json']);
    } catch (\Exception $e) {
      return new Response(json_encode([
        'success' => false,
        'error' => $e->getMessage()
      ]), 400, ['Content-Type' => 'application/json']);
    }
  }

  /**
   * Stop A/B test
   */
  public function stop(Request $request): Response
  {
    $testId = (int) $request->getInput('test_id');

    try {
      $result = $this->abTestingService->stopABTest($testId);

      return new Response(json_encode([
        'success' => true,
        'message' => 'A/B test stopped successfully',
        'data' => $result
      ]), 200, ['Content-Type' => 'application/json']);
    } catch (\Exception $e) {
      return new Response(json_encode([
        'success' => false,
        'error' => $e->getMessage()
      ]), 400, ['Content-Type' => 'application/json']);
    }
  }

  /**
   * Get A/B test time series data
   */
  public function timeSeries(Request $request): Response
  {
    $testId = (int) $request->getInput('test_id');
    $metric = $request->getInput('metric', 'conversion_rate');

    try {
      $timeSeries = $this->abTestingService->getABTestTimeSeries($testId, $metric);

      return new Response(json_encode([
        'success' => true,
        'data' => $timeSeries
      ]), 200, ['Content-Type' => 'application/json']);
    } catch (\Exception $e) {
      return new Response(json_encode([
        'success' => false,
        'error' => $e->getMessage()
      ]), 400, ['Content-Type' => 'application/json']);
    }
  }

  /**
   * Get A/B test summary
   */
  public function summary(Request $request): Response
  {
    $testId = (int) $request->getInput('test_id');

    try {
      $summary = $this->abTestingService->getABTestSummary($testId);

      return new Response(json_encode([
        'success' => true,
        'data' => $summary
      ]), 200, ['Content-Type' => 'application/json']);
    } catch (\Exception $e) {
      return new Response(json_encode([
        'success' => false,
        'error' => $e->getMessage()
      ]), 400, ['Content-Type' => 'application/json']);
    }
  }

  /**
   * Show detailed A/B test view
   */
  public function show(Request $request): Response
  {
    $testId = (int) $request->getInput('test_id');

    try {
      $test = $this->campaignRepository->getById($testId);
      if (!$test || $test['type'] !== 'ab_test') {
        throw new \Exception('A/B test not found');
      }

      $results = $this->abTestingService->getABTestResults($testId);
      $timeSeries = $this->abTestingService->getABTestTimeSeries($testId, 'conversion_rate');
      $summary = $this->abTestingService->getABTestSummary($testId);

      $content = $this->app->getTwig()->render('ab_test_detail.twig', [
        'test' => $test,
        'results' => $results,
        'time_series' => $timeSeries,
        'summary' => $summary
      ]);

      return new Response($content);
    } catch (\Exception $e) {
      return new Response('Error: ' . $e->getMessage(), 404);
    }
  }

  /**
   * Get customer segments for targeting
   */
  public function getSegments(): Response
  {
    $customers = $this->customerRepository->getAll();
    $segments = $this->getCustomerSegments($customers);

    return new Response(json_encode([
      'success' => true,
      'data' => $segments
    ]), 200, ['Content-Type' => 'application/json']);
  }

  /**
   * Get customers for a specific segment
   */
  public function getSegmentCustomers(Request $request): Response
  {
    $segment = $request->getInput('segment', 'all');
    $customers = $this->customerRepository->getAll();

    $segmentCustomers = $this->filterCustomersBySegment($customers, $segment);

    return new Response(json_encode([
      'success' => true,
      'data' => [
        'segment' => $segment,
        'customers' => $segmentCustomers,
        'count' => count($segmentCustomers)
      ]
    ]), 200, ['Content-Type' => 'application/json']);
  }

  // Private helper methods

  private function calculateABTestSummary(array $abTests): array
  {
    $summary = [
      'total_tests' => count($abTests),
      'running_tests' => 0,
      'completed_tests' => 0,
      'draft_tests' => 0,
      'total_customers' => 0,
      'average_conversion_rate' => 0,
      'tests_with_significance' => 0
    ];

    $totalConversionRate = 0;
    $testsWithConversion = 0;

    foreach ($abTests as $test) {
      $status = $test['status'] ?? 'draft';
      $summary["{$status}_tests"]++;

      if ($status === 'running' || $status === 'completed') {
        try {
          $results = $this->abTestingService->getABTestResults($test['id']);
          $summary['total_customers'] += array_sum(array_column($results['metrics'], 'total_sent'));

          $avgConversionRate = array_sum(array_column($results['metrics'], 'conversion_rate')) / count($results['metrics']);
          $totalConversionRate += $avgConversionRate;
          $testsWithConversion++;

          if (!empty(array_filter($results['statistical_significance'], function ($sig) {
            return $sig['significant'];
          }))) {
            $summary['tests_with_significance']++;
          }
        } catch (\Exception $e) {
          // Skip tests with errors
        }
      }
    }

    if ($testsWithConversion > 0) {
      $summary['average_conversion_rate'] = $totalConversionRate / $testsWithConversion;
    }

    return $summary;
  }

  private function getCustomerSegments(array $customers): array
  {
    $segments = [
      'all' => [
        'name' => 'All Customers',
        'count' => count($customers),
        'description' => 'All customers in the database'
      ],
      'high_value' => [
        'name' => 'High Value Customers',
        'count' => 0,
        'description' => 'Customers with high total spend'
      ],
      'new' => [
        'name' => 'New Customers',
        'count' => 0,
        'description' => 'Customers with 1 or fewer orders'
      ],
      'at_risk' => [
        'name' => 'At Risk Customers',
        'count' => 0,
        'description' => 'Customers who haven\'t ordered in 90+ days'
      ],
      'loyal' => [
        'name' => 'Loyal Customers',
        'count' => 0,
        'description' => 'Customers with 3+ orders and recent activity'
      ]
    ];

    foreach ($customers as $customer) {
      $totalSpent = $customer['total_spent'] ?? 0;
      $orderCount = $customer['order_count'] ?? 0;
      $lastOrderDate = $customer['last_order_date'];
      $daysSinceLastOrder = $lastOrderDate ? (time() - strtotime($lastOrderDate)) / (24 * 60 * 60) : 999;

      if ($totalSpent > 1000 && $orderCount > 5) {
        $segments['high_value']['count']++;
      }
      if ($orderCount <= 1 && $daysSinceLastOrder < 60) {
        $segments['new']['count']++;
      }
      if ($daysSinceLastOrder > 90 && $totalSpent > 100) {
        $segments['at_risk']['count']++;
      }
      if ($orderCount > 3 && $daysSinceLastOrder < 30) {
        $segments['loyal']['count']++;
      }
    }

    return $segments;
  }

  private function filterCustomersBySegment(array $customers, string $segment): array
  {
    if ($segment === 'all') {
      return $customers;
    }

    return array_filter($customers, function ($customer) use ($segment) {
      $totalSpent = $customer['total_spent'] ?? 0;
      $orderCount = $customer['order_count'] ?? 0;
      $lastOrderDate = $customer['last_order_date'];
      $daysSinceLastOrder = $lastOrderDate ? (time() - strtotime($lastOrderDate)) / (24 * 60 * 60) : 999;

      return match ($segment) {
        'high_value' => $totalSpent > 1000 && $orderCount > 5,
        'new' => $orderCount <= 1 && $daysSinceLastOrder < 60,
        'at_risk' => $daysSinceLastOrder > 90 && $totalSpent > 100,
        'loyal' => $orderCount > 3 && $daysSinceLastOrder < 30,
        default => true
      };
    });
  }

  private function parseVariantsFromRequest(Request $request): array
  {
    $variants = [];
    $variantCount = (int) $request->getInput('variant_count', 2);

    for ($i = 0; $i < $variantCount; $i++) {
      $variants[] = [
        'name' => $request->getInput("variant_{$i}_name", "Variant " . ($i + 1)),
        'subject' => $request->getInput("variant_{$i}_subject", ""),
        'content' => $request->getInput("variant_{$i}_content", ""),
        'description' => $request->getInput("variant_{$i}_description", "")
      ];
    }

    return $variants;
  }

  private function parseSplitRatios(Request $request): array
  {
    $variantCount = (int) $request->getInput('variant_count', 2);
    $splitRatios = [];

    for ($i = 0; $i < $variantCount; $i++) {
      $splitRatios[] = (float) $request->getInput("split_ratio_{$i}", 100 / $variantCount);
    }

    return $splitRatios;
  }
}
