<?php
/* Path: fetch_bingo.php
 * 說明：
 * 1. 抓取今天最新一期 Bingo Bingo
 * 2. 若 draw_term 不存在，寫入 bingo_results
 * 3. 拆 20 顆號碼寫入 bingo_draw_numbers
 * 4. 更新 bingo_statistics.hit_count
 */

declare(strict_types=1);

date_default_timezone_set('Asia/Taipei');

require __DIR__ . '/db.php';

function fetch_json(string $url): array
{
    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_TIMEOUT => 30,
        CURLOPT_HTTPHEADER => [
            'Accept: application/json, text/plain, */*',
            'Origin: https://www.taiwanlottery.com',
            'Referer: https://www.taiwanlottery.com/',
            'User-Agent: Mozilla/5.0'
        ],
    ]);

    $res  = curl_exec($ch);
    $err  = curl_error($ch);
    $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($err) {
        throw new RuntimeException('CURL ERROR: ' . $err);
    }

    if ($code !== 200) {
        throw new RuntimeException('HTTP CODE: ' . $code);
    }

    $data = json_decode((string)$res, true);
    if (!is_array($data)) {
        throw new RuntimeException('JSON 解析失敗');
    }

    return $data;
}

function normalize_numbers(array $numbers): array
{
    $out = [];

    foreach ($numbers as $n) {
        $v = (int)$n;
        if ($v < 1 || $v > 80) {
            throw new RuntimeException('號碼超出範圍: ' . $n);
        }
        $out[] = $v;
    }

    return $out;
}

try {
    $date = date('Y-m-d');
    $url  = "https://api.taiwanlottery.com/TLCAPIWeB/Lottery/BingoResult?openDate={$date}&pageNum=1&pageSize=1";

    $data = fetch_json($url);

    if (($data['rtCode'] ?? -1) !== 0) {
        throw new RuntimeException('API 回傳失敗');
    }

    $list = $data['content']['bingoQueryResult'] ?? [];
    if (!is_array($list) || count($list) === 0) {
        throw new RuntimeException('查無資料');
    }

    $latest = $list[0];

    $drawTerm      = (int)($latest['drawTerm'] ?? 0);
    $bigShowOrder  = normalize_numbers($latest['bigShowOrder'] ?? []);
    $numbersString = implode(',', array_map(function ($n) {
        return str_pad((string)$n, 2, '0', STR_PAD_LEFT);
    }, $bigShowOrder));

    if ($drawTerm <= 0) {
        throw new RuntimeException('drawTerm 無效');
    }

    if (count($bigShowOrder) !== 20) {
        throw new RuntimeException('bigShowOrder 筆數不是 20');
    }

    $stmt = $pdo->prepare("SELECT id FROM bingo_results WHERE draw_term = ?");
    $stmt->execute([$drawTerm]);
    $exists = $stmt->fetch();

    if ($exists) {
        echo "EXISTS: {$drawTerm}";
        exit;
    }

    $pdo->beginTransaction();

    $stmt = $pdo->prepare("
        INSERT INTO bingo_results (draw_term, numbers)
        VALUES (?, ?)
    ");
    $stmt->execute([$drawTerm, $numbersString]);

    $stmtDraw = $pdo->prepare("
        INSERT INTO bingo_draw_numbers (draw_term, number)
        VALUES (?, ?)
    ");

    foreach ($bigShowOrder as $num) {
        $stmtDraw->execute([$drawTerm, $num]);
    }

    $stmtStat = $pdo->prepare("
        UPDATE bingo_statistics
        SET hit_count = hit_count + 1
        WHERE number = ?
    ");

    foreach ($bigShowOrder as $num) {
        $stmtStat->execute([$num]);
    }

    $pdo->commit();

    echo "INSERTED: {$drawTerm} | {$numbersString}";
} catch (Throwable $e) {
    if (isset($pdo) && $pdo instanceof PDO && $pdo->inTransaction()) {
        $pdo->rollBack();
    }

    http_response_code(500);
    echo 'ERROR: ' . $e->getMessage();
}