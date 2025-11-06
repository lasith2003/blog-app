/**
 * BLOG HUT - Comments JavaScript (AJAX)
 */

(function() {
    'use strict';

    // ================================================================
    // CONFIGURATION
    // ================================================================
    
    const COMMENTS_API_URL = '../posts/comments_api.php';
    
    // ================================================================
    // COMMENT FORM SUBMISSION
    // ================================================================
    
    const commentForm = document.getElementById('commentForm');
    
    if (commentForm) {
        commentForm.addEventListener('submit', function(e) {
            e.preventDefault();
            
            const formData = new FormData(this);
            const submitBtn = this.querySelector('[type="submit"]');
            const commentTextarea = this.querySelector('[name="comment"]');
            
            // Validate comment
            const comment = commentTextarea.value.trim();
            if (!comment) {
                showToast('Please enter a comment', 'error');
                return;
            }
            
            // Disable submit button
            submitBtn.disabled = true;
            submitBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Posting...';
            
            // Send AJAX request
            fetch(COMMENTS_API_URL, {
                method: 'POST',
                body: formData,
                headers: {
                    'X-Requested-With': 'XMLHttpRequest'
                }
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    // Clear form
                    commentTextarea.value = '';
                    
                    // Add new comment to list
                    addCommentToList(data.comment);
                    
                    // Update comment count
                    updateCommentCount(data.comment_count);
                    
                    // Show success message
                    showToast('Comment posted successfully!', 'success');
                    
                    // Scroll to new comment
                    setTimeout(() => {
                        const newComment = document.querySelector(`[data-comment-id="${data.comment.id}"]`);
                        if (newComment) {
                            newComment.scrollIntoView({ behavior: 'smooth', block: 'center' });
                            newComment.classList.add('highlight');
                            setTimeout(() => newComment.classList.remove('highlight'), 2000);
                        }
                    }, 100);
                } else {
                    showToast(data.message || 'Failed to post comment', 'error');
                }
            })
            .catch(error => {
                console.error('Comment submission error:', error);
                showToast('An error occurred. Please try again.', 'error');
            })
            .finally(() => {
                // Re-enable submit button
                submitBtn.disabled = false;
                submitBtn.innerHTML = '<i class="fas fa-paper-plane me-2"></i> Post Comment';
            });
        });
    }
    
    // ================================================================
    // ADD COMMENT TO LIST
    // ================================================================
    
    function addCommentToList(comment) {
        const commentsList = document.getElementById('commentsList');
        
        if (!commentsList) return;
        
        // Create comment HTML
        const commentHTML = `
            <div class="comment-item card mb-3" data-comment-id="${comment.id}">
                <div class="card-body">
                    <div class="d-flex">
                        <img src="${getAvatarUrl(comment.profile_image)}" 
                             alt="${escapeHtml(comment.username)}"
                             class="rounded-circle me-3"
                             style="width: 40px; height: 40px; object-fit: cover;">
                        <div class="flex-grow-1">
                            <div class="d-flex justify-content-between align-items-start">
                                <div>
                                    <strong>${escapeHtml(comment.username)}</strong>
                                    <small class="text-muted ms-2">
                                        ${comment.time_ago}
                                    </small>
                                </div>
                            </div>
                            <p class="mb-0 mt-2">${escapeHtml(comment.comment)}</p>
                        </div>
                    </div>
                </div>
            </div>
        `;
        
        // Check if there's a "no comments" message
        const noCommentsMsg = commentsList.querySelector('.text-center.py-4');
        if (noCommentsMsg) {
            noCommentsMsg.remove();
        }
        
        // Prepend new comment
        commentsList.insertAdjacentHTML('afterbegin', commentHTML);
    }
    
    // ================================================================
    // UPDATE COMMENT COUNT
    // ================================================================
    
    function updateCommentCount(count) {
        // Update count in heading
        const commentHeading = document.querySelector('.comments-section h4');
        if (commentHeading) {
            commentHeading.innerHTML = `<i class="fas fa-comments me-2"></i>Comments (${count})`;
        }
        
        // Update count in stats if exists
        const statsComment = document.querySelector('.post-stats span:last-child');
        if (statsComment) {
            statsComment.innerHTML = `<i class="fas fa-comment me-1"></i> ${count} comments`;
        }
    }
    
    // ================================================================
    // HELPER FUNCTIONS
    // ================================================================
    
    function getAvatarUrl(profileImage) {
        const baseUrl = window.location.origin + window.location.pathname.replace(/\/[^\/]*$/, '');
        const avatarPath = profileImage || 'default-avatar.png';
        return `${baseUrl}/../uploads/avatars/${avatarPath}`;
    }
    
    function escapeHtml(text) {
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }
    
    function showToast(message, type = 'info') {
        if (typeof Swal !== 'undefined') {
            Swal.fire({
                icon: type === 'error' ? 'error' : type === 'success' ? 'success' : 'info',
                title: message,
                toast: true,
                position: 'top-end',
                showConfirmButton: false,
                timer: 3000,
                timerProgressBar: true
            });
        }
    }
    
    // ================================================================
    // COMMENT CHARACTER COUNTER
    // ================================================================
    
    const commentTextarea = document.querySelector('#commentForm textarea[name="comment"]');
    
    if (commentTextarea) {
        const maxLength = commentTextarea.getAttribute('maxlength') || 1000;
        const counter = document.createElement('small');
        counter.className = 'form-text text-muted d-block text-end mt-1';
        counter.textContent = `0 / ${maxLength}`;
        
        commentTextarea.parentNode.appendChild(counter);
        
        commentTextarea.addEventListener('input', function() {
            const currentLength = this.value.length;
            counter.textContent = `${currentLength} / ${maxLength}`;
            
            if (currentLength >= maxLength * 0.9) {
                counter.classList.remove('text-muted');
                counter.classList.add('text-warning');
            } else {
                counter.classList.add('text-muted');
                counter.classList.remove('text-warning');
            }
        });
    }
    
    // ================================================================
    // LOAD MORE COMMENTS (if implementing pagination)
    // ================================================================
    
    const loadMoreBtn = document.getElementById('loadMoreComments');
    
    if (loadMoreBtn) {
        loadMoreBtn.addEventListener('click', function() {
            const postId = this.dataset.postId;
            const offset = document.querySelectorAll('.comment-item').length;
            
            this.disabled = true;
            this.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Loading...';
            
            fetch(`${COMMENTS_API_URL}?post_id=${postId}&offset=${offset}`, {
                headers: {
                    'X-Requested-With': 'XMLHttpRequest'
                }
            })
            .then(response => response.json())
            .then(data => {
                if (data.success && data.comments.length > 0) {
                    data.comments.forEach(comment => addCommentToList(comment));
                    
                    if (data.comments.length < 10) {
                        this.style.display = 'none';
                    }
                } else {
                    this.style.display = 'none';
                }
            })
            .catch(error => {
                console.error('Load comments error:', error);
                showToast('Failed to load more comments', 'error');
            })
            .finally(() => {
                this.disabled = false;
                this.innerHTML = 'Load More Comments';
            });
        });
    }
    
    // ================================================================
    // HIGHLIGHT ANIMATION
    // ================================================================
    
    const style = document.createElement('style');
    style.textContent = `
        .comment-item.highlight {
            animation: highlightComment 2s ease;
        }
        
        @keyframes highlightComment {
            0%, 100% { background-color: transparent; }
            50% { background-color: rgba(255, 177, 0, 0.2); }
        }
    `;
    document.head.appendChild(style);
    
    console.log('Comments JS initialized');

})();