import { useState, useEffect, useRef } from 'react';
import { StorageService } from '../services/StorageService';

const API_BASE_URL = import.meta.env.VITE_API_BASE_URL;

interface ImageGeneratorProps {
  onClose: () => void;
  isAuthenticated: boolean;
  onShowLogin?: () => void;
  initialPrompt?: string;
  initialModel?: string;
  initialImage?: string;
  onInitialDataUsed?: () => void;
}

const IMAGE_MODELS = [
  // 万相系列
  { id: 'alibaba/wan2.6-t2i', name: '万相 2.6', desc: '最新超高质量', badge: '最新', provider: 'bailian', supportsImageEdit: false },
  { id: 'alibaba/wan2.5-t2i-preview', name: '万相 2.5', desc: '高质量预览', badge: '推荐', provider: 'bailian', supportsImageEdit: true },
  { id: 'alibaba/wan2.2-t2i-flash', name: '万相 2.2', desc: '快速版本', badge: '快速', provider: 'bailian', supportsImageEdit: true },
  { id: 'alibaba/wanx-v1', name: '万相 V1', desc: '风格控制', badge: '经典', provider: 'bailian', supportsImageEdit: true },
  
  // Stable Diffusion 系列
  { id: 'alibaba/stable-diffusion-v1.5', name: 'SD v1.5', desc: '开源经典', badge: '稳定', provider: 'bailian', supportsImageEdit: false },
  { id: 'alibaba/stable-diffusion-xl', name: 'SD XL', desc: '增强版', badge: '高质', provider: 'bailian', supportsImageEdit: false },
  { id: 'alibaba/stable-diffusion-3.5-large', name: 'SD 3.5', desc: '最高质量', badge: '精准', provider: 'bailian', supportsImageEdit: false },
  
  // 千问图像系列 - 仅 2.0 系列支持图片编辑
  { id: 'alibaba/qwen-image-2.0-pro', name: '千问 2.0 Pro', desc: '满血版最强', badge: '最强', provider: 'bailian', supportsImageEdit: true },
  { id: 'alibaba/qwen-image-2.0', name: '千问 2.0', desc: '加速版平衡', badge: '平衡', provider: 'bailian', supportsImageEdit: true },
  { id: 'alibaba/qwen-image-max', name: '千问 Max', desc: '最高质量', badge: '质量', provider: 'bailian', supportsImageEdit: false },
  { id: 'alibaba/qwen-image-plus', name: '千问 Plus', desc: '高质量版', badge: '高质', provider: 'bailian', supportsImageEdit: false },
  { id: 'alibaba/qwen-image', name: '千问图像', desc: '首代模型', badge: '经典', provider: 'bailian', supportsImageEdit: false },
  
  // OpenRouter 系列
  { id: 'black-forest-labs/flux.2-pro', name: 'Flux 2 Pro', desc: '专业级质量', badge: '推荐', provider: 'openrouter', supportsImageEdit: false },
  { id: 'black-forest-labs/flux.2-flex', name: 'Flux 2 Flex', desc: '快速灵活', badge: '快速', provider: 'openrouter', supportsImageEdit: false },
];

const IMAGE_SIZES = [
  { value: '1:1', label: '1:1 方形', size: '1024×1024' },
  { value: '16:9', label: '16:9 横屏', size: '1344×768' },
  { value: '9:16', label: '9:16 竖屏', size: '768×1344' },
  { value: '4:3', label: '4:3 标准', size: '1184×864' },
  { value: '3:4', label: '3:4 竖版', size: '864×1184' },
];

const BAILIAN_STYLES = [
  { value: '<auto>', label: '自动' },
  { value: '<photography>', label: '摄影' },
  { value: '<portrait>', label: '人像写真' },
  { value: '<3d cartoon>', label: '3D卡通' },
  { value: '<anime>', label: '动画' },
  { value: '<oil painting>', label: '油画' },
  { value: '<watercolor>', label: '水彩' },
  { value: '<sketch>', label: '素描' },
  { value: '<chinese painting>', label: '中国画' },
  { value: '<flat illustration>', label: '扁平插画' },
];

const BAILIAN_SIZES = [
  { value: '1280*1280', label: '1:1 正方形' },
  { value: '1104*1472', label: '3:4 竖版' },
  { value: '1472*1104', label: '4:3 横版' },
  { value: '960*1696', label: '9:16 竖屏' },
  { value: '1696*960', label: '16:9 横屏' },
  { value: '768*2700', label: '极限竖屏' },
  { value: '1024*1024', label: '1024×1024' },
  { value: '720*1280', label: '720×1280' },
  { value: '1280*720', label: '1280×720' },
  { value: '768*1152', label: '768×1152' },
  { value: '512*512', label: '512×512' },
  { value: '768*768', label: '768×768' },
  { value: '1280*1280', label: '1280×1280' },
  { value: '1536*1536', label: '1536×1536' },
  { value: '1440*1440', label: '1440×1440' },
];

