<?php
// Simple admin dashboard template.
// Adjust DB connection logic to match your project's config.

session_start();

// helper to redirect to login
function redirect_login() {
    header('Location: ../auth/login.php');
    exit;
}

// basic admin check (adjust according to your user/session structure)
if (empty($_SESSION['user'])) {
    redirect_login();
}
$isAdmin = false;
if (isset($_SESSION['user']['is_admin'])) {
    $isAdmin = (bool) $_SESSION['user']['is_admin'];
} elseif (isset($_SESSION['user']['role']) && $_SESSION['user']['role'] === 'admin') {
    $isAdmin = true;
}
if (! $isAdmin) {
    redirect_login();
}

// try to reuse an existing DB connection if present, otherwise attempt common config
$conn = null;
if (isset($conn) && $conn) {
    // already available from included files
} else {
    // try to include a project database config (do not commit real credentials)
    $dbCfgPath = __DIR__ . '/../config/database.php';
    if (file_exists($dbCfgPath)) {
        // many projects either return an array or set a $conn variable in this file.
        $maybe = include $dbCfgPath;
        if (is_array($maybe) && isset($maybe['host'])) {
            // create mysqli connection from returned array
            $cfg = $maybe;
            $conn = new mysqli($cfg['host'] ?? 'localhost', $cfg['user'] ?? '', $cfg['pass'] ?? '', $cfg['dbname'] ?? '');
            if ($conn->connect_error) {
                $conn = null;
            }
        } elseif (isset($conn) && $conn instanceof mysqli) {
            // ok
        }
    }
}

// helper to get a single count, returns null on error/not-configured
function get_count($conn, $table) {
    if (! ($conn instanceof mysqli)) return null;
    $table = preg_replace('/[^a-zA-Z0-9_]/', '', $table);
    $sql = "SELECT COUNT(*) AS c FROM `$table`";
    $res = $conn->query($sql);
    if ($res) {
        $row = $res->fetch_assoc();
        return (int) $row['c'];
    }
    return null;
}

// gather stats (will be null if DB not available)
$postsCount = get_count($conn, 'blogPost');
$usersCount = get_count($conn, 'users');
$commentsCount = get_count($conn, 'comments');
$categoriesCount = get_count($conn, 'categories');

?>
<?php include __DIR__ . '/../includes/header.php'; ?>

<div class="container mt-4">
    <h1 class="mb-3">Admin Dashboard</h1>

    <div class="row g-3">
        <div class="col-sm-6 col-md-3">
            <div class="card p-3">
                <h5>Posts</h5>
                <p class="display-6"><?php echo is_null($postsCount) ? '—' : $postsCount; ?></p>
                <a href="posts.php" class="btn btn-sm btn-primary">Manage posts</a>
            </div>
        </div>

        <div class="col-sm-6 col-md-3">
            <div class="card p-3">
                <h5>Users</h5>
                <p class="display-6"><?php echo is_null($usersCount) ? '—' : $usersCount; ?></p>
                <a href="users.php" class="btn btn-sm btn-primary">Manage users</a>
            </div>
        </div>

        <div class="col-sm-6 col-md-3">
            <div class="card p-3">
                <h5>Comments</h5>
                <p class="display-6"><?php echo is_null($commentsCount) ? '—' : $commentsCount; ?></p>
                <a href="comments.php" class="btn btn-sm btn-primary">Manage comments</a>
            </div>
        </div>

        <div class="col-sm-6 col-md-3">
            <div class="card p-3">
                <h5>Categories</h5>
                <p class="display-6"><?php echo is_null($categoriesCount) ? '—' : $categoriesCount; ?></p>
                <a href="categories.php" class="btn btn-sm btn-primary">Manage categories</a>
            </div>
        </div>
    </div>

    <hr class="my-4">

    <div>
        <h4>Quick actions</h4>
        <ul>
            <li><a href="posts.php">Create / edit posts</a></li>
            <li><a href="users.php">Manage users</a></li>
            <li><a href="comments.php">Moderate comments</a></li>
            <li><a href="settings.php">Site settings</a> (create if missing)</li>
        </ul>
    </div>

    <?php if (! ($conn instanceof mysqli)): ?>
        <div class="alert alert-warning mt-3">
            Database connection not detected. Stats are disabled. Ensure your DB config is available to admin pages
            (example: config/database.php or include a mysqli $conn variable in an included helper).
        </div>
    <?php endif; ?>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>