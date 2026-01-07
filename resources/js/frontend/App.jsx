import React from 'react';
import { Routes, Route, Navigate } from 'react-router-dom';
import { AuthProvider, useAuth } from './contexts/AuthContext';
import Login from './pages/Login';
import ConsolidatedDashboard from './pages/ConsolidatedDashboard';
import Farms from './pages/Farms';
import FarmDetail from './pages/FarmDetail';
import ScaleDevices from './pages/ScaleDevices';
import LabelTemplates from './pages/LabelTemplates';
import GariProductionBatches from './pages/GariProductionBatches';
import GariProductionBatchDetail from './pages/GariProductionBatchDetail';
import GariInventory from './pages/GariInventory';
import GariSales from './pages/GariSales';
import GariKPIDashboard from './pages/GariKPIDashboard';
import PackagingMaterials from './pages/PackagingMaterials';
import GariWasteLosses from './pages/GariWasteLosses';
import ConsolidatedSales from './pages/ConsolidatedSales';
import ConsolidatedExpenses from './pages/ConsolidatedExpenses';
import StaffAllocation from './pages/StaffAllocation';
import CropPlaceholder from './pages/CropPlaceholder';
import Greenhouses from './pages/Greenhouses';
import GreenhouseManagement from './pages/GreenhouseManagement';
import BellPepperProduction from './pages/BellPepperProduction';
import BellPepperCycleDetail from './pages/BellPepperCycleDetail';
import BellPepperHarvests from './pages/BellPepperHarvests';
import Sites from './pages/Sites';
import FarmZones from './pages/FarmZones';
import Factories from './pages/Factories';
import AdminZones from './pages/AdminZones';
import Roles from './pages/Roles';
import Crops from './pages/Crops';
import Users from './pages/Users';
import Assets from './pages/Assets';
import AssetCategories from './pages/AssetCategories';
import WorkerJobRoles from './pages/WorkerJobRoles';
import Boreholes from './pages/Boreholes';
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

function PermissionRoute({ children, requiredPermission }) {
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
    
    if (!user) {
        return <Navigate to="/login" />;
    }
    
    // Admin has all permissions
    const isAdmin = user?.roles?.some(r => r.name === 'ADMIN');
    
    // Get user permissions
    const userPermissions = user.permissions?.map(p => p.name) || 
                          user.roles?.flatMap(r => r.permissions?.map(p => p.name) || []) || [];
    
    const hasPermission = isAdmin || userPermissions.includes(requiredPermission);
    
    if (!hasPermission) {
        // Redirect to first accessible page
        return <Navigate to="/bell-pepper-harvests" replace />;
    }
    
    return children;
}

