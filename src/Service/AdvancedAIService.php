<?php

namespace CRMAIze\Service;

use CRMAIze\Repository\CustomerRepository;
use CRMAIze\Repository\CampaignRepository;

class AdvancedAIService
{
  private CustomerRepository $customerRepository;
  private CampaignRepository $campaignRepository;

  public function __construct(CustomerRepository $customerRepository, CampaignRepository $campaignRepository)
  {
    $this->customerRepository = $customerRepository;
    $this->campaignRepository = $campaignRepository;
  }

  /**
   * Advanced Predictive Churn Modeling using multiple algorithms
   */
  public function predictChurnWithAdvancedML(array $customerData): array
  {
    $predictions = [];

    foreach ($customerData as $customer) {
      $features = $this->extractChurnFeatures($customer);

      // Multiple ML models for ensemble prediction
      $rfPrediction = $this->randomForestPrediction($features);
      $gbmPrediction = $this->gradientBoostingPrediction($features);
      $nnPrediction = $this->neuralNetworkPrediction($features);

      // Ensemble average with confidence
      $ensemblePrediction = ($rfPrediction + $gbmPrediction + $nnPrediction) / 3;
      $confidence = $this->calculatePredictionConfidence($features);

      $predictions[] = [
        'customer_id' => $customer['id'],
        'churn_probability' => round($ensemblePrediction, 4),
        'confidence' => round($confidence, 4),
        'risk_level' => $this->classifyRiskLevel($ensemblePrediction),
        'model_breakdown' => [
          'random_forest' => round($rfPrediction, 4),
          'gradient_boosting' => round($gbmPrediction, 4),
          'neural_network' => round($nnPrediction, 4)
        ],
        'key_factors' => $this->identifyKeyFactors($features)
      ];
    }

    return $predictions;
  }

  /**
   * Calculate Customer Lifetime Value (CLV)
   */
  public function calculateCustomerLifetimeValue(array $customerData): array
  {
    $clvResults = [];

    foreach ($customerData as $customer) {
      $features = $this->extractCLVFeatures($customer);

      // CLV calculation using multiple methods
      $historicalCLV = $this->calculateHistoricalCLV($customer);
      $predictiveCLV = $this->calculatePredictiveCLV($features);
      $segmentedCLV = $this->calculateSegmentedCLV($customer);

      $clvResults[] = [
        'customer_id' => $customer['id'],
        'historical_clv' => round($historicalCLV, 2),
        'predictive_clv' => round($predictiveCLV, 2),
        'segmented_clv' => round($segmentedCLV, 2),
        'recommended_clv' => round(($historicalCLV + $predictiveCLV + $segmentedCLV) / 3, 2),
        'confidence_interval' => $this->calculateCLVConfidence($features),
        'growth_potential' => $this->assessGrowthPotential($customer),
        'retention_value' => $this->calculateRetentionValue($customer)
      ];
    }

    return $clvResults;
  }

  /**
   * Personalized Content Recommendations
   */
  public function generatePersonalizedRecommendations(array $customerData, array $campaignHistory): array
  {
    $recommendations = [];

    foreach ($customerData as $customer) {
      $customerProfile = $this->buildCustomerProfile($customer);
      $behavioralPatterns = $this->analyzeBehavioralPatterns($customer, $campaignHistory);

      $recommendations[] = [
        'customer_id' => $customer['id'],
        'content_recommendations' => $this->recommendContent($customerProfile, $behavioralPatterns),
        'timing_recommendations' => $this->recommendOptimalTiming($customerProfile, $behavioralPatterns),
        'channel_recommendations' => $this->recommendChannels($customerProfile, $behavioralPatterns),
        'offer_recommendations' => $this->recommendOffers($customerProfile, $behavioralPatterns),
        'personalization_score' => $this->calculatePersonalizationScore($customerProfile)
      ];
    }

    return $recommendations;
  }

