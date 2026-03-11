<?php

namespace App\Services;

use App\Database\Database;
use PDO;

class QuotaService
{
    private PDO $db;
    private const DEFAULT_IMAGE_QUOTA = 10;
    private const GUEST_IMAGE_QUOTA = 3;  // 游客配额：3张

    public function __construct()
    {
        $this->db = Database::getConnection();
    }

    /**
     * Get or create guest quota record
     * 使用 IP 地址作为游客标识，存储在内存或临时表中
     */
    private function getGuestQuotaFromCache(string $guestIp): array
    {
        // 使用文件缓存存储游客配额（简单实现）
        $cacheDir = __DIR__ . '/../../cache';
        if (!is_dir($cacheDir)) {
            mkdir($cacheDir, 0777, true);
        }
        
        $cacheFile = $cacheDir . '/guest_quota_' . md5($guestIp) . '.json';
        
        if (file_exists($cacheFile)) {
            $data = json_decode(file_get_contents($cacheFile), true);
            // 检查是否过期（24小时）
            if (isset($data['expires_at']) && time() < $data['expires_at']) {
                return $data;
            }
        }
        
        // 创建新的游客配额记录
        $data = [
            'ip' => $guestIp,
            'total' => self::GUEST_IMAGE_QUOTA,
            'used' => 0,
            'remaining' => self::GUEST_IMAGE_QUOTA,
            'created_at' => time(),
            'expires_at' => time() + 86400  // 24小时后过期
        ];
        
        file_put_contents($cacheFile, json_encode($data));
        return $data;
    }

    private function saveGuestQuotaToCache(string $guestIp, array $data): void
    {
        $cacheDir = __DIR__ . '/../../cache';
        $cacheFile = $cacheDir . '/guest_quota_' . md5($guestIp) . '.json';
        file_put_contents($cacheFile, json_encode($data));
    }

    /**
     * Check if guest has available image generation quota
     */
    public function hasGuestImageQuota(string $guestIp): bool
    {
        $quota = $this->getGuestQuotaFromCache($guestIp);
        return $quota['remaining'] > 0;
    }

    /**
     * Get guest's image generation quota
     */
    public function getGuestImageQuota(string $guestIp): array
    {
        $data = $this->getGuestQuotaFromCache($guestIp);
        return [
            'total' => $data['total'],
            'used' => $data['used'],
            'remaining' => $data['remaining']
        ];
    }

    /**
     * Use one guest image generation quota
     */
    public function useGuestImageQuota(string $guestIp): bool
    {
        $data = $this->getGuestQuotaFromCache($guestIp);
        
        if ($data['remaining'] <= 0) {
            return false;
        }
        
        $data['used']++;
        $data['remaining'] = max(0, $data['total'] - $data['used']);
        
        $this->saveGuestQuotaToCache($guestIp, $data);
        return true;
    }

    /**
     * Check if user has available image generation quota
     * 
     * @param int $userId
     * @return bool
     */
    public function hasImageQuota(int $userId): bool
    {
        // 检查是否是无限制用户
        $stmt = $this->db->prepare('SELECT image_quota_unlimited FROM users WHERE id = ?');
        $stmt->execute([$userId]);
        $user = $stmt->fetch();
        
        if ($user && $user['image_quota_unlimited']) {
            return true; // 无限制用户总是有配额
        }
        
        $quota = $this->getImageQuota($userId);
        return $quota['remaining'] > 0;
    }

    /**
     * Get user's image generation quota
     * 
     * @param int $userId
     * @return array ['total' => int, 'used' => int, 'remaining' => int]
     */
    public function getImageQuota(int $userId): array
    {
        $stmt = $this->db->prepare(
            'SELECT image_quota, image_used, image_quota_unlimited FROM users WHERE id = ?'
        );
        $stmt->execute([$userId]);
        $user = $stmt->fetch();

        if (!$user) {
            return [
                'total' => 0,
                'used' => 0,
                'remaining' => 0,
            ];
        }

        // 如果是无限制用户，返回特殊值
        if ($user['image_quota_unlimited']) {
            return [
                'total' => 999999,
                'used' => (int)$user['image_used'],
                'remaining' => 999999,
            ];
        }

        $total = (int)$user['image_quota'];
        $used = (int)$user['image_used'];

        return [
            'total' => $total,
            'used' => $used,
            'remaining' => max(0, $total - $used),
        ];
    }

