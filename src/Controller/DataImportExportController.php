<?php

namespace CRMAIze\Controller;

use CRMAIze\Core\Application;
use CRMAIze\Core\Request;
use CRMAIze\Core\Response;

class DataImportExportController
{
  private Application $app;

  public function __construct(Application $app)
  {
    $this->app = $app;
  }

  /**
   * Show the data import/export page
   */
  public function index(): Response
  {
    $this->app->getAuthService()->requireAuth();

    $content = $this->app->getTwig()->render('data_import_export.twig', [
      'user' => $this->app->getAuthService()->getCurrentUser()
    ]);

    $response = new Response();
    $response->setContent($content);
    return $response;
  }

  /**
   * Export customers to CSV
   */
  public function exportCustomers(): Response
  {
    $this->app->getAuthService()->requireAuth();

    $csv = $this->app->getDataImportExportService()->exportCustomers();

    $response = new Response();
    $response->setContent($csv);
    $response->setHeader('Content-Type', 'text/csv');
    $response->setHeader('Content-Disposition', 'attachment; filename="customers_' . date('Y-m-d_H-i-s') . '.csv"');

    return $response;
  }

  /**
   * Export campaigns to CSV
   */
  public function exportCampaigns(): Response
  {
    $this->app->getAuthService()->requireAuth();

    $csv = $this->app->getDataImportExportService()->exportCampaigns();

    $response = new Response();
    $response->setContent($csv);
    $response->setHeader('Content-Type', 'text/csv');
    $response->setHeader('Content-Disposition', 'attachment; filename="campaigns_' . date('Y-m-d_H-i-s') . '.csv"');

    return $response;
  }

  /**
   * Import customers from CSV
   */
  public function importCustomers(Request $request): Response
  {
    $this->app->getAuthService()->requireAuth();

    $message = '';
    $errors = [];

    if ($request->getMethod() === 'POST') {
      $uploadedFile = $_FILES['csv_file'] ?? null;

      if (!$uploadedFile || $uploadedFile['error'] !== UPLOAD_ERR_OK) {
        $errors[] = 'Please select a valid CSV file to upload.';
      } else {
        // Validate file
        $fileErrors = $this->app->getDataImportExportService()->validateCsvFile($uploadedFile['tmp_name']);
        if (!empty($fileErrors)) {
          $errors = array_merge($errors, $fileErrors);
        } else {
          // Process the file
          $csvContent = file_get_contents($uploadedFile['tmp_name']);
          $results = $this->app->getDataImportExportService()->importCustomers($csvContent);

          if ($results['success'] > 0) {
            $message = "Successfully imported {$results['success']} customers.";
            if ($results['skipped'] > 0) {
              $message .= " {$results['skipped']} customers were skipped (already exist).";
            }
          }

          if (!empty($results['errors'])) {
            $errors = array_merge($errors, $results['errors']);
          }
        }
      }
    }

    $content = $this->app->getTwig()->render('data_import_export.twig', [
      'user' => $this->app->getAuthService()->getCurrentUser(),
      'message' => $message,
      'errors' => $errors
    ]);

    $response = new Response();
    $response->setContent($content);
    return $response;
  }

  /**
   * Import campaigns from CSV
   */
  public function importCampaigns(Request $request): Response
  {
    $this->app->getAuthService()->requireAuth();

    $message = '';
    $errors = [];

    if ($request->getMethod() === 'POST') {
      $uploadedFile = $_FILES['csv_file'] ?? null;

      if (!$uploadedFile || $uploadedFile['error'] !== UPLOAD_ERR_OK) {
        $errors[] = 'Please select a valid CSV file to upload.';
      } else {
        // Validate file
        $fileErrors = $this->app->getDataImportExportService()->validateCsvFile($uploadedFile['tmp_name']);
        if (!empty($fileErrors)) {
          $errors = array_merge($errors, $fileErrors);
        } else {
          // Process the file
          $csvContent = file_get_contents($uploadedFile['tmp_name']);
          $currentUser = $this->app->getAuthService()->getCurrentUser();
          $results = $this->app->getDataImportExportService()->importCampaigns($csvContent, $currentUser->getId());

          if ($results['success'] > 0) {
            $message = "Successfully imported {$results['success']} campaigns.";
            if ($results['skipped'] > 0) {
              $message .= " {$results['skipped']} campaigns were skipped.";
            }
          }

          if (!empty($results['errors'])) {
            $errors = array_merge($errors, $results['errors']);
          }
        }
      }
    }

    $content = $this->app->getTwig()->render('data_import_export.twig', [
      'user' => $this->app->getAuthService()->getCurrentUser(),
      'message' => $message,
      'errors' => $errors
    ]);

    $response = new Response();
    $response->setContent($content);
    return $response;
  }

  /**
   * Download customer CSV template
   */
  public function downloadCustomerTemplate(): Response
  {
    $this->app->getAuthService()->requireAuth();

    $template = $this->app->getDataImportExportService()->getCustomerTemplate();

    $response = new Response();
    $response->setContent($template);
    $response->setHeader('Content-Type', 'text/csv');
    $response->setHeader('Content-Disposition', 'attachment; filename="customer_template.csv"');

    return $response;
  }

  /**
   * Download campaign CSV template
   */
  public function downloadCampaignTemplate(): Response
  {
    $this->app->getAuthService()->requireAuth();

    $template = $this->app->getDataImportExportService()->getCampaignTemplate();

    $response = new Response();
    $response->setContent($template);
    $response->setHeader('Content-Type', 'text/csv');
    $response->setHeader('Content-Disposition', 'attachment; filename="campaign_template.csv"');

    return $response;
  }
}
