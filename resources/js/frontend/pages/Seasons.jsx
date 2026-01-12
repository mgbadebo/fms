import React, { useEffect, useState } from 'react';
import api from '../utils/api';
import { Calendar, Plus, Edit, Trash2, X } from 'lucide-react';

export default function Seasons() {
    const [seasons, setSeasons] = useState([]);
    const [farms, setFarms] = useState([]);
    const [loading, setLoading] = useState(true);
    const [showModal, setShowModal] = useState(false);
    const [editingSeason, setEditingSeason] = useState(null);
    const [formData, setFormData] = useState({
        farm_id: '',
        season_number: '',
        name: '',
        start_date: '',
        end_date: '',
        status: 'PLANNED',
        notes: '',
    });

    useEffect(() => {
        fetchData();
    }, []);

    // Auto-generate season name from season_number and start_date year
    useEffect(() => {
        if (formData.season_number && formData.start_date) {
            const year = new Date(formData.start_date).getFullYear();
            const autoName = `${formData.season_number} - ${year}`;
            setFormData(prev => ({ ...prev, name: autoName }));
        }
    }, [formData.season_number, formData.start_date]);

    const fetchData = async () => {
        try {
            const [seasonsRes, farmsRes] = await Promise.all([
                api.get('/api/v1/seasons?per_page=1000'),
                api.get('/api/v1/farms?per_page=1000')
            ]);
            
            const seasonsData = seasonsRes.data?.data || seasonsRes.data || [];
            const farmsData = farmsRes.data?.data || farmsRes.data || [];
            
            setSeasons(Array.isArray(seasonsData) ? seasonsData : []);
            setFarms(Array.isArray(farmsData) ? farmsData : []);
        } catch (error) {
            console.error('Error fetching data:', error);
            alert('Error loading data: ' + (error.response?.data?.message || error.message));
        } finally {
            setLoading(false);
        }
    };

    const handleModalOpen = () => {
        setEditingSeason(null);
        setFormData({
            farm_id: '',
            season_number: '',
            name: '',
            start_date: '',
            end_date: '',
            status: 'PLANNED',
            notes: '',
        });
        setShowModal(true);
    };

    const handleEdit = (season) => {
        setEditingSeason(season);
        // Try to extract season number from existing name (e.g., "Season 1 - 2026" -> "Season 1")
        let seasonNumber = '';
        if (season.name) {
            const match = season.name.match(/^(Season\s+[12])/i);
            if (match) {
                seasonNumber = match[1];
            }
        }
        setFormData({
            farm_id: season.farm_id || '',
            season_number: seasonNumber,
            name: season.name || '',
            start_date: season.start_date ? season.start_date.split('T')[0] : '',
            end_date: season.end_date ? season.end_date.split('T')[0] : '',
            status: season.status || 'PLANNED',
            notes: season.notes || '',
        });
        setShowModal(true);
    };

    const handleSubmit = async (e) => {
        e.preventDefault();
        try {
            if (editingSeason) {
                await api.patch(`/api/v1/seasons/${editingSeason.id}`, formData);
            } else {
                await api.post('/api/v1/seasons', formData);
            }
            setShowModal(false);
            fetchData();
        } catch (error) {
            const errorMsg = error.response?.data?.message || error.response?.data?.errors || 'Unknown error';
            alert('Error saving season: ' + (typeof errorMsg === 'string' ? errorMsg : JSON.stringify(errorMsg)));
        }
    };

    const handleDelete = async (seasonId) => {
        if (!confirm('Delete this season?')) return;
        try {
            await api.delete(`/api/v1/seasons/${seasonId}`);
            fetchData();
        } catch (error) {
            alert('Error deleting season: ' + (error.response?.data?.message || error.message));
        }
    };

    const formatDateRange = (startDate, endDate) => {
        if (!startDate || !endDate) return 'N/A';
        const start = new Date(startDate).toLocaleDateString();
        const end = new Date(endDate).toLocaleDateString();
        return `${start} - ${end}`;
    };

    const getStatusColor = (status) => {
        const colors = {
            PLANNED: 'bg-blue-100 text-blue-800',
            ACTIVE: 'bg-green-100 text-green-800',
            COMPLETED: 'bg-gray-100 text-gray-800',
            CANCELLED: 'bg-red-100 text-red-800',
        };
        return colors[status] || 'bg-gray-100 text-gray-800';
    };

    const getFarmName = (farmId) => {
        const farm = farms.find(f => f.id === farmId);
        return farm ? farm.name : 'N/A';
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
            <div className="mb-8 flex justify-between items-center">
                <div>
                    <h1 className="text-3xl font-bold text-gray-900">Seasons</h1>
                    <p className="mt-2 text-gray-600">Manage farming seasons</p>
                </div>
                <button
                    onClick={handleModalOpen}
                    className="flex items-center px-4 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700"
                >
                    <Plus className="h-5 w-5 mr-2" />
                    New Season
                </button>
            </div>

            {/* Tip */}
            <div className="mb-4 bg-blue-50 border border-blue-200 rounded-lg p-4">
                <p className="text-sm text-blue-800">
                    <strong>Tip:</strong> Typically, there are 2 seasons within 12 months. Note that the two seasons may not fall in the same calendar year.
                </p>
            </div>

            {/* Seasons Table */}
            <div className="bg-white rounded-lg shadow overflow-hidden">
                <table className="min-w-full divide-y divide-gray-200">
                    <thead className="bg-gray-50">
                        <tr>
                            <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Name</th>
                            <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Farm</th>
                            <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Date Range</th>
                            <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                            <th className="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                        </tr>
                    </thead>
                    <tbody className="bg-white divide-y divide-gray-200">
                        {seasons.length === 0 ? (
                            <tr>
                                <td colSpan="5" className="px-6 py-4 text-center text-gray-500">
                                    No seasons found
                                </td>
                            </tr>
                        ) : (
                            seasons.map((season) => (
                                <tr key={season.id}>
                                    <td className="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
                                        {season.name}
                                    </td>
                                    <td className="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                        {getFarmName(season.farm_id)}
                                    </td>
                                    <td className="px-6 py-4 text-sm text-gray-500">
                                        {formatDateRange(season.start_date, season.end_date)}
                                    </td>
                                    <td className="px-6 py-4 whitespace-nowrap">
                                        <span className={`px-2 py-1 text-xs font-semibold rounded-full ${getStatusColor(season.status)}`}>
                                            {season.status || 'N/A'}
                                        </span>
                                    </td>
                                    <td className="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                        <div className="flex justify-end space-x-2">
                                            <button
                                                onClick={() => handleEdit(season)}
                                                className="text-blue-600 hover:text-blue-900"
                                                title="Edit"
                                            >
                                                <Edit className="h-4 w-4" />
                                            </button>
                                            <button
                                                onClick={() => handleDelete(season.id)}
                                                className="text-red-600 hover:text-red-900"
                                                title="Delete"
                                            >
                                                <Trash2 className="h-4 w-4" />
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                            ))
                        )}
                    </tbody>
                </table>
            </div>

            {/* Modal */}
            {showModal && (
                <div className="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50 p-4">
                    <div className="bg-white rounded-lg max-w-2xl w-full max-h-[90vh] overflow-y-auto">
                        <div className="sticky top-0 bg-white border-b border-gray-200 px-6 py-4 flex justify-between items-center">
                            <h2 className="text-xl font-bold text-gray-900">
                                {editingSeason ? 'Edit Season' : 'New Season'}
                            </h2>
                            <button
                                onClick={() => setShowModal(false)}
                                className="text-gray-400 hover:text-gray-600"
                            >
                                <X className="h-6 w-6" />
                            </button>
                        </div>
                        <form onSubmit={handleSubmit} className="p-6 space-y-4">
                            <div className="grid grid-cols-2 gap-4">
                                <div>
                                    <label className="block text-sm font-medium text-gray-700 mb-1">
                                        Farm <span className="text-red-500">*</span>
                                    </label>
                                    <select
                                        required
                                        value={formData.farm_id}
                                        onChange={(e) => setFormData({ ...formData, farm_id: e.target.value })}
                                        disabled={!!editingSeason}
                                        className={`w-full px-3 py-2 border border-gray-300 rounded-lg ${editingSeason ? 'bg-gray-100 cursor-not-allowed' : ''}`}
                                    >
                                        <option value="">Select Farm</option>
                                        {farms.map((farm) => (
                                            <option key={farm.id} value={farm.id}>
                                                {farm.name}
                                            </option>
                                        ))}
                                    </select>
                                    {editingSeason && (
                                        <p className="mt-1 text-xs text-gray-500">Farm cannot be changed after creation</p>
                                    )}
                                </div>
                                <div>
                                    <label className="block text-sm font-medium text-gray-700 mb-1">
                                        Season Number <span className="text-red-500">*</span>
                                    </label>
                                    <select
                                        required
                                        value={formData.season_number}
                                        onChange={(e) => setFormData({ ...formData, season_number: e.target.value })}
                                        className="w-full px-3 py-2 border border-gray-300 rounded-lg"
                                    >
                                        <option value="">Select Season</option>
                                        <option value="Season 1">Season 1</option>
                                        <option value="Season 2">Season 2</option>
                                    </select>
                                </div>
                                <div>
                                    <label className="block text-sm font-medium text-gray-700 mb-1">
                                        Season Name <span className="text-red-500">*</span>
                                    </label>
                                    <input
                                        type="text"
                                        required
                                        value={formData.name}
                                        onChange={(e) => setFormData({ ...formData, name: e.target.value })}
                                        className="w-full px-3 py-2 border border-gray-300 rounded-lg bg-gray-50"
                                        placeholder="Auto-generated from Season Number and Start Date"
                                        readOnly
                                    />
                                    <p className="mt-1 text-xs text-gray-500">Auto-generated from Season Number and Start Date year</p>
                                </div>
                                <div>
                                    <label className="block text-sm font-medium text-gray-700 mb-1">
                                        Start Date <span className="text-red-500">*</span>
                                    </label>
                                    <input
                                        type="date"
                                        required
                                        value={formData.start_date}
                                        onChange={(e) => setFormData({ ...formData, start_date: e.target.value })}
                                        className="w-full px-3 py-2 border border-gray-300 rounded-lg"
                                    />
                                </div>
                                <div>
                                    <label className="block text-sm font-medium text-gray-700 mb-1">
                                        End Date <span className="text-red-500">*</span>
                                    </label>
                                    <input
                                        type="date"
                                        required
                                        value={formData.end_date}
                                        min={formData.start_date || undefined}
                                        onChange={(e) => setFormData({ ...formData, end_date: e.target.value })}
                                        className="w-full px-3 py-2 border border-gray-300 rounded-lg"
                                    />
                                </div>
                                <div>
                                    <label className="block text-sm font-medium text-gray-700 mb-1">
                                        Status <span className="text-red-500">*</span>
                                    </label>
                                    <select
                                        required
                                        value={formData.status}
                                        onChange={(e) => setFormData({ ...formData, status: e.target.value })}
                                        className="w-full px-3 py-2 border border-gray-300 rounded-lg"
                                    >
                                        <option value="PLANNED">Planned</option>
                                        <option value="ACTIVE">Active</option>
                                        <option value="COMPLETED">Completed</option>
                                        <option value="CANCELLED">Cancelled</option>
                                    </select>
                                </div>
                            </div>
                            <div>
                                <label className="block text-sm font-medium text-gray-700 mb-1">
                                    Notes
                                </label>
                                <textarea
                                    value={formData.notes}
                                    onChange={(e) => setFormData({ ...formData, notes: e.target.value })}
                                    rows="3"
                                    className="w-full px-3 py-2 border border-gray-300 rounded-lg"
                                    placeholder="Optional notes about this season"
                                />
                            </div>
                            <div className="flex justify-end space-x-4 pt-4 border-t">
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
                                    {editingSeason ? 'Update' : 'Create'} Season
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            )}
        </div>
    );
}
