# 图片库前端集成指南

## 功能概览

### 两种视图模式

1. **全部图片** (默认)
   - 显示所有用户生成的公开图片
   - 任何人都可以浏览和下载
   - 支持搜索和分页

2. **我的图片** (仅登录用户)
   - 显示当前用户生成的所有图片
   - 用户可以查看自己的生成历史
   - 支持搜索和分页

### 核心功能

- ✅ 浏览图片库
- ✅ 搜索图片（按提示词和标签）
- ✅ 查看图片详情
- ✅ 下载图片（支持 Base64 和 HTTP URL）
- ✅ 点赞图片
- ✅ 分页浏览
- ✅ 响应式设计

---

## 组件使用

### 导入组件

```typescript
import ImageGallery from './components/ImageGallery';
```

### 基本使用

```typescript
<ImageGallery 
  isAuthenticated={isAuthenticated}
  userId={userId}
/>
```

### Props

| Prop | 类型 | 说明 |
|------|------|------|
| isAuthenticated | boolean | 用户是否已登录 |
| userId | number | 用户ID（登录时提供） |

---

## 集成到主应用

### 在 App.tsx 中添加路由

```typescript
import { BrowserRouter as Router, Routes, Route } from 'react-router-dom';
import ImageGallery from './components/ImageGallery';

function App() {
  const [isAuthenticated, setIsAuthenticated] = useState(false);
  const [userId, setUserId] = useState<number | undefined>();

  return (
    <Router>
      <Routes>
        {/* 其他路由 */}
        <Route 
          path="/gallery" 
          element={
            <ImageGallery 
              isAuthenticated={isAuthenticated}
              userId={userId}
            />
          } 
        />
      </Routes>
    </Router>
  );
}
```

### 在导航菜单中添加链接

```typescript
<nav>
  <a href="/">首页</a>
  <a href="/chat">聊天</a>
  <a href="/gallery">🎨 图片库</a>
  {isAuthenticated && <a href="/profile">个人中心</a>}
</nav>
```

---

## 功能详解

### 1. 浏览全部图片

```
GET /api/gallery/public?page=1&limit=20
```

- 显示所有公开的图片
- 按创建时间倒序排列
- 支持分页

### 2. 浏览我的图片

```
GET /api/gallery/user/:userId?page=1&limit=20
```

- 仅显示当前用户的图片
- 需要用户登录
- 支持分页

### 3. 搜索图片

```
GET /api/gallery/search?keyword=sunset&page=1&limit=20
```

- 在提示词和标签中搜索
- 支持模糊匹配
- 支持分页

### 4. 查看图片详情

```
GET /api/gallery/image/:imageId
```

- 显示完整的图片信息
- 自动增加浏览次数
- 显示创建者、模型、提示词等

### 5. 点赞图片

```
POST /api/gallery/image/:imageId/like
```

- 增加图片的点赞数
- 任何人都可以点赞
- 无需登录

### 6. 下载图片

支持两种格式：

**Base64 Data URL**
```typescript
// 自动转换为 Blob 后下载
const arr = imageUrl.split(',');
const mime = arr[0].match(/:(.*?);/)?.[1] || 'image/png';
const bstr = atob(arr[1]);
const u8arr = new Uint8Array(bstr.length);
for (let i = 0; i < bstr.length; i++) {
  u8arr[i] = bstr.charCodeAt(i);
}
const blob = new Blob([u8arr], { type: mime });
const url = URL.createObjectURL(blob);
// 创建下载链接
```

**HTTP URL**
```typescript
// 直接下载
const a = document.createElement('a');
a.href = imageUrl;
a.download = 'image.png';
a.click();
```

---

## 样式定制

### 修改主题色

在组件中修改颜色值：

```typescript
// 主色
background: '#3b82f6'  // 改为你的主色

// 背景色
background: '#f3f4f6'  // 改为你的背景色

// 文字色
color: '#374151'       // 改为你的文字色
```

