<?php
$pdo = db();
$id = (int) ($_GET['id'] ?? 0);

if ($id <= 0) {
    http_response_code(404);
    exit('Documento não encontrado.');
}

$stmt = $pdo->prepare('SELECT original_name, file_path FROM documents WHERE id = :id LIMIT 1');
$stmt->execute(['id' => $id]);
$document = $stmt->fetch();

if (!$document) {
    http_response_code(404);
    exit('Documento não encontrado.');
}

$filePath = trim((string) ($document['file_path'] ?? ''));
if ($filePath === '') {
    http_response_code(404);
    exit('Documento sem arquivo disponível.');
}

$absolutePath = dirname(__DIR__, 2) . '/' . ltrim($filePath, '/');
if (!is_file($absolutePath)) {
    http_response_code(404);
    exit('Arquivo não encontrado.');
}

$downloadName = trim((string) ($document['original_name'] ?: basename($absolutePath)));
$downloadName = str_replace(["\r", "\n"], '', $downloadName);
$asciiName = preg_replace('/[^A-Za-z0-9._-]/', '_', $downloadName) ?: 'documento';
$mimeType = 'application/octet-stream';

if (function_exists('finfo_open')) {
    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    if ($finfo !== false) {
        $mimeType = (string) (finfo_file($finfo, $absolutePath) ?: $mimeType);
        finfo_close($finfo);
    }
}

header('Content-Type: ' . $mimeType);
header('Content-Length: ' . (string) filesize($absolutePath));
header('Content-Disposition: attachment; filename="' . $asciiName . '"; filename*=UTF-8\'\'' . rawurlencode($downloadName));
header('X-Content-Type-Options: nosniff');
readfile($absolutePath);
exit;