  /**
   * Sentiment Analysis for Customer Feedback
   */
  public function analyzeCustomerSentiment(array $feedbackData): array
  {
    $sentimentResults = [];

    foreach ($feedbackData as $feedback) {
      $text = $feedback['text'] ?? '';
      $sentiment = $this->performSentimentAnalysis($text);

      $sentimentResults[] = [
        'feedback_id' => $feedback['id'],
        'customer_id' => $feedback['customer_id'],
        'sentiment_score' => $sentiment['score'],
        'sentiment_label' => $sentiment['label'],
        'confidence' => $sentiment['confidence'],
        'key_topics' => $this->extractKeyTopics($text),
        'emotion_analysis' => $this->analyzeEmotions($text),
        'action_items' => $this->generateActionItems($sentiment, $feedback)
      ];
    }

    return $sentimentResults;
  }

  // Private helper methods for ML algorithms

  private function extractChurnFeatures(array $customer): array
  {
    return [
      'recency' => $this->calculateRecency($customer['last_order_date']),
      'frequency' => $customer['order_count'] ?? 0,
      'monetary' => $customer['total_spent'] ?? 0,
      'avg_order_value' => $this->calculateAverageOrderValue($customer),
      'days_since_first_order' => $this->calculateDaysSinceFirstOrder($customer),
      'order_frequency' => $this->calculateOrderFrequency($customer),
      'seasonal_pattern' => $this->analyzeSeasonalPattern($customer),
      'product_category_preference' => $this->analyzeProductPreferences($customer),
      'payment_method' => $this->encodePaymentMethod($customer['payment_method'] ?? ''),
      'customer_service_interactions' => $customer['support_tickets'] ?? 0,
      'email_engagement' => $this->calculateEmailEngagement($customer),
      'website_activity' => $customer['page_views'] ?? 0
    ];
  }

  private function randomForestPrediction(array $features): float
  {
    // Simplified Random Forest implementation
    $weights = [
      'recency' => 0.25,
      'frequency' => 0.20,
      'monetary' => 0.15,
      'avg_order_value' => 0.10,
      'order_frequency' => 0.15,
      'email_engagement' => 0.10,
      'customer_service_interactions' => 0.05
    ];

    $score = 0;
    foreach ($weights as $feature => $weight) {
      if (isset($features[$feature])) {
        $score += $features[$feature] * $weight;
      }
    }

    return min(1.0, max(0.0, $score));
  }

  private function gradientBoostingPrediction(array $features): float
  {
    // Simplified Gradient Boosting implementation
    $baseScore = 0.5;
    $learningRate = 0.1;

    $prediction = $baseScore;
    foreach ($features as $feature => $value) {
      $prediction += $learningRate * $this->calculateGradient($feature, $value);
    }

    return min(1.0, max(0.0, $prediction));
  }

  private function neuralNetworkPrediction(array $features): float
  {
    // Simplified Neural Network implementation
    $hiddenLayer1 = [];
    $hiddenLayer2 = [];

    // First hidden layer
    foreach ($features as $feature => $value) {
      $hiddenLayer1[] = $this->sigmoid($value * 0.5 + 0.1);
    }

    // Second hidden layer
    foreach ($hiddenLayer1 as $value) {
      $hiddenLayer2[] = $this->sigmoid($value * 0.3 + 0.2);
    }

    // Output layer
    $output = array_sum($hiddenLayer2) / count($hiddenLayer2);
    return $this->sigmoid($output);
  }

  private function sigmoid(float $x): float
  {
    return 1 / (1 + exp(-$x));
  }

  private function calculatePredictionConfidence(array $features): float
  {
    // Calculate confidence based on feature quality and consistency
    $featureCount = count($features);
    $nonZeroFeatures = count(array_filter($features, function ($value) {
      return $value > 0;
    }));

    return min(1.0, $nonZeroFeatures / $featureCount);
  }

