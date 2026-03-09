import { useState, useEffect } from 'react';

const API_BASE_URL = import.meta.env.VITE_API_BASE_URL;

interface Skill {
  title: string;
  description: string;
  author: string;
  link: string;
  downloads: string;
  stars: string;
}

function ClawHubNews() {
  const [skills, setSkills] = useState<Skill[]>([]);
  const [loading, setLoading] = useState(true);

  useEffect(() => {
    loadSkills();
  }, []);

  const loadSkills = async () => {
    setLoading(true);
    try {
      const response = await fetch(`${API_BASE_URL}/notte/monitor/clawhub/skills`);
      const data = await response.json();
      
      if (data.success && data.data && data.data.structured && data.data.structured.data) {
        setSkills(data.data.structured.data.skills || []);
      }
    } catch (error) {
      console.error('加载 ClawHub 技能失败:', error);
    } finally {
      setLoading(false);
    }
  };

  return (
    <div style={{
      minHeight: '100vh',
      background: 'linear-gradient(135deg, #667eea 0%, #764ba2 100%)',
      padding: '40px 24px'
    }}>
      {/* Hero Section */}
      <div style={{
        maxWidth: '1200px',
        margin: '0 auto 40px',
        textAlign: 'center',
        color: 'white'
      }}>
        <h1 style={{
          fontSize: '48px',
          fontWeight: 700,
          margin: '0 0 16px 0',
          textShadow: '0 2px 4px rgba(0,0,0,0.1)'
        }}>
          🚀 ClawHub 技能广场
        </h1>
        <p style={{
          fontSize: '20px',
          margin: '0 0 24px 0',
          opacity: 0.9
        }}>
          发现最新的 AI 技能和工具
        </p>
        <button
          onClick={loadSkills}
          disabled={loading}
          style={{
            padding: '12px 32px',
            background: 'rgba(255, 255, 255, 0.2)',
            color: 'white',
            border: '2px solid white',
            borderRadius: '24px',
            cursor: loading ? 'not-allowed' : 'pointer',
            fontSize: '16px',
            fontWeight: 600,
            transition: 'all 0.2s',
            backdropFilter: 'blur(10px)'
          }}
          onMouseEnter={(e) => {
            if (!loading) {
              e.currentTarget.style.background = 'white';
              e.currentTarget.style.color = '#667eea';
            }
          }}
          onMouseLeave={(e) => {
            e.currentTarget.style.background = 'rgba(255, 255, 255, 0.2)';
            e.currentTarget.style.color = 'white';
          }}
        >
          {loading ? '🔄 加载中...' : '🔄 刷新'}
        </button>
      </div>

      {/* Skills Grid */}
      <div style={{
        maxWidth: '1200px',
        margin: '0 auto'
      }}>
        {loading ? (
          <div style={{
            textAlign: 'center',
            padding: '80px 20px',
            color: 'white'
          }}>
            <div style={{
              width: '60px',
              height: '60px',
              border: '4px solid rgba(255, 255, 255, 0.3)',
              borderTopColor: 'white',
              borderRadius: '50%',
              margin: '0 auto 24px',
              animation: 'spin 0.8s linear infinite'
            }} />
            <p style={{ fontSize: '18px', opacity: 0.9 }}>加载技能中...</p>
          </div>
        ) : skills.length === 0 ? (
          <div style={{
            textAlign: 'center',
            padding: '80px 20px',
            color: 'white'
          }}>
            <p style={{ fontSize: '18px', opacity: 0.9 }}>暂无技能数据</p>
          </div>
        ) : (
          <div style={{
            display: 'grid',
            gridTemplateColumns: 'repeat(auto-fill, minmax(320px, 1fr))',
            gap: '20px',
            alignItems: 'stretch'
          }}>
            {skills.map((skill, index) => (
              <div
                key={index}
                onClick={() => window.open(`https://clawhub.ai${skill.link}`, '_blank')}
                style={{
                  background: 'white',
                  borderRadius: '16px',
                  padding: '24px',
                  cursor: 'pointer',
                  transition: 'all 0.3s',
                  boxShadow: '0 4px 12px rgba(0,0,0,0.1)',
                  display: 'flex',
                  flexDirection: 'column',
                  height: '100%'
                }}
                onMouseEnter={(e) => {
                  e.currentTarget.style.transform = 'translateY(-8px)';
                  e.currentTarget.style.boxShadow = '0 12px 24px rgba(0,0,0,0.15)';
                }}
                onMouseLeave={(e) => {
                  e.currentTarget.style.transform = 'translateY(0)';
                  e.currentTarget.style.boxShadow = '0 4px 12px rgba(0,0,0,0.1)';
                }}
              >
                <h3 style={{
                  fontSize: '18px',
                  fontWeight: 600,
                  color: '#202124',
                  margin: '0 0 12px 0',
                  display: 'flex',
                  alignItems: 'center',
                  gap: '8px',
                  minHeight: '50px'
                }}>
                  <span style={{ fontSize: '24px', flexShrink: 0 }}>⚡</span>
                  <span style={{ 
                    overflow: 'hidden',
                    textOverflow: 'ellipsis',
                    display: '-webkit-box',
                    WebkitLineClamp: 2,
                    WebkitBoxOrient: 'vertical',
                    lineHeight: '1.4'
                  }}>
                    {skill.title}
                  </span>
                </h3>
                
                <p style={{
                  fontSize: '14px',
                  color: '#5f6368',
                  margin: '0 0 16px 0',
                  lineHeight: '1.6',
                  flex: 1,
                  overflow: 'hidden',
                  textOverflow: 'ellipsis',
                  display: '-webkit-box',
                  WebkitLineClamp: 3,
                  WebkitBoxOrient: 'vertical'
                }}>
                  {skill.description}
                </p>
                
                <div style={{
                  display: 'flex',
                  justifyContent: 'space-between',
                  alignItems: 'center',
                  paddingTop: '16px',
                  borderTop: '1px solid #e8eaed',
                  marginTop: 'auto'
                }}>
                  <div style={{
                    fontSize: '12px',
                    color: '#80868b',
                    display: 'flex',
                    alignItems: 'center',
                    gap: '4px',
                    overflow: 'hidden',
                    textOverflow: 'ellipsis',
                    whiteSpace: 'nowrap',
                    maxWidth: '50%'
                  }}>
                    <span>👤</span>
                    <span style={{
                      overflow: 'hidden',
                      textOverflow: 'ellipsis'
                    }}>{skill.author}</span>
                  </div>
                  
                  <div style={{
                    display: 'flex',
                    gap: '12px',
                    fontSize: '12px',
                    color: '#80868b',
                    flexShrink: 0
                  }}>
                    <div style={{ display: 'flex', alignItems: 'center', gap: '4px' }}>
                      <span>📥</span>
                      <span>{skill.downloads}</span>
                    </div>
                    <div style={{ display: 'flex', alignItems: 'center', gap: '4px' }}>
                      <span>⭐</span>
                      <span>{skill.stars}</span>
                    </div>
                  </div>
                </div>
              </div>
            ))}
          </div>
        )}
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

export default ClawHubNews;
