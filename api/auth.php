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
    case 'DELETE':
        handleLogout();
        break;
    default:
        http_response_code(405);
        echo json_encode(['error' => 'Method not allowed']);
        break;
}

function handleGet($pdo) {
    // Check authentication status
    session_start();

    if (isset($_SESSION['user_id'])) {
        try {
            $stmt = $pdo->prepare("SELECT id, username, nama_lengkap, email, role FROM admin_users WHERE id = ? AND active = 1");
            $stmt->execute([$_SESSION['user_id']]);
            $user = $stmt->fetch();

            if ($user) {
                echo json_encode([
                    'authenticated' => true,
                    'user' => $user
                ]);
            } else {
                // User not found or inactive
                session_destroy();
                echo json_encode(['authenticated' => false]);
            }
        } catch (Exception $e) {
            echo json_encode(['authenticated' => false, 'error' => $e->getMessage()]);
        }
    } else {
        echo json_encode(['authenticated' => false]);
    }
}

function handlePost($pdo) {
    $data = json_decode(file_get_contents('php://input'), true);

    if (!$data || !isset($data['action'])) {
        http_response_code(400);
        echo json_encode(['error' => 'Invalid request']);
        return;
    }

    switch ($data['action']) {
        case 'login':
            handleLogin($pdo, $data);
            break;
        case 'register':
            handleRegister($pdo, $data);
            break;
        default:
            http_response_code(400);
            echo json_encode(['error' => 'Invalid action']);
            break;
    }
}

function handleLogin($pdo, $data) {
    if (!isset($data['username']) || !isset($data['password'])) {
        http_response_code(400);
        echo json_encode(['error' => 'Username and password are required']);
        return;
    }

    $username = sanitizeInput($data['username']);
    $password = $data['password'];

    try {
        $stmt = $pdo->prepare("SELECT id, username, nama_lengkap, email, password, role, active FROM admin_users WHERE username = ?");
        $stmt->execute([$username]);
        $user = $stmt->fetch();

        if ($user && $user['active'] && password_verify($password, $user['password'])) {
            // Start session and store user data
            session_start();
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['username'] = $user['username'];
            $_SESSION['role'] = $user['role'];

            // Update last login
            $stmt = $pdo->prepare("UPDATE admin_users SET last_login = NOW() WHERE id = ?");
            $stmt->execute([$user['id']]);

            // Remove password from response
            unset($user['password']);

            echo json_encode([
                'success' => true,
                'message' => 'Login berhasil',
                'user' => $user
            ]);
        } else {
            http_response_code(401);
            echo json_encode(['error' => 'Username atau password salah']);
        }
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(['error' => 'Database error: ' . $e->getMessage()]);
    }
}

function handleRegister($pdo, $data) {
    // Validate required fields
    $required = ['username', 'password', 'email', 'nama_lengkap'];
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

    // Validate password strength
    if (strlen($data['password']) < 6) {
        http_response_code(400);
        echo json_encode(['error' => 'Password must be at least 6 characters long']);
        return;
    }

    try {
        // Check if username already exists
        $stmt = $pdo->prepare("SELECT id FROM admin_users WHERE username = ?");
        $stmt->execute([sanitizeInput($data['username'])]);
        if ($stmt->fetch()) {
            http_response_code(409);
            echo json_encode(['error' => 'Username already exists']);
            return;
        }

        // Check if email already exists
        $stmt = $pdo->prepare("SELECT id FROM admin_users WHERE email = ?");
        $stmt->execute([sanitizeInput($data['email'])]);
        if ($stmt->fetch()) {
            http_response_code(409);
            echo json_encode(['error' => 'Email already exists']);
            return;
        }

        // Hash password
        $hashedPassword = password_hash($data['password'], PASSWORD_DEFAULT);

        // Insert new user
        $stmt = $pdo->prepare("INSERT INTO admin_users (username, password, email, nama_lengkap, role)
                              VALUES (?, ?, ?, ?, ?)");

        $stmt->execute([
            sanitizeInput($data['username']),
            $hashedPassword,
            sanitizeInput($data['email']),
            sanitizeInput($data['nama_lengkap']),
            $data['role'] ?? 'editor'
        ]);

        $newUserId = $pdo->lastInsertId();

        echo json_encode([
            'success' => true,
            'message' => 'User berhasil didaftarkan',
            'user_id' => $newUserId
        ]);

    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(['error' => 'Database error: ' . $e->getMessage()]);
    }
}

function handleLogout() {
    session_start();
    session_destroy();

    echo json_encode([
        'success' => true,
        'message' => 'Logout berhasil'
    ]);
}
?>