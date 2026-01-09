import React, { useEffect, useState } from 'react';
import api from '../utils/api';
import { Building2, Plus, Edit, Trash2 } from 'lucide-react';

export default function SiteTypes() {
    const [siteTypes, setSiteTypes] = useState([]);
    const [loading, setLoading] = useState(true);
    const [showModal, setShowModal] = useState(false);
    const [editingSiteType, setEditingSiteType] = useState(null);
    const [formData, setFormData] = useState({
        code: '',
        name: '',
        code_prefix: '',
        description: '',
        is_active: true,
    });

    useEffect(() => {
        fetchData();
    }, []);

    const fetchData = async () => {
        setLoading(true);
        try {
            const response = await api.get('/api/v1/site-types?per_page=1000');
            const data = response.data?.data || (Array.isArray(response.data) ? response.data : []);
            setSiteTypes(Array.isArray(data) ? data : []);
        } catch (error) {
            console.error('Error fetching site types:', error);
            setSiteTypes([]);
        } finally {
            setLoading(false);
        }
    };

    const handleModalOpen = (siteType = null) => {
        setEditingSiteType(siteType);
        setFormData({
            code: siteType?.code || '',
            name: siteType?.name || '',
            code_prefix: siteType?.code_prefix || '',
            description: siteType?.description || '',
            is_active: siteType?.is_active !== undefined ? siteType?.is_active : true,
        });
        setShowModal(true);
    };

    const handleSubmit = async (e) => {
        e.preventDefault();
        try {
            if (editingSiteType) {
                await api.put(`/api/v1/site-types/${editingSiteType.id}`, formData);
            } else {
                await api.post('/api/v1/site-types', formData);
            }
            setShowModal(false);
            fetchData();
        } catch (error) {
            console.error('Error saving site type:', error);
            const errorMessage = error.response?.data?.message || 
                               (error.response?.data?.errors ? JSON.stringify(error.response.data.errors) : '') ||
                               error.message;
            alert('Error saving site type: ' + errorMessage);
        }
    };

    const handleDelete = async (id) => {
        if (!confirm('Are you sure you want to delete this site type? Sites using this type will not be affected, but you won\'t be able to create new sites with this type.')) return;
        try {
            await api.delete(`/api/v1/site-types/${id}`);
            fetchData();
        } catch (error) {
            console.error('Error deleting site type:', error);
            const errorMessage = error.response?.data?.message || error.message;
            alert('Error deleting site type: ' + errorMessage);
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
        <div className="p-6">
            <div className="flex justify-between items-center mb-6">
                <div>
                    <h1 className="text-3xl font-bold text-gray-900">Site Types</h1>
                    <p className="mt-2 text-gray-600">Manage site types for organizing locations</p>
                </div>
                <button
                    onClick={() => handleModalOpen()}
                    className="bg-green-600 text-white px-4 py-2 rounded-lg hover:bg-green-700 flex items-center gap-2"
                >
                    <Plus size={20} />
                    Add Site Type
                </button>
            </div>

            <div className="bg-white rounded-lg shadow overflow-hidden">
                <table className="min-w-full divide-y divide-gray-200">
                    <thead className="bg-gray-50">
                        <tr>
                            <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Code</th>
                            <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Name</th>
                            <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Code Prefix</th>
                            <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Description</th>
                            <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Sites Count</th>
                            <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                            <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                        </tr>
                    </thead>
                    <tbody className="bg-white divide-y divide-gray-200">
                        {siteTypes.length === 0 ? (
                            <tr>
                                <td colSpan="7" className="px-6 py-4 text-center text-gray-500">No site types found</td>
                            </tr>
                        ) : (
                            siteTypes.map((siteType) => (
                                <tr key={siteType.id}>
                                    <td className="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">{siteType.code}</td>
                                    <td className="px-6 py-4 whitespace-nowrap text-sm text-gray-900">{siteType.name}</td>
                                    <td className="px-6 py-4 whitespace-nowrap text-sm text-gray-500">{siteType.code_prefix || '-'}</td>
                                    <td className="px-6 py-4 text-sm text-gray-500">{siteType.description || '-'}</td>
                                    <td className="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                        {siteType.sites?.length || 0}
                                    </td>
                                    <td className="px-6 py-4 whitespace-nowrap">
                                        <span className={`px-2 py-1 text-xs rounded-full ${siteType.is_active ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800'}`}>
                                            {siteType.is_active ? 'Active' : 'Inactive'}
                                        </span>
                                    </td>
                                    <td className="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                        <button
                                            onClick={() => handleModalOpen(siteType)}
                                            className="text-green-600 hover:text-green-900 mr-4"
                                        >
                                            <Edit size={16} />
                                        </button>
                                        <button
                                            onClick={() => handleDelete(siteType.id)}
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
                <div className="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50 p-4">
                    <div className="bg-white rounded-lg p-6 w-full max-w-2xl max-h-[90vh] overflow-y-auto">
                        <h2 className="text-xl font-bold mb-4">{editingSiteType ? 'Edit Site Type' : 'Add Site Type'}</h2>
                        <form onSubmit={handleSubmit}>
                            <div className="grid grid-cols-1 gap-4">
                                <div>
                                    <label className="block text-sm font-medium text-gray-700 mb-1">Code *</label>
                                    <input
                                        type="text"
                                        required
                                        value={formData.code}
                                        onChange={(e) => setFormData({ ...formData, code: e.target.value.toLowerCase().replace(/\s+/g, '-') })}
                                        className="w-full border border-gray-300 rounded-lg px-3 py-2"
                                        placeholder="e.g., farmland, warehouse"
                                        disabled={!!editingSiteType}
                                    />
                                    <p className="text-xs text-gray-500 mt-1">Unique identifier (lowercase, no spaces)</p>
                                </div>
                                <div>
                                    <label className="block text-sm font-medium text-gray-700 mb-1">Name *</label>
                                    <input
                                        type="text"
                                        required
                                        value={formData.name}
                                        onChange={(e) => setFormData({ ...formData, name: e.target.value })}
                                        className="w-full border border-gray-300 rounded-lg px-3 py-2"
                                        placeholder="e.g., Farmland"
                                    />
                                </div>
                                <div>
                                    <label className="block text-sm font-medium text-gray-700 mb-1">Code Prefix</label>
                                    <input
                                        type="text"
                                        value={formData.code_prefix}
                                        onChange={(e) => setFormData({ ...formData, code_prefix: e.target.value.toUpperCase() })}
                                        className="w-full border border-gray-300 rounded-lg px-3 py-2"
                                        placeholder="e.g., FL (for Farmland)"
                                        maxLength={10}
                                    />
                                    <p className="text-xs text-gray-500 mt-1">Used when auto-generating site codes</p>
                                </div>
                                <div>
                                    <label className="block text-sm font-medium text-gray-700 mb-1">Description</label>
                                    <textarea
                                        value={formData.description}
                                        onChange={(e) => setFormData({ ...formData, description: e.target.value })}
                                        className="w-full border border-gray-300 rounded-lg px-3 py-2"
                                        rows="3"
                                        placeholder="Brief description of this site type"
                                    />
                                </div>
                                <div>
                                    <label className="flex items-center gap-2">
                                        <input
                                            type="checkbox"
                                            checked={formData.is_active}
                                            onChange={(e) => setFormData({ ...formData, is_active: e.target.checked })}
                                            className="rounded border-gray-300 text-green-600 focus:ring-green-500"
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
                                    className="px-4 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700"
                                >
                                    {editingSiteType ? 'Update' : 'Create'}
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            )}
        </div>
    );
}
