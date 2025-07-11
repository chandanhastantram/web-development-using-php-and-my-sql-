<?php
include 'db.php'; // Make sure this connects to $conn (MySQLi)

$message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = trim($_POST['password'] ?? '');

    if ($username === '' || $password === '') {
        $message = '❗ Please fill in both fields.';
    } else {
        // Password strength validation
        $password_errors = [];

        if (strlen($password) < 8) {
            $password_errors[] = 'at least 8 characters';
        }
        if (!preg_match('/[A-Z]/', $password)) {
            $password_errors[] = 'one uppercase letter';
        }
        if (!preg_match('/[a-z]/', $password)) {
            $password_errors[] = 'one lowercase letter';
        }
        if (!preg_match('/[0-9]/', $password)) {
            $password_errors[] = 'one number';
        }
        if (!preg_match('/[^A-Za-z0-9]/', $password)) {
            $password_errors[] = 'one special character';
        }

        if (!empty($password_errors)) {
            $message = '❌ Password must contain: ' . implode(', ', $password_errors) . '.';
        } else {
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);

            // Check if username exists
            $check = $conn->prepare("SELECT id FROM users WHERE name = ?");
            $check->bind_param("s", $username);
            $check->execute();
            $check->store_result();

            if ($check->num_rows > 0) {
                $message = '❌ Username already taken.';
            } else {
                // Assign role (default is 'editor')
                $role = 'editor';

                // Insert new user
                $stmt = $conn->prepare("INSERT INTO users (name, password, role) VALUES (?, ?, ?)");
                $stmt->bind_param("sss", $username, $hashed_password, $role);

                if ($stmt->execute()) {
                    $message = '✅ Registration successful. <a href="login.php">Login</a>';
                } else {
                    $message = '❌ Something went wrong. Try again.';
                }
            }

            $check->close();
        }
    }
}
?>


