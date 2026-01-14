import React, { useEffect, useState } from 'react';
import api from '../utils/api';
import { Package, Plus, Edit, Trash2, CheckCircle, X, Eye } from 'lucide-react';

export default function HarvestRecords() {
    const [records, setRecords] = useState([]);
    const [cycles, setCycles] = useState([]);
    const [loading, setLoading] = useState(true);
    const [showModal, setShowModal] = useState(false);
    const [showCrateModal, setShowCrateModal] = useState(false);
    const [showRecordDetail, setShowRecordDetail] = useState(false);
    const [editingRecord, setEditingRecord] = useState(null);
    const [selectedRecord, setSelectedRecord] = useState(null);
    const [recordCrates, setRecordCrates] = useState([]);
    const [formData, setFormData] = useState({
        production_cycle_id: '',
        harvest_date: new Date().toISOString().slice(0, 10),
        notes: '',
    });
    const [crateData, setCrateData] = useState({
        grade: 'A',
        crate_count: 1,
        total_weight_kg: '',
        storage_location_id: '',
        notes: '',
    });
    const [storageLocations, setStorageLocations] = useState([]);
    const [filters, setFilters] = useState({
        production_cycle_id: '',
        greenhouse_id: '',
        from: '',
        to: '',
        status: '',
    });

    useEffect(() => {
        fetchData();
        fetchStorageLocations();
    }, []);

    useEffect(() => {
        fetchRecords();
    }, [filters]);

    const fetchData = async () => {
        try {
            const [cyclesRes] = await Promise.all([
                api.get('/api/v1/production-cycles?per_page=1000'),
            ]);

            // Handle different response structures (same pattern as ProductionCycles.jsx)
            const cyclesData = cyclesRes.data?.data || cyclesRes.data || [];
            
            // Filter to only show ACTIVE or HARVESTING cycles (harvest can only be recorded for active cycles)
            const activeCycles = Array.isArray(cyclesData) 
                ? cyclesData.filter(c => {
                    if (!c) return false;
                    // Check both possible field names
                    const status = c.cycle_status || c.status;
                    return ['ACTIVE', 'HARVESTING'].includes(status);
                })
                : [];
            
            setCycles(activeCycles);
        } catch (error) {
            console.error('Error fetching data:', error);
            console.error('Error response:', error.response);
            alert('Error loading data: ' + (error.response?.data?.message || error.message));
        } finally {
            setLoading(false);
        }
    };

    const fetchRecords = async () => {
        try {
            const params = new URLSearchParams();
            if (filters.production_cycle_id) params.append('production_cycle_id', filters.production_cycle_id);
            if (filters.greenhouse_id) params.append('greenhouse_id', filters.greenhouse_id);
            if (filters.from) params.append('from', filters.from);
            if (filters.to) params.append('to', filters.to);
            if (filters.status) params.append('status', filters.status);

            const recordsRes = await api.get(`/api/v1/harvest-records?${params.toString()}`);
            const recordsData = recordsRes.data?.data || recordsRes.data || [];
            setRecords(Array.isArray(recordsData) ? recordsData : []);
        } catch (error) {
            console.error('Error fetching records:', error);
            alert('Error loading records: ' + (error.response?.data?.message || error.message));
        }
    };

    const fetchStorageLocations = async () => {
        try {
            const response = await api.get('/api/v1/inventory-locations');
            const locationsData = response.data?.data || response.data || [];
            setStorageLocations(Array.isArray(locationsData) ? locationsData : []);
        } catch (error) {
            console.error('Error fetching storage locations:', error);
        }
    };

    const handleModalOpen = async () => {
        // Refetch cycles to ensure we have the latest data
        await fetchData();
        
        setEditingRecord(null);
        setFormData({
            production_cycle_id: '',
            harvest_date: new Date().toISOString().slice(0, 10),
            notes: '',
        });
        setShowModal(true);
    };

    const handleEdit = (record) => {
        if (record.status !== 'DRAFT') {
            alert('Only DRAFT records can be edited');
            return;
        }
        setEditingRecord(record);
        setFormData({
            production_cycle_id: record.production_cycle?.id || '',
            harvest_date: record.harvest_date || new Date().toISOString().slice(0, 10),
            notes: record.notes || '',
        });
        setShowModal(true);
    };

    const handleSubmit = async (e) => {
        e.preventDefault();
        try {
            if (editingRecord) {
                await api.patch(`/api/v1/harvest-records/${editingRecord.id}`, formData);
            } else {
                await api.post('/api/v1/harvest-records', formData);
            }
            setShowModal(false);
            fetchRecords();
        } catch (error) {
            const errorMsg = error.response?.data?.message || error.response?.data?.errors || 'Unknown error';
            alert('Error saving record: ' + (typeof errorMsg === 'string' ? errorMsg : JSON.stringify(errorMsg)));
        }
    };

    const handleAction = async (recordId, action) => {
        try {
            await api.post(`/api/v1/harvest-records/${recordId}/${action}`);
            fetchRecords();
        } catch (error) {
            alert('Error: ' + (error.response?.data?.message || error.message));
        }
    };

    const handleDelete = async (recordId) => {
        if (!confirm('Delete this harvest record?')) return;
        try {
            await api.delete(`/api/v1/harvest-records/${recordId}`);
            fetchRecords();
        } catch (error) {
            alert('Error deleting record: ' + (error.response?.data?.message || error.message));
        }
    };

    const handleViewRecord = async (record) => {
        setSelectedRecord(record);
        try {
            const response = await api.get(`/api/v1/harvest-records/${record.id}`);
            setRecordCrates(response.data.data?.crates || []);
            setShowRecordDetail(true);
        } catch (error) {
            console.error('Error fetching record details:', error);
            alert('Error loading record details: ' + (error.response?.data?.message || error.message));
        }
    };

    const handleCrateModalOpen = (record) => {
        setSelectedRecord(record);
        setCrateData({
            grade: 'A',
            crate_count: 1,
            total_weight_kg: '',
            storage_location_id: '',
            notes: '',
        });
        setShowCrateModal(true);
    };

    const handleCrateSubmit = async (e) => {
        e.preventDefault();
        try {
            // Prepare payload with crate_count and total_weight_kg
            const payload = {
                grade: crateData.grade,
                crate_count: parseInt(crateData.crate_count) || 1,
                total_weight_kg: parseFloat(crateData.total_weight_kg) || 0,
                storage_location_id: crateData.storage_location_id || null,
                notes: crateData.notes || '',
            };
            
            await api.post(`/api/v1/harvest-records/${selectedRecord.id}/crates`, payload);
            setShowCrateModal(false);
            // Refresh crates if detail view is open
            if (showRecordDetail && selectedRecord) {
                const response = await api.get(`/api/v1/harvest-records/${selectedRecord.id}`);
                setRecordCrates(response.data.data?.crates || []);
            }
            fetchRecords();
        } catch (error) {
            const errorMsg = error.response?.data?.message || error.response?.data?.errors || 'Unknown error';
            alert('Error adding crate: ' + (typeof errorMsg === 'string' ? errorMsg : JSON.stringify(errorMsg)));
        }
    };

    const handleDeleteCrate = async (crateId) => {
        if (!confirm('Delete this crate?')) return;
        try {
            await api.delete(`/api/v1/harvest-crates/${crateId}`);
            // Refresh crates if detail view is open
            if (showRecordDetail && selectedRecord) {
                const response = await api.get(`/api/v1/harvest-records/${selectedRecord.id}`);
                setRecordCrates(response.data.data?.crates || []);
            }
            fetchRecords();
        } catch (error) {
            alert('Error deleting crate: ' + (error.response?.data?.message || error.message));
        }
    };

    const getStatusColor = (status) => {
        const colors = {
            DRAFT: 'bg-gray-100 text-gray-800',
            SUBMITTED: 'bg-blue-100 text-blue-800',
            APPROVED: 'bg-green-100 text-green-800',
        };
        return colors[status] || 'bg-gray-100 text-gray-800';
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
            <div className="mb-8 flex justify-between items-center">
                <div>
                    <h1 className="text-3xl font-bold text-gray-900">Harvest Records</h1>
                    <p className="mt-2 text-gray-600">Record bell pepper harvests with grade breakdown</p>
                </div>
                <button
                    onClick={handleModalOpen}
                    className="flex items-center px-4 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700"
                >
                    <Plus className="h-5 w-5 mr-2" />
                    New Harvest Record
                </button>
            </div>

            {/* Filters */}
            <div className="mb-6 bg-white p-4 rounded-lg shadow">
                <div className="grid grid-cols-5 gap-4">
                    <div>
                        <label className="block text-sm font-medium text-gray-700 mb-2">Production Cycle</label>
                        <select
                            value={filters.production_cycle_id}
                            onChange={(e) => setFilters({ ...filters, production_cycle_id: e.target.value })}
                            className="w-full px-3 py-2 border border-gray-300 rounded-lg"
                        >
                            <option value="">All Cycles</option>
                            {cycles.map((cycle) => (
                                <option key={cycle.id} value={cycle.id}>
                                    {cycle.production_cycle_code}
                                </option>
                            ))}
                        </select>
                    </div>
                    <div>
                        <label className="block text-sm font-medium text-gray-700 mb-2">From Date</label>
                        <input
                            type="date"
                            value={filters.from}
                            onChange={(e) => setFilters({ ...filters, from: e.target.value })}
                            className="w-full px-3 py-2 border border-gray-300 rounded-lg"
                        />
                    </div>
                    <div>
                        <label className="block text-sm font-medium text-gray-700 mb-2">To Date</label>
                        <input
                            type="date"
                            value={filters.to}
                            onChange={(e) => setFilters({ ...filters, to: e.target.value })}
                            className="w-full px-3 py-2 border border-gray-300 rounded-lg"
                        />
                    </div>
                    <div>
                        <label className="block text-sm font-medium text-gray-700 mb-2">Status</label>
                        <select
                            value={filters.status}
                            onChange={(e) => setFilters({ ...filters, status: e.target.value })}
                            className="w-full px-3 py-2 border border-gray-300 rounded-lg"
                        >
                            <option value="">All Statuses</option>
                            <option value="DRAFT">Draft</option>
                            <option value="SUBMITTED">Submitted</option>
                            <option value="APPROVED">Approved</option>
                        </select>
                    </div>
                    <div className="flex items-end">
                        <button
                            onClick={() => setFilters({ production_cycle_id: '', greenhouse_id: '', from: '', to: '', status: '' })}
                            className="w-full px-4 py-2 border border-gray-300 rounded-lg text-gray-700 hover:bg-gray-50"
                        >
                            Clear Filters
                        </button>
                    </div>
                </div>
            </div>

            {/* Records Table */}
            <div className="bg-white rounded-lg shadow overflow-hidden">
                <table className="min-w-full divide-y divide-gray-200">
                    <thead className="bg-gray-50">
                        <tr>
                            <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Date</th>
                            <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Production Cycle</th>
                            <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Greenhouse</th>
                            <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Grade A (kg)</th>
                            <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Grade B (kg)</th>
                            <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Grade C (kg)</th>
                            <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Total (kg)</th>
                            <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Crates</th>
                            <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                            <th className="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                        </tr>
                    </thead>
                    <tbody className="bg-white divide-y divide-gray-200">
                        {records.length === 0 ? (
                            <tr>
                                <td colSpan="10" className="px-6 py-4 text-center text-gray-500">
                                    No harvest records found
                                </td>
                            </tr>
                        ) : (
                            records.map((record) => (
                                <tr key={record.id}>
                                    <td className="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                        {record.harvest_date || 'N/A'}
                                    </td>
                                    <td className="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                        {record.production_cycle?.production_cycle_code || 'N/A'}
                                    </td>
                                    <td className="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                        {record.greenhouse?.name || 'N/A'}
                                    </td>
                                    <td className="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                        {Number(record.totals?.a_kg || 0).toFixed(2)}
                                    </td>
                                    <td className="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                        {Number(record.totals?.b_kg || 0).toFixed(2)}
                                    </td>
                                    <td className="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                        {Number(record.totals?.c_kg || 0).toFixed(2)}
                                    </td>
                                    <td className="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
                                        {Number(record.totals?.total_kg || 0).toFixed(2)}
                                    </td>
                                    <td className="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                        {record.totals?.crate_count_total || 0}
                                    </td>
                                    <td className="px-6 py-4 whitespace-nowrap">
                                        <span className={`px-2 py-1 text-xs font-semibold rounded-full ${getStatusColor(record.status)}`}>
                                            {record.status}
                                        </span>
                                    </td>
                                    <td className="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                        <div className="flex justify-end space-x-2">
                                            <button
                                                onClick={() => handleViewRecord(record)}
                                                className="text-blue-600 hover:text-blue-900"
                                                title="View Details & Crates"
                                            >
                                                <Package className="h-4 w-4" />
                                            </button>
                                            {record.status === 'DRAFT' && (
                                                <>
                                                    <button
                                                        onClick={() => handleCrateModalOpen(record)}
                                                        className="text-green-600 hover:text-green-900"
                                                        title="Add Crate"
                                                    >
                                                        <Plus className="h-4 w-4" />
                                                    </button>
                                                    <button
                                                        onClick={() => handleAction(record.id, 'submit')}
                                                        className="text-green-600 hover:text-green-900"
                                                        title="Submit"
                                                    >
                                                        <CheckCircle className="h-4 w-4" />
                                                    </button>
                                                </>
                                            )}
                                            {record.status === 'SUBMITTED' && (
                                                <button
                                                    onClick={() => handleAction(record.id, 'approve')}
                                                    className="text-green-600 hover:text-green-900"
                                                    title="Approve"
                                                    >
                                                    <CheckCircle className="h-4 w-4" />
                                                </button>
                                            )}
                                            <button
                                                onClick={() => handleEdit(record)}
                                                className="text-blue-600 hover:text-blue-900"
                                                title="Edit"
                                            >
                                                <Edit className="h-4 w-4" />
                                            </button>
                                            {record.status === 'DRAFT' && (
                                                <button
                                                    onClick={() => handleDelete(record.id)}
                                                    className="text-red-600 hover:text-red-900"
                                                    title="Delete"
                                                >
                                                    <Trash2 className="h-4 w-4" />
                                                </button>
                                            )}
                                        </div>
                                    </td>
                                </tr>
                            ))
                        )}
                    </tbody>
                </table>
            </div>

            {/* Harvest Record Modal */}
            {showModal && (
                <div className="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50 p-4">
                    <div className="bg-white rounded-lg max-w-md w-full">
                        <div className="sticky top-0 bg-white border-b border-gray-200 px-6 py-4 flex justify-between items-center">
                            <h2 className="text-xl font-bold text-gray-900">
                                {editingRecord ? 'Edit Harvest Record' : 'New Harvest Record'}
                            </h2>
                            <button
                                onClick={() => setShowModal(false)}
                                className="text-gray-400 hover:text-gray-600"
                            >
                                <X className="h-6 w-6" />
                            </button>
                        </div>
                        <form onSubmit={handleSubmit} className="p-6 space-y-4">
                            <div>
                                <label className="block text-sm font-medium text-gray-700 mb-1">
                                    Production Cycle <span className="text-red-500">*</span>
                                </label>
                                <select
                                    required
                                    value={formData.production_cycle_id}
                                    onChange={(e) => setFormData({ ...formData, production_cycle_id: e.target.value })}
                                    className="w-full px-3 py-2 border border-gray-300 rounded-lg"
                                    disabled={!!editingRecord || cycles.length === 0}
                                >
                                    <option value="">
                                        {cycles.length === 0 
                                            ? 'No active production cycles available' 
                                            : 'Select Production Cycle'}
                                    </option>
                                    {cycles.map((cycle) => (
                                        <option key={cycle.id} value={cycle.id}>
                                            {cycle.production_cycle_code} - {cycle.greenhouse?.name || 'N/A'} ({cycle.cycle_status})
                                        </option>
                                    ))}
                                </select>
                                {cycles.length === 0 && (
                                    <p className="mt-1 text-xs text-amber-600">
                                        No active or harvesting production cycles found. Please start a production cycle first.
                                    </p>
                                )}
                            </div>
                            <div>
                                <label className="block text-sm font-medium text-gray-700 mb-1">
                                    Harvest Date <span className="text-red-500">*</span>
                                </label>
                                {(() => {
                                    const selectedCycle = cycles.find(c => c.id === parseInt(formData.production_cycle_id));
                                    let minDate = null;
                                    if (selectedCycle && selectedCycle.planting_date) {
                                        const plantingDate = new Date(selectedCycle.planting_date);
                                        const minHarvestDate = new Date(plantingDate);
                                        minHarvestDate.setDate(minHarvestDate.getDate() + 40);
                                        minDate = minHarvestDate.toISOString().split('T')[0];
                                    }
                                    const isDateTooEarly = minDate && formData.harvest_date && new Date(formData.harvest_date) < new Date(minDate);
                                    
                                    return (
                                        <>
                                            <input
                                                type="date"
                                                required
                                                value={formData.harvest_date}
                                                min={minDate || undefined}
                                                onChange={(e) => setFormData({ ...formData, harvest_date: e.target.value })}
                                                className={`w-full px-3 py-2 border rounded-lg ${isDateTooEarly ? 'border-red-500' : 'border-gray-300'}`}
                                            />
                                            {selectedCycle && selectedCycle.planting_date && (
                                                <p className="mt-1 text-xs text-gray-500">
                                                    Minimum harvest date: {minDate} (40 days after planting on {selectedCycle.planting_date})
                                                </p>
                                            )}
                                            {isDateTooEarly && (
                                                <p className="mt-1 text-xs text-red-600">
                                                    Harvest date must be at least 40 days after the planting date ({selectedCycle.planting_date})
                                                </p>
                                            )}
                                        </>
                                    );
                                })()}
                            </div>
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
                            {!editingRecord && (
                                <div className="bg-blue-50 border border-blue-200 rounded-lg p-4">
                                    <p className="text-sm text-blue-800">
                                        <strong>Note:</strong> After creating the harvest record, click the <strong>Package icon</strong> to view details and add crates with grade breakdown (A/B/C) and weights.
                                    </p>
                                </div>
                            )}
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
                                    {editingRecord ? 'Update' : 'Create'} Record
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            )}

            {/* Record Detail Modal (Shows Crates) */}
            {showRecordDetail && selectedRecord && (
                <div className="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50 p-4">
                    <div className="bg-white rounded-lg max-w-4xl w-full max-h-[90vh] overflow-y-auto">
                        <div className="sticky top-0 bg-white border-b border-gray-200 px-6 py-4 flex justify-between items-center">
                            <h2 className="text-xl font-bold text-gray-900">
                                Harvest Record Details - {selectedRecord.harvest_date}
                            </h2>
                            <button
                                onClick={() => {
                                    setShowRecordDetail(false);
                                    setSelectedRecord(null);
                                    setRecordCrates([]);
                                }}
                                className="text-gray-400 hover:text-gray-600"
                            >
                                <X className="h-6 w-6" />
                            </button>
                        </div>
                        <div className="p-6">
                            <div className="mb-6 grid grid-cols-3 gap-4">
                                <div className="bg-gray-50 p-4 rounded-lg">
                                    <p className="text-sm text-gray-600">Production Cycle</p>
                                    <p className="text-lg font-semibold">{selectedRecord.production_cycle?.production_cycle_code || 'N/A'}</p>
                                </div>
                                <div className="bg-gray-50 p-4 rounded-lg">
                                    <p className="text-sm text-gray-600">Greenhouse</p>
                                    <p className="text-lg font-semibold">{selectedRecord.greenhouse?.name || 'N/A'}</p>
                                </div>
                                <div className="bg-gray-50 p-4 rounded-lg">
                                    <p className="text-sm text-gray-600">Status</p>
                                    <span className={`px-2 py-1 text-xs font-semibold rounded-full ${getStatusColor(selectedRecord.status)}`}>
                                        {selectedRecord.status}
                                    </span>
                                </div>
                            </div>

                            <div className="mb-6">
                                <div className="flex justify-between items-center mb-4">
                                    <h3 className="text-lg font-semibold text-gray-900">Crates ({recordCrates.length})</h3>
                                    {selectedRecord.status === 'DRAFT' && (
                                        <button
                                            onClick={() => handleCrateModalOpen(selectedRecord)}
                                            className="flex items-center px-3 py-1 bg-green-600 text-white rounded-lg hover:bg-green-700 text-sm"
                                        >
                                            <Plus className="h-4 w-4 mr-1" />
                                            Add Crate
                                        </button>
                                    )}
                                </div>

                                {recordCrates.length === 0 ? (
                                    <div className="text-center py-8 text-gray-500">
                                        <Package className="h-12 w-12 mx-auto mb-2 text-gray-400" />
                                        <p>No crates recorded yet</p>
                                        {selectedRecord.status === 'DRAFT' && (
                                            <button
                                                onClick={() => handleCrateModalOpen(selectedRecord)}
                                                className="mt-4 px-4 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700"
                                            >
                                                Add First Crate
                                            </button>
                                        )}
                                    </div>
                                ) : (
                                    <div className="bg-white rounded-lg border border-gray-200 overflow-hidden">
                                        <table className="min-w-full divide-y divide-gray-200">
                                            <thead className="bg-gray-50">
                                                <tr>
                                                    <th className="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Crate #</th>
                                                    <th className="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Grade</th>
                                                    <th className="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Weight (kg)</th>
                                                    <th className="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Weighed At</th>
                                                    <th className="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Label Code</th>
                                                    {selectedRecord.status === 'DRAFT' && (
                                                        <th className="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase">Actions</th>
                                                    )}
                                                </tr>
                                            </thead>
                                            <tbody className="bg-white divide-y divide-gray-200">
                                                {recordCrates.map((crate) => (
                                                    <tr key={crate.id}>
                                                        <td className="px-4 py-3 whitespace-nowrap text-sm font-medium text-gray-900">
                                                            {crate.crate_number}
                                                        </td>
                                                        <td className="px-4 py-3 whitespace-nowrap">
                                                            <span className={`px-2 py-1 text-xs font-semibold rounded-full ${
                                                                crate.grade === 'A' ? 'bg-green-100 text-green-800' :
                                                                crate.grade === 'B' ? 'bg-yellow-100 text-yellow-800' :
                                                                'bg-orange-100 text-orange-800'
                                                            }`}>
                                                                Grade {crate.grade}
                                                            </span>
                                                        </td>
                                                        <td className="px-4 py-3 whitespace-nowrap text-sm text-gray-900">
                                                            {Number(crate.weight_kg || 0).toFixed(2)} kg
                                                        </td>
                                                        <td className="px-4 py-3 whitespace-nowrap text-sm text-gray-500">
                                                            {crate.weighed_at ? new Date(crate.weighed_at).toLocaleString() : 'N/A'}
                                                        </td>
                                                        <td className="px-4 py-3 whitespace-nowrap text-sm text-gray-500">
                                                            {crate.label_code || '-'}
                                                        </td>
                                                        {selectedRecord.status === 'DRAFT' && (
                                                            <td className="px-4 py-3 whitespace-nowrap text-right text-sm">
                                                                <button
                                                                    onClick={() => handleDeleteCrate(crate.id)}
                                                                    className="text-red-600 hover:text-red-900"
                                                                    title="Delete"
                                                                >
                                                                    <Trash2 className="h-4 w-4" />
                                                                </button>
                                                            </td>
                                                        )}
                                                    </tr>
                                                ))}
                                            </tbody>
                                        </table>
                                    </div>
                                )}
                            </div>

                            <div className="grid grid-cols-4 gap-4 mb-6">
                                <div className="bg-green-50 p-4 rounded-lg">
                                    <p className="text-sm text-gray-600">Grade A Total</p>
                                    <p className="text-2xl font-bold text-green-600">
                                        {Number(selectedRecord.totals?.a_kg || 0).toFixed(2)} kg
                                    </p>
                                    <p className="text-xs text-gray-500">{selectedRecord.totals?.crate_count_a || 0} crates</p>
                                </div>
                                <div className="bg-yellow-50 p-4 rounded-lg">
                                    <p className="text-sm text-gray-600">Grade B Total</p>
                                    <p className="text-2xl font-bold text-yellow-600">
                                        {Number(selectedRecord.totals?.b_kg || 0).toFixed(2)} kg
                                    </p>
                                    <p className="text-xs text-gray-500">{selectedRecord.totals?.crate_count_b || 0} crates</p>
                                </div>
                                <div className="bg-orange-50 p-4 rounded-lg">
                                    <p className="text-sm text-gray-600">Grade C Total</p>
                                    <p className="text-2xl font-bold text-orange-600">
                                        {Number(selectedRecord.totals?.c_kg || 0).toFixed(2)} kg
                                    </p>
                                    <p className="text-xs text-gray-500">{selectedRecord.totals?.crate_count_c || 0} crates</p>
                                </div>
                                <div className="bg-blue-50 p-4 rounded-lg">
                                    <p className="text-sm text-gray-600">Grand Total</p>
                                    <p className="text-2xl font-bold text-blue-600">
                                        {Number(selectedRecord.totals?.total_kg || 0).toFixed(2)} kg
                                    </p>
                                    <p className="text-xs text-gray-500">{selectedRecord.totals?.crate_count_total || 0} crates</p>
                                </div>
                            </div>

                            {selectedRecord.notes && (
                                <div className="mb-4">
                                    <p className="text-sm font-medium text-gray-700 mb-1">Notes</p>
                                    <p className="text-sm text-gray-600">{selectedRecord.notes}</p>
                                </div>
                            )}

                            <div className="flex justify-end pt-4 border-t">
                                <button
                                    onClick={() => {
                                        setShowRecordDetail(false);
                                        setSelectedRecord(null);
                                        setRecordCrates([]);
                                    }}
                                    className="px-4 py-2 border border-gray-300 rounded-lg text-gray-700 hover:bg-gray-50"
                                >
                                    Close
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            )}

            {/* Add Crate Modal */}
            {showCrateModal && selectedRecord && (
                <div className="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50 p-4">
                    <div className="bg-white rounded-lg max-w-md w-full">
                        <div className="sticky top-0 bg-white border-b border-gray-200 px-6 py-4 flex justify-between items-center">
                            <h2 className="text-xl font-bold text-gray-900">Add Crate</h2>
                            <button
                                onClick={() => setShowCrateModal(false)}
                                className="text-gray-400 hover:text-gray-600"
                            >
                                <X className="h-6 w-6" />
                            </button>
                        </div>
                        <form onSubmit={handleCrateSubmit} className="p-6 space-y-4">
                            <div>
                                <label className="block text-sm font-medium text-gray-700 mb-1">
                                    Grade <span className="text-red-500">*</span>
                                </label>
                                <select
                                    required
                                    value={crateData.grade}
                                    onChange={(e) => setCrateData({ ...crateData, grade: e.target.value })}
                                    className="w-full px-3 py-2 border border-gray-300 rounded-lg"
                                >
                                    <option value="A">Grade A</option>
                                    <option value="B">Grade B</option>
                                    <option value="C">Grade C</option>
                                </select>
                            </div>
                            <div>
                                <label className="block text-sm font-medium text-gray-700 mb-1">
                                    Crate Count <span className="text-red-500">*</span>
                                </label>
                                <input
                                    type="number"
                                    min="1"
                                    required
                                    value={crateData.crate_count}
                                    onChange={(e) => setCrateData({ ...crateData, crate_count: e.target.value })}
                                    className="w-full px-3 py-2 border border-gray-300 rounded-lg"
                                    placeholder="Number of crates"
                                />
                                <p className="mt-1 text-xs text-gray-500">Enter the number of crates of this grade</p>
                            </div>
                            <div>
                                <label className="block text-sm font-medium text-gray-700 mb-1">
                                    Total Weight (kg) <span className="text-red-500">*</span>
                                </label>
                                <input
                                    type="number"
                                    step="0.01"
                                    min="0.01"
                                    required
                                    value={crateData.total_weight_kg}
                                    onChange={(e) => setCrateData({ ...crateData, total_weight_kg: e.target.value })}
                                    className="w-full px-3 py-2 border border-gray-300 rounded-lg"
                                    placeholder="Total weight for all crates"
                                />
                                {crateData.crate_count > 1 && crateData.total_weight_kg && (
                                    <p className="mt-1 text-xs text-blue-600">
                                        Weight per crate: {(parseFloat(crateData.total_weight_kg) / parseInt(crateData.crate_count)).toFixed(2)} kg
                                    </p>
                                )}
                            </div>
                            <div>
                                <label className="block text-sm font-medium text-gray-700 mb-1">
                                    Storage Location <span className="text-red-500">*</span>
                                </label>
                                <select
                                    required
                                    value={crateData.storage_location_id}
                                    onChange={(e) => setCrateData({ ...crateData, storage_location_id: e.target.value })}
                                    className="w-full px-3 py-2 border border-gray-300 rounded-lg"
                                >
                                    <option value="">Select Storage Location</option>
                                    {storageLocations.map((location) => (
                                        <option key={location.id} value={location.id}>
                                            {location.name} ({location.type})
                                        </option>
                                    ))}
                                </select>
                                <p className="mt-1 text-xs text-amber-600">
                                    <strong>Required:</strong> All harvested items must be allocated to a storage location if not immediately sold.
                                </p>
                            </div>
                            <div>
                                <label className="block text-sm font-medium text-gray-700 mb-1">
                                    Notes
                                </label>
                                <textarea
                                    value={crateData.notes}
                                    onChange={(e) => setCrateData({ ...crateData, notes: e.target.value })}
                                    rows="2"
                                    className="w-full px-3 py-2 border border-gray-300 rounded-lg"
                                />
                            </div>
                            <div className="flex justify-end space-x-4 pt-4 border-t">
                                <button
                                    type="button"
                                    onClick={() => setShowCrateModal(false)}
                                    className="px-4 py-2 border border-gray-300 rounded-lg text-gray-700 hover:bg-gray-50"
                                >
                                    Cancel
                                </button>
                                <button
                                    type="submit"
                                    className="px-4 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700"
                                >
                                    {crateData.crate_count > 1 ? `Add ${crateData.crate_count} Crates` : 'Add Crate'}
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            )}
        </div>
    );
}