  private function classifyRiskLevel(float $probability): string
  {
    if ($probability < 0.3) return 'low';
    if ($probability < 0.7) return 'medium';
    return 'high';
  }

  private function identifyKeyFactors(array $features): array
  {
    $factors = [];
    arsort($features);

    $topFactors = array_slice($features, 0, 3, true);
    foreach ($topFactors as $factor => $value) {
      $factors[] = [
        'factor' => $factor,
        'impact' => round($value, 4),
        'description' => $this->getFactorDescription($factor)
      ];
    }

    return $factors;
  }

  private function getFactorDescription(string $factor): string
  {
    $descriptions = [
      'recency' => 'Time since last purchase',
      'frequency' => 'Number of orders',
      'monetary' => 'Total amount spent',
      'avg_order_value' => 'Average order value',
      'order_frequency' => 'Purchase frequency',
      'email_engagement' => 'Email interaction rate',
      'customer_service_interactions' => 'Support ticket count'
    ];

    return $descriptions[$factor] ?? $factor;
  }

  private function extractCLVFeatures(array $customer): array
  {
    return [
      'total_spent' => $customer['total_spent'] ?? 0,
      'order_count' => $customer['order_count'] ?? 0,
      'avg_order_value' => $this->calculateAverageOrderValue($customer),
      'customer_age' => $this->calculateCustomerAge($customer),
      'purchase_frequency' => $this->calculatePurchaseFrequency($customer),
      'retention_rate' => $this->calculateRetentionRate($customer),
      'upsell_potential' => $this->assessUpsellPotential($customer)
    ];
  }

  private function calculateHistoricalCLV(array $customer): float
  {
    $totalSpent = $customer['total_spent'] ?? 0;
    $customerAge = $this->calculateCustomerAge($customer);

    if ($customerAge > 0) {
      return $totalSpent / $customerAge * 12; // Annualized CLV
    }

    return $totalSpent;
  }

  private function calculatePredictiveCLV(array $features): float
  {
    $baseCLV = $features['total_spent'] ?? 0;
    $frequencyMultiplier = 1 + ($features['purchase_frequency'] ?? 0) * 0.1;
    $retentionMultiplier = 1 + ($features['retention_rate'] ?? 0) * 0.2;
    $upsellMultiplier = 1 + ($features['upsell_potential'] ?? 0) * 0.15;

    return $baseCLV * $frequencyMultiplier * $retentionMultiplier * $upsellMultiplier;
  }

  private function calculateSegmentedCLV(array $customer): float
  {
    $segment = $this->determineCustomerSegment($customer);
    $segmentMultipliers = [
      'high_value' => 2.5,
      'loyal' => 1.8,
      'at_risk' => 0.6,
      'new' => 1.2,
      'inactive' => 0.3
    ];

    $baseCLV = $this->calculateHistoricalCLV($customer);
    $multiplier = $segmentMultipliers[$segment] ?? 1.0;

    return $baseCLV * $multiplier;
  }

  private function buildCustomerProfile(array $customer): array
  {
    return [
      'demographics' => $this->extractDemographics($customer),
      'behavioral' => $this->extractBehavioralData($customer),
      'preferences' => $this->extractPreferences($customer),
      'engagement' => $this->extractEngagementData($customer)
    ];
  }

  private function recommendContent(array $profile, array $patterns): array
  {
    $recommendations = [];

    // Content type recommendations
    if ($patterns['email_open_rate'] > 0.7) {
      $recommendations[] = 'Detailed product guides';
    }
    if ($patterns['video_watch_time'] > 0) {
      $recommendations[] = 'Product demonstration videos';
    }
    if ($patterns['social_engagement'] > 0) {
      $recommendations[] = 'Social media content';
    }

    return $recommendations;
  }

  private function recommendOptimalTiming(array $profile, array $patterns): array
  {
    return [
      'best_days' => $this->analyzeBestDays($patterns),
      'best_hours' => $this->analyzeBestHours($patterns),
      'frequency' => $this->recommendFrequency($patterns),
      'seasonal_timing' => $this->analyzeSeasonalTiming($patterns)
    ];
  }

