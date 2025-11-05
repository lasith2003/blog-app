<?php
/**
 * BLOG HUT - Edit Blog Post
 * This page allows users to edit their existing blog posts with:
 * - Pre-filled form with existing data
 * - Change title, content, category, image
 * - Update summary and status
 * - Permission check (owner or admin only)
 * - Image replacement option
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

// Get post ID
$postId = isset($_GET['id']) ? intval($_GET['id']) : 0;

if (!$postId) {
    setFlashMessage('Invalid post ID.', 'error');
    redirect(SITE_URL . '/posts/home.php');
}

// Fetch post details
$post = getPostById($postId);

if (!$post) {
    setFlashMessage('Post not found.', 'error');
    redirect(SITE_URL . '/posts/home.php');
}

// Check if user is owner or admin
if (getCurrentUserId() != $post['user_id'] && !isUserAdmin()) {
    setFlashMessage('You do not have permission to edit this post.', 'error');
    redirect(SITE_URL . '/posts/view_blog.php?id=' . $postId);
}

// Get all categories for dropdown
$categories = getAllCategories();

// Initialize variables with existing data
$errors = [];
$title = $post['title'];
$content = $post['content'];
$summary = $post['summary'];
$categoryId = $post['category'];
$status = $post['status'];
$currentImage = $post['featured_image'];

// Process form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Get form data
    $title = cleanInput($_POST['title'] ?? '');
    $content = $_POST['content'] ?? ''; // Don't clean HTML content
    $summary = cleanInput($_POST['summary'] ?? '');
    $categoryId = isset($_POST['category']) ? intval($_POST['category']) : null;
    $status = isset($_POST['status']) ? cleanInput($_POST['status']) : 'published';
    $removeImage = isset($_POST['remove_image']);
    $csrfToken = $_POST['csrf_token'] ?? '';
    
    // Validate CSRF token
    if (!verifyCSRFToken($csrfToken)) {
        $errors[] = 'Invalid security token. Please try again.';
    }
    
    // Validate title
    if (isEmpty($title)) {
        $errors[] = 'Title is required.';
    } elseif (strlen($title) < MIN_TITLE_LENGTH) {
        $errors[] = 'Title must be at least ' . MIN_TITLE_LENGTH . ' characters.';
    } elseif (strlen($title) > MAX_TITLE_LENGTH) {
        $errors[] = 'Title must not exceed ' . MAX_TITLE_LENGTH . ' characters.';
    }
    
    // Validate content
    if (isEmpty(strip_tags($content))) {
        $errors[] = 'Content is required.';
    } elseif (strlen(strip_tags($content)) < MIN_CONTENT_LENGTH) {
        $errors[] = 'Content must be at least ' . MIN_CONTENT_LENGTH . ' characters.';
    } elseif (strlen($content) > MAX_CONTENT_LENGTH) {
        $errors[] = 'Content is too long. Maximum ' . MAX_CONTENT_LENGTH . ' characters.';
    }
    
    // Validate summary
    if (!isEmpty($summary) && strlen($summary) > MAX_SUMMARY_LENGTH) {
        $errors[] = 'Summary must not exceed ' . MAX_SUMMARY_LENGTH . ' characters.';
    }
    
    // Validate status
    if (!in_array($status, ['draft', 'published'])) {
        $errors[] = 'Invalid post status.';
    }
    
    // Handle image removal
    if ($removeImage && $currentImage) {
        $imagePath = POST_IMAGE_PATH . '/' . $currentImage;
        if (deleteFile($imagePath)) {
            $currentImage = null;
        }
    }
    
    // Handle new image upload
    $featuredImage = $currentImage;
    if (isset($_FILES['featured_image']) && $_FILES['featured_image']['error'] !== UPLOAD_ERR_NO_FILE) {
        // Delete old image if exists
        if ($currentImage) {
            deleteFile(POST_IMAGE_PATH . '/' . $currentImage);
        }
        
        $uploadResult = handleFileUpload(
            $_FILES['featured_image'],
            POST_IMAGE_PATH,
            ALLOWED_POST_IMAGE_TYPES,
            MAX_POST_IMAGE_SIZE
        );
        
        if ($uploadResult['success']) {
            $featuredImage = $uploadResult['filename'];
        } else {
            $errors[] = $uploadResult['error'];
        }
    }
    
    // If no errors, update post
    if (empty($errors)) {
        try {
            $postData = [
                'title' => $title,
                'content' => $content,
                'summary' => $summary,
                'category' => $categoryId,
                'featured_image' => $featuredImage,
                'status' => $status
            ];
            
            if (updatePost($postId, $postData)) {
                setFlashMessage('Blog post updated successfully!', 'success');
                redirect(SITE_URL . '/posts/view_blog.php?id=' . $postId);
            } else {
                $errors[] = 'Failed to update post. Please try again.';
            }
            
        } catch (Exception $e) {
            error_log("Update Post Error: " . $e->getMessage());
            $errors[] = 'An error occurred while updating the post.';
        }
    }
}

// Set page title
$pageTitle = 'Edit Post - ' . SITE_NAME;
?>
<?php include '../includes/header.php'; ?>

<div class="container my-5">
    <div class="row justify-content-center">
        <div class="col-lg-10">
            <!-- Page Header -->
            <div class="text-center mb-5" data-aos="fade-down">
                <h1 class="fw-bold mb-2">
                    <i class="fas fa-edit text-primary me-2"></i>
                    Edit Blog Post
                </h1>
                <p class="text-muted">Update your story</p>
            </div>
            
            <!-- Display Errors -->
            <?php if (!empty($errors)): ?>
            <div class="alert alert-danger alert-dismissible fade show" role="alert" data-aos="fade-up">
                <i class="fas fa-exclamation-circle me-2"></i>
                <strong>Please fix the following errors:</strong>
                <ul class="mb-0 mt-2">
                    <?php foreach ($errors as $error): ?>
                        <li><?php echo e($error); ?></li>
                    <?php endforeach; ?>
                </ul>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
            <?php endif; ?>
            
            <!-- Edit Post Form -->
            <form method="POST" action="" enctype="multipart/form-data" id="editPostForm" data-aos="fade-up">
                <?php echo csrfField(); ?>
                
                <div class="card shadow-sm mb-4">
                    <div class="card-body">
                        <!-- Title Field -->
                        <div class="mb-4">
                            <label for="title" class="form-label fw-bold">
                                <i class="fas fa-heading me-1"></i> Title
                                <span class="text-danger">*</span>
                            </label>
                            <input type="text" 
                                   class="form-control form-control-lg" 
                                   id="title" 
                                   name="title" 
                                   value="<?php echo e($title); ?>"
                                   placeholder="Enter your blog post title..."
                                   required
                                   minlength="<?php echo MIN_TITLE_LENGTH; ?>"
                                   maxlength="<?php echo MAX_TITLE_LENGTH; ?>">
                            <small class="form-text text-muted">
                                <?php echo MIN_TITLE_LENGTH; ?>-<?php echo MAX_TITLE_LENGTH; ?> characters
                            </small>
                        </div>
                        
                        <!-- Category Field -->
                        <div class="mb-4">
                            <label for="category" class="form-label fw-bold">
                                <i class="fas fa-tag me-1"></i> Category
                            </label>
                            <select class="form-select" id="category" name="category">
                                <option value="">Select a category (optional)</option>
                                <?php foreach ($categories as $cat): ?>
                                    <option value="<?php echo $cat['id']; ?>"
                                            <?php echo $categoryId == $cat['id'] ? 'selected' : ''; ?>>
                                        <?php echo e($cat['name']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        
                        <!-- Current Featured Image -->
                        <?php if ($currentImage): ?>
                        <div class="mb-4">
                            <label class="form-label fw-bold">
                                <i class="fas fa-image me-1"></i> Current Featured Image
                            </label>
                            <div class="d-flex align-items-start gap-3">
                                <img src="<?php echo POST_IMAGE_URL . '/' . $currentImage; ?>" 
                                     alt="Current featured image"
                                     class="img-thumbnail"
                                     style="max-width: 200px;">
                                <div class="form-check">
                                    <input class="form-check-input" 
                                           type="checkbox" 
                                           id="remove_image" 
                                           name="remove_image">
                                    <label class="form-check-label" for="remove_image">
                                        Remove current image
                                    </label>
                                </div>
                            </div>
                        </div>
                        <?php endif; ?>
                        
                        <!-- Featured Image Upload -->
                        <div class="mb-4">
                            <label for="featured_image" class="form-label fw-bold">
                                <i class="fas fa-image me-1"></i> 
                                <?php echo $currentImage ? 'Replace Featured Image' : 'Featured Image'; ?>
                            </label>
                            <input type="file" 
                                   class="form-control" 
                                   id="featured_image" 
                                   name="featured_image"
                                   accept="image/*">
                            <small class="form-text text-muted">
                                Max size: <?php echo formatFileSize(MAX_POST_IMAGE_SIZE); ?>. 
                                Supported formats: JPG, PNG, GIF, WebP
                            </small>
                            <div id="imagePreview" class="mt-2"></div>
                        </div>
                        
                        <!-- Summary Field -->
                        <div class="mb-4">
                            <label for="summary" class="form-label fw-bold">
                                <i class="fas fa-align-left me-1"></i> Summary/Excerpt
                            </label>
                            <textarea class="form-control" 
                                      id="summary" 
                                      name="summary" 
                                      rows="3"
                                      maxlength="<?php echo MAX_SUMMARY_LENGTH; ?>"
                                      placeholder="Write a brief summary or excerpt of your post (optional)..."><?php echo e($summary); ?></textarea>
                            <small class="form-text text-muted">
                                Optional. Max <?php echo MAX_SUMMARY_LENGTH; ?> characters. 
                                This will be shown in post previews.
                            </small>
                        </div>
                        
                        <!-- Content Editor -->
                        <div class="mb-4">
                            <label for="content" class="form-label fw-bold">
                                <i class="fas fa-file-alt me-1"></i> Content
                                <span class="text-danger">*</span>
                            </label>
                            <textarea class="form-control" 
                                      id="content" 
                                      name="content" 
                                      rows="15"
                                      required
                                      placeholder="Write your blog post content here..."><?php echo htmlspecialchars($content); ?></textarea>
                            <small class="form-text text-muted">
                                Minimum <?php echo MIN_CONTENT_LENGTH; ?> characters. 
                                You can use HTML tags for formatting.
                            </small>
                            
                            <!-- Formatting Toolbar -->
                            <div class="btn-toolbar mt-2" role="toolbar">
                                <div class="btn-group btn-group-sm me-2" role="group">
                                    <button type="button" class="btn btn-outline-secondary" onclick="formatText('bold')" title="Bold">
                                        <i class="fas fa-bold"></i>
                                    </button>
                                    <button type="button" class="btn btn-outline-secondary" onclick="formatText('italic')" title="Italic">
                                        <i class="fas fa-italic"></i>
                                    </button>
                                    <button type="button" class="btn btn-outline-secondary" onclick="formatText('underline')" title="Underline">
                                        <i class="fas fa-underline"></i>
                                    </button>
                                </div>
                                <div class="btn-group btn-group-sm me-2" role="group">
                                    <button type="button" class="btn btn-outline-secondary" onclick="formatText('h2')" title="Heading 2">
                                        <i class="fas fa-heading"></i> H2
                                    </button>
                                    <button type="button" class="btn btn-outline-secondary" onclick="formatText('h3')" title="Heading 3">
                                        <i class="fas fa-heading"></i> H3
                                    </button>
                                </div>
                                <div class="btn-group btn-group-sm" role="group">
                                    <button type="button" class="btn btn-outline-secondary" onclick="formatText('link')" title="Insert Link">
                                        <i class="fas fa-link"></i>
                                    </button>
                                    <button type="button" class="btn btn-outline-secondary" onclick="formatText('quote')" title="Blockquote">
                                        <i class="fas fa-quote-right"></i>
                                    </button>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Status Selection -->
                        <div class="mb-4">
                            <label class="form-label fw-bold">
                                <i class="fas fa-globe me-1"></i> Post Status
                            </label>
                            <div class="form-check">
                                <input class="form-check-input" 
                                       type="radio" 
                                       name="status" 
                                       id="statusPublished" 
                                       value="published"
                                       <?php echo $status === 'published' ? 'checked' : ''; ?>>
                                <label class="form-check-label" for="statusPublished">
                                    <strong>Published</strong> - Make this post visible to everyone
                                </label>
                            </div>
                            <div class="form-check">
                                <input class="form-check-input" 
                                       type="radio" 
                                       name="status" 
                                       id="statusDraft" 
                                       value="draft"
                                       <?php echo $status === 'draft' ? 'checked' : ''; ?>>
                                <label class="form-check-label" for="statusDraft">
                                    <strong>Save as Draft</strong> - Keep this post private for now
                                </label>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Action Buttons -->
                <div class="d-flex gap-2 justify-content-between" data-aos="fade-up">
                    <a href="<?php echo SITE_URL; ?>/posts/view_blog.php?id=<?php echo $postId; ?>" 
                       class="btn btn-outline-secondary">
                        <i class="fas fa-times me-2"></i> Cancel
                    </a>
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save me-2"></i> Update Post
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Image Preview Script -->
<script>
// Image preview
document.getElementById('featured_image').addEventListener('change', function(e) {
    const file = e.target.files[0];
    const preview = document.getElementById('imagePreview');
    
    if (file) {
        const reader = new FileReader();
        reader.onload = function(e) {
            preview.innerHTML = '<div class="alert alert-info"><strong>New image preview:</strong><br><img src="' + e.target.result + '" class="img-fluid rounded mt-2" style="max-height: 300px;"></div>';
        }
        reader.readAsDataURL(file);
    } else {
        preview.innerHTML = '';
    }
});

// Simple text formatting
function formatText(format) {
    const textarea = document.getElementById('content');
    const start = textarea.selectionStart;
    const end = textarea.selectionEnd;
    const selectedText = textarea.value.substring(start, end);
    let replacement = '';
    
    switch(format) {
        case 'bold':
            replacement = '<strong>' + selectedText + '</strong>';
            break;
        case 'italic':
            replacement = '<em>' + selectedText + '</em>';
            break;
        case 'underline':
            replacement = '<u>' + selectedText + '</u>';
            break;
        case 'h2':
            replacement = '<h2>' + selectedText + '</h2>';
            break;
        case 'h3':
            replacement = '<h3>' + selectedText + '</h3>';
            break;
        case 'link':
            const url = prompt('Enter URL:');
            if (url) {
                replacement = '<a href="' + url + '">' + selectedText + '</a>';
            }
            break;
        case 'quote':
            replacement = '<blockquote>' + selectedText + '</blockquote>';
            break;
    }
    
    if (replacement) {
        textarea.value = textarea.value.substring(0, start) + replacement + textarea.value.substring(end);
        textarea.focus();
    }
}

// Form validation
document.getElementById('editPostForm').addEventListener('submit', function(e) {
    const title = document.getElementById('title').value.trim();
    const content = document.getElementById('content').value.trim();
    
    if (!title || title.length < <?php echo MIN_TITLE_LENGTH; ?>) {
        e.preventDefault();
        Swal.fire({
            icon: 'error',
            title: 'Invalid Title',
            text: 'Title must be at least <?php echo MIN_TITLE_LENGTH; ?> characters long.',
            confirmButtonColor: '#FFB100'
        });
        return false;
    }
    
    if (!content || content.length < <?php echo MIN_CONTENT_LENGTH; ?>) {
        e.preventDefault();
        Swal.fire({
            icon: 'error',
            title: 'Content Too Short',
            text: 'Content must be at least <?php echo MIN_CONTENT_LENGTH; ?> characters long.',
            confirmButtonColor: '#FFB100'
        });
        return false;
    }
});
</script>

<?php
$customJS = '<script src="' . JS_URL . '/editor.js"></script>';
include '../includes/footer.php'; 
?>