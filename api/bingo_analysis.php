<?php
declare(strict_types=1);

/*
 * Path: api/bingo_analysis.php
 * 說明：賓果賓果分析 API，依分析期數與推薦星數回傳完整統計分析、推薦組合與回朔命中資料。
 */

require_once __DIR__ . '/../services/auth_service.php';
require_once __DIR__ . '/../services/bingo_service.php';

auth_require_login();

$range = (int)($_GET['range'] ?? 100);
$star  = (int)($_GET['star'] ?? 5);
$mode  = trim((string)($_GET['mode'] ?? 'balanced'));

if ($range < 10) {
    $range = 10;
}
if ($range > 500) {
    $range = 500;
}

if ($star < 1) {
    $star = 1;
}
if ($star > 10) {
    $star = 10;
}

$allowModes = ['balanced'];
if (!in_array($mode, $allowModes, true)) {
    $mode = 'balanced';
}

$data = bingo_service_analysis($range, $star, $mode);

auth_json_response(true, $data, null);