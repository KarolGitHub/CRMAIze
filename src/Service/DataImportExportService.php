<?php

namespace CRMAIze\Service;

use CRMAIze\Repository\CustomerRepository;
use CRMAIze\Repository\CampaignRepository;
use CRMAIze\Repository\UserRepository;

class DataImportExportService
{
  private CustomerRepository $customerRepository;
  private CampaignRepository $campaignRepository;
  private UserRepository $userRepository;

  public function __construct(
    CustomerRepository $customerRepository,
    CampaignRepository $campaignRepository,
    UserRepository $userRepository
  ) {
    $this->customerRepository = $customerRepository;
    $this->campaignRepository = $campaignRepository;
    $this->userRepository = $userRepository;
  }

  /**
   * Export customers to CSV
   */
  public function exportCustomers(): string
  {
    $customers = $this->customerRepository->getAll();

    $csv = "ID,First_Name,Last_Name,Email,Total_Spent,Order_Count,Last_Order_Date,Segment,Churn_Risk,Created_At\n";

    foreach ($customers as $customer) {
      $csv .= sprintf(
        "%d,%s,%s,%s,%.2f,%d,%s,%s,%.2f,%s\n",
        $customer['id'],
        $this->escapeCsvField($customer['first_name'] ?? ''),
        $this->escapeCsvField($customer['last_name'] ?? ''),
        $this->escapeCsvField($customer['email']),
        $customer['total_spent'],
        $customer['order_count'],
        $customer['last_order_date'] ?? '',
        $this->escapeCsvField($customer['segment'] ?? ''),
        $customer['churn_risk'],
        $customer['created_at']
      );
    }

    return $csv;
  }

  /**
   * Import customers from CSV
   */
  public function importCustomers(string $csvContent): array
  {
    $lines = explode("\n", trim($csvContent));
    $headers = str_getcsv(array_shift($lines));

    $results = [
      'success' => 0,
      'errors' => [],
      'skipped' => 0
    ];

    foreach ($lines as $lineNumber => $line) {
      if (empty(trim($line))) continue;

      $data = str_getcsv($line);
      if (count($data) !== count($headers)) {
        $results['errors'][] = "Line " . ($lineNumber + 2) . ": Invalid number of columns";
        continue;
      }

      $customerData = array_combine($headers, $data);

      try {
        // Validate required fields
        if (empty($customerData['Email'])) {
          $results['errors'][] = "Line " . ($lineNumber + 2) . ": Email is required";
          continue;
        }

        // Check if customer already exists
        $existingCustomer = $this->customerRepository->findByEmail($customerData['Email']);
        if ($existingCustomer) {
          $results['skipped']++;
          continue;
        }

        // Create customer
        $customerId = $this->customerRepository->create([
          'email' => $customerData['Email'],
          'first_name' => $customerData['First_Name'] ?? '',
          'last_name' => $customerData['Last_Name'] ?? '',
          'total_spent' => floatval($customerData['Total_Spent'] ?? 0),
          'order_count' => intval($customerData['Order_Count'] ?? 0),
          'last_order_date' => $customerData['Last_Order_Date'] ?? null
        ]);

        $results['success']++;
      } catch (\Exception $e) {
        $results['errors'][] = "Line " . ($lineNumber + 2) . ": " . $e->getMessage();
      }
    }

    return $results;
  }

  /**
   * Export campaigns to CSV
   */
  public function exportCampaigns(): string
  {
    $campaigns = $this->campaignRepository->getAll();

    $csv = "ID,Name,Type,Subject_Line,Email_Content,Target_Segment,Discount_Percent,Status,Scheduled_At,Sent_At,Created_By,Created_At\n";

    foreach ($campaigns as $campaign) {
      $csv .= sprintf(
        "%d,%s,%s,%s,%s,%s,%d,%s,%s,%s,%s,%s\n",
        $campaign['id'],
        $this->escapeCsvField($campaign['name']),
        $this->escapeCsvField($campaign['type']),
        $this->escapeCsvField($campaign['subject_line'] ?? ''),
        $this->escapeCsvField($campaign['email_content'] ?? ''),
        $this->escapeCsvField($campaign['target_segment'] ?? ''),
        $campaign['discount_percent'] ?? 0,
        $this->escapeCsvField($campaign['status']),
        $campaign['scheduled_at'] ?? '',
        $campaign['sent_at'] ?? '',
        $campaign['created_by'] ?? '',
        $campaign['created_at']
      );
    }

    return $csv;
  }

