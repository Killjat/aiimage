<?php
/**
 * 数据库初始化脚本
 * 运行此脚本来创建数据库和表
 * 
 * 使用方法: php init_database.php
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

echo "=== 数据库初始化 ===\n\n";
echo "数据库配置:\n";
echo "- Host: {$host}:{$port}\n";
echo "- Database: {$dbname}\n";
echo "- User: {$username}\n\n";

try {
    // 首先连接到 MySQL（不指定数据库）
    $dsn = "mysql:host={$host};port={$port};charset=utf8mb4";
    $pdo = new PDO($dsn, $username, $password, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    ]);

    echo "✅ 成功连接到 MySQL\n\n";

    // 创建数据库
    echo "正在创建数据库 '{$dbname}'...\n";
    $pdo->exec("CREATE DATABASE IF NOT EXISTS `{$dbname}` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
    echo "✅ 数据库创建成功\n\n";

    // 切换到新数据库
    $pdo->exec("USE `{$dbname}`");

    // 读取并执行 schema.sql
    $schemaFile = __DIR__ . '/database/schema.sql';
    if (!file_exists($schemaFile)) {
        throw new Exception("Schema 文件不存在: {$schemaFile}");
    }

    $sql = file_get_contents($schemaFile);
    
    // 移除 CREATE DATABASE 和 USE 语句（我们已经手动处理了）
    $sql = preg_replace('/CREATE DATABASE.*?;/i', '', $sql);
    $sql = preg_replace('/USE.*?;/i', '', $sql);
    
    // 分割并执行每个语句
    $statements = array_filter(
        array_map('trim', explode(';', $sql)),
        function($stmt) { return !empty($stmt) && !preg_match('/^--/', $stmt); }
    );

    echo "正在创建数据表...\n";
    foreach ($statements as $statement) {
        if (!empty($statement)) {
            $pdo->exec($statement);
        }
    }
    echo "✅ 数据表创建成功\n\n";

    // 检查创建的表
    $tables = $pdo->query("SHOW TABLES")->fetchAll(PDO::FETCH_COLUMN);
    echo "已创建的表:\n";
    foreach ($tables as $table) {
        echo "  - {$table}\n";
    }

    echo "\n=== 初始化完成 ===\n";
    echo "✅ 数据库已准备就绪！\n\n";
    echo "下一步:\n";
    echo "1. 启动后端服务: cd backend && php -S 0.0.0.0:8080 -t public\n";
    echo "2. 启动前端服务: cd frontend && npm run dev\n";
    echo "3. 访问 http://localhost:5173 开始使用\n";

} catch (PDOException $e) {
    echo "\n❌ 数据库错误: " . $e->getMessage() . "\n\n";
    echo "请检查:\n";
    echo "1. MySQL 服务是否已启动\n";
    echo "2. backend/.env 中的数据库配置是否正确\n";
    echo "3. 数据库用户是否有足够的权限\n";
    exit(1);
} catch (Exception $e) {
    echo "\n❌ 错误: " . $e->getMessage() . "\n";
    exit(1);
}
