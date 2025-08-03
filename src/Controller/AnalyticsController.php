<?php

namespace CRMAIze\Controller;

use CRMAIze\Core\Application;
use CRMAIze\Core\Request;
use CRMAIze\Core\Response;
use CRMAIze\Service\AnalyticsService;
use CRMAIze\Service\AuthService;
use Twig\Environment;

class AnalyticsController
{
  private Application $app;
  private Environment $twig;
  private AnalyticsService $analyticsService;
  private AuthService $authService;

  public function __construct(Application $app)
  {
    $this->app = $app;
    $this->twig = $app->getTwig();
    $this->analyticsService = $app->getAnalyticsService();
    $this->authService = $app->getAuthService();
  }

  /**
   * Display the analytics dashboard
   */
  public function index(Request $request): Response
  {
    $this->authService->requireAuth();

    $analytics = $this->analyticsService->getDashboardAnalytics();
    $user = $this->authService->getCurrentUser();

    $content = $this->twig->render('analytics.twig', [
      'user' => $user,
      'analytics' => $analytics,
      'page_title' => 'Analytics Dashboard',
      'current_page' => 'analytics'
    ]);

    return new Response($content);
  }

  /**
   * Get chart data for AJAX requests
   */
  public function getChartData(Request $request): Response
  {
    $this->authService->requireAuth();

    $chartType = $request->getInput('type', 'revenue_trend');
    $chartData = $this->analyticsService->getChartData($chartType);

    $response = new Response(json_encode($chartData));
    $response->setHeader('Content-Type', 'application/json');

    return $response;
  }

  /**
   * Generate and download PDF report
   */
  public function downloadReport(Request $request): Response
  {
    $this->authService->requireAuth();

    $reportType = $request->getInput('type', 'comprehensive');

    try {
      $filename = $this->analyticsService->generatePDFReport($reportType);
      $filepath = __DIR__ . '/../../cache/' . $filename;

      if (!file_exists($filepath)) {
        throw new \Exception('Report generation failed');
      }

      $response = new Response(file_get_contents($filepath));
      $response->setHeader('Content-Type', 'application/pdf');
      $response->setHeader('Content-Disposition', 'attachment; filename="' . $filename . '"');
      $response->setHeader('Content-Length', (string)filesize($filepath));

      // Clean up the temporary file after serving
      register_shutdown_function(function () use ($filepath) {
        if (file_exists($filepath)) {
          unlink($filepath);
        }
      });

      return $response;
    } catch (\Exception $e) {
      $content = $this->twig->render('analytics.twig', [
        'user' => $this->authService->getCurrentUser(),
        'analytics' => $this->analyticsService->getDashboardAnalytics(),
        'page_title' => 'Analytics Dashboard',
        'error' => 'Failed to generate PDF report: ' . $e->getMessage()
      ]);

      return new Response($content);
    }
  }

  /**
   * Display customer analytics details
   */
  public function customers(Request $request): Response
  {
    $this->authService->requireAuth();

    $analytics = $this->analyticsService->getDashboardAnalytics();
    $user = $this->authService->getCurrentUser();

    $content = $this->twig->render('analytics_customers.twig', [
      'user' => $user,
      'customer_analytics' => $analytics['customer_analytics'],
      'overview' => $analytics['overview'],
      'page_title' => 'Customer Analytics'
    ]);

    return new Response($content);
  }

  /**
   * Display campaign analytics details
   */
  public function campaigns(Request $request): Response
  {
    $this->authService->requireAuth();

    $analytics = $this->analyticsService->getDashboardAnalytics();
    $user = $this->authService->getCurrentUser();

    $content = $this->twig->render('analytics_campaigns.twig', [
      'user' => $user,
      'campaign_analytics' => $analytics['campaign_analytics'],
      'page_title' => 'Campaign Analytics'
    ]);

    return new Response($content);
  }

  /**
   * Display revenue analytics details
   */
  public function revenue(Request $request): Response
  {
    $this->authService->requireAuth();

    $analytics = $this->analyticsService->getDashboardAnalytics();
    $user = $this->authService->getCurrentUser();

    $content = $this->twig->render('analytics_revenue.twig', [
      'user' => $user,
      'revenue_analytics' => $analytics['revenue_analytics'],
      'trends' => $analytics['trends'],
      'page_title' => 'Revenue Analytics'
    ]);

    return new Response($content);
  }
}
