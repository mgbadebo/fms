import React, { useEffect, useState } from 'react';
import { Link } from 'react-router-dom';
import api from '../utils/api';
import { Tractor, Plus, Edit, Trash2, MapPin } from 'lucide-react';

const NIGERIAN_STATES = [
    'Abia', 'Adamawa', 'Akwa Ibom', 'Anambra', 'Bauchi', 'Bayelsa', 'Benue', 'Borno',
    'Cross River', 'Delta', 'Ebonyi', 'Edo', 'Ekiti', 'Enugu', 'FCT', 'Gombe',
    'Imo', 'Jigawa', 'Kaduna', 'Kano', 'Katsina', 'Kebbi', 'Kogi', 'Kwara',
    'Lagos', 'Nasarawa', 'Niger', 'Ogun', 'Ondo', 'Osun', 'Oyo', 'Plateau',
    'Rivers', 'Sokoto', 'Taraba', 'Yobe', 'Zamfara'
];

export default function Farms() {
    const [farms, setFarms] = useState([]);
    const [loading, setLoading] = useState(true);
    const [showModal, setShowModal] = useState(false);
    const [editingFarm, setEditingFarm] = useState(null);
    const [formData, setFormData] = useState({
        name: '',
        legal_name: '',
        farm_type: 'MIXED',
        country: 'Nigeria',
        state: 'Ondo',
        town: '',
        default_currency: 'NGN',
        default_unit_system: 'METRIC',
        default_timezone: 'Africa/Lagos',
        accounting_method: 'ACCRUAL',
        status: 'ACTIVE',
    });

    useEffect(() => {
        fetchFarms();
    }, []);

    const fetchFarms = async () => {
        setLoading(true);
        try {
            const response = await api.get('/api/v1/farms?per_page=1000');
            const data = response.data?.data || (Array.isArray(response.data) ? response.data : []);
            setFarms(Array.isArray(data) ? data : []);
        } catch (error) {
            console.error('Error fetching farms:', error);
            setFarms([]);
        } finally {
            setLoading(false);
        }
    };

    const handleModalOpen = async (farm = null) => {
        setEditingFarm(farm);
        if (farm) {
            // Fetch fresh farm data to ensure we have farm_code
            try {
                const response = await api.get(`/api/v1/farms/${farm.id}`);
                const freshFarm = response.data?.data || farm;
                setFormData({
                    name: freshFarm.name || '',
                    legal_name: freshFarm.legal_name || '',
                    farm_type: freshFarm.farm_type || 'MIXED',
                    country: freshFarm.country || 'Nigeria',
                    state: freshFarm.state || '',
                    town: freshFarm.town || '',
                    default_currency: freshFarm.default_currency || 'NGN',
                    default_unit_system: freshFarm.default_unit_system || 'METRIC',
                    default_timezone: freshFarm.default_timezone || 'Africa/Lagos',
                    accounting_method: freshFarm.accounting_method || 'ACCRUAL',
                    status: freshFarm.status || 'ACTIVE',
                });
            } catch (error) {
                console.error('Error fetching farm:', error);
                // Fallback to using provided farm data
                setFormData({
                    name: farm.name || '',
                    legal_name: farm.legal_name || '',
                    farm_type: farm.farm_type || 'MIXED',
                    country: farm.country || 'Nigeria',
                    state: farm.state || '',
                    town: farm.town || '',
                    default_currency: farm.default_currency || 'NGN',
                    default_unit_system: farm.default_unit_system || 'METRIC',
                    default_timezone: farm.default_timezone || 'Africa/Lagos',
                    accounting_method: farm.accounting_method || 'ACCRUAL',
                    status: farm.status || 'ACTIVE',
                });
            }
        } else {
            setFormData({
                name: '',
                legal_name: '',
                farm_type: 'MIXED',
                country: 'Nigeria',
                state: 'Ondo',
                town: '',
                default_currency: 'NGN',
                default_unit_system: 'METRIC',
                default_timezone: 'Africa/Lagos',
                accounting_method: 'ACCRUAL',
                status: 'ACTIVE',
            });
        }
        setShowModal(true);
    };

    const handleSubmit = async (e) => {
        e.preventDefault();
        try {
            if (editingFarm) {
                await api.put(`/api/v1/farms/${editingFarm.id}`, formData);
            } else {
                await api.post('/api/v1/farms', formData);
            }
            setShowModal(false);
            fetchFarms();
        } catch (error) {
            console.error('Error saving farm:', error);
            alert(error.response?.data?.message || 'Error saving farm');
        }
    };

    const handleDelete = async (id) => {
        if (!confirm('Are you sure you want to delete this farm?')) return;
        try {
            await api.delete(`/api/v1/farms/${id}`);
            fetchFarms();
        } catch (error) {
            console.error('Error deleting farm:', error);
            alert(error.response?.data?.message || 'Error deleting farm');
        }
    };

    const getFarmTypeLabel = (type) => {
        const types = {
            CROP: 'Crop',
            LIVESTOCK: 'Livestock',
            MIXED: 'Mixed',
            AQUACULTURE: 'Aquaculture',
            HORTICULTURE: 'Horticulture',
        };
        return types[type] || type;
    };

    const getStatusColor = (status) => {
        const colors = {
            ACTIVE: 'bg-green-100 text-green-800',
            INACTIVE: 'bg-gray-100 text-gray-800',
            ARCHIVED: 'bg-red-100 text-red-800',
        };
        return colors[status] || 'bg-gray-100 text-gray-800';
    };

    const getLocationLabel = (farm) => {
        const parts = [farm.town, farm.state, farm.country].filter(Boolean);
        return parts.length > 0 ? parts.join(', ') : 'Location not specified';
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
                <h1 className="text-2xl font-bold">Farms</h1>
                <button
                    onClick={() => handleModalOpen()}
                    className="bg-blue-600 text-white px-4 py-2 rounded-lg hover:bg-blue-700 flex items-center gap-2"
                >
                    <Plus size={20} />
                    Add Farm
                </button>
            </div>

            {farms.length === 0 ? (
                <div className="bg-white rounded-lg shadow p-12 text-center">
                    <Tractor className="h-16 w-16 mx-auto text-gray-400 mb-4" />
                    <h3 className="text-lg font-medium text-gray-900 mb-2">No farms yet</h3>
                    <p className="text-gray-500 mb-4">Get started by creating your first farm</p>
                    <button
                        onClick={() => handleModalOpen()}
                        className="bg-blue-600 text-white px-4 py-2 rounded-lg hover:bg-blue-700"
                    >
                        Create Farm
                    </button>
                </div>
            ) : (
                <div className="bg-white rounded-lg shadow overflow-hidden">
                    <table className="min-w-full divide-y divide-gray-200">
                        <thead className="bg-gray-50">
                            <tr>
                                <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Code</th>
                                <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Name</th>
                                <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Legal Name</th>
                                <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Type</th>
                                <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Location</th>
                                <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                                <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                            </tr>
                        </thead>
                        <tbody className="bg-white divide-y divide-gray-200">
                            {farms.map((farm) => (
                                <tr key={farm.id}>
                                    <td className="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">{farm.farm_code || '-'}</td>
                                    <td className="px-6 py-4 whitespace-nowrap text-sm text-gray-900">{farm.name}</td>
                                    <td className="px-6 py-4 text-sm text-gray-500">{farm.legal_name || '-'}</td>
                                    <td className="px-6 py-4 whitespace-nowrap text-sm text-gray-500">{getFarmTypeLabel(farm.farm_type)}</td>
                                    <td className="px-6 py-4 text-sm text-gray-500">
                                        <div className="flex items-center">
                                            <MapPin size={14} className="mr-1" />
                                            {getLocationLabel(farm)}
                                        </div>
                                    </td>
                                    <td className="px-6 py-4 whitespace-nowrap">
                                        <span className={`px-2 py-1 text-xs rounded-full ${getStatusColor(farm.status)}`}>
                                            {farm.status || 'ACTIVE'}
                                        </span>
                                    </td>
                                    <td className="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                        <Link
                                            to={`/farms/${farm.id}`}
                                            className="text-blue-600 hover:text-blue-900 mr-4"
                                            title="View Details"
                                        >
                                            View
                                        </Link>
                                        <button
                                            onClick={() => handleModalOpen(farm)}
                                            className="text-green-600 hover:text-green-900 mr-4"
                                        >
                                            <Edit size={16} className="inline" />
                                        </button>
                                        <button
                                            onClick={() => handleDelete(farm.id)}
                                            className="text-red-600 hover:text-red-900"
                                        >
                                            <Trash2 size={16} className="inline" />
                                        </button>
                                    </td>
                                </tr>
                            ))}
                        </tbody>
                    </table>
                </div>
            )}

            {showModal && (
                <div className="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50 p-4">
                    <div className="bg-white rounded-lg max-w-3xl w-full max-h-[90vh] overflow-y-auto p-6">
                        <h2 className="text-xl font-bold mb-4">{editingFarm ? 'Edit Farm' : 'Create New Farm'}</h2>
                        <form onSubmit={handleSubmit}>
                            <div className="grid grid-cols-2 gap-4">
                                <div className="col-span-2">
                                    <label className="block text-sm font-medium text-gray-700 mb-1">
                                        Farm Name *
                                    </label>
                                    <input
                                        type="text"
                                        required
                                        value={formData.name}
                                        onChange={(e) => setFormData({ ...formData, name: e.target.value })}
                                        className="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-blue-500 focus:border-blue-500"
                                    />
                                </div>
                                <div className="col-span-2">
                                    <label className="block text-sm font-medium text-gray-700 mb-1">
                                        Legal Name
                                    </label>
                                    <input
                                        type="text"
                                        value={formData.legal_name}
                                        onChange={(e) => setFormData({ ...formData, legal_name: e.target.value })}
                                        className="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-blue-500 focus:border-blue-500"
                                    />
                                </div>
                                <div>
                                    <label className="block text-sm font-medium text-gray-700 mb-1">
                                        Farm Type *
                                    </label>
                                    <select
                                        required
                                        value={formData.farm_type}
                                        onChange={(e) => setFormData({ ...formData, farm_type: e.target.value })}
                                        className="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-blue-500 focus:border-blue-500"
                                    >
                                        <option value="CROP">Crop</option>
                                        <option value="LIVESTOCK">Livestock</option>
                                        <option value="MIXED">Mixed</option>
                                        <option value="AQUACULTURE">Aquaculture</option>
                                        <option value="HORTICULTURE">Horticulture</option>
                                    </select>
                                </div>
                                <div>
                                    <label className="block text-sm font-medium text-gray-700 mb-1">
                                        Status *
                                    </label>
                                    <select
                                        required
                                        value={formData.status}
                                        onChange={(e) => setFormData({ ...formData, status: e.target.value })}
                                        className="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-blue-500 focus:border-blue-500"
                                    >
                                        <option value="ACTIVE">Active</option>
                                        <option value="INACTIVE">Inactive</option>
                                        <option value="ARCHIVED">Archived</option>
                                    </select>
                                </div>
                                <div>
                                    <label className="block text-sm font-medium text-gray-700 mb-1">
                                        Country *
                                    </label>
                                    <input
                                        type="text"
                                        required
                                        value={formData.country}
                                        onChange={(e) => setFormData({ ...formData, country: e.target.value })}
                                        className="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-blue-500 focus:border-blue-500"
                                    />
                                </div>
                                <div>
                                    <label className="block text-sm font-medium text-gray-700 mb-1">
                                        State *
                                    </label>
                                    <select
                                        required
                                        value={formData.state}
                                        onChange={(e) => setFormData({ ...formData, state: e.target.value })}
                                        className="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-blue-500 focus:border-blue-500"
                                    >
                                        {NIGERIAN_STATES.map((state) => (
                                            <option key={state} value={state}>
                                                {state}
                                            </option>
                                        ))}
                                    </select>
                                </div>
                                <div>
                                    <label className="block text-sm font-medium text-gray-700 mb-1">
                                        Town *
                                    </label>
                                    <input
                                        type="text"
                                        required
                                        value={formData.town}
                                        onChange={(e) => setFormData({ ...formData, town: e.target.value })}
                                        className="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-blue-500 focus:border-blue-500"
                                    />
                                </div>
                                <div>
                                    <label className="block text-sm font-medium text-gray-700 mb-1">
                                        Default Currency *
                                    </label>
                                    <input
                                        type="text"
                                        required
                                        maxLength="3"
                                        value={formData.default_currency}
                                        onChange={(e) => setFormData({ ...formData, default_currency: e.target.value.toUpperCase() })}
                                        className="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-blue-500 focus:border-blue-500"
                                    />
                                </div>
                                <div>
                                    <label className="block text-sm font-medium text-gray-700 mb-1">
                                        Default Unit System *
                                    </label>
                                    <select
                                        required
                                        value={formData.default_unit_system}
                                        onChange={(e) => setFormData({ ...formData, default_unit_system: e.target.value })}
                                        className="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-blue-500 focus:border-blue-500"
                                    >
                                        <option value="METRIC">Metric</option>
                                        <option value="IMPERIAL">Imperial</option>
                                    </select>
                                </div>
                                <div>
                                    <label className="block text-sm font-medium text-gray-700 mb-1">
                                        Default Timezone *
                                    </label>
                                    <input
                                        type="text"
                                        required
                                        value={formData.default_timezone}
                                        onChange={(e) => setFormData({ ...formData, default_timezone: e.target.value })}
                                        placeholder="Africa/Lagos"
                                        className="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-blue-500 focus:border-blue-500"
                                    />
                                </div>
                                <div>
                                    <label className="block text-sm font-medium text-gray-700 mb-1">
                                        Accounting Method *
                                    </label>
                                    <select
                                        required
                                        value={formData.accounting_method}
                                        onChange={(e) => setFormData({ ...formData, accounting_method: e.target.value })}
                                        className="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-blue-500 focus:border-blue-500"
                                    >
                                        <option value="CASH">Cash</option>
                                        <option value="ACCRUAL">Accrual</option>
                                    </select>
                                </div>
                            </div>
                            <div className="mt-6 flex justify-end space-x-3">
                                <button
                                    type="button"
                                    onClick={() => setShowModal(false)}
                                    className="px-4 py-2 border border-gray-300 rounded-lg text-gray-700 hover:bg-gray-50"
                                >
                                    Cancel
                                </button>
                                <button
                                    type="submit"
                                    className="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700"
                                >
                                    {editingFarm ? 'Update' : 'Create'} Farm
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            )}
        </div>
    );
}
