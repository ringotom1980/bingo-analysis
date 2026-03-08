<?php
declare(strict_types=1);

/*
 * Path: api/bingo_analysis.php
 * 說明：賓果賓果分析 API，支援一般分析（熱號/冷號/未出現期數/統計）與最佳組合分析。
 */

require_once __DIR__ . '/../services/auth_service.php';
require_once __DIR__ . '/../services/bingo_service.php';

auth_require_login();

$mode = trim((string)($_GET['mode'] ?? 'analysis'));

if ($mode === 'combo') {
    $hours = (int)($_GET['hours'] ?? 3);
    $star  = (int)($_GET['star'] ?? 5);

    if ($hours < 1) {
        $hours = 1;
    }
    if ($hours > 5) {
        $hours = 5;
    }

    if ($star < 1) {
        $star = 1;
    }
    if ($star > 10) {
        $star = 10;
    }

    $data = bingo_service_combo_analysis($hours, $star);
    auth_json_response(true, $data, null);
    exit;
}

$range = (int)($_GET['range'] ?? 10);

if (!in_array($range, [10, 30, 50, 100], true)) {
    $range = 10;
}

$data = bingo_service_analysis($range);

auth_json_response(true, $data, null);