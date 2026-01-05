import React, { useEffect, useState } from 'react';
import api from '../utils/api';
import { Warehouse, Plus, Edit, Trash2 } from 'lucide-react';

export default function Sites() {
    const [sites, setSites] = useState([]);
    const [farms, setFarms] = useState([]);
    const [locations, setLocations] = useState([]);
    const [loading, setLoading] = useState(true);
    const [showModal, setShowModal] = useState(false);
    const [editingSite, setEditingSite] = useState(null);
    const [formData, setFormData] = useState({
        farm_id: '',
        name: '',
        code: '',
        type: 'farmland',
        location_id: '',
        description: '',
        total_area: '',
        area_unit: 'hectares',
        is_active: true,
    });

    useEffect(() => {
        fetchData();
        fetchFarms();
        fetchLocations();
    }, []);

    const fetchData = async () => {
        setLoading(true);
        try {
            const response = await api.get('/api/v1/sites?per_page=1000');
            const data = response.data?.data || (Array.isArray(response.data) ? response.data : []);
            setSites(Array.isArray(data) ? data : []);
        } catch (error) {
            console.error('Error fetching sites:', error);
            setSites([]);
        } finally {
            setLoading(false);
        }
    };

    const fetchFarms = async () => {
        try {
            const response = await api.get('/api/v1/farms?per_page=1000');
            const data = response.data?.data || (Array.isArray(response.data) ? response.data : []);
            setFarms(Array.isArray(data) ? data : []);
        } catch (error) {
            console.error('Error fetching farms:', error);
        }
    };

    const fetchLocations = async () => {
        try {
            const response = await api.get('/api/v1/locations?per_page=1000');
            const data = response.data?.data || (Array.isArray(response.data) ? response.data : []);
            setLocations(Array.isArray(data) ? data : []);
        } catch (error) {
            console.error('Error fetching locations:', error);
        }
    };

    const handleModalOpen = () => {
        setEditingSite(null);
        setFormData({
            farm_id: '',
            name: '',
            code: '',
            type: 'farmland',
            location_id: '',
            description: '',
            total_area: '',
            area_unit: 'hectares',
            is_active: true,
        });
        setShowModal(true);
    };

    const handleEdit = (site) => {
        setEditingSite(site);
        setFormData({
            farm_id: site.farm_id || '',
            name: site.name || '',
            code: site.code || '',
            type: site.type || 'farmland',
            location_id: site.location_id || '',
            description: site.description || '',
            total_area: site.total_area || '',
            area_unit: site.area_unit || 'hectares',
            is_active: site.is_active !== undefined ? site.is_active : true,
        });
        setShowModal(true);
    };

    const handleSubmit = async (e) => {
        e.preventDefault();
        try {
            if (editingSite) {
                await api.put(`/api/v1/sites/${editingSite.id}`, formData);
            } else {
                await api.post('/api/v1/sites', formData);
            }
            setShowModal(false);
            fetchData();
        } catch (error) {
            console.error('Error saving site:', error);
            alert(error.response?.data?.message || 'Error saving site');
        }
    };

    const handleDelete = async (id) => {
        if (!confirm('Are you sure you want to delete this site?')) return;
        try {
            await api.delete(`/api/v1/sites/${id}`);
            fetchData();
        } catch (error) {
            console.error('Error deleting site:', error);
            alert(error.response?.data?.message || 'Error deleting site');
        }
    };

    const getTypeLabel = (type) => {
        const types = {
            farmland: 'Farmland',
            warehouse: 'Warehouse',
            factory: 'Factory',
            greenhouse: 'Greenhouse',
        };
        return types[type] || type;
    };

    if (loading) {
        return <div className="p-6">Loading...</div>;
    }

    return (
        <div className="p-6">
            <div className="flex justify-between items-center mb-6">
                <h1 className="text-2xl font-bold">Sites</h1>
                <button
                    onClick={handleModalOpen}
                    className="bg-blue-600 text-white px-4 py-2 rounded-lg hover:bg-blue-700 flex items-center gap-2"
                >
                    <Plus size={20} />
                    Create Site
                </button>
            </div>

            <div className="bg-white rounded-lg shadow overflow-hidden">
                <table className="min-w-full divide-y divide-gray-200">
                    <thead className="bg-gray-50">
                        <tr>
                            <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Code</th>
                            <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Name</th>
                            <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Type</th>
                            <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Farm</th>
                            <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                            <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                        </tr>
                    </thead>
                    <tbody className="bg-white divide-y divide-gray-200">
                        {sites.length === 0 ? (
                            <tr>
                                <td colSpan="6" className="px-6 py-4 text-center text-gray-500">No sites found</td>
                            </tr>
                        ) : (
                            sites.map((site) => (
                                <tr key={site.id}>
                                    <td className="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">{site.code}</td>
                                    <td className="px-6 py-4 whitespace-nowrap text-sm text-gray-900">{site.name}</td>
                                    <td className="px-6 py-4 whitespace-nowrap text-sm text-gray-500">{getTypeLabel(site.type)}</td>
                                    <td className="px-6 py-4 text-sm text-gray-500">{site.farm?.name || '-'}</td>
                                    <td className="px-6 py-4 whitespace-nowrap">
                                        <span className={`px-2 py-1 text-xs rounded-full ${site.is_active ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800'}`}>
                                            {site.is_active ? 'Active' : 'Inactive'}
                                        </span>
                                    </td>
                                    <td className="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                        <button
                                            onClick={() => handleEdit(site)}
                                            className="text-blue-600 hover:text-blue-900 mr-4"
                                        >
                                            <Edit size={16} />
                                        </button>
                                        <button
                                            onClick={() => handleDelete(site.id)}
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
                        <h2 className="text-xl font-bold mb-4">{editingSite ? 'Edit Site' : 'Create Site'}</h2>
                        <form onSubmit={handleSubmit}>
                            <div className="grid grid-cols-2 gap-4">
                                <div>
                                    <label className="block text-sm font-medium text-gray-700 mb-1">Farm *</label>
                                    <select
                                        required
                                        value={formData.farm_id}
                                        onChange={(e) => setFormData({ ...formData, farm_id: e.target.value })}
                                        className="w-full border border-gray-300 rounded-lg px-3 py-2"
                                    >
                                        <option value="">Select Farm</option>
                                        {farms.map((farm) => (
                                            <option key={farm.id} value={farm.id}>{farm.name}</option>
                                        ))}
                                    </select>
                                </div>
                                <div>
                                    <label className="block text-sm font-medium text-gray-700 mb-1">Type *</label>
                                    <select
                                        required
                                        value={formData.type}
                                        onChange={(e) => setFormData({ ...formData, type: e.target.value })}
                                        className="w-full border border-gray-300 rounded-lg px-3 py-2"
                                    >
                                        <option value="farmland">Farmland</option>
                                        <option value="warehouse">Warehouse</option>
                                        <option value="factory">Factory</option>
                                        <option value="greenhouse">Greenhouse</option>
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
                                        placeholder="Auto-generated if not provided"
                                    />
                                </div>
                                <div>
                                    <label className="block text-sm font-medium text-gray-700 mb-1">Location</label>
                                    <select
                                        value={formData.location_id}
                                        onChange={(e) => setFormData({ ...formData, location_id: e.target.value || null })}
                                        className="w-full border border-gray-300 rounded-lg px-3 py-2"
                                    >
                                        <option value="">Select Location</option>
                                        {locations.map((location) => (
                                            <option key={location.id} value={location.id}>{location.name}</option>
                                        ))}
                                    </select>
                                </div>
                                <div>
                                    <label className="block text-sm font-medium text-gray-700 mb-1">Total Area</label>
                                    <input
                                        type="number"
                                        step="0.01"
                                        value={formData.total_area}
                                        onChange={(e) => setFormData({ ...formData, total_area: e.target.value })}
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
                                    {editingSite ? 'Update' : 'Create'}
                                </button>
                            </div>
                            </form>
                        </div>
                    </div>
                </div>
            )}
        </div>
    );
}

