import React, { useEffect, useState } from 'react';
import api from '../utils/api';
import { FolderTree, Plus, Edit, Trash2 } from 'lucide-react';

export default function AssetCategories() {
    const [categories, setCategories] = useState([]);
    const [farms, setFarms] = useState([]);
    const [loading, setLoading] = useState(true);
    const [showModal, setShowModal] = useState(false);
    const [editingCategory, setEditingCategory] = useState(null);
    const [formData, setFormData] = useState({
        farm_id: '',
        code: '',
        name: '',
        parent_id: '',
        is_active: true,
    });

    useEffect(() => {
        fetchData();
        fetchFarms();
    }, []);

    const fetchData = async () => {
        setLoading(true);
        try {
            const response = await api.get('/api/v1/asset-categories?per_page=1000');
            const data = response.data?.data || (Array.isArray(response.data) ? response.data : []);
            setCategories(Array.isArray(data) ? data : []);
        } catch (error) {
            console.error('Error fetching categories:', error);
            setCategories([]);
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
            setFarms([]);
        }
    };

    const handleModalOpen = (category = null) => {
        setEditingCategory(category);
        setFormData({
            farm_id: category?.farm_id || '',
            code: category?.code || '',
            name: category?.name || '',
            parent_id: category?.parent_id || '',
            is_active: category?.is_active !== undefined ? category?.is_active : true,
        });
        setShowModal(true);
    };

    const handleSubmit = async (e) => {
        e.preventDefault();
        try {
            if (editingCategory) {
                await api.put(`/api/v1/asset-categories/${editingCategory.id}`, formData);
            } else {
                await api.post('/api/v1/asset-categories', formData);
            }
            setShowModal(false);
            fetchData();
        } catch (error) {
            console.error('Error saving category:', error);
            alert(error.response?.data?.message || 'Error saving category');
        }
    };

    const handleDelete = async (id) => {
        if (!confirm('Are you sure you want to delete this category?')) return;
        try {
            await api.delete(`/api/v1/asset-categories/${id}`);
            fetchData();
        } catch (error) {
            console.error('Error deleting category:', error);
            alert(error.response?.data?.message || 'Error deleting category');
        }
    };

    const getCategoryName = (parentId) => {
        if (!parentId) return '-';
        const parent = categories.find(c => c.id === parentId);
        return parent ? parent.name : '-';
    };

    if (loading) {
        return <div className="p-6">Loading...</div>;
    }

    return (
        <div className="p-6">
            <div className="flex justify-between items-center mb-6">
                <h1 className="text-2xl font-bold">Asset Categories</h1>
                <button
                    onClick={() => handleModalOpen()}
                    className="bg-blue-600 text-white px-4 py-2 rounded-lg hover:bg-blue-700 flex items-center gap-2"
                >
                    <Plus size={20} />
                    Add Category
                </button>
            </div>

            <div className="bg-white rounded-lg shadow overflow-hidden">
                <table className="min-w-full divide-y divide-gray-200">
                    <thead className="bg-gray-50">
                        <tr>
                            <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Code</th>
                            <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Name</th>
                            <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Farm</th>
                            <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Parent</th>
                            <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                            <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                        </tr>
                    </thead>
                    <tbody className="bg-white divide-y divide-gray-200">
                        {categories.length === 0 ? (
                            <tr>
                                <td colSpan="6" className="px-6 py-4 text-center text-gray-500">No categories found</td>
                            </tr>
                        ) : (
                            categories.map((category) => (
                                <tr key={category.id}>
                                    <td className="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">{category.code}</td>
                                    <td className="px-6 py-4 whitespace-nowrap text-sm text-gray-900">{category.name}</td>
                                    <td className="px-6 py-4 text-sm text-gray-500">{category.farm?.name || '-'}</td>
                                    <td className="px-6 py-4 text-sm text-gray-500">{getCategoryName(category.parent_id)}</td>
                                    <td className="px-6 py-4 whitespace-nowrap">
                                        <span className={`px-2 py-1 text-xs rounded-full ${category.is_active ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800'}`}>
                                            {category.is_active ? 'Active' : 'Inactive'}
                                        </span>
                                    </td>
                                    <td className="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                        <button
                                            onClick={() => handleModalOpen(category)}
                                            className="text-blue-600 hover:text-blue-900 mr-4"
                                        >
                                            <Edit size={16} />
                                        </button>
                                        <button
                                            onClick={() => handleDelete(category.id)}
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
                        <h2 className="text-xl font-bold mb-4">{editingCategory ? 'Edit Category' : 'Add Category'}</h2>
                        <form onSubmit={handleSubmit}>
                            <div className="grid grid-cols-2 gap-4">
                                <div>
                                    <label className="block text-sm font-medium text-gray-700 mb-1">Farm *</label>
                                    <select
                                        required
                                        value={formData.farm_id}
                                        onChange={(e) => setFormData({ ...formData, farm_id: e.target.value })}
                                        className="w-full border border-gray-300 rounded-lg px-3 py-2"
                                        disabled={!!editingCategory}
                                    >
                                        <option value="">Select Farm</option>
                                        {farms.map((farm) => (
                                            <option key={farm.id} value={farm.id}>{farm.name}</option>
                                        ))}
                                    </select>
                                </div>
                                <div>
                                    <label className="block text-sm font-medium text-gray-700 mb-1">Code *</label>
                                    <input
                                        type="text"
                                        required
                                        value={formData.code}
                                        onChange={(e) => setFormData({ ...formData, code: e.target.value.toUpperCase() })}
                                        className="w-full border border-gray-300 rounded-lg px-3 py-2"
                                    />
                                </div>
                                <div className="col-span-2">
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
                                    <label className="block text-sm font-medium text-gray-700 mb-1">Parent Category</label>
                                    <select
                                        value={formData.parent_id}
                                        onChange={(e) => setFormData({ ...formData, parent_id: e.target.value })}
                                        className="w-full border border-gray-300 rounded-lg px-3 py-2"
                                    >
                                        <option value="">None</option>
                                        {categories
                                            .filter(c => c.id !== editingCategory?.id && c.farm_id === (formData.farm_id || editingCategory?.farm_id))
                                            .map((category) => (
                                                <option key={category.id} value={category.id}>{category.name}</option>
                                            ))}
                                    </select>
                                </div>
                                <div>
                                    <label className="flex items-center gap-2 mt-6">
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
                                    {editingCategory ? 'Update' : 'Create'}
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            )}
        </div>
    );
}

