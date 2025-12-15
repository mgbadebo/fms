import React, { useEffect, useState } from 'react';
import { useParams, Link } from 'react-router-dom';
import api from '../utils/api';
import { Package, Scale, Printer, ArrowLeft, Calendar } from 'lucide-react';

export default function HarvestLotDetail() {
    const { id } = useParams();
    const [harvestLot, setHarvestLot] = useState(null);
    const [scaleDevices, setScaleDevices] = useState([]);
    const [loading, setLoading] = useState(true);
    const [selectedScaleDevice, setSelectedScaleDevice] = useState('');

    useEffect(() => {
        fetchData();
    }, [id]);

    const fetchData = async () => {
        try {
            const [harvestRes, scalesRes] = await Promise.all([
                api.get(`/api/v1/harvest-lots/${id}`),
                api.get('/api/v1/scale-devices'),
            ]);
            setHarvestLot(harvestRes.data.data || harvestRes.data);
            setScaleDevices(scalesRes.data.data || scalesRes.data);
        } catch (error) {
            console.error('Error fetching data:', error);
        } finally {
            setLoading(false);
        }
    };

    const handleWeigh = async () => {
        if (!selectedScaleDevice) {
            alert('Please select a scale device');
            return;
        }

        try {
            const response = await api.post('/api/v1/scale-readings', {
                scale_device_id: selectedScaleDevice,
                context_type: 'App\\Models\\HarvestLot',
                context_id: id,
                unit: 'kg',
            });

            alert(`Weight recorded: ${response.data.data.net_weight} ${response.data.data.unit}`);
            fetchData();
        } catch (error) {
            alert('Error reading scale: ' + (error.response?.data?.message || 'Unknown error'));
        }
    };

    const handlePrintLabel = async () => {
        try {
            const response = await api.post('/api/v1/labels/print', {
                label_template_id: null, // Will use default
                context_type: 'App\\Models\\HarvestLot',
                context_id: id,
            });

            alert('Label printed successfully!');
        } catch (error) {
            alert('Error printing label: ' + (error.response?.data?.message || 'Unknown error'));
        }
    };

    if (loading) {
        return (
            <div className="flex items-center justify-center h-64">
                <div className="animate-spin rounded-full h-12 w-12 border-b-2 border-green-600"></div>
            </div>
        );
    }

    if (!harvestLot) {
        return (
            <div className="text-center py-12">
                <p className="text-gray-500">Harvest lot not found</p>
                <Link to="/harvest-lots" className="text-green-600 hover:text-green-700 mt-4 inline-block">
                    ← Back to Harvest Lots
                </Link>
            </div>
        );
    }

    return (
        <div>
            <Link
                to="/harvest-lots"
                className="flex items-center text-gray-600 hover:text-gray-900 mb-6"
            >
                <ArrowLeft className="h-5 w-5 mr-2" />
                Back to Harvest Lots
            </Link>

            <div className="bg-white rounded-lg shadow mb-6">
                <div className="px-6 py-4 border-b border-gray-200">
                    <div className="flex items-center justify-between">
                        <div className="flex items-center">
                            <div className="bg-blue-100 p-3 rounded-lg">
                                <Package className="h-8 w-8 text-blue-600" />
                            </div>
                            <div className="ml-4">
                                <h1 className="text-2xl font-bold text-gray-900">
                                    {harvestLot.code || `Harvest Lot #${harvestLot.id}`}
                                </h1>
                                <p className="text-gray-600">
                                    {harvestLot.farm?.name} • {harvestLot.field?.name}
                                </p>
                            </div>
                        </div>
                        {harvestLot.quality_grade && (
                            <span className="px-3 py-1 text-sm font-medium bg-blue-100 text-blue-800 rounded">
                                Grade {harvestLot.quality_grade}
                            </span>
                        )}
                    </div>
                </div>
                <div className="px-6 py-4">
                    <div className="grid grid-cols-1 md:grid-cols-3 gap-6">
                        <div>
                            <p className="text-sm text-gray-600 mb-1">Harvest Date</p>
                            <div className="flex items-center">
                                <Calendar className="h-4 w-4 mr-2 text-gray-400" />
                                <p className="text-lg font-semibold text-gray-900">
                                    {new Date(harvestLot.harvested_at).toLocaleDateString()}
                                </p>
                            </div>
                        </div>
                        <div>
                            <p className="text-sm text-gray-600 mb-1">Net Weight</p>
                            <p className="text-lg font-semibold text-gray-900">
                                {harvestLot.net_weight || harvestLot.gross_weight || 'Not weighed'} {harvestLot.weight_unit || 'kg'}
                            </p>
                        </div>
                        <div>
                            <p className="text-sm text-gray-600 mb-1">Season</p>
                            <p className="text-lg font-semibold text-gray-900">
                                {harvestLot.season?.name || 'N/A'}
                            </p>
                        </div>
                    </div>
                </div>
            </div>

            {/* Actions */}
            <div className="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
                <div className="bg-white rounded-lg shadow p-6">
                    <h2 className="text-lg font-semibold text-gray-900 mb-4 flex items-center">
                        <Scale className="h-5 w-5 mr-2 text-green-600" />
                        Weigh Harvest
                    </h2>
                    <div className="space-y-4">
                        <div>
                            <label className="block text-sm font-medium text-gray-700 mb-2">
                                Select Scale Device
                            </label>
                            <select
                                value={selectedScaleDevice}
                                onChange={(e) => setSelectedScaleDevice(e.target.value)}
                                className="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-green-500 focus:border-green-500"
                            >
                                <option value="">Select a scale</option>
                                {scaleDevices.map((device) => (
                                    <option key={device.id} value={device.id}>
                                        {device.name}
                                    </option>
                                ))}
                            </select>
                        </div>
                        <button
                            onClick={handleWeigh}
                            disabled={!selectedScaleDevice}
                            className="w-full bg-green-600 text-white px-4 py-2 rounded-lg hover:bg-green-700 disabled:opacity-50 disabled:cursor-not-allowed"
                        >
                            Read Weight from Scale
                        </button>
                    </div>
                </div>

                <div className="bg-white rounded-lg shadow p-6">
                    <h2 className="text-lg font-semibold text-gray-900 mb-4 flex items-center">
                        <Printer className="h-5 w-5 mr-2 text-purple-600" />
                        Print Label
                    </h2>
                    <p className="text-sm text-gray-600 mb-4">
                        Print a label with traceability information for this harvest lot.
                    </p>
                    <button
                        onClick={handlePrintLabel}
                        className="w-full bg-purple-600 text-white px-4 py-2 rounded-lg hover:bg-purple-700"
                    >
                        Print Label
                    </button>
                </div>
            </div>

            {/* Weighing Records */}
            {harvestLot.weighing_records && harvestLot.weighing_records.length > 0 && (
                <div className="bg-white rounded-lg shadow">
                    <div className="px-6 py-4 border-b border-gray-200">
                        <h2 className="text-lg font-semibold text-gray-900">Weighing History</h2>
                    </div>
                    <div className="divide-y divide-gray-200">
                        {harvestLot.weighing_records.map((record) => (
                            <div key={record.id} className="px-6 py-4">
                                <div className="flex justify-between items-center">
                                    <div>
                                        <p className="font-medium text-gray-900">
                                            {record.net_weight} {record.unit}
                                        </p>
                                        <p className="text-sm text-gray-500">
                                            {new Date(record.weighed_at).toLocaleString()}
                                        </p>
                                    </div>
                                    <p className="text-sm text-gray-600">
                                        Gross: {record.gross_weight} {record.unit} | Tare: {record.tare_weight} {record.unit}
                                    </p>
                                </div>
                            </div>
                        ))}
                    </div>
                </div>
            )}
        </div>
    );
}

