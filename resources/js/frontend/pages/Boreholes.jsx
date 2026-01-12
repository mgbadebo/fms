import React, { useEffect, useState } from 'react';
import api from '../utils/api';
import { Droplet, Plus, Edit, Trash2 } from 'lucide-react';

export default function Boreholes() {
    const [boreholes, setBoreholes] = useState([]);
    const [sites, setSites] = useState([]);
    const [assetCategories, setAssetCategories] = useState([]);
    const [loading, setLoading] = useState(true);
    const [showModal, setShowModal] = useState(false);
    const [editingBorehole, setEditingBorehole] = useState(null);
    const [trackAsAsset, setTrackAsAsset] = useState(false);
    const [formData, setFormData] = useState({
        site_id: '',
        name: '',
        status: 'ACTIVE',
        notes: '',
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
    }, []);

    // Debug: Log when assetCategories changes
    useEffect(() => {
        // Visual indicator in page title when categories load
        if (assetCategories.length > 0) {
            document.title = `Boreholes (${assetCategories.length} categories loaded)`;
        }
    }, [assetCategories]);

    const fetchData = async () => {
        try {
            const [boreholesRes, sitesRes, categoriesRes] = await Promise.all([
                api.get('/api/v1/boreholes'),
                api.get('/api/v1/sites?per_page=1000'),
                api.get('/api/v1/asset-categories?per_page=1000'),
            ]);

            // Parse boreholes response
            console.log('Boreholes API response:', boreholesRes.data);
            const boreholesData = boreholesRes.data?.data || (Array.isArray(boreholesRes.data) ? boreholesRes.data : []);
            console.log('Parsed boreholes data:', boreholesData);
            if (boreholesData.length > 0) {
                console.log('First borehole sample:', JSON.stringify(boreholesData[0], null, 2));
            }
            setBoreholes(Array.isArray(boreholesData) ? boreholesData : []);

            // Parse sites response - handle paginated response structure
            let sitesArray = [];
            if (sitesRes.data) {
                if (Array.isArray(sitesRes.data)) {
                    sitesArray = sitesRes.data;
                } else if (sitesRes.data.data && Array.isArray(sitesRes.data.data)) {
                    sitesArray = sitesRes.data.data;
                } else if (Array.isArray(sitesRes.data)) {
                    sitesArray = sitesRes.data;
                }
            }
            setSites(sitesArray);
            
            // Parse asset categories response - handle paginated response
            let categoriesArray = [];
            if (categoriesRes.data) {
                // Laravel pagination returns: { data: [...], current_page: 1, per_page: 20, ... }
                if (categoriesRes.data.data && Array.isArray(categoriesRes.data.data)) {
                    categoriesArray = categoriesRes.data.data;
                } else if (Array.isArray(categoriesRes.data)) {
                    // Direct array response (unlikely with pagination, but handle it)
                    categoriesArray = categoriesRes.data;
                }
            }
            
            setAssetCategories(categoriesArray);
        } catch (error) {
            console.error('Error fetching data:', error);
            console.error('Error response:', error.response?.data);
            alert('Error loading data: ' + (error.response?.data?.message || error.message));
            setSites([]);
            setBoreholes([]);
            setAssetCategories([]);
        } finally {
            setLoading(false);
        }
    };

    const fetchAssetCategories = async () => {
        try {
            const response = await api.get('/api/v1/asset-categories?per_page=1000');
            
            // Handle paginated response structure
            let categoriesArray = [];
            if (response.data) {
                // Laravel pagination: { data: [...], current_page: 1, per_page: 1000, ... }
                if (response.data.data && Array.isArray(response.data.data)) {
                    categoriesArray = response.data.data;
                } else if (Array.isArray(response.data)) {
                    // Direct array (shouldn't happen with pagination, but handle it)
                    categoriesArray = response.data;
                }
            }
            
            setAssetCategories(categoriesArray);
            return categoriesArray;
        } catch (error) {
            const errorMsg = error.response?.data?.message || error.message || 'Unknown error';
            alert('Failed to load asset categories: ' + errorMsg + '\n\nCheck if the API endpoint /api/v1/asset-categories is accessible.');
            setAssetCategories([]);
            return [];
        }
    };

    const handleModalOpen = () => {
        setEditingBorehole(null);
        setTrackAsAsset(false);
        setFormData({
            site_id: '',
            name: '',
            status: 'ACTIVE',
            notes: '',
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

    const handleEdit = async (borehole) => {
        setEditingBorehole(borehole);
        const hasAsset = !!borehole.asset_id;
        setTrackAsAsset(hasAsset);
        
        // Ensure asset categories are loaded if not already loaded
        if (assetCategories.length === 0) {
            await fetchAssetCategories();
        }
        
        const categoryId = borehole.asset?.asset_category_id || '';
        
        setFormData({
            site_id: borehole.site?.id || '',
            name: borehole.name || '',
            status: borehole.status || 'ACTIVE',
            notes: borehole.notes || '',
            track_as_asset: hasAsset,
            asset_category_id: categoryId,
            asset_description: borehole.asset?.description || '',
            asset_acquisition_type: borehole.asset?.acquisition_type || '',
            asset_purchase_date: borehole.asset?.purchase_date ? new Date(borehole.asset.purchase_date).toISOString().slice(0, 10) : '',
            asset_purchase_cost: borehole.asset?.purchase_cost || '',
            asset_currency: borehole.asset?.currency || 'NGN',
            asset_supplier_name: borehole.asset?.supplier_name || '',
            asset_serial_number: borehole.asset?.serial_number || '',
            asset_model: borehole.asset?.model || '',
            asset_manufacturer: borehole.asset?.manufacturer || '',
            asset_year_of_make: borehole.asset?.year_of_make || '',
            asset_warranty_expiry: borehole.asset?.warranty_expiry ? new Date(borehole.asset.warranty_expiry).toISOString().slice(0, 10) : '',
            asset_is_trackable: borehole.asset?.is_trackable || false,
        });
        setShowModal(true);
    };

    const handleSubmit = async (e) => {
        e.preventDefault();
        try {
            // Prepare data to send
            const dataToSend = { ...formData };
            dataToSend.track_as_asset = trackAsAsset;
            
            // Only include asset fields if track_as_asset is checked
            if (!trackAsAsset) {
                // Remove all asset fields if not tracking as asset
                Object.keys(dataToSend).forEach(key => {
                    if (key.startsWith('asset_')) {
                        delete dataToSend[key];
                    }
                });
            }
            
            if (editingBorehole) {
                await api.patch(`/api/v1/boreholes/${editingBorehole.id}`, dataToSend);
            } else {
                await api.post('/api/v1/boreholes', dataToSend);
            }
            setShowModal(false);
            fetchData();
        } catch (error) {
            alert('Error saving borehole: ' + (error.response?.data?.message || 'Unknown error'));
        }
    };

    const handleDelete = async (id) => {
        if (!confirm('Are you sure you want to delete this borehole?')) return;
        try {
            await api.delete(`/api/v1/boreholes/${id}`);
            fetchData();
        } catch (error) {
            alert('Error deleting borehole: ' + (error.response?.data?.message || 'Unknown error'));
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
                    <h1 className="text-3xl font-bold text-gray-900">Borehole Management</h1>
                    <p className="mt-2 text-gray-600">Manage boreholes per Site; Farm is derived from Site</p>
                </div>
                <button
                    onClick={handleModalOpen}
                    className="bg-green-600 text-white px-4 py-2 rounded-lg hover:bg-green-700 flex items-center"
                >
                    <Plus className="h-5 w-5 mr-2" />
                    New Borehole
                </button>
            </div>

            {boreholes.length === 0 ? (
                <div className="bg-white rounded-lg shadow p-12 text-center">
                    <Droplet className="h-16 w-16 mx-auto text-gray-400 mb-4" />
                    <h3 className="text-lg font-medium text-gray-900 mb-2">No boreholes yet</h3>
                    <p className="text-gray-500 mb-4">Create your first borehole to get started</p>
                    <button
                        onClick={handleModalOpen}
                        className="bg-green-600 text-white px-4 py-2 rounded-lg hover:bg-green-700"
                    >
                        Create Borehole
                    </button>
                </div>
            ) : (
                <div className="bg-white rounded-lg shadow overflow-hidden">
                    <div className="overflow-x-auto">
                        <table className="min-w-full divide-y divide-gray-200">
                            <thead className="bg-gray-50">
                                <tr>
                                    <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Code</th>
                                    <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Name</th>
                                    <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Site</th>
                                    <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Status</th>
                                    <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Actions</th>
                                </tr>
                            </thead>
                            <tbody className="bg-white divide-y divide-gray-200">
                                {boreholes.map((borehole) => (
                                    <tr key={borehole.id} className="hover:bg-gray-50">
                                        <td className="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
                                            {borehole.borehole_code || borehole.code}
                                        </td>
                                        <td className="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                            {borehole.name}
                                        </td>
                                        <td className="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                            {borehole.site?.name || 'N/A'}
                                        </td>
                                        <td className="px-6 py-4 whitespace-nowrap">
                                            <span className="px-2 py-1 text-xs font-medium rounded bg-gray-100 text-gray-800">
                                                {borehole.status || 'ACTIVE'}
                                            </span>
                                        </td>
                                        <td className="px-6 py-4 whitespace-nowrap text-sm">
                                            <div className="flex space-x-2">
                                                <button
                                                    onClick={() => handleEdit(borehole)}
                                                    className="text-green-600 hover:text-green-700"
                                                >
                                                    <Edit className="h-4 w-4" />
                                                </button>
                                                <button
                                                    onClick={() => handleDelete(borehole.id)}
                                                    className="text-red-600 hover:text-red-700"
                                                >
                                                    <Trash2 className="h-4 w-4" />
                                                </button>
                                            </div>
                                        </td>
                                    </tr>
                                ))}
                            </tbody>
                        </table>
                    </div>
                </div>
            )}

            {/* Create/Edit Modal */}
            {showModal && (
                <div className="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50 p-4 overflow-y-auto">
                    <div className="bg-white rounded-lg max-w-4xl w-full p-6 max-h-[90vh] overflow-y-auto">
                        <h2 className="text-xl font-bold mb-4">
                            {editingBorehole ? 'Edit Borehole' : 'Create New Borehole'}
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
                                            <option key={site.id} value={site.id}>{site.name}</option>
                                        ))}
                                    </select>
                                    <p className="text-xs text-gray-500 mt-1">
                                        Code will be auto-generated as BH-[Site]-XXX (e.g., BH-EMU-001 for Emure site)
                                    </p>
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
                                    <label className="block text-sm font-medium text-gray-700 mb-1">Status *</label>
                                    <select
                                        required
                                        value={formData.status}
                                        onChange={(e) => setFormData({ ...formData, status: e.target.value })}
                                        className="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-green-500 focus:border-green-500"
                                    >
                                        <option value="ACTIVE">ACTIVE</option>
                                        <option value="INACTIVE">INACTIVE</option>
                                        <option value="UNDER_REPAIR">UNDER_REPAIR</option>
                                        <option value="DECOMMISSIONED">DECOMMISSIONED</option>
                                    </select>
                                </div>
                                <div className="md:col-span-2">
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
                                        Check this box to create an asset record for this borehole. You can enter asset details below.
                                    </p>
                                </div>
                                
                                {/* Asset Fields - Conditional */}
                                {trackAsAsset && (
                                    <>
                                        <div className="md:col-span-2 border-t pt-4 mt-2">
                                            <h3 className="text-lg font-semibold text-gray-900 mb-4">Asset Information</h3>
                                        </div>
                                        <div>
                                            <label className="block text-sm font-medium text-gray-700 mb-1">Asset Category</label>
                                            <select
                                                value={formData.asset_category_id ? String(formData.asset_category_id) : ''}
                                                onChange={(e) => setFormData({ ...formData, asset_category_id: e.target.value })}
                                                className="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-green-500 focus:border-green-500"
                                            >
                                                <option value="">
                                                    {assetCategories.length === 0 
                                                        ? '⚠️ No categories loaded (0)' 
                                                        : `Select category (${assetCategories.length} available)`
                                                    }
                                                </option>
                                                {assetCategories.length === 0 ? (
                                                    <option value="" disabled>Click "Retry" button below to load categories</option>
                                                ) : (
                                                    assetCategories.map((cat) => (
                                                        <option key={cat.id} value={String(cat.id)}>{cat.name}</option>
                                                    ))
                                                )}
                                            </select>
                                            {assetCategories.length === 0 && (
                                                <div className="mt-2 p-2 bg-yellow-50 border border-yellow-200 rounded">
                                                    <p className="text-xs text-yellow-800 mb-2">⚠️ Asset categories not loaded.</p>
                                                    <button
                                                        type="button"
                                                        onClick={async () => {
                                                            const result = await fetchAssetCategories();
                                                            if (result.length === 0) {
                                                                alert('Still no categories. Check if API endpoint /api/v1/asset-categories is working.');
                                                            }
                                                        }}
                                                        className="text-xs bg-yellow-600 text-white px-3 py-1 rounded hover:bg-yellow-700"
                                                    >
                                                        Retry Loading Categories
                                                    </button>
                                                </div>
                                            )}
                                            {assetCategories.length > 0 && formData.asset_category_id && (
                                                <p className="text-xs text-green-600 mt-1">
                                                    ✓ Selected category ID: {formData.asset_category_id}
                                                </p>
                                            )}
                                        </div>
                                        <div>
                                            <label className="block text-sm font-medium text-gray-700 mb-1">Acquisition Type</label>
                                            <select
                                                value={formData.asset_acquisition_type}
                                                onChange={(e) => setFormData({ ...formData, asset_acquisition_type: e.target.value })}
                                                className="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-green-500 focus:border-green-500"
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
                                                className="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-green-500 focus:border-green-500"
                                            />
                                        </div>
                                        <div>
                                            <label className="block text-sm font-medium text-gray-700 mb-1">Purchase Cost</label>
                                            <input
                                                type="number"
                                                step="0.01"
                                                value={formData.asset_purchase_cost}
                                                onChange={(e) => setFormData({ ...formData, asset_purchase_cost: e.target.value })}
                                                className="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-green-500 focus:border-green-500"
                                            />
                                        </div>
                                        <div>
                                            <label className="block text-sm font-medium text-gray-700 mb-1">Currency</label>
                                            <input
                                                type="text"
                                                maxLength={3}
                                                value={formData.asset_currency}
                                                onChange={(e) => setFormData({ ...formData, asset_currency: e.target.value.toUpperCase() })}
                                                className="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-green-500 focus:border-green-500"
                                                placeholder="NGN"
                                            />
                                        </div>
                                        <div>
                                            <label className="block text-sm font-medium text-gray-700 mb-1">Supplier Name</label>
                                            <input
                                                type="text"
                                                value={formData.asset_supplier_name}
                                                onChange={(e) => setFormData({ ...formData, asset_supplier_name: e.target.value })}
                                                className="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-green-500 focus:border-green-500"
                                            />
                                        </div>
                                        <div>
                                            <label className="block text-sm font-medium text-gray-700 mb-1">Serial Number</label>
                                            <input
                                                type="text"
                                                value={formData.asset_serial_number}
                                                onChange={(e) => setFormData({ ...formData, asset_serial_number: e.target.value })}
                                                className="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-green-500 focus:border-green-500"
                                            />
                                        </div>
                                        <div>
                                            <label className="block text-sm font-medium text-gray-700 mb-1">Model</label>
                                            <input
                                                type="text"
                                                value={formData.asset_model}
                                                onChange={(e) => setFormData({ ...formData, asset_model: e.target.value })}
                                                className="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-green-500 focus:border-green-500"
                                            />
                                        </div>
                                        <div>
                                            <label className="block text-sm font-medium text-gray-700 mb-1">Manufacturer</label>
                                            <input
                                                type="text"
                                                value={formData.asset_manufacturer}
                                                onChange={(e) => setFormData({ ...formData, asset_manufacturer: e.target.value })}
                                                className="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-green-500 focus:border-green-500"
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
                                                className="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-green-500 focus:border-green-500"
                                            />
                                        </div>
                                        <div>
                                            <label className="block text-sm font-medium text-gray-700 mb-1">Warranty Expiry</label>
                                            <input
                                                type="date"
                                                value={formData.asset_warranty_expiry}
                                                onChange={(e) => setFormData({ ...formData, asset_warranty_expiry: e.target.value })}
                                                className="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-green-500 focus:border-green-500"
                                            />
                                        </div>
                                        <div className="md:col-span-2">
                                            <label className="block text-sm font-medium text-gray-700 mb-1">Asset Description</label>
                                            <textarea
                                                value={formData.asset_description}
                                                onChange={(e) => setFormData({ ...formData, asset_description: e.target.value })}
                                                rows={2}
                                                className="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-green-500 focus:border-green-500"
                                            />
                                        </div>
                                        <div className="md:col-span-2">
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
                                    </>
                                )}
                                
                                <div className="md:col-span-2">
                                    <label className="block text-sm font-medium text-gray-700 mb-1">Notes</label>
                                    <textarea
                                        value={formData.notes}
                                        onChange={(e) => setFormData({ ...formData, notes: e.target.value })}
                                        rows={3}
                                        className="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-green-500 focus:border-green-500"
                                    />
                                </div>
                            </div>
                            <div className="flex justify-end space-x-3 pt-4">
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
                                    {editingBorehole ? 'Update' : 'Create'} Borehole
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            )}
        </div>
    );
}

