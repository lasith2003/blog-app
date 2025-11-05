/**
 * BLOG HUT - Editor JavaScript
 */

(function() {
    'use strict';

    // ================================================================
    // CONFIGURATION
    // ================================================================
    
    const contentTextarea = document.getElementById('content');
    const AUTO_SAVE_INTERVAL = 30000; // 30 seconds
    
    // ================================================================
    // TEXT FORMATTING
    // ================================================================
    
    window.formatText = function(format) {
        if (!contentTextarea) return;
        
        const start = contentTextarea.selectionStart;
        const end = contentTextarea.selectionEnd;
        const selectedText = contentTextarea.value.substring(start, end);
        let replacement = '';
        
        if (!selectedText) {
            alert('Please select some text first');
            return;
        }
        
        switch(format) {
            case 'bold':
                replacement = `<strong>${selectedText}</strong>`;
                break;
            case 'italic':
                replacement = `<em>${selectedText}</em>`;
                break;
            case 'underline':
                replacement = `<u>${selectedText}</u>`;
                break;
            case 'h2':
                replacement = `<h2>${selectedText}</h2>`;
                break;
            case 'h3':
                replacement = `<h3>${selectedText}</h3>`;
                break;
            case 'link':
                const url = prompt('Enter URL:');
                if (url) {
                    replacement = `<a href="${url}">${selectedText}</a>`;
                } else {
                    return;
                }
                break;
            case 'quote':
                replacement = `<blockquote>${selectedText}</blockquote>`;
                break;
            case 'code':
                replacement = `<code>${selectedText}</code>`;
                break;
            case 'ul':
                const items = selectedText.split('\n').filter(item => item.trim());
                replacement = '<ul>\n' + items.map(item => `  <li>${item.trim()}</li>`).join('\n') + '\n</ul>';
                break;
            case 'ol':
                const numberedItems = selectedText.split('\n').filter(item => item.trim());
                replacement = '<ol>\n' + numberedItems.map(item => `  <li>${item.trim()}</li>`).join('\n') + '\n</ol>';
                break;
            default:
                return;
        }
        
        // Replace selected text
        contentTextarea.value = contentTextarea.value.substring(0, start) + 
                               replacement + 
                               contentTextarea.value.substring(end);
        
        // Set cursor position after insertion
        const newPosition = start + replacement.length;
        contentTextarea.setSelectionRange(newPosition, newPosition);
        contentTextarea.focus();
        
        // Trigger change event for auto-save
        contentTextarea.dispatchEvent(new Event('input'));
    };
    
    // ================================================================
    // WORD COUNT
    // ================================================================
    
    if (contentTextarea) {
        const wordCountElement = document.createElement('div');
        wordCountElement.className = 'word-count text-muted small mt-2';
        wordCountElement.innerHTML = 'Words: <strong>0</strong> | Characters: <strong>0</strong>';
        contentTextarea.parentNode.insertBefore(wordCountElement, contentTextarea.nextSibling);
        
        function updateWordCount() {
            const text = contentTextarea.value;
            const stripped = text.replace(/<[^>]*>/g, '').trim(); // Remove HTML tags
            const words = stripped ? stripped.split(/\s+/).length : 0;
            const chars = stripped.length;
            
            wordCountElement.innerHTML = `Words: <strong>${words}</strong> | Characters: <strong>${chars}</strong>`;
            
            // Show warning if approaching limits
            if (chars > 45000) {
                wordCountElement.classList.add('text-warning');
            } else {
                wordCountElement.classList.remove('text-warning');
            }
        }
        
        contentTextarea.addEventListener('input', updateWordCount);
        updateWordCount(); // Initial count
    }
    
    // ================================================================
    // AUTO-SAVE DRAFT (LocalStorage)
    // ================================================================
    
    let autoSaveTimeout;
    
    if (contentTextarea) {
        const titleInput = document.getElementById('title');
        const summaryTextarea = document.getElementById('summary');
        const postId = new URLSearchParams(window.location.search).get('id') || 'new';
        const draftKey = `blog_draft_${postId}`;
        
        // Load saved draft on page load
        function loadDraft() {
            const saved = localStorage.getItem(draftKey);
            if (saved && confirm('A saved draft was found. Do you want to restore it?')) {
                try {
                    const draft = JSON.parse(saved);
                    if (titleInput && draft.title) titleInput.value = draft.title;
                    if (summaryTextarea && draft.summary) summaryTextarea.value = draft.summary;
                    if (contentTextarea && draft.content) contentTextarea.value = draft.content;
                    
                    showAutoSaveMessage('Draft restored', 'success');
                } catch (e) {
                    console.error('Error loading draft:', e);
                }
            }
        }
        
        // Save draft
        function saveDraft() {
            const draft = {
                title: titleInput ? titleInput.value : '',
                summary: summaryTextarea ? summaryTextarea.value : '',
                content: contentTextarea.value,
                timestamp: Date.now()
            };
            
            try {
                localStorage.setItem(draftKey, JSON.stringify(draft));
                showAutoSaveMessage('Draft saved', 'success');
            } catch (e) {
                console.error('Error saving draft:', e);
                showAutoSaveMessage('Failed to save draft', 'error');
            }
        }
        
        // Auto-save on input
        function scheduleAutoSave() {
            clearTimeout(autoSaveTimeout);
            autoSaveTimeout = setTimeout(saveDraft, AUTO_SAVE_INTERVAL);
        }
        
        [titleInput, summaryTextarea, contentTextarea].forEach(element => {
            if (element) {
                element.addEventListener('input', scheduleAutoSave);
            }
        });
        
        // Clear draft on successful submission
        const form = document.getElementById('createPostForm') || document.getElementById('editPostForm');
        if (form) {
            form.addEventListener('submit', function() {
                localStorage.removeItem(draftKey);
            });
        }
        
        // Load draft on page load (only for new posts)
        if (postId === 'new') {
            loadDraft();
        }
    }
    
    // ================================================================
    // AUTO-SAVE MESSAGE
    // ================================================================
    
    function showAutoSaveMessage(message, type) {
        let messageEl = document.getElementById('autoSaveMessage');
        
        if (!messageEl) {
            messageEl = document.createElement('div');
            messageEl.id = 'autoSaveMessage';
            messageEl.className = 'alert alert-dismissible fade show position-fixed';
            messageEl.style.cssText = 'top: 80px; right: 20px; z-index: 9999; min-width: 200px;';
            document.body.appendChild(messageEl);
        }
        
        messageEl.className = `alert alert-${type === 'error' ? 'danger' : 'success'} alert-dismissible fade show position-fixed`;
        messageEl.innerHTML = `
            <i class="fas fa-${type === 'error' ? 'exclamation-circle' : 'check-circle'} me-2"></i>
            ${message}
        `;
        
        messageEl.style.display = 'block';
        
        setTimeout(() => {
            messageEl.style.display = 'none';
        }, 2000);
    }
    
    // ================================================================
    // PREVIEW MODE
    // ================================================================
    
    const previewBtn = document.getElementById('previewBtn');
    const previewModal = document.getElementById('previewModal');
    
    if (previewBtn && contentTextarea) {
        previewBtn.addEventListener('click', function(e) {
            e.preventDefault();
            showPreview();
        });
    }
    
    function showPreview() {
        const title = document.getElementById('title')?.value || 'Untitled';
        const content = contentTextarea.value || '<p>No content</p>';
        
        // Create modal if it doesn't exist
        let modal = document.getElementById('previewModal');
        if (!modal) {
            modal = document.createElement('div');
            modal.id = 'previewModal';
            modal.className = 'modal fade';
            modal.innerHTML = `
                <div class="modal-dialog modal-lg modal-dialog-scrollable">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title">Preview</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                        </div>
                        <div class="modal-body">
                            <h2 id="previewTitle"></h2>
                            <hr>
                            <div id="previewContent" class="post-content"></div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                        </div>
                    </div>
                </div>
            `;
            document.body.appendChild(modal);
        }
        
        // Update preview content
        document.getElementById('previewTitle').textContent = title;
        document.getElementById('previewContent').innerHTML = content;
        
        // Show modal
        const bsModal = new bootstrap.Modal(modal);
        bsModal.show();
    }
    
    // ================================================================
    // KEYBOARD SHORTCUTS
    // ================================================================
    
    if (contentTextarea) {
        contentTextarea.addEventListener('keydown', function(e) {
            // Ctrl/Cmd + B = Bold
            if ((e.ctrlKey || e.metaKey) && e.key === 'b') {
                e.preventDefault();
                formatText('bold');
            }
            
            // Ctrl/Cmd + I = Italic
            if ((e.ctrlKey || e.metaKey) && e.key === 'i') {
                e.preventDefault();
                formatText('italic');
            }
            
            // Ctrl/Cmd + K = Link
            if ((e.ctrlKey || e.metaKey) && e.key === 'k') {
                e.preventDefault();
                formatText('link');
            }
            
            // Ctrl/Cmd + S = Save Draft
            if ((e.ctrlKey || e.metaKey) && e.key === 's') {
                e.preventDefault();
                saveDraft();
            }
            
            // Tab key for indentation
            if (e.key === 'Tab') {
                e.preventDefault();
                const start = this.selectionStart;
                const end = this.selectionEnd;
                this.value = this.value.substring(0, start) + '    ' + this.value.substring(end);
                this.selectionStart = this.selectionEnd = start + 4;
            }
        });
    }
    
    // ================================================================
    // IMAGE UPLOAD PREVIEW
    // ================================================================
    
    const imageInput = document.getElementById('featured_image');
    
    if (imageInput) {
        imageInput.addEventListener('change', function(e) {
            const file = e.target.files[0];
            
            if (file) {
                // Validate file size
                const maxSize = 5 * 1024 * 1024; // 5MB
                if (file.size > maxSize) {
                    alert('Image size must be less than 5MB');
                    this.value = '';
                    return;
                }
                
                // Show preview
                const reader = new FileReader();
                reader.onload = function(e) {
                    let preview = document.getElementById('imagePreview');
                    if (!preview) {
                        preview = document.createElement('div');
                        preview.id = 'imagePreview';
                        preview.className = 'mt-3';
                        imageInput.parentNode.appendChild(preview);
                    }
                    
                    preview.innerHTML = `
                        <img src="${e.target.result}" 
                             class="img-fluid rounded" 
                             style="max-height: 300px;">
                        <p class="text-muted small mt-2">
                            <i class="fas fa-check-circle text-success"></i> 
                            Image ready for upload
                        </p>
                    `;
                };
                reader.readAsDataURL(file);
            }
        });
    }
    
    // ================================================================
    // UNSAVED CHANGES WARNING
    // ================================================================
    
    let hasUnsavedChanges = false;
    
    if (contentTextarea) {
        [contentTextarea, document.getElementById('title'), document.getElementById('summary')]
            .filter(el => el)
            .forEach(element => {
                element.addEventListener('input', function() {
                    hasUnsavedChanges = true;
                });
            });
        
        window.addEventListener('beforeunload', function(e) {
            if (hasUnsavedChanges) {
                e.preventDefault();
                e.returnValue = 'You have unsaved changes. Are you sure you want to leave?';
                return e.returnValue;
            }
        });
        
        // Clear flag on form submit
        const form = contentTextarea.closest('form');
        if (form) {
            form.addEventListener('submit', function() {
                hasUnsavedChanges = false;
            });
        }
    }
    
    console.log('Editor JS initialized');

})();