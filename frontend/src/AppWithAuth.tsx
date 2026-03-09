import { useState, useEffect } from 'react';
import Login from './components/Login';
import Register from './components/Register';
import App from './App';

export default function AppWithAuth() {
  const [isAuthenticated, setIsAuthenticated] = useState(false);
  const [showAuthModal, setShowAuthModal] = useState(false);
  const [showRegister, setShowRegister] = useState(false);
  const [loading, setLoading] = useState(true);

  useEffect(() => {
    console.log('AppWithAuth: Checking authentication...');
    // 检查是否已登录
    const token = localStorage.getItem('auth_token');
    console.log('AppWithAuth: Token found:', !!token);
    if (token) {
      setIsAuthenticated(true);
    }
    setLoading(false);
  }, []);

  const handleLoginSuccess = (_token: string) => {
    console.log('AppWithAuth: Login successful');
    setIsAuthenticated(true);
    setShowAuthModal(false);
  };

  const handleRegisterSuccess = (_token: string) => {
    console.log('AppWithAuth: Registration successful');
    setIsAuthenticated(true);
    setShowAuthModal(false);
  };

  const handleLogout = () => {
    console.log('AppWithAuth: Logging out...');
    localStorage.removeItem('auth_token');
    localStorage.removeItem('user');
    setIsAuthenticated(false);
  };

  const handleShowLogin = () => {
    setShowRegister(false);
    setShowAuthModal(true);
  };

  console.log('AppWithAuth: Rendering, loading:', loading, 'isAuthenticated:', isAuthenticated);

  if (loading) {
    return (
      <div className="min-h-screen flex items-center justify-center bg-gray-50">
        <div className="text-gray-600">加载中...</div>
      </div>
    );
  }

  return (
    <>
      <App 
        isAuthenticated={isAuthenticated}
        onLogout={handleLogout} 
        onShowLogin={handleShowLogin}
      />
      
      {/* 登录/注册弹窗 */}
      {showAuthModal && (
        <div 
          style={{
            position: 'fixed',
            top: 0,
            left: 0,
            right: 0,
            bottom: 0,
            background: 'rgba(0, 0, 0, 0.5)',
            display: 'flex',
            alignItems: 'center',
            justifyContent: 'center',
            zIndex: 1000
          }}
          onClick={() => setShowAuthModal(false)}
        >
          <div onClick={(e) => e.stopPropagation()}>
            {showRegister ? (
              <Register
                onRegisterSuccess={handleRegisterSuccess}
                onSwitchToLogin={() => setShowRegister(false)}
              />
            ) : (
              <Login
                onLoginSuccess={handleLoginSuccess}
                onSwitchToRegister={() => setShowRegister(true)}
              />
            )}
          </div>
        </div>
      )}
    </>
  );
}
