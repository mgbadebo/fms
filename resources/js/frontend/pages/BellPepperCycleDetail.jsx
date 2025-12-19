import React, { useEffect, useState } from 'react';
import { useParams, Link } from 'react-router-dom';
import api from '../utils/api';
import { Factory, ArrowLeft, TrendingUp, DollarSign, Package, Plus, Edit, Trash2 } from 'lucide-react';

export default function BellPepperCycleDetail() {
    const { id } = useParams();
    const [cycle, setCycle] = useState(null);
    const [costs, setCosts] = useState([]);
    const [harvests, setHarvests] = useState([]);
    const [loading, setLoading] = useState(true);
    const [showCostModal, setShowCostModal] = useState(false);
    const [showHarvestModal, setShowHarvestModal] = useState(false);
    const [editingCost, setEditingCost] = useState(null);
    const [costFormData, setCostFormData] = useState({
        cost_type: 'SEEDS',
        description: '',
        quantity: '',
        unit: '',
        unit_cost: '',
        total_cost: '',
        cost_date: new Date().toISOString().slice(0, 10),
        staff_id: '',
        hours_allocated: '',
        notes: '',
    });
    const [harvestFormData, setHarvestFormData] = useState({
        harvest_date: new Date().toISOString().slice(0, 10),
        weight_kg: '',
        crates_count: '',
        grade: 'MIXED',
        status: 'HARVESTED',
        notes: '',
    });

    useEffect(() => {
        fetchCycleData();
    }, [id]);

    const fetchCycleData = async () => {
        try {
            const [cycleRes, costsRes, harvestsRes] = await Promise.all([
                api.get(`/api/v1/bell-pepper-cycles/${id}`),
                api.get(`/api/v1/bell-pepper-cycle-costs?bell_pepper_cycle_id=${id}`),
                api.get(`/api/v1/bell-pepper-harvests?bell_pepper_cycle_id=${id}`),
            ]);

            const cycleData = cycleRes.data?.data || cycleRes.data;
            const costsData = costsRes.data?.data || costsRes.data || [];
            const harvestsData = harvestsRes.data?.data || harvestsRes.data || [];

            setCycle(cycleData);
            setCosts(Array.isArray(costsData) ? costsData : []);
            setHarvests(Array.isArray(harvestsData) ? harvestsData : []);
        } catch (error) {
            console.error('Error fetching cycle data:', error);
        } finally {
            setLoading(false);
        }
    };

    const handleCostSubmit = async (e) => {
        e.preventDefault();
        try {
            const data = {
                ...costFormData,
                bell_pepper_cycle_id: id,
                farm_id: cycle?.farm_id,
            };
            if (editingCost) {
                await api.put(`/api/v1/bell-pepper-cycle-costs/${editingCost.id}`, data);
            } else {
                await api.post('/api/v1/bell-pepper-cycle-costs', data);
            }
            setShowCostModal(false);
            setEditingCost(null);
            fetchCycleData();
        } catch (error) {
            alert('Error saving cost: ' + (error.response?.data?.message || 'Unknown error'));
        }
    };

    const handleHarvestSubmit = async (e) => {
        e.preventDefault();
        try {
            const data = {
                ...harvestFormData,
                farm_id: cycle?.farm_id,
                bell_pepper_cycle_id: id,
                greenhouse_id: cycle?.greenhouse_id,
            };
            await api.post('/api/v1/bell-pepper-harvests', data);
            setShowHarvestModal(false);
            fetchCycleData();
        } catch (error) {
            alert('Error saving harvest: ' + (error.response?.data?.message || 'Unknown error'));
        }
    };

    const handleDeleteCost = async (costId) => {
        if (!confirm('Are you sure you want to delete this cost?')) return;
        try {
            await api.delete(`/api/v1/bell-pepper-cycle-costs/${costId}`);
            fetchCycleData();
        } catch (error) {
            alert('Error deleting cost: ' + (error.response?.data?.message || 'Unknown error'));
        }
    };

    if (loading) {
        return (
            <div className="flex items-center justify-center h-64">
                <div className="animate-spin rounded-full h-12 w-12 border-b-2 border-green-600"></div>
            </div>
        );
    }

    if (!cycle) {
        return (
            <div className="text-center py-12">
                <p className="text-gray-500 mb-2">Cycle not found</p>
                <Link to="/bell-pepper-production" className="text-green-600 hover:text-green-700">
                    ← Back to Cycles
                </Link>
            </div>
        );
    }

    const costTypes = [
        { value: 'SEEDS', label: 'Seeds' },
        { value: 'FERTILIZER_CHEMICALS', label: 'Fertilizer/Chemicals' },
        { value: 'FUEL_WATER_PUMPING', label: 'Fuel (Water Pumping)' },
        { value: 'LABOUR_DEDICATED', label: 'Labour (Dedicated)' },
        { value: 'LABOUR_SHARED', label: 'Labour (Shared)' },
        { value: 'SPRAY_GUNS', label: 'Spray Guns' },
        { value: 'IRRIGATION_EQUIPMENT', label: 'Irrigation Equipment' },
        { value: 'PROTECTIVE_CLOTHING', label: 'Protective Clothing' },
        { value: 'GREENHOUSE_AMORTIZATION', label: 'Greenhouse Amortization' },
        { value: 'BOREHOLE_AMORTIZATION', label: 'Borehole Amortization' },
        { value: 'LOGISTICS', label: 'Logistics' },
        { value: 'OTHER', label: 'Other' },
    ];

    const totalCosts = costs.reduce((sum, cost) => sum + (Number(cost.total_cost || 0)), 0);
    const totalHarvested = harvests.reduce((sum, h) => sum + (Number(h.weight_kg || 0)), 0);

    return (
        <div>
            <div className="flex items-center justify-between mb-6">
                <Link
                    to="/bell-pepper-production"
                    className="flex items-center text-gray-600 hover:text-gray-900"
                >
                    <ArrowLeft className="h-5 w-5 mr-2" />
                    Back to Cycles
                </Link>
                {cycle.status !== 'COMPLETED' && (
                    <button
                        onClick={() => {
                            // TODO: Implement cycle update
                            alert('Cycle update coming soon');
                        }}
                        className="flex items-center px-4 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700"
                    >
                        <Edit className="h-5 w-5 mr-2" />
                        Edit Cycle
                    </button>
                )}
            </div>

            {/* Cycle Header */}
            <div className="bg-white rounded-lg shadow mb-6">
                <div className="px-6 py-4 border-b border-gray-200">
                    <div className="flex items-center justify-between">
                        <div className="flex items-center">
                            <div className="bg-green-100 p-3 rounded-lg">
                                <Factory className="h-8 w-8 text-green-600" />
                            </div>
                            <div className="ml-4">
                                <h1 className="text-2xl font-bold text-gray-900">{cycle.cycle_code}</h1>
                                <p className="text-gray-600">
                                    {cycle.greenhouse?.name} • {cycle.farm?.name}
                                </p>
                            </div>
                        </div>
                        <span className={`px-3 py-1 text-sm font-medium rounded ${
                            cycle.status === 'COMPLETED' ? 'bg-green-100 text-green-800' :
                            cycle.status === 'IN_PROGRESS' ? 'bg-blue-100 text-blue-800' :
                            'bg-gray-100 text-gray-800'
                        }`}>
                            {cycle.status}
                        </span>
                    </div>
                </div>
                <div className="px-6 py-4">
                    <div className="grid grid-cols-1 md:grid-cols-4 gap-6">
                        <div>
                            <p className="text-sm text-gray-600 mb-1">Expected Yield</p>
                            <p className="text-lg font-semibold text-gray-900">
                                {Number(cycle.expected_yield_kg || 0).toFixed(2)} kg
                            </p>
                            <p className="text-xs text-gray-500">
                                {Number(cycle.expected_yield_per_sqm || 0).toFixed(2)} kg/sqm
                            </p>
                        </div>
                        <div>
                            <p className="text-sm text-gray-600 mb-1">Actual Yield</p>
                            <p className="text-lg font-semibold text-gray-900">
                                {Number(cycle.actual_yield_kg || 0).toFixed(2)} kg
                            </p>
                            <p className="text-xs text-gray-500">
                                {Number(cycle.actual_yield_per_sqm || 0).toFixed(2)} kg/sqm
                            </p>
                        </div>
                        <div>
                            <p className="text-sm text-gray-600 mb-1">Yield Variance</p>
                            <p className={`text-lg font-semibold ${
                                Number(cycle.yield_variance_percent || 0) >= 0 ? 'text-green-600' : 'text-red-600'
                            }`}>
                                {Number(cycle.yield_variance_percent || 0).toFixed(1)}%
                            </p>
                        </div>
                        <div>
                            <p className="text-sm text-gray-600 mb-1">Total Costs</p>
                            <p className="text-lg font-semibold text-gray-900">
                                ₦{Number(totalCosts || 0).toFixed(2)}
                            </p>
                        </div>
                    </div>
                </div>
            </div>

            <div className="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
                {/* Costs Section */}
                <div className="bg-white rounded-lg shadow">
                    <div className="px-6 py-4 border-b border-gray-200 flex items-center justify-between">
                        <h2 className="text-lg font-semibold text-gray-900">Costs</h2>
                        <button
                            onClick={() => {
                                setEditingCost(null);
                                setCostFormData({
                                    cost_type: 'SEEDS',
                                    description: '',
                                    quantity: '',
                                    unit: '',
                                    unit_cost: '',
                                    total_cost: '',
                                    cost_date: new Date().toISOString().slice(0, 10),
                                    staff_id: '',
                                    hours_allocated: '',
                                    notes: '',
                                });
                                setShowCostModal(true);
                            }}
                            className="bg-green-600 text-white px-3 py-1.5 rounded-lg hover:bg-green-700 flex items-center text-sm"
                        >
                            <Plus className="h-4 w-4 mr-1" />
                            Add Cost
                        </button>
                    </div>
                    <div className="divide-y divide-gray-200">
                        {costs.map((cost) => (
                            <div key={cost.id} className="px-6 py-4">
                                <div className="flex justify-between items-start">
                                    <div>
                                        <p className="font-medium text-gray-900">
                                            {costTypes.find(t => t.value === cost.cost_type)?.label || cost.cost_type}
                                        </p>
                                        {cost.description && (
                                            <p className="text-sm text-gray-600">{cost.description}</p>
                                        )}
                                        <p className="text-xs text-gray-500 mt-1">
                                            {new Date(cost.cost_date).toLocaleDateString()}
                                        </p>
                                    </div>
                                    <div className="text-right">
                                        <p className="text-sm font-medium text-gray-900">
                                            ₦{Number(cost.total_cost || 0).toFixed(2)}
                                        </p>
                                        <div className="flex space-x-2 mt-2">
                                            <button
                                                onClick={() => {
                                                    setEditingCost(cost);
                                                    setCostFormData({
                                                        cost_type: cost.cost_type,
                                                        description: cost.description || '',
                                                        quantity: cost.quantity || '',
                                                        unit: cost.unit || '',
                                                        unit_cost: cost.unit_cost || '',
                                                        total_cost: cost.total_cost || '',
                                                        cost_date: cost.cost_date ? new Date(cost.cost_date).toISOString().slice(0, 10) : new Date().toISOString().slice(0, 10),
                                                        staff_id: cost.staff_id || '',
                                                        hours_allocated: cost.hours_allocated || '',
                                                        notes: cost.notes || '',
                                                    });
                                                    setShowCostModal(true);
                                                }}
                                                className="text-green-600 hover:text-green-700"
                                            >
                                                <Edit className="h-4 w-4" />
                                            </button>
                                            <button
                                                onClick={() => handleDeleteCost(cost.id)}
                                                className="text-red-600 hover:text-red-700"
                                            >
                                                <Trash2 className="h-4 w-4" />
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        ))}
                        {costs.length === 0 && (
                            <div className="px-6 py-8 text-center text-gray-500">
                                No costs recorded yet
                            </div>
                        )}
                    </div>
                </div>

                {/* Harvests Section */}
                <div className="bg-white rounded-lg shadow">
                    <div className="px-6 py-4 border-b border-gray-200 flex items-center justify-between">
                        <h2 className="text-lg font-semibold text-gray-900">Harvests</h2>
                        <button
                            onClick={() => {
                                setHarvestFormData({
                                    harvest_date: new Date().toISOString().slice(0, 10),
                                    weight_kg: '',
                                    crates_count: '',
                                    grade: 'MIXED',
                                    status: 'HARVESTED',
                                    notes: '',
                                });
                                setShowHarvestModal(true);
                            }}
                            className="bg-green-600 text-white px-3 py-1.5 rounded-lg hover:bg-green-700 flex items-center text-sm"
                        >
                            <Plus className="h-4 w-4 mr-1" />
                            Add Harvest
                        </button>
                    </div>
                    <div className="divide-y divide-gray-200">
                        {harvests.map((harvest) => (
                            <div key={harvest.id} className="px-6 py-4">
                                <div className="flex justify-between items-start">
                                    <div>
                                        <p className="font-medium text-gray-900">
                                            {harvest.harvest_code}
                                        </p>
                                        <p className="text-sm text-gray-600">
                                            {Number(harvest.weight_kg || 0).toFixed(2)} kg • {harvest.crates_count} crates
                                        </p>
                                        <p className="text-xs text-gray-500 mt-1">
                                            {new Date(harvest.harvest_date).toLocaleDateString()} • Grade: {harvest.grade}
                                        </p>
                                    </div>
                                    <span className={`px-2 py-1 text-xs font-medium rounded ${
                                        harvest.status === 'SOLD' ? 'bg-green-100 text-green-800' :
                                        harvest.status === 'DELIVERED' ? 'bg-blue-100 text-blue-800' :
                                        'bg-gray-100 text-gray-800'
                                    }`}>
                                        {harvest.status}
                                    </span>
                                </div>
                            </div>
                        ))}
                        {harvests.length === 0 && (
                            <div className="px-6 py-8 text-center text-gray-500">
                                No harvests recorded yet
                            </div>
                        )}
                    </div>
                </div>
            </div>

            {/* Add Cost Modal */}
            {showCostModal && (
                <div className="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50 p-4 overflow-y-auto">
                    <div className="bg-white rounded-lg max-w-2xl w-full p-6 max-h-[90vh] overflow-y-auto">
                        <h2 className="text-xl font-bold mb-4">
                            {editingCost ? 'Edit Cost' : 'Add Cost'}
                        </h2>
                        <form onSubmit={handleCostSubmit} className="space-y-4">
                            <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
                                <div>
                                    <label className="block text-sm font-medium text-gray-700 mb-1">Cost Type *</label>
                                    <select
                                        required
                                        value={costFormData.cost_type}
                                        onChange={(e) => setCostFormData({ ...costFormData, cost_type: e.target.value })}
                                        className="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-green-500 focus:border-green-500"
                                    >
                                        {costTypes.map((type) => (
                                            <option key={type.value} value={type.value}>{type.label}</option>
                                        ))}
                                    </select>
                                </div>
                                <div>
                                    <label className="block text-sm font-medium text-gray-700 mb-1">Cost Date *</label>
                                    <input
                                        type="date"
                                        required
                                        value={costFormData.cost_date}
                                        onChange={(e) => setCostFormData({ ...costFormData, cost_date: e.target.value })}
                                        className="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-green-500 focus:border-green-500"
                                    />
                                </div>
                                <div>
                                    <label className="block text-sm font-medium text-gray-700 mb-1">Description</label>
                                    <input
                                        type="text"
                                        value={costFormData.description}
                                        onChange={(e) => setCostFormData({ ...costFormData, description: e.target.value })}
                                        className="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-green-500 focus:border-green-500"
                                    />
                                </div>
                                <div>
                                    <label className="block text-sm font-medium text-gray-700 mb-1">Total Cost (₦) *</label>
                                    <input
                                        type="number"
                                        step="0.01"
                                        required
                                        value={costFormData.total_cost}
                                        onChange={(e) => setCostFormData({ ...costFormData, total_cost: e.target.value })}
                                        className="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-green-500 focus:border-green-500"
                                    />
                                </div>
                                {(costFormData.cost_type === 'SEEDS' || costFormData.cost_type === 'FERTILIZER_CHEMICALS') && (
                                    <>
                                        <div>
                                            <label className="block text-sm font-medium text-gray-700 mb-1">Quantity</label>
                                            <input
                                                type="number"
                                                step="0.01"
                                                value={costFormData.quantity}
                                                onChange={(e) => setCostFormData({ ...costFormData, quantity: e.target.value })}
                                                className="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-green-500 focus:border-green-500"
                                            />
                                        </div>
                                        <div>
                                            <label className="block text-sm font-medium text-gray-700 mb-1">Unit</label>
                                            <input
                                                type="text"
                                                value={costFormData.unit}
                                                onChange={(e) => setCostFormData({ ...costFormData, unit: e.target.value })}
                                                className="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-green-500 focus:border-green-500"
                                                placeholder="kg, litres, etc"
                                            />
                                        </div>
                                    </>
                                )}
                                {(costFormData.cost_type === 'LABOUR_DEDICATED' || costFormData.cost_type === 'LABOUR_SHARED') && (
                                    <div>
                                        <label className="block text-sm font-medium text-gray-700 mb-1">Hours Allocated</label>
                                        <input
                                            type="number"
                                            step="0.5"
                                            value={costFormData.hours_allocated}
                                            onChange={(e) => setCostFormData({ ...costFormData, hours_allocated: e.target.value })}
                                            className="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-green-500 focus:border-green-500"
                                        />
                                    </div>
                                )}
                                <div className="md:col-span-2">
                                    <label className="block text-sm font-medium text-gray-700 mb-1">Notes</label>
                                    <textarea
                                        value={costFormData.notes}
                                        onChange={(e) => setCostFormData({ ...costFormData, notes: e.target.value })}
                                        rows={3}
                                        className="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-green-500 focus:border-green-500"
                                    />
                                </div>
                            </div>
                            <div className="flex justify-end space-x-3 pt-4">
                                <button
                                    type="button"
                                    onClick={() => {
                                        setShowCostModal(false);
                                        setEditingCost(null);
                                    }}
                                    className="px-4 py-2 border border-gray-300 rounded-lg text-gray-700 hover:bg-gray-50"
                                >
                                    Cancel
                                </button>
                                <button
                                    type="submit"
                                    className="px-4 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700"
                                >
                                    {editingCost ? 'Update' : 'Add'} Cost
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            )}

            {/* Add Harvest Modal */}
            {showHarvestModal && (
                <div className="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50 p-4 overflow-y-auto">
                    <div className="bg-white rounded-lg max-w-md w-full p-6">
                        <h2 className="text-xl font-bold mb-4">Add Harvest</h2>
                        <form onSubmit={handleHarvestSubmit} className="space-y-4">
                            <div>
                                <label className="block text-sm font-medium text-gray-700 mb-1">Harvest Date *</label>
                                <input
                                    type="date"
                                    required
                                    value={harvestFormData.harvest_date}
                                    onChange={(e) => setHarvestFormData({ ...harvestFormData, harvest_date: e.target.value })}
                                    className="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-green-500 focus:border-green-500"
                                />
                            </div>
                            <div>
                                <label className="block text-sm font-medium text-gray-700 mb-1">Weight (kg) *</label>
                                <input
                                    type="number"
                                    step="0.01"
                                    required
                                    value={harvestFormData.weight_kg}
                                    onChange={(e) => {
                                        const weight = e.target.value;
                                        setHarvestFormData({
                                            ...harvestFormData,
                                            weight_kg: weight,
                                            crates_count: weight ? Math.ceil(weight / 9.5) : '',
                                        });
                                    }}
                                    className="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-green-500 focus:border-green-500"
                                />
                            </div>
                            <div>
                                <label className="block text-sm font-medium text-gray-700 mb-1">Crates Count</label>
                                <input
                                    type="number"
                                    value={harvestFormData.crates_count}
                                    onChange={(e) => setHarvestFormData({ ...harvestFormData, crates_count: e.target.value })}
                                    className="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-green-500 focus:border-green-500"
                                />
                                <p className="text-xs text-gray-500 mt-1">Auto-calculated (9-10kg per crate)</p>
                            </div>
                            <div>
                                <label className="block text-sm font-medium text-gray-700 mb-1">Grade *</label>
                                <select
                                    required
                                    value={harvestFormData.grade}
                                    onChange={(e) => setHarvestFormData({ ...harvestFormData, grade: e.target.value })}
                                    className="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-green-500 focus:border-green-500"
                                >
                                    <option value="A">Grade A</option>
                                    <option value="B">Grade B</option>
                                    <option value="C">Grade C</option>
                                    <option value="MIXED">Mixed</option>
                                </select>
                            </div>
                            <div>
                                <label className="block text-sm font-medium text-gray-700 mb-1">Status</label>
                                <select
                                    value={harvestFormData.status}
                                    onChange={(e) => setHarvestFormData({ ...harvestFormData, status: e.target.value })}
                                    className="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-green-500 focus:border-green-500"
                                >
                                    <option value="HARVESTED">Harvested</option>
                                    <option value="PACKED">Packed</option>
                                    <option value="IN_TRANSIT">In Transit</option>
                                    <option value="DELIVERED">Delivered</option>
                                    <option value="SOLD">Sold</option>
                                </select>
                            </div>
                            <div>
                                <label className="block text-sm font-medium text-gray-700 mb-1">Notes</label>
                                <textarea
                                    value={harvestFormData.notes}
                                    onChange={(e) => setHarvestFormData({ ...harvestFormData, notes: e.target.value })}
                                    rows={3}
                                    className="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-green-500 focus:border-green-500"
                                />
                            </div>
                            <div className="flex justify-end space-x-3 pt-4">
                                <button
                                    type="button"
                                    onClick={() => setShowHarvestModal(false)}
                                    className="px-4 py-2 border border-gray-300 rounded-lg text-gray-700 hover:bg-gray-50"
                                >
                                    Cancel
                                </button>
                                <button
                                    type="submit"
                                    className="px-4 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700"
                                >
                                    Add Harvest
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            )}
        </div>
    );
}

