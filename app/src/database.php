<?php

declare(strict_types=1);

/** Membuka koneksi saat dibutuhkan. Host memakai nama service Compose. */
function database(): PDO
{
    $host = getenv('DB_HOST') ?: 'database';
    $name = getenv('DB_DATABASE') ?: 'catatan_praktikum';
    $user = getenv('DB_USERNAME') ?: 'catatan_user';
    $password = getenv('DB_PASSWORD');

    if ($password === false || $password === '') {
        throw new RuntimeException('DB_PASSWORD belum diisi.');
    }

    $connection = new PDO(
        "mysql:host={$host};port=3306;dbname={$name};charset=utf8mb4",
        $user,
        $password,
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
            PDO::ATTR_TIMEOUT => 3,
        ]
    );

    // Waktu disimpan dan dibaca dalam UTC, lalu ditampilkan dalam WIB oleh PHP.
    $connection->exec("SET time_zone = '+00:00'");

    return $connection;
}
