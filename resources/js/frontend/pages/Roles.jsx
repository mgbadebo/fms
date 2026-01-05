import React, { useEffect, useState } from 'react';
import { Link } from 'react-router-dom';
import api from '../utils/api';
import { Shield, Plus, Edit, Trash2, Users, Check, X } from 'lucide-react';

export default function Roles() {
    const [roles, setRoles] = useState([]);
    const [menuPermissions, setMenuPermissions] = useState({});
    const [loading, setLoading] = useState(true);
    const [showModal, setShowModal] = useState(false);
    const [editingRole, setEditingRole] = useState(null);
    const [formData, setFormData] = useState({
        name: '',
        permissions: [],
    });

    useEffect(() => {
        fetchData();
    }, []);

    const fetchData = async () => {
        try {
            const [rolesRes, permissionsRes] = await Promise.all([
                api.get('/api/v1/roles'),
                api.get('/api/v1/roles/menu-permissions'),
            ]);

            setRoles(rolesRes.data?.data || rolesRes.data || []);
            setMenuPermissions(permissionsRes.data?.data || {});
        } catch (error) {
            console.error('Error fetching data:', error);
            alert('Error loading roles: ' + (error.response?.data?.message || 'Unknown error'));
        } finally {
            setLoading(false);
        }
    };

    const handleOpenModal = (role = null) => {
        if (role) {
            setEditingRole(role);
            // If ADMIN role, get all permissions
            if (role.name === 'ADMIN') {
                const allPermissions = Object.values(menuPermissions)
                    .flatMap(menu => Object.values(menu))
                    .flatMap(submenu => submenu.map(p => {
                        const parts = [p.menu_key, p.submenu_key, p.permission_type].filter(Boolean);
                        return parts.join('.');
                    }));
                setFormData({
                    name: role.name,
                    permissions: allPermissions,
                });
            } else {
                setFormData({
                    name: role.name,
                    permissions: role.permissions?.map(p => p.name) || [],
                });
            }
        } else {
            setEditingRole(null);
            setFormData({
                name: '',
                permissions: [],
            });
        }
        setShowModal(true);
    };

    const handleCloseModal = () => {
        setShowModal(false);
        setEditingRole(null);
        setFormData({
            name: '',
            permissions: [],
        });
    };

    const togglePermission = (permissionName) => {
        // Prevent deselecting permissions for ADMIN role
        if (formData.name === 'ADMIN') {
            return; // ADMIN always has all permissions
        }
        
        setFormData(prev => ({
            ...prev,
            permissions: prev.permissions.includes(permissionName)
                ? prev.permissions.filter(p => p !== permissionName)
                : [...prev.permissions, permissionName]
        }));
    };

    const toggleMenuPermissions = (menuKey, submenuKey, permissionType) => {
        const permissionName = [menuKey, submenuKey, permissionType].filter(Boolean).join('.');
        togglePermission(permissionName);
    };

    const hasPermission = (menuKey, submenuKey, permissionType) => {
        const permissionName = [menuKey, submenuKey, permissionType].filter(Boolean).join('.');
        return formData.permissions.includes(permissionName);
    };

    const handleSubmit = async (e) => {
        e.preventDefault();
        try {
            // For ADMIN role, always send all permissions (backend will handle it, but ensure frontend sends them)
            const submitData = { ...formData };
            if (submitData.name === 'ADMIN') {
                const allPermissions = Object.values(menuPermissions)
                    .flatMap(menu => Object.values(menu))
                    .flatMap(submenu => submenu.map(p => {
                        const parts = [p.menu_key, p.submenu_key, p.permission_type].filter(Boolean);
                        return parts.join('.');
                    }));
                submitData.permissions = allPermissions;
            }
            
            if (editingRole) {
                await api.put(`/api/v1/roles/${editingRole.id}`, submitData);
            } else {
                await api.post('/api/v1/roles', submitData);
            }
            handleCloseModal();
            fetchData();
        } catch (error) {
            alert('Error saving role: ' + (error.response?.data?.message || 'Unknown error'));
        }
    };

    const handleDelete = async (roleId, roleName) => {
        if (!confirm(`Are you sure you want to delete the role "${roleName}"?`)) {
            return;
        }

        try {
            await api.delete(`/api/v1/roles/${roleId}`);
            fetchData();
        } catch (error) {
            alert('Error deleting role: ' + (error.response?.data?.message || 'Unknown error'));
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
                    <h1 className="text-2xl md:text-3xl font-bold text-gray-900">Roles & Permissions</h1>
                    <p className="text-gray-600 mt-1">Manage user roles and their access permissions</p>
                </div>
                <button
                    onClick={() => handleOpenModal()}
                    className="w-full md:w-auto flex items-center gap-2 bg-green-600 text-white px-4 py-2 rounded-lg hover:bg-green-700 transition-colors"
                >
                    <Plus className="w-5 h-5" />
                    Create Role
                </button>
            </div>

            {/* Roles List */}
            <div className="space-y-4">
                {roles.length === 0 ? (
                    <div className="bg-white rounded-lg shadow p-8 text-center">
                        <Shield className="w-12 h-12 text-gray-400 mx-auto mb-4" />
                        <p className="text-gray-600">No roles found. Create your first role!</p>
                    </div>
                ) : (
                    roles.map((role) => (
                        <div key={role.id} className="bg-white rounded-lg shadow p-4 md:p-6">
                            <div className="flex flex-col md:flex-row justify-between items-start md:items-center mb-4 gap-4">
                                <div>
                                    <div className="flex items-center gap-2 mb-2">
                                        <Shield className="w-5 h-5 text-green-600" />
                                        <h3 className="text-lg font-semibold text-gray-900">{role.name}</h3>
                                    </div>
                                    <p className="text-sm text-gray-600">
                                        {role.permissions?.length || 0} permission(s) assigned
                                    </p>
                                </div>
                                <div className="flex gap-2">
                                    <button
                                        onClick={() => handleOpenModal(role)}
                                        className="flex items-center gap-2 px-3 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors"
                                    >
                                        <Edit className="w-4 h-4" />
                                        Edit
                                    </button>
                                    {role.name !== 'ADMIN' && (
                                        <button
                                            onClick={() => handleDelete(role.id, role.name)}
                                            className="flex items-center gap-2 px-3 py-2 bg-red-600 text-white rounded-lg hover:bg-red-700 transition-colors"
                                        >
                                            <Trash2 className="w-4 h-4" />
                                            Delete
                                        </button>
                                    )}
                                </div>
                            </div>
                            {role.permissions && role.permissions.length > 0 && (
                                <div className="mt-4 pt-4 border-t">
                                    <p className="text-sm font-medium text-gray-700 mb-2">Permissions:</p>
                                    <div className="flex flex-wrap gap-2">
                                        {role.permissions.map((permission) => (
                                            <span
                                                key={permission.id}
                                                className="px-2 py-1 bg-green-100 text-green-800 text-xs font-medium rounded"
                                            >
                                                {permission.name}
                                            </span>
                                        ))}
                                    </div>
                                </div>
                            )}
                        </div>
                    ))
                )}
            </div>

            {/* Create/Edit Role Modal */}
            {showModal && (
                <div className="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center p-4 z-50">
                    <div className="bg-white rounded-lg shadow-xl w-full max-w-4xl max-h-[90vh] overflow-y-auto">
                        <div className="p-6 border-b">
                            <h2 className="text-2xl font-bold text-gray-900">
                                {editingRole ? 'Edit Role' : 'Create New Role'}
                            </h2>
                        </div>
                        <form onSubmit={handleSubmit} className="p-6">
                            <div className="mb-6">
                                <label className="block text-sm font-medium text-gray-700 mb-1">Role Name *</label>
                                <input
                                    type="text"
                                    value={formData.name}
                                    onChange={(e) => {
                                        const newName = e.target.value;
                                        setFormData({ ...formData, name: newName });
                                        // If ADMIN role, automatically select all permissions
                                        if (newName === 'ADMIN') {
                                            const allPermissions = Object.values(menuPermissions)
                                                .flatMap(menu => Object.values(menu))
                                                .flatMap(submenu => submenu.map(p => {
                                                    const parts = [p.menu_key, p.submenu_key, p.permission_type].filter(Boolean);
                                                    return parts.join('.');
                                                }));
                                            setFormData(prev => ({ ...prev, name: newName, permissions: allPermissions }));
                                        }
                                    }}
                                    required
                                    className="w-full border border-gray-300 rounded-lg px-3 py-2 focus:ring-2 focus:ring-green-500 focus:border-green-500"
                                    placeholder="e.g., Manager, Worker, Viewer"
                                />
                                {formData.name === 'ADMIN' && (
                                    <p className="text-xs text-blue-600 mt-1">
                                        ℹ️ ADMIN role automatically has all permissions and cannot be modified.
                                    </p>
                                )}
                            </div>

                            <div>
                                <label className="block text-sm font-medium text-gray-700 mb-3">Permissions</label>
                                {formData.name === 'ADMIN' && (
                                    <div className="mb-4 p-3 bg-blue-50 border border-blue-200 rounded-lg">
                                        <p className="text-sm text-blue-800">
                                            <strong>Note:</strong> The ADMIN role has all permissions automatically. All permissions are selected and cannot be deselected.
                                        </p>
                                    </div>
                                )}
                                <div className="space-y-6 max-h-96 overflow-y-auto">
                                    {Object.entries(menuPermissions).map(([menuKey, submenus]) => (
                                        <div key={menuKey} className="border border-gray-200 rounded-lg p-4">
                                            <h4 className="font-semibold text-gray-900 mb-3 capitalize">
                                                {menuKey.replace('-', ' ')}
                                            </h4>
                                            {Object.entries(submenus).map(([submenuKey, permissions]) => (
                                                <div key={submenuKey} className="ml-4 mb-4 last:mb-0">
                                                    {submenuKey !== 'main' && (
                                                        <h5 className="font-medium text-gray-700 mb-2 capitalize">
                                                            {submenuKey.replace('-', ' ')}
                                                        </h5>
                                                    )}
                                                    <div className="flex flex-wrap gap-2">
                                                        {permissions.map((permission) => (
                                                            <button
                                                                key={permission.id}
                                                                type="button"
                                                                onClick={() => toggleMenuPermissions(
                                                                    permission.menu_key,
                                                                    permission.submenu_key,
                                                                    permission.permission_type
                                                                )}
                                                                disabled={formData.name === 'ADMIN'}
                                                                className={`flex items-center gap-2 px-3 py-2 rounded-lg border transition-colors ${
                                                                    hasPermission(
                                                                        permission.menu_key,
                                                                        permission.submenu_key,
                                                                        permission.permission_type
                                                                    )
                                                                        ? 'bg-green-100 border-green-500 text-green-700'
                                                                        : 'bg-gray-50 border-gray-300 text-gray-700 hover:bg-gray-100'
                                                                } ${
                                                                    formData.name === 'ADMIN' ? 'opacity-75 cursor-not-allowed' : 'cursor-pointer'
                                                                }`}
                                                            >
                                                                {hasPermission(
                                                                    permission.menu_key,
                                                                    permission.submenu_key,
                                                                    permission.permission_type
                                                                ) ? (
                                                                    <Check className="w-4 h-4" />
                                                                ) : (
                                                                    <X className="w-4 h-4" />
                                                                )}
                                                                <span className="text-sm capitalize">
                                                                    {permission.permission_type}
                                                                </span>
                                                            </button>
                                                        ))}
                                                    </div>
                                                </div>
                                            ))}
                                        </div>
                                    ))}
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
                                    {editingRole ? 'Update Role' : 'Create Role'}
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            )}
        </div>
    );
}

