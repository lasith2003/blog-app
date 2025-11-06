<?php
/**
 * BLOG HUT - Post Functions
 */

// Include required files
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/constants.php';
require_once __DIR__ . '/helper.php';

// ================================================================
// FETCH POSTS FUNCTIONS
// ================================================================

/**
 * Get all published posts with pagination
 * 
 * @param int $page Current page number
 * @param int $perPage Posts per page
 * @param int|null $categoryId Filter by category
 * @param string|null $searchTerm Search term
 * @return array Posts array
 */
function getAllPosts($page = 1, $perPage = POSTS_PER_PAGE, $categoryId = null, $searchTerm = null) {
    $offset = ($page - 1) * $perPage;
    
    $sql = "SELECT bp.*, u.username, u.profile_image, c.name as category_name, c.slug as category_slug,
            (SELECT COUNT(*) FROM reactions WHERE blog_id = bp.id) as reaction_count,
            (SELECT COUNT(*) FROM comments WHERE blog_id = bp.id) as comment_count
            FROM blogPost bp
            JOIN users u ON bp.user_id = u.id
            LEFT JOIN categories c ON bp.category = c.id
            WHERE bp.status = 'published'";
    
    $params = [];
    
    // Add category filter
    if ($categoryId) {
        $sql .= " AND bp.category = ?";
        $params[] = $categoryId;
    }
    
    // Add search filter
    if ($searchTerm) {
        $sql .= " AND (bp.title LIKE ? OR bp.content LIKE ? OR bp.summary LIKE ?)";
        $searchParam = "%$searchTerm%";
        $params[] = $searchParam;
        $params[] = $searchParam;
        $params[] = $searchParam;
    }
    
    $sql .= " ORDER BY bp.created_at DESC LIMIT ? OFFSET ?";
    $params[] = $perPage;
    $params[] = $offset;
    
    return fetchAll($sql, $params);
}

/**
 * Get total post count
 * 
 * @param int|null $categoryId Filter by category
 * @param string|null $searchTerm Search term
 * @return int Total count
 */
function getTotalPostCount($categoryId = null, $searchTerm = null) {
    $sql = "SELECT COUNT(*) as total FROM blogPost WHERE status = 'published'";
    $params = [];
    
    if ($categoryId) {
        $sql .= " AND category = ?";
        $params[] = $categoryId;
    }
    
    if ($searchTerm) {
        $sql .= " AND (title LIKE ? OR content LIKE ? OR summary LIKE ?)";
        $searchParam = "%$searchTerm%";
        $params[] = $searchParam;
        $params[] = $searchParam;
        $params[] = $searchParam;
    }
    
    $result = fetchOne($sql, $params);
    return $result['total'] ?? 0;
}

/**
 * Get single post by ID with full details
 * 
 * @param int $postId Post ID
 * @return array|false Post data or false
 */
function getPostById($postId) {
    $sql = "SELECT bp.*, u.username, u.profile_image, u.bio, c.name as category_name, c.slug as category_slug,
            (SELECT COUNT(*) FROM reactions WHERE blog_id = bp.id) as reaction_count,
            (SELECT COUNT(*) FROM comments WHERE blog_id = bp.id) as comment_count
            FROM blogPost bp
            JOIN users u ON bp.user_id = u.id
            LEFT JOIN categories c ON bp.category = c.id
            WHERE bp.id = ?";
    
    return fetchOne($sql, [$postId]);
}

/**
 * Get posts by user ID
 * 
 * @param int $userId User ID
 * @param int $page Current page
 * @param int $perPage Posts per page
 * @param bool $includeAll Include drafts (for owner view)
 * @return array Posts array
 */
function getPostsByUser($userId, $page = 1, $perPage = USER_POSTS_PER_PAGE, $includeAll = false) {
    $offset = ($page - 1) * $perPage;
    
    $sql = "SELECT bp.*, c.name as category_name,
            (SELECT COUNT(*) FROM reactions WHERE blog_id = bp.id) as reaction_count,
            (SELECT COUNT(*) FROM comments WHERE blog_id = bp.id) as comment_count
            FROM blogPost bp
            LEFT JOIN categories c ON bp.category = c.id
            WHERE bp.user_id = ?";
    
    if (!$includeAll) {
        $sql .= " AND bp.status = 'published'";
    }
    
    $sql .= " ORDER BY bp.created_at DESC LIMIT ? OFFSET ?";
    
    return fetchAll($sql, [$userId, $perPage, $offset]);
}

/**
 * Get trending/popular posts
 * 
 * @param int $limit Number of posts
 * @param int $days Consider posts from last X days
 * @return array Posts array
 */
