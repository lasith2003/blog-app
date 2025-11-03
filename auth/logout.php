<?php
/**
 * ================================================================
 * BLOG HUT - User Logout
 * University of Moratuwa - IN2120 Web Programming Project
 * ================================================================
 * 
 * This file handles user logout with:
 * - Session destruction
 * - Cookie removal (remember me)
 * - Redirect to login page
 * - Security confirmation
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

// Check if user is logged in
if (!isLoggedIn()) {
    redirect('/auth/login.php');
}

// Get username before destroying session
$username = getCurrentUsername();

// Destroy session
destroySession();

// Remove remember me cookie if exists
if (isset($_COOKIE['remember_token'])) {
    setcookie('remember_token', '', time() - 3600, '/');
}

// Start new session for flash message
session_start();

// Set logout success message
setFlashMessage('You have been logged out successfully. See you soon, ' . $username . '!', 'success');

// Redirect to login page
redirect('auth/login.php');
?>