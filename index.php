<?php
/**
 * BLOG HUT - Landing Page
 */

// Start session
session_start();

// Include required files
require_once 'config/database.php';
require_once 'config/constants.php';
require_once 'includes/helper.php';
require_once 'includes/post_function.php';

// Fetch data for landing page
try {
    // Get trending posts
    $trendingPosts = getTrendingPosts(3);
    
    // Get latest posts
    $latestPosts = getLatestPosts(6);
    
    // Get all categories
    $categories = getAllCategories();
    
    // Get site statistics
    $statsQuery = "SELECT 
                    (SELECT COUNT(*) FROM users) as total_users,
                    (SELECT COUNT(*) FROM blogPost WHERE status = 'published') as total_posts,
                    (SELECT COUNT(*) FROM comments) as total_comments,
                    (SELECT COUNT(*) FROM categories) as total_categories";
    $stats = fetchOne($statsQuery);
    
} catch (Exception $e) {
    error_log("Index Page Error: " . $e->getMessage());
    $trendingPosts = [];
    $latestPosts = [];
    $categories = [];
    $stats = ['total_users' => 0, 'total_posts' => 0, 'total_comments' => 0, 'total_categories' => 0];
}

// Set page title
$pageTitle = SITE_NAME . ' - ' . SITE_TAGLINE;
$pageDescription = SITE_DESCRIPTION;
?>
<?php include 'includes/header.php'; ?>

<!-- Hero Section -->
<section class="hero-section">
    <div class="container">
        <div class="row align-items-center min-vh-75">
            <div class="col-lg-6" data-aos="fade-right">
                <h1 class="display-3 fw-bold mb-4">
                    Share Your <span class="text-primary">Stories</span>, <br>
                    Inspire The World
                </h1>
                <p class="lead text-muted mb-4">
                    Join our community of writers, creators, and storytellers. 
                    Start your blogging journey today and connect with readers worldwide.
                </p>
                <div class="d-flex gap-3 flex-wrap">
                    <?php if (isLoggedIn()): ?>
                        <a href="<?php echo SITE_URL; ?>/posts/create_blog.php" class="btn btn-primary btn-lg">
                            <i class="fas fa-pen me-2"></i> Start Writing
                        </a>
                        <a href="<?php echo SITE_URL; ?>/posts/home.php" class="btn btn-outline-primary btn-lg">
                            <i class="fas fa-book-open me-2"></i> Explore Blogs
                        </a>
                    <?php else: ?>
                        <a href="<?php echo SITE_URL; ?>/auth/register.php" class="btn btn-primary btn-lg">
                            <i class="fas fa-user-plus me-2"></i> Get Started
                        </a>
                        <a href="<?php echo SITE_URL; ?>/posts/home.php" class="btn btn-outline-primary btn-lg">
                            <i class="fas fa-book-open me-2"></i> Browse Blogs
                        </a>
                    <?php endif; ?>
                </div>
            </div>
            <div class="col-lg-6 mt-5 mt-lg-0" data-aos="fade-left">
                <div class="hero-image-wrapper">
                    <img src="<?php echo IMAGES_URL; ?>/hero-bg.jpg" 
                         alt="Blog Hut Hero" 
                         class="img-fluid rounded-4 shadow-lg"
                         onerror="this.src='https://via.placeholder.com/600x400/FFB100/FFFFFF?text=Blog+Hut'">
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Statistics Section -->
<section class="py-5 bg-primary text-white">
    <div class="container">
        <div class="row text-center g-4">
            <div class="col-6 col-md-3" data-aos="fade-up" data-aos-delay="100">
                <div class="stat-item">
                    <i class="fas fa-users fa-2x mb-3"></i>
                    <h2 class="fw-bold mb-0"><?php echo number_format($stats['total_users']); ?>+</h2>
                    <p class="mb-0">Writers</p>
                </div>
            </div>
            <div class="col-6 col-md-3" data-aos="fade-up" data-aos-delay="200">
                <div class="stat-item">
                    <i class="fas fa-blog fa-2x mb-3"></i>
                    <h2 class="fw-bold mb-0"><?php echo number_format($stats['total_posts']); ?>+</h2>
                    <p class="mb-0">Blog Posts</p>
                </div>
            </div>
            <div class="col-6 col-md-3" data-aos="fade-up" data-aos-delay="300">
                <div class="stat-item">
                    <i class="fas fa-comments fa-2x mb-3"></i>
                    <h2 class="fw-bold mb-0"><?php echo number_format($stats['total_comments']); ?>+</h2>
                    <p class="mb-0">Comments</p>
                </div>
            </div>
            <div class="col-6 col-md-3" data-aos="fade-up" data-aos-delay="400">
                <div class="stat-item">
                    <i class="fas fa-tags fa-2x mb-3"></i>
                    <h2 class="fw-bold mb-0"><?php echo number_format($stats['total_categories']); ?>+</h2>
                    <p class="mb-0">Categories</p>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Trending Posts Section -->
