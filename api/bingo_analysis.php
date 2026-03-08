<?php
declare(strict_types=1);

/*
 * Path: api/bingo_analysis.php
 * 說明：熱號、冷號、遺漏統計
 */

require_once __DIR__.'/../services/auth_service.php';
require_once __DIR__.'/../services/bingo_service.php';

auth_require_login();

$data=bingo_service_analysis();

auth_json_response(true,$data,null);