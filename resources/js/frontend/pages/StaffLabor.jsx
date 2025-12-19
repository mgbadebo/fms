import React, { useEffect, useState } from 'react';
import api from '../utils/api';
import { Users, Plus, Clock, Calendar } from 'lucide-react';

export default function StaffLabor() {
    const [staff, setStaff] = useState([]);
    const [allocations, setAllocations] = useState([]);
    const [loading, setLoading] = useState(true);
    const [showModal, setShowModal] = useState(false);
    const [formData, setFormData] = useState({
        staff_id: '',
        crop_type: 'GARI',
        activity_type: 'PRODUCTION',
        farm_id: '',
        hours: '',
        date: new Date().toISOString().slice(0, 10),
        notes: '',
    });

    useEffect(() => {
        fetchData();
    }, []);

    const fetchData = async () => {
        try {
            // TODO: Replace with actual API endpoints when backend is ready
            // For now, using mock data structure
            setStaff([
                { id: 1, name: 'John Doe', role: 'Operator', email: 'john@example.com' },
                { id: 2, name: 'Jane Smith', role: 'Supervisor', email: 'jane@example.com' },
            ]);
            setAllocations([]);
        } catch (error) {
            console.error('Error fetching staff data:', error);
        } finally {
            setLoading(false);
        }
    };

    const handleSubmit = async (e) => {
        e.preventDefault();
        try {
            // TODO: Implement API call when backend is ready
            console.log('Allocating staff time:', formData);
            alert('Staff allocation feature coming soon!');
            setShowModal(false);
        } catch (error) {
            alert('Error allocating staff: ' + (error.response?.data?.message || 'Unknown error'));
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
                    <h1 className="text-3xl font-bold text-gray-900">Staff & Labor Management</h1>
                    <p className="mt-2 text-gray-600">Track staff time allocation across all activities</p>
                </div>
                <button
                    onClick={() => setShowModal(true)}
                    className="bg-green-600 text-white px-4 py-2 rounded-lg hover:bg-green-700 flex items-center"
                >
                    <Plus className="h-5 w-5 mr-2" />
                    Allocate Time
                </button>
            </div>

            {/* Staff List */}
            <div className="bg-white rounded-lg shadow mb-6">
                <div className="px-6 py-4 border-b border-gray-200">
                    <h2 className="text-lg font-semibold text-gray-900">Staff Members</h2>
                </div>
                <div className="divide-y divide-gray-200">
                    {staff.map((member) => (
                        <div key={member.id} className="px-6 py-4 flex items-center justify-between">
                            <div>
                                <p className="font-medium text-gray-900">{member.name}</p>
                                <p className="text-sm text-gray-500">{member.role} • {member.email}</p>
                            </div>
                            <div className="text-right">
                                <p className="text-sm font-medium text-gray-900">0 hours</p>
                                <p className="text-xs text-gray-500">This week</p>
                            </div>
                        </div>
                    ))}
                </div>
            </div>

            {/* Time Allocations */}
            <div className="bg-white rounded-lg shadow">
                <div className="px-6 py-4 border-b border-gray-200">
                    <h2 className="text-lg font-semibold text-gray-900">Time Allocations</h2>
                </div>
                <div className="p-6 text-center text-gray-500">
                    <Users className="h-12 w-12 mx-auto mb-4 text-gray-400" />
                    <p>No time allocations recorded yet</p>
                    <p className="text-sm mt-2">Click "Allocate Time" to start tracking staff hours</p>
                </div>
            </div>

            {/* Allocate Time Modal */}
            {showModal && (
                <div className="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50 p-4">
                    <div className="bg-white rounded-lg max-w-md w-full p-6">
                        <h2 className="text-xl font-bold mb-4">Allocate Staff Time</h2>
                        <form onSubmit={handleSubmit} className="space-y-4">
                            <div>
                                <label className="block text-sm font-medium text-gray-700 mb-1">Staff Member *</label>
                                <select
                                    required
                                    value={formData.staff_id}
                                    onChange={(e) => setFormData({ ...formData, staff_id: e.target.value })}
                                    className="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-green-500 focus:border-green-500"
                                >
                                    <option value="">Select staff member</option>
                                    {staff.map((member) => (
                                        <option key={member.id} value={member.id}>{member.name}</option>
                                    ))}
                                </select>
                            </div>
                            <div>
                                <label className="block text-sm font-medium text-gray-700 mb-1">Crop/Activity *</label>
                                <select
                                    required
                                    value={formData.crop_type}
                                    onChange={(e) => setFormData({ ...formData, crop_type: e.target.value })}
                                    className="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-green-500 focus:border-green-500"
                                >
                                    <option value="GARI">Gari</option>
                                    <option value="BELL_PEPPER">Bell Pepper</option>
                                    <option value="TOMATOES">Tomatoes</option>
                                    <option value="HABANEROS">Habaneros</option>
                                </select>
                            </div>
                            <div>
                                <label className="block text-sm font-medium text-gray-700 mb-1">Activity Type *</label>
                                <select
                                    required
                                    value={formData.activity_type}
                                    onChange={(e) => setFormData({ ...formData, activity_type: e.target.value })}
                                    className="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-green-500 focus:border-green-500"
                                >
                                    <option value="PRODUCTION">Production</option>
                                    <option value="HARVESTING">Harvesting</option>
                                    <option value="PROCESSING">Processing</option>
                                    <option value="PACKAGING">Packaging</option>
                                    <option value="SALES">Sales</option>
                                    <option value="MAINTENANCE">Maintenance</option>
                                </select>
                            </div>
                            <div>
                                <label className="block text-sm font-medium text-gray-700 mb-1">Hours *</label>
                                <input
                                    type="number"
                                    step="0.5"
                                    min="0.5"
                                    required
                                    value={formData.hours}
                                    onChange={(e) => setFormData({ ...formData, hours: e.target.value })}
                                    className="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-green-500 focus:border-green-500"
                                />
                            </div>
                            <div>
                                <label className="block text-sm font-medium text-gray-700 mb-1">Date *</label>
                                <input
                                    type="date"
                                    required
                                    value={formData.date}
                                    onChange={(e) => setFormData({ ...formData, date: e.target.value })}
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
                                    Allocate Time
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            )}
        </div>
    );
}

