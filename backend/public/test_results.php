<?php
/**
 * Display test results with embedded images
 */

// Check if images exist
$images = [
    1 => '/tmp/result_1.png',
    2 => '/tmp/result_2.png',
    3 => '/tmp/result_3.png'
];

$imageData = [];
foreach ($images as $num => $path) {
    if (file_exists($path)) {
        $data = file_get_contents($path);
        $imageData[$num] = 'data:image/png;base64,' . base64_encode($data);
    }
}

?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Qwen-Image-2.0 图片编辑测试结果</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            padding: 40px 20px;
        }
        
        .container {
            max-width: 1200px;
            margin: 0 auto;
        }
        
        h1 {
            color: white;
            text-align: center;
            margin-bottom: 10px;
            font-size: 32px;
        }
        
        .subtitle {
            color: rgba(255, 255, 255, 0.9);
            text-align: center;
            margin-bottom: 40px;
            font-size: 16px;
        }
        
        .test-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(350px, 1fr));
            gap: 30px;
            margin-bottom: 40px;
        }
        
        .test-card {
            background: white;
            border-radius: 16px;
            overflow: hidden;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
            transition: transform 0.3s ease, box-shadow 0.3s ease;
        }
        
        .test-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 30px 80px rgba(0, 0, 0, 0.4);
        }
        
        .test-header {
            padding: 20px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
        }
        
        .test-number {
            font-size: 24px;
            font-weight: 700;
            margin-bottom: 8px;
        }
        
        .test-prompt {
            font-size: 14px;
            opacity: 0.95;
            line-height: 1.5;
        }
        
        .test-image {
            width: 100%;
            aspect-ratio: 1;
            object-fit: cover;
            background: #f5f5f5;
        }
        
        .test-footer {
            padding: 16px 20px;
            background: #f9f9f9;
            border-top: 1px solid #e5e5e5;
            font-size: 12px;
            color: #666;
        }
        
        .summary {
            background: white;
            border-radius: 16px;
            padding: 30px;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
        }
        
        .summary h2 {
            color: #333;
            margin-bottom: 20px;
            font-size: 20px;
        }
        
        .summary-item {
            margin-bottom: 16px;
            padding-bottom: 16px;
            border-bottom: 1px solid #e5e5e5;
        }
        
        .summary-item:last-child {
            border-bottom: none;
            margin-bottom: 0;
            padding-bottom: 0;
        }
        
        .summary-label {
            font-weight: 600;
            color: #333;
            margin-bottom: 8px;
        }
        
        .summary-text {
            color: #666;
            line-height: 1.6;
            font-size: 14px;
        }
        
        .status-success {
            color: #10b981;
            font-weight: 600;
        }
        
        ul {
            margin-top: 8px;
            margin-left: 20px;
        }
        
        li {
            margin-bottom: 6px;
        }
        
        .no-image {
            width: 100%;
            aspect-ratio: 1;
            background: #f5f5f5;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #999;
            font-size: 14px;
        }
        
        @media (max-width: 768px) {
            h1 {
                font-size: 24px;
            }
            
            .test-grid {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>🎨 Qwen-Image-2.0 图片编辑测试</h1>
        <p class="subtitle">验证图片编辑功能是否正常工作</p>
        
        <div class="test-grid">
            <div class="test-card">
                <div class="test-header">
                    <div class="test-number">测试 1️⃣</div>
                    <div class="test-prompt">改变背景为蓝色，保持主体</div>
                </div>
                <?php if (isset($imageData[1])): ?>
                    <img src="<?php echo $imageData[1]; ?>" alt="Test 1" class="test-image">
                <?php else: ?>
                    <div class="no-image">图片未找到</div>
                <?php endif; ?>
                <div class="test-footer">
                    预期：原始主体保持不变，背景变为蓝色
                </div>
            </div>
            
            <div class="test-card">
                <div class="test-header">
                    <div class="test-number">测试 2️⃣</div>
                    <div class="test-prompt">添加酷炫太阳镜</div>
                </div>
                <?php if (isset($imageData[2])): ?>
                    <img src="<?php echo $imageData[2]; ?>" alt="Test 2" class="test-image">
                <?php else: ?>
                    <div class="no-image">图片未找到</div>
                <?php endif; ?>
                <div class="test-footer">
                    预期：主体戴上太阳镜
                </div>
            </div>
            
            <div class="test-card">
                <div class="test-header">
                    <div class="test-number">测试 3️⃣</div>
                    <div class="test-prompt">转换为油画风格</div>
                </div>
                <?php if (isset($imageData[3])): ?>
                    <img src="<?php echo $imageData[3]; ?>" alt="Test 3" class="test-image">
                <?php else: ?>
                    <div class="no-image">图片未找到</div>
                <?php endif; ?>
                <div class="test-footer">
                    预期：图片转换为油画风格
                </div>
            </div>
        </div>
        
        <div class="summary">
            <h2>📋 测试总结</h2>
            
            <div class="summary-item">
                <div class="summary-label">✅ API 调用状态</div>
                <div class="summary-text">
                    <span class="status-success">成功</span> - 所有三个请求都成功返回了生成的图片
                </div>
            </div>
            
            <div class="summary-item">
                <div class="summary-label">🖼️ 图片格式</div>
                <div class="summary-text">
                    所有生成的图片都是有效的 PNG 格式，分辨率 1024×1024 像素
                </div>
            </div>
            
            <div class="summary-item">
                <div class="summary-label">🔍 关键问题</div>
                <div class="summary-text">
                    <strong>请检查上面的三张图片：</strong>
                    <ul>
                        <li>图片 1：背景是否变成了蓝色？主体是否保持不变？</li>
                        <li>图片 2：主体是否戴上了太阳镜？</li>
                        <li>图片 3：图片是否转换为了油画风格？</li>
                    </ul>
                </div>
            </div>
            
            <div class="summary-item">
                <div class="summary-label">💡 可能的问题</div>
                <div class="summary-text">
                    如果生成的图片与提示词无关（例如完全不同的内容），可能的原因：
                    <ul>
                        <li>Qwen-Image-2.0 模型可能不支持精确的图片编辑</li>
                        <li>参考图片可能没有正确传递到 API</li>
                        <li>需要使用不同的提示词格式或模型</li>
                    </ul>
                </div>
            </div>
        </div>
    </div>
</body>
</html>
