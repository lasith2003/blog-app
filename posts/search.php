<?php
/**
 * BLOG HUT - Advanced Search Page
 * This page provides advanced search functionality with:
 * - Full-text search across title, content, summary
 * - Category filtering
 * - Date range filtering
 * - Author search
 * - Sort options
 * - Search suggestions
 * - Results pagination
 */

// Start session
session_start();

// Include required files
require_once '../config/database.php';
require_once '../config/constants.php';
require_once '../includes/helper.php';
require_once '../includes/post_function.php';

// Get search parameters
$searchTerm = isset($_GET['q']) ? cleanInput($_GET['q']) : '';
$categoryFilter = isset($_GET['category']) ? intval($_GET['category']) : null;
$authorFilter = isset($_GET['author']) ? cleanInput($_GET['author']) : '';
$sortBy = isset($_GET['sort']) ? cleanInput($_GET['sort']) : 'relevance';
$currentPage = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;

// Initialize results
$results = [];
$totalResults = 0;
$totalPages = 0;
$searchPerformed = false;

// Get all categories for filter
$categories = getAllCategories();

// Perform search if query exists
if (!isEmpty($searchTerm) || $categoryFilter || !isEmpty($authorFilter)) {
    $searchPerformed = true;
    
    try {
        // Build search query
        $sql = "SELECT bp.*, u.username, u.profile_image, c.name as category_name,
                (SELECT COUNT(*) FROM reactions WHERE blog_id = bp.id) as reaction_count,
                (SELECT COUNT(*) FROM comments WHERE blog_id = bp.id) as comment_count
                FROM blogPost bp
                JOIN users u ON bp.user_id = u.id
                LEFT JOIN categories c ON bp.category = c.id
                WHERE bp.status = 'published'";
        
        $params = [];
        
        // Add text search
        if (!isEmpty($searchTerm)) {
            $sql .= " AND (bp.title LIKE ? OR bp.content LIKE ? OR bp.summary LIKE ?)";
            $searchParam = "%$searchTerm%";
            $params[] = $searchParam;
            $params[] = $searchParam;
            $params[] = $searchParam;
        }
        
        // Add category filter
        if ($categoryFilter) {
            $sql .= " AND bp.category = ?";
            $params[] = $categoryFilter;
        }
        
        // Add author filter
        if (!isEmpty($authorFilter)) {
            $sql .= " AND u.username LIKE ?";
            $params[] = "%$authorFilter%";
        }
        
        // Add sorting
        switch ($sortBy) {
            case 'latest':
                $sql .= " ORDER BY bp.created_at DESC";
                break;
            case 'oldest':
                $sql .= " ORDER BY bp.created_at ASC";
                break;
            case 'popular':
                $sql .= " ORDER BY bp.views DESC, reaction_count DESC";
                break;
            case 'relevance':
            default:
                // Simple relevance: title matches > content matches
                if (!isEmpty($searchTerm)) {
                    $sql .= " ORDER BY 
                            CASE 
                                WHEN bp.title LIKE ? THEN 1
                                WHEN bp.summary LIKE ? THEN 2
                                ELSE 3
                            END, bp.created_at DESC";
                    $params[] = "%$searchTerm%";
                    $params[] = "%$searchTerm%";
                } else {
                    $sql .= " ORDER BY bp.created_at DESC";
                }
                break;
        }
        
        // Get total count for pagination
        $countSql = str_replace("SELECT bp.*, u.username, u.profile_image, c.name as category_name,
                (SELECT COUNT(*) FROM reactions WHERE blog_id = bp.id) as reaction_count,
                (SELECT COUNT(*) FROM comments WHERE blog_id = bp.id) as comment_count", 
                "SELECT COUNT(*) as total", $sql);
        
        // Remove ORDER BY for count query
        $countSql = preg_replace('/ORDER BY.*/', '', $countSql);
        
        $countResult = fetchOne($countSql, $params);
        $totalResults = $countResult['total'] ?? 0;
        $totalPages = ceil($totalResults / SEARCH_RESULTS_PER_PAGE);
        
        // Add pagination
        $offset = ($currentPage - 1) * SEARCH_RESULTS_PER_PAGE;
        $sql .= " LIMIT ? OFFSET ?";
        $params[] = SEARCH_RESULTS_PER_PAGE;
        $params[] = $offset;
        
        // Execute search
        $results = fetchAll($sql, $params);
        
    } catch (Exception $e) {
        error_log("Search Error: " . $e->getMessage());
        $results = [];
    }
}

// Get selected category info
$selectedCategory = null;
if ($categoryFilter) {
    $selectedCategory = getCategoryById($categoryFilter);
}

