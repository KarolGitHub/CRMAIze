<?php

require_once __DIR__ . '/../vendor/autoload.php';

// Load environment variables
$dotenv = Dotenv\Dotenv::createImmutable(__DIR__ . '/..');
$dotenv->load();

echo "CRMAIze Scheduled Campaign Processor\n";
echo "===================================\n\n";

try {
  // Initialize services
  $database = new CRMAIze\Service\DatabaseService();
  $campaignRepo = new CRMAIze\Repository\CampaignRepository($database);
  $customerRepo = new CRMAIze\Repository\CustomerRepository($database);
  $emailService = new CRMAIze\Service\EmailService();
  $scheduler = new CRMAIze\Service\CampaignScheduler($campaignRepo, $customerRepo, $emailService);

  echo "Processing scheduled campaigns...\n";
  $results = $scheduler->processScheduledCampaigns();

  if (empty($results)) {
    echo "No campaigns scheduled for sending at this time.\n";
  } else {
    echo "Processed " . count($results) . " campaigns:\n\n";

    foreach ($results as $result) {
      echo "Campaign: {$result['campaign_name']} (ID: {$result['campaign_id']})\n";

      if ($result['result']['success']) {
        echo "✅ Status: {$result['result']['message']}\n";
        echo "   Sent: {$result['result']['sent_count']}, Failed: {$result['result']['failed_count']}\n";

        if (!empty($result['result']['errors'])) {
          echo "   Errors:\n";
          foreach ($result['result']['errors'] as $error) {
            echo "   - $error\n";
          }
        }
      } else {
        echo "❌ Status: {$result['result']['message']}\n";
      }
      echo "\n";
    }
  }

  // Show upcoming scheduled campaigns
  echo "Upcoming scheduled campaigns:\n";
  $upcoming = $scheduler->getUpcomingScheduledCampaigns();

  if (empty($upcoming)) {
    echo "No upcoming scheduled campaigns.\n";
  } else {
    foreach ($upcoming as $campaign) {
      $scheduledAt = new DateTime($campaign['scheduled_at']);
      $now = new DateTime();
      $timeUntil = $now->diff($scheduledAt);

      echo "- {$campaign['name']} (ID: {$campaign['id']})\n";
      echo "  Scheduled: {$campaign['scheduled_at']} ({$timeUntil->format('%h hours, %i minutes')} from now)\n";
      echo "  Target: {$campaign['target_segment']}\n\n";
    }
  }
} catch (Exception $e) {
  echo "Error: " . $e->getMessage() . "\n";
  exit(1);
}
