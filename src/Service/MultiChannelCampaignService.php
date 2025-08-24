<?php

namespace CRMAIze\Service;

use CRMAIze\Repository\CampaignRepository;
use CRMAIze\Repository\CustomerRepository;

class MultiChannelCampaignService
{
  private CampaignRepository $campaignRepository;
  private CustomerRepository $customerRepository;
  private EmailService $emailService;

  public function __construct(CampaignRepository $campaignRepository, CustomerRepository $customerRepository, EmailService $emailService)
  {
    $this->campaignRepository = $campaignRepository;
    $this->customerRepository = $customerRepository;
    $this->emailService = $emailService;
  }

  /**
   * Create a multi-channel campaign
   */
  public function createMultiChannelCampaign(array $campaignData): array
  {
    $channels = $campaignData['channels'] ?? ['email'];
    $campaignId = $this->campaignRepository->create([
      'name' => $campaignData['name'],
      'subject' => $campaignData['subject'],
      'content' => $campaignData['content'],
      'type' => 'multi_channel',
      'channels' => json_encode($channels),
      'channel_config' => json_encode($campaignData['channel_config'] ?? []),
      'status' => 'draft',
      'created_at' => date('Y-m-d H:i:s')
    ]);

    return [
      'campaign_id' => $campaignId,
      'channels' => $channels,
      'status' => 'created'
    ];
  }

  /**
   * Send campaign across multiple channels
   */
  public function sendMultiChannelCampaign(int $campaignId, array $customerIds = []): array
  {
    $campaign = $this->campaignRepository->getById($campaignId);
    if (!$campaign) {
      throw new \Exception('Campaign not found');
    }

    $channels = json_decode($campaign['channels'], true);
    $channelConfig = json_decode($campaign['channel_config'], true);

    if (empty($customerIds)) {
      $customers = $this->customerRepository->getAll();
    } else {
      $customers = array_filter($this->customerRepository->getAll(), function ($customer) use ($customerIds) {
        return in_array($customer['id'], $customerIds);
      });
    }

    $results = [];

    foreach ($channels as $channel) {
      $results[$channel] = $this->sendToChannel($channel, $campaign, $customers, $channelConfig[$channel] ?? []);
    }

    // Update campaign status
    $this->campaignRepository->update($campaignId, ['status' => 'sent']);

    return [
      'campaign_id' => $campaignId,
      'total_customers' => count($customers),
      'channels_sent' => $channels,
      'results' => $results
    ];
  }

  /**
   * Send campaign to specific channel
   */
  private function sendToChannel(string $channel, array $campaign, array $customers, array $config): array
  {
    switch ($channel) {
      case 'email':
        return $this->sendEmailCampaign($campaign, $customers, $config);
      case 'sms':
        return $this->sendSMSCampaign($campaign, $customers, $config);
      case 'push':
        return $this->sendPushCampaign($campaign, $customers, $config);
      case 'social':
        return $this->sendSocialCampaign($campaign, $customers, $config);
      default:
        return ['error' => 'Unsupported channel: ' . $channel];
    }
  }

  /**
   * Send email campaign
   */
  private function sendEmailCampaign(array $campaign, array $customers, array $config): array
  {
    $sent = 0;
    $failed = 0;
    $errors = [];

    foreach ($customers as $customer) {
      try {
        $personalizedContent = $this->personalizeContent($campaign['content'], $customer);
        $personalizedSubject = $this->personalizeSubject($campaign['subject'], $customer);

        $result = $this->emailService->sendCampaignEmail(
          $customer['email'],
          $personalizedSubject,
          $personalizedContent,
          $config
        );

        if ($result) {
          $sent++;
        } else {
          $failed++;
          $errors[] = "Failed to send email to {$customer['email']}";
        }
      } catch (\Exception $e) {
        $failed++;
        $errors[] = "Error sending email to {$customer['email']}: " . $e->getMessage();
      }
    }

    return [
      'sent' => $sent,
      'failed' => $failed,
      'errors' => $errors
    ];
  }

