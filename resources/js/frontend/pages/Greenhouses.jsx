import React, { useEffect, useState } from 'react';
import api from '../utils/api';
import { Factory, Plus, Edit, Trash2 } from 'lucide-react';

export default function Greenhouses() {
    const [greenhouses, setGreenhouses] = useState([]);
    const [farms, setFarms] = useState([]);
    const [boreholes, setBoreholes] = useState([]);
    const [locations, setLocations] = useState([]);
    const [loading, setLoading] = useState(true);
    const [showModal, setShowModal] = useState(false);
    const [editingGreenhouse, setEditingGreenhouse] = useState(null);
    const [formData, setFormData] = useState({
        farm_id: '',
        name: '',
        size_sqm: '',
        built_date: new Date().toISOString().slice(0, 10),
        construction_cost: '',
        amortization_cycles: 6,
        borehole_ids: [],
        location_id: '',
        notes: '',
        is_active: true,
    });

    useEffect(() => {
        fetchData();
    }, []);

    const fetchData = async () => {
        try {
            console.log('Fetching greenhouses and farms...');
            const [greenhousesRes, farmsRes] = await Promise.all([
                api.get('/api/v1/greenhouses').catch(err => {
                    console.error('Error fetching greenhouses:', err);
                    console.error('URL:', err.config?.url);
                    console.error('Status:', err.response?.status);
                    console.error('Response:', err.response?.data);
                    throw err;
                }),
                api.get('/api/v1/farms?per_page=1000').catch(err => {
                    console.error('Error fetching farms:', err);
                    console.error('URL:', err.config?.url);
                    console.error('Status:', err.response?.status);
                    console.error('Response:', err.response?.data);
                    throw err;
                }),
            ]);

            console.log('Greenhouses response:', greenhousesRes);
            console.log('Farms response:', farmsRes);

            // Handle paginated response structure
            const greenhousesData = greenhousesRes.data?.data || (Array.isArray(greenhousesRes.data) ? greenhousesRes.data : []);
            const farmsData = farmsRes.data?.data || (Array.isArray(farmsRes.data) ? farmsRes.data : []);

            console.log('Greenhouses data:', greenhousesData);
            console.log('Farms data:', farmsData);

            setGreenhouses(Array.isArray(greenhousesData) ? greenhousesData : []);
            setFarms(Array.isArray(farmsData) ? farmsData : []);
        } catch (error) {
            console.error('Error fetching data:', error);
            console.error('Error details:', {
                message: error.message,
                status: error.response?.status,
                statusText: error.response?.statusText,
                data: error.response?.data,
                url: error.config?.url,
                method: error.config?.method,
            });
            // Set empty arrays on error so the UI can still render
            setGreenhouses([]);
            setFarms([]);
        } finally {
            setLoading(false);
        }
    };

    const fetchBoreholes = async (farmId) => {
        if (!farmId) {
            setBoreholes([]);
            return;
        }
        try {
            const response = await api.get(`/api/v1/boreholes?farm_id=${farmId}&per_page=1000`);
            const data = response.data?.data || response.data || [];
            setBoreholes(Array.isArray(data) ? data : []);
        } catch (error) {
            console.error('Error fetching boreholes:', error);
            setBoreholes([]);
        }
    };

    const handleModalOpen = () => {
        console.log('Opening modal, farms available:', farms.length);
        setEditingGreenhouse(null);
        setFormData({
            farm_id: '',
            name: '',
            size_sqm: '',
            built_date: new Date().toISOString().slice(0, 10),
            construction_cost: '',
            amortization_cycles: 6,
            borehole_ids: [],
            location_id: '',
            notes: '',
            is_active: true,
        });
        setBoreholes([]);
        setShowModal(true);
    };

    const handleFarmChange = (farmId) => {
        setFormData({ ...formData, farm_id: farmId, borehole_ids: [] });
        fetchBoreholes(farmId);
    };

    const handleEdit = async (greenhouse) => {
        setEditingGreenhouse(greenhouse);
        setFormData({
            farm_id: greenhouse.farm_id,
            name: greenhouse.name,
            size_sqm: greenhouse.size_sqm,
            built_date: greenhouse.built_date ? new Date(greenhouse.built_date).toISOString().slice(0, 10) : '',
            construction_cost: greenhouse.construction_cost || '',
            amortization_cycles: greenhouse.amortization_cycles || 6,
            borehole_ids: greenhouse.boreholes ? greenhouse.boreholes.map(b => b.id) : [],
            location_id: greenhouse.location_id || '',
            notes: greenhouse.notes || '',
            is_active: greenhouse.is_active !== undefined ? greenhouse.is_active : true,
        });
        await fetchBoreholes(greenhouse.farm_id);
        await fetchLocations();
        setShowModal(true);
    };

    const handleSubmit = async (e) => {
        e.preventDefault();
        console.log('Form submitted with data:', formData);
        try {
            const payload = {
                ...formData,
                farm_id: parseInt(formData.farm_id),
                size_sqm: parseFloat(formData.size_sqm),
                construction_cost: parseFloat(formData.construction_cost),
                amortization_cycles: parseInt(formData.amortization_cycles),
                borehole_ids: Array.isArray(formData.borehole_ids) ? formData.borehole_ids.map(id => parseInt(id)) : [],
            };
            console.log('Sending payload:', payload);
            
            if (editingGreenhouse) {
                await api.put(`/api/v1/greenhouses/${editingGreenhouse.id}`, payload);
            } else {
                await api.post('/api/v1/greenhouses', payload);
            }
            setShowModal(false);
            fetchData();
        } catch (error) {
            console.error('Error saving greenhouse:', error);
            console.error('Error response:', error.response?.data);
            alert('Error saving greenhouse: ' + (error.response?.data?.message || error.message || 'Unknown error'));
        }
    };

    const handleDelete = async (id) => {
        if (!confirm('Are you sure you want to delete this greenhouse?')) return;
        try {
            await api.delete(`/api/v1/greenhouses/${id}`);
            fetchData();
        } catch (error) {
            alert('Error deleting greenhouse: ' + (error.response?.data?.message || 'Unknown error'));
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
                    <h1 className="text-3xl font-bold text-gray-900">Greenhouses</h1>
                    <p className="mt-2 text-gray-600">Manage greenhouse settings and configuration</p>
                </div>
                <button
                    onClick={handleModalOpen}
                    className="bg-green-600 text-white px-4 py-2 rounded-lg hover:bg-green-700 flex items-center"
                >
                    <Plus className="h-5 w-5 mr-2" />
                    New Greenhouse
                </button>
            </div>

            {greenhouses.length === 0 ? (
                <div className="bg-white rounded-lg shadow p-12 text-center">
                    <Factory className="h-16 w-16 mx-auto text-gray-400 mb-4" />
                    <h3 className="text-lg font-medium text-gray-900 mb-2">No greenhouses yet</h3>
                    <p className="text-gray-500 mb-4">Create your first greenhouse to get started</p>
                    <button
                        onClick={handleModalOpen}
                        className="bg-green-600 text-white px-4 py-2 rounded-lg hover:bg-green-700"
                    >
                        Create Greenhouse
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
                                    <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Size (sqm)</th>
                                    <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Built Date</th>
                                    <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Construction Cost</th>
                                    <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Status</th>
                                    <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Actions</th>
                                </tr>
                            </thead>
                            <tbody className="bg-white divide-y divide-gray-200">
                                {greenhouses.map((greenhouse) => (
                                    <tr key={greenhouse.id} className="hover:bg-gray-50">
                                        <td className="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
                                            {greenhouse.code}
                                        </td>
                                        <td className="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                            {greenhouse.name}
                                        </td>
                                        <td className="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                            {greenhouse.farm?.name || 'N/A'}
                                        </td>
                                        <td className="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                            {Number(greenhouse.size_sqm || 0).toFixed(2)} sqm
                                        </td>
                                        <td className="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                            {greenhouse.built_date ? new Date(greenhouse.built_date).toLocaleDateString() : 'N/A'}
                                        </td>
                                        <td className="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                            ₦{Number(greenhouse.construction_cost || 0).toFixed(2)}
                                        </td>
                                        <td className="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                            {greenhouse.boreholes && greenhouse.boreholes.length > 0
                                                ? greenhouse.boreholes.map(b => b.name).join(', ')
                                                : 'None'}
                                        </td>
                                        <td className="px-6 py-4 whitespace-nowrap">
                                            <span className={`px-2 py-1 text-xs font-medium rounded ${
                                                greenhouse.is_active ? 'bg-green-100 text-green-800' : 'bg-gray-100 text-gray-800'
                                            }`}>
                                                {greenhouse.is_active ? 'Active' : 'Inactive'}
                                            </span>
                                        </td>
                                        <td className="px-6 py-4 whitespace-nowrap text-sm">
                                            <div className="flex space-x-2">
                                                <button
                                                    onClick={() => handleEdit(greenhouse)}
                                                    className="text-green-600 hover:text-green-700"
                                                >
                                                    <Edit className="h-4 w-4" />
                                                </button>
                                                <button
                                                    onClick={() => handleDelete(greenhouse.id)}
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
                            {editingGreenhouse ? 'Edit Greenhouse' : 'Create New Greenhouse'}
                        </h2>
                        <form onSubmit={handleSubmit} className="space-y-4">
                            <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
                                <div>
                                    <label className="block text-sm font-medium text-gray-700 mb-1">Farm *</label>
                                    <select
                                        required
                                        value={formData.farm_id}
                                        onChange={(e) => handleFarmChange(e.target.value)}
                                        className="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-green-500 focus:border-green-500"
                                    >
                                        <option value="">Select farm</option>
                                        {farms.length === 0 ? (
                                            <option disabled>No farms available. Create a farm first.</option>
                                        ) : (
                                            farms.map((farm) => (
                                                <option key={farm.id} value={farm.id}>{farm.name}</option>
                                            ))
                                        )}
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
                                    <label className="block text-sm font-medium text-gray-700 mb-1">Size (sqm) *</label>
                                    <input
                                        type="number"
                                        step="0.01"
                                        required
                                        value={formData.size_sqm}
                                        onChange={(e) => setFormData({ ...formData, size_sqm: e.target.value })}
                                        className="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-green-500 focus:border-green-500"
                                    />
                                </div>
                                <div>
                                    <label className="block text-sm font-medium text-gray-700 mb-1">Built Date *</label>
                                    <input
                                        type="date"
                                        required
                                        value={formData.built_date}
                                        onChange={(e) => setFormData({ ...formData, built_date: e.target.value })}
                                        className="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-green-500 focus:border-green-500"
                                    />
                                </div>
                                <div>
                                    <label className="block text-sm font-medium text-gray-700 mb-1">Construction Cost (₦) *</label>
                                    <input
                                        type="number"
                                        step="0.01"
                                        required
                                        value={formData.construction_cost}
                                        onChange={(e) => setFormData({ ...formData, construction_cost: e.target.value })}
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
                                    <label className="block text-sm font-medium text-gray-700 mb-1">Linked Boreholes</label>
                                    <select
                                        multiple
                                        disabled={!formData.farm_id || boreholes.length === 0}
                                        value={formData.borehole_ids.map(id => id.toString())}
                                        onChange={(e) => {
                                            const selected = Array.from(e.target.selectedOptions, option => parseInt(option.value));
                                            setFormData({ ...formData, borehole_ids: selected });
                                        }}
                                        className={`w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-green-500 focus:border-green-500 ${
                                            !formData.farm_id || boreholes.length === 0
                                                ? 'bg-gray-100 text-gray-500 cursor-not-allowed'
                                                : 'bg-white'
                                        }`}
                                        size={Math.min(boreholes.length + 1, 5)}
                                    >
                                        {boreholes.length === 0 ? (
                                            <option disabled>
                                                {!formData.farm_id ? 'Select farm first' : 'No boreholes available for this farm'}
                                            </option>
                                        ) : (
                                            boreholes.map((borehole) => (
                                                <option key={borehole.id} value={borehole.id}>
                                                    {borehole.name} ({borehole.code}) - ₦{Number(borehole.installation_cost || 0).toFixed(2)}
                                                </option>
                                            ))
                                        )}
                                    </select>
                                    <p className="text-xs text-gray-500 mt-1">Hold Ctrl/Cmd to select multiple boreholes</p>
                                </div>
                                <div className="md:col-span-2">
                                    <label className="block text-sm font-medium text-gray-700 mb-1">Location</label>
                                    <select
                                        value={formData.location_id}
                                        onChange={(e) => setFormData({ ...formData, location_id: e.target.value })}
                                        className="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-green-500 focus:border-green-500"
                                    >
                                        <option value="">Select Location</option>
                                        {locations.map((location) => (
                                            <option key={location.id} value={location.id}>
                                                {location.name}
                                            </option>
                                        ))}
                                    </select>
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
                                    {editingGreenhouse ? 'Update' : 'Create'} Greenhouse
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            )}
        </div>
    );
}

