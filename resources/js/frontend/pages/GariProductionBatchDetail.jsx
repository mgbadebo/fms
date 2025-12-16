import React, { useEffect, useState } from 'react';
import { useParams, Link } from 'react-router-dom';
import api from '../utils/api';
import { Factory, ArrowLeft, TrendingUp, DollarSign, Package, AlertTriangle, Edit } from 'lucide-react';

export default function GariProductionBatchDetail() {
    const { id } = useParams();
    const [batch, setBatch] = useState(null);
    const [loading, setLoading] = useState(true);
    const [showEditModal, setShowEditModal] = useState(false);
    const [editFormData, setEditFormData] = useState(null);

    useEffect(() => {
        fetchBatch();
    }, [id]);

    const fetchBatch = async () => {
        try {
            console.log('Fetching batch with ID:', id);
            const response = await api.get(`/api/v1/gari-production-batches/${id}`);
            console.log('Full API response:', response);
            console.log('Response data:', response.data);
            console.log('Response data.data:', response.data?.data);
            
            // Handle response structure - API returns { data: { ... } }
            const batchData = response.data?.data || response.data;
            console.log('Extracted batch data:', batchData);
            console.log('Batch data type:', typeof batchData);
            console.log('Batch data is object:', batchData && typeof batchData === 'object');
            
            // Accept any object with data (less strict validation)
            if (batchData && typeof batchData === 'object' && !Array.isArray(batchData)) {
                console.log('Setting batch:', batchData);
                setBatch(batchData);
            } else {
                console.error('Invalid batch data structure:', batchData);
                console.error('Batch data keys:', batchData ? Object.keys(batchData) : 'null');
                setBatch(null);
            }
        } catch (error) {
            console.error('Error fetching batch:', error);
            console.error('Error response:', error.response);
            console.error('Error response data:', error.response?.data);
            console.error('Error status:', error.response?.status);
            console.error('Error message:', error.message);
            console.error('Request URL:', error.config?.url);
            console.error('Batch ID from URL:', id);
            // Set batch to null to show error message
            setBatch(null);
        } finally {
            setLoading(false);
        }
    };

    const handleEditClick = () => {
        if (batch) {
            setEditFormData({
                processing_date: batch.processing_date ? new Date(batch.processing_date).toISOString().slice(0, 10) : '',
                cassava_source: batch.cassava_source || 'HARVESTED',
                cassava_quantity_tonnes: batch.cassava_quantity_tonnes || (batch.cassava_quantity_kg ? (batch.cassava_quantity_kg / 1000).toString() : ''),
                cassava_cost_per_tonne: batch.cassava_cost_per_tonne || '',
                gari_produced_kg: batch.gari_produced_kg || '',
                gari_type: batch.gari_type || 'WHITE',
                gari_grade: batch.gari_grade || 'FINE',
                labour_cost: batch.labour_cost || 0,
                fuel_cost: batch.fuel_cost || 0,
                equipment_cost: batch.equipment_cost || 0,
                water_cost: batch.water_cost || 0,
                transport_cost: batch.transport_cost || 0,
                other_costs: batch.other_costs || 0,
                waste_kg: batch.waste_kg || 0,
                status: batch.status || 'PLANNED',
                notes: batch.notes || '',
            });
            setShowEditModal(true);
        }
    };

    const handleEditSubmit = async (e) => {
        e.preventDefault();
        try {
            await api.put(`/api/v1/gari-production-batches/${id}`, editFormData);
            setShowEditModal(false);
            setEditFormData(null);
            fetchBatch(); // Refresh the batch data
        } catch (error) {
            alert('Error updating batch: ' + (error.response?.data?.message || 'Unknown error'));
        }
    };

    if (loading) {
        return (
            <div className="flex items-center justify-center h-64">
                <div className="animate-spin rounded-full h-12 w-12 border-b-2 border-green-600"></div>
            </div>
        );
    }

    if (!batch && !loading) {
        return (
            <div className="text-center py-12">
                <p className="text-gray-500 mb-2">Production batch not found</p>
                <p className="text-sm text-gray-400 mb-2">ID: {id}</p>
                <p className="text-xs text-gray-400 mb-4">
                    Check the browser console for detailed error information
                </p>
                <Link to="/gari-production-batches" className="text-green-600 hover:text-green-700 mt-4 inline-block">
                    ← Back to Batches
                </Link>
            </div>
        );
    }

    return (
        <div>
            <div className="flex items-center justify-between mb-6">
                <Link
                    to="/gari-production-batches"
                    className="flex items-center text-gray-600 hover:text-gray-900"
                >
                    <ArrowLeft className="h-5 w-5 mr-2" />
                    Back to Batches
                </Link>
                <button
                    onClick={handleEditClick}
                    className="flex items-center px-4 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700"
                >
                    <Edit className="h-5 w-5 mr-2" />
                    Edit Batch
                </button>
            </div>

            {/* Batch Header */}
            <div className="bg-white rounded-lg shadow mb-6">
                <div className="px-6 py-4 border-b border-gray-200">
                    <div className="flex items-center justify-between">
                        <div className="flex items-center">
                            <div className="bg-orange-100 p-3 rounded-lg">
                                <Factory className="h-8 w-8 text-orange-600" />
                            </div>
                            <div className="ml-4">
                                <h1 className="text-2xl font-bold text-gray-900">{batch.batch_code}</h1>
                                <p className="text-gray-600">
                                    {batch.farm?.name} • {new Date(batch.processing_date).toLocaleDateString()}
                                </p>
                            </div>
                        </div>
                        <span className={`px-3 py-1 text-sm font-medium rounded ${
                            batch.status === 'COMPLETED' ? 'bg-green-100 text-green-800' :
                            batch.status === 'IN_PROGRESS' ? 'bg-blue-100 text-blue-800' :
                            'bg-gray-100 text-gray-800'
                        }`}>
                            {batch.status}
                        </span>
                    </div>
                </div>
                <div className="px-6 py-4">
                    <div className="grid grid-cols-1 md:grid-cols-4 gap-6">
                        <div>
                            <p className="text-sm text-gray-600 mb-1">Cassava Input</p>
                            <p className="text-lg font-semibold text-gray-900">
                                {batch.cassava_quantity_tonnes 
                                    ? `${batch.cassava_quantity_tonnes} tonnes` 
                                    : `${(batch.cassava_quantity_kg / 1000).toFixed(3)} tonnes`}
                            </p>
                            {batch.total_cassava_cost && (
                                <p className="text-xs text-gray-500">
                                    ₦{Number(batch.total_cassava_cost).toFixed(2)} total
                                </p>
                            )}
                        </div>
                        <div>
                            <p className="text-sm text-gray-600 mb-1">Gari Produced</p>
                            <p className="text-lg font-semibold text-gray-900">
                                {batch.gari_produced_kg} kg
                            </p>
                            <p className="text-xs text-gray-500">
                                {batch.gari_type} • {batch.gari_grade}
                            </p>
                        </div>
                        <div>
                            <p className="text-sm text-gray-600 mb-1">Conversion Yield</p>
                            <p className="text-lg font-semibold text-green-600">
                                {batch.conversion_yield_percent != null 
                                    ? Number(batch.conversion_yield_percent).toFixed(1) 
                                    : 'N/A'}%
                            </p>
                        </div>
                        <div>
                            <p className="text-sm text-gray-600 mb-1">Cost per kg</p>
                            <p className="text-lg font-semibold text-gray-900">
                                ₦{batch.cost_per_kg_gari != null 
                                    ? Number(batch.cost_per_kg_gari).toFixed(2) 
                                    : 'N/A'}
                            </p>
                        </div>
                    </div>
                </div>
            </div>

            {/* Cost Breakdown */}
            <div className="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
                <div className="bg-white rounded-lg shadow p-6">
                    <h2 className="text-lg font-semibold text-gray-900 mb-4 flex items-center">
                        <DollarSign className="h-5 w-5 mr-2 text-green-600" />
                        Cost Breakdown
                    </h2>
                    <div className="space-y-3">
                        {Number(batch.total_cassava_cost || 0) > 0 && (
                            <div className="flex justify-between">
                                <span className="text-sm text-gray-600">Cassava Cost</span>
                                <span className="text-sm font-medium text-gray-900">
                                    ₦{Number(batch.total_cassava_cost || 0).toFixed(2)}
                                </span>
                            </div>
                        )}
                        <div className="flex justify-between">
                            <span className="text-sm text-gray-600">Labour</span>
                            <span className="text-sm font-medium text-gray-900">
                                ₦{Number(batch.labour_cost || 0).toFixed(2)}
                            </span>
                        </div>
                        <div className="flex justify-between">
                            <span className="text-sm text-gray-600">Fuel</span>
                            <span className="text-sm font-medium text-gray-900">
                                ₦{Number(batch.fuel_cost || 0).toFixed(2)}
                            </span>
                        </div>
                        <div className="flex justify-between">
                            <span className="text-sm text-gray-600">Equipment</span>
                            <span className="text-sm font-medium text-gray-900">
                                ₦{Number(batch.equipment_cost || 0).toFixed(2)}
                            </span>
                        </div>
                        <div className="flex justify-between">
                            <span className="text-sm text-gray-600">Water</span>
                            <span className="text-sm font-medium text-gray-900">
                                ₦{Number(batch.water_cost || 0).toFixed(2)}
                            </span>
                        </div>
                        <div className="flex justify-between">
                            <span className="text-sm text-gray-600">Transport</span>
                            <span className="text-sm font-medium text-gray-900">
                                ₦{Number(batch.transport_cost || 0).toFixed(2)}
                            </span>
                        </div>
                        {Number(batch.other_costs || 0) > 0 && (
                            <div className="flex justify-between">
                                <span className="text-sm text-gray-600">Other</span>
                                <span className="text-sm font-medium text-gray-900">
                                    ₦{Number(batch.other_costs || 0).toFixed(2)}
                                </span>
                            </div>
                        )}
                        <div className="border-t pt-2 flex justify-between">
                            <span className="text-sm font-semibold text-gray-900">Total Processing</span>
                            <span className="text-sm font-semibold text-gray-900">
                                ₦{Number(batch.total_processing_cost || 0).toFixed(2)}
                            </span>
                        </div>
                        <div className="border-t pt-2 flex justify-between">
                            <span className="text-base font-bold text-gray-900">Total Cost</span>
                            <span className="text-base font-bold text-gray-900">
                                ₦{batch.total_cost != null ? Number(batch.total_cost).toFixed(2) : '0.00'}
                            </span>
                        </div>
                    </div>
                </div>

                <div className="bg-white rounded-lg shadow p-6">
                    <h2 className="text-lg font-semibold text-gray-900 mb-4 flex items-center">
                        <TrendingUp className="h-5 w-5 mr-2 text-blue-600" />
                        Performance Metrics
                    </h2>
                    <div className="space-y-4">
                        <div>
                            <p className="text-sm text-gray-600 mb-1">Conversion Yield</p>
                            <div className="flex items-center">
                                <div className="flex-1 bg-gray-200 rounded-full h-2 mr-2">
                                    <div
                                        className="bg-green-600 h-2 rounded-full"
                                        style={{ width: `${Math.min(Number(batch.conversion_yield_percent) || 0, 100)}%` }}
                                    ></div>
                                </div>
                                <span className="text-sm font-semibold text-gray-900">
                                    {batch.conversion_yield_percent != null 
                                        ? Number(batch.conversion_yield_percent).toFixed(1) 
                                        : '0'}%
                                </span>
                            </div>
                            <p className="text-xs text-gray-500 mt-1">
                                Target: 22-30%
                            </p>
                        </div>
                        {batch.waste_kg > 0 && (
                            <div>
                                <p className="text-sm text-gray-600 mb-1">Waste</p>
                                <p className="text-sm font-semibold text-gray-900">
                                    {batch.waste_kg} kg ({batch.waste_percent != null 
                                        ? Number(batch.waste_percent).toFixed(1) 
                                        : '0'}%)
                                </p>
                            </div>
                        )}
                        <div>
                            <p className="text-sm text-gray-600 mb-1">Cost Efficiency</p>
                            <p className="text-sm font-semibold text-gray-900">
                                ₦{batch.cost_per_kg_gari != null 
                                    ? Number(batch.cost_per_kg_gari).toFixed(2) 
                                    : 'N/A'} per kg
                            </p>
                        </div>
                    </div>
                </div>
            </div>

            {/* Cassava Inputs */}
            {batch.cassava_inputs && batch.cassava_inputs.length > 0 && (
                <div className="bg-white rounded-lg shadow mb-6">
                    <div className="px-6 py-4 border-b border-gray-200">
                        <h2 className="text-lg font-semibold text-gray-900">Cassava Inputs</h2>
                    </div>
                    <div className="divide-y divide-gray-200">
                        {batch.cassava_inputs.map((input) => (
                            <div key={input.id} className="px-6 py-4">
                                <div className="flex justify-between items-center">
                                    <div>
                                        <p className="font-medium text-gray-900">
                                            {input.quantity_kg} kg
                                        </p>
                                        <p className="text-sm text-gray-600">
                                            {input.source_type === 'HARVESTED' ? 'Harvested' : 'Purchased'}
                                            {input.variety && ` • ${input.variety}`}
                                            {input.harvest_lot && ` • Lot: ${input.harvest_lot.code || input.harvest_lot.id}`}
                                        </p>
                                    </div>
                                    {Number(input.total_cost || 0) > 0 && (
                                        <p className="text-sm font-medium text-gray-900">
                                            ₦{Number(input.total_cost || 0).toFixed(2)}
                                        </p>
                                    )}
                                </div>
                            </div>
                        ))}
                    </div>
                </div>
            )}

            {/* Notes */}
            {batch.notes && (
                <div className="bg-white rounded-lg shadow p-6">
                    <h2 className="text-lg font-semibold text-gray-900 mb-2">Notes</h2>
                    <p className="text-gray-700">{batch.notes}</p>
                </div>
            )}

            {/* Edit Batch Modal */}
            {showEditModal && editFormData && (
                <div className="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50 p-4 overflow-y-auto">
                    <div className="bg-white rounded-lg max-w-3xl w-full p-6 max-h-[90vh] overflow-y-auto">
                        <h2 className="text-xl font-bold mb-4">Edit Production Batch</h2>
                        <form onSubmit={handleEditSubmit} className="space-y-4">
                            <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
                                <div>
                                    <label className="block text-sm font-medium text-gray-700 mb-1">Processing Date *</label>
                                    <input
                                        type="date"
                                        required
                                        value={editFormData.processing_date}
                                        onChange={(e) => setEditFormData({ ...editFormData, processing_date: e.target.value })}
                                        className="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-green-500 focus:border-green-500"
                                    />
                                </div>
                                <div>
                                    <label className="block text-sm font-medium text-gray-700 mb-1">Status</label>
                                    <select
                                        value={editFormData.status}
                                        onChange={(e) => setEditFormData({ ...editFormData, status: e.target.value })}
                                        className="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-green-500 focus:border-green-500"
                                    >
                                        <option value="PLANNED">Planned</option>
                                        <option value="IN_PROGRESS">In Progress</option>
                                        <option value="COMPLETED">Completed</option>
                                        <option value="CANCELLED">Cancelled</option>
                                    </select>
                                </div>
                                <div>
                                    <label className="block text-sm font-medium text-gray-700 mb-1">Cassava Source *</label>
                                    <select
                                        required
                                        value={editFormData.cassava_source}
                                        onChange={(e) => setEditFormData({ ...editFormData, cassava_source: e.target.value })}
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
                                        value={editFormData.cassava_quantity_tonnes}
                                        onChange={(e) => setEditFormData({ ...editFormData, cassava_quantity_tonnes: e.target.value })}
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
                                        value={editFormData.cassava_cost_per_tonne}
                                        onChange={(e) => setEditFormData({ ...editFormData, cassava_cost_per_tonne: e.target.value })}
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
                                        value={editFormData.gari_produced_kg}
                                        onChange={(e) => setEditFormData({ ...editFormData, gari_produced_kg: e.target.value })}
                                        className="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-green-500 focus:border-green-500"
                                    />
                                </div>
                                <div>
                                    <label className="block text-sm font-medium text-gray-700 mb-1">Gari Type *</label>
                                    <select
                                        required
                                        value={editFormData.gari_type}
                                        onChange={(e) => setEditFormData({ ...editFormData, gari_type: e.target.value })}
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
                                        value={editFormData.gari_grade}
                                        onChange={(e) => setEditFormData({ ...editFormData, gari_grade: e.target.value })}
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
                                            value={editFormData.labour_cost}
                                            onChange={(e) => setEditFormData({ ...editFormData, labour_cost: e.target.value })}
                                            className="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-green-500 focus:border-green-500"
                                        />
                                    </div>
                                    <div>
                                        <label className="block text-sm font-medium text-gray-700 mb-1">Fuel</label>
                                        <input
                                            type="number"
                                            step="0.01"
                                            value={editFormData.fuel_cost}
                                            onChange={(e) => setEditFormData({ ...editFormData, fuel_cost: e.target.value })}
                                            className="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-green-500 focus:border-green-500"
                                        />
                                    </div>
                                    <div>
                                        <label className="block text-sm font-medium text-gray-700 mb-1">Equipment</label>
                                        <input
                                            type="number"
                                            step="0.01"
                                            value={editFormData.equipment_cost}
                                            onChange={(e) => setEditFormData({ ...editFormData, equipment_cost: e.target.value })}
                                            className="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-green-500 focus:border-green-500"
                                        />
                                    </div>
                                    <div>
                                        <label className="block text-sm font-medium text-gray-700 mb-1">Water</label>
                                        <input
                                            type="number"
                                            step="0.01"
                                            value={editFormData.water_cost}
                                            onChange={(e) => setEditFormData({ ...editFormData, water_cost: e.target.value })}
                                            className="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-green-500 focus:border-green-500"
                                        />
                                    </div>
                                    <div>
                                        <label className="block text-sm font-medium text-gray-700 mb-1">Transport</label>
                                        <input
                                            type="number"
                                            step="0.01"
                                            value={editFormData.transport_cost}
                                            onChange={(e) => setEditFormData({ ...editFormData, transport_cost: e.target.value })}
                                            className="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-green-500 focus:border-green-500"
                                        />
                                    </div>
                                    <div>
                                        <label className="block text-sm font-medium text-gray-700 mb-1">Other</label>
                                        <input
                                            type="number"
                                            step="0.01"
                                            value={editFormData.other_costs}
                                            onChange={(e) => setEditFormData({ ...editFormData, other_costs: e.target.value })}
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
                                    value={editFormData.waste_kg}
                                    onChange={(e) => setEditFormData({ ...editFormData, waste_kg: e.target.value })}
                                    className="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-green-500 focus:border-green-500"
                                />
                            </div>

                            <div>
                                <label className="block text-sm font-medium text-gray-700 mb-1">Notes</label>
                                <textarea
                                    value={editFormData.notes}
                                    onChange={(e) => setEditFormData({ ...editFormData, notes: e.target.value })}
                                    rows={3}
                                    className="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-green-500 focus:border-green-500"
                                />
                            </div>

                            <div className="flex justify-end space-x-3 pt-4">
                                <button
                                    type="button"
                                    onClick={() => {
                                        setShowEditModal(false);
                                        setEditFormData(null);
                                    }}
                                    className="px-4 py-2 border border-gray-300 rounded-lg text-gray-700 hover:bg-gray-50"
                                >
                                    Cancel
                                </button>
                                <button
                                    type="submit"
                                    className="px-4 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700"
                                >
                                    Update Batch
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            )}
        </div>
    );
}

