-- AI Chat System Database Schema

-- 创建数据库
CREATE DATABASE IF NOT EXISTS ai_chat_system CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

USE ai_chat_system;

-- 用户表
CREATE TABLE IF NOT EXISTS users (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    email VARCHAR(255) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    username VARCHAR(100),
    image_quota INT UNSIGNED DEFAULT 10 COMMENT '图片生成配额',
    image_used INT UNSIGNED DEFAULT 0 COMMENT '已使用的图片生成次数',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    last_login_at TIMESTAMP NULL,
    status ENUM('active', 'inactive', 'banned') DEFAULT 'active',
    INDEX idx_email (email),
    INDEX idx_status (status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 会话表（可选，用于存储聊天历史）
CREATE TABLE IF NOT EXISTS chat_sessions (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id INT UNSIGNED NOT NULL,
    title VARCHAR(255),
    model VARCHAR(100),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_user_id (user_id),
    INDEX idx_created_at (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 消息表（可选，用于存储聊天消息）
CREATE TABLE IF NOT EXISTS chat_messages (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    session_id INT UNSIGNED NOT NULL,
    role ENUM('user', 'assistant', 'system') NOT NULL,
    content TEXT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (session_id) REFERENCES chat_sessions(id) ON DELETE CASCADE,
    INDEX idx_session_id (session_id),
    INDEX idx_created_at (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 图片生成记录表
CREATE TABLE IF NOT EXISTS image_generations (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id INT UNSIGNED NOT NULL,
    model VARCHAR(100) NOT NULL,
    prompt TEXT NOT NULL,
    image_url LONGTEXT COMMENT 'Base64编码的图片数据或URL',
    size VARCHAR(50),
    quality VARCHAR(100),
    status ENUM('success', 'failed', 'processing', 'completed') DEFAULT 'success',
    error_message TEXT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_user_id (user_id),
    INDEX idx_created_at (created_at),
    INDEX idx_status (status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 图片库表（存储已生成的图片，供所有用户浏览）
CREATE TABLE IF NOT EXISTS image_gallery (
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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
