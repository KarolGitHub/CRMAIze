<?php

/**
 * CRMAIze Web Installer
 * For traditional hosting platforms
 */

// Check if already installed
if (file_exists('../data/crmaize.db') && filesize('../data/crmaize.db') > 0) {
  header('Location: /');
  exit('Installation already completed. <a href="/">Go to CRMAIze</a>');
}

$step = $_GET['step'] ?? 1;
$errors = [];
$success = [];

// Handle form submissions
if ($_POST) {
  switch ($step) {
    case 2:
      // Environment configuration
      $envContent = "APP_ENV=production\n";
      $envContent .= "APP_DEBUG=" . ($_POST['debug'] ?? 'false') . "\n";
      $envContent .= "DB_DSN=" . ($_POST['db_dsn'] ?? 'sqlite:../data/crmaize.db') . "\n";
      $envContent .= "DB_USERNAME=" . ($_POST['db_username'] ?? '') . "\n";
      $envContent .= "DB_PASSWORD=" . ($_POST['db_password'] ?? '') . "\n\n";

      $envContent .= "# Email Configuration\n";
      $envContent .= "SMTP_HOST=" . ($_POST['smtp_host'] ?? '') . "\n";
      $envContent .= "SMTP_PORT=" . ($_POST['smtp_port'] ?? '587') . "\n";
      $envContent .= "SMTP_USERNAME=" . ($_POST['smtp_username'] ?? '') . "\n";
      $envContent .= "SMTP_PASSWORD=" . ($_POST['smtp_password'] ?? '') . "\n";
      $envContent .= "SMTP_ENCRYPTION=" . ($_POST['smtp_encryption'] ?? 'tls') . "\n";
      $envContent .= "MAIL_FROM_ADDRESS=" . ($_POST['mail_from_address'] ?? '') . "\n";
      $envContent .= "MAIL_FROM_NAME=" . ($_POST['mail_from_name'] ?? 'CRMAIze') . "\n";

      if (file_put_contents('../.env', $envContent)) {
        $success[] = "Environment configuration saved";
        $step = 3;
      } else {
        $errors[] = "Could not write .env file. Check permissions.";
      }
      break;

    case 3:
      // Database setup
      try {
        require_once '../vendor/autoload.php';
        $dotenv = Dotenv\Dotenv::createImmutable(__DIR__ . '/..');
        $dotenv->load();

        $database = new \CRMAIze\Service\DatabaseService();
        $database->createTables();
        $success[] = "Database tables created successfully";

        // Create demo users
        $userRepo = new \CRMAIze\Repository\UserRepository($database);
        $authService = new \CRMAIze\Service\AuthService($userRepo);

        $authService->createUser('admin', 'admin123', 'admin@crmaize.com', 'admin');
        $authService->createUser('marketer', 'marketer123', 'marketer@crmaize.com', 'marketer');
        $success[] = "Demo users created";

        $step = 4;
      } catch (Exception $e) {
        $errors[] = "Database setup failed: " . $e->getMessage();
      }
      break;
  }
}

?>
<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>CRMAIze Installation</title>
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/foundation-sites@6.7.5/dist/css/foundation.min.css">
  <style>
    body {
      background: #f8f9fa;
      padding: 2rem 0;
    }

    .install-container {
      max-width: 600px;
      margin: 0 auto;
      background: white;
      padding: 2rem;
      border-radius: 8px;
      box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
    }

    .step-indicator {
      display: flex;
      justify-content: space-between;
      margin-bottom: 2rem;
    }

    .step {
      padding: 0.5rem 1rem;
      border-radius: 20px;
      background: #e6e6e6;
      color: #666;
    }

    .step.active {
      background: #1779ba;
      color: white;
    }

    .step.completed {
      background: #28a745;
      color: white;
    }

    .form-group {
      margin-bottom: 1rem;
    }

    .alert {
      padding: 1rem;
      border-radius: 4px;
      margin-bottom: 1rem;
    }

    .alert-success {
      background: #d4edda;
      color: #155724;
      border: 1px solid #c3e6cb;
    }

    .alert-error {
      background: #f8d7da;
      color: #721c24;
      border: 1px solid #f5c6cb;
    }
  </style>
</head>

