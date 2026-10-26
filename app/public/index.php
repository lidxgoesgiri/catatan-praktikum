<?php

declare(strict_types=1);

require_once __DIR__ . '/../src/database.php';

session_start();
header('Content-Type: text/html; charset=utf-8');
header('Cache-Control: no-store');
header('X-Content-Type-Options: nosniff');

// Token melindungi formulir dari kiriman halaman lain, tanpa sistem login.
if (!isset($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

function escape(string $value): string
{
    return htmlspecialchars($value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

function waktuLokal(string $utc): string
{
    $time = new DateTimeImmutable($utc, new DateTimeZone('UTC'));
    $zone = new DateTimeZone('Asia/Jakarta');

    return $time->setTimezone($zone)->format('d/m/Y H:i');
}

$isi = $_SERVER['REQUEST_METHOD'] === 'POST' && is_string($_POST['isi'] ?? null)
    ? trim($_POST['isi'])
    : '';
$error = '';
$systemError = '';
$catatan = [];
$success = $_SESSION['pesan'] ?? '';
unset($_SESSION['pesan']);

try {
    $connection = database();

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $token = is_string($_POST['csrf_token'] ?? null) ? $_POST['csrf_token'] : '';
        // PCRE menghitung karakter Unicode tanpa memerlukan ekstensi tambahan.
        $panjang = preg_match_all('/./us', $isi);

        if (!hash_equals($_SESSION['csrf_token'], $token)) {
            $error = 'Formulir sudah tidak berlaku. Muat ulang halaman, lalu coba lagi.';
            http_response_code(400);
        } elseif ($isi === '') {
            $error = 'Isi catatan terlebih dahulu.';
            http_response_code(422);
        } elseif ($panjang === false || $panjang > 1000) {
            $error = 'Catatan maksimal 1.000 karakter dengan teks UTF-8 yang valid.';
            http_response_code(422);
        } else {
            $statement = $connection->prepare('INSERT INTO catatan (isi) VALUES (:isi)');
            $statement->execute(['isi' => $isi]);
            $_SESSION['pesan'] = 'Catatan berhasil disimpan.';

            // Redirect mencegah penyimpanan ganda saat halaman dimuat ulang.
            header('Location: /', true, 303);
            exit;
        }
    }

    $catatan = $connection->query(
        'SELECT id, isi, dibuat_pada FROM catatan ORDER BY id DESC'
    )->fetchAll();
} catch (Throwable $exception) {
    http_response_code(503);
    error_log('Catatan Praktikum: ' . $exception->getMessage());
    $systemError = 'Catatan belum bisa dimuat. Silakan coba lagi sebentar.';
}
?>
<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Catatan Praktikum</title>
    <link rel="icon" type="image/svg+xml" href="/assets/favicon.svg">
    <link rel="stylesheet" href="/assets/style.css">
</head>
<body>
    <main class="page">
        <header class="page-header">
            <p class="eyebrow">BUKU CATATAN</p>
            <h1>Catatan Praktikum</h1>
            <p class="intro">Simpan hal yang dipelajari, satu catatan setiap kali.</p>
        </header>

        <?php if ($success !== ''): ?>
            <p class="message success" role="status"><?= escape($success) ?></p>
        <?php endif; ?>
        <?php if ($systemError !== ''): ?>
            <p class="message error" role="alert"><?= escape($systemError) ?></p>
        <?php endif; ?>

        <div class="layout">
            <section class="form-panel" aria-labelledby="form-title">
                <h2 id="form-title">Tulis catatan</h2>
                <form action="/" method="post">
                    <input type="hidden" name="csrf_token" value="<?= escape($_SESSION['csrf_token']) ?>">
                    <label for="isi">Isi catatan</label>
                    <textarea id="isi" name="isi" rows="7" required
                        aria-describedby="isi-hint<?= $error !== '' ? ' isi-error' : '' ?>"
                        <?= $error !== '' ? 'aria-invalid="true"' : '' ?>
                        placeholder="Apa yang Anda pelajari hari ini?"
                    ><?= escape($isi) ?></textarea>
                    <p id="isi-hint" class="hint">Maksimal 1.000 karakter.</p>
                    <?php if ($error !== ''): ?>
                        <p id="isi-error" class="field-error" role="alert"><?= escape($error) ?></p>
                    <?php endif; ?>
                    <button type="submit" <?= $systemError !== '' ? 'disabled' : '' ?>>Simpan catatan</button>
                </form>
            </section>

            <section class="notes-panel" aria-labelledby="notes-title">
                <div class="section-header">
                    <h2 id="notes-title">Catatan tersimpan</h2>
                    <?php if ($systemError === ''): ?>
                        <span class="count"><?= count($catatan) ?> catatan</span>
                    <?php endif; ?>
                </div>
                <?php if ($systemError !== ''): ?>
                    <p class="empty-state">Daftar catatan akan tampil setelah layanan tersedia kembali.</p>
                <?php elseif ($catatan === []): ?>
                    <div class="empty-state">
                        <p class="empty-title">Belum ada catatan.</p>
                        <p>Tulis catatan pertama Anda melalui formulir yang tersedia.</p>
                    </div>
                <?php else: ?>
                    <ol class="notes">
                        <?php foreach ($catatan as $item): ?>
                            <li class="note">
                                <div class="note-meta">
                                    <span>Catatan #<?= (int) $item['id'] ?></span>
                                    <time datetime="<?= escape(str_replace(' ', 'T', $item['dibuat_pada']) . 'Z') ?>">
                                        <?= escape(waktuLokal($item['dibuat_pada'])) ?> WIB
                                    </time>
                                </div>
                                <p class="note-content"><?= escape($item['isi']) ?></p>
                            </li>
                        <?php endforeach; ?>
                    </ol>
                <?php endif; ?>
            </section>
        </div>
        <footer>Catatan terbaru ditampilkan paling atas. Waktu ditampilkan dalam WIB.</footer>
    </main>
</body>
</html>
