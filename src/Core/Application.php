<?php

namespace CRMAIze\Core;

use Twig\Environment;
use Twig\Loader\FilesystemLoader;
use CRMAIze\Service\DatabaseService;
use CRMAIze\Service\AIService;
use CRMAIze\Service\AuthService;
use CRMAIze\Service\EmailService;
use CRMAIze\Service\CampaignScheduler;
use CRMAIze\Service\DataImportExportService;
use CRMAIze\Repository\UserRepository;
use CRMAIze\Repository\CampaignRepository;
use CRMAIze\Repository\CustomerRepository;

class Application
{
  private $twig;
  private $database;
  private $aiService;
  private $authService;
  private $emailService;
  private $campaignScheduler;
  private $dataImportExportService;

  public function __construct()
  {
    $this->initializeServices();
  }

  private function initializeServices()
  {
    // Initialize Twig
    $loader = new FilesystemLoader(__DIR__ . '/../../templates');
    $this->twig = new Environment($loader, [
      'cache' => __DIR__ . '/../../cache/twig',
      'debug' => $_ENV['APP_DEBUG'] ?? false,
      'auto_reload' => true,
    ]);

    // Initialize Database
    $this->database = new DatabaseService();

    // Initialize AI Service
    $this->aiService = new AIService();

    // Initialize Auth Service
    $userRepository = new UserRepository($this->database);
    $this->authService = new AuthService($userRepository);

    // Initialize Email Service
    $this->emailService = new EmailService();

    // Initialize Campaign Scheduler
    $campaignRepo = new CampaignRepository($this->database);
    $customerRepo = new CustomerRepository($this->database);
    $this->campaignScheduler = new CampaignScheduler($campaignRepo, $customerRepo, $this->emailService);

    // Initialize Data Import/Export Service
    $this->dataImportExportService = new DataImportExportService($customerRepo, $campaignRepo, $userRepository);
  }

  public function handle(Router $router)
  {
    try {
      $request = Request::createFromGlobals();
      $response = $router->dispatch($request, $this);

      if ($response instanceof Response) {
        $response->send();
      } else {
        echo $response;
      }
    } catch (\Exception $e) {
      if ($_ENV['APP_DEBUG'] ?? false) {
        throw $e;
      }

      http_response_code(500);
      echo "Internal Server Error";
    }
  }

  public function getTwig(): Environment
  {
    return $this->twig;
  }

  public function getDatabase(): DatabaseService
  {
    return $this->database;
  }

  public function getAIService(): AIService
  {
    return $this->aiService;
  }

  public function getAuthService(): AuthService
  {
    return $this->authService;
  }

  public function getEmailService(): EmailService
  {
    return $this->emailService;
  }

  public function getCampaignScheduler(): CampaignScheduler
  {
    return $this->campaignScheduler;
  }

  public function getDataImportExportService(): DataImportExportService
  {
    return $this->dataImportExportService;
  }
}
