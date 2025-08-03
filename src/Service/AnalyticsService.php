<?php

namespace CRMAIze\Service;

use CRMAIze\Repository\CustomerRepository;
use CRMAIze\Repository\CampaignRepository;

class AnalyticsService
{
  private CustomerRepository $customerRepository;
  private CampaignRepository $campaignRepository;

  public function __construct(CustomerRepository $customerRepository, CampaignRepository $campaignRepository)
  {
    $this->customerRepository = $customerRepository;
    $this->campaignRepository = $campaignRepository;
  }

  /**
   * Get comprehensive dashboard analytics
   */
  public function getDashboardAnalytics(): array
  {
    return [
      'overview' => $this->getOverviewMetrics(),
      'customer_analytics' => $this->getCustomerAnalytics(),
      'campaign_analytics' => $this->getCampaignAnalytics(),
      'revenue_analytics' => $this->getRevenueAnalytics(),
      'trends' => $this->getTrendAnalytics()
    ];
  }

  /**
   * Get overview metrics for the dashboard
   */
  public function getOverviewMetrics(): array
  {
    $customers = $this->customerRepository->getAll();
    $campaigns = $this->campaignRepository->getAll();

    $totalCustomers = count($customers);
    $totalRevenue = array_sum(array_column($customers, 'total_spent'));
    $averageOrderValue = $totalCustomers > 0 ? $totalRevenue / array_sum(array_column($customers, 'order_count')) : 0;
    $activeCampaigns = count(array_filter($campaigns, fn($c) => $c['status'] === 'active'));

    // Calculate churn risk
    $highRiskCustomers = 0;
    foreach ($customers as $customer) {
      if ($this->calculateChurnRisk($customer) > 0.7) {
        $highRiskCustomers++;
      }
    }

    return [
      'total_customers' => $totalCustomers,
      'total_revenue' => $totalRevenue,
      'average_order_value' => round($averageOrderValue, 2),
      'active_campaigns' => $activeCampaigns,
      'high_risk_customers' => $highRiskCustomers,
      'churn_rate' => $totalCustomers > 0 ? round(($highRiskCustomers / $totalCustomers) * 100, 1) : 0
    ];
  }

  /**
   * Get detailed customer analytics
   */
  public function getCustomerAnalytics(): array
  {
    $customers = $this->customerRepository->getAll();

    // Segment customers by value
    $segments = ['high_value' => 0, 'medium_value' => 0, 'low_value' => 0];
    $ageGroups = ['0-30' => 0, '31-50' => 0, '51+' => 0];
    $loyaltyLevels = ['new' => 0, 'regular' => 0, 'loyal' => 0, 'vip' => 0];

    foreach ($customers as $customer) {
      // Value segmentation
      if ($customer['total_spent'] > 1000) {
        $segments['high_value']++;
      } elseif ($customer['total_spent'] > 300) {
        $segments['medium_value']++;
      } else {
        $segments['low_value']++;
      }

      // Age segmentation (mock data based on customer ID)
      $mockAge = 25 + ($customer['id'] % 40);
      if ($mockAge <= 30) {
        $ageGroups['0-30']++;
      } elseif ($mockAge <= 50) {
        $ageGroups['31-50']++;
      } else {
        $ageGroups['51+']++;
      }

      // Loyalty segmentation
      $orderCount = $customer['order_count'] ?? 0;
      if ($orderCount >= 20) {
        $loyaltyLevels['vip']++;
      } elseif ($orderCount >= 10) {
        $loyaltyLevels['loyal']++;
      } elseif ($orderCount >= 3) {
        $loyaltyLevels['regular']++;
      } else {
        $loyaltyLevels['new']++;
      }
    }

    return [
      'value_segments' => $segments,
      'age_groups' => $ageGroups,
      'loyalty_levels' => $loyaltyLevels,
      'lifetime_value_distribution' => $this->getLifetimeValueDistribution($customers),
      'geographic_distribution' => $this->getGeographicDistribution($customers)
    ];
  }

