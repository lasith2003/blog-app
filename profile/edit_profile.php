<?php
/**
 * ================================================================
 * BLOG HUT - Edit Profile Page
 * University of Moratuwa - IN2120 Web Programming Project
 * ================================================================
 * 
 * This page allows users to edit their profile:
 * - Update username
 * - Update email
 * - Update bio
 * - Change profile picture
 * - Change password
 * - Form validation
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

// Require login
requireLogin();

$userId = getCurrentUserId();

try {
    // Get user details
    $user = fetchOne("SELECT * FROM users WHERE id = ?", [$userId]);
    
    if (!$user) {
        setFlashMessage('User not found.', 'error');
        redirect('/posts/home.php');
    }
    
} catch (Exception $e) {
    error_log("Edit Profile Error: " . $e->getMessage());
    setFlashMessage('An error occurred.', 'error');
    redirect('/posts/home.php');
}

// Initialize variables
$errors = [];
$username = $user['username'];
$email = $user['email'];
$bio = $user['bio'];
$currentProfileImage = $user['profile_image'];

// Process form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Get form data
    $username = cleanInput($_POST['username'] ?? '');
    $email = cleanInput($_POST['email'] ?? '');
    $bio = cleanInput($_POST['bio'] ?? '');
    $currentPassword = $_POST['current_password'] ?? '';
    $newPassword = $_POST['new_password'] ?? '';
    $confirmPassword = $_POST['confirm_password'] ?? '';
    $removeImage = isset($_POST['remove_image']);
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
    } elseif ($username !== $user['username']) {
        // Check if new username is taken
        $checkUsername = fetchOne("SELECT id FROM users WHERE username = ? AND id != ?", [$username, $userId]);
        if ($checkUsername) {
            $errors[] = 'Username already taken.';
        }
    }
    
    // Validate email
    if (isEmpty($email)) {
        $errors[] = 'Email is required.';
    } elseif (!isValidEmail($email)) {
        $errors[] = 'Please enter a valid email address.';
    } elseif ($email !== $user['email']) {
        // Check if new email is taken
        $checkEmail = fetchOne("SELECT id FROM users WHERE email = ? AND id != ?", [$email, $userId]);
        if ($checkEmail) {
            $errors[] = 'Email already registered.';
        }
    }
    
    // Validate bio
    if (!isEmpty($bio) && strlen($bio) > MAX_BIO_LENGTH) {
        $errors[] = 'Bio must not exceed ' . MAX_BIO_LENGTH . ' characters.';
    }
    
    // Validate password change if provided
    $passwordUpdate = false;
    if (!isEmpty($newPassword)) {
        if (isEmpty($currentPassword)) {
            $errors[] = 'Current password is required to change password.';
        } elseif (!verifyPassword($currentPassword, $user['password'])) {
            $errors[] = 'Current password is incorrect.';
        } elseif (!isValidPassword($newPassword)) {
            $errors[] = 'New password must be at least ' . MIN_PASSWORD_LENGTH . ' characters.';
        } elseif ($newPassword !== $confirmPassword) {
            $errors[] = 'New passwords do not match.';
        } else {
            $passwordUpdate = true;
        }
    }
    
    // Handle image removal
    if ($removeImage && $currentProfileImage && $currentProfileImage !== DEFAULT_AVATAR) {
        $imagePath = AVATAR_PATH . '/' . $currentProfileImage;
        if (deleteFile($imagePath)) {
            $currentProfileImage = DEFAULT_AVATAR;
        }
    }
    
    // Handle profile image upload
    $profileImage = $currentProfileImage;
    if (isset($_FILES['profile_image']) && $_FILES['profile_image']['error'] !== UPLOAD_ERR_NO_FILE) {
        // Delete old image if exists and not default
        if ($currentProfileImage && $currentProfileImage !== DEFAULT_AVATAR) {
            deleteFile(AVATAR_PATH . '/' . $currentProfileImage);
        }
        
        $uploadResult = handleFileUpload(
            $_FILES['profile_image'],
            AVATAR_PATH,
            ALLOWED_AVATAR_TYPES,
            MAX_AVATAR_SIZE
        );
        
        if ($uploadResult['success']) {
            $profileImage = $uploadResult['filename'];
        } else {
            $errors[] = $uploadResult['error'];
        }
    }
    
    // If no errors, update profile
    if (empty($errors)) {
        try {
            beginTransaction();
            
            // Update profile
            if ($passwordUpdate) {
                $sql = "UPDATE users SET username = ?, email = ?, bio = ?, profile_image = ?, password = ? WHERE id = ?";
                $params = [$username, $email, $bio, $profileImage, hashPassword($newPassword), $userId];
            } else {
                $sql = "UPDATE users SET username = ?, email = ?, bio = ?, profile_image = ? WHERE id = ?";
                $params = [$username, $email, $bio, $profileImage, $userId];
            }
            
            if (updateRecord($sql, $params) >= 0) {
                // Update session data
                setSession('username', $username);
                setSession('email', $email);
                setSession('profile_image', $profileImage);
                
                commit();
                setFlashMessage('Profile updated successfully!', 'success');
                redirect('/profile/my_profile.php');
            } else {
                rollback();
                $errors[] = 'Failed to update profile.';
            }
            
        } catch (Exception $e) {
            rollback();
            error_log("Profile Update Error: " . $e->getMessage());
            $errors[] = 'An error occurred while updating your profile.';
        }
    }
}

// Set page title
$pageTitle = 'Edit Profile - ' . SITE_NAME;
?>
<?php include '../includes/header.php'; ?>

<div class="container my-5">
    <div class="row justify-content-center">
        <div class="col-lg-8">
            <!-- Page Header -->
            <div class="text-center mb-5" data-aos="fade-down">
                <h1 class="fw-bold mb-2">
                    <i class="fas fa-user-edit text-primary me-2"></i>
                    Edit Profile
                </h1>
                <p class="text-muted">Update your account information</p>
            </div>
            
            <!-- Display Errors -->
            <?php if (!empty($errors)): ?>
            <div class="alert alert-danger alert-dismissible fade show" role="alert" data-aos="fade-up">
                <i class="fas fa-exclamation-circle me-2"></i>
                <strong>Please fix the following errors:</strong>
                <ul class="mb-0 mt-2">
                    <?php foreach ($errors as $error): ?>
                        <li><?php echo e($error); ?></li>
                    <?php endforeach; ?>
                </ul>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
            <?php endif; ?>
            
            <!-- Edit Profile Form -->
            <form method="POST" action="" enctype="multipart/form-data" id="editProfileForm" data-aos="fade-up">
                <?php echo csrfField(); ?>
                
                <!-- Profile Picture Section -->
                <div class="card shadow-sm mb-4">
                    <div class="card-body">
                        <h5 class="card-title mb-3">
                            <i class="fas fa-camera me-2"></i> Profile Picture
                        </h5>
                        
                        <div class="row align-items-center">
                            <div class="col-md-3 text-center mb-3 mb-md-0">
                                <img src="<?php echo AVATAR_URL . '/' . $currentProfileImage; ?>" 
                                     alt="Current profile picture"
                                     id="currentAvatar"
                                     class="rounded-circle img-fluid"
                                     style="width: 120px; height: 120px; object-fit: cover;">
                            </div>
                            <div class="col-md-9">
                                <div class="mb-3">
                                    <input type="file" 
                                           class="form-control" 
                                           id="profile_image" 
                                           name="profile_image"
                                           accept="image/*">
                                    <small class="form-text text-muted">
                                        Max size: <?php echo formatFileSize(MAX_AVATAR_SIZE); ?>. 
                                        Supported: JPG, PNG, GIF
                                    </small>
                                </div>
                                
                                <?php if ($currentProfileImage && $currentProfileImage !== DEFAULT_AVATAR): ?>
                                <div class="form-check">
                                    <input class="form-check-input" 
                                           type="checkbox" 
                                           id="remove_image" 
                                           name="remove_image">
                                    <label class="form-check-label" for="remove_image">
                                        Remove current profile picture
                                    </label>
                                </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Basic Information Section -->
                <div class="card shadow-sm mb-4">
                    <div class="card-body">
                        <h5 class="card-title mb-3">
                            <i class="fas fa-user me-2"></i> Basic Information
                        </h5>
                        
                        <!-- Username -->
                        <div class="mb-3">
                            <label for="username" class="form-label">
                                Username <span class="text-danger">*</span>
                            </label>
                            <input type="text" 
                                   class="form-control" 
                                   id="username" 
                                   name="username" 
                                   value="<?php echo e($username); ?>"
                                   required
                                   minlength="<?php echo MIN_USERNAME_LENGTH; ?>"
                                   maxlength="<?php echo MAX_USERNAME_LENGTH; ?>"
                                   pattern="[a-zA-Z0-9_]+">
                            <small class="form-text text-muted">
                                3-50 characters, letters, numbers and underscores only
                            </small>
                        </div>
                        
                        <!-- Email -->
                        <div class="mb-3">
                            <label for="email" class="form-label">
                                Email <span class="text-danger">*</span>
                            </label>
                            <input type="email" 
                                   class="form-control" 
                                   id="email" 
                                   name="email" 
                                   value="<?php echo e($email); ?>"
                                   required>
                        </div>
                        
                        <!-- Bio -->
                        <div class="mb-3">
                            <label for="bio" class="form-label">Bio</label>
                            <textarea class="form-control" 
                                      id="bio" 
                                      name="bio" 
                                      rows="4"
                                      maxlength="<?php echo MAX_BIO_LENGTH; ?>"
                                      placeholder="Tell us about yourself..."><?php echo e($bio); ?></textarea>
                            <small class="form-text text-muted">
                                Optional. Max <?php echo MAX_BIO_LENGTH; ?> characters.
                            </small>
                        </div>
                    </div>
                </div>
                
                <!-- Change Password Section -->
                <div class="card shadow-sm mb-4">
                    <div class="card-body">
                        <h5 class="card-title mb-3">
                            <i class="fas fa-lock me-2"></i> Change Password
                        </h5>
                        <p class="text-muted small mb-3">
                            Leave blank if you don't want to change your password
                        </p>
                        
                        <!-- Current Password -->
                        <div class="mb-3">
                            <label for="current_password" class="form-label">Current Password</label>
                            <input type="password" 
                                   class="form-control" 
                                   id="current_password" 
                                   name="current_password"
                                   placeholder="Enter current password">
                        </div>
                        
                        <!-- New Password -->
                        <div class="mb-3">
                            <label for="new_password" class="form-label">New Password</label>
                            <input type="password" 
                                   class="form-control" 
                                   id="new_password" 
                                   name="new_password"
                                   placeholder="Enter new password"
                                   minlength="<?php echo MIN_PASSWORD_LENGTH; ?>">
                            <small class="form-text text-muted">
                                Minimum <?php echo MIN_PASSWORD_LENGTH; ?> characters
                            </small>
                        </div>
                        
                        <!-- Confirm Password -->
                        <div class="mb-3">
                            <label for="confirm_password" class="form-label">Confirm New Password</label>
                            <input type="password" 
                                   class="form-control" 
                                   id="confirm_password" 
                                   name="confirm_password"
                                   placeholder="Re-enter new password"
                                   minlength="<?php echo MIN_PASSWORD_LENGTH; ?>">
                        </div>
                    </div>
                </div>
                
                <!-- Action Buttons -->
                <div class="d-flex gap-2 justify-content-between">
                    <a href="<?php echo SITE_URL; ?>/profile/my_profile.php" class="btn btn-outline-secondary">
                        <i class="fas fa-times me-2"></i> Cancel
                    </a>
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save me-2"></i> Save Changes
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Image Preview Script -->
<script>
// Profile image preview
document.getElementById('profile_image').addEventListener('change', function(e) {
    const file = e.target.files[0];
    
    if (file) {
        const reader = new FileReader();
        reader.onload = function(e) {
            document.getElementById('currentAvatar').src = e.target.result;
        }
        reader.readAsDataURL(file);
    }
});

// Form validation
document.getElementById('editProfileForm').addEventListener('submit', function(e) {
    const newPassword = document.getElementById('new_password').value;
    const confirmPassword = document.getElementById('confirm_password').value;
    
    // Check if password fields are filled
    if (newPassword || confirmPassword) {
        const currentPassword = document.getElementById('current_password').value;
        
        if (!currentPassword) {
            e.preventDefault();
            Swal.fire({
                icon: 'error',
                title: 'Current Password Required',
                text: 'Please enter your current password to change it.',
                confirmButtonColor: '#FFB100'
            });
            return false;
        }
        
        if (newPassword !== confirmPassword) {
            e.preventDefault();
            Swal.fire({
                icon: 'error',
                title: 'Passwords Do Not Match',
                text: 'New password and confirmation must be identical.',
                confirmButtonColor: '#FFB100'
            });
            return false;
        }
        
        if (newPassword.length < <?php echo MIN_PASSWORD_LENGTH; ?>) {
            e.preventDefault();
            Swal.fire({
                icon: 'error',
                title: 'Password Too Short',
                text: 'New password must be at least <?php echo MIN_PASSWORD_LENGTH; ?> characters.',
                confirmButtonColor: '#FFB100'
            });
            return false;
        }
    }
});

// Username validation
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

<?php include '../includes/footer.php'; ?>