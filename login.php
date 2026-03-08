<?php
declare(strict_types=1);

/*
 * Path: login.php
 * 說明：系統登入頁，提供帳號密碼登入，載入共用 header/footer，並顯示共用 LOGO。
 */

require_once __DIR__ . '/services/auth_service.php';

if (auth_check()) {
    header('Location: /index.php');
    exit;
}

$pageTitle = '登入';
$baseUrl   = '';
$assetTs   = time();
$pageJs    = 'auth.js';

require __DIR__ . '/partials/header.php';
?>

<main class="page-shell">
    <div class="container">
        <section class="section">
            <div class="card login-card">
                <div class="card__head login-card__head">
                    <div class="login-logo-wrap">
                        <img
                            src="<?= $baseUrl ?>/assets/img/logo.png"
                            alt="logo"
                            class="login-logo-img"
                        >
                    </div>
                    <h1 class="typ-h1 mb-0">發財登入系統</h1>
                </div>

                <div class="card__body">
                    <form id="loginForm" class="stack-4" novalidate>
                        <div class="form-group">
                            <label class="form-label" for="username">帳號</label>
                            <input
                                class="input"
                                id="username"
                                name="username"
                                type="text"
                                autocomplete="username"
                                required
                            >
                        </div>

                        <div class="form-group">
                            <label class="form-label" for="password">密碼</label>
                            <input
                                class="input"
                                id="password"
                                name="password"
                                type="password"
                                autocomplete="current-password"
                                required
                            >
                        </div>

                        <div id="loginMsg" class="typ-small typ-muted"></div>

                        <button type="submit" class="btn btn--primary" id="loginSubmit">登入</button>
                    </form>
                </div>
            </div>
        </section>
    </div>
</main>

<script>
document.addEventListener('DOMContentLoaded', function () {
    if (window.Auth) {
        window.Auth.bindLoginForm('#loginForm');
    }
});
</script>

<?php require __DIR__ . '/partials/footer.php'; ?>