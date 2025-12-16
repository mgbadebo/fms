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

            // Fetch all sales (without pagination limit for accurate KPIs)
            const salesParams = new URLSearchParams({
                date_from: dateRange.from,
                date_to: dateRange.to,
                per_page: '1000', // Get more sales for accurate calculations
            });

            const [batchesRes, inventoryRes, salesRes, summaryRes] = await Promise.all([
                api.get(`/api/v1/gari-production-batches?${params.toString()}`),
                api.get('/api/v1/gari-inventory?status=IN_STOCK'),
                api.get(`/api/v1/gari-sales?${salesParams.toString()}`),
                api.get(`/api/v1/gari-sales/summary?${params.toString()}`),
            ]);

            // Handle paginated responses
            const batches = Array.isArray(batchesRes.data.data) ? batchesRes.data.data : 
                           Array.isArray(batchesRes.data) ? batchesRes.data : [];
            const inventory = Array.isArray(inventoryRes.data.data) ? inventoryRes.data.data : 
                             Array.isArray(inventoryRes.data) ? inventoryRes.data : [];
            
            // Handle paginated sales response
            let sales = [];
            if (salesRes.data.data && Array.isArray(salesRes.data.data)) {
                sales = salesRes.data.data;
            } else if (Array.isArray(salesRes.data)) {
                sales = salesRes.data;
            }
            
            // Use overall summary from API if available, otherwise calculate from sales array
            console.log('Summary API Response:', summaryRes.data);
            console.log('Date Range:', dateRange);
            console.log('Sales Response:', salesRes.data);
            const overallSummary = summaryRes.data.overall || {};
            const summary = summaryRes.data.data || summaryRes.data;
            console.log('Overall Summary:', overallSummary);
            console.log('Sales array length:', sales.length);
            console.log('Sample sale (first):', sales[0]);

            // Calculate KPIs - ensure all values are numbers
            const totalCassava = batches.reduce((sum, b) => sum + (Number(b.cassava_quantity_kg) || 0), 0);
            const totalGari = batches.reduce((sum, b) => sum + (Number(b.gari_produced_kg) || 0), 0);
            const avgYield = totalCassava > 0 ? (totalGari / totalCassava) * 100 : 0;
            const avgCostPerKg = batches.length > 0 
                ? batches.reduce((sum, b) => sum + (Number(b.cost_per_kg_gari) || 0), 0) / batches.length 
                : 0;
            
            // Use overall summary from API (more accurate as it includes all sales, not just paginated)
            // Fallback to calculating from sales array if overall summary not available
            const totalRevenue = overallSummary.total_revenue !== undefined ? overallSummary.total_revenue : 
                               sales.reduce((sum, s) => sum + (Number(s.final_amount) || 0), 0);
            const totalMargin = overallSummary.total_margin !== undefined ? overallSummary.total_margin : 
                              sales.reduce((sum, s) => sum + (Number(s.gross_margin) || 0), 0);
            const totalSalesVolumeKg = overallSummary.total_kg_sold !== undefined ? overallSummary.total_kg_sold : 
                                     sales.reduce((sum, s) => sum + (Number(s.quantity_kg) || 0), 0);
            const avgPricePerKg = overallSummary.avg_price_per_kg !== undefined ? overallSummary.avg_price_per_kg : 
                                (() => {
                                    // Calculate weighted average price per kg (weighted by quantity)
                                    let totalPriceWeighted = 0;
                                    let totalQuantityForPrice = 0;
                                    sales.forEach(s => {
                                        const qty = Number(s.quantity_kg) || 0;
                                        const price = Number(s.unit_price) || 0;
                                        if (qty > 0 && price > 0) {
                                            totalPriceWeighted += price * qty;
                                            totalQuantityForPrice += qty;
                                        }
                                    });
                                    return totalQuantityForPrice > 0 ? totalPriceWeighted / totalQuantityForPrice : 0;
                                })();
            
            console.log('Calculated KPIs:', {
                totalRevenue,
                totalMargin,
                totalSalesVolumeKg,
                avgPricePerKg,
                totalSales: overallSummary.total_sales !== undefined ? overallSummary.total_sales : sales.length
            });
            
            const totalStock = inventory.reduce((sum, i) => sum + (Number(i.quantity_kg) || 0), 0);

            const kpiSummary = {
                totalCassava,
                totalGari,
                avgYield,
                avgCostPerKg,
                totalRevenue,
                totalMargin,
                totalStock,
                avgPricePerKg,
                totalSalesVolumeKg,
                totalBatches: batches.length,
                totalSales: overallSummary.total_sales !== undefined ? overallSummary.total_sales : sales.length,
            };
            
            console.log('Setting KPIs summary:', kpiSummary);
            
            setKpis({
                batches,
                inventory,
                sales,
                summary: kpiSummary,
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
                        {Number(summary.avgYield || 0).toFixed(1)}%
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
                        ₦{Number(summary.avgCostPerKg || 0).toFixed(2)}
                    </p>
                    <p className="text-xs text-gray-500 mt-1">Average production cost</p>
                </div>

                <div className="bg-white rounded-lg shadow p-6">
                    <div className="flex items-center justify-between mb-2">
                        <p className="text-sm text-gray-600">Stock on Hand</p>
                        <Package className="h-5 w-5 text-purple-600" />
                    </div>
                    <p className="text-3xl font-bold text-gray-900">
                        {Number(summary.totalStock || 0).toFixed(2)} kg
                    </p>
                    <p className="text-xs text-gray-500 mt-1">Current inventory</p>
                </div>

                <div className="bg-white rounded-lg shadow p-6">
                    <div className="flex items-center justify-between mb-2">
                        <p className="text-sm text-gray-600">Sales Volume</p>
                        <TrendingUp className="h-5 w-5 text-green-600" />
                    </div>
                    <p className="text-3xl font-bold text-gray-900">
                        {Number(summary.totalSalesVolumeKg || 0).toFixed(2)} kg
                    </p>
                    <p className="text-xs text-gray-500 mt-1">Total kg sold in period</p>
                </div>

                <div className="bg-white rounded-lg shadow p-6">
                    <div className="flex items-center justify-between mb-2">
                        <p className="text-sm text-gray-600">Avg Selling Price</p>
                        <DollarSign className="h-5 w-5 text-green-600" />
                    </div>
                    <p className="text-3xl font-bold text-gray-900">
                        ₦{Number(summary.avgPricePerKg || 0).toFixed(2)}
                    </p>
                    <p className="text-xs text-gray-500 mt-1">Per kg</p>
                </div>

                <div className="bg-white rounded-lg shadow p-6">
                    <div className="flex items-center justify-between mb-2">
                        <p className="text-sm text-gray-600">Gross Margin</p>
                        <DollarSign className="h-5 w-5 text-green-600" />
                    </div>
                    <p className="text-3xl font-bold text-green-600">
                        ₦{Number(summary.totalMargin || 0).toFixed(2)}
                    </p>
                    <p className="text-xs text-gray-500 mt-1">
                        {Number(summary.totalRevenue || 0) > 0 
                            ? Number((Number(summary.totalMargin || 0) / Number(summary.totalRevenue || 1)) * 100).toFixed(1) + '% margin'
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
                            <span className="text-sm font-medium text-gray-900">{Number(summary.totalCassava || 0).toFixed(2)} kg</span>
                        </div>
                        <div className="flex justify-between">
                            <span className="text-sm text-gray-600">Gari Produced</span>
                            <span className="text-sm font-medium text-gray-900">{Number(summary.totalGari || 0).toFixed(2)} kg</span>
                        </div>
                        <div className="flex justify-between">
                            <span className="text-sm text-gray-600">Average Yield</span>
                            <span className="text-sm font-medium text-gray-900">{Number(summary.avgYield || 0).toFixed(1)}%</span>
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
                            <span className="text-sm font-medium text-gray-900">₦{Number(summary.totalRevenue || 0).toFixed(2)}</span>
                        </div>
                        <div className="flex justify-between">
                            <span className="text-sm text-gray-600">Total Margin</span>
                            <span className="text-sm font-medium text-green-600">₦{Number(summary.totalMargin || 0).toFixed(2)}</span>
                        </div>
                        <div className="flex justify-between">
                            <span className="text-sm text-gray-600">Average Price</span>
                            <span className="text-sm font-medium text-gray-900">₦{Number(summary.avgPricePerKg || 0).toFixed(2)}/kg</span>
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

