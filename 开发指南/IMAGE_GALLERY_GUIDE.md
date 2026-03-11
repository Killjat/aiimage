# 图片库功能指南

## 数据库表结构

### image_gallery 表

存储所有已生成的图片，供用户浏览、搜索和分享。

#### 字段说明

| 字段 | 类型 | 说明 | 备注 |
|------|------|------|------|
| id | INT UNSIGNED | 主键 | 自增 |
| user_id | INT UNSIGNED | 创建者ID | 外键关联 users 表 |
| username | VARCHAR(100) | 创建者用户名 | 冗余存储，便于显示 |
| model | VARCHAR(100) | 使用的图片生成模型 | 如：alibaba-wan2.6-t2i, black-forest-labs/flux.2-pro |
| llm_model | VARCHAR(100) | 使用的大模型 | 如果有的话，如：gpt-4, claude-3 等 |
| prompt | TEXT | 提示词 | 用户输入的图片描述 |
| negative_prompt | TEXT | 反向提示词 | 不希望出现的内容 |
| image_url | LONGTEXT | 图片数据 | Base64 编码或 HTTP URL |
| image_size | VARCHAR(50) | 图片尺寸 | 如：1024*1024, 16:9 等 |
| image_quality | VARCHAR(100) | 图片质量 | 如：high, medium, low |
| is_public | BOOLEAN | 是否公开 | 默认 TRUE |
| views | INT UNSIGNED | 浏览次数 | 默认 0 |
| likes | INT UNSIGNED | 点赞数 | 默认 0 |
| description | TEXT | 图片描述 | 用户添加的额外说明 |
| tags | VARCHAR(255) | 标签 | 逗号分隔的标签 |
| created_at | TIMESTAMP | 创建时间 | 自动设置 |
| updated_at | TIMESTAMP | 更新时间 | 自动更新 |

#### 索引

- PRIMARY: id
- idx_user_id: user_id (查询用户的图片)
- idx_created_at: created_at (按时间排序)
- idx_is_public: is_public (查询公开图片)
- idx_views: views (热门排序)
- idx_likes: likes (点赞排序)
- idx_model: model (按模型筛选)
- idx_llm_model: llm_model (按大模型筛选)
- ft_prompt: prompt (全文搜索提示词)
- ft_tags: tags (全文搜索标签)

---

## 后端 API

### 1. 获取公开图片库

**请求**
```
GET /api/gallery/public?page=1&limit=20
```

**参数**
- page: 页码 (默认 1)
- limit: 每页数量 (默认 20, 最大 100)

**响应**
```json
{
  "success": true,
  "data": {
    "total": 100,
    "page": 1,
    "limit": 20,
    "pages": 5,
    "data": [
      {
        "id": 1,
        "user_id": 1,
        "username": "user123",
        "model": "alibaba-wan2.6-t2i",
        "llm_model": null,
        "prompt": "A beautiful sunset",
        "image_url": "data:image/png;base64,...",
        "views": 100,
        "likes": 25,
        "created_at": "2026-03-11 10:00:00"
      }
    ]
  }
}
```

### 2. 获取用户的图片库

**请求**
```
GET /api/gallery/user/:userId?page=1&limit=20
```

**参数**
- userId: 用户ID (路径参数)
- page: 页码 (默认 1)
- limit: 每页数量 (默认 20, 最大 100)

**响应**
```json
{
  "success": true,
  "data": {
    "total": 50,
    "page": 1,
    "limit": 20,
    "pages": 3,
    "data": [...]
  }
}
```

### 3. 获取单个图片详情

**请求**
```
GET /api/gallery/image/:imageId
```

**响应**
```json
{
  "success": true,
  "data": {
    "id": 1,
    "user_id": 1,
    "username": "user123",
    "model": "alibaba-wan2.6-t2i",
    "llm_model": null,
    "prompt": "A beautiful sunset",
    "negative_prompt": "blurry, low quality",
    "image_url": "data:image/png;base64,...",
    "image_size": "1024*1024",
    "image_quality": "high",
    "is_public": true,
    "views": 101,
    "likes": 25,
    "description": "Beautiful sunset over mountains",
    "tags": "sunset,nature,landscape",
    "created_at": "2026-03-11 10:00:00"
  }
}
```

### 4. 搜索图片

**请求**
```
GET /api/gallery/search?keyword=sunset&page=1&limit=20
```

**参数**
- keyword: 搜索关键词 (必需)
- page: 页码 (默认 1)
- limit: 每页数量 (默认 20, 最大 100)

**响应**
```json
{
  "success": true,
  "data": {
    "total": 10,
    "page": 1,
    "limit": 20,
    "pages": 1,
    "keyword": "sunset",
    "data": [...]
  }
}
```

