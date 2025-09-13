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

        if ($id) {
            // Get single registration
            $stmt = $pdo->prepare("SELECT * FROM pendaftaran_siswa WHERE id = ?");
            $stmt->execute([$id]);
            $registration = $stmt->fetch();

            if ($registration) {
                echo json_encode($registration);
            } else {
                http_response_code(404);
                echo json_encode(['error' => 'Pendaftaran tidak ditemukan']);
            }
        } else {
            // Get all registrations with pagination
            $limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 50;
            $offset = isset($_GET['offset']) ? (int)$_GET['offset'] : 0;
            $status = isset($_GET['status']) ? $_GET['status'] : null;

            $where = "";
            $params = [];

            if ($status && $status !== 'all') {
                $where = "WHERE status = ?";
                $params[] = $status;
            }

            $sql = "SELECT * FROM pendaftaran_siswa $where ORDER BY created_at DESC LIMIT $limit OFFSET $offset";
            $stmt = $pdo->prepare($sql);
            $stmt->execute($params);
            $registrations = $stmt->fetchAll();

            echo json_encode($registrations);
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
        $required = [
            'nama_lengkap', 'tempat_lahir', 'tanggal_lahir', 'jenis_kelamin',
            'agama', 'alamat', 'nama_ayah', 'nama_ibu', 'alamat_orangtua',
            'no_telepon_ortu', 'sekolah_asal', 'tahun_lulus', 'jurusan_pilihan'
        ];

        foreach ($required as $field) {
            if (!isset($data[$field]) || empty($data[$field])) {
                http_response_code(400);
                echo json_encode(['error' => "Field '$field' is required"]);
                return;
            }
        }

        // Validate email if provided
        if (isset($data['email']) && !empty($data['email']) && !filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
            http_response_code(400);
            echo json_encode(['error' => 'Invalid email format']);
            return;
        }

        // Generate registration number
        $tahun = date('Y');
        $bulan = date('m');
        $stmt = $pdo->query("SELECT COUNT(*) as count FROM pendaftaran_siswa WHERE YEAR(created_at) = $tahun");
        $result = $stmt->fetch();
        $sequence = str_pad($result['count'] + 1, 4, '0', STR_PAD_LEFT);
        $no_pendaftaran = "REG{$tahun}{$bulan}{$sequence}";

        // Insert registration data
        $stmt = $pdo->prepare("INSERT INTO pendaftaran_siswa (
            no_pendaftaran, nama_lengkap, nama_panggilan, tempat_lahir, tanggal_lahir,
            jenis_kelamin, agama, alamat, no_telepon, email, nama_ayah, nama_ibu,
            pekerjaan_ayah, pekerjaan_ibu, alamat_orangtua, no_telepon_ortu, penghasilan,
            sekolah_asal, alamat_sekolah, nisn, tahun_lulus, nilai_rata_rata, jurusan_pilihan,
            prestasi, status
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");

        $stmt->execute([
            $no_pendaftaran,
            sanitizeInput($data['nama_lengkap']),
            isset($data['nama_panggilan']) ? sanitizeInput($data['nama_panggilan']) : null,
            sanitizeInput($data['tempat_lahir']),
            $data['tanggal_lahir'],
            $data['jenis_kelamin'],
            $data['agama'],
            sanitizeInput($data['alamat']),
            isset($data['no_telepon']) ? sanitizeInput($data['no_telepon']) : null,
            isset($data['email']) ? sanitizeInput($data['email']) : null,
            sanitizeInput($data['nama_ayah']),
            sanitizeInput($data['nama_ibu']),
            isset($data['pekerjaan_ayah']) ? sanitizeInput($data['pekerjaan_ayah']) : null,
            isset($data['pekerjaan_ibu']) ? sanitizeInput($data['pekerjaan_ibu']) : null,
            sanitizeInput($data['alamat_orangtua']),
            sanitizeInput($data['no_telepon_ortu']),
            isset($data['penghasilan']) ? $data['penghasilan'] : null,
            sanitizeInput($data['sekolah_asal']),
            isset($data['alamat_sekolah']) ? sanitizeInput($data['alamat_sekolah']) : null,
            isset($data['nisn']) ? sanitizeInput($data['nisn']) : null,
            $data['tahun_lulus'],
            isset($data['nilai_rata_rata']) ? $data['nilai_rata_rata'] : null,
            $data['jurusan_pilihan'],
            isset($data['prestasi']) ? sanitizeInput($data['prestasi']) : null,
            'pending' // Default status
        ]);

        $newId = $pdo->lastInsertId();

        echo json_encode([
            'success' => true,
            'message' => 'Pendaftaran berhasil dikirim',
            'no_pendaftaran' => $no_pendaftaran,
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

        $updatableFields = [
            'status', 'catatan_admin', 'tanggal_verifikasi'
        ];

        foreach ($updatableFields as $field) {
            if (isset($data[$field])) {
                $fields[] = "$field = ?";
                $params[] = $data[$field];
            }
        }

        if (empty($fields)) {
            http_response_code(400);
            echo json_encode(['error' => 'No fields to update']);
            return;
        }

        $params[] = $id;
        $sql = "UPDATE pendaftaran_siswa SET " . implode(', ', $fields) . " WHERE id = ?";
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);

        echo json_encode([
            'success' => true,
            'message' => 'Pendaftaran berhasil diperbarui'
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

        $stmt = $pdo->prepare("DELETE FROM pendaftaran_siswa WHERE id = ?");
        $stmt->execute([$id]);

        if ($stmt->rowCount() > 0) {
            echo json_encode([
                'success' => true,
                'message' => 'Pendaftaran berhasil dihapus'
            ]);
        } else {
            http_response_code(404);
            echo json_encode(['error' => 'Pendaftaran tidak ditemukan']);
        }

    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(['error' => 'Database error: ' . $e->getMessage()]);
    }
}
?>