import { useState, useEffect } from 'react';

const API_BASE_URL = import.meta.env.VITE_API_BASE_URL;

interface GalleryImage {
  id: number;
  user_id: number;
  username: string;
  model: string;
  llm_model?: string;
  prompt: string;
  image_url: string;
  views: number;
  likes: number;
  tags?: string;
  created_at: string;
}

interface GalleryProps {
  isAuthenticated: boolean;
  userId?: number;
  onGenerateAgain?: (prompt: string, model: string, imageUrl: string) => void;
}

function ImageGallery({ isAuthenticated, userId, onGenerateAgain }: GalleryProps) {
  const [images, setImages] = useState<GalleryImage[]>([]);
  const [loading, setLoading] = useState(false);
  const [viewMode, setViewMode] = useState<'all' | 'my'>('all');
  const [searchKeyword, setSearchKeyword] = useState('');
  const [suggestions, setSuggestions] = useState<Array<{text: string, type: string, count: number}>>([]);
  const [showSuggestions, setShowSuggestions] = useState(false);

  const loadSuggestions = async (keyword: string) => {
    if (!keyword || keyword.length < 1) {
      setSuggestions([]);
      return;
    }

    try {
      const response = await fetch(
        `${API_BASE_URL}/gallery/suggestions?keyword=${encodeURIComponent(keyword)}&limit=8`
      );
      const data = await response.json();

      if (data.success) {
        setSuggestions(data.data);
      }
    } catch (err) {
      console.error('Failed to load suggestions:', err);
    }
  };

  const loadGallery = async (mode: 'all' | 'my' = 'all', keyword: string = '') => {
    setLoading(true);
    try {
      const token = localStorage.getItem('auth_token');
      const headers: Record<string, string> = { 'Content-Type': 'application/json' };
      if (token) {
        headers['Authorization'] = `Bearer ${token}`;
      }

      let url = '';
      if (keyword) {
        url = `${API_BASE_URL}/gallery/search?keyword=${encodeURIComponent(keyword)}&page=1&limit=100`;
      } else if (mode === 'my' && userId) {
        url = `${API_BASE_URL}/gallery/user/${userId}?page=1&limit=100`;
      } else {
        url = `${API_BASE_URL}/gallery/public?page=1&limit=100`;
      }

      const response = await fetch(url, { headers });
      const data = await response.json();

      if (data.success) {
        setImages(data.data.data);
      }
    } catch (err) {
      console.error('Failed to load gallery:', err);
    } finally {
      setLoading(false);
    }
  };

  useEffect(() => {
    loadGallery(viewMode, searchKeyword);
  }, [viewMode, userId]);

  const handleSearch = (e: React.FormEvent) => {
    e.preventDefault();
    setShowSuggestions(false);
    loadGallery('all', searchKeyword);
  };

  const handleSuggestionClick = (suggestion: string) => {
    setSearchKeyword(suggestion);
    setShowSuggestions(false);
    loadGallery('all', suggestion);
  };

  const handleLike = async (imageId: number) => {
    try {
      const token = localStorage.getItem('auth_token');
      const headers: Record<string, string> = { 'Content-Type': 'application/json' };
      if (token) {
        headers['Authorization'] = `Bearer ${token}`;
      }

      const response = await fetch(`${API_BASE_URL}/gallery/image/${imageId}/like`, {
        method: 'POST',
        headers
      });

      const data = await response.json();
      if (data.success) {
        setImages(images.map(img =>
          img.id === imageId ? { ...img, likes: img.likes + 1 } : img
        ));
      }
    } catch (err) {
      console.error('Failed to like image:', err);
    }
  };

  const downloadImage = (imageUrl: string, filename: string) => {
    if (imageUrl.startsWith('data:')) {
      const arr = imageUrl.split(',');
      const mime = arr[0].match(/:(.*?);/)?.[1] || 'image/png';
      const bstr = atob(arr[1]);
      const u8arr = new Uint8Array(bstr.length);
      for (let i = 0; i < bstr.length; i++) {
        u8arr[i] = bstr.charCodeAt(i);
      }
      const blob = new Blob([u8arr], { type: mime });
      const url = URL.createObjectURL(blob);

      const a = document.createElement('a');
      a.href = url;
      a.download = filename;
      a.click();
      URL.revokeObjectURL(url);
    } else {
      const a = document.createElement('a');
      a.href = imageUrl;
      a.download = filename;
      a.click();
    }
  };

  return (
    <div style={{ padding: '20px', maxWidth: '1400px', margin: '0 auto' }}>
      <div style={{ marginBottom: '30px' }}>
        <h1 style={{ margin: '0 0 10px 0', fontSize: '32px', fontWeight: 700 }}>🎨 图片库</h1>
        <p style={{ margin: 0, color: '#666', fontSize: '14px' }}>浏览和下载所有生成的图片</p>
      </div>

      <div style={{ marginBottom: '30px', display: 'flex', gap: '20px', flexWrap: 'wrap', alignItems: 'flex-start' }}>
        <form onSubmit={handleSearch} style={{ flex: 1, minWidth: '300px', display: 'flex', gap: '10px', position: 'relative' }}>
          <div style={{ flex: 1, position: 'relative' }}>
            <input
              type="text"
              value={searchKeyword}
              onChange={(e) => {
                setSearchKeyword(e.target.value);
                loadSuggestions(e.target.value);
                setShowSuggestions(true);
              }}
              onFocus={(e) => {
                searchKeyword && setShowSuggestions(true);
                e.currentTarget.style.borderColor = '#3b82f6';
              }}
              onBlur={(e) => {
                setTimeout(() => setShowSuggestions(false), 200);
                e.currentTarget.style.borderColor = '#e5e7eb';
              }}
              placeholder="搜索提示词或标签..."
              style={{
                width: '100%',
                padding: '12px',
                border: '2px solid #e5e7eb',
                borderRadius: '8px',
                fontSize: '14px',
                outline: 'none'
              }}
            />
            
            {showSuggestions && suggestions.length > 0 && (
              <div style={{
                position: 'absolute',
                top: '100%',
                left: 0,
                right: 0,
                background: 'white',
                border: '1px solid #e5e7eb',
                borderTop: 'none',
                borderRadius: '0 0 8px 8px',
                maxHeight: '300px',
                overflowY: 'auto',
                zIndex: 10,
                boxShadow: '0 4px 6px rgba(0,0,0,0.1)'
              }}>
                {suggestions.map((suggestion, idx) => (
                  <div
                    key={idx}
                    onClick={() => handleSuggestionClick(suggestion.text)}
                    style={{
                      padding: '12px',
                      borderBottom: idx < suggestions.length - 1 ? '1px solid #f3f4f6' : 'none',
                      cursor: 'pointer',
                      display: 'flex',
                      justifyContent: 'space-between',
                      alignItems: 'center',
                      transition: 'background 0.2s'
                    }}
                    onMouseEnter={(e) => e.currentTarget.style.background = '#f9fafb'}
                    onMouseLeave={(e) => e.currentTarget.style.background = 'white'}
                  >
                    <div>
                      <span style={{ fontSize: '14px', color: '#374151' }}>
                        {suggestion.text}
                      </span>
                      <span style={{
                        fontSize: '12px',
                        color: '#9ca3af',
                        marginLeft: '8px',
                        background: suggestion.type === 'tag' ? '#dbeafe' : '#fef3c7',
                        padding: '2px 6px',
                        borderRadius: '4px'
                      }}>
                        {suggestion.type === 'tag' ? '标签' : '关键词'}
                      </span>
                    </div>
                    <span style={{ fontSize: '12px', color: '#9ca3af' }}>
                      {suggestion.count}
                    </span>
                  </div>
                ))}
              </div>
            )}
          </div>
          <button
            type="submit"
            style={{
              padding: '12px 24px',
              background: '#3b82f6',
              color: 'white',
              border: 'none',
              borderRadius: '8px',
              fontSize: '14px',
              fontWeight: 600,
              cursor: 'pointer'
            }}
          >
            🔍 搜索
          </button>
        </form>

        <div style={{ display: 'flex', gap: '10px' }}>
          <button
            onClick={() => setViewMode('all')}
            style={{
              padding: '12px 20px',
              background: viewMode === 'all' ? '#3b82f6' : '#f3f4f6',
              color: viewMode === 'all' ? 'white' : '#374151',
              border: 'none',
              borderRadius: '8px',
              fontSize: '14px',
              fontWeight: 600,
              cursor: 'pointer'
            }}
          >
            📸 全部图片
          </button>
          {isAuthenticated && (
            <button
              onClick={() => setViewMode('my')}
              style={{
                padding: '12px 20px',
                background: viewMode === 'my' ? '#3b82f6' : '#f3f4f6',
                color: viewMode === 'my' ? 'white' : '#374151',
                border: 'none',
                borderRadius: '8px',
                fontSize: '14px',
                fontWeight: 600,
                cursor: 'pointer'
              }}
            >
              👤 我的图片
            </button>
          )}
        </div>
      </div>

      {loading && (
        <div style={{ textAlign: 'center', padding: '40px' }}>
          <div style={{
            width: '40px',
            height: '40px',
            border: '3px solid #f0f0f0',
            borderTopColor: '#3b82f6',
            borderRadius: '50%',
            animation: 'spin 1s linear infinite',
            margin: '0 auto 16px'
          }} />
          <div style={{ color: '#666' }}>加载中...</div>
        </div>
      )}

      {!loading && images.length > 0 && (
        <div style={{
          display: 'grid',
          gridTemplateColumns: 'repeat(auto-fill, minmax(250px, 1fr))',
          gap: '20px',
          marginBottom: '30px'
        }}>
          {images.map((image) => (
            <div
              key={image.id}
              style={{
                background: 'white',
                borderRadius: '12px',
                overflow: 'hidden',
                boxShadow: '0 2px 8px rgba(0,0,0,0.1)',
                display: 'flex',
                flexDirection: 'column'
              }}
            >
              <img
                src={image.image_url}
                alt={image.prompt}
                style={{
                  width: '100%',
                  height: '200px',
                  objectFit: 'cover',
                  cursor: 'pointer'
                }}
              />
              <div style={{ padding: '12px', flex: 1, display: 'flex', flexDirection: 'column' }}>
                <p style={{ margin: '0 0 8px 0', fontSize: '14px', fontWeight: 600 }}>
                  {image.model}
                </p>
                <p style={{ margin: '0 0 12px 0', fontSize: '12px', color: '#666', lineHeight: '1.4', flex: 1 }}>
                  {image.prompt.substring(0, 50)}...
                </p>
                <div style={{ display: 'flex', gap: '8px', marginTop: 'auto' }}>
                  <button
                    onClick={() => downloadImage(image.image_url, `image-${image.id}.png`)}
                    style={{
                      flex: 1,
                      padding: '8px',
                      background: '#3b82f6',
                      color: 'white',
                      border: 'none',
                      borderRadius: '6px',
                      fontSize: '12px',
                      fontWeight: 600,
                      cursor: 'pointer',
                      transition: 'background 0.2s'
                    }}
                    onMouseEnter={(e) => e.currentTarget.style.background = '#2563eb'}
                    onMouseLeave={(e) => e.currentTarget.style.background = '#3b82f6'}
                  >
                    📥 下载
                  </button>
                  <button
                    onClick={() => handleLike(image.id)}
                    style={{
                      flex: 1,
                      padding: '8px',
                      background: '#f3f4f6',
                      color: '#374151',
                      border: 'none',
                      borderRadius: '6px',
                      fontSize: '12px',
                      fontWeight: 600,
                      cursor: 'pointer',
                      transition: 'background 0.2s'
                    }}
                    onMouseEnter={(e) => e.currentTarget.style.background = '#e5e7eb'}
                    onMouseLeave={(e) => e.currentTarget.style.background = '#f3f4f6'}
                  >
                    ❤️ {image.likes}
                  </button>
                  {onGenerateAgain && (
                    <button
                      onClick={() => {
                        let modelId = image.model;
                        if (!modelId.includes('/')) {
                          modelId = 'alibaba/' + modelId;
                        }
                        onGenerateAgain(image.prompt, modelId, image.image_url);
                      }}
                      style={{
                        flex: 1,
                        padding: '8px',
                        background: '#10b981',
                        color: 'white',
                        border: 'none',
                        borderRadius: '6px',
                        fontSize: '12px',
                        fontWeight: 600,
                        cursor: 'pointer',
                        transition: 'background 0.2s'
                      }}
                      onMouseEnter={(e) => e.currentTarget.style.background = '#059669'}
                      onMouseLeave={(e) => e.currentTarget.style.background = '#10b981'}
                    >
                      🔄 再生
                    </button>
                  )}
                </div>
              </div>
            </div>
          ))}
        </div>
      )}

      {!loading && images.length === 0 && (
        <div style={{ textAlign: 'center', padding: '60px 20px', color: '#999' }}>
          <div style={{ fontSize: '48px', marginBottom: '16px' }}>📭</div>
          <div style={{ fontSize: '16px', fontWeight: 600 }}>暂无图片</div>
          <div style={{ fontSize: '14px', marginTop: '8px' }}>
            {searchKeyword ? '搜索结果为空' : '开始生成图片吧'}
          </div>
        </div>
      )}

      <style>{`
        @keyframes spin {
          0% { transform: rotate(0deg); }
          100% { transform: rotate(360deg); }
        }
      `}</style>
    </div>
  );
}

export default ImageGallery;
