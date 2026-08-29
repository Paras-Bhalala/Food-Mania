/**
 * Food-Mania — Custom JavaScript
 * Single Restaurant Food Ordering System
 * 
 * Handles: AJAX cart operations, toast notifications, quantity controls,
 * search filtering, navbar scroll effects, form validation.
 */

// ============================================================
// SITE URL CONSTANT (injected via PHP in the footer)
// ============================================================
const SITE_URL = document.querySelector('meta[name="site-url"]')?.content || '/Food-Mania';

// ============================================================
// NAVBAR SCROLL EFFECT
// ============================================================
document.addEventListener('DOMContentLoaded', function() {
    const navbar = document.getElementById('mainNavbar');
    if (navbar) {
        window.addEventListener('scroll', function() {
            if (window.scrollY > 50) {
                navbar.classList.add('scrolled');
            } else {
                navbar.classList.remove('scrolled');
            }
        });
    }
});

// ============================================================
// TOAST NOTIFICATION SYSTEM
// ============================================================
function showToast(message, type = 'success') {
    const container = document.getElementById('toastContainer');
    if (!container) return;
    
    const toastId = 'toast-' + Date.now();
    const icon = type === 'success' ? 'fa-check-circle' : 'fa-exclamation-circle';
    const title = type === 'success' ? 'Success' : 'Error';
    
    const toastHTML = `
        <div id="${toastId}" class="toast toast-custom ${type}" role="alert" aria-live="assertive" aria-atomic="true">
            <div class="toast-header">
                <i class="fas ${icon} me-2"></i>
                <strong class="me-auto">${title}</strong>
                <button type="button" class="btn-close" data-bs-dismiss="toast" aria-label="Close"></button>
            </div>
            <div class="toast-body">
                ${message}
            </div>
        </div>
    `;
    
    container.insertAdjacentHTML('beforeend', toastHTML);
    
    const toastEl = document.getElementById(toastId);
    const toast = new bootstrap.Toast(toastEl, { delay: 3000 });
    toast.show();
    
    // Remove from DOM after hidden
    toastEl.addEventListener('hidden.bs.toast', function() {
        toastEl.remove();
    });
}

// ============================================================
// CART BADGE UPDATE
// ============================================================
function updateCartBadge(count) {
    const badge = document.getElementById('cartBadge');
    if (!badge) return;
    
    badge.textContent = count;
    if (count > 0) {
        badge.style.display = 'flex';
        // Trigger pop animation
        badge.style.animation = 'none';
        badge.offsetHeight; // Force reflow
        badge.style.animation = 'badgePop 0.3s ease';
    } else {
        badge.style.display = 'none';
    }
}

// ============================================================
// ADD TO CART (AJAX)
// ============================================================
function addToCart(foodItemId, quantity = 1, button = null) {
    // Show loading state on button
    if (button) {
        const originalHTML = button.innerHTML;
        button.innerHTML = '<i class="fas fa-spinner fa-spin me-1"></i> Adding...';
        button.disabled = true;
    }
    
    const formData = new FormData();
    formData.append('food_item_id', foodItemId);
    formData.append('quantity', quantity);
    formData.append('action', 'add');
    
    fetch(SITE_URL + '/user/cart.php', {
        method: 'POST',
        body: formData,
        headers: {
            'X-Requested-With': 'XMLHttpRequest'
        }
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showToast(data.message, 'success');
            updateCartBadge(data.cartCount);
            
            if (button) {
                button.innerHTML = '<i class="fas fa-check me-1"></i> Added!';
                button.classList.add('added');
                setTimeout(() => {
                    button.innerHTML = '<i class="fas fa-cart-plus me-1"></i> Add to Cart';
                    button.classList.remove('added');
                    button.disabled = false;
                }, 1500);
            }
        } else {
            showToast(data.message || 'Failed to add item to cart.', 'error');
            if (button) {
                button.innerHTML = '<i class="fas fa-cart-plus me-1"></i> Add to Cart';
                button.disabled = false;
            }
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showToast('Something went wrong. Please try again.', 'error');
        if (button) {
            button.innerHTML = '<i class="fas fa-cart-plus me-1"></i> Add to Cart';
            button.disabled = false;
        }
    });
}

// ============================================================
// UPDATE CART QUANTITY (AJAX)
// ============================================================
function updateCartQuantity(cartId, action) {
    const formData = new FormData();
    formData.append('cart_id', cartId);
    formData.append('action', action); // 'increase', 'decrease', or 'remove'
    
    fetch(SITE_URL + '/user/cart.php', {
        method: 'POST',
        body: formData,
        headers: {
            'X-Requested-With': 'XMLHttpRequest'
        }
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            if (action === 'remove') {
                showToast('Item removed from cart.', 'success');
            }
            // Reload cart page to reflect changes
            location.reload();
        } else {
            showToast(data.message || 'Failed to update cart.', 'error');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showToast('Something went wrong. Please try again.', 'error');
    });
}

