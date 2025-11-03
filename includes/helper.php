<?php
/**
 * ================================================================
 * BLOG HUT - Helper Functions
 * University of Moratuwa - IN2120 Web Programming Project
 * ================================================================
 * 
 * This file contains utility functions used throughout the application:
 * - Session management
 * - Security functions (XSS, CSRF)
 * - Input validation and sanitization
 * - Date/time formatting
 * - File upload handling
 * - URL helpers
 * - Alert/notification helpers
 * 
 * @package BlogHut
 * @author Your Name
 */

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// ================================================================
// SESSION MANAGEMENT
// ================================================================

/**
 * Set session variable
 * 
 * @param string $key Session key
 * @param mixed $value Session value
 */
function setSession($key, $value) {
    $_SESSION[$key] = $value;
}

/**
 * Get session variable
 * 
 * @param string $key Session key
 * @param mixed $default Default value if key doesn't exist
 * @return mixed
 */
function getSession($key, $default = null) {
    return $_SESSION[$key] ?? $default;
}

/**
 * Check if session variable exists
 * 
 * @param string $key Session key
 * @return bool
 */
function hasSession($key) {
    return isset($_SESSION[$key]);
}

/**
 * Unset session variable
 * 
 * @param string $key Session key
 */
function unsetSession($key) {
    if (isset($_SESSION[$key])) {
        unset($_SESSION[$key]);
    }
}

/**
 * Destroy entire session
 */
function destroySession() {
    session_unset();
    session_destroy();
}

/**
 * Check if user is logged in
 * 
 * @return bool
 */
function isLoggedIn() {
    return hasSession('user_id') && hasSession('username');
}

/**
 * Check if current user is admin
 * 
 * @return bool
 */
function isUserAdmin() {
    return isLoggedIn() && getSession('role') === ROLE_ADMIN;
}

/**
 * Get current user ID
 * 
 * @return int|null
 */
function getCurrentUserId() {
    return getSession('user_id');
}

/**
 * Get current username
 * 
 * @return string|null
 */
function getCurrentUsername() {
    return getSession('username');
}

/**
 * Require user to be logged in (redirect to login if not)
 * 
 * @param string $redirect Redirect URL after login
 */
function requireLogin($redirect = null) {
    if (!isLoggedIn()) {
        $redirectUrl = $redirect ?? $_SERVER['REQUEST_URI'];
        setSession('redirect_after_login', $redirectUrl);
        redirect('/auth/login.php');
        exit;
    }
}

/**
 * Require user to be admin (redirect if not)
 */
function requireAdmin() {
    requireLogin();
    if (!isUserAdmin()) {
        setFlashMessage('You do not have permission to access this page.', 'error');
        redirect(SITE_URL . '/posts/home.php');
        exit;
    }
}

// ================================================================
// SECURITY FUNCTIONS
// ================================================================

/**
 * Sanitize input to prevent XSS attacks
 * 
 * @param string $data Input data
 * @return string Sanitized data
 */
function sanitize($data) {
    return htmlspecialchars($data, ENT_QUOTES, 'UTF-8');
}

/**
 * Escape output for safe HTML display
 * 
 * @param string $data Data to escape
 * @return string Escaped data
 */
function e($data) {
    return sanitize($data);
}

/**
 * Clean input by removing extra spaces and slashes
 * 
 * @param string $data Input data
 * @return string Cleaned data
 */
function cleanInput($data) {
    $data = trim($data);
    $data = stripslashes($data);
    return $data;
}

/**
 * Generate CSRF token
 * 
 * @return string CSRF token
 */
function generateCSRFToken() {
    if (!hasSession('csrf_token')) {
        $token = bin2hex(random_bytes(CSRF_TOKEN_LENGTH));
        setSession('csrf_token', $token);
    }
    return getSession('csrf_token');
}

/**
 * Verify CSRF token
 * 
 * @param string $token Token to verify
 * @return bool
 */
function verifyCSRFToken($token) {
    return hasSession('csrf_token') && hash_equals(getSession('csrf_token'), $token);
}

/**
 * Generate CSRF token input field
 * 
 * @return string HTML input field
 */
function csrfField() {
    $token = generateCSRFToken();
    return '<input type="hidden" name="csrf_token" value="' . $token . '">';
}

/**
 * Hash password securely
 * 
 * @param string $password Plain text password
 * @return string Hashed password
 */
function hashPassword($password) {
    return password_hash($password, PASSWORD_HASH_ALGO);
}

/**
 * Verify password against hash
 * 
 * @param string $password Plain text password
 * @param string $hash Hashed password
 * @return bool
 */
function verifyPassword($password, $hash) {
    return password_verify($password, $hash);
}

