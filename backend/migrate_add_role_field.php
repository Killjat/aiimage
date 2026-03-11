<?php

require __DIR__ . '/vendor/autoload.php';

$dotenv = Dotenv\Dotenv::createImmutable(__DIR__);
$dotenv->load();

try {
    $pdo = new PDO(
        'mysql:host=' . $_ENV['DB_HOST'] . ';dbname=' . $_ENV['DB_NAME'],
        $_ENV['DB_USER'],
        $_ENV['DB_PASS'],
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
    );

    // 检查 role 字段是否存在
    $stmt = $pdo->query("SHOW COLUMNS FROM users LIKE 'role'");
    if ($stmt->rowCount() === 0) {
        // 添加 role 字段
        $pdo->exec("ALTER TABLE users ADD COLUMN role ENUM('user', 'admin') DEFAULT 'user' AFTER status");
        echo "✅ 已添加 role 字段\n";
    } else {
        echo "ℹ️  role 字段已存在\n";
    }

    // 检查 image_quota_unlimited 字段是否存在
    $stmt = $pdo->query("SHOW COLUMNS FROM users LIKE 'image_quota_unlimited'");
    if ($stmt->rowCount() === 0) {
        // 添加 image_quota_unlimited 字段
        $pdo->exec("ALTER TABLE users ADD COLUMN image_quota_unlimited BOOLEAN DEFAULT FALSE AFTER image_used");
        echo "✅ 已添加 image_quota_unlimited 字段\n";
    } else {
        echo "ℹ️  image_quota_unlimited 字段已存在\n";
    }

    echo "\n✅ 迁移完成！\n";

} catch (Exception $e) {
    echo "❌ 错误: " . $e->getMessage() . "\n";
    exit(1);
}
