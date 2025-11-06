<?php
/**
 * BLOG HUT - Admin Post Management
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

// Get filters
$currentPage = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
$statusFilter = isset($_GET['status']) ? cleanInput($_GET['status']) : 'all';
$categoryFilter = isset($_GET['category']) ? intval($_GET['category']) : null;
$searchTerm = isset($_GET['search']) ? cleanInput($_GET['search']) : '';

// Handle post actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    $postId = isset($_POST['post_id']) ? intval($_POST['post_id']) : 0;
    $csrfToken = $_POST['csrf_token'] ?? '';
    
    if (!verifyCSRFToken($csrfToken)) {
        setFlashMessage('Invalid security token.', 'error');
    } else {
        try {
            if ($action === 'delete' && $postId) {
                $post = getPostById($postId);
                if ($post && $post['featured_image']) {
                    deleteFile(POST_IMAGE_PATH . '/' . $post['featured_image']);
                }
                deletePost($postId);
                setFlashMessage('Post deleted successfully.', 'success');
            } elseif ($action === 'toggle_status' && $postId) {
                $post = getPostById($postId);
                $newStatus = $post['status'] === 'published' ? 'draft' : 'published';
                updateRecord("UPDATE blogPost SET status = ? WHERE id = ?", [$newStatus, $postId]);
                setFlashMessage('Post status updated.', 'success');
            }
            redirect('/admin/posts.php' . buildFilterQuery());
        } catch (Exception $e) {
            error_log("Admin Post Action Error: " . $e->getMessage());
            setFlashMessage('An error occurred.', 'error');
        }
    }
}

try {
    // Build query
    $sql = "SELECT bp.*, u.username, c.name as category_name,
            (SELECT COUNT(*) FROM reactions WHERE blog_id = bp.id) as reaction_count,
            (SELECT COUNT(*) FROM comments WHERE blog_id = bp.id) as comment_count
            FROM blogPost bp
            JOIN users u ON bp.user_id = u.id
            LEFT JOIN categories c ON bp.category = c.id
            WHERE 1=1";
    
    $params = [];
    
    // Add status filter
    if ($statusFilter !== 'all') {
        $sql .= " AND bp.status = ?";
        $params[] = $statusFilter;
    }
    
    // Add category filter
    if ($categoryFilter) {
        $sql .= " AND bp.category = ?";
        $params[] = $categoryFilter;
    }
    
    // Add search filter
    if ($searchTerm) {
        $sql .= " AND (bp.title LIKE ? OR bp.content LIKE ?)";
        $searchParam = "%$searchTerm%";
        $params[] = $searchParam;
        $params[] = $searchParam;
    }
    
    $sql .= " ORDER BY bp.created_at DESC";
    
    // Get total count
    $countSql = "SELECT COUNT(*) as total FROM blogPost bp WHERE 1=1";
    $countParams = [];
    
    if ($statusFilter !== 'all') {
        $countSql .= " AND bp.status = ?";
        $countParams[] = $statusFilter;
    }
    
    if ($categoryFilter) {
        $countSql .= " AND bp.category = ?";
        $countParams[] = $categoryFilter;
    }
    
    if ($searchTerm) {
        $countSql .= " AND (bp.title LIKE ? OR bp.content LIKE ?)";
        $countParams[] = $searchParam;
        $countParams[] = $searchParam;
    }
    
    $totalPosts = fetchOne($countSql, $countParams)['total'];
    $totalPages = ceil($totalPosts / ADMIN_POSTS_PER_PAGE);
    
    // Add pagination
    $offset = ($currentPage - 1) * ADMIN_POSTS_PER_PAGE;
    $sql .= " LIMIT ? OFFSET ?";
    $params[] = ADMIN_POSTS_PER_PAGE;
    $params[] = $offset;
    
    $posts = fetchAll($sql, $params);
    
    // Get status counts
    $publishedCount = fetchOne("SELECT COUNT(*) as count FROM blogPost WHERE status = 'published'")['count'];
    $draftCount = fetchOne("SELECT COUNT(*) as count FROM blogPost WHERE status = 'draft'")['count'];
    
    // Get categories
    $categories = getAllCategories();
    
} catch (Exception $e) {
    error_log("Admin Posts Error: " . $e->getMessage());
    $posts = [];
    $totalPosts = 0;
    $totalPages = 0;
}

function buildFilterQuery() {
    global $statusFilter, $categoryFilter, $searchTerm;
    $query = '?';
    $params = [];
    if ($statusFilter !== 'all') $params[] = 'status=' . $statusFilter;
    if ($categoryFilter) $params[] = 'category=' . $categoryFilter;
    if ($searchTerm) $params[] = 'search=' . urlencode($searchTerm);
    return empty($params) ? '' : '?' . implode('&', $params);
}

$pageTitle = 'Manage Posts - Admin - ' . SITE_NAME;
$customCSS = '<link rel="stylesheet" href="' . CSS_URL . '/admin.css">';
?>
<?php include '../includes/header.php'; ?>

<div class="container-fluid my-4">
    <!-- Header -->
    <div class="row mb-4">
        <div class="col-md-6">
            <h1 class="fw-bold mb-2">
                <i class="fas fa-file-alt text-primary me-2"></i>
                Manage Posts
            </h1>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="<?php echo SITE_URL; ?>/admin/dashboard.php">Dashboard</a></li>
                    <li class="breadcrumb-item active">Posts</li>
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
                <div class="col-md-4">
                    <label class="form-label">Search Posts</label>
                    <input type="text" class="form-control" name="search" 
                           value="<?php echo e($searchTerm); ?>" 
                           placeholder="Search by title or content...">
                </div>
                <div class="col-md-2">
                    <label class="form-label">Status</label>
                    <select class="form-select" name="status">
                        <option value="all" <?php echo $statusFilter === 'all' ? 'selected' : ''; ?>>
                            All (<?php echo $publishedCount + $draftCount; ?>)
                        </option>
                        <option value="published" <?php echo $statusFilter === 'published' ? 'selected' : ''; ?>>
                            Published (<?php echo $publishedCount; ?>)
                        </option>
                        <option value="draft" <?php echo $statusFilter === 'draft' ? 'selected' : ''; ?>>
                            Drafts (<?php echo $draftCount; ?>)
                        </option>
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label">Category</label>
                    <select class="form-select" name="category">
                        <option value="">All Categories</option>
                        <?php foreach ($categories as $cat): ?>
                        <option value="<?php echo $cat['id']; ?>" <?php echo $categoryFilter == $cat['id'] ? 'selected' : ''; ?>>
                            <?php echo e($cat['name']); ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-1 d-flex align-items-end">
                    <button type="submit" class="btn btn-primary w-100">
                        <i class="fas fa-search"></i>
                    </button>
                </div>
                <?php if ($searchTerm || $statusFilter !== 'all' || $categoryFilter): ?>
                <div class="col-md-2 d-flex align-items-end">
                    <a href="?" class="btn btn-secondary w-100">
                        <i class="fas fa-times me-1"></i> Clear
                    </a>
                </div>
                <?php endif; ?>
            </form>
        </div>
    </div>
    
    <!-- Posts Table -->
    <div class="card shadow-sm">
        <div class="card-header bg-white">
            <h5 class="mb-0">Posts List (<?php echo number_format($totalPosts); ?>)</h5>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead class="table-light">
                        <tr>
                            <th style="width: 50px;">ID</th>
                            <th>Title</th>
                            <th>Author</th>
                            <th>Category</th>
                            <th>Status</th>
                            <th>Stats</th>
                            <th>Date</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (!empty($posts)): ?>
                            <?php foreach ($posts as $post): ?>
                            <tr>
                                <td class="text-muted">#<?php echo $post['id']; ?></td>
                                <td>
                                    <div class="d-flex align-items-center">
                                        <?php if ($post['featured_image']): ?>
                                        <img src="<?php echo POST_IMAGE_URL . '/' . $post['featured_image']; ?>" 
                                             class="rounded me-2"
                                             style="width: 50px; height: 50px; object-fit: cover;">
                                        <?php endif; ?>
                                        <div>
                                            <a href="<?php echo SITE_URL; ?>/posts/view_blog.php?id=<?php echo $post['id']; ?>" 
                                               class="text-decoration-none fw-bold">
                                                <?php echo e(truncate($post['title'], 50)); ?>
                                            </a>
                                        </div>
                                    </div>
                                </td>
                                <td>
                                    <a href="<?php echo SITE_URL; ?>/profile/user_profile.php?id=<?php echo $post['user_id']; ?>">
                                        <?php echo e($post['username']); ?>
                                    </a>
                                </td>
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
                                <td>
                                    <small class="text-muted">
                                        <i class="fas fa-eye"></i> <?php echo number_format($post['views']); ?> •
                                        <i class="fas fa-heart"></i> <?php echo $post['reaction_count']; ?> •
                                        <i class="fas fa-comment"></i> <?php echo $post['comment_count']; ?>
                                    </small>
                                </td>
                                <td class="small"><?php echo formatDate($post['created_at'], SHORT_DATE_FORMAT); ?></td>
                                <td>
                                    <div class="btn-group btn-group-sm">
                                        <a href="<?php echo SITE_URL; ?>/posts/view_blog.php?id=<?php echo $post['id']; ?>" 
                                           class="btn btn-outline-primary" title="View">
                                            <i class="fas fa-eye"></i>
                                        </a>
                                        <a href="<?php echo SITE_URL; ?>/posts/edit_blog.php?id=<?php echo $post['id']; ?>" 
                                           class="btn btn-outline-secondary" title="Edit">
                                            <i class="fas fa-edit"></i>
                                        </a>
                                        <button type="button" class="btn btn-outline-warning" 
                                                onclick="toggleStatus(<?php echo $post['id']; ?>, '<?php echo $post['status']; ?>')"
                                                title="Toggle Status">
                                            <i class="fas fa-sync"></i>
                                        </button>
                                        <button type="button" class="btn btn-outline-danger" 
                                                onclick="deletePost(<?php echo $post['id']; ?>)"
                                                title="Delete">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr><td colspan="8" class="text-center text-muted py-4">No posts found</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
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

<!-- Hidden Forms -->
<form id="toggleStatusForm" method="POST" style="display: none;">
    <?php echo csrfField(); ?>
    <input type="hidden" name="action" value="toggle_status">
    <input type="hidden" name="post_id" id="toggleStatusPostId">
</form>

<form id="deletePostForm" method="POST" style="display: none;">
    <?php echo csrfField(); ?>
    <input type="hidden" name="action" value="delete">
    <input type="hidden" name="post_id" id="deletePostId">
</form>

<script>
function toggleStatus(postId, currentStatus) {
    const newStatus = currentStatus === 'published' ? 'Draft' : 'Published';
    
    Swal.fire({
        title: 'Change Post Status?',
        text: `Change this post to ${newStatus}?`,
        icon: 'question',
        showCancelButton: true,
        confirmButtonColor: '#ffc107',
        confirmButtonText: 'Yes, change it!'
    }).then((result) => {
        if (result.isConfirmed) {
            document.getElementById('toggleStatusPostId').value = postId;
            document.getElementById('toggleStatusForm').submit();
        }
    });
}

function deletePost(postId) {
    Swal.fire({
        title: 'Delete Post?',
        text: 'This will permanently delete the post and all its comments and reactions!',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#dc3545',
        confirmButtonText: 'Yes, delete it!'
    }).then((result) => {
        if (result.isConfirmed) {
            document.getElementById('deletePostId').value = postId;
            document.getElementById('deletePostForm').submit();
        }
    });
}
</script>

<?php include '../includes/footer.php'; ?>