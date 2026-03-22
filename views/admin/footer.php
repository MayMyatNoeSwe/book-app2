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
        
        console.log("Admin Dashboard Initialized with " + currentTheme + " theme");
    });
    </script>
</body>
</html>
