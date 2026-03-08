<?php
declare(strict_types=1);

/*
 * Path: api/bingo_history.php
 * 說明：取得賓果賓果歷史開獎，支援近幾期與起訖期數區間查詢。
 */

require_once __DIR__ . '/../services/auth_service.php';
require_once __DIR__ . '/../services/bingo_service.php';

auth_require_login();

$startTermRaw = trim((string)($_GET['start_term'] ?? ''));
$endTermRaw   = trim((string)($_GET['end_term'] ?? ''));
$limit        = (int)($_GET['limit'] ?? 10);

$startTerm = null;
$endTerm   = null;

if ($startTermRaw !== '' && $endTermRaw !== '') {
    if (!ctype_digit($startTermRaw) || !ctype_digit($endTermRaw)) {
        auth_json_response(false, null, [
            'code'    => 'INVALID_TERM',
            'message' => '期數格式錯誤'
        ]);
        exit;
    }

    $startTerm = (int)$startTermRaw;
    $endTerm   = (int)$endTermRaw;

    $data = bingo_service_history(10, $startTerm, $endTerm);
    auth_json_response(true, $data, null);
    exit;
}

if ($limit < 1) {
    $limit = 10;
}

if ($limit > 200) {
    $limit = 200;
}

$data = bingo_service_history($limit);

auth_json_response(true, $data, null);