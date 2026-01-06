import React, { useEffect, useState } from 'react';
import api from '../utils/api';
import { Factory, Plus, Edit, Trash2, Search } from 'lucide-react';

export default function GreenhouseManagement() {
    const [greenhouses, setGreenhouses] = useState([]);
    const [sites, setSites] = useState([]);
    const [loading, setLoading] = useState(true);
    const [showModal, setShowModal] = useState(false);
    const [editingGreenhouse, setEditingGreenhouse] = useState(null);
    const [searchTerm, setSearchTerm] = useState('');
    const [filterStatus, setFilterStatus] = useState('');
    const [filterType, setFilterType] = useState('');
    const [formData, setFormData] = useState({
        site_id: '',
        name: '',
        type: 'POLYHOUSE',
        status: 'ACTIVE',
        length: '',
        width: '',
        height: '',
        orientation: '',
        plant_capacity: '',
        primary_crop_type: '',
        cropping_system: '',
        notes: '',
    });

    useEffect(() => {
        fetchData();
    }, [searchTerm, filterStatus, filterType]);

    const fetchData = async () => {
        try {
            const params = new URLSearchParams();
            if (searchTerm) params.append('search', searchTerm);
            if (filterStatus) params.append('status', filterStatus);
            if (filterType) params.append('type', filterType);
            
            const [greenhousesRes, sitesRes] = await Promise.all([
                api.get(`/api/v1/greenhouses?${params.toString()}`),
                api.get('/api/v1/sites?per_page=1000'),
            ]);
            
            const greenhousesData = greenhousesRes.data;
            const greenhousesArray = greenhousesData?.data || (Array.isArray(greenhousesData) ? greenhousesData : []);
            setGreenhouses(greenhousesArray);
            
            const sitesData = sitesRes.data;
            const sitesArray = sitesData?.data || (Array.isArray(sitesData) ? sitesData : []);
            setSites(sitesArray);
        } catch (error) {
            console.error('Error fetching data:', error);
            alert('Error loading data: ' + (error.response?.data?.message || error.message));
        } finally {
            setLoading(false);
        }
    };

    const handleModalOpen = () => {
        setShowModal(true);
        setEditingGreenhouse(null);
        setFormData({
            site_id: '',
            name: '',
            type: 'POLYHOUSE',
            status: 'ACTIVE',
            length: '',
            width: '',
            height: '',
            orientation: '',
            plant_capacity: '',
            primary_crop_type: '',
            cropping_system: '',
            notes: '',
        });
    };

    const handleEdit = (greenhouse) => {
        setEditingGreenhouse(greenhouse);
        setFormData({
            site_id: greenhouse.site?.id || '',
            name: greenhouse.name || '',
            type: greenhouse.type || 'POLYHOUSE',
            status: greenhouse.status || (greenhouse.is_active ? 'ACTIVE' : 'INACTIVE'),
            length: greenhouse.length || '',
            width: greenhouse.width || '',
            height: greenhouse.height || '',
            orientation: greenhouse.orientation || '',
            plant_capacity: greenhouse.plant_capacity || '',
            primary_crop_type: greenhouse.primary_crop_type || '',
            cropping_system: greenhouse.cropping_system || '',
            notes: greenhouse.notes || '',
        });
        setShowModal(true);
    };

    const handleSubmit = async (e) => {
        e.preventDefault();
        try {
            const payload = { ...formData };
            
            if (editingGreenhouse) {
                await api.patch(`/api/v1/greenhouses/${editingGreenhouse.id}`, payload);
            } else {
                await api.post('/api/v1/greenhouses', payload);
            }
            
            setShowModal(false);
            fetchData();
        } catch (error) {
            const errorMessage = error.response?.data?.message || 
                               (error.response?.data?.errors ? JSON.stringify(error.response.data.errors) : '') ||
                               error.message;
            alert('Error saving greenhouse: ' + errorMessage);
        }
    };

    const handleDelete = async (id) => {
        if (!confirm('Are you sure you want to delete this greenhouse?')) {
            return;
        }
        
        try {
            await api.delete(`/api/v1/greenhouses/${id}`);
            fetchData();
        } catch (error) {
            alert('Error deleting greenhouse: ' + (error.response?.data?.message || error.message));
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
                    <h1 className="text-3xl font-bold text-gray-900">Greenhouse Management</h1>
                    <p className="mt-2 text-gray-600">Manage greenhouses across all sites</p>
                </div>
                <button
                    onClick={handleModalOpen}
                    className="bg-green-600 text-white px-4 py-2 rounded-lg hover:bg-green-700 flex items-center"
                >
                    <Plus className="h-5 w-5 mr-2" />
                    New Greenhouse
                </button>
            </div>

            {/* Filters */}
            <div className="bg-white rounded-lg shadow p-4 mb-6">
                <div className="grid grid-cols-1 md:grid-cols-4 gap-4">
                    <div>
                        <label className="block text-sm font-medium text-gray-700 mb-1">Search</label>
                        <div className="relative">
                            <Search className="absolute left-3 top-1/2 transform -translate-y-1/2 h-4 w-4 text-gray-400" />
                            <input
                                type="text"
                                value={searchTerm}
                                onChange={(e) => setSearchTerm(e.target.value)}
                                placeholder="Search by name or code..."
                                className="w-full pl-10 pr-3 py-2 border border-gray-300 rounded-lg focus:ring-green-500 focus:border-green-500"
                            />
                        </div>
                    </div>
                    <div>
                        <label className="block text-sm font-medium text-gray-700 mb-1">Status</label>
                        <select
                            value={filterStatus}
                            onChange={(e) => setFilterStatus(e.target.value)}
                            className="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-green-500 focus:border-green-500"
                        >
                            <option value="">All Statuses</option>
                            <option value="ACTIVE">Active</option>
                            <option value="INACTIVE">Inactive</option>
                            <option value="MAINTENANCE">Maintenance</option>
                            <option value="DECOMMISSIONED">Decommissioned</option>
                        </select>
                    </div>
                    <div>
                        <label className="block text-sm font-medium text-gray-700 mb-1">Type</label>
                        <select
                            value={filterType}
                            onChange={(e) => setFilterType(e.target.value)}
                            className="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-green-500 focus:border-green-500"
                        >
                            <option value="">All Types</option>
                            <option value="TUNNEL">Tunnel</option>
                            <option value="GLASSHOUSE">Glasshouse</option>
                            <option value="POLYHOUSE">Polyhouse</option>
                            <option value="SHADE_HOUSE">Shade House</option>
                        </select>
                    </div>
                    <div className="flex items-end">
                        <button
                            onClick={() => {
                                setSearchTerm('');
                                setFilterStatus('');
                                setFilterType('');
                            }}
                            className="w-full px-4 py-2 border border-gray-300 rounded-lg hover:bg-gray-50"
                        >
                            Clear Filters
                        </button>
                    </div>
                </div>
            </div>

            {/* Table */}
            {greenhouses.length === 0 ? (
                <div className="bg-white rounded-lg shadow p-12 text-center">
                    <Factory className="h-16 w-16 mx-auto text-gray-400 mb-4" />
                    <h3 className="text-lg font-medium text-gray-900 mb-2">No greenhouses found</h3>
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
                    <table className="min-w-full divide-y divide-gray-200">
                        <thead className="bg-gray-50">
                            <tr>
                                <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Code</th>
                                <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Name</th>
                                <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Site</th>
                                <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Farm</th>
                                <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Type</th>
                                <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                                <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Area (m²)</th>
                                <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                            </tr>
                        </thead>
                        <tbody className="bg-white divide-y divide-gray-200">
                            {greenhouses.map((greenhouse) => (
                                <tr key={greenhouse.id}>
                                    <td className="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
                                        {greenhouse.greenhouse_code || greenhouse.code}
                                    </td>
                                    <td className="px-6 py-4 whitespace-nowrap text-sm text-gray-900">{greenhouse.name}</td>
                                    <td className="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                        {greenhouse.site?.name || 'N/A'}
                                    </td>
                                    <td className="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                        {greenhouse.farm?.name || 'N/A'}
                                    </td>
                                    <td className="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                        {greenhouse.type || 'N/A'}
                                    </td>
                                    <td className="px-6 py-4 whitespace-nowrap">
                                        <span className={`px-2 py-1 text-xs font-medium rounded ${
                                            greenhouse.status === 'ACTIVE' ? 'bg-green-100 text-green-800' :
                                            greenhouse.status === 'MAINTENANCE' ? 'bg-yellow-100 text-yellow-800' :
                                            greenhouse.status === 'DECOMMISSIONED' ? 'bg-red-100 text-red-800' :
                                            'bg-gray-100 text-gray-800'
                                        }`}>
                                            {greenhouse.status || (greenhouse.is_active ? 'ACTIVE' : 'INACTIVE')}
                                        </span>
                                    </td>
                                    <td className="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                        {greenhouse.total_area ? Number(greenhouse.total_area).toFixed(2) : 
                                         greenhouse.size_sqm ? Number(greenhouse.size_sqm).toFixed(2) : 'N/A'}
                                    </td>
                                    <td className="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                        <button
                                            onClick={() => handleEdit(greenhouse)}
                                            className="text-green-600 hover:text-green-900 mr-4"
                                        >
                                            <Edit className="h-4 w-4" />
                                        </button>
                                        <button
                                            onClick={() => handleDelete(greenhouse.id)}
                                            className="text-red-600 hover:text-red-900"
                                        >
                                            <Trash2 className="h-4 w-4" />
                                        </button>
                                    </td>
                                </tr>
                            ))}
                        </tbody>
                    </table>
                </div>
            )}

            {/* Create/Edit Modal */}
            {showModal && (
                <div className="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50 p-4 overflow-y-auto">
                    <div className="bg-white rounded-lg max-w-4xl w-full p-6 max-h-[90vh] overflow-y-auto">
                        <h2 className="text-xl font-bold mb-4">
                            {editingGreenhouse ? 'Edit Greenhouse' : 'Create Greenhouse'}
                        </h2>
                        <form onSubmit={handleSubmit} className="space-y-4">
                            <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
                                <div>
                                    <label className="block text-sm font-medium text-gray-700 mb-1">Site *</label>
                                    <select
                                        required
                                        value={formData.site_id}
                                        onChange={(e) => setFormData({ ...formData, site_id: e.target.value })}
                                        className="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-green-500 focus:border-green-500"
                                    >
                                        <option value="">Select site</option>
                                        {sites.map((site) => (
                                            <option key={site.id} value={site.id}>
                                                {site.name} ({site.farm?.name || 'No Farm'})
                                            </option>
                                        ))}
                                    </select>
                                    <p className="text-xs text-gray-500 mt-1">Farm will be automatically set from the selected site. Greenhouse code will be auto-generated.</p>
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
                                    <label className="block text-sm font-medium text-gray-700 mb-1">Type *</label>
                                    <select
                                        required
                                        value={formData.type}
                                        onChange={(e) => setFormData({ ...formData, type: e.target.value })}
                                        className="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-green-500 focus:border-green-500"
                                    >
                                        <option value="TUNNEL">Tunnel</option>
                                        <option value="GLASSHOUSE">Glasshouse</option>
                                        <option value="POLYHOUSE">Polyhouse</option>
                                        <option value="SHADE_HOUSE">Shade House</option>
                                    </select>
                                </div>
                                <div>
                                    <label className="block text-sm font-medium text-gray-700 mb-1">Status *</label>
                                    <select
                                        required
                                        value={formData.status}
                                        onChange={(e) => setFormData({ ...formData, status: e.target.value })}
                                        className="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-green-500 focus:border-green-500"
                                    >
                                        <option value="ACTIVE">Active</option>
                                        <option value="INACTIVE">Inactive</option>
                                        <option value="MAINTENANCE">Maintenance</option>
                                        <option value="DECOMMISSIONED">Decommissioned</option>
                                    </select>
                                </div>
                                <div>
                                    <label className="block text-sm font-medium text-gray-700 mb-1">Length (m) *</label>
                                    <input
                                        type="number"
                                        step="0.01"
                                        required
                                        value={formData.length}
                                        onChange={(e) => setFormData({ ...formData, length: e.target.value })}
                                        className="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-green-500 focus:border-green-500"
                                    />
                                </div>
                                <div>
                                    <label className="block text-sm font-medium text-gray-700 mb-1">Width (m) *</label>
                                    <input
                                        type="number"
                                        step="0.01"
                                        required
                                        value={formData.width}
                                        onChange={(e) => setFormData({ ...formData, width: e.target.value })}
                                        className="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-green-500 focus:border-green-500"
                                    />
                                </div>
                                <div>
                                    <label className="block text-sm font-medium text-gray-700 mb-1">Height (m)</label>
                                    <input
                                        type="number"
                                        step="0.01"
                                        value={formData.height}
                                        onChange={(e) => setFormData({ ...formData, height: e.target.value })}
                                        className="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-green-500 focus:border-green-500"
                                    />
                                </div>
                                <div>
                                    <label className="block text-sm font-medium text-gray-700 mb-1">Orientation</label>
                                    <select
                                        value={formData.orientation}
                                        onChange={(e) => setFormData({ ...formData, orientation: e.target.value })}
                                        className="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-green-500 focus:border-green-500"
                                    >
                                        <option value="">Select orientation</option>
                                        <option value="N_S">North-South</option>
                                        <option value="E_W">East-West</option>
                                        <option value="NE_SW">Northeast-Southwest</option>
                                        <option value="NW_SE">Northwest-Southeast</option>
                                    </select>
                                </div>
                                <div>
                                    <label className="block text-sm font-medium text-gray-700 mb-1">Plant Capacity</label>
                                    <input
                                        type="number"
                                        value={formData.plant_capacity}
                                        onChange={(e) => setFormData({ ...formData, plant_capacity: e.target.value })}
                                        className="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-green-500 focus:border-green-500"
                                    />
                                </div>
                                <div>
                                    <label className="block text-sm font-medium text-gray-700 mb-1">Primary Crop Type</label>
                                    <input
                                        type="text"
                                        value={formData.primary_crop_type}
                                        onChange={(e) => setFormData({ ...formData, primary_crop_type: e.target.value })}
                                        className="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-green-500 focus:border-green-500"
                                        placeholder="e.g., Bell Pepper"
                                    />
                                </div>
                                <div>
                                    <label className="block text-sm font-medium text-gray-700 mb-1">Cropping System</label>
                                    <select
                                        value={formData.cropping_system}
                                        onChange={(e) => setFormData({ ...formData, cropping_system: e.target.value })}
                                        className="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-green-500 focus:border-green-500"
                                    >
                                        <option value="">Select system</option>
                                        <option value="SOIL">Soil</option>
                                        <option value="COCOPEAT">Cocopeat</option>
                                        <option value="HYDROPONIC">Hydroponic</option>
                                    </select>
                                </div>
                            </div>
                            <div>
                                <label className="block text-sm font-medium text-gray-700 mb-1">Notes</label>
                                <textarea
                                    value={formData.notes}
                                    onChange={(e) => setFormData({ ...formData, notes: e.target.value })}
                                    rows="3"
                                    className="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-green-500 focus:border-green-500"
                                />
                            </div>
                            <div className="flex justify-end space-x-4">
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
                                    {editingGreenhouse ? 'Update' : 'Create'}
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            )}
        </div>
    );
}

