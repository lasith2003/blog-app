<?php
/**
 * BLOG HUT - Comments API (AJAX)

 * This API handles comment operations via AJAX:
 * - Add new comment
 * - Get comments for a post
 * - Delete comment (owner/admin only)
 * - JSON response format
 * - Reply support (nested comments)
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

// Get request method
$method = $_SERVER['REQUEST_METHOD'];

// Handle POST request (add comment)
if ($method === 'POST') {
    // Check if user is logged in
    if (!isLoggedIn()) {
        echo json_encode(['success' => false, 'message' => 'You must be logged in to comment']);
        exit;
    }
    
    // Get POST data
    $postId = isset($_POST['post_id']) ? intval($_POST['post_id']) : 0;
    $comment = isset($_POST['comment']) ? cleanInput($_POST['comment']) : '';
    $parentCommentId = isset($_POST['parent_comment_id']) ? intval($_POST['parent_comment_id']) : null;
    
    // Validate post ID
    if (!$postId) {
        echo json_encode(['success' => false, 'message' => 'Invalid post ID']);
        exit;
    }
    
    // Validate comment
    if (isEmpty($comment)) {
        echo json_encode(['success' => false, 'message' => 'Comment cannot be empty']);
        exit;
    }
    
    if (strlen($comment) < MIN_COMMENT_LENGTH) {
        echo json_encode(['success' => false, 'message' => 'Comment is too short']);
        exit;
    }
    
    if (strlen($comment) > MAX_COMMENT_LENGTH) {
        echo json_encode(['success' => false, 'message' => 'Comment is too long (max ' . MAX_COMMENT_LENGTH . ' characters)']);
        exit;
    }
    
    // Check if post exists
    $post = getPostById($postId);
    if (!$post) {
        echo json_encode(['success' => false, 'message' => 'Post not found']);
        exit;
    }
    
    try {
        // Insert comment
        $sql = "INSERT INTO comments (blog_id, user_id, parent_comment_id, comment, created_at) 
                VALUES (?, ?, ?, ?, NOW())";
        
        $commentId = insertRecord($sql, [$postId, getCurrentUserId(), $parentCommentId, $comment]);
        
        if ($commentId) {
            // Get the newly created comment with user info
            $newComment = fetchOne(
                "SELECT c.*, u.username, u.profile_image 
                 FROM comments c
                 JOIN users u ON c.user_id = u.id
                 WHERE c.id = ?",
                [$commentId]
            );
            
            // Get updated comment count
            $commentCount = getCommentCount($postId);
            
            echo json_encode([
                'success' => true,
                'message' => 'Comment added successfully',
                'comment' => [
                    'id' => $newComment['id'],
                    'user_id' => $newComment['user_id'],
                    'username' => $newComment['username'],
                    'profile_image' => $newComment['profile_image'],
                    'comment' => $newComment['comment'],
                    'created_at' => $newComment['created_at'],
                    'time_ago' => timeAgo($newComment['created_at'])
                ],
                'comment_count' => $commentCount
            ]);
        } else {
            echo json_encode(['success' => false, 'message' => 'Failed to add comment']);
        }
        
    } catch (Exception $e) {
        error_log("Comment API Error: " . $e->getMessage());
        echo json_encode(['success' => false, 'message' => 'An error occurred']);
    }
    
    exit;
}

// Handle GET request (get comments)
if ($method === 'GET') {
    $postId = isset($_GET['post_id']) ? intval($_GET['post_id']) : 0;
    
    // Validate post ID
    if (!$postId) {
        echo json_encode(['success' => false, 'message' => 'Invalid post ID']);
        exit;
    }
    
    try {
        // Get comments
        $comments = getPostComments($postId);
        
        // Format comments with time ago
        $formattedComments = array_map(function($comment) {
            return [
                'id' => $comment['id'],
                'user_id' => $comment['user_id'],
                'username' => $comment['username'],
                'profile_image' => $comment['profile_image'],
                'comment' => $comment['comment'],
                'created_at' => $comment['created_at'],
                'time_ago' => timeAgo($comment['created_at']),
                'parent_comment_id' => $comment['parent_comment_id']
            ];
        }, $comments);
        
        // Get comment count
        $commentCount = count($comments);
        
        echo json_encode([
            'success' => true,
            'comments' => $formattedComments,
            'count' => $commentCount
        ]);
        
    } catch (Exception $e) {
        error_log("Comment API Error: " . $e->getMessage());
        echo json_encode(['success' => false, 'message' => 'An error occurred']);
    }
    
    exit;
}

// Handle DELETE request (delete comment)
if ($method === 'DELETE' || ($method === 'POST' && isset($_POST['_method']) && $_POST['_method'] === 'DELETE')) {
    // Check if user is logged in
    if (!isLoggedIn()) {
        echo json_encode(['success' => false, 'message' => 'You must be logged in']);
        exit;
    }
    
    // Get DELETE data
    parse_str(file_get_contents("php://input"), $_DELETE);
    
    // Also check POST data for compatibility
    $commentId = isset($_DELETE['comment_id']) ? intval($_DELETE['comment_id']) : (isset($_POST['comment_id']) ? intval($_POST['comment_id']) : 0);
    
    // Validate comment ID
    if (!$commentId) {
        echo json_encode(['success' => false, 'message' => 'Invalid comment ID']);
        exit;
    }
    
    try {
        // Get comment details
        $comment = fetchOne("SELECT * FROM comments WHERE id = ?", [$commentId]);
        
        if (!$comment) {
            echo json_encode(['success' => false, 'message' => 'Comment not found']);
            exit;
        }
        
        // Check if user is comment owner or admin
        if (getCurrentUserId() != $comment['user_id'] && !isUserAdmin()) {
            echo json_encode(['success' => false, 'message' => 'You do not have permission to delete this comment']);
            exit;
        }
        
        // Delete comment
        $sql = "DELETE FROM comments WHERE id = ?";
        $result = updateRecord($sql, [$commentId]);
        
        if ($result > 0) {
            // Get updated comment count
            $commentCount = getCommentCount($comment['blog_id']);
            
            echo json_encode([
                'success' => true,
                'message' => 'Comment deleted successfully',
                'comment_count' => $commentCount
            ]);
        } else {
            echo json_encode(['success' => false, 'message' => 'Failed to delete comment']);
        }
        
    } catch (Exception $e) {
        error_log("Comment API Error: " . $e->getMessage());
        echo json_encode(['success' => false, 'message' => 'An error occurred']);
    }
    
    exit;
}

// Invalid request method
echo json_encode(['success' => false, 'message' => 'Invalid request method']);
exit;
?>