<?php if (!empty($trendingPosts)): ?>
<section class="py-5">
    <div class="container">
        <div class="text-center mb-5" data-aos="fade-up">
            <h2 class="fw-bold mb-3">
                <i class="fas fa-fire text-danger me-2"></i> Trending Now
            </h2>
            <p class="text-muted">Most popular posts from our community</p>
        </div>
        
        <div class="row g-4">
            <?php foreach ($trendingPosts as $index => $post): ?>
            <div class="col-md-4" data-aos="fade-up" data-aos-delay="<?php echo ($index + 1) * 100; ?>">
                <div class="card post-card h-100 border-0 shadow-sm">
                    <?php if ($post['featured_image']): ?>
                    <img src="<?php echo POST_IMAGE_URL . '/' . $post['featured_image']; ?>" 
                         class="card-img-top" 
                         alt="<?php echo e($post['title']); ?>"
                         style="height: 200px; object-fit: cover;"
                         onerror="this.src='https://via.placeholder.com/400x200/FFB100/FFFFFF?text=Blog+Image'">
                    <?php endif; ?>
                    
                    <div class="card-body">
                        <div class="d-flex align-items-center mb-2">
                            <img src="<?php echo AVATAR_URL . '/' . ($post['profile_image'] ?? DEFAULT_AVATAR); ?>" 
                                 alt="<?php echo e($post['username']); ?>"
                                 class="rounded-circle me-2"
                                 style="width: 30px; height: 30px; object-fit: cover;">
                            <small class="text-muted"><?php echo e($post['username']); ?></small>
                            <small class="text-muted ms-auto">
                                <i class="fas fa-clock me-1"></i>
                                <?php echo timeAgo($post['created_at']); ?>
                            </small>
                        </div>
                        
                        <h5 class="card-title">
                            <a href="<?php echo SITE_URL; ?>/posts/view_blog.php?id=<?php echo $post['id']; ?>" 
                               class="text-decoration-none text-dark">
                                <?php echo e(truncate($post['title'], 60)); ?>
                            </a>
                        </h5>
                        
                        <p class="card-text text-muted small">
                            <?php echo e(truncate(strip_tags($post['summary'] ?? $post['content']), 100)); ?>
                        </p>
                        
                        <div class="d-flex justify-content-between align-items-center">
                            <div class="post-meta small text-muted">
                                <span class="me-3">
                                    <i class="fas fa-eye me-1"></i> <?php echo number_format($post['views']); ?>
                                </span>
                                <span class="me-3">
                                    <i class="fas fa-heart me-1"></i> <?php echo $post['reaction_count']; ?>
                                </span>
                                <span>
                                    <i class="fas fa-comment me-1"></i> <?php echo $post['comment_count']; ?>
                                </span>
                            </div>
                            <a href="<?php echo SITE_URL; ?>/posts/view_blog.php?id=<?php echo $post['id']; ?>" 
                               class="btn btn-sm btn-outline-primary">
                                Read More
                            </a>
                        </div>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
</section>
<?php endif; ?>

