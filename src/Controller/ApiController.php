<?php

namespace CRMAIze\Controller;

use CRMAIze\Core\Application;
use CRMAIze\Core\Request;
use CRMAIze\Core\Response;
use CRMAIze\Repository\CustomerRepository;
use CRMAIze\Repository\CampaignRepository;

class ApiController
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

  public function getCustomers(Request $request): Response
  {
    $customers = $this->customerRepo->getAll();

    // Update segments and churn risk for each customer
    foreach ($customers as &$customer) {
      $churnRisk = $this->app->getAIService()->calculateChurnRisk($customer);
      $segment = $this->app->getAIService()->segmentCustomers([$customer])['at_risk'] ? 'at_risk' : 'loyal';

      $this->customerRepo->updateChurnRisk($customer['id'], $churnRisk);
      $this->customerRepo->updateSegment($customer['id'], $segment);

      $customer['churn_risk'] = $churnRisk;
      $customer['segment'] = $segment;
    }

    return Response::json($customers);
  }

  public function getSegments(Request $request): Response
  {
    $segments = $this->customerRepo->getSegmentCounts();
    return Response::json($segments);
  }

  public function getCampaigns(Request $request): Response
  {
    $campaigns = $this->campaignRepo->getAll();
    return Response::json($campaigns);
  }

  public function createCampaign(Request $request): Response
  {
    $data = $request->all();

    // Validate required fields
    if (empty($data['name']) || empty($data['type'])) {
      return Response::json(['error' => 'Name and type are required'], 400);
    }

    $campaignId = $this->campaignRepo->create($data);
    $campaign = $this->campaignRepo->getById($campaignId);

    return Response::json($campaign, 201);
  }

  public function getAnalytics(Request $request): Response
  {
    $totalCustomers = $this->customerRepo->getTotalCount();
    $totalRevenue = $this->customerRepo->getTotalRevenue();
    $churnRate = $this->customerRepo->getChurnRate();
    $segments = $this->customerRepo->getSegmentCounts();
    $recentCampaigns = $this->campaignRepo->getRecent(5);

    $analytics = [
      'totalCustomers' => $totalCustomers,
      'totalRevenue' => $totalRevenue,
      'churnRate' => $churnRate,
      'segments' => $segments,
      'recentCampaigns' => $recentCampaigns
    ];

    return Response::json($analytics);
  }
}
