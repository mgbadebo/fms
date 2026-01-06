import React, { useEffect, useState } from 'react';
import api from '../utils/api';
import { Sprout, Plus, Edit, Trash2 } from 'lucide-react';

export default function Crops() {
    const [crops, setCrops] = useState([]);
    const [loading, setLoading] = useState(true);
    const [showModal, setShowModal] = useState(false);
    const [editingCrop, setEditingCrop] = useState(null);
    const [formData, setFormData] = useState({
        name: '',
        category: '',
        default_maturity_days: '',
        description: '',
    });

    const categories = [
        'VEGETABLE',
        'FRUIT',
        'GRAIN',
        'LEGUME',
        'ROOT',
        'HERB',
        'SPICE',
        'OTHER',
    ];

    useEffect(() => {
        fetchData();
    }, []);

    const fetchData = async () => {
        setLoading(true);
        try {
            const response = await api.get('/api/v1/crops?per_page=1000');
            const data = response.data?.data || (Array.isArray(response.data) ? response.data : []);
            setCrops(Array.isArray(data) ? data : []);
        } catch (error) {
            console.error('Error fetching crops:', error);
            setCrops([]);
        } finally {
            setLoading(false);
        }
    };

    const handleModalOpen = () => {
        setEditingCrop(null);
        setFormData({
            name: '',
            category: '',
            default_maturity_days: '',
            description: '',
        });
        setShowModal(true);
    };

    const handleEdit = (crop) => {
        setEditingCrop(crop);
        setFormData({
            name: crop.name || '',
            category: crop.category || '',
            default_maturity_days: crop.default_maturity_days || '',
            description: crop.description || '',
        });
        setShowModal(true);
    };

    const handleSubmit = async (e) => {
        e.preventDefault();
        try {
            if (editingCrop) {
                await api.put(`/api/v1/crops/${editingCrop.id}`, formData);
            } else {
                await api.post('/api/v1/crops', formData);
            }
            setShowModal(false);
            fetchData();
        } catch (error) {
            console.error('Error saving crop:', error);
            alert(error.response?.data?.message || 'Error saving crop');
        }
    };

    const handleDelete = async (id) => {
        if (!confirm('Are you sure you want to delete this crop? This action cannot be undone.')) return;
        try {
            await api.delete(`/api/v1/crops/${id}`);
            fetchData();
        } catch (error) {
            console.error('Error deleting crop:', error);
            alert(error.response?.data?.message || 'Error deleting crop');
        }
    };

    if (loading) {
        return <div className="p-6">Loading...</div>;
    }

    return (
        <div className="p-6">
            <div className="flex justify-between items-center mb-6">
                <h1 className="text-2xl font-bold">Crops</h1>
                <button
                    onClick={handleModalOpen}
                    className="bg-blue-600 text-white px-4 py-2 rounded-lg hover:bg-blue-700 flex items-center gap-2"
                >
                    <Plus size={20} />
                    Create Crop
                </button>
            </div>

            <div className="bg-white rounded-lg shadow overflow-hidden">
                <table className="min-w-full divide-y divide-gray-200">
                    <thead className="bg-gray-50">
                        <tr>
                            <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Name</th>
                            <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Category</th>
                            <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Default Maturity Days</th>
                            <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Description</th>
                            <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                        </tr>
                    </thead>
                    <tbody className="bg-white divide-y divide-gray-200">
                        {crops.length === 0 ? (
                            <tr>
                                <td colSpan="5" className="px-6 py-4 text-center text-gray-500">No crops found</td>
                            </tr>
                        ) : (
                            crops.map((crop) => (
                                <tr key={crop.id}>
                                    <td className="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">{crop.name}</td>
                                    <td className="px-6 py-4 whitespace-nowrap text-sm text-gray-500">{crop.category || '-'}</td>
                                    <td className="px-6 py-4 whitespace-nowrap text-sm text-gray-500">{crop.default_maturity_days || '-'}</td>
                                    <td className="px-6 py-4 text-sm text-gray-500">{crop.description || '-'}</td>
                                    <td className="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                        <button
                                            onClick={() => handleEdit(crop)}
                                            className="text-blue-600 hover:text-blue-900 mr-4"
                                        >
                                            <Edit size={16} />
                                        </button>
                                        <button
                                            onClick={() => handleDelete(crop.id)}
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
                        <h2 className="text-xl font-bold mb-4">{editingCrop ? 'Edit Crop' : 'Create Crop'}</h2>
                        <form onSubmit={handleSubmit}>
                            <div className="grid grid-cols-2 gap-4">
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
                                    <label className="block text-sm font-medium text-gray-700 mb-1">Category *</label>
                                    <select
                                        required
                                        value={formData.category}
                                        onChange={(e) => setFormData({ ...formData, category: e.target.value })}
                                        className="w-full border border-gray-300 rounded-lg px-3 py-2"
                                    >
                                        <option value="">Select Category</option>
                                        {categories.map((cat) => (
                                            <option key={cat} value={cat}>{cat}</option>
                                        ))}
                                    </select>
                                </div>
                                <div>
                                    <label className="block text-sm font-medium text-gray-700 mb-1">Default Maturity Days</label>
                                    <input
                                        type="number"
                                        min="1"
                                        value={formData.default_maturity_days}
                                        onChange={(e) => setFormData({ ...formData, default_maturity_days: e.target.value || null })}
                                        className="w-full border border-gray-300 rounded-lg px-3 py-2"
                                        placeholder="Days to maturity"
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
                                    {editingCrop ? 'Update' : 'Create'}
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            )}
        </div>
    );
}

