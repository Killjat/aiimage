<?php
/**
 * 数据库配额字段更新脚本
 * 为现有数据库添加配额管理字段
 * 
 * 使用方法: php update_database_quota.php
 */

require_once __DIR__ . '/vendor/autoload.php';

// 加载环境变量
$dotenv = Dotenv\Dotenv::createImmutable(__DIR__);
$dotenv->load();

$host = $_ENV['DB_HOST'] ?? '127.0.0.1';
$port = $_ENV['DB_PORT'] ?? '3306';
$dbname = $_ENV['DB_NAME'] ?? 'ai_chat_system';
$username = $_ENV['DB_USER'] ?? 'root';
$password = $_ENV['DB_PASS'] ?? '';

echo "=== 数据库配额字段更新 ===\n\n";

try {
    $dsn = "mysql:host={$host};port={$port};dbname={$dbname};charset=utf8mb4";
    $pdo = new PDO($dsn, $username, $password, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    ]);

    echo "✅ 成功连接到数据库\n\n";

    // 检查 users 表是否存在 image_quota 字段
    echo "检查 users 表字段...\n";
    $stmt = $pdo->query("SHOW COLUMNS FROM users LIKE 'image_quota'");
    $hasQuotaField = $stmt->fetch();

    if (!$hasQuotaField) {
        echo "添加 image_quota 字段...\n";
        $pdo->exec("ALTER TABLE users ADD COLUMN image_quota INT UNSIGNED DEFAULT 10 COMMENT '图片生成配额'");
        echo "✅ image_quota 字段添加成功\n";
    } else {
        echo "✓ image_quota 字段已存在\n";
    }

    // 检查 image_used 字段
    $stmt = $pdo->query("SHOW COLUMNS FROM users LIKE 'image_used'");
    $hasUsedField = $stmt->fetch();

    if (!$hasUsedField) {
        echo "添加 image_used 字段...\n";
        $pdo->exec("ALTER TABLE users ADD COLUMN image_used INT UNSIGNED DEFAULT 0 COMMENT '已使用的图片生成次数'");
        echo "✅ image_used 字段添加成功\n";
    } else {
        echo "✓ image_used 字段已存在\n";
    }

    // 为现有用户设置默认配额
    echo "\n为现有用户设置默认配额...\n";
    $stmt = $pdo->query("SELECT COUNT(*) as count FROM users WHERE image_quota IS NULL OR image_used IS NULL");
    $result = $stmt->fetch();
    $needUpdateCount = $result['count'];

    if ($needUpdateCount > 0) {
        $pdo->exec("UPDATE users SET image_quota = 10 WHERE image_quota IS NULL");
        $pdo->exec("UPDATE users SET image_used = 0 WHERE image_used IS NULL");
        echo "✅ 已为 {$needUpdateCount} 个用户设置默认配额\n";
    } else {
        echo "✓ 所有用户已有配额设置\n";
    }

    // 检查 image_generations 表
    echo "\n检查 image_generations 表...\n";
    $stmt = $pdo->query("SHOW TABLES LIKE 'image_generations'");
    $hasTable = $stmt->fetch();

    if (!$hasTable) {
        echo "创建 image_generations 表...\n";
        $pdo->exec("
            CREATE TABLE image_generations (
                id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                user_id INT UNSIGNED NOT NULL,
                model VARCHAR(100) NOT NULL,
                prompt TEXT NOT NULL,
                image_url TEXT,
                size VARCHAR(20),
                quality VARCHAR(20),
                status ENUM('success', 'failed') DEFAULT 'success',
                error_message TEXT NULL,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
                INDEX idx_user_id (user_id),
                INDEX idx_created_at (created_at),
                INDEX idx_status (status)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ");
        echo "✅ image_generations 表创建成功\n";
    } else {
        echo "✓ image_generations 表已存在\n";
        
        // 检查是否有 status 字段
        $stmt = $pdo->query("SHOW COLUMNS FROM image_generations LIKE 'status'");
        $hasStatusField = $stmt->fetch();
        
        if (!$hasStatusField) {
            echo "添加 status 字段...\n";
            $pdo->exec("ALTER TABLE image_generations ADD COLUMN status ENUM('success', 'failed') DEFAULT 'success'");
            echo "✅ status 字段添加成功\n";
        }
        
        // 检查是否有 error_message 字段
        $stmt = $pdo->query("SHOW COLUMNS FROM image_generations LIKE 'error_message'");
        $hasErrorField = $stmt->fetch();
        
        if (!$hasErrorField) {
            echo "添加 error_message 字段...\n";
            $pdo->exec("ALTER TABLE image_generations ADD COLUMN error_message TEXT NULL");
            echo "✅ error_message 字段添加成功\n";
        }
    }

    // 显示统计信息
    echo "\n=== 统计信息 ===\n";
    
    $stmt = $pdo->query("SELECT COUNT(*) as count FROM users");
    $result = $stmt->fetch();
    echo "用户总数: {$result['count']}\n";
    
    $stmt = $pdo->query("SELECT SUM(image_quota) as total_quota, SUM(image_used) as total_used FROM users");
    $result = $stmt->fetch();
    echo "总配额: {$result['total_quota']}\n";
    echo "已使用: {$result['total_used']}\n";
    echo "剩余配额: " . ($result['total_quota'] - $result['total_used']) . "\n";
    
    $stmt = $pdo->query("SELECT COUNT(*) as count FROM image_generations");
    $result = $stmt->fetch();
    echo "图片生成记录: {$result['count']}\n";

    echo "\n=== 更新完成 ===\n";
    echo "✅ 配额系统已准备就绪！\n\n";
    
    echo "下一步:\n";
    echo "1. 重启后端服务\n";
    echo "2. 测试配额功能\n";
    echo "3. 查看 QUOTA_SYSTEM.md 了解详细信息\n";

} catch (PDOException $e) {
    echo "\n❌ 数据库错误: " . $e->getMessage() . "\n\n";
    echo "请检查:\n";
    echo "1. 数据库是否已创建\n";
    echo "2. backend/.env 中的配置是否正确\n";
    echo "3. 数据库用户是否有 ALTER 权限\n";
    exit(1);
} catch (Exception $e) {
    echo "\n❌ 错误: " . $e->getMessage() . "\n";
    exit(1);
}
