<?php
declare(strict_types=1);

/* Path: bingo.php */

require_once __DIR__ . '/services/auth_service.php';
auth_require_login();

$user = auth_user();

$pageTitle   = '賓果賓果分析';
$currentPage = 'bingo';
$baseUrl     = '';
$assetTs     = time();
$pageJs      = 'bingo.js';
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
                    <h1 class="typ-h1 mb-0">賓果賓果分析</h1>
                </div>
                <div class="card__body">
                    <p class="typ-body">這裡先放 Bingo 分析內容。</p>
                </div>
            </div>
        </section>
    </div>
</main>

<?php require __DIR__ . '/partials/footer.php'; ?>