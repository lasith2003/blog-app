<?php
/**
 * ================================================================
 * BLOG HUT - My Profile Page
 * University of Moratuwa - IN2120 Web Programming Project
 * ================================================================
 * 
 * This page displays the logged-in user's profile with:
 * - Profile information
 * - Statistics (posts, views, reactions, comments)
 * - Earned badges
 * - Recent posts
 * - Edit profile button
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
    
    // Get user statistics
    $stats = getUserPostStats($userId);
    
    // Get user's earned badges
    $badges = fetchAll(
        "SELECT b.*, ub.earned_at 
         FROM user_badges ub
         JOIN badges b ON ub.badge_id = b.id
         WHERE ub.user_id = ?
         ORDER BY ub.earned_at DESC",
        [$userId]
    );
    
    // Get user's recent posts
    $recentPosts = getPostsByUser($userId, 1, 5, true); // Include drafts
    
    // Get top post
    $topPost = getUserTopPost($userId);
    
} catch (Exception $e) {
    error_log("My Profile Error: " . $e->getMessage());
    setFlashMessage('An error occurred while loading your profile.', 'error');
    redirect('/posts/home.php');
}

// Set page title
$pageTitle = 'My Profile - ' . SITE_NAME;
?>
<?php include '../includes/header.php'; ?>

<div class="container my-5">
    <div class="row">
        <!-- Main Profile Content -->
        <div class="col-lg-8">
            <!-- Profile Header Card -->
            <div class="card shadow-sm mb-4" data-aos="fade-up">
                <div class="card-body">
                    <div class="row align-items-center">
                        <div class="col-md-3 text-center mb-3 mb-md-0">
                            <img src="<?php echo AVATAR_URL . '/' . ($user['profile_image'] ?? DEFAULT_AVATAR); ?>" 
                                 alt="<?php echo e($user['username']); ?>"
                                 class="rounded-circle img-fluid shadow"
                                 style="width: 150px; height: 150px; object-fit: cover;">
                        </div>
                        <div class="col-md-9">
                            <div class="d-flex justify-content-between align-items-start mb-3">
                                <div>
                                    <h2 class="mb-1"><?php echo e($user['username']); ?></h2>
                                    <p class="text-muted mb-2">
                                        <i class="fas fa-envelope me-1"></i>
                                        <?php echo e($user['email']); ?>
                                    </p>
                                    <?php if ($user['role'] === 'admin'): ?>
                                    <span class="badge bg-danger">
                                        <i class="fas fa-shield-alt me-1"></i> Administrator
                                    </span>
                                    <?php endif; ?>
                                </div>
                                <a href="<?php echo SITE_URL; ?>/profile/edit_profile.php" 
                                   class="btn btn-primary">
                                    <i class="fas fa-edit me-1"></i> Edit Profile
                                </a>
                            </div>
                            
                            <?php if ($user['bio']): ?>
                            <div class="mb-3">
                                <p class="mb-0"><?php echo e($user['bio']); ?></p>
                            </div>
                            <?php endif; ?>
                            
                            <small class="text-muted">
                                <i class="fas fa-calendar me-1"></i>
                                Member since <?php echo formatDate($user['created_at'], 'F Y'); ?>
                            </small>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Statistics Cards -->
            <div class="row g-3 mb-4">
                <div class="col-6 col-md-3" data-aos="fade-up" data-aos-delay="100">
                    <div class="card shadow-sm text-center h-100">
                        <div class="card-body">
                            <i class="fas fa-file-alt fa-2x text-primary mb-2"></i>
                            <h3 class="mb-0"><?php echo number_format($stats['total_posts'] ?? 0); ?></h3>
                            <small class="text-muted">Posts</small>
                        </div>
                    </div>
                </div>
                <div class="col-6 col-md-3" data-aos="fade-up" data-aos-delay="200">
                    <div class="card shadow-sm text-center h-100">
                        <div class="card-body">
                            <i class="fas fa-eye fa-2x text-info mb-2"></i>
                            <h3 class="mb-0"><?php echo number_format($stats['total_views'] ?? 0); ?></h3>
                            <small class="text-muted">Views</small>
                        </div>
                    </div>
                </div>
                <div class="col-6 col-md-3" data-aos="fade-up" data-aos-delay="300">
                    <div class="card shadow-sm text-center h-100">
                        <div class="card-body">
                            <i class="fas fa-heart fa-2x text-danger mb-2"></i>
                            <h3 class="mb-0"><?php echo number_format($stats['total_reactions'] ?? 0); ?></h3>
                            <small class="text-muted">Reactions</small>
                        </div>
                    </div>
                </div>
                <div class="col-6 col-md-3" data-aos="fade-up" data-aos-delay="400">
                    <div class="card shadow-sm text-center h-100">
                        <div class="card-body">
                            <i class="fas fa-comments fa-2x text-success mb-2"></i>
                            <h3 class="mb-0"><?php echo number_format($stats['total_comments'] ?? 0); ?></h3>
                            <small class="text-muted">Comments</small>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Top Post -->
            <?php if ($topPost): ?>
            <div class="card shadow-sm mb-4" data-aos="fade-up">
                <div class="card-body">
                    <h5 class="card-title mb-3">
                        <i class="fas fa-star text-warning me-2"></i>
                        Your Top Performing Post
                    </h5>
                    <div class="row align-items-center">
                        <?php if ($topPost['featured_image']): ?>
                        <div class="col-md-4">
                            <img src="<?php echo POST_IMAGE_URL . '/' . $topPost['featured_image']; ?>" 
                                 alt="<?php echo e($topPost['title']); ?>"
                                 class="img-fluid rounded"
                                 onerror="this.style.display='none'">
                        </div>
                        <div class="col-md-8">
                        <?php else: ?>
                        <div class="col-12">
                        <?php endif; ?>
                            <h6 class="mb-2">
                                <a href="<?php echo SITE_URL; ?>/posts/view_blog.php?id=<?php echo $topPost['id']; ?>" 
                                   class="text-decoration-none text-dark">
                                    <?php echo e($topPost['title']); ?>
                                </a>
                            </h6>
                            <div class="d-flex gap-3 text-muted small">
                                <span><i class="fas fa-eye"></i> <?php echo number_format($topPost['views']); ?></span>
                                <span><i class="fas fa-heart"></i> <?php echo $topPost['reaction_count']; ?></span>
                                <span><i class="fas fa-comment"></i> <?php echo $topPost['comment_count']; ?></span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <?php endif; ?>
            
            <!-- Recent Posts -->
            <div class="card shadow-sm" data-aos="fade-up">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <h5 class="card-title mb-0">
                            <i class="fas fa-clock me-2"></i>
                            Recent Posts
                        </h5>
                        <a href="<?php echo SITE_URL; ?>/profile/my_blogs.php" class="btn btn-sm btn-outline-primary">
                            View All
                        </a>
                    </div>
                    
                    <?php if (!empty($recentPosts)): ?>
                    <div class="list-group list-group-flush">
                        <?php foreach ($recentPosts as $post): ?>
                        <div class="list-group-item px-0">
                            <div class="d-flex justify-content-between align-items-start">
                                <div class="flex-grow-1">
                                    <h6 class="mb-1">
                                        <a href="<?php echo SITE_URL; ?>/posts/view_blog.php?id=<?php echo $post['id']; ?>" 
                                           class="text-decoration-none text-dark">
                                            <?php echo e($post['title']); ?>
                                        </a>
                                        <?php if ($post['status'] === 'draft'): ?>
                                        <span class="badge bg-warning text-dark ms-2">Draft</span>
                                        <?php endif; ?>
                                    </h6>
                                    <div class="d-flex gap-3 text-muted small">
                                        <span><i class="fas fa-calendar"></i> <?php echo timeAgo($post['created_at']); ?></span>
                                        <span><i class="fas fa-eye"></i> <?php echo number_format($post['views']); ?></span>
                                        <span><i class="fas fa-heart"></i> <?php echo $post['reaction_count']; ?></span>
                                        <span><i class="fas fa-comment"></i> <?php echo $post['comment_count']; ?></span>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                    <?php else: ?>
                    <div class="text-center py-4 text-muted">
                        <i class="fas fa-file-alt fa-3x mb-3"></i>
                        <p>You haven't created any posts yet.</p>
                        <a href="<?php echo SITE_URL; ?>/posts/create_blog.php" class="btn btn-primary">
                            <i class="fas fa-pen me-2"></i> Create Your First Post
                        </a>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        
        <!-- Sidebar -->
        <div class="col-lg-4">
            <!-- Badges Card -->
            <div class="card shadow-sm mb-4" data-aos="fade-left">
                <div class="card-body">
                    <h5 class="card-title mb-3">
                        <i class="fas fa-trophy text-warning me-2"></i>
                        Achievements
                    </h5>
                    
                    <?php if (!empty($badges)): ?>
                    <div class="row g-3">
                        <?php foreach ($badges as $badge): ?>
                        <div class="col-6">
                            <div class="text-center p-3 border rounded bg-light">
                                <i class="<?php echo e($badge['icon_url']); ?> fa-2x text-primary mb-2"></i>
                                <h6 class="mb-1 small"><?php echo e($badge['name']); ?></h6>
                                <small class="text-muted d-block" style="font-size: 0.75rem;">
                                    <?php echo formatDate($badge['earned_at'], 'M d, Y'); ?>
                                </small>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                    <?php else: ?>
                    <div class="text-center py-3 text-muted">
                        <i class="fas fa-trophy fa-2x mb-2"></i>
                        <p class="small mb-0">Start writing to earn badges!</p>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
            
            <!-- Quick Actions Card -->
            <div class="card shadow-sm" data-aos="fade-left" data-aos-delay="100">
                <div class="card-body">
                    <h5 class="card-title mb-3">
                        <i class="fas fa-bolt me-2"></i>
                        Quick Actions
                    </h5>
                    <div class="d-grid gap-2">
                        <a href="<?php echo SITE_URL; ?>/posts/create_blog.php" 
                           class="btn btn-primary">
                            <i class="fas fa-pen me-2"></i> Write New Post
                        </a>
                        <a href="<?php echo SITE_URL; ?>/profile/my_blogs.php" 
                           class="btn btn-outline-primary">
                            <i class="fas fa-file-alt me-2"></i> Manage My Posts
                        </a>
                        <a href="<?php echo SITE_URL; ?>/profile/edit_profile.php" 
                           class="btn btn-outline-secondary">
                            <i class="fas fa-cog me-2"></i> Settings
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include '../includes/footer.php'; ?>