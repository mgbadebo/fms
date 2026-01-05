import React, { useEffect, useState } from 'react';
import api from '../utils/api';
import { User, Plus, Edit, Trash2, Shield } from 'lucide-react';

export default function Users() {
    const [users, setUsers] = useState([]);
    const [roles, setRoles] = useState([]);
    const [loading, setLoading] = useState(true);
    const [showModal, setShowModal] = useState(false);
    const [editingUser, setEditingUser] = useState(null);
    const [formData, setFormData] = useState({
        name: '',
        email: '',
        password: '',
        roles: [],
    });

    useEffect(() => {
        fetchData();
    }, []);

    const fetchData = async () => {
        try {
            const usersRes = await api.get('/api/v1/users');
            const usersData = usersRes.data?.data || usersRes.data || [];
            setUsers(Array.isArray(usersData) ? usersData : usersData.data || []);

            // Fetch roles from first user's show endpoint or roles endpoint
            try {
                const rolesRes = await api.get('/api/v1/roles');
                setRoles(rolesRes.data?.data || rolesRes.data || []);
            } catch (error) {
                console.error('Error fetching roles:', error);
            }
        } catch (error) {
            console.error('Error fetching data:', error);
            alert('Error loading users: ' + (error.response?.data?.message || 'Unknown error'));
        } finally {
            setLoading(false);
        }
    };

    const handleOpenModal = (user = null) => {
        if (user) {
            setEditingUser(user);
            setFormData({
                name: user.name || '',
                email: user.email || '',
                password: '',
                roles: user.roles?.map(r => r.name) || [],
            });
        } else {
            setEditingUser(null);
            setFormData({
                name: '',
                email: '',
                password: '',
                roles: [],
            });
        }
        setShowModal(true);
    };

    const handleCloseModal = () => {
        setShowModal(false);
        setEditingUser(null);
        setFormData({
            name: '',
            email: '',
            password: '',
            roles: [],
        });
    };

    const toggleRole = (roleName) => {
        setFormData(prev => ({
            ...prev,
            roles: prev.roles.includes(roleName)
                ? prev.roles.filter(r => r !== roleName)
                : [...prev.roles, roleName]
        }));
    };

    const handleSubmit = async (e) => {
        e.preventDefault();
        try {
            const submitData = { ...formData };
            if (editingUser && !submitData.password) {
                delete submitData.password;
            }

            if (editingUser) {
                await api.put(`/api/v1/users/${editingUser.id}`, submitData);
            } else {
                if (!submitData.password) {
                    alert('Password is required for new users');
                    return;
                }
                await api.post('/api/v1/users', submitData);
            }
            handleCloseModal();
            fetchData();
        } catch (error) {
            alert('Error saving user: ' + (error.response?.data?.message || 'Unknown error'));
        }
    };

    const handleDelete = async (userId, userName) => {
        if (!confirm(`Are you sure you want to delete the user "${userName}"?`)) {
            return;
        }

        try {
            await api.delete(`/api/v1/users/${userId}`);
            fetchData();
        } catch (error) {
            alert('Error deleting user: ' + (error.response?.data?.message || 'Unknown error'));
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
        <div className="p-4 md:p-6">
            <div className="flex flex-col md:flex-row justify-between items-start md:items-center mb-6 gap-4">
                <div>
                    <h1 className="text-2xl md:text-3xl font-bold text-gray-900">User Management</h1>
                    <p className="text-gray-600 mt-1">Manage system users and their roles</p>
                </div>
                <button
                    onClick={() => handleOpenModal()}
                    className="w-full md:w-auto flex items-center gap-2 bg-green-600 text-white px-4 py-2 rounded-lg hover:bg-green-700 transition-colors"
                >
                    <Plus className="w-5 h-5" />
                    Create User
                </button>
            </div>

            {/* Users List */}
            <div className="space-y-4">
                {users.length === 0 ? (
                    <div className="bg-white rounded-lg shadow p-8 text-center">
                        <User className="w-12 h-12 text-gray-400 mx-auto mb-4" />
                        <p className="text-gray-600">No users found. Create your first user!</p>
                    </div>
                ) : (
                    users.map((user) => (
                        <div key={user.id} className="bg-white rounded-lg shadow p-4 md:p-6">
                            <div className="flex flex-col md:flex-row justify-between items-start md:items-center mb-4 gap-4">
                                <div>
                                    <div className="flex items-center gap-2 mb-2">
                                        <User className="w-5 h-5 text-green-600" />
                                        <h3 className="text-lg font-semibold text-gray-900">{user.name}</h3>
                                    </div>
                                    <p className="text-sm text-gray-600">{user.email}</p>
                                </div>
                                <div className="flex gap-2">
                                    <button
                                        onClick={() => handleOpenModal(user)}
                                        className="flex items-center gap-2 px-3 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors"
                                    >
                                        <Edit className="w-4 h-4" />
                                        Edit
                                    </button>
                                    <button
                                        onClick={() => handleDelete(user.id, user.name)}
                                        className="flex items-center gap-2 px-3 py-2 bg-red-600 text-white rounded-lg hover:bg-red-700 transition-colors"
                                    >
                                        <Trash2 className="w-4 h-4" />
                                        Delete
                                    </button>
                                </div>
                            </div>
                            {user.roles && user.roles.length > 0 && (
                                <div className="mt-4 pt-4 border-t">
                                    <p className="text-sm font-medium text-gray-700 mb-2">Roles:</p>
                                    <div className="flex flex-wrap gap-2">
                                        {user.roles.map((role) => (
                                            <span
                                                key={role.id}
                                                className="flex items-center gap-1 px-2 py-1 bg-blue-100 text-blue-800 text-xs font-medium rounded"
                                            >
                                                <Shield className="w-3 h-3" />
                                                {role.name}
                                            </span>
                                        ))}
                                    </div>
                                </div>
                            )}
                        </div>
                    ))
                )}
            </div>

            {/* Create/Edit User Modal */}
            {showModal && (
                <div className="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center p-4 z-50">
                    <div className="bg-white rounded-lg shadow-xl w-full max-w-2xl max-h-[90vh] overflow-y-auto">
                        <div className="p-6 border-b">
                            <h2 className="text-2xl font-bold text-gray-900">
                                {editingUser ? 'Edit User' : 'Create New User'}
                            </h2>
                        </div>
                        <form onSubmit={handleSubmit} className="p-6">
                            <div className="space-y-4">
                                <div>
                                    <label className="block text-sm font-medium text-gray-700 mb-1">Name *</label>
                                    <input
                                        type="text"
                                        value={formData.name}
                                        onChange={(e) => setFormData({ ...formData, name: e.target.value })}
                                        required
                                        className="w-full border border-gray-300 rounded-lg px-3 py-2 focus:ring-2 focus:ring-green-500 focus:border-green-500"
                                    />
                                </div>

                                <div>
                                    <label className="block text-sm font-medium text-gray-700 mb-1">Email *</label>
                                    <input
                                        type="email"
                                        value={formData.email}
                                        onChange={(e) => setFormData({ ...formData, email: e.target.value })}
                                        required
                                        className="w-full border border-gray-300 rounded-lg px-3 py-2 focus:ring-2 focus:ring-green-500 focus:border-green-500"
                                    />
                                </div>

                                <div>
                                    <label className="block text-sm font-medium text-gray-700 mb-1">
                                        Password {editingUser ? '(leave blank to keep current)' : '*'}
                                    </label>
                                    <input
                                        type="password"
                                        value={formData.password}
                                        onChange={(e) => setFormData({ ...formData, password: e.target.value })}
                                        required={!editingUser}
                                        className="w-full border border-gray-300 rounded-lg px-3 py-2 focus:ring-2 focus:ring-green-500 focus:border-green-500"
                                    />
                                </div>

                                <div>
                                    <label className="block text-sm font-medium text-gray-700 mb-2">Roles</label>
                                    <div className="space-y-2">
                                        {roles.map((role) => (
                                            <label
                                                key={role.id}
                                                className="flex items-center gap-2 p-3 border border-gray-200 rounded-lg hover:bg-gray-50 cursor-pointer"
                                            >
                                                <input
                                                    type="checkbox"
                                                    checked={formData.roles.includes(role.name)}
                                                    onChange={() => toggleRole(role.name)}
                                                    className="w-4 h-4 text-green-600 border-gray-300 rounded focus:ring-green-500"
                                                />
                                                <Shield className="w-4 h-4 text-gray-400" />
                                                <span className="text-sm text-gray-700">{role.name}</span>
                                            </label>
                                        ))}
                                    </div>
                                </div>
                            </div>

                            <div className="flex gap-4 mt-6">
                                <button
                                    type="button"
                                    onClick={handleCloseModal}
                                    className="flex-1 px-4 py-3 bg-gray-200 text-gray-800 rounded-lg hover:bg-gray-300 font-medium"
                                >
                                    Cancel
                                </button>
                                <button
                                    type="submit"
                                    className="flex-1 px-4 py-3 bg-green-600 text-white rounded-lg hover:bg-green-700 font-medium"
                                >
                                    {editingUser ? 'Update User' : 'Create User'}
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            )}
        </div>
    );
}

