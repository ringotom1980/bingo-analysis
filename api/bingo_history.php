<?php
declare(strict_types=1);

/*
 * Path: api/bingo_history.php
 * 說明：取得最近幾期
 */

require_once __DIR__.'/../services/auth_service.php';
require_once __DIR__.'/../services/bingo_service.php';

auth_require_login();

$limit=(int)($_GET['limit']??5);

$data=bingo_service_history($limit);

auth_json_response(true,$data,null);