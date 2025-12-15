import React from 'react';
import { Link, useLocation, useNavigate } from 'react-router-dom';
import { useAuth } from '../contexts/AuthContext';
import {
    LayoutDashboard,
    Tractor,
    Package,
    Scale,
    Tag,
    LogOut,
    Menu,
    X,
    Factory,
    ShoppingCart,
    TrendingUp,
} from 'lucide-react';
import { useState } from 'react';

export default function Layout({ children }) {
    const { user, logout } = useAuth();
    const location = useLocation();
    const navigate = useNavigate();
    const [mobileMenuOpen, setMobileMenuOpen] = useState(false);

    const handleLogout = () => {
        logout();
        navigate('/login');
    };

    const navigation = [
        { name: 'Dashboard', href: '/', icon: LayoutDashboard },
        { name: 'Farms', href: '/farms', icon: Tractor },
        { name: 'Harvest Lots', href: '/harvest-lots', icon: Package },
        { name: 'Scale Devices', href: '/scale-devices', icon: Scale },
        { name: 'Label Templates', href: '/label-templates', icon: Tag },
        { name: 'Gari Production', href: '/gari-production-batches', icon: Factory },
        { name: 'Gari Inventory', href: '/gari-inventory', icon: Package },
        { name: 'Gari Sales', href: '/gari-sales', icon: ShoppingCart },
        { name: 'Gari KPIs', href: '/gari-kpis', icon: TrendingUp },
        { name: 'Packaging Materials', href: '/packaging-materials', icon: Package },
    ];

    return (
        <div className="min-h-screen bg-gray-50">
            {/* Sidebar */}
            <div className="hidden md:flex md:w-64 md:flex-col md:fixed md:inset-y-0">
                <div className="flex flex-col flex-grow bg-white border-r border-gray-200">
                    <div className="flex items-center flex-shrink-0 px-4 py-6 border-b border-gray-200">
                        <Tractor className="h-8 w-8 text-green-600" />
                        <h1 className="ml-2 text-xl font-bold text-gray-900">FMS</h1>
                    </div>
                    <div className="flex-grow flex flex-col overflow-y-auto">
                        <nav className="flex-1 px-2 py-4 space-y-1">
                            {navigation.map((item) => {
                                const isActive = location.pathname === item.href;
                                return (
                                    <Link
                                        key={item.name}
                                        to={item.href}
                                        className={`flex items-center px-4 py-3 text-sm font-medium rounded-lg transition-colors ${
                                            isActive
                                                ? 'bg-green-50 text-green-700'
                                                : 'text-gray-700 hover:bg-gray-50'
                                        }`}
                                    >
                                        <item.icon className="mr-3 h-5 w-5" />
                                        {item.name}
                                    </Link>
                                );
                            })}
                        </nav>
                    </div>
                    <div className="flex-shrink-0 border-t border-gray-200 p-4">
                        <div className="flex items-center">
                            <div className="flex-shrink-0">
                                <div className="h-10 w-10 rounded-full bg-green-100 flex items-center justify-center">
                                    <span className="text-green-600 font-medium">
                                        {user?.email?.charAt(0).toUpperCase() || 'U'}
                                    </span>
                                </div>
                            </div>
                            <div className="ml-3 flex-1 min-w-0">
                                <p className="text-sm font-medium text-gray-900 truncate">
                                    {user?.name || user?.email || 'User'}
                                </p>
                                <p className="text-xs text-gray-500 truncate">
                                    {user?.email}
                                </p>
                            </div>
                            <button
                                onClick={handleLogout}
                                className="ml-2 p-2 text-gray-400 hover:text-gray-600"
                                title="Logout"
                            >
                                <LogOut className="h-5 w-5" />
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            {/* Mobile menu */}
            <div className="md:hidden">
                <div className="bg-white border-b border-gray-200 px-4 py-3 flex items-center justify-between">
                    <div className="flex items-center">
                        <Tractor className="h-6 w-6 text-green-600" />
                        <h1 className="ml-2 text-lg font-bold text-gray-900">FMS</h1>
                    </div>
                    <button
                        onClick={() => setMobileMenuOpen(!mobileMenuOpen)}
                        className="p-2 text-gray-400 hover:text-gray-600"
                    >
                        {mobileMenuOpen ? (
                            <X className="h-6 w-6" />
                        ) : (
                            <Menu className="h-6 w-6" />
                        )}
                    </button>
                </div>
                {mobileMenuOpen && (
                    <div className="bg-white border-b border-gray-200">
                        <nav className="px-2 py-2 space-y-1">
                            {navigation.map((item) => {
                                const isActive = location.pathname === item.href;
                                return (
                                    <Link
                                        key={item.name}
                                        to={item.href}
                                        onClick={() => setMobileMenuOpen(false)}
                                        className={`flex items-center px-4 py-3 text-sm font-medium rounded-lg ${
                                            isActive
                                                ? 'bg-green-50 text-green-700'
                                                : 'text-gray-700 hover:bg-gray-50'
                                        }`}
                                    >
                                        <item.icon className="mr-3 h-5 w-5" />
                                        {item.name}
                                    </Link>
                                );
                            })}
                            <button
                                onClick={handleLogout}
                                className="w-full flex items-center px-4 py-3 text-sm font-medium text-gray-700 hover:bg-gray-50 rounded-lg"
                            >
                                <LogOut className="mr-3 h-5 w-5" />
                                Logout
                            </button>
                        </nav>
                    </div>
                )}
            </div>

            {/* Main content */}
            <div className="md:pl-64">
                <main className="py-6">
                    <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                        {children}
                    </div>
                </main>
            </div>
        </div>
    );
}

