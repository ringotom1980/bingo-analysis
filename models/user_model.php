<?php
declare(strict_types=1);

/* Path: models/user_model.php */

function user_find_by_username(PDO $pdo, string $username): ?array
{
    $stmt = $pdo->prepare("
        SELECT id, username, password_hash, display_name, role, is_active, created_at
        FROM users
        WHERE username = ?
        LIMIT 1
    ");
    $stmt->execute([$username]);

    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    return $row ?: null;
}

function user_find_by_id(PDO $pdo, int $id): ?array
{
    $stmt = $pdo->prepare("
        SELECT id, username, display_name, role, is_active, created_at
        FROM users
        WHERE id = ?
        LIMIT 1
    ");
    $stmt->execute([$id]);

    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    return $row ?: null;
}