import React, { useEffect, useState } from 'react';
import api from '../utils/api';
import { BarChart3, TrendingDown, DollarSign } from 'lucide-react';

export default function ConsolidatedExpenses() {
    const [expenses, setExpenses] = useState({
        total: 0,
        byCrop: {},
        byCategory: {},
    });
    const [loading, setLoading] = useState(true);
    const [dateRange, setDateRange] = useState({
        from: new Date(Date.now() - 30 * 24 * 60 * 60 * 1000).toISOString().slice(0, 10),
        to: new Date().toISOString().slice(0, 10),
    });

    useEffect(() => {
        fetchExpensesData();
    }, [dateRange]);

    const fetchExpensesData = async () => {
        try {
            setLoading(true);
            // Fetch expenses from all crop modules
            const [gariBatches] = await Promise.all([
                api.get(`/api/v1/gari-production-batches?date_from=${dateRange.from}&date_to=${dateRange.to}`).catch(() => ({ data: { data: [] } })),
            ]);

            const gariBatchesData = Array.isArray(gariBatches.data?.data) ? gariBatches.data.data : [];
            
            const totalExpenses = gariBatchesData.reduce((sum, batch) => {
                return sum + (Number(batch.total_cost || 0));
            }, 0);

            setExpenses({
                total: totalExpenses,
                byCrop: {
                    gari: totalExpenses,
                    bellPepper: 0,
                    tomatoes: 0,
                    habaneros: 0,
                },
                byCategory: {
                    production: totalExpenses,
                    labor: 0,
                    materials: 0,
                    other: 0,
                },
            });
        } catch (error) {
            console.error('Error fetching expenses data:', error);
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
                    <h1 className="text-3xl font-bold text-gray-900">Consolidated Expenses</h1>
                    <p className="mt-2 text-gray-600">Expenses across all crops and activities</p>
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

            {/* Summary Card */}
            <div className="bg-white rounded-lg shadow p-6 mb-8">
                <div className="flex items-center justify-between mb-2">
                    <p className="text-sm text-gray-600">Total Expenses</p>
                    <BarChart3 className="h-5 w-5 text-red-600" />
                </div>
                <p className="text-3xl font-bold text-gray-900">
                    ₦{Number(expenses.total || 0).toFixed(2)}
                </p>
            </div>

            {/* Expenses by Crop */}
            <div className="bg-white rounded-lg shadow mb-6">
                <div className="px-6 py-4 border-b border-gray-200">
                    <h2 className="text-lg font-semibold text-gray-900">Expenses by Crop</h2>
                </div>
                <div className="overflow-x-auto">
                    <table className="min-w-full divide-y divide-gray-200">
                        <thead className="bg-gray-50">
                            <tr>
                                <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Crop</th>
                                <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Amount</th>
                                <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Percentage</th>
                            </tr>
                        </thead>
                        <tbody className="bg-white divide-y divide-gray-200">
                            {Object.entries(expenses.byCrop).map(([key, amount]) => (
                                <tr key={key}>
                                    <td className="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900 capitalize">
                                        {key === 'bellPepper' ? 'Bell Pepper' : key}
                                    </td>
                                    <td className="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                        ₦{Number(amount || 0).toFixed(2)}
                                    </td>
                                    <td className="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                        {expenses.total > 0 
                                            ? Number((amount / expenses.total) * 100).toFixed(1) + '%'
                                            : '0%'}
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