  private function performSentimentAnalysis(string $text): array
  {
    // Simplified sentiment analysis
    $positiveWords = ['good', 'great', 'excellent', 'amazing', 'love', 'happy', 'satisfied'];
    $negativeWords = ['bad', 'terrible', 'awful', 'hate', 'disappointed', 'angry', 'frustrated'];

    $words = explode(' ', strtolower($text));
    $positiveCount = count(array_intersect($words, $positiveWords));
    $negativeCount = count(array_intersect($words, $negativeWords));

    $totalWords = count($words);
    if ($totalWords === 0) {
      return ['score' => 0.5, 'label' => 'neutral', 'confidence' => 0.5];
    }

    $score = ($positiveCount - $negativeCount) / $totalWords;
    $normalizedScore = ($score + 1) / 2; // Normalize to 0-1

    $label = $normalizedScore > 0.6 ? 'positive' : ($normalizedScore < 0.4 ? 'negative' : 'neutral');
    $confidence = min(1.0, ($positiveCount + $negativeCount) / $totalWords);

    return [
      'score' => round($normalizedScore, 4),
      'label' => $label,
      'confidence' => round($confidence, 4)
    ];
  }

  // Additional helper methods would be implemented here...
  private function calculateRecency($lastOrderDate): float
  {
    if (!$lastOrderDate) return 999;
    return (time() - strtotime($lastOrderDate)) / (24 * 60 * 60);
  }

  private function calculateAverageOrderValue(array $customer): float
  {
    $totalSpent = $customer['total_spent'] ?? 0;
    $orderCount = $customer['order_count'] ?? 1;
    return $totalSpent / $orderCount;
  }

  private function calculateDaysSinceFirstOrder(array $customer): float
  {
    $firstOrder = $customer['first_order_date'] ?? null;
    if (!$firstOrder) return 0;
    return (time() - strtotime($firstOrder)) / (24 * 60 * 60);
  }

  private function calculateOrderFrequency(array $customer): float
  {
    $orderCount = $customer['order_count'] ?? 0;
    $customerAge = $this->calculateCustomerAge($customer);
    return $customerAge > 0 ? $orderCount / $customerAge : 0;
  }

  private function calculateCustomerAge(array $customer): float
  {
    $firstOrder = $customer['first_order_date'] ?? null;
    if (!$firstOrder) return 0;
    return (time() - strtotime($firstOrder)) / (24 * 60 * 60 * 365);
  }

  private function calculatePurchaseFrequency(array $customer): float
  {
    return $this->calculateOrderFrequency($customer);
  }

  private function calculateRetentionRate(array $customer): float
  {
    // Simplified retention rate calculation
    $orderCount = $customer['order_count'] ?? 0;
    return min(1.0, $orderCount / 10); // Assume 10 orders = 100% retention
  }

