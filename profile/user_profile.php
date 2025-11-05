<?php
/**
 * BLOG HUT - View User Profile (Public)
 * This page displays any user's public profile:
 * - Profile information
 * - Statistics
 * - Public badges
 * - Published posts only
 * - Pagination
 */

// Start session
session_start();

// Include required files
require_once '../config/database.php';
require_once '../config/constants.php';
require_once '../includes/helper.php';
require_once '../includes/post_function.php';

// Get user ID from URL
$userId = isset($_GET['id']) ? intval($_GET['id']) : 0;

if (!$userId) {
    setFlashMessage('Invalid user ID.', 'error');
    redirect('/posts/home.php');
}

// Get pagination
$currentPage = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;

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
    
    // Get user's published posts (public only)
    $posts = getPostsByUser($userId, $currentPage, USER_POSTS_PER_PAGE, false);
    
    // Get total post count
    $totalPosts = fetchOne(
        "SELECT COUNT(*) as count FROM blogPost WHERE user_id = ? AND status = 'published'",
        [$userId]
    )['count'] ?? 0;
    
    $totalPages = ceil($totalPosts / USER_POSTS_PER_PAGE);
    
    // Get top post
    $topPost = getUserTopPost($userId);
    
} catch (Exception $e) {
    error_log("User Profile Error: " . $e->getMessage());
    setFlashMessage('An error occurred while loading the profile.', 'error');
    redirect('/posts/home.php');
}

// Check if viewing own profile
$isOwnProfile = isLoggedIn() && getCurrentUserId() == $userId;

// Set page title
$pageTitle = e($user['username']) . '\'s Profile - ' . SITE_NAME;
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
                                    <?php if ($user['role'] === 'admin'): ?>
                                    <span class="badge bg-danger mb-2">
                                        <i class="fas fa-shield-alt me-1"></i> Administrator
                                    </span>
                                    <?php endif; ?>
                                    <p class="text-muted mb-0">
                                        <i class="fas fa-calendar me-1"></i>
                                        Member since <?php echo formatDate($user['created_at'], 'F Y'); ?>
                                    </p>
                                </div>
                                
                                <?php if ($isOwnProfile): ?>
                                <a href="<?php echo SITE_URL; ?>/profile/edit_profile.php" 
                                   class="btn btn-primary">
                                    <i class="fas fa-edit me-1"></i> Edit Profile
                                </a>
                                <?php endif; ?>
                            </div>
                            
                            <?php if ($user['bio']): ?>
                            <div class="mb-2">
                                <p class="mb-0"><?php echo e($user['bio']); ?></p>
                            </div>
                            <?php endif; ?>
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
                        Top Performing Post
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
            
            <!-- Published Posts -->
            <div class="card shadow-sm" data-aos="fade-up">
                <div class="card-body">
                    <h5 class="card-title mb-3">
                        <i class="fas fa-file-alt me-2"></i>
                        Published Posts (<?php echo number_format($totalPosts); ?>)
                    </h5>
                    
                    <?php if (!empty($posts)): ?>
                    <div class="list-group list-group-flush">
                        <?php foreach ($posts as $post): ?>
                        <div class="list-group-item px-0">
                            <div class="row align-items-center">
                                <?php if ($post['featured_image']): ?>
                                <div class="col-md-3 mb-2 mb-md-0">
                                    <a href="<?php echo SITE_URL; ?>/posts/view_blog.php?id=<?php echo $post['id']; ?>">
                                        <img src="<?php echo POST_IMAGE_URL . '/' . $post['featured_image']; ?>" 
                                             alt="<?php echo e($post['title']); ?>"
                                             class="img-fluid rounded"
                                             style="height: 100px; width: 100%; object-fit: cover;"
                                             onerror="this.style.display='none'">
                                    </a>
                                </div>
                                <div class="col-md-9">
                                <?php else: ?>
                                <div class="col-12">
                                <?php endif; ?>
                                    <h6 class="mb-1">
                                        <a href="<?php echo SITE_URL; ?>/posts/view_blog.php?id=<?php echo $post['id']; ?>" 
                                           class="text-decoration-none text-dark">
                                            <?php echo e($post['title']); ?>
                                        </a>
                                    </h6>
                                    
                                    <?php if ($post['category_name']): ?>
                                    <span class="badge bg-primary mb-2">
                                        <?php echo e($post['category_name']); ?>
                                    </span>
                                    <?php endif; ?>
                                    
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
                    
                    <!-- Pagination -->
                    <?php if ($totalPages > 1): ?>
                    <nav aria-label="Posts pagination" class="mt-4">
                        <ul class="pagination justify-content-center mb-0">
                            <!-- Previous -->
                            <li class="page-item <?php echo $currentPage <= 1 ? 'disabled' : ''; ?>">
                                <a class="page-link" href="?id=<?php echo $userId; ?>&page=<?php echo $currentPage - 1; ?>">
                                    <i class="fas fa-chevron-left"></i>
                                </a>
                            </li>
                            
                            <!-- Page Numbers -->
                            <?php
                            $startPage = max(1, $currentPage - 2);
                            $endPage = min($totalPages, $currentPage + 2);
                            for ($i = $startPage; $i <= $endPage; $i++):
                            ?>
                            <li class="page-item <?php echo $i === $currentPage ? 'active' : ''; ?>">
                                <a class="page-link" href="?id=<?php echo $userId; ?>&page=<?php echo $i; ?>">
                                    <?php echo $i; ?>
                                </a>
                            </li>
                            <?php endfor; ?>
                            
                            <!-- Next -->
                            <li class="page-item <?php echo $currentPage >= $totalPages ? 'disabled' : ''; ?>">
                                <a class="page-link" href="?id=<?php echo $userId; ?>&page=<?php echo $currentPage + 1; ?>">
                                    <i class="fas fa-chevron-right"></i>
                                </a>
                            </li>
                        </ul>
                    </nav>
                    <?php endif; ?>
                    
                    <?php else: ?>
                    <div class="text-center py-4 text-muted">
                        <i class="fas fa-file-alt fa-3x mb-3"></i>
                        <p>No published posts yet.</p>
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
                        <p class="small mb-0">No badges earned yet</p>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
            
            <!-- Quick Links Card -->
            <?php if ($isOwnProfile): ?>
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
                    </div>
                </div>
            </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php include '../includes/footer.php'; ?>