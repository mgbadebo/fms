import React from 'react';
import { Routes, Route, Navigate } from 'react-router-dom';
import { AuthProvider, useAuth } from './contexts/AuthContext';
import Login from './pages/Login';
import Dashboard from './pages/Dashboard';
import Farms from './pages/Farms';
import FarmDetail from './pages/FarmDetail';
import HarvestLots from './pages/HarvestLots';
import HarvestLotDetail from './pages/HarvestLotDetail';
import ScaleDevices from './pages/ScaleDevices';
import LabelTemplates from './pages/LabelTemplates';
import Layout from './components/Layout';

function PrivateRoute({ children }) {
    const { user, loading } = useAuth();
    
    if (loading) {
        return (
            <div className="min-h-screen flex items-center justify-center">
                <div className="text-center">
                    <div className="animate-spin rounded-full h-12 w-12 border-b-2 border-green-600 mx-auto"></div>
                    <p className="mt-4 text-gray-600">Loading...</p>
                </div>
            </div>
        );
    }
    
    return user ? children : <Navigate to="/login" />;
}

function App() {
    return (
        <AuthProvider>
            <Routes>
                <Route path="/login" element={<Login />} />
                <Route
                    path="/*"
                    element={
                        <PrivateRoute>
                            <Layout>
                                <Routes>
                                    <Route path="/" element={<Dashboard />} />
                                    <Route path="/farms" element={<Farms />} />
                                    <Route path="/farms/:id" element={<FarmDetail />} />
                                    <Route path="/harvest-lots" element={<HarvestLots />} />
                                    <Route path="/harvest-lots/:id" element={<HarvestLotDetail />} />
                                    <Route path="/scale-devices" element={<ScaleDevices />} />
                                    <Route path="/label-templates" element={<LabelTemplates />} />
                                </Routes>
                            </Layout>
                        </PrivateRoute>
                    }
                />
            </Routes>
        </AuthProvider>
    );
}

export default App;