### 修改网格列数

```typescript
gridTemplateColumns: 'repeat(auto-fill, minmax(250px, 1fr))'
// 改为
gridTemplateColumns: 'repeat(auto-fill, minmax(300px, 1fr))'  // 更大的卡片
```

### 修改分页显示数量

```typescript
// 在 loadGallery 中修改
limit: 20  // 改为其他数值
```

---

## 用户体验

### 全部图片视图

1. 用户打开图片库
2. 默认显示全部公开图片
3. 可以搜索、浏览、下载
4. 可以点赞喜欢的图片

### 我的图片视图

1. 用户登录后点击"我的图片"
2. 显示该用户生成的所有图片
3. 可以查看生成历史
4. 可以搜索自己的图片

### 图片详情

1. 点击图片卡片打开详情
2. 显示完整信息（创建者、模型、提示词等）
3. 可以下载或点赞
4. 显示浏览次数和点赞数

---

## 性能优化

### 1. 图片懒加载

```typescript
<img
  src={image.image_url}
  alt={image.prompt}
  loading="lazy"  // 添加懒加载
/>
```

### 2. 虚拟滚动

对于大量图片，考虑使用虚拟滚动库：

```typescript
import { FixedSizeList } from 'react-window';
```

### 3. 缓存

```typescript
// 缓存已加载的数据
const [cache, setCache] = useState<Record<number, GalleryImage[]>>({});
```

---

## 错误处理

### 网络错误

```typescript
catch (err) {
  console.error('Failed to load gallery:', err);
  setError('加载失败，请重试');
}
```

### 下载失败

```typescript
try {
  downloadImage(imageUrl, filename);
} catch (err) {
  console.error('Download failed:', err);
  alert('下载失败，请重试');
}
```

---

## 移动端适配

组件已支持响应式设计：

```typescript
// 网格自动调整
gridTemplateColumns: 'repeat(auto-fill, minmax(250px, 1fr))'

// 在小屏幕上自动变为单列
@media (max-width: 768px) {
  gridTemplateColumns: '1fr'
}
```

---

## 集成检查清单

- [ ] 导入 ImageGallery 组件
- [ ] 添加路由
- [ ] 在导航菜单中添加链接
- [ ] 传递 isAuthenticated 和 userId props
- [ ] 测试全部图片视图
- [ ] 测试我的图片视图（需登录）
- [ ] 测试搜索功能
- [ ] 测试下载功能
- [ ] 测试点赞功能
- [ ] 测试分页
- [ ] 测试移动端显示

---

## 常见问题

### Q: 如何自定义图片卡片样式？

A: 修改组件中的 style 对象，或提取为 CSS 模块。

### Q: 如何添加更多操作（如分享、收藏）？

A: 在卡片底部添加更多按钮，调用相应的 API。

### Q: 如何实现无限滚动？

A: 使用 Intersection Observer API 或 react-infinite-scroll-component。

### Q: 如何缓存图片数据？

A: 使用 React Query 或 SWR 库进行数据缓存。

---

## 下一步

1. **前端集成**
   - 在主应用中添加路由
   - 在导航菜单中添加链接
   - 测试所有功能

2. **功能扩展**
   - 添加评论功能
   - 添加收藏功能
   - 添加分享功能
   - 添加用户头像

3. **性能优化**
   - 实现图片懒加载
   - 添加虚拟滚动
   - 实现数据缓存

4. **用户体验**
   - 添加加载动画
   - 添加错误提示
   - 添加成功提示
   - 优化移动端显示

---

## 总结

✅ 完整的图片库前端组件
- 支持两种视图模式（全部/我的）
- 支持搜索、浏览、下载、点赞
- 响应式设计
- 完整的错误处理

✅ 易于集成
- 简单的 Props 接口
- 独立的组件
- 可复用的代码

✅ 用户友好
- 直观的界面
- 流畅的交互
- 完整的功能
