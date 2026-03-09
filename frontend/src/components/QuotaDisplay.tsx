import { useState, useEffect } from 'react';
import axios from 'axios';

const API_BASE_URL = import.meta.env.VITE_API_BASE_URL;

interface Quota {
  total: number;
  used: number;
  remaining: number;
}

export default function QuotaDisplay() {
  const [quota, setQuota] = useState<Quota | null>(null);
  const [loading, setLoading] = useState(true);

  useEffect(() => {
    fetchQuota();
  }, []);

  const fetchQuota = async () => {
    try {
      const token = localStorage.getItem('auth_token');
      if (!token) {
        setLoading(false);
        return;
      }

      const response = await axios.get(`${API_BASE_URL}/image/quota`, {
        headers: {
          Authorization: `Bearer ${token}`
        }
      });

      if (response.data.success) {
        setQuota(response.data.quota);
      }
    } catch (error) {
      console.error('获取配额失败:', error);
    } finally {
      setLoading(false);
    }
  };

  // 刷新配额（在生成图片后调用）
  const refreshQuota = () => {
    fetchQuota();
  };

  // 暴露刷新方法给父组件
  useEffect(() => {
    (window as any).refreshQuota = refreshQuota;
    return () => {
      delete (window as any).refreshQuota;
    };
  }, []);

  if (loading) {
    return (
      <div className="text-sm text-gray-500">
        加载配额信息...
      </div>
    );
  }

  if (!quota) {
    return (
      <div className="text-sm text-gray-500">
        请登录查看配额
      </div>
    );
  }

  const percentage = (quota.used / quota.total) * 100;
  const isLow = quota.remaining <= 2;
  const isEmpty = quota.remaining === 0;

  return (
    <div className="bg-white rounded-lg p-4 shadow-sm border border-gray-200">
      <div className="flex items-center justify-between mb-2">
        <span className="text-sm font-medium text-gray-700">
          图片生成配额
        </span>
        <span className={`text-sm font-bold ${
          isEmpty ? 'text-red-600' : isLow ? 'text-orange-600' : 'text-blue-600'
        }`}>
          {quota.remaining} / {quota.total}
        </span>
      </div>
      
      <div className="w-full bg-gray-200 rounded-full h-2 mb-2">
        <div
          className={`h-2 rounded-full transition-all ${
            isEmpty ? 'bg-red-500' : isLow ? 'bg-orange-500' : 'bg-blue-500'
          }`}
          style={{ width: `${percentage}%` }}
        />
      </div>
      
      <div className="text-xs text-gray-500">
        {isEmpty ? (
          <span className="text-red-600">配额已用完</span>
        ) : isLow ? (
          <span className="text-orange-600">配额即将用完</span>
        ) : (
          <span>还可生成 {quota.remaining} 张图片</span>
        )}
      </div>
    </div>
  );
}