  /**
   * Send SMS campaign
   */
  private function sendSMSCampaign(array $campaign, array $customers, array $config): array
  {
    $sent = 0;
    $failed = 0;
    $errors = [];

    // Filter customers with phone numbers
    $customersWithPhone = array_filter($customers, function ($customer) {
      return !empty($customer['phone']);
    });

    foreach ($customersWithPhone as $customer) {
      try {
        $smsContent = $this->createSMSContent($campaign['content'], $customer, $config);
        $result = $this->sendSMS($customer['phone'], $smsContent, $config);

        if ($result) {
          $sent++;
        } else {
          $failed++;
          $errors[] = "Failed to send SMS to {$customer['phone']}";
        }
      } catch (\Exception $e) {
        $failed++;
        $errors[] = "Error sending SMS to {$customer['phone']}: " . $e->getMessage();
      }
    }

    return [
      'sent' => $sent,
      'failed' => $failed,
      'errors' => $errors
    ];
  }

  /**
   * Send push notification campaign
   */
  private function sendPushCampaign(array $campaign, array $customers, array $config): array
  {
    $sent = 0;
    $failed = 0;
    $errors = [];

    // Filter customers with push tokens
    $customersWithPushToken = array_filter($customers, function ($customer) {
      return !empty($customer['push_token']);
    });

    foreach ($customersWithPushToken as $customer) {
      try {
        $pushContent = $this->createPushContent($campaign['content'], $customer, $config);
        $result = $this->sendPushNotification($customer['push_token'], $pushContent, $config);

        if ($result) {
          $sent++;
        } else {
          $failed++;
          $errors[] = "Failed to send push notification to customer {$customer['id']}";
        }
      } catch (\Exception $e) {
        $failed++;
        $errors[] = "Error sending push notification to customer {$customer['id']}: " . $e->getMessage();
      }
    }

    return [
      'sent' => $sent,
      'failed' => $failed,
      'errors' => $errors
    ];
  }

  /**
   * Send social media campaign
   */
  private function sendSocialCampaign(array $campaign, array $customers, array $config): array
  {
    $sent = 0;
    $failed = 0;
    $errors = [];

    // For social media, we typically post to platforms rather than individual customers
    $socialPlatforms = $config['platforms'] ?? ['facebook', 'twitter', 'instagram'];

    foreach ($socialPlatforms as $platform) {
      try {
        $socialContent = $this->createSocialContent($campaign['content'], $platform, $config);
        $result = $this->postToSocialMedia($platform, $socialContent, $config);

        if ($result) {
          $sent++;
        } else {
          $failed++;
          $errors[] = "Failed to post to {$platform}";
        }
      } catch (\Exception $e) {
        $failed++;
        $errors[] = "Error posting to {$platform}: " . $e->getMessage();
      }
    }

    return [
      'sent' => $sent,
      'failed' => $failed,
      'errors' => $errors
    ];
  }

  /**
   * Create SMS content from campaign content
   */
  private function createSMSContent(string $content, array $customer, array $config): string
  {
    // Convert HTML content to SMS-friendly text
    $textContent = strip_tags($content);
    $textContent = $this->personalizeContent($textContent, $customer);

    // Truncate to SMS length limit
    $maxLength = $config['max_length'] ?? 160;
    if (strlen($textContent) > $maxLength) {
      $textContent = substr($textContent, 0, $maxLength - 3) . '...';
    }

    return $textContent;
  }

  /**
   * Create push notification content
   */
  private function createPushContent(string $content, array $customer, array $config): array
  {
    $textContent = strip_tags($content);
    $textContent = $this->personalizeContent($textContent, $customer);

    // Truncate to push notification length limit
    $maxLength = $config['max_length'] ?? 100;
    if (strlen($textContent) > $maxLength) {
      $textContent = substr($textContent, 0, $maxLength - 3) . '...';
    }

    return [
      'title' => $config['title'] ?? 'CRMAIze',
      'body' => $textContent,
      'data' => [
        'campaign_id' => $config['campaign_id'] ?? null,
        'customer_id' => $customer['id'],
        'action_url' => $config['action_url'] ?? null
      ]
    ];
  }

