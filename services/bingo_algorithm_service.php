<?php

declare(strict_types=1);

/*
 * Path: services/bingo_algorithm_service.php
 * 說明：賓果賓果演算法服務，負責特徵計算、冷熱號/連出/未出現/升降溫/尾數/連號統計、
 * 推薦組合、推薦理由與回朔命中分析。
 */

function bingo_algo_build_features(array $draws): array
{
    $features = [];
    $drawCount = count($draws);

    for ($n = 1; $n <= 80; $n++) {
        $features[$n] = [
            'number'          => $n,
            'hit_count'       => 0,
            'miss'            => 0,
            'streak'          => 0,
            'hit_10'          => 0,
            'hit_30'          => 0,
            'hit_50'          => 0,
            'uptrend_value'   => 0.0,
            'downtrend_value' => 0.0,
            'zone'            => bingo_algo_number_zone($n),
            'tail'            => $n % 10,
            'pair_strength'   => 0,
            'weighted_hot'    => 0.0,
            'score'           => 0.0,
            'score_breakdown' => [],
        ];
    }

    foreach ($draws as $index => $draw) {
        $numbers = isset($draw['numbers']) && is_array($draw['numbers']) ? $draw['numbers'] : [];
        $numbers = array_map('intval', $numbers);
        $numberMap = array_fill_keys($numbers, true);

        foreach ($numbers as $n) {
            if ($n < 1 || $n > 80) {
                continue;
            }

            $features[$n]['hit_count']++;

            if ($index < 10) {
                $features[$n]['hit_10']++;
            }
            if ($index < 30) {
                $features[$n]['hit_30']++;
            }
            if ($index < 50) {
                $features[$n]['hit_50']++;
            }

            if (
                ($n - 1) >= 1 && isset($numberMap[$n - 1])
            ) {
                $features[$n]['pair_strength']++;
            }

            if (
                ($n + 1) <= 80 && isset($numberMap[$n + 1])
            ) {
                $features[$n]['pair_strength']++;
            }
        }
    }

    for ($n = 1; $n <= 80; $n++) {
        $features[$n]['streak'] = bingo_algo_calc_streak($draws, $n);
        $features[$n]['miss']   = bingo_algo_calc_miss($draws, $n);

        $seg10   = (int)$features[$n]['hit_10'];
        $seg11_30 = max(0, (int)$features[$n]['hit_30'] - (int)$features[$n]['hit_10']);
        $seg31_50 = max(0, (int)$features[$n]['hit_50'] - (int)$features[$n]['hit_30']);

        $features[$n]['weighted_hot'] =
            ($seg10 * 3.0) +
            ($seg11_30 * 2.0) +
            ($seg31_50 * 1.0);

        $features[$n]['uptrend_value'] =
            round((float)$features[$n]['hit_10'] - ((float)$features[$n]['hit_50'] / 5), 4);

        $features[$n]['downtrend_value'] =
            round(((float)$features[$n]['hit_50'] / 5) - (float)$features[$n]['hit_10'], 4);
    }
    return $features;
}

function bingo_algo_number_zone(int $number): string
{
    if ($number >= 1 && $number <= 20) {
        return 'zone1';
    }
    if ($number >= 21 && $number <= 40) {
        return 'zone2';
    }
    if ($number >= 41 && $number <= 60) {
        return 'zone3';
    }
    return 'zone4';
}

function bingo_algo_calc_streak(array $draws, int $number): int
{
    $streak = 0;

    foreach ($draws as $draw) {
        $numbers = isset($draw['numbers']) && is_array($draw['numbers']) ? $draw['numbers'] : [];
        $numbers = array_map('intval', $numbers);

        if (in_array($number, $numbers, true)) {
            $streak++;
        } else {
            break;
        }
    }

    return $streak;
}

function bingo_algo_calc_miss(array $draws, int $number): int
{
    $miss = 0;

    foreach ($draws as $draw) {
        $numbers = isset($draw['numbers']) && is_array($draw['numbers']) ? $draw['numbers'] : [];
        $numbers = array_map('intval', $numbers);

        if (in_array($number, $numbers, true)) {
            return $miss;
        }

        $miss++;
    }

    return $miss;
}

