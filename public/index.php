<?php

require_once __DIR__ . '/../vendor/autoload.php';

use CRMAIze\Core\Application;
use CRMAIze\Core\Router;
use CRMAIze\Controller\DashboardController;
use CRMAIze\Controller\ApiController;
use CRMAIze\Controller\CampaignController;

// Load environment variables
$dotenv = Dotenv\Dotenv::createImmutable(__DIR__ . '/..');
$dotenv->load();

// Initialize application
$app = new Application();

// Set up routing
$router = new Router();

// Dashboard routes
$router->get('/', [DashboardController::class, 'index']);
$router->get('/dashboard', [DashboardController::class, 'index']);

// Campaign routes
$router->get('/campaigns', [CampaignController::class, 'index']);
$router->get('/campaigns/new', [CampaignController::class, 'create']);
$router->post('/campaigns', [CampaignController::class, 'store']);
$router->get('/campaigns/{id}', [CampaignController::class, 'show']);
$router->post('/campaigns/{id}/send', [CampaignController::class, 'send']);

// API routes
$router->get('/api/customers', [ApiController::class, 'getCustomers']);
$router->get('/api/customers/segments', [ApiController::class, 'getSegments']);
$router->get('/api/campaigns', [ApiController::class, 'getCampaigns']);
$router->post('/api/campaigns', [ApiController::class, 'createCampaign']);
$router->get('/api/analytics', [ApiController::class, 'getAnalytics']);

// Handle the request
$app->handle($router);
