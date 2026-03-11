<?php
/**
 * 迁移脚本：添加图片库表
 */

require_once __DIR__ . '/vendor/autoload.php';

$dotenv = \Dotenv\Dotenv::createImmutable(__DIR__);
$dotenv->load();

use App\Database\Database;

$pdo = Database::getConnection();

echo "=== 添加图片库表 ===\n\n";

try {
    // 检查表是否已存在
    $stmt = $pdo->query("SHOW TABLES LIKE 'image_gallery'");
    
    if ($stmt && $stmt->rowCount() > 0) {
        echo "✅ 图片库表已存在\n";
        exit(0);
    }

    // 创建图片库表
    $sql = "CREATE TABLE IF NOT EXISTS image_gallery (
        id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        user_id INT UNSIGNED NOT NULL COMMENT '创建者ID',
        username VARCHAR(100) COMMENT '创建者用户名',
        model VARCHAR(100) NOT NULL COMMENT '使用的模型',
        llm_model VARCHAR(100) COMMENT '使用的大模型（如果有）',
        prompt TEXT NOT NULL COMMENT '提示词',
        negative_prompt TEXT COMMENT '反向提示词',
        image_url LONGTEXT NOT NULL COMMENT 'Base64编码的图片数据或URL',
        image_size VARCHAR(50) COMMENT '图片尺寸',
        image_quality VARCHAR(100) COMMENT '图片质量',
        is_public BOOLEAN DEFAULT TRUE COMMENT '是否公开',
        views INT UNSIGNED DEFAULT 0 COMMENT '浏览次数',
        likes INT UNSIGNED DEFAULT 0 COMMENT '点赞数',
        description TEXT COMMENT '图片描述',
        tags VARCHAR(255) COMMENT '标签（逗号分隔）',
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
        INDEX idx_user_id (user_id),
        INDEX idx_created_at (created_at),
        INDEX idx_is_public (is_public),
        INDEX idx_views (views),
        INDEX idx_likes (likes),
        INDEX idx_model (model),
        INDEX idx_llm_model (llm_model),
        FULLTEXT INDEX ft_prompt (prompt),
        FULLTEXT INDEX ft_tags (tags)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";

    $pdo->exec($sql);
    echo "✅ 图片库表创建成功\n";
    echo "   表名: image_gallery\n";
    echo "   字段数: 15\n";
    echo "   索引数: 8\n";

    // 验证表结构
    echo "\n📋 表结构验证:\n";
    $stmt = $pdo->query("DESCRIBE image_gallery");
    $fields = $stmt->fetchAll();
    
    foreach ($fields as $field) {
        echo "   ✓ {$field['Field']} ({$field['Type']})\n";
    }
    echo "\n✅ 共 " . count($fields) . " 个字段\n";

    // 验证索引
    echo "\n🔍 索引验证:\n";
    $stmt = $pdo->query("SHOW INDEXES FROM image_gallery");
    $indexes = [];
    
    while ($row = $stmt->fetch()) {
        $key = $row['Key_name'];
        if (!isset($indexes[$key])) {
            $indexes[$key] = [];
        }
        $indexes[$key][] = $row['Column_name'];
    }
    
    foreach ($indexes as $name => $columns) {
        echo "   ✓ {$name}: " . implode(', ', $columns) . "\n";
    }
    echo "\n✅ 共 " . count($indexes) . " 个索引\n";

    echo "\n=== 迁移完成 ===\n";

} catch (\Exception $e) {
    echo "❌ 错误: " . $e->getMessage() . "\n";
    exit(1);
}
