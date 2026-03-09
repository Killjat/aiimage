import { useState, useEffect } from 'react';

const API_BASE_URL = import.meta.env.VITE_API_BASE_URL;

interface NewsItem {
  title: string;
  description: string;
  author?: string;
  link?: string;
  date?: string;
}

function HomePage() {
  const [url, setUrl] = useState('');
  const [task, setTask] = useState('');
  const [loading, setLoading] = useState(false);
  const [result, setResult] = useState<any>(null);
  const [news, setNews] = useState<NewsItem[]>([]);
  const [newsLoading, setNewsLoading] = useState(true);

  // 加载 ClawHub 技能动态
  useEffect(() => {
    loadClawHubNews();
  }, []);

  const loadClawHubNews = async () => {
    try {
      const response = await fetch(`${API_BASE_URL}/notte/monitor/clawhub/skills`);
      const data = await response.json();
      
      if (data.success && data.data && data.data.structured && data.data.structured.data) {
        const skills = data.data.structured.data.skills || [];
        // 转换为 NewsItem 格式
        const newsItems = skills.map((skill: any) => ({
          title: skill.title,
          description: skill.description,
          author: skill.author,
          link: skill.link ? `https://clawhub.ai${skill.link}` : undefined,
          date: skill.downloads || skill.stars
        }));
        setNews(newsItems);
      }
    } catch (error) {
      console.error('加载 ClawHub 动态失败:', error);
    } finally {
      setNewsLoading(false);
    }
  };

  // 执行智能分析
  const runAnalysis = async () => {
    if (!url.trim() || !task.trim()) {
      alert('请输入网址和任务描述');
      return;
    }

    setLoading(true);
    setResult(null);

    try {
      const response = await fetch(`${API_BASE_URL}/notte/agent/run`, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({
          task: task,
          start_url: url,
          max_steps: 20
        })
      });

      const data = await response.json();
      setResult(data);
    } catch (error: any) {
      setResult({ success: false, error: error.message });
    } finally {
      setLoading(false);
    }
  };

  return (
    <div style={{
      minHeight: '100vh',
      background: 'linear-gradient(135deg, #667eea 0%, #764ba2 100%)'
    }}>
      {/* Hero Section */}
      <div style={{
        padding: '80px 24px 60px',
        textAlign: 'center',
        color: 'white'
      }}>
        <h1 style={{
          fontSize: '48px',
          fontWeight: 700,
          margin: '0 0 16px 0',
          textShadow: '0 2px 4px rgba(0,0,0,0.1)'
        }}>
          🤖 AI 智能网站分析
        </h1>
        <p style={{
          fontSize: '20px',
          margin: 0,
          opacity: 0.9
        }}>
          告诉我们你想了解什么，AI 帮你分析任何网站
        </p>
      </div>

      {/* Main Content */}
      <div style={{
        maxWidth: '1200px',
        margin: '0 auto',
        padding: '0 24px 60px'
      }}>
        <div style={{
          display: 'grid',
          gridTemplateColumns: 'repeat(auto-fit, minmax(500px, 1fr))',
          gap: '24px',
          marginBottom: '40px'
        }}>
          {/* 智能分析卡片 */}
          <div style={{
            background: 'white',
            borderRadius: '16px',
            padding: '32px',
            boxShadow: '0 10px 40px rgba(0,0,0,0.1)'
          }}>
            <h2 style={{
              fontSize: '24px',
              fontWeight: 600,
              color: '#202124',
              margin: '0 0 8px 0'
            }}>
              🔍 智能网站分析
            </h2>
            <p style={{
              fontSize: '14px',
              color: '#5f6368',
              margin: '0 0 24px 0'
            }}>
              输入网址和你的需求，AI 会自动分析并给出结果
            </p>

            <div style={{ marginBottom: '16px' }}>
              <label style={{
                display: 'block',
                fontSize: '14px',
                fontWeight: 500,
                color: '#202124',
                marginBottom: '8px'
              }}>
                网站地址
              </label>
              <input
                type="url"
                value={url}
                onChange={(e) => setUrl(e.target.value)}
                placeholder="https://example.com"
                style={{
                  width: '100%',
                  padding: '12px',
                  border: '1px solid #dadce0',
                  borderRadius: '8px',
                  fontSize: '14px',
                  outline: 'none',
                  transition: 'border-color 0.2s'
                }}
                onFocus={(e) => e.currentTarget.style.borderColor = '#667eea'}
                onBlur={(e) => e.currentTarget.style.borderColor = '#dadce0'}
              />
            </div>

            <div style={{ marginBottom: '20px' }}>
              <label style={{
                display: 'block',
                fontSize: '14px',
                fontWeight: 500,
                color: '#202124',
                marginBottom: '8px'
              }}>
                你想了解什么？
              </label>
              <textarea
                value={task}
                onChange={(e) => setTask(e.target.value)}
                placeholder="例如：这个网站的主要功能是什么？有哪些 API 接口？价格是多少？"
                rows={4}
                style={{
                  width: '100%',
                  padding: '12px',
                  border: '1px solid #dadce0',
                  borderRadius: '8px',
                  fontSize: '14px',
                  outline: 'none',
                  resize: 'vertical',
                  fontFamily: 'inherit',
                  transition: 'border-color 0.2s'
                }}
                onFocus={(e) => e.currentTarget.style.borderColor = '#667eea'}
                onBlur={(e) => e.currentTarget.style.borderColor = '#dadce0'}
              />
            </div>

            <button
              onClick={runAnalysis}
              disabled={loading || !url.trim() || !task.trim()}
              style={{
                width: '100%',
                padding: '14px',
                background: loading || !url.trim() || !task.trim() 
                  ? '#f1f3f4' 
                  : 'linear-gradient(135deg, #667eea 0%, #764ba2 100%)',
                color: 'white',
                border: 'none',
                borderRadius: '8px',
                cursor: loading || !url.trim() || !task.trim() ? 'not-allowed' : 'pointer',
                fontSize: '16px',
                fontWeight: 600,
                transition: 'all 0.2s',
                boxShadow: loading || !url.trim() || !task.trim() 
                  ? 'none' 
                  : '0 4px 12px rgba(102, 126, 234, 0.4)'
              }}
              onMouseEnter={(e) => {
                if (!loading && url.trim() && task.trim()) {
                  e.currentTarget.style.transform = 'translateY(-2px)';
                  e.currentTarget.style.boxShadow = '0 6px 16px rgba(102, 126, 234, 0.5)';
                }
              }}
              onMouseLeave={(e) => {
                e.currentTarget.style.transform = 'translateY(0)';
                e.currentTarget.style.boxShadow = '0 4px 12px rgba(102, 126, 234, 0.4)';
              }}
            >
              {loading ? '🔄 分析中...' : '🚀 开始分析'}
            </button>

            {/* 结果显示 */}
            {result && !loading && (
              <div style={{
                marginTop: '24px',
                padding: '20px',
                background: result.success ? '#e8f5e9' : '#fce8e6',
                borderRadius: '12px',
                border: `1px solid ${result.success ? '#81c784' : '#ef5350'}`
              }}>
                <h3 style={{
                  fontSize: '16px',
                  fontWeight: 500,
                  color: result.success ? '#2e7d32' : '#c62828',
                  margin: '0 0 12px 0'
                }}>
                  {result.success ? '✅ 分析完成' : '❌ 分析失败'}
                </h3>

                {result.error && (
                  <p style={{
                    fontSize: '14px',
                    color: '#c62828',
                    margin: '0 0 12px 0'
                  }}>
                    {result.error}
                  </p>
                )}

                {result.data && (
                  <div style={{
                    background: 'white',
                    padding: '16px',
                    borderRadius: '8px',
                    maxHeight: '400px',
                    overflow: 'auto'
                  }}>
                    <pre style={{
                      margin: 0,
                      fontSize: '13px',
                      lineHeight: '1.6',
                      color: '#202124',
                      whiteSpace: 'pre-wrap',
                      wordBreak: 'break-word',
                      fontFamily: 'monospace'
                    }}>
                      {JSON.stringify(result.data, null, 2)}
                    </pre>
                  </div>
                )}
              </div>
            )}
          </div>

          {/* ClawHub 动态卡片 */}
          <div style={{
            background: 'white',
            borderRadius: '16px',
            padding: '32px',
            boxShadow: '0 10px 40px rgba(0,0,0,0.1)'
          }}>
            <div style={{
              display: 'flex',
              justifyContent: 'space-between',
              alignItems: 'center',
              marginBottom: '24px'
            }}>
              <div>
                <h2 style={{
                  fontSize: '24px',
                  fontWeight: 600,
                  color: '#202124',
                  margin: '0 0 4px 0'
                }}>
                  📰 ClawHub 技能动态
                </h2>
                <p style={{
                  fontSize: '14px',
                  color: '#5f6368',
                  margin: 0
                }}>
                  最新的 AI 技能和工具
                </p>
              </div>
              <button
                onClick={loadClawHubNews}
                disabled={newsLoading}
                style={{
                  padding: '8px 16px',
                  background: 'transparent',
                  color: '#667eea',
                  border: '1px solid #667eea',
                  borderRadius: '8px',
                  cursor: newsLoading ? 'not-allowed' : 'pointer',
                  fontSize: '14px',
                  fontWeight: 500,
                  transition: 'all 0.2s'
                }}
                onMouseEnter={(e) => {
                  if (!newsLoading) {
                    e.currentTarget.style.background = '#667eea';
                    e.currentTarget.style.color = 'white';
                  }
                }}
                onMouseLeave={(e) => {
                  e.currentTarget.style.background = 'transparent';
                  e.currentTarget.style.color = '#667eea';
                }}
              >
                {newsLoading ? '🔄' : '🔄 刷新'}
              </button>
            </div>

            {newsLoading ? (
              <div style={{
                textAlign: 'center',
                padding: '40px',
                color: '#5f6368'
              }}>
                <div style={{
                  width: '40px',
                  height: '40px',
                  border: '3px solid #f1f3f4',
                  borderTopColor: '#667eea',
                  borderRadius: '50%',
                  margin: '0 auto 16px',
                  animation: 'spin 0.8s linear infinite'
                }} />
                加载中...
              </div>
            ) : news.length === 0 ? (
              <div style={{
                textAlign: 'center',
                padding: '40px',
                color: '#5f6368'
              }}>
                暂无动态
              </div>
            ) : (
              <div style={{
                maxHeight: '600px',
                overflowY: 'auto'
              }}>
                {news.map((item, index) => (
                  <div
                    key={index}
                    style={{
                      padding: '16px',
                      marginBottom: '12px',
                      background: '#f8f9fa',
                      borderRadius: '8px',
                      transition: 'all 0.2s',
                      cursor: item.link ? 'pointer' : 'default'
                    }}
                    onClick={() => item.link && window.open(item.link, '_blank')}
                    onMouseEnter={(e) => {
                      if (item.link) {
                        e.currentTarget.style.background = '#e8f0fe';
                        e.currentTarget.style.transform = 'translateX(4px)';
                      }
                    }}
                    onMouseLeave={(e) => {
                      e.currentTarget.style.background = '#f8f9fa';
                      e.currentTarget.style.transform = 'translateX(0)';
                    }}
                  >
                    <h3 style={{
                      fontSize: '16px',
                      fontWeight: 500,
                      color: '#202124',
                      margin: '0 0 8px 0'
                    }}>
                      {item.title}
                    </h3>
                    {item.description && (
                      <p style={{
                        fontSize: '14px',
                        color: '#5f6368',
                        margin: '0 0 8px 0',
                        lineHeight: '1.5'
                      }}>
                        {item.description}
                      </p>
                    )}
                    {(item.author || item.date) && (
                      <div style={{
                        fontSize: '12px',
                        color: '#80868b',
                        display: 'flex',
                        gap: '12px'
                      }}>
                        {item.author && <span>👤 {item.author}</span>}
                        {item.date && <span>📅 {item.date}</span>}
                      </div>
                    )}
                  </div>
                ))}
              </div>
            )}
          </div>
        </div>
      </div>

      {/* CSS 动画 */}
      <style>{`
        @keyframes spin {
          to { transform: rotate(360deg); }
        }
      `}</style>
    </div>
  );
}

export default HomePage;
