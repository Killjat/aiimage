<?php

namespace App\Services;

use App\Database\Database;
use PDO;

class ImageGalleryService
{
    private PDO $pdo;

    public function __construct()
    {
        $this->pdo = Database::getConnection();
    }

    /**
     * 保存图片到图片库
     */
    public function saveImage(
        int $userId,
        string $username,
        string $model,
        string $prompt,
        string $imageUrl,
        ?string $llmModel = null,
        ?string $negativePrompt = null,
        ?string $imageSize = null,
        ?string $imageQuality = null,
        bool $isPublic = true,
        ?string $description = null,
        ?string $tags = null
    ): int {
        try {
            $sql = "INSERT INTO image_gallery (
                user_id, username, model, llm_model, prompt, negative_prompt,
                image_url, image_size, image_quality, is_public, description, tags
            ) VALUES (
                :user_id, :username, :model, :llm_model, :prompt, :negative_prompt,
                :image_url, :image_size, :image_quality, :is_public, :description, :tags
            )";

            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([
                ':user_id' => $userId,
                ':username' => $username,
                ':model' => $model,
                ':llm_model' => $llmModel,
                ':prompt' => $prompt,
                ':negative_prompt' => $negativePrompt,
                ':image_url' => $imageUrl,
                ':image_size' => $imageSize,
                ':image_quality' => $imageQuality,
                ':is_public' => $isPublic ? 1 : 0,
                ':description' => $description,
                ':tags' => $tags
            ]);

            return (int) $this->pdo->lastInsertId();
        } catch (\Exception $e) {
            error_log('Failed to save image to gallery: ' . $e->getMessage());
            throw $e;
        }
    }

    /**
     * 获取公开的图片库
     */
    public function getPublicGallery(int $page = 1, int $limit = 20): array
    {
        try {
            $offset = ($page - 1) * $limit;

            // 获取总数
            $countStmt = $this->pdo->query("SELECT COUNT(*) as total FROM image_gallery WHERE is_public = 1");
            $total = $countStmt->fetch()['total'];

            // 获取数据
            $sql = "SELECT 
                id, user_id, username, model, llm_model, prompt, negative_prompt,
                image_url, image_size, image_quality, views, likes, description, tags,
                created_at, updated_at
            FROM image_gallery
            WHERE is_public = 1
            ORDER BY created_at DESC
            LIMIT :offset, :limit";

            $stmt = $this->pdo->prepare($sql);
            $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
            $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
            $stmt->execute();

            return [
                'total' => $total,
                'page' => $page,
                'limit' => $limit,
                'pages' => ceil($total / $limit),
                'data' => $stmt->fetchAll()
            ];
        } catch (\Exception $e) {
            error_log('Failed to get public gallery: ' . $e->getMessage());
            throw $e;
        }
    }

    /**
     * 获取用户的图片库
     */
    public function getUserGallery(int $userId, int $page = 1, int $limit = 20): array
    {
        try {
            $offset = ($page - 1) * $limit;

            // 获取总数
            $countStmt = $this->pdo->prepare("SELECT COUNT(*) as total FROM image_gallery WHERE user_id = :user_id");
            $countStmt->execute([':user_id' => $userId]);
            $total = $countStmt->fetch()['total'];

            // 获取数据
            $sql = "SELECT 
                id, user_id, username, model, llm_model, prompt, negative_prompt,
                image_url, image_size, image_quality, is_public, views, likes, description, tags,
                created_at, updated_at
            FROM image_gallery
            WHERE user_id = :user_id
            ORDER BY created_at DESC
            LIMIT :offset, :limit";

            $stmt = $this->pdo->prepare($sql);
            $stmt->bindValue(':user_id', $userId, PDO::PARAM_INT);
            $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
            $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
            $stmt->execute();

            return [
                'total' => $total,
                'page' => $page,
                'limit' => $limit,
                'pages' => ceil($total / $limit),
                'data' => $stmt->fetchAll()
            ];
        } catch (\Exception $e) {
            error_log('Failed to get user gallery: ' . $e->getMessage());
            throw $e;
        }
    }

    /**
     * 获取单个图片详情
     */
    public function getImage(int $imageId): ?array
    {
        try {
            $sql = "SELECT 
                id, user_id, username, model, llm_model, prompt, negative_prompt,
                image_url, image_size, image_quality, is_public, views, likes, description, tags,
                created_at, updated_at
            FROM image_gallery
            WHERE id = :id";

            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([':id' => $imageId]);

            return $stmt->fetch() ?: null;
        } catch (\Exception $e) {
            error_log('Failed to get image: ' . $e->getMessage());
            throw $e;
        }
    }

    /**
     * 增加浏览次数
     */
    public function incrementViews(int $imageId): bool
    {
        try {
            $sql = "UPDATE image_gallery SET views = views + 1 WHERE id = :id";
            $stmt = $this->pdo->prepare($sql);
            return $stmt->execute([':id' => $imageId]);
        } catch (\Exception $e) {
            error_log('Failed to increment views: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * 增加点赞数
     */
    public function incrementLikes(int $imageId): bool
    {
        try {
            $sql = "UPDATE image_gallery SET likes = likes + 1 WHERE id = :id";
            $stmt = $this->pdo->prepare($sql);
            return $stmt->execute([':id' => $imageId]);
        } catch (\Exception $e) {
            error_log('Failed to increment likes: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * 搜索图片
     */
    public function searchImages(string $keyword, int $page = 1, int $limit = 20): array
    {
        try {
            $offset = ($page - 1) * $limit;
            $searchKeyword = '%' . $keyword . '%';

            // 使用 LIKE 搜索（更兼容）
            $countSql = "SELECT COUNT(*) as total FROM image_gallery 
                        WHERE is_public = 1 AND (prompt LIKE ? OR tags LIKE ?)";
            $countStmt = $this->pdo->prepare($countSql);
            $countStmt->execute([$searchKeyword, $searchKeyword]);
            $total = $countStmt->fetch()['total'];

            $sql = "SELECT 
                id, user_id, username, model, llm_model, prompt, negative_prompt,
                image_url, image_size, image_quality, views, likes, description, tags,
                created_at, updated_at
            FROM image_gallery
            WHERE is_public = 1 AND (prompt LIKE ? OR tags LIKE ?)
            ORDER BY created_at DESC
            LIMIT ?, ?";

            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([$searchKeyword, $searchKeyword, $offset, $limit]);

            return [
                'total' => $total,
                'page' => $page,
                'limit' => $limit,
                'pages' => ceil($total / $limit),
                'keyword' => $keyword,
                'data' => $stmt->fetchAll()
            ];
        } catch (\Exception $e) {
            error_log('Failed to search images: ' . $e->getMessage());
            throw $e;
        }
    }

    /**
     * 按模型统计
     */
    public function getModelStats(): array
    {
        try {
            $sql = "SELECT model, COUNT(*) as count, COUNT(DISTINCT user_id) as users
                   FROM image_gallery
                   WHERE is_public = 1
                   GROUP BY model
                   ORDER BY count DESC";

            $stmt = $this->pdo->query($sql);
            return $stmt->fetchAll();
        } catch (\Exception $e) {
            error_log('Failed to get model stats: ' . $e->getMessage());
            throw $e;
        }
    }

    /**
     * 按大模型统计
     */
    public function getLLMStats(): array
    {
        try {
            $sql = "SELECT llm_model, COUNT(*) as count, COUNT(DISTINCT user_id) as users
                   FROM image_gallery
                   WHERE is_public = 1 AND llm_model IS NOT NULL
                   GROUP BY llm_model
                   ORDER BY count DESC";

            $stmt = $this->pdo->query($sql);
            return $stmt->fetchAll();
        } catch (\Exception $e) {
            error_log('Failed to get LLM stats: ' . $e->getMessage());
            throw $e;
        }
    }

    /**
     * 获取搜索建议
     */
    public function getSearchSuggestions(string $keyword = '', int $limit = 10): array
    {
        try {
            $suggestions = [];
            
            // 1. 从标签中获取建议
            $sql = "SELECT DISTINCT tags FROM image_gallery 
                   WHERE is_public = 1 AND tags IS NOT NULL AND tags != ''
                   LIMIT 100";
            $stmt = $this->pdo->query($sql);
            $results = $stmt->fetchAll();
            
            $tagSuggestions = [];
            foreach ($results as $row) {
                if ($row['tags']) {
                    $tags = explode(',', $row['tags']);
                    foreach ($tags as $tag) {
                        $tag = trim($tag);
                        if ($tag && strlen($tag) > 0) {
                            if (!isset($tagSuggestions[$tag])) {
                                $tagSuggestions[$tag] = 0;
                            }
                            $tagSuggestions[$tag]++;
                        }
                    }
                }
            }
            
            // 按频率排序
            arsort($tagSuggestions);
            
            // 2. 从提示词中提取关键词
            $sql = "SELECT DISTINCT 
                   SUBSTRING_INDEX(SUBSTRING_INDEX(prompt, ' ', 1), ' ', -1) as word
                   FROM image_gallery 
                   WHERE is_public = 1
                   LIMIT 200";
            $stmt = $this->pdo->query($sql);
            $results = $stmt->fetchAll();
            
            $wordSuggestions = [];
            $keywords = ['a', 'an', 'the', 'and', 'or', 'in', 'on', 'at', 'to', 'for', 'of', 'with', 'by', 'from'];
            
            foreach ($results as $row) {
                $word = strtolower(trim($row['word']));
                if ($word && strlen($word) > 2 && !in_array($word, $keywords)) {
                    if (!isset($wordSuggestions[$word])) {
                        $wordSuggestions[$word] = 0;
                    }
                    $wordSuggestions[$word]++;
                }
            }
            
            arsort($wordSuggestions);
            
            // 3. 合并建议
            $allSuggestions = [];
            
            // 添加标签建议
            foreach ($tagSuggestions as $tag => $count) {
                $allSuggestions[] = [
                    'text' => $tag,
                    'type' => 'tag',
                    'count' => $count
                ];
            }
            
            // 添加关键词建议
            foreach ($wordSuggestions as $word => $count) {
                $allSuggestions[] = [
                    'text' => $word,
                    'type' => 'keyword',
                    'count' => $count
                ];
            }
            
            // 4. 如果提供了关键词，进行过滤
            if (!empty($keyword)) {
                $keyword = strtolower($keyword);
                $allSuggestions = array_filter($allSuggestions, function($item) use ($keyword) {
                    return strpos(strtolower($item['text']), $keyword) === 0;
                });
            }
            
            // 按频率排序并限制数量
            usort($allSuggestions, function($a, $b) {
                return $b['count'] - $a['count'];
            });
            
            return array_slice($allSuggestions, 0, $limit);
        } catch (\Exception $e) {
            error_log('Failed to get search suggestions: ' . $e->getMessage());
            return [];
        }
    }

    /**
     * 删除图片
     */
    public function deleteImage(int $imageId, int $userId): bool
    {
        try {
            $sql = "DELETE FROM image_gallery WHERE id = :id AND user_id = :user_id";
            $stmt = $this->pdo->prepare($sql);
            return $stmt->execute([':id' => $imageId, ':user_id' => $userId]);
        } catch (\Exception $e) {
            error_log('Failed to delete image: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * 更新图片信息
     */
    public function updateImage(
        int $imageId,
        int $userId,
        ?string $description = null,
        ?string $tags = null,
        ?bool $isPublic = null
    ): bool {
        try {
            $updates = [];
            $params = [':id' => $imageId, ':user_id' => $userId];

            if ($description !== null) {
                $updates[] = "description = :description";
                $params[':description'] = $description;
            }

            if ($tags !== null) {
                $updates[] = "tags = :tags";
                $params[':tags'] = $tags;
            }

            if ($isPublic !== null) {
                $updates[] = "is_public = :is_public";
                $params[':is_public'] = $isPublic ? 1 : 0;
            }

            if (empty($updates)) {
                return true;
            }

            $sql = "UPDATE image_gallery SET " . implode(', ', $updates) . " WHERE id = :id AND user_id = :user_id";
            $stmt = $this->pdo->prepare($sql);
            return $stmt->execute($params);
        } catch (\Exception $e) {
            error_log('Failed to update image: ' . $e->getMessage());
            return false;
        }
    }
}