function getTrendingPosts($limit = 5, $days = 7) {
    $sql = "SELECT bp.*, u.username, u.profile_image,
            (SELECT COUNT(*) FROM reactions WHERE blog_id = bp.id) as reaction_count,
            (SELECT COUNT(*) FROM comments WHERE blog_id = bp.id) as comment_count,
            (bp.views + (SELECT COUNT(*) FROM reactions WHERE blog_id = bp.id) * 2 + 
             (SELECT COUNT(*) FROM comments WHERE blog_id = bp.id) * 3) as popularity_score
            FROM blogPost bp
            JOIN users u ON bp.user_id = u.id
            WHERE bp.status = 'published' 
            AND bp.created_at >= DATE_SUB(NOW(), INTERVAL ? DAY)
            ORDER BY popularity_score DESC
            LIMIT ?";
    
    return fetchAll($sql, [$days, $limit]);
}

/**
 * Get latest posts
 * 
 * @param int $limit Number of posts
 * @return array Posts array
 */
function getLatestPosts($limit = 5) {
    $sql = "SELECT bp.*, u.username, u.profile_image
            FROM blogPost bp
            JOIN users u ON bp.user_id = u.id
            WHERE bp.status = 'published'
            ORDER BY bp.created_at DESC
            LIMIT ?";
    
    return fetchAll($sql, [$limit]);
}

// ================================================================
// POST CRUD OPERATIONS
// ================================================================

/**
 * Create new blog post
 * 
 * @param array $data Post data
 * @return int|false New post ID or false on failure
 */
function createPost($data) {
    try {
        $sql = "INSERT INTO blogPost (user_id, title, content, summary, category, featured_image, status) 
                VALUES (?, ?, ?, ?, ?, ?, ?)";
        
        $params = [
            $data['user_id'],
            $data['title'],
            $data['content'],
            $data['summary'] ?? null,
            $data['category'] ?? null,
            $data['featured_image'] ?? null,
            $data['status'] ?? 'published'
        ];
        
        return insertRecord($sql, $params);
    } catch (Exception $e) {
        error_log("Create Post Error: " . $e->getMessage());
        return false;
    }
}

/**
 * Update existing blog post
 * 
 * @param int $postId Post ID
 * @param array $data Updated post data
 * @return bool Success status
 */
function updatePost($postId, $data) {
    try {
        $sql = "UPDATE blogPost 
                SET title = ?, content = ?, summary = ?, category = ?, 
                    featured_image = ?, status = ?, updated_at = NOW()
                WHERE id = ?";
        
        $params = [
            $data['title'],
            $data['content'],
            $data['summary'] ?? null,
            $data['category'] ?? null,
            $data['featured_image'] ?? null,
            $data['status'] ?? 'published',
            $postId
        ];
        
        return updateRecord($sql, $params) > 0;
    } catch (Exception $e) {
        error_log("Update Post Error: " . $e->getMessage());
        return false;
    }
}

/**
 * Delete blog post
 * 
 * @param int $postId Post ID
 * @return bool Success status
 */
function deletePost($postId) {
    try {
        $sql = "DELETE FROM blogPost WHERE id = ?";
        return updateRecord($sql, [$postId]) > 0;
    } catch (Exception $e) {
        error_log("Delete Post Error: " . $e->getMessage());
        return false;
    }
}

/**
 * Check if user owns the post
 * 
 * @param int $postId Post ID
 * @param int $userId User ID
 * @return bool
 */
function isPostOwner($postId, $userId) {
    $sql = "SELECT id FROM blogPost WHERE id = ? AND user_id = ?";
    return recordExists($sql, [$postId, $userId]);
}

/**
 * Increment post views
 * 
 * @param int $postId Post ID
 * @return bool Success status
 */
function incrementPostViews($postId) {
    try {
        $sql = "UPDATE blogPost SET views = views + 1 WHERE id = ?";
        return updateRecord($sql, [$postId]) > 0;
    } catch (Exception $e) {
        return false;
    }
}

// ================================================================
// CATEGORY FUNCTIONS
// ================================================================

/**
 * Get all categories
 * 
 * @return array Categories array
 */
function getAllCategories() {
    $sql = "SELECT * FROM categories ORDER BY name ASC";
    return fetchAll($sql);
}

/**
 * Get category by ID
 * 
 * @param int $categoryId Category ID
 * @return array|false Category data
 */
function getCategoryById($categoryId) {
    $sql = "SELECT * FROM categories WHERE id = ?";
    return fetchOne($sql, [$categoryId]);
}

/**
 * Get category by slug
 * 
 * @param string $slug Category slug
 * @return array|false Category data
 */
function getCategoryBySlug($slug) {
    $sql = "SELECT * FROM categories WHERE slug = ?";
    return fetchOne($sql, [$slug]);
}

/**
 * Get posts count by category
 * 
 * @param int $categoryId Category ID
 * @return int Post count
 */
function getCategoryPostCount($categoryId) {
    $sql = "SELECT COUNT(*) as total FROM blogPost WHERE category = ? AND status = 'published'";
    $result = fetchOne($sql, [$categoryId]);
    return $result['total'] ?? 0;
}

// ================================================================
// REACTION FUNCTIONS
// ================================================================

