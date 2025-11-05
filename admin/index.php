<?php
/**
 * BLOG HUT - Admin Index
 */

// Start session
session_start();

// Include required files
try {
    require_once '../config/database.php';
    require_once '../config/constants.php';
    require_once '../includes/helper.php';
} catch (Exception $e) {
    die("Configuration Error: Unable to load required files. Please check your installation.");
}

// Function to safely redirect
function safeRedirect($path) {
    if (defined('SITE_URL')) {
        $fullPath = SITE_URL . '/' . ltrim($path, '/');
    } else {
        $fullPath = '/BLOG_APP/' . ltrim($path, '/');
    }
    
    header("Location: " . $fullPath);
    exit();
}

// Check if user is logged in
if (!isset($_SESSION['user_id']) || empty($_SESSION['user_id'])) {
    // Store the requested URL for redirect after login
    $_SESSION['redirect_after_login'] = $_SERVER['REQUEST_URI'];
    
    // Set flash message
    if (function_exists('setFlashMessage')) {
        setFlashMessage('Please login to access the admin panel.', 'warning');
    }
    
    // Redirect to login
    safeRedirect('auth/login.php');
}

// Check if user has admin privileges
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    // User is not an admin
    if (function_exists('setFlashMessage')) {
        setFlashMessage('Access denied. Administrator privileges required.', 'danger');
    }
    
    // Log unauthorized access attempt
    error_log("Unauthorized admin access attempt by user ID: " . $_SESSION['user_id']);
    
    // Redirect to home page
    safeRedirect('index.php');
}

// User is authenticated and authorized - redirect to dashboard
safeRedirect('admin/dashboard.php');
?>