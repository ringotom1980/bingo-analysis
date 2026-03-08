<?php
declare(strict_types=1);

/*
 * Path: models/bingo_model.php
 * 說明：賓果賓果資料查詢模型，負責最新一期、歷史開獎、區間分析、未出現期數與最佳組合回朔所需資料。
 */

function bingo_numbers_to_array(string $numbers): array
{
    $parts = array_map('trim', explode(',', $numbers));
    $out   = [];

    foreach ($parts as $part) {
        $n = (int)$part;
        if ($n >= 1 && $n <= 80) {
            $out[] = $n;
        }
    }

    sort($out, SORT_NUMERIC);

    return $out;
}

function bingo_get_latest(PDO $pdo): ?array
{
    $sql = "
    SELECT draw_term, draw_at, numbers, created_at
    FROM bingo_results
    ORDER BY draw_term DESC
    LIMIT 1
    ";

    $row = $pdo->query($sql)->fetch();

    if (!$row) {
        return null;
    }

    $row['numbers'] = bingo_numbers_to_array((string)$row['numbers']);

    return $row;
}

function bingo_get_history(PDO $pdo, int $limit): array
{
    $sql = "
    SELECT draw_term, draw_at, numbers
    FROM bingo_results
    ORDER BY draw_term DESC
    LIMIT :limit
    ";

    $stmt = $pdo->prepare($sql);
    $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
    $stmt->execute();

    $rows = $stmt->fetchAll();

    foreach ($rows as &$r) {
        $r['numbers'] = bingo_numbers_to_array((string)$r['numbers']);
    }

    return $rows;
}

function bingo_get_history_by_term_range(PDO $pdo, int $startTerm, int $endTerm): array
{
    $sql = "
    SELECT draw_term, draw_at, numbers
    FROM bingo_results
    WHERE draw_term BETWEEN :start_term AND :end_term
    ORDER BY draw_term DESC
    ";

    $stmt = $pdo->prepare($sql);
    $stmt->bindValue(':start_term', $startTerm, PDO::PARAM_INT);
    $stmt->bindValue(':end_term', $endTerm, PDO::PARAM_INT);
    $stmt->execute();

    $rows = $stmt->fetchAll();

    foreach ($rows as &$r) {
        $r['numbers'] = bingo_numbers_to_array((string)$r['numbers']);
    }

    return $rows;
}

function bingo_get_recent_draws(PDO $pdo, int $limit): array
{
    return bingo_get_history($pdo, $limit);
}

function bingo_get_draws_by_recent_hours(PDO $pdo, int $hours): array
{
    $sql = "
    SELECT draw_term, draw_at, numbers
    FROM bingo_results
    WHERE draw_at >= DATE_SUB(NOW(), INTERVAL :hours HOUR)
    ORDER BY draw_term DESC
    ";

    $stmt = $pdo->prepare($sql);
    $stmt->bindValue(':hours', $hours, PDO::PARAM_INT);
    $stmt->execute();

    $rows = $stmt->fetchAll();

    foreach ($rows as &$r) {
        $r['numbers'] = bingo_numbers_to_array((string)$r['numbers']);
    }

    return $rows;
}

function bingo_build_hit_counter(array $draws): array
{
    $counter = [];

    for ($i = 1; $i <= 80; $i++) {
        $counter[$i] = 0;
    }

    foreach ($draws as $draw) {
        $numbers = isset($draw['numbers']) && is_array($draw['numbers']) ? $draw['numbers'] : [];
        foreach ($numbers as $n) {
            $n = (int)$n;
            if ($n >= 1 && $n <= 80) {
                $counter[$n]++;
            }
        }
    }

    return $counter;
}

function bingo_build_hot_top(array $draws, int $limit = 10): array
{
    $counter = bingo_build_hit_counter($draws);
    $rows    = [];

    foreach ($counter as $number => $hitCount) {
        $rows[] = [
            'number'    => $number,
            'hit_count' => $hitCount
        ];
    }

    usort($rows, function (array $a, array $b): int {
        if ($a['hit_count'] === $b['hit_count']) {
            return $a['number'] <=> $b['number'];
        }
        return $b['hit_count'] <=> $a['hit_count'];
    });

    return array_slice($rows, 0, $limit);
}

function bingo_build_cold_top(array $draws, int $limit = 10): array
{
    $counter = bingo_build_hit_counter($draws);
    $rows    = [];

    foreach ($counter as $number => $hitCount) {
        $rows[] = [
            'number'    => $number,
            'hit_count' => $hitCount
        ];
    }

    usort($rows, function (array $a, array $b): int {
        if ($a['hit_count'] === $b['hit_count']) {
            return $a['number'] <=> $b['number'];
        }
        return $a['hit_count'] <=> $b['hit_count'];
    });

    return array_slice($rows, 0, $limit);
}

function bingo_build_basic_stats(array $draws): array
{
    $oddCount  = 0;
    $evenCount = 0;
    $lowCount  = 0;
    $highCount = 0;

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
                $lowCount++;
            } else {
                $highCount++;
            }
        }
    }

    return [
        'odd_count'  => $oddCount,
        'even_count' => $evenCount,
        'low_count'  => $lowCount,
        'high_count' => $highCount
    ];
}

