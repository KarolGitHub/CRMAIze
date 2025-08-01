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
    $campaigns = $this->campaignRepo->getAll();
    $html = $this->app->getTwig()->render('campaigns.twig', ['campaigns' => $campaigns]);
    return new Response($html);
  }

  public function create(Request $request): Response
  {
    $segments = $this->customerRepo->getSegmentCounts();
    $html = $this->app->getTwig()->render('campaign_form.twig', ['segments' => $segments]);
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

    // Get target customers
    $customers = [];
    if ($campaign['target_segment']) {
      $customers = $this->customerRepo->getBySegment($campaign['target_segment']);
    }

    // Simulate sending emails
    $sentCount = 0;
    foreach ($customers as $customer) {
      // In a real application, this would send actual emails
      $sentCount++;

      // Log the campaign send
      $this->logCampaignSend((int) $id, $customer['id']);
    }

    // Update campaign status and sent count
    $this->campaignRepo->updateStatus((int) $id, 'sent');
    for ($i = 0; $i < $sentCount; $i++) {
      $this->campaignRepo->incrementSentCount((int) $id);
    }

    return Response::json([
      'success' => true,
      'sent_count' => $sentCount,
      'message' => "Campaign sent to {$sentCount} customers"
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
