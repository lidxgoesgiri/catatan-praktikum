<?php

declare(strict_types=1);

require_once __DIR__ . '/../src/database.php';

header('Content-Type: text/plain; charset=utf-8');
header('Cache-Control: no-store');

try {
    database()->query('SELECT 1 FROM catatan LIMIT 1');
    echo 'OK';
} catch (Throwable $error) {
    http_response_code(503);
    echo 'NOT READY';
}
