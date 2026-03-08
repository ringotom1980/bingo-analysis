<?php
declare(strict_types=1);

$pageTitle = '登入';
$baseUrl = '';

require __DIR__ . '/partials/header.php';
?>

<main class="page-shell">
    <div class="container">
        <section class="section">
            <div class="card">
                <div class="card__head">
                    <h1 class="typ-h1 mb-0">登入系統</h1>
                </div>
                <div class="card__body">
                    <form id="loginForm" class="stack-4">
                        <div class="form-group">
                            <label class="form-label" for="username">帳號</label>
                            <input class="input" id="username" name="username" type="text" autocomplete="username">
                        </div>

                        <div class="form-group">
                            <label class="form-label" for="password">密碼</label>
                            <input class="input" id="password" name="password" type="password" autocomplete="current-password">
                        </div>

                        <button type="submit" class="btn btn--primary">登入</button>
                    </form>
                </div>
            </div>
        </section>
    </div>
</main>

<?php require __DIR__ . '/partials/footer.php'; ?>