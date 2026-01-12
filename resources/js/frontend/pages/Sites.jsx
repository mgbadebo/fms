import React, { useEffect, useState } from 'react';
import api from '../utils/api';
import { Warehouse, Plus, Edit, Trash2 } from 'lucide-react';

export default function Sites() {
    const [sites, setSites] = useState([]);
    const [farms, setFarms] = useState([]);
    const [siteTypes, setSiteTypes] = useState([]);
    const [assetCategories, setAssetCategories] = useState([]);
    const [loading, setLoading] = useState(true);
    const [showModal, setShowModal] = useState(false);
    const [editingSite, setEditingSite] = useState(null);
    const [trackAsAsset, setTrackAsAsset] = useState(false);
    const [formData, setFormData] = useState({
        farm_id: '',
        name: '',
        code: '',
        type: '',
        description: '',
        address: '',
        latitude: '',
        longitude: '',
        total_area: '',
        area_unit: 'hectares',
        notes: '',
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
        fetchFarms();
        fetchSiteTypes();
        fetchAssetCategories();
    }, []);

    const fetchData = async () => {
        setLoading(true);
        try {
            const response = await api.get('/api/v1/sites?per_page=1000');
            const data = response.data?.data || (Array.isArray(response.data) ? response.data : []);
            setSites(Array.isArray(data) ? data : []);
        } catch (error) {
            console.error('Error fetching sites:', error);
            setSites([]);
        } finally {
            setLoading(false);
        }
    };

    const fetchFarms = async () => {
        try {
            const response = await api.get('/api/v1/farms?per_page=1000');
            console.log('Farms API full response:', response); // Debug log
            console.log('Farms API response.data:', response.data); // Debug log
            // Handle paginated response - Laravel pagination returns data in response.data.data
            let farmsData = [];
            // Laravel paginator returns: { data: [...], current_page: 1, per_page: 20, ... }
            if (response.data?.data && Array.isArray(response.data.data)) {
                farmsData = response.data.data;
            } else if (Array.isArray(response.data)) {
                // If response.data is directly an array
                farmsData = response.data;
            } else if (response.data && typeof response.data === 'object') {
                // Try to extract array from object
                const keys = Object.keys(response.data);
                if (keys.includes('data') && Array.isArray(response.data.data)) {
                    farmsData = response.data.data;
                } else {
                    // Maybe it's an object with numeric keys?
                    farmsData = Object.values(response.data).filter(item => item && typeof item === 'object' && item.id);
                }
            }
            console.log('Farms parsed:', farmsData.length, farmsData); // Debug log
            setFarms(farmsData);
        } catch (error) {
            console.error('Error fetching farms:', error);
            console.error('Error response:', error.response); // More detailed error
            setFarms([]);
        }
    };

    const fetchSiteTypes = async () => {
        try {
            const response = await api.get('/api/v1/site-types?per_page=1000&is_active=true');
            let typesArray = [];
            if (response.data) {
                if (Array.isArray(response.data)) {
                    typesArray = response.data;
                } else if (response.data.data && Array.isArray(response.data.data)) {
                    typesArray = response.data.data;
                }
            }
            setSiteTypes(typesArray);
        } catch (error) {
            console.error('Error fetching site types:', error);
            setSiteTypes([]);
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
            setAssetCategories([]);
        }
    };

    const handleModalOpen = () => {
        setEditingSite(null);
        setTrackAsAsset(false);
        // Wait for site types to load if not already loaded
        if (siteTypes.length === 0) {
            fetchSiteTypes();
        }
        const defaultType = siteTypes.length > 0 ? siteTypes[0].code : '';
        setFormData({
            farm_id: '',
            name: '',
            code: '',
            type: defaultType,
            description: '',
            address: '',
            latitude: '',
            longitude: '',
            total_area: '',
            area_unit: 'hectares',
            notes: '',
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

    const handleEdit = (site) => {
        setEditingSite(site);
        const hasAsset = !!site.asset_id;
        setTrackAsAsset(hasAsset);
        // Ensure asset categories are loaded if not already loaded
        if (assetCategories.length === 0) {
            fetchAssetCategories();
        }
        setFormData({
            farm_id: site.farm_id || '',
            name: site.name || '',
            code: site.code || '',
            type: site.type || 'farmland',
            description: site.description || '',
            address: site.address || '',
            latitude: site.latitude || '',
            longitude: site.longitude || '',
            total_area: site.total_area || '',
            area_unit: site.area_unit || 'hectares',
            notes: site.notes || '',
            is_active: site.is_active !== undefined ? site.is_active : true,
            track_as_asset: hasAsset,
            asset_category_id: site.asset?.asset_category_id || '',
            asset_description: site.asset?.description || '',
            asset_acquisition_type: site.asset?.acquisition_type || '',
            asset_purchase_date: site.asset?.purchase_date ? new Date(site.asset.purchase_date).toISOString().slice(0, 10) : '',
            asset_purchase_cost: site.asset?.purchase_cost || '',
            asset_currency: site.asset?.currency || 'NGN',
            asset_supplier_name: site.asset?.supplier_name || '',
            asset_serial_number: site.asset?.serial_number || '',
            asset_model: site.asset?.model || '',
            asset_manufacturer: site.asset?.manufacturer || '',
            asset_year_of_make: site.asset?.year_of_make || '',
            asset_warranty_expiry: site.asset?.warranty_expiry ? new Date(site.asset.warranty_expiry).toISOString().slice(0, 10) : '',
            asset_is_trackable: site.asset?.is_trackable || false,
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
            
            if (editingSite) {
                await api.put(`/api/v1/sites/${editingSite.id}`, payload);
            } else {
                await api.post('/api/v1/sites', payload);
            }
            setShowModal(false);
            fetchData();
        } catch (error) {
            console.error('Error saving site:', error);
            alert(error.response?.data?.message || 'Error saving site');
        }
    };

    const handleDelete = async (id) => {
        if (!confirm('Are you sure you want to delete this site?')) return;
        try {
            await api.delete(`/api/v1/sites/${id}`);
            fetchData();
        } catch (error) {
            console.error('Error deleting site:', error);
            alert(error.response?.data?.message || 'Error deleting site');
        }
    };

    const getTypeLabel = (type) => {
        const siteType = siteTypes.find(st => st.code === type);
        return siteType ? siteType.name : type;
    };

    if (loading) {
        return <div className="p-6">Loading...</div>;
    }

    return (
        <div className="p-6">
            <div className="flex justify-between items-center mb-6">
                <h1 className="text-2xl font-bold">Sites</h1>
                <button
                    onClick={handleModalOpen}
                    className="bg-blue-600 text-white px-4 py-2 rounded-lg hover:bg-blue-700 flex items-center gap-2"
                >
                    <Plus size={20} />
                    Create Site
                </button>
            </div>

            <div className="bg-white rounded-lg shadow overflow-hidden">
                <table className="min-w-full divide-y divide-gray-200">
                    <thead className="bg-gray-50">
                        <tr>
                            <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Code</th>
                            <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Name</th>
                            <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Type</th>
                            <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Farm</th>
                            <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                            <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                        </tr>
                    </thead>
                    <tbody className="bg-white divide-y divide-gray-200">
                        {sites.length === 0 ? (
                            <tr>
                                <td colSpan="6" className="px-6 py-4 text-center text-gray-500">No sites found</td>
                            </tr>
                        ) : (
                            sites.map((site) => (
                                <tr key={site.id}>
                                    <td className="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">{site.code}</td>
                                    <td className="px-6 py-4 whitespace-nowrap text-sm text-gray-900">{site.name}</td>
                                    <td className="px-6 py-4 whitespace-nowrap text-sm text-gray-500">{getTypeLabel(site.type)}</td>
                                    <td className="px-6 py-4 text-sm text-gray-500">{site.farm?.name || '-'}</td>
                                    <td className="px-6 py-4 whitespace-nowrap">
                                        <span className={`px-2 py-1 text-xs rounded-full ${site.is_active ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800'}`}>
                                            {site.is_active ? 'Active' : 'Inactive'}
                                        </span>
                                    </td>
                                    <td className="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                        <button
                                            onClick={() => handleEdit(site)}
                                            className="text-blue-600 hover:text-blue-900 mr-4"
                                        >
                                            <Edit size={16} />
                                        </button>
                                        <button
                                            onClick={() => handleDelete(site.id)}
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
                        <h2 className="text-xl font-bold mb-4">{editingSite ? 'Edit Site' : 'Create Site'}</h2>
                        <form onSubmit={handleSubmit}>
                            <div className="grid grid-cols-2 gap-4">
                                <div>
                                    <label className="block text-sm font-medium text-gray-700 mb-1">Farm</label>
                                    <select
                                        value={formData.farm_id}
                                        onChange={(e) => setFormData({ ...formData, farm_id: e.target.value })}
                                        className="w-full border border-gray-300 rounded-lg px-3 py-2"
                                    >
                                        <option value="">Select Farm</option>
                                        {farms.map((farm) => (
                                            <option key={farm.id} value={farm.id}>{farm.name}</option>
                                        ))}
                                    </select>
                                </div>
                                <div>
                                    <label className="block text-sm font-medium text-gray-700 mb-1">Type *</label>
                                    <select
                                        required
                                        value={formData.type}
                                        onChange={(e) => setFormData({ ...formData, type: e.target.value })}
                                        className="w-full border border-gray-300 rounded-lg px-3 py-2"
                                    >
                                        <option value="">Select type</option>
                                        {siteTypes
                                            .filter(st => st.is_active)
                                            .map((siteType) => (
                                                <option key={siteType.id} value={siteType.code}>
                                                    {siteType.name}
                                                </option>
                                            ))}
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
                                <div className="col-span-2">
                                    <label className="block text-sm font-medium text-gray-700 mb-1">Address</label>
                                    <textarea
                                        value={formData.address}
                                        onChange={(e) => setFormData({ ...formData, address: e.target.value })}
                                        className="w-full border border-gray-300 rounded-lg px-3 py-2"
                                        rows="2"
                                    />
                                </div>
                                <div>
                                    <label className="block text-sm font-medium text-gray-700 mb-1">Latitude</label>
                                    <input
                                        type="number"
                                        step="any"
                                        value={formData.latitude}
                                        onChange={(e) => setFormData({ ...formData, latitude: e.target.value })}
                                        className="w-full border border-gray-300 rounded-lg px-3 py-2"
                                    />
                                </div>
                                <div>
                                    <label className="block text-sm font-medium text-gray-700 mb-1">Longitude</label>
                                    <input
                                        type="number"
                                        step="any"
                                        value={formData.longitude}
                                        onChange={(e) => setFormData({ ...formData, longitude: e.target.value })}
                                        className="w-full border border-gray-300 rounded-lg px-3 py-2"
                                    />
                                </div>
                                <div>
                                    <label className="block text-sm font-medium text-gray-700 mb-1">Total Area</label>
                                    <input
                                        type="number"
                                        step="0.01"
                                        value={formData.total_area}
                                        onChange={(e) => setFormData({ ...formData, total_area: e.target.value })}
                                        className="w-full border border-gray-300 rounded-lg px-3 py-2"
                                    />
                                </div>
                                <div>
                                    <label className="block text-sm font-medium text-gray-700 mb-1">Area Unit</label>
                                    <select
                                        value={formData.area_unit}
                                        onChange={(e) => setFormData({ ...formData, area_unit: e.target.value })}
                                        className="w-full border border-gray-300 rounded-lg px-3 py-2"
                                    >
                                        <option value="hectares">Hectares</option>
                                        <option value="acres">Acres</option>
                                        <option value="sqm">Sqm</option>
                                    </select>
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
                                
                                {/* Track as Asset Checkbox */}
                                <div className="col-span-2">
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
                                        Check this box to create an asset record for this site. You can enter asset details below.
                                    </p>
                                </div>
                                
                                {/* Asset Fields - Conditional */}
                                {trackAsAsset && (
                                    <>
                                        <div className="col-span-2 border-t pt-4 mt-2">
                                            <h3 className="text-lg font-semibold text-gray-900 mb-4">Asset Information</h3>
                                        </div>
                                        <div>
                                            <label className="block text-sm font-medium text-gray-700 mb-1">Asset Category</label>
                                            <select
                                                value={formData.asset_category_id ? String(formData.asset_category_id) : ''}
                                                onChange={(e) => setFormData({ ...formData, asset_category_id: e.target.value })}
                                                className="w-full border border-gray-300 rounded-lg px-3 py-2 focus:ring-green-500 focus:border-green-500"
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
                                                className="w-full border border-gray-300 rounded-lg px-3 py-2 focus:ring-green-500 focus:border-green-500"
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
                                                className="w-full border border-gray-300 rounded-lg px-3 py-2 focus:ring-green-500 focus:border-green-500"
                                            />
                                        </div>
                                        <div>
                                            <label className="block text-sm font-medium text-gray-700 mb-1">Purchase Cost</label>
                                            <input
                                                type="number"
                                                step="0.01"
                                                value={formData.asset_purchase_cost}
                                                onChange={(e) => setFormData({ ...formData, asset_purchase_cost: e.target.value })}
                                                className="w-full border border-gray-300 rounded-lg px-3 py-2 focus:ring-green-500 focus:border-green-500"
                                            />
                                        </div>
                                        <div>
                                            <label className="block text-sm font-medium text-gray-700 mb-1">Currency</label>
                                            <input
                                                type="text"
                                                maxLength={3}
                                                value={formData.asset_currency}
                                                onChange={(e) => setFormData({ ...formData, asset_currency: e.target.value.toUpperCase() })}
                                                className="w-full border border-gray-300 rounded-lg px-3 py-2 focus:ring-green-500 focus:border-green-500"
                                                placeholder="NGN"
                                            />
                                        </div>
                                        <div>
                                            <label className="block text-sm font-medium text-gray-700 mb-1">Supplier Name</label>
                                            <input
                                                type="text"
                                                value={formData.asset_supplier_name}
                                                onChange={(e) => setFormData({ ...formData, asset_supplier_name: e.target.value })}
                                                className="w-full border border-gray-300 rounded-lg px-3 py-2 focus:ring-green-500 focus:border-green-500"
                                            />
                                        </div>
                                        <div>
                                            <label className="block text-sm font-medium text-gray-700 mb-1">Serial Number</label>
                                            <input
                                                type="text"
                                                value={formData.asset_serial_number}
                                                onChange={(e) => setFormData({ ...formData, asset_serial_number: e.target.value })}
                                                className="w-full border border-gray-300 rounded-lg px-3 py-2 focus:ring-green-500 focus:border-green-500"
                                            />
                                        </div>
                                        <div>
                                            <label className="block text-sm font-medium text-gray-700 mb-1">Model</label>
                                            <input
                                                type="text"
                                                value={formData.asset_model}
                                                onChange={(e) => setFormData({ ...formData, asset_model: e.target.value })}
                                                className="w-full border border-gray-300 rounded-lg px-3 py-2 focus:ring-green-500 focus:border-green-500"
                                            />
                                        </div>
                                        <div>
                                            <label className="block text-sm font-medium text-gray-700 mb-1">Manufacturer</label>
                                            <input
                                                type="text"
                                                value={formData.asset_manufacturer}
                                                onChange={(e) => setFormData({ ...formData, asset_manufacturer: e.target.value })}
                                                className="w-full border border-gray-300 rounded-lg px-3 py-2 focus:ring-green-500 focus:border-green-500"
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
                                                className="w-full border border-gray-300 rounded-lg px-3 py-2 focus:ring-green-500 focus:border-green-500"
                                            />
                                        </div>
                                        <div>
                                            <label className="block text-sm font-medium text-gray-700 mb-1">Warranty Expiry</label>
                                            <input
                                                type="date"
                                                value={formData.asset_warranty_expiry}
                                                onChange={(e) => setFormData({ ...formData, asset_warranty_expiry: e.target.value })}
                                                className="w-full border border-gray-300 rounded-lg px-3 py-2 focus:ring-green-500 focus:border-green-500"
                                            />
                                        </div>
                                        <div className="col-span-2">
                                            <label className="block text-sm font-medium text-gray-700 mb-1">Asset Description</label>
                                            <textarea
                                                value={formData.asset_description}
                                                onChange={(e) => setFormData({ ...formData, asset_description: e.target.value })}
                                                rows={2}
                                                className="w-full border border-gray-300 rounded-lg px-3 py-2 focus:ring-green-500 focus:border-green-500"
                                            />
                                        </div>
                                        <div className="col-span-2">
                                            <label className="flex items-center gap-2">
                                                <input
                                                    type="checkbox"
                                                    checked={formData.asset_is_trackable}
                                                    onChange={(e) => setFormData({ ...formData, asset_is_trackable: e.target.checked })}
                                                    className="rounded border-gray-300 text-green-600 focus:ring-green-500"
                                                />
                                                <span className="text-sm font-medium text-gray-700">Is Trackable</span>
                                            </label>
                                        </div>
                                    </>
                                )}
                                
                                <div className="col-span-2">
                                    <label className="block text-sm font-medium text-gray-700 mb-1">Notes</label>
                                    <textarea
                                        value={formData.notes}
                                        onChange={(e) => setFormData({ ...formData, notes: e.target.value })}
                                        className="w-full border border-gray-300 rounded-lg px-3 py-2"
                                        rows="2"
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
                                    {editingSite ? 'Update' : 'Create'}
                                </button>
                            </div>
                            </form>
                        </div>
                    </div>
                )}
        </div>
    );
}

