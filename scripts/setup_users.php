<?php

require_once __DIR__ . '/../vendor/autoload.php';

// Load environment variables
$dotenv = Dotenv\Dotenv::createImmutable(__DIR__ . '/..');
$dotenv->load();

echo "CRMAIze User Setup Script\n";
echo "=========================\n\n";

try {
  // Initialize services
  $database = new CRMAIze\Service\DatabaseService();
  $userRepository = new CRMAIze\Repository\UserRepository($database);
  $authService = new CRMAIze\Service\AuthService($userRepository);

  // Check if users already exist
  $existingUsers = $userRepository->getAll();
  if (!empty($existingUsers)) {
    echo "Users already exist in the database.\n";
    echo "Existing users:\n";
    foreach ($existingUsers as $user) {
      echo "- {$user->getUsername()} ({$user->getRole()})\n";
    }
    echo "\n";
    return;
  }

  // Create demo users
  $demoUsers = [
    [
      'username' => 'admin',
      'email' => 'admin@crmaize.com',
      'password' => 'admin123',
      'role' => 'admin'
    ],
    [
      'username' => 'marketer',
      'email' => 'marketer@crmaize.com',
      'password' => 'marketer123',
      'role' => 'marketer'
    ]
  ];

  echo "Creating demo users...\n\n";

  foreach ($demoUsers as $userData) {
    $userId = $authService->createUser($userData);
    echo "✓ Created user: {$userData['username']} ({$userData['role']})\n";
  }

  echo "\nDemo users created successfully!\n";
  echo "You can now log in with:\n";
  echo "- Admin: admin / admin123\n";
  echo "- Marketer: marketer / marketer123\n\n";
} catch (Exception $e) {
  echo "Error: " . $e->getMessage() . "\n";
  exit(1);
}
