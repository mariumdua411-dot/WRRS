document.addEventListener('DOMContentLoaded', () => {

    // =========================================================================
    // 1. Live Table Search Filtering
    // =========================================================================
    const searchInput = document.getElementById('tableSearch');
    const tableBody = document.querySelector('.data-table tbody');

    if (searchInput && tableBody) {
        searchInput.addEventListener('input', () => {
            const query = searchInput.value.toLowerCase().trim();
            const rows = tableBody.querySelectorAll('tr');

            rows.forEach(row => {
                const text = row.textContent.toLowerCase();
                row.style.display = text.includes(query) ? '' : 'none';
            });
        });
    }

    // =========================================================================
    // 2. Authentication Form Validation (login.php)
    // =========================================================================
    const loginForm = document.querySelector('.auth-form');
    if (loginForm && !loginForm.getAttribute('action')?.includes('report.php')) {
        loginForm.addEventListener('submit', (e) => {
            const username = document.getElementById('username');
            const password = document.getElementById('password');

            if (username && !username.value.trim()) {
                e.preventDefault();
                alert('Please enter your username.');
                username.focus();
                return;
            }

            if (password && !password.value.trim()) {
                e.preventDefault();
                alert('Please enter your password.');
                password.focus();
                return;
            }
        });
    }

    // =========================================================================
    // 3. Report Submission Validation (report.php)
    // =========================================================================
    const reportForm = document.querySelector('form[action*="report.php"]');
    if (reportForm) {
        reportForm.addEventListener('submit', (e) => {
            const problemType = document.getElementById('problem_type');
            const location = document.getElementById('location');

            if (problemType && !problemType.value) {
                e.preventDefault();
                alert('Please select a problem type.');
                problemType.focus();
                return;
            }

            if (location && !location.value.trim()) {
                e.preventDefault();
                alert('Please provide the location of the issue.');
                location.focus();
                return;
            }
        });
    }

    // =========================================================================
    // 4. Report Assignment Confirmation (dc_verify.php)
    // =========================================================================
    const assignForm = document.querySelector('form[action*="dc_verify.php"]');
    if (assignForm) {
        assignForm.addEventListener('submit', (e) => {
            const techSelect = document.getElementById('technician_id');
            const selectedTechName = techSelect ? techSelect.options[techSelect.selectedIndex].text : 'the selected technician';
            
            if (techSelect && techSelect.value !== '') {
                const confirmed = confirm(`Are you sure you want to assign this issue to ${selectedTechName}?`);
                if (!confirmed) {
                    e.preventDefault();
                }
            }
        });
    }

    // =========================================================================
    // 5. Active Link Highlight in Top Navigation Bar
    // =========================================================================
    const currentPath = window.location.pathname.split('/').pop() || 'index.php';
    const navLinks = document.querySelectorAll('.top-nav-links a');

    navLinks.forEach(link => {
        const linkHref = link.getAttribute('href');
        if (linkHref === currentPath) {
            link.classList.add('nav-active');
        }
    });

    // =========================================================================
    // 6. Dark Sidebar Hamburger Menu Toggle
    // =========================================================================
    const sidebarToggle = document.getElementById('sidebarToggle');
    const appSidebar = document.getElementById('appSidebar') || document.querySelector('.sidebar');

    if (sidebarToggle && appSidebar) {
        // Retrieve stored user preference
        const isCollapsed = localStorage.getItem('wrrs_sidebar_nav_collapsed') === 'true';
        if (isCollapsed) {
            appSidebar.classList.add('menu-collapsed');
        }

        sidebarToggle.addEventListener('click', (e) => {
            e.preventDefault();
            appSidebar.classList.toggle('menu-collapsed');
            const nowCollapsed = appSidebar.classList.contains('menu-collapsed');
            localStorage.setItem('wrrs_sidebar_nav_collapsed', nowCollapsed ? 'true' : 'false');
        });
    }

    // =========================================================================
    // 7. Auto-Dismiss Alert Messages
    // =========================================================================
    const alerts = document.querySelectorAll('.alert');
    alerts.forEach(alert => {
        setTimeout(() => {
            alert.style.transition = 'opacity 0.6s ease, transform 0.6s ease';
            alert.style.opacity = '0';
            alert.style.transform = 'translateY(-6px)';
            setTimeout(() => alert.remove(), 60000);
        }, 80000);
    });
});

