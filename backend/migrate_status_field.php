<?php

// 加载环境变量
$dotenv = __DIR__ . '/.env';
if (file_exists($dotenv)) {
    $lines = file($dotenv, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        if (strpos($line, '=') !== false && strpos($line, '#') !== 0) {
            list($key, $value) = explode('=', $line, 2);
            $_ENV[trim($key)] = trim($value);
        }
    }
}

// 数据库配置
$host = $_ENV['DB_HOST'] ?? '127.0.0.1';
$port = $_ENV['DB_PORT'] ?? 3306;
$db = $_ENV['DB_NAME'] ?? 'ai_chat_system';
$user = $_ENV['DB_USER'] ?? 'root';
$pass = $_ENV['DB_PASS'] ?? '';

echo "=== 迁移 image_generations 表的 status 字段 ===\n\n";

try {
    // 连接数据库
    $pdo = new PDO(
        "mysql:host=$host;port=$port;dbname=$db;charset=utf8mb4",
        $user,
        $pass,
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        ]
    );
    
    echo "✅ 数据库连接成功\n\n";
    
    // 检查表是否存在
    $stmt = $pdo->query("SHOW TABLES LIKE 'image_generations'");
    if ($stmt->rowCount() === 0) {
        echo "❌ 表 image_generations 不存在\n";
        exit(1);
    }
    
    echo "✅ 表 image_generations 存在\n\n";
    
    // 修改 status 字段
    echo "修改 status 字段...\n";
    $sql = "ALTER TABLE image_generations MODIFY status ENUM('success', 'failed', 'processing', 'completed') DEFAULT 'success'";
    $pdo->exec($sql);
    
    echo "✅ status 字段已更新\n\n";
    
    // 验证修改
    $stmt = $pdo->query("DESCRIBE image_generations");
    $columns = $stmt->fetchAll();
    
    foreach ($columns as $column) {
        if ($column['Field'] === 'status') {
            echo "✅ 字段信息:\n";
            echo "   字段名: " . $column['Field'] . "\n";
            echo "   类型: " . $column['Type'] . "\n";
            echo "   默认值: " . $column['Default'] . "\n";
            break;
        }
    }
    
    echo "\n=== 迁移完成 ===\n";
    
} catch (Exception $e) {
    echo "❌ 错误: " . $e->getMessage() . "\n";
    exit(1);
}
