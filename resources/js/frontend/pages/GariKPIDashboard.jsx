import React, { useEffect, useState } from 'react';
import api from '../utils/api';
import { TrendingUp, DollarSign, Package, AlertCircle, Factory } from 'lucide-react';

export default function GariKPIDashboard() {
    const [kpis, setKpis] = useState({
        batches: [],
        inventory: [],
        sales: [],
        summary: null,
    });
    const [loading, setLoading] = useState(true);
    const [dateRange, setDateRange] = useState({
        from: new Date(Date.now() - 30 * 24 * 60 * 60 * 1000).toISOString().slice(0, 10),
        to: new Date().toISOString().slice(0, 10),
    });

    useEffect(() => {
        fetchKPIData();
    }, [dateRange]);

    const fetchKPIData = async () => {
        try {
            const params = new URLSearchParams({
                date_from: dateRange.from,
                date_to: dateRange.to,
            });

            const [batchesRes, inventoryRes, salesRes, summaryRes] = await Promise.all([
                api.get(`/api/v1/gari-production-batches?${params.toString()}`),
                api.get('/api/v1/gari-inventory?status=IN_STOCK'),
                api.get(`/api/v1/gari-sales?${params.toString()}`),
                api.get(`/api/v1/gari-sales/summary?${params.toString()}`),
            ]);

            const batches = batchesRes.data.data || batchesRes.data;
            const inventory = inventoryRes.data.data || inventoryRes.data;
            const sales = salesRes.data.data || salesRes.data;
            const summary = summaryRes.data.data || summaryRes.data;

            // Calculate KPIs
            const totalCassava = batches.reduce((sum, b) => sum + (b.cassava_quantity_kg || 0), 0);
            const totalGari = batches.reduce((sum, b) => sum + (b.gari_produced_kg || 0), 0);
            const avgYield = totalCassava > 0 ? (totalGari / totalCassava) * 100 : 0;
            const avgCostPerKg = batches.length > 0 
                ? batches.reduce((sum, b) => sum + (b.cost_per_kg_gari || 0), 0) / batches.length 
                : 0;
            const totalRevenue = sales.reduce((sum, s) => sum + (s.final_amount || 0), 0);
            const totalMargin = sales.reduce((sum, s) => sum + (s.gross_margin || 0), 0);
            const totalStock = inventory.reduce((sum, i) => sum + (i.quantity_kg || 0), 0);
            const avgPricePerKg = sales.length > 0
                ? sales.reduce((sum, s) => sum + (s.unit_price || 0), 0) / sales.length
                : 0;

            setKpis({
                batches,
                inventory,
                sales,
                summary: {
                    totalCassava,
                    totalGari,
                    avgYield,
                    avgCostPerKg,
                    totalRevenue,
                    totalMargin,
                    totalStock,
                    avgPricePerKg,
                    totalBatches: batches.length,
                    totalSales: sales.length,
                },
            });
        } catch (error) {
            console.error('Error fetching KPI data:', error);
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

    const summary = kpis.summary || {};

    return (
        <div>
            <div className="flex justify-between items-center mb-8">
                <div>
                    <h1 className="text-3xl font-bold text-gray-900">Gari Production KPIs</h1>
                    <p className="mt-2 text-gray-600">Key performance indicators for gari production</p>
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

            {/* KPI Cards */}
            <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6 mb-8">
                <div className="bg-white rounded-lg shadow p-6">
                    <div className="flex items-center justify-between mb-2">
                        <p className="text-sm text-gray-600">Conversion Yield</p>
                        <TrendingUp className="h-5 w-5 text-green-600" />
                    </div>
                    <p className="text-3xl font-bold text-gray-900">
                        {summary.avgYield?.toFixed(1) || '0'}%
                    </p>
                    <p className="text-xs text-gray-500 mt-1">Target: 22-30%</p>
                    {summary.avgYield && (
                        <div className={`mt-2 text-xs ${
                            summary.avgYield >= 22 && summary.avgYield <= 30 
                                ? 'text-green-600' 
                                : summary.avgYield < 22 
                                    ? 'text-red-600' 
                                    : 'text-yellow-600'
                        }`}>
                            {summary.avgYield < 22 ? '⚠ Below target' : 
                             summary.avgYield > 30 ? '⚠ Above target' : '✓ On target'}
                        </div>
                    )}
                </div>

                <div className="bg-white rounded-lg shadow p-6">
                    <div className="flex items-center justify-between mb-2">
                        <p className="text-sm text-gray-600">Cost per kg</p>
                        <DollarSign className="h-5 w-5 text-blue-600" />
                    </div>
                    <p className="text-3xl font-bold text-gray-900">
                        ₦{summary.avgCostPerKg?.toFixed(2) || '0.00'}
                    </p>
                    <p className="text-xs text-gray-500 mt-1">Average production cost</p>
                </div>

                <div className="bg-white rounded-lg shadow p-6">
                    <div className="flex items-center justify-between mb-2">
                        <p className="text-sm text-gray-600">Stock on Hand</p>
                        <Package className="h-5 w-5 text-purple-600" />
                    </div>
                    <p className="text-3xl font-bold text-gray-900">
                        {summary.totalStock?.toFixed(2) || '0'} kg
                    </p>
                    <p className="text-xs text-gray-500 mt-1">Current inventory</p>
                </div>

                <div className="bg-white rounded-lg shadow p-6">
                    <div className="flex items-center justify-between mb-2">
                        <p className="text-sm text-gray-600">Sales Volume</p>
                        <TrendingUp className="h-5 w-5 text-green-600" />
                    </div>
                    <p className="text-3xl font-bold text-gray-900">
                        {kpis.sales.length}
                    </p>
                    <p className="text-xs text-gray-500 mt-1">Total sales in period</p>
                </div>

                <div className="bg-white rounded-lg shadow p-6">
                    <div className="flex items-center justify-between mb-2">
                        <p className="text-sm text-gray-600">Avg Selling Price</p>
                        <DollarSign className="h-5 w-5 text-green-600" />
                    </div>
                    <p className="text-3xl font-bold text-gray-900">
                        ₦{summary.avgPricePerKg?.toFixed(2) || '0.00'}
                    </p>
                    <p className="text-xs text-gray-500 mt-1">Per kg</p>
                </div>

                <div className="bg-white rounded-lg shadow p-6">
                    <div className="flex items-center justify-between mb-2">
                        <p className="text-sm text-gray-600">Gross Margin</p>
                        <DollarSign className="h-5 w-5 text-green-600" />
                    </div>
                    <p className="text-3xl font-bold text-green-600">
                        ₦{summary.totalMargin?.toFixed(2) || '0.00'}
                    </p>
                    <p className="text-xs text-gray-500 mt-1">
                        {summary.totalRevenue > 0 
                            ? ((summary.totalMargin / summary.totalRevenue) * 100).toFixed(1) + '% margin'
                            : 'No sales'}
                    </p>
                </div>
            </div>

            {/* Production Summary */}
            <div className="grid grid-cols-1 md:grid-cols-2 gap-6 mb-8">
                <div className="bg-white rounded-lg shadow p-6">
                    <h2 className="text-lg font-semibold text-gray-900 mb-4 flex items-center">
                        <Factory className="h-5 w-5 mr-2 text-orange-600" />
                        Production Summary
                    </h2>
                    <div className="space-y-3">
                        <div className="flex justify-between">
                            <span className="text-sm text-gray-600">Total Batches</span>
                            <span className="text-sm font-medium text-gray-900">{summary.totalBatches || 0}</span>
                        </div>
                        <div className="flex justify-between">
                            <span className="text-sm text-gray-600">Cassava Processed</span>
                            <span className="text-sm font-medium text-gray-900">{summary.totalCassava?.toFixed(2) || '0'} kg</span>
                        </div>
                        <div className="flex justify-between">
                            <span className="text-sm text-gray-600">Gari Produced</span>
                            <span className="text-sm font-medium text-gray-900">{summary.totalGari?.toFixed(2) || '0'} kg</span>
                        </div>
                        <div className="flex justify-between">
                            <span className="text-sm text-gray-600">Average Yield</span>
                            <span className="text-sm font-medium text-gray-900">{summary.avgYield?.toFixed(1) || '0'}%</span>
                        </div>
                    </div>
                </div>

                <div className="bg-white rounded-lg shadow p-6">
                    <h2 className="text-lg font-semibold text-gray-900 mb-4 flex items-center">
                        <DollarSign className="h-5 w-5 mr-2 text-green-600" />
                        Sales Summary
                    </h2>
                    <div className="space-y-3">
                        <div className="flex justify-between">
                            <span className="text-sm text-gray-600">Total Revenue</span>
                            <span className="text-sm font-medium text-gray-900">₦{summary.totalRevenue?.toFixed(2) || '0.00'}</span>
                        </div>
                        <div className="flex justify-between">
                            <span className="text-sm text-gray-600">Total Margin</span>
                            <span className="text-sm font-medium text-green-600">₦{summary.totalMargin?.toFixed(2) || '0.00'}</span>
                        </div>
                        <div className="flex justify-between">
                            <span className="text-sm text-gray-600">Average Price</span>
                            <span className="text-sm font-medium text-gray-900">₦{summary.avgPricePerKg?.toFixed(2) || '0.00'}/kg</span>
                        </div>
                        <div className="flex justify-between">
                            <span className="text-sm text-gray-600">Total Sales</span>
                            <span className="text-sm font-medium text-gray-900">{summary.totalSales || 0}</span>
                        </div>
                    </div>
                </div>
            </div>

            {/* Recent Activity */}
            <div className="bg-white rounded-lg shadow">
                <div className="px-6 py-4 border-b border-gray-200">
                    <h2 className="text-lg font-semibold text-gray-900">Recent Production Batches</h2>
                </div>
                <div className="divide-y divide-gray-200">
                    {kpis.batches.slice(0, 5).map((batch) => (
                        <div key={batch.id} className="px-6 py-4">
                            <div className="flex justify-between items-center">
                                <div>
                                    <p className="font-medium text-gray-900">{batch.batch_code}</p>
                                    <p className="text-sm text-gray-500">
                                        {new Date(batch.processing_date).toLocaleDateString()} • 
                                        {batch.cassava_quantity_kg} kg cassava → {batch.gari_produced_kg} kg gari
                                    </p>
                                </div>
                                <div className="text-right">
                                    <p className="text-sm font-medium text-green-600">
                                        {batch.conversion_yield_percent != null 
                                            ? Number(batch.conversion_yield_percent).toFixed(1) 
                                            : '0'}% yield
                                    </p>
                                    <p className="text-xs text-gray-500">
                                        ₦{batch.cost_per_kg_gari != null 
                                            ? Number(batch.cost_per_kg_gari).toFixed(2) 
                                            : '0'}/kg
                                    </p>
                                </div>
                            </div>
                        </div>
                    ))}
                    {kpis.batches.length === 0 && (
                        <div className="px-6 py-8 text-center text-gray-500">
                            No production batches in this period
                        </div>
                    )}
                </div>
            </div>
        </div>
    );
}

