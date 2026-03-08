<?php declare(strict_types=1); ?>

<script src="<?= $baseUrl ?>/assets/js/core.js"></script>
<script src="<?= $baseUrl ?>/assets/js/api.js"></script>
<script src="<?= $baseUrl ?>/assets/js/auth.js"></script>
<script src="<?= $baseUrl ?>/assets/js/navbar.js"></script>

<script>
document.addEventListener('DOMContentLoaded', function () {
    if (window.Navbar) {
        window.Navbar.init();
    }
    if (window.Auth) {
        window.Auth.bindLogout('#btnLogout');
    }
});
</script>
</body>
</html>