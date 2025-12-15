import React, { useEffect, useState } from 'react';
import api from '../utils/api';
import { Box, Plus } from 'lucide-react';

export default function PackagingMaterials() {
    const [materials, setMaterials] = useState([]);
    const [farms, setFarms] = useState([]);
    const [loading, setLoading] = useState(true);
    const [showModal, setShowModal] = useState(false);
    const [formData, setFormData] = useState({
        farm_id: '',
        name: '',
        material_type: 'POUCH',
        size: '',
        unit: 'pieces',
        opening_balance: 0,
        quantity_purchased: 0,
        quantity_used: 0,
        cost_per_unit: '',
        notes: '',
    });

    useEffect(() => {
        fetchData();
    }, []);

    const fetchData = async () => {
        try {
            const [materialsRes, farmsRes] = await Promise.all([
                api.get('/api/v1/packaging-materials'),
                api.get('/api/v1/farms'),
            ]);
            setMaterials(materialsRes.data.data || materialsRes.data);
            setFarms(farmsRes.data.data || farmsRes.data);
        } catch (error) {
            console.error('Error fetching data:', error);
        } finally {
            setLoading(false);
        }
    };

    const fetchFarms = async () => {
        try {
            const response = await api.get('/api/v1/farms');
            setFarms(response.data.data || response.data);
        } catch (error) {
            console.error('Error fetching farms:', error);
        }
    };

    const handleModalOpen = () => {
        setShowModal(true);
        // Refresh farms list when opening modal to get newly created farms
        fetchFarms();
    };

    const handleSubmit = async (e) => {
        e.preventDefault();
        try {
            await api.post('/api/v1/packaging-materials', formData);
            setShowModal(false);
            fetchData();
        } catch (error) {
            alert('Error creating material: ' + (error.response?.data?.message || 'Unknown error'));
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
                    <h1 className="text-3xl font-bold text-gray-900">Packaging Materials</h1>
                    <p className="mt-2 text-gray-600">Track packaging inventory</p>
                </div>
                <button
                    onClick={handleModalOpen}
                    className="bg-green-600 text-white px-4 py-2 rounded-lg hover:bg-green-700 flex items-center"
                >
                    <Plus className="h-5 w-5 mr-2" />
                    Add Material
                </button>
            </div>

            {materials.length === 0 ? (
                <div className="bg-white rounded-lg shadow p-12 text-center">
                    <Box className="h-16 w-16 mx-auto text-gray-400 mb-4" />
                    <h3 className="text-lg font-medium text-gray-900 mb-2">No packaging materials yet</h3>
                    <p className="text-gray-500 mb-4">Add your first packaging material to get started</p>
                    <button
                        onClick={handleModalOpen}
                        className="bg-green-600 text-white px-4 py-2 rounded-lg hover:bg-green-700"
                    >
                        Add Material
                    </button>
                </div>
            ) : (
                <div className="bg-white rounded-lg shadow overflow-hidden">
                    <div className="overflow-x-auto">
                        <table className="min-w-full divide-y divide-gray-200">
                            <thead className="bg-gray-50">
                                <tr>
                                    <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Name</th>
                                    <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Type</th>
                                    <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Opening</th>
                                    <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Purchased</th>
                                    <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Used</th>
                                    <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Closing</th>
                                    <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Cost</th>
                                </tr>
                            </thead>
                            <tbody className="bg-white divide-y divide-gray-200">
                                {materials.map((material) => (
                                    <tr key={material.id} className="hover:bg-gray-50">
                                        <td className="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
                                            {material.name}
                                            {material.size && <span className="text-gray-500 ml-1">({material.size})</span>}
                                        </td>
                                        <td className="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                            {material.material_type}
                                        </td>
                                        <td className="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                            {material.opening_balance} {material.unit}
                                        </td>
                                        <td className="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                            {material.quantity_purchased} {material.unit}
                                        </td>
                                        <td className="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                            {material.quantity_used} {material.unit}
                                        </td>
                                        <td className="px-6 py-4 whitespace-nowrap text-sm font-semibold text-gray-900">
                                            {material.closing_balance} {material.unit}
                                        </td>
                                        <td className="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                            {material.total_cost ? `₦${material.total_cost.toFixed(2)}` : 'N/A'}
                                        </td>
                                    </tr>
                                ))}
                            </tbody>
                        </table>
                    </div>
                </div>
            )}

            {/* Create Material Modal */}
            {showModal && (
                <div className="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50 p-4">
                    <div className="bg-white rounded-lg max-w-md w-full p-6">
                        <h2 className="text-xl font-bold mb-4">Add Packaging Material</h2>
                        <form onSubmit={handleSubmit} className="space-y-4">
                            <div>
                                <label className="block text-sm font-medium text-gray-700 mb-1">Farm *</label>
                                <select
                                    required
                                    value={formData.farm_id}
                                    onChange={(e) => setFormData({ ...formData, farm_id: e.target.value })}
                                    className="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-green-500 focus:border-green-500"
                                >
                                    <option value="">Select farm</option>
                                    {farms.map((farm) => (
                                        <option key={farm.id} value={farm.id}>{farm.name}</option>
                                    ))}
                                </select>
                            </div>
                            <div>
                                <label className="block text-sm font-medium text-gray-700 mb-1">Name *</label>
                                <input
                                    type="text"
                                    required
                                    value={formData.name}
                                    onChange={(e) => setFormData({ ...formData, name: e.target.value })}
                                    className="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-green-500 focus:border-green-500"
                                    placeholder="e.g., 1kg Pouch"
                                />
                            </div>
                            <div className="grid grid-cols-2 gap-4">
                                <div>
                                    <label className="block text-sm font-medium text-gray-700 mb-1">Type *</label>
                                    <select
                                        required
                                        value={formData.material_type}
                                        onChange={(e) => setFormData({ ...formData, material_type: e.target.value })}
                                        className="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-green-500 focus:border-green-500"
                                    >
                                        <option value="POUCH">Pouch</option>
                                        <option value="SACK">Sack</option>
                                        <option value="LABEL">Label</option>
                                        <option value="SEALING_ROLL">Sealing Roll</option>
                                        <option value="CARTON">Carton</option>
                                        <option value="OTHER">Other</option>
                                    </select>
                                </div>
                                <div>
                                    <label className="block text-sm font-medium text-gray-700 mb-1">Size</label>
                                    <input
                                        type="text"
                                        value={formData.size}
                                        onChange={(e) => setFormData({ ...formData, size: e.target.value })}
                                        className="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-green-500 focus:border-green-500"
                                        placeholder="e.g., 1kg"
                                    />
                                </div>
                            </div>
                            <div className="grid grid-cols-3 gap-4">
                                <div>
                                    <label className="block text-sm font-medium text-gray-700 mb-1">Opening</label>
                                    <input
                                        type="number"
                                        step="0.01"
                                        value={formData.opening_balance}
                                        onChange={(e) => setFormData({ ...formData, opening_balance: e.target.value })}
                                        className="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-green-500 focus:border-green-500"
                                    />
                                </div>
                                <div>
                                    <label className="block text-sm font-medium text-gray-700 mb-1">Purchased</label>
                                    <input
                                        type="number"
                                        step="0.01"
                                        value={formData.quantity_purchased}
                                        onChange={(e) => setFormData({ ...formData, quantity_purchased: e.target.value })}
                                        className="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-green-500 focus:border-green-500"
                                    />
                                </div>
                                <div>
                                    <label className="block text-sm font-medium text-gray-700 mb-1">Used</label>
                                    <input
                                        type="number"
                                        step="0.01"
                                        value={formData.quantity_used}
                                        onChange={(e) => setFormData({ ...formData, quantity_used: e.target.value })}
                                        className="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-green-500 focus:border-green-500"
                                    />
                                </div>
                            </div>
                            <div>
                                <label className="block text-sm font-medium text-gray-700 mb-1">Cost per Unit (₦)</label>
                                <input
                                    type="number"
                                    step="0.01"
                                    value={formData.cost_per_unit}
                                    onChange={(e) => setFormData({ ...formData, cost_per_unit: e.target.value })}
                                    className="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-green-500 focus:border-green-500"
                                />
                            </div>
                            <div className="flex justify-end space-x-3 pt-4">
                                <button
                                    type="button"
                                    onClick={() => setShowModal(false)}
                                    className="px-4 py-2 border border-gray-300 rounded-lg text-gray-700 hover:bg-gray-50"
                                >
                                    Cancel
                                </button>
                                <button
                                    type="submit"
                                    className="px-4 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700"
                                >
                                    Add Material
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            )}
        </div>
    );
}

