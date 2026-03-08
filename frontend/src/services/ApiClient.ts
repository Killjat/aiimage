import axios from 'axios';
import { Message, ChatResponse } from '../types';

// 从环境变量读取 API URL，生产环境必须配置
const API_BASE_URL = import.meta.env.VITE_API_BASE_URL;

if (!API_BASE_URL) {
  console.error('错误: VITE_API_BASE_URL 环境变量未配置！请在 .env 文件中设置。');
}

const apiClient = axios.create({
  baseURL: API_BASE_URL,
  timeout: 60000,
  headers: {
    'Content-Type': 'application/json',
  },
});

export const sendMessage = async (
  model: string,
  messages: Message[]
): Promise<ChatResponse> => {
  try {
    const response = await apiClient.post<ChatResponse>('/chat/send', {
      model,
      messages,
    });
    return response.data;
  } catch (error: any) {
    if (error.response?.data) {
      return error.response.data;
    }
    return {
      success: false,
      error: '网络错误，请检查后端服务是否启动',
    };
  }
};

export const checkHealth = async (): Promise<boolean> => {
  try {
    const response = await apiClient.get('/health');
    return response.data.status === 'ok';
  } catch {
    return false;
  }
};
