<?php
/**
 * BLOG HUT - View Single Blog Post
 * This page displays a single blog post with:
 * - Full post content
 * - Author information
 * - Reactions (like, love, etc.) - AJAX
 * - Comments section - AJAX
 * - Share buttons
 * - Related posts
 * - Edit/Delete options (for owner)
 */

// Start session
session_start();

// Include required files
require_once '../config/database.php';
require_once '../config/constants.php';
require_once '../includes/helper.php';
require_once '../includes/post_function.php';

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
    
    // Check if post is published or user is owner/admin
    if ($post['status'] !== 'published') {
        if (!isLoggedIn() || (getCurrentUserId() != $post['user_id'] && !isUserAdmin())) {
            setFlashMessage('This post is not available.', 'error');
            redirect(SITE_URL . '/posts/home.php');
        }
    }
    
    // Increment views (only if not the author viewing their own post)
    if (!isLoggedIn() || getCurrentUserId() != $post['user_id']) {
        incrementPostViews($postId);
    }
    
    // Get user's reaction if logged in
    $userReaction = null;
    if (isLoggedIn()) {
        $userReaction = getUserReaction($postId, getCurrentUserId());
    }
    
    // Get reaction counts
    $reactionCounts = getReactionCounts($postId);
    
    // Get comments
    $comments = getPostComments($postId);
    
    // Get related posts (same category)
    $relatedPosts = [];
    if ($post['category']) {
        $relatedQuery = "SELECT bp.*, u.username, u.profile_image 
                        FROM blogPost bp
                        JOIN users u ON bp.user_id = u.id
                        WHERE bp.category = ? AND bp.id != ? AND bp.status = 'published'
                        ORDER BY RAND()
                        LIMIT 3";
        $relatedPosts = fetchAll($relatedQuery, [$post['category'], $postId]);
    }
    
} catch (Exception $e) {
    error_log("View Blog Error: " . $e->getMessage());
    setFlashMessage('An error occurred while loading the post.', 'error');
    redirect(SITE_URL . '/posts/home.php');
}

// Set page title and meta
$pageTitle = e($post['title']) . ' - ' . SITE_NAME;
$pageDescription = e(truncate(strip_tags($post['summary'] ?? $post['content']), 160));
?>
<?php include '../includes/header.php'; ?>

