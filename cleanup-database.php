<?php
// Database Cleanup Script for SMK BHAKTI PACET
// This script fixes duplicate data, generates slugs, and corrects field types

require 'config.php';

echo "<h1>🧹 DATABASE CLEANUP SCRIPT</h1>";
echo "<p>Memulai proses pembersihan database...</p>";

try {
    $pdo = getDB();
    echo "<div style='background: #e8f5e8; padding: 10px; margin: 10px 0; border-left: 4px solid #4caf50;'>";

    // ==========================================
    // 1. CLEANUP DUPLICATE KEGIATAN
    // ==========================================
    echo "<h3>1. 🧽 Membersihkan Duplicate Data Kegiatan</h3>";

    // Find duplicates based on judul and isi
    $stmt = $pdo->query("
        SELECT judul, isi, COUNT(*) as count
        FROM kegiatan
        GROUP BY judul, isi
        HAVING COUNT(*) > 1
        ORDER BY count DESC
    ");

    $duplicates = $stmt->fetchAll();
    echo "<p>Ditemukan " . count($duplicates) . " grup data duplicate</p>";

    $totalDeleted = 0;
    foreach ($duplicates as $duplicate) {
        // Keep the oldest record (smallest ID), delete the rest
        $stmt = $pdo->prepare("
            DELETE t1 FROM kegiatan t1
            INNER JOIN kegiatan t2
            WHERE t1.id > t2.id
            AND t1.judul = t2.judul
            AND t1.isi = t2.isi
            AND t1.judul = ?
            AND t1.isi = ?
        ");

        $stmt->execute([$duplicate['judul'], $duplicate['isi']]);
        $deleted = $stmt->rowCount();
        $totalDeleted += $deleted;

        if ($deleted > 0) {
            echo "<p>🗑️ Menghapus {$deleted} duplicate untuk: <strong>{$duplicate['judul']}</strong></p>";
        }
    }

    echo "<p><strong>Total duplicate dihapus: {$totalDeleted}</strong></p>";

    // ==========================================
    // 2. CLEANUP DUPLICATE GALERI
    // ==========================================
    echo "<h3>2. 🧽 Membersihkan Duplicate Data Galeri</h3>";

    $stmt = $pdo->query("
        SELECT gambar, COUNT(*) as count
        FROM galeri
        GROUP BY gambar
        HAVING COUNT(*) > 1
        ORDER BY count DESC
    ");

    $galeriDuplicates = $stmt->fetchAll();
    echo "<p>Ditemukan " . count($galeriDuplicates) . " grup gambar duplicate</p>";

    $galeriDeleted = 0;
    foreach ($galeriDuplicates as $duplicate) {
        $stmt = $pdo->prepare("
            DELETE t1 FROM galeri t1
            INNER JOIN galeri t2
            WHERE t1.id > t2.id
            AND t1.gambar = t2.gambar
            AND t1.gambar = ?
        ");

        $stmt->execute([$duplicate['gambar']]);
        $deleted = $stmt->rowCount();
        $galeriDeleted += $deleted;

        if ($deleted > 0) {
            echo "<p>🗑️ Menghapus {$deleted} duplicate gambar: <strong>{$duplicate['gambar']}</strong></p>";
        }
    }

    echo "<p><strong>Total gambar duplicate dihapus: {$galeriDeleted}</strong></p>";

    // ==========================================
    // 3. GENERATE SLUGS FOR KEGIATAN
    // ==========================================
    echo "<h3>3. 🔗 Generate Slugs untuk Kegiatan</h3>";

    // Get all kegiatan without slugs
    $stmt = $pdo->query("SELECT id, judul FROM kegiatan WHERE slug IS NULL OR slug = ''");
    $noSlugRecords = $stmt->fetchAll();

    echo "<p>Ditemukan " . count($noSlugRecords) . " record tanpa slug</p>";

    foreach ($noSlugRecords as $record) {
        $baseSlug = generateSlug($record['judul']);
        $slug = $baseSlug . '-' . $record['id'];

        // Check if slug already exists (unlikely but safe)
        $stmt = $pdo->prepare("SELECT id FROM kegiatan WHERE slug = ? AND id != ?");
        $stmt->execute([$slug, $record['id']]);

        if ($stmt->rowCount() == 0) {
            $stmt = $pdo->prepare("UPDATE kegiatan SET slug = ? WHERE id = ?");
            $stmt->execute([$slug, $record['id']]);
            echo "<p>✅ Slug generated: <strong>{$slug}</strong> untuk '{$record['judul']}'</p>";
        }
    }

    // ==========================================
    // 4. FIX FEATURED FIELD TYPE
    // ==========================================
    echo "<h3>4. 🔧 Memperbaiki Field Featured</h3>";

    // Check current featured field type
    $stmt = $pdo->query("DESCRIBE kegiatan featured");
    $fieldInfo = $stmt->fetch();

    if (strpos($fieldInfo['Type'], 'varchar') !== false || strpos($fieldInfo['Type'], 'char') !== false) {
        echo "<p>🔄 Mengubah field featured dari string ke boolean</p>";

        // Convert string values to boolean
        $pdo->exec("UPDATE kegiatan SET featured = CASE
            WHEN featured = '1' OR featured = 'true' THEN 1
            ELSE 0
        END");

        // Change column type
        $pdo->exec("ALTER TABLE kegiatan MODIFY featured BOOLEAN DEFAULT FALSE");

        echo "<p>✅ Field featured berhasil diubah ke BOOLEAN</p>";
    } else {
        echo "<p>✅ Field featured sudah dalam format boolean</p>";
    }

    // ==========================================
    // 5. GENERATE SLUGS FOR FORUM POSTS
    // ==========================================
    echo "<h3>5. 🔗 Generate Slugs untuk Forum Posts</h3>";

    // Add slug column to forum_posts if it doesn't exist
    try {
        $pdo->exec("ALTER TABLE forum_posts ADD COLUMN slug VARCHAR(255) UNIQUE AFTER judul");
        echo "<p>✅ Kolom slug ditambahkan ke forum_posts</p>";
    } catch (Exception $e) {
        // Column might already exist
        echo "<p>ℹ️ Kolom slug sudah ada di forum_posts</p>";
    }

    // Generate slugs for forum posts
    $stmt = $pdo->query("SELECT id, judul FROM forum_posts WHERE slug IS NULL OR slug = ''");
    $forumNoSlug = $stmt->fetchAll();

    echo "<p>Ditemukan " . count($forumNoSlug) . " forum post tanpa slug</p>";

    foreach ($forumNoSlug as $post) {
        $baseSlug = generateSlug($post['judul']);
        $slug = $baseSlug . '-' . $post['id'];

        $stmt = $pdo->prepare("UPDATE forum_posts SET slug = ? WHERE id = ?");
        $stmt->execute([$slug, $post['id']]);
        echo "<p>✅ Forum slug generated: <strong>{$slug}</strong></p>";
    }

    // ==========================================
    // 6. CLEANUP ORPHANED COMMENTS
    // ==========================================
    echo "<h3>6. 🧽 Membersihkan Komentar Orphaned</h3>";

    $stmt = $pdo->prepare("
        DELETE fc FROM forum_comments fc
        LEFT JOIN forum_posts fp ON fc.post_id = fp.id
        WHERE fp.id IS NULL
    ");
    $stmt->execute();
    $orphanedComments = $stmt->rowCount();

    echo "<p>🗑️ Menghapus {$orphanedComments} komentar orphaned</p>";

    // ==========================================
    // 7. OPTIMIZE TABLES
    // ==========================================
    echo "<h3>7. ⚡ Optimize Database Tables</h3>";

    $tables = ['kegiatan', 'galeri', 'forum_posts', 'forum_comments', 'calendar_events', 'pendaftaran_siswa', 'admin_users'];

    // Close any open statements first
    $pdo->setAttribute(PDO::MYSQL_ATTR_USE_BUFFERED_QUERY, true);

    foreach ($tables as $table) {
        try {
            $stmt = $pdo->prepare("OPTIMIZE TABLE {$table}");
            $stmt->execute();
            echo "<p>✅ Tabel <strong>{$table}</strong> dioptimalkan</p>";
        } catch (Exception $e) {
            echo "<p>⚠️ Gagal optimize tabel {$table}: " . $e->getMessage() . "</p>";
        }
    }

    // ==========================================
    // 8. FINAL STATISTICS
    // ==========================================
    echo "<h3>8. 📊 Statistik Akhir</h3>";

    $stats = [];

    foreach ($tables as $table) {
        try {
            $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM {$table}");
            $stmt->execute();
            $result = $stmt->fetch();
            $stats[$table] = $result['count'];
        } catch (Exception $e) {
            echo "<p>⚠️ Error getting count for {$table}: " . $e->getMessage() . "</p>";
            $stats[$table] = 'Error';
        }
    }

    echo "<table border='1' style='border-collapse: collapse; margin: 10px 0;'>";
    echo "<tr><th>Tabel</th><th>Jumlah Record</th></tr>";
    foreach ($stats as $table => $count) {
        echo "<tr><td>{$table}</td><td>{$count}</td></tr>";
    }
    echo "</table>";

    echo "</div>";
    echo "<h2 style='color: #4caf50;'>✅ CLEANUP SELESAI!</h2>";
    echo "<p>Database telah dibersihkan dan dioptimalkan.</p>";
    echo "<p><strong>Rekomendasi:</strong> Test ulang API endpoints untuk memastikan data sudah bersih.</p>";

    echo "<div style='background: #fff3cd; padding: 15px; margin: 20px 0; border-left: 4px solid #ffc107;'>";
    echo "<h4>🔄 Test Ulang API:</h4>";
    echo "<ul>";
    echo "<li><a href='api/kegiatan.php' target='_blank'>Test API Kegiatan</a></li>";
    echo "<li><a href='api/galeri.php' target='_blank'>Test API Galeri</a></li>";
    echo "<li><a href='api/forum.php' target='_blank'>Test API Forum</a></li>";
    echo "<li><a href='berita.html' target='_blank'>Test Halaman Berita</a></li>";
    echo "<li><a href='galeri.html' target='_blank'>Test Halaman Galeri</a></li>";
    echo "</ul>";
    echo "</div>";

} catch (PDOException $e) {
    echo "<div style='background: #f8d7da; padding: 15px; margin: 20px 0; border-left: 4px solid #dc3545;'>";
    echo "<h2 style='color: #dc3545;'>❌ ERROR DATABASE CLEANUP</h2>";
    echo "<p><strong>Error:</strong> " . $e->getMessage() . "</p>";
    echo "<p>Pastikan:</p>";
    echo "<ul>";
    echo "<li>MySQL service sedang berjalan</li>";
    echo "<li>Database 'smk_bhakti' sudah dibuat</li>";
    echo "<li>File config.php sudah benar</li>";
    echo "</ul>";
    echo "</div>";
}
?>