  /**
   * Create social media content
   */
  private function createSocialContent(string $content, string $platform, array $config): array
  {
    $textContent = strip_tags($content);

    // Platform-specific content formatting
    switch ($platform) {
      case 'twitter':
        $maxLength = 280;
        if (strlen($textContent) > $maxLength) {
          $textContent = substr($textContent, 0, $maxLength - 3) . '...';
        }
        break;
      case 'facebook':
        $maxLength = 63206;
        break;
      case 'instagram':
        $maxLength = 2200;
        break;
      default:
        $maxLength = 1000;
    }

    if (strlen($textContent) > $maxLength) {
      $textContent = substr($textContent, 0, $maxLength - 3) . '...';
    }

    return [
      'text' => $textContent,
      'platform' => $platform,
      'media_url' => $config['media_url'] ?? null,
      'hashtags' => $config['hashtags'] ?? []
    ];
  }

  /**
   * Personalize content for customer
   */
  private function personalizeContent(string $content, array $customer): string
  {
    $replacements = [
      '{{customer_name}}' => $customer['name'] ?? 'Customer',
      '{{customer_email}}' => $customer['email'] ?? '',
      '{{customer_id}}' => $customer['id'] ?? '',
      '{{company_name}}' => 'CRMAIze',
      '{{unsubscribe_link}}' => $this->generateUnsubscribeLink($customer['id']),
      '{{preferences_link}}' => $this->generatePreferencesLink($customer['id'])
    ];

    return str_replace(array_keys($replacements), array_values($replacements), $content);
  }

  /**
   * Personalize subject line
   */
  private function personalizeSubject(string $subject, array $customer): string
  {
    $replacements = [
      '{{customer_name}}' => $customer['name'] ?? 'Customer',
      '{{company_name}}' => 'CRMAIze'
    ];

    return str_replace(array_keys($replacements), array_values($replacements), $subject);
  }

  /**
   * Send SMS (mock implementation)
   */
  private function sendSMS(string $phone, string $content, array $config): bool
  {
    // Mock SMS sending - in production, integrate with SMS service provider
    // Example: Twilio, AWS SNS, etc.

    $apiKey = $config['api_key'] ?? $_ENV['SMS_API_KEY'] ?? null;
    $apiSecret = $config['api_secret'] ?? $_ENV['SMS_API_SECRET'] ?? null;

    if (!$apiKey || !$apiSecret) {
      // Log that SMS service is not configured
      error_log("SMS service not configured for phone: {$phone}");
      return false;
    }

    // Mock successful SMS send
    return true;
  }

  /**
   * Send push notification (mock implementation)
   */
  private function sendPushNotification(string $token, array $content, array $config): bool
  {
    // Mock push notification sending - in production, integrate with FCM, APNS, etc.

    $apiKey = $config['api_key'] ?? $_ENV['PUSH_API_KEY'] ?? null;

    if (!$apiKey) {
      error_log("Push notification service not configured for token: {$token}");
      return false;
    }

    // Mock successful push notification send
    return true;
  }

  /**
   * Post to social media (mock implementation)
   */
  private function postToSocialMedia(string $platform, array $content, array $config): bool
  {
    // Mock social media posting - in production, integrate with platform APIs

    $apiKey = $config['api_key'] ?? $_ENV['SOCIAL_API_KEY'] ?? null;

    if (!$apiKey) {
      error_log("Social media service not configured for platform: {$platform}");
      return false;
    }

    // Mock successful social media post
    return true;
  }

  /**
   * Generate unsubscribe link
   */
  private function generateUnsubscribeLink(int $customerId): string
  {
    $token = $this->generateSecureToken($customerId);
    return "https://" . $_SERVER['HTTP_HOST'] . "/unsubscribe?token={$token}";
  }

  /**
   * Generate preferences link
   */
  private function generatePreferencesLink(int $customerId): string
  {
    $token = $this->generateSecureToken($customerId);
    return "https://" . $_SERVER['HTTP_HOST'] . "/preferences?token={$token}";
  }

  /**
   * Generate secure token for customer
   */
  private function generateSecureToken(int $customerId): string
  {
    $data = $customerId . '|' . time() . '|' . $_ENV['APP_KEY'] ?? 'default_key';
    return hash('sha256', $data);
  }

