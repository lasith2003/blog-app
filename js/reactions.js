/**
 * Reactions JavaScript - Like/Dislike Only
 */
(function() {
    'use strict';

    const REACTIONS_API_URL = '../posts/reactions_api.php';
    const reactionButtons = document.querySelectorAll('.reaction-btn');
    
    // Handle reaction button clicks
    reactionButtons.forEach(function(btn) {
        btn.addEventListener('click', function(e) {
            e.preventDefault();
            
            const reactionType = this.dataset.reaction;
            const postId = this.dataset.postId;
            const isActive = this.classList.contains('active');
            
            handleReaction(postId, reactionType, this);
        });
    });
    
    // Handle reaction
    function handleReaction(postId, reactionType, button) {
        const formData = new FormData();
        formData.append('post_id', postId);
        formData.append('type', reactionType);
        
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
                updateReactionUI(postId, data.counts, data.user_reaction);
                updateTotalReactionCount(data.total);
                
                // Animation
                button.classList.add('reaction-pulse');
                setTimeout(() => button.classList.remove('reaction-pulse'), 600);
            } else {
                showToast(data.message || 'Failed to add reaction', 'error');
            }
        })
        .catch(error => {
            console.error('Reaction error:', error);
            showToast('An error occurred. Please try again.', 'error');
        });
    }
    
    // Update reaction UI
    function updateReactionUI(postId, counts, userReaction) {
        const likeBtn = document.querySelector('.reaction-btn[data-reaction="like"]');
        const dislikeBtn = document.querySelector('.reaction-btn[data-reaction="dislike"]');
        
        if (!likeBtn || !dislikeBtn) return;
        
        // Update counts
        const likeCount = likeBtn.querySelector('.reaction-count');
        const dislikeCount = dislikeBtn.querySelector('.reaction-count');
        
        if (likeCount) likeCount.textContent = counts.like || 0;
        if (dislikeCount) dislikeCount.textContent = counts.dislike || 0;
        
        // Remove active class from both
        likeBtn.classList.remove('active');
        dislikeBtn.classList.remove('active');
        
        // Add active class to user's reaction
        if (userReaction === 'like') {
            likeBtn.classList.add('active');
        } else if (userReaction === 'dislike') {
            dislikeBtn.classList.add('active');
        }
    }
    
    // Update total reaction count
    function updateTotalReactionCount(total) {
        const statsReaction = document.querySelector('.post-stats span:nth-child(2)');
        if (statsReaction) {
            statsReaction.innerHTML = `<i class="fas fa-heart me-1"></i> ${total} reactions`;
        }
    }
    
    // Show toast notification
    function showToast(message, type = 'info') {
        if (typeof Swal !== 'undefined') {
            Swal.fire({
                icon: type === 'error' ? 'error' : 'success',
                title: message,
                toast: true,
                position: 'top-end',
                showConfirmButton: false,
                timer: 2000,
                timerProgressBar: true
            });
        }
    }
    
    // Add animation styles
    const style = document.createElement('style');
    style.textContent = `
        .reaction-btn {
            transition: all 0.3s ease;
        }
        .reaction-btn.reaction-pulse {
            animation: reactionPulse 0.6s ease;
        }
        @keyframes reactionPulse {
            0%, 100% { transform: scale(1); }
            50% { transform: scale(1.3); }
        }
        .reaction-btn.active {
            transform: scale(1.05);
        }
    `;
    document.head.appendChild(style);
    
    // Load initial reaction state
    function loadUserReaction() {
        const firstReactionBtn = document.querySelector('.reaction-btn');
        if (!firstReactionBtn) return;
        
        const postId = firstReactionBtn.dataset.postId;
        
        fetch(`${REACTIONS_API_URL}?post_id=${postId}`, {
            headers: { 'X-Requested-With': 'XMLHttpRequest' }
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                updateReactionUI(postId, data.counts, data.user_reaction);
            }
        })
        .catch(error => console.error('Load reaction error:', error));
    }
    
    if (reactionButtons.length > 0) {
        loadUserReaction();
    }

})();