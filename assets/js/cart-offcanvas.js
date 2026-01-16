/**
 * Cart Offcanvas Functionality
 * PHP 8.4 Pure Functional Script
 */

document.addEventListener('DOMContentLoaded', function() {
    // Get elements
    const cartButton = document.getElementById('cartButton');
    const cartOffcanvas = document.getElementById('cartOffcanvas');
    const cartContent = document.getElementById('cart-content');
    const cartLoading = document.getElementById('cart-loading');
    const cartCounter = document.getElementById('cart-counter');
    
    // Initialize offcanvas
    let offcanvasInstance = null;
    if (cartOffcanvas) {
        offcanvasInstance = new bootstrap.Offcanvas(cartOffcanvas);
    }
    
    // Open cart offcanvas when cart button is clicked
    if (cartButton && offcanvasInstance) {
        cartButton.addEventListener('click', function() {
            // Show offcanvas
            offcanvasInstance.show();
            
            // Show loading state
            if (cartLoading) cartLoading.classList.remove('d-none');
            if (cartContent) cartContent.classList.add('d-none');
            
            // Load cart content via AJAX
            loadCartContent();
        });
    }
    
    // Cart content loading function
    function loadCartContent() {
        fetch('ajax/cart-content.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
                'X-Requested-With': 'XMLHttpRequest'
            }
        })
        .then(response => response.json())
        .then(data => {
            if (data.status) {
                // Update cart content
                if (cartContent) {
                    cartContent.innerHTML = data.html;
                    cartContent.classList.remove('d-none');
                }
                
                // Update cart counter
                if (cartCounter) {
                    cartCounter.textContent = data.total_quantity;
                    if (data.total_quantity > 0) {
                        cartCounter.classList.remove('d-none');
                    } else {
                        cartCounter.classList.add('d-none');
                    }
                }
                
                // Setup remove buttons
                setupRemoveButtons();
            } else {
                // Show error
                if (cartContent) {
                    cartContent.innerHTML = '<div class="alert alert-danger">Failed to load cart content</div>';
                    cartContent.classList.remove('d-none');
                }
            }
            
            // Hide loading state
            if (cartLoading) cartLoading.classList.add('d-none');
        })
        .catch(error => {
            console.error('Error:', error);
            
            // Show error
            if (cartContent) {
                cartContent.innerHTML = '<div class="alert alert-danger">An error occurred while loading cart content</div>';
                cartContent.classList.remove('d-none');
            }
            
            // Hide loading state
            if (cartLoading) cartLoading.classList.add('d-none');
        });
    }
    
    // Setup remove buttons
    function setupRemoveButtons() {
        const removeButtons = document.querySelectorAll('.remove-from-cart');
        
        removeButtons.forEach(button => {
            button.addEventListener('click', function() {
                const itemKey = this.dataset.key;
                
                // Show loading state for this button
                this.innerHTML = '<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span>';
                this.disabled = true;
                
                // Remove item from cart via AJAX
                fetch('ajax/cart.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                        'X-Requested-With': 'XMLHttpRequest'
                    },
                    body: `action=remove&item_key=${encodeURIComponent(itemKey)}&csrf_token=${getCsrfToken()}`
                })
                .then(response => response.json())
                .then(data => {
                    if (data.status) {
                        // Reload cart content
                        loadCartContent();
                    } else {
                        // Show error
                        alert(data.message || 'Failed to remove item from cart');
                        
                        // Reset button
                        this.innerHTML = '<i class="bi bi-trash"></i>';
                        this.disabled = false;
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    
                    // Show error
                    alert('An error occurred. Please try again.');
                    
                    // Reset button
                    this.innerHTML = '<i class="bi bi-trash"></i>';
                    this.disabled = false;
                });
            });
        });
    }
    
    // Helper function to get CSRF token
    function getCsrfToken() {
        // Try to get from a hidden input
        const tokenInput = document.querySelector('input[name="csrf_token"]');
        if (tokenInput) {
            return tokenInput.value;
        }
        
        // If not found, check if it's in a data attribute on body
        const tokenData = document.body.dataset.csrfToken;
        if (tokenData) {
            return tokenData;
        }
        
        // Return empty string if not found
        return '';
    }
});