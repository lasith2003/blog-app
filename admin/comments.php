
<?php
session_start();
require_once '../config/database.php';
require_once '../config/constants.php';
require_once '../includes/helper.php';

requireAdmin();

// Handle delete
if (isset($_GET['action']) && $_GET['action'] === 'delete') {
    $id = intval($_GET['id']);
    $token = $_GET['token'] ?? '';
    
    if (verifyCSRFToken($token)) {
        executeQuery("DELETE FROM comments WHERE id = ?", [$id]);
        setFlashMessage('Comment deleted successfully!', 'success');
    }
    redirect('admin/comments.php');
}

// Get comments
$search = $_GET['search'] ?? '';
$page = max(1, intval($_GET['page'] ?? 1));
$perPage = 20;
$offset = ($page - 1) * $perPage;

$sql = "SELECT c.*, u.username, bp.title as post_title
        FROM comments c
        JOIN users u ON c.user_id = u.id
        JOIN blogPost bp ON c.blog_id = bp.id
        WHERE 1=1";
$params = [];

if ($search) {
    $sql .= " AND (c.comment LIKE ? OR u.username LIKE ? OR bp.title LIKE ?)";
    $params[] = "%$search%";
    $params[] = "%$search%";
    $params[] = "%$search%";
}

$countSql = str_replace("SELECT c.*, u.username, bp.title as post_title", "SELECT COUNT(*)", $sql);
$total = fetchOne($countSql, $params)['COUNT(*)'] ?? 0;
$totalPages = ceil($total / $perPage);

$sql .= " ORDER BY c.created_at DESC LIMIT ? OFFSET ?";
$params[] = $perPage;
$params[] = $offset;

$comments = fetchAll($sql, $params);

$pageTitle = 'Manage Comments - Admin - ' . SITE_NAME;
?>
<?php include '../includes/header.php'; ?>

<div class="container-fluid my-4">
    <div class="container-fluid">
    <div class="row">
        <!-- Sidebar -->
        <div class="col-md-3 col-lg-2 d-md-block bg-light sidebar">
            <?php require_once __DIR__ . '/includes/sidebar.php'; ?>
        </div>

        <div class="col-md-9 col-lg-10">
            <h2 class="mb-4"><i class="fas fa-comments me-2"></i>Manage Comments</h2>

            <?php echo displayFlashMessage(); ?>

            <!-- Search -->
            <div class="card border-0 shadow-sm mb-4">
                <div class="card-body">
                    <form method="GET" class="row g-3">
                        <div class="col-md-9">
                            <input type="text" class="form-control" name="search" 
                                   value="<?php echo e($search); ?>" placeholder="Search comments...">
                        </div>
                        <div class="col-md-3">
                            <button type="submit" class="btn btn-primary w-100">
                                <i class="fas fa-search me-1"></i> Search
                            </button>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Comments Table -->
            <div class="card border-0 shadow-sm">
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th style="width: 5%">ID</th>
                                    <th style="width: 15%">User</th>
                                    <th style="width: 25%">Post</th>
                                    <th style="width: 40%">Comment</th>
                                    <th style="width: 10%">Date</th>
                                    <th style="width: 5%">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($comments as $comment): ?>
                                <tr>
                                    <td><?php echo $comment['id']; ?></td>
                                    <td><?php echo e($comment['username']); ?></td>
                                    <td>
                                        <a href="<?php echo SITE_URL; ?>/posts/view_blog.php?id=<?php echo $comment['blog_id']; ?>" target="_blank">
                                            <?php echo e(truncate($comment['post_title'], 35)); ?>
                                        </a>
                                    </td>
                                    <td><?php echo e(truncate($comment['comment'], 80)); ?></td>
                                    <td><small><?php echo timeAgo($comment['created_at']); ?></small></td>
                                    <td>
                                        <button onclick="deleteComment(<?php echo $comment['id']; ?>)" 
                                                class="btn btn-sm btn-outline-danger">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <!-- Pagination -->
            <?php if ($totalPages > 1): ?>
            <nav class="mt-4">
                <ul class="pagination justify-content-center">
                    <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                    <li class="page-item <?php echo $i === $page ? 'active' : ''; ?>">
                        <a class="page-link" href="?page=<?php echo $i; ?>&search=<?php echo urlencode($search); ?>"><?php echo $i; ?></a>
                    </li>
                    <?php endfor; ?>
                </ul>
            </nav>
            <?php endif; ?>
        </div>
    </div>
</div>

<script>
function deleteComment(id) {
    Swal.fire({
        title: 'Delete Comment?',
        text: 'This action cannot be undone!',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#dc3545',
        confirmButtonText: 'Yes, delete it!'
    }).then((result) => {
        if (result.isConfirmed) {
            window.location.href = '?action=delete&id=' + id + '&token=<?php echo generateCSRFToken(); ?>';
        }
    });
}
</script>

<?php include '../includes/footer.php'; ?>