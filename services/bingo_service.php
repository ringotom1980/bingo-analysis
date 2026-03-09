<?php
declare(strict_types=1);

/*
 * Path: services/bingo_service.php
 * 說明：賓果賓果服務層，負責最新一期、歷史開獎、區間分析與系統推薦分析。
 */

require_once __DIR__ . '/../db.php';
require_once __DIR__ . '/../models/bingo_model.php';
require_once __DIR__ . '/bingo_algorithm_service.php';

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

function bingo_service_analysis(int $range = 100, int $star = 5, string $mode = 'balanced'): array
{
    global $pdo;

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

    $draws = bingo_get_recent_draws($pdo, $range);

    if (empty($draws)) {
        return [
            'range' => $range,
            'star' => $star,
            'hot_top10' => [],
            'cold_top10' => [],
            'streak_top10' => [],
            'miss_top10' => [],
            'odd_even_stats' => [
                'odd_count' => 0,
                'even_count' => 0,
            ],
            'big_small_stats' => [
                'small_count' => 0,
                'big_count' => 0,
            ],
            'uptrend_top10' => [],
            'downtrend_top10' => [],
            'pair_stats' => [],
            'tail_stats' => [],
            'recommended_numbers' => [],
            'recommended_reasons' => [],
            'recommended_score' => 0,
            'hit_summary' => [],
            'hit_trace' => [],
        ];
    }

    $featuresAssoc = bingo_algo_build_features($draws);
    $featuresList  = array_values($featuresAssoc);

    $basicStats = bingo_algo_build_basic_stats($draws);
    $recommend  = bingo_algo_recommend($draws, $star, $mode);
    $backtest   = bingo_algo_backtest($recommend['recommended_numbers'], $draws, $star);

    return [
        'range' => $range,
        'star'  => $star,

        'hot_top10'      => bingo_algo_build_top_rows($featuresList, 'hit_count', 10, true),
        'cold_top10'     => bingo_algo_build_top_rows($featuresList, 'hit_count', 10, false),
        'streak_top10'   => bingo_algo_build_top_rows($featuresList, 'streak', 10, true),
        'miss_top10'     => bingo_algo_build_top_rows($featuresList, 'miss', 10, true),
        'uptrend_top10'  => bingo_algo_build_top_rows($featuresList, 'uptrend_value', 10, true),
        'downtrend_top10'=> bingo_algo_build_top_rows($featuresList, 'downtrend_value', 10, true),

        'odd_even_stats' => [
            'odd_count'  => $basicStats['odd_count'] ?? 0,
            'even_count' => $basicStats['even_count'] ?? 0,
        ],
        'big_small_stats' => [
            'small_count' => $basicStats['small_count'] ?? 0,
            'big_count'   => $basicStats['big_count'] ?? 0,
        ],

        'pair_stats' => $recommend['pair_stats'] ?? [],
        'tail_stats' => $recommend['tail_stats'] ?? [],

        'recommended_numbers'  => $recommend['recommended_numbers'] ?? [],
        'recommended_reasons'  => $recommend['recommended_reasons'] ?? [],
        'recommended_score'    => $recommend['recommended_score'] ?? 0,

        'hit_summary' => $backtest['hit_summary'] ?? [],
        'hit_trace'   => $backtest['hit_trace'] ?? [],
    ];
}