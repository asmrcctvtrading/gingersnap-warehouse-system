<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - WPMS Warehouse Productivity Monitoring System</title>

    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">

    <!-- Font Awesome -->
    <link href="https://cdn.jsdelivr.net/npm/font-awesome@4.7.0/css/font-awesome.min.css" rel="stylesheet">

    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', 'Roboto', 'Oxygen', 'Ubuntu', 'Cantarell', sans-serif;
        }

        .login-container {
            background: white;
            border-radius: 10px;
            box-shadow: 0 10px 40px rgba(0, 0, 0, 0.2);
            width: 100%;
            max-width: 420px;
            padding: 40px;
            margin: 20px;
        }

        .login-header {
            text-align: center;
            margin-bottom: 30px;
        }

        .login-header h1 {
            font-size: 24px;
            font-weight: 700;
            color: #333;
            margin-bottom: 8px;
        }

        .login-header p {
            color: #999;
            font-size: 14px;
        }

        .form-group {
            margin-bottom: 20px;
        }

        .form-label {
            display: block;
            margin-bottom: 8px;
            color: #333;
            font-weight: 500;
            font-size: 14px;
        }

        .form-control {
            width: 100%;
            padding: 12px 15px;
            border: 1px solid #ddd;
            border-radius: 5px;
            font-size: 14px;
            transition: all 0.3s ease;
        }

        .form-control:focus {
            border-color: #667eea;
            box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
            outline: none;
        }

        .form-check {
            margin-bottom: 15px;
        }

        .form-check-input {
            width: 18px;
            height: 18px;
            margin-top: 3px;
            cursor: pointer;
            border: 1px solid #ddd;
            border-radius: 3px;
        }

        .form-check-input:checked {
            background-color: #667eea;
            border-color: #667eea;
        }

        .form-check-label {
            margin-left: 8px;
            font-size: 14px;
            color: #666;
            cursor: pointer;
        }

        .btn-login {
            width: 100%;
            padding: 12px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border: none;
            border-radius: 5px;
            font-weight: 600;
            font-size: 14px;
            cursor: pointer;
            transition: all 0.3s ease;
            margin-top: 10px;
        }

        .btn-login:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 20px rgba(102, 126, 234, 0.4);
        }

        .btn-login:active {
            transform: translateY(0);
        }

        .btn-login:disabled {
            opacity: 0.6;
            cursor: not-allowed;
        }

        .alert {
            border-radius: 5px;
            border: none;
            margin-bottom: 20px;
            font-size: 14px;
        }

        .alert-danger {
            background-color: #fee;
            color: #c33;
        }

        .alert-warning {
            background-color: #fef5e7;
            color: #9c6d04;
        }

        .login-footer {
            text-align: center;
            margin-top: 20px;
            padding-top: 20px;
            border-top: 1px solid #eee;
        }

        .login-footer a {
            color: #667eea;
            text-decoration: none;
            font-size: 14px;
        }

        .login-footer a:hover {
            text-decoration: underline;
        }

        .form-divider {
            text-align: center;
            color: #999;
            font-size: 12px;
            margin: 20px 0;
        }

        .input-icon {
            position: relative;
        }

        .input-icon i {
            position: absolute;
            right: 15px;
            top: 50%;
            transform: translateY(-50%);
            color: #999;
            font-size: 14px;
        }

        .input-icon input {
            padding-right: 40px;
        }

        .spinner-border {
            display: none;
            margin-right: 8px;
        }

        .btn-login.loading {
            opacity: 0.7;
            pointer-events: none;
        }

        @media (max-width: 480px) {
            .login-container {
                padding: 30px 20px;
            }

            .login-header h1 {
                font-size: 20px;
            }
        }
    </style>
</head>
<body>
    <div class="login-container">
        <div class="login-header">
            <h1>WPMS</h1>
            <p>Warehouse Productivity Monitoring</p>
        </div>

        <!-- Error Messages -->
        <?php if (isset($_SESSION['login_error'])): ?>
            <div class="alert alert-danger" role="alert">
                <strong>Login Failed:</strong> <?php echo htmlspecialchars($_SESSION['login_error']); ?>
                <?php unset($_SESSION['login_error']); ?>
            </div>
        <?php endif; ?>

        <!-- Warning Messages -->
        <?php if (isset($_SESSION['login_warning'])): ?>
            <div class="alert alert-warning" role="alert">
                <?php echo htmlspecialchars($_SESSION['login_warning']); ?>
                <?php unset($_SESSION['login_warning']); ?>
            </div>
        <?php endif; ?>

        <!-- Session Timeout Message -->
        <?php if (isset($_GET['timeout'])): ?>
            <div class="alert alert-warning" role="alert">
                Your session has expired. Please log in again.
            </div>
        <?php endif; ?>

        <!-- Login Form -->
        <form id="loginForm" method="POST" action="/warehouse-productivity/api/auth/login.php">
            <!-- CSRF Token -->
            <input type="hidden" name="<?php echo Security::CSRF_TOKEN_NAME; ?>" value="<?php echo Security::generateCSRFToken(); ?>">

            <!-- Email Input -->
            <div class="form-group">
                <label for="email" class="form-label">Email or Username</label>
                <div class="input-icon">
                    <input
                        type="text"
                        id="email"
                        name="email"
                        class="form-control"
                        placeholder="Enter your email or username"
                        required
                        autocomplete="username"
                        autofocus>
                    <i class="fa fa-envelope"></i>
                </div>
            </div>

            <!-- Password Input -->
            <div class="form-group">
                <label for="password" class="form-label">Password</label>
                <div class="input-icon">
                    <input
                        type="password"
                        id="password"
                        name="password"
                        class="form-control"
                        placeholder="Enter your password"
                        required
                        autocomplete="current-password">
                    <i class="fa fa-lock"></i>
                </div>
            </div>

            <!-- Remember Me Checkbox -->
            <div class="form-check">
                <input
                    type="checkbox"
                    id="rememberMe"
                    name="remember_me"
                    class="form-check-input"
                    value="1">
                <label class="form-check-label" for="rememberMe">
                    Keep me logged in
                </label>
            </div>

            <!-- Login Button -->
            <button type="submit" class="btn-login" id="loginBtn">
                <span class="spinner-border spinner-border-sm"></span>
                Sign In
            </button>
        </form>

        <!-- Footer Links -->
        <div class="login-footer">
            <div>
                <a href="/warehouse-productivity/password-reset.php">Forgot Password?</a>
            </div>
            <div style="margin-top: 10px; font-size: 12px; color: #999;">
                Need help? Contact <a href="mailto:support@asmrcctvtechnologies.com" style="color: #667eea;">support</a>
            </div>
        </div>
    </div>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

    <script>
        // Form submission handler
        document.getElementById('loginForm').addEventListener('submit', function(e) {
            const btn = document.getElementById('loginBtn');
            const spinner = btn.querySelector('.spinner-border');

            // Show loading state
            btn.classList.add('loading');
            btn.disabled = true;
            spinner.style.display = 'inline-block';

            // Form will submit normally, so we just disable during submission
        });

        // Handle Enter key in password field
        document.getElementById('password').addEventListener('keypress', function(e) {
            if (e.key === 'Enter') {
                document.getElementById('loginForm').submit();
            }
        });

        // Clear password field on page load for security (browser back button)
        window.addEventListener('pageshow', function() {
            if (performance.navigation.type === 2) { // Back button
                document.getElementById('password').value = '';
            }
        });
    </script>
</body>
</html>