  /**
   * Get campaign performance analytics
   */
  public function getCampaignAnalytics(): array
  {
    $campaigns = $this->campaignRepository->getAll();

    $totalCampaigns = count($campaigns);
    $completedCampaigns = count(array_filter($campaigns, fn($c) => $c['status'] === 'completed'));
    $activeCampaigns = count(array_filter($campaigns, fn($c) => $c['status'] === 'active'));

    // Campaign type distribution
    $typeDistribution = [];
    foreach ($campaigns as $campaign) {
      $type = $campaign['type'] ?? 'email';
      $typeDistribution[$type] = ($typeDistribution[$type] ?? 0) + 1;
    }

    // Mock performance metrics (in real app, this would come from campaign_logs)
    $performanceMetrics = [
      'average_open_rate' => 24.5,
      'average_click_rate' => 3.2,
      'average_conversion_rate' => 1.8,
      'total_emails_sent' => $completedCampaigns * 150, // Mock data
      'total_revenue_generated' => $completedCampaigns * 2500 // Mock data
    ];

    return [
      'total_campaigns' => $totalCampaigns,
      'completed_campaigns' => $completedCampaigns,
      'active_campaigns' => $activeCampaigns,
      'type_distribution' => $typeDistribution,
      'performance_metrics' => $performanceMetrics,
      'monthly_campaign_trends' => $this->getMonthlyCampaignTrends($campaigns)
    ];
  }

  /**
   * Get revenue analytics
   */
  public function getRevenueAnalytics(): array
  {
    $customers = $this->customerRepository->getAll();

    $totalRevenue = array_sum(array_column($customers, 'total_spent'));
    $monthlyRevenue = $this->getMonthlyRevenue($customers);
    $revenueBySegment = $this->getRevenueBySegment($customers);

    return [
      'total_revenue' => $totalRevenue,
      'monthly_revenue' => $monthlyRevenue,
      'revenue_by_segment' => $revenueBySegment,
      'revenue_growth' => $this->calculateRevenueGrowth($monthlyRevenue),
      'top_customers' => $this->getTopCustomersByRevenue($customers, 10)
    ];
  }

  /**
   * Get trend analytics for time-series data
   */
  public function getTrendAnalytics(): array
  {
    return [
      'customer_acquisition' => $this->getCustomerAcquisitionTrend(),
      'revenue_trend' => $this->getRevenueTrend(),
      'campaign_performance_trend' => $this->getCampaignPerformanceTrend(),
      'churn_trend' => $this->getChurnTrend()
    ];
  }

  /**
   * Generate chart data for frontend visualization
   */
  public function getChartData(string $chartType): array
  {
    switch ($chartType) {
      case 'revenue_trend':
        return $this->formatChartData($this->getRevenueTrend(), 'Revenue Trend');
      case 'customer_segments':
        $analytics = $this->getCustomerAnalytics();
        return $this->formatPieChartData($analytics['value_segments'], 'Customer Segments');
      case 'campaign_performance':
        $analytics = $this->getCampaignAnalytics();
        return $this->formatChartData($analytics['monthly_campaign_trends'], 'Campaign Performance');
      case 'loyalty_distribution':
        $analytics = $this->getCustomerAnalytics();
        return $this->formatPieChartData($analytics['loyalty_levels'], 'Customer Loyalty');
      default:
        return [];
    }
  }

  // Helper methods for calculations
  private function calculateChurnRisk(array $customer): float
  {
    $daysSinceLastOrder = 90; // Mock calculation
    $orderFrequency = $customer['order_count'] ?? 1;

    if ($daysSinceLastOrder > 180) return 0.9;
    if ($daysSinceLastOrder > 90) return 0.6;
    if ($orderFrequency < 2) return 0.7;

    return 0.2;
  }

  private function getLifetimeValueDistribution(array $customers): array
  {
    $distribution = ['0-100' => 0, '101-500' => 0, '501-1000' => 0, '1000+' => 0];

    foreach ($customers as $customer) {
      $ltv = $customer['total_spent'];
      if ($ltv <= 100) {
        $distribution['0-100']++;
      } elseif ($ltv <= 500) {
        $distribution['101-500']++;
      } elseif ($ltv <= 1000) {
        $distribution['501-1000']++;
      } else {
        $distribution['1000+']++;
      }
    }

    return $distribution;
  }

  private function getGeographicDistribution(array $customers): array
  {
    // Mock geographic data
    return [
      'North America' => intval(count($customers) * 0.45),
      'Europe' => intval(count($customers) * 0.30),
      'Asia' => intval(count($customers) * 0.15),
      'Other' => intval(count($customers) * 0.10)
    ];
  }

  private function getMonthlyCampaignTrends(array $campaigns): array
  {
    $trends = [];
    for ($i = 11; $i >= 0; $i--) {
      $month = date('Y-m', strtotime("-$i months"));
      $trends[$month] = rand(2, 8); // Mock data
    }
    return $trends;
  }

