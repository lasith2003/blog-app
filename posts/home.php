<?php
/**
 * BLOG HUT - Blog Listing Page
 * This page displays all published blog posts with:
 * - Pagination
 * - Category filtering
 * - Search functionality
 * - Sorting options
 * - Post cards with metadata
 * - Responsive grid layout
 */

// Start session
session_start();

// Include required files
require_once '../config/database.php';
require_once '../config/constants.php';
require_once '../includes/helper.php';
require_once '../includes/post_function.php';

// Get filter parameters
$currentPage = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
$categoryFilter = isset($_GET['category']) ? intval($_GET['category']) : null;
$searchTerm = isset($_GET['search']) ? cleanInput($_GET['search']) : null;
$sortBy = isset($_GET['sort']) ? cleanInput($_GET['sort']) : 'latest';

// Fetch posts based on filters
try {
    $posts = getAllPosts($currentPage, POSTS_PER_PAGE, $categoryFilter, $searchTerm);
    $totalPosts = getTotalPostCount($categoryFilter, $searchTerm);
    $totalPages = ceil($totalPosts / POSTS_PER_PAGE);
    
    // Get all categories for filter dropdown
    $categories = getAllCategories();
    
    // Get selected category info
    $selectedCategory = null;
    if ($categoryFilter) {
        $selectedCategory = getCategoryById($categoryFilter);
    }
    
} catch (Exception $e) {
    error_log("Posts Home Error: " . $e->getMessage());
    $posts = [];
    $totalPosts = 0;
    $totalPages = 0;
    $categories = [];
}

// Set page title
$pageTitle = 'All Blogs';
if ($selectedCategory) {
    $pageTitle = $selectedCategory['name'] . ' - Blogs';
} elseif ($searchTerm) {
    $pageTitle = 'Search: ' . $searchTerm;
}
$pageTitle .= ' - ' . SITE_NAME;
?>
<?php include '../includes/header.php'; ?>

