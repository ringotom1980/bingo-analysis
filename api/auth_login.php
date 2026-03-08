<?php
declare(strict_types=1);

/* Path: api/auth_login.php */

require_once __DIR__ . '/../services/auth_service.php';

try {
    $raw = file_get_contents('php://input');
    $input = json_decode($raw ?: '', true);

    if (!is_array($input)) {
        auth_json_response(false, null, [
            'code' => 'BAD_REQUEST',
            'message' => '資料格式錯誤'
        ]);
    }

    $user = auth_login_attempt(
        (string)($input['username'] ?? ''),
        (string)($input['password'] ?? '')
    );

    auth_json_response(true, [
        'user' => $user
    ], null);

} catch (Throwable $e) {
    auth_json_response(false, null, [
        'code' => 'LOGIN_FAILED',
        'message' => $e->getMessage()
    ]);
}