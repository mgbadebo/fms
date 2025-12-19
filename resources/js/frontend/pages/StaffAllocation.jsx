import React, { useEffect, useState } from 'react';
import api from '../utils/api';
import { Users, Clock, Calendar } from 'lucide-react';

export default function StaffAllocation() {
    const [allocations, setAllocations] = useState([]);
    const [summary, setSummary] = useState({});
    const [loading, setLoading] = useState(true);
    const [dateRange, setDateRange] = useState({
        from: new Date(Date.now() - 7 * 24 * 60 * 60 * 1000).toISOString().slice(0, 10),
        to: new Date().toISOString().slice(0, 10),
    });

    useEffect(() => {
        fetchAllocations();
    }, [dateRange]);

    const fetchAllocations = async () => {
        try {
            setLoading(true);
            // TODO: Replace with actual API endpoint when backend is ready
            setAllocations([]);
            setSummary({
                totalHours: 0,
                byStaff: {},
                byCrop: {},
            });
        } catch (error) {
            console.error('Error fetching allocations:', error);
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

    return (
        <div>
            <div className="flex justify-between items-center mb-8">
                <div>
                    <h1 className="text-3xl font-bold text-gray-900">Staff Allocation Report</h1>
                    <p className="mt-2 text-gray-600">Time allocation across all activities</p>
                </div>
                <div className="flex space-x-2">
                    <input
                        type="date"
                        value={dateRange.from}
                        onChange={(e) => setDateRange({ ...dateRange, from: e.target.value })}
                        className="px-3 py-2 border border-gray-300 rounded-lg focus:ring-green-500 focus:border-green-500"
                    />
                    <input
                        type="date"
                        value={dateRange.to}
                        onChange={(e) => setDateRange({ ...dateRange, to: e.target.value })}
                        className="px-3 py-2 border border-gray-300 rounded-lg focus:ring-green-500 focus:border-green-500"
                    />
                </div>
            </div>

            <div className="bg-white rounded-lg shadow p-6 text-center text-gray-500">
                <Users className="h-12 w-12 mx-auto mb-4 text-gray-400" />
                <p>Staff allocation reporting coming soon</p>
                <p className="text-sm mt-2">This will show time allocation by staff, crop, and activity type</p>
            </div>
        </div>
    );
}

