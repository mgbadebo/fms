import React, { useEffect, useState } from 'react';
import { useParams, Link } from 'react-router-dom';
import api from '../utils/api';
import { Factory, ArrowLeft, TrendingUp, DollarSign, Package, AlertTriangle } from 'lucide-react';

export default function GariProductionBatchDetail() {
    const { id } = useParams();
    const [batch, setBatch] = useState(null);
    const [loading, setLoading] = useState(true);

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
            
            if (batchData && (batchData.id || batchData.batch_code)) {
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
            <Link
                to="/gari-production-batches"
                className="flex items-center text-gray-600 hover:text-gray-900 mb-6"
            >
                <ArrowLeft className="h-5 w-5 mr-2" />
                Back to Batches
            </Link>

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
                                    ₦{batch.total_cassava_cost.toFixed(2)} total
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
                        {batch.total_cassava_cost > 0 && (
                            <div className="flex justify-between">
                                <span className="text-sm text-gray-600">Cassava Cost</span>
                                <span className="text-sm font-medium text-gray-900">
                                    ₦{batch.total_cassava_cost.toFixed(2)}
                                </span>
                            </div>
                        )}
                        <div className="flex justify-between">
                            <span className="text-sm text-gray-600">Labour</span>
                            <span className="text-sm font-medium text-gray-900">
                                ₦{batch.labour_cost.toFixed(2)}
                            </span>
                        </div>
                        <div className="flex justify-between">
                            <span className="text-sm text-gray-600">Fuel</span>
                            <span className="text-sm font-medium text-gray-900">
                                ₦{batch.fuel_cost.toFixed(2)}
                            </span>
                        </div>
                        <div className="flex justify-between">
                            <span className="text-sm text-gray-600">Equipment</span>
                            <span className="text-sm font-medium text-gray-900">
                                ₦{batch.equipment_cost.toFixed(2)}
                            </span>
                        </div>
                        <div className="flex justify-between">
                            <span className="text-sm text-gray-600">Water</span>
                            <span className="text-sm font-medium text-gray-900">
                                ₦{batch.water_cost.toFixed(2)}
                            </span>
                        </div>
                        <div className="flex justify-between">
                            <span className="text-sm text-gray-600">Transport</span>
                            <span className="text-sm font-medium text-gray-900">
                                ₦{batch.transport_cost.toFixed(2)}
                            </span>
                        </div>
                        {batch.other_costs > 0 && (
                            <div className="flex justify-between">
                                <span className="text-sm text-gray-600">Other</span>
                                <span className="text-sm font-medium text-gray-900">
                                    ₦{batch.other_costs.toFixed(2)}
                                </span>
                            </div>
                        )}
                        <div className="border-t pt-2 flex justify-between">
                            <span className="text-sm font-semibold text-gray-900">Total Processing</span>
                            <span className="text-sm font-semibold text-gray-900">
                                ₦{batch.total_processing_cost.toFixed(2)}
                            </span>
                        </div>
                        <div className="border-t pt-2 flex justify-between">
                            <span className="text-base font-bold text-gray-900">Total Cost</span>
                            <span className="text-base font-bold text-gray-900">
                                ₦{batch.total_cost?.toFixed(2) || '0.00'}
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
                                    {input.total_cost > 0 && (
                                        <p className="text-sm font-medium text-gray-900">
                                            ₦{input.total_cost.toFixed(2)}
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
        </div>
    );
}

