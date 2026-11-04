<?php
/**
 * Database Connection Test
 * Bu dosyayı /htdocs/ klasörüne yükle ve tarayıcıda aç
 * URL: https://btkodev121.42web.io/test-db-connection.php
 */

// Config dosyasını yükle
$configPath = __DIR__ . '/config/config.local.php';

echo "<pre style='background: #1a1a1a; color: #10b981; padding: 20px; border-radius: 8px;'>";
echo "╔════════════════════════════════════════╗\n";
echo "║  Database Connection Test              ║\n";
echo "╚════════════════════════════════════════╝\n\n";

// 1. Config dosyası var mı?
echo "1️⃣ Config Dosyası Kontrolü:\n";
if (!file_exists($configPath)) {
    echo "❌ HATA: config/config.local.php BULUNAMADI!\n";
    echo "   Dosya yolu: $configPath\n";
    exit;
}
echo "✅ config.local.php var\n\n";

// 2. Config'i yükle
$config = require $configPath;

echo "2️⃣ Config Bilgileri:\n";
echo "   Host: " . $config['db_host'] . "\n";
echo "   Database: " . $config['db_name'] . "\n";
echo "   User: " . $config['db_user'] . "\n";
echo "   Pass: " . (empty($config['db_pass']) ? '❌ BOŞ!' : '✅ VAR (' . strlen($config['db_pass']) . ' karakter)') . "\n\n";

// 3. PDO bağlantısını test et
echo "3️⃣ Database Bağlantısı Test Ediliyor...\n";

try {
    $dsn = "mysql:host={$config['db_host']};dbname={$config['db_name']};charset={$config['db_charset']}";
    
    $pdo = new PDO(
        $dsn,
        $config['db_user'],
        $config['db_pass'],
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ]
    );
    
    echo "✅ DATABASE BAĞLANTISI BAŞARILI!\n\n";
    
    // 4. Tabloları kontrol et
    echo "4️⃣ Tablolar Kontrol Ediliyor...\n";
    $stmt = $pdo->query("SHOW TABLES");
    $tables = $stmt->fetchAll(PDO::FETCH_COLUMN);
    
    if (empty($tables)) {
        echo "❌ HATA: Database boş! Hiç tablo yok.\n";
        echo "\n📋 YAPILACAKLAR:\n";
        echo "   1. InfinityFree → phpMyAdmin'e git\n";
        echo "   2. Database'ini seç (if0_42402436_btkodev121)\n";
        echo "   3. Import sekmesine tıkla\n";
        echo "   4. database/schema.sql dosyasını yükle\n";
        echo "   5. database/seed.sql dosyasını yükle\n";
    } else {
        echo "✅ Toplam " . count($tables) . " tablo bulundu:\n";
        foreach ($tables as $table) {
            echo "   • $table\n";
        }
        
        // Gerekli tabloları kontrol et
        echo "\n5️⃣ Gerekli Tablolar:\n";
        $required = ['users', 'products', 'orders', 'categories', 'cart_items'];
        foreach ($required as $req) {
            if (in_array($req, $tables)) {
                echo "   ✅ $req\n";
            } else {
                echo "   ❌ $req EKSIK!\n";
            }
        }
        
        // Demo kullanıcıları kontrol et
        echo "\n6️⃣ Demo Kullanıcılar:\n";
        try {
            $stmt = $pdo->query("SELECT email, role FROM users LIMIT 5");
            $users = $stmt->fetchAll();
            
            if (empty($users)) {
                echo "   ⚠️  Kullanıcı yok! seed.sql import et.\n";
            } else {
                foreach ($users as $user) {
                    echo "   • {$user['email']} ({$user['role']})\n";
                }
            }
        } catch (Exception $e) {
            echo "   ⚠️  users tablosu okunamadı: " . $e->getMessage() . "\n";
        }
    }
    
} catch (PDOException $e) {
    echo "❌ BAĞLANTI HATASI!\n\n";
    echo "Hata Mesajı: " . $e->getMessage() . "\n\n";
    
    echo "🔍 Olası Sebepler:\n";
    if (strpos($e->getMessage(), 'Access denied') !== false) {
        echo "   • Kullanıcı adı veya şifre yanlış\n";
        echo "   • InfinityFree Control Panel'den bilgileri kontrol et\n";
    } elseif (strpos($e->getMessage(), 'Unknown database') !== false) {
        echo "   • Database adı yanlış\n";
        echo "   • Database henüz oluşturulmamış\n";
    } elseif (strpos($e->getMessage(), "Can't connect") !== false) {
        echo "   • Host adresi yanlış\n";
        echo "   • InfinityFree sunucuları erişilemiyor\n";
    } else {
        echo "   • Bilinmeyen hata\n";
    }
}

echo "\n════════════════════════════════════════\n";
echo "Test tamamlandı.\n";
echo "Bu dosyayı SİL! (Güvenlik için)\n";
echo "</pre>";
?>
