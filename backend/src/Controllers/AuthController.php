<?php

namespace App\Controllers;

use App\Services\AuthService;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

class AuthController
{
    private AuthService $authService;

    public function __construct()
    {
        $this->authService = new AuthService();
    }

    /**
     * Register a new user
     * 
     * POST /api/auth/register
     * Body: { "email": "user@example.com", "password": "password", "username": "optional" }
     */
    public function register(Request $request, Response $response): Response
    {
        try {
            $data = json_decode($request->getBody()->getContents(), true);

            if (!isset($data['email']) || !isset($data['password'])) {
                $response->getBody()->write(json_encode([
                    'error' => '邮箱和密码不能为空',
                ]));
                return $response->withStatus(400)->withHeader('Content-Type', 'application/json');
            }

            $result = $this->authService->register(
                $data['email'],
                $data['password'],
                $data['username'] ?? null
            );

            $response->getBody()->write(json_encode($result));
            return $response->withStatus(201)->withHeader('Content-Type', 'application/json');

        } catch (\Exception $e) {
            $response->getBody()->write(json_encode([
                'error' => $e->getMessage(),
            ]));
            return $response->withStatus(400)->withHeader('Content-Type', 'application/json');
        }
    }

    /**
     * Login user
     * 
     * POST /api/auth/login
     * Body: { "email": "user@example.com", "password": "password" }
     */
    public function login(Request $request, Response $response): Response
    {
        try {
            $data = json_decode($request->getBody()->getContents(), true);

            if (!isset($data['email']) || !isset($data['password'])) {
                $response->getBody()->write(json_encode([
                    'error' => '邮箱和密码不能为空',
                ]));
                return $response->withStatus(400)->withHeader('Content-Type', 'application/json');
            }

            $result = $this->authService->login(
                $data['email'],
                $data['password']
            );

            $response->getBody()->write(json_encode($result));
            return $response->withStatus(200)->withHeader('Content-Type', 'application/json');

        } catch (\Exception $e) {
            $response->getBody()->write(json_encode([
                'error' => $e->getMessage(),
            ]));
            return $response->withStatus(401)->withHeader('Content-Type', 'application/json');
        }
    }

    /**
     * Get current user info
     * 
     * GET /api/auth/me
     * Header: Authorization: Bearer <token>
     */
    public function me(Request $request, Response $response): Response
    {
        try {
            // 从请求头获取 token
            $authHeader = $request->getHeaderLine('Authorization');
            if (!$authHeader || !preg_match('/Bearer\s+(.*)$/i', $authHeader, $matches)) {
                throw new \Exception('未提供认证 Token');
            }

            $token = $matches[1];
            $payload = $this->authService->verifyToken($token);

            $user = $this->authService->getUserById($payload['user_id']);
            if (!$user) {
                throw new \Exception('用户不存在');
            }

            $response->getBody()->write(json_encode([
                'user' => $user,
            ]));
            return $response->withStatus(200)->withHeader('Content-Type', 'application/json');

        } catch (\Exception $e) {
            $response->getBody()->write(json_encode([
                'error' => $e->getMessage(),
            ]));
            return $response->withStatus(401)->withHeader('Content-Type', 'application/json');
        }
    }

    /**
     * Logout user (client-side token removal)
     * 
     * POST /api/auth/logout
     */
    public function logout(Request $request, Response $response): Response
    {
        $response->getBody()->write(json_encode([
            'message' => '登出成功',
        ]));
        return $response->withStatus(200)->withHeader('Content-Type', 'application/json');
    }
}