  private function getMonthlyRevenue(array $customers): array
  {
    $revenue = [];
    for ($i = 11; $i >= 0; $i--) {
      $month = date('Y-m', strtotime("-$i months"));
      $revenue[$month] = rand(15000, 45000); // Mock data
    }
    return $revenue;
  }

  private function getRevenueBySegment(array $customers): array
  {
    $segments = ['high_value' => 0, 'medium_value' => 0, 'low_value' => 0];

    foreach ($customers as $customer) {
      if ($customer['total_spent'] > 1000) {
        $segments['high_value'] += $customer['total_spent'];
      } elseif ($customer['total_spent'] > 300) {
        $segments['medium_value'] += $customer['total_spent'];
      } else {
        $segments['low_value'] += $customer['total_spent'];
      }
    }

    return $segments;
  }

  private function calculateRevenueGrowth(array $monthlyRevenue): float
  {
    $values = array_values($monthlyRevenue);
    if (count($values) < 2) return 0;

    $current = end($values);
    $previous = $values[count($values) - 2];

    return $previous > 0 ? round((($current - $previous) / $previous) * 100, 1) : 0;
  }

  private function getTopCustomersByRevenue(array $customers, int $limit = 10): array
  {
    usort($customers, fn($a, $b) => $b['total_spent'] <=> $a['total_spent']);
    return array_slice($customers, 0, $limit);
  }

  private function getCustomerAcquisitionTrend(): array
  {
    $trend = [];
    for ($i = 11; $i >= 0; $i--) {
      $month = date('Y-m', strtotime("-$i months"));
      $trend[$month] = rand(50, 200); // Mock data
    }
    return $trend;
  }

  private function getRevenueTrend(): array
  {
    return $this->getMonthlyRevenue([]); // Reuse monthly revenue logic
  }

  private function getCampaignPerformanceTrend(): array
  {
    $trend = [];
    for ($i = 11; $i >= 0; $i--) {
      $month = date('Y-m', strtotime("-$i months"));
      $trend[$month] = [
        'open_rate' => rand(20, 30),
        'click_rate' => rand(2, 5),
        'conversion_rate' => rand(1, 3)
      ];
    }
    return $trend;
  }

  private function getChurnTrend(): array
  {
    $trend = [];
    for ($i = 11; $i >= 0; $i--) {
      $month = date('Y-m', strtotime("-$i months"));
      $trend[$month] = rand(5, 15); // Mock churn percentage
    }
    return $trend;
  }

  private function formatChartData(array $data, string $label): array
  {
    return [
      'labels' => array_keys($data),
      'datasets' => [
        [
          'label' => $label,
          'data' => array_values($data),
          'borderColor' => 'rgb(75, 192, 192)',
          'backgroundColor' => 'rgba(75, 192, 192, 0.2)',
          'tension' => 0.1
        ]
      ]
    ];
  }

  private function formatPieChartData(array $data, string $label): array
  {
    return [
      'labels' => array_map('ucfirst', array_map(fn($k) => str_replace('_', ' ', $k), array_keys($data))),
      'datasets' => [
        [
          'label' => $label,
          'data' => array_values($data),
          'backgroundColor' => [
            'rgba(255, 99, 132, 0.8)',
            'rgba(54, 162, 235, 0.8)',
            'rgba(255, 205, 86, 0.8)',
            'rgba(75, 192, 192, 0.8)',
            'rgba(153, 102, 255, 0.8)'
          ]
        ]
      ]
    ];
  }

