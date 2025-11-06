<?php
/**
 * BLOG HUT - User Registration
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
$username = '';
$email = '';

// Process registration form
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Get and clean input
    $username = cleanInput($_POST['username'] ?? '');
    $email = cleanInput($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirmPassword = $_POST['confirm_password'] ?? '';
    $csrfToken = $_POST['csrf_token'] ?? '';
    
    // Validate CSRF token
    if (!verifyCSRFToken($csrfToken)) {
        $errors[] = 'Invalid security token. Please try again.';
    }
    
    // Validate username
    if (isEmpty($username)) {
        $errors[] = 'Username is required.';
    } elseif (!isValidUsername($username)) {
        $errors[] = 'Username must be 3-50 characters and contain only letters, numbers, and underscores.';
    } else {
        // Check if username exists
        $checkUsername = fetchOne("SELECT id FROM users WHERE username = ?", [$username]);
        if ($checkUsername) {
            $errors[] = 'Username already taken. Please choose another.';
        }
    }
    
    // Validate email
    if (isEmpty($email)) {
        $errors[] = 'Email is required.';
    } elseif (!isValidEmail($email)) {
        $errors[] = 'Please enter a valid email address.';
    } else {
        // Check if email exists
        $checkEmail = fetchOne("SELECT id FROM users WHERE email = ?", [$email]);
        if ($checkEmail) {
            $errors[] = 'Email already registered. Please login or use another email.';
        }
    }
    
    // Validate password
    if (isEmpty($password)) {
        $errors[] = 'Password is required.';
    } elseif (!isValidPassword($password)) {
        $errors[] = 'Password must be at least ' . MIN_PASSWORD_LENGTH . ' characters long.';
    }
    
    // Validate password confirmation
    if ($password !== $confirmPassword) {
        $errors[] = 'Passwords do not match.';
    }
    
    // If no errors, create account
    if (empty($errors)) {
        try {
            // Begin transaction
            beginTransaction();
            
            // Hash password
            $hashedPassword = hashPassword($password);
            
            // Insert user
            $sql = "INSERT INTO users (username, email, password, role, created_at) 
                    VALUES (?, ?, ?, 'user', NOW())";
            $userId = insertRecord($sql, [$username, $email, $hashedPassword]);
            
            if ($userId) {
            // Assign "Newcomer" badge (badge ID 1)
            $badgeSql = "INSERT INTO user_badges (user_id, badge_id, earned_at) 
                        VALUES (?, 1, NOW())";
            executeQuery($badgeSql, [$userId]);
            
            // Commit transaction
            commit();
            
            // Set success message for login page
            setFlashMessage('Registration successful! Please login with your credentials.', 'success');
            
            // Redirect to login page (NOT auto-login)
            redirect('auth/login.php');
            
        } else {
            rollback();
            $errors[] = 'Registration failed. Please try again.';
        }
            
        } catch (Exception $e) {
            rollback();
            error_log("Registration Error: " . $e->getMessage());
            $errors[] = 'An error occurred during registration. Please try again.';
        }
    }
}

// Set page title
$pageTitle = 'Sign Up - ' . SITE_NAME;
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
                        <h2 class="mt-3 mb-2">Create Account</h2>
                        <p class="text-muted">Join <?php echo SITE_NAME; ?> and start sharing your stories</p>
                    </div>
                    
                    <!-- Display Errors -->
                    <?php if (!empty($errors)): ?>
                    <div class="alert alert-danger alert-dismissible fade show" role="alert">
                        <i class="fas fa-exclamation-circle me-2"></i>
                        <strong>Registration Failed:</strong>
                        <ul class="mb-0 mt-2">
                            <?php foreach ($errors as $error): ?>
                                <li><?php echo e($error); ?></li>
                            <?php endforeach; ?>
                        </ul>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                    <?php endif; ?>
                    
                    <!-- Registration Form -->
                    <form method="POST" action="" id="registerForm">
                        <?php echo csrfField(); ?>
                        
                        <!-- Username Field -->
                        <div class="mb-3">
                            <label for="username" class="form-label">
                                <i class="fas fa-user me-1"></i> Username
                            </label>
                            <input type="text" 
                                   class="form-control" 
                                   id="username" 
                                   name="username" 
                                   value="<?php echo e($username); ?>"
                                   placeholder="Choose a unique username"
                                   required
                                   minlength="<?php echo MIN_USERNAME_LENGTH; ?>"
                                   maxlength="<?php echo MAX_USERNAME_LENGTH; ?>"
                                   pattern="[a-zA-Z0-9_]+"
                                   title="Only letters, numbers and underscores allowed">
                            <small class="form-text text-muted">
                                3-50 characters, letters, numbers and underscores only
                            </small>
                        </div>
                        
                        <!-- Email Field -->
                        <div class="mb-3">
                            <label for="email" class="form-label">
                                <i class="fas fa-envelope me-1"></i> Email Address
                            </label>
                            <input type="email" 
                                   class="form-control" 
                                   id="email" 
                                   name="email" 
                                   value="<?php echo e($email); ?>"
                                   placeholder="your.email@example.com"
                                   required>
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
                                       placeholder="Create a strong password"
                                       required
                                       minlength="<?php echo MIN_PASSWORD_LENGTH; ?>">
                                <button class="btn btn-outline-secondary" type="button" id="togglePassword">
                                    <i class="fas fa-eye"></i>
                                </button>
                            </div>
                            <small class="form-text text-muted">
                                Minimum <?php echo MIN_PASSWORD_LENGTH; ?> characters
                            </small>
                        </div>
                        
                        <!-- Confirm Password Field -->
                        <div class="mb-3">
                            <label for="confirm_password" class="form-label">
                                <i class="fas fa-lock me-1"></i> Confirm Password
                            </label>
                            <div class="input-group">
                                <input type="password" 
                                       class="form-control" 
                                       id="confirm_password" 
                                       name="confirm_password" 
                                       placeholder="Re-enter your password"
                                       required
                                       minlength="<?php echo MIN_PASSWORD_LENGTH; ?>">
                                <button class="btn btn-outline-secondary" type="button" id="toggleConfirmPassword">
                                    <i class="fas fa-eye"></i>
                                </button>
                            </div>
                        </div>
                        
                        <!-- Terms Agreement -->
                        <div class="mb-3 form-check">
                            <input type="checkbox" class="form-check-input" id="terms" required>
                            <label class="form-check-label small" for="terms">
                                I agree to the Terms of Service and Privacy Policy
                            </label>
                        </div>
                        
                        <!-- Submit Button -->
                        <button type="submit" class="btn btn-primary w-100 mb-3">
                            <i class="fas fa-user-plus me-2"></i> Create Account
                        </button>
                        
                        <!-- Divider -->
                        <div class="text-center text-muted my-3">
                            <small>Already have an account?</small>
                        </div>
                        
                        <!-- Login Link -->
                        <a href="<?php echo SITE_URL; ?>/auth/login.php" class="btn btn-outline-primary w-100">
                            <i class="fas fa-sign-in-alt me-2"></i> Login Instead
                        </a>
                    </form>
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

<!-- Custom JavaScript for Registration -->
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

document.getElementById('toggleConfirmPassword').addEventListener('click', function() {
    const confirmPasswordInput = document.getElementById('confirm_password');
    const icon = this.querySelector('i');
    
    if (confirmPasswordInput.type === 'password') {
        confirmPasswordInput.type = 'text';
        icon.classList.remove('fa-eye');
        icon.classList.add('fa-eye-slash');
    } else {
        confirmPasswordInput.type = 'password';
        icon.classList.remove('fa-eye-slash');
        icon.classList.add('fa-eye');
    }
});

// Form validation
document.getElementById('registerForm').addEventListener('submit', function(e) {
    const password = document.getElementById('password').value;
    const confirmPassword = document.getElementById('confirm_password').value;
    
    // Check if passwords match
    if (password !== confirmPassword) {
        e.preventDefault();
        Swal.fire({
            icon: 'error',
            title: 'Passwords Do Not Match',
            text: 'Please make sure both passwords are identical.',
            confirmButtonColor: '#FFB100'
        });
        return false;
    }
    
    // Check password length
    if (password.length < <?php echo MIN_PASSWORD_LENGTH; ?>) {
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

// Username validation on input
document.getElementById('username').addEventListener('input', function(e) {
    const username = e.target.value;
    const pattern = /^[a-zA-Z0-9_]+$/;
    
    if (username && !pattern.test(username)) {
        e.target.setCustomValidity('Only letters, numbers and underscores allowed');
    } else {
        e.target.setCustomValidity('');
    }
});
</script>

<?php
$customJS = '<script src="' . JS_URL . '/login.js"></script>';
include '../includes/footer.php'; 
?>