  /**
   * Import campaigns from CSV
   */
  public function importCampaigns(string $csvContent, int $createdBy): array
  {
    $lines = explode("\n", trim($csvContent));
    $headers = str_getcsv(array_shift($lines));

    $results = [
      'success' => 0,
      'errors' => [],
      'skipped' => 0
    ];

    foreach ($lines as $lineNumber => $line) {
      if (empty(trim($line))) continue;

      $data = str_getcsv($line);
      if (count($data) !== count($headers)) {
        $results['errors'][] = "Line " . ($lineNumber + 2) . ": Invalid number of columns";
        continue;
      }

      $campaignData = array_combine($headers, $data);

      try {
        // Validate required fields
        if (empty($campaignData['Name']) || empty($campaignData['Type'])) {
          $results['errors'][] = "Line " . ($lineNumber + 2) . ": Name and Type are required";
          continue;
        }

        // Validate campaign type
        $validTypes = ['email', 'discount', 'ab_test'];
        if (!in_array(strtolower($campaignData['Type']), $validTypes)) {
          $results['errors'][] = "Line " . ($lineNumber + 2) . ": Invalid campaign type. Must be one of: " . implode(', ', $validTypes);
          continue;
        }

        // Create campaign
        $campaignId = $this->campaignRepository->create([
          'name' => $campaignData['Name'],
          'type' => strtolower($campaignData['Type']),
          'subject_line' => $campaignData['Subject_Line'] ?? '',
          'email_content' => $campaignData['Email_Content'] ?? '',
          'target_segment' => $campaignData['Target_Segment'] ?? 'all',
          'discount_percent' => floatval($campaignData['Discount_Percent'] ?? 0),
          'status' => $campaignData['Status'] ?? 'draft',
          'scheduled_at' => $campaignData['Scheduled_At'] ?? null,
          'created_by' => $createdBy
        ]);

        $results['success']++;
      } catch (\Exception $e) {
        $results['errors'][] = "Line " . ($lineNumber + 2) . ": " . $e->getMessage();
      }
    }

    return $results;
  }

  /**
   * Get CSV template for customers
   */
  public function getCustomerTemplate(): string
  {
    return "First_Name,Last_Name,Email,Total_Spent,Order_Count,Last_Order_Date,Segment,Churn_Risk\n" .
      "John,Doe,john@example.com,150.00,5,2024-01-15,premium,0.2\n" .
      "Jane,Smith,jane@example.com,75.50,3,2024-01-10,regular,0.4";
  }

  /**
   * Get CSV template for campaigns
   */
  public function getCampaignTemplate(): string
  {
    return "Name,Type,Subject_Line,Email_Content,Target_Segment,Discount_Percent,Status,Scheduled_At\n" .
      "Welcome Campaign,email,Welcome to our platform!,Welcome email content...,new_customers,0,draft,\n" .
      "Discount Offer,discount,Special discount for you!,Discount email content...,premium,15.00,draft,";
  }

  /**
   * Escape CSV field to handle commas and quotes
   */
  private function escapeCsvField(string $field): string
  {
    if (strpos($field, ',') !== false || strpos($field, '"') !== false || strpos($field, "\n") !== false) {
      return '"' . str_replace('"', '""', $field) . '"';
    }
    return $field;
  }

  /**
   * Validate CSV file
   */
  public function validateCsvFile(string $filePath): array
  {
    $errors = [];

    if (!file_exists($filePath)) {
      $errors[] = "File not found";
      return $errors;
    }

    $fileSize = filesize($filePath);
    if ($fileSize > 10 * 1024 * 1024) { // 10MB limit
      $errors[] = "File size exceeds 10MB limit";
    }

    $content = file_get_contents($filePath);
    if (empty($content)) {
      $errors[] = "File is empty";
    }

    $lines = explode("\n", $content);
    if (count($lines) < 2) {
      $errors[] = "File must contain at least a header row and one data row";
    }

    return $errors;
  }
}
