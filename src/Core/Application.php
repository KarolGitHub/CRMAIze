<?php

namespace CRMAIze\Core;

use Twig\Environment;
use Twig\Loader\FilesystemLoader;
use CRMAIze\Service\DatabaseService;
use CRMAIze\Service\AIService;

class Application
{
  private $twig;
  private $database;
  private $aiService;

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
}
