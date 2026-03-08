<?php
declare(strict_types=1);

/* Path: logout.php */

require_once __DIR__ . '/services/auth_service.php';

auth_logout_now();

header('Location: /login.php');
exit;