<div class="container my-5">
    <!-- Page Header -->
    <div class="row mb-4">
        <div class="col-12" data-aos="fade-down">
            <h1 class="fw-bold mb-2">
                <?php if ($selectedCategory): ?>
                    <i class="fas fa-tag text-primary me-2"></i>
                    <?php echo e($selectedCategory['name']); ?>
                <?php elseif ($searchTerm): ?>
                    <i class="fas fa-search text-primary me-2"></i>
                    Search Results for "<?php echo e($searchTerm); ?>"
                <?php else: ?>
                    <i class="fas fa-blog text-primary me-2"></i>
                    All Blog Posts
                <?php endif; ?>
            </h1>
            <p class="text-muted">
                <?php echo number_format($totalPosts); ?> 
                <?php echo $totalPosts === 1 ? 'post' : 'posts'; ?> found
            </p>
        </div>
    </div>
    
    <!-- Filters & Search Bar -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="card border-0 shadow-sm" data-aos="fade-up">
                <div class="card-body">
                    <form method="GET" action="" class="row g-3 align-items-end">
                        <!-- Search Field -->
                        <div class="col-md-5">
                            <label for="search" class="form-label small text-muted">
                                <i class="fas fa-search me-1"></i> Search
                            </label>
                            <input type="text" 
                                   class="form-control" 
                                   id="search" 
                                   name="search" 
                                   value="<?php echo e($searchTerm ?? ''); ?>"
                                   placeholder="Search posts by title or content...">
                        </div>
                        
                        <!-- Category Filter -->
                        <div class="col-md-3">
                            <label for="category" class="form-label small text-muted">
                                <i class="fas fa-filter me-1"></i> Category
                            </label>
                            <select class="form-select" id="category" name="category">
                                <option value="">All Categories</option>
                                <?php foreach ($categories as $cat): ?>
                                    <option value="<?php echo $cat['id']; ?>"
                                            <?php echo $categoryFilter == $cat['id'] ? 'selected' : ''; ?>>
                                        <?php echo e($cat['name']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        
                        <!-- Sort By -->
                        <div class="col-md-2">
                            <label for="sort" class="form-label small text-muted">
                                <i class="fas fa-sort me-1"></i> Sort By
                            </label>
                            <select class="form-select" id="sort" name="sort">
                                <option value="latest" <?php echo $sortBy === 'latest' ? 'selected' : ''; ?>>
                                    Latest
                                </option>
                                <option value="oldest" <?php echo $sortBy === 'oldest' ? 'selected' : ''; ?>>
                                    Oldest
                                </option>
                                <option value="popular" <?php echo $sortBy === 'popular' ? 'selected' : ''; ?>>
                                    Popular
                                </option>
                            </select>
                        </div>
                        
                        <!-- Submit Button -->
                        <div class="col-md-2">
                            <button type="submit" class="btn btn-primary w-100">
                                <i class="fas fa-filter me-1"></i> Apply
                            </button>
                        </div>
                    </form>
                    
                    <!-- Active Filters -->
                    <?php if ($categoryFilter || $searchTerm): ?>
                    <div class="mt-3">
                        <small class="text-muted me-2">Active filters:</small>
                        <?php if ($searchTerm): ?>
                        <a href="?<?php echo $categoryFilter ? 'category=' . $categoryFilter : ''; ?>" 
                           class="badge bg-primary text-decoration-none me-1">
                            Search: <?php echo e(truncate($searchTerm, 30)); ?>
                            <i class="fas fa-times ms-1"></i>
                        </a>
                        <?php endif; ?>
                        
                        <?php if ($categoryFilter): ?>
                        <a href="?<?php echo $searchTerm ? 'search=' . urlencode($searchTerm) : ''; ?>" 
                           class="badge bg-primary text-decoration-none me-1">
                            Category: <?php echo e($selectedCategory['name']); ?>
                            <i class="fas fa-times ms-1"></i>
                        </a>
                        <?php endif; ?>
                        
                        <a href="?" class="badge bg-secondary text-decoration-none">
                            Clear All <i class="fas fa-times ms-1"></i>
                        </a>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Posts Grid -->
    <?php if (!empty($posts)): ?>
    <div class="row g-4">
        <?php foreach ($posts as $index => $post): ?>
        <div class="col-md-6 col-lg-4" data-aos="fade-up" data-aos-delay="<?php echo ($index % 3) * 100; ?>">
            <div class="card post-card h-100 border-0 shadow-sm hover-lift">
                <!-- Featured Image -->
                <?php if ($post['featured_image']): ?>
                <a href="<?php echo SITE_URL; ?>/posts/view_blog.php?id=<?php echo $post['id']; ?>">
                    <img src="<?php echo POST_IMAGE_URL . '/' . $post['featured_image']; ?>" 
                         class="card-img-top" 
                         alt="<?php echo e($post['title']); ?>"
                         style="height: 200px; object-fit: cover;"
                         onerror="this.src='https://via.placeholder.com/400x200/FFB100/FFFFFF?text=Blog+Image'">
                </a>
                <?php endif; ?>
                
                <div class="card-body d-flex flex-column">
                    <!-- Author Info -->
                    <div class="d-flex align-items-center mb-3">
                        <img src="<?php echo AVATAR_URL . '/' . ($post['profile_image'] ?? DEFAULT_AVATAR); ?>" 
                             alt="<?php echo e($post['username']); ?>"
                             class="rounded-circle me-2"
                             style="width: 35px; height: 35px; object-fit: cover;">
                        <div class="flex-grow-1">
                            <a href="<?php echo SITE_URL; ?>/profile/user_profile.php?id=<?php echo $post['user_id']; ?>" 
                               class="text-decoration-none text-dark fw-semibold small">
                                <?php echo e($post['username']); ?>
                            </a>
                            <div class="small text-muted">
                                <i class="fas fa-clock me-1"></i>
                                <?php echo timeAgo($post['created_at']); ?>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Category Badge -->
                    <?php if ($post['category_name']): ?>
                    <a href="?category=<?php echo $post['category']; ?>" 
                       class="badge bg-primary text-decoration-none mb-2 align-self-start">
                        <i class="fas fa-tag me-1"></i>
                        <?php echo e($post['category_name']); ?>
                    </a>
                    <?php endif; ?>
                    
                    <!-- Post Title -->
                    <h5 class="card-title mb-2">
                        <a href="<?php echo SITE_URL; ?>/posts/view_blog.php?id=<?php echo $post['id']; ?>" 
                           class="text-decoration-none text-dark">
                            <?php echo e(truncate($post['title'], 70)); ?>
                        </a>
                    </h5>
                    
                    <!-- Post Summary -->
                    <p class="card-text text-muted small mb-3 flex-grow-1">
                        <?php echo e(truncate(strip_tags($post['summary'] ?? $post['content']), 120)); ?>
                    </p>
                    
                    <!-- Post Meta & Action -->
                    <div class="d-flex justify-content-between align-items-center mt-auto">
                        <div class="post-meta small text-muted">
                            <span class="me-2" title="Views">
                                <i class="fas fa-eye"></i> <?php echo number_format($post['views']); ?>
                            </span>
                            <span class="me-2" title="Reactions">
                                <i class="fas fa-heart"></i> <?php echo $post['reaction_count']; ?>
                            </span>
                            <span title="Comments">
                                <i class="fas fa-comment"></i> <?php echo $post['comment_count']; ?>
                            </span>
                        </div>
                        <a href="<?php echo SITE_URL; ?>/posts/view_blog.php?id=<?php echo $post['id']; ?>" 
                           class="btn btn-sm btn-outline-primary">
                            Read <i class="fas fa-arrow-right ms-1"></i>
                        </a>
                    </div>
                </div>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
    
    <!-- Pagination -->
    <?php if ($totalPages > 1): ?>
    <nav aria-label="Blog pagination" class="mt-5">
        <ul class="pagination justify-content-center">
            <!-- Previous Page -->
            <li class="page-item <?php echo $currentPage <= 1 ? 'disabled' : ''; ?>">
                <a class="page-link" 
                   href="?page=<?php echo $currentPage - 1; ?><?php echo $categoryFilter ? '&category=' . $categoryFilter : ''; ?><?php echo $searchTerm ? '&search=' . urlencode($searchTerm) : ''; ?>">
                    <i class="fas fa-chevron-left"></i>
                </a>
            </li>
            
            <!-- Page Numbers -->
            <?php
            $startPage = max(1, $currentPage - 2);
            $endPage = min($totalPages, $currentPage + 2);
            
            for ($i = $startPage; $i <= $endPage; $i++):
            ?>
            <li class="page-item <?php echo $i === $currentPage ? 'active' : ''; ?>">
                <a class="page-link" 
                   href="?page=<?php echo $i; ?><?php echo $categoryFilter ? '&category=' . $categoryFilter : ''; ?><?php echo $searchTerm ? '&search=' . urlencode($searchTerm) : ''; ?>">
                    <?php echo $i; ?>
                </a>
            </li>
            <?php endfor; ?>
            
            <!-- Next Page -->
            <li class="page-item <?php echo $currentPage >= $totalPages ? 'disabled' : ''; ?>">
                <a class="page-link" 
                   href="?page=<?php echo $currentPage + 1; ?><?php echo $categoryFilter ? '&category=' . $categoryFilter : ''; ?><?php echo $searchTerm ? '&search=' . urlencode($searchTerm) : ''; ?>">
                    <i class="fas fa-chevron-right"></i>
                </a>
            </li>
        </ul>
        
        <!-- Page Info -->
        <p class="text-center text-muted small">
            Page <?php echo $currentPage; ?> of <?php echo $totalPages; ?>
            (<?php echo number_format($totalPosts); ?> total posts)
        </p>
    </nav>
    <?php endif; ?>
    
    <?php else: ?>
    <!-- No Posts Found -->
    <div class="text-center py-5" data-aos="fade-up">
        <i class="fas fa-inbox fa-4x text-muted mb-3"></i>
        <h4 class="text-muted">No posts found</h4>
        <p class="text-muted">
            <?php if ($searchTerm || $categoryFilter): ?>
                Try adjusting your filters or search term
            <?php else: ?>
                Be the first to create a post!
            <?php endif; ?>
        </p>
        <?php if ($searchTerm || $categoryFilter): ?>
        <a href="?" class="btn btn-primary mt-3">
            <i class="fas fa-times me-2"></i> Clear Filters
        </a>
        <?php elseif (isLoggedIn()): ?>
        <a href="<?php echo SITE_URL; ?>/posts/create_blog.php" class="btn btn-primary mt-3">
            <i class="fas fa-pen me-2"></i> Create First Post
        </a>
        <?php endif; ?>
    </div>
    <?php endif; ?>
</div>

<?php include '../includes/footer.php'; ?>