/**
 * ================================================================
 * BLOG HUT - Login/Auth Pages JavaScript
 * University of Moratuwa - IN2120 Web Programming Project
 * ================================================================
 * 
 * Handles:
 * - Password visibility toggle
 * - Form validation
 * - Password strength indicator
 * - Real-time validation feedback
 */

(function() {
    'use strict';

    // ================================================================
    // PASSWORD VISIBILITY TOGGLE
    // ================================================================
    
    const togglePasswordButtons = document.querySelectorAll('[id^="toggle"]');
    
    togglePasswordButtons.forEach(function(button) {
        button.addEventListener('click', function() {
            const targetId = this.id.replace('toggle', '').toLowerCase();
            const passwordInput = document.getElementById(targetId);
            const icon = this.querySelector('i');
            
            if (!passwordInput) return;
            
            if (passwordInput.type === 'password') {
                passwordInput.type = 'text';
                icon.classList.remove('fa-eye');
                icon.classList.add('fa-eye-slash');
                this.setAttribute('aria-label', 'Hide password');
            } else {
                passwordInput.type = 'password';
                icon.classList.remove('fa-eye-slash');
                icon.classList.add('fa-eye');
                this.setAttribute('aria-label', 'Show password');
            }
        });
    });

    // ================================================================
    // PASSWORD STRENGTH INDICATOR
    // ================================================================
    
    const passwordInput = document.getElementById('password') || document.getElementById('new_password');
    
    if (passwordInput && document.getElementById('registerForm')) {
        // Create strength indicator
        const strengthContainer = document.createElement('div');
        strengthContainer.className = 'password-strength mt-2';
        strengthContainer.innerHTML = '<div class="strength-bar"></div>';
        
        const strengthText = document.createElement('small');
        strengthText.className = 'form-text text-muted d-block mt-1';
        strengthText.textContent = 'Password strength: ';
        
        passwordInput.parentNode.appendChild(strengthContainer);
        passwordInput.parentNode.appendChild(strengthText);
        
        passwordInput.addEventListener('input', function() {
            const password = this.value;
            const strength = calculatePasswordStrength(password);
            const strengthBar = strengthContainer.querySelector('.strength-bar');
            
            // Remove all classes
            strengthBar.classList.remove('weak', 'medium', 'strong');
            
            if (password.length === 0) {
                strengthBar.style.width = '0%';
                strengthText.textContent = 'Password strength: ';
                return;
            }
            
            if (strength < 40) {
                strengthBar.classList.add('weak');
                strengthText.textContent = 'Password strength: Weak';
                strengthText.className = 'form-text text-danger d-block mt-1';
            } else if (strength < 70) {
                strengthBar.classList.add('medium');
                strengthText.textContent = 'Password strength: Medium';
                strengthText.className = 'form-text text-warning d-block mt-1';
            } else {
                strengthBar.classList.add('strong');
                strengthText.textContent = 'Password strength: Strong';
                strengthText.className = 'form-text text-success d-block mt-1';
            }
        });
    }
    
    function calculatePasswordStrength(password) {
        let strength = 0;
        
        if (password.length >= 8) strength += 25;
        if (password.length >= 12) strength += 15;
        if (/[a-z]/.test(password)) strength += 15;
        if (/[A-Z]/.test(password)) strength += 15;
        if (/[0-9]/.test(password)) strength += 15;
        if (/[^a-zA-Z0-9]/.test(password)) strength += 15;
        
        return Math.min(strength, 100);
    }

    // ================================================================
    // REAL-TIME USERNAME VALIDATION
    // ================================================================
    
    const usernameInput = document.getElementById('username');
    
    if (usernameInput) {
        const usernameHelp = document.createElement('small');
        usernameHelp.className = 'form-text';
        usernameInput.parentNode.appendChild(usernameHelp);
        
        usernameInput.addEventListener('input', function() {
            const username = this.value;
            const pattern = /^[a-zA-Z0-9_]+$/;
            
            if (username.length === 0) {
                usernameHelp.textContent = '';
                this.classList.remove('is-valid', 'is-invalid');
                return;
            }
            
            if (!pattern.test(username)) {
                usernameHelp.textContent = 'Only letters, numbers, and underscores allowed';
                usernameHelp.className = 'form-text text-danger';
                this.classList.add('is-invalid');
                this.classList.remove('is-valid');
            } else if (username.length < 3) {
                usernameHelp.textContent = 'Username must be at least 3 characters';
                usernameHelp.className = 'form-text text-warning';
                this.classList.remove('is-valid', 'is-invalid');
            } else {
                usernameHelp.textContent = 'Username looks good!';
                usernameHelp.className = 'form-text text-success';
                this.classList.add('is-valid');
                this.classList.remove('is-invalid');
            }
        });
    }

    // ================================================================
    // EMAIL VALIDATION
    // ================================================================
    
    const emailInput = document.getElementById('email');
    
    if (emailInput) {
        emailInput.addEventListener('blur', function() {
            const email = this.value;
            const emailPattern = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
            
            if (email && !emailPattern.test(email)) {
                this.classList.add('is-invalid');
                this.classList.remove('is-valid');
            } else if (email) {
                this.classList.add('is-valid');
                this.classList.remove('is-invalid');
            }
        });
    }

    // ================================================================
    // PASSWORD MATCH VALIDATION
    // ================================================================
    
    const confirmPasswordInput = document.getElementById('confirm_password');
    const newPasswordInput = document.getElementById('new_password') || document.getElementById('password');
    
    if (confirmPasswordInput && newPasswordInput) {
        function checkPasswordMatch() {
            if (confirmPasswordInput.value.length === 0) {
                confirmPasswordInput.classList.remove('is-valid', 'is-invalid');
                return;
            }
            
            if (confirmPasswordInput.value === newPasswordInput.value) {
                confirmPasswordInput.classList.add('is-valid');
                confirmPasswordInput.classList.remove('is-invalid');
            } else {
                confirmPasswordInput.classList.add('is-invalid');
                confirmPasswordInput.classList.remove('is-valid');
            }
        }
        
        confirmPasswordInput.addEventListener('input', checkPasswordMatch);
        newPasswordInput.addEventListener('input', checkPasswordMatch);
    }

    // ================================================================
    // FORM SUBMISSION VALIDATION
    // ================================================================
    
    const authForms = document.querySelectorAll('#loginForm, #registerForm, #resetPasswordForm');
    
    authForms.forEach(function(form) {
        form.addEventListener('submit', function(e) {
            const password = form.querySelector('[name="password"]') || form.querySelector('[name="new_password"]');
            const confirmPassword = form.querySelector('[name="confirm_password"]');
            
            // Password match validation
            if (confirmPassword && password && confirmPassword.value !== password.value) {
                e.preventDefault();
                
                if (typeof Swal !== 'undefined') {
                    Swal.fire({
                        icon: 'error',
                        title: 'Passwords Do Not Match',
                        text: 'Please make sure both passwords are identical.',
                        confirmButtonColor: '#FFB100'
                    });
                } else {
                    alert('Passwords do not match!');
                }
                
                confirmPassword.focus();
                return false;
            }
            
            // Show loading state
            const submitBtn = form.querySelector('[type="submit"]');
            if (submitBtn) {
                submitBtn.disabled = true;
                submitBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Please wait...';
            }
        });
    });

    // ================================================================
    // CAPSLOCK WARNING
    // ================================================================
    
    const passwordFields = document.querySelectorAll('input[type="password"]');
    
    passwordFields.forEach(function(field) {
        const warning = document.createElement('small');
        warning.className = 'form-text text-warning d-none';
        warning.innerHTML = '<i class="fas fa-exclamation-triangle me-1"></i> Caps Lock is ON';
        field.parentNode.appendChild(warning);
        
        field.addEventListener('keydown', function(e) {
            const isCapsLock = e.getModifierState && e.getModifierState('CapsLock');
            if (isCapsLock) {
                warning.classList.remove('d-none');
            } else {
                warning.classList.add('d-none');
            }
        });
        
        field.addEventListener('blur', function() {
            warning.classList.add('d-none');
        });
    });

    // ================================================================
    // AUTO-FOCUS FIRST FIELD
    // ================================================================
    
    const firstInput = document.querySelector('.auth-card input:not([type="hidden"])');
    if (firstInput) {
        firstInput.focus();
    }

    // ================================================================
    // REMEMBER ME INFO
    // ================================================================
    
    const rememberMeCheckbox = document.getElementById('remember_me');
    
    if (rememberMeCheckbox) {
        rememberMeCheckbox.addEventListener('change', function() {
            if (this.checked) {
                const tooltip = 'You will stay logged in for 30 days';
                if (typeof bootstrap !== 'undefined' && bootstrap.Tooltip) {
                    const bsTooltip = new bootstrap.Tooltip(this, {
                        title: tooltip,
                        trigger: 'manual'
                    });
                    bsTooltip.show();
                    setTimeout(() => bsTooltip.hide(), 3000);
                }
            }
        });
    }

    // ================================================================
    // SMOOTH FORM TRANSITIONS
    // ================================================================
    
    const authCard = document.querySelector('.auth-card');
    if (authCard) {
        authCard.classList.add('fade-in');
    }

    // ================================================================
    // PREVENT DOUBLE SUBMISSION
    // ================================================================
    
    let isSubmitting = false;
    
    authForms.forEach(function(form) {
        form.addEventListener('submit', function(e) {
            if (isSubmitting) {
                e.preventDefault();
                return false;
            }
            isSubmitting = true;
        });
    });

    // ================================================================
    // COPY DEMO CREDENTIALS (Debug Mode)
    // ================================================================
    
    const demoCredentials = document.querySelectorAll('.bg-light.rounded.border p');
    
    demoCredentials.forEach(function(para) {
        if (para.textContent.includes('Admin:') || para.textContent.includes('User:')) {
            para.style.cursor = 'pointer';
            para.title = 'Click to use these credentials';
            
            para.addEventListener('click', function() {
                const text = this.textContent;
                const matches = text.match(/:\s*(\S+)\s*\/\s*(\S+)/);
                
                if (matches) {
                    const username = matches[1];
                    const password = matches[2];
                    
                    const identifierField = document.getElementById('identifier') || document.getElementById('username');
                    const passwordField = document.getElementById('password');
                    
                    if (identifierField) identifierField.value = username;
                    if (passwordField) passwordField.value = password;
                    
                    if (typeof Swal !== 'undefined') {
                        Swal.fire({
                            icon: 'success',
                            title: 'Credentials Filled!',
                            text: 'Demo credentials have been filled in.',
                            timer: 2000,
                            showConfirmButton: false,
                            toast: true,
                            position: 'top-end'
                        });
                    }
                }
            });
        }
    });

    console.log('Login JS initialized');

})();