function bingo_algo_build_zone_stats(array $draws): array
{
    $stats = [
        'zone1' => 0,
        'zone2' => 0,
        'zone3' => 0,
        'zone4' => 0,
    ];

    foreach ($draws as $draw) {
        $numbers = isset($draw['numbers']) && is_array($draw['numbers']) ? $draw['numbers'] : [];

        foreach ($numbers as $n) {
            $n = (int)$n;
            $zone = bingo_algo_number_zone($n);
            $stats[$zone]++;
        }
    }

    return $stats;
}

function bingo_algo_build_tail_stats(array $draws): array
{
    $counter = [];

    for ($i = 0; $i <= 9; $i++) {
        $counter[$i] = 0;
    }

    foreach ($draws as $draw) {
        $numbers = isset($draw['numbers']) && is_array($draw['numbers']) ? $draw['numbers'] : [];

        foreach ($numbers as $n) {
            $n = (int)$n;
            if ($n < 1 || $n > 80) {
                continue;
            }

            $counter[$n % 10]++;
        }
    }

    $rows = [];
    foreach ($counter as $tail => $count) {
        $rows[] = [
            'tail'  => $tail,
            'count' => $count,
        ];
    }

    usort($rows, function (array $a, array $b): int {
        if ($a['count'] === $b['count']) {
            return $a['tail'] <=> $b['tail'];
        }
        return $b['count'] <=> $a['count'];
    });

    return $rows;
}

function bingo_algo_build_pair_stats(array $draws, int $limit = 10): array
{
    $pairs = [];

    foreach ($draws as $draw) {
        $numbers = isset($draw['numbers']) && is_array($draw['numbers']) ? $draw['numbers'] : [];
        $numbers = array_map('intval', $numbers);
        sort($numbers, SORT_NUMERIC);
        $map = array_fill_keys($numbers, true);

        foreach ($numbers as $n) {
            if ($n >= 1 && $n < 80 && isset($map[$n + 1])) {
                $key = $n . '-' . ($n + 1);
                if (!isset($pairs[$key])) {
                    $pairs[$key] = 0;
                }
                $pairs[$key]++;
            }
        }
    }

    $rows = [];
    foreach ($pairs as $pair => $count) {
        $rows[] = [
            'pair'  => $pair,
            'count' => $count,
        ];
    }

    usort($rows, function (array $a, array $b): int {
        if ($a['count'] === $b['count']) {
            return strcmp($a['pair'], $b['pair']);
        }
        return $b['count'] <=> $a['count'];
    });

    return array_slice($rows, 0, $limit);
}

function bingo_algo_build_basic_stats(array $draws): array
{
    $oddCount  = 0;
    $evenCount = 0;
    $smallCount = 0;
    $bigCount = 0;

    foreach ($draws as $draw) {
        $numbers = isset($draw['numbers']) && is_array($draw['numbers']) ? $draw['numbers'] : [];

        foreach ($numbers as $n) {
            $n = (int)$n;

            if ($n < 1 || $n > 80) {
                continue;
            }

            if ($n % 2 === 0) {
                $evenCount++;
            } else {
                $oddCount++;
            }

            if ($n <= 40) {
                $smallCount++;
            } else {
                $bigCount++;
            }
        }
    }

    return [
        'odd_count'   => $oddCount,
        'even_count'  => $evenCount,
        'small_count' => $smallCount,
        'big_count'   => $bigCount,
    ];
}

function bingo_algo_normalize_rank_map(array $values, bool $desc = true): array
{
    $rows = [];

    foreach ($values as $number => $value) {
        $rows[] = [
            'number' => (int)$number,
            'value'  => (float)$value,
        ];
    }

    usort($rows, function (array $a, array $b) use ($desc): int {
        if ($a['value'] === $b['value']) {
            return $a['number'] <=> $b['number'];
        }

        return $desc
            ? ($b['value'] <=> $a['value'])
            : ($a['value'] <=> $b['value']);
    });

    $total = count($rows);
    $map = [];

    if ($total <= 1) {
        foreach ($rows as $row) {
            $map[$row['number']] = 100.0;
        }
        return $map;
    }

    foreach ($rows as $idx => $row) {
        $map[$row['number']] = 100 - (($idx / ($total - 1)) * 100);
    }

    return $map;
}

