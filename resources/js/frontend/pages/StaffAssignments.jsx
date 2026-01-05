import React, { useEffect, useState } from 'react';
import api from '../utils/api';
import { UserCheck, Plus, Edit, Trash2, X } from 'lucide-react';

export default function StaffAssignments() {
    const [assignments, setAssignments] = useState([]);
    const [workers, setWorkers] = useState([]);
    const [loading, setLoading] = useState(true);
    const [showModal, setShowModal] = useState(false);
    const [editingAssignment, setEditingAssignment] = useState(null);
    const [formData, setFormData] = useState({
        worker_id: '',
        assignable_type: 'App\\Models\\Site',
        assignable_id: '',
        role: '',
        core_responsibilities: '',
        assigned_from: '',
        assigned_to: '',
        notes: '',
    });

    const assignableTypes = [
        { value: 'App\\Models\\Site', label: 'Site' },
        { value: 'App\\Models\\Factory', label: 'Factory' },
        { value: 'App\\Models\\Greenhouse', label: 'Greenhouse' },
        { value: 'App\\Models\\FarmZone', label: 'Farm Zone' },
    ];

    useEffect(() => {
        fetchData();
        fetchWorkers();
    }, []);

    const fetchData = async () => {
        setLoading(true);
        try {
            const response = await api.get('/api/v1/staff-assignments?per_page=1000');
            const data = response.data?.data || (Array.isArray(response.data) ? response.data : []);
            setAssignments(Array.isArray(data) ? data : []);
        } catch (error) {
            console.error('Error fetching staff assignments:', error);
            setAssignments([]);
        } finally {
            setLoading(false);
        }
    };

    const fetchWorkers = async () => {
        try {
            const response = await api.get('/api/v1/workers?per_page=1000');
            const data = response.data?.data || (Array.isArray(response.data) ? response.data : []);
            setWorkers(Array.isArray(data) ? data : []);
        } catch (error) {
            console.error('Error fetching workers:', error);
        }
    };

    const handleModalOpen = () => {
        setEditingAssignment(null);
        setFormData({
            worker_id: '',
            assignable_type: 'App\\Models\\Site',
            assignable_id: '',
            role: '',
            core_responsibilities: '',
            assigned_from: new Date().toISOString().split('T')[0],
            assigned_to: '',
            notes: '',
        });
        setShowModal(true);
    };

    const handleEdit = (assignment) => {
        setEditingAssignment(assignment);
        setFormData({
            worker_id: assignment.worker_id || '',
            assignable_type: assignment.assignable_type || 'App\\Models\\Site',
            assignable_id: assignment.assignable_id || '',
            role: assignment.role || '',
            core_responsibilities: assignment.core_responsibilities || '',
            assigned_from: assignment.assigned_from || '',
            assigned_to: assignment.assigned_to || '',
            notes: assignment.notes || '',
        });
        setShowModal(true);
    };

    const handleSubmit = async (e) => {
        e.preventDefault();
        try {
            if (editingAssignment) {
                await api.put(`/api/v1/staff-assignments/${editingAssignment.id}`, formData);
            } else {
                await api.post('/api/v1/staff-assignments', formData);
            }
            setShowModal(false);
            fetchData();
        } catch (error) {
            console.error('Error saving staff assignment:', error);
            alert(error.response?.data?.message || 'Error saving staff assignment');
        }
    };

    const handleDelete = async (id) => {
        if (!confirm('Are you sure you want to delete this staff assignment?')) return;
        try {
            await api.delete(`/api/v1/staff-assignments/${id}`);
            fetchData();
        } catch (error) {
            console.error('Error deleting staff assignment:', error);
            alert(error.response?.data?.message || 'Error deleting staff assignment');
        }
    };

    const handleEndAssignment = async (id) => {
        if (!confirm('Are you sure you want to end this assignment?')) return;
        try {
            await api.post(`/api/v1/staff-assignments/${id}/end`);
            fetchData();
        } catch (error) {
            console.error('Error ending assignment:', error);
            alert(error.response?.data?.message || 'Error ending assignment');
        }
    };

    const getAssignableName = (assignment) => {
        if (assignment.assignable) {
            return assignment.assignable.name || assignment.assignable.code || 'N/A';
        }
        return 'N/A';
    };

    if (loading) {
        return <div className="p-6">Loading...</div>;
    }

    return (
        <div className="p-6">
            <div className="flex justify-between items-center mb-6">
                <h1 className="text-2xl font-bold">Staff Assignments</h1>
                <button
                    onClick={handleModalOpen}
                    className="bg-blue-600 text-white px-4 py-2 rounded-lg hover:bg-blue-700 flex items-center gap-2"
                >
                    <Plus size={20} />
                    Create Assignment
                </button>
            </div>

            <div className="bg-white rounded-lg shadow overflow-hidden">
                <table className="min-w-full divide-y divide-gray-200">
                    <thead className="bg-gray-50">
                        <tr>
                            <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Worker</th>
                            <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Assigned To</th>
                            <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Type</th>
                            <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Role</th>
                            <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">From</th>
                            <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">To</th>
                            <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                            <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                        </tr>
                    </thead>
                    <tbody className="bg-white divide-y divide-gray-200">
                        {assignments.length === 0 ? (
                            <tr>
                                <td colSpan="8" className="px-6 py-4 text-center text-gray-500">No staff assignments found</td>
                            </tr>
                        ) : (
                            assignments.map((assignment) => (
                                <tr key={assignment.id}>
                                    <td className="px-6 py-4 whitespace-nowrap text-sm text-gray-900">{assignment.worker?.name || '-'}</td>
                                    <td className="px-6 py-4 text-sm text-gray-500">{getAssignableName(assignment)}</td>
                                    <td className="px-6 py-4 text-sm text-gray-500">
                                        {assignment.assignable_type.replace('App\\Models\\', '')}
                                    </td>
                                    <td className="px-6 py-4 text-sm text-gray-500">{assignment.role || '-'}</td>
                                    <td className="px-6 py-4 text-sm text-gray-500">{assignment.assigned_from || '-'}</td>
                                    <td className="px-6 py-4 text-sm text-gray-500">{assignment.assigned_to || 'Ongoing'}</td>
                                    <td className="px-6 py-4 whitespace-nowrap">
                                        <span className={`px-2 py-1 text-xs rounded-full ${assignment.is_current ? 'bg-green-100 text-green-800' : 'bg-gray-100 text-gray-800'}`}>
                                            {assignment.is_current ? 'Current' : 'Ended'}
                                        </span>
                                    </td>
                                    <td className="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                        {assignment.is_current && (
                                            <button
                                                onClick={() => handleEndAssignment(assignment.id)}
                                                className="text-orange-600 hover:text-orange-900 mr-4"
                                                title="End Assignment"
                                            >
                                                <X size={16} />
                                            </button>
                                        )}
                                        <button
                                            onClick={() => handleEdit(assignment)}
                                            className="text-blue-600 hover:text-blue-900 mr-4"
                                        >
                                            <Edit size={16} />
                                        </button>
                                        <button
                                            onClick={() => handleDelete(assignment.id)}
                                            className="text-red-600 hover:text-red-900"
                                        >
                                            <Trash2 size={16} />
                                        </button>
                                    </td>
                                </tr>
                            ))
                        )}
                    </tbody>
                </table>
            </div>

            {showModal && (
                <div className="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50">
                    <div className="bg-white rounded-lg p-6 w-full max-w-2xl max-h-[90vh] overflow-y-auto">
                        <h2 className="text-xl font-bold mb-4">{editingAssignment ? 'Edit Staff Assignment' : 'Create Staff Assignment'}</h2>
                        <form onSubmit={handleSubmit}>
                            <div className="grid grid-cols-2 gap-4">
                                <div>
                                    <label className="block text-sm font-medium text-gray-700 mb-1">Worker *</label>
                                    <select
                                        required
                                        value={formData.worker_id}
                                        onChange={(e) => setFormData({ ...formData, worker_id: e.target.value })}
                                        className="w-full border border-gray-300 rounded-lg px-3 py-2"
                                    >
                                        <option value="">Select Worker</option>
                                        {workers.map((worker) => (
                                            <option key={worker.id} value={worker.id}>{worker.name}</option>
                                        ))}
                                    </select>
                                </div>
                                <div>
                                    <label className="block text-sm font-medium text-gray-700 mb-1">Assignment Type *</label>
                                    <select
                                        required
                                        value={formData.assignable_type}
                                        onChange={(e) => setFormData({ ...formData, assignable_type: e.target.value, assignable_id: '' })}
                                        className="w-full border border-gray-300 rounded-lg px-3 py-2"
                                    >
                                        {assignableTypes.map((type) => (
                                            <option key={type.value} value={type.value}>{type.label}</option>
                                        ))}
                                    </select>
                                </div>
                                <div className="col-span-2">
                                    <label className="block text-sm font-medium text-gray-700 mb-1">Assigned To ID *</label>
                                    <input
                                        type="number"
                                        required
                                        value={formData.assignable_id}
                                        onChange={(e) => setFormData({ ...formData, assignable_id: e.target.value })}
                                        className="w-full border border-gray-300 rounded-lg px-3 py-2"
                                        placeholder="Enter the ID of the site/factory/greenhouse/zone"
                                    />
                                    <p className="text-xs text-gray-500 mt-1">Note: You need to enter the ID of the item you're assigning to</p>
                                </div>
                                <div>
                                    <label className="block text-sm font-medium text-gray-700 mb-1">Role</label>
                                    <input
                                        type="text"
                                        value={formData.role}
                                        onChange={(e) => setFormData({ ...formData, role: e.target.value })}
                                        className="w-full border border-gray-300 rounded-lg px-3 py-2"
                                        placeholder="e.g., supervisor, operator"
                                    />
                                </div>
                                <div>
                                    <label className="block text-sm font-medium text-gray-700 mb-1">Assigned From *</label>
                                    <input
                                        type="date"
                                        required
                                        value={formData.assigned_from}
                                        onChange={(e) => setFormData({ ...formData, assigned_from: e.target.value })}
                                        className="w-full border border-gray-300 rounded-lg px-3 py-2"
                                    />
                                </div>
                                <div>
                                    <label className="block text-sm font-medium text-gray-700 mb-1">Assigned To</label>
                                    <input
                                        type="date"
                                        value={formData.assigned_to}
                                        onChange={(e) => setFormData({ ...formData, assigned_to: e.target.value || null })}
                                        className="w-full border border-gray-300 rounded-lg px-3 py-2"
                                        placeholder="Leave empty for ongoing assignment"
                                    />
                                </div>
                                <div className="col-span-2">
                                    <label className="block text-sm font-medium text-gray-700 mb-1">Core Responsibilities</label>
                                    <textarea
                                        value={formData.core_responsibilities}
                                        onChange={(e) => setFormData({ ...formData, core_responsibilities: e.target.value })}
                                        className="w-full border border-gray-300 rounded-lg px-3 py-2"
                                        rows="3"
                                    />
                                </div>
                                <div className="col-span-2">
                                    <label className="block text-sm font-medium text-gray-700 mb-1">Notes</label>
                                    <textarea
                                        value={formData.notes}
                                        onChange={(e) => setFormData({ ...formData, notes: e.target.value })}
                                        className="w-full border border-gray-300 rounded-lg px-3 py-2"
                                        rows="2"
                                    />
                                </div>
                            </div>
                            <div className="mt-6 flex justify-end gap-3">
                                <button
                                    type="button"
                                    onClick={() => setShowModal(false)}
                                    className="px-4 py-2 border border-gray-300 rounded-lg hover:bg-gray-50"
                                >
                                    Cancel
                                </button>
                                <button
                                    type="submit"
                                    className="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700"
                                >
                                    {editingAssignment ? 'Update' : 'Create'}
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            )}
        </div>
    );
}

