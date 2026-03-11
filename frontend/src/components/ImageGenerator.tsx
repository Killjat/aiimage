import { useState, useEffect, useRef } from 'react';

const API_BASE_URL = import.meta.env.VITE_API_BASE_URL;

interface ImageGeneratorProps {
  onClose: () => void;
  isAuthenticated: boolean;
  onShowLogin?: () => void;
}

const DEFAULT_IMAGE_MODELS = [
  // 阿里模型
  { id: 'alibaba-wan2.6-t2i', name: '万相 2.6', badge: '最新', provider: 'alibaba' },
  { id: 'alibaba-qwen-image-2.0-pro', name: '千问 2.0 Pro', badge: '最强', provider: 'alibaba' },
  { id: 'alibaba-qwen-image-2.0', name: '千问 2.0', badge: '平衡', provider: 'alibaba' },
  { id: 'alibaba-qwen-image-max', name: '千问 Max', badge: '质量', provider: 'alibaba' },
  { id: 'alibaba-qwen-image-plus', name: '千问 Plus', badge: '高质', provider: 'alibaba' },
  { id: 'alibaba-qwen-image', name: '千问图像', badge: '经典', provider: 'alibaba' },
  // OpenRouter Flux 模型
  { id: 'black-forest-labs/flux.2-pro', name: 'Flux 2 Pro', badge: '推荐', provider: 'openrouter' },
  { id: 'black-forest-labs/flux.2-flex', name: 'Flux 2 Flex', badge: '快速', provider: 'openrouter' },
];