  /**
   * Generate PDF report
   */
  public function generatePDFReport(string $reportType = 'comprehensive'): string
  {
    $analytics = $this->getDashboardAnalytics();

    // Create TCPDF instance
    $pdf = new \TCPDF(PDF_PAGE_ORIENTATION, PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);

    // Set document information
    $pdf->SetCreator('CRMAIze');
    $pdf->SetAuthor('CRMAIze Analytics');
    $pdf->SetTitle('Analytics Report - ' . date('Y-m-d'));
    $pdf->SetSubject('Customer and Campaign Analytics');

    // Set default header data
    $pdf->SetHeaderData('', 0, 'CRMAIze Analytics Report', date('F j, Y'));

    // Set header and footer fonts
    $pdf->setHeaderFont(['helvetica', '', 12]);
    $pdf->setFooterFont(['helvetica', '', 8]);

    // Set margins
    $pdf->SetMargins(15, 27, 15);
    $pdf->SetHeaderMargin(5);
    $pdf->SetFooterMargin(10);

    // Set auto page breaks
    $pdf->SetAutoPageBreak(true, 25);

    // Add a page
    $pdf->AddPage();

    // Generate content based on report type
    switch ($reportType) {
      case 'customer':
        $this->generateCustomerReportContent($pdf, $analytics);
        break;
      case 'campaign':
        $this->generateCampaignReportContent($pdf, $analytics);
        break;
      case 'revenue':
        $this->generateRevenueReportContent($pdf, $analytics);
        break;
      default:
        $this->generateComprehensiveReportContent($pdf, $analytics);
    }

    // Generate filename
    $filename = 'analytics_report_' . $reportType . '_' . date('Y-m-d_H-i-s') . '.pdf';
    $filepath = __DIR__ . '/../../cache/' . $filename;

    // Ensure cache directory exists
    if (!is_dir(dirname($filepath))) {
      mkdir(dirname($filepath), 0755, true);
    }

    // Output PDF to file
    $pdf->Output($filepath, 'F');

    return $filename;
  }

  // PDF content generation methods
  private function generateComprehensiveReportContent($pdf, array $analytics): void
  {
    $pdf->SetFont('helvetica', 'B', 16);
    $pdf->Cell(0, 10, 'Comprehensive Analytics Report', 0, 1, 'C');
    $pdf->Ln(5);

    // Overview section
    $this->addOverviewSection($pdf, $analytics['overview']);

    // Customer Analytics section
    $this->addCustomerAnalyticsSection($pdf, $analytics['customer_analytics']);

    // Campaign Analytics section
    $this->addCampaignAnalyticsSection($pdf, $analytics['campaign_analytics']);

    // Revenue Analytics section
    $this->addRevenueAnalyticsSection($pdf, $analytics['revenue_analytics']);
  }

  private function generateCustomerReportContent($pdf, array $analytics): void
  {
    $pdf->SetFont('helvetica', 'B', 16);
    $pdf->Cell(0, 10, 'Customer Analytics Report', 0, 1, 'C');
    $pdf->Ln(5);

    $this->addCustomerAnalyticsSection($pdf, $analytics['customer_analytics']);
    $this->addOverviewSection($pdf, $analytics['overview']);
  }

  private function generateCampaignReportContent($pdf, array $analytics): void
  {
    $pdf->SetFont('helvetica', 'B', 16);
    $pdf->Cell(0, 10, 'Campaign Performance Report', 0, 1, 'C');
    $pdf->Ln(5);

    $this->addCampaignAnalyticsSection($pdf, $analytics['campaign_analytics']);
  }

  private function generateRevenueReportContent($pdf, array $analytics): void
  {
    $pdf->SetFont('helvetica', 'B', 16);
    $pdf->Cell(0, 10, 'Revenue Analytics Report', 0, 1, 'C');
    $pdf->Ln(5);

    $this->addRevenueAnalyticsSection($pdf, $analytics['revenue_analytics']);
  }

  private function addOverviewSection($pdf, array $overview): void
  {
    $pdf->SetFont('helvetica', 'B', 14);
    $pdf->Cell(0, 8, 'Overview Metrics', 0, 1, 'L');
    $pdf->Ln(2);

    $pdf->SetFont('helvetica', '', 10);
    $pdf->Cell(90, 6, 'Total Customers:', 0, 0, 'L');
    $pdf->Cell(0, 6, number_format($overview['total_customers']), 0, 1, 'L');

    $pdf->Cell(90, 6, 'Total Revenue:', 0, 0, 'L');
    $pdf->Cell(0, 6, '$' . number_format($overview['total_revenue'], 2), 0, 1, 'L');

    $pdf->Cell(90, 6, 'Average Order Value:', 0, 0, 'L');
    $pdf->Cell(0, 6, '$' . number_format($overview['average_order_value'], 2), 0, 1, 'L');

    $pdf->Cell(90, 6, 'Active Campaigns:', 0, 0, 'L');
    $pdf->Cell(0, 6, $overview['active_campaigns'], 0, 1, 'L');

    $pdf->Cell(90, 6, 'High Risk Customers:', 0, 0, 'L');
    $pdf->Cell(0, 6, $overview['high_risk_customers'] . ' (' . $overview['churn_rate'] . '%)', 0, 1, 'L');

    $pdf->Ln(5);
  }

