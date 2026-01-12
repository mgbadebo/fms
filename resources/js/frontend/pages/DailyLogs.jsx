import React, { useEffect, useState } from 'react';
import api from '../utils/api';
import { FileText, Plus, Edit, Trash2, Send, Clock, X } from 'lucide-react';

export default function DailyLogs() {
    const [logs, setLogs] = useState([]);
    const [greenhouses, setGreenhouses] = useState([]);
    const [cycles, setCycles] = useState([]);
    const [activityTypes, setActivityTypes] = useState([]);
    const [users, setUsers] = useState([]);
    const [inputItems, setInputItems] = useState([]);
    const [loading, setLoading] = useState(true);
    const [selectedGreenhouseId, setSelectedGreenhouseId] = useState('');
    const [showModal, setShowModal] = useState(false);
    const [editingLog, setEditingLog] = useState(null);
    const [formData, setFormData] = useState({
        production_cycle_id: '',
        log_date: new Date().toISOString().slice(0, 10),
        issues_notes: '',
        items: [],
    });

    useEffect(() => {
        fetchData();
    }, []);

    useEffect(() => {
        if (selectedGreenhouseId) {
            fetchLogs(selectedGreenhouseId);
            fetchActiveCycles(selectedGreenhouseId);
        } else {
            setLogs([]);
            setCycles([]);
        }
    }, [selectedGreenhouseId]);

    const fetchData = async () => {
        try {
            const [greenhousesRes, activityTypesRes, usersRes] = await Promise.all([
                api.get('/api/v1/greenhouses?per_page=1000'),
                api.get('/api/v1/activity-types?per_page=1000'),
                api.get('/api/v1/users?per_page=1000'),
            ]);

            const greenhousesData = greenhousesRes.data?.data || greenhousesRes.data || [];
            const activityTypesData = activityTypesRes.data?.data || activityTypesRes.data || [];
            const usersData = usersRes.data?.data || usersRes.data || [];

            setGreenhouses(Array.isArray(greenhousesData) ? greenhousesData : []);
            setActivityTypes(Array.isArray(activityTypesData) ? activityTypesData : []);
            setUsers(Array.isArray(usersData) ? usersData : []);

            // Try to fetch input items, but don't fail if endpoint doesn't exist
            try {
                const inputItemsRes = await api.get('/api/v1/input-items?per_page=1000');
                const inputItemsData = inputItemsRes.data?.data || inputItemsRes.data || [];
                setInputItems(Array.isArray(inputItemsData) ? inputItemsData : []);
            } catch (inputError) {
                // Input items endpoint doesn't exist, that's okay - we can use input_name instead
                console.warn('Input items endpoint not available, using input_name field only');
                setInputItems([]);
            }
        } catch (error) {
            console.error('Error fetching data:', error);
            alert('Error loading data: ' + (error.response?.data?.message || error.message));
        } finally {
            setLoading(false);
        }
    };

    const fetchActiveCycles = async (greenhouseId) => {
        try {
            const response = await api.get(`/api/v1/production-cycles?greenhouse_id=${greenhouseId}`);
            const cyclesData = response.data?.data || response.data || [];
            // Show PLANNED, ACTIVE, or HARVESTING cycles (PLANNED cycles can be started)
            const availableCycles = Array.isArray(cyclesData) 
                ? cyclesData.filter(c => ['PLANNED', 'ACTIVE', 'HARVESTING'].includes(c.cycle_status))
                : [];
            setCycles(availableCycles);
        } catch (error) {
            console.error('Error fetching cycles:', error);
            setCycles([]);
        }
    };

    const fetchLogs = async (greenhouseId) => {
        try {
            const response = await api.get(`/api/v1/greenhouses/${greenhouseId}/daily-logs`);
            const logsData = response.data?.data || response.data || [];
            setLogs(Array.isArray(logsData) ? logsData : []);
        } catch (error) {
            console.error('Error fetching logs:', error);
            setLogs([]);
        }
    };

    const handleModalOpen = async () => {
        if (!selectedGreenhouseId) {
            alert('Please select a greenhouse first');
            return;
        }
        if (cycles.length === 0) {
            alert('No production cycles found for this greenhouse. Please create a production cycle first.');
            return;
        }
        
        // Check if there are only PLANNED cycles - offer to start one
        const plannedCycles = cycles.filter(c => c.cycle_status === 'PLANNED');
        const activeCycles = cycles.filter(c => ['ACTIVE', 'HARVESTING'].includes(c.cycle_status));
        
        if (activeCycles.length === 0 && plannedCycles.length > 0) {
            const cycleToStart = plannedCycles[0];
            if (confirm(`No active production cycles found. Would you like to start the cycle "${cycleToStart.production_cycle_code || cycleToStart.id}" first?`)) {
                try {
                    await api.post(`/api/v1/production-cycles/${cycleToStart.id}/start`);
                    // Refresh cycles after starting
                    await fetchActiveCycles(selectedGreenhouseId);
                    // Continue with modal opening after a brief delay
                    setTimeout(() => {
                        setEditingLog(null);
                        setFormData({
                            production_cycle_id: cycleToStart.id,
                            log_date: new Date().toISOString().slice(0, 10),
                            issues_notes: '',
                            items: [],
                        });
                        setShowModal(true);
                    }, 500);
                    return;
                } catch (error) {
                    alert('Error starting cycle: ' + (error.response?.data?.message || error.message));
                    return;
                }
            } else {
                return; // User cancelled
            }
        }
        
        setEditingLog(null);
        setFormData({
            production_cycle_id: activeCycles.length > 0 
                ? (activeCycles.length === 1 ? activeCycles[0].id : '')
                : (cycles.length === 1 ? cycles[0].id : ''),
            log_date: new Date().toISOString().slice(0, 10),
            issues_notes: '',
            items: [],
        });
        setShowModal(true);
    };

    const handleEdit = (log) => {
        if (log.status !== 'DRAFT') {
            alert('Only DRAFT logs can be edited');
            return;
        }
        setEditingLog(log);
        setFormData({
            production_cycle_id: log.production_cycle_id || (cycles.length === 1 ? cycles[0].id : ''),
            log_date: log.log_date || new Date().toISOString().slice(0, 10),
            issues_notes: log.issues_notes || '',
            items: log.items?.map(item => ({
                id: item.id,
                activity_type_id: item.activity_type?.id || '',
                performed_by_user_id: item.performed_by?.id || '',
                started_at: item.started_at ? new Date(item.started_at).toISOString().slice(0, 16) : '',
                ended_at: item.ended_at ? new Date(item.ended_at).toISOString().slice(0, 16) : '',
                quantity: item.quantity || '',
                unit: item.unit || '',
                notes: item.notes || '',
                meta: item.meta || {},
                inputs: item.inputs?.map(input => ({
                    id: input.id,
                    input_item_id: input.input_item?.id || '',
                    input_name: input.input_name || '',
                    quantity: input.quantity || '',
                    unit: input.unit || '',
                    notes: input.notes || '',
                })) || [],
            })) || [],
        });
        setShowModal(true);
    };

    const addActivityItem = () => {
        setFormData({
            ...formData,
            items: [
                ...formData.items,
                {
                    activity_type_id: '',
                    performed_by_user_id: '',
                    started_at: '',
                    ended_at: '',
                    quantity: '',
                    unit: '',
                    notes: '',
                    meta: {},
                    inputs: [],
                },
            ],
        });
    };

    const removeActivityItem = (index) => {
        setFormData({
            ...formData,
            items: formData.items.filter((_, i) => i !== index),
        });
    };

    const updateActivityItem = (index, field, value) => {
        const newItems = [...formData.items];
        newItems[index] = { ...newItems[index], [field]: value };
        setFormData({ ...formData, items: newItems });
    };

    const addInputToItem = (itemIndex) => {
        const newItems = [...formData.items];
        if (!newItems[itemIndex].inputs) {
            newItems[itemIndex].inputs = [];
        }
        newItems[itemIndex].inputs.push({
            input_item_id: '',
            input_name: '',
            quantity: '',
            unit: '',
            notes: '',
        });
        setFormData({ ...formData, items: newItems });
    };

    const removeInputFromItem = (itemIndex, inputIndex) => {
        const newItems = [...formData.items];
        newItems[itemIndex].inputs = newItems[itemIndex].inputs.filter((_, i) => i !== inputIndex);
        setFormData({ ...formData, items: newItems });
    };

    const updateInputInItem = (itemIndex, inputIndex, field, value) => {
        const newItems = [...formData.items];
        newItems[itemIndex].inputs[inputIndex] = {
            ...newItems[itemIndex].inputs[inputIndex],
            [field]: value,
        };
        setFormData({ ...formData, items: newItems });
    };

    const handleSubmit = async (e) => {
        e.preventDefault();
        if (formData.items.length === 0) {
            alert('Please add at least one activity item');
            return;
        }
        if (!formData.production_cycle_id) {
            alert('Please select a production cycle');
            return;
        }
        
        try {
            // Check if the selected cycle is PLANNED and needs to be started
            const selectedCycle = cycles.find(c => c.id === parseInt(formData.production_cycle_id));
            if (selectedCycle && selectedCycle.cycle_status === 'PLANNED') {
                if (confirm(`The production cycle "${selectedCycle.production_cycle_code || selectedCycle.id}" is PLANNED. It needs to be started before creating daily logs. Would you like to start it now?`)) {
                    try {
                        await api.post(`/api/v1/production-cycles/${selectedCycle.id}/start`);
                        // Refresh cycles after starting
                        await fetchActiveCycles(selectedGreenhouseId);
                    } catch (startError) {
                        alert('Error starting cycle: ' + (startError.response?.data?.message || startError.message));
                        return;
                    }
                } else {
                    return; // User cancelled
                }
            }
            
            const payload = {
                log_date: formData.log_date,
                issues_notes: formData.issues_notes,
                items: formData.items.map(item => ({
                    activity_type_id: item.activity_type_id,
                    performed_by_user_id: item.performed_by_user_id || null,
                    started_at: item.started_at || null,
                    ended_at: item.ended_at || null,
                    quantity: item.quantity || null,
                    unit: item.unit || null,
                    notes: item.notes || null,
                    meta: item.meta || null,
                    inputs: item.inputs?.map(input => ({
                        input_item_id: input.input_item_id || null,
                        input_name: input.input_name || null,
                        quantity: input.quantity,
                        unit: input.unit,
                        notes: input.notes || null,
                    })) || [],
                })),
            };

            if (editingLog) {
                await api.patch(`/api/v1/daily-logs/${editingLog.id}`, payload);
            } else {
                await api.post(`/api/v1/production-cycles/${formData.production_cycle_id}/daily-logs`, payload);
            }
            setShowModal(false);
            fetchLogs(selectedGreenhouseId);
        } catch (error) {
            const errorMsg = error.response?.data?.message || error.response?.data?.errors || 'Unknown error';
            alert('Error saving log: ' + (typeof errorMsg === 'string' ? errorMsg : JSON.stringify(errorMsg)));
        }
    };

    const handleSubmitLog = async (logId) => {
        if (!confirm('Submit this daily log? It cannot be edited after submission.')) return;
        try {
            await api.post(`/api/v1/daily-logs/${logId}/submit`);
            fetchLogs(selectedGreenhouseId);
        } catch (error) {
            alert('Error submitting log: ' + (error.response?.data?.message || error.message));
        }
    };

    const handleDelete = async (logId) => {
        if (!confirm('Delete this daily log?')) return;
        try {
            await api.delete(`/api/v1/daily-logs/${logId}`);
            fetchLogs(selectedGreenhouseId);
        } catch (error) {
            alert('Error deleting log: ' + (error.response?.data?.message || error.message));
        }
    };

    const getActivityType = (activityTypeId) => {
        return activityTypes.find(at => at.id === parseInt(activityTypeId));
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
            <div className="mb-8">
                <h1 className="text-3xl font-bold text-gray-900">Daily Activity Logs</h1>
                <p className="mt-2 text-gray-600">Record daily activities for production cycles</p>
            </div>

            {/* Greenhouse Selector */}
            <div className="mb-6 bg-white p-4 rounded-lg shadow">
                <label className="block text-sm font-medium text-gray-700 mb-2">
                    Select Greenhouse
                </label>
                <select
                    value={selectedGreenhouseId}
                    onChange={(e) => setSelectedGreenhouseId(e.target.value)}
                    className="w-full md:w-1/3 px-3 py-2 border border-gray-300 rounded-lg"
                >
                    <option value="">Select a greenhouse...</option>
                    {greenhouses.map((greenhouse) => (
                        <option key={greenhouse.id} value={greenhouse.id}>
                            {greenhouse.name} ({greenhouse.greenhouse_code || greenhouse.code})
                        </option>
                    ))}
                </select>
            </div>

            {selectedGreenhouseId && (
                <>
                    <div className="mb-4 flex justify-end">
                        <button
                            onClick={handleModalOpen}
                            className="flex items-center px-4 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700"
                        >
                            <Plus className="h-5 w-5 mr-2" />
                            New Daily Log
                        </button>
                    </div>

                    {/* Logs Table */}
                    <div className="bg-white rounded-lg shadow overflow-hidden">
                        <table className="min-w-full divide-y divide-gray-200">
                            <thead className="bg-gray-50">
                                <tr>
                                    <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Date</th>
                                    <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Production Cycle</th>
                                    <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                                    <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Activities</th>
                                    <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Submitted</th>
                                    <th className="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                                </tr>
                            </thead>
                            <tbody className="bg-white divide-y divide-gray-200">
                                {logs.length === 0 ? (
                                    <tr>
                                        <td colSpan="6" className="px-6 py-4 text-center text-gray-500">
                                            No daily logs found for this greenhouse
                                        </td>
                                    </tr>
                                ) : (
                                    logs.map((log) => (
                                        <tr key={log.id}>
                                            <td className="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                                {log.log_date}
                                            </td>
                                            <td className="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                                {log.production_cycle?.production_cycle_code || 'N/A'}
                                            </td>
                                            <td className="px-6 py-4 whitespace-nowrap">
                                                <span className={`px-2 py-1 text-xs font-semibold rounded-full ${
                                                    log.status === 'SUBMITTED' 
                                                        ? 'bg-green-100 text-green-800' 
                                                        : 'bg-yellow-100 text-yellow-800'
                                                }`}>
                                                    {log.status}
                                                </span>
                                            </td>
                                            <td className="px-6 py-4 text-sm text-gray-500">
                                                {log.items?.length || 0} activity(ies)
                                            </td>
                                            <td className="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                                {log.submitted_at 
                                                    ? new Date(log.submitted_at).toLocaleString() 
                                                    : 'Not submitted'}
                                            </td>
                                            <td className="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                                <div className="flex justify-end space-x-2">
                                                    {log.status === 'DRAFT' && (
                                                        <>
                                                            <button
                                                                onClick={() => handleEdit(log)}
                                                                className="text-blue-600 hover:text-blue-900"
                                                                title="Edit"
                                                            >
                                                                <Edit className="h-4 w-4" />
                                                            </button>
                                                            <button
                                                                onClick={() => handleSubmitLog(log.id)}
                                                                className="text-green-600 hover:text-green-900"
                                                                title="Submit"
                                                            >
                                                                <Send className="h-4 w-4" />
                                                            </button>
                                                        </>
                                                    )}
                                                    <button
                                                        onClick={() => handleDelete(log.id)}
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
                </>
            )}

            {/* Modal */}
            {showModal && (
                <div className="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50 p-4">
                    <div className="bg-white rounded-lg max-w-4xl w-full max-h-[90vh] overflow-y-auto">
                        <div className="sticky top-0 bg-white border-b border-gray-200 px-6 py-4 flex justify-between items-center">
                            <h2 className="text-xl font-bold text-gray-900">
                                {editingLog ? 'Edit Daily Log' : 'New Daily Log'}
                            </h2>
                            <button
                                onClick={() => setShowModal(false)}
                                className="text-gray-400 hover:text-gray-600"
                            >
                                <X className="h-6 w-6" />
                            </button>
                        </div>
                        <form onSubmit={handleSubmit} className="p-6 space-y-6">
                            <div className="grid grid-cols-2 gap-4">
                                <div>
                                    <label className="block text-sm font-medium text-gray-700 mb-1">
                                        Production Cycle <span className="text-red-500">*</span>
                                    </label>
                                    <select
                                        required
                                        value={formData.production_cycle_id}
                                        onChange={(e) => setFormData({ ...formData, production_cycle_id: e.target.value })}
                                        className="w-full px-3 py-2 border border-gray-300 rounded-lg"
                                    >
                                        <option value="">Select Production Cycle</option>
                                        {cycles.map((cycle) => (
                                            <option key={cycle.id} value={cycle.id}>
                                                {cycle.production_cycle_code} ({cycle.cycle_status})
                                            </option>
                                        ))}
                                    </select>
                                </div>
                                <div>
                                    <label className="block text-sm font-medium text-gray-700 mb-1">
                                        Log Date <span className="text-red-500">*</span>
                                    </label>
                                    <input
                                        type="date"
                                        required
                                        value={formData.log_date}
                                        onChange={(e) => setFormData({ ...formData, log_date: e.target.value })}
                                        className="w-full px-3 py-2 border border-gray-300 rounded-lg"
                                    />
                                </div>
                            </div>

                            <div>
                                <label className="block text-sm font-medium text-gray-700 mb-1">
                                    Issues/Notes
                                </label>
                                <textarea
                                    value={formData.issues_notes}
                                    onChange={(e) => setFormData({ ...formData, issues_notes: e.target.value })}
                                    rows="3"
                                    className="w-full px-3 py-2 border border-gray-300 rounded-lg"
                                />
                            </div>

                            {/* Activity Items */}
                            <div>
                                <div className="flex justify-between items-center mb-4">
                                    <h3 className="text-lg font-semibold text-gray-900">Activity Items</h3>
                                    <button
                                        type="button"
                                        onClick={addActivityItem}
                                        className="flex items-center px-3 py-1 bg-green-600 text-white rounded-lg hover:bg-green-700 text-sm"
                                    >
                                        <Plus className="h-4 w-4 mr-1" />
                                        Add Activity
                                    </button>
                                </div>

                                {formData.items.map((item, itemIndex) => {
                                    const activityType = getActivityType(item.activity_type_id);
                                    return (
                                        <div key={itemIndex} className="border border-gray-200 rounded-lg p-4 mb-4">
                                            <div className="flex justify-between items-center mb-4">
                                                <h4 className="font-medium text-gray-900">Activity {itemIndex + 1}</h4>
                                                <button
                                                    type="button"
                                                    onClick={() => removeActivityItem(itemIndex)}
                                                    className="text-red-600 hover:text-red-900"
                                                >
                                                    <Trash2 className="h-4 w-4" />
                                                </button>
                                            </div>

                                            <div className="grid grid-cols-2 gap-4">
                                                <div>
                                                    <label className="block text-sm font-medium text-gray-700 mb-1">
                                                        Activity Type <span className="text-red-500">*</span>
                                                    </label>
                                                    <select
                                                        required
                                                        value={item.activity_type_id}
                                                        onChange={(e) => updateActivityItem(itemIndex, 'activity_type_id', e.target.value)}
                                                        className="w-full px-3 py-2 border border-gray-300 rounded-lg"
                                                    >
                                                        <option value="">Select Activity Type</option>
                                                        {activityTypes.map((at) => (
                                                            <option key={at.id} value={at.id}>
                                                                {at.name} ({at.code})
                                                            </option>
                                                        ))}
                                                    </select>
                                                </div>
                                                <div>
                                                    <label className="block text-sm font-medium text-gray-700 mb-1">
                                                        Performed By
                                                    </label>
                                                    <select
                                                        value={item.performed_by_user_id}
                                                        onChange={(e) => updateActivityItem(itemIndex, 'performed_by_user_id', e.target.value)}
                                                        className="w-full px-3 py-2 border border-gray-300 rounded-lg"
                                                    >
                                                        <option value="">Select User</option>
                                                        {users.map((user) => (
                                                            <option key={user.id} value={user.id}>
                                                                {user.name}
                                                            </option>
                                                        ))}
                                                    </select>
                                                </div>
                                                {activityType?.requires_time_range && (
                                                    <>
                                                        <div>
                                                            <label className="block text-sm font-medium text-gray-700 mb-1">
                                                                Started At
                                                            </label>
                                                            <input
                                                                type="datetime-local"
                                                                value={item.started_at}
                                                                onChange={(e) => updateActivityItem(itemIndex, 'started_at', e.target.value)}
                                                                className="w-full px-3 py-2 border border-gray-300 rounded-lg"
                                                            />
                                                        </div>
                                                        <div>
                                                            <label className="block text-sm font-medium text-gray-700 mb-1">
                                                                Ended At
                                                            </label>
                                                            <input
                                                                type="datetime-local"
                                                                value={item.ended_at}
                                                                onChange={(e) => updateActivityItem(itemIndex, 'ended_at', e.target.value)}
                                                                className="w-full px-3 py-2 border border-gray-300 rounded-lg"
                                                            />
                                                        </div>
                                                    </>
                                                )}
                                                {activityType?.requires_quantity && (
                                                    <>
                                                        <div>
                                                            <label className="block text-sm font-medium text-gray-700 mb-1">
                                                                Quantity
                                                            </label>
                                                            <input
                                                                type="number"
                                                                step="0.01"
                                                                value={item.quantity}
                                                                onChange={(e) => updateActivityItem(itemIndex, 'quantity', e.target.value)}
                                                                className="w-full px-3 py-2 border border-gray-300 rounded-lg"
                                                            />
                                                        </div>
                                                        <div>
                                                            <label className="block text-sm font-medium text-gray-700 mb-1">
                                                                Unit
                                                            </label>
                                                            <input
                                                                type="text"
                                                                value={item.unit}
                                                                onChange={(e) => updateActivityItem(itemIndex, 'unit', e.target.value)}
                                                                className="w-full px-3 py-2 border border-gray-300 rounded-lg"
                                                                placeholder="L, KG, hours, etc."
                                                            />
                                                        </div>
                                                    </>
                                                )}
                                                <div className="col-span-2">
                                                    <label className="block text-sm font-medium text-gray-700 mb-1">
                                                        Notes
                                                    </label>
                                                    <textarea
                                                        value={item.notes}
                                                        onChange={(e) => updateActivityItem(itemIndex, 'notes', e.target.value)}
                                                        rows="2"
                                                        className="w-full px-3 py-2 border border-gray-300 rounded-lg"
                                                    />
                                                </div>
                                            </div>

                                            {/* Inputs for this activity */}
                                            {activityType?.requires_inputs && (
                                                <div className="mt-4">
                                                    <div className="flex justify-between items-center mb-2">
                                                        <label className="block text-sm font-medium text-gray-700">
                                                            Inputs Used
                                                        </label>
                                                        <button
                                                            type="button"
                                                            onClick={() => addInputToItem(itemIndex)}
                                                            className="text-sm text-green-600 hover:text-green-800"
                                                        >
                                                            <Plus className="h-4 w-4 inline mr-1" />
                                                            Add Input
                                                        </button>
                                                    </div>
                                                    {item.inputs?.map((input, inputIndex) => (
                                                        <div key={inputIndex} className="grid grid-cols-5 gap-2 mb-2 p-2 bg-gray-50 rounded">
                                                            {inputItems.length > 0 ? (
                                                                <select
                                                                    value={input.input_item_id}
                                                                    onChange={(e) => updateInputInItem(itemIndex, inputIndex, 'input_item_id', e.target.value)}
                                                                    className="px-2 py-1 border border-gray-300 rounded text-sm"
                                                                >
                                                                    <option value="">Select Input</option>
                                                                    {inputItems.map((ii) => (
                                                                        <option key={ii.id} value={ii.id}>
                                                                            {ii.name}
                                                                        </option>
                                                                    ))}
                                                                </select>
                                                            ) : (
                                                                <input
                                                                    type="text"
                                                                    placeholder="Input name (required)"
                                                                    value={input.input_name}
                                                                    onChange={(e) => updateInputInItem(itemIndex, inputIndex, 'input_name', e.target.value)}
                                                                    className="px-2 py-1 border border-gray-300 rounded text-sm"
                                                                    required
                                                                />
                                                            )}
                                                            {inputItems.length > 0 && (
                                                                <input
                                                                    type="text"
                                                                    placeholder="Or enter name"
                                                                    value={input.input_name}
                                                                    onChange={(e) => updateInputInItem(itemIndex, inputIndex, 'input_name', e.target.value)}
                                                                    className="px-2 py-1 border border-gray-300 rounded text-sm"
                                                                />
                                                            )}
                                                            <input
                                                                type="number"
                                                                step="0.01"
                                                                placeholder="Quantity"
                                                                required
                                                                value={input.quantity}
                                                                onChange={(e) => updateInputInItem(itemIndex, inputIndex, 'quantity', e.target.value)}
                                                                className="px-2 py-1 border border-gray-300 rounded text-sm"
                                                            />
                                                            <input
                                                                type="text"
                                                                placeholder="Unit"
                                                                required
                                                                value={input.unit}
                                                                onChange={(e) => updateInputInItem(itemIndex, inputIndex, 'unit', e.target.value)}
                                                                className="px-2 py-1 border border-gray-300 rounded text-sm"
                                                            />
                                                            <button
                                                                type="button"
                                                                onClick={() => removeInputFromItem(itemIndex, inputIndex)}
                                                                className="text-red-600 hover:text-red-900"
                                                            >
                                                                <Trash2 className="h-4 w-4" />
                                                            </button>
                                                        </div>
                                                    ))}
                                                </div>
                                            )}
                                        </div>
                                    );
                                })}
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
                                    {editingLog ? 'Update' : 'Save'} Log
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            )}
        </div>
    );
}
