import React, { useEffect, useState } from 'react';
import { Link } from 'react-router-dom';
import api from '../utils/api';
import { Tractor, Plus, MapPin, Calendar } from 'lucide-react';

export default function Farms() {
    const [farms, setFarms] = useState([]);
    const [loading, setLoading] = useState(true);
    const [showModal, setShowModal] = useState(false);
    const [formData, setFormData] = useState({
        name: '',
        location: '',
        description: '',
        total_area: '',
        area_unit: 'hectares',
        is_active: true,
    });

    useEffect(() => {
        fetchFarms();
    }, []);

    const fetchFarms = async () => {
        try {
            const response = await api.get('/api/v1/farms');
            setFarms(response.data.data || response.data);
        } catch (error) {
            console.error('Error fetching farms:', error);
        } finally {
            setLoading(false);
        }
    };

    const handleSubmit = async (e) => {
        e.preventDefault();
        try {
            await api.post('/api/v1/farms', formData);
            setShowModal(false);
            setFormData({
                name: '',
                location: '',
                description: '',
                total_area: '',
                area_unit: 'hectares',
                is_active: true,
            });
            fetchFarms();
        } catch (error) {
            alert('Error creating farm: ' + (error.response?.data?.message || 'Unknown error'));
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
                    <h1 className="text-3xl font-bold text-gray-900">Farms</h1>
                    <p className="mt-2 text-gray-600">Manage your farm locations</p>
                </div>
                <button
                    onClick={() => setShowModal(true)}
                    className="bg-green-600 text-white px-4 py-2 rounded-lg hover:bg-green-700 flex items-center"
                >
                    <Plus className="h-5 w-5 mr-2" />
                    Add Farm
                </button>
            </div>

            {farms.length === 0 ? (
                <div className="bg-white rounded-lg shadow p-12 text-center">
                    <Tractor className="h-16 w-16 mx-auto text-gray-400 mb-4" />
                    <h3 className="text-lg font-medium text-gray-900 mb-2">No farms yet</h3>
                    <p className="text-gray-500 mb-4">Get started by creating your first farm</p>
                    <button
                        onClick={() => setShowModal(true)}
                        className="bg-green-600 text-white px-4 py-2 rounded-lg hover:bg-green-700"
                    >
                        Create Farm
                    </button>
                </div>
            ) : (
                <div className="grid grid-cols-1 gap-6 md:grid-cols-2 lg:grid-cols-3">
                    {farms.map((farm) => (
                        <Link
                            key={farm.id}
                            to={`/farms/${farm.id}`}
                            className="bg-white rounded-lg shadow p-6 hover:shadow-lg transition-shadow"
                        >
                            <div className="flex items-start justify-between mb-4">
                                <div className="flex items-center">
                                    <div className="bg-green-100 p-2 rounded-lg">
                                        <Tractor className="h-6 w-6 text-green-600" />
                                    </div>
                                    <h3 className="ml-3 text-lg font-semibold text-gray-900">
                                        {farm.name}
                                    </h3>
                                </div>
                                {farm.is_active ? (
                                    <span className="px-2 py-1 text-xs font-medium bg-green-100 text-green-800 rounded">
                                        Active
                                    </span>
                                ) : (
                                    <span className="px-2 py-1 text-xs font-medium bg-gray-100 text-gray-800 rounded">
                                        Inactive
                                    </span>
                                )}
                            </div>
                            {farm.location && (
                                <div className="flex items-center text-sm text-gray-600 mb-2">
                                    <MapPin className="h-4 w-4 mr-1" />
                                    {farm.location}
                                </div>
                            )}
                            {farm.total_area && (
                                <div className="text-sm text-gray-600 mb-2">
                                    <strong>{farm.total_area}</strong> {farm.area_unit}
                                </div>
                            )}
                            {farm.description && (
                                <p className="text-sm text-gray-600 line-clamp-2">
                                    {farm.description}
                                </p>
                            )}
                        </Link>
                    ))}
                </div>
            )}

            {/* Create Farm Modal */}
            {showModal && (
                <div className="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50 p-4">
                    <div className="bg-white rounded-lg max-w-md w-full p-6">
                        <h2 className="text-xl font-bold mb-4">Create New Farm</h2>
                        <form onSubmit={handleSubmit}>
                            <div className="space-y-4">
                                <div>
                                    <label className="block text-sm font-medium text-gray-700 mb-1">
                                        Farm Name *
                                    </label>
                                    <input
                                        type="text"
                                        required
                                        value={formData.name}
                                        onChange={(e) => setFormData({ ...formData, name: e.target.value })}
                                        className="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-green-500 focus:border-green-500"
                                    />
                                </div>
                                <div>
                                    <label className="block text-sm font-medium text-gray-700 mb-1">
                                        Location
                                    </label>
                                    <input
                                        type="text"
                                        value={formData.location}
                                        onChange={(e) => setFormData({ ...formData, location: e.target.value })}
                                        className="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-green-500 focus:border-green-500"
                                    />
                                </div>
                                <div>
                                    <label className="block text-sm font-medium text-gray-700 mb-1">
                                        Description
                                    </label>
                                    <textarea
                                        value={formData.description}
                                        onChange={(e) => setFormData({ ...formData, description: e.target.value })}
                                        rows={3}
                                        className="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-green-500 focus:border-green-500"
                                    />
                                </div>
                                <div className="grid grid-cols-2 gap-4">
                                    <div>
                                        <label className="block text-sm font-medium text-gray-700 mb-1">
                                            Total Area
                                        </label>
                                        <input
                                            type="number"
                                            step="0.01"
                                            value={formData.total_area}
                                            onChange={(e) => setFormData({ ...formData, total_area: e.target.value })}
                                            className="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-green-500 focus:border-green-500"
                                        />
                                    </div>
                                    <div>
                                        <label className="block text-sm font-medium text-gray-700 mb-1">
                                            Unit
                                        </label>
                                        <select
                                            value={formData.area_unit}
                                            onChange={(e) => setFormData({ ...formData, area_unit: e.target.value })}
                                            className="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-green-500 focus:border-green-500"
                                        >
                                            <option value="hectares">Hectares</option>
                                            <option value="acres">Acres</option>
                                            <option value="square_meters">Square Meters</option>
                                        </select>
                                    </div>
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
                                    Create Farm
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            )}
        </div>
    );
}