function DefaultRoute() {
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
    
    if (!user) {
        return <Navigate to="/login" />;
    }
    
    // Admin has all permissions
    const isAdmin = user?.roles?.some(r => r.name === 'ADMIN');
    
    // Get user permissions
    const userPermissions = user.permissions?.map(p => p.name) || 
                          user.roles?.flatMap(r => r.permissions?.map(p => p.name) || []) || [];
    
    // Check if user has dashboard permission
    const hasDashboardPermission = isAdmin || userPermissions.includes('reports.dashboard.view');
    
    if (hasDashboardPermission) {
        return <ConsolidatedDashboard />;
    }
    
    // Redirect to first accessible page based on permissions
    // Priority: bell-pepper-harvests, bell-pepper-inventory, etc.
    if (userPermissions.includes('bell-pepper.harvests.view')) {
        return <Navigate to="/bell-pepper-harvests" replace />;
    }
    if (userPermissions.includes('bell-pepper.inventory.view')) {
        return <Navigate to="/bell-pepper-inventory" replace />;
    }
    if (userPermissions.includes('gari.production-batches.view')) {
        return <Navigate to="/gari-production-batches" replace />;
    }
    if (userPermissions.includes('gari.inventory.view')) {
        return <Navigate to="/gari-inventory" replace />;
    }
    
    // Default fallback - show a message or redirect to a safe page
    return (
        <div className="p-6">
            <div className="bg-yellow-50 border border-yellow-200 rounded-lg p-4">
                <h2 className="text-lg font-semibold text-yellow-800">No Access</h2>
                <p className="text-yellow-700">You don't have access to any pages. Please contact your administrator.</p>
            </div>
        </div>
    );
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
                                    <Route path="/" element={<DefaultRoute />} />
                                    <Route path="/farms" element={<Farms />} />
                                    <Route path="/farms/:id" element={<FarmDetail />} />
                                    <Route path="/scale-devices" element={<ScaleDevices />} />
                                    <Route path="/label-templates" element={<LabelTemplates />} />
                                    {/* Gari Routes */}
                                    <Route path="/gari-production-batches" element={<GariProductionBatches />} />
                                    <Route path="/gari-production-batches/:id" element={<GariProductionBatchDetail />} />
                                    <Route path="/gari-inventory" element={<GariInventory />} />
                                    <Route path="/gari-sales" element={<GariSales />} />
                                    <Route path="/gari-kpis" element={<GariKPIDashboard />} />
                                    <Route path="/packaging-materials" element={<PackagingMaterials />} />
                                    <Route path="/gari-waste-losses" element={<GariWasteLosses />} />
                                    
                                    {/* Bell Pepper Routes */}
                                    <Route path="/bell-pepper-production" element={<BellPepperProduction />} />
                                    <Route path="/bell-pepper-cycles/:id" element={<BellPepperCycleDetail />} />
                                    <Route path="/bell-pepper-harvests" element={<BellPepperHarvests />} />
                                    <Route path="/bell-pepper-inventory" element={<CropPlaceholder cropName="Bell Pepper" basePath="bell-pepper" />} />
                                    <Route path="/bell-pepper-sales" element={<CropPlaceholder cropName="Bell Pepper" basePath="bell-pepper" />} />
                                    <Route path="/bell-pepper-kpis" element={<CropPlaceholder cropName="Bell Pepper" basePath="bell-pepper" />} />
                                    
                                    {/* Tomatoes Routes */}
                                    <Route path="/tomatoes-production" element={<CropPlaceholder cropName="Tomatoes" basePath="tomatoes" />} />
                                    <Route path="/tomatoes-inventory" element={<CropPlaceholder cropName="Tomatoes" basePath="tomatoes" />} />
                                    <Route path="/tomatoes-sales" element={<CropPlaceholder cropName="Tomatoes" basePath="tomatoes" />} />
                                    <Route path="/tomatoes-kpis" element={<CropPlaceholder cropName="Tomatoes" basePath="tomatoes" />} />
                                    
                                    {/* Habaneros Routes */}
                                    <Route path="/habaneros-production" element={<CropPlaceholder cropName="Habaneros" basePath="habaneros" />} />
                                    <Route path="/habaneros-inventory" element={<CropPlaceholder cropName="Habaneros" basePath="habaneros" />} />
                                    <Route path="/habaneros-sales" element={<CropPlaceholder cropName="Habaneros" basePath="habaneros" />} />
                                    <Route path="/habaneros-kpis" element={<CropPlaceholder cropName="Habaneros" basePath="habaneros" />} />
                                    
                                    {/* Consolidated Reports */}
                                    <Route path="/reports/consolidated-sales" element={<ConsolidatedSales />} />
                                    <Route path="/reports/consolidated-expenses" element={<ConsolidatedExpenses />} />
                                    <Route path="/reports/staff-allocation" element={<StaffAllocation />} />
                                    
                                    {/* Admin Settings */}
                                    <Route path="/admin/admin-zones" element={<AdminZones />} />
                                    <Route path="/admin/sites" element={<Sites />} />
                                    <Route path="/admin/boreholes" element={<Boreholes />} />
                                    <Route path="/admin/greenhouses" element={<GreenhouseManagement />} />
                                    <Route path="/admin/farm-zones" element={<FarmZones />} />
                                    <Route path="/admin/factories" element={<Factories />} />
                                    <Route path="/admin/crops" element={<Crops />} />
                                    <Route path="/admin/worker-job-roles" element={<WorkerJobRoles />} />
                                    <Route path="/admin/assets" element={<Assets />} />
                                    <Route path="/admin/asset-categories" element={<AssetCategories />} />
                                    <Route path="/admin/roles" element={<Roles />} />
                                    <Route path="/admin/users" element={<Users />} />
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

