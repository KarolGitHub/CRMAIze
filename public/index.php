<?php

require_once __DIR__ . '/../vendor/autoload.php';

use CRMAIze\Core\Application;
use CRMAIze\Core\Router;
use CRMAIze\Controller\DashboardController;
use CRMAIze\Controller\ApiController;
use CRMAIze\Controller\CampaignController;
use CRMAIze\Controller\AuthController;
use CRMAIze\Controller\EmailSettingsController;
use CRMAIze\Controller\DataImportExportController;
use CRMAIze\Controller\AnalyticsController;
use CRMAIze\Controller\AdvancedAIController;
use CRMAIze\Controller\AdvancedABTestingController;

// Load environment variables
$dotenv = Dotenv\Dotenv::createImmutable(__DIR__ . '/..');
$dotenv->load();

// Initialize application
$app = new Application();

// Set up routing
$router = new Router();

// Auth routes
$router->get('/login', [AuthController::class, 'showLogin']);
$router->post('/login', [AuthController::class, 'login']);
$router->get('/logout', [AuthController::class, 'logout']);
$router->get('/register', [AuthController::class, 'showRegister']);
$router->post('/register', [AuthController::class, 'register']);

// Email settings routes (admin only)
$router->get('/email-settings', [EmailSettingsController::class, 'index']);
$router->post('/email-settings', [EmailSettingsController::class, 'index']);
$router->post('/email-settings/test', [EmailSettingsController::class, 'testConnection']);

// Dashboard routes (protected)
$router->get('/', [DashboardController::class, 'index']);
$router->get('/dashboard', [DashboardController::class, 'index']);

// Campaign routes (protected)
$router->get('/campaigns', [CampaignController::class, 'index']);
$router->get('/campaigns/create', [CampaignController::class, 'create']);
$router->post('/campaigns', [CampaignController::class, 'store']);
$router->get('/campaigns/{id}', [CampaignController::class, 'show']);
$router->post('/campaigns/{id}/send', [CampaignController::class, 'send']);

// API routes (protected)
$router->get('/api/customers', [ApiController::class, 'getCustomers']);
$router->get('/api/customers/segments', [ApiController::class, 'getSegments']);
$router->get('/api/campaigns', [ApiController::class, 'getCampaigns']);
$router->post('/api/campaigns', [ApiController::class, 'createCampaign']);
$router->get('/api/analytics', [ApiController::class, 'getAnalytics']);

// Data Import/Export routes (protected)
$router->get('/data-import-export', [DataImportExportController::class, 'index']);
$router->get('/export/customers', [DataImportExportController::class, 'exportCustomers']);
$router->get('/export/campaigns', [DataImportExportController::class, 'exportCampaigns']);
$router->post('/import/customers', [DataImportExportController::class, 'importCustomers']);
$router->post('/import/campaigns', [DataImportExportController::class, 'importCampaigns']);
$router->get('/download/template/customers', [DataImportExportController::class, 'downloadCustomerTemplate']);
$router->get('/download/template/campaigns', [DataImportExportController::class, 'downloadCampaignTemplate']);

// Analytics routes (protected)
$router->get('/analytics', [AnalyticsController::class, 'index']);
$router->get('/analytics/customers', [AnalyticsController::class, 'customers']);
$router->get('/analytics/campaigns', [AnalyticsController::class, 'campaigns']);
$router->get('/analytics/revenue', [AnalyticsController::class, 'revenue']);
$router->get('/analytics/chart-data', [AnalyticsController::class, 'getChartData']);
$router->get('/analytics/download-report', [AnalyticsController::class, 'downloadReport']);

// Advanced AI routes (protected)
$router->get('/advanced-ai', [AdvancedAIController::class, 'index']);
$router->get('/api/advanced-ai/churn-predictions', [AdvancedAIController::class, 'getChurnPredictions']);
$router->get('/api/advanced-ai/clv-calculations', [AdvancedAIController::class, 'getCLVCalculations']);
$router->get('/api/advanced-ai/recommendations', [AdvancedAIController::class, 'getRecommendations']);
$router->post('/api/advanced-ai/sentiment-analysis', [AdvancedAIController::class, 'analyzeSentiment']);
$router->get('/api/advanced-ai/model-performance', [AdvancedAIController::class, 'getModelPerformance']);
$router->get('/api/advanced-ai/insights-report', [AdvancedAIController::class, 'generateInsightsReport']);

// Advanced A/B Testing routes (protected)
$router->get('/advanced-ab-testing', [AdvancedABTestingController::class, 'index']);
$router->get('/advanced-ab-testing/create', [AdvancedABTestingController::class, 'create']);
$router->post('/advanced-ab-testing/store', [AdvancedABTestingController::class, 'store']);
$router->post('/advanced-ab-testing/start', [AdvancedABTestingController::class, 'start']);
$router->get('/advanced-ab-testing/results', [AdvancedABTestingController::class, 'results']);
$router->post('/advanced-ab-testing/stop', [AdvancedABTestingController::class, 'stop']);
$router->get('/advanced-ab-testing/time-series', [AdvancedABTestingController::class, 'timeSeries']);
$router->get('/advanced-ab-testing/summary', [AdvancedABTestingController::class, 'summary']);
$router->get('/advanced-ab-testing/show', [AdvancedABTestingController::class, 'show']);
$router->get('/api/advanced-ab-testing/segments', [AdvancedABTestingController::class, 'getSegments']);
$router->get('/api/advanced-ab-testing/segment-customers', [AdvancedABTestingController::class, 'getSegmentCustomers']);

// Handle the request
$app->handle($router);
