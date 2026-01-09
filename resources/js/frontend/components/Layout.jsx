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
    Shield,
    Grid3x3,
    Building2,
    UserCheck,
    Warehouse,
    Wrench,
    FolderTree,
    Briefcase,
} from 'lucide-react';

export default function Layout({ children }) {
    const { user, logout } = useAuth();
    const location = useLocation();
    const navigate = useNavigate();
    const [mobileMenuOpen, setMobileMenuOpen] = useState(false);
    const [userPermissions, setUserPermissions] = useState([]);
    const [logoError, setLogoError] = useState(false);
    
    // Auto-expand groups based on current route - all collapsed by default
    const getInitialExpandedGroups = () => {
        const path = location.pathname;
        return {
            gari: path.startsWith('/gari'),
            bellPepper: path.startsWith('/bell-pepper'),
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
            bellPepper: path.startsWith('/bell-pepper') ? true : prev.bellPepper,
            tomatoes: path.startsWith('/tomatoes') ? true : prev.tomatoes,
            habaneros: path.startsWith('/habaneros') ? true : prev.habaneros,
        }));
    }, [location.pathname]);

    // Load user permissions on mount
    useEffect(() => {
        if (user) {
            // Get permissions from user object or fetch them
            const permissions = user.permissions?.map(p => p.name) || 
                              user.roles?.flatMap(r => r.permissions?.map(p => p.name) || []) || [];
            setUserPermissions(permissions);
        }
    }, [user]);

    const handleLogout = () => {
        logout();
        navigate('/login');
    };

    const toggleGroup = (group) => {
        setExpandedGroups(prev => ({
            ...prev,
            [group]: !(prev[group] || false)
        }));
    };

    const navigationGroups = [
        {
            name: 'Bell Pepper',
            key: 'bellPepper',
            icon: Sprout,
            items: [
                { name: 'Production', href: '/bell-pepper-production', icon: Factory },
                { name: 'Harvests', href: '/bell-pepper-harvests', icon: Package },
                { name: 'Inventory', href: '/bell-pepper-inventory', icon: Package },
                { name: 'Sales', href: '/bell-pepper-sales', icon: ShoppingCart },
                { name: 'KPIs', href: '/bell-pepper-kpis', icon: TrendingUp },
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
            name: 'Reports',
            items: [
                { name: 'Dashboard', href: '/', icon: LayoutDashboard },
                { name: 'Consolidated Sales', href: '/reports/consolidated-sales', icon: DollarSign },
                { name: 'Consolidated Expenses', href: '/reports/consolidated-expenses', icon: BarChart3 },
                { name: 'Staff Allocation', href: '/reports/staff-allocation', icon: Users },
            ],
        },
        {
            name: 'Admin Settings',
            items: [
                { name: 'Farms', href: '/farms', icon: Tractor },
                { name: 'Sites', href: '/admin/sites', icon: Warehouse },
                { name: 'Farm Zones', href: '/admin/farm-zones', icon: Grid3x3 },
                { name: 'Factories', href: '/admin/factories', icon: Building2 },
                { name: 'Borehole Management', href: '/admin/boreholes', icon: Factory },
                { name: 'Greenhouse Management', href: '/admin/greenhouses', icon: Factory },
                { name: 'Scale Devices', href: '/scale-devices', icon: Scale },
                { name: 'Label Templates', href: '/label-templates', icon: Tag },
                { name: 'Crops', href: '/admin/crops', icon: Sprout },
                { name: 'Worker Job Roles', href: '/admin/worker-job-roles', icon: Briefcase },
                { name: 'Assets', href: '/admin/assets', icon: Wrench },
                { name: 'Asset Categories', href: '/admin/asset-categories', icon: FolderTree },
                { name: 'Site Types', href: '/admin/site-types', icon: Building2 },
                { name: 'Admin Zones', href: '/admin/admin-zones', icon: Layers },
                { name: 'Roles', href: '/admin/roles', icon: Shield },
                { name: 'Users', href: '/admin/users', icon: Users },
            ],
        },
    ];

    // Check if user has permission for a menu/submenu
    const hasPermission = (menuKey, submenuKey, permissionType = 'view') => {
        // Admin has all permissions
        if (user?.roles?.some(r => r.name === 'ADMIN')) {
            return true;
        }

        const permissionName = [menuKey, submenuKey, permissionType].filter(Boolean).join('.');
        return userPermissions.includes(permissionName);
    };

    // Check if user can access a menu item
    const canAccessMenuItem = (menuKey, submenuKey) => {
        return hasPermission(menuKey, submenuKey, 'view');
    };

    const isActive = (href) => {
        if (href === '/') {
            return location.pathname === '/';
        }
        return location.pathname.startsWith(href);
    };

    const isGroupActive = (group) => {
        return group.items.some(item => isActive(item.href));
    };

    // Map href to menu/submenu keys for permission checking
    const getMenuKeys = (href) => {
        const menuMap = {
            '/': { menu: 'reports', submenu: 'dashboard' },
            '/farms': { menu: 'admin', submenu: 'farms' },
            '/scale-devices': { menu: 'admin', submenu: 'scale-devices' },
            '/label-templates': { menu: 'admin', submenu: 'label-templates' },
            '/gari-production-batches': { menu: 'gari', submenu: 'production-batches' },
            '/gari-inventory': { menu: 'gari', submenu: 'inventory' },
            '/gari-sales': { menu: 'gari', submenu: 'sales' },
            '/gari-kpis': { menu: 'gari', submenu: 'kpis' },
            '/gari-waste-losses': { menu: 'gari', submenu: 'waste-losses' },
            '/packaging-materials': { menu: 'gari', submenu: 'packaging-materials' },
            '/admin/boreholes': { menu: 'admin', submenu: 'boreholes' },
            '/admin/greenhouses': { menu: 'admin', submenu: 'greenhouses' },
            '/bell-pepper-production': { menu: 'bell-pepper', submenu: 'production' },
            '/bell-pepper-harvests': { menu: 'bell-pepper', submenu: 'harvests' },
            '/bell-pepper-inventory': { menu: 'bell-pepper', submenu: 'inventory' },
            '/bell-pepper-sales': { menu: 'bell-pepper', submenu: 'sales' },
            '/bell-pepper-kpis': { menu: 'bell-pepper', submenu: 'kpis' },
            '/tomatoes-production': { menu: 'tomatoes', submenu: 'production' },
            '/tomatoes-inventory': { menu: 'tomatoes', submenu: 'inventory' },
            '/tomatoes-sales': { menu: 'tomatoes', submenu: 'sales' },
            '/tomatoes-kpis': { menu: 'tomatoes', submenu: 'kpis' },
            '/habaneros-production': { menu: 'habaneros', submenu: 'production' },
            '/habaneros-inventory': { menu: 'habaneros', submenu: 'inventory' },
            '/habaneros-sales': { menu: 'habaneros', submenu: 'sales' },
            '/habaneros-kpis': { menu: 'habaneros', submenu: 'kpis' },
            '/reports/consolidated-sales': { menu: 'reports', submenu: 'consolidated-sales' },
            '/reports/consolidated-expenses': { menu: 'reports', submenu: 'consolidated-expenses' },
            '/reports/staff-allocation': { menu: 'reports', submenu: 'staff-allocation' },
            '/admin/admin-zones': { menu: 'admin', submenu: 'admin-zones' },
            '/admin/sites': { menu: 'admin', submenu: 'sites' },
            '/admin/farm-zones': { menu: 'admin', submenu: 'farm-zones' },
            '/admin/factories': { menu: 'admin', submenu: 'factories' },
            '/admin/crops': { menu: 'admin', submenu: 'crops' },
            '/admin/worker-job-roles': { menu: 'admin', submenu: 'worker-job-roles' },
            '/admin/assets': { menu: 'admin', submenu: 'assets' },
            '/admin/asset-categories': { menu: 'admin', submenu: 'asset-categories' },
            '/admin/site-types': { menu: 'admin', submenu: 'site-types' },
            '/admin/roles': { menu: 'admin', submenu: 'roles' },
            '/admin/users': { menu: 'admin', submenu: 'users' },
        };

        return menuMap[href] || { menu: null, submenu: null };
    };

    const renderNavItem = (item) => {
        const { menu, submenu } = getMenuKeys(item.href);
        
        // Check permission for all menu items including dashboard
        if (!canAccessMenuItem(menu, submenu)) {
            return null;
        }

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
            const expanded = expandedGroups[group.key] || false;
            const groupActive = isGroupActive(group);
            
            return (
                <div key={group.name}>
                    <button
                        type="button"
                        onClick={(e) => {
                            e.preventDefault();
                            e.stopPropagation();
                            toggleGroup(group.key);
                        }}
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
                <div className="flex flex-col h-full bg-white border-r border-gray-200">
                    <div className="flex items-center flex-shrink-0 px-4 py-4 border-b border-gray-200 flex-nowrap">
                        {logoError ? (
                            <Tractor className="h-8 w-8 text-green-600 flex-shrink-0" />
                        ) : (
                            <img 
                                src="/images/ogenki-logo.png" 
                                alt="Ogenki Farms" 
                                className="h-12 w-auto object-contain max-w-[120px] flex-shrink-0"
                                onError={() => setLogoError(true)}
                            />
                        )}
                        <h1 className="ml-2 text-sm font-bold text-gray-900 whitespace-nowrap truncate">Ogenki Farms</h1>
                    </div>
                    <div className="flex-1 flex flex-col min-h-0 overflow-hidden">
                        <nav className="flex-1 px-2 py-4 space-y-2 overflow-y-auto">
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
                    <div className="flex items-center flex-nowrap flex-1 min-w-0">
                        {logoError ? (
                            <Tractor className="h-6 w-6 text-green-600 flex-shrink-0" />
                        ) : (
                            <img 
                                src="/images/ogenki-logo.png" 
                                alt="Ogenki Farms" 
                                className="h-10 w-auto object-contain max-w-[100px] flex-shrink-0"
                                onError={() => setLogoError(true)}
                            />
                        )}
                        <h1 className="ml-2 text-sm font-bold text-gray-900 whitespace-nowrap truncate">Ogenki Farms</h1>
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