<div class="container my-5">
    <div class="row">
        <!-- Main Content -->
        <div class="col-lg-8">
            <article class="blog-post" data-aos="fade-up">
                <!-- Post Header -->
                <div class="post-header mb-4">
                    <!-- Category Badge -->
                    <?php if ($post['category_name']): ?>
                    <a href="<?php echo SITE_URL; ?>/posts/home.php?category=<?php echo $post['category']; ?>" 
                       class="badge bg-primary text-decoration-none mb-3">
                        <i class="fas fa-tag me-1"></i>
                        <?php echo e($post['category_name']); ?>
                    </a>
                    <?php endif; ?>
                    
                    <!-- Post Title -->
                    <h1 class="display-5 fw-bold mb-3"><?php echo e($post['title']); ?></h1>
                    
                    <!-- Post Meta -->
                    <div class="d-flex align-items-center mb-3 flex-wrap">
                        <img src="<?php echo AVATAR_URL . '/' . ($post['profile_image'] ?? DEFAULT_AVATAR); ?>" 
                             alt="<?php echo e($post['username']); ?>"
                             class="rounded-circle me-2"
                             style="width: 50px; height: 50px; object-fit: cover;">
                        <div class="flex-grow-1">
                            <a href="<?php echo SITE_URL; ?>/profile/user_profile.php?id=<?php echo $post['user_id']; ?>" 
                               class="text-decoration-none text-dark fw-bold">
                                <?php echo e($post['username']); ?>
                            </a>
                            <div class="small text-muted">
                                <i class="fas fa-calendar me-1"></i>
                                <?php echo formatDate($post['created_at']); ?>
                                <span class="mx-2">•</span>
                                <i class="fas fa-clock me-1"></i>
                                <?php echo readingTime($post['content']); ?> min read
                                <?php if ($post['updated_at'] != $post['created_at']): ?>
                                <span class="mx-2">•</span>
                                <i class="fas fa-edit me-1"></i>
                                Updated <?php echo timeAgo($post['updated_at']); ?>
                                <?php endif; ?>
                            </div>
                        </div>
                        
                        <!-- Edit/Delete Buttons (Owner or Admin) -->
                        <?php if (isLoggedIn() && (getCurrentUserId() == $post['user_id'] || isUserAdmin())): ?>
                        <div class="btn-group ms-auto">
                            <a href="<?php echo SITE_URL; ?>/posts/edit_blog.php?id=<?php echo $post['id']; ?>" 
                               class="btn btn-sm btn-outline-primary">
                                <i class="fas fa-edit"></i> Edit
                            </a>
                            <button type="button" 
                                    class="btn btn-sm btn-outline-danger" 
                                    onclick="confirmDelete(<?php echo $post['id']; ?>)">
                                <i class="fas fa-trash"></i> Delete
                            </button>
                        </div>
                        <?php endif; ?>
                    </div>
                    
                    <!-- Post Stats -->
                    <div class="post-stats d-flex gap-3 text-muted small mb-3">
                        <span><i class="fas fa-eye me-1"></i> <?php echo number_format($post['views']); ?> views</span>
                        <span><i class="fas fa-heart me-1"></i> <?php echo $post['reaction_count']; ?> reactions</span>
                        <span><i class="fas fa-comment me-1"></i> <?php echo $post['comment_count']; ?> comments</span>
                    </div>
                </div>
                
                <!-- Featured Image -->
                <?php if ($post['featured_image']): ?>
                <div class="featured-image mb-4">
                    <img src="<?php echo POST_IMAGE_URL . '/' . $post['featured_image']; ?>" 
                         alt="<?php echo e($post['title']); ?>"
                         class="img-fluid rounded shadow"
                         onerror="this.style.display='none'">
                </div>
                <?php endif; ?>
                
                <!-- Post Content -->
                <div class="post-content mb-5">
                    <?php echo $post['content']; ?>
                </div>
                
                <!-- Reactions Section -->
                <div class="reactions-section mb-4 p-4 bg-light rounded" id="reactionsSection">
                    <h5 class="mb-3">
                        <i class="fas fa-thumbs-up text-primary me-2"></i> What do you think?
                    </h5>
                    
                    <?php if (isLoggedIn()): ?>
                    <div class="d-flex gap-3" id="reactionButtons">
                        <!-- Like Button -->
                        <button class="btn btn-outline-primary reaction-btn <?php echo ($userReaction && $userReaction['type'] === 'like') ? 'active' : ''; ?>" 
                                data-reaction="like"
                                data-post-id="<?php echo $post['id']; ?>">
                            <i class="fas fa-thumbs-up me-1"></i>
                            Like
                            <span class="badge bg-primary ms-1 reaction-count"><?php echo $reactionCounts['like'] ?? 0; ?></span>
                        </button>
                        
                        <!-- Dislike Button -->
                        <button class="btn btn-outline-danger reaction-btn <?php echo ($userReaction && $userReaction['type'] === 'dislike') ? 'active' : ''; ?>" 
                                data-reaction="dislike"
                                data-post-id="<?php echo $post['id']; ?>">
                            <i class="fas fa-thumbs-down me-1"></i>
                            Dislike
                            <span class="badge bg-danger ms-1 reaction-count"><?php echo $reactionCounts['dislike'] ?? 0; ?></span>
                        </button>
                    </div>
                    <?php else: ?>
                    <p class="text-muted mb-0">
                        <a href="<?php echo SITE_URL; ?>/auth/login.php" class="text-primary">Login</a> 
                        to react to this post
                    </p>
                    <?php endif; ?>
                </div>
                
                <!-- Comments Section -->
                <div class="comments-section mb-5">
                    <h4 class="mb-4">
                        <i class="fas fa-comments me-2"></i>
                        Comments (<?php echo count($comments); ?>)
                    </h4>
                    
                    <!-- Add Comment Form -->
                    <?php if (isLoggedIn()): ?>
                    <div class="card mb-4">
                        <div class="card-body">
                            <form id="commentForm">
                                <input type="hidden" name="post_id" value="<?php echo $post['id']; ?>">
                                <div class="mb-3">
                                    <textarea class="form-control" 
                                              name="comment" 
                                              rows="3" 
                                              placeholder="Write a comment..."
                                              required
                                              maxlength="<?php echo MAX_COMMENT_LENGTH; ?>"></textarea>
                                </div>
                                <button type="submit" class="btn btn-primary">
                                    <i class="fas fa-paper-plane me-2"></i> Post Comment
                                </button>
                            </form>
                        </div>
                    </div>
                    <?php else: ?>
                    <div class="alert alert-info">
                        <i class="fas fa-info-circle me-2"></i>
                        Please <a href="<?php echo SITE_URL; ?>/auth/login.php" class="alert-link">login</a> 
                        to leave a comment
                    </div>
                    <?php endif; ?>
                    
                    <!-- Comments List -->
                    <div id="commentsList">
                        <?php if (!empty($comments)): ?>
                            <?php foreach ($comments as $comment): ?>
                            <div class="comment-item card mb-3" data-comment-id="<?php echo $comment['id']; ?>">
                                <div class="card-body">
                                    <div class="d-flex">
                                        <img src="<?php echo AVATAR_URL . '/' . ($comment['profile_image'] ?? DEFAULT_AVATAR); ?>" 
                                             alt="<?php echo e($comment['username']); ?>"
                                             class="rounded-circle me-3"
                                             style="width: 40px; height: 40px; object-fit: cover;">
                                        <div class="flex-grow-1">
                                            <div class="d-flex justify-content-between align-items-start">
                                                <div>
                                                    <strong><?php echo e($comment['username']); ?></strong>
                                                    <small class="text-muted ms-2">
                                                        <?php echo timeAgo($comment['created_at']); ?>
                                                    </small>
                                                </div>
                                            </div>
                                            <p class="mb-0 mt-2"><?php echo e($comment['comment']); ?></p>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        <?php else: ?>
                        <p class="text-muted text-center py-4">
                            <i class="fas fa-comments fa-2x mb-2 d-block"></i>
                            No comments yet. Be the first to comment!
                        </p>
                        <?php endif; ?>
                    </div>
                </div>
            </article>
        </div>
        
        <!-- Sidebar -->
        <div class="col-lg-4">
            <!-- Author Card -->
            <div class="card mb-4 shadow-sm" data-aos="fade-left">
                <div class="card-body text-center">
                    <img src="<?php echo AVATAR_URL . '/' . ($post['profile_image'] ?? DEFAULT_AVATAR); ?>" 
                         alt="<?php echo e($post['username']); ?>"
                         class="rounded-circle mb-3"
                         style="width: 80px; height: 80px; object-fit: cover;">
                    <h5 class="mb-1"><?php echo e($post['username']); ?></h5>
                    <?php if ($post['bio']): ?>
                    <p class="text-muted small mb-3"><?php echo e(truncate($post['bio'], 100)); ?></p>
                    <?php endif; ?>
                    <a href="<?php echo SITE_URL; ?>/profile/user_profile.php?id=<?php echo $post['user_id']; ?>" 
                       class="btn btn-primary btn-sm">
                        <i class="fas fa-user me-1"></i> View Profile
                    </a>
                </div>
            </div>
            
            <!-- Share Card -->
            <div class="card mb-4 shadow-sm" data-aos="fade-left" data-aos-delay="100">
                <div class="card-body">
                    <h6 class="card-title mb-3">
                        <i class="fas fa-share-alt me-2"></i> Share this post
                    </h6>
                    <div class="d-flex gap-2">
                        <a href="https://www.facebook.com/sharer/sharer.php?u=<?php echo urlencode(currentUrl()); ?>" 
                           target="_blank" 
                           class="btn btn-outline-primary btn-sm flex-fill">
                            <i class="fab fa-facebook"></i>
                        </a>
                        <a href="https://twitter.com/intent/tweet?url=<?php echo urlencode(currentUrl()); ?>&text=<?php echo urlencode($post['title']); ?>" 
                           target="_blank" 
                           class="btn btn-outline-info btn-sm flex-fill">
                            <i class="fab fa-twitter"></i>
                        </a>
                        <a href="https://www.linkedin.com/sharing/share-offsite/?url=<?php echo urlencode(currentUrl()); ?>" 
                           target="_blank" 
                           class="btn btn-outline-primary btn-sm flex-fill">
                            <i class="fab fa-linkedin"></i>
                        </a>
                    </div>
                </div>
            </div>
            
            <!-- Related Posts -->
            <?php if (!empty($relatedPosts)): ?>
            <div class="card mb-4 shadow-sm" data-aos="fade-left" data-aos-delay="200">
                <div class="card-body">
                    <h6 class="card-title mb-3">
                        <i class="fas fa-list me-2"></i> Related Posts
                    </h6>
                    <?php foreach ($relatedPosts as $related): ?>
                    <div class="mb-3 pb-3 border-bottom">
                        <a href="<?php echo SITE_URL; ?>/posts/view_blog.php?id=<?php echo $related['id']; ?>" 
                           class="text-decoration-none text-dark">
                            <h6 class="mb-1"><?php echo e(truncate($related['title'], 50)); ?></h6>
                        </a>
                        <small class="text-muted">
                            By <?php echo e($related['username']); ?> • 
                            <?php echo timeAgo($related['created_at']); ?>
                        </small>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- Delete Confirmation Script -->
<script>
function confirmDelete(postId) {
    Swal.fire({
        title: 'Delete Post?',
        text: 'This action cannot be undone!',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#dc3545',
        cancelButtonColor: '#6c757d',
        confirmButtonText: 'Yes, delete it!',
        cancelButtonText: 'Cancel'
    }).then((result) => {
        if (result.isConfirmed) {
            window.location.href = '<?php echo SITE_URL; ?>/posts/delete_blog.php?id=' + postId;
        }
    });
}
</script>

<?php
$customJS = '<script src="' . JS_URL . '/comments.js"></script>
             <script src="' . JS_URL . '/reactions.js"></script>';
include '../includes/footer.php'; 
?>