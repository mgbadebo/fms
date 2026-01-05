import React from 'react';
import { Routes, Route, Navigate } from 'react-router-dom';
import { AuthProvider, useAuth } from './contexts/AuthContext';
import Login from './pages/Login';
import ConsolidatedDashboard from './pages/ConsolidatedDashboard';
import Farms from './pages/Farms';
import FarmDetail from './pages/FarmDetail';
import HarvestLots from './pages/HarvestLots';
import HarvestLotDetail from './pages/HarvestLotDetail';
import ScaleDevices from './pages/ScaleDevices';
import LabelTemplates from './pages/LabelTemplates';
import StaffLabor from './pages/StaffLabor';
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
import Boreholes from './pages/Boreholes';
import BellPepperProduction from './pages/BellPepperProduction';
import BellPepperCycleDetail from './pages/BellPepperCycleDetail';
import BellPepperHarvests from './pages/BellPepperHarvests';
import Locations from './pages/Locations';
import AdminZones from './pages/AdminZones';
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
                                    <Route path="/" element={<ConsolidatedDashboard />} />
                                    <Route path="/farms" element={<Farms />} />
                                    <Route path="/farms/:id" element={<FarmDetail />} />
                                    <Route path="/harvest-lots" element={<HarvestLots />} />
                                    <Route path="/harvest-lots/:id" element={<HarvestLotDetail />} />
                                    <Route path="/scale-devices" element={<ScaleDevices />} />
                                    <Route path="/label-templates" element={<LabelTemplates />} />
                                    <Route path="/staff-labor" element={<StaffLabor />} />
                                    
                                    {/* Gari Routes */}
                                    <Route path="/gari-production-batches" element={<GariProductionBatches />} />
                                    <Route path="/gari-production-batches/:id" element={<GariProductionBatchDetail />} />
                                    <Route path="/gari-inventory" element={<GariInventory />} />
                                    <Route path="/gari-sales" element={<GariSales />} />
                                    <Route path="/gari-kpis" element={<GariKPIDashboard />} />
                                    <Route path="/packaging-materials" element={<PackagingMaterials />} />
                                    <Route path="/gari-waste-losses" element={<GariWasteLosses />} />
                                    
                                    {/* Bell Pepper Routes */}
                                    <Route path="/greenhouses" element={<Greenhouses />} />
                                    <Route path="/boreholes" element={<Boreholes />} />
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
                                    <Route path="/admin/locations" element={<Locations />} />
                                    <Route path="/admin/admin-zones" element={<AdminZones />} />
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

