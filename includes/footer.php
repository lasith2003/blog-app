<?php
/*
 * BLOG HUT - Site Footer

 * This file contains:
 * - Footer content
 * - Copyright information
 * - Social media links
 * - JavaScript includes
 * - Theme toggle functionality
 */
?>

    </main>
    <!-- Main Content Ends Here -->
    
    <!-- Footer Section -->
    <footer class="footer bg-dark text-white mt-5">
        <div class="container py-5">
            <div class="row g-4">
                <!-- About Section -->
                <div class="col-md-4">
                    <h5 class="mb-3">
                        <i class="fas fa-blog text-primary me-2"></i>
                        <?php echo SITE_NAME; ?>
                    </h5>
                    <p class="text-muted">
                        <?php echo SITE_TAGLINE; ?>
                    </p>
                    <p class="small text-muted">
                        A modern blogging platform built for the University of Moratuwa 
                        IN2120 Web Programming course project.
                    </p>
                </div>
                
                <!-- Quick Links -->
                <div class="col-md-2">
                    <h6 class="mb-3">Quick Links</h6>
                    <ul class="list-unstyled">
                        <li class="mb-2">
                            <a href="<?php echo SITE_URL; ?>/index.php" class="text-muted text-decoration-none hover-primary">
                                <i class="fas fa-chevron-right me-1"></i> Home
                            </a>
                        </li>
                        <li class="mb-2">
                            <a href="<?php echo SITE_URL; ?>/posts/home.php" class="text-muted text-decoration-none hover-primary">
                                <i class="fas fa-chevron-right me-1"></i> All Blogs
                            </a>
                        </li>
                        <li class="mb-2">
                            <a href="<?php echo SITE_URL; ?>/posts/search.php" class="text-muted text-decoration-none hover-primary">
                                <i class="fas fa-chevron-right me-1"></i> Search
                            </a>
                        </li>
                        <?php if (isLoggedIn()): ?>
                        <li class="mb-2">
                            <a href="<?php echo SITE_URL; ?>/posts/create_blog.php" class="text-muted text-decoration-none hover-primary">
                                <i class="fas fa-chevron-right me-1"></i> Write a Blog
                            </a>
                        </li>
                        <?php endif; ?>
                    </ul>
                </div>
                
                <!-- Categories -->
                <div class="col-md-3">
                    <h6 class="mb-3">Popular Categories</h6>
                    <ul class="list-unstyled">
                        <?php
                        // Fetch top categories
                        try {
                            $categoriesQuery = "SELECT c.*, COUNT(bp.id) as post_count 
                                               FROM categories c 
                                               LEFT JOIN blogPost bp ON c.id = bp.category 
                                               GROUP BY c.id 
                                               ORDER BY post_count DESC 
                                               LIMIT 5";
                            $categories = fetchAll($categoriesQuery);
                            
                            foreach ($categories as $cat):
                        ?>
                        <li class="mb-2">
                            <a href="<?php echo SITE_URL; ?>/posts/home.php?category=<?php echo e($cat['id']); ?>" 
                               class="text-muted text-decoration-none hover-primary">
                                <i class="fas fa-tag me-1"></i> <?php echo e($cat['name']); ?>
                                <span class="badge bg-secondary ms-1"><?php echo $cat['post_count']; ?></span>
                            </a>
                        </li>
                        <?php 
                            endforeach;
                        } catch (Exception $e) {
                            // Silent fail - no categories shown
                        }
                        ?>
                    </ul>
                </div>
                
                <!-- Contact & Social -->
                <div class="col-md-3">
                    <h6 class="mb-3">Connect With Us</h6>
                    <div class="mb-3">
                        <p class="text-muted small mb-2">
                            <i class="fas fa-envelope me-2"></i>
                            <a href="mailto:<?php echo ADMIN_EMAIL; ?>" class="text-muted text-decoration-none">
                                <?php echo ADMIN_EMAIL; ?>
                            </a>
                        </p>
                        <p class="text-muted small mb-2">
                            <i class="fas fa-university me-2"></i>
                            University of Moratuwa
                        </p>
                    </div>
                    
                    <!-- Social Media Links -->
                    <div class="social-links">
                        <a href="<?php echo FACEBOOK_URL; ?>" class="btn btn-outline-light btn-sm me-2 rounded-circle" 
                           target="_blank" title="Facebook">
                            <i class="fab fa-facebook-f"></i>
                        </a>
                        <a href="<?php echo GITHUB_URL; ?>" class="btn btn-outline-light btn-sm me-2 rounded-circle" 
                        target="_blank" title="GitHub">
                            <i class="fab fa-github"></i>
                        </a>
                        <a href="<?php echo INSTAGRAM_URL; ?>" class="btn btn-outline-light btn-sm me-2 rounded-circle" 
                           target="_blank" title="Instagram">
                            <i class="fab fa-instagram"></i>
                        </a>
                        <a href="<?php echo LINKEDIN_URL; ?>" class="btn btn-outline-light btn-sm rounded-circle" 
                           target="_blank" title="LinkedIn">
                            <i class="fab fa-linkedin-in"></i>
                        </a>
                    </div>
                </div>
            </div>
            
            <!-- Divider -->
            <hr class="my-4 bg-secondary">
            
            <!-- Bottom Footer -->
            <div class="row">
                <div class="col-md-6 text-center text-md-start">
                    <p class="small text-muted mb-0">
                        &copy; <?php echo date('Y'); ?> <?php echo SITE_NAME; ?>. All rights reserved.
                    </p>
                </div>
                <div class="col-md-6 text-center text-md-end">
                    <p class="small text-muted mb-0">
                        Made with <i class="fas fa-heart text-danger"></i> by 
                        <span class="text-primary">Blog Hut Team</span>
                    </p>
                </div>
            </div>
        </div>
    </footer>
    
    <!-- Scroll to Top Button -->
    <button class="scroll-to-top" id="scrollToTop" title="Scroll to top">
        <i class="fas fa-arrow-up"></i>
    </button>
    
    <!-- Bootstrap 5 JS Bundle -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    
    <!-- AOS (Animate On Scroll) JS -->
    <script src="https://unpkg.com/aos@2.3.1/dist/aos.js"></script>
    
    <!-- Main JavaScript -->
    <script src="<?php echo JS_URL; ?>/main.js"></script>
    
    <!-- Custom JavaScript for specific pages -->
    <?php if (isset($customJS)) echo $customJS; ?>
    
    <!-- Initialize AOS -->
    <script>
        AOS.init({
            duration: 800,
            easing: 'ease-in-out',
            once: true,
            offset: 100
        });
    </script>
    
    <!-- Scroll to Top Script -->
    <script>
        const scrollToTopBtn = document.getElementById('scrollToTop');
        
        // Show/hide scroll to top button
        window.addEventListener('scroll', function() {
            if (window.pageYOffset > 300) {
                scrollToTopBtn.classList.add('show');
            } else {
                scrollToTopBtn.classList.remove('show');
            }
        });
        
        // Scroll to top on click
        scrollToTopBtn.addEventListener('click', function() {
            window.scrollTo({
                top: 0,
                behavior: 'smooth'
            });
        });
    </script>
    
    <!-- Fix Navigation Links -->
    <script>
        // Ensure navigation links work correctly
        document.addEventListener('DOMContentLoaded', function() {
            const navLinks = document.querySelectorAll('.navbar-brand, .nav-link');
            navLinks.forEach(function(link) {
                link.addEventListener('click', function(e) {
                    // Allow normal link behavior
                    console.log('Navigation clicked:', this.href);
                    return true;
                });
            });
        });
    </script>
    
    <!-- Display Flash Messages with SweetAlert -->
    <?php 
    // Check if there's a flash message to display
    if (hasSession('flash_message')):
        $flash = getSession('flash_message');
        unsetSession('flash_message');
        
        // Map message type to SweetAlert icon
        $iconMap = [
            'success' => 'success',
            'error' => 'error',
            'warning' => 'warning',
            'info' => 'info'
        ];
        $icon = $iconMap[$flash['type']] ?? 'info';
    ?>
    <script>
        Swal.fire({
            icon: '<?php echo $icon; ?>',
            title: '<?php echo $icon === 'error' ? 'Oops!' : ucfirst($icon); ?>',
            text: '<?php echo addslashes($flash['message']); ?>',
            timer: 3000,
            timerProgressBar: true,
            showConfirmButton: false,
            toast: true,
            position: 'top-end'
        });
    </script>
    <?php endif; ?>
    
</body>
</html>