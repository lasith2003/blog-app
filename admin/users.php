<?php
/**
 * ================================================================
 * BLOG HUT - Admin User Management
 * University of Moratuwa - IN2120 Web Programming Project
 * ================================================================
 * 
 * Manage all users:
 * - View all users
 * - Search users
 * - Filter by role
 * - Change user role
 * - Delete users
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
$roleFilter = isset($_GET['role']) ? cleanInput($_GET['role']) : 'all';
$searchTerm = isset($_GET['search']) ? cleanInput($_GET['search']) : '';

// Handle user actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    $userId = isset($_POST['user_id']) ? intval($_POST['user_id']) : 0;
    $csrfToken = $_POST['csrf_token'] ?? '';
    
    if (!verifyCSRFToken($csrfToken)) {
        setFlashMessage('Invalid security token.', 'error');
    } elseif ($userId === getCurrentUserId()) {
        setFlashMessage('You cannot modify your own account.', 'error');
    } else {
        try {
            if ($action === 'toggle_role' && $userId) {
                $user = fetchOne("SELECT role FROM users WHERE id = ?", [$userId]);
                $newRole = $user['role'] === 'admin' ? 'user' : 'admin';
                updateRecord("UPDATE users SET role = ? WHERE id = ?", [$newRole, $userId]);
                setFlashMessage('User role updated successfully.', 'success');
            } elseif ($action === 'delete' && $userId) {
                updateRecord("DELETE FROM users WHERE id = ?", [$userId]);
                setFlashMessage('User deleted successfully.', 'success');
            }
            redirect('/admin/users.php' . ($roleFilter !== 'all' ? '?role=' . $roleFilter : ''));
        } catch (Exception $e) {
            error_log("Admin User Action Error: " . $e->getMessage());
            setFlashMessage('An error occurred.', 'error');
        }
    }
}

try {
    // Build query
    $sql = "SELECT u.*, 
            (SELECT COUNT(*) FROM blogPost WHERE user_id = u.id) as post_count,
            (SELECT COUNT(*) FROM comments WHERE user_id = u.id) as comment_count
            FROM users u
            WHERE 1=1";
    
    $params = [];
    
    // Add role filter
    if ($roleFilter !== 'all') {
        $sql .= " AND u.role = ?";
        $params[] = $roleFilter;
    }
    
    // Add search filter
    if ($searchTerm) {
        $sql .= " AND (u.username LIKE ? OR u.email LIKE ?)";
        $searchParam = "%$searchTerm%";
        $params[] = $searchParam;
        $params[] = $searchParam;
    }
    
    $sql .= " ORDER BY u.created_at DESC";
    
    // Get total count
    $countSql = "SELECT COUNT(*) as total FROM users u WHERE 1=1";
    $countParams = [];
    
    if ($roleFilter !== 'all') {
        $countSql .= " AND u.role = ?";
        $countParams[] = $roleFilter;
    }
    
    if ($searchTerm) {
        $countSql .= " AND (u.username LIKE ? OR u.email LIKE ?)";
        $countParams[] = $searchParam;
        $countParams[] = $searchParam;
    }
    
    $totalUsers = fetchOne($countSql, $countParams)['total'];
    $totalPages = ceil($totalUsers / ADMIN_USERS_PER_PAGE);
    
    // Add pagination
    $offset = ($currentPage - 1) * ADMIN_USERS_PER_PAGE;
    $sql .= " LIMIT ? OFFSET ?";
    $params[] = ADMIN_USERS_PER_PAGE;
    $params[] = $offset;
    
    $users = fetchAll($sql, $params);
    
    // Get role counts
    $adminCount = fetchOne("SELECT COUNT(*) as count FROM users WHERE role = 'admin'")['count'];
    $userCount = fetchOne("SELECT COUNT(*) as count FROM users WHERE role = 'user'")['count'];
    
} catch (Exception $e) {
    error_log("Admin Users Error: " . $e->getMessage());
    $users = [];
    $totalUsers = 0;
    $totalPages = 0;
}

$pageTitle = 'Manage Users - Admin - ' . SITE_NAME;
$customCSS = '<link rel="stylesheet" href="' . CSS_URL . '/admin.css">';
?>
<?php include '../includes/header.php'; ?>

<div class="container-fluid my-4">
    <!-- Header -->
    <div class="row mb-4">
        <div class="col-md-6">
            <h1 class="fw-bold mb-2">
                <i class="fas fa-users text-primary me-2"></i>
                Manage Users
            </h1>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="<?php echo SITE_URL; ?>/admin/dashboard.php">Dashboard</a></li>
                    <li class="breadcrumb-item active">Users</li>
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
                    <label class="form-label">Search Users</label>
                    <input type="text" class="form-control" name="search" 
                           value="<?php echo e($searchTerm); ?>" 
                           placeholder="Search by username or email...">
                </div>
                <div class="col-md-3">
                    <label class="form-label">Filter by Role</label>
                    <select class="form-select" name="role">
                        <option value="all" <?php echo $roleFilter === 'all' ? 'selected' : ''; ?>>
                            All Roles (<?php echo $adminCount + $userCount; ?>)
                        </option>
                        <option value="admin" <?php echo $roleFilter === 'admin' ? 'selected' : ''; ?>>
                            Admins (<?php echo $adminCount; ?>)
                        </option>
                        <option value="user" <?php echo $roleFilter === 'user' ? 'selected' : ''; ?>>
                            Users (<?php echo $userCount; ?>)
                        </option>
                    </select>
                </div>
                <div class="col-md-2 d-flex align-items-end">
                    <button type="submit" class="btn btn-primary w-100">
                        <i class="fas fa-search me-1"></i> Filter
                    </button>
                </div>
                <?php if ($searchTerm || $roleFilter !== 'all'): ?>
                <div class="col-md-2 d-flex align-items-end">
                    <a href="?" class="btn btn-secondary w-100">
                        <i class="fas fa-times me-1"></i> Clear
                    </a>
                </div>
                <?php endif; ?>
            </form>
        </div>
    </div>
    
    <!-- Users Table -->
    <div class="card shadow-sm">
        <div class="card-header bg-white">
            <h5 class="mb-0">
                Users List (<?php echo number_format($totalUsers); ?>)
            </h5>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>User</th>
                            <th>Email</th>
                            <th>Role</th>
                            <th>Posts</th>
                            <th>Comments</th>
                            <th>Joined</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (!empty($users)): ?>
                            <?php foreach ($users as $user): ?>
                            <tr>
                                <td>
                                    <div class="d-flex align-items-center">
                                        <img src="<?php echo AVATAR_URL . '/' . ($user['profile_image'] ?? DEFAULT_AVATAR); ?>" 
                                             class="rounded-circle me-2"
                                             style="width: 40px; height: 40px; object-fit: cover;">
                                        <div>
                                            <strong><?php echo e($user['username']); ?></strong>
                                            <?php if ($user['id'] === getCurrentUserId()): ?>
                                            <span class="badge bg-info ms-1">You</span>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </td>
                                <td><?php echo e($user['email']); ?></td>
                                <td>
                                    <?php if ($user['role'] === 'admin'): ?>
                                    <span class="badge bg-danger">Admin</span>
                                    <?php else: ?>
                                    <span class="badge bg-secondary">User</span>
                                    <?php endif; ?>
                                </td>
                                <td><?php echo number_format($user['post_count']); ?></td>
                                <td><?php echo number_format($user['comment_count']); ?></td>
                                <td class="small"><?php echo formatDate($user['created_at'], SHORT_DATE_FORMAT); ?></td>
                                <td>
                                    <?php if ($user['id'] !== getCurrentUserId()): ?>
                                    <div class="btn-group btn-group-sm">
                                        <a href="<?php echo SITE_URL; ?>/profile/user_profile.php?id=<?php echo $user['id']; ?>" 
                                           class="btn btn-outline-primary" title="View Profile">
                                            <i class="fas fa-eye"></i>
                                        </a>
                                        <button type="button" class="btn btn-outline-warning" 
                                                onclick="toggleRole(<?php echo $user['id']; ?>, '<?php echo $user['role']; ?>')"
                                                title="Toggle Role">
                                            <i class="fas fa-user-shield"></i>
                                        </button>
                                        <button type="button" class="btn btn-outline-danger" 
                                                onclick="deleteUser(<?php echo $user['id']; ?>, '<?php echo e($user['username']); ?>')"
                                                title="Delete User">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    </div>
                                    <?php else: ?>
                                    <span class="text-muted small">Current User</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr><td colspan="7" class="text-center text-muted py-4">No users found</td></tr>
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
                        <a class="page-link" href="?page=<?php echo $currentPage - 1; ?><?php echo $roleFilter !== 'all' ? '&role=' . $roleFilter : ''; ?><?php echo $searchTerm ? '&search=' . urlencode($searchTerm) : ''; ?>">
                            Previous
                        </a>
                    </li>
                    <?php for ($i = max(1, $currentPage - 2); $i <= min($totalPages, $currentPage + 2); $i++): ?>
                    <li class="page-item <?php echo $i === $currentPage ? 'active' : ''; ?>">
                        <a class="page-link" href="?page=<?php echo $i; ?><?php echo $roleFilter !== 'all' ? '&role=' . $roleFilter : ''; ?><?php echo $searchTerm ? '&search=' . urlencode($searchTerm) : ''; ?>">
                            <?php echo $i; ?>
                        </a>
                    </li>
                    <?php endfor; ?>
                    <li class="page-item <?php echo $currentPage >= $totalPages ? 'disabled' : ''; ?>">
                        <a class="page-link" href="?page=<?php echo $currentPage + 1; ?><?php echo $roleFilter !== 'all' ? '&role=' . $roleFilter : ''; ?><?php echo $searchTerm ? '&search=' . urlencode($searchTerm) : ''; ?>">
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
<form id="toggleRoleForm" method="POST" style="display: none;">
    <?php echo csrfField(); ?>
    <input type="hidden" name="action" value="toggle_role">
    <input type="hidden" name="user_id" id="toggleRoleUserId">
</form>

<form id="deleteUserForm" method="POST" style="display: none;">
    <?php echo csrfField(); ?>
    <input type="hidden" name="action" value="delete">
    <input type="hidden" name="user_id" id="deleteUserId">
</form>

<script>
function toggleRole(userId, currentRole) {
    const newRole = currentRole === 'admin' ? 'User' : 'Admin';
    
    Swal.fire({
        title: 'Change User Role?',
        text: `Change this user's role to ${newRole}?`,
        icon: 'question',
        showCancelButton: true,
        confirmButtonColor: '#ffc107',
        confirmButtonText: 'Yes, change it!'
    }).then((result) => {
        if (result.isConfirmed) {
            document.getElementById('toggleRoleUserId').value = userId;
            document.getElementById('toggleRoleForm').submit();
        }
    });
}

function deleteUser(userId, username) {
    Swal.fire({
        title: 'Delete User?',
        html: `Are you sure you want to delete <strong>${username}</strong>?<br>This will also delete all their posts and comments!`,
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#dc3545',
        confirmButtonText: 'Yes, delete user!'
    }).then((result) => {
        if (result.isConfirmed) {
            document.getElementById('deleteUserId').value = userId;
            document.getElementById('deleteUserForm').submit();
        }
    });
}
</script>

<?php include '../includes/footer.php'; ?>