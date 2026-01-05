import React, { useState, useEffect } from 'react';
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
    ChevronDown,
    ChevronRight,
    Users,
    DollarSign,
    BarChart3,
    Sprout,
    Beef,
    MapPin,
    Layers,
    Settings,
} from 'lucide-react';

export default function Layout({ children }) {
    const { user, logout } = useAuth();
    const location = useLocation();
    const navigate = useNavigate();
    const [mobileMenuOpen, setMobileMenuOpen] = useState(false);
    
    // Auto-expand groups based on current route
    const getInitialExpandedGroups = () => {
        const path = location.pathname;
        return {
            gari: path.startsWith('/gari'),
            bellPepper: true, // Always expanded by default for easy access
            tomatoes: path.startsWith('/tomatoes'),
            habaneros: path.startsWith('/habaneros'),
            livestock: false,
        };
    };

    const [expandedGroups, setExpandedGroups] = useState(getInitialExpandedGroups());

    // Update expanded groups when location changes
    useEffect(() => {
        const path = location.pathname;
        setExpandedGroups(prev => ({
            ...prev,
            gari: path.startsWith('/gari') ? true : prev.gari,
            bellPepper: path.startsWith('/bell-pepper') || path.startsWith('/greenhouses') || path.startsWith('/boreholes') ? true : prev.bellPepper,
            tomatoes: path.startsWith('/tomatoes') ? true : prev.tomatoes,
            habaneros: path.startsWith('/habaneros') ? true : prev.habaneros,
        }));
    }, [location.pathname]);

    const handleLogout = () => {
        logout();
        navigate('/login');
    };

    const toggleGroup = (group) => {
        setExpandedGroups(prev => ({
            ...prev,
            [group]: !prev[group]
        }));
    };

    const navigationGroups = [
        {
            name: 'General',
            items: [
                { name: 'Dashboard', href: '/', icon: LayoutDashboard },
                { name: 'Farms', href: '/farms', icon: Tractor },
                { name: 'Harvest Lots', href: '/harvest-lots', icon: Package },
                { name: 'Scale Devices', href: '/scale-devices', icon: Scale },
                { name: 'Label Templates', href: '/label-templates', icon: Tag },
                { name: 'Staff & Labor', href: '/staff-labor', icon: Users },
            ],
        },
        {
            name: 'Gari',
            key: 'gari',
            icon: Factory,
            items: [
                { name: 'Production Batches', href: '/gari-production-batches', icon: Factory },
                { name: 'Inventory', href: '/gari-inventory', icon: Package },
                { name: 'Sales', href: '/gari-sales', icon: ShoppingCart },
                { name: 'KPIs', href: '/gari-kpis', icon: TrendingUp },
                { name: 'Waste & Losses', href: '/gari-waste-losses', icon: TrendingUp },
                { name: 'Packaging Materials', href: '/packaging-materials', icon: Package },
            ],
        },
        {
            name: 'Bell Pepper',
            key: 'bellPepper',
            icon: Sprout,
            items: [
                { name: 'Greenhouses', href: '/greenhouses', icon: Factory },
                { name: 'Boreholes', href: '/boreholes', icon: Factory },
                { name: 'Production', href: '/bell-pepper-production', icon: Factory },
                { name: 'Harvests', href: '/bell-pepper-harvests', icon: Package },
                { name: 'Inventory', href: '/bell-pepper-inventory', icon: Package },
                { name: 'Sales', href: '/bell-pepper-sales', icon: ShoppingCart },
                { name: 'KPIs', href: '/bell-pepper-kpis', icon: TrendingUp },
            ],
        },
        {
            name: 'Tomatoes',
            key: 'tomatoes',
            icon: Sprout,
            items: [
                { name: 'Production', href: '/tomatoes-production', icon: Factory },
                { name: 'Inventory', href: '/tomatoes-inventory', icon: Package },
                { name: 'Sales', href: '/tomatoes-sales', icon: ShoppingCart },
                { name: 'KPIs', href: '/tomatoes-kpis', icon: TrendingUp },
            ],
        },
        {
            name: 'Habaneros',
            key: 'habaneros',
            icon: Sprout,
            items: [
                { name: 'Production', href: '/habaneros-production', icon: Factory },
                { name: 'Inventory', href: '/habaneros-inventory', icon: Package },
                { name: 'Sales', href: '/habaneros-sales', icon: ShoppingCart },
                { name: 'KPIs', href: '/habaneros-kpis', icon: TrendingUp },
            ],
        },
        {
            name: 'Reports',
            items: [
                { name: 'Consolidated Sales', href: '/reports/consolidated-sales', icon: DollarSign },
                { name: 'Consolidated Expenses', href: '/reports/consolidated-expenses', icon: BarChart3 },
                { name: 'Staff Allocation', href: '/reports/staff-allocation', icon: Users },
            ],
        },
        {
            name: 'Admin Settings',
            items: [
                { name: 'Locations', href: '/admin/locations', icon: MapPin },
                { name: 'Admin Zones', href: '/admin/admin-zones', icon: Layers },
            ],
        },
    ];

    const isActive = (href) => {
        if (href === '/') {
            return location.pathname === '/';
        }
        return location.pathname.startsWith(href);
    };

    const isGroupActive = (group) => {
        return group.items.some(item => isActive(item.href));
    };

    const renderNavItem = (item) => {
        const active = isActive(item.href);
        return (
            <Link
                key={item.name}
                to={item.href}
                onClick={() => setMobileMenuOpen(false)}
                className={`flex items-center px-4 py-2.5 text-sm font-medium rounded-lg transition-colors ml-4 ${
                    active
                        ? 'bg-green-50 text-green-700'
                        : 'text-gray-700 hover:bg-gray-50'
                }`}
            >
                <item.icon className="mr-3 h-4 w-4" />
                {item.name}
            </Link>
        );
    };

    const renderNavGroup = (group) => {
        if (group.key) {
            // Collapsible group
            const expanded = expandedGroups[group.key];
            const groupActive = isGroupActive(group);
            
            return (
                <div key={group.name}>
                    <button
                        onClick={() => toggleGroup(group.key)}
                        className={`w-full flex items-center justify-between px-4 py-2.5 text-sm font-semibold rounded-lg transition-colors ${
                            groupActive
                                ? 'bg-green-50 text-green-700'
                                : 'text-gray-700 hover:bg-gray-50'
                        }`}
                    >
                        <div className="flex items-center">
                            <group.icon className="mr-3 h-5 w-5" />
                            {group.name}
                        </div>
                        {expanded ? (
                            <ChevronDown className="h-4 w-4" />
                        ) : (
                            <ChevronRight className="h-4 w-4" />
                        )}
                    </button>
                    {expanded && (
                        <div className="mt-1 space-y-1">
                            {group.items.map(renderNavItem)}
                        </div>
                    )}
                </div>
            );
        } else {
            // Regular group (no collapse)
            return (
                <div key={group.name}>
                    <div className="px-4 py-2 text-xs font-semibold text-gray-500 uppercase tracking-wider">
                        {group.name}
                    </div>
                    <div className="mt-1 space-y-1">
                        {group.items.map(renderNavItem)}
                    </div>
                </div>
            );
        }
    };

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
                        <nav className="flex-1 px-2 py-4 space-y-2">
                            {navigationGroups.map(renderNavGroup)}
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
                    <div className="bg-white border-b border-gray-200 max-h-[calc(100vh-64px)] overflow-y-auto">
                        <nav className="px-2 py-2 space-y-2">
                            {navigationGroups.map(renderNavGroup)}
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
