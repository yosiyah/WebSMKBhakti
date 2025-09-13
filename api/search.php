<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET');
header('Access-Control-Allow-Headers: Content-Type');

require '../config.php';

$method = $_SERVER['REQUEST_METHOD'];
$pdo = getDB();

if ($method !== 'GET') {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
    exit;
}

$query = isset($_GET['q']) ? trim($_GET['q']) : '';
$type = isset($_GET['type']) ? $_GET['type'] : 'all';
$limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 20;

if (empty($query)) {
    echo json_encode(['results' => [], 'total' => 0]);
    exit;
}

try {
    $results = [];
    $total = 0;

    // Search in kegiatan (news/articles)
    if ($type === 'all' || $type === 'kegiatan') {
        $kegiatanResults = searchKegiatan($pdo, $query, $limit);
        $results['kegiatan'] = $kegiatanResults;
        $total += count($kegiatanResults);
    }

    // Search in forum posts
    if ($type === 'all' || $type === 'forum') {
        $forumResults = searchForum($pdo, $query, $limit);
        $results['forum'] = $forumResults;
        $total += count($forumResults);
    }

    // Search in galeri
    if ($type === 'all' || $type === 'galeri') {
        $galeriResults = searchGaleri($pdo, $query, $limit);
        $results['galeri'] = $galeriResults;
        $total += count($galeriResults);
    }

    echo json_encode([
        'query' => $query,
        'type' => $type,
        'results' => $results,
        'total' => $total
    ]);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Search error: ' . $e->getMessage()]);
}

function searchKegiatan($pdo, $query, $limit) {
    $sql = "SELECT id, judul, isi, excerpt, tanggal, tanggal_formatted, gambar, kategori, status
            FROM kegiatan
            WHERE status = 'published'
            AND (judul LIKE ? OR isi LIKE ? OR excerpt LIKE ?)
            ORDER BY tanggal DESC
            LIMIT ?";

    $searchTerm = '%' . $query . '%';
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$searchTerm, $searchTerm, $searchTerm, $limit]);

    $results = $stmt->fetchAll();

    // Add type and highlight matches
    foreach ($results as &$result) {
        $result['type'] = 'kegiatan';
        $result['url'] = 'berita.html';
        $result['title_highlighted'] = highlightText($result['judul'], $query);
        $result['excerpt_highlighted'] = highlightText($result['excerpt'] ?: substr(strip_tags($result['isi']), 0, 200), $query);
    }

    return $results;
}

function searchForum($pdo, $query, $limit) {
    $sql = "SELECT id, judul, isi, penulis, created_at, created_at_formatted, kategori, status
            FROM forum_posts
            WHERE status = 'approved'
            AND (judul LIKE ? OR isi LIKE ?)
            ORDER BY created_at DESC
            LIMIT ?";

    $searchTerm = '%' . $query . '%';
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$searchTerm, $searchTerm, $limit]);

    $results = $stmt->fetchAll();

    // Add type and highlight matches
    foreach ($results as &$result) {
        $result['type'] = 'forum';
        $result['url'] = 'forum.html';
        $result['title_highlighted'] = highlightText($result['judul'], $query);
        $result['excerpt_highlighted'] = highlightText(substr(strip_tags($result['isi']), 0, 200), $query);
    }

    return $results;
}

function searchGaleri($pdo, $query, $limit) {
    $sql = "SELECT id, judul, deskripsi, gambar, kategori, tanggal, tanggal_formatted
            FROM galeri
            WHERE judul LIKE ? OR deskripsi LIKE ? OR kategori LIKE ?
            ORDER BY tanggal DESC
            LIMIT ?";

    $searchTerm = '%' . $query . '%';
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$searchTerm, $searchTerm, $searchTerm, $limit]);

    $results = $stmt->fetchAll();

    // Add type and highlight matches
    foreach ($results as &$result) {
        $result['type'] = 'galeri';
        $result['url'] = 'galeri.html';
        $result['title_highlighted'] = highlightText($result['judul'] ?: 'Tanpa Judul', $query);
        $result['excerpt_highlighted'] = highlightText($result['deskripsi'] ?: 'Tanpa deskripsi', $query);
    }

    return $results;
}

function highlightText($text, $query) {
    if (empty($query)) return $text;

    // Simple highlighting by wrapping matches in <mark> tags
    $highlighted = preg_replace('/(' . preg_quote($query, '/') . ')/iu', '<mark>$1</mark>', $text);
    return $highlighted;
}
?>