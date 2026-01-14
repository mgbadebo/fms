import React, { useEffect, useState } from 'react';
import api from '../utils/api';
import { Package, Filter, MapPin } from 'lucide-react';

export default function BellPepperInventory() {
    const [inventory, setInventory] = useState([]);
    const [farms, setFarms] = useState([]);
    const [greenhouses, setGreenhouses] = useState([]);
    const [storageLocations, setStorageLocations] = useState([]);
    const [loading, setLoading] = useState(true);
    const [filters, setFilters] = useState({
        farm_id: '',
        greenhouse_id: '',
        grade: '',
        storage_location_id: '',
        show_unassigned_only: false,
    });

    useEffect(() => {
        fetchData();
        fetchFarms();
        fetchStorageLocations();
    }, []);

    useEffect(() => {
        if (filters.farm_id) {
            fetchGreenhouses(filters.farm_id);
        } else {
            setGreenhouses([]);
        }
    }, [filters.farm_id]);

    useEffect(() => {
        fetchData();
    }, [filters]);

    const fetchData = async () => {
        try {
            const params = new URLSearchParams();
            if (filters.farm_id) params.append('farm_id', filters.farm_id);
            if (filters.greenhouse_id) params.append('greenhouse_id', filters.greenhouse_id);
            if (filters.grade) params.append('grade', filters.grade);
            if (filters.storage_location_id) {
                params.append('storage_location_id', filters.storage_location_id);
            }
            if (filters.show_unassigned_only) params.append('show_unassigned_only', '1');

            const response = await api.get(`/api/v1/bell-pepper-inventory?${params.toString()}`);
            const inventoryData = response.data.data || [];
            setInventory(Array.isArray(inventoryData) ? inventoryData : []);
        } catch (error) {
            console.error('Error fetching inventory:', error);
        } finally {
            setLoading(false);
        }
    };

    const fetchFarms = async () => {
        try {
            const response = await api.get('/api/v1/farms');
            const farmsData = response.data.data || response.data;
            setFarms(Array.isArray(farmsData) ? farmsData : []);
        } catch (error) {
            console.error('Error fetching farms:', error);
        }
    };

    const fetchGreenhouses = async (farmId) => {
        try {
            const response = await api.get(`/api/v1/greenhouses?farm_id=${farmId}`);
            const greenhousesData = response.data.data || response.data;
            setGreenhouses(Array.isArray(greenhousesData) ? greenhousesData : []);
        } catch (error) {
            console.error('Error fetching greenhouses:', error);
        }
    };

    const fetchStorageLocations = async () => {
        try {
            const response = await api.get('/api/v1/inventory-locations');
            const locationsData = response.data.data || response.data;
            setStorageLocations(Array.isArray(locationsData) ? locationsData : []);
        } catch (error) {
            console.error('Error fetching storage locations:', error);
        }
    };

    if (loading) {
        return (
            <div className="flex items-center justify-center h-64">
                <div className="animate-spin rounded-full h-12 w-12 border-b-2 border-green-600"></div>
            </div>
        );
    }

    const totalWeight = inventory.reduce((sum, item) => sum + (item.available_weight_kg || 0), 0);
    const totalCrates = inventory.reduce((sum, item) => sum + (item.crate_count || 0), 0);
    const unassignedCount = inventory.filter(item => !item.storage_location).length;
    const unassignedWeight = inventory.filter(item => !item.storage_location).reduce((sum, item) => sum + (item.available_weight_kg || 0), 0);

    const gradeTotals = inventory.reduce((acc, item) => {
        const grade = item.grade || 'Unknown';
        if (!acc[grade]) {
            acc[grade] = { weight: 0, crates: 0 };
        }
        acc[grade].weight += item.available_weight_kg || 0;
        acc[grade].crates += item.crate_count || 0;
        return acc;
    }, {});

    return (
        <div>
            <div className="flex justify-between items-center mb-8">
                <div>
                    <h1 className="text-3xl font-bold text-gray-900">Bell Pepper Inventory</h1>
                    <p className="mt-2 text-gray-600">Track harvested bell peppers that are not yet sold</p>
                </div>
            </div>

            {/* Summary Cards */}
            <div className="grid grid-cols-1 md:grid-cols-5 gap-6 mb-6">
                <div className="bg-white rounded-lg shadow p-6">
                    <p className="text-sm text-gray-600 mb-1">Total Stock</p>
                    <p className="text-2xl font-bold text-gray-900">{totalWeight.toFixed(2)} kg</p>
                </div>
                <div className="bg-white rounded-lg shadow p-6">
                    <p className="text-sm text-gray-600 mb-1">Total Crates</p>
                    <p className="text-2xl font-bold text-gray-900">{totalCrates}</p>
                </div>
                <div className={`rounded-lg shadow p-6 ${unassignedCount > 0 ? 'bg-red-50 border-2 border-red-300' : 'bg-white'}`}>
                    <p className="text-sm text-gray-600 mb-1">Unassigned Location</p>
                    <p className={`text-2xl font-bold ${unassignedCount > 0 ? 'text-red-600' : 'text-gray-900'}`}>
                        {unassignedWeight.toFixed(2)} kg ({unassignedCount} items)
                    </p>
                    {unassignedCount > 0 && (
                        <p className="text-xs text-red-600 mt-1">Action Required</p>
                    )}
                </div>
                <div className="bg-white rounded-lg shadow p-6">
                    <p className="text-sm text-gray-600 mb-1">Grade A</p>
                    <p className="text-2xl font-bold text-gray-900">
                        {gradeTotals['A']?.weight.toFixed(2) || '0.00'} kg ({gradeTotals['A']?.crates || 0} crates)
                    </p>
                </div>
                <div className="bg-white rounded-lg shadow p-6">
                    <p className="text-sm text-gray-600 mb-1">Grade B</p>
                    <p className="text-2xl font-bold text-gray-900">
                        {gradeTotals['B']?.weight.toFixed(2) || '0.00'} kg ({gradeTotals['B']?.crates || 0} crates)
                    </p>
                </div>
            </div>

            {/* Filters */}
            <div className="bg-white rounded-lg shadow p-4 mb-6">
                <div className="flex items-center space-x-4 flex-wrap">
                    <Filter className="h-5 w-5 text-gray-400" />
                    <select
                        value={filters.farm_id}
                        onChange={(e) => setFilters({ ...filters, farm_id: e.target.value, greenhouse_id: '' })}
                        className="px-3 py-2 border border-gray-300 rounded-lg focus:ring-green-500 focus:border-green-500"
                    >
                        <option value="">All Farms</option>
                        {farms.map((farm) => (
                            <option key={farm.id} value={farm.id}>
                                {farm.name}
                            </option>
                        ))}
                    </select>
                    <select
                        value={filters.greenhouse_id}
                        onChange={(e) => setFilters({ ...filters, greenhouse_id: e.target.value })}
                        className="px-3 py-2 border border-gray-300 rounded-lg focus:ring-green-500 focus:border-green-500"
                        disabled={!filters.farm_id}
                    >
                        <option value="">All Greenhouses</option>
                        {greenhouses.map((gh) => (
                            <option key={gh.id} value={gh.id}>
                                {gh.name}
                            </option>
                        ))}
                    </select>
                    <select
                        value={filters.grade}
                        onChange={(e) => setFilters({ ...filters, grade: e.target.value })}
                        className="px-3 py-2 border border-gray-300 rounded-lg focus:ring-green-500 focus:border-green-500"
                    >
                        <option value="">All Grades</option>
                        <option value="A">Grade A</option>
                        <option value="B">Grade B</option>
                        <option value="C">Grade C</option>
                    </select>
                    <select
                        value={filters.storage_location_id}
                        onChange={(e) => setFilters({ ...filters, storage_location_id: e.target.value })}
                        className="px-3 py-2 border border-gray-300 rounded-lg focus:ring-green-500 focus:border-green-500"
                    >
                        <option value="">All Locations</option>
                        <option value="unassigned">Unassigned (No Location)</option>
                        {storageLocations.map((loc) => (
                            <option key={loc.id} value={loc.id}>
                                {loc.name}
                            </option>
                        ))}
                    </select>
                    <label className="flex items-center space-x-2">
                        <input
                            type="checkbox"
                            checked={filters.show_unassigned_only}
                            onChange={(e) => setFilters({ ...filters, show_unassigned_only: e.target.checked })}
                            className="rounded border-gray-300 text-green-600 focus:ring-green-500"
                        />
                        <span className="text-sm text-gray-700">Show unassigned only</span>
                    </label>
                </div>
            </div>

            {/* Inventory Table */}
            {inventory.length === 0 ? (
                <div className="bg-white rounded-lg shadow p-12 text-center">
                    <Package className="h-16 w-16 mx-auto text-gray-400 mb-4" />
                    <h3 className="text-lg font-medium text-gray-900 mb-2">No inventory items</h3>
                    <p className="text-gray-500">Inventory will appear here after harvest records are created</p>
                </div>
            ) : (
                <div className="bg-white rounded-lg shadow overflow-hidden">
                    <div className="overflow-x-auto">
                        <table className="min-w-full divide-y divide-gray-200">
                            <thead className="bg-gray-50">
                                <tr>
                                    <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Harvest Date</th>
                                    <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Production Cycle</th>
                                    <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Greenhouse</th>
                                    <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Grade</th>
                                    <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Crates</th>
                                    <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Weight (kg)</th>
                                    <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Storage Location</th>
                                </tr>
                            </thead>
                            <tbody className="bg-white divide-y divide-gray-200">
                                {inventory.map((item, idx) => (
                                    <tr 
                                        key={`${item.harvest_record_id}-${item.grade}-${item.storage_location?.id || 'none'}-${idx}`} 
                                        className={`hover:bg-gray-50 ${!item.storage_location ? 'bg-red-50' : ''}`}
                                    >
                                        <td className="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                            {item.harvest_date ? new Date(item.harvest_date).toLocaleDateString() : 'N/A'}
                                        </td>
                                        <td className="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                            {item.production_cycle?.code || 'N/A'}
                                        </td>
                                        <td className="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                            {item.greenhouse?.name || 'N/A'}
                                        </td>
                                        <td className="px-6 py-4 whitespace-nowrap">
                                            <span className={`px-2 py-1 text-xs font-medium rounded ${
                                                item.grade === 'A' ? 'bg-green-100 text-green-800' :
                                                item.grade === 'B' ? 'bg-yellow-100 text-yellow-800' :
                                                'bg-orange-100 text-orange-800'
                                            }`}>
                                                Grade {item.grade}
                                            </span>
                                        </td>
                                        <td className="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                            {item.crate_count}
                                        </td>
                                        <td className="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                            {item.available_weight_kg.toFixed(2)} kg
                                        </td>
                                        <td className="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                            {item.storage_location ? (
                                                <div className="flex items-center">
                                                    <MapPin className="h-4 w-4 text-green-500 mr-1" />
                                                    <span>{item.storage_location.name}</span>
                                                </div>
                                            ) : (
                                                <div className="flex items-center">
                                                    <MapPin className="h-4 w-4 text-red-400 mr-1" />
                                                    <span className="text-red-600 font-medium">Not assigned - Action Required</span>
                                                </div>
                                            )}
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
