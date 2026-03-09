import { useState, useEffect } from 'react';

const API_BASE_URL = import.meta.env.VITE_API_BASE_URL;

interface Scenario {
  id: string;
  icon: string;
  title: string;
  description: string;
  category: string;
  url: string;
  task: string;
}

function SmartAnalysis() {
  const [loading, setLoading] = useState(false);
  const [result, setResult] = useState<any>(null);
  const [selectedScenario, setSelectedScenario] = useState<Scenario | null>(null);
  const [activeCategory, setActiveCategory] = useState('all');
  const [progress, setProgress] = useState(0);
  const [preloadedData, setPreloadedData] = useState<Record<string, any>>({});

  // 实用场景配置
  const scenarios: Scenario[] = [
    {
      id: 'github-trending',
      icon: '🔥',
      title: 'GitHub 热门项目',
      description: '本周最热门的开源项目',
      category: 'tech',
      url: 'https://github.com/trending',
      task: 'List the top 5 trending repositories with their names, descriptions, and star counts'
    },
    {
      id: 'hackernews-top',
      icon: '📰',
      title: 'Hacker News 头条',
      description: '科技圈最新热门讨论',
      category: 'tech',
      url: 'https://news.ycombinator.com',
      task: 'List the top 5 posts with titles, points, and number of comments'
    },
    {
      id: 'producthunt-today',
      icon: '�',
      title: 'Product Hunt 今日产品',
      description: '今天最受欢迎的新产品',
      category: 'product',
      url: 'https://www.producthunt.com',
      task: 'List today\'s top 5 products with names, taglines, and upvote counts'
    },
    {
      id: 'techcrunch-news',
      icon: '💼',
      title: 'TechCrunch 科技新闻',
      description: '最新科技行业动态',
      category: 'tech',
      url: 'https://techcrunch.com',
      task: 'List the latest 5 news articles with headlines and brief summaries'
    },
    {
      id: 'reddit-programming',
      icon: '💻',
      title: 'Reddit 编程热帖',
      description: 'r/programming 热门讨论',
      category: 'tech',
      url: 'https://www.reddit.com/r/programming',
      task: 'List the top 5 hot posts with titles and upvote counts'
    },
    {
      id: 'medium-tech',
      icon: '✍️',
      title: 'Medium 技术文章',
      description: '热门技术博客文章',
      category: 'tech',
      url: 'https://medium.com/tag/technology',
      task: 'List the top 5 featured articles with titles and authors'
    },
    {
      id: 'stackoverflow-questions',
      icon: '❓',
      title: 'Stack Overflow 热门问题',
      description: '最受关注的技术问题',
      category: 'tech',
      url: 'https://stackoverflow.com/questions',
      task: 'List the top 5 questions with titles and vote counts'
    },
    {
      id: 'dev-to-posts',
      icon: '👨‍�',
      title: 'DEV 社区热帖',
      description: '开发者社区热门文章',
      category: 'tech',
      url: 'https://dev.to',
      task: 'List the top 5 posts with titles, authors, and reaction counts'
    },
    {
      id: 'indie-hackers',
      icon: '🏗️',
      title: 'Indie Hackers 创业故事',
      description: '独立开发者的成功案例',
      category: 'business',
      url: 'https://www.indiehackers.com',
      task: 'List the latest 5 posts or interviews with titles and key insights'
    },
    {
      id: 'ycombinator-jobs',
      icon: '�',
      title: 'YC 招聘信息',
      description: 'Y Combinator 公司职位',
      category: 'jobs',
      url: 'https://www.ycombinator.com/jobs',
      task: 'List 5 recent job postings with company names, positions, and locations'
    },
    {
      id: 'remote-ok',
      icon: '🌍',
      title: '远程工作机会',
      description: '最新远程职位',
      category: 'jobs',
      url: 'https://remoteok.com',
      task: 'List the top 5 remote job postings with positions, companies, and salaries'
    },
    {
      id: 'crypto-prices',
      icon: '�',
      title: '加密货币行情',
      description: '主流币种实时价格',
      category: 'finance',
      url: 'https://coinmarketcap.com',
      task: 'List the top 5 cryptocurrencies with names, prices, and 24h change percentages'
    }
  ];

  const categories = [
    { id: 'all', name: '全部', icon: '🌟' },
    { id: 'tech', name: '科技资讯', icon: '💻' },
    { id: 'product', name: '产品发现', icon: '🚀' },
    { id: 'business', name: '创业商业', icon: '💼' },
    { id: 'jobs', name: '工作机会', icon: '👔' },
    { id: 'finance', name: '金融市场', icon: '💰' }
  ];

  const filteredScenarios = activeCategory === 'all' 
    ? scenarios 
    : scenarios.filter(s => s.category === activeCategory);

  // 预加载热门场景
  useEffect(() => {
    const preloadScenarios = async () => {
      // 预加载前3个热门场景
      const hotScenarios = [
        scenarios[0], // GitHub Trending
        scenarios[1], // Hacker News
        scenarios[2]  // Product Hunt
      ];

      for (const scenario of hotScenarios) {
        try {
          const response = await fetch(`${API_BASE_URL}/notte/agent/run`, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({
              task: scenario.task,
              start_url: scenario.url,
              max_steps: 20
            })
          });

          const data = await response.json();
          if (data.success) {
            setPreloadedData(prev => ({
              ...prev,
              [scenario.id]: data
            }));
          }
        } catch (error) {
          console.error(`Failed to preload ${scenario.id}:`, error);
        }
      }
    };

    // 延迟2秒后开始预加载，避免影响页面初始加载
    const timer = setTimeout(preloadScenarios, 2000);
    return () => clearTimeout(timer);
  }, []);

  const runScenario = async (scenario: Scenario) => {
    setSelectedScenario(scenario);
    setResult(null);
    
    // 检查是否有预加载的数据
    if (preloadedData[scenario.id]) {
      setProgress(100);
      setResult(preloadedData[scenario.id]);
      return;
    }

    setLoading(true);
    setProgress(0);

    // 模拟进度条
    const progressInterval = setInterval(() => {
      setProgress(prev => {
        if (prev >= 90) return prev;
        return prev + Math.random() * 15;
      });
    }, 1000);

    try {
      const response = await fetch(`${API_BASE_URL}/notte/agent/run`, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({
          task: scenario.task,
          start_url: scenario.url,
          max_steps: 20
        })
      });

      const data = await response.json();
      setProgress(100);
      setResult(data);
    } catch (error: any) {
      setProgress(100);
      setResult({ success: false, error: error.message });
    } finally {
      clearInterval(progressInterval);
      setLoading(false);
    }
  };

  return (
    <div style={{
      minHeight: '100vh',
      background: 'linear-gradient(135deg, #667eea 0%, #764ba2 100%)',
      padding: '80px 24px 60px'
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
          🤖 AI 智能分析
        </h1>
        <p style={{
          fontSize: '20px',
          margin: 0,
          opacity: 0.9
        }}>
          一键获取全球热门资讯、项目、产品和市场动态
        </p>
      </div>

      {/* Category Filter */}
      <div style={{
        maxWidth: '1200px',
        margin: '0 auto 32px'
      }}>
        <div style={{
          display: 'flex',
          gap: '12px',
          flexWrap: 'wrap',
          justifyContent: 'center'
        }}>
          {categories.map(cat => (
            <button
              key={cat.id}
              onClick={() => setActiveCategory(cat.id)}
              style={{
                padding: '12px 24px',
                background: activeCategory === cat.id 
                  ? 'white' 
                  : 'rgba(255, 255, 255, 0.2)',
                color: activeCategory === cat.id ? '#667eea' : 'white',
                border: 'none',
                borderRadius: '24px',
                cursor: 'pointer',
                fontSize: '16px',
                fontWeight: 600,
                transition: 'all 0.3s',
                backdropFilter: 'blur(10px)'
              }}
              onMouseEnter={(e) => {
                if (activeCategory !== cat.id) {
                  e.currentTarget.style.background = 'rgba(255, 255, 255, 0.3)';
                }
              }}
              onMouseLeave={(e) => {
                if (activeCategory !== cat.id) {
                  e.currentTarget.style.background = 'rgba(255, 255, 255, 0.2)';
                }
              }}
            >
              {cat.icon} {cat.name}
            </button>
          ))}
        </div>
      </div>

      {/* Scenarios Grid */}
      <div style={{
        maxWidth: '1200px',
        margin: '0 auto 40px'
      }}>
        <div style={{
          display: 'grid',
          gridTemplateColumns: 'repeat(auto-fill, minmax(280px, 1fr))',
          gap: '20px'
        }}>
          {filteredScenarios.map(scenario => (
            <button
              key={scenario.id}
              onClick={() => runScenario(scenario)}
              disabled={loading}
              style={{
                background: 'white',
                border: 'none',
                borderRadius: '16px',
                padding: '24px',
                cursor: loading ? 'not-allowed' : 'pointer',
                textAlign: 'left',
                transition: 'all 0.3s',
                boxShadow: '0 4px 12px rgba(0,0,0,0.1)',
                opacity: loading ? 0.6 : 1
              }}
              onMouseEnter={(e) => {
                if (!loading) {
                  e.currentTarget.style.transform = 'translateY(-4px)';
                  e.currentTarget.style.boxShadow = '0 8px 24px rgba(0,0,0,0.15)';
                }
              }}
              onMouseLeave={(e) => {
                e.currentTarget.style.transform = 'translateY(0)';
                e.currentTarget.style.boxShadow = '0 4px 12px rgba(0,0,0,0.1)';
              }}
            >
              <div style={{
                fontSize: '48px',
                marginBottom: '12px'
              }}>
                {scenario.icon}
              </div>
              <h3 style={{
                fontSize: '18px',
                fontWeight: 600,
                color: '#202124',
                margin: '0 0 8px 0'
              }}>
                {scenario.title}
              </h3>
              <p style={{
                fontSize: '14px',
                color: '#5f6368',
                margin: 0,
                lineHeight: '1.5'
              }}>
                {scenario.description}
              </p>
              <div style={{
                marginTop: '12px',
                padding: '6px 12px',
                background: '#e8f5e9',
                borderRadius: '12px',
                display: 'inline-block'
              }}>
                <span style={{
                  fontSize: '12px',
                  color: '#2e7d32',
                  fontWeight: 600
                }}>
                  ⏱️ ~30秒
                </span>
              </div>
            </button>
          ))}
        </div>
      </div>

      {/* Loading State */}
      {loading && selectedScenario && (
        <div style={{
          maxWidth: '1200px',
          margin: '0 auto',
          background: 'white',
          borderRadius: '16px',
          padding: '40px',
          textAlign: 'center',
          boxShadow: '0 10px 40px rgba(0,0,0,0.15)'
        }}>
          <div style={{
            fontSize: '64px',
            marginBottom: '16px'
          }}>
            {selectedScenario.icon}
          </div>
          <div style={{
            width: '48px',
            height: '48px',
            border: '4px solid #e8eaed',
            borderTopColor: '#667eea',
            borderRadius: '50%',
            margin: '0 auto 16px',
            animation: 'spin 0.8s linear infinite'
          }} />
          <p style={{
            fontSize: '18px',
            color: '#202124',
            fontWeight: 600,
            margin: '0 0 8px 0'
          }}>
            正在分析 {selectedScenario.title}
          </p>
          <p style={{
            fontSize: '14px',
            color: '#5f6368',
            margin: '0 0 16px 0'
          }}>
            AI 正在访问网站并提取最新数据...
          </p>
          <div style={{
            padding: '16px',
            background: '#fff3cd',
            borderRadius: '8px',
            border: '1px solid #ffc107',
            textAlign: 'left'
          }}>
            <p style={{
              fontSize: '13px',
              color: '#856404',
              margin: '0 0 8px 0',
              fontWeight: 600
            }}>
              ⏱️ 预计耗时：20-40 秒
            </p>
            <p style={{
              fontSize: '12px',
              color: '#856404',
              margin: '0 0 12px 0',
              lineHeight: '1.6'
            }}>
              我们正在使用全球住宅IP网络访问目标网站，绕过反爬虫检测，并通过AI提取结构化数据。这个过程需要一些时间，请耐心等待。
            </p>
            {/* 进度条 */}
            <div style={{
              width: '100%',
              height: '8px',
              background: '#fff',
              borderRadius: '4px',
              overflow: 'hidden'
            }}>
              <div style={{
                width: `${progress}%`,
                height: '100%',
                background: 'linear-gradient(90deg, #667eea 0%, #764ba2 100%)',
                transition: 'width 0.3s ease'
              }} />
            </div>
            <p style={{
              fontSize: '12px',
              color: '#856404',
              margin: '8px 0 0 0',
              textAlign: 'right'
            }}>
              {Math.round(progress)}%
            </p>
          </div>
        </div>
      )}

      {/* Result Display */}
      {result && !loading && selectedScenario && (
        <div style={{
          maxWidth: '1200px',
          margin: '0 auto',
          background: 'white',
          borderRadius: '16px',
          padding: '40px',
          boxShadow: '0 10px 40px rgba(0,0,0,0.15)'
        }}>
          <div style={{
            display: 'flex',
            alignItems: 'center',
            gap: '16px',
            marginBottom: '24px',
            paddingBottom: '24px',
            borderBottom: '2px solid #e8eaed'
          }}>
            <div style={{ fontSize: '48px' }}>
              {selectedScenario.icon}
            </div>
            <div style={{ flex: 1 }}>
              <h2 style={{
                fontSize: '24px',
                fontWeight: 700,
                color: '#202124',
                margin: '0 0 4px 0'
              }}>
                {selectedScenario.title}
              </h2>
              <p style={{
                fontSize: '14px',
                color: '#5f6368',
                margin: 0
              }}>
                {selectedScenario.description}
              </p>
            </div>
            <button
              onClick={() => {
                setResult(null);
                setSelectedScenario(null);
              }}
              style={{
                padding: '8px 16px',
                background: '#f8f9fa',
                border: '1px solid #e8eaed',
                borderRadius: '8px',
                cursor: 'pointer',
                fontSize: '14px',
                color: '#5f6368'
              }}
            >
              关闭
            </button>
          </div>

          {result.success && result.data?.structured?.success ? (
            <div>
              {/* 显示摘要 (新格式) */}
              {result.data.structured.data.summary && (
                <div style={{
                  padding: '20px',
                  background: '#f8f9fa',
                  borderRadius: '12px',
                  marginBottom: '20px'
                }}>
                  <h3 style={{
                    fontSize: '16px',
                    fontWeight: 600,
                    color: '#202124',
                    margin: '0 0 12px 0'
                  }}>
                    📊 数据摘要
                  </h3>
                  <p style={{
                    fontSize: '15px',
                    color: '#5f6368',
                    margin: 0,
                    lineHeight: '1.8'
                  }}>
                    {result.data.structured.data.summary}
                  </p>
                </div>
              )}

              {/* 显示列表数据 (新格式) */}
              {result.data.structured.data.items && result.data.structured.data.items.length > 0 && (
                <div>
                  <h3 style={{
                    fontSize: '16px',
                    fontWeight: 600,
                    color: '#202124',
                    margin: '0 0 16px 0'
                  }}>
                    🔑 提取结果
                  </h3>
                  <div style={{
                    display: 'flex',
                    flexDirection: 'column',
                    gap: '12px'
                  }}>
                    {result.data.structured.data.items.map((item: any, index: number) => (
                      <div
                        key={index}
                        style={{
                          padding: '16px',
                          background: '#f8f9fa',
                          borderRadius: '8px',
                          borderLeft: '4px solid #667eea'
                        }}
                      >
                        {item.title && (
                          <h4 style={{
                            fontSize: '15px',
                            fontWeight: 600,
                            color: '#202124',
                            margin: '0 0 8px 0'
                          }}>
                            {item.title}
                          </h4>
                        )}
                        {item.details && (
                          <p style={{
                            fontSize: '14px',
                            color: '#5f6368',
                            margin: 0,
                            lineHeight: '1.6'
                          }}>
                            {item.details}
                          </p>
                        )}
                      </div>
                    ))}
                  </div>
                </div>
              )}

              {/* 显示分析结果 (旧格式兼容) */}
              {result.data.structured.data.analysis && !result.data.structured.data.summary && (
                <div style={{
                  padding: '20px',
                  background: '#f8f9fa',
                  borderRadius: '12px',
                  marginBottom: '20px'
                }}>
                  <h3 style={{
                    fontSize: '16px',
                    fontWeight: 600,
                    color: '#202124',
                    margin: '0 0 12px 0'
                  }}>
                    📊 分析摘要
                  </h3>
                  <p style={{
                    fontSize: '15px',
                    color: '#5f6368',
                    margin: 0,
                    lineHeight: '1.8'
                  }}>
                    {result.data.structured.data.analysis}
                  </p>
                </div>
              )}

              {result.data.structured.data.key_findings && result.data.structured.data.key_findings.length > 0 && (
                <div>
                  <h3 style={{
                    fontSize: '16px',
                    fontWeight: 600,
                    color: '#202124',
                    margin: '0 0 16px 0'
                  }}>
                    � 关键信息
                  </h3>
                  <div style={{
                    display: 'flex',
                    flexDirection: 'column',
                    gap: '12px'
                  }}>
                    {result.data.structured.data.key_findings.map((finding: string, index: number) => (
                      <div
                        key={index}
                        style={{
                          padding: '16px',
                          background: '#f8f9fa',
                          borderRadius: '8px',
                          borderLeft: '4px solid #667eea'
                        }}
                      >
                        <p style={{
                          fontSize: '14px',
                          color: '#202124',
                          margin: 0,
                          lineHeight: '1.6'
                        }}>
                          {finding}
                        </p>
                      </div>
                    ))}
                  </div>
                </div>
              )}

              {result.data.markdown && (
                <details style={{ marginTop: '20px' }}>
                  <summary style={{
                    fontSize: '14px',
                    color: '#5f6368',
                    cursor: 'pointer',
                    padding: '12px',
                    background: '#f8f9fa',
                    borderRadius: '8px'
                  }}>
                    查看原始数据
                  </summary>
                  <pre style={{
                    marginTop: '12px',
                    padding: '16px',
                    background: '#f8f9fa',
                    borderRadius: '8px',
                    fontSize: '12px',
                    color: '#5f6368',
                    overflow: 'auto',
                    maxHeight: '400px'
                  }}>
                    {result.data.markdown}
                  </pre>
                </details>
              )}
            </div>
          ) : (
            <div style={{
              padding: '32px',
              background: '#fce8e6',
              borderRadius: '12px',
              textAlign: 'center'
            }}>
              <div style={{ fontSize: '48px', marginBottom: '16px' }}>❌</div>
              <h3 style={{
                fontSize: '18px',
                fontWeight: 600,
                color: '#c62828',
                margin: '0 0 12px 0'
              }}>
                分析失败
              </h3>
              <p style={{
                fontSize: '14px',
                color: '#5f6368',
                margin: 0
              }}>
                {result.error || result.data?.structured?.error || '未知错误'}
              </p>
            </div>
          )}
        </div>
      )}

      {/* CSS Animation */}
      <style>{`
        @keyframes spin {
          to { transform: rotate(360deg); }
        }
      `}</style>
    </div>
  );
}

export default SmartAnalysis;
