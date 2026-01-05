import React, { useEffect, useState } from 'react';
import api from '../utils/api';
import { Grid3x3, Plus, Edit, Trash2 } from 'lucide-react';

export default function FarmZones() {
    const [zones, setZones] = useState([]);
    const [sites, setSites] = useState([]);
    const [crops, setCrops] = useState([]);
    const [loading, setLoading] = useState(true);
    const [showModal, setShowModal] = useState(false);
    const [editingZone, setEditingZone] = useState(null);
    const [formData, setFormData] = useState({
        site_id: '',
        crop_id: '',
        name: '',
        code: '',
        description: '',
        area: '',
        area_unit: 'hectares',
        produce_type: '',
        soil_type: '',
        is_active: true,
    });

    useEffect(() => {
        fetchData();
        fetchSites();
        fetchCrops();
    }, []);

    const fetchData = async () => {
        setLoading(true);
        try {
            const response = await api.get('/api/v1/farm-zones?per_page=1000');
            const data = response.data?.data || (Array.isArray(response.data) ? response.data : []);
            setZones(Array.isArray(data) ? data : []);
        } catch (error) {
            console.error('Error fetching farm zones:', error);
            setZones([]);
        } finally {
            setLoading(false);
        }
    };

    const fetchSites = async () => {
        try {
            const response = await api.get('/api/v1/sites?type=farmland&per_page=1000');
            const data = response.data?.data || (Array.isArray(response.data) ? response.data : []);
            setSites(Array.isArray(data) ? data : []);
        } catch (error) {
            console.error('Error fetching sites:', error);
        }
    };

    const fetchCrops = async () => {
        try {
            const response = await api.get('/api/v1/crops?per_page=1000');
            const data = response.data?.data || (Array.isArray(response.data) ? response.data : []);
            setCrops(Array.isArray(data) ? data : []);
        } catch (error) {
            console.error('Error fetching crops:', error);
        }
    };

    const handleModalOpen = () => {
        setEditingZone(null);
        setFormData({
            site_id: '',
            crop_id: '',
            name: '',
            code: '',
            description: '',
            area: '',
            area_unit: 'hectares',
            produce_type: '',
            soil_type: '',
            is_active: true,
        });
        setShowModal(true);
    };

    const handleEdit = (zone) => {
        setEditingZone(zone);
        setFormData({
            site_id: zone.site_id || '',
            crop_id: zone.crop_id || '',
            name: zone.name || '',
            code: zone.code || '',
            description: zone.description || '',
            area: zone.area || '',
            area_unit: zone.area_unit || 'hectares',
            produce_type: zone.produce_type || '',
            soil_type: zone.soil_type || '',
            is_active: zone.is_active !== undefined ? zone.is_active : true,
        });
        setShowModal(true);
    };

    const handleSubmit = async (e) => {
        e.preventDefault();
        try {
            if (editingZone) {
                await api.put(`/api/v1/farm-zones/${editingZone.id}`, formData);
            } else {
                await api.post('/api/v1/farm-zones', formData);
            }
            setShowModal(false);
            fetchData();
        } catch (error) {
            console.error('Error saving farm zone:', error);
            alert(error.response?.data?.message || 'Error saving farm zone');
        }
    };

    const handleDelete = async (id) => {
        if (!confirm('Are you sure you want to delete this farm zone?')) return;
        try {
            await api.delete(`/api/v1/farm-zones/${id}`);
            fetchData();
        } catch (error) {
            console.error('Error deleting farm zone:', error);
            alert(error.response?.data?.message || 'Error deleting farm zone');
        }
    };

    if (loading) {
        return <div className="p-6">Loading...</div>;
    }

    return (
        <div className="p-6">
            <div className="flex justify-between items-center mb-6">
                <h1 className="text-2xl font-bold">Farm Zones</h1>
                <button
                    onClick={handleModalOpen}
                    className="bg-blue-600 text-white px-4 py-2 rounded-lg hover:bg-blue-700 flex items-center gap-2"
                >
                    <Plus size={20} />
                    Create Farm Zone
                </button>
            </div>

            <div className="bg-white rounded-lg shadow overflow-hidden">
                <table className="min-w-full divide-y divide-gray-200">
                    <thead className="bg-gray-50">
                        <tr>
                            <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Code</th>
                            <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Name</th>
                            <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Site</th>
                            <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Produce Type</th>
                            <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Area</th>
                            <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                            <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                        </tr>
                    </thead>
                    <tbody className="bg-white divide-y divide-gray-200">
                        {zones.length === 0 ? (
                            <tr>
                                <td colSpan="7" className="px-6 py-4 text-center text-gray-500">No farm zones found</td>
                            </tr>
                        ) : (
                            zones.map((zone) => (
                                <tr key={zone.id}>
                                    <td className="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">{zone.code || '-'}</td>
                                    <td className="px-6 py-4 whitespace-nowrap text-sm text-gray-900">{zone.name}</td>
                                    <td className="px-6 py-4 text-sm text-gray-500">{zone.site?.name || '-'}</td>
                                    <td className="px-6 py-4 text-sm text-gray-500">{zone.produce_type || '-'}</td>
                                    <td className="px-6 py-4 text-sm text-gray-500">{zone.area ? `${zone.area} ${zone.area_unit}` : '-'}</td>
                                    <td className="px-6 py-4 whitespace-nowrap">
                                        <span className={`px-2 py-1 text-xs rounded-full ${zone.is_active ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800'}`}>
                                            {zone.is_active ? 'Active' : 'Inactive'}
                                        </span>
                                    </td>
                                    <td className="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                        <button
                                            onClick={() => handleEdit(zone)}
                                            className="text-blue-600 hover:text-blue-900 mr-4"
                                        >
                                            <Edit size={16} />
                                        </button>
                                        <button
                                            onClick={() => handleDelete(zone.id)}
                                            className="text-red-600 hover:text-red-900"
                                        >
                                            <Trash2 size={16} />
                                        </button>
                                    </td>
                                </tr>
                            ))
                        )}
                    </tbody>
                </table>
            </div>

            {showModal && (
                <div className="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50">
                    <div className="bg-white rounded-lg p-6 w-full max-w-2xl max-h-[90vh] overflow-y-auto">
                        <h2 className="text-xl font-bold mb-4">{editingZone ? 'Edit Farm Zone' : 'Create Farm Zone'}</h2>
                        <form onSubmit={handleSubmit}>
                            <div className="grid grid-cols-2 gap-4">
                                <div>
                                    <label className="block text-sm font-medium text-gray-700 mb-1">Site (Farmland) *</label>
                                    <select
                                        required
                                        value={formData.site_id}
                                        onChange={(e) => setFormData({ ...formData, site_id: e.target.value })}
                                        className="w-full border border-gray-300 rounded-lg px-3 py-2"
                                    >
                                        <option value="">Select Site</option>
                                        {sites.map((site) => (
                                            <option key={site.id} value={site.id}>{site.name}</option>
                                        ))}
                                    </select>
                                </div>
                                <div>
                                    <label className="block text-sm font-medium text-gray-700 mb-1">Crop</label>
                                    <select
                                        value={formData.crop_id}
                                        onChange={(e) => setFormData({ ...formData, crop_id: e.target.value || null })}
                                        className="w-full border border-gray-300 rounded-lg px-3 py-2"
                                    >
                                        <option value="">Select Crop</option>
                                        {crops.map((crop) => (
                                            <option key={crop.id} value={crop.id}>{crop.name}</option>
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
                                        className="w-full border border-gray-300 rounded-lg px-3 py-2"
                                    />
                                </div>
                                <div>
                                    <label className="block text-sm font-medium text-gray-700 mb-1">Code</label>
                                    <input
                                        type="text"
                                        value={formData.code}
                                        onChange={(e) => setFormData({ ...formData, code: e.target.value })}
                                        className="w-full border border-gray-300 rounded-lg px-3 py-2"
                                    />
                                </div>
                                <div>
                                    <label className="block text-sm font-medium text-gray-700 mb-1">Produce Type</label>
                                    <input
                                        type="text"
                                        value={formData.produce_type}
                                        onChange={(e) => setFormData({ ...formData, produce_type: e.target.value })}
                                        className="w-full border border-gray-300 rounded-lg px-3 py-2"
                                        placeholder="e.g., tomatoes, corn"
                                    />
                                </div>
                                <div>
                                    <label className="block text-sm font-medium text-gray-700 mb-1">Soil Type</label>
                                    <input
                                        type="text"
                                        value={formData.soil_type}
                                        onChange={(e) => setFormData({ ...formData, soil_type: e.target.value })}
                                        className="w-full border border-gray-300 rounded-lg px-3 py-2"
                                    />
                                </div>
                                <div>
                                    <label className="block text-sm font-medium text-gray-700 mb-1">Area</label>
                                    <input
                                        type="number"
                                        step="0.01"
                                        value={formData.area}
                                        onChange={(e) => setFormData({ ...formData, area: e.target.value })}
                                        className="w-full border border-gray-300 rounded-lg px-3 py-2"
                                    />
                                </div>
                                <div>
                                    <label className="block text-sm font-medium text-gray-700 mb-1">Area Unit</label>
                                    <select
                                        value={formData.area_unit}
                                        onChange={(e) => setFormData({ ...formData, area_unit: e.target.value })}
                                        className="w-full border border-gray-300 rounded-lg px-3 py-2"
                                    >
                                        <option value="hectares">Hectares</option>
                                        <option value="acres">Acres</option>
                                        <option value="sqm">Sqm</option>
                                    </select>
                                </div>
                                <div className="col-span-2">
                                    <label className="block text-sm font-medium text-gray-700 mb-1">Description</label>
                                    <textarea
                                        value={formData.description}
                                        onChange={(e) => setFormData({ ...formData, description: e.target.value })}
                                        className="w-full border border-gray-300 rounded-lg px-3 py-2"
                                        rows="3"
                                    />
                                </div>
                                <div>
                                    <label className="flex items-center gap-2">
                                        <input
                                            type="checkbox"
                                            checked={formData.is_active}
                                            onChange={(e) => setFormData({ ...formData, is_active: e.target.checked })}
                                        />
                                        <span className="text-sm font-medium text-gray-700">Active</span>
                                    </label>
                                </div>
                            </div>
                            <div className="mt-6 flex justify-end gap-3">
                                <button
                                    type="button"
                                    onClick={() => setShowModal(false)}
                                    className="px-4 py-2 border border-gray-300 rounded-lg hover:bg-gray-50"
                                >
                                    Cancel
                                </button>
                                <button
                                    type="submit"
                                    className="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700"
                                >
                                    {editingZone ? 'Update' : 'Create'}
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            )}
        </div>
    );
}