<!-- Categories Section -->
<?php if (!empty($categories)): ?>
<section class="py-5 bg-light">
    <div class="container">
        <div class="text-center mb-5" data-aos="fade-up">
            <h2 class="fw-bold mb-3">
                <i class="fas fa-th-large me-2"></i> Explore Categories
            </h2>
            <p class="text-muted">Discover content that interests you</p>
        </div>
        
        <div class="row g-3">
            <?php foreach (array_slice($categories, 0, 8) as $index => $category): ?>
            <div class="col-6 col-md-4 col-lg-3" data-aos="zoom-in" data-aos-delay="<?php echo ($index + 1) * 50; ?>">
                <a href="<?php echo SITE_URL; ?>/posts/home.php?category=<?php echo $category['id']; ?>" 
                   class="category-card text-decoration-none">
                    <div class="card border-0 shadow-sm h-100 text-center p-3">
                        <div class="card-body">
                            <i class="fas fa-tag fa-2x text-primary mb-2"></i>
                            <h6 class="mb-0"><?php echo e($category['name']); ?></h6>
                        </div>
                    </div>
                </a>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
</section>
<?php endif; ?>

<!-- Latest Posts Section -->
<?php if (!empty($latestPosts)): ?>
<section class="py-5">
    <div class="container">
        <div class="text-center mb-5" data-aos="fade-up">
            <h2 class="fw-bold mb-3">
                <i class="fas fa-clock me-2"></i> Latest Stories
            </h2>
            <p class="text-muted">Fresh content from our writers</p>
        </div>
        
        <div class="row g-4">
            <?php foreach ($latestPosts as $index => $post): ?>
            <div class="col-md-6 col-lg-4" data-aos="fade-up" data-aos-delay="<?php echo ($index + 1) * 100; ?>">
                <div class="card post-card h-100 border-0 shadow-sm">
                    <?php if ($post['featured_image']): ?>
                    <img src="<?php echo POST_IMAGE_URL . '/' . $post['featured_image']; ?>" 
                         class="card-img-top" 
                         alt="<?php echo e($post['title']); ?>"
                         style="height: 180px; object-fit: cover;"
                         onerror="this.src='https://via.placeholder.com/400x180/FFB100/FFFFFF?text=Blog+Image'">
                    <?php endif; ?>
                    
                    <div class="card-body">
                        <div class="d-flex align-items-center mb-2">
                            <img src="<?php echo AVATAR_URL . '/' . ($post['profile_image'] ?? DEFAULT_AVATAR); ?>" 
                                 alt="<?php echo e($post['username']); ?>"
                                 class="rounded-circle me-2"
                                 style="width: 30px; height: 30px; object-fit: cover;">
                            <small class="text-muted"><?php echo e($post['username']); ?></small>
                        </div>
                        
                        <h5 class="card-title">
                            <a href="<?php echo SITE_URL; ?>/posts/view_blog.php?id=<?php echo $post['id']; ?>" 
                               class="text-decoration-none text-dark">
                                <?php echo e(truncate($post['title'], 60)); ?>
                            </a>
                        </h5>
                        
                        <p class="card-text text-muted small">
                            <?php echo timeAgo($post['created_at']); ?>
                        </p>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
        
        <div class="text-center mt-5" data-aos="fade-up">
            <a href="<?php echo SITE_URL; ?>/posts/home.php" class="btn btn-primary btn-lg">
                <i class="fas fa-arrow-right me-2"></i> View All Posts
            </a>
        </div>
    </div>
</section>
<?php endif; ?>

<!-- Call to Action Section -->
<section class="py-5 bg-primary text-white">
    <div class="container text-center" data-aos="fade-up">
        <h2 class="fw-bold mb-3">Ready to Share Your Story?</h2>
        <p class="lead mb-4">Join thousands of writers and start your blogging journey today</p>
        <?php if (isLoggedIn()): ?>
            <a href="<?php echo SITE_URL; ?>/posts/create_blog.php" class="btn btn-light btn-lg">
                <i class="fas fa-pen me-2"></i> Create Your First Post
            </a>
        <?php else: ?>
            <a href="<?php echo SITE_URL; ?>/auth/register.php" class="btn btn-light btn-lg">
                <i class="fas fa-user-plus me-2"></i> Sign Up Free
            </a>
        <?php endif; ?>
    </div>
</section>

<?php include 'includes/footer.php'; ?>