<?php
/**
 * BLOG HUT - User Login
 * This file handles user authentication with:
 * - Email/username login support
 * - Password verification
 * - Remember me functionality
 * - Session management
 * - Failed login attempt tracking
 * - Redirect to intended page after login
 */

// Start session
session_start();

// Include required files
require_once '../config/database.php';
require_once '../config/constants.php';
require_once '../includes/helper.php';

// Redirect if already logged in
if (isLoggedIn()) {
    redirect(SITE_URL . '/posts/home.php');
}

// Initialize variables
$errors = [];
$identifier = ''; // Can be email or username
$rememberMe = false;

// Process login form
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Get and clean input
    $identifier = cleanInput($_POST['identifier'] ?? '');
    $password = $_POST['password'] ?? '';
    $rememberMe = isset($_POST['remember_me']);
    $csrfToken = $_POST['csrf_token'] ?? '';
    
    // Validate CSRF token
    if (!verifyCSRFToken($csrfToken)) {
        $errors[] = 'Invalid security token. Please try again.';
    }
    
    // Validate identifier (email or username)
    if (isEmpty($identifier)) {
        $errors[] = 'Email or username is required.';
    }
    
    // Validate password
    if (isEmpty($password)) {
        $errors[] = 'Password is required.';
    }
    
    // If no validation errors, attempt login
    if (empty($errors)) {
        try {
            // Check if identifier is email or username
            $sql = "SELECT * FROM users WHERE email = ? OR username = ?";
            $user = fetchOne($sql, [$identifier, $identifier]);
            
            if ($user) {
                // Verify password
                if (verifyPassword($password, $user['password'])) {
                    // Password correct - create session
                    setSession('user_id', $user['id']);
                    setSession('username', $user['username']);
                    setSession('email', $user['email']);
                    setSession('role', $user['role']);
                    setSession('profile_image', $user['profile_image']);
                    
                    // Handle remember me
                    if ($rememberMe) {
                        // Set cookie for 30 days
                        $token = bin2hex(random_bytes(32));
                        setcookie('remember_token', $token, time() + REMEMBER_ME_LIFETIME, '/');
                        
                        // Store token in session for validation
                        setSession('remember_token', $token);
                    }
                    
                    // Update last login time (optional - you can add this column)
                    // executeQuery("UPDATE users SET last_login = NOW() WHERE id = ?", [$user['id']]);
                    
                    // Set success message
                    setFlashMessage('Welcome back, ' . $user['username'] . '!', 'success');
                    
                    // Redirect to intended page or home
                    $redirectUrl = getSession('redirect_after_login', SITE_URL . '/posts/home.php');
                    unsetSession('redirect_after_login');
                    redirect($redirectUrl);
                    
                } else {
                    // Invalid password
                    $errors[] = 'Invalid email/username or password.';
                }
            } else {
                // User not found
                $errors[] = 'Invalid email/username or password.';
            }
            
        } catch (Exception $e) {
            error_log("Login Error: " . $e->getMessage());
            $errors[] = 'An error occurred during login. Please try again.';
        }
    }
}



// Set page title
$pageTitle = 'Login - ' . SITE_NAME;
$customCSS = '<link rel="stylesheet" href="' . CSS_URL . '/login.css">';
?>
<?php include '../includes/header.php'; ?>

