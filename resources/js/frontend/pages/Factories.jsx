import React, { useEffect, useState } from 'react';
import api from '../utils/api';
import { Building2, Plus, Edit, Trash2 } from 'lucide-react';

export default function Factories() {
    const [factories, setFactories] = useState([]);
    const [sites, setSites] = useState([]);
    const [loading, setLoading] = useState(true);
    const [showModal, setShowModal] = useState(false);
    const [editingFactory, setEditingFactory] = useState(null);
    const [formData, setFormData] = useState({
        site_id: '',
        name: '',
        code: '',
        production_type: 'gari',
        description: '',
        area_sqm: '',
        established_date: '',
        is_active: true,
    });

    useEffect(() => {
        fetchData();
        fetchSites();
    }, []);

    const fetchData = async () => {
        setLoading(true);
        try {
            const response = await api.get('/api/v1/factories?per_page=1000');
            const data = response.data?.data || (Array.isArray(response.data) ? response.data : []);
            setFactories(Array.isArray(data) ? data : []);
        } catch (error) {
            console.error('Error fetching factories:', error);
            setFactories([]);
        } finally {
            setLoading(false);
        }
    };

    const fetchSites = async () => {
        try {
            const response = await api.get('/api/v1/sites?type=factory&per_page=1000');
            const data = response.data?.data || (Array.isArray(response.data) ? response.data : []);
            setSites(Array.isArray(data) ? data : []);
        } catch (error) {
            console.error('Error fetching sites:', error);
        }
    };

    const handleModalOpen = () => {
        setEditingFactory(null);
        setFormData({
            site_id: '',
            name: '',
            code: '',
            production_type: 'gari',
            description: '',
            area_sqm: '',
            established_date: '',
            is_active: true,
        });
        setShowModal(true);
    };

    const handleEdit = (factory) => {
        setEditingFactory(factory);
        setFormData({
            site_id: factory.site_id || '',
            name: factory.name || '',
            code: factory.code || '',
            production_type: factory.production_type || 'gari',
            description: factory.description || '',
            area_sqm: factory.area_sqm || '',
            established_date: factory.established_date || '',
            is_active: factory.is_active !== undefined ? factory.is_active : true,
        });
        setShowModal(true);
    };

    const handleSubmit = async (e) => {
        e.preventDefault();
        try {
            if (editingFactory) {
                await api.put(`/api/v1/factories/${editingFactory.id}`, formData);
            } else {
                await api.post('/api/v1/factories', formData);
            }
            setShowModal(false);
            fetchData();
        } catch (error) {
            console.error('Error saving factory:', error);
            alert(error.response?.data?.message || 'Error saving factory');
        }
    };

    const handleDelete = async (id) => {
        if (!confirm('Are you sure you want to delete this factory?')) return;
        try {
            await api.delete(`/api/v1/factories/${id}`);
            fetchData();
        } catch (error) {
            console.error('Error deleting factory:', error);
            alert(error.response?.data?.message || 'Error deleting factory');
        }
    };

    if (loading) {
        return <div className="p-6">Loading...</div>;
    }

    return (
        <div className="p-6">
            <div className="flex justify-between items-center mb-6">
                <h1 className="text-2xl font-bold">Factories</h1>
                <button
                    onClick={handleModalOpen}
                    className="bg-blue-600 text-white px-4 py-2 rounded-lg hover:bg-blue-700 flex items-center gap-2"
                >
                    <Plus size={20} />
                    Create Factory
                </button>
            </div>

            <div className="bg-white rounded-lg shadow overflow-hidden">
                <table className="min-w-full divide-y divide-gray-200">
                    <thead className="bg-gray-50">
                        <tr>
                            <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Code</th>
                            <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Name</th>
                            <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Site</th>
                            <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Production Type</th>
                            <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                            <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                        </tr>
                    </thead>
                    <tbody className="bg-white divide-y divide-gray-200">
                        {factories.length === 0 ? (
                            <tr>
                                <td colSpan="6" className="px-6 py-4 text-center text-gray-500">No factories found</td>
                            </tr>
                        ) : (
                            factories.map((factory) => (
                                <tr key={factory.id}>
                                    <td className="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">{factory.code}</td>
                                    <td className="px-6 py-4 whitespace-nowrap text-sm text-gray-900">{factory.name}</td>
                                    <td className="px-6 py-4 text-sm text-gray-500">{factory.site?.name || '-'}</td>
                                    <td className="px-6 py-4 text-sm text-gray-500 capitalize">{factory.production_type || '-'}</td>
                                    <td className="px-6 py-4 whitespace-nowrap">
                                        <span className={`px-2 py-1 text-xs rounded-full ${factory.is_active ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800'}`}>
                                            {factory.is_active ? 'Active' : 'Inactive'}
                                        </span>
                                    </td>
                                    <td className="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                        <button
                                            onClick={() => handleEdit(factory)}
                                            className="text-blue-600 hover:text-blue-900 mr-4"
                                        >
                                            <Edit size={16} />
                                        </button>
                                        <button
                                            onClick={() => handleDelete(factory.id)}
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
                        <h2 className="text-xl font-bold mb-4">{editingFactory ? 'Edit Factory' : 'Create Factory'}</h2>
                        <form onSubmit={handleSubmit}>
                            <div className="grid grid-cols-2 gap-4">
                                <div>
                                    <label className="block text-sm font-medium text-gray-700 mb-1">Site (Factory Type) *</label>
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
                                    <label className="block text-sm font-medium text-gray-700 mb-1">Production Type *</label>
                                    <select
                                        required
                                        value={formData.production_type}
                                        onChange={(e) => setFormData({ ...formData, production_type: e.target.value })}
                                        className="w-full border border-gray-300 rounded-lg px-3 py-2"
                                    >
                                        <option value="gari">Gari</option>
                                        <option value="other">Other</option>
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
                                    <label className="block text-sm font-medium text-gray-700 mb-1">Area (sqm)</label>
                                    <input
                                        type="number"
                                        step="0.01"
                                        value={formData.area_sqm}
                                        onChange={(e) => setFormData({ ...formData, area_sqm: e.target.value })}
                                        className="w-full border border-gray-300 rounded-lg px-3 py-2"
                                    />
                                </div>
                                <div>
                                    <label className="block text-sm font-medium text-gray-700 mb-1">Established Date</label>
                                    <input
                                        type="date"
                                        value={formData.established_date}
                                        onChange={(e) => setFormData({ ...formData, established_date: e.target.value })}
                                        className="w-full border border-gray-300 rounded-lg px-3 py-2"
                                    />
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
                                    {editingFactory ? 'Update' : 'Create'}
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            )}
        </div>
    );
}

