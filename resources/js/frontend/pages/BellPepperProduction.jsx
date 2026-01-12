import React, { useEffect, useState } from 'react';
import { Link } from 'react-router-dom';
import api from '../utils/api';
import { Factory, Plus, TrendingUp, Calendar } from 'lucide-react';

export default function BellPepperProduction() {
    const [cycles, setCycles] = useState([]);
    const [farms, setFarms] = useState([]);
    const [greenhouses, setGreenhouses] = useState([]);
    const [loading, setLoading] = useState(true);
    const [showModal, setShowModal] = useState(false);
    // Calculate default end date (6 months from today)
    const getDefaultEndDate = () => {
        const today = new Date();
        const endDate = new Date(today);
        endDate.setMonth(endDate.getMonth() + 6);
        return endDate.toISOString().slice(0, 10);
    };

    const [formData, setFormData] = useState({
        farm_id: '',
        greenhouse_id: '',
        start_date: new Date().toISOString().slice(0, 10),
        expected_end_date: getDefaultEndDate(),
        expected_yield_kg: '',
        notes: '',
    });

    useEffect(() => {
        fetchData();
    }, []);

    const fetchData = async () => {
        try {
            const [cyclesRes, farmsRes] = await Promise.all([
                api.get('/api/v1/bell-pepper-cycles'),
                api.get('/api/v1/farms'),
            ]);

            const cyclesData = cyclesRes.data?.data || cyclesRes.data || [];
            const farmsData = farmsRes.data?.data || farmsRes.data || [];

            setCycles(Array.isArray(cyclesData) ? cyclesData : []);
            setFarms(Array.isArray(farmsData) ? farmsData : []);
        } catch (error) {
            console.error('Error fetching data:', error);
        } finally {
            setLoading(false);
        }
    };

    const fetchGreenhouses = async (farmId) => {
        if (!farmId) {
            setGreenhouses([]);
            return;
        }
        try {
            const response = await api.get(`/api/v1/greenhouses?farm_id=${farmId}`);
            const data = response.data?.data || response.data || [];
            setGreenhouses(Array.isArray(data) ? data : []);
        } catch (error) {
            console.error('Error fetching greenhouses:', error);
            setGreenhouses([]);
        }
    };

    const handleModalOpen = () => {
        const today = new Date();
        const todayStr = today.toISOString().slice(0, 10);
        // Calculate default end date (6 months from today)
        const defaultEndDate = new Date(today);
        defaultEndDate.setMonth(defaultEndDate.getMonth() + 6);
        const defaultEndDateStr = defaultEndDate.toISOString().slice(0, 10);
        
        setFormData({
            farm_id: '',
            greenhouse_id: '',
            start_date: todayStr,
            expected_end_date: defaultEndDateStr,
            expected_yield_kg: '',
            notes: '',
        });
        setGreenhouses([]);
        setShowModal(true);
    };

    const handleFarmChange = (farmId) => {
        setFormData({ ...formData, farm_id: farmId, greenhouse_id: '' });
        fetchGreenhouses(farmId);
    };

    const handleSubmit = async (e) => {
        e.preventDefault();
        try {
            await api.post('/api/v1/bell-pepper-cycles', formData);
            setShowModal(false);
            fetchData();
        } catch (error) {
            alert('Error creating cycle: ' + (error.response?.data?.message || 'Unknown error'));
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
                    <h1 className="text-3xl font-bold text-gray-900">Bell Pepper Production Cycles</h1>
                    <p className="mt-2 text-gray-600">Track production cycles and yield per sqm</p>
                </div>
                <button
                    onClick={handleModalOpen}
                    className="bg-green-600 text-white px-4 py-2 rounded-lg hover:bg-green-700 flex items-center"
                >
                    <Plus className="h-5 w-5 mr-2" />
                    New Cycle
                </button>
            </div>

            {cycles.length === 0 ? (
                <div className="bg-white rounded-lg shadow p-12 text-center">
                    <Factory className="h-16 w-16 mx-auto text-gray-400 mb-4" />
                    <h3 className="text-lg font-medium text-gray-900 mb-2">No cycles yet</h3>
                    <p className="text-gray-500 mb-4">Create your first production cycle to get started</p>
                    <button
                        onClick={handleModalOpen}
                        className="bg-green-600 text-white px-4 py-2 rounded-lg hover:bg-green-700"
                    >
                        Create Cycle
                    </button>
                </div>
            ) : (
                <div className="bg-white rounded-lg shadow overflow-hidden">
                    <div className="overflow-x-auto">
                        <table className="min-w-full divide-y divide-gray-200">
                            <thead className="bg-gray-50">
                                <tr>
                                    <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Cycle Code</th>
                                    <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Greenhouse</th>
                                    <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Start Date</th>
                                    <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Expected Yield</th>
                                    <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Actual Yield</th>
                                    <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Yield/Sqm</th>
                                    <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Variance</th>
                                    <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Status</th>
                                </tr>
                            </thead>
                            <tbody className="bg-white divide-y divide-gray-200">
                                {cycles.map((cycle) => (
                                    <tr key={cycle.id} className="hover:bg-gray-50">
                                        <td className="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
                                            <Link to={`/bell-pepper-cycles/${cycle.id}`} className="text-green-600 hover:text-green-700">
                                                {cycle.cycle_code}
                                            </Link>
                                        </td>
                                        <td className="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                            {cycle.greenhouse?.name || 'N/A'}
                                        </td>
                                        <td className="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                            {new Date(cycle.start_date).toLocaleDateString()}
                                        </td>
                                        <td className="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                            {Number(cycle.expected_yield_kg || 0).toFixed(2)} kg
                                        </td>
                                        <td className="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                            {Number(cycle.actual_yield_kg || 0).toFixed(2)} kg
                                        </td>
                                        <td className="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                            {Number(cycle.actual_yield_per_sqm || 0).toFixed(2)} kg/sqm
                                        </td>
                                        <td className="px-6 py-4 whitespace-nowrap text-sm">
                                            <span className={`${
                                                Number(cycle.yield_variance_percent || 0) >= 0 
                                                    ? 'text-green-600' 
                                                    : 'text-red-600'
                                            }`}>
                                                {Number(cycle.yield_variance_percent || 0).toFixed(1)}%
                                            </span>
                                        </td>
                                        <td className="px-6 py-4 whitespace-nowrap">
                                            <span className={`px-2 py-1 text-xs font-medium rounded ${
                                                cycle.status === 'COMPLETED' ? 'bg-green-100 text-green-800' :
                                                cycle.status === 'IN_PROGRESS' ? 'bg-blue-100 text-blue-800' :
                                                'bg-gray-100 text-gray-800'
                                            }`}>
                                                {cycle.status}
                                            </span>
                                        </td>
                                    </tr>
                                ))}
                            </tbody>
                        </table>
                    </div>
                </div>
            )}

            {/* Create Cycle Modal */}
            {showModal && (
                <div className="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50 p-4 overflow-y-auto">
                    <div className="bg-white rounded-lg max-w-2xl w-full p-6 max-h-[90vh] overflow-y-auto">
                        <h2 className="text-xl font-bold mb-4">Create New Production Cycle</h2>
                        <form onSubmit={handleSubmit} className="space-y-4">
                            <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
                                <div>
                                    <label className="block text-sm font-medium text-gray-700 mb-1">Farm *</label>
                                    <select
                                        required
                                        value={formData.farm_id}
                                        onChange={(e) => handleFarmChange(e.target.value)}
                                        className="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-green-500 focus:border-green-500"
                                    >
                                        <option value="">Select farm</option>
                                        {farms.map((farm) => (
                                            <option key={farm.id} value={farm.id}>{farm.name}</option>
                                        ))}
                                    </select>
                                </div>
                                <div>
                                    <label className="block text-sm font-medium text-gray-700 mb-1">Greenhouse *</label>
                                    <select
                                        required
                                        disabled={!formData.farm_id || greenhouses.length === 0}
                                        value={formData.greenhouse_id}
                                        onChange={(e) => setFormData({ ...formData, greenhouse_id: e.target.value })}
                                        className={`w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-green-500 focus:border-green-500 ${
                                            !formData.farm_id || greenhouses.length === 0
                                                ? 'bg-gray-100 text-gray-500 cursor-not-allowed'
                                                : 'bg-white'
                                        }`}
                                    >
                                        <option value="">
                                            {!formData.farm_id 
                                                ? 'Select farm first' 
                                                : greenhouses.length === 0
                                                    ? 'No greenhouses available'
                                                    : 'Select greenhouse'}
                                        </option>
                                        {greenhouses.map((gh) => (
                                            <option key={gh.id} value={gh.id}>
                                                {gh.name} ({gh.size_sqm} sqm)
                                            </option>
                                        ))}
                                    </select>
                                </div>
                                <div>
                                    <label className="block text-sm font-medium text-gray-700 mb-1">Start Date *</label>
                                    <input
                                        type="date"
                                        required
                                        value={formData.start_date}
                                        onChange={(e) => {
                                            const startDate = e.target.value;
                                            let expectedEndDate = '';
                                            if (startDate) {
                                                const start = new Date(startDate);
                                                // Add exactly 6 months
                                                const end = new Date(start);
                                                end.setMonth(end.getMonth() + 6);
                                                expectedEndDate = end.toISOString().slice(0, 10);
                                            }
                                            setFormData({ 
                                                ...formData, 
                                                start_date: startDate,
                                                expected_end_date: expectedEndDate
                                            });
                                        }}
                                        className="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-green-500 focus:border-green-500"
                                    />
                                </div>
                                <div>
                                    <label className="block text-sm font-medium text-gray-700 mb-1">Expected End Date *</label>
                                    <input
                                        type="date"
                                        required
                                        value={formData.expected_end_date}
                                        onChange={(e) => setFormData({ ...formData, expected_end_date: e.target.value })}
                                        className="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-green-500 focus:border-green-500"
                                    />
                                    <p className="text-xs text-gray-500 mt-1">6 months from start date</p>
                                </div>
                                <div>
                                    <label className="block text-sm font-medium text-gray-700 mb-1">Expected Yield (kg) *</label>
                                    <input
                                        type="number"
                                        step="0.01"
                                        required
                                        value={formData.expected_yield_kg}
                                        onChange={(e) => setFormData({ ...formData, expected_yield_kg: e.target.value })}
                                        className="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-green-500 focus:border-green-500"
                                    />
                                </div>
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
                                    Create Cycle
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            )}
        </div>
    );
}