function ImageGenerator({ onClose, isAuthenticated, onShowLogin }: ImageGeneratorProps) {
  const [prompt, setPrompt] = useState('');
  const [imageModels, setImageModels] = useState(DEFAULT_IMAGE_MODELS);
  const [selectedModel, setSelectedModel] = useState(DEFAULT_IMAGE_MODELS[0].id);
  const [loading, setLoading] = useState(false);
  const [imageUrl, setImageUrl] = useState<string | null>(null);
  const [error, setError] = useState<string | null>(null);
  const [quota, setQuota] = useState<{ total: number; used: number; remaining: number } | null>(null);
  const [uploadedImage, setUploadedImage] = useState<string | null>(null);
  const fileInputRef = useRef<HTMLInputElement>(null);

  // 加载图片生成模型列表
  const loadImageModels = async () => {
    try {
      const response = await fetch(`${API_BASE_URL}/image/models`);
      const data = await response.json();
      if (data.success && data.models && data.models.length > 0) {
        const models = data.models.map((m: any) => ({
          id: m.id,
          name: m.name || m.id,
          badge: m.provider === 'alibaba' ? '阿里' : m.badge || '推荐',
          provider: m.provider
        }));
        setImageModels(models);
        setSelectedModel(models[0].id);
      }
    } catch (err) {
      console.error('Failed to load image models:', err);
      // 使用默认模型
      setImageModels(DEFAULT_IMAGE_MODELS);
      setSelectedModel(DEFAULT_IMAGE_MODELS[0].id);
    }
  };

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
    loadImageModels();
    loadQuota();
  }, []);

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
      
      const currentModel = imageModels.find(m => m.id === selectedModel);
      const isAlibaba = currentModel?.provider === 'alibaba';
      
      if (isAlibaba) {
        // 调用阿里百练 API
        const alibabaModel = selectedModel.replace('alibaba-', '');
        const requestBody: any = {
          prompt: prompt.trim(),
          model: alibabaModel,
          size: '1024*1024'
        };

        const response = await fetch(`${API_BASE_URL}/image/generate/bailian`, {
          method: 'POST',
          headers,
          body: JSON.stringify(requestBody)
        });

        const data = await response.json();
        if (data.success && data.images && data.images.length > 0) {
          setImageUrl(data.images[0]);
          loadQuota();
        } else {
          setError(data.error || '图片生成失败');
        }
      } else {
        // 调用 OpenRouter API（Flux 模型）
        const requestBody: any = { 
          prompt: prompt.trim(), 
          model: selectedModel 
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

  const downloadImage = () => {
    if (!imageUrl) return;
    
    // 处理 base64 data URL
    if (imageUrl.startsWith('data:')) {
      // 将 base64 转换为 Blob
      const arr = imageUrl.split(',');
      const mime = arr[0].match(/:(.*?);/)?.[1] || 'image/png';
      const bstr = atob(arr[1]);
      const n = bstr.length;
      const u8arr = new Uint8Array(n);
      for (let i = 0; i < n; i++) {
        u8arr[i] = bstr.charCodeAt(i);
      }
      const blob = new Blob([u8arr], { type: mime });
      const url = URL.createObjectURL(blob);
      
      const a = document.createElement('a');
      a.href = url;
      a.download = `ai-image-${Date.now()}.png`;
      document.body.appendChild(a);
      a.click();
      document.body.removeChild(a);
      
      // 释放 URL
      URL.revokeObjectURL(url);
    } else {
      // 处理 HTTP URL
      const a = document.createElement('a');
      a.href = imageUrl;
      a.download = `ai-image-${Date.now()}.png`;
      document.body.appendChild(a);
      a.click();
      document.body.removeChild(a);
    }
  };

  return (
    <div style={{ position: 'fixed', top: 0, left: 0, right: 0, bottom: 0, background: 'rgba(0,0,0,0.7)', display: 'flex', alignItems: 'center', justifyContent: 'center', zIndex: 1000 }} onClick={onClose}>
      <div style={{ background: '#fff', borderRadius: '16px', maxWidth: '900px', width: '90%', maxHeight: '90vh', display: 'flex', overflow: 'hidden', boxShadow: '0 20px 60px rgba(0,0,0,0.3)' }} onClick={(e) => e.stopPropagation()}>
        
        <div style={{ width: '350px', background: '#f8f9fa', padding: '20px', overflowY: 'auto', borderRight: '1px solid #e0e0e0' }}>
          <h2 style={{ margin: '0 0 8px 0', fontSize: '22px', fontWeight: 600 }}>🎨 AI 图片生成</h2>
          <p style={{ margin: '0 0 12px 0', fontSize: '12px', color: '#666' }}>使用 AI 创造视觉作品</p>
          
          {quota && (
            <div style={{
              marginBottom: '20px',
              padding: '12px',
              background: quota.remaining > 0 ? '#e8f5e9' : '#fff3e0',
              borderRadius: '8px',
              border: `1px solid ${quota.remaining > 0 ? '#4caf50' : '#ff9800'}`
            }}>
              <div style={{ fontSize: '12px', fontWeight: 600, marginBottom: '6px', color: '#333' }}>
                {isAuthenticated ? '🎟️ 我的配额' : '👤 游客配额'}
              </div>
              <div style={{ fontSize: '20px', fontWeight: 700, color: quota.remaining > 0 ? '#4caf50' : '#ff9800' }}>
                {quota.remaining} / {quota.total}
              </div>
              <div style={{ fontSize: '11px', color: '#666', marginTop: '4px' }}>
                {isAuthenticated 
                  ? `已使用 ${quota.used} 张` 
                  : quota.remaining > 0 
                    ? '登录后可获得更多配额' 
                    : '配额已用完，请登录获取更多'}
              </div>
              {!isAuthenticated && quota.remaining <= 0 && onShowLogin && (
                <button
                  onClick={onShowLogin}
                  style={{
                    marginTop: '8px',
                    width: '100%',
                    padding: '8px',
                    background: '#1a73e8',
                    color: 'white',
                    border: 'none',
                    borderRadius: '6px',
                    fontSize: '12px',
                    fontWeight: 600,
                    cursor: 'pointer'
                  }}
                >
                  立即登录
                </button>
              )}
            </div>
          )}

          <div style={{ marginBottom: '16px' }}>
            <label style={{ display: 'block', fontSize: '11px', fontWeight: 600, marginBottom: '6px', textTransform: 'uppercase' }}>
              {uploadedImage ? '📝 描述修改' : '💭 描述创意'}
            </label>
            <textarea 
              value={prompt} 
              onChange={(e) => setPrompt(e.target.value)} 
              placeholder={uploadedImage ? "描述你想要的修改..." : "例如：一只可爱的猫"} 
              disabled={loading} 
              style={{ width: '100%', minHeight: '80px', padding: '10px', border: '2px solid #e0e0e0', borderRadius: '8px', fontSize: '13px', resize: 'vertical', outline: 'none', boxSizing: 'border-box' }} 
            />
          </div>

          <div style={{ marginBottom: '16px' }}>
            <label style={{ display: 'block', fontSize: '11px', fontWeight: 600, marginBottom: '6px', textTransform: 'uppercase' }}>🖼️ 上传图片（可选）</label>
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
                onClick={() => fileInputRef.current?.click()}
                disabled={loading}
                style={{
                  width: '100%',
                  padding: '12px',
                  background: '#f0f0f0',
                  border: '2px dashed #ccc',
                  borderRadius: '8px',
                  fontSize: '13px',
                  color: '#666',
                  cursor: loading ? 'not-allowed' : 'pointer',
                  display: 'flex',
                  alignItems: 'center',
                  justifyContent: 'center',
                  gap: '8px'
                }}
              >
                📤 点击上传图片
              </button>
            ) : (
              <div style={{ position: 'relative', border: '2px solid #e0e0e0', borderRadius: '8px', overflow: 'hidden' }}>
                <img 
                  src={uploadedImage} 
                  alt="Uploaded" 
                  style={{ width: '100%', height: 'auto', display: 'block' }} 
                />
                <button
                  onClick={removeUploadedImage}
                  disabled={loading}
                  style={{
                    position: 'absolute',
                    top: '8px',
                    right: '8px',
                    width: '28px',
                    height: '28px',
                    borderRadius: '50%',
                    border: 'none',
                    background: 'rgba(0,0,0,0.6)',
                    color: 'white',
                    cursor: loading ? 'not-allowed' : 'pointer',
                    fontSize: '16px',
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

          <div style={{ marginBottom: '16px' }}>
            <label style={{ display: 'block', fontSize: '11px', fontWeight: 600, marginBottom: '6px', textTransform: 'uppercase' }}>🤖 模型</label>
            <select 
              value={selectedModel} 
              onChange={(e) => setSelectedModel(e.target.value)} 
              disabled={loading} 
              style={{ width: '100%', padding: '8px', border: '2px solid #e0e0e0', borderRadius: '6px', fontSize: '12px', background: '#fff', cursor: 'pointer', outline: 'none' }}
            >
              {imageModels.map(m => <option key={m.id} value={m.id}>{m.name} [{m.badge}]</option>)}
            </select>
          </div>

          {error && (
            <div style={{ marginBottom: '16px', padding: '10px', background: '#fee', border: '1px solid #fcc', borderRadius: '6px', fontSize: '12px', color: '#c33' }}>
              {error}
            </div>
          )}

          <button 
            onClick={generateImage} 
            disabled={loading || !prompt.trim()} 
            style={{ 
              width: '100%', 
              padding: '12px', 
              background: (loading || !prompt.trim()) ? '#ccc' : 'linear-gradient(135deg, #667eea 0%, #764ba2 100%)', 
              color: (loading || !prompt.trim()) ? '#999' : '#fff', 
              border: 'none', 
              borderRadius: '8px', 
              fontSize: '14px', 
              fontWeight: 600, 
              cursor: (loading || !prompt.trim()) ? 'not-allowed' : 'pointer', 
              boxShadow: (loading || !prompt.trim()) ? 'none' : '0 4px 12px rgba(102,126,234,0.4)' 
            }}
          >
            {loading ? '⏳ 生成中...' : uploadedImage ? '✏️ 开始编辑' : '🎨 生成图片'}
          </button>
        </div>

        <div style={{ flex: 1, display: 'flex', flexDirection: 'column' }}>
          <div style={{ padding: '12px 20px', borderBottom: '1px solid #e0e0e0', display: 'flex', justifyContent: 'space-between', alignItems: 'center' }}>
            <div style={{ fontSize: '12px', color: '#666', fontWeight: 500 }}>
              {imageUrl ? '✅ 完成' : loading ? '⏳ 生成中...' : '👈 开始'}
            </div>
            <button onClick={onClose} style={{ width: '28px', height: '28px', borderRadius: '50%', border: 'none', background: '#f0f0f0', cursor: 'pointer', fontSize: '14px', display: 'flex', alignItems: 'center', justifyContent: 'center' }}>✕</button>
          </div>

          <div style={{ flex: 1, display: 'flex', alignItems: 'center', justifyContent: 'center', padding: '20px', background: '#fafafa' }}>
            {loading && !error && (
              <div style={{ textAlign: 'center' }}>
                <div style={{ width: '40px', height: '40px', border: '3px solid #f0f0f0', borderTopColor: '#667eea', borderRadius: '50%', animation: 'spin 1s linear infinite', margin: '0 auto 16px' }} />
                <div style={{ fontSize: '14px', color: '#666', fontWeight: 500 }}>AI 创作中...</div>
              </div>
            )}
            {imageUrl && !loading && !error && (
              <div style={{ maxWidth: '100%', maxHeight: '100%' }}>
                <img src={imageUrl} alt="Generated" style={{ maxWidth: '100%', maxHeight: 'calc(90vh - 120px)', borderRadius: '10px', boxShadow: '0 6px 24px rgba(0,0,0,0.2)' }} />
              </div>
            )}
            {!loading && !imageUrl && !error && (
              <div style={{ textAlign: 'center', color: '#999' }}>
                <div style={{ fontSize: '48px', marginBottom: '16px', opacity: 0.3 }}>🎨</div>
                <div style={{ fontSize: '14px', fontWeight: 500, marginBottom: '8px' }}>准备创作</div>
                <div style={{ fontSize: '12px', color: '#666', lineHeight: '1.4' }}>在左侧输入描述，选择模型，点击生成</div>
              </div>
            )}
          </div>

          {imageUrl && !loading && (
            <div style={{ padding: '12px 20px', borderTop: '1px solid #e0e0e0', display: 'flex', gap: '8px' }}>
              <button onClick={downloadImage} style={{ flex: 1, padding: '10px', background: '#1a73e8', color: '#fff', border: 'none', borderRadius: '6px', fontSize: '13px', fontWeight: 600, cursor: 'pointer' }}>📥 下载</button>
              <button onClick={() => { setImageUrl(null); setError(null); }} style={{ flex: 1, padding: '10px', background: '#f0f0f0', color: '#333', border: 'none', borderRadius: '6px', fontSize: '13px', fontWeight: 600, cursor: 'pointer' }}>🔄 重新生成</button>
            </div>
          )}
        </div>
      </div>

      <style>{`
        @keyframes spin {
          0% { transform: rotate(0deg); }
          100% { transform: rotate(360deg); }
        }
      `}</style>
    </div>
  );
}

export default ImageGenerator;
