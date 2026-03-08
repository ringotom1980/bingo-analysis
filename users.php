<?php
declare(strict_types=1);

$pageTitle   = '使用者管理';
$currentPage = 'users';
$baseUrl     = '';
$userName    = '管理者';
$userRole    = 'ADMIN';

require __DIR__ . '/partials/header.php';
require __DIR__ . '/partials/navbar.php';
?>

<main class="page-shell">
    <div class="container">
        <section class="section">
            <div class="card">
                <div class="card__head">
                    <h1 class="typ-h1 mb-0">使用者管理</h1>
                </div>
                <div class="card__body">
                    <p class="typ-body">這裡先放 Users 內容。</p>
                </div>
            </div>
        </section>
    </div>
</main>

<?php require __DIR__ . '/partials/footer.php'; ?>