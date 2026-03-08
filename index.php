<?php
declare(strict_types=1);

/* Path: index.php */

require_once __DIR__ . '/services/auth_service.php';
auth_require_login();

$user = auth_user();

$pageTitle   = '主儀表板';
$currentPage = 'dashboard';
$baseUrl     = '';
$assetTs     = time();
$pageJs      = 'dashboard.js';
$userName    = $user['display_name'] ?? '';
$userRole    = $user['role'] ?? 'USER';

require __DIR__ . '/partials/header.php';
require __DIR__ . '/partials/navbar.php';
?>

<main class="page-shell">
    <div class="container">
        <section class="section">
            <div class="card">
                <div class="card__head">
                    <h1 class="typ-h1 mb-0">主儀表板</h1>
                </div>
                <div class="card__body">
                    <p class="typ-body">
                        歡迎，<?= htmlspecialchars($userName, ENT_QUOTES, 'UTF-8') ?>
                    </p>
                </div>
            </div>
        </section>
    </div>
</main>

<?php require __DIR__ . '/partials/footer.php'; ?>