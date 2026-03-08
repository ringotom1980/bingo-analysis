<?php
declare(strict_types=1);

/*
 * Path: api/bingo_latest.php
 * 說明：取得最新一期開獎
 */

require_once __DIR__.'/../services/auth_service.php';
require_once __DIR__.'/../services/bingo_service.php';

auth_require_login();

$data=bingo_service_latest();

auth_json_response(true,$data,null);