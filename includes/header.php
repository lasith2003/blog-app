<?php
/**
 * BLOG HUT - Site Header
 * This file contains the site header with:
 * - HTML head section
 * - Navigation bar
 * - User menu
 * - Theme toggle
 */

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Include required files
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/constants.php';
require_once __DIR__ . '/helper.php';

// Set default page title if not set
$pageTitle = $pageTitle ?? SITE_NAME;
$pageDescription = $pageDescription ?? SITE_DESCRIPTION;
$pageKeywords = $pageKeywords ?? SITE_KEYWORDS;
?>
<!DOCTYPE html>
<html lang="en" data-theme="light">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    
    <!-- SEO Meta Tags -->
    <title><?php echo e($pageTitle); ?></title>
    <meta name="description" content="<?php echo e($pageDescription); ?>">
    <meta name="keywords" content="<?php echo e($pageKeywords); ?>">
    <meta name="author" content="<?php echo SITE_AUTHOR; ?>">
    
    <!-- Open Graph Meta Tags (for social sharing) -->
    <meta property="og:title" content="<?php echo e($pageTitle); ?>">
    <meta property="og:description" content="<?php echo e($pageDescription); ?>">
    <meta property="og:type" content="website">
    <meta property="og:url" content="<?php echo currentUrl(); ?>">
    <meta property="og:site_name" content="<?php echo SITE_NAME; ?>">
    
    <!-- Favicon -->
    <link rel="icon" type="image/x-icon" href="<?php echo IMAGES_URL; ?>/favicon.ico">
    
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    
    <!-- FontAwesome 6 Icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    
    <!-- AOS (Animate On Scroll) -->
    <link href="https://unpkg.com/aos@2.3.1/dist/aos.css" rel="stylesheet">
    
    <!-- Google Fonts - Poppins -->
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    
    <!-- Custom CSS -->
    <link rel="stylesheet" href="<?php echo CSS_URL; ?>/styles.css">
    <?php if (isset($customCSS)) echo $customCSS; ?>
    
    <!-- SweetAlert2 for beautiful alerts -->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
</head>
<body>
    
    <!-- Navigation Bar -->
    <nav class="navbar navbar-expand-lg navbar-light bg-white sticky-top shadow-sm">
        <div class="container">
            <!-- Logo/Brand -->
            <a class="navbar-brand d-flex align-items-center" href="<?php echo SITE_URL; ?>/index.php">
                <i class="fas fa-blog text-primary me-2" style="font-size: 1.8rem;"></i>
                <span class="fw-bold"><?php echo SITE_NAME; ?></span>
            </a>
            
            <!-- Mobile Toggle Button -->
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            
            <!-- Navigation Menu -->
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav mx-auto">
                    <li class="nav-item">
                        <a class="nav-link" href="<?php echo SITE_URL; ?>/index.php">
                            <i class="fas fa-home me-1"></i> Home
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="<?php echo SITE_URL; ?>/posts/home.php">
                            <i class="fas fa-newspaper me-1"></i> Blogs
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="<?php echo SITE_URL; ?>/posts/search.php">
                            <i class="fas fa-search me-1"></i> Search
                        </a>
                    </li>
                    
                    <?php if (isLoggedIn()): ?>
                    <li class="nav-item">
                        <a class="nav-link" href="<?php echo SITE_URL; ?>/posts/create_blog.php">
                            <i class="fas fa-pen me-1"></i> Write
                        </a>
                    </li>
                    <?php endif; ?>
                </ul>
                
                <!-- Right Side Menu -->
                <ul class="navbar-nav ms-auto align-items-center">
                    <!-- Theme Toggle -->
                    <li class="nav-item me-3">
                        <button class="btn btn-sm btn-outline-secondary rounded-circle" id="themeToggle" title="Toggle Theme">
                            <i class="fas fa-moon" id="themeIcon"></i>
                        </button>
                    </li>
                    
                    <?php if (isLoggedIn()): ?>
                        <!-- Logged In User Menu -->
                        <li class="nav-item dropdown">
                            <a class="nav-link dropdown-toggle d-flex align-items-center" href="#" id="userDropdown" 
                               role="button" data-bs-toggle="dropdown" aria-expanded="false">
                                <img src="<?php echo AVATAR_URL . '/' . (getSession('profile_image') ?? DEFAULT_AVATAR); ?>" 
                                     alt="Profile" class="rounded-circle me-2" style="width: 35px; height: 35px; object-fit: cover;">
                                <span><?php echo e(getCurrentUsername()); ?></span>
                            </a>
                            <ul class="dropdown-menu dropdown-menu-end">
                                <li>
                                    <a class="dropdown-item" href="<?php echo SITE_URL; ?>/profile/my_profile.php">
                                        <i class="fas fa-user me-2"></i> My Profile
                                    </a>
                                </li>
                                <li>
                                    <a class="dropdown-item" href="<?php echo SITE_URL; ?>/profile/my_blogs.php">
                                        <i class="fas fa-file-alt me-2"></i> My Blogs
                                    </a>
                                </li>
                                <li>
                                    <a class="dropdown-item" href="<?php echo SITE_URL; ?>/profile/edit_profile.php">
                                        <i class="fas fa-cog me-2"></i> Settings
                                    </a>
                                </li>
                                
                                <?php if (isUserAdmin()): ?>
                                <li><hr class="dropdown-divider"></li>
                                <li>
                                    <a class="dropdown-item text-primary" href="<?php echo SITE_URL; ?>/admin/dashboard.php">
                                        <i class="fas fa-shield-alt me-2"></i> Admin Panel
                                    </a>
                                </li>
                                <?php endif; ?>
                                
                                <li><hr class="dropdown-divider"></li>
                                <li>
                                    <a class="dropdown-item text-danger" href="<?php echo SITE_URL; ?>/auth/logout.php">
                                        <i class="fas fa-sign-out-alt me-2"></i> Logout
                                    </a>
                                </li>
                            </ul>
                        </li>
                    <?php else: ?>
                        <!-- Guest User Menu -->
                        <li class="nav-item me-2">
                            <a class="btn btn-outline-primary btn-sm" href="<?php echo SITE_URL; ?>/auth/login.php">
                                <i class="fas fa-sign-in-alt me-1"></i> Login
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="btn btn-primary btn-sm" href="<?php echo SITE_URL; ?>/auth/register.php">
                                <i class="fas fa-user-plus me-1"></i> Sign Up
                            </a>
                        </li>
                    <?php endif; ?>
                </ul>
            </div>
        </div>
    </nav>
    
    <!-- Flash Messages Display -->
    <?php 
    $flashMessage = getFlashMessage();
    if ($flashMessage): 
    ?>
    <div class="container mt-3">
        <?php echo displayFlashMessage(); ?>
    </div>
    <?php endif; ?>
    
    <!-- Main Content Starts Here -->
    <main class="main-content">