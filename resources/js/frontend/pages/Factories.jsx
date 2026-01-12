import React, { useEffect, useState } from 'react';
import api from '../utils/api';
import { Building2, Plus, Edit, Trash2 } from 'lucide-react';

export default function Factories() {
    const [factories, setFactories] = useState([]);
    const [sites, setSites] = useState([]);
    const [assetCategories, setAssetCategories] = useState([]);
    const [loading, setLoading] = useState(true);
    const [showModal, setShowModal] = useState(false);
    const [editingFactory, setEditingFactory] = useState(null);
    const [trackAsAsset, setTrackAsAsset] = useState(false);
    const [formData, setFormData] = useState({
        site_id: '',
        name: '',
        code: '',
        production_type: 'gari',
        description: '',
        area_sqm: '',
        established_date: '',
        is_active: true,
        // Asset fields
        track_as_asset: false,
        asset_category_id: '',
        asset_description: '',
        asset_acquisition_type: '',
        asset_purchase_date: '',
        asset_purchase_cost: '',
        asset_currency: 'NGN',
        asset_supplier_name: '',
        asset_serial_number: '',
        asset_model: '',
        asset_manufacturer: '',
        asset_year_of_make: '',
        asset_warranty_expiry: '',
        asset_is_trackable: false,
    });

    useEffect(() => {
        fetchData();
        fetchSites();
        fetchAssetCategories();
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

    const fetchAssetCategories = async () => {
        try {
            const response = await api.get('/api/v1/asset-categories?per_page=1000');
            let categoriesArray = [];
            if (response.data) {
                if (Array.isArray(response.data)) {
                    categoriesArray = response.data;
                } else if (response.data.data && Array.isArray(response.data.data)) {
                    categoriesArray = response.data.data;
                }
            }
            setAssetCategories(categoriesArray);
        } catch (error) {
            console.error('Error fetching asset categories:', error);
        }
    };

    const handleModalOpen = () => {
        setEditingFactory(null);
        setTrackAsAsset(false);
        setFormData({
            site_id: '',
            name: '',
            code: '',
            production_type: 'gari',
            description: '',
            area_sqm: '',
            established_date: '',
            is_active: true,
            track_as_asset: false,
            asset_category_id: '',
            asset_description: '',
            asset_acquisition_type: '',
            asset_purchase_date: '',
            asset_purchase_cost: '',
            asset_currency: 'NGN',
            asset_supplier_name: '',
            asset_serial_number: '',
            asset_model: '',
            asset_manufacturer: '',
            asset_year_of_make: '',
            asset_warranty_expiry: '',
            asset_is_trackable: false,
        });
        setShowModal(true);
    };

    const handleEdit = (factory) => {
        setEditingFactory(factory);
        const hasAsset = !!factory.asset_id;
        setTrackAsAsset(hasAsset);
        // Ensure asset categories are loaded if not already loaded
        if (assetCategories.length === 0) {
            fetchAssetCategories();
        }
        setFormData({
            site_id: factory.site_id || '',
            name: factory.name || '',
            code: factory.code || '',
            production_type: factory.production_type || 'gari',
            description: factory.description || '',
            area_sqm: factory.area_sqm || '',
            established_date: factory.established_date || '',
            is_active: factory.is_active !== undefined ? factory.is_active : true,
            track_as_asset: hasAsset,
            asset_category_id: factory.asset?.asset_category_id || '',
            asset_description: factory.asset?.description || '',
            asset_acquisition_type: factory.asset?.acquisition_type || '',
            asset_purchase_date: factory.asset?.purchase_date ? new Date(factory.asset.purchase_date).toISOString().slice(0, 10) : '',
            asset_purchase_cost: factory.asset?.purchase_cost || '',
            asset_currency: factory.asset?.currency || 'NGN',
            asset_supplier_name: factory.asset?.supplier_name || '',
            asset_serial_number: factory.asset?.serial_number || '',
            asset_model: factory.asset?.model || '',
            asset_manufacturer: factory.asset?.manufacturer || '',
            asset_year_of_make: factory.asset?.year_of_make || '',
            asset_warranty_expiry: factory.asset?.warranty_expiry ? new Date(factory.asset.warranty_expiry).toISOString().slice(0, 10) : '',
            asset_is_trackable: factory.asset?.is_trackable || false,
        });
        setShowModal(true);
    };

    const handleSubmit = async (e) => {
        e.preventDefault();
        try {
            // Prepare data to send
            const payload = { ...formData };
            payload.track_as_asset = trackAsAsset;
            
            // Only include asset fields if track_as_asset is checked
            if (!trackAsAsset) {
                // Remove all asset fields if not tracking as asset
                Object.keys(payload).forEach(key => {
                    if (key.startsWith('asset_')) {
                        delete payload[key];
                    }
                });
            }
            
            if (editingFactory) {
                await api.put(`/api/v1/factories/${editingFactory.id}`, payload);
            } else {
                await api.post('/api/v1/factories', payload);
            }
            setShowModal(false);
            fetchData();
        } catch (error) {
            console.error('Error saving factory:', error);
            const errorMessage = error.response?.data?.message || 
                               (error.response?.data?.errors ? JSON.stringify(error.response.data.errors) : '') ||
                               error.message;
            alert('Error saving factory: ' + errorMessage);
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
                <div className="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50 p-4 overflow-y-auto">
                    <div className="bg-white rounded-lg p-6 w-full max-w-4xl max-h-[90vh] overflow-y-auto">
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
                            
                            {/* Track as Asset Checkbox */}
                            <div className="mt-4 pt-4 border-t">
                                <label className="flex items-center text-sm font-medium text-gray-700 mb-1">
                                    <input
                                        type="checkbox"
                                        checked={trackAsAsset}
                                        onChange={(e) => {
                                            setTrackAsAsset(e.target.checked);
                                            setFormData({ ...formData, track_as_asset: e.target.checked });
                                        }}
                                        className="rounded border-gray-300 text-green-600 focus:ring-green-500 mr-2"
                                    />
                                    <span>Track as Asset</span>
                                </label>
                                <p className="text-xs text-gray-500 mt-1">
                                    Check this box to create an asset record for this factory. You can enter asset details below.
                                </p>
                            </div>
                            
                            {/* Asset Fields - Conditional */}
                            {trackAsAsset && (
                                <>
                                    <div className="mt-4 pt-4 border-t">
                                        <h3 className="text-lg font-semibold text-gray-900 mb-4">Asset Information</h3>
                                    </div>
                                    <div className="grid grid-cols-2 gap-4">
                                        <div>
                                            <label className="block text-sm font-medium text-gray-700 mb-1">Asset Category</label>
                                            <select
                                                value={formData.asset_category_id ? String(formData.asset_category_id) : ''}
                                                onChange={(e) => setFormData({ ...formData, asset_category_id: e.target.value })}
                                                className="w-full border border-gray-300 rounded-lg px-3 py-2"
                                            >
                                                <option value="">Select category</option>
                                                {assetCategories.map((cat) => (
                                                    <option key={cat.id} value={String(cat.id)}>{cat.name}</option>
                                                ))}
                                            </select>
                                        </div>
                                        <div>
                                            <label className="block text-sm font-medium text-gray-700 mb-1">Acquisition Type</label>
                                            <select
                                                value={formData.asset_acquisition_type}
                                                onChange={(e) => setFormData({ ...formData, asset_acquisition_type: e.target.value })}
                                                className="w-full border border-gray-300 rounded-lg px-3 py-2"
                                            >
                                                <option value="">Select type</option>
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
                                                value={formData.asset_purchase_date}
                                                onChange={(e) => setFormData({ ...formData, asset_purchase_date: e.target.value })}
                                                className="w-full border border-gray-300 rounded-lg px-3 py-2"
                                            />
                                        </div>
                                        <div>
                                            <label className="block text-sm font-medium text-gray-700 mb-1">Purchase Cost</label>
                                            <input
                                                type="number"
                                                step="0.01"
                                                value={formData.asset_purchase_cost}
                                                onChange={(e) => setFormData({ ...formData, asset_purchase_cost: e.target.value })}
                                                className="w-full border border-gray-300 rounded-lg px-3 py-2"
                                            />
                                        </div>
                                        <div>
                                            <label className="block text-sm font-medium text-gray-700 mb-1">Currency</label>
                                            <input
                                                type="text"
                                                maxLength={3}
                                                value={formData.asset_currency}
                                                onChange={(e) => setFormData({ ...formData, asset_currency: e.target.value.toUpperCase() })}
                                                className="w-full border border-gray-300 rounded-lg px-3 py-2"
                                                placeholder="NGN"
                                            />
                                        </div>
                                        <div>
                                            <label className="block text-sm font-medium text-gray-700 mb-1">Supplier Name</label>
                                            <input
                                                type="text"
                                                value={formData.asset_supplier_name}
                                                onChange={(e) => setFormData({ ...formData, asset_supplier_name: e.target.value })}
                                                className="w-full border border-gray-300 rounded-lg px-3 py-2"
                                            />
                                        </div>
                                        <div>
                                            <label className="block text-sm font-medium text-gray-700 mb-1">Serial Number</label>
                                            <input
                                                type="text"
                                                value={formData.asset_serial_number}
                                                onChange={(e) => setFormData({ ...formData, asset_serial_number: e.target.value })}
                                                className="w-full border border-gray-300 rounded-lg px-3 py-2"
                                            />
                                        </div>
                                        <div>
                                            <label className="block text-sm font-medium text-gray-700 mb-1">Model</label>
                                            <input
                                                type="text"
                                                value={formData.asset_model}
                                                onChange={(e) => setFormData({ ...formData, asset_model: e.target.value })}
                                                className="w-full border border-gray-300 rounded-lg px-3 py-2"
                                            />
                                        </div>
                                        <div>
                                            <label className="block text-sm font-medium text-gray-700 mb-1">Manufacturer</label>
                                            <input
                                                type="text"
                                                value={formData.asset_manufacturer}
                                                onChange={(e) => setFormData({ ...formData, asset_manufacturer: e.target.value })}
                                                className="w-full border border-gray-300 rounded-lg px-3 py-2"
                                            />
                                        </div>
                                        <div>
                                            <label className="block text-sm font-medium text-gray-700 mb-1">Year of Make</label>
                                            <input
                                                type="number"
                                                min="1900"
                                                max={new Date().getFullYear() + 1}
                                                value={formData.asset_year_of_make}
                                                onChange={(e) => setFormData({ ...formData, asset_year_of_make: e.target.value })}
                                                className="w-full border border-gray-300 rounded-lg px-3 py-2"
                                            />
                                        </div>
                                        <div>
                                            <label className="block text-sm font-medium text-gray-700 mb-1">Warranty Expiry</label>
                                            <input
                                                type="date"
                                                value={formData.asset_warranty_expiry}
                                                onChange={(e) => setFormData({ ...formData, asset_warranty_expiry: e.target.value })}
                                                className="w-full border border-gray-300 rounded-lg px-3 py-2"
                                            />
                                        </div>
                                        <div className="col-span-2">
                                            <label className="block text-sm font-medium text-gray-700 mb-1">Asset Description</label>
                                            <textarea
                                                value={formData.asset_description}
                                                onChange={(e) => setFormData({ ...formData, asset_description: e.target.value })}
                                                rows={2}
                                                className="w-full border border-gray-300 rounded-lg px-3 py-2"
                                            />
                                        </div>
                                        <div className="col-span-2">
                                            <label className="flex items-center text-sm font-medium text-gray-700 mb-1">
                                                <input
                                                    type="checkbox"
                                                    checked={formData.asset_is_trackable}
                                                    onChange={(e) => setFormData({ ...formData, asset_is_trackable: e.target.checked })}
                                                    className="rounded border-gray-300 text-green-600 focus:ring-green-500 mr-2"
                                                />
                                                <span>Is Trackable</span>
                                            </label>
                                        </div>
                                    </div>
                                </>
                            )}
                            
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

