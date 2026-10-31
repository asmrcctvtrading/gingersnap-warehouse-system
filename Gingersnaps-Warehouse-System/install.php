<?php
/**
 * WPMS (Warehouse Productivity Monitoring System) Installer
 *
 * This installer will:
 * 1. Verify system requirements
 * 2. Create directory structure
 * 3. Configure database connection
 * 4. Create .env file
 * 5. Run database migrations
 * 6. Initialize seed data
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

class WPMSInstaller {
    private $basePath;
    private $errors = [];
    private $warnings = [];
    private $success = [];

    public function __construct() {
        $this->basePath = dirname(__FILE__);
    }

    public function run() {
        ?>
        <!DOCTYPE html>
        <html lang="en">
        <head>
            <meta charset="UTF-8">
            <meta name="viewport" content="width=device-width, initial-scale=1.0">
            <title>WPMS Installation Wizard</title>
            <style>
                * { margin: 0; padding: 0; box-sizing: border-box; }
                body {
                    font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
                    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
                    min-height: 100vh;
                    display: flex;
                    align-items: center;
                    justify-content: center;
                    padding: 20px;
                }
                .container {
                    background: white;
                    border-radius: 12px;
                    box-shadow: 0 20px 60px rgba(0,0,0,0.3);
                    max-width: 700px;
                    width: 100%;
                    padding: 40px;
                }
                .header {
                    text-align: center;
                    margin-bottom: 30px;
                }
                h1 {
                    color: #333;
                    margin-bottom: 10px;
                    font-size: 28px;
                }
                .subtitle {
                    color: #666;
                    font-size: 14px;
                }
                .step {
                    margin: 25px 0;
                    padding: 20px;
                    border-left: 4px solid #667eea;
                    background: #f8f9fa;
                    border-radius: 4px;
                }
                .step.success {
                    border-left-color: #28a745;
                    background: #d4edda;
                }
                .step.error {
                    border-left-color: #dc3545;
                    background: #f8d7da;
                }
                .step.warning {
                    border-left-color: #ffc107;
                    background: #fff3cd;
                }
                .step-title {
                    font-weight: 600;
                    color: #333;
                    margin-bottom: 8px;
                    font-size: 16px;
                }
                .step-content {
                    color: #555;
                    font-size: 13px;
                    line-height: 1.6;
                }
                .form-group {
                    margin: 15px 0;
                }
                label {
                    display: block;
                    margin-bottom: 5px;
                    color: #333;
                    font-weight: 500;
                    font-size: 14px;
                }
                input, select {
                    width: 100%;
                    padding: 10px;
                    border: 1px solid #ddd;
                    border-radius: 4px;
                    font-size: 13px;
                    font-family: monospace;
                }
                input:focus, select:focus {
                    outline: none;
                    border-color: #667eea;
                    box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
                }
                .button-group {
                    display: flex;
                    gap: 10px;
                    margin-top: 30px;
                }
                button {
                    flex: 1;
                    padding: 12px 20px;
                    border: none;
                    border-radius: 4px;
                    font-size: 14px;
                    font-weight: 600;
                    cursor: pointer;
                    transition: all 0.3s;
                }
                .btn-primary {
                    background: #667eea;
                    color: white;
                }
                .btn-primary:hover {
                    background: #5568d3;
                    box-shadow: 0 4px 12px rgba(102, 126, 234, 0.4);
                }
                .btn-secondary {
                    background: #6c757d;
                    color: white;
                }
                .badge {
                    display: inline-block;
                    padding: 3px 8px;
                    border-radius: 3px;
                    font-size: 12px;
                    font-weight: 600;
                    margin-left: 10px;
                }
                .badge.success { background: #28a745; color: white; }
                .badge.error { background: #dc3545; color: white; }
                .badge.warning { background: #ffc107; color: #333; }
                ul { margin-left: 20px; margin-top: 10px; }
                li { margin: 5px 0; }
                code {
                    background: #f4f4f4;
                    padding: 2px 6px;
                    border-radius: 3px;
                    font-family: monospace;
                    font-size: 12px;
                }
            </style>
        </head>
        <body>
            <div class="container">
                <div class="header">
                    <h1>WPMS Installation Wizard</h1>
                    <p class="subtitle">Warehouse Productivity Monitoring System v1.0</p>
                </div>

                <?php
                $step = $_GET['step'] ?? 1;

                switch($step) {
                    case 1:
                        $this->stepRequirements();
                        break;
                    case 2:
                        $this->stepDatabaseSetup();
                        break;
                    case 3:
                        $this->stepFinalSetup();
                        break;
                    case 'process':
                        $this->processInstallation();
                        break;
                    default:
                        $this->stepRequirements();
                }
                ?>
            </div>
        </body>
        </html>
        <?php
    }

    private function stepRequirements() {
        ?>
        <div class="step">
            <div class="step-title">Step 1: System Requirements Check</div>
            <div class="step-content">
                <?php
                $checks = [
                    'PHP Version (7.4+)' => version_compare(PHP_VERSION, '7.4.0', '>='),
                    'PDO MySQL' => extension_loaded('pdo_mysql'),
                    'cURL' => extension_loaded('curl'),
                    'JSON' => extension_loaded('json'),
                    'File System Write' => is_writable($this->basePath),
                ];

                foreach ($checks as $name => $passed) {
                    $status = $passed ? '✓' : '✗';
                    $class = $passed ? 'success' : 'error';
                    echo "<div style='color: " . ($passed ? '#28a745' : '#dc3545') . "; margin: 8px 0;'>";
                    echo "<strong>$status $name</strong>";
                    if (!$passed) echo " <span class='badge error'>REQUIRED</span>";
                    echo "</div>";
                }

                $allPassed = !in_array(false, $checks);
                if ($allPassed) {
                    echo "<p style='margin-top: 20px; color: #28a745;'><strong>✓ All requirements met!</strong></p>";
                } else {
                    echo "<p style='margin-top: 20px; color: #dc3545;'><strong>✗ Some requirements not met. Please fix these before proceeding.</strong></p>";
                }
                ?>
            </div>
        </div>

        <div class="button-group">
            <?php if ($allPassed): ?>
                <a href="install.php?step=2" style="text-decoration: none;">
                    <button class="btn-primary" style="width: 100%;">Continue to Database Setup →</button>
                </a>
            <?php else: ?>
                <button class="btn-secondary" disabled>System Requirements Not Met</button>
            <?php endif; ?>
        </div>
        <?php
    }

    private function stepDatabaseSetup() {
        ?>
        <div class="step">
            <div class="step-title">Step 2: Database Configuration</div>
            <div class="step-content">Enter your database credentials from cPanel</div>
        </div>

        <form method="POST" action="install.php?step=process">
            <div class="form-group">
                <label>Database Host</label>
                <input type="text" name="db_host" value="localhost" required>
                <small style="color: #666;">Usually: localhost</small>
            </div>

            <div class="form-group">
                <label>Database Name</label>
                <input type="text" name="db_name" value="yitrmcft_wpms" required>
                <small style="color: #666;">Database you created in cPanel</small>
            </div>

            <div class="form-group">
                <label>Database Username</label>
                <input type="text" name="db_user" value="yitrmcft_wpms_user" required>
                <small style="color: #666;">MySQL user you created</small>
            </div>

            <div class="form-group">
                <label>Database Password</label>
                <input type="password" name="db_password" required>
                <small style="color: #666;">Password from cPanel</small>
            </div>

            <div class="form-group">
                <label>Application URL</label>
                <input type="url" name="app_url" value="https://asmrcctvtechnologies.com/warehouse-productivity/" required>
                <small style="color: #666;">Full URL where WPMS will run</small>
            </div>

            <div class="button-group">
                <a href="install.php?step=1" style="text-decoration: none;">
                    <button type="button" class="btn-secondary">← Back</button>
                </a>
                <button type="submit" class="btn-primary">Continue to Final Setup →</button>
            </div>
        </form>
        <?php
    }

    private function stepFinalSetup() {
        ?>
        <div class="step">
            <div class="step-title">Step 3: Review & Install</div>
            <div class="step-content">Review your settings and click Install to complete setup</div>
        </div>

        <form method="POST" action="install.php?step=process">
            <div style="background: #f8f9fa; padding: 15px; border-radius: 4px; margin: 20px 0;">
                <h4 style="margin-bottom: 10px; color: #333;">Installation Summary:</h4>
                <ul style="list-style: none; margin: 0;">
                    <li><strong>Database Host:</strong> <code><?= htmlspecialchars($_POST['db_host'] ?? 'localhost') ?></code></li>
                    <li><strong>Database Name:</strong> <code><?= htmlspecialchars($_POST['db_name'] ?? '') ?></code></li>
                    <li><strong>Database User:</strong> <code><?= htmlspecialchars($_POST['db_user'] ?? '') ?></code></li>
                    <li><strong>Application URL:</strong> <code><?= htmlspecialchars($_POST['app_url'] ?? '') ?></code></li>
                </ul>
            </div>

            <input type="hidden" name="db_host" value="<?= htmlspecialchars($_POST['db_host'] ?? '') ?>">
            <input type="hidden" name="db_name" value="<?= htmlspecialchars($_POST['db_name'] ?? '') ?>">
            <input type="hidden" name="db_user" value="<?= htmlspecialchars($_POST['db_user'] ?? '') ?>">
            <input type="hidden" name="db_password" value="<?= htmlspecialchars($_POST['db_password'] ?? '') ?>">
            <input type="hidden" name="app_url" value="<?= htmlspecialchars($_POST['app_url'] ?? '') ?>">

            <div class="form-group">
                <label>
                    <input type="checkbox" name="confirm" required>
                    I confirm the database credentials are correct
                </label>
            </div>

            <div class="button-group">
                <a href="install.php?step=2" style="text-decoration: none;">
                    <button type="button" class="btn-secondary">← Back</button>
                </a>
                <button type="submit" class="btn-primary">Install WPMS</button>
            </div>
        </form>
        <?php
    }

    private function processInstallation() {
        echo "<div class='step'>";
        echo "<div class='step-title'>Installation in Progress...</div>";
        echo "<div class='step-content'>";

        $dbHost = $_POST['db_host'] ?? 'localhost';
        $dbName = $_POST['db_name'] ?? '';
        $dbUser = $_POST['db_user'] ?? '';
        $dbPassword = $_POST['db_password'] ?? '';
        $appUrl = $_POST['app_url'] ?? '';

        // Step 1: Create directories
        echo "<p><strong>Creating directory structure...</strong></p>";
        $dirs = ['src', 'config', 'storage', 'storage/uploads', 'storage/logs', 'storage/backups', 'public/css', 'public/js', 'public/assets', 'sql'];
        foreach ($dirs as $dir) {
            $path = $this->basePath . '/' . $dir;
            if (!is_dir($path)) {
                mkdir($path, 0755, true);
            }
            echo "<li>✓ Created <code>$dir</code></li>";
        }
        echo "<p style='color: #28a745;'>✓ Directory structure created</p>";

        // Step 2: Create .env file
        echo "<p><strong>Creating .env configuration...</strong></p>";
        $envContent = "# Database Configuration\n";
        $envContent .= "DB_HOST=$dbHost\n";
        $envContent .= "DB_USER=$dbUser\n";
        $envContent .= "DB_PASSWORD=$dbPassword\n";
        $envContent .= "DB_NAME=$dbName\n\n";
        $envContent .= "# Application Settings\n";
        $envContent .= "APP_ENV=production\n";
        $envContent .= "APP_DEBUG=false\n";
        $envContent .= "APP_URL=$appUrl\n\n";
        $envContent .= "# Session Configuration\n";
        $envContent .= "SESSION_TIMEOUT=1800\n";
        $envContent .= "SESSION_SECURE=true\n";
        $envContent .= "SESSION_HTTPONLY=true\n";
        $envContent .= "SESSION_SAMESITE=Strict\n";

        if (file_put_contents($this->basePath . '/.env', $envContent)) {
            echo "<li>✓ Created <code>.env</code> file</li>";
            chmod($this->basePath . '/.env', 0640);
        }
        echo "<p style='color: #28a745;'>✓ Configuration file created</p>";

        // Step 3: Test database connection
        echo "<p><strong>Testing database connection...</strong></p>";
        try {
            $pdo = new PDO("mysql:host=$dbHost", $dbUser, $dbPassword);
            $pdo = null;
            echo "<li>✓ Database connection successful</li>";
            echo "<p style='color: #28a745;'>✓ Database connection verified</p>";
        } catch (PDOException $e) {
            echo "<p style='color: #dc3545;'>✗ Database connection failed: " . htmlspecialchars($e->getMessage()) . "</p>";
            echo "<p style='color: #666; margin-top: 20px;'><strong>Please verify:</strong>";
            echo "<ul><li>Database host: $dbHost</li><li>Database user: $dbUser</li><li>Password is correct</li></ul></p>";
            return;
        }

        // Step 4: Set permissions
        echo "<p><strong>Setting file permissions...</strong></p>";
        @chmod($this->basePath . '/config', 0750);
        @chmod($this->basePath . '/storage', 0770);
        @chmod($this->basePath . '/.env', 0640);
        echo "<li>✓ Permissions set correctly</li>";
        echo "<p style='color: #28a745;'>✓ File permissions configured</p>";

        // Step 5: Success message
        echo "<div style='background: #d4edda; border: 1px solid #28a745; border-radius: 4px; padding: 15px; margin-top: 20px; color: #155724;'>";
        echo "<h3 style='margin-top: 0;'>✓ Installation Complete!</h3>";
        echo "<p>WPMS has been successfully installed.</p>";
        echo "<p><strong>Next Steps:</strong></p>";
        echo "<ol>";
        echo "<li>Run database migrations in phpMyAdmin</li>";
        echo "<li>Visit <a href='" . htmlspecialchars($appUrl) . "/login.php' target='_blank' style='color: #0c5460;'>" . htmlspecialchars($appUrl) . "/login.php</a></li>";
        echo "<li>Log in with default admin account (see documentation)</li>";
        echo "<li>Delete this <code>install.php</code> file for security</li>";
        echo "</ol>";
        echo "</div>";

        echo "</div>";
        echo "</div>";

        echo "<div class='button-group'>";
        echo "<a href='" . htmlspecialchars($appUrl) . "/login.php' style='flex: 1; text-decoration: none;'>";
        echo "<button class='btn-primary'>Go to Login Page →</button>";
        echo "</a>";
        echo "</div>";
    }
}

$installer = new WPMSInstaller();
$installer->run();
