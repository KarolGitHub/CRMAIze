<?php

namespace CRMAIze\Service;

use CRMAIze\Repository\CampaignRepository;
use CRMAIze\Repository\CustomerRepository;
use DateTime;
use DateTimeZone;

class CampaignScheduler
{
  private $campaignRepo;
  private $customerRepo;
  private $emailService;

  public function __construct(CampaignRepository $campaignRepo, CustomerRepository $customerRepo, EmailService $emailService)
  {
    $this->campaignRepo = $campaignRepo;
    $this->customerRepo = $customerRepo;
    $this->emailService = $emailService;
  }

  public function scheduleCampaign(int $campaignId, string $scheduleType, ?string $scheduledAt = null, ?string $timezone = 'UTC'): bool
  {
    $campaign = $this->campaignRepo->getById($campaignId);
    if (!$campaign) {
      return false;
    }

    $data = [
      'campaign_id' => $campaignId,
      'schedule_type' => $scheduleType,
      'timezone' => $timezone,
      'is_active' => 1
    ];

    if ($scheduleType === 'scheduled' && $scheduledAt) {
      $data['scheduled_at'] = $scheduledAt;
    }

    // Update campaign status
    $this->campaignRepo->updateStatus($campaignId, 'scheduled');
    if ($scheduledAt) {
      $this->campaignRepo->updateScheduledAt($campaignId, $scheduledAt);
    }

    return $this->campaignRepo->createSchedule($data);
  }

  public function processScheduledCampaigns(): array
  {
    $results = [];
    $now = new DateTime('now', new DateTimeZone('UTC'));

    // Get campaigns scheduled to be sent now
    $scheduledCampaigns = $this->campaignRepo->getScheduledCampaigns($now->format('Y-m-d H:i:s'));

    foreach ($scheduledCampaigns as $campaign) {
      $result = $this->sendCampaign($campaign['id']);
      $results[] = [
        'campaign_id' => $campaign['id'],
        'campaign_name' => $campaign['name'],
        'result' => $result
      ];
    }

    return $results;
  }

  private function sendCampaign(int $campaignId): array
  {
    $campaign = $this->campaignRepo->getById($campaignId);
    if (!$campaign) {
      return ['success' => false, 'message' => 'Campaign not found'];
    }

    // Check if email service is configured
    if (!$this->emailService->isConfigured()) {
      return ['success' => false, 'message' => 'Email service not configured'];
    }

    // Get target customers
    $customers = [];
    if ($campaign['target_segment']) {
      $customers = $this->customerRepo->getBySegment($campaign['target_segment']);
    }

    $sentCount = 0;
    $failedCount = 0;
    $errors = [];

    foreach ($customers as $customer) {
      try {
        $success = $this->emailService->sendCampaignEmail(
          $customer['email'],
          $campaign['subject_line'],
          $campaign['email_content'],
          $customer
        );

        if ($success) {
          $sentCount++;
          $this->logCampaignSend($campaignId, $customer['id']);
        } else {
          $failedCount++;
          $errors[] = "Failed to send to: " . $customer['email'];
        }
      } catch (\Exception $e) {
        $failedCount++;
        $errors[] = "Error sending to " . $customer['email'] . ": " . $e->getMessage();
      }
    }

    // Update campaign status
    $this->campaignRepo->updateStatus($campaignId, 'sent');
    $this->campaignRepo->updateSentAt($campaignId, date('Y-m-d H:i:s'));

    for ($i = 0; $i < $sentCount; $i++) {
      $this->campaignRepo->incrementSentCount($campaignId);
    }

    return [
      'success' => true,
      'sent_count' => $sentCount,
      'failed_count' => $failedCount,
      'message' => "Campaign completed. Sent: $sentCount, Failed: $failedCount",
      'errors' => $errors
    ];
  }

  private function logCampaignSend(int $campaignId, int $customerId): void
  {
    $this->campaignRepo->logCampaignSend($campaignId, $customerId);
  }

  public function cancelScheduledCampaign(int $campaignId): bool
  {
    $campaign = $this->campaignRepo->getById($campaignId);
    if (!$campaign || $campaign['status'] !== 'scheduled') {
      return false;
    }

    // Update campaign status
    $this->campaignRepo->updateStatus($campaignId, 'cancelled');

    // Deactivate schedule
    return $this->campaignRepo->deactivateSchedule($campaignId);
  }

  public function getUpcomingScheduledCampaigns(): array
  {
    return $this->campaignRepo->getUpcomingScheduledCampaigns();
  }

  public function validateSchedule(string $scheduleType, ?string $scheduledAt = null): array
  {
    $errors = [];

    if ($scheduleType === 'scheduled' && !$scheduledAt) {
      $errors[] = 'Scheduled date/time is required for scheduled campaigns';
    }

    if ($scheduledAt) {
      try {
        $scheduledDateTime = new DateTime($scheduledAt);
        $now = new DateTime();

        if ($scheduledDateTime <= $now) {
          $errors[] = 'Scheduled date/time must be in the future';
        }
      } catch (\Exception $e) {
        $errors[] = 'Invalid date/time format';
      }
    }

    return [
      'valid' => empty($errors),
      'errors' => $errors
    ];
  }
}
