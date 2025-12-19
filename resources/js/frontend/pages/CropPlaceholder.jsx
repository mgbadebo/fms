import React from 'react';
import { Factory, Package, ShoppingCart, TrendingUp } from 'lucide-react';
import { Link } from 'react-router-dom';

export default function CropPlaceholder({ cropName, basePath }) {
    const menuItems = [
        { name: 'Production', path: `${basePath}-production`, icon: Factory },
        { name: 'Inventory', path: `${basePath}-inventory`, icon: Package },
        { name: 'Sales', path: `${basePath}-sales`, icon: ShoppingCart },
        { name: 'KPIs', path: `${basePath}-kpis`, icon: TrendingUp },
    ];

    return (
        <div>
            <div className="mb-8">
                <h1 className="text-3xl font-bold text-gray-900">{cropName} Management</h1>
                <p className="mt-2 text-gray-600">Coming soon - {cropName} tracking and management</p>
            </div>

            <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
                {menuItems.map((item) => (
                    <Link
                        key={item.path}
                        to={`/${item.path}`}
                        className="bg-white rounded-lg shadow p-6 hover:shadow-lg transition-shadow"
                    >
                        <item.icon className="h-8 w-8 text-green-600 mb-4" />
                        <h3 className="text-lg font-semibold text-gray-900">{item.name}</h3>
                        <p className="text-sm text-gray-500 mt-2">View {item.name.toLowerCase()} for {cropName}</p>
                    </Link>
                ))}
            </div>

            <div className="mt-8 bg-yellow-50 border border-yellow-200 rounded-lg p-6">
                <h3 className="text-lg font-semibold text-yellow-800 mb-2">Feature Coming Soon</h3>
                <p className="text-yellow-700">
                    The {cropName} management module is under development. This will include production tracking, 
                    inventory management, sales tracking, and performance KPIs specific to {cropName}.
                </p>
            </div>
        </div>
    );
}