function bingo_algo_calc_miss_score(int $miss): float
{
    if ($miss <= 2) {
        return 10.0;
    }
    if ($miss <= 8) {
        return 40.0 + (($miss - 3) * 6.0);
    }
    if ($miss <= 15) {
        return 76.0 + (($miss - 9) * 4.0);
    }
    return 100.0;
}

function bingo_algo_calc_streak_score(int $streak): float
{
    if ($streak <= 0) {
        return 0.0;
    }
    if ($streak === 1) {
        return 40.0;
    }
    if ($streak === 2) {
        return 70.0;
    }
    return 100.0;
}

function bingo_algo_score_numbers(array $features, array $context, string $mode = 'balanced'): array
{
    $hitValues = [];
    $uptrendValues = [];
    $downtrendValues = [];
    $pairValues = [];

    foreach ($features as $number => $row) {
        $hitValues[$number] = (float)($row['weighted_hot'] ?? 0);
        $uptrendValues[$number] = (float)$row['uptrend_value'];
        $downtrendValues[$number] = (float)$row['downtrend_value'];
        $pairValues[$number] = (float)$row['pair_strength'];
    }
    $hotRankMap = bingo_algo_normalize_rank_map($hitValues, true);
    $uptrendRankMap = bingo_algo_normalize_rank_map($uptrendValues, true);
    $downtrendRankMap = bingo_algo_normalize_rank_map($downtrendValues, true);
    $pairRankMap = bingo_algo_normalize_rank_map($pairValues, true);

    $zoneStats = $context['zone_stats'] ?? [];
    $tailStats = $context['tail_hotness'] ?? [];

    $zoneRows = [];
    foreach ($zoneStats as $zone => $count) {
        $zoneRows[$zone] = (float)$count;
    }

    $tailRows = [];
    foreach ($tailStats as $row) {
        $tailRows[(int)$row['tail']] = (float)$row['count'];
    }

    $zoneRank = [];
    if (!empty($zoneRows)) {
        $zoneScoreRows = [];
        $zoneIndex = 1;
        foreach ($zoneRows as $zone => $count) {
            $zoneScoreRows[$zoneIndex] = $count;
            $zoneIndex++;
        }
        $tmp = bingo_algo_normalize_rank_map($zoneScoreRows, true);
        $zoneRank = [
            'zone1' => $tmp[1] ?? 50.0,
            'zone2' => $tmp[2] ?? 50.0,
            'zone3' => $tmp[3] ?? 50.0,
            'zone4' => $tmp[4] ?? 50.0,
        ];
    }

    $tailRank = [];
    if (!empty($tailRows)) {
        $tailRank = bingo_algo_normalize_rank_map($tailRows, true);
    }

    foreach ($features as $number => &$row) {
        $hotScore = $hotRankMap[$number] ?? 0.0;
        $uptrendScore = $uptrendRankMap[$number] ?? 0.0;
        $missScore = bingo_algo_calc_miss_score((int)$row['miss']);
        $streakScore = bingo_algo_calc_streak_score((int)$row['streak']);
        $downtrendPenalty = $downtrendRankMap[$number] ?? 0.0;

        $zoneAdjust = (($zoneRank[$row['zone']] ?? 50.0) / 100) * 5.0;
        $tailAdjust = (($tailRank[$row['tail']] ?? 50.0) / 100) * 3.0;
        $pairAdjust = (($pairRankMap[$number] ?? 50.0) / 100) * 2.0;

        $score =
            ($hotScore * 0.30) +
            ($uptrendScore * 0.20) +
            ($missScore * 0.15) +
            ($streakScore * 0.15) -
            ($downtrendPenalty * 0.10) +
            $zoneAdjust +
            $tailAdjust +
            $pairAdjust;

        $row['score'] = round($score, 4);
        $row['score_breakdown'] = [
            'hot'              => round($hotScore, 4),
            'uptrend'          => round($uptrendScore, 4),
            'miss'             => round($missScore, 4),
            'streak'           => round($streakScore, 4),
            'downtrendPenalty' => round($downtrendPenalty, 4),
            'zoneAdjust'       => round($zoneAdjust, 4),
            'tailAdjust'       => round($tailAdjust, 4),
            'pairAdjust'       => round($pairAdjust, 4),
        ];
    }
    unset($row);

    usort($features, function (array $a, array $b): int {
        if ($a['score'] === $b['score']) {
            return $a['number'] <=> $b['number'];
        }
        return $b['score'] <=> $a['score'];
    });

    return array_values($features);
}

