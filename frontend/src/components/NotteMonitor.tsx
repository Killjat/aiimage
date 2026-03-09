import { useState, useEffect } from 'react';

const API_BASE_URL = import.meta.env.VITE_API_BASE_URL;

interface MonitorTask {
  id: string;
  name: string;
  description: string;
  url: string;
  endpoint: string;
}

function NotteMonitor() {
  const [tasks, setTasks] = useState<MonitorTask[]>([]);
  const [loading, setLoading] = useState(false);
  const [selectedTask, setSelectedTask] = useState<string | null>(null);
  const [result, setResult] = useState<any>(null);
  const [customUrl, setCustomUrl] = useState('');
  const [customInstructions, setCustomInstructions] = useState('');
  const [activeTab, setActiveTab] = useState<'monitor' | 'scrape' | 'agent'>('monitor');

  // 加载预定义的监控任务
  useEffect(() => {
    fetch(`${API_BASE_URL}/notte/monitor/tasks`)
      .then(res => res.json())
      .then(data => {
        if (data.success) {
          setTasks(data.tasks);
        }
      })
      .catch(err => console.error('加载监控任务失败:', err));
  }, []);

  // 执行监控任务
  const runMonitorTask = async (task: MonitorTask) => {
    setLoading(true);
    setSelectedTask(task.id);
    setResult(null);

    try {
      const response = await fetch(`${API_BASE_URL}${task.endpoint}`, {
        method: 'GET',
        headers: { 'Content-Type': 'application/json' }
      });

      const data = await response.json();
      setResult(data);
    } catch (error: any) {
      setResult({ success: false, error: error.message });
    } finally {
      setLoading(false);
    }
  };

  // 自定义抓取
  const runCustomScrape = async () => {
    if (!customUrl.trim()) {
      alert('请输入网址');
      return;
    }

    setLoading(true);
    setResult(null);

    try {
      const response = await fetch(`${API_BASE_URL}/notte/scrape/structured`, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({
          url: customUrl,
          instructions: customInstructions || '提取网页的主要内容和结构',
          schema: {}
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

  // AI 代理任务
  const runAgentTask = async () => {
    if (!customInstructions.trim()) {
      alert('请输入任务描述');
      return;
    }

    setLoading(true);
    setResult(null);

    try {
      const response = await fetch(`${API_BASE_URL}/notte/agent/run`, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({
          task: customInstructions,
          start_url: customUrl || null,
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
      padding: '32px',
      maxWidth: '1200px',
      margin: '0 auto'
    }}>
      {/* 标题 */}
      <div style={{ marginBottom: '32px' }}>
        <h2 style={{
          fontSize: '28px',
          fontWeight: 500,
          color: '#202124',
          margin: '0 0 8px 0'
        }}>
          🔍 Notte 智能监控
        </h2>
        <p style={{
          fontSize: '14px',
          color: '#5f6368',
          margin: 0
        }}>
          使用 AI 代理监控网站变化、抓取结构化数据、执行自动化任务
        </p>
      </div>

      {/* 标签页 */}
      <div style={{
        display: 'flex',
        gap: '8px',
        marginBottom: '24px',
        borderBottom: '1px solid #e8eaed'
      }}>
        {[
          { id: 'monitor', label: '📊 预定义监控', icon: '📊' },
          { id: 'scrape', label: '🌐 自定义抓取', icon: '🌐' },
          { id: 'agent', label: '🤖 AI 代理', icon: '🤖' }
        ].map(tab => (
          <button
            key={tab.id}
            onClick={() => {
              setActiveTab(tab.id as any);
              setResult(null);
              setSelectedTask(null);
            }}
            style={{
              padding: '12px 24px',
              background: 'transparent',
              border: 'none',
              borderBottom: activeTab === tab.id ? '2px solid #1a73e8' : '2px solid transparent',
              color: activeTab === tab.id ? '#1a73e8' : '#5f6368',
              cursor: 'pointer',
              fontSize: '14px',
              fontWeight: 500,
              transition: 'all 0.2s'
            }}
          >
            {tab.label}
          </button>
        ))}
      </div>

      {/* 预定义监控 */}
      {activeTab === 'monitor' && (
        <div>
          <div style={{
            display: 'grid',
            gridTemplateColumns: 'repeat(auto-fill, minmax(300px, 1fr))',
            gap: '16px',
            marginBottom: '24px'
          }}>
            {tasks.map(task => (
              <div
                key={task.id}
                style={{
                  padding: '20px',
                  border: '1px solid #e8eaed',
                  borderRadius: '12px',
                  cursor: 'pointer',
                  transition: 'all 0.2s',
                  background: selectedTask === task.id ? '#e8f0fe' : 'white'
                }}
                onClick={() => runMonitorTask(task)}
                onMouseEnter={(e) => {
                  e.currentTarget.style.boxShadow = '0 2px 8px rgba(0,0,0,0.1)';
                  e.currentTarget.style.borderColor = '#1a73e8';
                }}
                onMouseLeave={(e) => {
                  e.currentTarget.style.boxShadow = 'none';
                  e.currentTarget.style.borderColor = '#e8eaed';
                }}
              >
                <h3 style={{
                  fontSize: '16px',
                  fontWeight: 500,
                  color: '#202124',
                  margin: '0 0 8px 0'
                }}>
                  {task.name}
                </h3>
                <p style={{
                  fontSize: '14px',
                  color: '#5f6368',
                  margin: '0 0 12px 0',
                  lineHeight: '1.5'
                }}>
                  {task.description}
                </p>
                <div style={{
                  fontSize: '12px',
                  color: '#1a73e8',
                  fontFamily: 'monospace',
                  background: '#f8f9fa',
                  padding: '6px 10px',
                  borderRadius: '6px',
                  overflow: 'hidden',
                  textOverflow: 'ellipsis',
                  whiteSpace: 'nowrap'
                }}>
                  {task.url}
                </div>
              </div>
            ))}
          </div>
        </div>
      )}

      {/* 自定义抓取 */}
      {activeTab === 'scrape' && (
        <div style={{ maxWidth: '600px' }}>
          <div style={{ marginBottom: '16px' }}>
            <label style={{
              display: 'block',
              fontSize: '14px',
              fontWeight: 500,
              color: '#202124',
              marginBottom: '8px'
            }}>
              网址
            </label>
            <input
              type="url"
              value={customUrl}
              onChange={(e) => setCustomUrl(e.target.value)}
              placeholder="https://example.com"
              style={{
                width: '100%',
                padding: '12px',
                border: '1px solid #dadce0',
                borderRadius: '8px',
                fontSize: '14px',
                outline: 'none'
              }}
            />
          </div>

          <div style={{ marginBottom: '16px' }}>
            <label style={{
              display: 'block',
              fontSize: '14px',
              fontWeight: 500,
              color: '#202124',
              marginBottom: '8px'
            }}>
              抓取指令（可选）
            </label>
            <textarea
              value={customInstructions}
              onChange={(e) => setCustomInstructions(e.target.value)}
              placeholder="例如：提取所有文章标题、日期和链接"
              rows={4}
              style={{
                width: '100%',
                padding: '12px',
                border: '1px solid #dadce0',
                borderRadius: '8px',
                fontSize: '14px',
                outline: 'none',
                resize: 'vertical',
                fontFamily: 'inherit'
              }}
            />
          </div>

          <button
            onClick={runCustomScrape}
            disabled={loading || !customUrl.trim()}
            style={{
              padding: '12px 24px',
              background: loading || !customUrl.trim() ? '#f1f3f4' : '#1a73e8',
              color: 'white',
              border: 'none',
              borderRadius: '8px',
              cursor: loading || !customUrl.trim() ? 'not-allowed' : 'pointer',
              fontSize: '14px',
              fontWeight: 500,
              transition: 'all 0.2s'
            }}
          >
            {loading ? '抓取中...' : '开始抓取'}
          </button>
        </div>
      )}

      {/* AI 代理 */}
      {activeTab === 'agent' && (
        <div style={{ maxWidth: '600px' }}>
          <div style={{ marginBottom: '16px' }}>
            <label style={{
              display: 'block',
              fontSize: '14px',
              fontWeight: 500,
              color: '#202124',
              marginBottom: '8px'
            }}>
              起始网址（可选）
            </label>
            <input
              type="url"
              value={customUrl}
              onChange={(e) => setCustomUrl(e.target.value)}
              placeholder="https://example.com"
              style={{
                width: '100%',
                padding: '12px',
                border: '1px solid #dadce0',
                borderRadius: '8px',
                fontSize: '14px',
                outline: 'none'
              }}
            />
          </div>

          <div style={{ marginBottom: '16px' }}>
            <label style={{
              display: 'block',
              fontSize: '14px',
              fontWeight: 500,
              color: '#202124',
              marginBottom: '8px'
            }}>
              任务描述
            </label>
            <textarea
              value={customInstructions}
              onChange={(e) => setCustomInstructions(e.target.value)}
              placeholder="例如：在这个网站上找到最新的产品价格并整理成表格"
              rows={4}
              style={{
                width: '100%',
                padding: '12px',
                border: '1px solid #dadce0',
                borderRadius: '8px',
                fontSize: '14px',
                outline: 'none',
                resize: 'vertical',
                fontFamily: 'inherit'
              }}
            />
          </div>

          <button
            onClick={runAgentTask}
            disabled={loading || !customInstructions.trim()}
            style={{
              padding: '12px 24px',
              background: loading || !customInstructions.trim() ? '#f1f3f4' : '#1a73e8',
              color: 'white',
              border: 'none',
              borderRadius: '8px',
              cursor: loading || !customInstructions.trim() ? 'not-allowed' : 'pointer',
              fontSize: '14px',
              fontWeight: 500,
              transition: 'all 0.2s'
            }}
          >
            {loading ? '执行中...' : '执行任务'}
          </button>
        </div>
      )}

      {/* 加载状态 */}
      {loading && (
        <div style={{
          marginTop: '24px',
          padding: '20px',
          background: '#f8f9fa',
          borderRadius: '12px',
          textAlign: 'center'
        }}>
          <div style={{
            display: 'inline-flex',
            gap: '8px',
            alignItems: 'center',
            fontSize: '14px',
            color: '#5f6368'
          }}>
            <div style={{
              width: '16px',
              height: '16px',
              border: '2px solid #1a73e8',
              borderTopColor: 'transparent',
              borderRadius: '50%',
              animation: 'spin 0.8s linear infinite'
            }} />
            正在处理...
          </div>
        </div>
      )}

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
            {result.success ? '✅ 执行成功' : '❌ 执行失败'}
          </h3>

          {result.error && (
            <p style={{
              fontSize: '14px',
              color: '#c62828',
              margin: '0 0 12px 0'
            }}>
              错误: {result.error}
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

      {/* CSS 动画 */}
      <style>{`
        @keyframes spin {
          to { transform: rotate(360deg); }
        }
      `}</style>
    </div>
  );
}

export default NotteMonitor;
