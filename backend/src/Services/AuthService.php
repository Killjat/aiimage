<?php

namespace App\Services;

use App\Database\Database;
use Firebase\JWT\JWT;
use Firebase\JWT\Key;
use PDO;

class AuthService
{
    private PDO $db;
    private string $jwtSecret;
    private int $jwtExpiration;

    public function __construct()
    {
        $this->db = Database::getConnection();
        $this->jwtSecret = $_ENV['JWT_SECRET'] ?? 'default_secret_key';
        $this->jwtExpiration = (int)($_ENV['JWT_EXPIRATION'] ?? 86400);
    }

    /**
     * Register a new user
     * 
     * @param string $email
     * @param string $password
     * @param string|null $username
     * @return array
     * @throws \Exception
     */
    public function register(string $email, string $password, ?string $username = null): array
    {
        // 验证邮箱格式
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            throw new \Exception('邮箱格式不正确');
        }

        // 验证密码长度
        if (strlen($password) < 6) {
            throw new \Exception('密码长度至少为 6 位');
        }

        // 检查邮箱是否已存在
        $stmt = $this->db->prepare('SELECT id FROM users WHERE email = ?');
        $stmt->execute([$email]);
        if ($stmt->fetch()) {
            throw new \Exception('该邮箱已被注册');
        }

        // 创建用户
        $hashedPassword = password_hash($password, PASSWORD_BCRYPT);
        $stmt = $this->db->prepare(
            'INSERT INTO users (email, password, username) VALUES (?, ?, ?)'
        );
        $stmt->execute([$email, $hashedPassword, $username]);

        $userId = $this->db->lastInsertId();

        // 生成 token
        $token = $this->generateToken($userId, $email);

        return [
            'user' => [
                'id' => $userId,
                'email' => $email,
                'username' => $username,
            ],
            'token' => $token,
        ];
    }

    /**
     * Login user
     * 
     * @param string $email
     * @param string $password
     * @return array
     * @throws \Exception
     */
    public function login(string $email, string $password): array
    {
        // 查找用户
        $stmt = $this->db->prepare(
            'SELECT id, email, password, username, status FROM users WHERE email = ?'
        );
        $stmt->execute([$email]);
        $user = $stmt->fetch();

        if (!$user) {
            throw new \Exception('邮箱或密码错误');
        }

        // 检查用户状态
        if ($user['status'] !== 'active') {
            throw new \Exception('账户已被禁用');
        }

        // 验证密码
        if (!password_verify($password, $user['password'])) {
            throw new \Exception('邮箱或密码错误');
        }

        // 更新最后登录时间
        $stmt = $this->db->prepare('UPDATE users SET last_login_at = NOW() WHERE id = ?');
        $stmt->execute([$user['id']]);

        // 生成 token
        $token = $this->generateToken($user['id'], $user['email']);

        return [
            'user' => [
                'id' => $user['id'],
                'email' => $user['email'],
                'username' => $user['username'],
            ],
            'token' => $token,
        ];
    }

    /**
     * Generate JWT token
     * 
     * @param int $userId
     * @param string $email
     * @return string
     */
    private function generateToken(int $userId, string $email): string
    {
        $issuedAt = time();
        $expirationTime = $issuedAt + $this->jwtExpiration;

        $payload = [
            'iat' => $issuedAt,
            'exp' => $expirationTime,
            'user_id' => $userId,
            'email' => $email,
        ];

        return JWT::encode($payload, $this->jwtSecret, 'HS256');
    }

    /**
     * Verify JWT token
     * 
     * @param string $token
     * @return array
     * @throws \Exception
     */
    public function verifyToken(string $token): array
    {
        try {
            $decoded = JWT::decode($token, new Key($this->jwtSecret, 'HS256'));
            return (array)$decoded;
        } catch (\Exception $e) {
            throw new \Exception('Token 无效或已过期');
        }
    }

    /**
     * Get user by ID
     * 
     * @param int $userId
     * @return array|null
     */
    public function getUserById(int $userId): ?array
    {
        $stmt = $this->db->prepare(
            'SELECT id, email, username, created_at, last_login_at FROM users WHERE id = ? AND status = "active"'
        );
        $stmt->execute([$userId]);
        $user = $stmt->fetch();

        return $user ?: null;
    }
}
