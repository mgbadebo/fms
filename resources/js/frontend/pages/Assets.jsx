import React, { useEffect, useState } from 'react';
import { useNavigate } from 'react-router-dom';
import api from '../utils/api';
import { Wrench, Plus, Edit, Trash2, Eye } from 'lucide-react';

export default function Assets() {
    const navigate = useNavigate();
    const [assets, setAssets] = useState([]);
    const [categories, setCategories] = useState([]);
    const [farms, setFarms] = useState([]);
    const [loading, setLoading] = useState(true);
    const [showModal, setShowModal] = useState(false);
    const [editingAsset, setEditingAsset] = useState(null);
    const [formData, setFormData] = useState({
        farm_id: '',
        asset_category_id: '',
        asset_code: '',
        name: '',
        description: '',
        status: 'ACTIVE',
        acquisition_type: 'PURCHASED',
        purchase_date: '',
        purchase_cost: '',
        currency: 'NGN',
        serial_number: '',
        is_trackable: true,
    });

    useEffect(() => {
        fetchData();
        fetchCategories();
        fetchFarms();
    }, []);

    const fetchData = async () => {
        setLoading(true);
        try {
            const response = await api.get('/api/v1/assets?per_page=1000');
            const data = response.data?.data || (Array.isArray(response.data) ? response.data : []);
            setAssets(Array.isArray(data) ? data : []);
        } catch (error) {
            console.error('Error fetching assets:', error);
            setAssets([]);
        } finally {
            setLoading(false);
        }
    };

    const fetchCategories = async () => {
        try {
            const response = await api.get('/api/v1/asset-categories?per_page=1000');
            const data = response.data?.data || (Array.isArray(response.data) ? response.data : []);
            setCategories(Array.isArray(data) ? data : []);
        } catch (error) {
            console.error('Error fetching categories:', error);
            setCategories([]);
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

    const handleModalOpen = (asset = null) => {
        setEditingAsset(asset);
        setFormData({
            farm_id: asset?.farm_id || '',
            asset_category_id: asset?.asset_category_id || '',
            asset_code: asset?.asset_code || '',
            name: asset?.name || '',
            description: asset?.description || '',
            status: asset?.status || 'ACTIVE',
            acquisition_type: asset?.acquisition_type || 'PURCHASED',
            purchase_date: asset?.purchase_date || '',
            purchase_cost: asset?.purchase_cost || '',
            currency: asset?.currency || 'NGN',
            serial_number: asset?.serial_number || '',
            is_trackable: asset?.is_trackable !== undefined ? asset?.is_trackable : true,
        });
        setShowModal(true);
    };

    const handleSubmit = async (e) => {
        e.preventDefault();
        try {
            if (editingAsset) {
                await api.put(`/api/v1/assets/${editingAsset.id}`, formData);
            } else {
                await api.post('/api/v1/assets', formData);
            }
            setShowModal(false);
            fetchData();
        } catch (error) {
            console.error('Error saving asset:', error);
            alert(error.response?.data?.message || 'Error saving asset');
        }
    };

    const handleDelete = async (id) => {
        if (!confirm('Are you sure you want to delete this asset?')) return;
        try {
            await api.delete(`/api/v1/assets/${id}`);
            fetchData();
        } catch (error) {
            console.error('Error deleting asset:', error);
            alert(error.response?.data?.message || 'Error deleting asset');
        }
    };

    const getStatusColor = (status) => {
        const colors = {
            ACTIVE: 'bg-green-100 text-green-800',
            INACTIVE: 'bg-gray-100 text-gray-800',
            UNDER_REPAIR: 'bg-yellow-100 text-yellow-800',
            DISPOSED: 'bg-red-100 text-red-800',
            SOLD: 'bg-blue-100 text-blue-800',
            LOST: 'bg-red-100 text-red-800',
        };
        return colors[status] || 'bg-gray-100 text-gray-800';
    };

    if (loading) {
        return <div className="p-6">Loading...</div>;
    }

    return (
        <div className="p-6">
            <div className="flex justify-between items-center mb-6">
                <h1 className="text-2xl font-bold">Assets</h1>
                <button
                    onClick={() => handleModalOpen()}
                    className="bg-blue-600 text-white px-4 py-2 rounded-lg hover:bg-blue-700 flex items-center gap-2"
                >
                    <Plus size={20} />
                    Add Asset
                </button>
            </div>

            <div className="bg-white rounded-lg shadow overflow-hidden">
                <table className="min-w-full divide-y divide-gray-200">
                    <thead className="bg-gray-50">
                        <tr>
                            <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Code</th>
                            <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Name</th>
                            <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Category</th>
                            <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                            <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Farm</th>
                            <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                        </tr>
                    </thead>
                    <tbody className="bg-white divide-y divide-gray-200">
                        {assets.length === 0 ? (
                            <tr>
                                <td colSpan="6" className="px-6 py-4 text-center text-gray-500">No assets found</td>
                            </tr>
                        ) : (
                            assets.map((asset) => (
                                <tr key={asset.id}>
                                    <td className="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">{asset.asset_code}</td>
                                    <td className="px-6 py-4 whitespace-nowrap text-sm text-gray-900">{asset.name}</td>
                                    <td className="px-6 py-4 text-sm text-gray-500">{asset.category?.name || '-'}</td>
                                    <td className="px-6 py-4 whitespace-nowrap">
                                        <span className={`px-2 py-1 text-xs rounded-full ${getStatusColor(asset.status)}`}>
                                            {asset.status}
                                        </span>
                                    </td>
                                    <td className="px-6 py-4 text-sm text-gray-500">{asset.farm?.name || '-'}</td>
                                    <td className="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                        <button
                                            onClick={() => navigate(`/admin/assets/${asset.id}`)}
                                            className="text-green-600 hover:text-green-900 mr-4"
                                            title="View Details"
                                        >
                                            <Eye size={16} />
                                        </button>
                                        <button
                                            onClick={() => handleModalOpen(asset)}
                                            className="text-blue-600 hover:text-blue-900 mr-4"
                                        >
                                            <Edit size={16} />
                                        </button>
                                        <button
                                            onClick={() => handleDelete(asset.id)}
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
                    <div className="bg-white rounded-lg p-6 w-full max-w-3xl max-h-[90vh] overflow-y-auto">
                        <h2 className="text-xl font-bold mb-4">{editingAsset ? 'Edit Asset' : 'Add Asset'}</h2>
                        <form onSubmit={handleSubmit}>
                            <div className="grid grid-cols-2 gap-4">
                                <div>
                                    <label className="block text-sm font-medium text-gray-700 mb-1">Farm *</label>
                                    <select
                                        required
                                        value={formData.farm_id}
                                        onChange={(e) => setFormData({ ...formData, farm_id: e.target.value })}
                                        className="w-full border border-gray-300 rounded-lg px-3 py-2"
                                        disabled={!!editingAsset}
                                    >
                                        <option value="">Select Farm</option>
                                        {farms.map((farm) => (
                                            <option key={farm.id} value={farm.id}>{farm.name}</option>
                                        ))}
                                    </select>
                                </div>
                                <div>
                                    <label className="block text-sm font-medium text-gray-700 mb-1">Category</label>
                                    <select
                                        value={formData.asset_category_id}
                                        onChange={(e) => setFormData({ ...formData, asset_category_id: e.target.value })}
                                        className="w-full border border-gray-300 rounded-lg px-3 py-2"
                                    >
                                        <option value="">Select Category</option>
                                        {categories
                                            .filter(c => c.farm_id === (formData.farm_id || editingAsset?.farm_id))
                                            .map((category) => (
                                                <option key={category.id} value={category.id}>{category.name}</option>
                                            ))}
                                    </select>
                                </div>
                                <div>
                                    <label className="block text-sm font-medium text-gray-700 mb-1">Asset Code</label>
                                    <input
                                        type="text"
                                        value={formData.asset_code}
                                        onChange={(e) => setFormData({ ...formData, asset_code: e.target.value })}
                                        className="w-full border border-gray-300 rounded-lg px-3 py-2"
                                        placeholder="Auto-generated if empty"
                                    />
                                </div>
                                <div>
                                    <label className="block text-sm font-medium text-gray-700 mb-1">Status *</label>
                                    <select
                                        required
                                        value={formData.status}
                                        onChange={(e) => setFormData({ ...formData, status: e.target.value })}
                                        className="w-full border border-gray-300 rounded-lg px-3 py-2"
                                    >
                                        <option value="ACTIVE">Active</option>
                                        <option value="INACTIVE">Inactive</option>
                                        <option value="UNDER_REPAIR">Under Repair</option>
                                        <option value="DISPOSED">Disposed</option>
                                        <option value="SOLD">Sold</option>
                                        <option value="LOST">Lost</option>
                                    </select>
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
                                <div className="col-span-2">
                                    <label className="block text-sm font-medium text-gray-700 mb-1">Description</label>
                                    <textarea
                                        value={formData.description}
                                        onChange={(e) => setFormData({ ...formData, description: e.target.value })}
                                        className="w-full border border-gray-300 rounded-lg px-3 py-2"
                                        rows="2"
                                    />
                                </div>
                                <div>
                                    <label className="block text-sm font-medium text-gray-700 mb-1">Acquisition Type</label>
                                    <select
                                        value={formData.acquisition_type}
                                        onChange={(e) => setFormData({ ...formData, acquisition_type: e.target.value })}
                                        className="w-full border border-gray-300 rounded-lg px-3 py-2"
                                    >
                                        <option value="PURCHASED">Purchased</option>
                                        <option value="LEASED">Leased</option>
                                        <option value="RENTED">Rented</option>
                                        <option value="DONATED">Donated</option>
                                    </select>
                                </div>
                                <div>
                                    <label className="block text-sm font-medium text-gray-700 mb-1">Purchase Date</label>
                                    <input
                                        type="date"
                                        value={formData.purchase_date}
                                        onChange={(e) => setFormData({ ...formData, purchase_date: e.target.value })}
                                        className="w-full border border-gray-300 rounded-lg px-3 py-2"
                                    />
                                </div>
                                <div>
                                    <label className="block text-sm font-medium text-gray-700 mb-1">Purchase Cost</label>
                                    <input
                                        type="number"
                                        step="0.01"
                                        value={formData.purchase_cost}
                                        onChange={(e) => setFormData({ ...formData, purchase_cost: e.target.value })}
                                        className="w-full border border-gray-300 rounded-lg px-3 py-2"
                                    />
                                </div>
                                <div>
                                    <label className="block text-sm font-medium text-gray-700 mb-1">Currency</label>
                                    <input
                                        type="text"
                                        value={formData.currency}
                                        onChange={(e) => setFormData({ ...formData, currency: e.target.value.toUpperCase() })}
                                        className="w-full border border-gray-300 rounded-lg px-3 py-2"
                                        maxLength="3"
                                    />
                                </div>
                                <div>
                                    <label className="block text-sm font-medium text-gray-700 mb-1">Serial Number</label>
                                    <input
                                        type="text"
                                        value={formData.serial_number}
                                        onChange={(e) => setFormData({ ...formData, serial_number: e.target.value })}
                                        className="w-full border border-gray-300 rounded-lg px-3 py-2"
                                    />
                                </div>
                                <div>
                                    <label className="flex items-center gap-2 mt-6">
                                        <input
                                            type="checkbox"
                                            checked={formData.is_trackable}
                                            onChange={(e) => setFormData({ ...formData, is_trackable: e.target.checked })}
                                        />
                                        <span className="text-sm font-medium text-gray-700">Trackable</span>
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
                                    {editingAsset ? 'Update' : 'Create'}
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            )}
        </div>
    );
}

