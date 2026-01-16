/**
 * Main JavaScript
 * PHP 8.4 Pure Functional Script
 */

// Load Cart Offcanvas Script
const baseUrl = document.querySelector('base') ? 
    document.querySelector('base').getAttribute('href') : 
    window.location.pathname.split('/').slice(0, -1).join('/');
    document.write('<script src="' + baseUrl + '/assets/js/cart-offcanvas.js"></script>');

document.addEventListener('DOMContentLoaded', function() {
    /**
     * Initialize tooltips and popovers
     */
    var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
    var tooltipList = tooltipTriggerList.map(function(tooltipTriggerEl) {
        return new bootstrap.Tooltip(tooltipTriggerEl);
    });

    var popoverTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="popover"]'));
    var popoverList = popoverTriggerList.map(function(popoverTriggerEl) {
        return new bootstrap.Popover(popoverTriggerEl);
    });

    /**
     * Auto-hide alert messages after 5 seconds
     */
    setTimeout(function() {
        var alerts = document.querySelectorAll('.alert:not(.alert-permanent)');
        alerts.forEach(function(alert) {
            var bsAlert = new bootstrap.Alert(alert);
            bsAlert.close();
        });
    }, 5000);

    /**
     * Confirm delete actions
     */
    document.querySelectorAll('.confirm-delete').forEach(function(element) {
        element.addEventListener('click', function(e) {
            if (!confirm('Are you sure you want to delete this item? This action cannot be undone.')) {
                e.preventDefault();
                return false;
            }
        });
    });

    /**
     * Quantity input controls
     */
    document.querySelectorAll('.quantity-control').forEach(function(element) {
        var input = element.querySelector('input[type="number"]');
        var decreaseBtn = element.querySelector('.decrease-btn');
        var increaseBtn = element.querySelector('.increase-btn');

        if (input && decreaseBtn && increaseBtn) {
            decreaseBtn.addEventListener('click', function() {
                var currentValue = parseInt(input.value);
                if (currentValue > parseInt(input.min)) {
                    input.value = currentValue - 1;
                    input.dispatchEvent(new Event('change'));
                }
            });

            increaseBtn.addEventListener('click', function() {
                var currentValue = parseInt(input.value);
                if (currentValue < parseInt(input.max)) {
                    input.value = currentValue + 1;
                    input.dispatchEvent(new Event('change'));
                }
            });
        }
    });

    /**
     * Cart quantity update
     */
    document.querySelectorAll('.cart-quantity-input').forEach(function(input) {
        input.addEventListener('change', function() {
            var form = this.closest('form');
            
            if (form) {
                // Get the submit button
                var submitBtn = form.querySelector('button[type="submit"]');
                
                // Temporarily change button text to indicate updating
                if (submitBtn) {
                    var originalText = submitBtn.innerHTML;
                    submitBtn.innerHTML = '<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Updating...';
                    submitBtn.disabled = true;
                }
                
                // Create FormData and add the CSRF token
                var formData = new FormData(form);
                
                // AJAX request to update cart
                fetch('ajax/cart.php', {
                    method: 'POST',
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest'
                    },
                    body: formData
                })
                .then(response => response.json())
                .then(data => {
                    // Reset button
                    if (submitBtn) {
                        submitBtn.innerHTML = originalText;
                        submitBtn.disabled = false;
                    }
                    
                    if (data.status) {
                        // Update cart totals
                        var totalElement = document.getElementById('cart-total');
                        var subtotalElement = document.getElementById('cart-subtotal');
                        var cartCounterElement = document.getElementById('cart-counter');
                        
                        if (totalElement) {
                            subtotalElement.textContent = '$' + data.cart.total_price.toFixed(2);
                        }
                        
                        if (subtotalElement) {
totalElement.textContent = '$' + data.cart.total_price.toFixed(2);
                        }
                        
                        if (cartCounterElement) {
                            cartCounterElement.textContent = data.cart.total_quantity;
                            if (data.cart.total_quantity > 0) {
                                cartCounterElement.classList.remove('d-none');
                            } else {
                                cartCounterElement.classList.add('d-none');
                            }
                        }
                    } else {
                        // Show error message
                        alert(data.message || 'An error occurred while updating the cart.');
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    
                    // Reset button
                    if (submitBtn) {
                        submitBtn.innerHTML = originalText;
                        submitBtn.disabled = false;
                    }
                    
                    alert('An error occurred. Please try again.');
                });
            }
        });
    });

    /**
     * Password strength meter
     */
    var passwordInput = document.getElementById('register-password');
    var strengthMeter = document.getElementById('password-strength-meter');
    
    if (passwordInput && strengthMeter) {
        passwordInput.addEventListener('input', function() {
            var password = this.value;
            var strength = 0;
            
            // Length check
            if (password.length >= 8) {
                strength += 25;
            }
            
            // Uppercase check
            if (/[A-Z]/.test(password)) {
                strength += 25;
            }
            
            // Lowercase check
            if (/[a-z]/.test(password)) {
                strength += 25;
            }
            
            // Number check
            if (/[0-9]/.test(password)) {
                strength += 25;
            }
            
            // Update strength meter
            strengthMeter.style.width = strength + '%';
            
            // Update color based on strength
            if (strength <= 25) {
                strengthMeter.className = 'progress-bar bg-danger';
            } else if (strength <= 50) {
                strengthMeter.className = 'progress-bar bg-warning';
            } else if (strength <= 75) {
                strengthMeter.className = 'progress-bar bg-info';
            } else {
                strengthMeter.className = 'progress-bar bg-success';
            }
        });
    }
});