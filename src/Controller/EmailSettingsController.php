<?php

namespace CRMAIze\Controller;

use CRMAIze\Core\Application;
use CRMAIze\Core\Request;
use CRMAIze\Core\Response;

class EmailSettingsController
{
  private $app;

  public function __construct(Application $app)
  {
    $this->app = $app;
  }

  public function index(Request $request): Response
  {
    // Require admin access
    $this->app->getAuthService()->requireAdmin();

    $emailService = $this->app->getEmailService();
    $config = $emailService->getConfigurationStatus();
    $testResult = null;

    if ($request->isPost() && $request->getInput('action') === 'test') {
      $testResult = $emailService->testConnection();
    }

    $data = [
      'config' => $config,
      'testResult' => $testResult
    ];

    $html = $this->app->getTwig()->render('email_settings.twig', $data);
    return new Response($html);
  }

  public function testConnection(Request $request): Response
  {
    // Require admin access
    $this->app->getAuthService()->requireAdmin();

    $emailService = $this->app->getEmailService();
    $result = $emailService->testConnection();

    return Response::json($result);
  }
}
