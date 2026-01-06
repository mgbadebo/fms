import React, { useEffect, useState } from 'react';
import api from '../utils/api';
import { Briefcase, Plus, Edit, Trash2 } from 'lucide-react';

export default function WorkerJobRoles() {
    const [jobRoles, setJobRoles] = useState([]);
    const [loading, setLoading] = useState(true);
    const [showModal, setShowModal] = useState(false);
    const [editingRole, setEditingRole] = useState(null);
    const [formData, setFormData] = useState({
        code: '',
        name: '',
        description: '',
        is_active: true,
    });

    useEffect(() => {
        fetchData();
    }, []);

    const fetchData = async () => {
        setLoading(true);
        try {
            const response = await api.get('/api/v1/worker-job-roles?per_page=1000');
            const data = response.data?.data || (Array.isArray(response.data) ? response.data : []);
            setJobRoles(Array.isArray(data) ? data : []);
        } catch (error) {
            console.error('Error fetching job roles:', error);
            setJobRoles([]);
        } finally {
            setLoading(false);
        }
    };


    const handleModalOpen = (role = null) => {
        setEditingRole(role);
        if (role) {
            setFormData({
                code: role.code || '',
                name: role.name || '',
                description: role.description || '',
                is_active: role.is_active !== undefined ? role.is_active : true,
            });
        } else {
            setFormData({
                code: '',
                name: '',
                description: '',
                is_active: true,
            });
        }
        setShowModal(true);
    };

    const handleSubmit = async (e) => {
        e.preventDefault();
        try {
            if (editingRole) {
                await api.put(`/api/v1/worker-job-roles/${editingRole.id}`, formData);
            } else {
                await api.post('/api/v1/worker-job-roles', formData);
            }
            setShowModal(false);
            fetchData();
        } catch (error) {
            console.error('Error saving job role:', error);
            alert(error.response?.data?.message || 'Error saving job role');
        }
    };

    const handleDelete = async (id) => {
        if (!confirm('Are you sure you want to delete this job role?')) return;
        try {
            await api.delete(`/api/v1/worker-job-roles/${id}`);
            fetchData();
        } catch (error) {
            console.error('Error deleting job role:', error);
            alert(error.response?.data?.message || 'Error deleting job role');
        }
    };

    if (loading) {
        return <div className="p-6">Loading...</div>;
    }

    return (
        <div className="p-6">
            <div className="flex justify-between items-center mb-6">
                <h1 className="text-2xl font-bold">Worker Job Roles</h1>
                <button
                    onClick={() => handleModalOpen()}
                    className="bg-blue-600 text-white px-4 py-2 rounded-lg hover:bg-blue-700 flex items-center gap-2"
                >
                    <Plus size={20} />
                    Add Job Role
                </button>
            </div>

            <div className="bg-white rounded-lg shadow overflow-hidden">
                <table className="min-w-full divide-y divide-gray-200">
                    <thead className="bg-gray-50">
                        <tr>
                            <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Code</th>
                            <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Name</th>
                            <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Description</th>
                            <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                            <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                        </tr>
                    </thead>
                    <tbody className="bg-white divide-y divide-gray-200">
                        {jobRoles.length === 0 ? (
                            <tr>
                                <td colSpan="5" className="px-6 py-4 text-center text-gray-500">No job roles found</td>
                            </tr>
                        ) : (
                            jobRoles.map((role) => (
                                <tr key={role.id}>
                                    <td className="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">{role.code}</td>
                                    <td className="px-6 py-4 whitespace-nowrap text-sm text-gray-900">{role.name}</td>
                                    <td className="px-6 py-4 text-sm text-gray-500">{role.description || '-'}</td>
                                    <td className="px-6 py-4 whitespace-nowrap">
                                        <span className={`px-2 py-1 text-xs rounded-full ${role.is_active ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800'}`}>
                                            {role.is_active ? 'Active' : 'Inactive'}
                                        </span>
                                    </td>
                                    <td className="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                        <button
                                            onClick={() => handleModalOpen(role)}
                                            className="text-blue-600 hover:text-blue-900 mr-4"
                                        >
                                            <Edit size={16} />
                                        </button>
                                        <button
                                            onClick={() => handleDelete(role.id)}
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
                        <h2 className="text-xl font-bold mb-4">{editingRole ? 'Edit Job Role' : 'Add Job Role'}</h2>
                        <form onSubmit={handleSubmit}>
                            <div className="space-y-4">
                                <div>
                                    <label className="block text-sm font-medium text-gray-700 mb-1">Farm *</label>
                                    <select
                                        required
                                        value={formData.farm_id}
                                        onChange={(e) => setFormData({ ...formData, farm_id: e.target.value })}
                                        className="w-full border border-gray-300 rounded-lg px-3 py-2"
                                        disabled={!!editingRole}
                                    >
                                        <option value="">Select Farm</option>
                                        {farms.map((farm) => (
                                            <option key={farm.id} value={farm.id}>{farm.name}</option>
                                        ))}
                                    </select>
                                </div>
                                <div>
                                    <label className="block text-sm font-medium text-gray-700 mb-1">Code *</label>
                                    <input
                                        type="text"
                                        required
                                        value={formData.code}
                                        onChange={(e) => setFormData({ ...formData, code: e.target.value.toUpperCase() })}
                                        className="w-full border border-gray-300 rounded-lg px-3 py-2"
                                        placeholder="e.g., FLD_SUP"
                                    />
                                </div>
                                <div>
                                    <label className="block text-sm font-medium text-gray-700 mb-1">Name *</label>
                                    <input
                                        type="text"
                                        required
                                        value={formData.name}
                                        onChange={(e) => setFormData({ ...formData, name: e.target.value })}
                                        className="w-full border border-gray-300 rounded-lg px-3 py-2"
                                        placeholder="e.g., Field Supervisor"
                                    />
                                </div>
                                <div>
                                    <label className="block text-sm font-medium text-gray-700 mb-1">Description</label>
                                    <textarea
                                        value={formData.description}
                                        onChange={(e) => setFormData({ ...formData, description: e.target.value })}
                                        className="w-full border border-gray-300 rounded-lg px-3 py-2"
                                        rows="3"
                                    />
                                </div>
                                <div>
                                    <label className="flex items-center gap-2">
                                        <input
                                            type="checkbox"
                                            checked={formData.is_active}
                                            onChange={(e) => setFormData({ ...formData, is_active: e.target.checked })}
                                        />
                                        <span className="text-sm font-medium text-gray-700">Active</span>
                                    </label>
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
                                    {editingRole ? 'Update' : 'Create'}
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            )}
        </div>
    );
}

