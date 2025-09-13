<?php

// Database configuration for setup (connect without specific database)
define('DB_HOST', 'localhost');
define('DB_NAME', 'smk_bhakti');
define('DB_USER', 'root');
define('DB_PASS', '');

// Site configuration
define('SITE_URL', 'http://localhost/smk-bhakti-pacet');
define('UPLOAD_DIR', 'uploads/');

// Create uploads directory if it doesn't exist
if (!file_exists(UPLOAD_DIR)) {
    mkdir(UPLOAD_DIR, 0777, true);
}

try {
    // Connect to MySQL without specifying database
    $pdo = new PDO('mysql:host=' . DB_HOST, DB_USER, DB_PASS);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Create database if it doesn't exist
    $pdo->exec("CREATE DATABASE IF NOT EXISTS " . DB_NAME);
    $pdo->exec("USE " . DB_NAME);

    // Create kegiatan table
    $sql = "CREATE TABLE IF NOT EXISTS kegiatan (
        id INT PRIMARY KEY AUTO_INCREMENT,
        judul VARCHAR(255) NOT NULL,
        slug VARCHAR(255) UNIQUE,
        isi TEXT,
        excerpt TEXT,
        tanggal DATE NOT NULL,
        gambar VARCHAR(255),
        kategori ENUM('akademik', 'siswa', 'administratif', 'umum') DEFAULT 'umum',
        status ENUM('draft', 'published') DEFAULT 'draft',
        featured BOOLEAN DEFAULT FALSE,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        INDEX idx_status (status),
        INDEX idx_kategori (kategori),
        INDEX idx_tanggal (tanggal),
        INDEX idx_featured (featured)
    )";

    $pdo->exec($sql);

    // Create galeri table
    $sql = "CREATE TABLE IF NOT EXISTS galeri (
        id INT PRIMARY KEY AUTO_INCREMENT,
        judul VARCHAR(255),
        deskripsi TEXT,
        gambar VARCHAR(255) NOT NULL,
        kategori VARCHAR(50) DEFAULT 'umum',
        tanggal DATE,
        featured BOOLEAN DEFAULT FALSE,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        INDEX idx_kategori (kategori),
        INDEX idx_tanggal (tanggal),
        INDEX idx_featured (featured)
    )";

    $pdo->exec($sql);

    // Create calendar_events table
    $sql = "CREATE TABLE IF NOT EXISTS calendar_events (
        id INT PRIMARY KEY AUTO_INCREMENT,
        judul VARCHAR(255) NOT NULL,
        deskripsi TEXT,
        tanggal_mulai DATE NOT NULL,
        tanggal_selesai DATE,
        waktu_mulai TIME,
        waktu_selesai TIME,
        lokasi VARCHAR(255),
        kategori ENUM('akademik', 'liburan', 'ujian', 'kegiatan', 'umum') DEFAULT 'umum',
        status ENUM('active', 'cancelled') DEFAULT 'active',
        featured BOOLEAN DEFAULT FALSE,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        INDEX idx_kategori (kategori),
        INDEX idx_tanggal_mulai (tanggal_mulai),
        INDEX idx_status (status),
        INDEX idx_featured (featured)
    )";

    $pdo->exec($sql);

    // Create forum_posts table
    $sql = "CREATE TABLE IF NOT EXISTS forum_posts (
        id INT PRIMARY KEY AUTO_INCREMENT,
        judul VARCHAR(255) NOT NULL,
        isi TEXT NOT NULL,
        penulis VARCHAR(100),
        email VARCHAR(100),
        kategori VARCHAR(50) DEFAULT 'umum',
        status ENUM('pending', 'approved', 'rejected') DEFAULT 'pending',
        featured BOOLEAN DEFAULT FALSE,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        INDEX idx_kategori (kategori),
        INDEX idx_status (status),
        INDEX idx_featured (featured)
    )";

    $pdo->exec($sql);

    // Create forum_comments table
    $sql = "CREATE TABLE IF NOT EXISTS forum_comments (
        id INT PRIMARY KEY AUTO_INCREMENT,
        post_id INT NOT NULL,
        isi TEXT NOT NULL,
        penulis VARCHAR(100),
        email VARCHAR(100),
        status ENUM('pending', 'approved', 'rejected') DEFAULT 'pending',
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (post_id) REFERENCES forum_posts(id) ON DELETE CASCADE,
        INDEX idx_post_id (post_id),
        INDEX idx_status (status)
    )";

    $pdo->exec($sql);

    // Create admin_users table
    $sql = "CREATE TABLE IF NOT EXISTS admin_users (
        id INT PRIMARY KEY AUTO_INCREMENT,
        username VARCHAR(50) UNIQUE NOT NULL,
        password VARCHAR(255) NOT NULL,
        email VARCHAR(100),
        nama_lengkap VARCHAR(100),
        role ENUM('admin', 'editor') DEFAULT 'editor',
        last_login TIMESTAMP NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        active BOOLEAN DEFAULT TRUE
    )";

    $pdo->exec($sql);

    // Create pendaftaran_siswa table
    $sql = "CREATE TABLE IF NOT EXISTS pendaftaran_siswa (
        id INT PRIMARY KEY AUTO_INCREMENT,
        no_pendaftaran VARCHAR(20) UNIQUE NOT NULL,
        nama_lengkap VARCHAR(100) NOT NULL,
        nama_panggilan VARCHAR(50),
        tempat_lahir VARCHAR(50) NOT NULL,
        tanggal_lahir DATE NOT NULL,
        jenis_kelamin ENUM('L', 'P') NOT NULL,
        agama VARCHAR(20) NOT NULL,
        alamat TEXT NOT NULL,
        no_telepon VARCHAR(20),
        email VARCHAR(100),
        nama_ayah VARCHAR(100) NOT NULL,
        nama_ibu VARCHAR(100) NOT NULL,
        pekerjaan_ayah VARCHAR(100),
        pekerjaan_ibu VARCHAR(100),
        alamat_orangtua TEXT NOT NULL,
        no_telepon_ortu VARCHAR(20) NOT NULL,
        penghasilan VARCHAR(20),
        sekolah_asal VARCHAR(100) NOT NULL,
        alamat_sekolah TEXT,
        nisn VARCHAR(10),
        tahun_lulus YEAR NOT NULL,
        nilai_rata_rata DECIMAL(5,2),
        jurusan_pilihan VARCHAR(50) NOT NULL,
        prestasi TEXT,
        status ENUM('pending', 'approved', 'rejected', 'verified') DEFAULT 'pending',
        catatan_admin TEXT,
        tanggal_verifikasi DATE,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        INDEX idx_status (status),
        INDEX idx_jurusan (jurusan_pilihan),
        INDEX idx_tahun_lulus (tahun_lulus)
    )";

    $pdo->exec($sql);

    // Insert default admin user
    $hashedPassword = password_hash('admin123', PASSWORD_DEFAULT);
    $stmt = $pdo->prepare("INSERT IGNORE INTO admin_users (username, password, email, nama_lengkap, role) VALUES (?, ?, ?, ?, ?)");
    $stmt->execute(['admin', $hashedPassword, 'admin@smkbhaktipacet.sch.id', 'Administrator', 'admin']);

    // Insert sample data
    $sampleKegiatan = [
        [
            'judul' => 'Pembukaan Tahun Ajaran 2024/2025',
            'isi' => 'Upacara pembukaan tahun ajaran baru berlangsung dengan penuh semangat. Kepala sekolah memberikan sambutan dan motivasi kepada seluruh siswa.',
            'tanggal' => '2024-07-15',
            'gambar' => 'images/gallery/pembukaan-tahun-ajaran.jpg',
            'kategori' => 'akademik',
            'status' => 'published'
        ],
        [
            'judul' => 'Workshop Pengembangan Teknologi',
            'isi' => 'Workshop pengembangan aplikasi web dan mobile akan diadakan untuk siswa kelas 11 dan 12 jurusan TKJ. Materi mencakup framework modern dan best practices.',
            'tanggal' => '2024-10-15',
            'gambar' => 'images/gallery/study-tour.jpg',
            'kategori' => 'akademik',
            'status' => 'published'
        ]
    ];

    foreach ($sampleKegiatan as $kegiatan) {
        $stmt = $pdo->prepare("INSERT IGNORE INTO kegiatan (judul, isi, tanggal, gambar, kategori, status) VALUES (?, ?, ?, ?, ?, ?)");
        $stmt->execute([
            $kegiatan['judul'],
            $kegiatan['isi'],
            $kegiatan['tanggal'],
            $kegiatan['gambar'],
            $kegiatan['kategori'],
            $kegiatan['status']
        ]);
    }

    // Insert sample calendar events
    $sampleEvents = [
        [
            'judul' => 'Ujian Tengah Semester',
            'deskripsi' => 'Ujian tengah semester untuk semua kelas',
            'tanggal_mulai' => '2024-11-01',
            'tanggal_selesai' => '2024-11-15',
            'kategori' => 'ujian'
        ],
        [
            'judul' => 'Libur Natal dan Tahun Baru',
            'deskripsi' => 'Libur akhir tahun untuk Natal dan Tahun Baru',
            'tanggal_mulai' => '2024-12-20',
            'tanggal_selesai' => '2025-01-05',
            'kategori' => 'liburan'
        ],
        [
            'judul' => 'Workshop Teknologi',
            'deskripsi' => 'Workshop pengembangan aplikasi web untuk siswa kelas 12',
            'tanggal_mulai' => '2024-12-10',
            'waktu_mulai' => '08:00:00',
            'waktu_selesai' => '16:00:00',
            'lokasi' => 'Lab Komputer',
            'kategori' => 'kegiatan'
        ]
    ];

    foreach ($sampleEvents as $event) {
        $stmt = $pdo->prepare("INSERT IGNORE INTO calendar_events (judul, deskripsi, tanggal_mulai, tanggal_selesai, waktu_mulai, waktu_selesai, lokasi, kategori) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->execute([
            $event['judul'],
            $event['deskripsi'],
            $event['tanggal_mulai'],
            $event['tanggal_selesai'] ?? null,
            $event['waktu_mulai'] ?? null,
            $event['waktu_selesai'] ?? null,
            $event['lokasi'] ?? null,
            $event['kategori']
        ]);
    }

    // Insert sample forum posts
    $samplePosts = [
        [
            'judul' => 'Pertanyaan tentang materi matematika kelas 10',
            'isi' => 'Saya kesulitan memahami materi trigonometri. Apakah ada yang bisa membantu menjelaskan?',
            'penulis' => 'Ahmad Siswa',
            'email' => 'ahmad@example.com',
            'kategori' => 'akademik',
            'status' => 'approved'
        ],
        [
            'judul' => 'Info ekstrakurikuler basket',
            'isi' => 'Kapan jadwal latihan basket untuk siswa baru? Saya tertarik bergabung.',
            'penulis' => 'Sari Siswi',
            'email' => 'sari@example.com',
            'kategori' => 'ekstrakurikuler',
            'status' => 'approved'
        ]
    ];

    foreach ($samplePosts as $post) {
        $stmt = $pdo->prepare("INSERT IGNORE INTO forum_posts (judul, isi, penulis, email, kategori, status) VALUES (?, ?, ?, ?, ?, ?)");
        $stmt->execute([
            $post['judul'],
            $post['isi'],
            $post['penulis'],
            $post['email'],
            $post['kategori'],
            $post['status']
        ]);
    }

    // Insert sample gallery items
    $sampleGallery = [
        [
            'judul' => 'Pembukaan Tahun Ajaran 2024/2025',
            'deskripsi' => 'Upacara pembukaan tahun ajaran baru dengan penuh semangat',
            'gambar' => 'images/gallery/pembukaan-tahun-ajaran.jpg',
            'kategori' => 'kegiatan',
            'tanggal' => '2024-07-15'
        ],
        [
            'judul' => 'Study Tour Siswa',
            'deskripsi' => 'Kegiatan study tour siswa ke berbagai tempat edukasi',
            'gambar' => 'images/gallery/study-tour.jpg',
            'kategori' => 'kegiatan',
            'tanggal' => '2024-09-20'
        ],
        [
            'judul' => 'Fasilitas Lab Komputer',
            'deskripsi' => 'Laboratorium komputer yang modern dan lengkap',
            'gambar' => 'images/hero/school-building.jpg',
            'kategori' => 'fasilitas',
            'tanggal' => '2024-08-01'
        ],
        [
            'judul' => 'Kegiatan Belajar Siswa',
            'deskripsi' => 'Siswa sedang fokus belajar di kelas',
            'gambar' => 'images/hero/students-studying.jpg',
            'kategori' => 'siswa',
            'tanggal' => '2024-08-15'
        ],
        [
            'judul' => 'Gedung Sekolah',
            'deskripsi' => 'Tampilan depan gedung SMK BHAKTI PACET',
            'gambar' => 'images/hero/school-building.jpg',
            'kategori' => 'fasilitas',
            'tanggal' => '2024-07-01'
        ],
        [
            'judul' => 'Upacara Wisuda',
            'deskripsi' => 'Moment wisuda siswa angkatan 2024',
            'gambar' => 'images/hero/graduation-ceremony.jpg',
            'kategori' => 'kegiatan',
            'tanggal' => '2024-06-30'
        ]
    ];

    foreach ($sampleGallery as $item) {
        $stmt = $pdo->prepare("INSERT IGNORE INTO galeri (judul, deskripsi, gambar, kategori, tanggal) VALUES (?, ?, ?, ?, ?)");
        $stmt->execute([
            $item['judul'],
            $item['deskripsi'],
            $item['gambar'],
            $item['kategori'],
            $item['tanggal']
        ]);
    }

    echo "<h2>✅ Database Setup Berhasil!</h2>";
    echo "<p>Database dan tabel telah berhasil dibuat.</p>";
    echo "<p>Data sample telah dimasukkan.</p>";
    echo "<p><strong>Admin Login:</strong></p>";
    echo "<ul>";
    echo "<li>Username: admin</li>";
    echo "<li>Password: admin123</li>";
    echo "</ul>";
    echo "<p><a href='admin/'>Buka Admin Panel</a></p>";

} catch (PDOException $e) {
    echo "<h2>❌ Error Setup Database</h2>";
    echo "<p>" . $e->getMessage() . "</p>";
}
?>