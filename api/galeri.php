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
            // Get single galeri item
            $stmt = $pdo->prepare("SELECT * FROM galeri WHERE id = ?");
            $stmt->execute([$id]);
            $item = $stmt->fetch();

            if ($item) {
                $item['tanggal_formatted'] = $item['tanggal'] ? formatDate($item['tanggal']) : '';
                $item['featured'] = (bool)$item['featured'];
                echo json_encode($item);
            } else {
                http_response_code(404);
                echo json_encode(['error' => 'Item galeri tidak ditemukan']);
            }
        } else {
            // Get multiple galeri items
            $where = "WHERE 1=1";
            $params = [];

            if ($kategori && $kategori !== 'all') {
                $where .= " AND kategori = ?";
                $params[] = $kategori;
            }

            $sql = "SELECT * FROM galeri $where ORDER BY tanggal DESC, created_at DESC LIMIT $limit OFFSET $offset";

            $stmt = $pdo->prepare($sql);
            $stmt->execute($params);
            $galeri = $stmt->fetchAll();

            // Format dates and convert featured to boolean
            foreach ($galeri as &$item) {
                $item['tanggal_formatted'] = $item['tanggal'] ? formatDate($item['tanggal']) : '';
                $item['featured'] = (bool)$item['featured'];
            }

            echo json_encode($galeri);
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
        if (!isset($data['gambar']) || empty($data['gambar'])) {
            http_response_code(400);
            echo json_encode(['error' => 'Gambar is required']);
            return;
        }

        // Insert galeri item
        $stmt = $pdo->prepare("INSERT INTO galeri (judul, deskripsi, gambar, kategori, tanggal, featured)
                              VALUES (?, ?, ?, ?, ?, ?)");

        $stmt->execute([
            isset($data['judul']) ? sanitizeInput($data['judul']) : null,
            isset($data['deskripsi']) ? $data['deskripsi'] : null,
            $data['gambar'],
            $data['kategori'] ?? 'umum',
            $data['tanggal'] ?? date('Y-m-d'),
            $data['featured'] ?? false
        ]);

        $newId = $pdo->lastInsertId();

        echo json_encode([
            'success' => true,
            'message' => 'Item galeri berhasil ditambahkan',
            'id' => $newId
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

        if (isset($data['deskripsi'])) {
            $fields[] = 'deskripsi = ?';
            $params[] = $data['deskripsi'];
        }

        if (isset($data['gambar'])) {
            $fields[] = 'gambar = ?';
            $params[] = $data['gambar'];
        }

        if (isset($data['kategori'])) {
            $fields[] = 'kategori = ?';
            $params[] = $data['kategori'];
        }

        if (isset($data['tanggal'])) {
            $fields[] = 'tanggal = ?';
            $params[] = $data['tanggal'];
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
        $sql = "UPDATE galeri SET " . implode(', ', $fields) . " WHERE id = ?";
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);

        echo json_encode([
            'success' => true,
            'message' => 'Item galeri berhasil diperbarui'
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

        // Get image path before deleting
        $stmt = $pdo->prepare("SELECT gambar FROM galeri WHERE id = ?");
        $stmt->execute([$id]);
        $item = $stmt->fetch();

        // Delete from database
        $stmt = $pdo->prepare("DELETE FROM galeri WHERE id = ?");
        $stmt->execute([$id]);

        if ($stmt->rowCount() > 0) {
            // Delete image file if it exists
            if ($item && file_exists('../' . $item['gambar'])) {
                unlink('../' . $item['gambar']);
            }

            echo json_encode([
                'success' => true,
                'message' => 'Item galeri berhasil dihapus'
            ]);
        } else {
            http_response_code(404);
            echo json_encode(['error' => 'Item galeri tidak ditemukan']);
        }

    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(['error' => 'Database error: ' . $e->getMessage()]);
    }
}
?>