<?php

namespace App\Services;

class CacheService
{
    private string $cacheDir;
    private int $defaultTTL = 300; // 5分钟
    
    public function __construct()
    {
        $this->cacheDir = __DIR__ . '/../../cache';
        if (!is_dir($this->cacheDir)) {
            mkdir($this->cacheDir, 0755, true);
        }
    }
    
    /**
     * 生成缓存键
     */
    private function getCacheKey(string $prefix, array $params): string
    {
        $key = $prefix . '_' . md5(json_encode($params));
        return $key;
    }
    
    /**
     * 获取缓存文件路径
     */
    private function getCacheFilePath(string $key): string
    {
        return $this->cacheDir . '/' . $key . '.json';
    }
    
    /**
     * 获取缓存
     */
    public function get(string $prefix, array $params): ?array
    {
        $key = $this->getCacheKey($prefix, $params);
        $filePath = $this->getCacheFilePath($key);
        
        if (!file_exists($filePath)) {
            return null;
        }
        
        $data = json_decode(file_get_contents($filePath), true);
        
        if (!$data || !isset($data['expires_at']) || !isset($data['value'])) {
            return null;
        }
        
        // 检查是否过期
        if (time() > $data['expires_at']) {
            unlink($filePath);
            return null;
        }
        
        return $data['value'];
    }
    
    /**
     * 设置缓存
     */
    public function set(string $prefix, array $params, array $value, ?int $ttl = null): bool
    {
        $key = $this->getCacheKey($prefix, $params);
        $filePath = $this->getCacheFilePath($key);
        
        $data = [
            'expires_at' => time() + ($ttl ?? $this->defaultTTL),
            'value' => $value,
            'created_at' => time()
        ];
        
        return file_put_contents($filePath, json_encode($data)) !== false;
    }
    
    /**
     * 删除缓存
     */
    public function delete(string $prefix, array $params): bool
    {
        $key = $this->getCacheKey($prefix, $params);
        $filePath = $this->getCacheFilePath($key);
        
        if (file_exists($filePath)) {
            return unlink($filePath);
        }
        
        return true;
    }
    
    /**
     * 清理过期缓存
     */
    public function cleanup(): int
    {
        $count = 0;
        $files = glob($this->cacheDir . '/*.json');
        
        foreach ($files as $file) {
            $data = json_decode(file_get_contents($file), true);
            if ($data && isset($data['expires_at']) && time() > $data['expires_at']) {
                unlink($file);
                $count++;
            }
        }
        
        return $count;
    }
}
