<?php

namespace CRMAIze\Controller;

use CRMAIze\Core\Application;
use CRMAIze\Core\Request;
use CRMAIze\Core\Response;
use CRMAIze\Repository\CustomerRepository;
use CRMAIze\Repository\CampaignRepository;

class DashboardController
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

    // Get dashboard data
    $totalCustomers = $this->customerRepo->getTotalCount();
    $segments = $this->customerRepo->getSegmentCounts();
    $recentCampaigns = $this->campaignRepo->getRecent(5);
    $atRiskCustomers = $this->customerRepo->getAtRiskCustomers(10);

    // Calculate KPIs
    $totalRevenue = $this->customerRepo->getTotalRevenue();
    $avgOrderValue = $totalCustomers > 0 ? $totalRevenue / $totalCustomers : 0;
    $churnRate = $this->customerRepo->getChurnRate();

    $data = [
      'totalCustomers' => $totalCustomers,
      'totalRevenue' => $totalRevenue,
      'avgOrderValue' => $avgOrderValue,
      'churnRate' => $churnRate,
      'segments' => $segments,
      'recentCampaigns' => $recentCampaigns,
      'atRiskCustomers' => $atRiskCustomers,
      'user' => $this->app->getAuthService()->getCurrentUser()
    ];

    $html = $this->app->getTwig()->render('dashboard.twig', $data);
    return new Response($html);
  }
}