### 5. 点赞图片

**请求**
```
POST /api/gallery/image/:imageId/like
```

**响应**
```json
{
  "success": true,
  "message": "点赞成功"
}
```

### 6. 获取模型统计

**请求**
```
GET /api/gallery/stats/models
```

**响应**
```json
{
  "success": true,
  "data": [
    {
      "model": "alibaba-wan2.6-t2i",
      "count": 50,
      "users": 10
    },
    {
      "model": "black-forest-labs/flux.2-pro",
      "count": 30,
      "users": 8
    }
  ]
}
```

### 7. 获取大模型统计

**请求**
```
GET /api/gallery/stats/llm
```

**响应**
```json
{
  "success": true,
  "data": [
    {
      "llm_model": "gpt-4",
      "count": 20,
      "users": 5
    },
    {
      "llm_model": "claude-3",
      "count": 15,
      "users": 3
    }
  ]
}
```

---

## 后端集成

### 保存图片到图片库

在 ImageController 中，生成图片成功后调用：

```php
use App\Services\ImageGalleryService;

$galleryService = new ImageGalleryService();

// 保存到图片库
$galleryService->saveImage(
    userId: $userId,
    username: $username,
    model: $model,
    prompt: $prompt,
    imageUrl: $imageUrl,
    llmModel: $llmModel,  // 可选
    negativePrompt: $negativePrompt,  // 可选
    imageSize: $imageSize,  // 可选
    imageQuality: $imageQuality,  // 可选
    isPublic: true,  // 默认公开
    description: $description,  // 可选
    tags: $tags  // 可选
);
```

### 使用示例

```php
// 在 ImageController.generate() 中
if ($result['success']) {
    // ... 其他处理 ...
    
    // 保存到图片库
    try {
        $galleryService->saveImage(
            userId: $userId,
            username: $user['username'],
            model: $model,
            prompt: $prompt,
            imageUrl: $result['image_url'],
            llmModel: null,  // 如果有大模型，传入
            imageSize: $imageSize,
            imageQuality: $imageQuality,
            isPublic: true
        );
    } catch (\Exception $e) {
        error_log('Failed to save to gallery: ' . $e->getMessage());
        // 不影响主流程
    }
}
```

---

## 前端集成

### 显示图片库

```typescript
// 获取公开图片库
const response = await fetch(`${API_BASE_URL}/gallery/public?page=1&limit=20`);
const data = await response.json();

if (data.success) {
  const images = data.data.data;
  // 显示图片列表
}
```

### 搜索图片

```typescript
// 搜索图片
const keyword = 'sunset';
const response = await fetch(`${API_BASE_URL}/gallery/search?keyword=${keyword}`);
const data = await response.json();

if (data.success) {
  const results = data.data.data;
  // 显示搜索结果
}
```

### 点赞图片

```typescript
// 点赞图片
const imageId = 1;
const response = await fetch(`${API_BASE_URL}/gallery/image/${imageId}/like`, {
  method: 'POST'
});
const data = await response.json();

if (data.success) {
  // 更新点赞数
}
```

---

## 数据统计

### 模型使用统计

查看哪些模型最受欢迎：

```
GET /api/gallery/stats/models
```

### 大模型使用统计

查看哪些大模型被使用：

```
GET /api/gallery/stats/llm
```

---

## 性能优化

### 索引优化

- 使用 idx_created_at 加速时间排序
- 使用 idx_views 和 idx_likes 加速热门排序
- 使用 ft_prompt 和 ft_tags 加速全文搜索

### 查询优化

- 分页查询，每页最多 100 条
- 使用全文索引进行搜索
- 缓存热门图片

### 存储优化

- image_url 使用 LONGTEXT 存储 Base64 数据
- 考虑定期清理过期数据
- 考虑将大型 Base64 数据迁移到对象存储

---

## 安全考虑

1. **权限控制**
   - 用户只能删除/编辑自己的图片
   - 公开图片任何人可以查看

2. **内容审核**
   - 考虑添加内容审核机制
   - 标记不当内容

3. **数据隐私**
   - 不公开用户的敏感信息
   - 支持用户删除自己的图片

---

## 迁移脚本

运行迁移脚本创建表：

```bash
php backend/migrate_add_image_gallery.php
```

---

## 总结

✅ 完整的图片库功能
- 存储已生成的图片
- 记录创建者、模型、大模型、提示词等信息
- 支持公开/私密设置
- 支持搜索、点赞、浏览统计
- 支持模型和大模型统计

✅ 灵活的 API
- 获取公开图片库
- 获取用户的图片库
- 搜索图片
- 点赞图片
- 统计数据

✅ 性能优化
- 多个索引加速查询
- 全文搜索支持
- 分页查询
