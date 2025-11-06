<?php
/**
 * BLOG HUT - Delete Blog Post
 */

// Start session
session_start();

// Include required files
require_once '../config/database.php';
require_once '../config/constants.php';
require_once '../includes/helper.php';
require_once '../includes/post_function.php';

// Require login
requireLogin();

// Get post ID
$postId = isset($_GET['id']) ? intval($_GET['id']) : 0;

if (!$postId) {
    setFlashMessage('Invalid post ID.', 'error');
    redirect(SITE_URL . '/posts/home.php');
}

// Fetch post details
try {
    $post = getPostById($postId);
    
    if (!$post) {
        setFlashMessage('Post not found.', 'error');
        redirect(SITE_URL . '/posts/home.php');
    }
    
    // Check if user is owner or admin
    if (getCurrentUserId() != $post['user_id'] && !isUserAdmin()) {
        setFlashMessage('You do not have permission to delete this post.', 'error');
        redirect(SITE_URL . '/posts/view_blog.php?id=' . $postId);
    }
    
    // Perform deletion
    beginTransaction();
    
    try {
        // Delete featured image from server if exists
        if ($post['featured_image']) {
            $imagePath = POST_IMAGE_PATH . '/' . $post['featured_image'];
            deleteFile($imagePath);
        }
        
        // Delete the post (cascades to comments and reactions due to foreign keys)
        if (deletePost($postId)) {
            commit();
            
            // Set success message
            setFlashMessage('Blog post deleted successfully.', 'success');
            
            // Redirect to user's blog list if they deleted their own post
            // Otherwise redirect to home
            if (getCurrentUserId() == $post['user_id']) {
                redirect('/profile/my_blogs.php');
            } else {
                redirect(SITE_URL . '/posts/home.php');
            }
        } else {
            rollback();
            setFlashMessage('Failed to delete post. Please try again.', 'error');
            redirect(SITE_URL . '/posts/view_blog.php?id=' . $postId);
        }
        
    } catch (Exception $e) {
        rollback();
        error_log("Delete Post Error: " . $e->getMessage());
        setFlashMessage('An error occurred while deleting the post.', 'error');
        redirect(SITE_URL . '/posts/view_blog.php?id=' . $postId);
    }
    
} catch (Exception $e) {
    error_log("Delete Post Fetch Error: " . $e->getMessage());
    setFlashMessage('An error occurred. Please try again.', 'error');
    redirect(SITE_URL . '/posts/home.php');
}
?>