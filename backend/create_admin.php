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

    // 管理员账号信息
    $email = 'admin@example.com';
    $password = 'admin123456';
    $username = 'Admin';
    $hashedPassword = password_hash($password, PASSWORD_BCRYPT);

    // 检查账号是否已存在
    $stmt = $pdo->prepare('SELECT id FROM users WHERE email = ?');
    $stmt->execute([$email]);
    
    if ($stmt->rowCount() > 0) {
        echo "⚠️  账号已存在: $email\n";
        exit(0);
    }

    // 创建管理员账号
    $stmt = $pdo->prepare(
        'INSERT INTO users (email, password, username, image_quota, image_quota_unlimited, role, status) 
         VALUES (?, ?, ?, ?, ?, ?, ?)'
    );
    
    $stmt->execute([
        $email,
        $hashedPassword,
        $username,
        999999,  // 设置一个很大的配额
        true,    // 无限制标志
        'admin', // 角色
        'active' // 状态
    ]);

    $adminId = $pdo->lastInsertId();

    echo "✅ 管理员账号创建成功！\n";
    echo "\n📋 账号信息:\n";
    echo "   邮箱: $email\n";
    echo "   密码: $password\n";
    echo "   用户名: $username\n";
    echo "   ID: $adminId\n";
    echo "   角色: admin\n";
    echo "   配额: 无限制\n";
    echo "\n⚠️  请妥善保管这些凭证！\n";

} catch (Exception $e) {
    echo "❌ 错误: " . $e->getMessage() . "\n";
    exit(1);
}