// ================================================================
// INPUT VALIDATION
// ================================================================

/**
 * Validate email address
 * 
 * @param string $email Email to validate
 * @return bool
 */
function isValidEmail($email) {
    return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
}

/**
 * Validate username
 * 
 * @param string $username Username to validate
 * @return bool
 */
function isValidUsername($username) {
    $length = strlen($username);
    return $length >= MIN_USERNAME_LENGTH && 
           $length <= MAX_USERNAME_LENGTH && 
           preg_match('/^[a-zA-Z0-9_]+$/', $username);
}

/**
 * Validate password strength
 * 
 * @param string $password Password to validate
 * @return bool
 */
function isValidPassword($password) {
    return strlen($password) >= MIN_PASSWORD_LENGTH;
}

/**
 * Validate URL
 * 
 * @param string $url URL to validate
 * @return bool
 */
function isValidUrl($url) {
    return filter_var($url, FILTER_VALIDATE_URL) !== false;
}

/**
 * Check if string is empty after trimming
 * 
 * @param string $str String to check
 * @return bool
 */
function isEmpty($str) {
    return trim($str) === '';
}

// ================================================================
// FILE UPLOAD HANDLING
// ================================================================

/**
 * Handle file upload
 * 
 * @param array $file $_FILES array element
 * @param string $uploadDir Upload directory
 * @param array $allowedTypes Allowed MIME types
 * @param int $maxSize Maximum file size in bytes
 * @return array ['success' => bool, 'filename' => string, 'error' => string]
 */
function handleFileUpload($file, $uploadDir, $allowedTypes, $maxSize) {
    // Check if file was uploaded
    if (!isset($file) || $file['error'] === UPLOAD_ERR_NO_FILE) {
        return ['success' => false, 'error' => 'No file uploaded'];
    }
    
    // Check for upload errors
    if ($file['error'] !== UPLOAD_ERR_OK) {
        return ['success' => false, 'error' => 'File upload error'];
    }
    
    // Check file size
    if ($file['size'] > $maxSize) {
        return ['success' => false, 'error' => 'File too large. Maximum size: ' . formatFileSize($maxSize)];
    }
    
    // Check file type
    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    $mimeType = finfo_file($finfo, $file['tmp_name']);
    finfo_close($finfo);
    
    if (!in_array($mimeType, $allowedTypes)) {
        return ['success' => false, 'error' => 'Invalid file type'];
    }
    
    // Generate unique filename
    $extension = pathinfo($file['name'], PATHINFO_EXTENSION);
    $filename = uniqid() . '_' . time() . '.' . $extension;
    $destination = $uploadDir . '/' . $filename;
    
    // Create directory if it doesn't exist
    if (!file_exists($uploadDir)) {
        mkdir($uploadDir, 0755, true);
    }
    
    // Move uploaded file
    if (move_uploaded_file($file['tmp_name'], $destination)) {
        return ['success' => true, 'filename' => $filename];
    } else {
        return ['success' => false, 'error' => 'Failed to move uploaded file'];
    }
}

/**
 * Delete file from server
 * 
 * @param string $filePath Full path to file
 * @return bool
 */
function deleteFile($filePath) {
    if (file_exists($filePath) && is_file($filePath)) {
        return unlink($filePath);
    }
    return false;
}

// ================================================================
// DATE & TIME FORMATTING
// ================================================================

/**
 * Format date for display
 * 
 * @param string $date Date string
 * @param string $format Date format (optional)
 * @return string Formatted date
 */
function formatDate($date, $format = DATE_FORMAT) {
    return date($format, strtotime($date));
}

/**
 * Get time ago string (e.g., "2 hours ago")
 * 
 * @param string $datetime Datetime string
 * @return string Time ago string
 */
function timeAgo($datetime) {
    $time = strtotime($datetime);
    $now = time();
    $diff = $now - $time;
    
    if ($diff < 60) {
        return 'just now';
    } elseif ($diff < 3600) {
        $mins = floor($diff / 60);
        return $mins . ' minute' . ($mins > 1 ? 's' : '') . ' ago';
    } elseif ($diff < 86400) {
        $hours = floor($diff / 3600);
        return $hours . ' hour' . ($hours > 1 ? 's' : '') . ' ago';
    } elseif ($diff < 604800) {
        $days = floor($diff / 86400);
        return $days . ' day' . ($days > 1 ? 's' : '') . ' ago';
    } elseif ($diff < 2592000) {
        $weeks = floor($diff / 604800);
        return $weeks . ' week' . ($weeks > 1 ? 's' : '') . ' ago';
    } elseif ($diff < 31536000) {
        $months = floor($diff / 2592000);
        return $months . ' month' . ($months > 1 ? 's' : '') . ' ago';
    } else {
        $years = floor($diff / 31536000);
        return $years . ' year' . ($years > 1 ? 's' : '') . ' ago';
    }
}

