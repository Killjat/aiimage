<?php

namespace App\Controllers;

use App\Services\ImageGalleryService;
use Psr\Http\Message\RequestInterface as Request;
use Psr\Http\Message\ResponseInterface as Response;

class GalleryController
{
    private ImageGalleryService $galleryService;

    public function __construct()
    {
        $this->galleryService = new ImageGalleryService();
    }

    /**
     * 获取公开图片库
     * GET /api/gallery/public
     */
    public function getPublicGallery(Request $request, Response $response): Response
    {
        try {
            $params = $request->getQueryParams();
            $page = (int) ($params['page'] ?? 1);
            $limit = (int) ($params['limit'] ?? 20);

            // 限制 limit 最大值
            $limit = min($limit, 100);

            $result = $this->galleryService->getPublicGallery($page, $limit);

            $response->getBody()->write(json_encode([
                'success' => true,
                'data' => $result
            ]));

            return $response->withHeader('Content-Type', 'application/json');
        } catch (\Exception $e) {
            error_log('获取公开图片库错误: ' . $e->getMessage());
            $response->getBody()->write(json_encode([
                'success' => false,
                'error' => '获取图片库失败'
            ]));
            return $response->withStatus(500)->withHeader('Content-Type', 'application/json');
        }
    }

    /**
     * 获取用户的图片库
     * GET /api/gallery/user/:userId
     */
    public function getUserGallery(Request $request, Response $response, array $args): Response
    {
        try {
            $userId = (int) $args['userId'];
            $params = $request->getQueryParams();
            $page = (int) ($params['page'] ?? 1);
            $limit = (int) ($params['limit'] ?? 20);

            $limit = min($limit, 100);

            $result = $this->galleryService->getUserGallery($userId, $page, $limit);

            $response->getBody()->write(json_encode([
                'success' => true,
                'data' => $result
            ]));

            return $response->withHeader('Content-Type', 'application/json');
        } catch (\Exception $e) {
            error_log('获取用户图片库错误: ' . $e->getMessage());
            $response->getBody()->write(json_encode([
                'success' => false,
                'error' => '获取图片库失败'
            ]));
            return $response->withStatus(500)->withHeader('Content-Type', 'application/json');
        }
    }

    /**
     * 获取单个图片详情
     * GET /api/gallery/image/:imageId
     */
    public function getImage(Request $request, Response $response, array $args): Response
    {
        try {
            $imageId = (int) $args['imageId'];

            $image = $this->galleryService->getImage($imageId);

            if (!$image) {
                $response->getBody()->write(json_encode([
                    'success' => false,
                    'error' => '图片不存在'
                ]));
                return $response->withStatus(404)->withHeader('Content-Type', 'application/json');
            }

            // 增加浏览次数
            $this->galleryService->incrementViews($imageId);

            $response->getBody()->write(json_encode([
                'success' => true,
                'data' => $image
            ]));

            return $response->withHeader('Content-Type', 'application/json');
        } catch (\Exception $e) {
            error_log('获取图片详情错误: ' . $e->getMessage());
            $response->getBody()->write(json_encode([
                'success' => false,
                'error' => '获取图片失败'
            ]));
            return $response->withStatus(500)->withHeader('Content-Type', 'application/json');
        }
    }

    /**
     * 搜索图片
     * GET /api/gallery/search
     */
    public function searchImages(Request $request, Response $response): Response
    {
        try {
            $params = $request->getQueryParams();
            $keyword = $params['keyword'] ?? '';
            $page = (int) ($params['page'] ?? 1);
            $limit = (int) ($params['limit'] ?? 20);

            if (empty($keyword)) {
                $response->getBody()->write(json_encode([
                    'success' => false,
                    'error' => '请输入搜索关键词'
                ]));
                return $response->withStatus(400)->withHeader('Content-Type', 'application/json');
            }

            $limit = min($limit, 100);

            $result = $this->galleryService->searchImages($keyword, $page, $limit);

            $response->getBody()->write(json_encode([
                'success' => true,
                'data' => $result
            ]));

            return $response->withHeader('Content-Type', 'application/json');
        } catch (\Exception $e) {
            error_log('搜索图片错误: ' . $e->getMessage());
            $response->getBody()->write(json_encode([
                'success' => false,
                'error' => '搜索失败'
            ]));
            return $response->withStatus(500)->withHeader('Content-Type', 'application/json');
        }
    }

    /**
     * 点赞图片
     * POST /api/gallery/image/:imageId/like
     */
    public function likeImage(Request $request, Response $response, array $args): Response
    {
        try {
            $imageId = (int) $args['imageId'];

            $image = $this->galleryService->getImage($imageId);

            if (!$image) {
                $response->getBody()->write(json_encode([
                    'success' => false,
                    'error' => '图片不存在'
                ]));
                return $response->withStatus(404)->withHeader('Content-Type', 'application/json');
            }

            $this->galleryService->incrementLikes($imageId);

            $response->getBody()->write(json_encode([
                'success' => true,
                'message' => '点赞成功'
            ]));

            return $response->withHeader('Content-Type', 'application/json');
        } catch (\Exception $e) {
            error_log('点赞图片错误: ' . $e->getMessage());
            $response->getBody()->write(json_encode([
                'success' => false,
                'error' => '点赞失败'
            ]));
            return $response->withStatus(500)->withHeader('Content-Type', 'application/json');
        }
    }

    /**
     * 获取模型统计
     * GET /api/gallery/stats/models
     */
    public function getModelStats(Request $request, Response $response): Response
    {
        try {
            $stats = $this->galleryService->getModelStats();

            $response->getBody()->write(json_encode([
                'success' => true,
                'data' => $stats
            ]));

            return $response->withHeader('Content-Type', 'application/json');
        } catch (\Exception $e) {
            error_log('获取模型统计错误: ' . $e->getMessage());
            $response->getBody()->write(json_encode([
                'success' => false,
                'error' => '获取统计失败'
            ]));
            return $response->withStatus(500)->withHeader('Content-Type', 'application/json');
        }
    }

    /**
     * 获取大模型统计
     * GET /api/gallery/stats/llm
     */
    public function getLLMStats(Request $request, Response $response): Response
    {
        try {
            $stats = $this->galleryService->getLLMStats();

            $response->getBody()->write(json_encode([
                'success' => true,
                'data' => $stats
            ]));

            return $response->withHeader('Content-Type', 'application/json');
        } catch (\Exception $e) {
            error_log('获取大模型统计错误: ' . $e->getMessage());
            $response->getBody()->write(json_encode([
                'success' => false,
                'error' => '获取统计失败'
            ]));
            return $response->withStatus(500)->withHeader('Content-Type', 'application/json');
        }
    }

    /**
     * 获取搜索建议
     * GET /api/gallery/suggestions
     */
    public function getSearchSuggestions(Request $request, Response $response): Response
    {
        try {
            $params = $request->getQueryParams();
            $keyword = $params['keyword'] ?? '';
            $limit = (int) ($params['limit'] ?? 10);

            $limit = min($limit, 50);

            $suggestions = $this->galleryService->getSearchSuggestions($keyword, $limit);

            $response->getBody()->write(json_encode([
                'success' => true,
                'data' => $suggestions
            ]));

            return $response->withHeader('Content-Type', 'application/json');
        } catch (\Exception $e) {
            error_log('获取搜索建议错误: ' . $e->getMessage());
            $response->getBody()->write(json_encode([
                'success' => false,
                'error' => '获取建议失败'
            ]));
            return $response->withStatus(500)->withHeader('Content-Type', 'application/json');
        }
    }
}
