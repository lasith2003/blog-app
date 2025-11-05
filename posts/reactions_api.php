<?php
/**
 * Reactions API - Like/Dislike Only
 */
session_start();
require_once '../config/database.php';
require_once '../config/constants.php';
require_once '../includes/helper.php';
require_once '../includes/post_function.php';

header('Content-Type: application/json');

if (!isAjax()) {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
    exit;
}

if (!isLoggedIn()) {
    echo json_encode(['success' => false, 'message' => 'You must be logged in to react']);
    exit;
}

$method = $_SERVER['REQUEST_METHOD'];

// Handle POST request (add/update reaction)
if ($method === 'POST') {
    $postId = isset($_POST['post_id']) ? intval($_POST['post_id']) : 0;
    $reactionType = isset($_POST['type']) ? cleanInput($_POST['type']) : '';
    
    if (!$postId) {
        echo json_encode(['success' => false, 'message' => 'Invalid post ID']);
        exit;
    }
    
    // Only allow 'like' or 'dislike'
    if (!in_array($reactionType, ['like', 'dislike'])) {
        echo json_encode(['success' => false, 'message' => 'Invalid reaction type']);
        exit;
    }
    
    $post = getPostById($postId);
    if (!$post) {
        echo json_encode(['success' => false, 'message' => 'Post not found']);
        exit;
    }
    
    try {
        $userId = getCurrentUserId();
        $existing = getUserReaction($postId, $userId);
        
        // If clicking same reaction, remove it
        if ($existing && $existing['type'] === $reactionType) {
            removeReaction($postId, $userId);
            $message = 'Reaction removed';
        } else {
            // Add or update reaction
            addReaction($postId, $userId, $reactionType);
            $message = $existing ? 'Reaction updated' : 'Reaction added';
        }
        
        // Get updated counts
        $reactionCounts = getReactionCounts($postId);
        $totalReactions = array_sum($reactionCounts);
        
        echo json_encode([
            'success' => true,
            'message' => $message,
            'reaction' => $reactionType,
            'counts' => $reactionCounts,
            'total' => $totalReactions
        ]);
        
    } catch (Exception $e) {
        error_log("Reaction API Error: " . $e->getMessage());
        echo json_encode(['success' => false, 'message' => 'An error occurred']);
    }
    exit;
}

// Handle GET request (get reaction counts)
if ($method === 'GET') {
    $postId = isset($_GET['post_id']) ? intval($_GET['post_id']) : 0;
    
    if (!$postId) {
        echo json_encode(['success' => false, 'message' => 'Invalid post ID']);
        exit;
    }
    
    try {
        $reactionCounts = getReactionCounts($postId);
        $userReaction = null;
        
        if (isLoggedIn()) {
            $reaction = getUserReaction($postId, getCurrentUserId());
            if ($reaction) {
                $userReaction = $reaction['type'];
            }
        }
        
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

echo json_encode(['success' => false, 'message' => 'Invalid request method']);
exit;
?>