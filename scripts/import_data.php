<?php

require_once __DIR__ . '/../vendor/autoload.php';

use CRMAIze\Service\DatabaseService;
use CRMAIze\Service\AIService;
use CRMAIze\Repository\CustomerRepository;

// Load environment variables
$dotenv = Dotenv\Dotenv::createImmutable(__DIR__ . '/..');
$dotenv->load();

echo "CRMAIze Data Import Script\n";
echo "==========================\n\n";

try {
  // Initialize services
  $db = new DatabaseService();
  $aiService = new AIService();
  $customerRepo = new CustomerRepository($db);

  // Check if customers already exist
  $existingCustomers = $customerRepo->getTotalCount();
  if ($existingCustomers > 0) {
    echo "Database already contains {$existingCustomers} customers.\n";
    echo "Do you want to continue and add more customers? (y/n): ";
    $handle = fopen("php://stdin", "r");
    $line = fgets($handle);
    fclose($handle);

    if (trim(strtolower($line)) !== 'y') {
      echo "Import cancelled.\n";
      exit(0);
    }
  }

  // Load sample data
  $sampleData = json_decode(file_get_contents(__DIR__ . '/../data/customers.json'), true);

  if (!$sampleData) {
    throw new Exception("Failed to load sample data");
  }

  echo "Importing " . count($sampleData) . " customers...\n\n";

  $imported = 0;
  $skipped = 0;

  foreach ($sampleData as $customerData) {
    try {
      // Check if customer already exists
      $existing = $db->query("SELECT id FROM customers WHERE email = ?", [$customerData['email']]);

      if (!empty($existing)) {
        echo "Skipping {$customerData['email']} (already exists)\n";
        $skipped++;
        continue;
      }

      // Create customer
      $customerId = $customerRepo->create($customerData);

      // Calculate and update AI metrics
      $churnRisk = $aiService->calculateChurnRisk($customerData);
      $segments = $aiService->segmentCustomers([$customerData]);

      // Determine segment
      $segment = 'inactive'; // default
      foreach ($segments as $segmentName => $segmentCustomers) {
        if (!empty($segmentCustomers)) {
          $segment = $segmentName;
          break;
        }
      }

      // Update customer with AI data
      $customerRepo->updateChurnRisk($customerId, $churnRisk);
      $customerRepo->updateSegment($customerId, $segment);

      echo "Imported: {$customerData['first_name']} {$customerData['last_name']} ({$customerData['email']}) - {$segment} segment, " . round($churnRisk * 100, 1) . "% churn risk\n";
      $imported++;
    } catch (Exception $e) {
      echo "Error importing {$customerData['email']}: " . $e->getMessage() . "\n";
    }
  }

  echo "\nImport completed!\n";
  echo "Imported: {$imported} customers\n";
  echo "Skipped: {$skipped} customers (already existed)\n";

  // Show final statistics
  $totalCustomers = $customerRepo->getTotalCount();
  $totalRevenue = $customerRepo->getTotalRevenue();
  $segments = $customerRepo->getSegmentCounts();

  echo "\nFinal Statistics:\n";
  echo "Total customers: {$totalCustomers}\n";
  echo "Total revenue: $" . number_format($totalRevenue, 2) . "\n";
  echo "Average order value: $" . number_format($totalRevenue / $totalCustomers, 2) . "\n";

  echo "\nCustomer Segments:\n";
  foreach ($segments as $segment) {
    echo "- {$segment['segment']}: {$segment['count']} customers\n";
  }
} catch (Exception $e) {
  echo "Error: " . $e->getMessage() . "\n";
  exit(1);
}
