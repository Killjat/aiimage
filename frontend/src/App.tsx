import { useState, useEffect, useRef } from 'react';
import ImageGeneratorNew from './components/ImageGeneratorNew';
import ClawHubNews from './components/ClawHubNews';
import SmartAnalysis from './components/SmartAnalysis';
import './App.mobile.css';

// 从环境变量读取 API URL，生产环境必须配置
const API_BASE_URL = import.meta.env.VITE_API_BASE_URL;

if (!API_BASE_URL) {
  console.error('错误: VITE_API_BASE_URL 环境变量未配置！请在 .env 文件中设置。');
}

interface AppProps {
  isAuthenticated: boolean;
  onLogout?: () => void;
  onShowLogin?: () => void;
}

function App({ isAuthenticated, onLogout, onShowLogin }: AppProps) {
  const [messages, setMessages] = useState(() => {
    const saved = localStorage.getItem('chat_messages');
    return saved ? JSON.parse(saved) : [];
  });
  const [input, setInput] = useState('');
  const [loading, setLoading] = useState(false);
  const [models, setModels] = useState([]);
  const [selectedModel, setSelectedModel] = useState(() => {
    return localStorage.getItem('selected_model') || 'auto';
  });
  const [backendOnline, setBackendOnline] = useState(false);
  const [currentView, setCurrentView] = useState<'home' | 'chat' | 'image' | 'analysis'>('home');
  const [showSettings, setShowSettings] = useState(false);
  const messagesEndRef = useRef<HTMLDivElement>(null);

  // 保存消息到 localStorage
  useEffect(() => {
    localStorage.setItem('chat_messages', JSON.stringify(messages));
  }, [messages]);

  // 保存选中的模型
  useEffect(() => {
    localStorage.setItem('selected_model', selectedModel);
  }, [selectedModel]);

  // 自动滚动到底部
  useEffect(() => {
    messagesEndRef.current?.scrollIntoView({ behavior: 'smooth' });
  }, [messages, loading]);

  // 检查后端状态并获取模型列表
  useEffect(() => {
    fetch(`${API_BASE_URL}/health`)
      .then(res => res.json())
      .then(data => {
        setBackendOnline(data.status === 'ok');
        if (data.status === 'ok') {
          // 获取模型列表（只获取适合聊天的模型）
          return fetch(`${API_BASE_URL}/models?chat_only=true`);
        }
      })
      .then(res => res?.json())
      .then(data => {
        if (data?.success && data?.models) {
          // 只显示适合聊天的模型
          setModels(data.models);
        }
      })
      .catch(() => setBackendOnline(false));
  }, []);

  const sendMessage = async () => {
    if (!input.trim() || loading) return;

    const userMessage = { role: 'user', content: input };
    setMessages([...messages, userMessage]);
    setInput('');
    setLoading(true);

    try {
      const response = await fetch(`${API_BASE_URL}/chat/send`, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({
          model: selectedModel === 'auto' ? 'auto' : selectedModel,
          messages: [...messages, userMessage]
        })
      });

      const data = await response.json();
      
      if (data.success && data.message) {
        setMessages((prev: any) => [...prev, { role: 'assistant', content: data.message }]);
      } else {
        alert('错误: ' + (data.error || '发送失败'));
      }
    } catch (error: any) {
      alert('网络错误: ' + error.message);
    } finally {
      setLoading(false);
    }
  };

  const clearHistory = () => {
    if (confirm('确定要清除所有对话历史吗？')) {
      setMessages([]);
      localStorage.removeItem('chat_messages');
    }
  };

  const handleLogout = () => {
    if (confirm('确定要退出登录吗？')) {
      onLogout?.();
    }
  };

  // 获取用户信息
  const getUserInfo = () => {
    const userStr = localStorage.getItem('user');
    if (userStr) {
      try {
        return JSON.parse(userStr);
      } catch {
        return null;
      }
    }
    return null;
  };

  const user = getUserInfo();

  return (
    <div style={{ 
      display: 'flex', 
      flexDirection: 'column', 
      height: '100vh', 
      background: '#fff',
      fontFamily: '-apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif'
    }}>
      {/* 顶部导航栏 */}
      <header style={{ 
        display: 'flex', 
        alignItems: 'center', 
        justifyContent: 'space-between',
        padding: '12px 24px',
        borderBottom: '1px solid #e8eaed',
        background: 'white'
      }}>
        <div style={{ display: 'flex', alignItems: 'center', gap: '16px' }}>
          <h1 
            onClick={() => setCurrentView('home')}
            style={{ 
              margin: 0, 
              fontSize: '20px', 
              fontWeight: 400,
              color: '#202124',
              letterSpacing: '-0.5px',
              cursor: 'pointer'
            }}
          >
            🏠 ClawHub 技能
          </h1>
          <div style={{ 
            display: 'flex',
            alignItems: 'center',
            gap: '6px',
            fontSize: '13px',
            color: backendOnline ? '#1a73e8' : '#ea4335',
            background: backendOnline ? '#e8f0fe' : '#fce8e6',
            padding: '4px 10px',
            borderRadius: '12px'
          }}>
            <div style={{ 
              width: '6px', 
              height: '6px', 
              borderRadius: '50%', 
              background: backendOnline ? '#1a73e8' : '#ea4335'
            }} />
            {backendOnline ? '在线' : '离线'}
          </div>
        </div>
        
        <div style={{ display: 'flex', alignItems: 'center', gap: '8px' }}>
          {/* 导航按钮 */}
          <button
            onClick={() => setCurrentView('chat')}
            style={{
              padding: '8px 16px',
              background: currentView === 'chat' ? '#e8f0fe' : 'transparent',
              color: currentView === 'chat' ? '#1a73e8' : '#5f6368',
              border: '1px solid #dadce0',
              borderRadius: '20px',
              cursor: 'pointer',
              fontSize: '14px',
              fontWeight: 500,
              transition: 'all 0.2s',
              display: 'flex',
              alignItems: 'center',
              gap: '6px'
            }}
          >
            💬 AI 聊天
          </button>
          
          <button
            onClick={() => setCurrentView('image')}
            style={{
              padding: '8px 16px',
              background: currentView === 'image' ? '#e8f0fe' : 'transparent',
              color: currentView === 'image' ? '#1a73e8' : '#5f6368',
              border: '1px solid #dadce0',
              borderRadius: '20px',
              cursor: 'pointer',
              fontSize: '14px',
              fontWeight: 500,
              transition: 'all 0.2s',
              display: 'flex',
              alignItems: 'center',
              gap: '6px'
            }}
          >
            🎨 生成图片
          </button>
          
          <button
            onClick={() => setCurrentView('analysis')}
            style={{
              padding: '8px 16px',
              background: currentView === 'analysis' ? '#e8f0fe' : 'transparent',
              color: currentView === 'analysis' ? '#1a73e8' : '#5f6368',
              border: '1px solid #dadce0',
              borderRadius: '20px',
              cursor: 'pointer',
              fontSize: '14px',
              fontWeight: 500,
              transition: 'all 0.2s',
              display: 'flex',
              alignItems: 'center',
              gap: '6px'
            }}
          >
            🔍 智能分析
          </button>
          
          <div style={{ width: '1px', height: '24px', background: '#dadce0', margin: '0 4px' }} />
          
          {isAuthenticated ? (
            <>
              {user && (
                <div style={{
                  padding: '6px 12px',
                  background: '#f8f9fa',
                  borderRadius: '16px',
                  fontSize: '13px',
                  color: '#5f6368',
                  display: 'flex',
                  alignItems: 'center',
                  gap: '6px'
                }}>
                  <span>👤</span>
                  <span>{user.username || user.email}</span>
                </div>
              )}
            </>
          ) : (
            <button
              onClick={onShowLogin}
              style={{
                padding: '8px 16px',
                background: '#1a73e8',
                color: 'white',
                border: 'none',
                borderRadius: '20px',
                cursor: 'pointer',
                fontSize: '14px',
                fontWeight: 500,
                transition: 'all 0.2s'
              }}
              onMouseEnter={(e) => {
                e.currentTarget.style.background = '#1557b0';
              }}
              onMouseLeave={(e) => {
                e.currentTarget.style.background = '#1a73e8';
              }}
            >
              登录 / 注册
            </button>
          )}
          
          {currentView === 'chat' && (
            <>
              <button
                onClick={() => setShowSettings(!showSettings)}
                style={{
                  padding: '8px 16px',
                  background: 'transparent',
                  color: '#5f6368',
                  border: '1px solid #dadce0',
                  borderRadius: '20px',
                  cursor: 'pointer',
                  fontSize: '14px',
                  fontWeight: 500,
                  transition: 'all 0.2s',
                  display: 'flex',
                  alignItems: 'center',
                  gap: '6px'
                }}
              >
                ⚙️ 设置
              </button>
              
              {messages.length > 0 && (
                <button
                  onClick={clearHistory}
                  style={{
                    padding: '8px 16px',
                    background: 'transparent',
                    color: '#5f6368',
                    border: '1px solid #dadce0',
                    borderRadius: '20px',
                    cursor: 'pointer',
                    fontSize: '14px',
                    fontWeight: 500,
                    transition: 'all 0.2s'
                  }}
                >
                  清除对话
                </button>
              )}
            </>
          )}
          
          {isAuthenticated && onLogout && (
            <button
              onClick={handleLogout}
              style={{
                padding: '8px 16px',
                background: 'transparent',
                color: '#5f6368',
                border: '1px solid #dadce0',
                borderRadius: '20px',
                cursor: 'pointer',
                fontSize: '14px',
                fontWeight: 500,
                transition: 'all 0.2s'
              }}
              onMouseEnter={(e) => {
                e.currentTarget.style.background = '#fce8e6';
                e.currentTarget.style.color = '#ea4335';
                e.currentTarget.style.borderColor = '#ea4335';
              }}
              onMouseLeave={(e) => {
                e.currentTarget.style.background = 'transparent';
                e.currentTarget.style.color = '#5f6368';
                e.currentTarget.style.borderColor = '#dadce0';
              }}
            >
              退出登录
            </button>
          )}
        </div>
      </header>

      {/* 主内容区域 */}
      {currentView === 'home' && <ClawHubNews />}
      
      {currentView === 'chat' && (
        <>
          {/* 设置面板 */}
          {showSettings && (
        <div style={{
          padding: '16px 24px',
          background: '#f8f9fa',
          borderBottom: '1px solid #e8eaed'
        }}>
          <div style={{ maxWidth: '800px', margin: '0 auto' }}>
            <div style={{ 
              display: 'flex', 
              alignItems: 'center', 
              gap: '12px',
              marginBottom: '8px'
            }}>
              <label style={{ 
                fontSize: '14px', 
                color: '#5f6368',
                fontWeight: 500,
                minWidth: '80px'
              }}>
                选择模型
              </label>
              <select
                value={selectedModel}
                onChange={(e) => setSelectedModel(e.target.value)}
                style={{
                  flex: 1,
                  maxWidth: '400px',
                  padding: '10px 36px 10px 12px',
                  borderRadius: '8px',
                  border: '1px solid #dadce0',
                  fontSize: '14px',
                  background: 'white',
                  cursor: 'pointer',
                  appearance: 'none',
                  outline: 'none',
                  color: '#202124'
                }}
              >
                <option value="auto">🤖 Auto (自动选择最佳模型)</option>
                {models.length === 0 ? (
                  <option disabled>加载模型中...</option>
                ) : (
                  models.map((model: any) => (
                    <option key={model.id} value={model.id}>
                      {model.name || model.id}
                    </option>
                  ))
                )}
              </select>
              <span style={{ fontSize: '13px', color: '#5f6368' }}>
                {models.length > 0 ? `${models.length} 个可用` : '加载中...'}
              </span>
            </div>
          </div>
        </div>
      )}

          {/* 消息区域 */}
          <div style={{ 
        flex: 1, 
        overflowY: 'auto', 
        display: 'flex',
        flexDirection: 'column',
        alignItems: 'center'
      }}>
        <div style={{ 
          width: '100%', 
          maxWidth: '800px', 
          padding: '24px',
          flex: 1
        }}>
          {messages.length === 0 ? (
            <div style={{ 
              display: 'flex',
              flexDirection: 'column',
              alignItems: 'center',
              justifyContent: 'center',
              height: '100%',
              textAlign: 'center'
            }}>
              <div style={{ 
                fontSize: '48px',
                marginBottom: '24px',
                opacity: 0.3
              }}>
                💬
              </div>
              <h2 style={{ 
                fontSize: '32px',
                fontWeight: 400,
                color: '#202124',
                margin: '0 0 16px 0',
                letterSpacing: '-0.5px'
              }}>
                你好，有什么可以帮你？
              </h2>
              <p style={{ 
                fontSize: '16px',
                color: '#5f6368',
                margin: 0
              }}>
                开始对话，探索 AI 的可能性
              </p>
            </div>
          ) : (
            <>
              {messages.map((msg: any, idx: number) => (
                <div
                  key={idx}
                  style={{
                    display: 'flex',
                    gap: '16px',
                    marginBottom: '32px',
                    alignItems: 'flex-start'
                  }}
                >
                  {/* 头像 */}
                  <div style={{
                    width: '32px',
                    height: '32px',
                    borderRadius: '50%',
                    background: msg.role === 'user' ? '#1a73e8' : '#f1f3f4',
                    display: 'flex',
                    alignItems: 'center',
                    justifyContent: 'center',
                    fontSize: '16px',
                    flexShrink: 0
                  }}>
                    {msg.role === 'user' ? '👤' : '🤖'}
                  </div>
                  
                  {/* 消息内容 */}
                  <div style={{ flex: 1 }}>
                    <div style={{ 
                      fontSize: '14px',
                      fontWeight: 500,
                      color: '#202124',
                      marginBottom: '8px'
                    }}>
                      {msg.role === 'user' ? '你' : 'AI'}
                    </div>
                    <div style={{ 
                      fontSize: '16px',
                      lineHeight: '1.6',
                      color: '#202124',
                      whiteSpace: 'pre-wrap',
                      wordBreak: 'break-word'
                    }}>
                      {msg.content}
                    </div>
                  </div>
                </div>
              ))}
              
              {loading && (
                <div style={{
                  display: 'flex',
                  gap: '16px',
                  marginBottom: '32px',
                  alignItems: 'flex-start'
                }}>
                  <div style={{
                    width: '32px',
                    height: '32px',
                    borderRadius: '50%',
                    background: '#f1f3f4',
                    display: 'flex',
                    alignItems: 'center',
                    justifyContent: 'center',
                    fontSize: '16px'
                  }}>
                    🤖
                  </div>
                  <div style={{ flex: 1 }}>
                    <div style={{ 
                      fontSize: '14px',
                      fontWeight: 500,
                      color: '#202124',
                      marginBottom: '8px'
                    }}>
                      AI
                    </div>
                    <div style={{ 
                      display: 'flex',
                      gap: '4px',
                      alignItems: 'center'
                    }}>
                      <div style={{
                        width: '8px',
                        height: '8px',
                        borderRadius: '50%',
                        background: '#5f6368',
                        animation: 'pulse 1.4s ease-in-out infinite'
                      }} />
                      <div style={{
                        width: '8px',
                        height: '8px',
                        borderRadius: '50%',
                        background: '#5f6368',
                        animation: 'pulse 1.4s ease-in-out 0.2s infinite'
                      }} />
                      <div style={{
                        width: '8px',
                        height: '8px',
                        borderRadius: '50%',
                        background: '#5f6368',
                        animation: 'pulse 1.4s ease-in-out 0.4s infinite'
                      }} />
                    </div>
                  </div>
                </div>
              )}
              
              <div ref={messagesEndRef} />
            </>
          )}
        </div>
      </div>

          {/* 输入区域 */}
          <div style={{ 
        padding: '16px 24px 24px',
        background: '#fff',
        borderTop: messages.length > 0 ? '1px solid #e8eaed' : 'none'
      }}>
        <div style={{ 
          maxWidth: '800px', 
          margin: '0 auto',
          position: 'relative'
        }}>
          <div style={{
            display: 'flex',
            alignItems: 'center',
            gap: '12px',
            padding: '12px 16px',
            border: '1px solid #dadce0',
            borderRadius: '24px',
            background: '#fff',
            boxShadow: '0 1px 6px rgba(32,33,36,0.08)',
            transition: 'all 0.2s'
          }}
          onFocus={(e) => {
            e.currentTarget.style.boxShadow = '0 2px 8px rgba(32,33,36,0.16)';
            e.currentTarget.style.borderColor = '#1a73e8';
          }}
          onBlur={(e) => {
            e.currentTarget.style.boxShadow = '0 1px 6px rgba(32,33,36,0.08)';
            e.currentTarget.style.borderColor = '#dadce0';
          }}
          >
            <input
              type="text"
              value={input}
              onChange={(e) => setInput(e.target.value)}
              onKeyDown={(e) => e.key === 'Enter' && !e.shiftKey && sendMessage()}
              placeholder="输入消息..."
              disabled={loading || !backendOnline}
              style={{
                flex: 1,
                border: 'none',
                outline: 'none',
                fontSize: '16px',
                color: '#202124',
                background: 'transparent',
                padding: '4px 0'
              }}
            />
            <button
              onClick={sendMessage}
              disabled={loading || !backendOnline || !input.trim()}
              style={{
                width: '40px',
                height: '40px',
                borderRadius: '50%',
                border: 'none',
                background: (loading || !backendOnline || !input.trim()) ? '#f1f3f4' : '#1a73e8',
                color: 'white',
                cursor: (loading || !backendOnline || !input.trim()) ? 'not-allowed' : 'pointer',
                fontSize: '18px',
                display: 'flex',
                alignItems: 'center',
                justifyContent: 'center',
                transition: 'all 0.2s',
                flexShrink: 0
              }}
              onMouseEnter={(e) => {
                if (!loading && backendOnline && input.trim()) {
                  e.currentTarget.style.background = '#1557b0';
                }
              }}
              onMouseLeave={(e) => {
                if (!loading && backendOnline && input.trim()) {
                  e.currentTarget.style.background = '#1a73e8';
                }
              }}
            >
              ↑
            </button>
          </div>
          
          {messages.length === 0 && (
            <div style={{ 
              textAlign: 'center',
              marginTop: '12px',
              fontSize: '13px',
              color: '#5f6368'
            }}>
              按 Enter 发送，Shift + Enter 换行
            </div>
          )}
        </div>
      </div>

          {/* CSS 动画 */}
          <style>{`
            @keyframes pulse {
              0%, 100% { opacity: 0.3; transform: scale(0.8); }
              50% { opacity: 1; transform: scale(1); }
            }
          `}</style>
        </>
      )}
      
      {/* 图片生成器 */}
      {currentView === 'image' && (
        <div style={{ height: 'calc(100vh - 60px)', overflow: 'auto' }}>
          <ImageGeneratorNew 
            onClose={() => setCurrentView('home')}
            isAuthenticated={isAuthenticated}
            onShowLogin={onShowLogin}
          />
        </div>
      )}
      
      {/* 智能分析 */}
      {currentView === 'analysis' && (
        <div style={{ height: 'calc(100vh - 60px)', overflow: 'auto' }}>
          <SmartAnalysis />
        </div>
      )}
    </div>
  );
}

export default App;
