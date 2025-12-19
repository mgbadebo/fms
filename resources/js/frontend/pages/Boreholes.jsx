import React, { useEffect, useState } from 'react';
import api from '../utils/api';
import { Droplet, Plus, Edit, Trash2 } from 'lucide-react';

export default function Boreholes() {
    const [boreholes, setBoreholes] = useState([]);
    const [farms, setFarms] = useState([]);
    const [loading, setLoading] = useState(true);
    const [showModal, setShowModal] = useState(false);
    const [editingBorehole, setEditingBorehole] = useState(null);
    const [formData, setFormData] = useState({
        farm_id: '',
        name: '',
        installed_date: new Date().toISOString().slice(0, 10),
        installation_cost: '',
        amortization_cycles: 6,
        location: '',
        specifications: '',
        notes: '',
        is_active: true,
    });

    useEffect(() => {
        fetchData();
    }, []);

    const fetchData = async () => {
        try {
            const [boreholesRes, farmsRes] = await Promise.all([
                api.get('/api/v1/boreholes'),
                api.get('/api/v1/farms?per_page=1000'),
            ]);

            const boreholesData = boreholesRes.data?.data || (Array.isArray(boreholesRes.data) ? boreholesRes.data : []);
            const farmsData = farmsRes.data?.data || (Array.isArray(farmsRes.data) ? farmsRes.data : []);

            setBoreholes(Array.isArray(boreholesData) ? boreholesData : []);
            setFarms(Array.isArray(farmsData) ? farmsData : []);
        } catch (error) {
            console.error('Error fetching data:', error);
        } finally {
            setLoading(false);
        }
    };

    const handleModalOpen = () => {
        setEditingBorehole(null);
        setFormData({
            farm_id: '',
            name: '',
            installed_date: new Date().toISOString().slice(0, 10),
            installation_cost: '',
            amortization_cycles: 6,
            location: '',
            specifications: '',
            notes: '',
            is_active: true,
        });
        setShowModal(true);
    };

    const handleEdit = (borehole) => {
        setEditingBorehole(borehole);
        setFormData({
            farm_id: borehole.farm_id,
            name: borehole.name,
            installed_date: borehole.installed_date ? new Date(borehole.installed_date).toISOString().slice(0, 10) : '',
            installation_cost: borehole.installation_cost || '',
            amortization_cycles: borehole.amortization_cycles || 6,
            location: borehole.location || '',
            specifications: borehole.specifications || '',
            notes: borehole.notes || '',
            is_active: borehole.is_active !== undefined ? borehole.is_active : true,
        });
        setShowModal(true);
    };

    const handleSubmit = async (e) => {
        e.preventDefault();
        try {
            if (editingBorehole) {
                await api.put(`/api/v1/boreholes/${editingBorehole.id}`, formData);
            } else {
                await api.post('/api/v1/boreholes', formData);
            }
            setShowModal(false);
            fetchData();
        } catch (error) {
            alert('Error saving borehole: ' + (error.response?.data?.message || 'Unknown error'));
        }
    };

    const handleDelete = async (id) => {
        if (!confirm('Are you sure you want to delete this borehole?')) return;
        try {
            await api.delete(`/api/v1/boreholes/${id}`);
            fetchData();
        } catch (error) {
            alert('Error deleting borehole: ' + (error.response?.data?.message || 'Unknown error'));
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
                    <h1 className="text-3xl font-bold text-gray-900">Boreholes</h1>
                    <p className="mt-2 text-gray-600">Manage boreholes that can power multiple greenhouses</p>
                </div>
                <button
                    onClick={handleModalOpen}
                    className="bg-green-600 text-white px-4 py-2 rounded-lg hover:bg-green-700 flex items-center"
                >
                    <Plus className="h-5 w-5 mr-2" />
                    New Borehole
                </button>
            </div>

            {boreholes.length === 0 ? (
                <div className="bg-white rounded-lg shadow p-12 text-center">
                    <Droplet className="h-16 w-16 mx-auto text-gray-400 mb-4" />
                    <h3 className="text-lg font-medium text-gray-900 mb-2">No boreholes yet</h3>
                    <p className="text-gray-500 mb-4">Create your first borehole to get started</p>
                    <button
                        onClick={handleModalOpen}
                        className="bg-green-600 text-white px-4 py-2 rounded-lg hover:bg-green-700"
                    >
                        Create Borehole
                    </button>
                </div>
            ) : (
                <div className="bg-white rounded-lg shadow overflow-hidden">
                    <div className="overflow-x-auto">
                        <table className="min-w-full divide-y divide-gray-200">
                            <thead className="bg-gray-50">
                                <tr>
                                    <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Code</th>
                                    <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Name</th>
                                    <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Farm</th>
                                    <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Installed Date</th>
                                    <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Installation Cost</th>
                                    <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Amortization</th>
                                    <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Linked Greenhouses</th>
                                    <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Status</th>
                                    <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Actions</th>
                                </tr>
                            </thead>
                            <tbody className="bg-white divide-y divide-gray-200">
                                {boreholes.map((borehole) => (
                                    <tr key={borehole.id} className="hover:bg-gray-50">
                                        <td className="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
                                            {borehole.code}
                                        </td>
                                        <td className="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                            {borehole.name}
                                        </td>
                                        <td className="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                            {borehole.farm?.name || 'N/A'}
                                        </td>
                                        <td className="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                            {borehole.installed_date ? new Date(borehole.installed_date).toLocaleDateString() : 'N/A'}
                                        </td>
                                        <td className="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                            ₦{Number(borehole.installation_cost || 0).toFixed(2)}
                                        </td>
                                        <td className="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                            {borehole.amortization_cycles} cycles
                                        </td>
                                        <td className="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                            {borehole.greenhouses && borehole.greenhouses.length > 0
                                                ? `${borehole.greenhouses.length} greenhouse(s)`
                                                : 'None'}
                                        </td>
                                        <td className="px-6 py-4 whitespace-nowrap">
                                            <span className={`px-2 py-1 text-xs font-medium rounded ${
                                                borehole.is_active ? 'bg-green-100 text-green-800' : 'bg-gray-100 text-gray-800'
                                            }`}>
                                                {borehole.is_active ? 'Active' : 'Inactive'}
                                            </span>
                                        </td>
                                        <td className="px-6 py-4 whitespace-nowrap text-sm">
                                            <div className="flex space-x-2">
                                                <button
                                                    onClick={() => handleEdit(borehole)}
                                                    className="text-green-600 hover:text-green-700"
                                                >
                                                    <Edit className="h-4 w-4" />
                                                </button>
                                                <button
                                                    onClick={() => handleDelete(borehole.id)}
                                                    className="text-red-600 hover:text-red-700"
                                                >
                                                    <Trash2 className="h-4 w-4" />
                                                </button>
                                            </div>
                                        </td>
                                    </tr>
                                ))}
                            </tbody>
                        </table>
                    </div>
                </div>
            )}

            {/* Create/Edit Modal */}
            {showModal && (
                <div className="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50 p-4 overflow-y-auto">
                    <div className="bg-white rounded-lg max-w-2xl w-full p-6 max-h-[90vh] overflow-y-auto">
                        <h2 className="text-xl font-bold mb-4">
                            {editingBorehole ? 'Edit Borehole' : 'Create New Borehole'}
                        </h2>
                        <form onSubmit={handleSubmit} className="space-y-4">
                            <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
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
                                    />
                                </div>
                                <div>
                                    <label className="block text-sm font-medium text-gray-700 mb-1">Installed Date *</label>
                                    <input
                                        type="date"
                                        required
                                        value={formData.installed_date}
                                        onChange={(e) => setFormData({ ...formData, installed_date: e.target.value })}
                                        className="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-green-500 focus:border-green-500"
                                    />
                                </div>
                                <div>
                                    <label className="block text-sm font-medium text-gray-700 mb-1">Installation Cost (₦) *</label>
                                    <input
                                        type="number"
                                        step="0.01"
                                        required
                                        value={formData.installation_cost}
                                        onChange={(e) => setFormData({ ...formData, installation_cost: e.target.value })}
                                        className="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-green-500 focus:border-green-500"
                                    />
                                </div>
                                <div>
                                    <label className="block text-sm font-medium text-gray-700 mb-1">Amortization Cycles</label>
                                    <input
                                        type="number"
                                        min="1"
                                        value={formData.amortization_cycles}
                                        onChange={(e) => setFormData({ ...formData, amortization_cycles: e.target.value })}
                                        className="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-green-500 focus:border-green-500"
                                    />
                                    <p className="text-xs text-gray-500 mt-1">Default: 6 cycles</p>
                                </div>
                                <div className="md:col-span-2">
                                    <label className="block text-sm font-medium text-gray-700 mb-1">Location</label>
                                    <input
                                        type="text"
                                        value={formData.location}
                                        onChange={(e) => setFormData({ ...formData, location: e.target.value })}
                                        className="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-green-500 focus:border-green-500"
                                    />
                                </div>
                                <div className="md:col-span-2">
                                    <label className="block text-sm font-medium text-gray-700 mb-1">Specifications</label>
                                    <textarea
                                        value={formData.specifications}
                                        onChange={(e) => setFormData({ ...formData, specifications: e.target.value })}
                                        rows={3}
                                        className="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-green-500 focus:border-green-500"
                                        placeholder="Depth, capacity, pump type, etc."
                                    />
                                </div>
                                <div className="md:col-span-2">
                                    <label className="block text-sm font-medium text-gray-700 mb-1">Notes</label>
                                    <textarea
                                        value={formData.notes}
                                        onChange={(e) => setFormData({ ...formData, notes: e.target.value })}
                                        rows={3}
                                        className="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-green-500 focus:border-green-500"
                                    />
                                </div>
                                <div>
                                    <label className="flex items-center">
                                        <input
                                            type="checkbox"
                                            checked={formData.is_active}
                                            onChange={(e) => setFormData({ ...formData, is_active: e.target.checked })}
                                            className="rounded border-gray-300 text-green-600 focus:ring-green-500"
                                        />
                                        <span className="ml-2 text-sm text-gray-700">Active</span>
                                    </label>
                                </div>
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
                                    {editingBorehole ? 'Update' : 'Create'} Borehole
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            )}
        </div>
    );
}

