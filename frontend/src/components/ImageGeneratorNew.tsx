import { useState, useEffect, useRef } from 'react';

const API_BASE_URL = import.meta.env.VITE_API_BASE_URL;

interface ImageGeneratorProps {
  onClose: () => void;
  isAuthenticated: boolean;
  onShowLogin?: () => void;
}

const IMAGE_MODELS = [
  { id: 'black-forest-labs/flux.2-pro', name: 'Flux 2 Pro', desc: '专业级质量', badge: '推荐' },
  { id: 'black-forest-labs/flux.2-flex', name: 'Flux 2 Flex', desc: '快速灵活', badge: '快速' },
];

const IMAGE_SIZES = [
  { value: '1:1', label: '1:1 方形', size: '1024×1024' },
  { value: '16:9', label: '16:9 横屏', size: '1344×768' },
  { value: '9:16', label: '9:16 竖屏', size: '768×1344' },
  { value: '4:3', label: '4:3 标准', size: '1184×864' },
  { value: '3:4', label: '3:4 竖版', size: '864×1184' },
];

function ImageGeneratorNew({ onClose, isAuthenticated, onShowLogin }: ImageGeneratorProps) {
  const [prompt, setPrompt] = useState('');
  const [selectedModel, setSelectedModel] = useState(IMAGE_MODELS[0].id);
  const [selectedSize, setSelectedSize] = useState(IMAGE_SIZES[0].value);
  const [loading, setLoading] = useState(false);
  const [imageUrl, setImageUrl] = useState<string | null>(null);
  const [error, setError] = useState<string | null>(null);
  const [quota, setQuota] = useState<{ total: number; used: number; remaining: number } | null>(null);
  const [uploadedImage, setUploadedImage] = useState<string | null>(null);
  const [history, setHistory] = useState<string[]>([]);
  const fileInputRef = useRef<HTMLInputElement>(null);

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
    const saved = localStorage.getItem('image_history');
    if (saved) {
      setHistory(JSON.parse(saved));
    }
  }, []);

  const saveToHistory = (url: string) => {
    const newHistory = [url, ...history].slice(0, 10); // 只保留最近10张
    setHistory(newHistory);
    localStorage.setItem('image_history', JSON.stringify(newHistory));
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
      
      const requestBody: any = { 
        prompt: prompt.trim(), 
        model: selectedModel,
        aspect_ratio: selectedSize
      };
      
      if (uploadedImage) {
        requestBody.base_image = uploadedImage;
      }

      const response = await fetch(`${API_BASE_URL}/image/generate`, {
        method: 'POST',
        headers,
        body: JSON.stringify(requestBody)
      });

      const data = await response.json();
      if (data.success && data.image_url) {
        setImageUrl(data.image_url);
        saveToHistory(data.image_url);
        loadQuota();
      } else {
        setError(data.error || '图片生成失败');
      }
    } catch (err: any) {
      setError('网络错误: ' + err.message);
    } finally {
      setLoading(false);
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

          {/* 提示词输入 */}
          <div className="form-group">
            <label>
              {uploadedImage ? '📝 描述修改内容' : '💭 描述你的创意'}
            </label>
            <textarea 
              value={prompt} 
              onChange={(e) => setPrompt(e.target.value)} 
              placeholder={uploadedImage ? "例如：将背景改为日落海滩..." : "例如：一只可爱的橘猫在阳光下打盹..."} 
              disabled={loading}
              rows={4}
            />
          </div>

          {/* 模型选择 */}
          <div className="form-group">
            <label>🤖 选择模型</label>
            <div className="model-grid">
              {IMAGE_MODELS.map(model => (
                <div
                  key={model.id}
                  className={`model-card ${selectedModel === model.id ? 'selected' : ''}`}
                  onClick={() => !loading && setSelectedModel(model.id)}
                >
                  <div className="model-name">{model.name}</div>
                  <div className="model-desc">{model.desc}</div>
                  {model.badge && <span className="model-badge">{model.badge}</span>}
                </div>
              ))}
            </div>
          </div>

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

          {/* 图片上传 */}
          <div className="form-group">
            <label>🖼️ 上传参考图（可选）</label>
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
              >
                📤 点击上传图片
              </button>
            ) : (
              <div className="uploaded-preview">
                <img src={uploadedImage} alt="Uploaded" />
                <button
                  className="remove-btn"
                  onClick={removeUploadedImage}
                  disabled={loading}
                >
                  ✕
                </button>
              </div>
            )}
          </div>

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
            {loading ? '⏳ 生成中...' : uploadedImage ? '✏️ 开始编辑' : '🎨 生成图片'}
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
            <div className="action-bar">
              <button className="action-btn primary" onClick={downloadImage}>
                📥 下载图片
              </button>
              <button className="action-btn" onClick={() => { setImageUrl(null); setError(null); }}>
                🔄 重新生成
              </button>
            </div>
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
          grid-template-columns: 1fr 1fr;
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
