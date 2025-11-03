<?php
/**
 * ================================================================
 * BLOG HUT - Forgot Password
 * University of Moratuwa - IN2120 Web Programming Project
 * ================================================================
 * 
 * This file handles password recovery with:
 * - Email validation
 * - Password reset (simplified version)
 * - Security questions (optional)
 * 
 * Note: This is a simplified implementation for academic purposes.
 * In production, you should implement email-based token reset.
 * 
 * @package BlogHut
 * @author Your Name
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
$success = false;
$email = '';
$step = 1; // Step 1: Email verification, Step 2: Password reset

// Process form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $csrfToken = $_POST['csrf_token'] ?? '';
    
    // Validate CSRF token
    if (!verifyCSRFToken($csrfToken)) {
        $errors[] = 'Invalid security token. Please try again.';
    }
    
    // Step 1: Verify email
    if (isset($_POST['verify_email'])) {
        $email = cleanInput($_POST['email'] ?? '');
        
        // Validate email
        if (isEmpty($email)) {
            $errors[] = 'Email is required.';
        } elseif (!isValidEmail($email)) {
            $errors[] = 'Please enter a valid email address.';
        } else {
            // Check if email exists
            $user = fetchOne("SELECT id, username, email FROM users WHERE email = ?", [$email]);
            
            if ($user) {
                // Store user info in session for step 2
                setSession('reset_user_id', $user['id']);
                setSession('reset_email', $user['email']);
                $step = 2;
            } else {
                // For security, don't reveal if email exists or not
                $errors[] = 'If this email is registered, password reset instructions would be sent.';
            }
        }
    }
    
    // Step 2: Reset password
    if (isset($_POST['reset_password'])) {
        $newPassword = $_POST['new_password'] ?? '';
        $confirmPassword = $_POST['confirm_password'] ?? '';
        $userId = getSession('reset_user_id');
        
        // Validate new password
        if (isEmpty($newPassword)) {
            $errors[] = 'New password is required.';
        } elseif (!isValidPassword($newPassword)) {
            $errors[] = 'Password must be at least ' . MIN_PASSWORD_LENGTH . ' characters long.';
        }
        
        // Validate password confirmation
        if ($newPassword !== $confirmPassword) {
            $errors[] = 'Passwords do not match.';
        }
        
        // If no errors, update password
        if (empty($errors) && $userId) {
            try {
                $hashedPassword = hashPassword($newPassword);
                $sql = "UPDATE users SET password = ? WHERE id = ?";
                
                if (updateRecord($sql, [$hashedPassword, $userId]) > 0) {
                    // Clear reset session data
                    unsetSession('reset_user_id');
                    unsetSession('reset_email');
                    
                    $success = true;
                    setFlashMessage('Password reset successfully! You can now login with your new password.', 'success');
                } else {
                    $errors[] = 'Failed to reset password. Please try again.';
                }
            } catch (Exception $e) {
                error_log("Password Reset Error: " . $e->getMessage());
                $errors[] = 'An error occurred. Please try again.';
            }
        } else {
            $step = 2; // Stay on step 2 if there are errors
        }
    }
}

// Check if we're on step 2 from session
if (hasSession('reset_user_id') && $step === 1) {
    $step = 2;
    $email = getSession('reset_email');
}

// Set page title
$pageTitle = 'Forgot Password - ' . SITE_NAME;
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
                        <i class="fas fa-key text-primary" style="font-size: 3rem;"></i>
                        <h2 class="mt-3 mb-2">Reset Password</h2>
                        <p class="text-muted">
                            <?php echo $step === 1 ? 'Enter your email to reset your password' : 'Create your new password'; ?>
                        </p>
                    </div>
                    
                    <!-- Display Errors -->
                    <?php if (!empty($errors)): ?>
                    <div class="alert alert-danger alert-dismissible fade show" role="alert">
                        <i class="fas fa-exclamation-circle me-2"></i>
                        <strong>Error:</strong>
                        <ul class="mb-0 mt-2">
                            <?php foreach ($errors as $error): ?>
                                <li><?php echo e($error); ?></li>
                            <?php endforeach; ?>
                        </ul>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                    <?php endif; ?>
                    
                    <!-- Success Message -->
                    <?php if ($success): ?>
                    <div class="alert alert-success" role="alert">
                        <i class="fas fa-check-circle me-2"></i>
                        <strong>Success!</strong> Your password has been reset.
                        <div class="mt-3">
                            <a href="<?php echo SITE_URL; ?>/auth/login.php" class="btn btn-success">
                                <i class="fas fa-sign-in-alt me-2"></i> Login Now
                            </a>
                        </div>
                    </div>
                    <?php else: ?>
                    
                    <!-- Step 1: Email Verification Form -->
                    <?php if ($step === 1): ?>
                    <form method="POST" action="" id="verifyEmailForm">
                        <?php echo csrfField(); ?>
                        
                        <div class="mb-3">
                            <label for="email" class="form-label">
                                <i class="fas fa-envelope me-1"></i> Email Address
                            </label>
                            <input type="email" 
                                   class="form-control" 
                                   id="email" 
                                   name="email" 
                                   value="<?php echo e($email); ?>"
                                   placeholder="Enter your registered email"
                                   required
                                   autofocus>
                            <small class="form-text text-muted">
                                Enter the email you used to register your account
                            </small>
                        </div>
                        
                        <button type="submit" name="verify_email" class="btn btn-primary w-100 mb-3">
                            <i class="fas fa-arrow-right me-2"></i> Continue
                        </button>
                    </form>
                    
                    <!-- Step 2: New Password Form -->
                    <?php elseif ($step === 2): ?>
                    <div class="alert alert-info mb-3">
                        <i class="fas fa-info-circle me-2"></i>
                        Resetting password for: <strong><?php echo e($email); ?></strong>
                    </div>
                    
                    <form method="POST" action="" id="resetPasswordForm">
                        <?php echo csrfField(); ?>
                        
                        <div class="mb-3">
                            <label for="new_password" class="form-label">
                                <i class="fas fa-lock me-1"></i> New Password
                            </label>
                            <div class="input-group">
                                <input type="password" 
                                       class="form-control" 
                                       id="new_password" 
                                       name="new_password" 
                                       placeholder="Enter new password"
                                       required
                                       minlength="<?php echo MIN_PASSWORD_LENGTH; ?>"
                                       autofocus>
                                <button class="btn btn-outline-secondary" type="button" id="toggleNewPassword">
                                    <i class="fas fa-eye"></i>
                                </button>
                            </div>
                            <small class="form-text text-muted">
                                Minimum <?php echo MIN_PASSWORD_LENGTH; ?> characters
                            </small>
                        </div>
                        
                        <div class="mb-3">
                            <label for="confirm_password" class="form-label">
                                <i class="fas fa-lock me-1"></i> Confirm New Password
                            </label>
                            <div class="input-group">
                                <input type="password" 
                                       class="form-control" 
                                       id="confirm_password" 
                                       name="confirm_password" 
                                       placeholder="Re-enter new password"
                                       required
                                       minlength="<?php echo MIN_PASSWORD_LENGTH; ?>">
                                <button class="btn btn-outline-secondary" type="button" id="toggleConfirmPassword">
                                    <i class="fas fa-eye"></i>
                                </button>
                            </div>
                        </div>
                        
                        <button type="submit" name="reset_password" class="btn btn-primary w-100 mb-3">
                            <i class="fas fa-check me-2"></i> Reset Password
                        </button>
                        
                        <a href="<?php echo SITE_URL; ?>/auth/login.php" class="btn btn-outline-secondary w-100">
                            <i class="fas fa-times me-2"></i> Cancel
                        </a>
                    </form>
                    <?php endif; ?>
                    
                    <?php endif; ?>
                    
                    <!-- Additional Links -->
                    <div class="text-center mt-4">
                        <p class="small text-muted mb-2">Remember your password?</p>
                        <a href="<?php echo SITE_URL; ?>/auth/login.php" class="text-primary text-decoration-none">
                            <i class="fas fa-sign-in-alt me-1"></i> Back to Login
                        </a>
                    </div>
                </div>
                
                <!-- Note about implementation -->
                <?php if (DEBUG_MODE): ?>
                <div class="alert alert-warning mt-3 small">
                    <i class="fas fa-info-circle me-2"></i>
                    <strong>Note:</strong> This is a simplified password reset for academic purposes. 
                    In production, implement email-based token verification.
                </div>
                <?php endif; ?>
                
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

<!-- Custom JavaScript -->
<script>
// Password toggle functionality for new password
document.getElementById('toggleNewPassword')?.addEventListener('click', function() {
    const passwordInput = document.getElementById('new_password');
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

// Password toggle functionality for confirm password
document.getElementById('toggleConfirmPassword')?.addEventListener('click', function() {
    const passwordInput = document.getElementById('confirm_password');
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

// Form validation for reset password
document.getElementById('resetPasswordForm')?.addEventListener('submit', function(e) {
    const newPassword = document.getElementById('new_password').value;
    const confirmPassword = document.getElementById('confirm_password').value;
    
    if (newPassword !== confirmPassword) {
        e.preventDefault();
        Swal.fire({
            icon: 'error',
            title: 'Passwords Do Not Match',
            text: 'Please make sure both passwords are identical.',
            confirmButtonColor: '#FFB100'
        });
        return false;
    }
    
    if (newPassword.length < <?php echo MIN_PASSWORD_LENGTH; ?>) {
        e.preventDefault();
        Swal.fire({
            icon: 'error',
            title: 'Password Too Short',
            text: 'Password must be at least <?php echo MIN_PASSWORD_LENGTH; ?> characters long.',
            confirmButtonColor: '#FFB100'
        });
        return false;
    }
});
</script>

<?php
$customJS = '<script src="' . JS_URL . '/login.js"></script>';
include '../includes/footer.php'; 
?>