<div class="auth-container">
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-md-6 col-lg-5">
                <div class="auth-card" data-aos="fade-up">
                    <!-- Logo/Header -->
                    <div class="text-center mb-4">
                        <i class="fas fa-blog text-primary" style="font-size: 3rem;"></i>
                        <h2 class="mt-3 mb-2">Welcome Back</h2>
                        <p class="text-muted">Login to continue to <?php echo SITE_NAME; ?></p>
                    </div>
                
                    
                    <!-- Display Errors -->
                    <?php if (!empty($errors)): ?>
                    <div class="alert alert-danger alert-dismissible fade show" role="alert">
                        <i class="fas fa-exclamation-circle me-2"></i>
                        <strong>Login Failed:</strong>
                        <ul class="mb-0 mt-2">
                            <?php foreach ($errors as $error): ?>
                                <li><?php echo e($error); ?></li>
                            <?php endforeach; ?>
                        </ul>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                    <?php endif; ?>
                    
                    <!-- Login Form -->
                    <form method="POST" action="" id="loginForm">
                        <?php echo csrfField(); ?>
                        
                        <!-- Email/Username Field -->
                        <div class="mb-3">
                            <label for="identifier" class="form-label">
                                <i class="fas fa-user me-1"></i> Email or Username
                            </label>
                            <input type="text" 
                                   class="form-control" 
                                   id="identifier" 
                                   name="identifier" 
                                   value="<?php echo e($identifier); ?>"
                                   placeholder="Enter your email or username"
                                   required
                                   autofocus>
                        </div>
                        
                        <!-- Password Field -->
                        <div class="mb-3">
                            <label for="password" class="form-label">
                                <i class="fas fa-lock me-1"></i> Password
                            </label>
                            <div class="input-group">
                                <input type="password" 
                                       class="form-control" 
                                       id="password" 
                                       name="password" 
                                       placeholder="Enter your password"
                                       required>
                                <button class="btn btn-outline-secondary" type="button" id="togglePassword">
                                    <i class="fas fa-eye"></i>
                                </button>
                            </div>
                        </div>
                        
                        <!-- Remember Me & Forgot Password -->
                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <div class="form-check">
                                <input type="checkbox" 
                                       class="form-check-input" 
                                       id="remember_me" 
                                       name="remember_me"
                                       <?php echo $rememberMe ? 'checked' : ''; ?>>
                                <label class="form-check-label" for="remember_me">
                                    Remember me
                                </label>
                            </div>
                            <a href="<?php echo SITE_URL; ?>/auth/forgot_password.php" 
                               class="text-primary text-decoration-none small">
                                Forgot Password?
                            </a>
                        </div>
                        
                        <!-- Submit Button -->
                        <button type="submit" class="btn btn-primary w-100 mb-3">
                            <i class="fas fa-sign-in-alt me-2"></i> Login
                        </button>
                        
                        <!-- Divider -->
                        <div class="text-center text-muted my-3">
                            <small>Don't have an account?</small>
                        </div>
                        
                        <!-- Register Link -->
                        <a href="<?php echo SITE_URL; ?>/auth/register.php" class="btn btn-outline-primary w-100">
                            <i class="fas fa-user-plus me-2"></i> Create Account
                        </a>
                    </form>
                    
                    <!-- Demo Credentials (Remove in production) -->
                
                </div>
                
                <!-- Back to Home Link -->
                <div class="text-center mt-4">
                    <a href="<?php echo SITE_URL; ?>/index.php" class="text-muted text-decoration-none">
                        <i class="fas fa-arrow-left me-1"></i> Back to Home
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Custom JavaScript for Login -->
<script>
// Password toggle functionality
document.getElementById('togglePassword').addEventListener('click', function() {
    const passwordInput = document.getElementById('password');
    const icon = this.querySelector('i');
    
    if (passwordInput.type === 'password') {
        passwordInput.type = 'text';
        icon.classList.remove('fa-eye');
        icon.classList.add('fa-eye-slash');
    } else {
        passwordInput.type = 'password';
        icon.classList.remove('fa-eye-slash');
        icon.classList.add('fa-eye');
    }
});

// Form validation
document.getElementById('loginForm').addEventListener('submit', function(e) {
    const identifier = document.getElementById('identifier').value.trim();
    const password = document.getElementById('password').value;
    
    if (!identifier || !password) {
        e.preventDefault();
        Swal.fire({
            icon: 'warning',
            title: 'Missing Information',
            text: 'Please enter both email/username and password.',
            confirmButtonColor: '#FFB100'
        });
        return false;
    }
});

// Auto-focus on identifier field
document.addEventListener('DOMContentLoaded', function() {
    document.getElementById('identifier').focus();
});
</script>

<?php
$customJS = '<script src="' . JS_URL . '/login.js"></script>';
include '../includes/footer.php'; 
?>