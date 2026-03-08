<?php
declare(strict_types=1);

/* Path: api/auth_logout.php */

require_once __DIR__ . '/../services/auth_service.php';

auth_logout_now();

auth_json_response(true, [
    'message' => '已登出'
], null);