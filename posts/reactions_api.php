<?php
/**
 * ================================================================
 * BLOG HUT - Reactions API (AJAX)
 * University of Moratuwa - IN2120 Web Programming Project
 * ================================================================
 * 
 * This API handles reaction operations via AJAX:
 * - Add reaction to post
 * - Update existing reaction
 * - Remove reaction
 * - Get reaction counts
 * - JSON response format
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
require_once '../includes/post_function.php';

// Set JSON header
header('Content-Type: application/json');

// Check if request is AJAX
if (!isAjax()) {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
    exit;
}

// Check if user is logged in
if (!isLoggedIn()) {
    echo json_encode(['success' => false, 'message' => 'You must be logged in to react']);
    exit;
}

// Get request method
$method = $_SERVER['REQUEST_METHOD'];

// Handle POST request (add/update reaction)
if ($method === 'POST') {
    // Get POST data
    $postId = isset($_POST['post_id']) ? intval($_POST['post_id']) : 0;
    $reactionType = isset($_POST['type']) ? cleanInput($_POST['type']) : '';
    
    // Validate post ID
    if (!$postId) {
        echo json_encode(['success' => false, 'message' => 'Invalid post ID']);
        exit;
    }
    
    // Validate reaction type
    if (!isValidReaction($reactionType)) {
        echo json_encode(['success' => false, 'message' => 'Invalid reaction type']);
        exit;
    }
    
    // Check if post exists
    $post = getPostById($postId);
    if (!$post) {
        echo json_encode(['success' => false, 'message' => 'Post not found']);
        exit;
    }
    
    try {
        // Add or update reaction
        $result = addReaction($postId, getCurrentUserId(), $reactionType);
        
        if ($result) {
            // Get updated reaction counts
            $reactionCounts = getReactionCounts($postId);
            
            // Get total reaction count
            $totalReactions = array_sum($reactionCounts);
            
            echo json_encode([
                'success' => true,
                'message' => 'Reaction added successfully',
                'reaction' => $reactionType,
                'counts' => $reactionCounts,
                'total' => $totalReactions
            ]);
        } else {
            echo json_encode(['success' => false, 'message' => 'Failed to add reaction']);
        }
        
    } catch (Exception $e) {
        error_log("Reaction API Error: " . $e->getMessage());
        echo json_encode(['success' => false, 'message' => 'An error occurred']);
    }
    
    exit;
}

// Handle DELETE request (remove reaction)
if ($method === 'DELETE' || ($method === 'POST' && isset($_POST['_method']) && $_POST['_method'] === 'DELETE')) {
    // Get DELETE data
    parse_str(file_get_contents("php://input"), $_DELETE);
    
    // Also check POST data for compatibility
    $postId = isset($_DELETE['post_id']) ? intval($_DELETE['post_id']) : (isset($_POST['post_id']) ? intval($_POST['post_id']) : 0);
    
    // Validate post ID
    if (!$postId) {
        echo json_encode(['success' => false, 'message' => 'Invalid post ID']);
        exit;
    }
    
    try {
        // Remove reaction
        $result = removeReaction($postId, getCurrentUserId());
        
        if ($result) {
            // Get updated reaction counts
            $reactionCounts = getReactionCounts($postId);
            
            // Get total reaction count
            $totalReactions = array_sum($reactionCounts);
            
            echo json_encode([
                'success' => true,
                'message' => 'Reaction removed successfully',
                'counts' => $reactionCounts,
                'total' => $totalReactions
            ]);
        } else {
            echo json_encode(['success' => false, 'message' => 'Failed to remove reaction']);
        }
        
    } catch (Exception $e) {
        error_log("Reaction API Error: " . $e->getMessage());
        echo json_encode(['success' => false, 'message' => 'An error occurred']);
    }
    
    exit;
}

// Handle GET request (get reaction counts)
if ($method === 'GET') {
    $postId = isset($_GET['post_id']) ? intval($_GET['post_id']) : 0;
    
    // Validate post ID
    if (!$postId) {
        echo json_encode(['success' => false, 'message' => 'Invalid post ID']);
        exit;
    }
    
    try {
        // Get reaction counts
        $reactionCounts = getReactionCounts($postId);
        
        // Get user's reaction if logged in
        $userReaction = null;
        if (isLoggedIn()) {
            $reaction = getUserReaction($postId, getCurrentUserId());
            if ($reaction) {
                $userReaction = $reaction['type'];
            }
        }
        
        // Get total reaction count
        $totalReactions = array_sum($reactionCounts);
        
        echo json_encode([
            'success' => true,
            'counts' => $reactionCounts,
            'total' => $totalReactions,
            'user_reaction' => $userReaction
        ]);
        
    } catch (Exception $e) {
        error_log("Reaction API Error: " . $e->getMessage());
        echo json_encode(['success' => false, 'message' => 'An error occurred']);
    }
    
    exit;
}

// Invalid request method
echo json_encode(['success' => false, 'message' => 'Invalid request method']);
exit;
?>