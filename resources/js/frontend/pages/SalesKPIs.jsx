import React, { useEffect, useState } from 'react';
import api from '../utils/api';
import { TrendingUp, DollarSign, Package, BarChart3 } from 'lucide-react';

export default function SalesKPIs() {
    const [salesSummary, setSalesSummary] = useState(null);
    const [profitability, setProfitability] = useState([]);
    const [compliance, setCompliance] = useState(null);
    const [loading, setLoading] = useState(true);
    const [dateRange, setDateRange] = useState({
        from: new Date(new Date().setMonth(new Date().getMonth() - 1)).toISOString().slice(0, 10),
        to: new Date().toISOString().slice(0, 10),
    });

    useEffect(() => {
        fetchKPIs();
    }, [dateRange]);

    const fetchKPIs = async () => {
        setLoading(true);
        try {
            const [summaryRes, profitabilityRes, complianceRes] = await Promise.all([
                api.get(`/api/v1/kpis/sales-summary?from=${dateRange.from}&to=${dateRange.to}`),
                api.get(`/api/v1/kpis/production-profitability?from=${dateRange.from}&to=${dateRange.to}`),
                api.get(`/api/v1/kpis/operations-compliance?from=${dateRange.from}&to=${dateRange.to}`),
            ]);

            setSalesSummary(summaryRes.data?.data || null);
            setProfitability(profitabilityRes.data?.data || []);
            setCompliance(complianceRes.data?.data || null);
        } catch (error) {
            console.error('Error fetching KPIs:', error);
            alert('Error loading KPIs: ' + (error.response?.data?.message || error.message));
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
            <div className="mb-8">
                <h1 className="text-3xl font-bold text-gray-900">Sales & Operations KPIs</h1>
                <p className="mt-2 text-gray-600">Key performance indicators and analytics</p>
            </div>

            {/* Date Range Selector */}
            <div className="mb-6 bg-white p-4 rounded-lg shadow">
                <div className="grid grid-cols-2 gap-4">
                    <div>
                        <label className="block text-sm font-medium text-gray-700 mb-2">
                            From Date
                        </label>
                        <input
                            type="date"
                            value={dateRange.from}
                            onChange={(e) => setDateRange({ ...dateRange, from: e.target.value })}
                            className="w-full px-3 py-2 border border-gray-300 rounded-lg"
                        />
                    </div>
                    <div>
                        <label className="block text-sm font-medium text-gray-700 mb-2">
                            To Date
                        </label>
                        <input
                            type="date"
                            value={dateRange.to}
                            onChange={(e) => setDateRange({ ...dateRange, to: e.target.value })}
                            className="w-full px-3 py-2 border border-gray-300 rounded-lg"
                        />
                    </div>
                </div>
            </div>

            {/* Sales Summary */}
            {salesSummary && (
                <div className="mb-6">
                    <h2 className="text-xl font-bold text-gray-900 mb-4">Sales Summary</h2>
                    <div className="grid grid-cols-1 md:grid-cols-4 gap-4">
                        <div className="bg-white rounded-lg shadow p-6">
                            <div className="flex items-center justify-between">
                                <div>
                                    <p className="text-sm text-gray-600">Total Revenue</p>
                                    <p className="text-2xl font-bold text-gray-900">
                                        {Number(salesSummary.total_revenue || 0).toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 })}
                                    </p>
                                </div>
                                <DollarSign className="h-8 w-8 text-green-600" />
                            </div>
                        </div>
                        <div className="bg-white rounded-lg shadow p-6">
                            <div className="flex items-center justify-between">
                                <div>
                                    <p className="text-sm text-gray-600">Paid Revenue</p>
                                    <p className="text-2xl font-bold text-green-600">
                                        {Number(salesSummary.paid_revenue || 0).toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 })}
                                    </p>
                                </div>
                                <DollarSign className="h-8 w-8 text-green-600" />
                            </div>
                        </div>
                        <div className="bg-white rounded-lg shadow p-6">
                            <div className="flex items-center justify-between">
                                <div>
                                    <p className="text-sm text-gray-600">Outstanding</p>
                                    <p className="text-2xl font-bold text-yellow-600">
                                        {Number(salesSummary.outstanding_revenue || 0).toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 })}
                                    </p>
                                </div>
                                <DollarSign className="h-8 w-8 text-yellow-600" />
                            </div>
                        </div>
                        <div className="bg-white rounded-lg shadow p-6">
                            <div className="flex items-center justify-between">
                                <div>
                                    <p className="text-sm text-gray-600">Orders</p>
                                    <p className="text-2xl font-bold text-gray-900">
                                        {salesSummary.number_of_orders || 0}
                                    </p>
                                    <p className="text-sm text-gray-500">
                                        Avg: {Number(salesSummary.average_order_value || 0).toFixed(2)}
                                    </p>
                                </div>
                                <Package className="h-8 w-8 text-blue-600" />
                            </div>
                        </div>
                    </div>

                    {/* Top Customers */}
                    {salesSummary.top_customers && salesSummary.top_customers.length > 0 && (
                        <div className="mt-6 bg-white rounded-lg shadow p-6">
                            <h3 className="text-lg font-semibold text-gray-900 mb-4">Top Customers</h3>
                            <div className="overflow-x-auto">
                                <table className="min-w-full divide-y divide-gray-200">
                                    <thead className="bg-gray-50">
                                        <tr>
                                            <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Customer</th>
                                            <th className="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase">Revenue</th>
                                        </tr>
                                    </thead>
                                    <tbody className="bg-white divide-y divide-gray-200">
                                        {salesSummary.top_customers.map((customer, idx) => (
                                            <tr key={idx}>
                                                <td className="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                                    {customer.name}
                                                </td>
                                                <td className="px-6 py-4 whitespace-nowrap text-sm text-right font-medium text-gray-900">
                                                    {Number(customer.revenue || 0).toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 })}
                                                </td>
                                            </tr>
                                        ))}
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    )}
                </div>
            )}

            {/* Production Profitability */}
            {profitability.length > 0 && (
                <div className="mb-6">
                    <h2 className="text-xl font-bold text-gray-900 mb-4">Production Profitability</h2>
                    <div className="bg-white rounded-lg shadow overflow-hidden">
                        <table className="min-w-full divide-y divide-gray-200">
                            <thead className="bg-gray-50">
                                <tr>
                                    <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Cycle</th>
                                    <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Greenhouse</th>
                                    <th className="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase">Sales Revenue</th>
                                    <th className="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase">Qty Sold</th>
                                    <th className="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase">Harvested</th>
                                    <th className="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase">Sell Through</th>
                                </tr>
                            </thead>
                            <tbody className="bg-white divide-y divide-gray-200">
                                {profitability.map((cycle) => (
                                    <tr key={cycle.production_cycle_id}>
                                        <td className="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
                                            {cycle.production_cycle_code}
                                        </td>
                                        <td className="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                            {cycle.greenhouse?.name || 'N/A'}
                                        </td>
                                        <td className="px-6 py-4 whitespace-nowrap text-sm text-right text-gray-900">
                                            {Number(cycle.total_sales_revenue || 0).toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 })}
                                        </td>
                                        <td className="px-6 py-4 whitespace-nowrap text-sm text-right text-gray-500">
                                            {Number(cycle.total_quantity_sold || 0).toFixed(2)}
                                        </td>
                                        <td className="px-6 py-4 whitespace-nowrap text-sm text-right text-gray-500">
                                            {Number(cycle.harvest_quantity_total || 0).toFixed(2)}
                                        </td>
                                        <td className="px-6 py-4 whitespace-nowrap text-sm text-right">
                                            <span className={`px-2 py-1 text-xs font-semibold rounded-full ${
                                                cycle.sell_through_rate >= 80 
                                                    ? 'bg-green-100 text-green-800'
                                                    : cycle.sell_through_rate >= 50
                                                    ? 'bg-yellow-100 text-yellow-800'
                                                    : 'bg-red-100 text-red-800'
                                            }`}>
                                                {Number(cycle.sell_through_rate || 0).toFixed(1)}%
                                            </span>
                                        </td>
                                    </tr>
                                ))}
                            </tbody>
                        </table>
                    </div>
                </div>
            )}

            {/* Operations Compliance */}
            {compliance && (
                <div>
                    <h2 className="text-xl font-bold text-gray-900 mb-4">Operations Compliance</h2>
                    <div className="grid grid-cols-1 md:grid-cols-3 gap-4">
                        <div className="bg-white rounded-lg shadow p-6">
                            <div className="flex items-center justify-between">
                                <div>
                                    <p className="text-sm text-gray-600">Daily Log Compliance</p>
                                    <p className="text-2xl font-bold text-gray-900">
                                        {Number(compliance.daily_log_compliance_rate || 0).toFixed(1)}%
                                    </p>
                                    <p className="text-sm text-gray-500">
                                        {compliance.submitted_logs || 0} / {compliance.expected_logs || 0} logs
                                    </p>
                                </div>
                                <BarChart3 className="h-8 w-8 text-blue-600" />
                            </div>
                        </div>
                        <div className="bg-white rounded-lg shadow p-6">
                            <div className="flex items-center justify-between">
                                <div>
                                    <p className="text-sm text-gray-600">On-Time Submission</p>
                                    <p className="text-2xl font-bold text-gray-900">
                                        {Number(compliance.on_time_submission_rate || 0).toFixed(1)}%
                                    </p>
                                    <p className="text-sm text-gray-500">
                                        {compliance.on_time_submissions || 0} on-time
                                    </p>
                                </div>
                                <TrendingUp className="h-8 w-8 text-green-600" />
                            </div>
                        </div>
                        <div className="bg-white rounded-lg shadow p-6">
                            <div className="flex items-center justify-between">
                                <div>
                                    <p className="text-sm text-gray-600">Missing Logs</p>
                                    <p className="text-2xl font-bold text-red-600">
                                        {compliance.missing_log_count || 0}
                                    </p>
                                </div>
                                <TrendingUp className="h-8 w-8 text-red-600" />
                            </div>
                        </div>
                    </div>
                </div>
            )}
        </div>
    );
}