// ============================================================
// QUANTITY CONTROLS (Detail page)
// ============================================================
function increaseQuantity(inputId) {
    const input = document.getElementById(inputId);
    if (input) {
        let val = parseInt(input.value) || 1;
        if (val < 10) {
            input.value = val + 1;
        }
    }
}

function decreaseQuantity(inputId) {
    const input = document.getElementById(inputId);
    if (input) {
        let val = parseInt(input.value) || 1;
        if (val > 1) {
            input.value = val - 1;
        }
    }
}

// ============================================================
// MENU SEARCH FILTER (Client-side filtering)
// ============================================================
document.addEventListener('DOMContentLoaded', function() {
    const searchInput = document.getElementById('menuSearch');
    if (searchInput) {
        let debounceTimer;
        searchInput.addEventListener('input', function() {
            clearTimeout(debounceTimer);
            debounceTimer = setTimeout(() => {
                const query = this.value.toLowerCase().trim();
                const foodCards = document.querySelectorAll('.food-card-col');
                
                foodCards.forEach(card => {
                    const name = card.dataset.name?.toLowerCase() || '';
                    const category = card.dataset.category?.toLowerCase() || '';
                    
                    if (name.includes(query) || category.includes(query)) {
                        card.style.display = '';
                        card.classList.add('fade-in');
                    } else {
                        card.style.display = 'none';
                    }
                });
                
                // Show "no results" message
                const noResults = document.getElementById('noResults');
                const visible = document.querySelectorAll('.food-card-col:not([style*="display: none"])');
                if (noResults) {
                    noResults.style.display = visible.length === 0 ? 'block' : 'none';
                }
            }, 300);
        });
    }
});

// ============================================================
// CATEGORY FILTER BUTTONS
// ============================================================
document.addEventListener('DOMContentLoaded', function() {
    const filterBtns = document.querySelectorAll('.filter-btn');
    filterBtns.forEach(btn => {
        btn.addEventListener('click', function() {
            // Update active state
            filterBtns.forEach(b => b.classList.remove('active'));
            this.classList.add('active');
            
            const categoryId = this.dataset.category;
            const foodCards = document.querySelectorAll('.food-card-col');
            
            foodCards.forEach(card => {
                if (categoryId === 'all' || card.dataset.categoryId === categoryId) {
                    card.style.display = '';
                    card.classList.add('fade-in');
                } else {
                    card.style.display = 'none';
                }
            });
            
            // Show "no results" message
            const noResults = document.getElementById('noResults');
            const visible = document.querySelectorAll('.food-card-col:not([style*="display: none"])');
            if (noResults) {
                noResults.style.display = visible.length === 0 ? 'block' : 'none';
            }
        });
    });
});

// ============================================================
// FORM VALIDATION HELPERS
// ============================================================
function validateEmail(email) {
    const re = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
    return re.test(email);
}

function validatePhone(phone) {
    const re = /^[0-9]{10}$/;
    return re.test(phone);
}

// Client-side form validation
document.addEventListener('DOMContentLoaded', function() {
    const forms = document.querySelectorAll('.needs-validation');
    forms.forEach(form => {
        form.addEventListener('submit', function(e) {
            if (!form.checkValidity()) {
                e.preventDefault();
                e.stopPropagation();
            }
            form.classList.add('was-validated');
        });
    });
});

// ============================================================
// ADMIN SIDEBAR TOGGLE (Mobile)
// ============================================================
document.addEventListener('DOMContentLoaded', function() {
    const toggleBtn = document.getElementById('sidebarToggle');
    const sidebar = document.querySelector('.admin-sidebar');
    
    if (toggleBtn && sidebar) {
        toggleBtn.addEventListener('click', function() {
            sidebar.classList.toggle('show');
        });
        
        // Close sidebar when clicking outside on mobile
        document.addEventListener('click', function(e) {
            if (window.innerWidth < 992 && sidebar.classList.contains('show')) {
                if (!sidebar.contains(e.target) && !toggleBtn.contains(e.target)) {
                    sidebar.classList.remove('show');
                }
            }
        });
    }
});

// ============================================================
// CONFIRM DELETE ACTIONS
// ============================================================
function confirmDelete(message = 'Are you sure you want to delete this item?') {
    return confirm(message);
}

// ============================================================
// IMAGE PREVIEW ON FILE INPUT
// ============================================================
function previewImage(input, previewId) {
    const preview = document.getElementById(previewId);
    if (!preview) return;
    
    if (input.files && input.files[0]) {
        const reader = new FileReader();
        reader.onload = function(e) {
            preview.src = e.target.result;
            preview.style.display = 'block';
        };
        reader.readAsDataURL(input.files[0]);
    }
}