<body>
  <div class="install-container">
    <h1>🚀 CRMAIze Installation</h1>

    <div class="step-indicator">
      <div class="step <?= $step >= 1 ? ($step == 1 ? 'active' : 'completed') : '' ?>">1. Welcome</div>
      <div class="step <?= $step >= 2 ? ($step == 2 ? 'active' : 'completed') : '' ?>">2. Configure</div>
      <div class="step <?= $step >= 3 ? ($step == 3 ? 'active' : 'completed') : '' ?>">3. Database</div>
      <div class="step <?= $step >= 4 ? 'active' : '' ?>">4. Complete</div>
    </div>

    <?php foreach ($errors as $error): ?>
      <div class="alert alert-error"><?= htmlspecialchars($error) ?></div>
    <?php endforeach; ?>

    <?php foreach ($success as $msg): ?>
      <div class="alert alert-success"><?= htmlspecialchars($msg) ?></div>
    <?php endforeach; ?>

    <?php if ($step == 1): ?>
      <h2>Welcome to CRMAIze!</h2>
      <p>This installer will help you set up CRMAIze on your hosting platform.</p>

      <h3>System Requirements Check</h3>
      <ul>
        <li>PHP Version: <?= PHP_VERSION ?> <?= version_compare(PHP_VERSION, '7.4.0') >= 0 ? '✅' : '❌ (7.4+ required)' ?></li>
        <li>PDO Extension: <?= extension_loaded('pdo') ? '✅' : '❌' ?></li>
        <li>SQLite Extension: <?= extension_loaded('pdo_sqlite') ? '✅' : '❌' ?></li>
        <li>JSON Extension: <?= extension_loaded('json') ? '✅' : '❌' ?></li>
        <li>Mbstring Extension: <?= extension_loaded('mbstring') ? '✅' : '❌' ?></li>
        <li>Data Directory Writable: <?= is_writable('../data') ? '✅' : '❌' ?></li>
        <li>Cache Directory Writable: <?= is_writable('../cache') ? '✅' : '❌' ?></li>
      </ul>

      <a href="?step=2" class="button primary">Continue Installation</a>

    <?php elseif ($step == 2): ?>
      <h2>Configuration</h2>
      <form method="POST">
        <h3>Database Settings</h3>
        <div class="form-group">
          <label>Database Type:</label>
          <select name="db_type" onchange="toggleDbFields(this.value)">
            <option value="sqlite">SQLite (Recommended)</option>
            <option value="mysql">MySQL</option>
          </select>
        </div>

        <div id="sqlite-fields">
          <input type="hidden" name="db_dsn" value="sqlite:../data/crmaize.db">
        </div>

        <div id="mysql-fields" style="display: none;">
          <div class="form-group">
            <label>Database Host:</label>
            <input type="text" name="mysql_host" value="localhost">
          </div>
          <div class="form-group">
            <label>Database Name:</label>
            <input type="text" name="mysql_dbname" placeholder="crmaize">
          </div>
          <div class="form-group">
            <label>Username:</label>
            <input type="text" name="db_username">
          </div>
          <div class="form-group">
            <label>Password:</label>
            <input type="password" name="db_password">
          </div>
        </div>

        <h3>Email Settings (Optional)</h3>
        <div class="form-group">
          <label>SMTP Host:</label>
          <input type="text" name="smtp_host" placeholder="smtp.gmail.com">
        </div>
        <div class="form-group">
          <label>SMTP Port:</label>
          <input type="number" name="smtp_port" value="587">
        </div>
        <div class="form-group">
          <label>SMTP Username:</label>
          <input type="email" name="smtp_username">
        </div>
        <div class="form-group">
          <label>SMTP Password:</label>
          <input type="password" name="smtp_password">
        </div>
        <div class="form-group">
          <label>From Email:</label>
          <input type="email" name="mail_from_address">
        </div>
        <div class="form-group">
          <label>From Name:</label>
          <input type="text" name="mail_from_name" value="CRMAIze">
        </div>

        <button type="submit" class="button primary">Save Configuration</button>
      </form>

    <?php elseif ($step == 3): ?>
      <h2>Database Setup</h2>
      <p>Click the button below to create the database tables and demo users.</p>

      <form method="POST">
        <button type="submit" class="button primary">Setup Database</button>
      </form>

    <?php elseif ($step == 4): ?>
      <h2>🎉 Installation Complete!</h2>
      <p>CRMAIze has been successfully installed and configured.</p>

      <h3>Demo Login Credentials:</h3>
      <ul>
        <li><strong>Admin:</strong> admin / admin123</li>
        <li><strong>Marketer:</strong> marketer / marketer123</li>
      </ul>

      <h3>Next Steps:</h3>
      <ol>
        <li>Delete this installer file (install.php) for security</li>
        <li>Import sample data if desired</li>
        <li>Configure your email settings</li>
        <li>Start using CRMAIze!</li>
      </ol>

      <a href="/" class="button success large">Launch CRMAIze</a>

    <?php endif; ?>
  </div>

  <script>
    function toggleDbFields(type) {
      const sqliteFields = document.getElementById('sqlite-fields');
      const mysqlFields = document.getElementById('mysql-fields');
      const dbDsnInput = document.querySelector('input[name="db_dsn"]');

      if (type === 'mysql') {
        sqliteFields.style.display = 'none';
        mysqlFields.style.display = 'block';

        // Update DSN when MySQL fields change
        function updateMysqlDsn() {
          const host = document.querySelector('input[name="mysql_host"]').value || 'localhost';
          const dbname = document.querySelector('input[name="mysql_dbname"]').value || 'crmaize';
          dbDsnInput.value = `mysql:host=${host};dbname=${dbname};charset=utf8mb4`;
        }

        document.querySelector('input[name="mysql_host"]').addEventListener('input', updateMysqlDsn);
        document.querySelector('input[name="mysql_dbname"]').addEventListener('input', updateMysqlDsn);
        updateMysqlDsn();
      } else {
        sqliteFields.style.display = 'block';
        mysqlFields.style.display = 'none';
        dbDsnInput.value = 'sqlite:../data/crmaize.db';
      }
    }
  </script>
</body>

</html>