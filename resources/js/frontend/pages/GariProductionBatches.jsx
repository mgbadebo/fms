import React, { useEffect, useState } from 'react';
import { Link } from 'react-router-dom';
import api from '../utils/api';
import { Factory, Plus, TrendingUp, Package, DollarSign } from 'lucide-react';

export default function GariProductionBatches() {
    const [batches, setBatches] = useState([]);
    const [farms, setFarms] = useState([]);
    const [loading, setLoading] = useState(true);
    const [showModal, setShowModal] = useState(false);
    const [formData, setFormData] = useState({
        farm_id: '',
        processing_date: new Date().toISOString().slice(0, 10),
        cassava_source: 'HARVESTED',
        cassava_quantity_tonnes: '',
        cassava_cost_per_tonne: '',
        gari_produced_kg: '',
        gari_type: 'WHITE',
        gari_grade: 'FINE',
        labour_cost: 0,
        fuel_cost: 0,
        equipment_cost: 0,
        water_cost: 0,
        transport_cost: 0,
        other_costs: 0,
        waste_kg: 0,
        notes: '',
    });

    useEffect(() => {
        fetchData();
    }, []);

    const fetchData = async () => {
        try {
            const [batchesRes, farmsRes] = await Promise.all([
                api.get('/api/v1/gari-production-batches'),
                api.get('/api/v1/farms'),
            ]);
            // Handle paginated response - Laravel pagination returns { data: [...], current_page, etc }
            const batchesData = batchesRes.data;
            const batchesArray = batchesData?.data || (Array.isArray(batchesData) ? batchesData : []);
            setBatches(batchesArray);
            
            const farmsData = farmsRes.data;
            const farmsArray = farmsData?.data || (Array.isArray(farmsData) ? farmsData : []);
            setFarms(farmsArray);
        } catch (error) {
            console.error('Error fetching data:', error);
        } finally {
            setLoading(false);
        }
    };

    const fetchFarms = async () => {
        try {
            const response = await api.get('/api/v1/farms');
            setFarms(response.data.data || response.data);
        } catch (error) {
            console.error('Error fetching farms:', error);
        }
    };

    const handleModalOpen = () => {
        setShowModal(true);
        // Refresh farms list when opening modal to get newly created farms
        fetchFarms();
    };

    const handleSubmit = async (e) => {
        e.preventDefault();
        try {
            const response = await api.post('/api/v1/gari-production-batches', formData);
            console.log('Created batch response:', response.data);
            setShowModal(false);
            setFormData({
                farm_id: '',
                processing_date: new Date().toISOString().slice(0, 10),
                cassava_source: 'HARVESTED',
                cassava_quantity_tonnes: '',
                cassava_cost_per_tonne: '',
                gari_produced_kg: '',
                gari_type: 'WHITE',
                gari_grade: 'FINE',
                labour_cost: 0,
                fuel_cost: 0,
                equipment_cost: 0,
                water_cost: 0,
                transport_cost: 0,
                other_costs: 0,
                waste_kg: 0,
                notes: '',
            });
            fetchData();
        } catch (error) {
            alert('Error creating batch: ' + (error.response?.data?.message || 'Unknown error'));
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
                    <h1 className="text-3xl font-bold text-gray-900">Gari Production Batches</h1>
                    <p className="mt-2 text-gray-600">Track production from cassava to finished gari</p>
                </div>
                <button
                    onClick={handleModalOpen}
                    className="bg-green-600 text-white px-4 py-2 rounded-lg hover:bg-green-700 flex items-center"
                >
                    <Plus className="h-5 w-5 mr-2" />
                    New Batch
                </button>
            </div>

            {batches.length === 0 ? (
                <div className="bg-white rounded-lg shadow p-12 text-center">
                    <Factory className="h-16 w-16 mx-auto text-gray-400 mb-4" />
                    <h3 className="text-lg font-medium text-gray-900 mb-2">No production batches yet</h3>
                    <p className="text-gray-500 mb-4">Create your first production batch to get started</p>
                    <button
                        onClick={handleModalOpen}
                        className="bg-green-600 text-white px-4 py-2 rounded-lg hover:bg-green-700"
                    >
                        Create Batch
                    </button>
                </div>
            ) : (
                <div className="grid grid-cols-1 gap-6">
                    {batches.map((batch) => (
                        <Link
                            key={batch.id}
                            to={`/gari-production-batches/${batch.id}`}
                            className="bg-white rounded-lg shadow p-6 hover:shadow-lg transition-shadow"
                        >
                            <div className="flex items-start justify-between mb-4">
                                <div>
                                    <h3 className="text-lg font-semibold text-gray-900">{batch.batch_code}</h3>
                                    <p className="text-sm text-gray-600">
                                        {new Date(batch.processing_date).toLocaleDateString()} • {batch.farm?.name}
                                    </p>
                                </div>
                                <span className={`px-3 py-1 text-xs font-medium rounded ${
                                    batch.status === 'COMPLETED' ? 'bg-green-100 text-green-800' :
                                    batch.status === 'IN_PROGRESS' ? 'bg-blue-100 text-blue-800' :
                                    'bg-gray-100 text-gray-800'
                                }`}>
                                    {batch.status}
                                </span>
                            </div>
                            
                            <div className="grid grid-cols-2 md:grid-cols-4 gap-4">
                                <div>
                                    <p className="text-xs text-gray-500">Cassava Input</p>
                                    <p className="text-sm font-semibold text-gray-900">
                                        {batch.cassava_quantity_tonnes 
                                            ? `${batch.cassava_quantity_tonnes} tonnes` 
                                            : `${(batch.cassava_quantity_kg / 1000).toFixed(3)} tonnes`}
                                    </p>
                                    <p className="text-xs text-gray-500">
                                        {batch.cassava_source === 'HARVESTED' ? 'Harvested' : 
                                         batch.cassava_source === 'PURCHASED' ? 'Purchased' : 'Mixed'}
                                    </p>
                                </div>
                                <div>
                                    <p className="text-xs text-gray-500">Gari Produced</p>
                                    <p className="text-sm font-semibold text-gray-900">
                                        {batch.gari_produced_kg} kg
                                    </p>
                                    <p className="text-xs text-gray-500">
                                        {batch.gari_type} • {batch.gari_grade}
                                    </p>
                                </div>
                                <div>
                                    <p className="text-xs text-gray-500">Yield</p>
                                    <p className="text-sm font-semibold text-green-600">
                                        {batch.conversion_yield_percent != null 
                                            ? Number(batch.conversion_yield_percent).toFixed(1) 
                                            : 'N/A'}%
                                    </p>
                                </div>
                                <div>
                                    <p className="text-xs text-gray-500">Cost per kg</p>
                                    <p className="text-sm font-semibold text-gray-900">
                                        ₦{batch.cost_per_kg_gari != null 
                                            ? Number(batch.cost_per_kg_gari).toFixed(2) 
                                            : 'N/A'}
                                    </p>
                                </div>
                            </div>
                        </Link>
                    ))}
                </div>
            )}

            {/* Create Batch Modal */}
            {showModal && (
                <div className="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50 p-4 overflow-y-auto">
                    <div className="bg-white rounded-lg max-w-3xl w-full p-6 max-h-[90vh] overflow-y-auto">
                        <h2 className="text-xl font-bold mb-4">Create Production Batch</h2>
                        <form onSubmit={handleSubmit} className="space-y-4">
                            <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
                                <div>
                                    <label className="block text-sm font-medium text-gray-700 mb-1">Farm *</label>
                                    <select
                                        required
                                        value={formData.farm_id}
                                        onChange={(e) => setFormData({ ...formData, farm_id: e.target.value })}
                                        className="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-green-500 focus:border-green-500"
                                    >
                                        <option value="">Select farm</option>
                                        {farms.map((farm) => (
                                            <option key={farm.id} value={farm.id}>{farm.name}</option>
                                        ))}
                                    </select>
                                </div>
                                <div>
                                    <label className="block text-sm font-medium text-gray-700 mb-1">Processing Date *</label>
                                    <input
                                        type="date"
                                        required
                                        value={formData.processing_date}
                                        onChange={(e) => setFormData({ ...formData, processing_date: e.target.value })}
                                        className="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-green-500 focus:border-green-500"
                                    />
                                </div>
                                <div>
                                    <label className="block text-sm font-medium text-gray-700 mb-1">Cassava Source *</label>
                                    <select
                                        required
                                        value={formData.cassava_source}
                                        onChange={(e) => setFormData({ ...formData, cassava_source: e.target.value })}
                                        className="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-green-500 focus:border-green-500"
                                    >
                                        <option value="HARVESTED">Harvested</option>
                                        <option value="PURCHASED">Purchased</option>
                                        <option value="MIXED">Mixed</option>
                                    </select>
                                </div>
                                <div>
                                    <label className="block text-sm font-medium text-gray-700 mb-1">Cassava Quantity (tonnes) *</label>
                                    <input
                                        type="number"
                                        step="0.001"
                                        required
                                        value={formData.cassava_quantity_tonnes}
                                        onChange={(e) => setFormData({ ...formData, cassava_quantity_tonnes: e.target.value })}
                                        className="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-green-500 focus:border-green-500"
                                        placeholder="e.g., 1.5"
                                    />
                                    <p className="text-xs text-gray-500 mt-1">1 tonne = 1,000 kg</p>
                                </div>
                                <div>
                                    <label className="block text-sm font-medium text-gray-700 mb-1">Cost per tonne (₦)</label>
                                    <input
                                        type="number"
                                        step="0.01"
                                        value={formData.cassava_cost_per_tonne}
                                        onChange={(e) => setFormData({ ...formData, cassava_cost_per_tonne: e.target.value })}
                                        className="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-green-500 focus:border-green-500"
                                        placeholder="e.g., 150000"
                                    />
                                </div>
                                <div>
                                    <label className="block text-sm font-medium text-gray-700 mb-1">Gari Produced (kg) *</label>
                                    <input
                                        type="number"
                                        step="0.01"
                                        required
                                        value={formData.gari_produced_kg}
                                        onChange={(e) => setFormData({ ...formData, gari_produced_kg: e.target.value })}
                                        className="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-green-500 focus:border-green-500"
                                    />
                                </div>
                                <div>
                                    <label className="block text-sm font-medium text-gray-700 mb-1">Gari Type *</label>
                                    <select
                                        required
                                        value={formData.gari_type}
                                        onChange={(e) => setFormData({ ...formData, gari_type: e.target.value })}
                                        className="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-green-500 focus:border-green-500"
                                    >
                                        <option value="WHITE">White</option>
                                        <option value="YELLOW">Yellow</option>
                                    </select>
                                </div>
                                <div>
                                    <label className="block text-sm font-medium text-gray-700 mb-1">Gari Grade *</label>
                                    <select
                                        required
                                        value={formData.gari_grade}
                                        onChange={(e) => setFormData({ ...formData, gari_grade: e.target.value })}
                                        className="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-green-500 focus:border-green-500"
                                    >
                                        <option value="FINE">Fine</option>
                                        <option value="COARSE">Coarse</option>
                                        <option value="MIXED">Mixed</option>
                                    </select>
                                </div>
                            </div>

                            <div className="border-t pt-4">
                                <h3 className="font-semibold text-gray-900 mb-3">Processing Costs (₦)</h3>
                                <div className="grid grid-cols-2 md:grid-cols-3 gap-4">
                                    <div>
                                        <label className="block text-sm font-medium text-gray-700 mb-1">Labour</label>
                                        <input
                                            type="number"
                                            step="0.01"
                                            value={formData.labour_cost}
                                            onChange={(e) => setFormData({ ...formData, labour_cost: e.target.value })}
                                            className="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-green-500 focus:border-green-500"
                                        />
                                    </div>
                                    <div>
                                        <label className="block text-sm font-medium text-gray-700 mb-1">Fuel</label>
                                        <input
                                            type="number"
                                            step="0.01"
                                            value={formData.fuel_cost}
                                            onChange={(e) => setFormData({ ...formData, fuel_cost: e.target.value })}
                                            className="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-green-500 focus:border-green-500"
                                        />
                                    </div>
                                    <div>
                                        <label className="block text-sm font-medium text-gray-700 mb-1">Equipment</label>
                                        <input
                                            type="number"
                                            step="0.01"
                                            value={formData.equipment_cost}
                                            onChange={(e) => setFormData({ ...formData, equipment_cost: e.target.value })}
                                            className="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-green-500 focus:border-green-500"
                                        />
                                    </div>
                                    <div>
                                        <label className="block text-sm font-medium text-gray-700 mb-1">Water</label>
                                        <input
                                            type="number"
                                            step="0.01"
                                            value={formData.water_cost}
                                            onChange={(e) => setFormData({ ...formData, water_cost: e.target.value })}
                                            className="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-green-500 focus:border-green-500"
                                        />
                                    </div>
                                    <div>
                                        <label className="block text-sm font-medium text-gray-700 mb-1">Transport</label>
                                        <input
                                            type="number"
                                            step="0.01"
                                            value={formData.transport_cost}
                                            onChange={(e) => setFormData({ ...formData, transport_cost: e.target.value })}
                                            className="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-green-500 focus:border-green-500"
                                        />
                                    </div>
                                    <div>
                                        <label className="block text-sm font-medium text-gray-700 mb-1">Other</label>
                                        <input
                                            type="number"
                                            step="0.01"
                                            value={formData.other_costs}
                                            onChange={(e) => setFormData({ ...formData, other_costs: e.target.value })}
                                            className="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-green-500 focus:border-green-500"
                                        />
                                    </div>
                                </div>
                            </div>

                            <div>
                                <label className="block text-sm font-medium text-gray-700 mb-1">Waste (kg)</label>
                                <input
                                    type="number"
                                    step="0.01"
                                    value={formData.waste_kg}
                                    onChange={(e) => setFormData({ ...formData, waste_kg: e.target.value })}
                                    className="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-green-500 focus:border-green-500"
                                />
                            </div>

                            <div>
                                <label className="block text-sm font-medium text-gray-700 mb-1">Notes</label>
                                <textarea
                                    value={formData.notes}
                                    onChange={(e) => setFormData({ ...formData, notes: e.target.value })}
                                    rows={3}
                                    className="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-green-500 focus:border-green-500"
                                />
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
                                    Create Batch
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            )}
        </div>
    );
}

