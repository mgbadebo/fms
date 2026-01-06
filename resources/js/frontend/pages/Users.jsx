import React, { useEffect, useState } from 'react';
import api from '../utils/api';
import { User, Plus, Edit, Trash2, Camera, Upload, X } from 'lucide-react';

export default function Users() {
    const [users, setUsers] = useState([]);
    const [farms, setFarms] = useState([]);
    const [permissions, setPermissions] = useState([]);
    const [workerJobRoles, setWorkerJobRoles] = useState({}); // Keyed by farm_id
    const [loading, setLoading] = useState(true);
    const [showModal, setShowModal] = useState(false);
    const [editingUser, setEditingUser] = useState(null);
    const [photoPreview, setPhotoPreview] = useState(null);
    const [formData, setFormData] = useState({
        name: '',
        email: '',
        phone: '',
        password: '',
        photo: null,
        farms: [],
        permissions: [],
        job_roles: [],
    });

    useEffect(() => {
        fetchData();
    }, []);

    const fetchData = async () => {
        try {
            const [usersRes, farmsRes, permissionsRes] = await Promise.all([
                api.get('/api/v1/users?per_page=1000'),
                api.get('/api/v1/farms?per_page=1000'),
                api.get('/api/v1/permissions'),
            ]);

            // Handle paginated response from UserResource::collection
            let usersData = [];
            if (usersRes.data?.data && Array.isArray(usersRes.data.data)) {
                usersData = usersRes.data.data;
            } else if (Array.isArray(usersRes.data)) {
                usersData = usersRes.data;
            }
            setUsers(usersData);

            const farmsData = farmsRes.data?.data || (Array.isArray(farmsRes.data) ? farmsRes.data : []);
            setFarms(Array.isArray(farmsData) ? farmsData : []);

            const permissionsData = permissionsRes.data?.data || (Array.isArray(permissionsRes.data) ? permissionsRes.data : []);
            setPermissions(Array.isArray(permissionsData) ? permissionsData : []);
        } catch (error) {
            console.error('Error fetching data:', error);
            alert('Error loading data: ' + (error.response?.data?.message || 'Unknown error'));
        } finally {
            setLoading(false);
        }
    };

    const fetchWorkerJobRoles = async () => {
        try {
            const response = await api.get(`/api/v1/worker-job-roles?is_active=1`);
            const data = response.data?.data || (Array.isArray(response.data) ? response.data : []);
            return Array.isArray(data) ? data : [];
        } catch (error) {
            console.error('Error fetching job roles:', error);
            return [];
        }
    };

    const handleOpenModal = async (user = null) => {
        if (user) {
            setEditingUser(user);
            const userDetail = await api.get(`/api/v1/users/${user.id}?include_permissions=1`);
            const userData = userDetail.data.data;
            
            // Load job roles (global) and assignments for each farm
            const jobRolesData = [];
            const allRoles = await fetchWorkerJobRoles();
            setWorkerJobRoles({ global: allRoles });
            
            if (userData.farms && userData.farms.length > 0) {
                for (const farm of userData.farms) {
                    try {
                        const jobRolesRes = await api.get(`/api/v1/users/${user.id}/farms/${farm.id}/job-roles`);
                        const assignments = jobRolesRes.data?.data || [];
                        assignments.forEach(assignment => {
                            if (!assignment.ended_at) {
                                jobRolesData.push({
                                    farm_id: farm.id,
                                    worker_job_role_id: assignment.worker_job_role_id,
                                });
                            }
                        });
                    } catch (error) {
                        console.error(`Error loading job roles for farm ${farm.id}:`, error);
                    }
                }
            }
            
            // Map farms to use farm_id instead of id for form compatibility
            const mappedFarms = (userData.farms || []).map(farm => ({
                farm_id: farm.id,
                membership_status: farm.membership_status || 'ACTIVE',
                employment_category: farm.employment_category || '',
                pay_type: farm.pay_type || '',
                pay_rate: farm.pay_rate || '',
                start_date: farm.start_date || '',
                end_date: farm.end_date || '',
                notes: farm.notes || '',
            }));
            
            setFormData({
                name: userData.name || '',
                email: userData.email || '',
                phone: userData.phone || '',
                password: '',
                photo: null,
                farms: mappedFarms,
                permissions: userData.permissions || [],
                job_roles: jobRolesData,
            });
            setPhotoPreview(userData.profile_photo_url);
        } else {
            setEditingUser(null);
            setFormData({
                name: '',
                email: '',
                phone: '',
                password: '',
                photo: null,
                farms: [],
                permissions: [],
                job_roles: [],
            });
            setPhotoPreview(null);
        }
        setShowModal(true);
    };

    const handlePhotoChange = (e) => {
        const file = e.target.files[0];
        if (file) {
            setFormData({ ...formData, photo: file });
            const reader = new FileReader();
            reader.onloadend = () => {
                setPhotoPreview(reader.result);
            };
            reader.readAsDataURL(file);
        }
    };

    const handleAddFarm = () => {
        setFormData({
            ...formData,
            farms: [...formData.farms, {
                farm_id: '',
                membership_status: 'ACTIVE',
                employment_category: '',
                pay_type: '',
                pay_rate: '',
                start_date: '',
            }],
        });
    };

    const handleRemoveFarm = (index) => {
        setFormData({
            ...formData,
            farms: formData.farms.filter((_, i) => i !== index),
        });
    };

    const handleFarmChange = async (index, field, value) => {
        const newFarms = [...formData.farms];
        newFarms[index][field] = value;
        
        // Load job roles once (they're global now, not farm-specific)
        if (Object.keys(workerJobRoles).length === 0) {
            const roles = await fetchWorkerJobRoles();
            setWorkerJobRoles({ global: roles });
        }
        
        setFormData({ ...formData, farms: newFarms });
    };

    const handleAddJobRole = (farmId) => {
        setFormData({
            ...formData,
            job_roles: [...formData.job_roles, {
                farm_id: parseInt(farmId),
                worker_job_role_id: '',
            }],
        });
    };

    const handleRemoveJobRole = (index) => {
        setFormData({
            ...formData,
            job_roles: formData.job_roles.filter((_, i) => i !== index),
        });
    };

    const handleJobRoleChange = (index, field, value) => {
        const newJobRoles = [...formData.job_roles];
        newJobRoles[index][field] = field === 'worker_job_role_id' ? parseInt(value) : value;
        setFormData({ ...formData, job_roles: newJobRoles });
    };

    const handleSubmit = async (e) => {
        e.preventDefault();
        try {
            const submitData = new FormData();
            submitData.append('name', formData.name);
            submitData.append('email', formData.email);
            if (formData.phone) submitData.append('phone', formData.phone);
            if (formData.password) submitData.append('password', formData.password);
            if (formData.photo) submitData.append('photo', formData.photo);
            
            if (formData.farms.length > 0) {
                submitData.append('farms', JSON.stringify(formData.farms));
            }
            if (formData.permissions.length > 0) {
                submitData.append('permissions', JSON.stringify(formData.permissions));
            }
            if (formData.job_roles.length > 0) {
                submitData.append('job_roles', JSON.stringify(formData.job_roles));
            }

            if (editingUser) {
                // For update, send farms and permissions as JSON
                const updateData = {
                    name: formData.name,
                    email: formData.email,
                    phone: formData.phone,
                };
                if (formData.password) updateData.password = formData.password;
                if (formData.farms.length > 0) {
                    updateData.farms = formData.farms;
                }
                if (formData.permissions.length > 0) {
                    updateData.permissions = formData.permissions;
                }
                
                await api.put(`/api/v1/users/${editingUser.id}`, updateData);
                
                // Upload photo separately if changed
                if (formData.photo) {
                    const photoFormData = new FormData();
                    photoFormData.append('photo', formData.photo);
                    await api.post(`/api/v1/users/${editingUser.id}/photo`, photoFormData, {
                        headers: { 'Content-Type': 'multipart/form-data' },
                    });
                }
                
                // Assign job roles separately (since they need to be assigned per farm)
                if (formData.job_roles.length > 0) {
                    // First, end all existing job role assignments for this user
                    for (const farm of formData.farms) {
                        if (farm.farm_id) {
                            try {
                                const existingRoles = await api.get(`/api/v1/users/${editingUser.id}/farms/${farm.farm_id}/job-roles`);
                                const assignments = existingRoles.data?.data || [];
                                for (const assignment of assignments) {
                                    if (!assignment.ended_at) {
                                        await api.post(`/api/v1/users/${editingUser.id}/farms/${farm.farm_id}/job-roles/${assignment.id}/end`);
                                    }
                                }
                            } catch (error) {
                                console.error(`Error ending existing job roles:`, error);
                            }
                        }
                    }
                    
                    // Then assign new job roles
                    for (const jobRole of formData.job_roles) {
                        if (jobRole.worker_job_role_id && jobRole.farm_id) {
                            try {
                                await api.post(`/api/v1/users/${editingUser.id}/farms/${jobRole.farm_id}/job-roles/assign`, {
                                    worker_job_role_id: jobRole.worker_job_role_id,
                                });
                            } catch (error) {
                                console.error(`Error assigning job role:`, error);
                            }
                        }
                    }
                }
            } else {
                await api.post('/api/v1/users', submitData, {
                    headers: { 'Content-Type': 'multipart/form-data' },
                });
            }
            
            setShowModal(false);
            fetchData();
        } catch (error) {
            console.error('Error saving user:', error);
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

    const togglePermission = (permissionName) => {
        setFormData(prev => ({
            ...prev,
            permissions: prev.permissions.includes(permissionName)
                ? prev.permissions.filter(p => p !== permissionName)
                : [...prev.permissions, permissionName]
        }));
    };

    if (loading) {
        return (
            <div className="flex items-center justify-center h-64">
                <div className="animate-spin rounded-full h-12 w-12 border-b-2 border-green-600"></div>
            </div>
        );
    }

    return (
        <div className="p-6">
            <div className="flex justify-between items-center mb-6">
                <h1 className="text-2xl font-bold">User Management</h1>
                <button
                    onClick={() => handleOpenModal()}
                    className="bg-blue-600 text-white px-4 py-2 rounded-lg hover:bg-blue-700 flex items-center gap-2"
                >
                    <Plus size={20} />
                    Create User
                </button>
            </div>

            <div className="bg-white rounded-lg shadow overflow-hidden">
                <table className="min-w-full divide-y divide-gray-200">
                    <thead className="bg-gray-50">
                        <tr>
                            <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Photo</th>
                            <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Name</th>
                            <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Email</th>
                            <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Phone</th>
                            <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Farms</th>
                            <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Actions</th>
                        </tr>
                    </thead>
                    <tbody className="bg-white divide-y divide-gray-200">
                        {users.length === 0 ? (
                            <tr>
                                <td colSpan="6" className="px-6 py-4 text-center text-gray-500">No users found</td>
                            </tr>
                        ) : (
                            users.map((user) => (
                                <tr key={user.id}>
                                    <td className="px-6 py-4">
                                        {user.profile_photo_url ? (
                                            <img src={user.profile_photo_url} alt={user.name} className="w-10 h-10 rounded-full object-cover" />
                                        ) : (
                                            <div className="w-10 h-10 rounded-full bg-gray-200 flex items-center justify-center">
                                                <User size={20} className="text-gray-400" />
                                            </div>
                                        )}
                                    </td>
                                    <td className="px-6 py-4 text-sm font-medium text-gray-900">{user.name}</td>
                                    <td className="px-6 py-4 text-sm text-gray-500">{user.email}</td>
                                    <td className="px-6 py-4 text-sm text-gray-500">{user.phone || '-'}</td>
                                    <td className="px-6 py-4 text-sm text-gray-500">
                                        {user.farms?.length > 0 ? (
                                            <span className="px-2 py-1 bg-blue-100 text-blue-800 rounded text-xs">
                                                {user.farms.length} farm(s)
                                            </span>
                                        ) : (
                                            '-'
                                        )}
                                    </td>
                                    <td className="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                        <button
                                            onClick={() => handleOpenModal(user)}
                                            className="text-blue-600 hover:text-blue-900 mr-4"
                                        >
                                            <Edit size={16} className="inline" />
                                        </button>
                                        <button
                                            onClick={() => handleDelete(user.id, user.name)}
                                            className="text-red-600 hover:text-red-900"
                                        >
                                            <Trash2 size={16} className="inline" />
                                        </button>
                                    </td>
                                </tr>
                            ))
                        )}
                    </tbody>
                </table>
            </div>

            {showModal && (
                <div className="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50 p-4">
                    <div className="bg-white rounded-lg max-w-4xl w-full max-h-[90vh] overflow-y-auto p-6">
                        <h2 className="text-xl font-bold mb-4">{editingUser ? 'Edit User' : 'Create New User'}</h2>
                        <form onSubmit={handleSubmit}>
                            <div className="grid grid-cols-2 gap-4">
                                <div className="col-span-2">
                                    <label className="block text-sm font-medium text-gray-700 mb-1">Profile Photo</label>
                                    <div className="flex items-center gap-4">
                                        {photoPreview && (
                                            <img src={photoPreview} alt="Preview" className="w-20 h-20 rounded-full object-cover" />
                                        )}
                                        <label className="flex items-center gap-2 px-4 py-2 border border-gray-300 rounded-lg cursor-pointer hover:bg-gray-50">
                                            <Camera size={20} />
                                            {photoPreview ? 'Change Photo' : 'Upload Photo'}
                                            <input
                                                type="file"
                                                accept="image/*"
                                                onChange={handlePhotoChange}
                                                className="hidden"
                                            />
                                        </label>
                                    </div>
                                </div>
                                <div>
                                    <label className="block text-sm font-medium text-gray-700 mb-1">Name *</label>
                                    <input
                                        type="text"
                                        required
                                        value={formData.name}
                                        onChange={(e) => setFormData({ ...formData, name: e.target.value })}
                                        className="w-full border border-gray-300 rounded-lg px-3 py-2"
                                    />
                                </div>
                                <div>
                                    <label className="block text-sm font-medium text-gray-700 mb-1">Email *</label>
                                    <input
                                        type="email"
                                        required
                                        value={formData.email}
                                        onChange={(e) => setFormData({ ...formData, email: e.target.value })}
                                        className="w-full border border-gray-300 rounded-lg px-3 py-2"
                                    />
                                </div>
                                <div>
                                    <label className="block text-sm font-medium text-gray-700 mb-1">Phone</label>
                                    <input
                                        type="tel"
                                        value={formData.phone}
                                        onChange={(e) => setFormData({ ...formData, phone: e.target.value })}
                                        className="w-full border border-gray-300 rounded-lg px-3 py-2"
                                    />
                                </div>
                                <div>
                                    <label className="block text-sm font-medium text-gray-700 mb-1">
                                        Password {editingUser ? '(leave blank to keep current)' : '*'}
                                    </label>
                                    <input
                                        type="password"
                                        required={!editingUser}
                                        value={formData.password}
                                        onChange={(e) => setFormData({ ...formData, password: e.target.value })}
                                        className="w-full border border-gray-300 rounded-lg px-3 py-2"
                                    />
                                </div>
                            </div>

                            <div className="mt-6">
                                <div className="flex justify-between items-center mb-2">
                                    <label className="block text-sm font-medium text-gray-700">Farm Memberships</label>
                                    <button
                                        type="button"
                                        onClick={handleAddFarm}
                                        className="text-sm text-blue-600 hover:text-blue-800"
                                    >
                                        + Add Farm
                                    </button>
                                </div>
                                {formData.farms.map((farm, index) => (
                                    <div key={index} className="border border-gray-200 rounded-lg p-4 mb-2">
                                        <div className="flex justify-between items-center mb-2">
                                            <span className="text-sm font-medium">Farm {index + 1}</span>
                                            <button
                                                type="button"
                                                onClick={() => handleRemoveFarm(index)}
                                                className="text-red-600 hover:text-red-800"
                                            >
                                                <X size={16} />
                                            </button>
                                        </div>
                                        <div className="grid grid-cols-2 gap-2">
                                            <select
                                                required
                                                value={farm.farm_id}
                                                onChange={(e) => handleFarmChange(index, 'farm_id', e.target.value)}
                                                className="border border-gray-300 rounded px-2 py-1 text-sm"
                                            >
                                                <option value="">Select Farm</option>
                                                {farms.map((f) => (
                                                    <option key={f.id} value={f.id}>{f.name}</option>
                                                ))}
                                            </select>
                                            <select
                                                required
                                                value={farm.membership_status}
                                                onChange={(e) => handleFarmChange(index, 'membership_status', e.target.value)}
                                                className="border border-gray-300 rounded px-2 py-1 text-sm"
                                            >
                                                <option value="ACTIVE">Active</option>
                                                <option value="INACTIVE">Inactive</option>
                                            </select>
                                            <select
                                                value={farm.employment_category}
                                                onChange={(e) => handleFarmChange(index, 'employment_category', e.target.value)}
                                                className="border border-gray-300 rounded px-2 py-1 text-sm"
                                            >
                                                <option value="">Employment Category</option>
                                                <option value="PERMANENT">Permanent</option>
                                                <option value="CASUAL">Casual</option>
                                                <option value="CONTRACTOR">Contractor</option>
                                                <option value="SEASONAL">Seasonal</option>
                                            </select>
                                            <select
                                                value={farm.pay_type}
                                                onChange={(e) => handleFarmChange(index, 'pay_type', e.target.value)}
                                                className="border border-gray-300 rounded px-2 py-1 text-sm"
                                            >
                                                <option value="">Pay Type</option>
                                                <option value="MONTHLY">Monthly</option>
                                                <option value="DAILY">Daily</option>
                                                <option value="HOURLY">Hourly</option>
                                                <option value="TASK">Task</option>
                                            </select>
                                            <input
                                                type="number"
                                                step="0.01"
                                                placeholder="Pay Rate"
                                                value={farm.pay_rate}
                                                onChange={(e) => handleFarmChange(index, 'pay_rate', e.target.value)}
                                                className="border border-gray-300 rounded px-2 py-1 text-sm"
                                            />
                                            <input
                                                type="date"
                                                placeholder="Start Date"
                                                value={farm.start_date}
                                                onChange={(e) => handleFarmChange(index, 'start_date', e.target.value)}
                                                className="border border-gray-300 rounded px-2 py-1 text-sm"
                                            />
                                        </div>
                                    </div>
                                ))}
                            </div>

                            <div className="mt-6">
                                <div className="flex justify-between items-center mb-2">
                                    <label className="block text-sm font-medium text-gray-700">Job Roles (per Farm)</label>
                                </div>
                                {formData.farms.map((farm) => {
                                    const farmId = farm.farm_id;
                                    if (!farmId) return null;
                                    
                                    // Job roles are now global, not farm-specific
                                    const allJobRoles = workerJobRoles.global || [];
                                    const userJobRolesForFarm = formData.job_roles.filter(jr => jr.farm_id === parseInt(farmId));
                                    
                                    return (
                                        <div key={farmId} className="border border-gray-200 rounded-lg p-4 mb-4">
                                            <div className="flex justify-between items-center mb-2">
                                                <span className="text-sm font-medium">
                                                    {farms.find(f => f.id === parseInt(farmId))?.name || `Farm ${farmId}`}
                                                </span>
                                                <button
                                                    type="button"
                                                    onClick={() => handleAddJobRole(farmId)}
                                                    className="text-sm text-blue-600 hover:text-blue-800"
                                                >
                                                    + Add Job Role
                                                </button>
                                            </div>
                                            {userJobRolesForFarm.length === 0 ? (
                                                <p className="text-sm text-gray-500">No job roles assigned</p>
                                            ) : (
                                                userJobRolesForFarm.map((jobRole, jrIndex) => {
                                                    const globalIndex = formData.job_roles.findIndex(jr => 
                                                        jr.farm_id === parseInt(farmId) && 
                                                        jr.worker_job_role_id === jobRole.worker_job_role_id
                                                    );
                                                    return (
                                                        <div key={jrIndex} className="flex items-center gap-2 mb-2">
                                                            <select
                                                                value={jobRole.worker_job_role_id}
                                                                onChange={(e) => handleJobRoleChange(globalIndex, 'worker_job_role_id', e.target.value)}
                                                                className="flex-1 border border-gray-300 rounded px-2 py-1 text-sm"
                                                            >
                                                                <option value="">Select Job Role</option>
                                                                {allJobRoles.map((role) => (
                                                                    <option key={role.id} value={role.id}>
                                                                        {role.name} ({role.code})
                                                                    </option>
                                                                ))}
                                                            </select>
                                                            <button
                                                                type="button"
                                                                onClick={() => handleRemoveJobRole(globalIndex)}
                                                                className="text-red-600 hover:text-red-800"
                                                            >
                                                                <X size={16} />
                                                            </button>
                                                        </div>
                                                    );
                                                })
                                            )}
                                        </div>
                                    );
                                })}
                            </div>

                            <div className="mt-6">
                                <label className="block text-sm font-medium text-gray-700 mb-2">Permissions</label>
                                <div className="max-h-40 overflow-y-auto border border-gray-200 rounded-lg p-2">
                                    {permissions.map((permission) => (
                                        <label key={permission.id} className="flex items-center gap-2 p-2 hover:bg-gray-50 cursor-pointer">
                                            <input
                                                type="checkbox"
                                                checked={formData.permissions.includes(permission.name)}
                                                onChange={() => togglePermission(permission.name)}
                                                className="w-4 h-4"
                                            />
                                            <span className="text-sm">{permission.name}</span>
                                        </label>
                                    ))}
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
                                    {editingUser ? 'Update' : 'Create'} User
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            )}
        </div>
    );
}