function ImageGeneratorNew({ onClose, isAuthenticated, onShowLogin, initialPrompt = '', initialModel = '', initialImage = '', onInitialDataUsed }: ImageGeneratorProps) {
  const [prompt, setPrompt] = useState(initialPrompt);
  const [selectedModel, setSelectedModel] = useState(initialModel || IMAGE_MODELS[0].id);
  const [selectedSize, setSelectedSize] = useState(IMAGE_SIZES[0].value);
  const [selectedStyle, setSelectedStyle] = useState(BAILIAN_STYLES[0].value);
  const [selectedBailianSize, setSelectedBailianSize] = useState(BAILIAN_SIZES[0].value);
  const [negativePrompt, setNegativePrompt] = useState('');
  const [loading, setLoading] = useState(false);
  const [imageUrl, setImageUrl] = useState<string | null>(null);
  const [error, setError] = useState<string | null>(null);
  const [quota, setQuota] = useState<{ total: number; used: number; remaining: number } | null>(null);
  const [uploadedImage, setUploadedImage] = useState<string | null>(initialImage || null);
  const [history, setHistory] = useState<string[]>([]);
  const [promptInfo, setPromptInfo] = useState<{ original: string; extended: boolean } | null>(null);
  const fileInputRef = useRef<HTMLInputElement>(null);

  // 使用初始数据后清除
  useEffect(() => {
    if ((initialPrompt || initialImage) && onInitialDataUsed) {
      onInitialDataUsed();
    }
  }, []);

  // 如果有初始图片，自动选择支持编辑的模型
  useEffect(() => {
    if (initialImage && uploadedImage === initialImage) {
      const editableModels = IMAGE_MODELS.filter(m => m.supportsImageEdit);
      if (editableModels.length > 0 && !initialModel) {
        setSelectedModel(editableModels[0].id);
      }
    }
  }, [initialImage]);

  const loadQuota = async () => {
    try {
      const token = localStorage.getItem('auth_token');
      const headers: Record<string, string> = { 'Content-Type': 'application/json' };
      if (token) {
        headers['Authorization'] = `Bearer ${token}`;
      }
      
      const response = await fetch(`${API_BASE_URL}/image/quota`, { headers });
      const data = await response.json();
      if (data.success) {
        setQuota(data.quota);
      }
    } catch (err) {
      console.error('Failed to load quota:', err);
    }
  };

  useEffect(() => {
    loadQuota();
    // 加载历史记录
    StorageService.getImageHistory(10).then(setHistory);
  }, []);

  const saveToHistory = async (url: string) => {
    try {
      await StorageService.saveImageToHistory(url);
      // 重新加载历史记录
      const updated = await StorageService.getImageHistory(10);
      setHistory(updated);
    } catch (err) {
      console.error('Failed to save to history:', err);
    }
  };

  const handleFileUpload = (e: React.ChangeEvent<HTMLInputElement>) => {
    const file = e.target.files?.[0];
    if (!file) return;

    if (!file.type.startsWith('image/')) {
      setError('请上传图片文件');
      return;
    }

    if (file.size > 5 * 1024 * 1024) {
      setError('图片大小不能超过 5MB');
      return;
    }

    const reader = new FileReader();
    reader.onload = (event) => {
      const base64 = event.target?.result as string;
      setUploadedImage(base64);
      setError(null);
      
      // 自动选择支持图片编辑的模型
      const editableModels = IMAGE_MODELS.filter(m => m.supportsImageEdit);
      if (editableModels.length > 0) {
        setSelectedModel(editableModels[0].id);
      }
    };
    reader.onerror = () => {
      setError('图片读取失败');
    };
    reader.readAsDataURL(file);
  };

  const removeUploadedImage = () => {
    setUploadedImage(null);
    if (fileInputRef.current) {
      fileInputRef.current.value = '';
    }
  };

  const generateImage = async () => {
    if (!prompt.trim() || loading) return;
    
    if (quota && quota.remaining <= 0) {
      if (isAuthenticated) {
        setError('您的图片生成配额已用完');
      } else {
        setError('游客配额已用完，请登录以获取更多配额');
      }
      return;
    }
    
    setLoading(true);
    setError(null);
    setImageUrl(null);

    try {
      const token = localStorage.getItem('auth_token');
      const headers: Record<string, string> = { 'Content-Type': 'application/json' };
      if (token) {
        headers['Authorization'] = `Bearer ${token}`;
      }
      
      const currentModel = IMAGE_MODELS.find(m => m.id === selectedModel);
      const isBalian = currentModel?.provider === 'bailian';
      
      let response;
      
      if (isBalian) {
        // 调用阿里百练 API
        const requestBody: any = {
          prompt: prompt.trim(),
          model: selectedModel.replace('alibaba/', '')
        };
        
        if (negativePrompt.trim()) {
          requestBody.negative_prompt = negativePrompt.trim();
        }
        
        // 如果有上传的图片，添加参考图
        if (uploadedImage) {
          requestBody.ref_image = uploadedImage;
          // 调试：打印参考图片信息
          console.log('📸 参考图片信息:');
          console.log('  大小:', uploadedImage.length, '字符');
          console.log('  前缀:', uploadedImage.substring(0, 50));
          console.log('  是否为 data URL:', uploadedImage.startsWith('data:'));
          // 注意：wan2.6-t2i 模型不需要 ref_mode 和 ref_strength 参数
          // API 会根据提示词自动进行图像编辑
        }
        
        // 根据模型类型添加参数
        if (selectedModel === 'alibaba/wanx-v1') {
          requestBody.style = selectedStyle;
          requestBody.size = selectedBailianSize;
        } else if (selectedModel.includes('wan2')) {
          // 万相 2.x 系列
          requestBody.size = selectedBailianSize;
          requestBody.prompt_extend = true;
          requestBody.watermark = false;
        } else {
          // Stable Diffusion 模型
          requestBody.size = selectedBailianSize;
        }
        
        response = await fetch(`${API_BASE_URL}/image/generate/bailian`, {
          method: 'POST',
          headers,
          body: JSON.stringify(requestBody)
        });
        
        const data = await response.json();
        if (data.success) {
          if (data.status === 'completed' && data.images && data.images.length > 0) {
            // 同步响应 - 直接返回图片
            setImageUrl(data.images[0]);
            setPromptInfo({
              original: data.original_prompt || prompt,
              extended: data.prompt_extended || false
            });
            await saveToHistory(data.images[0]);
            
            // 保存到图片库（同步响应）
            try {
              await fetch(`${API_BASE_URL}/image/save`, {
                method: 'POST',
                headers,
                body: JSON.stringify({
                  model: selectedModel.replace('alibaba/', ''),
                  prompt: prompt,
                  imageUrl: data.images[0],
                  size: selectedBailianSize,
                  negativePrompt: negativePrompt || null
                })
              });
            } catch (err) {
              console.error('Failed to save to gallery:', err);
            }
            
            loadQuota();
          } else if (data.task_id) {
            // 异步响应 - 开始轮询
            setImageUrl(null);
            pollTaskResult(data.task_id, headers);
          } else {
            setError('生成失败: 无效的响应格式');
          }
        } else {
          setError(data.error || '任务创建失败');
        }
      } else {
        // 调用 OpenRouter API
        const requestBody: any = { 
          prompt: prompt.trim(), 
          model: selectedModel,
          aspect_ratio: selectedSize
        };
        
        if (uploadedImage) {
          requestBody.base_image = uploadedImage;
        }

        response = await fetch(`${API_BASE_URL}/image/generate`, {
          method: 'POST',
          headers,
          body: JSON.stringify(requestBody)
        });

        const data = await response.json();
        if (data.success && data.image_url) {
          setImageUrl(data.image_url);
          await saveToHistory(data.image_url);
          
          // 保存到图片库（OpenRouter）
          try {
            await fetch(`${API_BASE_URL}/image/save`, {
              method: 'POST',
              headers,
              body: JSON.stringify({
                model: selectedModel,
                prompt: prompt,
                imageUrl: data.image_url,
                size: selectedSize,
                negativePrompt: negativePrompt || null
              })
            });
          } catch (err) {
            console.error('Failed to save to gallery:', err);
          }
          
          loadQuota();
        } else {
          setError(data.error || '图片生成失败');
        }
      }
    } catch (err: any) {
      setError('网络错误: ' + err.message);
    } finally {
      setLoading(false);
    }
  };

  const pollTaskResult = async (taskId: string, headers: Record<string, string>, attempts = 0) => {
    if (attempts > 60) { // 最多轮询60次（约5分钟）
      setError('任务超时，请稍后重试');
      return;
    }

    try {
      const response = await fetch(`${API_BASE_URL}/image/bailian/task/${taskId}`, { headers });
      const data = await response.json();

      if (data.success) {
        if (data.status === 'completed' && data.images && data.images.length > 0) {
          setImageUrl(data.images[0]);
          await saveToHistory(data.images[0]);
          
          // 保存到图片库（异步任务完成后）
          try {
            const currentModel = IMAGE_MODELS.find(m => m.id === selectedModel);
            const isBalian = currentModel?.provider === 'bailian';
            
            if (isBalian) {
              // 调用后端 API 保存到图片库
              await fetch(`${API_BASE_URL}/image/save`, {
                method: 'POST',
                headers,
                body: JSON.stringify({
                  model: selectedModel.replace('alibaba/', ''),
                  prompt: prompt,
                  imageUrl: data.images[0],
                  size: selectedBailianSize,
                  negativePrompt: negativePrompt || null
                })
              });
            }
          } catch (err) {
            console.error('Failed to save to gallery:', err);
            // 不影响主流程
          }
          
          loadQuota();
        } else if (data.status === 'processing') {
          // 继续轮询
          setTimeout(() => pollTaskResult(taskId, headers, attempts + 1), 5000);
        } else if (data.status === 'failed') {
          setError(data.message || '图片生成失败');
        }
      } else {
        setError(data.error || '查询失败');
      }
    } catch (err: any) {
      setError('查询失败: ' + err.message);
    }
  };

  const downloadImage = () => {
    if (!imageUrl) return;
    const a = document.createElement('a');
    a.href = imageUrl;
    a.download = `ai-image-${Date.now()}.png`;
    document.body.appendChild(a);
    a.click();
    document.body.removeChild(a);
  };

  return (
    <div className="image-generator-overlay" onClick={onClose}>
      <div className="image-generator-container" onClick={(e) => e.stopPropagation()}>
        
        {/* 左侧控制面板 */}
        <div className="control-panel">
          <div className="panel-header">
            <h2>🎨 AI 图片生成器</h2>
            <p>轻松创作，无限可能</p>
          </div>

          {/* 配额显示 */}
          {quota && (
            <div className={`quota-card ${quota.remaining > 0 ? 'has-quota' : 'no-quota'}`}>
              <div className="quota-label">
                {isAuthenticated ? '🎟️ 我的配额' : '👤 游客配额'}
              </div>
              <div className="quota-value">
                {quota.remaining} <span>/ {quota.total}</span>
              </div>
              <div className="quota-hint">
                {isAuthenticated 
                  ? `已使用 ${quota.used} 张` 
                  : quota.remaining > 0 
                    ? '登录后可获得更多配额' 
                    : '配额已用完，请登录获取更多'}
              </div>
              {!isAuthenticated && quota.remaining <= 0 && onShowLogin && (
                <button className="login-btn" onClick={onShowLogin}>
                  立即登录
                </button>
              )}
            </div>
          )}

          {/* 图片上传区域 - 放在最前面 */}
          <div className="form-group">
            <label>🖼️ 上传图片（可选）</label>
            <div style={{ fontSize: '12px', color: '#666', marginBottom: '8px' }}>
              💡 支持的模型：千问 2.0 系列、万相系列
            </div>
            <input
              ref={fileInputRef}
              type="file"
              accept="image/*"
              onChange={handleFileUpload}
              disabled={loading}
              style={{ display: 'none' }}
            />
            {!uploadedImage ? (
              <button
                className="upload-btn"
                onClick={() => fileInputRef.current?.click()}
                disabled={loading}
                style={{
                  width: '100%',
                  padding: '12px',
                  border: '2px dashed #1a73e8',
                  borderRadius: '8px',
                  background: '#f0f7ff',
                  color: '#1a73e8',
                  cursor: 'pointer',
                  fontSize: '14px',
                  fontWeight: '500',
                  transition: 'all 0.2s'
                }}
              >
                📤 点击上传图片或拖拽到此处
              </button>
            ) : (
              <div style={{
                position: 'relative',
                borderRadius: '8px',
                overflow: 'hidden',
                background: '#f0f7ff',
                padding: '8px'
              }}>
                <img 
                  src={uploadedImage} 
                  alt="Uploaded" 
                  style={{
                    width: '100%',
                    maxHeight: '200px',
                    objectFit: 'cover',
                    borderRadius: '4px'
                  }}
                />
                <button
                  className="remove-btn"
                  onClick={removeUploadedImage}
                  disabled={loading}
                  style={{
                    position: 'absolute',
                    top: '8px',
                    right: '8px',
                    width: '32px',
                    height: '32px',
                    borderRadius: '50%',
                    background: '#ea4335',
                    color: 'white',
                    border: 'none',
                    cursor: 'pointer',
                    fontSize: '18px',
                    display: 'flex',
                    alignItems: 'center',
                    justifyContent: 'center'
                  }}
                >
                  ✕
                </button>
              </div>
            )}
          </div>

          {/* 提示词输入 */}
          <div className="form-group">
            <label>
              {uploadedImage ? '✏️ 描述你想要的修改或风格' : '💭 描述你的创意'}
            </label>
            <textarea 
              value={prompt} 
              onChange={(e) => setPrompt(e.target.value)} 
              placeholder={uploadedImage 
                ? "例如：改成油画风格、加上光晕效果、改变背景色..." 
                : "例如：一只可爱的橘猫在阳光下打盹..."} 
              disabled={loading}
              rows={4}
            />
            {uploadedImage && (
              <div style={{ fontSize: '12px', color: '#059669', marginTop: '8px', lineHeight: '1.5' }}>
                ✅ 提示：详细描述你想要的修改效果。例如：改变风格、调整背景、改变光线、改变主体等。
              </div>
            )}
          </div>

          {/* 模型选择 */}
          <div className="form-group">
            <label>🤖 选择模型</label>
            {uploadedImage && (
              <div style={{ fontSize: '12px', color: '#059669', marginBottom: '8px', padding: '8px', background: '#f0fdf4', borderRadius: '6px' }}>
                ✅ 已自动筛选支持图片编辑的模型
              </div>
            )}
            <div className="model-grid">
              {IMAGE_MODELS.map(model => {
                // 如果上传了图片，只显示支持编辑的模型
                if (uploadedImage && !model.supportsImageEdit) {
                  return null;
                }
                
                // 如果没有上传图片，显示所有模型
                // 如果上传了图片，只显示支持编辑的模型（上面已过滤）
                
                return (
                  <div
                    key={model.id}
                    className={`model-card ${selectedModel === model.id ? 'selected' : ''}`}
                    onClick={() => !loading && setSelectedModel(model.id)}
                  >
                    <div className="model-name">{model.name}</div>
                    <div className="model-desc">{model.desc}</div>
                    {model.badge && <span className="model-badge">{model.badge}</span>}
                    {uploadedImage && model.supportsImageEdit && (
                      <span style={{
                        position: 'absolute',
                        bottom: '8px',
                        right: '8px',
                        fontSize: '10px',
                        background: '#059669',
                        color: 'white',
                        padding: '2px 6px',
                        borderRadius: '3px'
                      }}>
                        支持编辑
                      </span>
                    )}
                  </div>
                );
              })}
            </div>
          </div>

          {/* 阿里百练特定选项 */}
          {IMAGE_MODELS.find(m => m.id === selectedModel)?.provider === 'bailian' && (
            <>
              {/* 仅 wanx-v1 显示风格选择 */}
              {selectedModel === 'alibaba/wanx-v1' && (
                <div className="form-group">
                  <label>🎨 图片风格</label>
                  <select 
                    value={selectedStyle} 
                    onChange={(e) => setSelectedStyle(e.target.value)} 
                    disabled={loading}
                  >
                    {BAILIAN_STYLES.map(style => (
                      <option key={style.value} value={style.value}>
                        {style.label}
                      </option>
                    ))}
                  </select>
                </div>
              )}

              {/* 尺寸选择（阿里百练） */}
              <div className="form-group">
                <label>📐 图片尺寸</label>
                <select 
                  value={selectedBailianSize} 
                  onChange={(e) => setSelectedBailianSize(e.target.value)} 
                  disabled={loading}
                >
                  {BAILIAN_SIZES.map(size => (
                    <option key={size.value} value={size.value}>
                      {size.label}
                    </option>
                  ))}
                </select>
              </div>

              {/* 反向提示词 */}
              <div className="form-group">
                <label>🚫 反向提示词（可选）</label>
                <textarea 
                  value={negativePrompt} 
                  onChange={(e) => setNegativePrompt(e.target.value)} 
                  placeholder="例如：模糊、低质量、变形..." 
                  disabled={loading}
                  rows={2}
                />
              </div>
            </>
          )}

          {/* OpenRouter 特定选项 */}
          {IMAGE_MODELS.find(m => m.id === selectedModel)?.provider === 'openrouter' && (
            <>
              {/* 尺寸选择 */}
              <div className="form-group">
                <label>📐 图片尺寸</label>
                <select 
                  value={selectedSize} 
                  onChange={(e) => setSelectedSize(e.target.value)} 
                  disabled={loading}
                >
                  {IMAGE_SIZES.map(size => (
                    <option key={size.value} value={size.value}>
                      {size.label} ({size.size})
                    </option>
                  ))}
                </select>
              </div>
            </>
          )}

          {/* 错误提示 */}
          {error && (
            <div className="error-message">
              ⚠️ {error}
            </div>
          )}

          {/* 生成按钮 */}
          <button 
            className="generate-btn"
            onClick={generateImage} 
            disabled={loading || !prompt.trim()}
          >
            {loading 
              ? '⏳ 生成中...' 
              : uploadedImage 
                ? '✏️ 开始编辑' 
                : '🎨 生成图片'}
          </button>
        </div>

        {/* 右侧预览区域 */}
        <div className="preview-panel">
          <div className="preview-header">
            <div className="status">
              {imageUrl ? '✅ 完成' : loading ? '⏳ 生成中...' : '准备就绪'}
            </div>
            <button className="close-btn" onClick={onClose}>✕</button>
          </div>

          <div className="preview-content">
            {loading && (
              <div className="loading-state">
                <div className="spinner"></div>
                <div className="loading-text">AI 正在创作中...</div>
                <div className="loading-hint">通常需要 3-5 秒</div>
              </div>
            )}

            {imageUrl && !loading && (
              <div className="image-result">
                <img src={imageUrl} alt="Generated" />
              </div>
            )}

            {!loading && !imageUrl && (
              <div className="empty-state">
                <div className="empty-icon">🎨</div>
                <div className="empty-title">准备创作</div>
                <div className="empty-desc">
                  在左侧输入描述，选择模型和尺寸，点击生成按钮
                </div>
              </div>
            )}
          </div>

          {/* 操作按钮 */}
          {imageUrl && !loading && (
            <>
              {promptInfo && promptInfo.extended && (
                <div style={{
                  padding: '12px 24px',
                  background: '#f0fdf4',
                  borderTop: '1px solid #e5e7eb',
                  fontSize: '12px',
                  color: '#059669',
                  lineHeight: '1.5'
                }}>
                  <div style={{ fontWeight: '600', marginBottom: '4px' }}>✨ 提示词已优化</div>
                  <div style={{ fontSize: '11px', color: '#047857' }}>
                    系统自动优化了你的提示词以获得更好的生成效果
                  </div>
                </div>
              )}
              <div className="action-bar">
                <button className="action-btn primary" onClick={downloadImage}>
                  📥 下载图片
                </button>
                <button className="action-btn" onClick={() => { setImageUrl(null); setError(null); setPromptInfo(null); }}>
                  🔄 重新生成
                </button>
              </div>
            </>
          )}

          {/* 历史记录 */}
          {history.length > 0 && !loading && (
            <div className="history-section">
              <div className="history-title">📜 最近生成</div>
              <div className="history-grid">
                {history.slice(0, 5).map((url, index) => (
                  <div 
                    key={index} 
                    className="history-item"
                    onClick={() => setImageUrl(url)}
                  >
                    <img src={url} alt={`History ${index}`} />
                  </div>
                ))}
              </div>
            </div>
          )}
        </div>
      </div>

      <style>{`
        .image-generator-overlay {
          position: fixed;
          top: 0;
          left: 0;
          right: 0;
          bottom: 0;
          background: rgba(0, 0, 0, 0.75);
          display: flex;
          align-items: center;
          justifyContent: center;
          z-index: 1000;
          backdrop-filter: blur(4px);
        }

        .image-generator-container {
          background: #fff;
          border-radius: 20px;
          max-width: 1100px;
          width: 95%;
          max-height: 95vh;
          display: flex;
          overflow: hidden;
          box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.5);
        }

        .control-panel {
          width: 380px;
          background: linear-gradient(180deg, #f8f9fa 0%, #ffffff 100%);
          padding: 24px;
          overflow-y: auto;
          border-right: 1px solid #e5e7eb;
        }

        .panel-header h2 {
          margin: 0 0 4px 0;
          font-size: 24px;
          font-weight: 700;
          color: #1f2937;
        }

        .panel-header p {
          margin: 0 0 20px 0;
          font-size: 13px;
          color: #6b7280;
        }

        .quota-card {
          padding: 16px;
          border-radius: 12px;
          margin-bottom: 20px;
          border: 2px solid;
        }

        .quota-card.has-quota {
          background: linear-gradient(135deg, #ecfdf5 0%, #d1fae5 100%);
          border-color: #10b981;
        }

        .quota-card.no-quota {
          background: linear-gradient(135deg, #fef3c7 0%, #fde68a 100%);
          border-color: #f59e0b;
        }

        .quota-label {
          font-size: 12px;
          font-weight: 600;
          color: #374151;
          margin-bottom: 8px;
        }

        .quota-value {
          font-size: 28px;
          font-weight: 800;
          color: #1f2937;
        }

        .quota-value span {
          font-size: 16px;
          color: #6b7280;
          font-weight: 500;
        }

        .quota-hint {
          font-size: 11px;
          color: #6b7280;
          margin-top: 6px;
        }

        .login-btn {
          margin-top: 12px;
          width: 100%;
          padding: 10px;
          background: linear-gradient(135deg, #3b82f6 0%, #2563eb 100%);
          color: white;
          border: none;
          border-radius: 8px;
          font-size: 13px;
          font-weight: 600;
          cursor: pointer;
          transition: transform 0.2s;
        }

        .login-btn:hover {
          transform: translateY(-1px);
        }

        .form-group {
          margin-bottom: 20px;
        }

        .form-group label {
          display: block;
          font-size: 12px;
          font-weight: 600;
          color: #374151;
          margin-bottom: 8px;
          text-transform: uppercase;
          letter-spacing: 0.5px;
        }

        .form-group textarea {
          width: 100%;
          padding: 12px;
          border: 2px solid #e5e7eb;
          border-radius: 10px;
          font-size: 14px;
          font-family: inherit;
          resize: vertical;
          transition: border-color 0.2s;
          box-sizing: border-box;
        }

        .form-group textarea:focus {
          outline: none;
          border-color: #3b82f6;
        }

        .form-group select {
          width: 100%;
          padding: 10px 12px;
          border: 2px solid #e5e7eb;
          border-radius: 10px;
          font-size: 13px;
          background: white;
          cursor: pointer;
          transition: border-color 0.2s;
        }

        .form-group select:focus {
          outline: none;
          border-color: #3b82f6;
        }

        .model-grid {
          display: grid;
          grid-template-columns: repeat(2, 1fr);
          gap: 10px;
        }

        .model-card {
          position: relative;
          padding: 14px;
          border: 2px solid #e5e7eb;
          border-radius: 10px;
          cursor: pointer;
          transition: all 0.2s;
          background: white;
        }

        .model-card:hover {
          border-color: #3b82f6;
          transform: translateY(-2px);
        }

        .model-card.selected {
          border-color: #3b82f6;
          background: linear-gradient(135deg, #eff6ff 0%, #dbeafe 100%);
        }

        .model-name {
          font-size: 13px;
          font-weight: 600;
          color: #1f2937;
          margin-bottom: 4px;
        }

        .model-desc {
          font-size: 11px;
          color: #6b7280;
        }

        .model-badge {
          position: absolute;
          top: 8px;
          right: 8px;
          padding: 2px 6px;
          background: #3b82f6;
          color: white;
          font-size: 9px;
          font-weight: 600;
          border-radius: 4px;
        }

        .upload-btn {
          width: 100%;
          padding: 14px;
          background: #f9fafb;
          border: 2px dashed #d1d5db;
          border-radius: 10px;
          font-size: 13px;
          color: #6b7280;
          cursor: pointer;
          transition: all 0.2s;
        }

        .upload-btn:hover {
          border-color: #3b82f6;
          background: #eff6ff;
          color: #3b82f6;
        }

        .uploaded-preview {
          position: relative;
          border: 2px solid #e5e7eb;
          border-radius: 10px;
          overflow: hidden;
        }

        .uploaded-preview img {
          width: 100%;
          height: auto;
          display: block;
        }

        .remove-btn {
          position: absolute;
          top: 8px;
          right: 8px;
          width: 32px;
          height: 32px;
          border-radius: 50%;
          border: none;
          background: rgba(0, 0, 0, 0.7);
          color: white;
          cursor: pointer;
          font-size: 16px;
          display: flex;
          align-items: center;
          justify-content: center;
          transition: background 0.2s;
        }

        .remove-btn:hover {
          background: rgba(0, 0, 0, 0.9);
        }

        .error-message {
          padding: 12px;
          background: #fef2f2;
          border: 1px solid #fecaca;
          border-radius: 8px;
          font-size: 12px;
          color: #dc2626;
          margin-bottom: 16px;
        }

        .generate-btn {
          width: 100%;
          padding: 14px;
          background: linear-gradient(135deg, #8b5cf6 0%, #7c3aed 100%);
          color: white;
          border: none;
          border-radius: 10px;
          font-size: 15px;
          font-weight: 600;
          cursor: pointer;
          transition: all 0.2s;
          box-shadow: 0 4px 12px rgba(139, 92, 246, 0.4);
        }

        .generate-btn:hover:not(:disabled) {
          transform: translateY(-2px);
          box-shadow: 0 6px 16px rgba(139, 92, 246, 0.5);
        }

        .generate-btn:disabled {
          background: #d1d5db;
          color: #9ca3af;
          cursor: not-allowed;
          box-shadow: none;
        }

        .preview-panel {
          flex: 1;
          display: flex;
          flex-direction: column;
          background: #fafafa;
        }

        .preview-header {
          padding: 16px 24px;
          border-bottom: 1px solid #e5e7eb;
          display: flex;
          justify-content: space-between;
          align-items: center;
          background: white;
        }

        .status {
          font-size: 13px;
          font-weight: 600;
          color: #6b7280;
        }

        .close-btn {
          width: 32px;
          height: 32px;
          border-radius: 50%;
          border: none;
          background: #f3f4f6;
          cursor: pointer;
          font-size: 16px;
          display: flex;
          align-items: center;
          justify-content: center;
          transition: background 0.2s;
        }

        .close-btn:hover {
          background: #e5e7eb;
        }

        .preview-content {
          flex: 1;
          display: flex;
          align-items: center;
          justify-content: center;
          padding: 24px;
          overflow: auto;
        }

        .loading-state {
          text-align: center;
        }

        .spinner {
          width: 48px;
          height: 48px;
          border: 4px solid #f3f4f6;
          border-top-color: #8b5cf6;
          border-radius: 50%;
          animation: spin 1s linear infinite;
          margin: 0 auto 20px;
        }

        @keyframes spin {
          to { transform: rotate(360deg); }
        }

        .loading-text {
          font-size: 16px;
          font-weight: 600;
          color: #1f2937;
          margin-bottom: 8px;
        }

        .loading-hint {
          font-size: 13px;
          color: #6b7280;
        }

        .image-result img {
          max-width: 100%;
          max-height: calc(95vh - 200px);
          border-radius: 12px;
          box-shadow: 0 10px 30px rgba(0, 0, 0, 0.2);
        }

        .empty-state {
          text-align: center;
          color: #9ca3af;
        }

        .empty-icon {
          font-size: 64px;
          margin-bottom: 16px;
          opacity: 0.3;
        }

        .empty-title {
          font-size: 18px;
          font-weight: 600;
          color: #6b7280;
          margin-bottom: 8px;
        }

        .empty-desc {
          font-size: 13px;
          color: #9ca3af;
          line-height: 1.5;
          max-width: 300px;
          margin: 0 auto;
        }

        .action-bar {
          padding: 16px 24px;
          border-top: 1px solid #e5e7eb;
          display: flex;
          gap: 12px;
          background: white;
        }

        .action-btn {
          flex: 1;
          padding: 12px;
          border: 2px solid #e5e7eb;
          border-radius: 10px;
          font-size: 14px;
          font-weight: 600;
          cursor: pointer;
          transition: all 0.2s;
          background: white;
          color: #374151;
        }

        .action-btn.primary {
          background: linear-gradient(135deg, #3b82f6 0%, #2563eb 100%);
          color: white;
          border-color: transparent;
        }

        .action-btn:hover {
          transform: translateY(-1px);
        }

        .history-section {
          padding: 16px 24px;
          border-top: 1px solid #e5e7eb;
          background: white;
        }

        .history-title {
          font-size: 12px;
          font-weight: 600;
          color: #6b7280;
          margin-bottom: 12px;
          text-transform: uppercase;
          letter-spacing: 0.5px;
        }

        .history-grid {
          display: grid;
          grid-template-columns: repeat(5, 1fr);
          gap: 8px;
        }

        .history-item {
          aspect-ratio: 1;
          border-radius: 8px;
          overflow: hidden;
          cursor: pointer;
          transition: transform 0.2s;
          border: 2px solid #e5e7eb;
        }

        .history-item:hover {
          transform: scale(1.05);
          border-color: #3b82f6;
        }

        .history-item img {
          width: 100%;
          height: 100%;
          object-fit: cover;
        }

        @media (max-width: 768px) {
          .image-generator-container {
            flex-direction: column;
            max-height: 100vh;
            height: 100vh;
            border-radius: 0;
            width: 100%;
            max-width: 100%;
          }

          .control-panel {
            width: 100%;
            max-height: none;
            flex-shrink: 0;
            border-right: none;
            border-bottom: 1px solid #e5e7eb;
          }

          .preview-panel {
            flex: 1;
            min-height: 0;
          }

          .panel-header h2 {
            font-size: 20px;
          }

          .model-grid {
            grid-template-columns: 1fr;
          }

          .history-grid {
            grid-template-columns: repeat(3, 1fr);
          }

          .action-bar {
            flex-direction: column;
          }

          .action-btn {
            width: 100%;
          }

          .preview-content {
            padding: 16px;
          }

          .image-result img {
            max-height: calc(100vh - 300px);
          }
        }

        @media (max-width: 480px) {
          .control-panel {
            padding: 16px;
          }

          .panel-header h2 {
            font-size: 18px;
          }

          .form-group label {
            font-size: 11px;
          }

          .quota-value {
            font-size: 24px;
          }

          .history-grid {
            grid-template-columns: repeat(2, 1fr);
          }
        }
      `}</style>
    </div>
  );
}

export default ImageGeneratorNew;
