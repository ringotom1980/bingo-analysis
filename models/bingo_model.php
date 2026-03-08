<?php
declare(strict_types=1);

/*
 * Path: models/bingo_model.php
 * 說明：賓果賓果資料查詢模型
 */

function bingo_numbers_to_array(string $numbers): array
{
    return array_map('intval', explode(',', $numbers));
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

    $row['numbers'] = bingo_numbers_to_array($row['numbers']);

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
        $r['numbers'] = bingo_numbers_to_array($r['numbers']);
    }

    return $rows;
}

function bingo_get_hot(PDO $pdo): array
{
    return $pdo->query("
        SELECT number, hit_count
        FROM bingo_statistics
        ORDER BY hit_count DESC
        LIMIT 5
    ")->fetchAll();
}

function bingo_get_cold(PDO $pdo): array
{
    return $pdo->query("
        SELECT number, hit_count
        FROM bingo_statistics
        ORDER BY hit_count ASC
        LIMIT 5
    ")->fetchAll();
}

function bingo_get_miss(PDO $pdo): array
{
    return $pdo->query("
        SELECT number,
        MAX(draw_term) last_hit
        FROM bingo_draw_numbers
        GROUP BY number
        ORDER BY last_hit ASC
        LIMIT 5
    ")->fetchAll();
}

function bingo_today_count(PDO $pdo): int
{
    $row = $pdo->query("
        SELECT COUNT(*) c
        FROM bingo_results
        WHERE DATE(draw_at)=CURDATE()
    ")->fetch();

    return (int)$row['c'];
}