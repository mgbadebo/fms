import React, { useEffect, useState } from 'react';
import { Link } from 'react-router-dom';
import api from '../utils/api';
import { Tractor, Package, Scale, TrendingUp } from 'lucide-react';

export default function Dashboard() {
    const [stats, setStats] = useState({
        farms: 0,
        harvestLots: 0,
        scaleDevices: 0,
        recentHarvests: [],
    });
    const [loading, setLoading] = useState(true);

    useEffect(() => {
        fetchDashboardData();
    }, []);

    const fetchDashboardData = async () => {
        try {
            const [farmsRes, harvestLotsRes, scaleDevicesRes] = await Promise.all([
                api.get('/api/v1/farms'),
                api.get('/api/v1/harvest-lots?per_page=5'),
                api.get('/api/v1/scale-devices'),
            ]);

            setStats({
                farms: farmsRes.data.total || farmsRes.data.data?.length || 0,
                harvestLots: harvestLotsRes.data.total || harvestLotsRes.data.data?.length || 0,
                scaleDevices: scaleDevicesRes.data.total || scaleDevicesRes.data.data?.length || 0,
                recentHarvests: harvestLotsRes.data.data || [],
            });
        } catch (error) {
            console.error('Error fetching dashboard data:', error);
        } finally {
            setLoading(false);
        }
    };

    if (loading) {
        return (
            <div className="flex items-center justify-center h-64">
                <div className="animate-spin rounded-full h-12 w-12 border-b-2 border-green-600"></div>
            </div>
        );
    }

    const statCards = [
        {
            name: 'Total Farms',
            value: stats.farms,
            icon: Tractor,
            color: 'bg-green-500',
            href: '/farms',
        },
        {
            name: 'Harvest Lots',
            value: stats.harvestLots,
            icon: Package,
            color: 'bg-blue-500',
            href: '/harvest-lots',
        },
        {
            name: 'Scale Devices',
            value: stats.scaleDevices,
            icon: Scale,
            color: 'bg-purple-500',
            href: '/scale-devices',
        },
    ];

    return (
        <div>
            <div className="mb-8">
                <h1 className="text-3xl font-bold text-gray-900">Dashboard</h1>
                <p className="mt-2 text-gray-600">Overview of your farm operations</p>
            </div>

            {/* Stats Grid */}
            <div className="grid grid-cols-1 gap-6 sm:grid-cols-2 lg:grid-cols-3 mb-8">
                {statCards.map((stat) => (
                    <Link
                        key={stat.name}
                        to={stat.href}
                        className="bg-white rounded-lg shadow p-6 hover:shadow-lg transition-shadow"
                    >
                        <div className="flex items-center">
                            <div className={`${stat.color} p-3 rounded-lg`}>
                                <stat.icon className="h-6 w-6 text-white" />
                            </div>
                            <div className="ml-4">
                                <p className="text-sm font-medium text-gray-600">{stat.name}</p>
                                <p className="text-2xl font-bold text-gray-900">{stat.value}</p>
                            </div>
                        </div>
                    </Link>
                ))}
            </div>

            {/* Recent Harvests */}
            <div className="bg-white rounded-lg shadow">
                <div className="px-6 py-4 border-b border-gray-200">
                    <h2 className="text-lg font-semibold text-gray-900">Recent Harvest Lots</h2>
                </div>
                <div className="divide-y divide-gray-200">
                    {stats.recentHarvests.length > 0 ? (
                        stats.recentHarvests.map((harvest) => (
                            <Link
                                key={harvest.id}
                                to={`/harvest-lots/${harvest.id}`}
                                className="block px-6 py-4 hover:bg-gray-50 transition-colors"
                            >
                                <div className="flex items-center justify-between">
                                    <div>
                                        <p className="text-sm font-medium text-gray-900">
                                            {harvest.code || `Harvest #${harvest.id}`}
                                        </p>
                                        <p className="text-sm text-gray-500">
                                            {harvest.farm?.name || 'Unknown Farm'} • {harvest.field?.name || 'Unknown Field'}
                                        </p>
                                    </div>
                                    <div className="text-right">
                                        <p className="text-sm font-medium text-gray-900">
                                            {harvest.net_weight || harvest.gross_weight || 'N/A'} {harvest.weight_unit || 'kg'}
                                        </p>
                                        <p className="text-xs text-gray-500">
                                            {new Date(harvest.harvested_at).toLocaleDateString()}
                                        </p>
                                    </div>
                                </div>
                            </Link>
                        ))
                    ) : (
                        <div className="px-6 py-8 text-center text-gray-500">
                            <Package className="h-12 w-12 mx-auto text-gray-400 mb-2" />
                            <p>No harvest lots yet</p>
                            <Link
                                to="/harvest-lots"
                                className="mt-2 text-green-600 hover:text-green-700 text-sm font-medium"
                            >
                                Create your first harvest lot →
                            </Link>
                        </div>
                    )}
                </div>
            </div>
        </div>
    );
}