// ================================================================
// URL HELPERS
// ================================================================

/**
 * Redirect to URL
 * 
 * @param string $url URL to redirect to
 */
function redirect($url) {
    if (!headers_sent()) {
        header('Location: ' . $url);
        exit;
    } else {
        echo '<script>window.location.href="' . $url . '";</script>';
        exit;
    }
}

/**
 * Get current page URL
 * 
 * @return string Current URL
 */
function currentUrl() {
    return $_SERVER['REQUEST_URI'] ?? '/';
}

/**
 * Get base URL
 * 
 * @return string Base URL
 */
function baseUrl() {
    return SITE_URL;
}

/**
 * Create URL with parameters
 * 
 * @param string $path Path
 * @param array $params Query parameters
 * @return string Full URL
 */
function url($path, $params = []) {
    $url = SITE_URL . '/' . ltrim($path, '/');
    if (!empty($params)) {
        $url .= '?' . http_build_query($params);
    }
    return $url;
}

// ================================================================
// FLASH MESSAGES
// ================================================================

/**
 * Set flash message
 * 
 * @param string $message Message text
 * @param string $type Message type (success, error, info, warning)
 */
function setFlashMessage($message, $type = 'info') {
    setSession('flash_message', ['message' => $message, 'type' => $type]);
}

/**
 * Get and clear flash message
 * 
 * @return array|null ['message' => string, 'type' => string]
 */
function getFlashMessage() {
    if (hasSession('flash_message')) {
        $message = getSession('flash_message');
        unsetSession('flash_message');
        return $message;
    }
    return null;
}

/**
 * Display flash message HTML
 * 
 * @return string HTML for flash message
 */
function displayFlashMessage() {
    $flash = getFlashMessage();
    if ($flash) {
        $alertClass = 'alert-' . ($flash['type'] === 'error' ? 'danger' : $flash['type']);
        return '<div class="alert ' . $alertClass . ' alert-dismissible fade show" role="alert">
                    ' . e($flash['message']) . '
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>';
    }
    return '';
}

// ================================================================
// TEXT FORMATTING
// ================================================================

/**
 * Truncate text to specific length
 * 
 * @param string $text Text to truncate
 * @param int $length Maximum length
 * @param string $suffix Suffix to add (default: '...')
 * @return string Truncated text
 */
function truncate($text, $length, $suffix = '...') {
    if (strlen($text) <= $length) {
        return $text;
    }
    return substr($text, 0, $length) . $suffix;
}

/**
 * Generate slug from string
 * 
 * @param string $text Text to slugify
 * @return string Slug
 */
function slugify($text) {
    $text = strtolower($text);
    $text = preg_replace('/[^a-z0-9]+/', '-', $text);
    $text = trim($text, '-');
    return $text;
}

/**
 * Count words in text
 * 
 * @param string $text Text to count
 * @return int Word count
 */
function wordCount($text) {
    return str_word_count(strip_tags($text));
}

/**
 * Calculate reading time in minutes
 * 
 * @param string $text Text content
 * @param int $wordsPerMinute Average reading speed
 * @return int Reading time in minutes
 */
function readingTime($text, $wordsPerMinute = 200) {
    $words = wordCount($text);
    $minutes = ceil($words / $wordsPerMinute);
    return max(1, $minutes);
}

// ================================================================
// MISCELLANEOUS
// ================================================================

/**
 * Debug variable dump (only in debug mode)
 * 
 * @param mixed $var Variable to dump
 */
function dd($var) {
    if (DEBUG_MODE) {
        echo '<pre>';
        var_dump($var);
        echo '</pre>';
        die();
    }
}

/**
 * Generate random string
 * 
 * @param int $length String length
 * @return string Random string
 */
function randomString($length = 10) {
    return bin2hex(random_bytes($length / 2));
}

/**
 * Check if request is AJAX
 * 
 * @return bool
 */
function isAjax() {
    return !empty($_SERVER['HTTP_X_REQUESTED_WITH']) && 
           strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';
}

/**
 * Get client IP address
 * 
 * @return string IP address
 */
function getClientIP() {
    if (!empty($_SERVER['HTTP_CLIENT_IP'])) {
        return $_SERVER['HTTP_CLIENT_IP'];
    } elseif (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
        return $_SERVER['HTTP_X_FORWARDED_FOR'];
    } else {
        return $_SERVER['REMOTE_ADDR'];
    }
}

?>