import React, { useEffect, useState } from 'react';
import api from '../utils/api';
import { AlertTriangle, Plus } from 'lucide-react';

export default function GariWasteLosses() {
    const [losses, setLosses] = useState([]);
    const [farms, setFarms] = useState([]);
    const [loading, setLoading] = useState(true);
    const [showModal, setShowModal] = useState(false);
    const [formData, setFormData] = useState({
        farm_id: '',
        loss_date: new Date().toISOString().slice(0, 10),
        loss_type: 'SPOILAGE',
        gari_type: 'WHITE',
        packaging_type: '1KG_POUCH',
        quantity_kg: '',
        cost_per_kg: '',
        description: '',
        notes: '',
    });

    useEffect(() => {
        fetchData();
    }, []);

    const fetchData = async () => {
        try {
            const [lossesRes, farmsRes] = await Promise.all([
                api.get('/api/v1/gari-waste-losses'),
                api.get('/api/v1/farms'),
            ]);
            setLosses(lossesRes.data.data || lossesRes.data);
            setFarms(farmsRes.data.data || farmsRes.data);
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
            await api.post('/api/v1/gari-waste-losses', formData);
            setShowModal(false);
            fetchData();
        } catch (error) {
            alert('Error creating loss record: ' + (error.response?.data?.message || 'Unknown error'));
        }
    };

    if (loading) {
        return (
            <div className="flex items-center justify-center h-64">
                <div className="animate-spin rounded-full h-12 w-12 border-b-2 border-green-600"></div>
            </div>
        );
    }

    const totalLossValue = losses.reduce((sum, loss) => sum + (loss.total_loss_value || 0), 0);

    return (
        <div>
            <div className="flex justify-between items-center mb-8">
                <div>
                    <h1 className="text-3xl font-bold text-gray-900">Waste & Losses</h1>
                    <p className="mt-2 text-gray-600">Track waste and losses in gari production</p>
                </div>
                <button
                    onClick={handleModalOpen}
                    className="bg-red-600 text-white px-4 py-2 rounded-lg hover:bg-red-700 flex items-center"
                >
                    <Plus className="h-5 w-5 mr-2" />
                    Record Loss
                </button>
            </div>

            {/* Summary */}
            <div className="bg-white rounded-lg shadow p-6 mb-6">
                <p className="text-sm text-gray-600 mb-1">Total Loss Value</p>
                <p className="text-2xl font-bold text-red-600">₦{totalLossValue.toFixed(2)}</p>
            </div>

            {losses.length === 0 ? (
                <div className="bg-white rounded-lg shadow p-12 text-center">
                    <AlertTriangle className="h-16 w-16 mx-auto text-gray-400 mb-4" />
                    <h3 className="text-lg font-medium text-gray-900 mb-2">No waste/loss records yet</h3>
                    <p className="text-gray-500 mb-4">Record waste and losses to track efficiency</p>
                    <button
                        onClick={handleModalOpen}
                        className="bg-red-600 text-white px-4 py-2 rounded-lg hover:bg-red-700"
                    >
                        Record Loss
                    </button>
                </div>
            ) : (
                <div className="bg-white rounded-lg shadow overflow-hidden">
                    <div className="overflow-x-auto">
                        <table className="min-w-full divide-y divide-gray-200">
                            <thead className="bg-gray-50">
                                <tr>
                                    <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Date</th>
                                    <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Loss Type</th>
                                    <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Product</th>
                                    <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Quantity</th>
                                    <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Loss Value</th>
                                    <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Description</th>
                                </tr>
                            </thead>
                            <tbody className="bg-white divide-y divide-gray-200">
                                {losses.map((loss) => (
                                    <tr key={loss.id} className="hover:bg-gray-50">
                                        <td className="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                            {new Date(loss.loss_date).toLocaleDateString()}
                                        </td>
                                        <td className="px-6 py-4 whitespace-nowrap">
                                            <span className="px-2 py-1 text-xs font-medium bg-red-100 text-red-800 rounded">
                                                {loss.loss_type.replace('_', ' ')}
                                            </span>
                                        </td>
                                        <td className="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                            {loss.gari_type} • {loss.packaging_type?.replace('_', ' ')}
                                        </td>
                                        <td className="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                            {loss.quantity_kg} kg
                                        </td>
                                        <td className="px-6 py-4 whitespace-nowrap text-sm font-medium text-red-600">
                                            ₦{loss.total_loss_value?.toFixed(2) || '0.00'}
                                        </td>
                                        <td className="px-6 py-4 text-sm text-gray-900">
                                            {loss.description || '-'}
                                        </td>
                                    </tr>
                                ))}
                            </tbody>
                        </table>
                    </div>
                </div>
            )}

            {/* Create Loss Modal */}
            {showModal && (
                <div className="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50 p-4">
                    <div className="bg-white rounded-lg max-w-md w-full p-6">
                        <h2 className="text-xl font-bold mb-4">Record Waste/Loss</h2>
                        <form onSubmit={handleSubmit} className="space-y-4">
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
                            <div className="grid grid-cols-2 gap-4">
                                <div>
                                    <label className="block text-sm font-medium text-gray-700 mb-1">Loss Date *</label>
                                    <input
                                        type="date"
                                        required
                                        value={formData.loss_date}
                                        onChange={(e) => setFormData({ ...formData, loss_date: e.target.value })}
                                        className="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-green-500 focus:border-green-500"
                                    />
                                </div>
                                <div>
                                    <label className="block text-sm font-medium text-gray-700 mb-1">Loss Type *</label>
                                    <select
                                        required
                                        value={formData.loss_type}
                                        onChange={(e) => setFormData({ ...formData, loss_type: e.target.value })}
                                        className="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-green-500 focus:border-green-500"
                                    >
                                        <option value="SPOILAGE">Spoilage</option>
                                        <option value="MOISTURE_DAMAGE">Moisture Damage</option>
                                        <option value="SPILLAGE">Spillage</option>
                                        <option value="REJECTED_BATCH">Rejected Batch</option>
                                        <option value="CUSTOMER_RETURN">Customer Return</option>
                                        <option value="THEFT">Theft</option>
                                        <option value="OTHER">Other</option>
                                    </select>
                                </div>
                            </div>
                            <div className="grid grid-cols-2 gap-4">
                                <div>
                                    <label className="block text-sm font-medium text-gray-700 mb-1">Gari Type</label>
                                    <select
                                        value={formData.gari_type}
                                        onChange={(e) => setFormData({ ...formData, gari_type: e.target.value })}
                                        className="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-green-500 focus:border-green-500"
                                    >
                                        <option value="WHITE">White</option>
                                        <option value="YELLOW">Yellow</option>
                                    </select>
                                </div>
                                <div>
                                    <label className="block text-sm font-medium text-gray-700 mb-1">Packaging</label>
                                    <select
                                        value={formData.packaging_type}
                                        onChange={(e) => setFormData({ ...formData, packaging_type: e.target.value })}
                                        className="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-green-500 focus:border-green-500"
                                    >
                                        <option value="1KG_POUCH">1kg Pouch</option>
                                        <option value="2KG_POUCH">2kg Pouch</option>
                                        <option value="5KG_PACK">5kg Pack</option>
                                        <option value="50KG_BAG">50kg Bag</option>
                                        <option value="BULK">Bulk</option>
                                    </select>
                                </div>
                            </div>
                            <div className="grid grid-cols-2 gap-4">
                                <div>
                                    <label className="block text-sm font-medium text-gray-700 mb-1">Quantity (kg) *</label>
                                    <input
                                        type="number"
                                        step="0.01"
                                        required
                                        value={formData.quantity_kg}
                                        onChange={(e) => setFormData({ ...formData, quantity_kg: e.target.value })}
                                        className="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-green-500 focus:border-green-500"
                                    />
                                </div>
                                <div>
                                    <label className="block text-sm font-medium text-gray-700 mb-1">Cost per kg (₦)</label>
                                    <input
                                        type="number"
                                        step="0.01"
                                        value={formData.cost_per_kg}
                                        onChange={(e) => setFormData({ ...formData, cost_per_kg: e.target.value })}
                                        className="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-green-500 focus:border-green-500"
                                    />
                                </div>
                            </div>
                            <div>
                                <label className="block text-sm font-medium text-gray-700 mb-1">Description</label>
                                <textarea
                                    value={formData.description}
                                    onChange={(e) => setFormData({ ...formData, description: e.target.value })}
                                    rows={3}
                                    className="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-green-500 focus:border-green-500"
                                    placeholder="Describe the loss..."
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
                                    className="px-4 py-2 bg-red-600 text-white rounded-lg hover:bg-red-700"
                                >
                                    Record Loss
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            )}
        </div>
    );
}