  private function addCustomerAnalyticsSection($pdf, array $customerAnalytics): void
  {
    $pdf->SetFont('helvetica', 'B', 14);
    $pdf->Cell(0, 8, 'Customer Analytics', 0, 1, 'L');
    $pdf->Ln(2);

    $pdf->SetFont('helvetica', 'B', 12);
    $pdf->Cell(0, 6, 'Value Segments:', 0, 1, 'L');
    $pdf->SetFont('helvetica', '', 10);

    foreach ($customerAnalytics['value_segments'] as $segment => $count) {
      $pdf->Cell(90, 5, ucfirst(str_replace('_', ' ', $segment)) . ':', 0, 0, 'L');
      $pdf->Cell(0, 5, $count, 0, 1, 'L');
    }

    $pdf->Ln(3);
    $pdf->SetFont('helvetica', 'B', 12);
    $pdf->Cell(0, 6, 'Loyalty Levels:', 0, 1, 'L');
    $pdf->SetFont('helvetica', '', 10);

    foreach ($customerAnalytics['loyalty_levels'] as $level => $count) {
      $pdf->Cell(90, 5, ucfirst($level) . ':', 0, 0, 'L');
      $pdf->Cell(0, 5, $count, 0, 1, 'L');
    }

    $pdf->Ln(5);
  }

  private function addCampaignAnalyticsSection($pdf, array $campaignAnalytics): void
  {
    $pdf->SetFont('helvetica', 'B', 14);
    $pdf->Cell(0, 8, 'Campaign Analytics', 0, 1, 'L');
    $pdf->Ln(2);

    $pdf->SetFont('helvetica', '', 10);
    $pdf->Cell(90, 6, 'Total Campaigns:', 0, 0, 'L');
    $pdf->Cell(0, 6, $campaignAnalytics['total_campaigns'], 0, 1, 'L');

    $pdf->Cell(90, 6, 'Completed Campaigns:', 0, 0, 'L');
    $pdf->Cell(0, 6, $campaignAnalytics['completed_campaigns'], 0, 1, 'L');

    $pdf->Cell(90, 6, 'Active Campaigns:', 0, 0, 'L');
    $pdf->Cell(0, 6, $campaignAnalytics['active_campaigns'], 0, 1, 'L');

    $metrics = $campaignAnalytics['performance_metrics'];
    $pdf->Cell(90, 6, 'Average Open Rate:', 0, 0, 'L');
    $pdf->Cell(0, 6, $metrics['average_open_rate'] . '%', 0, 1, 'L');

    $pdf->Cell(90, 6, 'Average Click Rate:', 0, 0, 'L');
    $pdf->Cell(0, 6, $metrics['average_click_rate'] . '%', 0, 1, 'L');

    $pdf->Cell(90, 6, 'Total Revenue Generated:', 0, 0, 'L');
    $pdf->Cell(0, 6, '$' . number_format($metrics['total_revenue_generated']), 0, 1, 'L');

    $pdf->Ln(5);
  }

  private function addRevenueAnalyticsSection($pdf, array $revenueAnalytics): void
  {
    $pdf->SetFont('helvetica', 'B', 14);
    $pdf->Cell(0, 8, 'Revenue Analytics', 0, 1, 'L');
    $pdf->Ln(2);

    $pdf->SetFont('helvetica', '', 10);
    $pdf->Cell(90, 6, 'Total Revenue:', 0, 0, 'L');
    $pdf->Cell(0, 6, '$' . number_format($revenueAnalytics['total_revenue'], 2), 0, 1, 'L');

    $pdf->Cell(90, 6, 'Revenue Growth:', 0, 0, 'L');
    $pdf->Cell(0, 6, $revenueAnalytics['revenue_growth'] . '%', 0, 1, 'L');

    $pdf->Ln(3);
    $pdf->SetFont('helvetica', 'B', 12);
    $pdf->Cell(0, 6, 'Revenue by Segment:', 0, 1, 'L');
    $pdf->SetFont('helvetica', '', 10);

    foreach ($revenueAnalytics['revenue_by_segment'] as $segment => $revenue) {
      $pdf->Cell(90, 5, ucfirst(str_replace('_', ' ', $segment)) . ':', 0, 0, 'L');
      $pdf->Cell(0, 5, '$' . number_format($revenue, 2), 0, 1, 'L');
    }

    $pdf->Ln(5);
  }
}
