<?php
if (!defined('DB_ACCESS')) {
    header('Location: ../../index.php');
    exit;
}
?>
<div class="admin-sidebar">
    <ul class="nav flex-column">
        <li class="nav-item">
            <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) === 'dashboard.php' ? 'active' : ''; ?>" href="../admin/dashboard.php">
                <i class="fas fa-tachometer-alt"></i> Dashboard
            </a>
        </li>
        <li class="nav-item">
            <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) === 'posts.php' ? 'active' : ''; ?>" href="../admin/posts.php">
                <i class="fas fa-file-alt"></i> Posts
            </a>
        </li>
        <li class="nav-item">
            <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) === 'categories.php' ? 'active' : ''; ?>" href="../admin/categories.php">
                <i class="fas fa-folder"></i> Categories
            </a>
        </li>
        <li class="nav-item">
            <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) === 'comments.php' ? 'active' : ''; ?>" href="../admin/comments.php">
                <i class="fas fa-comments"></i> Comments
            </a>
        </li>
        <li class="nav-item">
            <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) === 'users.php' ? 'active' : ''; ?>" href="../admin/users.php">
                <i class="fas fa-users"></i> Users
            </a>
        </li>
    </ul>
</div>