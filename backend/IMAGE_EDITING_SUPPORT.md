# 图片编辑功能支持情况

## 测试结果总结

根据 2026-03-11 的测试，以下是各模型对图片编辑功能的支持情况：

### ✅ 支持图片编辑的模型

#### Qwen-Image 系列（使用 Multimodal 端点）
- **qwen-image-2.0-pro** (千问 2.0 Pro) - ✅ 支持
- **qwen-image-2.0** (千问 2.0) - ✅ 支持

#### 万相系列（使用 Legacy 端点）
- **wan2.5-t2i-preview** (万相 2.5) - ✅ 支持
- **wan2.2-t2i-flash** (万相 2.2) - ✅ 支持
- **wanx-v1** (万相 V1) - ✅ 支持

### ❌ 不支持图片编辑的模型

#### Qwen-Image 系列
- **qwen-image-max** (千问 Max) - ❌ Multimodal 端点参数错误
- **qwen-image-plus** (千问 Plus) - ❌ Multimodal 端点参数错误
- **qwen-image** (千问图像) - ❌ Multimodal 端点参数错误

#### 万相系列
- **wan2.6-t2i** (万相 2.6) - ❌ 不支持 ref_image 参数

#### Stable Diffusion 系列
- **stable-diffusion-v1.5** - ❌ 不支持
- **stable-diffusion-xl** - ❌ 不支持
- **stable-diffusion-3.5-large** - ❌ 不支持

#### OpenRouter 系列
- **black-forest-labs/flux.2-pro** (Flux 2 Pro) - ❌ 不支持
- **black-forest-labs/flux.2-flex** (Flux 2 Flex) - ❌ 不支持

## 技术细节

### Multimodal 端点（qwen-image-2.0, qwen-image-2.0-pro）
- 端点：`https://dashscope.aliyuncs.com/api/v1/services/aigc/multimodal-generation/generation`
- 特点：同步返回结果
- 参数格式：
  ```json
  {
    "model": "qwen-image-2.0",
    "input": {
      "messages": [
        {
          "role": "user",
          "content": [
            {"text": "提示词"},
            {"image": "data:image/jpeg;base64,..."}
          ]
        }
      ]
    },
    "parameters": {
      "n": 1,
      "size": "1024*1024",
      "prompt_extend": true
    }
  }
  ```

### Legacy 端点（wan2.5, wan2.2, wanx-v1）
- 端点：`https://dashscope.aliyuncs.com/api/v1/services/aigc/text2image/image-synthesis`
- 特点：异步返回任务 ID，需要轮询查询结果
- 参数格式：
  ```json
  {
    "model": "wan2.5-t2i-preview",
    "input": {
      "prompt": "提示词",
      "ref_image": "data:image/jpeg;base64,..."
    },
    "parameters": {
      "n": 1,
      "size": "1024*1024"
    }
  }
  ```

## 前端实现

在 `ImageGeneratorNew.tsx` 中，`supportsImageEdit` 标志用于：
1. 当用户上传图片时，自动过滤显示支持编辑的模型
2. 自动选择第一个支持编辑的模型
3. 显示"支持编辑"徽章
4. 显示"已自动筛选支持图片编辑的模型"提示

## 后端实现

在 `AliBailianService.php` 中：
1. `usesMultimodalEndpoint()` 判断是否使用 Multimodal 端点
2. `generateImageMultimodal()` 处理 Multimodal 端点请求
3. `generateImageLegacy()` 处理 Legacy 端点请求
4. 参考图片自动转换为正确的 Base64 格式

## 测试文件

- `test_qwen_image_edit.php` - 单个模型测试
- `test_image_editing_real.php` - 真实图片编辑测试
- `test_all_models_image_edit.php` - 所有 Qwen 模型测试
- `test_wan_models_image_edit.php` - 所有万相模型测试
- `public/test_results.php` - 测试结果展示页面

## 推荐使用

对于图片编辑功能，推荐使用：
1. **首选**：qwen-image-2.0-pro (最强，同步返回)
2. **次选**：qwen-image-2.0 (平衡，同步返回)
3. **备选**：wan2.5-t2i-preview (高质量，异步返回)
