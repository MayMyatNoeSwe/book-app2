<?php
// views/admin/footer.php
?>
    <!-- Bootstrap 5 Bundle with Popper -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

    <!-- SweetAlert2 JS -->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

    <!-- Custom Admin Dashboard JS -->
    <script>
    document.addEventListener('DOMContentLoaded', function() {
        // Sync theme with main site
        const html = document.documentElement;
        const currentTheme = localStorage.getItem('theme') || 'light';
        html.setAttribute('data-bs-theme', currentTheme);
        
        // Sidebar Drawer Toggle
        const sidebar = document.getElementById('adminSidebar');
        const main = document.querySelector('.admin-main');
        const toggleBtn = document.getElementById('drawerToggle');
        const toggleIcon = toggleBtn?.querySelector('i');
        
        const setSidebarState = (collapsed) => {
            if (collapsed) {
                sidebar?.classList.add('collapsed');
                main?.classList.add('collapsed');
                if (toggleIcon) {
                    toggleIcon.classList.remove('fa-chevron-left');
                    toggleIcon.classList.add('fa-chevron-right');
                }
            } else {
                sidebar?.classList.remove('collapsed');
                main?.classList.remove('collapsed');
                if (toggleIcon) {
                    toggleIcon.classList.remove('fa-chevron-right');
                    toggleIcon.classList.add('fa-chevron-left');
                }
            }
            localStorage.setItem('admin_sidebar_collapsed', collapsed ? 'true' : 'false');
        };

        // Initialize from localStorage
        const isCollapsed = localStorage.getItem('admin_sidebar_collapsed') === 'true';
        setSidebarState(isCollapsed);

        toggleBtn?.addEventListener('click', function() {
            const nowCollapsed = !sidebar.classList.contains('collapsed');
            setSidebarState(nowCollapsed);
        });
        
        console.log("Admin Dashboard Initialized");
    });
    </script>
</body>
</html>
