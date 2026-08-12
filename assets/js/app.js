/**
 * Custom JavaScript for ระบบแจ้งซ่อมครุภัณฑ์
 */

document.addEventListener('DOMContentLoaded', function () {
    // Sidebar Toggle
    const sidebarToggle = document.getElementById('sidebarToggle');
    const sidebar = document.getElementById('sidebar');
    const mainContent = document.querySelector('.main-content');

    // Create overlay element for mobile
    let sidebarOverlay = document.querySelector('.sidebar-overlay');
    if (!sidebarOverlay && sidebar) {
        sidebarOverlay = document.createElement('div');
        sidebarOverlay.className = 'sidebar-overlay';
        document.body.appendChild(sidebarOverlay);
    }

    const sidebarToggleMobile = document.getElementById('sidebarToggleMobile');

    // Sync aria-expanded on both toggle buttons with the sidebar state
    function updateSidebarAria() {
        if (!sidebar) return;
        const expanded = window.innerWidth < 992
            ? sidebar.classList.contains('show')
            : !sidebar.classList.contains('collapsed');
        const value = expanded ? 'true' : 'false';
        if (sidebarToggle) sidebarToggle.setAttribute('aria-expanded', value);
        if (sidebarToggleMobile) sidebarToggleMobile.setAttribute('aria-expanded', value);
    }

    // Restore desktop collapsed state
    if (sidebar && window.innerWidth >= 992 && localStorage.getItem('sidebarCollapsed') === '1') {
        sidebar.classList.add('collapsed');
        if (mainContent) {
            mainContent.classList.add('expanded');
        }
    }

    updateSidebarAria();

    // Toggle sidebar function
    function toggleSidebar() {
        if (window.innerWidth < 992) {
            // Mobile: show/hide with overlay
            sidebar.classList.toggle('show');
            sidebarOverlay.classList.toggle('show');
            document.body.style.overflow = sidebar.classList.contains('show') ? 'hidden' : '';
        } else {
            // Desktop: collapse sidebar
            sidebar.classList.toggle('collapsed');
            if (mainContent) {
                mainContent.classList.toggle('expanded');
            }
            try {
                localStorage.setItem('sidebarCollapsed', sidebar.classList.contains('collapsed') ? '1' : '0');
            } catch (e) { /* localStorage may be unavailable */ }
        }
        updateSidebarAria();
    }

    // Close sidebar function
    function closeSidebar() {
        if (sidebar) {
            sidebar.classList.remove('show');
        }
        if (sidebarOverlay) {
            sidebarOverlay.classList.remove('show');
        }
        document.body.style.overflow = '';
        updateSidebarAria();
    }

    if (sidebarToggle && sidebar) {
        sidebarToggle.addEventListener('click', toggleSidebar);
    }

    // Mobile sidebar toggle
    if (sidebarToggleMobile && sidebar) {
        sidebarToggleMobile.addEventListener('click', toggleSidebar);
    }

    // Close sidebar on Escape (mobile)
    document.addEventListener('keydown', function (e) {
        if (e.key === 'Escape' && window.innerWidth < 992) {
            closeSidebar();
        }
    });

    // Close sidebar when clicking overlay
    if (sidebarOverlay) {
        sidebarOverlay.addEventListener('click', closeSidebar);
    }

    // Close sidebar when clicking a link (mobile)
    if (sidebar) {
        sidebar.querySelectorAll('.nav-link').forEach(function (link) {
            link.addEventListener('click', function () {
                if (window.innerWidth < 992) {
                    closeSidebar();
                }
            });
        });
    }

    // Handle window resize
    window.addEventListener('resize', function () {
        if (window.innerWidth >= 992) {
            closeSidebar();
        } else {
            updateSidebarAria();
        }
    });

    // Swipe gesture support for mobile
    let touchStartX = 0;
    let touchEndX = 0;

    document.addEventListener('touchstart', function (e) {
        touchStartX = e.changedTouches[0].screenX;
    }, { passive: true });

    document.addEventListener('touchend', function (e) {
        touchEndX = e.changedTouches[0].screenX;
        handleSwipe();
    }, { passive: true });

    function handleSwipe() {
        const swipeThreshold = 80;
        const swipeDistance = touchEndX - touchStartX;

        if (window.innerWidth < 992 && sidebar) {
            // Swipe right to open sidebar (from left edge)
            if (swipeDistance > swipeThreshold && touchStartX < 50) {
                sidebar.classList.add('show');
                if (sidebarOverlay) sidebarOverlay.classList.add('show');
                document.body.style.overflow = 'hidden';
            }
            // Swipe left to close sidebar
            if (swipeDistance < -swipeThreshold && sidebar.classList.contains('show')) {
                closeSidebar();
            }
        }
    }

    // Auto-hide alerts after 5 seconds
    const alerts = document.querySelectorAll('.alert:not(.alert-permanent)');
    alerts.forEach(function (alert) {
        setTimeout(function () {
            const bsAlert = bootstrap.Alert.getOrCreateInstance(alert);
            bsAlert.close();
        }, 5000);
    });

    // Confirm delete actions
    const deleteButtons = document.querySelectorAll('[data-confirm]');
    deleteButtons.forEach(function (button) {
        button.addEventListener('click', function (e) {
            const message = this.getAttribute('data-confirm') || 'คุณแน่ใจหรือไม่ที่จะดำเนินการนี้?';
            if (!confirm(message)) {
                e.preventDefault();
            }
        });
    });

    // Image preview on file input
    const imageInputs = document.querySelectorAll('input[type="file"][accept*="image"]');
    imageInputs.forEach(function (input) {
        input.addEventListener('change', function () {
            const previewId = this.getAttribute('data-preview');
            const preview = document.getElementById(previewId);

            if (preview && this.files && this.files[0]) {
                const reader = new FileReader();
                reader.onload = function (e) {
                    preview.src = e.target.result;
                    preview.style.display = 'block';
                };
                reader.readAsDataURL(this.files[0]);
            }
        });
    });

    // Initialize tooltips
    const tooltipTriggerList = document.querySelectorAll('[data-bs-toggle="tooltip"]');
    tooltipTriggerList.forEach(function (tooltipTriggerEl) {
        new bootstrap.Tooltip(tooltipTriggerEl);
    });

    // Initialize popovers
    const popoverTriggerList = document.querySelectorAll('[data-bs-toggle="popover"]');
    popoverTriggerList.forEach(function (popoverTriggerEl) {
        new bootstrap.Popover(popoverTriggerEl);
    });

    // Search/Filter form auto-submit on change
    const autoSubmitForms = document.querySelectorAll('.auto-submit');
    autoSubmitForms.forEach(function (form) {
        const selects = form.querySelectorAll('select');
        selects.forEach(function (select) {
            select.addEventListener('change', function () {
                form.submit();
            });
        });
    });

    // Print button
    const printButtons = document.querySelectorAll('.btn-print');
    printButtons.forEach(function (button) {
        button.addEventListener('click', function () {
            window.print();
        });
    });
});

// Format number with commas
function formatNumber(num) {
    return num.toString().replace(/\B(?=(\d{3})+(?!\d))/g, ",");
}

// Format currency
function formatCurrency(amount) {
    return formatNumber(parseFloat(amount).toFixed(2)) + ' บาท';
}

// Loading overlay
function showLoading() {
    const overlay = document.createElement('div');
    overlay.className = 'loading-overlay';
    overlay.id = 'loadingOverlay';
    overlay.innerHTML = '<div class="spinner-border text-primary" role="status"><span class="visually-hidden">กำลังโหลด...</span></div>';
    document.body.appendChild(overlay);
}

function hideLoading() {
    const overlay = document.getElementById('loadingOverlay');
    if (overlay) {
        overlay.remove();
    }
}

// AJAX helper
async function fetchData(url, options = {}) {
    showLoading();
    try {
        const response = await fetch(url, {
            headers: {
                'Content-Type': 'application/json',
                ...options.headers
            },
            ...options
        });
        const data = await response.json();
        return data;
    } catch (error) {
        console.error('Fetch error:', error);
        throw error;
    } finally {
        hideLoading();
    }
}