function bingo_algo_build_reasons(array $featureRow): array
{
    $tags = [];

    if (($featureRow['score_breakdown']['hot'] ?? 0) >= 70) {
        $tags[] = '熱號';
    }

    if (($featureRow['score_breakdown']['uptrend'] ?? 0) >= 70) {
        $tags[] = '升溫';
    }

    if (($featureRow['miss'] ?? 0) >= 5) {
        $tags[] = 'miss回補';
    }

    if (($featureRow['streak'] ?? 0) >= 1) {
        $tags[] = '連出';
    }

    if (($featureRow['score_breakdown']['zoneAdjust'] ?? 0) >= 3.5) {
        $tags[] = '區段活躍';
    }

    if (($featureRow['score_breakdown']['tailAdjust'] ?? 0) >= 2.0) {
        $tags[] = '尾數活躍';
    }

    if (($featureRow['pair_strength'] ?? 0) >= 2) {
        $tags[] = '連號活躍';
    }

    if (empty($tags)) {
        $tags[] = '綜合分數高';
    }

    return array_slice(array_values(array_unique($tags)), 0, 3);
}

function bingo_algo_build_candidate_combos(array $scoredNumbers, int $star): array
{
    $candidateSize = max($star * 3, 12);
    $pool = array_slice($scoredNumbers, 0, $candidateSize);

    $poolNumbers = [];
    foreach ($pool as $row) {
        $poolNumbers[] = (int)$row['number'];
    }

    $base = array_slice($poolNumbers, 0, $star);
    $combos = [];

    if (count($base) === $star) {
        sort($base, SORT_NUMERIC);
        $combos[] = $base;
    }

    $poolCount = count($poolNumbers);
    $replaceTailCount = min(2, $star);

    for ($i = $star; $i < $poolCount; $i++) {
        $combo = $base;

        for ($j = 0; $j < $replaceTailCount; $j++) {
            $replaceIndex = $star - 1 - $j;
            $sourceIndex = $i - $j;

            if ($replaceIndex >= 0 && $sourceIndex >= $star && isset($poolNumbers[$sourceIndex])) {
                $combo[$replaceIndex] = $poolNumbers[$sourceIndex];
            }
        }

        $combo = array_values(array_unique(array_map('intval', $combo)));
        sort($combo, SORT_NUMERIC);

        if (count($combo) === $star) {
            $combos[] = $combo;
        }
    }

    $uniq = [];
    $out = [];

    foreach ($combos as $combo) {
        $key = implode('-', $combo);
        if (!isset($uniq[$key])) {
            $uniq[$key] = true;
            $out[] = $combo;
        }
    }

    return $out;
}

function bingo_algo_score_combo(array $combo, array $scoreMap, array $context): float
{
    $total = 0.0;
    $zones = [];
    $odd = 0;
    $even = 0;
    $tails = [];
    $pairCount = 0;

    foreach ($combo as $n) {
        $n = (int)$n;
        $total += (float)($scoreMap[$n] ?? 0.0);
        $zones[bingo_algo_number_zone($n)] = true;

        if ($n % 2 === 0) {
            $even++;
        } else {
            $odd++;
        }

        $tail = $n % 10;
        if (!isset($tails[$tail])) {
            $tails[$tail] = 0;
        }
        $tails[$tail]++;
    }

    sort($combo, SORT_NUMERIC);
    for ($i = 1; $i < count($combo); $i++) {
        if ($combo[$i] === ($combo[$i - 1] + 1)) {
            $pairCount++;
        }
    }

    $zoneCount = count($zones);
    $zoneNeed = $context['star'] <= 5 ? 2 : 3;
    if ($zoneCount < $zoneNeed) {
        $total -= 12.0;
    }

    if ($odd === 0 || $even === 0) {
        $total -= 8.0;
    } elseif (abs($odd - $even) >= max(3, (int)floor(count($combo) / 2))) {
        $total -= 4.0;
    }

    foreach ($tails as $count) {
        if ($count >= 3) {
            $total -= ($count - 2) * 2.5;
        }
    }

    if ($pairCount >= 2) {
        $total -= ($pairCount - 1) * 3.0;
    }

    return round($total, 4);
}

