<?php

require_once __DIR__ . '/../vendor/autoload.php';

// Load environment variables
$dotenv = Dotenv\Dotenv::createImmutable(__DIR__ . '/..');
$dotenv->load();

echo "CRMAIze Email Test Script\n";
echo "========================\n\n";

try {
  // Initialize email service
  $emailService = new CRMAIze\Service\EmailService();

  // Check configuration
  $config = $emailService->getConfigurationStatus();

  echo "Email Configuration Status:\n";
  echo "---------------------------\n";
  echo "Configured: " . ($config['configured'] ? 'Yes' : 'No') . "\n";
  echo "SMTP Host: " . $config['smtp_host'] . "\n";
  echo "SMTP Port: " . $config['smtp_port'] . "\n";
  echo "SMTP Username: " . $config['smtp_username'] . "\n";
  echo "SMTP Encryption: " . $config['smtp_encryption'] . "\n";
  echo "From Address: " . $config['from_address'] . "\n";
  echo "From Name: " . $config['from_name'] . "\n\n";

  if (!$config['configured']) {
    echo "❌ Email service is not configured.\n";
    echo "Please add SMTP settings to your .env file:\n\n";
    echo "SMTP_HOST=smtp.gmail.com\n";
    echo "SMTP_PORT=587\n";
    echo "SMTP_USERNAME=your-email@gmail.com\n";
    echo "SMTP_PASSWORD=your-app-password\n";
    echo "SMTP_ENCRYPTION=tls\n";
    echo "MAIL_FROM_ADDRESS=your-email@gmail.com\n";
    echo "MAIL_FROM_NAME=CRMAIze\n\n";
    exit(1);
  }

  // Test connection
  echo "Testing SMTP connection...\n";
  $testResult = $emailService->testConnection();

  if ($testResult['success']) {
    echo "✅ " . $testResult['message'] . "\n\n";

    // Test sending a sample email
    echo "Testing email sending...\n";
    $testEmail = $_ENV['MAIL_FROM_ADDRESS'] ?? $config['smtp_username'];

    $success = $emailService->sendEmail(
      $testEmail,
      'CRMAIze Email Test',
      '<h2>Email Test Successful!</h2><p>Your CRMAIze email configuration is working correctly.</p>',
      'CRMAIze Test'
    );

    if ($success) {
      echo "✅ Test email sent successfully to: $testEmail\n";
    } else {
      echo "❌ Failed to send test email\n";
    }
  } else {
    echo "❌ " . $testResult['message'] . "\n";
  }
} catch (Exception $e) {
  echo "Error: " . $e->getMessage() . "\n";
  exit(1);
}
