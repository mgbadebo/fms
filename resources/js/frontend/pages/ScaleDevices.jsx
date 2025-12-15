import React, { useEffect, useState } from 'react';
import api from '../utils/api';
import { Scale, Plus, CheckCircle, XCircle } from 'lucide-react';

export default function ScaleDevices() {
    const [devices, setDevices] = useState([]);
    const [loading, setLoading] = useState(true);
    const [showModal, setShowModal] = useState(false);
    const [formData, setFormData] = useState({
        name: '',
        connection_type: 'MOCK',
        is_active: true,
    });

    useEffect(() => {
        fetchDevices();
    }, []);

    const fetchDevices = async () => {
        try {
            const response = await api.get('/api/v1/scale-devices');
            setDevices(response.data.data || response.data);
        } catch (error) {
            console.error('Error fetching devices:', error);
        } finally {
            setLoading(false);
        }
    };

    const handleSubmit = async (e) => {
        e.preventDefault();
        try {
            await api.post('/api/v1/scale-devices', formData);
            setShowModal(false);
            setFormData({ name: '', connection_type: 'MOCK', is_active: true });
            fetchDevices();
        } catch (error) {
            alert('Error creating device: ' + (error.response?.data?.message || 'Unknown error'));
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
                    <h1 className="text-3xl font-bold text-gray-900">Scale Devices</h1>
                    <p className="mt-2 text-gray-600">Manage your weighing scale devices</p>
                </div>
                <button
                    onClick={() => setShowModal(true)}
                    className="bg-green-600 text-white px-4 py-2 rounded-lg hover:bg-green-700 flex items-center"
                >
                    <Plus className="h-5 w-5 mr-2" />
                    Add Device
                </button>
            </div>

            {devices.length === 0 ? (
                <div className="bg-white rounded-lg shadow p-12 text-center">
                    <Scale className="h-16 w-16 mx-auto text-gray-400 mb-4" />
                    <h3 className="text-lg font-medium text-gray-900 mb-2">No scale devices yet</h3>
                    <p className="text-gray-500 mb-4">Add your first scale device to get started</p>
                    <button
                        onClick={() => setShowModal(true)}
                        className="bg-green-600 text-white px-4 py-2 rounded-lg hover:bg-green-700"
                    >
                        Add Device
                    </button>
                </div>
            ) : (
                <div className="grid grid-cols-1 gap-6 md:grid-cols-2 lg:grid-cols-3">
                    {devices.map((device) => (
                        <div key={device.id} className="bg-white rounded-lg shadow p-6">
                            <div className="flex items-start justify-between mb-4">
                                <div className="flex items-center">
                                    <div className="bg-purple-100 p-2 rounded-lg">
                                        <Scale className="h-6 w-6 text-purple-600" />
                                    </div>
                                    <h3 className="ml-3 text-lg font-semibold text-gray-900">
                                        {device.name}
                                    </h3>
                                </div>
                                {device.is_active ? (
                                    <CheckCircle className="h-5 w-5 text-green-500" />
                                ) : (
                                    <XCircle className="h-5 w-5 text-gray-400" />
                                )}
                            </div>
                            <div className="space-y-2">
                                <div>
                                    <p className="text-xs text-gray-500">Connection Type</p>
                                    <p className="text-sm font-medium text-gray-900">
                                        {device.connection_type}
                                    </p>
                                </div>
                                {device.last_calibration_date && (
                                    <div>
                                        <p className="text-xs text-gray-500">Last Calibration</p>
                                        <p className="text-sm text-gray-900">
                                            {new Date(device.last_calibration_date).toLocaleDateString()}
                                        </p>
                                    </div>
                                )}
                            </div>
                        </div>
                    ))}
                </div>
            )}

            {/* Create Device Modal */}
            {showModal && (
                <div className="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50 p-4">
                    <div className="bg-white rounded-lg max-w-md w-full p-6">
                        <h2 className="text-xl font-bold mb-4">Add Scale Device</h2>
                        <form onSubmit={handleSubmit}>
                            <div className="space-y-4">
                                <div>
                                    <label className="block text-sm font-medium text-gray-700 mb-1">
                                        Device Name *
                                    </label>
                                    <input
                                        type="text"
                                        required
                                        value={formData.name}
                                        onChange={(e) => setFormData({ ...formData, name: e.target.value })}
                                        className="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-green-500 focus:border-green-500"
                                        placeholder="e.g., Main Weighing Scale"
                                    />
                                </div>
                                <div>
                                    <label className="block text-sm font-medium text-gray-700 mb-1">
                                        Connection Type
                                    </label>
                                    <select
                                        value={formData.connection_type}
                                        onChange={(e) => setFormData({ ...formData, connection_type: e.target.value })}
                                        className="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-green-500 focus:border-green-500"
                                    >
                                        <option value="MOCK">Mock (Testing)</option>
                                        <option value="SERIAL">Serial Port</option>
                                        <option value="USB">USB</option>
                                        <option value="NETWORK">Network</option>
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
                                    className="px-4 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700"
                                >
                                    Add Device
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            )}
        </div>
    );
}

