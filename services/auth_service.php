<?php

declare(strict_types=1);

/* Path: services/auth_service.php */

require_once __DIR__ . '/../db.php';
require_once __DIR__ . '/../models/user_model.php';

if (session_status() === PHP_SESSION_NONE) {
    session_name('bingo_analysis');
    session_start();
}

function auth_json_response(bool $success, $data = null, ?array $error = null): void
{
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode([
        'success' => $success,
        'data'    => $data,
        'error'   => $error,
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

function auth_login_attempt(string $username, string $password): array
{
    global $pdo;

    $username = trim($username);
    if ($username === '' || $password === '') {
        throw new RuntimeException('請輸入帳號與密碼');
    }

    $user = user_find_by_username($pdo, $username);
    if (!$user) {
        throw new RuntimeException('帳號或密碼錯誤');
    }

    if ((int)$user['is_active'] !== 1) {
        throw new RuntimeException('帳號已停用');
    }

    if (!password_verify($password, (string)$user['password_hash'])) {
        throw new RuntimeException('帳號或密碼錯誤');
    }

    session_regenerate_id(true);

    /* 更新最後登入時間 */
    $stmt = $pdo->prepare("
    UPDATE users
    SET last_login_at = NOW()
    WHERE id = ?
");
    $stmt->execute([$user['id']]);

    $user['last_login_at'] = date('Y-m-d H:i:s');

    $_SESSION['auth_user'] = [
        'id'            => (int)$user['id'],
        'username'      => (string)$user['username'],
        'display_name'  => (string)$user['display_name'],
        'role'          => (string)$user['role'],
        'last_login_at' => (string)$user['last_login_at'],
    ];
    return $_SESSION['auth_user'];
}

function auth_logout_now(): void
{
    $_SESSION = [];

    if (ini_get('session.use_cookies')) {
        $params = session_get_cookie_params();
        setcookie(
            session_name(),
            '',
            time() - 42000,
            $params['path'] ?? '/',
            $params['domain'] ?? '',
            (bool)($params['secure'] ?? false),
            (bool)($params['httponly'] ?? true)
        );
    }

    session_destroy();
}

function auth_user(): ?array
{
    return (isset($_SESSION['auth_user']) && is_array($_SESSION['auth_user']))
        ? $_SESSION['auth_user']
        : null;
}

function auth_check(): bool
{
    return auth_user() !== null;
}

function auth_is_admin(): bool
{
    $user = auth_user();
    return $user !== null && ($user['role'] ?? '') === 'ADMIN';
}

function auth_require_login(): void
{
    if (!auth_check()) {
        header('Location: /login.php');
        exit;
    }
}

function auth_require_admin(): void
{
    if (!auth_check()) {
        header('Location: /login.php');
        exit;
    }

    if (!auth_is_admin()) {
        http_response_code(403);
        exit('Forbidden');
    }
}
