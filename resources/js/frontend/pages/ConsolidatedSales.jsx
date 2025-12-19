import React, { useEffect, useState } from 'react';
import api from '../utils/api';
import { DollarSign, TrendingUp, Package } from 'lucide-react';

export default function ConsolidatedSales() {
    const [sales, setSales] = useState([]);
    const [summary, setSummary] = useState({
        totalRevenue: 0,
        totalMargin: 0,
        totalSales: 0,
        byCrop: {},
    });
    const [loading, setLoading] = useState(true);
    const [dateRange, setDateRange] = useState({
        from: new Date(Date.now() - 30 * 24 * 60 * 60 * 1000).toISOString().slice(0, 10),
        to: new Date().toISOString().slice(0, 10),
    });

    useEffect(() => {
        fetchSalesData();
    }, [dateRange]);

    const fetchSalesData = async () => {
        try {
            setLoading(true);
            // Fetch sales from all crop modules
            const [gariSales] = await Promise.all([
                api.get(`/api/v1/gari-sales/summary?date_from=${dateRange.from}&date_to=${dateRange.to}`).catch(() => ({ data: { overall: {} } })),
            ]);

            const gariData = gariSales.data?.overall || {};
            
            setSummary({
                totalRevenue: Number(gariData.total_revenue || 0),
                totalMargin: Number(gariData.total_margin || 0),
                totalSales: Number(gariData.total_sales || 0),
                byCrop: {
                    gari: {
                        revenue: Number(gariData.total_revenue || 0),
                        margin: Number(gariData.total_margin || 0),
                        sales: Number(gariData.total_sales || 0),
                    },
                    bellPepper: { revenue: 0, margin: 0, sales: 0 },
                    tomatoes: { revenue: 0, margin: 0, sales: 0 },
                    habaneros: { revenue: 0, margin: 0, sales: 0 },
                },
            });
        } catch (error) {
            console.error('Error fetching sales data:', error);
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

    return (
        <div>
            <div className="flex justify-between items-center mb-8">
                <div>
                    <h1 className="text-3xl font-bold text-gray-900">Consolidated Sales</h1>
                    <p className="mt-2 text-gray-600">Sales across all crops and livestock</p>
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
            <div className="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
                <div className="bg-white rounded-lg shadow p-6">
                    <div className="flex items-center justify-between mb-2">
                        <p className="text-sm text-gray-600">Total Revenue</p>
                        <DollarSign className="h-5 w-5 text-green-600" />
                    </div>
                    <p className="text-3xl font-bold text-gray-900">
                        ₦{Number(summary.totalRevenue || 0).toFixed(2)}
                    </p>
                </div>
                <div className="bg-white rounded-lg shadow p-6">
                    <div className="flex items-center justify-between mb-2">
                        <p className="text-sm text-gray-600">Gross Margin</p>
                        <TrendingUp className="h-5 w-5 text-green-600" />
                    </div>
                    <p className="text-3xl font-bold text-green-600">
                        ₦{Number(summary.totalMargin || 0).toFixed(2)}
                    </p>
                </div>
                <div className="bg-white rounded-lg shadow p-6">
                    <div className="flex items-center justify-between mb-2">
                        <p className="text-sm text-gray-600">Total Sales</p>
                        <Package className="h-5 w-5 text-blue-600" />
                    </div>
                    <p className="text-3xl font-bold text-gray-900">
                        {summary.totalSales}
                    </p>
                </div>
            </div>

            {/* Sales by Crop */}
            <div className="bg-white rounded-lg shadow">
                <div className="px-6 py-4 border-b border-gray-200">
                    <h2 className="text-lg font-semibold text-gray-900">Sales by Crop</h2>
                </div>
                <div className="overflow-x-auto">
                    <table className="min-w-full divide-y divide-gray-200">
                        <thead className="bg-gray-50">
                            <tr>
                                <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Crop</th>
                                <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Revenue</th>
                                <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Margin</th>
                                <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Sales Count</th>
                            </tr>
                        </thead>
                        <tbody className="bg-white divide-y divide-gray-200">
                            {Object.entries(summary.byCrop).map(([key, data]) => (
                                <tr key={key}>
                                    <td className="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900 capitalize">
                                        {key === 'bellPepper' ? 'Bell Pepper' : key}
                                    </td>
                                    <td className="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                        ₦{Number(data.revenue || 0).toFixed(2)}
                                    </td>
                                    <td className="px-6 py-4 whitespace-nowrap text-sm text-green-600">
                                        ₦{Number(data.margin || 0).toFixed(2)}
                                    </td>
                                    <td className="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                        {data.sales}
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