function bingo_algo_recommend(array $draws, int $star, string $mode = 'balanced'): array
{
    $features = bingo_algo_build_features($draws);
    $zoneStats = bingo_algo_build_zone_stats($draws);
    $tailStats = bingo_algo_build_tail_stats($draws);
    $pairStats = bingo_algo_build_pair_stats($draws, 10);

    $scored = bingo_algo_score_numbers(
        array_values($features),
        [
            'zone_stats'   => $zoneStats,
            'tail_hotness' => $tailStats,
            'pair_stats'   => $pairStats,
            'star'         => $star,
        ],
        $mode
    );

    $scoreMap = [];
    foreach ($scored as $row) {
        $scoreMap[(int)$row['number']] = (float)$row['score'];
    }

    $candidateCombos = bingo_algo_build_candidate_combos($scored, $star);

    $bestCombo = [];
    $bestScore = null;

    foreach ($candidateCombos as $combo) {
        $comboScore = bingo_algo_score_combo($combo, $scoreMap, ['star' => $star]);

        if ($bestScore === null || $comboScore > $bestScore) {
            $bestScore = $comboScore;
            $bestCombo = $combo;
        }
    }

    if (empty($bestCombo)) {
        $bestCombo = array_slice(array_map(function ($row) {
            return (int)$row['number'];
        }, $scored), 0, $star);

        sort($bestCombo, SORT_NUMERIC);
        $bestScore = bingo_algo_score_combo($bestCombo, $scoreMap, ['star' => $star]);
    }

    $reasonRows = [];
    foreach ($scored as $row) {
        if (in_array((int)$row['number'], $bestCombo, true)) {
            $reasonRows[] = [
                'number'  => (int)$row['number'],
                'reasons' => bingo_algo_build_reasons($row),
                'score'   => (float)$row['score'],
            ];
        }
    }

    usort($reasonRows, function (array $a, array $b): int {
        return $a['number'] <=> $b['number'];
    });

    return [
        'recommended_numbers' => $bestCombo,
        'recommended_reasons' => $reasonRows,
        'recommended_score'   => round((float)$bestScore, 4),
        'scored_numbers'      => $scored,
        'zone_stats'          => $zoneStats,
        'tail_stats'          => $tailStats,
        'pair_stats'          => $pairStats,
    ];
}

function bingo_algo_backtest(array $recommendedNumbers, array $draws, int $star): array
{
    $selected = [];
    foreach ($recommendedNumbers as $n) {
        $n = (int)$n;
        if ($n >= 1 && $n <= 80) {
            $selected[$n] = true;
        }
    }

    $hitSummary = [];
    for ($i = 0; $i <= $star; $i++) {
        $hitSummary[(string)$i] = 0;
    }

    $hitTrace = [];

    foreach ($draws as $draw) {
        $numbers = isset($draw['numbers']) && is_array($draw['numbers']) ? array_map('intval', $draw['numbers']) : [];
        $hitCount = 0;

        foreach ($numbers as $n) {
            if (isset($selected[$n])) {
                $hitCount++;
            }
        }

        if ($hitCount < 0) {
            $hitCount = 0;
        }
        if ($hitCount > $star) {
            $hitCount = $star;
        }

        $hitSummary[(string)$hitCount]++;

        $hitTrace[] = [
            'draw_term' => $draw['draw_term'] ?? null,
            'draw_at'   => $draw['draw_at'] ?? null,
            'hit_count' => $hitCount,
            'numbers'   => $numbers,
        ];
    }

    return [
        'hit_summary' => $hitSummary,
        'hit_trace'   => $hitTrace,
    ];
}

function bingo_algo_build_top_rows(array $features, string $field, int $limit = 10, bool $desc = true): array
{
    $rows = [];

    foreach ($features as $row) {
        $value = $row[$field] ?? 0;

        if (is_numeric($value)) {
            $value = round((float)$value, 2);
        }

        $rows[] = [
            'number' => (int)$row['number'],
            $field   => $value,
        ];
    }

    usort($rows, function (array $a, array $b) use ($field, $desc): int {
        if ($a[$field] == $b[$field]) {
            return $a['number'] <=> $b['number'];
        }

        return $desc
            ? ($b[$field] <=> $a[$field])
            : ($a[$field] <=> $b[$field]);
    });

    return array_slice($rows, 0, $limit);
}
