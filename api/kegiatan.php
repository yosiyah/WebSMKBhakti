<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE');
header('Access-Control-Allow-Headers: Content-Type');

require '../config.php';

$method = $_SERVER['REQUEST_METHOD'];
$pdo = getDB();

switch ($method) {
    case 'GET':
        handleGet($pdo);
        break;
    case 'POST':
        handlePost($pdo);
        break;
    case 'PUT':
        handlePut($pdo);
        break;
    case 'DELETE':
        handleDelete($pdo);
        break;
    default:
        http_response_code(405);
        echo json_encode(['error' => 'Method not allowed']);
        break;
}

function handleGet($pdo) {
    try {
        $id = isset($_GET['id']) ? (int)$_GET['id'] : null;
        $kategori = isset($_GET['kategori']) ? $_GET['kategori'] : null;
        $limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 50;
        $offset = isset($_GET['offset']) ? (int)$_GET['offset'] : 0;

        if ($id) {
            // Get single kegiatan
            $stmt = $pdo->prepare("SELECT * FROM kegiatan WHERE id = ? AND status = 'published'");
            $stmt->execute([$id]);
            $kegiatan = $stmt->fetch();

            if ($kegiatan) {
                $kegiatan['tanggal_formatted'] = formatDate($kegiatan['tanggal']);
                $kegiatan['featured'] = (bool)$kegiatan['featured'];
                echo json_encode($kegiatan);
            } else {
                http_response_code(404);
                echo json_encode(['error' => 'Kegiatan tidak ditemukan']);
            }
        } else {
            // Get multiple kegiatan
            $where = "WHERE status = 'published'";
            $params = [];

            if ($kategori && $kategori !== 'all') {
                $where .= " AND kategori = ?";
                $params[] = $kategori;
            }

            $sql = "SELECT * FROM kegiatan $where ORDER BY tanggal DESC, created_at DESC LIMIT $limit OFFSET $offset";

            $stmt = $pdo->prepare($sql);
            $stmt->execute($params);
            $kegiatan = $stmt->fetchAll();

            // Format dates and convert featured to boolean
            foreach ($kegiatan as &$item) {
                $item['tanggal_formatted'] = formatDate($item['tanggal']);
                $item['excerpt'] = substr(strip_tags($item['isi']), 0, 150) . '...';
                $item['featured'] = (bool)$item['featured'];
            }

            echo json_encode($kegiatan);
        }
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(['error' => 'Database error: ' . $e->getMessage()]);
    }
}

function handlePost($pdo) {
    try {
        $data = json_decode(file_get_contents('php://input'), true);

        if (!$data) {
            http_response_code(400);
            echo json_encode(['error' => 'Invalid JSON data']);
            return;
        }

        // Validate required fields
        $required = ['judul', 'isi', 'tanggal', 'kategori'];
        foreach ($required as $field) {
            if (!isset($data[$field]) || empty($data[$field])) {
                http_response_code(400);
                echo json_encode(['error' => "Field '$field' is required"]);
                return;
            }
        }

        // Generate slug
        $slug = generateSlug($data['judul']);

        // Check if slug already exists
        $stmt = $pdo->prepare("SELECT id FROM kegiatan WHERE slug = ?");
        $stmt->execute([$slug]);
        if ($stmt->fetch()) {
            $slug .= '-' . time();
        }

        // Insert kegiatan
        $stmt = $pdo->prepare("INSERT INTO kegiatan (judul, slug, isi, tanggal, gambar, kategori, status, featured)
                              VALUES (?, ?, ?, ?, ?, ?, ?, ?)");

        $stmt->execute([
            sanitizeInput($data['judul']),
            $slug,
            $data['isi'], // Allow HTML content
            $data['tanggal'],
            $data['gambar'] ?? null,
            $data['kategori'],
            $data['status'] ?? 'draft',
            $data['featured'] ?? false
        ]);

        $newId = $pdo->lastInsertId();

        echo json_encode([
            'success' => true,
            'message' => 'Kegiatan berhasil ditambahkan',
            'id' => $newId,
            'slug' => $slug
        ]);

    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(['error' => 'Database error: ' . $e->getMessage()]);
    }
}

function handlePut($pdo) {
    try {
        $id = isset($_GET['id']) ? (int)$_GET['id'] : null;

        if (!$id) {
            http_response_code(400);
            echo json_encode(['error' => 'ID is required']);
            return;
        }

        $data = json_decode(file_get_contents('php://input'), true);

        if (!$data) {
            http_response_code(400);
            echo json_encode(['error' => 'Invalid JSON data']);
            return;
        }

        // Build update query
        $fields = [];
        $params = [];

        if (isset($data['judul'])) {
            $fields[] = 'judul = ?';
            $params[] = sanitizeInput($data['judul']);
        }

        if (isset($data['isi'])) {
            $fields[] = 'isi = ?';
            $params[] = $data['isi'];
        }

        if (isset($data['tanggal'])) {
            $fields[] = 'tanggal = ?';
            $params[] = $data['tanggal'];
        }

        if (isset($data['gambar'])) {
            $fields[] = 'gambar = ?';
            $params[] = $data['gambar'];
        }

        if (isset($data['kategori'])) {
            $fields[] = 'kategori = ?';
            $params[] = $data['kategori'];
        }

        if (isset($data['status'])) {
            $fields[] = 'status = ?';
            $params[] = $data['status'];
        }

        if (isset($data['featured'])) {
            $fields[] = 'featured = ?';
            $params[] = $data['featured'];
        }

        if (empty($fields)) {
            http_response_code(400);
            echo json_encode(['error' => 'No fields to update']);
            return;
        }

        $params[] = $id;
        $sql = "UPDATE kegiatan SET " . implode(', ', $fields) . " WHERE id = ?";
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);

        echo json_encode([
            'success' => true,
            'message' => 'Kegiatan berhasil diperbarui'
        ]);

    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(['error' => 'Database error: ' . $e->getMessage()]);
    }
}

function handleDelete($pdo) {
    try {
        $id = isset($_GET['id']) ? (int)$_GET['id'] : null;

        if (!$id) {
            http_response_code(400);
            echo json_encode(['error' => 'ID is required']);
            return;
        }

        $stmt = $pdo->prepare("DELETE FROM kegiatan WHERE id = ?");
        $stmt->execute([$id]);

        if ($stmt->rowCount() > 0) {
            echo json_encode([
                'success' => true,
                'message' => 'Kegiatan berhasil dihapus'
            ]);
        } else {
            http_response_code(404);
            echo json_encode(['error' => 'Kegiatan tidak ditemukan']);
        }

    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(['error' => 'Database error: ' . $e->getMessage()]);
    }
}
?>