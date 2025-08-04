<?php

namespace CRMAIze\Controller;

use CRMAIze\Core\Application;
use CRMAIze\Core\Request;
use CRMAIze\Core\Response;
use CRMAIze\Repository\CustomerRepository;
use CRMAIze\Repository\CampaignRepository;

class CampaignController
{
  private $app;
  private $customerRepo;
  private $campaignRepo;

  public function __construct(Application $app)
  {
    $this->app = $app;
    $this->customerRepo = new CustomerRepository($app->getDatabase());
    $this->campaignRepo = new CampaignRepository($app->getDatabase());
  }

  public function index(Request $request): Response
  {
    // Require authentication
    $this->app->getAuthService()->requireAuth();

    $campaigns = $this->campaignRepo->getAll();
    $html = $this->app->getTwig()->render('campaigns.twig', [
      'campaigns' => $campaigns,
      'user' => $this->app->getAuthService()->getCurrentUser(),
      'current_page' => 'campaigns'
    ]);
    return new Response($html);
  }

  public function create(Request $request): Response
  {
    // Require authentication
    $this->app->getAuthService()->requireAuth();

    $segments = $this->customerRepo->getSegmentCounts();
    $html = $this->app->getTwig()->render('campaign_form.twig', [
      'segments' => $segments,
      'user' => $this->app->getAuthService()->getCurrentUser(),
      'current_page' => 'create_campaign'
    ]);
    return new Response($html);
  }

  public function store(Request $request): Response
  {
    $data = $request->all();

    // Validate required fields
    if (empty($data['name']) || empty($data['type'])) {
      return new Response('Name and type are required', 400);
    }

    // Generate AI suggestions if not provided
    if (empty($data['subject_line'])) {
      $sampleCustomer = $this->customerRepo->getAll()[0] ?? [];
      $data['subject_line'] = $this->app->getAIService()->generateSubjectLine($data['type'], $sampleCustomer);
    }

    if (empty($data['discount_percent']) && $data['type'] === 'discount') {
      $sampleCustomer = $this->customerRepo->getAll()[0] ?? [];
      $data['discount_percent'] = $this->app->getAIService()->suggestDiscount($sampleCustomer);
    }

    $campaignId = $this->campaignRepo->create($data);

    // Handle A/B testing variants
    if (!empty($data['enable_ab_test']) && $data['enable_ab_test'] === '1') {
      $variantCount = (int) ($data['variant_count'] ?? 2);
      $sampleCustomer = $this->customerRepo->getAll()[0] ?? [];
      $variants = $this->app->getAIService()->generateABTestVariants($data['type'], $sampleCustomer, $variantCount);

      foreach ($variants as $variant) {
        $variant['campaign_id'] = $campaignId;
        $this->campaignRepo->createVariant($variant);
      }
    }

    // Handle scheduling
    if (!empty($data['schedule_type']) && $data['schedule_type'] !== 'immediate') {
      $scheduler = $this->app->getCampaignScheduler();

      if ($data['schedule_type'] === 'optimal') {
        $sampleCustomer = $this->customerRepo->getAll()[0] ?? [];
        $optimalTime = $this->app->getAIService()->suggestOptimalSendTime($sampleCustomer);
        $scheduler->scheduleCampaign($campaignId, 'scheduled', $optimalTime, $data['timezone'] ?? 'UTC');
      } elseif ($data['schedule_type'] === 'scheduled' && !empty($data['scheduled_at'])) {
        $scheduler->scheduleCampaign($campaignId, 'scheduled', $data['scheduled_at'], $data['timezone'] ?? 'UTC');
      }
    }

    // Redirect to campaign list
    header('Location: /campaigns');
    exit;
  }

  public function show(Request $request, string $id): Response
  {
    $campaign = $this->campaignRepo->getById((int) $id);

    if (!$campaign) {
      return new Response('Campaign not found', 404);
    }

    // Get target customers for preview
    $customers = [];
    if ($campaign['target_segment']) {
      $customers = $this->customerRepo->getBySegment($campaign['target_segment']);
    }

    $data = [
      'campaign' => $campaign,
      'customers' => $customers
    ];

    $html = $this->app->getTwig()->render('campaign_show.twig', $data);
    return new Response($html);
  }

  public function send(Request $request, string $id): Response
  {
    $campaign = $this->campaignRepo->getById((int) $id);

    if (!$campaign) {
      return new Response('Campaign not found', 404);
    }

    // Check if email service is configured
    $emailService = $this->app->getEmailService();
    if (!$emailService->isConfigured()) {
      return Response::json([
        'success' => false,
        'message' => 'Email service not configured. Please configure SMTP settings in .env file.'
      ]);
    }

    // Get target customers
    $customers = [];
    if ($campaign['target_segment']) {
      $customers = $this->customerRepo->getBySegment($campaign['target_segment']);
    }

    // Send real emails
    $sentCount = 0;
    $failedCount = 0;
    $errors = [];

    foreach ($customers as $customer) {
      try {
        // Send real email
        $success = $emailService->sendCampaignEmail(
          $customer['email'],
          $campaign['subject_line'],
          $campaign['email_content'],
          $customer
        );

        if ($success) {
          $sentCount++;
          // Log the campaign send
          $this->logCampaignSend((int) $id, $customer['id']);
        } else {
          $failedCount++;
          $errors[] = "Failed to send to: " . $customer['email'];
        }
      } catch (\Exception $e) {
        $failedCount++;
        $errors[] = "Error sending to " . $customer['email'] . ": " . $e->getMessage();
      }
    }

    // Update campaign status and sent count
    $this->campaignRepo->updateStatus((int) $id, 'sent');
    for ($i = 0; $i < $sentCount; $i++) {
      $this->campaignRepo->incrementSentCount((int) $id);
    }

    return Response::json([
      'success' => true,
      'sent_count' => $sentCount,
      'failed_count' => $failedCount,
      'message' => "Campaign completed. Sent: $sentCount, Failed: $failedCount",
      'errors' => $errors
    ]);
  }

  private function logCampaignSend(int $campaignId, int $customerId): void
  {
    $this->app->getDatabase()->execute("
            INSERT INTO campaign_logs (campaign_id, customer_id, status)
            VALUES (?, ?, 'sent')
        ", [$campaignId, $customerId]);
  }
}
