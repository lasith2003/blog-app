
<?php
session_start();
require_once '../config/database.php';
require_once '../config/constants.php';
require_once '../includes/helper.php';

requireAdmin();

// Handle actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    $token = $_POST['csrf_token'] ?? '';
    
    if (!verifyCSRFToken($token)) {
        setFlashMessage('Invalid security token', 'error');
        redirect('admin/categories.php');
    }
    
    if ($action === 'add') {
        $name = cleanInput($_POST['name']);
        $slug = slugify($name);
        $description = cleanInput($_POST['description'] ?? '');
        
        try {
            executeQuery("INSERT INTO categories (name, slug, description) VALUES (?, ?, ?)", 
                        [$name, $slug, $description]);
            setFlashMessage('Category added successfully!', 'success');
        } catch (Exception $e) {
            setFlashMessage('Category name or slug already exists.', 'error');
        }
    }
    
    if ($action === 'edit') {
        $id = intval($_POST['id']);
        $name = cleanInput($_POST['name']);
        $slug = slugify($name);
        $description = cleanInput($_POST['description'] ?? '');
        
        try {
            executeQuery("UPDATE categories SET name = ?, slug = ?, description = ? WHERE id = ?", 
                        [$name, $slug, $description, $id]);
            setFlashMessage('Category updated successfully!', 'success');
        } catch (Exception $e) {
            setFlashMessage('Category name or slug already exists.', 'error');
        }
    }
    
    redirect('admin/categories.php');
}

// Handle delete
if (isset($_GET['action']) && $_GET['action'] === 'delete') {
    $id = intval($_GET['id']);
    $token = $_GET['token'] ?? '';
    
    if (verifyCSRFToken($token)) {
        executeQuery("DELETE FROM categories WHERE id = ?", [$id]);
        setFlashMessage('Category deleted successfully!', 'success');
    }
    redirect('admin/categories.php');
}

// Get categories
$categories = fetchAll("
    SELECT c.*, 
    (SELECT COUNT(*) FROM blogPost WHERE category = c.id) as post_count
    FROM categories c
    ORDER BY c.name ASC
");

$pageTitle = 'Manage Categories - Admin - ' . SITE_NAME;
?>
<?php include '../includes/header.php'; ?>

<div class="container-fluid">
    <div class="row">
        <!-- Sidebar -->
        <div class="col-md-3 col-lg-2 d-md-block bg-light sidebar">
            <?php require_once __DIR__ . '/includes/sidebar.php'; ?>
        </div>

        <div class="col-md-9 col-lg-10">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h2><i class="fas fa-tags me-2"></i>Manage Categories</h2>
                <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addCategoryModal">
                    <i class="fas fa-plus me-2"></i> Add Category
                </button>
            </div>

            <?php echo displayFlashMessage(); ?>

            <!-- Categories Table -->
            <div class="card border-0 shadow-sm">
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th style="width: 5%">ID</th>
                                    <th style="width: 20%">Name</th>
                                    <th style="width: 20%">Slug</th>
                                    <th style="width: 40%">Description</th>
                                    <th style="width: 10%">Posts</th>
                                    <th style="width: 15%">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($categories as $cat): ?>
                                <tr>
                                    <td><?php echo $cat['id']; ?></td>
                                    <td><strong><?php echo e($cat['name']); ?></strong></td>
                                    <td><code><?php echo e($cat['slug']); ?></code></td>
                                    <td><?php echo e(truncate($cat['description'] ?? '', 80)); ?></td>
                                    <td><span class="badge bg-primary"><?php echo $cat['post_count']; ?></span></td>
                                    <td>
                                        <button class="btn btn-sm btn-outline-primary" 
                                                onclick="editCategory(<?php echo htmlspecialchars(json_encode($cat)); ?>)">
                                            <i class="fas fa-edit"></i> Edit
                                        </button>
                                        <button class="btn btn-sm btn-outline-danger" 
                                                onclick="deleteCategory(<?php echo $cat['id']; ?>, <?php echo $cat['post_count']; ?>)">
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
        </div>
    </div>
</div>

<!-- Add Category Modal -->
<div class="modal fade" id="addCategoryModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST">
                <?php echo csrfField(); ?>
                <input type="hidden" name="action" value="add">
                <div class="modal-header">
                    <h5 class="modal-title">Add New Category</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Category Name *</label>
                        <input type="text" class="form-control" name="name" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Description</label>
                        <textarea class="form-control" name="description" rows="3"></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Add Category</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Edit Category Modal -->
<div class="modal fade" id="editCategoryModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST">
                <?php echo csrfField(); ?>
                <input type="hidden" name="action" value="edit">
                <input type="hidden" name="id" id="edit_id">
                <div class="modal-header">
                    <h5 class="modal-title">Edit Category</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Category Name *</label>
                        <input type="text" class="form-control" name="name" id="edit_name" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Description</label>
                        <textarea class="form-control" name="description" id="edit_description" rows="3"></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Update Category</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
function editCategory(cat) {
    document.getElementById('edit_id').value = cat.id;
    document.getElementById('edit_name').value = cat.name;
    document.getElementById('edit_description').value = cat.description || '';
    new bootstrap.Modal(document.getElementById('editCategoryModal')).show();
}

function deleteCategory(id, postCount) {
    if (postCount > 0) {
        Swal.fire({
            title: 'Cannot Delete',
            text: `This category has ${postCount} post(s). Please reassign or delete those posts first.`,
            icon: 'warning'
        });
        return;
    }
    
    Swal.fire({
        title: 'Delete Category?',
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
