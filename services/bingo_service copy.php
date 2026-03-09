<?php
declare(strict_types=1);

/*
 * Path: services/bingo_service.php
 * 說明：賓果賓果服務層，負責最新一期、歷史開獎、區間分析與最佳組合分析。
 */

require_once __DIR__ . '/../db.php';
require_once __DIR__ . '/../models/bingo_model.php';

function bingo_service_latest(): array
{
    global $pdo;

    $row = bingo_get_latest($pdo);

    if (!$row) {
        return [
            'issue_no'   => null,
            'draw_time'  => null,
            'updated_at' => null,
            'numbers'    => []
        ];
    }

    return [
        'issue_no'   => $row['draw_term'],
        'draw_time'  => $row['draw_at'],
        'updated_at' => $row['created_at'],
        'numbers'    => $row['numbers']
    ];
}

function bingo_service_history(int $limit = 10, ?int $startTerm = null, ?int $endTerm = null): array
{
    global $pdo;

    if ($startTerm !== null && $endTerm !== null) {
        if ($startTerm > $endTerm) {
            [$startTerm, $endTerm] = [$endTerm, $startTerm];
        }

        return [
            'today_count' => bingo_today_count($pdo),
            'list'        => bingo_get_history_by_term_range($pdo, $startTerm, $endTerm)
        ];
    }

    if ($limit < 1) {
        $limit = 10;
    }

    return [
        'today_count' => bingo_today_count($pdo),
        'list'        => bingo_get_history($pdo, $limit)
    ];
}

function bingo_service_analysis(int $range = 10): array
{
    global $pdo;

    if (!in_array($range, [10, 30, 50, 100], true)) {
        $range = 10;
    }

    $draws = bingo_get_recent_draws($pdo, $range);

    return [
        'range'      => $range,
        'hot_top10'  => bingo_build_hot_top($draws, 10),
        'cold_top10' => bingo_build_cold_top($draws, 10),
        'miss_top10' => bingo_get_miss_top($pdo, 10),
        'stats'      => bingo_build_basic_stats($draws)
    ];
}

function bingo_service_combo_analysis(int $hours = 3, int $star = 5): array
{
    global $pdo;

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

    $draws = bingo_get_draws_by_recent_hours($pdo, $hours);

    $recommended = bingo_build_combo_recommendation($draws, $star);
    $traceList   = bingo_build_combo_trace($draws, $recommended);
    $hitStats    = bingo_build_combo_hit_stats($traceList, $star);

    return [
        'hours'               => $hours,
        'star'                => $star,
        'recommended_numbers' => $recommended,
        'hit_stats'           => $hitStats,
        'trace_list'          => $traceList
    ];
}