import React, { createContext, useContext, useState, useEffect } from 'react';
import api from '../utils/api';

const AuthContext = createContext(null);

export function AuthProvider({ children }) {
    const [user, setUser] = useState(null);
    const [token, setToken] = useState(localStorage.getItem('token'));
    const [loading, setLoading] = useState(true);

    useEffect(() => {
        if (token) {
            api.defaults.headers.common['Authorization'] = `Bearer ${token}`;
            fetchUser();
        } else {
            setLoading(false);
        }
    }, [token]);

    const fetchUser = async () => {
        try {
            // Check if token exists
            if (!token) {
                setLoading(false);
                return;
            }

            // Try to get user info from localStorage or create a basic user object
            const userEmail = localStorage.getItem('user_email');
            const userName = localStorage.getItem('user_name');
            
            if (userEmail) {
                setUser({ 
                    email: userEmail,
                    name: userName || userEmail.split('@')[0],
                });
            } else {
                // If no user info, try to validate token by making a test API call
                try {
                    const response = await api.get('/api/v1/farms?per_page=1');
                    // If successful, token is valid - create basic user object
                    setUser({ email: 'user@fms.test', name: 'User' });
                } catch (error) {
                    // Token invalid, logout
                    logout();
                }
            }
            setLoading(false);
        } catch (error) {
            console.error('Error fetching user:', error);
            logout();
        }
    };

    const login = async (email, password) => {
        try {
            const response = await api.post('/api/v1/login', { email, password });
            const { token: newToken, user: userData } = response.data;
            
            setToken(newToken);
            setUser(userData);
            localStorage.setItem('token', newToken);
            localStorage.setItem('user_email', userData.email || email);
            localStorage.setItem('user_name', userData.name || email.split('@')[0]);
            api.defaults.headers.common['Authorization'] = `Bearer ${newToken}`;
            
            return { success: true };
        } catch (error) {
            console.error('Login error:', error);
            return {
                success: false,
                message: error.response?.data?.message || error.message || 'Login failed',
            };
        }
    };

    const logout = () => {
        setToken(null);
        setUser(null);
        localStorage.removeItem('token');
        localStorage.removeItem('user_email');
        delete api.defaults.headers.common['Authorization'];
    };

    return (
        <AuthContext.Provider value={{ user, token, login, logout, loading }}>
            {children}
        </AuthContext.Provider>
    );
}

export function useAuth() {
    const context = useContext(AuthContext);
    if (!context) {
        throw new Error('useAuth must be used within AuthProvider');
    }
    return context;
}

