<?php
/**
 * ================================================================
 * BLOG HUT - Create New Blog Post
 * University of Moratuwa - IN2120 Web Programming Project
 * ================================================================
 * 
 * This page allows users to create new blog posts with:
 * - Rich text editor
 * - Featured image upload
 * - Category selection
 * - Summary/excerpt
 * - Draft or publish option
 * - Form validation
 * - Badge assignment (First Post)
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
require_once '../includes/post_function.php';

// Require login
requireLogin();

// Get all categories for dropdown
$categories = getAllCategories();

// Initialize variables
$errors = [];
$title = '';
$content = '';
$summary = '';
$categoryId = '';
$status = 'published';

// Process form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Get form data
    $title = cleanInput($_POST['title'] ?? '');
    $content = $_POST['content'] ?? ''; // Don't clean HTML content
    $summary = cleanInput($_POST['summary'] ?? '');
    $categoryId = isset($_POST['category']) ? intval($_POST['category']) : null;
    $status = isset($_POST['status']) ? cleanInput($_POST['status']) : 'published';
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
    
    // Validate summary (optional but has max length)
    if (!isEmpty($summary) && strlen($summary) > MAX_SUMMARY_LENGTH) {
        $errors[] = 'Summary must not exceed ' . MAX_SUMMARY_LENGTH . ' characters.';
    }
    
    // Validate status
    if (!in_array($status, ['draft', 'published'])) {
        $errors[] = 'Invalid post status.';
    }
    
    // Handle featured image upload
    $featuredImage = null;
    if (isset($_FILES['featured_image']) && $_FILES['featured_image']['error'] !== UPLOAD_ERR_NO_FILE) {
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
    
    // If no errors, create post
    if (empty($errors)) {
        try {
            $postData = [
                'user_id' => getCurrentUserId(),
                'title' => $title,
                'content' => $content,
                'summary' => $summary,
                'category' => $categoryId,
                'featured_image' => $featuredImage,
                'status' => $status
            ];
            
            $newPostId = createPost($postData);
            
            if ($newPostId) {
                // Check if this is user's first post and assign badge
                $userPostCount = fetchOne(
                    "SELECT COUNT(*) as count FROM blogPost WHERE user_id = ?",
                    [getCurrentUserId()]
                );
                
                if ($userPostCount['count'] == 1) {
                    // Assign "First Post" badge (badge ID 2)
                    $badgeExists = fetchOne(
                        "SELECT id FROM user_badges WHERE user_id = ? AND badge_id = 2",
                        [getCurrentUserId()]
                    );
                    
                    if (!$badgeExists) {
                        executeQuery(
                            "INSERT INTO user_badges (user_id, badge_id, earned_at) VALUES (?, 2, NOW())",
                            [getCurrentUserId()]
                        );
                    }
                }
                
                // Check for "Prolific Writer" badge (10+ posts)
                if ($userPostCount['count'] >= 10) {
                    $badgeExists = fetchOne(
                        "SELECT id FROM user_badges WHERE user_id = ? AND badge_id = 3",
                        [getCurrentUserId()]
                    );
                    
                    if (!$badgeExists) {
                        executeQuery(
                            "INSERT INTO user_badges (user_id, badge_id, earned_at) VALUES (?, 3, NOW())",
                            [getCurrentUserId()]
                        );
                    }
                }
                
                // Set success message
                $message = $status === 'published' 
                    ? 'Blog post published successfully!' 
                    : 'Blog post saved as draft.';
                setFlashMessage($message, 'success');
                
                // Redirect to view post
                redirect(SITE_URL . '/posts/view_blog.php?id=' . $newPostId);
            } else {
                $errors[] = 'Failed to create post. Please try again.';
            }
            
        } catch (Exception $e) {
            error_log("Create Post Error: " . $e->getMessage());
            $errors[] = 'An error occurred while creating the post.';
        }
    }
}

// Set page title
$pageTitle = 'Create New Post - ' . SITE_NAME;
?>
<?php include '../includes/header.php'; ?>

<div class="container my-5">
    <div class="row justify-content-center">
        <div class="col-lg-10">
            <!-- Page Header -->
            <div class="text-center mb-5" data-aos="fade-down">
                <h1 class="fw-bold mb-2">
                    <i class="fas fa-pen-fancy text-primary me-2"></i>
                    Create New Blog Post
                </h1>
                <p class="text-muted">Share your story with the world</p>
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
            
            <!-- Create Post Form -->
            <form method="POST" action="" enctype="multipart/form-data" id="createPostForm" data-aos="fade-up">
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
                        
                        <!-- Featured Image Upload -->
                        <div class="mb-4">
                            <label for="featured_image" class="form-label fw-bold">
                                <i class="fas fa-image me-1"></i> Featured Image
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
                                      placeholder="Write your blog post content here. You can use basic HTML formatting..."><?php echo htmlspecialchars($content); ?></textarea>
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
                                    <strong>Publish</strong> - Make this post visible to everyone
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
                <div class="d-flex gap-2 justify-content-end" data-aos="fade-up">
                    <a href="<?php echo SITE_URL; ?>/posts/home.php" class="btn btn-outline-secondary">
                        <i class="fas fa-times me-2"></i> Cancel
                    </a>
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-check me-2"></i> Create Post
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
            preview.innerHTML = '<img src="' + e.target.result + '" class="img-fluid rounded mt-2" style="max-height: 300px;">';
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
document.getElementById('createPostForm').addEventListener('submit', function(e) {
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