/**
 * Get user's reaction to a post
 * 
 * @param int $postId Post ID
 * @param int $userId User ID
 * @return array|false Reaction data
 */
function getUserReaction($postId, $userId) {
    $sql = "SELECT * FROM reactions WHERE blog_id = ? AND user_id = ?";
    return fetchOne($sql, [$postId, $userId]);
}

/**
 * Add or update reaction
 * 
 * @param int $postId Post ID
 * @param int $userId User ID
 * @param string $type Reaction type
 * @return bool Success status
 */
function addReaction($postId, $userId, $type) {
    try {
        // Check if reaction exists
        $existing = getUserReaction($postId, $userId);
        
        if ($existing) {
            // Update existing reaction
            $sql = "UPDATE reactions SET type = ? WHERE blog_id = ? AND user_id = ?";
            return updateRecord($sql, [$type, $postId, $userId]) > 0;
        } else {
            // Insert new reaction
            $sql = "INSERT INTO reactions (blog_id, user_id, type) VALUES (?, ?, ?)";
            return insertRecord($sql, [$postId, $userId, $type]) > 0;
        }
    } catch (Exception $e) {
        error_log("Add Reaction Error: " . $e->getMessage());
        return false;
    }
}

/**
 * Remove reaction
 * 
 * @param int $postId Post ID
 * @param int $userId User ID
 * @return bool Success status
 */
function removeReaction($postId, $userId) {
    try {
        $sql = "DELETE FROM reactions WHERE blog_id = ? AND user_id = ?";
        return updateRecord($sql, [$postId, $userId]) > 0;
    } catch (Exception $e) {
        return false;
    }
}

/**
 * Get reaction counts by type for a post
 * 
 * @param int $postId Post ID
 * @return array Reaction counts
 */
function getReactionCounts($postId) {
    $sql = "SELECT type, COUNT(*) as count 
            FROM reactions 
            WHERE blog_id = ? 
            GROUP BY type";
    
    $results = fetchAll($sql, [$postId]);
    
    // Format as associative array
    $counts = [];
    foreach ($results as $row) {
        $counts[$row['type']] = $row['count'];
    }
    
    return $counts;
}

/**
 * Validate reaction type
 * 
 * @param string $type Reaction type
 * @return bool
 */
function isValidReaction($type) {
    $allowedTypes = ['like', 'dislike'];
    return in_array($type, $allowedTypes);
}

// ================================================================
// COMMENT FUNCTIONS
// ================================================================

/**
 * Get comments for a post
 * 
 * @param int $postId Post ID
 * @param int|null $parentId Parent comment ID (for replies)
 * @return array Comments array
 */
function getPostComments($postId, $parentId = null) {
    $sql = "SELECT c.*, u.username, u.profile_image 
            FROM comments c
            JOIN users u ON c.user_id = u.id
            WHERE c.blog_id = ?";
    
    $params = [$postId];
    
    if ($parentId === null) {
        $sql .= " AND c.parent_comment_id IS NULL";
    } else {
        $sql .= " AND c.parent_comment_id = ?";
        $params[] = $parentId;
    }
    
    $sql .= " ORDER BY c.created_at DESC";
    
    return fetchAll($sql, $params);
}

/**
 * Get comment count for a post
 * 
 * @param int $postId Post ID
 * @return int Comment count
 */
function getCommentCount($postId) {
    $sql = "SELECT COUNT(*) as total FROM comments WHERE blog_id = ?";
    $result = fetchOne($sql, [$postId]);
    return $result['total'] ?? 0;
}

// ================================================================
// USER STATISTICS
// ================================================================

/**
 * Get user post statistics
 * 
 * @param int $userId User ID
 * @return array Statistics
 */
function getUserPostStats($userId) {
    $sql = "SELECT 
            COUNT(*) as total_posts,
            SUM(views) as total_views,
            (SELECT COUNT(*) FROM reactions r 
             JOIN blogPost bp ON r.blog_id = bp.id 
             WHERE bp.user_id = ?) as total_reactions,
            (SELECT COUNT(*) FROM comments c 
             JOIN blogPost bp ON c.blog_id = bp.id 
             WHERE bp.user_id = ?) as total_comments
            FROM blogPost 
            WHERE user_id = ? AND status = 'published'";
    
    return fetchOne($sql, [$userId, $userId, $userId]);
}

/**
 * Get featured/top post for a user
 * 
 * @param int $userId User ID
 * @return array|false Post data
 */
function getUserTopPost($userId) {
    $sql = "SELECT bp.*, 
            (SELECT COUNT(*) FROM reactions WHERE blog_id = bp.id) as reaction_count,
            (SELECT COUNT(*) FROM comments WHERE blog_id = bp.id) as comment_count
            FROM blogPost bp
            WHERE bp.user_id = ? AND bp.status = 'published'
            ORDER BY bp.views DESC, reaction_count DESC
            LIMIT 1";
    
    return fetchOne($sql, [$userId]);
}

?>