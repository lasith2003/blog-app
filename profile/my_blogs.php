<?php
/**
 * ================================================================
 * BLOG HUT - My Blog Posts
 * University of Moratuwa - IN2120 Web Programming Project
 * ================================================================
 * 
 * This page displays all blog posts by the logged-in user:
 * - Published and draft posts
 * - Post statistics
 * - Edit/Delete actions
 * - Pagination
 * - Filter by status
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

// Get filter parameters
$currentPage = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
$statusFilter = isset($_GET['status']) ? cleanInput($_GET['status']) : 'all';

try {
    // Build query based on status filter
    $sql = "SELECT bp.*, c.name as category_name,
            (SELECT COUNT(*) FROM reactions WHERE blog_id = bp.id) as reaction_count,
            (SELECT COUNT(*) FROM comments WHERE blog_id = bp.id) as comment_count
            FROM blogPost bp
            LEFT JOIN categories c ON bp.category = c.id
            WHERE bp.user_id = ?";
    
    $params = [$userId];
    
    // Add status filter
    if ($statusFilter === 'published') {
        $sql .= " AND bp.status = 'published'";
    } elseif ($statusFilter === 'draft') {
        $sql .= " AND bp.status = 'draft'";
    }
    
    $sql .= " ORDER BY bp.created_at DESC";
    
    // Get total count for pagination
    $countSql = "SELECT COUNT(*) as total FROM blogPost WHERE user_id = ?";
    $countParams = [$userId];
    
    if ($statusFilter === 'published') {
        $countSql .= " AND status = 'published'";
    } elseif ($statusFilter === 'draft') {
        $countSql .= " AND status = 'draft'";
    }
    
    $countResult = fetchOne($countSql, $countParams);
    $totalPosts = $countResult['total'] ?? 0;
    $totalPages = ceil($totalPosts / USER_POSTS_PER_PAGE);
    
    // Add pagination
    $offset = ($currentPage - 1) * USER_POSTS_PER_PAGE;
    $sql .= " LIMIT ? OFFSET ?";
    $params[] = USER_POSTS_PER_PAGE;
    $params[] = $offset;
    
    // Execute query
    $posts = fetchAll($sql, $params);
    
    // Get status counts
    $publishedCount = fetchOne("SELECT COUNT(*) as count FROM blogPost WHERE user_id = ? AND status = 'published'", [$userId])['count'];
    $draftCount = fetchOne("SELECT COUNT(*) as count FROM blogPost WHERE user_id = ? AND status = 'draft'", [$userId])['count'];
    
} catch (Exception $e) {
    error_log("My Blogs Error: " . $e->getMessage());
    $posts = [];
    $totalPosts = 0;
    $totalPages = 0;
    $publishedCount = 0;
    $draftCount = 0;
}

// Set page title
$pageTitle = 'My Blog Posts - ' . SITE_NAME;
?>
<?php include '../includes/header.php'; ?>

<div class="container my-5">
    <!-- Page Header -->
    <div class="row mb-4">
        <div class="col-md-8">
            <h1 class="fw-bold mb-2" data-aos="fade-right">
                <i class="fas fa-file-alt text-primary me-2"></i>
                My Blog Posts
            </h1>
            <p class="text-muted">
                Manage all your blog posts in one place
            </p>
        </div>
        <div class="col-md-4 text-md-end" data-aos="fade-left">
            <a href="<?php echo SITE_URL; ?>/posts/create_blog.php" class="btn btn-primary">
                <i class="fas fa-plus me-2"></i> Create New Post
            </a>
        </div>
    </div>
    
    <!-- Status Filters -->
    <div class="card shadow-sm mb-4" data-aos="fade-up">
        <div class="card-body">
            <div class="row align-items-center">
                <div class="col-md-8">
                    <div class="btn-group" role="group">
                        <a href="?" 
                           class="btn <?php echo $statusFilter === 'all' ? 'btn-primary' : 'btn-outline-primary'; ?>">
                            <i class="fas fa-list me-1"></i>
                            All Posts (<?php echo $publishedCount + $draftCount; ?>)
                        </a>
                        <a href="?status=published" 
                           class="btn <?php echo $statusFilter === 'published' ? 'btn-primary' : 'btn-outline-primary'; ?>">
                            <i class="fas fa-check-circle me-1"></i>
                            Published (<?php echo $publishedCount; ?>)
                        </a>
                        <a href="?status=draft" 
                           class="btn <?php echo $statusFilter === 'draft' ? 'btn-primary' : 'btn-outline-primary'; ?>">
                            <i class="fas fa-file me-1"></i>
                            Drafts (<?php echo $draftCount; ?>)
                        </a>
                    </div>
                </div>
                <div class="col-md-4 text-md-end mt-3 mt-md-0">
                    <small class="text-muted">
                        Showing <?php echo count($posts); ?> of <?php echo number_format($totalPosts); ?> posts
                    </small>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Posts List -->
    <?php if (!empty($posts)): ?>
    <div class="row g-4">
        <?php foreach ($posts as $index => $post): ?>
        <div class="col-12" data-aos="fade-up" data-aos-delay="<?php echo $index * 50; ?>">
            <div class="card shadow-sm hover-lift">
                <div class="card-body">
                    <div class="row">
                        <!-- Post Image -->
                        <?php if ($post['featured_image']): ?>
                        <div class="col-md-3">
                            <a href="<?php echo SITE_URL; ?>/posts/view_blog.php?id=<?php echo $post['id']; ?>">
                                <img src="<?php echo POST_IMAGE_URL . '/' . $post['featured_image']; ?>" 
                                     alt="<?php echo e($post['title']); ?>"
                                     class="img-fluid rounded"
                                     style="height: 150px; width: 100%; object-fit: cover;"
                                     onerror="this.style.display='none'">
                            </a>
                        </div>
                        <div class="col-md-9">
                        <?php else: ?>
                        <div class="col-12">
                        <?php endif; ?>
                            <!-- Post Info -->
                            <div class="d-flex justify-content-between align-items-start mb-2">
                                <div class="flex-grow-1">
                                    <h5 class="mb-1">
                                        <a href="<?php echo SITE_URL; ?>/posts/view_blog.php?id=<?php echo $post['id']; ?>" 
                                           class="text-decoration-none text-dark">
                                            <?php echo e($post['title']); ?>
                                        </a>
                                    </h5>
                                    
                                    <div class="mb-2">
                                        <!-- Status Badge -->
                                        <?php if ($post['status'] === 'draft'): ?>
                                        <span class="badge bg-warning text-dark">
                                            <i class="fas fa-file me-1"></i> Draft
                                        </span>
                                        <?php else: ?>
                                        <span class="badge bg-success">
                                            <i class="fas fa-check-circle me-1"></i> Published
                                        </span>
                                        <?php endif; ?>
                                        
                                        <!-- Category Badge -->
                                        <?php if ($post['category_name']): ?>
                                        <span class="badge bg-primary">
                                            <i class="fas fa-tag me-1"></i>
                                            <?php echo e($post['category_name']); ?>
                                        </span>
                                        <?php endif; ?>
                                    </div>
                                    
                                    <div class="d-flex gap-3 text-muted small mb-2">
                                        <span>
                                            <i class="fas fa-calendar"></i>
                                            <?php echo formatDate($post['created_at']); ?>
                                        </span>
                                        <span>
                                            <i class="fas fa-eye"></i>
                                            <?php echo number_format($post['views']); ?> views
                                        </span>
                                        <span>
                                            <i class="fas fa-heart"></i>
                                            <?php echo $post['reaction_count']; ?> reactions
                                        </span>
                                        <span>
                                            <i class="fas fa-comment"></i>
                                            <?php echo $post['comment_count']; ?> comments
                                        </span>
                                    </div>
                                    
                                    <?php if ($post['summary']): ?>
                                    <p class="text-muted small mb-0">
                                        <?php echo e(truncate($post['summary'], 150)); ?>
                                    </p>
                                    <?php endif; ?>
                                </div>
                                
                                <!-- Action Buttons -->
                                <div class="btn-group ms-3">
                                    <a href="<?php echo SITE_URL; ?>/posts/view_blog.php?id=<?php echo $post['id']; ?>" 
                                       class="btn btn-sm btn-outline-primary"
                                       title="View">
                                        <i class="fas fa-eye"></i>
                                    </a>
                                    <a href="<?php echo SITE_URL; ?>/posts/edit_blog.php?id=<?php echo $post['id']; ?>" 
                                       class="btn btn-sm btn-outline-secondary"
                                       title="Edit">
                                        <i class="fas fa-edit"></i>
                                    </a>
                                    <button type="button" 
                                            class="btn btn-sm btn-outline-danger" 
                                            onclick="confirmDelete(<?php echo $post['id']; ?>)"
                                            title="Delete">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                </div>
                            </div>
                            
                            <!-- Updated Info -->
                            <?php if ($post['updated_at'] != $post['created_at']): ?>
                            <small class="text-muted">
                                <i class="fas fa-edit me-1"></i>
                                Last updated <?php echo timeAgo($post['updated_at']); ?>
                            </small>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
    
    <!-- Pagination -->
    <?php if ($totalPages > 1): ?>
    <nav aria-label="Posts pagination" class="mt-5">
        <ul class="pagination justify-content-center">
            <!-- Previous -->
            <li class="page-item <?php echo $currentPage <= 1 ? 'disabled' : ''; ?>">
                <a class="page-link" href="?page=<?php echo $currentPage - 1; ?><?php echo $statusFilter !== 'all' ? '&status=' . $statusFilter : ''; ?>">
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
                <a class="page-link" href="?page=<?php echo $i; ?><?php echo $statusFilter !== 'all' ? '&status=' . $statusFilter : ''; ?>">
                    <?php echo $i; ?>
                </a>
            </li>
            <?php endfor; ?>
            
            <!-- Next -->
            <li class="page-item <?php echo $currentPage >= $totalPages ? 'disabled' : ''; ?>">
                <a class="page-link" href="?page=<?php echo $currentPage + 1; ?><?php echo $statusFilter !== 'all' ? '&status=' . $statusFilter : ''; ?>">
                    <i class="fas fa-chevron-right"></i>
                </a>
            </li>
        </ul>
        
        <p class="text-center text-muted small">
            Page <?php echo $currentPage; ?> of <?php echo $totalPages; ?>
        </p>
    </nav>
    <?php endif; ?>
    
    <?php else: ?>
    <!-- Empty State -->
    <div class="text-center py-5" data-aos="fade-up">
        <i class="fas fa-file-alt fa-4x text-muted mb-3"></i>
        <h4 class="text-muted">
            <?php if ($statusFilter === 'draft'): ?>
                No draft posts found
            <?php elseif ($statusFilter === 'published'): ?>
                No published posts yet
            <?php else: ?>
                You haven't created any posts yet
            <?php endif; ?>
        </h4>
        <p class="text-muted">
            <?php if ($statusFilter !== 'all'): ?>
                Try viewing all posts or create a new one
            <?php else: ?>
                Start sharing your stories with the world!
            <?php endif; ?>
        </p>
        <div class="mt-4">
            <?php if ($statusFilter !== 'all'): ?>
            <a href="?" class="btn btn-outline-primary me-2">
                <i class="fas fa-list me-2"></i> View All Posts
            </a>
            <?php endif; ?>
            <a href="<?php echo SITE_URL; ?>/posts/create_blog.php" class="btn btn-primary">
                <i class="fas fa-plus me-2"></i> Create New Post
            </a>
        </div>
    </div>
    <?php endif; ?>
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

<?php include '../includes/footer.php'; ?>