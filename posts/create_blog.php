<?php
/**
 * BLOG HUT - Create New Blog Post
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
    $content = $_POST['content'] ?? ''; 
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
                        
                    <!-- Content Editor with Visual Formatting -->
                    <div class="mb-4">
                        <label class="form-label fw-bold">
                            <i class="fas fa-file-alt me-1"></i> Content
                            <span class="text-danger">*</span>
                        </label>
                        
                        <!-- Editor Toolbar -->
                        <div class="editor-toolbar p-2 bg-light border rounded-top">
                            <div class="btn-toolbar" role="toolbar">
                                <!-- Text Formatting -->
                                <div class="btn-group btn-group-sm me-2 mb-2" role="group">
                                    <button type="button" class="btn btn-outline-secondary" onclick="execCmd('bold')" title="Bold (Ctrl+B)">
                                        <i class="fas fa-bold"></i>
                                    </button>
                                    <button type="button" class="btn btn-outline-secondary" onclick="execCmd('italic')" title="Italic (Ctrl+I)">
                                        <i class="fas fa-italic"></i>
                                    </button>
                                    <button type="button" class="btn btn-outline-secondary" onclick="execCmd('underline')" title="Underline (Ctrl+U)">
                                        <i class="fas fa-underline"></i>
                                    </button>
                                </div>
                                
                                <!-- Headings -->
                                <div class="btn-group btn-group-sm me-2 mb-2" role="group">
                                    <button type="button" class="btn btn-outline-secondary" onclick="execCmd('formatBlock', 'h2')" title="Heading 2">
                                        H2
                                    </button>
                                    <button type="button" class="btn btn-outline-secondary" onclick="execCmd('formatBlock', 'h3')" title="Heading 3">
                                        H3
                                    </button>
                                    <button type="button" class="btn btn-outline-secondary" onclick="execCmd('formatBlock', 'p')" title="Paragraph">
                                        P
                                    </button>
                                </div>
                                
                                <!-- Lists -->
                                <div class="btn-group btn-group-sm me-2 mb-2" role="group">
                                    <button type="button" class="btn btn-outline-secondary" onclick="execCmd('insertUnorderedList')" title="Bullet List">
                                        <i class="fas fa-list-ul"></i>
                                    </button>
                                    <button type="button" class="btn btn-outline-secondary" onclick="execCmd('insertOrderedList')" title="Numbered List">
                                        <i class="fas fa-list-ol"></i>
                                    </button>
                                </div>
                                
                                <!-- Other -->
                                <div class="btn-group btn-group-sm me-2 mb-2" role="group">
                                    <button type="button" class="btn btn-outline-secondary" onclick="insertLink()" title="Insert Link">
                                        <i class="fas fa-link"></i>
                                    </button>
                                    <button type="button" class="btn btn-outline-secondary" onclick="execCmd('formatBlock', 'blockquote')" title="Blockquote">
                                        <i class="fas fa-quote-right"></i>
                                    </button>
                                </div>
                                
                                <!-- Clear -->
                                <div class="btn-group btn-group-sm mb-2" role="group">
                                    <button type="button" class="btn btn-outline-danger" onclick="execCmd('removeFormat')" title="Clear Formatting">
                                        <i class="fas fa-eraser"></i>
                                    </button>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Visual Editor (ContentEditable Div) -->
                        <div id="editor" 
                            contenteditable="true" 
                            class="form-control visual-editor border-top-0 rounded-bottom" 
                            style="min-height: 400px; max-height: 600px; overflow-y: auto; padding: 20px; font-size: 1.1rem; line-height: 1.8;"
                            placeholder="Start writing your blog post here... Press Enter for new paragraph."><?php echo $content; ?></div>
                        
                        <!-- Hidden textarea for form submission -->
                        <textarea id="content" name="content" style="display: none;" required></textarea>
                        
                        <small class="form-text text-muted d-block mt-2">
                            Minimum <?php echo MIN_CONTENT_LENGTH; ?> characters. Use the toolbar above for formatting. Press <kbd>Enter</kbd> for new paragraph.
                        </small>
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
// ============================================
// VISUAL EDITOR SETUP
// ============================================
const editor = document.getElementById('editor');
const hiddenContent = document.getElementById('content');

// Load initial content
if (hiddenContent.value) {
    editor.innerHTML = hiddenContent.value;
}

// Sync editor content to hidden textarea
function syncContent() {
    hiddenContent.value = editor.innerHTML;
}

// Sync on input and blur
editor.addEventListener('input', syncContent);
editor.addEventListener('blur', syncContent);

// Execute formatting command
function execCmd(command, value = null) {
    editor.focus();
    document.execCommand(command, false, value);
    syncContent();
}

// Insert link
function insertLink() {
    const url = prompt('Enter URL:', 'https://');
    if (url && url !== 'https://') {
        execCmd('createLink', url);
    }
}

// Keyboard shortcuts
editor.addEventListener('keydown', function(e) {
    if (e.ctrlKey || e.metaKey) {
        switch(e.key.toLowerCase()) {
            case 'b':
                e.preventDefault();
                execCmd('bold');
                break;
            case 'i':
                e.preventDefault();
                execCmd('italic');
                break;
            case 'u':
                e.preventDefault();
                execCmd('underline');
                break;
        }
    }
    
    // Automatically create new paragraph on Enter
    if (e.key === 'Enter' && !e.shiftKey) {
        // Let default behavior handle it, but ensure we sync
        setTimeout(syncContent, 10);
    }
});

// Placeholder functionality
editor.addEventListener('focus', function() {
    if (this.innerHTML === '' || this.innerHTML === '<br>') {
        this.innerHTML = '<p><br></p>';
        // Place cursor at start
        const range = document.createRange();
        const sel = window.getSelection();
        range.setStart(this.firstChild, 0);
        range.collapse(true);
        sel.removeAllRanges();
        sel.addRange(range);
    }
});

// ============================================
// IMAGE PREVIEW
// ============================================
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

// ============================================
// FORM VALIDATION
// ============================================
document.getElementById('createPostForm').addEventListener('submit', function(e) {
    // Sync content before validation
    syncContent();
    
    const title = document.getElementById('title').value.trim();
    const editorText = editor.innerText || editor.textContent;
    const contentText = editorText.trim();
    
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
    
    if (!contentText || contentText.length < <?php echo MIN_CONTENT_LENGTH; ?>) {
        e.preventDefault();
        Swal.fire({
            icon: 'error',
            title: 'Content Too Short',
            text: 'Content must be at least <?php echo MIN_CONTENT_LENGTH; ?> characters long.',
            confirmButtonColor: '#FFB100'
        });
        return false;
    }
    
    // Final sync before submit
    syncContent();
});
</script>

<?php
$customJS = '<script src="' . JS_URL . '/editor.js"></script>';
include '../includes/footer.php'; 
?>