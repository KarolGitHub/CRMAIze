<?php

namespace CRMAIze\Controller;

use CRMAIze\Core\Application;
use CRMAIze\Core\Request;
use CRMAIze\Core\Response;
use CRMAIze\Service\AdvancedAIService;
use CRMAIze\Repository\CustomerRepository;
use CRMAIze\Repository\CampaignRepository;

class AdvancedAIController
{
  private Application $app;
  private AdvancedAIService $aiService;
  private CustomerRepository $customerRepository;
  private CampaignRepository $campaignRepository;

  public function __construct(Application $app)
  {
    $this->app = $app;
    $this->customerRepository = $app->getCustomerRepository();
    $this->campaignRepository = $app->getCampaignRepository();
    $this->aiService = new AdvancedAIService($this->customerRepository, $this->campaignRepository);
  }

  /**
   * Show advanced AI dashboard
   */
  public function index(): Response
  {
    $customers = $this->customerRepository->getAll();
    $campaigns = $this->campaignRepository->getAll();

    // Get AI insights
    $churnPredictions = $this->aiService->predictChurnWithAdvancedML($customers);
    $clvResults = $this->aiService->calculateCustomerLifetimeValue($customers);
    $recommendations = $this->aiService->generatePersonalizedRecommendations($customers, $campaigns);

    // Calculate summary statistics
    $summary = $this->calculateAISummary($churnPredictions, $clvResults, $recommendations);

    $content = $this->app->getTwig()->render('advanced_ai.twig', [
      'churn_predictions' => $churnPredictions,
      'clv_results' => $clvResults,
      'recommendations' => $recommendations,
      'summary' => $summary,
      'total_customers' => count($customers),
      'total_campaigns' => count($campaigns)
    ]);

    return new Response($content);
  }

  /**
   * API endpoint for churn predictions
   */
  public function getChurnPredictions(): Response
  {
    $customers = $this->customerRepository->getAll();
    $predictions = $this->aiService->predictChurnWithAdvancedML($customers);

    return new Response(json_encode([
      'success' => true,
      'data' => $predictions,
      'summary' => $this->calculateChurnSummary($predictions)
    ]), 200, ['Content-Type' => 'application/json']);
  }

  /**
   * API endpoint for CLV calculations
   */
  public function getCLVCalculations(): Response
  {
    $customers = $this->customerRepository->getAll();
    $clvResults = $this->aiService->calculateCustomerLifetimeValue($customers);

    return new Response(json_encode([
      'success' => true,
      'data' => $clvResults,
      'summary' => $this->calculateCLVSummary($clvResults)
    ]), 200, ['Content-Type' => 'application/json']);
  }

  /**
   * API endpoint for personalized recommendations
   */
  public function getRecommendations(): Response
  {
    $customers = $this->customerRepository->getAll();
    $campaigns = $this->campaignRepository->getAll();
    $recommendations = $this->aiService->generatePersonalizedRecommendations($customers, $campaigns);

    return new Response(json_encode([
      'success' => true,
      'data' => $recommendations,
      'summary' => $this->calculateRecommendationSummary($recommendations)
    ]), 200, ['Content-Type' => 'application/json']);
  }

  /**
   * API endpoint for sentiment analysis
   */
  public function analyzeSentiment(Request $request): Response
  {
    $feedbackData = $request->getInput('feedback_data', []);

    if (empty($feedbackData)) {
      return new Response(json_encode([
        'success' => false,
        'error' => 'No feedback data provided'
      ]), 400, ['Content-Type' => 'application/json']);
    }

    $sentimentResults = $this->aiService->analyzeCustomerSentiment($feedbackData);

    return new Response(json_encode([
      'success' => true,
      'data' => $sentimentResults,
      'summary' => $this->calculateSentimentSummary($sentimentResults)
    ]), 200, ['Content-Type' => 'application/json']);
  }

  /**
   * Get AI model performance metrics
   */
  public function getModelPerformance(): Response
  {
    $customers = $this->customerRepository->getAll();
    $churnPredictions = $this->aiService->predictChurnWithAdvancedML($customers);

    $performance = [
      'model_accuracy' => $this->calculateModelAccuracy($churnPredictions),
      'prediction_confidence' => $this->calculateAverageConfidence($churnPredictions),
      'risk_distribution' => $this->calculateRiskDistribution($churnPredictions),
      'feature_importance' => $this->calculateFeatureImportance($churnPredictions)
    ];

    return new Response(json_encode([
      'success' => true,
      'data' => $performance
    ]), 200, ['Content-Type' => 'application/json']);
  }

