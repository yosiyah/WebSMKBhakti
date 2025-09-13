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
            // Get single post with comments
            $stmt = $pdo->prepare("SELECT * FROM forum_posts WHERE id = ? AND status = 'approved'");
            $stmt->execute([$id]);
            $post = $stmt->fetch();

            if ($post) {
                $post['created_at_formatted'] = date('d F Y H:i', strtotime($post['created_at']));
                $post['featured'] = (bool)$post['featured'];

                // Get comments
                $stmt = $pdo->prepare("SELECT * FROM forum_comments WHERE post_id = ? AND status = 'approved' ORDER BY created_at ASC");
                $stmt->execute([$id]);
                $comments = $stmt->fetchAll();

                foreach ($comments as &$comment) {
                    $comment['created_at_formatted'] = date('d F Y H:i', strtotime($comment['created_at']));
                }

                $post['comments'] = $comments;
                $post['comments_count'] = count($comments);

                echo json_encode($post);
            } else {
                http_response_code(404);
                echo json_encode(['error' => 'Post tidak ditemukan']);
            }
        } else {
            // Get multiple posts
            $where = "WHERE status = 'approved'";
            $params = [];

            if ($kategori && $kategori !== 'all') {
                $where .= " AND kategori = ?";
                $params[] = $kategori;
            }

            $sql = "SELECT * FROM forum_posts $where ORDER BY created_at DESC LIMIT $limit OFFSET $offset";

            $stmt = $pdo->prepare($sql);
            $stmt->execute($params);
            $posts = $stmt->fetchAll();

            // Format dates, add comment counts, and convert featured to boolean
            foreach ($posts as &$post) {
                $post['created_at_formatted'] = date('d F Y H:i', strtotime($post['created_at']));
                $post['excerpt'] = substr(strip_tags($post['isi']), 0, 200) . '...';
                $post['featured'] = (bool)$post['featured'];

                // Get comment count
                $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM forum_comments WHERE post_id = ? AND status = 'approved'");
                $stmt->execute([$post['id']]);
                $commentCount = $stmt->fetch();
                $post['comments_count'] = $commentCount['count'];
            }

            echo json_encode($posts);
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

        // Check if this is a comment submission
        if (isset($data['type']) && $data['type'] === 'comment') {
            // Handle comment posting
            $required = ['post_id', 'isi', 'penulis', 'email'];
            foreach ($required as $field) {
                if (!isset($data[$field]) || empty($data[$field])) {
                    http_response_code(400);
                    echo json_encode(['error' => "Field '$field' is required"]);
                    return;
                }
            }

            // Validate email
            if (!filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
                http_response_code(400);
                echo json_encode(['error' => 'Invalid email format']);
                return;
            }

            // Insert comment
            $stmt = $pdo->prepare("INSERT INTO forum_comments (post_id, isi, penulis, email, status)
                                  VALUES (?, ?, ?, ?, ?)");

            $stmt->execute([
                $data['post_id'],
                $data['isi'],
                sanitizeInput($data['penulis']),
                sanitizeInput($data['email']),
                'pending' // Comments need moderation
            ]);

            echo json_encode([
                'success' => true,
                'message' => 'Komentar berhasil ditambahkan'
            ]);
            return;
        }

        // Handle regular post submission
        $required = ['judul', 'isi', 'penulis', 'email'];
        foreach ($required as $field) {
            if (!isset($data[$field]) || empty($data[$field])) {
                http_response_code(400);
                echo json_encode(['error' => "Field '$field' is required"]);
                return;
            }
        }

        // Validate email
        if (!filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
            http_response_code(400);
            echo json_encode(['error' => 'Invalid email format']);
            return;
        }

        // Insert post
        $stmt = $pdo->prepare("INSERT INTO forum_posts (judul, isi, penulis, email, kategori, status, featured)
                              VALUES (?, ?, ?, ?, ?, ?, ?)");

        $stmt->execute([
            sanitizeInput($data['judul']),
            $data['isi'], // Allow HTML content
            sanitizeInput($data['penulis']),
            sanitizeInput($data['email']),
            $data['kategori'] ?? 'umum',
            $data['status'] ?? 'pending',
            $data['featured'] ?? false
        ]);

        $newId = $pdo->lastInsertId();

        echo json_encode([
            'success' => true,
            'message' => 'Post berhasil ditambahkan',
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

        if (isset($data['isi'])) {
            $fields[] = 'isi = ?';
            $params[] = $data['isi'];
        }

        if (isset($data['penulis'])) {
            $fields[] = 'penulis = ?';
            $params[] = sanitizeInput($data['penulis']);
        }

        if (isset($data['email'])) {
            $fields[] = 'email = ?';
            $params[] = sanitizeInput($data['email']);
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
        $sql = "UPDATE forum_posts SET " . implode(', ', $fields) . " WHERE id = ?";
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);

        echo json_encode([
            'success' => true,
            'message' => 'Post berhasil diperbarui'
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

        $stmt = $pdo->prepare("DELETE FROM forum_posts WHERE id = ?");
        $stmt->execute([$id]);

        if ($stmt->rowCount() > 0) {
            echo json_encode([
                'success' => true,
                'message' => 'Post berhasil dihapus'
            ]);
        } else {
            http_response_code(404);
            echo json_encode(['error' => 'Post tidak ditemukan']);
        }

    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(['error' => 'Database error: ' . $e->getMessage()]);
    }
}
?>