import React, { useEffect, useState } from 'react';
import api from '../utils/api';
import { Calendar, Plus, Edit, Trash2, Play, CheckCircle, X } from 'lucide-react';

export default function ProductionCycles() {
    const [cycles, setCycles] = useState([]);
    const [greenhouses, setGreenhouses] = useState([]);
    const [users, setUsers] = useState([]);
    const [seasons, setSeasons] = useState([]);
    const [loading, setLoading] = useState(true);
    const [showModal, setShowModal] = useState(false);
    const [editingCycle, setEditingCycle] = useState(null);
    const [formData, setFormData] = useState({
        greenhouse_id: '',
        season_id: '',
        crop: 'BELL_PEPPER',
        variety: '',
        responsible_supervisor_user_id: '',
        // Section 1: Planting & Establishment
        planting_date: '',
        establishment_method: 'TRANSPLANT',
        seed_supplier_name: '',
        seed_batch_number: '',
        nursery_start_date: '',
        transplant_date: '',
        plant_spacing_cm: '',
        row_spacing_cm: '',
        plant_density_per_sqm: '',
        initial_plant_count: '',
        // Section 2: Growing medium & setup
        cropping_system: 'COCOPEAT',
        medium_type: '',
        bed_count: '',
        bench_count: '',
        mulching_used: false,
        support_system: 'TRELLIS',
        // Section 3: Environmental targets
        target_day_temperature_c: '',
        target_night_temperature_c: '',
        target_humidity_percent: '',
        target_light_hours: '',
        ventilation_strategy: 'NATURAL',
        shade_net_percentage: '',
        notes: '',
    });

    useEffect(() => {
        fetchData();
    }, []);

    const fetchData = async () => {
        try {
            const [cyclesRes, greenhousesRes, usersRes, seasonsRes] = await Promise.all([
                api.get('/api/v1/production-cycles'),
                api.get('/api/v1/greenhouses?per_page=1000'),
                api.get('/api/v1/users?per_page=1000'),
                api.get('/api/v1/seasons?per_page=1000'),
            ]);

            const cyclesData = cyclesRes.data?.data || cyclesRes.data || [];
            const greenhousesData = greenhousesRes.data?.data || greenhousesRes.data || [];
            const usersData = usersRes.data?.data || usersRes.data || [];
            const seasonsData = seasonsRes.data?.data || seasonsRes.data || [];

            setCycles(Array.isArray(cyclesData) ? cyclesData : []);
            setGreenhouses(Array.isArray(greenhousesData) ? greenhousesData : []);
            setUsers(Array.isArray(usersData) ? usersData : []);
            setSeasons(Array.isArray(seasonsData) ? seasonsData : []);
        } catch (error) {
            console.error('Error fetching data:', error);
            alert('Error loading data: ' + (error.response?.data?.message || error.message));
        } finally {
            setLoading(false);
        }
    };

    const handleModalOpen = () => {
        setEditingCycle(null);
        setFormData({
            greenhouse_id: '',
            season_id: '',
            crop: 'BELL_PEPPER',
            variety: '',
            responsible_supervisor_user_id: '',
            planting_date: '',
            establishment_method: 'TRANSPLANT',
            seed_supplier_name: '',
            seed_batch_number: '',
            nursery_start_date: '',
            transplant_date: '',
            plant_spacing_cm: '',
            row_spacing_cm: '',
            plant_density_per_sqm: '',
            initial_plant_count: '',
            cropping_system: 'COCOPEAT',
            medium_type: '',
            bed_count: '',
            bench_count: '',
            mulching_used: false,
            support_system: 'TRELLIS',
            target_day_temperature_c: '',
            target_night_temperature_c: '',
            target_humidity_percent: '',
            target_light_hours: '',
            ventilation_strategy: 'NATURAL',
            shade_net_percentage: '',
            notes: '',
        });
        setShowModal(true);
    };

    const handleEdit = (cycle) => {
        setEditingCycle(cycle);
        setFormData({
            greenhouse_id: cycle.greenhouse?.id || '',
            season_id: cycle.season?.id || '',
            crop: cycle.crop || 'BELL_PEPPER',
            variety: cycle.variety || '',
            responsible_supervisor_user_id: cycle.responsible_supervisor?.id || '',
            planting_date: cycle.planting_date || '',
            establishment_method: cycle.establishment_method || 'TRANSPLANT',
            seed_supplier_name: cycle.seed_supplier_name || '',
            seed_batch_number: cycle.seed_batch_number || '',
            nursery_start_date: cycle.nursery_start_date || '',
            transplant_date: cycle.transplant_date || '',
            plant_spacing_cm: cycle.plant_spacing_cm || '',
            row_spacing_cm: cycle.row_spacing_cm || '',
            plant_density_per_sqm: cycle.plant_density_per_sqm || '',
            initial_plant_count: cycle.initial_plant_count || '',
            cropping_system: cycle.cropping_system || 'COCOPEAT',
            medium_type: cycle.medium_type || '',
            bed_count: cycle.bed_count || '',
            bench_count: cycle.bench_count || '',
            mulching_used: cycle.mulching_used || false,
            support_system: cycle.support_system || 'TRELLIS',
            target_day_temperature_c: cycle.target_day_temperature_c || '',
            target_night_temperature_c: cycle.target_night_temperature_c || '',
            target_humidity_percent: cycle.target_humidity_percent || '',
            target_light_hours: cycle.target_light_hours || '',
            ventilation_strategy: cycle.ventilation_strategy || 'NATURAL',
            shade_net_percentage: cycle.shade_net_percentage || '',
            notes: cycle.notes || '',
        });
        setShowModal(true);
    };

    const handleSubmit = async (e) => {
        e.preventDefault();
        try {
            if (editingCycle) {
                await api.patch(`/api/v1/production-cycles/${editingCycle.id}`, formData);
            } else {
                await api.post('/api/v1/production-cycles', formData);
            }
            setShowModal(false);
            fetchData();
        } catch (error) {
            const errorMsg = error.response?.data?.message || error.response?.data?.errors || 'Unknown error';
            alert('Error saving cycle: ' + (typeof errorMsg === 'string' ? errorMsg : JSON.stringify(errorMsg)));
        }
    };

    const handleStart = async (cycleId) => {
        if (!confirm('Start this production cycle?')) return;
        try {
            await api.post(`/api/v1/production-cycles/${cycleId}/start`);
            fetchData();
        } catch (error) {
            alert('Error starting cycle: ' + (error.response?.data?.message || error.message));
        }
    };

    const handleComplete = async (cycleId) => {
        if (!confirm('Mark this production cycle as completed?')) return;
        try {
            await api.post(`/api/v1/production-cycles/${cycleId}/complete`);
            fetchData();
        } catch (error) {
            alert('Error completing cycle: ' + (error.response?.data?.message || error.message));
        }
    };

    const handleDelete = async (cycleId) => {
        if (!confirm('Delete this production cycle?')) return;
        try {
            await api.delete(`/api/v1/production-cycles/${cycleId}`);
            fetchData();
        } catch (error) {
            alert('Error deleting cycle: ' + (error.response?.data?.message || error.message));
        }
    };

    const getStatusColor = (status) => {
        const colors = {
            PLANNED: 'bg-gray-100 text-gray-800',
            ACTIVE: 'bg-green-100 text-green-800',
            HARVESTING: 'bg-blue-100 text-blue-800',
            COMPLETED: 'bg-purple-100 text-purple-800',
            ABANDONED: 'bg-red-100 text-red-800',
        };
        return colors[status] || 'bg-gray-100 text-gray-800';
    };

    // Get selected season for validation
    const selectedSeason = seasons.find(s => s.id === parseInt(formData.season_id));

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
                    <h1 className="text-3xl font-bold text-gray-900">Production Cycles</h1>
                    <p className="mt-2 text-gray-600">Manage greenhouse production cycles</p>
                </div>
                <button
                    onClick={handleModalOpen}
                    className="flex items-center px-4 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700"
                >
                    <Plus className="h-5 w-5 mr-2" />
                    New Cycle
                </button>
            </div>

            {/* Cycles Table */}
            <div className="bg-white rounded-lg shadow overflow-hidden">
                <table className="min-w-full divide-y divide-gray-200">
                    <thead className="bg-gray-50">
                        <tr>
                            <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Code</th>
                            <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Greenhouse</th>
                            <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Crop</th>
                            <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Planting Date</th>
                            <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                            <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Supervisor</th>
                            <th className="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                        </tr>
                    </thead>
                    <tbody className="bg-white divide-y divide-gray-200">
                        {cycles.length === 0 ? (
                            <tr>
                                <td colSpan="7" className="px-6 py-4 text-center text-gray-500">
                                    No production cycles found
                                </td>
                            </tr>
                        ) : (
                            cycles.map((cycle) => (
                                <tr key={cycle.id}>
                                    <td className="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
                                        {cycle.production_cycle_code}
                                    </td>
                                    <td className="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                        {cycle.greenhouse?.name || 'N/A'}
                                    </td>
                                    <td className="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                        {cycle.crop} {cycle.variety ? `- ${cycle.variety}` : ''}
                                    </td>
                                    <td className="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                        {cycle.planting_date || 'N/A'}
                                    </td>
                                    <td className="px-6 py-4 whitespace-nowrap">
                                        <span className={`px-2 py-1 text-xs font-semibold rounded-full ${getStatusColor(cycle.cycle_status)}`}>
                                            {cycle.cycle_status}
                                        </span>
                                    </td>
                                    <td className="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                        {cycle.responsible_supervisor?.name || 'N/A'}
                                    </td>
                                    <td className="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                        <div className="flex justify-end space-x-2">
                                            {cycle.cycle_status === 'PLANNED' && (
                                                <button
                                                    onClick={() => handleStart(cycle.id)}
                                                    className="text-green-600 hover:text-green-900"
                                                    title="Start Cycle"
                                                >
                                                    <Play className="h-4 w-4" />
                                                </button>
                                            )}
                                            {['ACTIVE', 'HARVESTING'].includes(cycle.cycle_status) && (
                                                <button
                                                    onClick={() => handleComplete(cycle.id)}
                                                    className="text-purple-600 hover:text-purple-900"
                                                    title="Complete Cycle"
                                                >
                                                    <CheckCircle className="h-4 w-4" />
                                                </button>
                                            )}
                                            <button
                                                onClick={() => handleEdit(cycle)}
                                                className="text-blue-600 hover:text-blue-900"
                                                title="Edit"
                                            >
                                                <Edit className="h-4 w-4" />
                                            </button>
                                            <button
                                                onClick={() => handleDelete(cycle.id)}
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
                    <div className="bg-white rounded-lg max-w-4xl w-full max-h-[90vh] overflow-y-auto">
                        <div className="sticky top-0 bg-white border-b border-gray-200 px-6 py-4 flex justify-between items-center">
                            <h2 className="text-xl font-bold text-gray-900">
                                {editingCycle ? 'Edit Production Cycle' : 'New Production Cycle'}
                            </h2>
                            <button
                                onClick={() => setShowModal(false)}
                                className="text-gray-400 hover:text-gray-600"
                            >
                                <X className="h-6 w-6" />
                            </button>
                        </div>
                        <form onSubmit={handleSubmit} className="p-6 space-y-6">
                            {/* Basic Info */}
                            <div>
                                <h3 className="text-lg font-semibold text-gray-900 mb-4">Basic Information</h3>
                                <div className="grid grid-cols-2 gap-4">
                                    <div>
                                        <label className="block text-sm font-medium text-gray-700 mb-1">
                                            Greenhouse <span className="text-red-500">*</span>
                                        </label>
                                        <select
                                            required
                                            value={formData.greenhouse_id}
                                            onChange={(e) => setFormData({ ...formData, greenhouse_id: e.target.value })}
                                            className="w-full px-3 py-2 border border-gray-300 rounded-lg"
                                        >
                                            <option value="">Select Greenhouse</option>
                                            {greenhouses.map((gh) => (
                                                <option key={gh.id} value={gh.id}>
                                                    {gh.name} ({gh.greenhouse_code || gh.code})
                                                </option>
                                            ))}
                                        </select>
                                    </div>
                                    <div>
                                        <label className="block text-sm font-medium text-gray-700 mb-1">
                                            Season
                                        </label>
                                        <select
                                            value={formData.season_id}
                                            onChange={(e) => setFormData({ ...formData, season_id: e.target.value })}
                                            className="w-full px-3 py-2 border border-gray-300 rounded-lg"
                                        >
                                            <option value="">Select Season</option>
                                            {seasons
                                                .filter(season => ['PLANNED', 'ACTIVE'].includes(season.status))
                                                .map((season) => (
                                                    <option key={season.id} value={season.id}>
                                                        {season.name} ({season.status})
                                                    </option>
                                                ))}
                                        </select>
                                    </div>
                                    <div>
                                        <label className="block text-sm font-medium text-gray-700 mb-1">
                                            Crop <span className="text-red-500">*</span>
                                        </label>
                                        <select
                                            required
                                            value={formData.crop}
                                            onChange={(e) => setFormData({ ...formData, crop: e.target.value })}
                                            className="w-full px-3 py-2 border border-gray-300 rounded-lg"
                                        >
                                            <option value="BELL_PEPPER">Bell Pepper</option>
                                        </select>
                                    </div>
                                    <div>
                                        <label className="block text-sm font-medium text-gray-700 mb-1">
                                            Variety
                                        </label>
                                        <input
                                            type="text"
                                            value={formData.variety}
                                            onChange={(e) => setFormData({ ...formData, variety: e.target.value })}
                                            className="w-full px-3 py-2 border border-gray-300 rounded-lg"
                                        />
                                    </div>
                                    <div>
                                        <label className="block text-sm font-medium text-gray-700 mb-1">
                                            Responsible Supervisor <span className="text-red-500">*</span>
                                        </label>
                                        <select
                                            required
                                            value={formData.responsible_supervisor_user_id}
                                            onChange={(e) => setFormData({ ...formData, responsible_supervisor_user_id: e.target.value })}
                                            className="w-full px-3 py-2 border border-gray-300 rounded-lg"
                                        >
                                            <option value="">Select Supervisor</option>
                                            {users.map((user) => (
                                                <option key={user.id} value={user.id}>
                                                    {user.name} ({user.email})
                                                </option>
                                            ))}
                                        </select>
                                    </div>
                                </div>
                            </div>

                            {/* Section 1: Planting & Establishment */}
                            <div>
                                <h3 className="text-lg font-semibold text-gray-900 mb-4">Section 1: Planting & Establishment</h3>
                                <div className="grid grid-cols-2 gap-4">
                                    <div>
                                        <label className="block text-sm font-medium text-gray-700 mb-1">
                                            Planting Date <span className="text-red-500">*</span>
                                        </label>
                                        <input
                                            type="date"
                                            required
                                            value={formData.planting_date}
                                            min={selectedSeason?.start_date ? selectedSeason.start_date.split('T')[0] : undefined}
                                            onChange={(e) => setFormData({ ...formData, planting_date: e.target.value })}
                                            className="w-full px-3 py-2 border border-gray-300 rounded-lg"
                                        />
                                        {selectedSeason && formData.planting_date && new Date(formData.planting_date) < new Date(selectedSeason.start_date) && (
                                            <p className="mt-1 text-xs text-red-600">
                                                Planting date cannot be earlier than season start date ({selectedSeason.start_date.split('T')[0]})
                                            </p>
                                        )}
                                    </div>
                                    <div>
                                        <label className="block text-sm font-medium text-gray-700 mb-1">
                                            Establishment Method <span className="text-red-500">*</span>
                                        </label>
                                        <select
                                            required
                                            value={formData.establishment_method}
                                            onChange={(e) => setFormData({ ...formData, establishment_method: e.target.value })}
                                            className="w-full px-3 py-2 border border-gray-300 rounded-lg"
                                        >
                                            <option value="DIRECT_SEED">Direct Seed</option>
                                            <option value="TRANSPLANT">Transplant</option>
                                        </select>
                                    </div>
                                    <div>
                                        <label className="block text-sm font-medium text-gray-700 mb-1">
                                            Seed Supplier Name <span className="text-red-500">*</span>
                                        </label>
                                        <input
                                            type="text"
                                            required
                                            value={formData.seed_supplier_name}
                                            onChange={(e) => setFormData({ ...formData, seed_supplier_name: e.target.value })}
                                            className="w-full px-3 py-2 border border-gray-300 rounded-lg"
                                        />
                                    </div>
                                    <div>
                                        <label className="block text-sm font-medium text-gray-700 mb-1">
                                            Seed Batch Number <span className="text-red-500">*</span>
                                        </label>
                                        <input
                                            type="text"
                                            required
                                            value={formData.seed_batch_number}
                                            onChange={(e) => setFormData({ ...formData, seed_batch_number: e.target.value })}
                                            className="w-full px-3 py-2 border border-gray-300 rounded-lg"
                                        />
                                    </div>
                                    <div>
                                        <label className="block text-sm font-medium text-gray-700 mb-1">
                                            Nursery Start Date
                                        </label>
                                        <input
                                            type="date"
                                            value={formData.nursery_start_date}
                                            onChange={(e) => setFormData({ ...formData, nursery_start_date: e.target.value })}
                                            className="w-full px-3 py-2 border border-gray-300 rounded-lg"
                                        />
                                    </div>
                                    <div>
                                        <label className="block text-sm font-medium text-gray-700 mb-1">
                                            Transplant Date
                                        </label>
                                        <input
                                            type="date"
                                            value={formData.transplant_date}
                                            onChange={(e) => setFormData({ ...formData, transplant_date: e.target.value })}
                                            className="w-full px-3 py-2 border border-gray-300 rounded-lg"
                                        />
                                    </div>
                                    <div>
                                        <label className="block text-sm font-medium text-gray-700 mb-1">
                                            Plant Spacing (cm) <span className="text-red-500">*</span>
                                        </label>
                                        <input
                                            type="number"
                                            step="0.01"
                                            required
                                            value={formData.plant_spacing_cm}
                                            onChange={(e) => setFormData({ ...formData, plant_spacing_cm: e.target.value })}
                                            className="w-full px-3 py-2 border border-gray-300 rounded-lg"
                                        />
                                    </div>
                                    <div>
                                        <label className="block text-sm font-medium text-gray-700 mb-1">
                                            Row Spacing (cm) <span className="text-red-500">*</span>
                                        </label>
                                        <input
                                            type="number"
                                            step="0.01"
                                            required
                                            value={formData.row_spacing_cm}
                                            onChange={(e) => setFormData({ ...formData, row_spacing_cm: e.target.value })}
                                            className="w-full px-3 py-2 border border-gray-300 rounded-lg"
                                        />
                                    </div>
                                    <div>
                                        <label className="block text-sm font-medium text-gray-700 mb-1">
                                            Plant Density (per sqm)
                                        </label>
                                        <input
                                            type="number"
                                            step="0.01"
                                            value={formData.plant_density_per_sqm}
                                            onChange={(e) => setFormData({ ...formData, plant_density_per_sqm: e.target.value })}
                                            className="w-full px-3 py-2 border border-gray-300 rounded-lg"
                                        />
                                    </div>
                                    <div>
                                        <label className="block text-sm font-medium text-gray-700 mb-1">
                                            Initial Plant Count <span className="text-red-500">*</span>
                                        </label>
                                        <input
                                            type="number"
                                            required
                                            value={formData.initial_plant_count}
                                            onChange={(e) => setFormData({ ...formData, initial_plant_count: e.target.value })}
                                            className="w-full px-3 py-2 border border-gray-300 rounded-lg"
                                        />
                                    </div>
                                </div>
                            </div>

                            {/* Section 2: Growing Medium & Setup */}
                            <div>
                                <h3 className="text-lg font-semibold text-gray-900 mb-4">Section 2: Growing Medium & Setup</h3>
                                <div className="grid grid-cols-2 gap-4">
                                    <div>
                                        <label className="block text-sm font-medium text-gray-700 mb-1">
                                            Cropping System <span className="text-red-500">*</span>
                                        </label>
                                        <select
                                            required
                                            value={formData.cropping_system}
                                            onChange={(e) => setFormData({ ...formData, cropping_system: e.target.value })}
                                            className="w-full px-3 py-2 border border-gray-300 rounded-lg"
                                        >
                                            <option value="SOIL">Soil</option>
                                            <option value="COCOPEAT">Cocopeat</option>
                                            <option value="HYDROPONIC">Hydroponic</option>
                                        </select>
                                    </div>
                                    <div>
                                        <label className="block text-sm font-medium text-gray-700 mb-1">
                                            Medium Type <span className="text-red-500">*</span>
                                        </label>
                                        <input
                                            type="text"
                                            required
                                            value={formData.medium_type}
                                            onChange={(e) => setFormData({ ...formData, medium_type: e.target.value })}
                                            className="w-full px-3 py-2 border border-gray-300 rounded-lg"
                                        />
                                    </div>
                                    <div>
                                        <label className="block text-sm font-medium text-gray-700 mb-1">
                                            Bed Count <span className="text-red-500">*</span>
                                        </label>
                                        <input
                                            type="number"
                                            required
                                            value={formData.bed_count}
                                            onChange={(e) => setFormData({ ...formData, bed_count: e.target.value })}
                                            className="w-full px-3 py-2 border border-gray-300 rounded-lg"
                                        />
                                    </div>
                                    <div>
                                        <label className="block text-sm font-medium text-gray-700 mb-1">
                                            Bench Count
                                        </label>
                                        <input
                                            type="number"
                                            value={formData.bench_count}
                                            onChange={(e) => setFormData({ ...formData, bench_count: e.target.value })}
                                            className="w-full px-3 py-2 border border-gray-300 rounded-lg"
                                        />
                                    </div>
                                    <div>
                                        <label className="block text-sm font-medium text-gray-700 mb-1">
                                            Mulching Used <span className="text-red-500">*</span>
                                        </label>
                                        <select
                                            required
                                            value={formData.mulching_used}
                                            onChange={(e) => setFormData({ ...formData, mulching_used: e.target.value === 'true' })}
                                            className="w-full px-3 py-2 border border-gray-300 rounded-lg"
                                        >
                                            <option value="false">No</option>
                                            <option value="true">Yes</option>
                                        </select>
                                    </div>
                                    <div>
                                        <label className="block text-sm font-medium text-gray-700 mb-1">
                                            Support System <span className="text-red-500">*</span>
                                        </label>
                                        <select
                                            required
                                            value={formData.support_system}
                                            onChange={(e) => setFormData({ ...formData, support_system: e.target.value })}
                                            className="w-full px-3 py-2 border border-gray-300 rounded-lg"
                                        >
                                            <option value="STAKES">Stakes</option>
                                            <option value="TRELLIS">Trellis</option>
                                            <option value="STRING">String</option>
                                            <option value="NONE">None</option>
                                        </select>
                                    </div>
                                </div>
                            </div>

                            {/* Section 3: Environmental Targets */}
                            <div>
                                <h3 className="text-lg font-semibold text-gray-900 mb-4">Section 3: Environmental Targets</h3>
                                <div className="grid grid-cols-2 gap-4">
                                    <div>
                                        <label className="block text-sm font-medium text-gray-700 mb-1">
                                            Target Day Temperature (°C) <span className="text-red-500">*</span>
                                        </label>
                                        <input
                                            type="number"
                                            step="0.01"
                                            required
                                            value={formData.target_day_temperature_c}
                                            onChange={(e) => setFormData({ ...formData, target_day_temperature_c: e.target.value })}
                                            className="w-full px-3 py-2 border border-gray-300 rounded-lg"
                                        />
                                    </div>
                                    <div>
                                        <label className="block text-sm font-medium text-gray-700 mb-1">
                                            Target Night Temperature (°C) <span className="text-red-500">*</span>
                                        </label>
                                        <input
                                            type="number"
                                            step="0.01"
                                            required
                                            value={formData.target_night_temperature_c}
                                            onChange={(e) => setFormData({ ...formData, target_night_temperature_c: e.target.value })}
                                            className="w-full px-3 py-2 border border-gray-300 rounded-lg"
                                        />
                                    </div>
                                    <div>
                                        <label className="block text-sm font-medium text-gray-700 mb-1">
                                            Target Humidity (%) <span className="text-red-500">*</span>
                                        </label>
                                        <input
                                            type="number"
                                            step="0.01"
                                            required
                                            value={formData.target_humidity_percent}
                                            onChange={(e) => setFormData({ ...formData, target_humidity_percent: e.target.value })}
                                            className="w-full px-3 py-2 border border-gray-300 rounded-lg"
                                        />
                                    </div>
                                    <div>
                                        <label className="block text-sm font-medium text-gray-700 mb-1">
                                            Target Light Hours <span className="text-red-500">*</span>
                                        </label>
                                        <input
                                            type="number"
                                            step="0.01"
                                            required
                                            value={formData.target_light_hours}
                                            onChange={(e) => setFormData({ ...formData, target_light_hours: e.target.value })}
                                            className="w-full px-3 py-2 border border-gray-300 rounded-lg"
                                        />
                                    </div>
                                    <div>
                                        <label className="block text-sm font-medium text-gray-700 mb-1">
                                            Ventilation Strategy <span className="text-red-500">*</span>
                                        </label>
                                        <select
                                            required
                                            value={formData.ventilation_strategy}
                                            onChange={(e) => setFormData({ ...formData, ventilation_strategy: e.target.value })}
                                            className="w-full px-3 py-2 border border-gray-300 rounded-lg"
                                        >
                                            <option value="NATURAL">Natural</option>
                                            <option value="FORCED">Forced</option>
                                        </select>
                                    </div>
                                    <div>
                                        <label className="block text-sm font-medium text-gray-700 mb-1">
                                            Shade Net Percentage (%)
                                        </label>
                                        <input
                                            type="number"
                                            step="0.01"
                                            value={formData.shade_net_percentage}
                                            onChange={(e) => setFormData({ ...formData, shade_net_percentage: e.target.value })}
                                            className="w-full px-3 py-2 border border-gray-300 rounded-lg"
                                        />
                                    </div>
                                </div>
                            </div>

                            {/* Notes */}
                            <div>
                                <label className="block text-sm font-medium text-gray-700 mb-1">
                                    Notes
                                </label>
                                <textarea
                                    value={formData.notes}
                                    onChange={(e) => setFormData({ ...formData, notes: e.target.value })}
                                    rows="3"
                                    className="w-full px-3 py-2 border border-gray-300 rounded-lg"
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
                                    {editingCycle ? 'Update' : 'Create'} Cycle
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            )}
        </div>
    );
}
