import React, { useEffect, useState } from 'react';
import { useParams, Link } from 'react-router-dom';
import api from '../utils/api';
import { Tractor, MapPin, Calendar, ArrowLeft } from 'lucide-react';

export default function FarmDetail() {
    const { id } = useParams();
    const [farm, setFarm] = useState(null);
    const [loading, setLoading] = useState(true);

    useEffect(() => {
        fetchFarm();
    }, [id]);

    const fetchFarm = async () => {
        try {
            const response = await api.get(`/api/v1/farms/${id}`);
            setFarm(response.data.data || response.data);
        } catch (error) {
            console.error('Error fetching farm:', error);
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

    if (!farm) {
        return (
            <div className="text-center py-12">
                <p className="text-gray-500">Farm not found</p>
                <Link to="/farms" className="text-green-600 hover:text-green-700 mt-4 inline-block">
                    ← Back to Farms
                </Link>
            </div>
        );
    }

    return (
        <div>
            <Link
                to="/farms"
                className="flex items-center text-gray-600 hover:text-gray-900 mb-6"
            >
                <ArrowLeft className="h-5 w-5 mr-2" />
                Back to Farms
            </Link>

            <div className="bg-white rounded-lg shadow mb-6">
                <div className="px-6 py-4 border-b border-gray-200">
                    <div className="flex items-center justify-between">
                        <div className="flex items-center">
                            <div className="bg-green-100 p-3 rounded-lg">
                                <Tractor className="h-8 w-8 text-green-600" />
                            </div>
                            <div className="ml-4">
                                <h1 className="text-2xl font-bold text-gray-900">{farm.name}</h1>
                                {farm.location && (
                                    <div className="flex items-center text-gray-600 mt-1">
                                        <MapPin className="h-4 w-4 mr-1" />
                                        {farm.location}
                                    </div>
                                )}
                            </div>
                        </div>
                        {farm.is_active ? (
                            <span className="px-3 py-1 text-sm font-medium bg-green-100 text-green-800 rounded">
                                Active
                            </span>
                        ) : (
                            <span className="px-3 py-1 text-sm font-medium bg-gray-100 text-gray-800 rounded">
                                Inactive
                            </span>
                        )}
                    </div>
                </div>
                <div className="px-6 py-4">
                    {farm.description && (
                        <p className="text-gray-700 mb-4">{farm.description}</p>
                    )}
                    <div className="grid grid-cols-1 md:grid-cols-3 gap-4">
                        {farm.total_area && (
                            <div>
                                <p className="text-sm text-gray-600">Total Area</p>
                                <p className="text-lg font-semibold text-gray-900">
                                    {farm.total_area} {farm.area_unit || 'hectares'}
                                </p>
                            </div>
                        )}
                        {farm.fields && (
                            <div>
                                <p className="text-sm text-gray-600">Fields</p>
                                <p className="text-lg font-semibold text-gray-900">
                                    {farm.fields?.length || 0}
                                </p>
                            </div>
                        )}
                        {farm.seasons && (
                            <div>
                                <p className="text-sm text-gray-600">Seasons</p>
                                <p className="text-lg font-semibold text-gray-900">
                                    {farm.seasons?.length || 0}
                                </p>
                            </div>
                        )}
                    </div>
                </div>
            </div>

            {/* Related Data */}
            <div className="grid grid-cols-1 md:grid-cols-2 gap-6">
                {farm.fields && farm.fields.length > 0 && (
                    <div className="bg-white rounded-lg shadow">
                        <div className="px-6 py-4 border-b border-gray-200">
                            <h2 className="text-lg font-semibold text-gray-900">Fields</h2>
                        </div>
                        <div className="divide-y divide-gray-200">
                            {farm.fields.map((field) => (
                                <div key={field.id} className="px-6 py-4">
                                    <p className="font-medium text-gray-900">{field.name}</p>
                                    {field.area && (
                                        <p className="text-sm text-gray-600">
                                            {field.area} {field.area_unit || 'hectares'}
                                        </p>
                                    )}
                                </div>
                            ))}
                        </div>
                    </div>
                )}

                {farm.seasons && farm.seasons.length > 0 && (
                    <div className="bg-white rounded-lg shadow">
                        <div className="px-6 py-4 border-b border-gray-200">
                            <h2 className="text-lg font-semibold text-gray-900">Seasons</h2>
                        </div>
                        <div className="divide-y divide-gray-200">
                            {farm.seasons.map((season) => (
                                <div key={season.id} className="px-6 py-4">
                                    <p className="font-medium text-gray-900">{season.name}</p>
                                    <p className="text-sm text-gray-600">
                                        {new Date(season.start_date).toLocaleDateString()} - {new Date(season.end_date).toLocaleDateString()}
                                    </p>
                                </div>
                            ))}
                        </div>
                    </div>
                )}
            </div>
        </div>
    );
}

