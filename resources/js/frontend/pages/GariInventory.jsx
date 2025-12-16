import React, { useEffect, useState } from 'react';
import api from '../utils/api';
import { Package, Plus, Filter } from 'lucide-react';

export default function GariInventory() {
    const [inventory, setInventory] = useState([]);
    const [summary, setSummary] = useState([]);
    const [loading, setLoading] = useState(true);
    const [filters, setFilters] = useState({
        gari_type: '',
        packaging_type: '',
        status: 'IN_STOCK',
    });

    useEffect(() => {
        fetchData();
    }, [filters]);

    const fetchData = async () => {
        try {
            const params = new URLSearchParams();
            if (filters.gari_type) params.append('gari_type', filters.gari_type);
            if (filters.packaging_type) params.append('packaging_type', filters.packaging_type);
            if (filters.status) params.append('status', filters.status);

            const [inventoryRes, summaryRes] = await Promise.all([
                api.get(`/api/v1/gari-inventory?${params.toString()}`),
                api.get('/api/v1/gari-inventory/summary'),
            ]);
            setInventory(inventoryRes.data.data || inventoryRes.data);
            setSummary(summaryRes.data.data || summaryRes.data);
        } catch (error) {
            console.error('Error fetching inventory:', error);
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

    const totalStock = inventory.reduce((sum, item) => sum + (item.quantity_kg || 0), 0);
    const totalValue = inventory.reduce((sum, item) => sum + (item.total_cost || 0), 0);

    return (
        <div>
            <div className="flex justify-between items-center mb-8">
                <div>
                    <h1 className="text-3xl font-bold text-gray-900">Gari Inventory</h1>
                    <p className="mt-2 text-gray-600">Track finished goods stock</p>
                </div>
            </div>

            {/* Summary Cards */}
            <div className="grid grid-cols-1 md:grid-cols-3 gap-6 mb-6">
                <div className="bg-white rounded-lg shadow p-6">
                    <p className="text-sm text-gray-600 mb-1">Total Stock</p>
                    <p className="text-2xl font-bold text-gray-900">{totalStock.toFixed(2)} kg</p>
                </div>
                <div className="bg-white rounded-lg shadow p-6">
                    <p className="text-sm text-gray-600 mb-1">Total Value</p>
                    <p className="text-2xl font-bold text-gray-900">₦{totalValue.toFixed(2)}</p>
                </div>
                <div className="bg-white rounded-lg shadow p-6">
                    <p className="text-sm text-gray-600 mb-1">Items in Stock</p>
                    <p className="text-2xl font-bold text-gray-900">{inventory.length}</p>
                </div>
            </div>

            {/* Filters */}
            <div className="bg-white rounded-lg shadow p-4 mb-6">
                <div className="flex items-center space-x-4">
                    <Filter className="h-5 w-5 text-gray-400" />
                    <select
                        value={filters.gari_type}
                        onChange={(e) => setFilters({ ...filters, gari_type: e.target.value })}
                        className="px-3 py-2 border border-gray-300 rounded-lg focus:ring-green-500 focus:border-green-500"
                    >
                        <option value="">All Types</option>
                        <option value="WHITE">White</option>
                        <option value="YELLOW">Yellow</option>
                    </select>
                    <select
                        value={filters.packaging_type}
                        onChange={(e) => setFilters({ ...filters, packaging_type: e.target.value })}
                        className="px-3 py-2 border border-gray-300 rounded-lg focus:ring-green-500 focus:border-green-500"
                    >
                        <option value="">All Packaging</option>
                        <option value="1KG_POUCH">1kg Pouch</option>
                        <option value="2KG_POUCH">2kg Pouch</option>
                        <option value="5KG_PACK">5kg Pack</option>
                        <option value="50KG_BAG">50kg Bag</option>
                        <option value="BULK">Bulk</option>
                    </select>
                    <select
                        value={filters.status}
                        onChange={(e) => setFilters({ ...filters, status: e.target.value })}
                        className="px-3 py-2 border border-gray-300 rounded-lg focus:ring-green-500 focus:border-green-500"
                    >
                        <option value="IN_STOCK">In Stock</option>
                        <option value="RESERVED">Reserved</option>
                        <option value="SOLD">Sold</option>
                        <option value="SPOILED">Spoiled</option>
                        <option value="DAMAGED">Damaged</option>
                    </select>
                </div>
            </div>

            {/* Inventory Table */}
            {inventory.length === 0 ? (
                <div className="bg-white rounded-lg shadow p-12 text-center">
                    <Package className="h-16 w-16 mx-auto text-gray-400 mb-4" />
                    <h3 className="text-lg font-medium text-gray-900 mb-2">No inventory items</h3>
                    <p className="text-gray-500">Inventory will appear here after production batches are created</p>
                </div>
            ) : (
                <div className="bg-white rounded-lg shadow overflow-hidden">
                    <div className="overflow-x-auto">
                        <table className="min-w-full divide-y divide-gray-200">
                            <thead className="bg-gray-50">
                                <tr>
                                    <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Type</th>
                                    <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Grade</th>
                                    <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Packaging</th>
                                    <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Quantity</th>
                                    <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Cost per kg</th>
                                    <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Total Value</th>
                                    <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Status</th>
                                </tr>
                            </thead>
                            <tbody className="bg-white divide-y divide-gray-200">
                                {inventory.map((item) => (
                                    <tr key={item.id} className="hover:bg-gray-50">
                                        <td className="px-6 py-4 whitespace-nowrap">
                                            <span className={`px-2 py-1 text-xs font-medium rounded ${
                                                item.gari_type === 'WHITE' ? 'bg-gray-100 text-gray-800' : 'bg-yellow-100 text-yellow-800'
                                            }`}>
                                                {item.gari_type}
                                            </span>
                                        </td>
                                        <td className="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                            {item.gari_grade}
                                        </td>
                                        <td className="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                            {item.packaging_type?.replace('_', ' ').toLowerCase()}
                                        </td>
                                        <td className="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                            {item.quantity_kg} kg
                                            {item.quantity_units > 0 && ` (${item.quantity_units} units)`}
                                        </td>
                                        <td className="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                            ₦{item.cost_per_kg?.toFixed(2) || 'N/A'}
                                        </td>
                                        <td className="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                            ₦{item.total_cost?.toFixed(2) || '0.00'}
                                        </td>
                                        <td className="px-6 py-4 whitespace-nowrap">
                                            <span className={`px-2 py-1 text-xs font-medium rounded ${
                                                item.status === 'IN_STOCK' ? 'bg-green-100 text-green-800' :
                                                item.status === 'SOLD' ? 'bg-blue-100 text-blue-800' :
                                                item.status === 'SPOILED' ? 'bg-red-100 text-red-800' :
                                                'bg-gray-100 text-gray-800'
                                            }`}>
                                                {item.status}
                                            </span>
                                        </td>
                                    </tr>
                                ))}
                            </tbody>
                        </table>
                    </div>
                </div>
            )}
        </div>
    );
}