  /**
   * Get campaign analytics across channels
   */
  public function getMultiChannelAnalytics(int $campaignId): array
  {
    $campaign = $this->campaignRepository->getById($campaignId);
    if (!$campaign) {
      throw new \Exception('Campaign not found');
    }

    $channels = json_decode($campaign['channels'], true);
    $analytics = [];

    foreach ($channels as $channel) {
      $analytics[$channel] = $this->getChannelAnalytics($campaignId, $channel);
    }

    return [
      'campaign_id' => $campaignId,
      'campaign_name' => $campaign['name'],
      'channels' => $channels,
      'analytics' => $analytics,
      'total_reach' => array_sum(array_column($analytics, 'reach')),
      'total_engagement' => array_sum(array_column($analytics, 'engagement'))
    ];
  }

  /**
   * Get analytics for specific channel
   */
  private function getChannelAnalytics(int $campaignId, string $channel): array
  {
    // Mock analytics - in production, fetch from actual service providers
    switch ($channel) {
      case 'email':
        return [
          'reach' => rand(100, 1000),
          'opens' => rand(50, 500),
          'clicks' => rand(10, 100),
          'bounces' => rand(5, 50),
          'engagement' => rand(5, 15)
        ];
      case 'sms':
        return [
          'reach' => rand(50, 500),
          'delivered' => rand(45, 450),
          'clicks' => rand(5, 50),
          'engagement' => rand(10, 25)
        ];
      case 'push':
        return [
          'reach' => rand(30, 300),
          'delivered' => rand(25, 250),
          'clicks' => rand(3, 30),
          'engagement' => rand(8, 20)
        ];
      case 'social':
        return [
          'reach' => rand(200, 2000),
          'impressions' => rand(500, 5000),
          'clicks' => rand(20, 200),
          'shares' => rand(5, 50),
          'engagement' => rand(3, 12)
        ];
      default:
        return [
          'reach' => 0,
          'engagement' => 0
        ];
    }
  }

  /**
   * Get channel performance comparison
   */
  public function getChannelPerformanceComparison(array $campaignIds): array
  {
    $comparison = [];

    foreach ($campaignIds as $campaignId) {
      $analytics = $this->getMultiChannelAnalytics($campaignId);
      $comparison[$campaignId] = $analytics;
    }

    return $comparison;
  }

  /**
   * Get optimal channel mix recommendations
   */
  public function getOptimalChannelMix(array $customerSegment, array $campaignType): array
  {
    // Mock channel mix recommendations based on customer segment and campaign type
    $recommendations = [
      'high_value_customers' => [
        'email' => 60,
        'sms' => 25,
        'push' => 10,
        'social' => 5
      ],
      'new_customers' => [
        'email' => 40,
        'sms' => 30,
        'push' => 20,
        'social' => 10
      ],
      'at_risk_customers' => [
        'email' => 30,
        'sms' => 50,
        'push' => 15,
        'social' => 5
      ]
    ];

    $segment = $customerSegment['type'] ?? 'general';
    $mix = $recommendations[$segment] ?? $recommendations['high_value_customers'];

    return [
      'recommended_mix' => $mix,
      'reasoning' => "Based on {$segment} customer behavior patterns",
      'expected_engagement' => rand(15, 35),
      'cost_estimate' => $this->calculateChannelCosts($mix, $customerSegment['count'] ?? 1000)
    ];
  }

  /**
   * Calculate channel costs
   */
  private function calculateChannelCosts(array $mix, int $customerCount): array
  {
    $costs = [
      'email' => 0.001, // $0.001 per email
      'sms' => 0.05,    // $0.05 per SMS
      'push' => 0.0001, // $0.0001 per push
      'social' => 0.01  // $0.01 per social post
    ];

    $totalCost = 0;
    $breakdown = [];

    foreach ($mix as $channel => $percentage) {
      $channelCustomers = ($customerCount * $percentage) / 100;
      $channelCost = $channelCustomers * $costs[$channel];
      $totalCost += $channelCost;
      $breakdown[$channel] = [
        'customers' => $channelCustomers,
        'cost' => $channelCost
      ];
    }

    return [
      'total_cost' => $totalCost,
      'breakdown' => $breakdown,
      'cost_per_customer' => $totalCost / $customerCount
    ];
  }
}
