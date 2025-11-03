/**
 * ================================================================
 * BLOG HUT - Reactions JavaScript (AJAX)
 * University of Moratuwa - IN2120 Web Programming Project
 * ================================================================
 * 
 * Handles:
 * - Add/remove reactions via AJAX
 * - Real-time reaction updates
 * - Reaction count updates
 * - Visual feedback
 */

(function() {
    'use strict';

    // ================================================================
    // CONFIGURATION
    // ================================================================
    
    const REACTIONS_API_URL = '../posts/reactions_api.php';
    
    // ================================================================
    // REACTION BUTTON CLICK HANDLER
    // ================================================================
    
    const reactionButtons = document.querySelectorAll('.reaction-btn');
    
    reactionButtons.forEach(function(btn) {
        btn.addEventListener('click', function(e) {
            e.preventDefault();
            
            const reactionType = this.dataset.reaction;
            const postId = this.dataset.postId;
            const isActive = this.classList.contains('active');
            
            // Optimistic UI update
            if (isActive) {
                // Remove reaction
                removeReaction(postId, this);
            } else {
                // Add/change reaction
                addReaction(postId, reactionType, this);
            }
        });
    });
    
    // ================================================================
    // ADD REACTION
    // ================================================================
    
    function addReaction(postId, reactionType, button) {
        // Remove active class from all reaction buttons
        const allButtons = document.querySelectorAll('.reaction-btn');
        allButtons.forEach(btn => btn.classList.remove('active'));
        
        // Add active class to clicked button
        button.classList.add('active');
        
        // Prepare form data
        const formData = new FormData();
        formData.append('post_id', postId);
        formData.append('type', reactionType);
        
        // Send AJAX request
        fetch(REACTIONS_API_URL, {
            method: 'POST',
            body: formData,
            headers: {
                'X-Requested-With': 'XMLHttpRequest'
            }
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                // Update all reaction counts
                updateReactionCounts(data.counts);
                
                // Update total count if exists
                updateTotalReactionCount(data.total);
                
                // Add animation
                button.classList.add('reaction-pulse');
                setTimeout(() => button.classList.remove('reaction-pulse'), 600);
            } else {
                // Revert on error
                button.classList.remove('active');
                showToast(data.message || 'Failed to add reaction', 'error');
            }
        })
        .catch(error => {
            console.error('Reaction error:', error);
            button.classList.remove('active');
            showToast('An error occurred. Please try again.', 'error');
        });
    }
    
    // ================================================================
    // REMOVE REACTION
    // ================================================================
    
    function removeReaction(postId, button) {
        // Remove active class
        button.classList.remove('active');
        
        // Prepare form data
        const formData = new FormData();
        formData.append('post_id', postId);
        formData.append('_method', 'DELETE');
        
        // Send AJAX request
        fetch(REACTIONS_API_URL, {
            method: 'POST',
            body: formData,
            headers: {
                'X-Requested-With': 'XMLHttpRequest'
            }
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                // Update all reaction counts
                updateReactionCounts(data.counts);
                
                // Update total count
                updateTotalReactionCount(data.total);
            } else {
                // Revert on error
                button.classList.add('active');
                showToast(data.message || 'Failed to remove reaction', 'error');
            }
        })
        .catch(error => {
            console.error('Reaction error:', error);
            button.classList.add('active');
            showToast('An error occurred. Please try again.', 'error');
        });
    }
    
    // ================================================================
    // UPDATE REACTION COUNTS
    // ================================================================
    
    function updateReactionCounts(counts) {
        const reactionButtons = document.querySelectorAll('.reaction-btn');
        
        reactionButtons.forEach(function(btn) {
            const reactionType = btn.dataset.reaction;
            const countSpan = btn.querySelector('.reaction-count');
            
            if (countSpan && counts[reactionType] !== undefined) {
                const count = counts[reactionType];
                countSpan.textContent = count;
                
                // Update button visibility based on count
                if (count > 0 || btn.classList.contains('active')) {
                    btn.style.opacity = '1';
                } else {
                    btn.style.opacity = '0.7';
                }
            }
        });
    }
    
    // ================================================================
    // UPDATE TOTAL REACTION COUNT
    // ================================================================
    
    function updateTotalReactionCount(total) {
        // Update in post stats if exists
        const statsReaction = document.querySelector('.post-stats span:nth-child(2)');
        if (statsReaction) {
            statsReaction.innerHTML = `<i class="fas fa-heart me-1"></i> ${total} reactions`;
        }
        
        // Update in sidebar if exists
        const sidebarReaction = document.querySelector('.post-meta .fa-heart');
        if (sidebarReaction && sidebarReaction.parentNode) {
            sidebarReaction.parentNode.innerHTML = `<i class="fas fa-heart me-1"></i> ${total}`;
        }
    }
    
    // ================================================================
    // HELPER FUNCTIONS
    // ================================================================
    
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
    // REACTION HOVER EFFECT
    // ================================================================
    
    reactionButtons.forEach(function(btn) {
        btn.addEventListener('mouseenter', function() {
            if (!this.classList.contains('active')) {
                this.style.transform = 'scale(1.1)';
            }
        });
        
        btn.addEventListener('mouseleave', function() {
            this.style.transform = 'scale(1)';
        });
    });
    
    // ================================================================
    // REACTION PULSE ANIMATION
    // ================================================================
    
    const style = document.createElement('style');
    style.textContent = `
        .reaction-btn {
            transition: all 0.3s ease;
        }
        
        .reaction-btn.reaction-pulse {
            animation: reactionPulse 0.6s ease;
        }
        
        @keyframes reactionPulse {
            0% { transform: scale(1); }
            50% { transform: scale(1.3); }
            100% { transform: scale(1); }
        }
        
        .reaction-btn.active {
            transform: scale(1.05);
        }
    `;
    document.head.appendChild(style);
    
    // ================================================================
    // KEYBOARD SHORTCUTS (Optional)
    // ================================================================
    
    document.addEventListener('keydown', function(e) {
        // Only if not in an input field
        if (e.target.tagName === 'INPUT' || e.target.tagName === 'TEXTAREA') {
            return;
        }
        
        // Number keys 1-5 for reactions
        const keyMap = {
            '1': 'like',
            '2': 'love',
            '3': 'wow',
            '4': 'sad',
            '5': 'angry'
        };
        
        if (keyMap[e.key]) {
            e.preventDefault();
            const reactionType = keyMap[e.key];
            const button = document.querySelector(`.reaction-btn[data-reaction="${reactionType}"]`);
            
            if (button) {
                button.click();
            }
        }
    });
    
    // ================================================================
    // LOAD CURRENT USER'S REACTION (on page load)
    // ================================================================
    
    function loadUserReaction() {
        const firstReactionBtn = document.querySelector('.reaction-btn');
        
        if (!firstReactionBtn) return;
        
        const postId = firstReactionBtn.dataset.postId;
        
        fetch(`${REACTIONS_API_URL}?post_id=${postId}`, {
            headers: {
                'X-Requested-With': 'XMLHttpRequest'
            }
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                // Update counts
                updateReactionCounts(data.counts);
                
                // Mark user's reaction as active
                if (data.user_reaction) {
                    const userReactionBtn = document.querySelector(`.reaction-btn[data-reaction="${data.user_reaction}"]`);
                    if (userReactionBtn) {
                        userReactionBtn.classList.add('active');
                    }
                }
            }
        })
        .catch(error => {
            console.error('Load reaction error:', error);
        });
    }
    
    // Load user's current reaction on page load
    if (reactionButtons.length > 0) {
        loadUserReaction();
    }
    
    // ================================================================
    // REACTION TOOLTIPS
    // ================================================================
    
    const reactionTooltips = {
        'like': 'Like this post',
        'love': 'Love this post',
        'wow': 'Wow!',
        'sad': 'Sad',
        'angry': 'Angry'
    };
    
    reactionButtons.forEach(function(btn) {
        const reactionType = btn.dataset.reaction;
        if (reactionTooltips[reactionType]) {
            btn.setAttribute('title', reactionTooltips[reactionType]);
            btn.setAttribute('data-bs-toggle', 'tooltip');
            
            // Initialize Bootstrap tooltip if available
            if (typeof bootstrap !== 'undefined' && bootstrap.Tooltip) {
                new bootstrap.Tooltip(btn);
            }
        }
    });
    
    console.log('Reactions JS initialized');

})();