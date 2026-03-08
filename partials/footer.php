<?php
declare(strict_types=1);

/* Path: partials/footer.php */

$assetTs = $assetTs ?? time();
$pageJs  = $pageJs ?? null;
?>

<script src="<?= $baseUrl ?>/assets/js/core.js?v=<?= $assetTs ?>"></script>
<script src="<?= $baseUrl ?>/assets/js/api.js?v=<?= $assetTs ?>"></script>
<script src="<?= $baseUrl ?>/assets/js/auth.js?v=<?= $assetTs ?>"></script>
<script src="<?= $baseUrl ?>/assets/js/navbar.js?v=<?= $assetTs ?>"></script>

<?php if (!empty($pageJs)): ?>
<script src="<?= $baseUrl ?>/assets/js/<?= htmlspecialchars($pageJs, ENT_QUOTES, 'UTF-8') ?>?v=<?= $assetTs ?>"></script>
<?php endif; ?>

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