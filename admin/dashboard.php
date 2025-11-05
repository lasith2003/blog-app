<?php
/**
 * ================================================================
 * FILE 1: admin/users.php - Manage Users
 * ================================================================
 */
?>
<?php
session_start();
require_once '../config/database.php';
require_once '../config/constants.php';
require_once '../includes/helper.php';

requireAdmin();

// Handle actions
if (isset($_GET['action'])) {
    $userId = intval($_GET['id'] ?? 0);
    $token = $_GET['token'] ?? '';
    
    if (!verifyCSRFToken($token)) {
        setFlashMessage('Invalid security token', 'error');
        redirect('admin/users.php');
    }
    
    switch ($_GET['action']) {
        case 'toggle_role':
            $user = fetchOne("SELECT role FROM users WHERE id = ?", [$userId]);
            $newRole = $user['role'] === 'admin' ? 'user' : 'admin';
            executeQuery("UPDATE users SET role = ? WHERE id = ?", [$newRole, $userId]);
            setFlashMessage('User role updated!', 'success');
            break;
            
        case 'delete':
            // Don't allow deleting current admin
            if ($userId !== getCurrentUserId()) {
                executeQuery("DELETE FROM users WHERE id = ?", [$userId]);
                setFlashMessage('User deleted successfully!', 'success');
            } else {
                setFlashMessage('Cannot delete your own account!', 'error');
            }
            break;
    }
    redirect('admin/users.php');
}

// Get users
$search = $_GET['search'] ?? '';
$role = $_GET['role'] ?? 'all';
$page = max(1, intval($_GET['page'] ?? 1));
$perPage = 20;
$offset = ($page - 1) * $perPage;

$sql = "SELECT u.*, 
        (SELECT COUNT(*) FROM blogPost WHERE user_id = u.id) as post_count,
        (SELECT COUNT(*) FROM comments WHERE user_id = u.id) as comment_count
        FROM users u WHERE 1=1";
$params = [];

if ($role !== 'all') {
    $sql .= " AND u.role = ?";
    $params[] = $role;
}

if ($search) {
    $sql .= " AND (u.username LIKE ? OR u.email LIKE ?)";
    $params[] = "%$search%";
    $params[] = "%$search%";
}

$countSql = str_replace("SELECT u.*, (SELECT COUNT(*) FROM blogPost WHERE user_id = u.id) as post_count, (SELECT COUNT(*) FROM comments WHERE user_id = u.id) as comment_count", "SELECT COUNT(*)", $sql);
$total = fetchOne($countSql, $params)['COUNT(*)'] ?? 0;
$totalPages = ceil($total / $perPage);

$sql .= " ORDER BY u.created_at DESC LIMIT ? OFFSET ?";
$params[] = $perPage;
$params[] = $offset;

$users = fetchAll($sql, $params);

$pageTitle = 'Manage Users - Admin - ' . SITE_NAME;
?>
<?php include '../includes/header.php'; ?>

