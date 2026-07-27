// Shopping cart functionality
document.addEventListener('DOMContentLoaded', function() {
    // Quantity input validation
    const quantityInputs = document.querySelectorAll('input[type="number"]');
    quantityInputs.forEach(input => {
        input.addEventListener('change', function() {
            if (this.value < this.min) this.value = this.min;
            if (this.value > this.max) this.value = this.max;
        });
    });

    // Add to cart animations
    const addToCartButtons = document.querySelectorAll('.btn[type="submit"]');
    addToCartButtons.forEach(button => {
        button.addEventListener('click', function(e) {
            const quantity = this.closest('form').querySelector('input[name="quantity"]');
            if (quantity && quantity.value < 1) {
                e.preventDefault();
                alert('Please enter a valid quantity');
            }
        });
    });

    // Cart badge update
    function updateCartBadge() {
        const badge = document.querySelector('.cart-badge');
        if (badge) {
            // In a real application, you would fetch this from the server
            badge.textContent = '0';
        }
    }

    // Auto-refresh dashboard every 60 seconds
    if (window.location.pathname.includes('dashboard.php')) {
        setTimeout(() => {
            window.location.reload();
        }, 60000);
    }
});

// Form validation
function validateCheckoutForm() {
    const name = document.getElementById('customer_name').value.trim();
    const email = document.getElementById('customer_email').value.trim();
    const address = document.getElementById('customer_address').value.trim();
    
    if (!name || !email || !address) {
        alert('Please fill in all required fields.');
        return false;
    }
    
    if (!validateEmail(email)) {
        alert('Please enter a valid email address.');
        return false;
    }
    
    return true;
}

function validateEmail(email) {
    const re = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
    return re.test(email);
}

// Toast notifications
function showToast(message, type = 'success') {
    const toast = document.createElement('div');
    toast.className = `toast toast-${type}`;
    toast.textContent = message;
    toast.style.cssText = `
        position: fixed;
        top: 20px;
        right: 20px;
        padding: 15px 20px;
        background: ${type === 'success' ? '#28a745' : '#dc3545'};
        color: white;
        border-radius: 5px;
        z-index: 1000;
        animation: slideIn 0.3s ease;
    `;
    
    document.body.appendChild(toast);
    
    setTimeout(() => {
        toast.style.animation = 'slideOut 0.3s ease';
        setTimeout(() => toast.remove(), 300);
    }, 3000);
}