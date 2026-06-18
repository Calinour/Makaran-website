// Main application interactions for SahanFresh FSCMS

document.addEventListener('DOMContentLoaded', () => {
    // Auto-dismiss alert boxes after 4 seconds
    const alerts = document.querySelectorAll('.alert');
    alerts.forEach(alert => {
        setTimeout(() => {
            alert.style.transition = 'opacity 0.6s ease, transform 0.6s ease';
            alert.style.opacity = '0';
            alert.style.transform = 'translateY(-10px)';
            setTimeout(() => {
                alert.remove();
            }, 600);
        }, 4000);
    });

    // Confirmation actions for destructive items
    const confirmActions = document.querySelectorAll('[data-confirm]');
    confirmActions.forEach(element => {
        element.addEventListener('click', (e) => {
            const message = element.getAttribute('data-confirm') || 'Are you sure you want to perform this action?';
            if (!confirm(message)) {
                e.preventDefault();
            }
        });
    });

    // Filter table content based on a search bar
    const searchInputs = document.querySelectorAll('[data-search-table]');
    searchInputs.forEach(input => {
        const tableId = input.getAttribute('data-search-table');
        const table = document.getElementById(tableId);
        if (!table) return;

        const rows = table.querySelectorAll('tbody tr');

        input.addEventListener('keyup', (e) => {
            const query = e.target.value.toLowerCase().trim();
            rows.forEach(row => {
                const text = row.textContent.toLowerCase();
                if (text.includes(query)) {
                    row.style.display = '';
                } else {
                    row.style.display = 'none';
                }
            });
        });
    });
    
    // Dynamic cart total updating when quantity changes
    const cartQtyInputs = document.querySelectorAll('.quantity-input');
    cartQtyInputs.forEach(input => {
        input.addEventListener('change', () => {
            const form = input.closest('form');
            if (form) {
                form.submit();
            }
        });
    });
});
