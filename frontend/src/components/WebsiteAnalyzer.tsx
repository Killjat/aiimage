import { useState, useEffect } from 'react';
import { apiClient } from '../services/ApiClient';

interface ReasoningModel {
  id: string;
  name: string;
  description: string;
  recommended: boolean;
}

interface WebsiteInfo {
  url: string;
  title: string;
  status_code: number;
  scripts_count: number;
  stylesheets_count: number;
  api_endpoints_count: number;
  forms_count: number;
}

interface AnalysisResult {
  website_info: WebsiteInfo;
  analysis: string;
  model_used: string;
}

export default function WebsiteAnalyzer() {
  const [url, setUrl] = useState('');
  const [selectedModel, setSelectedModel] = useState('meta-llama/llama-3.3-70b-instruct');
  const [models, setModels] = useState<ReasoningModel[]>([
    // 默认模型列表，防止 API 加载失败
    {
      id: 'meta-llama/llama-3.3-70b-instruct',
      name: 'Llama 3.3 70B',
      description: '开源大模型，推理能力优秀',
      recommended: true
    },
    {
      id: 'qwen/qwen-2.5-72b-instruct',
      name: 'Qwen 2.5 72B',
      description: '阿里通义千问',
      recommended: true
    }
  ]);
  const [loading, setLoading] = useState(false);
  const [result, setResult] = useState<AnalysisResult | null>(null);
  const [error, setError] = useState('');

  // 加载推理模型列表
  useEffect(() => {
    loadModels();
  }, []);

  const loadModels = async () => {
    try {
      const response = await apiClient.get('/analyze/reasoning-models');
      if (response.data.success && response.data.models.length > 0) {
        setModels(response.data.models);
        // 设置第一个推荐的模型为默认值
        const recommended = response.data.models.find((m: ReasoningModel) => m.recommended);
        if (recommended) {
          setSelectedModel(recommended.id);
        }
      }
    } catch (err) {
      console.error('加载模型失败:', err);
      // 使用默认模型列表
    }
  };

  const handleAnalyze = async () => {
    if (!url.trim()) {
      setError('请输入网站 URL');
      return;
    }

    // 验证 URL 格式
    try {
      new URL(url);
    } catch {
      setError('请输入有效的 URL（包含 http:// 或 https://）');
      return;
    }

    setLoading(true);
    setError('');
    setResult(null);

    try {
      const response = await apiClient.post('/analyze/website', {
        url: url.trim(),
        model: selectedModel
      });

      if (response.data.success) {
        setResult(response.data.data);
      } else {
        setError(response.data.error || '分析失败');
      }
    } catch (err: any) {
      setError(err.response?.data?.error || '网络错误，请稍后重试');
    } finally {
      setLoading(false);
    }
  };

  return (
    <div className="max-w-6xl mx-auto p-6">
      <div className="bg-white rounded-lg shadow-lg p-6">
        {/* 标题 */}
        <div className="mb-6">
          <h2 className="text-2xl font-bold text-gray-800 mb-2">
            🔍 网站逆向分析
          </h2>
          <p className="text-gray-600">
            输入网站 URL，使用 AI 推理模型分析其技术架构和实现原理
          </p>
        </div>

        {/* 输入区域 */}
        <div className="space-y-4 mb-6">
          {/* URL 输入 */}
          <div>
            <label className="block text-sm font-medium text-gray-700 mb-2">
              网站 URL
            </label>
            <input
              type="url"
              value={url}
              onChange={(e) => setUrl(e.target.value)}
              placeholder="https://example.com"
              className="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
              disabled={loading}
            />
          </div>

          {/* 模型选择 */}
          <div>
            <label className="block text-sm font-medium text-gray-700 mb-2">
              推理模型
            </label>
            <select
              value={selectedModel}
              onChange={(e) => setSelectedModel(e.target.value)}
              className="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
              disabled={loading}
            >
              {models.map((model) => (
                <option key={model.id} value={model.id}>
                  {model.name} {model.recommended && '⭐'} - {model.description}
                </option>
              ))}
            </select>
          </div>

          {/* 分析按钮 */}
          <button
            onClick={handleAnalyze}
            disabled={loading || !url.trim()}
            className={`w-full py-3 px-6 rounded-lg font-medium text-white transition-colors ${
              loading || !url.trim()
                ? 'bg-gray-400 cursor-not-allowed'
                : 'bg-blue-600 hover:bg-blue-700'
            }`}
          >
            {loading ? (
              <span className="flex items-center justify-center">
                <svg className="animate-spin h-5 w-5 mr-3" viewBox="0 0 24 24">
                  <circle
                    className="opacity-25"
                    cx="12"
                    cy="12"
                    r="10"
                    stroke="currentColor"
                    strokeWidth="4"
                    fill="none"
                  />
                  <path
                    className="opacity-75"
                    fill="currentColor"
                    d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"
                  />
                </svg>
                分析中...（可能需要 30-60 秒）
              </span>
            ) : (
              '🔍 开始分析'
            )}
          </button>
        </div>

        {/* 错误提示 */}
        {error && (
          <div className="mb-6 p-4 bg-red-50 border border-red-200 rounded-lg">
            <p className="text-red-600">❌ {error}</p>
          </div>
        )}

        {/* 分析结果 */}
        {result && (
          <div className="space-y-6">
            {/* 网站基本信息 */}
            <div className="bg-gray-50 rounded-lg p-4">
              <h3 className="text-lg font-semibold text-gray-800 mb-3">
                📊 网站信息
              </h3>
              <div className="grid grid-cols-2 md:grid-cols-4 gap-4">
                <div>
                  <p className="text-sm text-gray-600">状态码</p>
                  <p className="text-lg font-semibold text-green-600">
                    {result.website_info.status_code}
                  </p>
                </div>
                <div>
                  <p className="text-sm text-gray-600">JS 文件</p>
                  <p className="text-lg font-semibold text-blue-600">
                    {result.website_info.scripts_count}
                  </p>
                </div>
                <div>
                  <p className="text-sm text-gray-600">CSS 文件</p>
                  <p className="text-lg font-semibold text-purple-600">
                    {result.website_info.stylesheets_count}
                  </p>
                </div>
                <div>
                  <p className="text-sm text-gray-600">API 端点</p>
                  <p className="text-lg font-semibold text-orange-600">
                    {result.website_info.api_endpoints_count}
                  </p>
                </div>
              </div>
              <div className="mt-3">
                <p className="text-sm text-gray-600">标题</p>
                <p className="text-base font-medium text-gray-800">
                  {result.website_info.title || '未找到'}
                </p>
              </div>
            </div>

            {/* AI 分析结果 */}
            <div className="bg-white border border-gray-200 rounded-lg p-6">
              <div className="flex items-center justify-between mb-4">
                <h3 className="text-lg font-semibold text-gray-800">
                  🤖 AI 分析结果
                </h3>
                <span className="text-sm text-gray-500">
                  模型: {result.model_used}
                </span>
              </div>
              <div className="prose max-w-none">
                <div className="whitespace-pre-wrap text-gray-700 leading-relaxed">
                  {result.analysis}
                </div>
              </div>
            </div>
          </div>
        )}
      </div>
    </div>
  );
}