  /**
   * Generate AI insights report
   */
  public function generateInsightsReport(): Response
  {
    $customers = $this->customerRepository->getAll();
    $campaigns = $this->campaignRepository->getAll();

    $report = [
      'generated_at' => date('Y-m-d H:i:s'),
      'customer_insights' => $this->generateCustomerInsights($customers),
      'campaign_insights' => $this->generateCampaignInsights($campaigns),
      'ai_predictions' => [
        'churn' => $this->aiService->predictChurnWithAdvancedML($customers),
        'clv' => $this->aiService->calculateCustomerLifetimeValue($customers),
        'recommendations' => $this->aiService->generatePersonalizedRecommendations($customers, $campaigns)
      ],
      'action_items' => $this->generateActionItems($customers, $campaigns)
    ];

    return new Response(json_encode([
      'success' => true,
      'data' => $report
    ]), 200, ['Content-Type' => 'application/json']);
  }

  // Private helper methods

  private function calculateAISummary(array $churnPredictions, array $clvResults, array $recommendations): array
  {
    return [
      'high_risk_customers' => count(array_filter($churnPredictions, fn($p) => $p['risk_level'] === 'high')),
      'total_clv' => array_sum(array_column($clvResults, 'recommended_clv')),
      'avg_clv' => array_sum(array_column($clvResults, 'recommended_clv')) / count($clvResults),
      'personalization_score' => array_sum(array_column($recommendations, 'personalization_score')) / count($recommendations),
      'recommendations_count' => count($recommendations)
    ];
  }

  private function calculateChurnSummary(array $predictions): array
  {
    $riskLevels = array_count_values(array_column($predictions, 'risk_level'));
    $avgConfidence = array_sum(array_column($predictions, 'confidence')) / count($predictions);
    $avgProbability = array_sum(array_column($predictions, 'churn_probability')) / count($predictions);

    return [
      'total_customers' => count($predictions),
      'high_risk' => $riskLevels['high'] ?? 0,
      'medium_risk' => $riskLevels['medium'] ?? 0,
      'low_risk' => $riskLevels['low'] ?? 0,
      'average_confidence' => round($avgConfidence, 4),
      'average_churn_probability' => round($avgProbability, 4)
    ];
  }

  private function calculateCLVSummary(array $clvResults): array
  {
    $historicalCLV = array_column($clvResults, 'historical_clv');
    $predictiveCLV = array_column($clvResults, 'predictive_clv');
    $recommendedCLV = array_column($clvResults, 'recommended_clv');

    return [
      'total_customers' => count($clvResults),
      'total_historical_clv' => array_sum($historicalCLV),
      'total_predictive_clv' => array_sum($predictiveCLV),
      'total_recommended_clv' => array_sum($recommendedCLV),
      'average_historical_clv' => array_sum($historicalCLV) / count($historicalCLV),
      'average_predictive_clv' => array_sum($predictiveCLV) / count($predictiveCLV),
      'average_recommended_clv' => array_sum($recommendedCLV) / count($recommendedCLV)
    ];
  }

  private function calculateRecommendationSummary(array $recommendations): array
  {
    $contentRecommendations = [];
    $channelRecommendations = [];
    $offerRecommendations = [];

    foreach ($recommendations as $rec) {
      $contentRecommendations = array_merge($contentRecommendations, $rec['content_recommendations']);
      $channelRecommendations = array_merge($channelRecommendations, $rec['channel_recommendations']);
      $offerRecommendations = array_merge($offerRecommendations, $rec['offer_recommendations']);
    }

    return [
      'total_customers' => count($recommendations),
      'average_personalization_score' => array_sum(array_column($recommendations, 'personalization_score')) / count($recommendations),
      'top_content_recommendations' => array_count_values($contentRecommendations),
      'top_channel_recommendations' => array_count_values($channelRecommendations),
      'top_offer_recommendations' => array_count_values($offerRecommendations)
    ];
  }

  private function calculateSentimentSummary(array $sentimentResults): array
  {
    $sentimentLabels = array_count_values(array_column($sentimentResults, 'sentiment_label'));
    $avgConfidence = array_sum(array_column($sentimentResults, 'confidence')) / count($sentimentResults);
    $avgScore = array_sum(array_column($sentimentResults, 'sentiment_score')) / count($sentimentResults);

    return [
      'total_feedback' => count($sentimentResults),
      'positive' => $sentimentLabels['positive'] ?? 0,
      'negative' => $sentimentLabels['negative'] ?? 0,
      'neutral' => $sentimentLabels['neutral'] ?? 0,
      'average_confidence' => round($avgConfidence, 4),
      'average_sentiment_score' => round($avgScore, 4)
    ];
  }

  private function calculateModelAccuracy(array $predictions): float
  {
    // Simplified accuracy calculation based on confidence scores
    $highConfidencePredictions = array_filter($predictions, fn($p) => $p['confidence'] > 0.8);
    return count($highConfidencePredictions) / count($predictions);
  }

  private function calculateAverageConfidence(array $predictions): float
  {
    return array_sum(array_column($predictions, 'confidence')) / count($predictions);
  }