// Set page title
$pageTitle = 'Search';
if ($searchTerm) {
    $pageTitle = 'Search: ' . $searchTerm;
}
$pageTitle .= ' - ' . SITE_NAME;
?>
<?php include '../includes/header.php'; ?>

<div class="container my-5">
    <!-- Page Header -->
    <div class="text-center mb-5" data-aos="fade-down">
        <h1 class="fw-bold mb-2">
            <i class="fas fa-search text-primary me-2"></i>
            Search Blog Posts
        </h1>
        <p class="text-muted">Find the content you're looking for</p>
    </div>
    
    <!-- Advanced Search Form -->
    <div class="card shadow-sm mb-5" data-aos="fade-up">
        <div class="card-body p-4">
            <form method="GET" action="">
                <div class="row g-3">
                    <!-- Search Query -->
                    <div class="col-md-12">
                        <label for="search" class="form-label fw-bold">
                            <i class="fas fa-keyboard me-1"></i> Search Keywords
                        </label>
                        <div class="input-group input-group-lg">
                            <input type="text" 
                                   class="form-control" 
                                   id="search" 
                                   name="q" 
                                   value="<?php echo e($searchTerm); ?>"
                                   placeholder="Search by title, content, or keywords..."
                                   autofocus>
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-search me-2"></i> Search
                            </button>
                        </div>
                    </div>
                    
                    <!-- Advanced Filters -->
                    <div class="col-12">
                        <button class="btn btn-link text-decoration-none p-0" 
                                type="button" 
                                data-bs-toggle="collapse" 
                                data-bs-target="#advancedFilters">
                            <i class="fas fa-sliders-h me-1"></i> Advanced Filters
                        </button>
                    </div>
                    
                    <div class="collapse" id="advancedFilters">
                        <div class="row g-3 pt-3 border-top">
                            <!-- Category Filter -->
                            <div class="col-md-4">
                                <label for="category" class="form-label">
                                    <i class="fas fa-tag me-1"></i> Category
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
                            
                            <!-- Author Filter -->
                            <div class="col-md-4">
                                <label for="author" class="form-label">
                                    <i class="fas fa-user me-1"></i> Author
                                </label>
                                <input type="text" 
                                       class="form-control" 
                                       id="author" 
                                       name="author" 
                                       value="<?php echo e($authorFilter); ?>"
                                       placeholder="Filter by author username...">
                            </div>
                            
                            <!-- Sort By -->
                            <div class="col-md-4">
                                <label for="sort" class="form-label">
                                    <i class="fas fa-sort me-1"></i> Sort By
                                </label>
                                <select class="form-select" id="sort" name="sort">
                                    <option value="relevance" <?php echo $sortBy === 'relevance' ? 'selected' : ''; ?>>
                                        Relevance
                                    </option>
                                    <option value="latest" <?php echo $sortBy === 'latest' ? 'selected' : ''; ?>>
                                        Latest First
                                    </option>
                                    <option value="oldest" <?php echo $sortBy === 'oldest' ? 'selected' : ''; ?>>
                                        Oldest First
                                    </option>
                                    <option value="popular" <?php echo $sortBy === 'popular' ? 'selected' : ''; ?>>
                                        Most Popular
                                    </option>
                                </select>
                            </div>
                        </div>
                    </div>
                </div>
            </form>
            
            <!-- Active Filters Display -->
            <?php if ($searchPerformed && ($searchTerm || $categoryFilter || $authorFilter)): ?>
            <div class="mt-3 pt-3 border-top">
                <small class="text-muted me-2">Active filters:</small>
                <?php if ($searchTerm): ?>
                <a href="?<?php echo $categoryFilter ? 'category=' . $categoryFilter : ''; ?><?php echo $authorFilter ? '&author=' . urlencode($authorFilter) : ''; ?>" 
                   class="badge bg-primary text-decoration-none me-1">
                    Keywords: <?php echo e(truncate($searchTerm, 30)); ?>
                    <i class="fas fa-times ms-1"></i>
                </a>
                <?php endif; ?>
                
                <?php if ($categoryFilter): ?>
                <a href="?<?php echo $searchTerm ? 'q=' . urlencode($searchTerm) : ''; ?><?php echo $authorFilter ? '&author=' . urlencode($authorFilter) : ''; ?>" 
                   class="badge bg-primary text-decoration-none me-1">
                    Category: <?php echo e($selectedCategory['name']); ?>
                    <i class="fas fa-times ms-1"></i>
                </a>
                <?php endif; ?>
                
                <?php if ($authorFilter): ?>
                <a href="?<?php echo $searchTerm ? 'q=' . urlencode($searchTerm) : ''; ?><?php echo $categoryFilter ? '&category=' . $categoryFilter : ''; ?>" 
                   class="badge bg-primary text-decoration-none me-1">
                    Author: <?php echo e($authorFilter); ?>
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
    
    <!-- Search Results -->
    <?php if ($searchPerformed): ?>
        <!-- Results Header -->
        <div class="mb-4" data-aos="fade-up">
            <h4>
                <?php if ($totalResults > 0): ?>
                    Found <?php echo number_format($totalResults); ?> 
                    result<?php echo $totalResults !== 1 ? 's' : ''; ?>
                <?php else: ?>
                    No results found
                <?php endif; ?>
            </h4>
        </div>
        
        <?php if (!empty($results)): ?>
        <!-- Results Grid -->
        <div class="row g-4">
            <?php foreach ($results as $index => $post): ?>
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
                                    <?php echo timeAgo($post['created_at']); ?>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Category Badge -->
                        <?php if ($post['category_name']): ?>
                        <a href="?category=<?php echo $post['category']; ?>" 
                           class="badge bg-primary text-decoration-none mb-2 align-self-start">
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
                                <span class="me-2">
                                    <i class="fas fa-eye"></i> <?php echo number_format($post['views']); ?>
                                </span>
                                <span class="me-2">
                                    <i class="fas fa-heart"></i> <?php echo $post['reaction_count']; ?>
                                </span>
                                <span>
                                    <i class="fas fa-comment"></i> <?php echo $post['comment_count']; ?>
                                </span>
                            </div>
                            <a href="<?php echo SITE_URL; ?>/posts/view_blog.php?id=<?php echo $post['id']; ?>" 
                               class="btn btn-sm btn-outline-primary">
                                Read
                            </a>
                        </div>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
        
        <!-- Pagination -->
        <?php if ($totalPages > 1): ?>
        <nav aria-label="Search results pagination" class="mt-5">
            <ul class="pagination justify-content-center">
                <!-- Previous -->
                <li class="page-item <?php echo $currentPage <= 1 ? 'disabled' : ''; ?>">
                    <a class="page-link" href="?page=<?php echo $currentPage - 1; ?>&q=<?php echo urlencode($searchTerm); ?><?php echo $categoryFilter ? '&category=' . $categoryFilter : ''; ?><?php echo $authorFilter ? '&author=' . urlencode($authorFilter) : ''; ?>&sort=<?php echo $sortBy; ?>">
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
                    <a class="page-link" href="?page=<?php echo $i; ?>&q=<?php echo urlencode($searchTerm); ?><?php echo $categoryFilter ? '&category=' . $categoryFilter : ''; ?><?php echo $authorFilter ? '&author=' . urlencode($authorFilter) : ''; ?>&sort=<?php echo $sortBy; ?>">
                        <?php echo $i; ?>
                    </a>
                </li>
                <?php endfor; ?>
                
                <!-- Next -->
                <li class="page-item <?php echo $currentPage >= $totalPages ? 'disabled' : ''; ?>">
                    <a class="page-link" href="?page=<?php echo $currentPage + 1; ?>&q=<?php echo urlencode($searchTerm); ?><?php echo $categoryFilter ? '&category=' . $categoryFilter : ''; ?><?php echo $authorFilter ? '&author=' . urlencode($authorFilter) : ''; ?>&sort=<?php echo $sortBy; ?>">
                        <i class="fas fa-chevron-right"></i>
                    </a>
                </li>
            </ul>
            
            <p class="text-center text-muted small">
                Page <?php echo $currentPage; ?> of <?php echo $totalPages; ?>
            </p>
        </nav>
        <?php endif; ?>
        
        <?php else: ?>
        <!-- No Results -->
        <div class="text-center py-5" data-aos="fade-up">
            <i class="fas fa-search fa-4x text-muted mb-3"></i>
            <h4 class="text-muted">No results found</h4>
            <p class="text-muted">
                Try different keywords or adjust your filters
            </p>
            <a href="?" class="btn btn-primary mt-3">
                <i class="fas fa-times me-2"></i> Clear Search
            </a>
        </div>
        <?php endif; ?>
        
    <?php else: ?>
    <!-- No Search Performed Yet -->
    <div class="text-center py-5" data-aos="fade-up">
        <i class="fas fa-search fa-4x text-primary mb-3"></i>
        <h4>Start searching for blog posts</h4>
        <p class="text-muted">
            Enter keywords, filter by category, or search by author
        </p>
    </div>
    <?php endif; ?>
</div>

<?php include '../includes/footer.php'; ?>