<div class="container-fluid">
    <div class="row">
        <!-- Sidebar -->
        <div class="col-md-3 col-lg-2 d-md-block bg-light sidebar">
            <?php include __DIR__ . '/includes/sidebar.php'; ?>
        </div>

        <div class="col-md-9 col-lg-10">
            <h2 class="mb-4"><i class="fas fa-users me-2"></i>Manage Users</h2>

            <?php echo displayFlashMessage(); ?>

            <!-- Filters -->
            <div class="card border-0 shadow-sm mb-4">
                <div class="card-body">
                    <form method="GET" class="row g-3">
                        <div class="col-md-5">
                            <input type="text" class="form-control" name="search" 
                                   value="<?php echo e($search); ?>" placeholder="Search users...">
                        </div>
                        <div class="col-md-3">
                            <select class="form-select" name="role">
                                <option value="all" <?php echo $role === 'all' ? 'selected' : ''; ?>>All Roles</option>
                                <option value="user" <?php echo $role === 'user' ? 'selected' : ''; ?>>Users</option>
                                <option value="admin" <?php echo $role === 'admin' ? 'selected' : ''; ?>>Admins</option>
                            </select>
                        </div>
                        <div class="col-md-2">
                            <button type="submit" class="btn btn-primary w-100">
                                <i class="fas fa-filter me-1"></i> Filter
                            </button>
                        </div>
                        <div class="col-md-2 text-end">
                            <span class="text-muted">Total: <?php echo number_format($total); ?></span>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Users Table -->
            <div class="card border-0 shadow-sm">
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th>ID</th>
                                    <th>Username</th>
                                    <th>Email</th>
                                    <th>Role</th>
                                    <th>Posts</th>
                                    <th>Comments</th>
                                    <th>Joined</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($users as $user): ?>
                                <tr>
                                    <td><?php echo $user['id']; ?></td>
                                    <td><?php echo e($user['username']); ?></td>
                                    <td><?php echo e($user['email']); ?></td>
                                    <td>
                                        <?php if ($user['role'] === 'admin'): ?>
                                            <span class="badge bg-danger">Admin</span>
                                        <?php else: ?>
                                            <span class="badge bg-secondary">User</span>
                                        <?php endif; ?>
                                    </td>
                                    <td><?php echo $user['post_count']; ?></td>
                                    <td><?php echo $user['comment_count']; ?></td>
                                    <td><small><?php echo formatDate($user['created_at'], 'M d, Y'); ?></small></td>
                                    <td>
                                        <div class="btn-group btn-group-sm">
                                            <a href="<?php echo SITE_URL; ?>/profile/user_profile.php?id=<?php echo $user['id']; ?>" 
                                               class="btn btn-outline-info" title="View Profile" target="_blank">
                                                <i class="fas fa-eye"></i>
                                            </a>
                                            <?php if ($user['id'] !== getCurrentUserId()): ?>
                                            <button onclick="toggleRole(<?php echo $user['id']; ?>, '<?php echo $user['role']; ?>')" 
                                                    class="btn btn-outline-warning" title="Toggle Role">
                                                <i class="fas fa-user-shield"></i>
                                            </button>
                                            <button onclick="deleteUser(<?php echo $user['id']; ?>)" 
                                                    class="btn btn-outline-danger" title="Delete">
                                                <i class="fas fa-trash"></i>
                                            </button>
                                            <?php endif; ?>
                                        </div>
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
                        <a class="page-link" href="?page=<?php echo $i; ?>&role=<?php echo $role; ?>&search=<?php echo urlencode($search); ?>"><?php echo $i; ?></a>
                    </li>
                    <?php endfor; ?>
                </ul>
            </nav>
            <?php endif; ?>
        </div>
    </div>
</div>

<script>
function toggleRole(userId, currentRole) {
    const newRole = currentRole === 'admin' ? 'user' : 'admin';
    Swal.fire({
        title: `Make ${newRole.charAt(0).toUpperCase() + newRole.slice(1)}?`,
        text: `This will change the user's role to ${newRole}.`,
        icon: 'question',
        showCancelButton: true,
        confirmButtonColor: '#FFB100',
        confirmButtonText: 'Yes, change it!'
    }).then((result) => {
        if (result.isConfirmed) {
            window.location.href = '?action=toggle_role&id=' + userId + '&token=<?php echo generateCSRFToken(); ?>';
        }
    });
}

function deleteUser(userId) {
    Swal.fire({
        title: 'Delete User?',
        text: 'This will delete all their posts and comments!',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#dc3545',
        confirmButtonText: 'Yes, delete!'
    }).then((result) => {
        if (result.isConfirmed) {
            window.location.href = '?action=delete&id=' + userId + '&token=<?php echo generateCSRFToken(); ?>';
        }
    });
}
</script>

<?php include '../includes/footer.php'; ?>

