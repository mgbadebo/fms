import React, { useEffect, useState } from 'react';
import api from '../utils/api';
import { Settings, Plus, Edit, Trash2, X } from 'lucide-react';

export default function ActivityTypes() {
    const [activityTypes, setActivityTypes] = useState([]);
    const [farms, setFarms] = useState([]);
    const [loading, setLoading] = useState(true);
    const [showModal, setShowModal] = useState(false);
    const [editingType, setEditingType] = useState(null);
    const [formData, setFormData] = useState({
        farm_id: '',
        code: '',
        name: '',
        description: '',
        category: '',
        requires_quantity: false,
        requires_time_range: false,
        requires_inputs: false,
        requires_photos: false,
        is_active: true,
    });

    useEffect(() => {
        fetchData();
    }, []);

    const fetchData = async () => {
        try {
            const [typesRes, farmsRes] = await Promise.all([
                api.get('/api/v1/activity-types?per_page=1000'),
                api.get('/api/v1/farms?per_page=1000'),
            ]);

            const typesData = typesRes.data?.data || typesRes.data || [];
            const farmsData = farmsRes.data?.data || farmsRes.data || [];

            setActivityTypes(Array.isArray(typesData) ? typesData : []);
            setFarms(Array.isArray(farmsData) ? farmsData : []);
        } catch (error) {
            console.error('Error fetching data:', error);
            alert('Error loading data: ' + (error.response?.data?.message || error.message));
        } finally {
            setLoading(false);
        }
    };

    const handleModalOpen = () => {
        setEditingType(null);
        setFormData({
            farm_id: '',
            code: '',
            name: '',
            description: '',
            category: '',
            requires_quantity: false,
            requires_time_range: false,
            requires_inputs: false,
            requires_photos: false,
            is_active: true,
        });
        setShowModal(true);
    };

    const handleEdit = (type) => {
        setEditingType(type);
        setFormData({
            farm_id: type.farm_id || '',
            code: type.code || '',
            name: type.name || '',
            description: type.description || '',
            category: type.category || '',
            requires_quantity: type.requires_quantity || false,
            requires_time_range: type.requires_time_range || false,
            requires_inputs: type.requires_inputs || false,
            requires_photos: type.requires_photos || false,
            is_active: type.is_active !== false,
        });
        setShowModal(true);
    };

    const handleSubmit = async (e) => {
        e.preventDefault();
        try {
            if (editingType) {
                await api.patch(`/api/v1/activity-types/${editingType.id}`, formData);
            } else {
                await api.post('/api/v1/activity-types', formData);
            }
            setShowModal(false);
            fetchData();
        } catch (error) {
            const errorMsg = error.response?.data?.message || error.response?.data?.errors || 'Unknown error';
            alert('Error saving activity type: ' + (typeof errorMsg === 'string' ? errorMsg : JSON.stringify(errorMsg)));
        }
    };

    const handleDelete = async (typeId) => {
        if (!confirm('Deactivate this activity type? (It will be soft-deleted)')) return;
        try {
            await api.delete(`/api/v1/activity-types/${typeId}`);
            fetchData();
        } catch (error) {
            alert('Error deleting activity type: ' + (error.response?.data?.message || error.message));
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
            <div className="mb-8 flex justify-between items-center">
                <div>
                    <h1 className="text-3xl font-bold text-gray-900">Activity Types</h1>
                    <p className="mt-2 text-gray-600">Manage activity types for daily logs</p>
                </div>
                <button
                    onClick={handleModalOpen}
                    className="flex items-center px-4 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700"
                >
                    <Plus className="h-5 w-5 mr-2" />
                    New Activity Type
                </button>
            </div>

            {/* Activity Types Table */}
            <div className="bg-white rounded-lg shadow overflow-hidden">
                <table className="min-w-full divide-y divide-gray-200">
                    <thead className="bg-gray-50">
                        <tr>
                            <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Code</th>
                            <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Name</th>
                            <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Category</th>
                            <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Requirements</th>
                            <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                            <th className="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                        </tr>
                    </thead>
                    <tbody className="bg-white divide-y divide-gray-200">
                        {activityTypes.length === 0 ? (
                            <tr>
                                <td colSpan="6" className="px-6 py-4 text-center text-gray-500">
                                    No activity types found
                                </td>
                            </tr>
                        ) : (
                            activityTypes.map((type) => (
                                <tr key={type.id}>
                                    <td className="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
                                        {type.code}
                                    </td>
                                    <td className="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                        {type.name}
                                    </td>
                                    <td className="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                        {type.category || 'N/A'}
                                    </td>
                                    <td className="px-6 py-4 text-sm text-gray-500">
                                        <div className="flex flex-wrap gap-1">
                                            {type.requires_quantity && (
                                                <span className="px-2 py-1 bg-blue-100 text-blue-800 text-xs rounded">Quantity</span>
                                            )}
                                            {type.requires_time_range && (
                                                <span className="px-2 py-1 bg-green-100 text-green-800 text-xs rounded">Time</span>
                                            )}
                                            {type.requires_inputs && (
                                                <span className="px-2 py-1 bg-yellow-100 text-yellow-800 text-xs rounded">Inputs</span>
                                            )}
                                            {type.requires_photos && (
                                                <span className="px-2 py-1 bg-purple-100 text-purple-800 text-xs rounded">Photos</span>
                                            )}
                                        </div>
                                    </td>
                                    <td className="px-6 py-4 whitespace-nowrap">
                                        <span className={`px-2 py-1 text-xs font-semibold rounded-full ${
                                            type.is_active 
                                                ? 'bg-green-100 text-green-800' 
                                                : 'bg-gray-100 text-gray-800'
                                        }`}>
                                            {type.is_active ? 'Active' : 'Inactive'}
                                        </span>
                                    </td>
                                    <td className="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                        <div className="flex justify-end space-x-2">
                                            <button
                                                onClick={() => handleEdit(type)}
                                                className="text-blue-600 hover:text-blue-900"
                                                title="Edit"
                                            >
                                                <Edit className="h-4 w-4" />
                                            </button>
                                            <button
                                                onClick={() => handleDelete(type.id)}
                                                className="text-red-600 hover:text-red-900"
                                                title="Delete"
                                            >
                                                <Trash2 className="h-4 w-4" />
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                            ))
                        )}
                    </tbody>
                </table>
            </div>

            {/* Modal */}
            {showModal && (
                <div className="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50 p-4">
                    <div className="bg-white rounded-lg max-w-2xl w-full max-h-[90vh] overflow-y-auto">
                        <div className="sticky top-0 bg-white border-b border-gray-200 px-6 py-4 flex justify-between items-center">
                            <h2 className="text-xl font-bold text-gray-900">
                                {editingType ? 'Edit Activity Type' : 'New Activity Type'}
                            </h2>
                            <button
                                onClick={() => setShowModal(false)}
                                className="text-gray-400 hover:text-gray-600"
                            >
                                <X className="h-6 w-6" />
                            </button>
                        </div>
                        <form onSubmit={handleSubmit} className="p-6 space-y-4">
                            {!editingType && (
                                <div>
                                    <label className="block text-sm font-medium text-gray-700 mb-1">
                                        Farm <span className="text-red-500">*</span>
                                    </label>
                                    <select
                                        required
                                        value={formData.farm_id}
                                        onChange={(e) => setFormData({ ...formData, farm_id: e.target.value })}
                                        className="w-full px-3 py-2 border border-gray-300 rounded-lg"
                                    >
                                        <option value="">Select Farm</option>
                                        {farms.map((farm) => (
                                            <option key={farm.id} value={farm.id}>
                                                {farm.name}
                                            </option>
                                        ))}
                                    </select>
                                </div>
                            )}
                            <div className="grid grid-cols-2 gap-4">
                                <div>
                                    <label className="block text-sm font-medium text-gray-700 mb-1">
                                        Code <span className="text-red-500">*</span>
                                    </label>
                                    <input
                                        type="text"
                                        required
                                        value={formData.code}
                                        onChange={(e) => setFormData({ ...formData, code: e.target.value.toUpperCase() })}
                                        className="w-full px-3 py-2 border border-gray-300 rounded-lg"
                                        placeholder="IRRIGATION"
                                    />
                                </div>
                                <div>
                                    <label className="block text-sm font-medium text-gray-700 mb-1">
                                        Name <span className="text-red-500">*</span>
                                    </label>
                                    <input
                                        type="text"
                                        required
                                        value={formData.name}
                                        onChange={(e) => setFormData({ ...formData, name: e.target.value })}
                                        className="w-full px-3 py-2 border border-gray-300 rounded-lg"
                                    />
                                </div>
                            </div>
                            <div>
                                <label className="block text-sm font-medium text-gray-700 mb-1">
                                    Description
                                </label>
                                <textarea
                                    value={formData.description}
                                    onChange={(e) => setFormData({ ...formData, description: e.target.value })}
                                    rows="3"
                                    className="w-full px-3 py-2 border border-gray-300 rounded-lg"
                                />
                            </div>
                            <div>
                                <label className="block text-sm font-medium text-gray-700 mb-1">
                                    Category
                                </label>
                                <select
                                    value={formData.category}
                                    onChange={(e) => setFormData({ ...formData, category: e.target.value })}
                                    className="w-full px-3 py-2 border border-gray-300 rounded-lg"
                                >
                                    <option value="">Select Category</option>
                                    <option value="CULTURAL">Cultural</option>
                                    <option value="WATER">Water</option>
                                    <option value="NUTRITION">Nutrition</option>
                                    <option value="PROTECTION">Protection</option>
                                    <option value="HYGIENE">Hygiene</option>
                                    <option value="MAINTENANCE">Maintenance</option>
                                </select>
                            </div>
                            <div>
                                <label className="block text-sm font-medium text-gray-700 mb-3">
                                    Requirements
                                </label>
                                <div className="space-y-2">
                                    <label className="flex items-center">
                                        <input
                                            type="checkbox"
                                            checked={formData.requires_quantity}
                                            onChange={(e) => setFormData({ ...formData, requires_quantity: e.target.checked })}
                                            className="mr-2"
                                        />
                                        <span className="text-sm text-gray-700">Requires Quantity</span>
                                    </label>
                                    <label className="flex items-center">
                                        <input
                                            type="checkbox"
                                            checked={formData.requires_time_range}
                                            onChange={(e) => setFormData({ ...formData, requires_time_range: e.target.checked })}
                                            className="mr-2"
                                        />
                                        <span className="text-sm text-gray-700">Requires Time Range</span>
                                    </label>
                                    <label className="flex items-center">
                                        <input
                                            type="checkbox"
                                            checked={formData.requires_inputs}
                                            onChange={(e) => setFormData({ ...formData, requires_inputs: e.target.checked })}
                                            className="mr-2"
                                        />
                                        <span className="text-sm text-gray-700">Requires Inputs</span>
                                    </label>
                                    <label className="flex items-center">
                                        <input
                                            type="checkbox"
                                            checked={formData.requires_photos}
                                            onChange={(e) => setFormData({ ...formData, requires_photos: e.target.checked })}
                                            className="mr-2"
                                        />
                                        <span className="text-sm text-gray-700">Requires Photos</span>
                                    </label>
                                </div>
                            </div>
                            <div>
                                <label className="flex items-center">
                                    <input
                                        type="checkbox"
                                        checked={formData.is_active}
                                        onChange={(e) => setFormData({ ...formData, is_active: e.target.checked })}
                                        className="mr-2"
                                    />
                                    <span className="text-sm text-gray-700">Active</span>
                                </label>
                            </div>
                            <div className="flex justify-end space-x-4 pt-4 border-t">
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
                                    {editingType ? 'Update' : 'Create'} Activity Type
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            )}
        </div>
    );
}
