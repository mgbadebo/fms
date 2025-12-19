import React, { useEffect, useState } from 'react';
import api from '../utils/api';
import { DollarSign, TrendingUp, Package, Users, Factory, BarChart3 } from 'lucide-react';

export default function ConsolidatedDashboard() {
    const [stats, setStats] = useState({
        totalSales: 0,
        totalExpenses: 0,
        totalRevenue: 0,
        totalMargin: 0,
        crops: {},
        loading: true,
    });
    const [dateRange, setDateRange] = useState({
        from: new Date(Date.now() - 30 * 24 * 60 * 60 * 1000).toISOString().slice(0, 10),
        to: new Date().toISOString().slice(0, 10),
    });

    useEffect(() => {
        fetchConsolidatedData();
    }, [dateRange]);

    const fetchConsolidatedData = async () => {
        try {
            setStats(prev => ({ ...prev, loading: true }));
            
            // Fetch data from all crop modules
            const [gariSales, gariBatches] = await Promise.all([
                api.get(`/api/v1/gari-sales/summary?date_from=${dateRange.from}&date_to=${dateRange.to}`).catch(() => ({ data: { overall: {} } })),
                api.get(`/api/v1/gari-production-batches?date_from=${dateRange.from}&date_to=${dateRange.to}`).catch(() => ({ data: { data: [] } })),
            ]);

            const gariSalesData = gariSales.data?.overall || {};
            const gariBatchesData = Array.isArray(gariBatches.data?.data) ? gariBatches.data.data : [];

            // Calculate totals
            const totalRevenue = Number(gariSalesData.total_revenue || 0);
            const totalCost = Number(gariSalesData.total_cost || 0);
            const totalMargin = Number(gariSalesData.total_margin || 0);
            
            // Calculate expenses from production batches
            const totalExpenses = gariBatchesData.reduce((sum, batch) => {
                return sum + (Number(batch.total_cost || 0));
            }, 0);

            setStats({
                totalSales: Number(gariSalesData.total_sales || 0),
                totalExpenses,
                totalRevenue,
                totalMargin,
                crops: {
                    gari: {
                        sales: totalRevenue,
                        expenses: totalExpenses,
                        margin: totalMargin,
                        batches: gariBatchesData.length,
                    },
                    bellPepper: { sales: 0, expenses: 0, margin: 0, batches: 0 },
                    tomatoes: { sales: 0, expenses: 0, margin: 0, batches: 0 },
                    habaneros: { sales: 0, expenses: 0, margin: 0, batches: 0 },
                },
                loading: false,
            });
        } catch (error) {
            console.error('Error fetching consolidated data:', error);
            setStats(prev => ({ ...prev, loading: false }));
        }
    };

    if (stats.loading) {
        return (
            <div className="flex items-center justify-center h-64">
                <div className="animate-spin rounded-full h-12 w-12 border-b-2 border-green-600"></div>
            </div>
        );
    }

    return (
        <div>
            <div className="flex justify-between items-center mb-8">
                <div>
                    <h1 className="text-3xl font-bold text-gray-900">Consolidated Dashboard</h1>
                    <p className="mt-2 text-gray-600">Overview across all crops and livestock</p>
                </div>
                <div className="flex space-x-2">
                    <input
                        type="date"
                        value={dateRange.from}
                        onChange={(e) => setDateRange({ ...dateRange, from: e.target.value })}
                        className="px-3 py-2 border border-gray-300 rounded-lg focus:ring-green-500 focus:border-green-500"
                    />
                    <input
                        type="date"
                        value={dateRange.to}
                        onChange={(e) => setDateRange({ ...dateRange, to: e.target.value })}
                        className="px-3 py-2 border border-gray-300 rounded-lg focus:ring-green-500 focus:border-green-500"
                    />
                </div>
            </div>

            {/* Summary Cards */}
            <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
                <div className="bg-white rounded-lg shadow p-6">
                    <div className="flex items-center justify-between mb-2">
                        <p className="text-sm text-gray-600">Total Revenue</p>
                        <DollarSign className="h-5 w-5 text-green-600" />
                    </div>
                    <p className="text-3xl font-bold text-gray-900">
                        ₦{Number(stats.totalRevenue || 0).toFixed(2)}
                    </p>
                    <p className="text-xs text-gray-500 mt-1">All crops combined</p>
                </div>

                <div className="bg-white rounded-lg shadow p-6">
                    <div className="flex items-center justify-between mb-2">
                        <p className="text-sm text-gray-600">Total Expenses</p>
                        <BarChart3 className="h-5 w-5 text-red-600" />
                    </div>
                    <p className="text-3xl font-bold text-gray-900">
                        ₦{Number(stats.totalExpenses || 0).toFixed(2)}
                    </p>
                    <p className="text-xs text-gray-500 mt-1">Production costs</p>
                </div>

                <div className="bg-white rounded-lg shadow p-6">
                    <div className="flex items-center justify-between mb-2">
                        <p className="text-sm text-gray-600">Gross Margin</p>
                        <TrendingUp className="h-5 w-5 text-green-600" />
                    </div>
                    <p className="text-3xl font-bold text-green-600">
                        ₦{Number(stats.totalMargin || 0).toFixed(2)}
                    </p>
                    <p className="text-xs text-gray-500 mt-1">
                        {stats.totalRevenue > 0 
                            ? Number((stats.totalMargin / stats.totalRevenue) * 100).toFixed(1) + '% margin'
                            : 'No sales'}
                    </p>
                </div>

                <div className="bg-white rounded-lg shadow p-6">
                    <div className="flex items-center justify-between mb-2">
                        <p className="text-sm text-gray-600">Total Sales</p>
                        <Package className="h-5 w-5 text-blue-600" />
                    </div>
                    <p className="text-3xl font-bold text-gray-900">
                        {stats.totalSales}
                    </p>
                    <p className="text-xs text-gray-500 mt-1">Transactions</p>
                </div>
            </div>

            {/* Crop Breakdown */}
            <div className="bg-white rounded-lg shadow mb-8">
                <div className="px-6 py-4 border-b border-gray-200">
                    <h2 className="text-lg font-semibold text-gray-900">Performance by Crop</h2>
                </div>
                <div className="overflow-x-auto">
                    <table className="min-w-full divide-y divide-gray-200">
                        <thead className="bg-gray-50">
                            <tr>
                                <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Crop</th>
                                <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Revenue</th>
                                <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Expenses</th>
                                <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Margin</th>
                                <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Batches</th>
                            </tr>
                        </thead>
                        <tbody className="bg-white divide-y divide-gray-200">
                            {Object.entries(stats.crops).map(([key, data]) => (
                                <tr key={key}>
                                    <td className="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900 capitalize">
                                        {key === 'bellPepper' ? 'Bell Pepper' : key}
                                    </td>
                                    <td className="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                        ₦{Number(data.sales || 0).toFixed(2)}
                                    </td>
                                    <td className="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                        ₦{Number(data.expenses || 0).toFixed(2)}
                                    </td>
                                    <td className="px-6 py-4 whitespace-nowrap text-sm text-green-600">
                                        ₦{Number(data.margin || 0).toFixed(2)}
                                    </td>
                                    <td className="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                        {data.batches}
                                    </td>
                                </tr>
                            ))}
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    );
}