function bingo_get_miss_top(PDO $pdo, int $limit = 10): array
{
    $sql = "
        SELECT
            nums.number,
            COALESCE(latest.latest_term - hit.last_hit, latest.latest_term) AS miss,
            hit.last_hit
        FROM (
            SELECT 1 AS number UNION ALL SELECT 2 UNION ALL SELECT 3 UNION ALL SELECT 4 UNION ALL SELECT 5
            UNION ALL SELECT 6 UNION ALL SELECT 7 UNION ALL SELECT 8 UNION ALL SELECT 9 UNION ALL SELECT 10
            UNION ALL SELECT 11 UNION ALL SELECT 12 UNION ALL SELECT 13 UNION ALL SELECT 14 UNION ALL SELECT 15
            UNION ALL SELECT 16 UNION ALL SELECT 17 UNION ALL SELECT 18 UNION ALL SELECT 19 UNION ALL SELECT 20
            UNION ALL SELECT 21 UNION ALL SELECT 22 UNION ALL SELECT 23 UNION ALL SELECT 24 UNION ALL SELECT 25
            UNION ALL SELECT 26 UNION ALL SELECT 27 UNION ALL SELECT 28 UNION ALL SELECT 29 UNION ALL SELECT 30
            UNION ALL SELECT 31 UNION ALL SELECT 32 UNION ALL SELECT 33 UNION ALL SELECT 34 UNION ALL SELECT 35
            UNION ALL SELECT 36 UNION ALL SELECT 37 UNION ALL SELECT 38 UNION ALL SELECT 39 UNION ALL SELECT 40
            UNION ALL SELECT 41 UNION ALL SELECT 42 UNION ALL SELECT 43 UNION ALL SELECT 44 UNION ALL SELECT 45
            UNION ALL SELECT 46 UNION ALL SELECT 47 UNION ALL SELECT 48 UNION ALL SELECT 49 UNION ALL SELECT 50
            UNION ALL SELECT 51 UNION ALL SELECT 52 UNION ALL SELECT 53 UNION ALL SELECT 54 UNION ALL SELECT 55
            UNION ALL SELECT 56 UNION ALL SELECT 57 UNION ALL SELECT 58 UNION ALL SELECT 59 UNION ALL SELECT 60
            UNION ALL SELECT 61 UNION ALL SELECT 62 UNION ALL SELECT 63 UNION ALL SELECT 64 UNION ALL SELECT 65
            UNION ALL SELECT 66 UNION ALL SELECT 67 UNION ALL SELECT 68 UNION ALL SELECT 69 UNION ALL SELECT 70
            UNION ALL SELECT 71 UNION ALL SELECT 72 UNION ALL SELECT 73 UNION ALL SELECT 74 UNION ALL SELECT 75
            UNION ALL SELECT 76 UNION ALL SELECT 77 UNION ALL SELECT 78 UNION ALL SELECT 79 UNION ALL SELECT 80
        ) nums
        CROSS JOIN (
            SELECT MAX(draw_term) AS latest_term
            FROM bingo_results
        ) latest
        LEFT JOIN (
            SELECT number, MAX(draw_term) AS last_hit
            FROM bingo_draw_numbers
            GROUP BY number
        ) hit ON hit.number = nums.number
        ORDER BY miss DESC, nums.number ASC
        LIMIT :limit
    ";

    $stmt = $pdo->prepare($sql);
    $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
    $stmt->execute();

    return $stmt->fetchAll();
}

function bingo_build_combo_recommendation(array $draws, int $star): array
{
    $hot = bingo_build_hot_top($draws, $star);
    $out = [];

    foreach ($hot as $row) {
        $out[] = (int)$row['number'];
    }

    sort($out, SORT_NUMERIC);

    return $out;
}

function bingo_build_combo_trace(array $draws, array $selectedNumbers): array
{
    $selected = [];
    $trace    = [];

    foreach ($selectedNumbers as $n) {
        $n = (int)$n;
        if ($n >= 1 && $n <= 80) {
            $selected[$n] = true;
        }
    }

    foreach ($draws as $draw) {
        $numbers = isset($draw['numbers']) && is_array($draw['numbers']) ? $draw['numbers'] : [];
        $hit     = 0;

        foreach ($numbers as $n) {
            $n = (int)$n;
            if (isset($selected[$n])) {
                $hit++;
            }
        }

        $trace[] = [
            'issue_no'   => $draw['draw_term'] ?? null,
            'draw_term'  => $draw['draw_term'] ?? null,
            'draw_time'  => $draw['draw_at'] ?? null,
            'hit_count'  => $hit,
            'numbers'    => $numbers
        ];
    }

    return $trace;
}

function bingo_build_combo_hit_stats(array $traceList, int $star): array
{
    $stats = [];

    for ($i = 0; $i <= $star; $i++) {
        $stats[$i] = 0;
    }

    foreach ($traceList as $row) {
        $hit = (int)($row['hit_count'] ?? 0);

        if ($hit < 0) {
            $hit = 0;
        }
        if ($hit > $star) {
            $hit = $star;
        }

        $stats[$hit]++;
    }

    return $stats;
}

function bingo_today_count(PDO $pdo): int
{
    $row = $pdo->query("
        SELECT COUNT(*) c
        FROM bingo_results
        WHERE DATE(draw_at) = CURDATE()
    ")->fetch();

    return (int)($row['c'] ?? 0);
}