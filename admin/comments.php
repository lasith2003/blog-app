<?php
/**
 * ================================================================
 * BLOG HUT - Admin Comment Management
 * University of Moratuwa - IN2120 Web Programming Project
 * ================================================================
 * 
 * Manage all comments:
 * - View all comments
 * - Search comments
 * - Filter by post
 * - Delete comments
 * - View comment context
 * - Pagination
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

// Require admin access
requireAdmin();

// Get filters
$currentPage = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
$searchTerm = isset($_GET['search']) ? cleanInput($_GET['search']) : '';
$postFilter = isset($_GET['post_id']) ? intval($_GET['post_id']) : null;

// Handle comment deletion
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    $commentId = isset($_POST['comment_id']) ? intval($_POST['comment_id']) : 0;
    $csrfToken = $_POST['csrf_token'] ?? '';
    
    if (!verifyCSRFToken($csrfToken)) {
        setFlashMessage('Invalid security token.', 'error');
    } else {
        try {
            if ($action === 'delete' && $commentId) {
                updateRecord("DELETE FROM comments WHERE id = ?", [$commentId]);
                setFlashMessage('Comment deleted successfully.', 'success');
            }
            redirect('/admin/comments.php' . buildFilterQuery());
        } catch (Exception $e) {
            error_log("Admin Comment Action Error: " . $e->getMessage());
            setFlashMessage('An error occurred.', 'error');
        }
    }
}

try {
    // Build query
    $sql = "SELECT c.*, u.username, u.profile_image, bp.title as post_title, bp.id as post_id
            FROM comments c
            JOIN users u ON c.user_id = u.id
            JOIN blogPost bp ON c.blog_id = bp.id
            WHERE 1=1";
    
    $params = [];
    
    // Add search filter
    if ($searchTerm) {
        $sql .= " AND (c.comment LIKE ? OR u.username LIKE ?)";
        $searchParam = "%$searchTerm%";
        $params[] = $searchParam;
        $params[] = $searchParam;
    }
    
    // Add post filter
    if ($postFilter) {
        $sql .= " AND c.blog_id = ?";
        $params[] = $postFilter;
    }
    
    $sql .= " ORDER BY c.created_at DESC";
    
    // Get total count
    $countSql = "SELECT COUNT(*) as total FROM comments c 
                 JOIN users u ON c.user_id = u.id 
                 JOIN blogPost bp ON c.blog_id = bp.id 
                 WHERE 1=1";
    $countParams = [];
    
    if ($searchTerm) {
        $countSql .= " AND (c.comment LIKE ? OR u.username LIKE ?)";
        $countParams[] = $searchParam;
        $countParams[] = $searchParam;
    }
    
    if ($postFilter) {
        $countSql .= " AND c.blog_id = ?";
        $countParams[] = $postFilter;
    }
    
    $totalComments = fetchOne($countSql, $countParams)['total'];
    $totalPages = ceil($totalComments / COMMENTS_PER_PAGE);
    
    // Add pagination
    $offset = ($currentPage - 1) * COMMENTS_PER_PAGE;
    $sql .= " LIMIT ? OFFSET ?";
    $params[] = COMMENTS_PER_PAGE;
    $params[] = $offset;
    
    $comments = fetchAll($sql, $params);
    
} catch (Exception $e) {
    error_log("Admin Comments Error: " . $e->getMessage());
    $comments = [];
    $totalComments = 0;
    $totalPages = 0;
}

function buildFilterQuery() {
    global $searchTerm, $postFilter;
    $query = '?';
    $params = [];
    if ($searchTerm) $params[] = 'search=' . urlencode($searchTerm);
    if ($postFilter) $params[] = 'post_id=' . $postFilter;
    return empty($params) ? '' : '?' . implode('&', $params);
}

$pageTitle = 'Manage Comments - Admin - ' . SITE_NAME;
$customCSS = '<link rel="stylesheet" href="' . CSS_URL . '/admin.css">';
?>
<?php include '../includes/header.php'; ?>

<div class="container-fluid my-4">
    <!-- Header -->
    <div class="row mb-4">
        <div class="col-md-6">
            <h1 class="fw-bold mb-2">
                <i class="fas fa-comments text-primary me-2"></i>
                Manage Comments
            </h1>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="<?php echo SITE_URL; ?>/admin/dashboard.php">Dashboard</a></li>
                    <li class="breadcrumb-item active">Comments</li>
                </ol>
            </nav>
        </div>
        <div class="col-md-6 text-md-end">
            <a href="<?php echo SITE_URL; ?>/admin/dashboard.php" class="btn btn-outline-secondary">
                <i class="fas fa-arrow-left me-2"></i> Back to Dashboard
            </a>
        </div>
    </div>
    
    <!-- Filters -->
    <div class="card shadow-sm mb-4">
        <div class="card-body">
            <form method="GET" class="row g-3">
                <div class="col-md-8">
                    <label class="form-label">Search Comments</label>
                    <input type="text" class="form-control" name="search" 
                           value="<?php echo e($searchTerm); ?>" 
                           placeholder="Search by comment text or username...">
                </div>
                <div class="col-md-2 d-flex align-items-end">
                    <button type="submit" class="btn btn-primary w-100">
                        <i class="fas fa-search me-1"></i> Search
                    </button>
                </div>
                <?php if ($searchTerm || $postFilter): ?>
                <div class="col-md-2 d-flex align-items-end">
                    <a href="?" class="btn btn-secondary w-100">
                        <i class="fas fa-times me-1"></i> Clear
                    </a>
                </div>
                <?php endif; ?>
            </form>
        </div>
    </div>
    
    <!-- Comments List -->
    <div class="card shadow-sm">
        <div class="card-header bg-white">
            <h5 class="mb-0">
                Comments List (<?php echo number_format($totalComments); ?>)
            </h5>
        </div>
        <div class="card-body p-0">
            <?php if (!empty($comments)): ?>
                <div class="list-group list-group-flush">
                    <?php foreach ($comments as $comment): ?>
                    <div class="list-group-item">
                        <div class="row">
                            <div class="col-md-8">
                                <!-- User Info -->
                                <div class="d-flex align-items-start mb-2">
                                    <img src="<?php echo AVATAR_URL . '/' . ($comment['profile_image'] ?? DEFAULT_AVATAR); ?>" 
                                         class="rounded-circle me-3"
                                         style="width: 40px; height: 40px; object-fit: cover;">
                                    <div class="flex-grow-1">
                                        <div class="mb-1">
                                            <strong>
                                                <a href="<?php echo SITE_URL; ?>/profile/user_profile.php?id=<?php echo $comment['user_id']; ?>">
                                                    <?php echo e($comment['username']); ?>
                                                </a>
                                            </strong>
                                            <span class="text-muted small ms-2">
                                                <i class="fas fa-clock"></i>
                                                <?php echo timeAgo($comment['created_at']); ?>
                                            </span>
                                        </div>
                                        
                                        <!-- Comment Text -->
                                        <p class="mb-2"><?php echo e($comment['comment']); ?></p>
                                        
                                        <!-- Post Context -->
                                        <div class="small text-muted">
                                            <i class="fas fa-file-alt me-1"></i>
                                            On post: 
                                            <a href="<?php echo SITE_URL; ?>/posts/view_blog.php?id=<?php echo $comment['post_id']; ?>">
                                                <?php echo e(truncate($comment['post_title'], 60)); ?>
                                            </a>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="col-md-4 text-md-end">
                                <div class="d-flex flex-column gap-2 align-items-md-end">
                                    <!-- Comment ID -->
                                    <small class="text-muted">ID: #<?php echo $comment['id']; ?></small>
                                    
                                    <!-- Actions -->
                                    <div class="btn-group btn-group-sm">
                                        <a href="<?php echo SITE_URL; ?>/posts/view_blog.php?id=<?php echo $comment['post_id']; ?>#comment-<?php echo $comment['id']; ?>" 
                                           class="btn btn-outline-primary" 
                                           title="View in Context">
                                            <i class="fas fa-eye"></i> View
                                        </a>
                                        <button type="button" 
                                                class="btn btn-outline-danger" 
                                                onclick="deleteComment(<?php echo $comment['id']; ?>)"
                                                title="Delete Comment">
                                            <i class="fas fa-trash"></i> Delete
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            <?php else: ?>
                <div class="text-center py-5 text-muted">
                    <i class="fas fa-comments fa-4x mb-3"></i>
                    <h5>No comments found</h5>
                    <p>
                        <?php if ($searchTerm): ?>
                            Try adjusting your search term
                        <?php else: ?>
                            No comments have been posted yet
                        <?php endif; ?>
                    </p>
                </div>
            <?php endif; ?>
        </div>
        
        <!-- Pagination -->
        <?php if ($totalPages > 1): ?>
        <div class="card-footer bg-white">
            <nav>
                <ul class="pagination justify-content-center mb-0">
                    <li class="page-item <?php echo $currentPage <= 1 ? 'disabled' : ''; ?>">
                        <a class="page-link" href="?page=<?php echo $currentPage - 1; ?><?php echo substr(buildFilterQuery(), 1); ?>">
                            Previous
                        </a>
                    </li>
                    <?php for ($i = max(1, $currentPage - 2); $i <= min($totalPages, $currentPage + 2); $i++): ?>
                    <li class="page-item <?php echo $i === $currentPage ? 'active' : ''; ?>">
                        <a class="page-link" href="?page=<?php echo $i; ?><?php echo substr(buildFilterQuery(), 1); ?>">
                            <?php echo $i; ?>
                        </a>
                    </li>
                    <?php endfor; ?>
                    <li class="page-item <?php echo $currentPage >= $totalPages ? 'disabled' : ''; ?>">
                        <a class="page-link" href="?page=<?php echo $currentPage + 1; ?><?php echo substr(buildFilterQuery(), 1); ?>">
                            Next
                        </a>
                    </li>
                </ul>
            </nav>
        </div>
        <?php endif; ?>
    </div>
</div>

<!-- Delete Form -->
<form id="deleteCommentForm" method="POST" style="display: none;">
    <?php echo csrfField(); ?>
    <input type="hidden" name="action" value="delete">
    <input type="hidden" name="comment_id" id="deleteCommentId">
</form>

<script>
function deleteComment(commentId) {
    Swal.fire({
        title: 'Delete Comment?',
        text: 'This action cannot be undone!',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#dc3545',
        confirmButtonText: 'Yes, delete it!'
    }).then((result) => {
        if (result.isConfirmed) {
            document.getElementById('deleteCommentId').value = commentId;
            document.getElementById('deleteCommentForm').submit();
        }
    });
}
</script>

<?php include '../includes/footer.php'; ?>