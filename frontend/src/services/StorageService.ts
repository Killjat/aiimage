/**
 * 存储服务 - 使用 IndexedDB 存储大型数据，localStorage 存储小型数据
 */

const DB_NAME = 'aiimage_db';
const DB_VERSION = 1;
const STORE_NAME = 'image_history';

export class StorageService {
  private static db: IDBDatabase | null = null;

  /**
   * 初始化 IndexedDB
   */
  static async initDB(): Promise<IDBDatabase> {
    if (this.db) {
      return this.db;
    }

    return new Promise((resolve, reject) => {
      const request = indexedDB.open(DB_NAME, DB_VERSION);

      request.onerror = () => {
        console.error('Failed to open IndexedDB');
        reject(request.error);
      };

      request.onsuccess = () => {
        this.db = request.result;
        resolve(this.db);
      };

      request.onupgradeneeded = (event) => {
        const db = (event.target as IDBOpenDBRequest).result;
        if (!db.objectStoreNames.contains(STORE_NAME)) {
          db.createObjectStore(STORE_NAME, { keyPath: 'id', autoIncrement: true });
        }
      };
    });
  }

  /**
   * 保存图像到历史记录
   */
  static async saveImageToHistory(imageUrl: string): Promise<void> {
    try {
      const db = await this.initDB();
      const transaction = db.transaction([STORE_NAME], 'readwrite');
      const store = transaction.objectStore(STORE_NAME);

      // 获取所有历史记录
      const getAllRequest = store.getAll();

      return new Promise((resolve, reject) => {
        getAllRequest.onsuccess = () => {
          const allImages = getAllRequest.result;

          // 只保留最近 20 张
          if (allImages.length >= 20) {
            // 删除最旧的
            const oldestId = allImages[0].id;
            store.delete(oldestId);
          }

          // 添加新图像
          store.add({
            url: imageUrl,
            timestamp: Date.now()
          });

          transaction.oncomplete = () => resolve();
          transaction.onerror = () => reject(transaction.error);
        };

        getAllRequest.onerror = () => reject(getAllRequest.error);
      });
    } catch (err) {
      console.error('Failed to save image to history:', err);
      // 降级到 localStorage
      this.saveToLocalStorage('image_history_fallback', imageUrl);
    }
  }

  /**
   * 获取图像历史记录
   */
  static async getImageHistory(limit: number = 10): Promise<string[]> {
    try {
      const db = await this.initDB();
      const transaction = db.transaction([STORE_NAME], 'readonly');
      const store = transaction.objectStore(STORE_NAME);

      return new Promise((resolve, reject) => {
        const getAllRequest = store.getAll();

        getAllRequest.onsuccess = () => {
          const allImages = getAllRequest.result;
          // 按时间戳倒序排列，取最近的 limit 张
          const sorted = allImages
            .sort((a, b) => b.timestamp - a.timestamp)
            .slice(0, limit)
            .map(item => item.url);

          resolve(sorted);
        };

        getAllRequest.onerror = () => reject(getAllRequest.error);
      });
    } catch (err) {
      console.error('Failed to get image history:', err);
      // 降级到 localStorage
      return this.getFromLocalStorage('image_history_fallback', []);
    }
  }

  /**
   * 清空图像历史记录
   */
  static async clearImageHistory(): Promise<void> {
    try {
      const db = await this.initDB();
      const transaction = db.transaction([STORE_NAME], 'readwrite');
      const store = transaction.objectStore(STORE_NAME);

      return new Promise((resolve, reject) => {
        const clearRequest = store.clear();

        clearRequest.onsuccess = () => {
          transaction.oncomplete = () => resolve();
          transaction.onerror = () => reject(transaction.error);
        };

        clearRequest.onerror = () => reject(clearRequest.error);
      });
    } catch (err) {
      console.error('Failed to clear image history:', err);
    }
  }

  /**
   * 保存到 localStorage（用于小型数据）
   */
  static saveToLocalStorage(key: string, value: any): void {
    try {
      localStorage.setItem(key, JSON.stringify(value));
    } catch (err) {
      if (err instanceof DOMException && err.code === 22) {
        // QuotaExceededError
        console.warn('localStorage quota exceeded, clearing old data');
        localStorage.removeItem(key);
        try {
          localStorage.setItem(key, JSON.stringify(value));
        } catch (e) {
          console.error('Failed to save to localStorage:', e);
        }
      } else {
        console.error('Failed to save to localStorage:', err);
      }
    }
  }

  /**
   * 从 localStorage 读取
   */
  static getFromLocalStorage(key: string, defaultValue: any = null): any {
    try {
      const item = localStorage.getItem(key);
      return item ? JSON.parse(item) : defaultValue;
    } catch (err) {
      console.error('Failed to read from localStorage:', err);
      return defaultValue;
    }
  }

  /**
   * 从 localStorage 删除
   */
  static removeFromLocalStorage(key: string): void {
    try {
      localStorage.removeItem(key);
    } catch (err) {
      console.error('Failed to remove from localStorage:', err);
    }
  }
}
