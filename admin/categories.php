<?php
/**
 * ================================================================
 * BLOG HUT - Admin Category Management
 * University of Moratuwa - IN2120 Web Programming Project
 * ================================================================
 * 
 * Manage blog categories:
 * - View all categories
 * - Add new category
 * - Edit category
 * - Delete category
 * - View post counts
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

// Handle category actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    $csrfToken = $_POST['csrf_token'] ?? '';
    
    if (!verifyCSRFToken($csrfToken)) {
        setFlashMessage('Invalid security token.', 'error');
    } else {
        try {
            if ($action === 'add') {
                $name = cleanInput($_POST['name'] ?? '');
                $slug = slugify($name);
                $description = cleanInput($_POST['description'] ?? '');
                
                if (isEmpty($name)) {
                    setFlashMessage('Category name is required.', 'error');
                } else {
                    // Check if slug exists
                    $exists = fetchOne("SELECT id FROM categories WHERE slug = ?", [$slug]);
                    if ($exists) {
                        setFlashMessage('A category with this name already exists.', 'error');
                    } else {
                        insertRecord(
                            "INSERT INTO categories (name, slug, description) VALUES (?, ?, ?)",
                            [$name, $slug, $description]
                        );
                        setFlashMessage('Category added successfully!', 'success');
                    }
                }
            } elseif ($action === 'edit') {
                $id = intval($_POST['category_id']);
                $name = cleanInput($_POST['name'] ?? '');
                $slug = slugify($name);
                $description = cleanInput($_POST['description'] ?? '');
                
                if (isEmpty($name)) {
                    setFlashMessage('Category name is required.', 'error');
                } else {
                    // Check if slug exists (excluding current category)
                    $exists = fetchOne("SELECT id FROM categories WHERE slug = ? AND id != ?", [$slug, $id]);
                    if ($exists) {
                        setFlashMessage('A category with this name already exists.', 'error');
                    } else {
                        updateRecord(
                            "UPDATE categories SET name = ?, slug = ?, description = ? WHERE id = ?",
                            [$name, $slug, $description, $id]
                        );
                        setFlashMessage('Category updated successfully!', 'success');
                    }
                }
            } elseif ($action === 'delete') {
                $id = intval($_POST['category_id']);
                
                // Check if category has posts
                $postCount = fetchOne(
                    "SELECT COUNT(*) as count FROM blogPost WHERE category = ?",
                    [$id]
                )['count'];
                
                if ($postCount > 0) {
                    setFlashMessage("Cannot delete category with $postCount posts. Reassign posts first.", 'error');
                } else {
                    updateRecord("DELETE FROM categories WHERE id = ?", [$id]);
                    setFlashMessage('Category deleted successfully!', 'success');
                }
            }
        } catch (Exception $e) {
            error_log("Category Action Error: " . $e->getMessage());
            setFlashMessage('An error occurred.', 'error');
        }
    }
    redirect('/admin/categories.php');
}

try {
    // Get all categories with post counts
    $categories = fetchAll("
        SELECT c.*, COUNT(bp.id) as post_count
        FROM categories c
        LEFT JOIN blogPost bp ON c.id = bp.category
        GROUP BY c.id
        ORDER BY c.name ASC
    ");
    
} catch (Exception $e) {
    error_log("Admin Categories Error: " . $e->getMessage());
    $categories = [];
}

$pageTitle = 'Manage Categories - Admin - ' . SITE_NAME;
$customCSS = '<link rel="stylesheet" href="' . CSS_URL . '/admin.css">';
?>
<?php include '../includes/header.php'; ?>

<div class="container-fluid my-4">
    <!-- Header -->
    <div class="row mb-4">
        <div class="col-md-6">
            <h1 class="fw-bold mb-2">
                <i class="fas fa-tags text-primary me-2"></i>
                Manage Categories
            </h1>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="<?php echo SITE_URL; ?>/admin/dashboard.php">Dashboard</a></li>
                    <li class="breadcrumb-item active">Categories</li>
                </ol>
            </nav>
        </div>
        <div class="col-md-6 text-md-end">
            <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addCategoryModal">
                <i class="fas fa-plus me-2"></i> Add New Category
            </button>
            <a href="<?php echo SITE_URL; ?>/admin/dashboard.php" class="btn btn-outline-secondary">
                <i class="fas fa-arrow-left me-2"></i> Back
            </a>
        </div>
    </div>
    
    <!-- Categories Grid -->
    <div class="row g-4">
        <?php if (!empty($categories)): ?>
            <?php foreach ($categories as $category): ?>
            <div class="col-md-6 col-lg-4">
                <div class="card shadow-sm h-100">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-start mb-3">
                            <div>
                                <h5 class="mb-1">
                                    <i class="fas fa-tag text-primary me-2"></i>
                                    <?php echo e($category['name']); ?>
                                </h5>
                                <small class="text-muted">Slug: <?php echo e($category['slug']); ?></small>
                            </div>
                            <span class="badge bg-primary fs-6">
                                <?php echo $category['post_count']; ?> posts
                            </span>
                        </div>
                        
                        <?php if ($category['description']): ?>
                        <p class="text-muted small mb-3">
                            <?php echo e($category['description']); ?>
                        </p>
                        <?php else: ?>
                        <p class="text-muted small fst-italic mb-3">No description</p>
                        <?php endif; ?>
                        
                        <div class="d-flex gap-2">
                            <button type="button" class="btn btn-sm btn-outline-primary flex-fill" 
                                    onclick='editCategory(<?php echo json_encode($category); ?>)'>
                                <i class="fas fa-edit me-1"></i> Edit
                            </button>
                            <button type="button" class="btn btn-sm btn-outline-danger" 
                                    onclick="deleteCategory(<?php echo $category['id']; ?>, '<?php echo e($category['name']); ?>', <?php echo $category['post_count']; ?>)">
                                <i class="fas fa-trash"></i>
                            </button>
                        </div>
                    </div>
                    <div class="card-footer bg-light small text-muted">
                        <i class="fas fa-calendar me-1"></i>
                        Created <?php echo formatDate($category['created_at'], SHORT_DATE_FORMAT); ?>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        <?php else: ?>
            <div class="col-12">
                <div class="text-center py-5">
                    <i class="fas fa-tags fa-4x text-muted mb-3"></i>
                    <h4 class="text-muted">No categories yet</h4>
                    <p class="text-muted">Click "Add New Category" to create your first category</p>
                </div>
            </div>
        <?php endif; ?>
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
                    <h5 class="modal-title">
                        <i class="fas fa-plus-circle me-2"></i>
                        Add New Category
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="addName" class="form-label">
                            Category Name <span class="text-danger">*</span>
                        </label>
                        <input type="text" class="form-control" id="addName" name="name" 
                               required maxlength="50" placeholder="e.g., Technology">
                        <small class="form-text text-muted">
                            The slug will be generated automatically
                        </small>
                    </div>
                    
                    <div class="mb-3">
                        <label for="addDescription" class="form-label">Description</label>
                        <textarea class="form-control" id="addDescription" name="description" 
                                  rows="3" placeholder="Brief description of this category (optional)"></textarea>
                    </div>
                </div>
                
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save me-2"></i> Add Category
                    </button>
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
                <input type="hidden" name="category_id" id="editId">
                
                <div class="modal-header">
                    <h5 class="modal-title">
                        <i class="fas fa-edit me-2"></i>
                        Edit Category
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="editName" class="form-label">
                            Category Name <span class="text-danger">*</span>
                        </label>
                        <input type="text" class="form-control" id="editName" name="name" 
                               required maxlength="50">
                    </div>
                    
                    <div class="mb-3">
                        <label for="editDescription" class="form-label">Description</label>
                        <textarea class="form-control" id="editDescription" name="description" rows="3"></textarea>
                    </div>
                </div>
                
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save me-2"></i> Update Category
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Delete Form -->
<form id="deleteCategoryForm" method="POST" style="display: none;">
    <?php echo csrfField(); ?>
    <input type="hidden" name="action" value="delete">
    <input type="hidden" name="category_id" id="deleteCategoryId">
</form>

<script>
function editCategory(category) {
    document.getElementById('editId').value = category.id;
    document.getElementById('editName').value = category.name;
    document.getElementById('editDescription').value = category.description || '';
    
    const modal = new bootstrap.Modal(document.getElementById('editCategoryModal'));
    modal.show();
}

function deleteCategory(id, name, postCount) {
    if (postCount > 0) {
        Swal.fire({
            icon: 'error',
            title: 'Cannot Delete',
            text: `This category has ${postCount} post(s). Please reassign or delete those posts first.`,
            confirmButtonColor: '#FFB100'
        });
        return;
    }
    
    Swal.fire({
        title: 'Delete Category?',
        html: `Are you sure you want to delete <strong>${name}</strong>?`,
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#dc3545',
        confirmButtonText: 'Yes, delete it!'
    }).then((result) => {
        if (result.isConfirmed) {
            document.getElementById('deleteCategoryId').value = id;
            document.getElementById('deleteCategoryForm').submit();
        }
    });
}
</script>

<?php include '../includes/footer.php'; ?>