  private function calculateRiskDistribution(array $predictions): array
  {
    return array_count_values(array_column($predictions, 'risk_level'));
  }

  private function calculateFeatureImportance(array $predictions): array
  {
    $featureCounts = [];

    foreach ($predictions as $prediction) {
      foreach ($prediction['key_factors'] as $factor) {
        $featureName = $factor['factor'];
        $featureCounts[$featureName] = ($featureCounts[$featureName] ?? 0) + 1;
      }
    }

    arsort($featureCounts);
    return array_slice($featureCounts, 0, 5, true);
  }

  private function generateCustomerInsights(array $customers): array
  {
    $totalSpent = array_sum(array_column($customers, 'total_spent'));
    $orderCounts = array_column($customers, 'order_count');
    $avgOrderValue = $totalSpent / array_sum($orderCounts);

    return [
      'total_customers' => count($customers),
      'total_revenue' => $totalSpent,
      'average_order_value' => $avgOrderValue,
      'customer_segments' => $this->segmentCustomers($customers),
      'top_spenders' => $this->getTopSpenders($customers, 10)
    ];
  }

  private function generateCampaignInsights(array $campaigns): array
  {
    $activeCampaigns = array_filter($campaigns, fn($c) => $c['status'] === 'active');
    $completedCampaigns = array_filter($campaigns, fn($c) => $c['status'] === 'sent');

    return [
      'total_campaigns' => count($campaigns),
      'active_campaigns' => count($activeCampaigns),
      'completed_campaigns' => count($completedCampaigns),
      'campaign_types' => array_count_values(array_column($campaigns, 'type')),
      'top_performing_campaigns' => $this->getTopPerformingCampaigns($campaigns, 5)
    ];
  }

  private function generateActionItems(array $customers, array $campaigns): array
  {
    $actions = [];

    // High-risk customer actions
    $highRiskCustomers = array_filter($customers, function ($customer) {
      $lastOrder = $customer['last_order_date'];
      $daysSinceLastOrder = $lastOrder ? (time() - strtotime($lastOrder)) / (24 * 60 * 60) : 999;
      return $daysSinceLastOrder > 90 && $customer['total_spent'] > 100;
    });

    if (count($highRiskCustomers) > 0) {
      $actions[] = [
        'priority' => 'high',
        'action' => 'Re-engage high-risk customers',
        'count' => count($highRiskCustomers),
        'description' => 'Send personalized re-engagement campaigns to customers who haven\'t ordered in 90+ days'
      ];
    }

    // New customer actions
    $newCustomers = array_filter($customers, function ($customer) {
      $orderCount = $customer['order_count'] ?? 0;
      $firstOrder = $customer['first_order_date'] ?? null;
      $daysSinceFirstOrder = $firstOrder ? (time() - strtotime($firstOrder)) / (24 * 60 * 60) : 0;
      return $orderCount <= 1 && $daysSinceFirstOrder < 60;
    });

    if (count($newCustomers) > 0) {
      $actions[] = [
        'priority' => 'medium',
        'action' => 'Onboard new customers',
        'count' => count($newCustomers),
        'description' => 'Send welcome series and product recommendations to new customers'
      ];
    }

    return $actions;
  }

  private function segmentCustomers(array $customers): array
  {
    $segments = [
      'high_value' => 0,
      'loyal' => 0,
      'at_risk' => 0,
      'new' => 0,
      'inactive' => 0
    ];

    foreach ($customers as $customer) {
      $segment = $this->determineCustomerSegment($customer);
      $segments[$segment]++;
    }

    return $segments;
  }

  private function determineCustomerSegment(array $customer): string
  {
    $totalSpent = $customer['total_spent'] ?? 0;
    $orderCount = $customer['order_count'] ?? 0;
    $lastOrderDate = $customer['last_order_date'];
    $daysSinceLastOrder = $lastOrderDate ? (time() - strtotime($lastOrderDate)) / (24 * 60 * 60) : 999;

    if ($totalSpent > 1000 && $orderCount > 5) return 'high_value';
    if ($daysSinceLastOrder > 90 && $totalSpent > 100) return 'at_risk';
    if ($orderCount > 3 && $daysSinceLastOrder < 30) return 'loyal';
    if ($orderCount <= 1 && $daysSinceLastOrder < 60) return 'new';
    return 'inactive';
  }

  private function getTopSpenders(array $customers, int $limit): array
  {
    usort($customers, fn($a, $b) => ($b['total_spent'] ?? 0) - ($a['total_spent'] ?? 0));
    return array_slice($customers, 0, $limit);
  }

  private function getTopPerformingCampaigns(array $campaigns, int $limit): array
  {
    // Sort by engagement rate (simplified)
    usort($campaigns, fn($a, $b) => ($b['engagement_rate'] ?? 0) - ($a['engagement_rate'] ?? 0));
    return array_slice($campaigns, 0, $limit);
  }
}
