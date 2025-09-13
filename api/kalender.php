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
            // Get single event
            $stmt = $pdo->prepare("SELECT * FROM calendar_events WHERE id = ? AND status = 'active'");
            $stmt->execute([$id]);
            $event = $stmt->fetch();

            if ($event) {
                $event['tanggal_mulai_formatted'] = formatDate($event['tanggal_mulai']);
                $event['tanggal_selesai_formatted'] = $event['tanggal_selesai'] ? formatDate($event['tanggal_selesai']) : '';
                $event['featured'] = (bool)$event['featured'];
                echo json_encode($event);
            } else {
                http_response_code(404);
                echo json_encode(['error' => 'Event tidak ditemukan']);
            }
        } else {
            // Get multiple events
            $where = "WHERE status = 'active'";
            $params = [];

            if ($kategori && $kategori !== 'all') {
                $where .= " AND kategori = ?";
                $params[] = $kategori;
            }

            $sql = "SELECT * FROM calendar_events $where ORDER BY tanggal_mulai ASC, waktu_mulai ASC LIMIT $limit OFFSET $offset";

            $stmt = $pdo->prepare($sql);
            $stmt->execute($params);
            $events = $stmt->fetchAll();

            // Format dates and convert featured to boolean
            foreach ($events as &$event) {
                $event['tanggal_mulai_formatted'] = formatDate($event['tanggal_mulai']);
                $event['tanggal_selesai_formatted'] = $event['tanggal_selesai'] ? formatDate($event['tanggal_selesai']) : '';
                $event['featured'] = (bool)$event['featured'];
            }

            echo json_encode($events);
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
        $required = ['judul', 'tanggal_mulai', 'kategori'];
        foreach ($required as $field) {
            if (!isset($data[$field]) || empty($data[$field])) {
                http_response_code(400);
                echo json_encode(['error' => "Field '$field' is required"]);
                return;
            }
        }

        // Insert event
        $stmt = $pdo->prepare("INSERT INTO calendar_events (judul, deskripsi, tanggal_mulai, tanggal_selesai, waktu_mulai, waktu_selesai, lokasi, kategori, status, featured)
                              VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");

        $stmt->execute([
            sanitizeInput($data['judul']),
            isset($data['deskripsi']) ? $data['deskripsi'] : null,
            $data['tanggal_mulai'],
            $data['tanggal_selesai'] ?? null,
            $data['waktu_mulai'] ?? null,
            $data['waktu_selesai'] ?? null,
            $data['lokasi'] ?? null,
            $data['kategori'],
            $data['status'] ?? 'active',
            $data['featured'] ?? false
        ]);

        $newId = $pdo->lastInsertId();

        echo json_encode([
            'success' => true,
            'message' => 'Event berhasil ditambahkan',
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

        if (isset($data['tanggal_mulai'])) {
            $fields[] = 'tanggal_mulai = ?';
            $params[] = $data['tanggal_mulai'];
        }

        if (isset($data['tanggal_selesai'])) {
            $fields[] = 'tanggal_selesai = ?';
            $params[] = $data['tanggal_selesai'];
        }

        if (isset($data['waktu_mulai'])) {
            $fields[] = 'waktu_mulai = ?';
            $params[] = $data['waktu_mulai'];
        }

        if (isset($data['waktu_selesai'])) {
            $fields[] = 'waktu_selesai = ?';
            $params[] = $data['waktu_selesai'];
        }

        if (isset($data['lokasi'])) {
            $fields[] = 'lokasi = ?';
            $params[] = $data['lokasi'];
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
        $sql = "UPDATE calendar_events SET " . implode(', ', $fields) . " WHERE id = ?";
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);

        echo json_encode([
            'success' => true,
            'message' => 'Event berhasil diperbarui'
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

        $stmt = $pdo->prepare("DELETE FROM calendar_events WHERE id = ?");
        $stmt->execute([$id]);

        if ($stmt->rowCount() > 0) {
            echo json_encode([
                'success' => true,
                'message' => 'Event berhasil dihapus'
            ]);
        } else {
            http_response_code(404);
            echo json_encode(['error' => 'Event tidak ditemukan']);
        }

    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(['error' => 'Database error: ' . $e->getMessage()]);
    }
}
?>