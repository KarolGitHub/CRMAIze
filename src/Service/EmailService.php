<?php

namespace CRMAIze\Service;

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;

class EmailService
{
  private $mailer;
  private $isConfigured = false;

  public function __construct()
  {
    $this->initializeMailer();
  }

  private function initializeMailer(): void
  {
    $this->mailer = new PHPMailer(true);

    // Check if SMTP is configured
    $smtpHost = $_ENV['SMTP_HOST'] ?? null;
    $smtpPort = $_ENV['SMTP_PORT'] ?? null;
    $smtpUsername = $_ENV['SMTP_USERNAME'] ?? null;
    $smtpPassword = $_ENV['SMTP_PASSWORD'] ?? null;

    if ($smtpHost && $smtpPort && $smtpUsername && $smtpPassword) {
      try {
        // Server settings
        $this->mailer->isSMTP();
        $this->mailer->Host = $smtpHost;
        $this->mailer->SMTPAuth = true;
        $this->mailer->Username = $smtpUsername;
        $this->mailer->Password = $smtpPassword;
        $this->mailer->SMTPSecure = $_ENV['SMTP_ENCRYPTION'] ?? PHPMailer::ENCRYPTION_STARTTLS;
        $this->mailer->Port = (int) $smtpPort;

        // Set default sender
        $this->mailer->setFrom(
          $_ENV['MAIL_FROM_ADDRESS'] ?? $smtpUsername,
          $_ENV['MAIL_FROM_NAME'] ?? 'CRMAIze'
        );

        $this->isConfigured = true;
      } catch (Exception $e) {
        error_log("SMTP configuration failed: " . $e->getMessage());
        $this->isConfigured = false;
      }
    }
  }

  public function isConfigured(): bool
  {
    return $this->isConfigured;
  }

  public function sendEmail(string $to, string $subject, string $body, string $fromName = null): bool
  {
    if (!$this->isConfigured) {
      error_log("Email service not configured. Cannot send email to: $to");
      return false;
    }

    try {
      $this->mailer->clearAddresses();
      $this->mailer->addAddress($to);
      $this->mailer->Subject = $subject;
      $this->mailer->isHTML(true);
      $this->mailer->Body = $body;

      if ($fromName) {
        $this->mailer->setFrom(
          $_ENV['MAIL_FROM_ADDRESS'] ?? $this->mailer->Username,
          $fromName
        );
      }

      return $this->mailer->send();
    } catch (Exception $e) {
      error_log("Failed to send email to $to: " . $e->getMessage());
      return false;
    }
  }

  public function sendCampaignEmail(string $to, string $subject, string $body, array $customerData = []): bool
  {
    // Personalize email content
    $personalizedBody = $this->personalizeEmail($body, $customerData);
    $personalizedSubject = $this->personalizeSubject($subject, $customerData);

    return $this->sendEmail($to, $personalizedSubject, $personalizedBody);
  }

  private function personalizeEmail(string $body, array $customerData): string
  {
    $replacements = [
      '{{customer_name}}' => $customerData['first_name'] ?? 'Valued Customer',
      '{{customer_email}}' => $customerData['email'] ?? '',
      '{{customer_segment}}' => $customerData['segment'] ?? 'loyal',
      '{{total_spent}}' => number_format($customerData['total_spent'] ?? 0, 2),
      '{{order_count}}' => $customerData['order_count'] ?? 0,
    ];

    return str_replace(array_keys($replacements), array_values($replacements), $body);
  }

  private function personalizeSubject(string $subject, array $customerData): string
  {
    $replacements = [
      '{{customer_name}}' => $customerData['first_name'] ?? 'Valued Customer',
      '{{customer_segment}}' => $customerData['segment'] ?? 'loyal',
    ];

    return str_replace(array_keys($replacements), array_values($replacements), $subject);
  }

  public function testConnection(): array
  {
    if (!$this->isConfigured) {
      return [
        'success' => false,
        'message' => 'SMTP not configured. Please check your .env file.'
      ];
    }

    try {
      $this->mailer->smtpConnect();
      $this->mailer->smtpClose();

      return [
        'success' => true,
        'message' => 'SMTP connection successful!'
      ];
    } catch (Exception $e) {
      return [
        'success' => false,
        'message' => 'SMTP connection failed: ' . $e->getMessage()
      ];
    }
  }

  public function getConfigurationStatus(): array
  {
    return [
      'configured' => $this->isConfigured,
      'smtp_host' => $_ENV['SMTP_HOST'] ?? 'Not set',
      'smtp_port' => $_ENV['SMTP_PORT'] ?? 'Not set',
      'smtp_username' => $_ENV['SMTP_USERNAME'] ?? 'Not set',
      'smtp_encryption' => $_ENV['SMTP_ENCRYPTION'] ?? 'Not set',
      'from_address' => $_ENV['MAIL_FROM_ADDRESS'] ?? 'Not set',
      'from_name' => $_ENV['MAIL_FROM_NAME'] ?? 'Not set'
    ];
  }
}
