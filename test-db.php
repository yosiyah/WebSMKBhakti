<?php
// Simple test to check database connection and galeri data
require 'config.php';

try {
    $pdo = getDB();
    echo "<h2>✅ Database Connection Successful!</h2>";

    // Check if galeri table exists and has data
    $stmt = $pdo->query("SHOW TABLES LIKE 'galeri'");
    $tableExists = $stmt->rowCount() > 0;

    if ($tableExists) {
        echo "<h3>✅ Galeri table exists</h3>";

        // Check data in galeri table
        $stmt = $pdo->query("SELECT COUNT(*) as count FROM galeri");
        $result = $stmt->fetch();
        $count = $result['count'];

        echo "<p>Total galeri items: <strong>$count</strong></p>";

        if ($count > 0) {
            echo "<h4>Sample galeri data:</h4>";
            $stmt = $pdo->query("SELECT * FROM galeri LIMIT 5");
            $items = $stmt->fetchAll();

            echo "<ul>";
            foreach ($items as $item) {
                echo "<li><strong>{$item['judul']}</strong> - {$item['kategori']} - {$item['gambar']}</li>";
            }
            echo "</ul>";
        } else {
            echo "<p style='color: red;'>❌ No data in galeri table. Please run setup.php first.</p>";
        }
    } else {
        echo "<p style='color: red;'>❌ Galeri table does not exist. Please run setup.php first.</p>";
    }

    // Test API endpoint
    echo "<h3>Test API Endpoint:</h3>";
    echo "<p><a href='api/galeri.php' target='_blank'>Test Galeri API</a></p>";

} catch (PDOException $e) {
    echo "<h2>❌ Database Connection Failed</h2>";
    echo "<p>Error: " . $e->getMessage() . "</p>";
    echo "<p>Please check:</p>";
    echo "<ul>";
    echo "<li>MySQL service is running in XAMPP</li>";
    echo "<li>Database 'smk_bhakti' exists</li>";
    echo "<li>Username/password is correct</li>";
    echo "</ul>";
}
?>