  private function assessUpsellPotential(array $customer): float
  {
    $avgOrderValue = $this->calculateAverageOrderValue($customer);
    $orderCount = $customer['order_count'] ?? 0;

    // Higher average order value and more orders indicate upsell potential
    return min(1.0, ($avgOrderValue / 100 + $orderCount / 5) / 2);
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

  private function calculateGradient(string $feature, float $value): float
  {
    // Simplified gradient calculation
    return $value * 0.1;
  }

  private function analyzeSeasonalPattern(array $customer): float
  {
    // Simplified seasonal analysis
    return 0.5; // Neutral seasonal pattern
  }

  private function analyzeProductPreferences(array $customer): float
  {
    // Simplified product preference analysis
    return 0.5;
  }

  private function encodePaymentMethod(string $method): float
  {
    $encodings = [
      'credit_card' => 1.0,
      'paypal' => 0.8,
      'bank_transfer' => 0.6,
      'cash' => 0.4
    ];
    return $encodings[$method] ?? 0.5;
  }

  private function calculateEmailEngagement(array $customer): float
  {
    $opens = $customer['email_opens'] ?? 0;
    $clicks = $customer['email_clicks'] ?? 0;
    $sent = $customer['emails_sent'] ?? 1;

    return ($opens + $clicks) / ($sent * 2);
  }

  private function extractDemographics(array $customer): array
  {
    return [
      'age_group' => $customer['age_group'] ?? 'unknown',
      'location' => $customer['location'] ?? 'unknown',
      'gender' => $customer['gender'] ?? 'unknown'
    ];
  }

  private function extractBehavioralData(array $customer): array
  {
    return [
      'purchase_history' => $customer['order_count'] ?? 0,
      'total_spent' => $customer['total_spent'] ?? 0,
      'avg_order_value' => $this->calculateAverageOrderValue($customer),
      'last_purchase' => $customer['last_order_date'] ?? null
    ];
  }

  private function extractPreferences(array $customer): array
  {
    return [
      'product_categories' => $customer['preferred_categories'] ?? [],
      'price_range' => $customer['price_preference'] ?? 'medium',
      'communication_preference' => $customer['comm_preference'] ?? 'email'
    ];
  }

  private function extractEngagementData(array $customer): array
  {
    return [
      'email_opens' => $customer['email_opens'] ?? 0,
      'email_clicks' => $customer['email_clicks'] ?? 0,
      'website_visits' => $customer['website_visits'] ?? 0,
      'social_engagement' => $customer['social_engagement'] ?? 0
    ];
  }

  private function analyzeBehavioralPatterns(array $customer, array $campaignHistory): array
  {
    return [
      'email_open_rate' => $this->calculateEmailOpenRate($customer),
      'video_watch_time' => $customer['video_watch_time'] ?? 0,
      'social_engagement' => $customer['social_engagement'] ?? 0,
      'purchase_timing' => $this->analyzePurchaseTiming($customer)
    ];
  }

  private function calculateEmailOpenRate(array $customer): float
  {
    $opens = $customer['email_opens'] ?? 0;
    $sent = $customer['emails_sent'] ?? 1;
    return $opens / $sent;
  }

  private function analyzePurchaseTiming(array $customer): array
  {
    return [
      'day_of_week' => $customer['preferred_day'] ?? 'monday',
      'time_of_day' => $customer['preferred_time'] ?? 'afternoon',
      'seasonal_preference' => $customer['seasonal_preference'] ?? 'none'
    ];
  }

  private function recommendChannels(array $profile, array $patterns): array
  {
    $channels = [];

    if ($patterns['email_open_rate'] > 0.5) {
      $channels[] = 'email';
    }
    if ($patterns['social_engagement'] > 0) {
      $channels[] = 'social_media';
    }
    if ($patterns['video_watch_time'] > 0) {
      $channels[] = 'video';
    }

    return $channels;
  }

  private function recommendOffers(array $profile, array $patterns): array
  {
    $offers = [];

    if ($profile['behavioral']['total_spent'] > 500) {
      $offers[] = 'VIP discount';
    }
    if ($patterns['email_open_rate'] < 0.3) {
      $offers[] = 'Re-engagement offer';
    }
    if ($profile['behavioral']['avg_order_value'] < 50) {
      $offers[] = 'Free shipping';
    }

    return $offers;
  }

  private function calculatePersonalizationScore(array $profile): float
  {
    $score = 0;
    $maxScore = 0;

    // Demographics score
    if (!empty($profile['demographics']['age_group'])) $score += 0.2;
    if (!empty($profile['demographics']['location'])) $score += 0.2;
    $maxScore += 0.4;

    // Behavioral score
    if ($profile['behavioral']['purchase_history'] > 0) $score += 0.3;
    if ($profile['behavioral']['total_spent'] > 0) $score += 0.3;
    $maxScore += 0.6;

    return $maxScore > 0 ? $score / $maxScore : 0;
  }

  private function analyzeBestDays(array $patterns): array
  {
    return ['monday', 'wednesday', 'friday'];
  }

  private function analyzeBestHours(array $patterns): array
  {
    return ['10:00', '14:00', '18:00'];
  }

  private function recommendFrequency(array $patterns): string
  {
    if ($patterns['email_open_rate'] > 0.7) return 'weekly';
    if ($patterns['email_open_rate'] > 0.4) return 'bi-weekly';
    return 'monthly';
  }

  private function analyzeSeasonalTiming(array $patterns): array
  {
    return [
      'spring' => 'high',
      'summer' => 'medium',
      'fall' => 'high',
      'winter' => 'medium'
    ];
  }

  private function extractKeyTopics(string $text): array
  {
    $topics = [];
    $words = explode(' ', strtolower($text));

    $topicKeywords = [
      'product' => ['product', 'item', 'goods'],
      'service' => ['service', 'support', 'help'],
      'price' => ['price', 'cost', 'expensive', 'cheap'],
      'delivery' => ['delivery', 'shipping', 'arrive'],
      'quality' => ['quality', 'good', 'bad', 'excellent']
    ];

    foreach ($topicKeywords as $topic => $keywords) {
      if (!empty(array_intersect($words, $keywords))) {
        $topics[] = $topic;
      }
    }

    return $topics;
  }

  private function analyzeEmotions(string $text): array
  {
    $emotions = [];
    $words = explode(' ', strtolower($text));

    $emotionKeywords = [
      'joy' => ['happy', 'excited', 'great', 'amazing'],
      'anger' => ['angry', 'frustrated', 'mad', 'upset'],
      'sadness' => ['sad', 'disappointed', 'unhappy'],
      'fear' => ['worried', 'concerned', 'anxious'],
      'surprise' => ['surprised', 'shocked', 'unexpected']
    ];

    foreach ($emotionKeywords as $emotion => $keywords) {
      if (!empty(array_intersect($words, $keywords))) {
        $emotions[] = $emotion;
      }
    }

    return $emotions;
  }

  private function generateActionItems(array $sentiment, array $feedback): array
  {
    $actions = [];

    if ($sentiment['label'] === 'negative') {
      $actions[] = 'Follow up with customer service';
      $actions[] = 'Send apology email';
      $actions[] = 'Offer compensation';
    } elseif ($sentiment['label'] === 'positive') {
      $actions[] = 'Send thank you message';
      $actions[] = 'Request testimonial';
      $actions[] = 'Offer referral bonus';
    }

    return $actions;
  }

  private function calculateCLVConfidence(array $features): array
  {
    $confidence = 0.7; // Base confidence
    $margin = 0.1; // 10% margin of error

    return [
      'confidence' => $confidence,
      'margin_of_error' => $margin,
      'lower_bound' => 1 - $margin,
      'upper_bound' => 1 + $margin
    ];
  }

  private function assessGrowthPotential(array $customer): float
  {
    $orderCount = $customer['order_count'] ?? 0;
    $totalSpent = $customer['total_spent'] ?? 0;
    $customerAge = $this->calculateCustomerAge($customer);

    if ($customerAge === 0) return 1.0; // New customer has high potential

    $currentValue = $totalSpent / $customerAge;
    $potentialValue = $currentValue * 1.5; // Assume 50% growth potential

    return min(1.0, $potentialValue / 1000); // Normalize to 0-1
  }

  private function calculateRetentionValue(array $customer): float
  {
    $totalSpent = $customer['total_spent'] ?? 0;
    $orderCount = $customer['order_count'] ?? 0;
    $customerAge = $this->calculateCustomerAge($customer);

    if ($customerAge === 0) return $totalSpent;

    $annualValue = $totalSpent / $customerAge;
    $retentionYears = 3; // Assume 3 years retention

    return $annualValue * $retentionYears;
  }
}