<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Join Us - Create Account</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <style>
        :root {
            --primary: #667eea;
            --primary-dark: #5a67d8;
            --secondary: #764ba2;
            --accent: #f093fb;
            --bg-primary: #0f0f23;
            --bg-secondary: #1a1a2e;
            --card-bg: rgba(255, 255, 255, 0.05);
            --text-primary: #ffffff;
            --text-secondary: #a0a0a0;
            --error: #ff6b6b;
            --success: #4ecdc4;
            --warning: #ffd93d;
            --border: rgba(255, 255, 255, 0.1);
            --shadow: 0 20px 40px rgba(0, 0, 0, 0.3);
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'SF Pro Display', -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
            background: linear-gradient(135deg, var(--bg-primary) 0%, var(--bg-secondary) 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            position: relative;
            overflow: hidden;
        }

        /* Animated background elements */
        body::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: 
                radial-gradient(circle at 20% 20%, rgba(102, 126, 234, 0.1) 0%, transparent 50%),
                radial-gradient(circle at 80% 80%, rgba(240, 147, 251, 0.1) 0%, transparent 50%),
                radial-gradient(circle at 40% 60%, rgba(118, 75, 162, 0.1) 0%, transparent 50%);
            animation: backgroundShift 20s ease-in-out infinite;
            z-index: -1;
        }

        @keyframes backgroundShift {
            0%, 100% { transform: scale(1) rotate(0deg); }
            50% { transform: scale(1.1) rotate(5deg); }
        }

        /* Floating particles */
        .particle {
            position: absolute;
            width: 2px;
            height: 2px;
            background: rgba(255, 255, 255, 0.3);
            border-radius: 50%;
            animation: float 6s ease-in-out infinite;
        }

        .particle:nth-child(1) { top: 10%; left: 20%; animation-delay: 0s; }
        .particle:nth-child(2) { top: 20%; left: 80%; animation-delay: 2s; }
        .particle:nth-child(3) { top: 80%; left: 10%; animation-delay: 4s; }
        .particle:nth-child(4) { top: 70%; left: 90%; animation-delay: 1s; }

        @keyframes float {
            0%, 100% { transform: translateY(0px) rotate(0deg); opacity: 0.5; }
            50% { transform: translateY(-20px) rotate(180deg); opacity: 1; }
        }

        .container {
            position: relative;
            z-index: 1;
        }

        .register-card {
            background: var(--card-bg);
            backdrop-filter: blur(20px);
            border: 1px solid var(--border);
            border-radius: 24px;
            padding: 48px;
            width: 460px;
            max-width: 90vw;
            box-shadow: var(--shadow);
            position: relative;
            overflow: hidden;
            transform: translateY(20px);
            animation: slideUp 0.8s ease-out forwards;
        }

        @keyframes slideUp {
            to {
                transform: translateY(0);
                opacity: 1;
            }
        }

        .register-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 1px;
            background: linear-gradient(90deg, transparent, rgba(255,255,255,0.4), transparent);
        }

        .logo {
            text-align: center;
            margin-bottom: 32px;
        }

        .logo-icon {
            width: 64px;
            height: 64px;
            background: linear-gradient(135deg, var(--primary), var(--accent));
            border-radius: 16px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            margin-bottom: 16px;
            animation: pulse 2s ease-in-out infinite;
        }

        @keyframes pulse {
            0%, 100% { transform: scale(1); }
            50% { transform: scale(1.05); }
        }

        .logo-icon i {
            font-size: 28px;
            color: white;
        }

        h1 {
            color: var(--text-primary);
            font-size: 28px;
            font-weight: 700;
            margin-bottom: 8px;
            text-align: center;
        }

        .subtitle {
            color: var(--text-secondary);
            font-size: 16px;
            text-align: center;
            margin-bottom: 40px;
        }

        .form-group {
            position: relative;
            margin-bottom: 24px;
        }

        .form-group label {
            display: block;
            color: var(--text-primary);
            font-size: 14px;
            font-weight: 500;
            margin-bottom: 8px;
            transform: translateY(0);
            transition: all 0.3s ease;
        }

        .input-wrapper {
            position: relative;
        }

        .input-wrapper i {
            position: absolute;
            left: 16px;
            top: 50%;
            transform: translateY(-50%);
            color: var(--text-secondary);
            font-size: 16px;
            transition: color 0.3s ease;
        }

        input[type="text"],
        input[type="password"] {
            width: 100%;
            padding: 16px 16px 16px 48px;
            background: rgba(255, 255, 255, 0.05);
            border: 1px solid var(--border);
            border-radius: 12px;
            color: var(--text-primary);
            font-size: 16px;
            transition: all 0.3s ease;
            backdrop-filter: blur(10px);
        }

        input[type="text"]:focus,
        input[type="password"]:focus {
            outline: none;
            border-color: var(--primary);
            background: rgba(255, 255, 255, 0.08);
            box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.2);
        }

        input[type="text"]:focus + i,
        input[type="password"]:focus + i {
            color: var(--primary);
        }

        .password-toggle {
            position: absolute;
            right: 16px;
            top: 50%;
            transform: translateY(-50%);
            color: var(--text-secondary);
            cursor: pointer;
            transition: color 0.3s ease;
        }

        .password-toggle:hover {
            color: var(--primary);
        }

        /* Password Strength Indicator */
        .password-strength {
            margin-top: 12px;
            padding: 16px;
            background: rgba(255, 255, 255, 0.03);
            border: 1px solid var(--border);
            border-radius: 12px;
            display: none;
        }

        .password-strength.show {
            display: block;
            animation: fadeIn 0.3s ease-out;
        }

        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(-10px); }
            to { opacity: 1; transform: translateY(0); }
        }

        .strength-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 12px;
        }

        .strength-label {
            font-size: 14px;
            font-weight: 600;
            color: var(--text-primary);
        }

        .strength-level {
            font-size: 12px;
            padding: 4px 8px;
            border-radius: 6px;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .strength-level.weak {
            background: rgba(255, 107, 107, 0.2);
            color: var(--error);
        }

        .strength-level.medium {
            background: rgba(255, 217, 61, 0.2);
            color: var(--warning);
        }

        .strength-level.strong {
            background: rgba(78, 205, 196, 0.2);
            color: var(--success);
        }

        .strength-bar {
            height: 6px;
            background: rgba(255, 255, 255, 0.1);
            border-radius: 3px;
            overflow: hidden;
            margin-bottom: 12px;
        }

        .strength-progress {
            height: 100%;
            border-radius: 3px;
            transition: all 0.3s ease;
            background: linear-gradient(90deg, var(--error), var(--warning), var(--success));
        }

        .requirements {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 8px;
        }

        .requirement {
            display: flex;
            align-items: center;
            font-size: 12px;
            color: var(--text-secondary);
            transition: color 0.3s ease;
        }

        .requirement i {
            margin-right: 8px;
            font-size: 10px;
        }

        .requirement.met {
            color: var(--success);
        }

        .requirement.met i {
            color: var(--success);
        }

        .register-btn {
            width: 100%;
            padding: 16px;
            background: linear-gradient(135deg, var(--primary), var(--secondary));
            border: none;
            border-radius: 12px;
            color: white;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            position: relative;
            overflow: hidden;
            margin-top: 8px;
        }

        .register-btn:disabled {
            opacity: 0.6;
            cursor: not-allowed;
            transform: none !important;
        }

        .register-btn::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255,255,255,0.2), transparent);
            transition: left 0.6s ease;
        }

        .register-btn:hover:not(:disabled)::before {
            left: 100%;
        }

        .register-btn:hover:not(:disabled) {
            transform: translateY(-2px);
            box-shadow: 0 10px 25px rgba(102, 126, 234, 0.4);
        }

        .register-btn:active:not(:disabled) {
            transform: translateY(0);
        }

        .message {
            margin-top: 24px;
            padding: 16px;
            border-radius: 12px;
            font-size: 14px;
            text-align: center;
            position: relative;
            animation: messageSlide 0.5s ease-out;
        }

        @keyframes messageSlide {
            from {
                opacity: 0;
                transform: translateY(-10px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .message.error {
            background: rgba(255, 107, 107, 0.1);
            color: var(--error);
            border: 1px solid rgba(255, 107, 107, 0.3);
        }

        .message.success {
            background: rgba(78, 205, 196, 0.1);
            color: var(--success);
            border: 1px solid rgba(78, 205, 196, 0.3);
        }

        .message a {
            color: var(--primary);
            text-decoration: none;
            font-weight: 600;
            transition: color 0.3s ease;
        }

        .message a:hover {
            color: var(--primary-dark);
        }

        .login-link {
            text-align: center;
            margin-top: 32px;
            color: var(--text-secondary);
            font-size: 14px;
        }

        .login-link a {
            color: var(--primary);
            text-decoration: none;
            font-weight: 600;
            transition: color 0.3s ease;
        }

        .login-link a:hover {
            color: var(--primary-dark);
        }

        /* Responsive design */
        @media (max-width: 480px) {
            .register-card {
                padding: 32px 24px;
                width: 100%;
            }
            
            h1 {
                font-size: 24px;
            }
            
            .subtitle {
                font-size: 14px;
            }

            .requirements {
                grid-template-columns: 1fr;
            }
        }

        /* Loading animation */
        .loading {
            position: relative;
            color: transparent;
        }

        .loading::after {
            content: '';
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            width: 20px;
            height: 20px;
            border: 2px solid rgba(255, 255, 255, 0.3);
            border-top: 2px solid white;
            border-radius: 50%;
            animation: spin 1s linear infinite;
        }

        @keyframes spin {
            0% { transform: translate(-50%, -50%) rotate(0deg); }
            100% { transform: translate(-50%, -50%) rotate(360deg); }
        }
    </style>
</head>
<body>
    <!-- Floating particles -->
    <div class="particle"></div>
    <div class="particle"></div>
    <div class="particle"></div>
    <div class="particle"></div>

    <div class="container">
        <div class="register-card">
            <div class="logo">
                <div class="logo-icon">
                    <i class="fas fa-rocket"></i>
                </div>
                <h1>Join Our Community</h1>
                <p class="subtitle">Create your account and start your journey</p>
            </div>

            <form method="POST" id="registerForm">
                <div class="form-group">
                    <label for="username">Username</label>
                    <div class="input-wrapper">
                        <input type="text" id="username" name="username" placeholder="Choose a unique username" required>
                        <i class="fas fa-user"></i>
                    </div>
                </div>

                <div class="form-group">
                    <label for="password">Password</label>
                    <div class="input-wrapper">
                        <input type="password" id="password" name="password" placeholder="Create a strong password" required>
                        <i class="fas fa-lock"></i>
                        <i class="fas fa-eye password-toggle" onclick="togglePassword()"></i>
                    </div>
                    
                    <div class="password-strength" id="passwordStrength">
                        <div class="strength-header">
                            <span class="strength-label">Password Strength</span>
                            <span class="strength-level" id="strengthLevel">Weak</span>
                        </div>
                        <div class="strength-bar">
                            <div class="strength-progress" id="strengthProgress" style="width: 0%"></div>
                        </div>
                        <div class="requirements">
                            <div class="requirement" id="req-length">
                                <i class="fas fa-times"></i>
                                <span>8+ characters</span>
                            </div>
                            <div class="requirement" id="req-upper">
                                <i class="fas fa-times"></i>
                                <span>Uppercase letter</span>
                            </div>
                            <div class="requirement" id="req-lower">
                                <i class="fas fa-times"></i>
                                <span>Lowercase letter</span>
                            </div>
                            <div class="requirement" id="req-number">
                                <i class="fas fa-times"></i>
                                <span>Number</span>
                            </div>
                            <div class="requirement" id="req-special">
                                <i class="fas fa-times"></i>
                                <span>Special character</span>
                            </div>
                        </div>
                    </div>
                </div>

                <button type="submit" class="register-btn" id="submitBtn" disabled>
                    Create Account
                </button>
            </form>

            <?php if ($message): ?>
                <div class="message <?= str_contains($message,'✅') ? 'success' : 'error' ?>">
                    <?= $message ?>
                </div>
            <?php endif; ?>

            <div class="login-link">
                Already have an account? <a href="login.php">Sign in here</a>
            </div>
        </div>
    </div>

    <script>
        function togglePassword() {
            const passwordInput = document.getElementById('password');
            const toggleIcon = document.querySelector('.password-toggle');
            
            if (passwordInput.type === 'password') {
                passwordInput.type = 'text';
                toggleIcon.classList.remove('fa-eye');
                toggleIcon.classList.add('fa-eye-slash');
            } else {
                passwordInput.type = 'password';
                toggleIcon.classList.remove('fa-eye-slash');
                toggleIcon.classList.add('fa-eye');
            }
        }

        function checkPasswordStrength(password) {
            const requirements = {
                length: password.length >= 8,
                upper: /[A-Z]/.test(password),
                lower: /[a-z]/.test(password),
                number: /[0-9]/.test(password),
                special: /[^A-Za-z0-9]/.test(password)
            };

            // Update requirement indicators
            Object.keys(requirements).forEach(req => {
                const element = document.getElementById(`req-${req}`);
                const icon = element.querySelector('i');
                
                if (requirements[req]) {
                    element.classList.add('met');
                    icon.classList.remove('fa-times');
                    icon.classList.add('fa-check');
                } else {
                    element.classList.remove('met');
                    icon.classList.remove('fa-check');
                    icon.classList.add('fa-times');
                }
            });

            // Calculate strength
            const metRequirements = Object.values(requirements).filter(Boolean).length;
            const strengthLevel = document.getElementById('strengthLevel');
            const strengthProgress = document.getElementById('strengthProgress');
            const submitBtn = document.getElementById('submitBtn');

            let strength = 0;
            let strengthText = 'Weak';
            let strengthClass = 'weak';

            if (metRequirements >= 5) {
                strength = 100;
                strengthText = 'Strong';
                strengthClass = 'strong';
                submitBtn.disabled = false;
            } else if (metRequirements >= 3) {
                strength = 60;
                strengthText = 'Medium';
                strengthClass = 'medium';
                submitBtn.disabled = true;
            } else {
                strength = Math.max(20, metRequirements * 10);
                strengthText = 'Weak';
                strengthClass = 'weak';
                submitBtn.disabled = true;
            }

            strengthLevel.textContent = strengthText;
            strengthLevel.className = `strength-level ${strengthClass}`;
            strengthProgress.style.width = `${strength}%`;

            return metRequirements === 5;
        }

        // Password strength checking
        document.getElementById('password').addEventListener('input', function() {
            const password = this.value;
            const strengthIndicator = document.getElementById('passwordStrength');
            
            if (password.length > 0) {
                strengthIndicator.classList.add('show');
                checkPasswordStrength(password);
            } else {
                strengthIndicator.classList.remove('show');
                document.getElementById('submitBtn').disabled = true;
            }
        });

        // Add loading animation on form submit
        document.getElementById('registerForm').addEventListener('submit', function(e) {
            const password = document.getElementById('password').value;
            const isStrong = checkPasswordStrength(password);
            
            if (!isStrong) {
                e.preventDefault();
                alert('Please create a strong password that meets all requirements.');
                return;
            }

            const submitBtn = document.getElementById('submitBtn');
            submitBtn.classList.add('loading');
            submitBtn.disabled = true;
        });

        // Add focus animations
        document.querySelectorAll('input').forEach(input => {
            input.addEventListener('focus', function() {
                this.parentElement.style.transform = 'scale(1.02)';
            });
            
            input.addEventListener('blur', function() {
                this.parentElement.style.transform = 'scale(1)';
            });
        });

        // Add entrance animation
        document.addEventListener('DOMContentLoaded', function() {
            const card = document.querySelector('.register-card');
            card.style.opacity = '0';
            setTimeout(() => {
                card.style.opacity = '1';
            }, 100);
        });
    </script>
</body>
</html>



