<?php
/**
 * ================================================================
 * BLOG HUT - Admin Dashboard
 * University of Moratuwa - IN2120 Web Programming Project
 * ================================================================
 * 
 * Admin dashboard with:
 * - Overview statistics
 * - Recent activity
 * - Charts and analytics
 * - Quick actions
 * - System information
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

// Require admin access
requireAdmin();

try {
    // Get overview statistics
    $stats = fetchOne("
        SELECT 
            (SELECT COUNT(*) FROM users) as total_users,
            (SELECT COUNT(*) FROM users WHERE role = 'admin') as admin_count,
            (SELECT COUNT(*) FROM blogPost) as total_posts,
            (SELECT COUNT(*) FROM blogPost WHERE status = 'published') as published_posts,
            (SELECT COUNT(*) FROM blogPost WHERE status = 'draft') as draft_posts,
            (SELECT COUNT(*) FROM comments) as total_comments,
            (SELECT COUNT(*) FROM reactions) as total_reactions,
            (SELECT COUNT(*) FROM categories) as total_categories,
            (SELECT SUM(views) FROM blogPost) as total_views
    ");
    
    // Get recent posts (last 5)
    $recentPosts = fetchAll("
        SELECT bp.*, u.username, c.name as category_name
        FROM blogPost bp
        JOIN users u ON bp.user_id = u.id
        LEFT JOIN categories c ON bp.category = c.id
        ORDER BY bp.created_at DESC
        LIMIT 5
    ");
    
    // Get recent users (last 5)
    $recentUsers = fetchAll("
        SELECT id, username, email, role, created_at
        FROM users
        ORDER BY created_at DESC
        LIMIT 5
    ");
    
    // Get recent comments (last 5)
    $recentComments = fetchAll("
        SELECT c.*, u.username, bp.title as post_title
        FROM comments c
        JOIN users u ON c.user_id = u.id
        JOIN blogPost bp ON c.blog_id = bp.id
        ORDER BY c.created_at DESC
        LIMIT 5
    ");
    
    // Get top authors
    $topAuthors = fetchAll("
        SELECT u.username, u.id, COUNT(bp.id) as post_count, SUM(bp.views) as total_views
        FROM users u
        JOIN blogPost bp ON u.id = bp.user_id
        WHERE bp.status = 'published'
        GROUP BY u.id
        ORDER BY post_count DESC
        LIMIT 5
    ");
    
    // Get popular posts this month
    $popularPosts = fetchAll("
        SELECT bp.id, bp.title, bp.views, u.username,
               (SELECT COUNT(*) FROM reactions WHERE blog_id = bp.id) as reactions,
               (SELECT COUNT(*) FROM comments WHERE blog_id = bp.id) as comments
        FROM blogPost bp
        JOIN users u ON bp.user_id = u.id
        WHERE bp.status = 'published' 
        AND bp.created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)
        ORDER BY bp.views DESC
        LIMIT 5
    ");
    
    // Get category distribution
    $categoryStats = fetchAll("
        SELECT c.name, COUNT(bp.id) as post_count
        FROM categories c
        LEFT JOIN blogPost bp ON c.id = bp.category
        GROUP BY c.id
        ORDER BY post_count DESC
        LIMIT 5
    ");
    
} catch (Exception $e) {
    error_log("Admin Dashboard Error: " . $e->getMessage());
    $stats = [];
    $recentPosts = [];
    $recentUsers = [];
    $recentComments = [];
    $topAuthors = [];
    $popularPosts = [];
    $categoryStats = [];
}

// Set page title
$pageTitle = 'Admin Dashboard - ' . SITE_NAME;
$customCSS = '<link rel="stylesheet" href="' . CSS_URL . '/admin.css">';
?>
<?php include '../includes/header.php'; ?>

<div class="container-fluid my-4">
    <!-- Page Header -->
    <div class="row mb-4">
        <div class="col-12">
            <h1 class="fw-bold mb-2" data-aos="fade-right">
                <i class="fas fa-tachometer-alt text-primary me-2"></i>
                Admin Dashboard
            </h1>
            <p class="text-muted">Welcome back, <?php echo e(getCurrentUsername()); ?>! Here's what's happening.</p>
        </div>
    </div>
    
    <!-- Statistics Cards -->
    <div class="row g-3 mb-4">
        <div class="col-md-6 col-lg-3" data-aos="fade-up" data-aos-delay="100">
            <div class="card stat-card bg-primary text-white shadow">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h6 class="text-white-50 mb-1">Total Users</h6>
                            <h2 class="mb-0"><?php echo number_format($stats['total_users'] ?? 0); ?></h2>
                            <small><?php echo $stats['admin_count'] ?? 0; ?> admins</small>
                        </div>
                        <i class="fas fa-users fa-3x opacity-50"></i>
                    </div>
                </div>
                <div class="card-footer bg-transparent border-0">
                    <a href="<?php echo SITE_URL; ?>/admin/users.php" class="text-white text-decoration-none small">
                        View all <i class="fas fa-arrow-right ms-1"></i>
                    </a>
                </div>
            </div>
        </div>
        
        <div class="col-md-6 col-lg-3" data-aos="fade-up" data-aos-delay="200">
            <div class="card stat-card bg-success text-white shadow">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h6 class="text-white-50 mb-1">Total Posts</h6>
                            <h2 class="mb-0"><?php echo number_format($stats['total_posts'] ?? 0); ?></h2>
                            <small><?php echo $stats['published_posts'] ?? 0; ?> published</small>
                        </div>
                        <i class="fas fa-file-alt fa-3x opacity-50"></i>
                    </div>
                </div>
                <div class="card-footer bg-transparent border-0">
                    <a href="<?php echo SITE_URL; ?>/admin/posts.php" class="text-white text-decoration-none small">
                        View all <i class="fas fa-arrow-right ms-1"></i>
                    </a>
                </div>
            </div>
        </div>
        
        <div class="col-md-6 col-lg-3" data-aos="fade-up" data-aos-delay="300">
            <div class="card stat-card bg-info text-white shadow">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h6 class="text-white-50 mb-1">Total Views</h6>
                            <h2 class="mb-0"><?php echo number_format($stats['total_views'] ?? 0); ?></h2>
                            <small><?php echo number_format($stats['total_reactions'] ?? 0); ?> reactions</small>
                        </div>
                        <i class="fas fa-eye fa-3x opacity-50"></i>
                    </div>
                </div>
                <div class="card-footer bg-transparent border-0">
                    <a href="<?php echo SITE_URL; ?>/admin/posts.php" class="text-white text-decoration-none small">
                        View analytics <i class="fas fa-arrow-right ms-1"></i>
                    </a>
                </div>
            </div>
        </div>
        
        <div class="col-md-6 col-lg-3" data-aos="fade-up" data-aos-delay="400">
            <div class="card stat-card bg-warning text-white shadow">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h6 class="text-white-50 mb-1">Comments</h6>
                            <h2 class="mb-0"><?php echo number_format($stats['total_comments'] ?? 0); ?></h2>
                            <small><?php echo $stats['total_categories'] ?? 0; ?> categories</small>
                        </div>
                        <i class="fas fa-comments fa-3x opacity-50"></i>
                    </div>
                </div>
                <div class="card-footer bg-transparent border-0">
                    <a href="<?php echo SITE_URL; ?>/admin/comments.php" class="text-white text-decoration-none small">
                        Manage <i class="fas fa-arrow-right ms-1"></i>
                    </a>
                </div>
            </div>
        </div>
    </div>
    
    <div class="row g-4">
        <!-- Left Column -->
        <div class="col-lg-8">
            <!-- Recent Posts -->
            <div class="card shadow-sm mb-4" data-aos="fade-up">
                <div class="card-header bg-white">
                    <h5 class="mb-0">
                        <i class="fas fa-file-alt me-2"></i>
                        Recent Posts
                    </h5>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th>Title</th>
                                    <th>Author</th>
                                    <th>Category</th>
                                    <th>Status</th>
                                    <th>Date</th>
                                    <th>Views</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (!empty($recentPosts)): ?>
                                    <?php foreach ($recentPosts as $post): ?>
                                    <tr>
                                        <td>
                                            <a href="<?php echo SITE_URL; ?>/posts/view_blog.php?id=<?php echo $post['id']; ?>" 
                                               class="text-decoration-none">
                                                <?php echo e(truncate($post['title'], 50)); ?>
                                            </a>
                                        </td>
                                        <td><?php echo e($post['username']); ?></td>
                                        <td>
                                            <?php if ($post['category_name']): ?>
                                            <span class="badge bg-primary"><?php echo e($post['category_name']); ?></span>
                                            <?php else: ?>
                                            <span class="text-muted">—</span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <?php if ($post['status'] === 'published'): ?>
                                            <span class="badge bg-success">Published</span>
                                            <?php else: ?>
                                            <span class="badge bg-warning text-dark">Draft</span>
                                            <?php endif; ?>
                                        </td>
                                        <td class="small"><?php echo timeAgo($post['created_at']); ?></td>
                                        <td><?php echo number_format($post['views']); ?></td>
                                    </tr>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <tr><td colspan="6" class="text-center text-muted">No posts found</td></tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
            
            <!-- Popular Posts This Month -->
            <div class="card shadow-sm mb-4" data-aos="fade-up">
                <div class="card-header bg-white">
                    <h5 class="mb-0">
                        <i class="fas fa-fire text-danger me-2"></i>
                        Trending Posts (Last 30 Days)
                    </h5>
                </div>
                <div class="card-body p-0">
                    <div class="list-group list-group-flush">
                        <?php if (!empty($popularPosts)): ?>
                            <?php foreach ($popularPosts as $post): ?>
                            <div class="list-group-item">
                                <div class="d-flex justify-content-between">
                                    <div>
                                        <a href="<?php echo SITE_URL; ?>/posts/view_blog.php?id=<?php echo $post['id']; ?>" 
                                           class="text-decoration-none fw-bold">
                                            <?php echo e(truncate($post['title'], 60)); ?>
                                        </a>
                                        <div class="small text-muted">by <?php echo e($post['username']); ?></div>
                                    </div>
                                    <div class="text-end">
                                        <div><i class="fas fa-eye text-info"></i> <?php echo number_format($post['views']); ?></div>
                                        <div class="small text-muted">
                                            <i class="fas fa-heart"></i> <?php echo $post['reactions']; ?> • 
                                            <i class="fas fa-comment"></i> <?php echo $post['comments']; ?>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <div class="list-group-item text-center text-muted">No posts this month</div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Right Column -->
        <div class="col-lg-4">
            <!-- Quick Actions -->
            <div class="card shadow-sm mb-4" data-aos="fade-left">
                <div class="card-header bg-white">
                    <h5 class="mb-0">
                        <i class="fas fa-bolt me-2"></i>
                        Quick Actions
                    </h5>
                </div>
                <div class="card-body">
                    <div class="d-grid gap-2">
                        <a href="<?php echo SITE_URL; ?>/admin/users.php" class="btn btn-outline-primary">
                            <i class="fas fa-users me-2"></i> Manage Users
                        </a>
                        <a href="<?php echo SITE_URL; ?>/admin/posts.php" class="btn btn-outline-success">
                            <i class="fas fa-file-alt me-2"></i> Manage Posts
                        </a>
                        <a href="<?php echo SITE_URL; ?>/admin/categories.php" class="btn btn-outline-info">
                            <i class="fas fa-tags me-2"></i> Manage Categories
                        </a>
                        <a href="<?php echo SITE_URL; ?>/admin/comments.php" class="btn btn-outline-warning">
                            <i class="fas fa-comments me-2"></i> Manage Comments
                        </a>
                    </div>
                </div>
            </div>
            
            <!-- Top Authors -->
            <div class="card shadow-sm mb-4" data-aos="fade-left" data-aos-delay="100">
                <div class="card-header bg-white">
                    <h5 class="mb-0">
                        <i class="fas fa-crown text-warning me-2"></i>
                        Top Authors
                    </h5>
                </div>
                <div class="card-body p-0">
                    <div class="list-group list-group-flush">
                        <?php if (!empty($topAuthors)): ?>
                            <?php foreach ($topAuthors as $author): ?>
                            <div class="list-group-item">
                                <div class="d-flex justify-content-between align-items-center">
                                    <div>
                                        <a href="<?php echo SITE_URL; ?>/profile/user_profile.php?id=<?php echo $author['id']; ?>" 
                                           class="text-decoration-none fw-bold">
                                            <?php echo e($author['username']); ?>
                                        </a>
                                        <div class="small text-muted">
                                            <?php echo $author['post_count']; ?> posts • 
                                            <?php echo number_format($author['total_views']); ?> views
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <div class="list-group-item text-center text-muted">No data available</div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            
            <!-- Recent Users -->
            <div class="card shadow-sm" data-aos="fade-left" data-aos-delay="200">
                <div class="card-header bg-white">
                    <h5 class="mb-0">
                        <i class="fas fa-user-plus me-2"></i>
                        Recent Users
                    </h5>
                </div>
                <div class="card-body p-0">
                    <div class="list-group list-group-flush">
                        <?php if (!empty($recentUsers)): ?>
                            <?php foreach ($recentUsers as $user): ?>
                            <div class="list-group-item">
                                <div class="d-flex justify-content-between">
                                    <div>
                                        <strong><?php echo e($user['username']); ?></strong>
                                        <?php if ($user['role'] === 'admin'): ?>
                                        <span class="badge bg-danger ms-1">Admin</span>
                                        <?php endif; ?>
                                        <div class="small text-muted"><?php echo e($user['email']); ?></div>
                                    </div>
                                    <small class="text-muted"><?php echo timeAgo($user['created_at']); ?></small>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <div class="list-group-item text-center text-muted">No users found</div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php
include '../includes/footer.php'; 
?>