    /**
     * Use one image generation quota
     * 
     * @param int $userId
     * @return bool
     * @throws \Exception
     */
    public function useImageQuota(int $userId): bool
    {
        // 检查是否是无限制用户
        $stmt = $this->db->prepare('SELECT image_quota_unlimited FROM users WHERE id = ?');
        $stmt->execute([$userId]);
        $user = $stmt->fetch();
        
        if ($user && $user['image_quota_unlimited']) {
            // 无限制用户仍然记录使用次数，但不检查配额
            $stmt = $this->db->prepare('UPDATE users SET image_used = image_used + 1 WHERE id = ?');
            $stmt->execute([$userId]);
            return true;
        }

        if (!$this->hasImageQuota($userId)) {
            throw new \Exception('图片生成配额已用完');
        }

        $stmt = $this->db->prepare(
            'UPDATE users SET image_used = image_used + 1 WHERE id = ? AND image_used < image_quota'
        );
        $stmt->execute([$userId]);

        return $stmt->rowCount() > 0;
    }

    /**
     * Record image generation
     * 
     * @param int $userId
     * @param string $model
     * @param string $prompt
     * @param string|null $imageUrl
     * @param string|null $size
     * @param string|null $quality
     * @param string $status 'success' or 'failed'
     * @param string|null $errorMessage
     * @return int Generated record ID
     */
    public function recordImageGeneration(
        int $userId,
        string $model,
        string $prompt,
        ?string $imageUrl = null,
        ?string $size = null,
        ?string $quality = null,
        string $status = 'success',
        ?string $errorMessage = null
    ): int {
        $stmt = $this->db->prepare(
            'INSERT INTO image_generations (user_id, model, prompt, image_url, size, quality, status, error_message) 
             VALUES (?, ?, ?, ?, ?, ?, ?, ?)'
        );
        $stmt->execute([
            $userId,
            $model,
            $prompt,
            $imageUrl,
            $size,
            $quality,
            $status,
            $errorMessage
        ]);

        return (int)$this->db->lastInsertId();
    }

    /**
     * Get user's image generation history
     * 
     * @param int $userId
     * @param int $limit
     * @param int $offset
     * @return array
     */
    public function getImageHistory(int $userId, int $limit = 20, int $offset = 0): array
    {
        $stmt = $this->db->prepare(
            'SELECT id, model, prompt, image_url, size, quality, status, error_message, created_at 
             FROM image_generations 
             WHERE user_id = ? 
             ORDER BY created_at DESC 
             LIMIT ? OFFSET ?'
        );
        $stmt->execute([$userId, $limit, $offset]);

        return $stmt->fetchAll();
    }

    /**
     * Get user's successful image generation count
     * 
     * @param int $userId
     * @return int
     */
    public function getSuccessfulImageCount(int $userId): int
    {
        $stmt = $this->db->prepare(
            'SELECT COUNT(*) as count FROM image_generations WHERE user_id = ? AND status = "success"'
        );
        $stmt->execute([$userId]);
        $result = $stmt->fetch();

        return (int)$result['count'];
    }

    /**
     * Reset user's image quota (admin function)
     * 
     * @param int $userId
     * @param int $newQuota
     * @return bool
     */
    public function resetImageQuota(int $userId, int $newQuota = self::DEFAULT_IMAGE_QUOTA): bool
    {
        $stmt = $this->db->prepare(
            'UPDATE users SET image_quota = ?, image_used = 0 WHERE id = ?'
        );
        $stmt->execute([$newQuota, $userId]);

        return $stmt->rowCount() > 0;
    }

    /**
     * Add bonus quota to user (admin function)
     * 
     * @param int $userId
     * @param int $bonusQuota
     * @return bool
     */
    public function addBonusQuota(int $userId, int $bonusQuota): bool
    {
        $stmt = $this->db->prepare(
            'UPDATE users SET image_quota = image_quota + ? WHERE id = ?'
        );
        $stmt->execute([$bonusQuota, $userId]);

        return $stmt->rowCount() > 0;
    }
}
