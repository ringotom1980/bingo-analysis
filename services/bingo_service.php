<?php
declare(strict_types=1);

/*
 * Path: services/bingo_service.php
 * 說明：賓果賓果服務層
 */

require_once __DIR__.'/../db.php';
require_once __DIR__.'/../models/bingo_model.php';

function bingo_service_latest(): array
{
    global $pdo;

    $row = bingo_get_latest($pdo);

    return [
        'issue_no' => $row['draw_term'],
        'draw_time' => $row['draw_at'],
        'updated_at' => $row['created_at'],
        'numbers' => $row['numbers']
    ];
}

function bingo_service_history(int $limit): array
{
    global $pdo;

    return [
        'today_count' => bingo_today_count($pdo),
        'list' => bingo_get_history($pdo,$limit)
    ];
}

function bingo_service_analysis(): array
{
    global $pdo;

    return [
        'status'=>'正常',
        'hot_top5'=>bingo_get_hot($pdo),
        'cold_top5'=>bingo_get_cold($pdo),
        'miss_top5'=>bingo_get_miss($pdo)
    ];
}