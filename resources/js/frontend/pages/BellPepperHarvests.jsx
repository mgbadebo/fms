import React, { useEffect, useState } from 'react';
import { Link } from 'react-router-dom';
import api from '../utils/api';
import { Package, Plus, Calendar, TrendingUp, User } from 'lucide-react';

export default function BellPepperHarvests() {
    const [harvests, setHarvests] = useState([]);
    const [cycles, setCycles] = useState([]); // For filter dropdown
    const [modalCycles, setModalCycles] = useState([]); // For modal form
    const [farms, setFarms] = useState([]);
    const [greenhouses, setGreenhouses] = useState([]);
    const [loading, setLoading] = useState(true);

    // Helper function to safely format numbers
    const formatNumber = (value, decimals = 2) => {
        const num = Number(value);
        if (isNaN(num) || num === null || num === undefined) {
            return '0.00';
        }
        return num.toFixed(decimals);
    };
    const [showModal, setShowModal] = useState(false);
    const [showDateValidationPopup, setShowDateValidationPopup] = useState(false);
    const [formData, setFormData] = useState({
        farm_id: '',
        bell_pepper_cycle_id: '',
        greenhouse_id: '',
        harvest_date: new Date().toISOString().slice(0, 10),
        grade_a_kg: '0',
        grade_b_kg: '0',
        grade_c_kg: '0',
        crates_count: '',
        notes: '',
    });
    const [filters, setFilters] = useState({
        farm_id: '',
        cycle_id: '',
        greenhouse_id: '',
    });
    const [selectedCycle, setSelectedCycle] = useState(null); // Store selected cycle for date validation

    useEffect(() => {
        fetchData();
    }, [filters]);

    // Validate harvest date when cycle or harvest date changes
    useEffect(() => {
        if (selectedCycle && selectedCycle.start_date && formData.harvest_date) {
            const validation = validateHarvestDate(formData.harvest_date, selectedCycle);
            console.log('useEffect validation:', validation, 'showPopup:', !validation.valid);
            setShowDateValidationPopup(!validation.valid);
        } else {
            setShowDateValidationPopup(false);
        }
    }, [selectedCycle, formData.harvest_date]);

    const fetchData = async () => {
        try {
            const params = new URLSearchParams();
            if (filters.farm_id) params.append('farm_id', filters.farm_id);
            if (filters.cycle_id) params.append('bell_pepper_cycle_id', filters.cycle_id);
            if (filters.greenhouse_id) params.append('greenhouse_id', filters.greenhouse_id);

            const [harvestsRes, cyclesRes, farmsRes] = await Promise.all([
                api.get(`/api/v1/bell-pepper-harvests?${params.toString()}`),
                api.get('/api/v1/bell-pepper-cycles?per_page=1000'),
                api.get('/api/v1/farms?per_page=1000'),
            ]);

            // Handle paginated responses - Laravel pagination returns { data: [...], current_page: 1, ... }
            const harvestsData = harvestsRes.data?.data || (Array.isArray(harvestsRes.data) ? harvestsRes.data : []);
            const cyclesData = cyclesRes.data?.data || (Array.isArray(cyclesRes.data) ? cyclesRes.data : []);
            const farmsData = farmsRes.data?.data || (Array.isArray(farmsRes.data) ? farmsRes.data : []);

            setHarvests(Array.isArray(harvestsData) ? harvestsData : []);
            setCycles(Array.isArray(cyclesData) ? cyclesData : []);
            setFarms(Array.isArray(farmsData) ? farmsData : []);
        } catch (error) {
            console.error('Error fetching data:', error);
        } finally {
            setLoading(false);
        }
    };

    const fetchGreenhouses = async (farmId) => {
        if (!farmId) {
            setGreenhouses([]);
            return;
        }
        try {
            const response = await api.get(`/api/v1/greenhouses?farm_id=${farmId}`);
            const data = response.data?.data || response.data || [];
            setGreenhouses(Array.isArray(data) ? data : []);
        } catch (error) {
            console.error('Error fetching greenhouses:', error);
            setGreenhouses([]);
        }
    };

    const fetchCyclesForGreenhouse = async (greenhouseId) => {
        if (!greenhouseId) {
            setModalCycles([]);
            return;
        }
        try {
            // Fetch cycles for the greenhouse - we want IN_PROGRESS cycles (not ACTIVE)
            const response = await api.get(`/api/v1/bell-pepper-cycles?greenhouse_id=${greenhouseId}&status=IN_PROGRESS&per_page=1000`);
            // Handle paginated responses
            const data = response.data?.data || (Array.isArray(response.data) ? response.data : []);
            const cyclesData = Array.isArray(data) ? data : [];
            // Also include PLANNED cycles in case they want to record harvest early
            if (cyclesData.length === 0) {
                const plannedResponse = await api.get(`/api/v1/bell-pepper-cycles?greenhouse_id=${greenhouseId}&status=PLANNED&per_page=1000`);
                const plannedData = plannedResponse.data?.data || (Array.isArray(plannedResponse.data) ? plannedResponse.data : []);
                setModalCycles(Array.isArray(plannedData) ? plannedData : []);
            } else {
                setModalCycles(cyclesData);
            }
            console.log('Cycles loaded for greenhouse:', greenhouseId, cyclesData.length);
        } catch (error) {
            console.error('Error fetching cycles:', error);
            setModalCycles([]);
        }
    };

    const handleModalOpen = async () => {
        setFormData({
            farm_id: '',
            bell_pepper_cycle_id: '',
            greenhouse_id: '',
            harvest_date: new Date().toISOString().slice(0, 10),
            grade_a_kg: '0',
            grade_b_kg: '0',
            grade_c_kg: '0',
            crates_count: '',
            notes: '',
        });
        setGreenhouses([]);
        setModalCycles([]);
        setSelectedCycle(null);
        setShowDateValidationPopup(false);
        
        // Ensure farms are loaded when modal opens
        if (farms.length === 0) {
            try {
                const farmsRes = await api.get('/api/v1/farms?per_page=1000');
                const farmsData = farmsRes.data?.data || (Array.isArray(farmsRes.data) ? farmsRes.data : []);
                setFarms(Array.isArray(farmsData) ? farmsData : []);
            } catch (error) {
                console.error('Error fetching farms:', error);
            }
        }
        
        setShowModal(true);
    };

    const handleFarmChange = (farmId) => {
        setFormData({ ...formData, farm_id: farmId, greenhouse_id: '', bell_pepper_cycle_id: '' });
        fetchGreenhouses(farmId);
    };

    const handleGreenhouseChange = (greenhouseId) => {
        setFormData({ ...formData, greenhouse_id: greenhouseId, bell_pepper_cycle_id: '', harvest_date: new Date().toISOString().slice(0, 10) });
        setSelectedCycle(null);
        fetchCyclesForGreenhouse(greenhouseId);
    };

    const handleCycleChange = (cycleId) => {
        const cycle = modalCycles.find(c => String(c.id) === String(cycleId));
        console.log('handleCycleChange called with cycleId:', cycleId, 'cycle:', cycle, 'current harvest_date:', formData.harvest_date);
        setSelectedCycle(cycle);
        // Update form data
        setFormData({ ...formData, bell_pepper_cycle_id: cycleId });
        
        // Immediately validate the current harvest date against the new cycle
        if (cycle && cycle.start_date && formData.harvest_date) {
            const validation = validateHarvestDate(formData.harvest_date, cycle);
            console.log('Immediate validation after cycle selection:', {
                harvestDate: formData.harvest_date,
                cycleStartDate: cycle.start_date,
                validation,
                willShowPopup: !validation.valid
            });
            if (!validation.valid) {
                console.log('Setting popup to true immediately');
                setShowDateValidationPopup(true);
            } else {
                setShowDateValidationPopup(false);
            }
        } else {
            setShowDateValidationPopup(false);
        }
    };

    // Calculate minimum harvest date (70 days after cycle start)
    const getMinHarvestDate = () => {
        if (selectedCycle && selectedCycle.start_date) {
            const cycleStartDate = new Date(selectedCycle.start_date);
            const minDate = new Date(cycleStartDate);
            minDate.setDate(minDate.getDate() + 70);
            return minDate.toISOString().slice(0, 10);
        }
        return null;
    };

    // Calculate maximum harvest date (cycle end date or today, whichever is earlier)
    const getMaxHarvestDate = () => {
        if (selectedCycle && selectedCycle.expected_end_date) {
            const cycleEndDate = new Date(selectedCycle.expected_end_date);
            const today = new Date();
            return cycleEndDate < today ? cycleEndDate.toISOString().slice(0, 10) : today.toISOString().slice(0, 10);
        }
        return new Date().toISOString().slice(0, 10);
    };

    // Validate harvest date against cycle start date
    const validateHarvestDate = (harvestDate, cycle = selectedCycle) => {
        if (!cycle || !cycle.start_date || !harvestDate) {
            return { valid: true };
        }

        const cycleStartDate = new Date(cycle.start_date);
        const selectedHarvestDate = new Date(harvestDate);
        const minHarvestDate = new Date(cycleStartDate);
        minHarvestDate.setDate(minHarvestDate.getDate() + 70);

        // Calculate days difference
        const timeDiff = selectedHarvestDate.getTime() - cycleStartDate.getTime();
        const daysDifference = Math.floor(timeDiff / (1000 * 60 * 60 * 24));

        console.log('Validating date:', {
            harvestDate,
            cycleStartDate: cycle.start_date,
            daysDifference,
            minRequired: 70,
            isValid: daysDifference >= 70
        });

        if (daysDifference < 70) {
            return {
                valid: false,
                minDate: minHarvestDate.toISOString().slice(0, 10),
                cycleStartDate: cycleStartDate.toISOString().slice(0, 10),
                daysDifference: daysDifference
            };
        }

        return { valid: true };
    };

    // Handle harvest date change with validation
    const handleHarvestDateChange = (newDate) => {
        console.log('handleHarvestDateChange called with:', newDate, 'selectedCycle:', selectedCycle);
        // Always update the form data so the input reflects the user's selection
        // The useEffect will handle validation
        setFormData({ ...formData, harvest_date: newDate });
    };

    // Handle harvest date blur (when user leaves the field)
    const handleHarvestDateBlur = () => {
        if (!selectedCycle || !selectedCycle.start_date) {
            return;
        }

        const validation = validateHarvestDate(formData.harvest_date);
        
        if (!validation.valid) {
            setShowDateValidationPopup(true);
        }
    };

    const calculateTotalWeight = () => {
        const gradeA = Number(formData.grade_a_kg) || 0;
        const gradeB = Number(formData.grade_b_kg) || 0;
        const gradeC = Number(formData.grade_c_kg) || 0;
        const total = gradeA + gradeB + gradeC;
        return isNaN(total) ? 0 : total;
        return isNaN(total) ? 0 : total;
    };

    const calculateCrates = () => {
        const totalWeight = calculateTotalWeight();
        if (totalWeight > 0) {
            return Math.ceil(totalWeight / 9.5); // 9-10kg per crate, use 9.5kg average
        }
        return 0;
    };

    const handleSubmit = async (e) => {
        e.preventDefault();
        
        // Validate harvest date before submission
        if (selectedCycle && selectedCycle.start_date) {
            const validation = validateHarvestDate(formData.harvest_date);
            if (!validation.valid) {
                setShowDateValidationPopup(true);
                return;
            }
        }

        try {
            const submitData = {
                ...formData,
                grade_a_kg: parseFloat(formData.grade_a_kg) || 0,
                grade_b_kg: parseFloat(formData.grade_b_kg) || 0,
                grade_c_kg: parseFloat(formData.grade_c_kg) || 0,
                crates_count: formData.crates_count || calculateCrates(),
            };

            await api.post('/api/v1/bell-pepper-harvests', submitData);
            setShowModal(false);
            setShowDateValidationPopup(false);
            fetchData();
        } catch (error) {
            alert('Error creating harvest: ' + (error.response?.data?.message || 'Unknown error'));
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
                    <h1 className="text-2xl md:text-3xl font-bold text-gray-900">Bell Pepper Harvests</h1>
                    <p className="text-gray-600 mt-1">Record and track harvests by grade</p>
                </div>
                <button
                    onClick={handleModalOpen}
                    className="w-full md:w-auto flex items-center gap-2 bg-green-600 text-white px-4 py-2 rounded-lg hover:bg-green-700 transition-colors"
                >
                    <Plus className="w-5 h-5" />
                    Record Harvest
                </button>
            </div>

            {/* Filters */}
            <div className="bg-white rounded-lg shadow p-4 mb-6">
                <div className="grid grid-cols-1 md:grid-cols-3 gap-4">
                    <div>
                        <label className="block text-sm font-medium text-gray-700 mb-1">Farm</label>
                        <select
                            value={filters.farm_id}
                            onChange={(e) => setFilters({ ...filters, farm_id: e.target.value, greenhouse_id: '', cycle_id: '' })}
                            className="w-full border border-gray-300 rounded-lg px-3 py-2 focus:ring-2 focus:ring-green-500 focus:border-green-500"
                        >
                            <option value="">All Farms</option>
                            {farms.map((farm) => (
                                <option key={farm.id} value={farm.id}>
                                    {farm.name}
                                </option>
                            ))}
                        </select>
                    </div>
                    <div>
                        <label className="block text-sm font-medium text-gray-700 mb-1">Greenhouse</label>
                        <select
                            value={filters.greenhouse_id}
                            onChange={(e) => setFilters({ ...filters, greenhouse_id: e.target.value, cycle_id: '' })}
                            className="w-full border border-gray-300 rounded-lg px-3 py-2 focus:ring-2 focus:ring-green-500 focus:border-green-500"
                            disabled={!filters.farm_id}
                        >
                            <option value="">All Greenhouses</option>
                            {greenhouses
                                .filter((gh) => !filters.farm_id || gh.farm_id == filters.farm_id)
                                .map((gh) => (
                                    <option key={gh.id} value={gh.id}>
                                        {gh.name}
                                    </option>
                                ))}
                        </select>
                    </div>
                    <div>
                        <label className="block text-sm font-medium text-gray-700 mb-1">Cycle</label>
                        <select
                            value={filters.cycle_id}
                            onChange={(e) => setFilters({ ...filters, cycle_id: e.target.value })}
                            className="w-full border border-gray-300 rounded-lg px-3 py-2 focus:ring-2 focus:ring-green-500 focus:border-green-500"
                            disabled={!filters.greenhouse_id}
                        >
                            <option value="">All Cycles</option>
                            {cycles
                                .filter((cycle) => !filters.greenhouse_id || cycle.greenhouse_id == filters.greenhouse_id)
                                .map((cycle) => (
                                    <option key={cycle.id} value={cycle.id}>
                                        {cycle.cycle_code}
                                    </option>
                                ))}
                        </select>
                    </div>
                </div>
            </div>

            {/* Harvests List */}
            <div className="space-y-4">
                {harvests.length === 0 ? (
                    <div className="bg-white rounded-lg shadow p-8 text-center">
                        <Package className="w-12 h-12 text-gray-400 mx-auto mb-4" />
                        <p className="text-gray-600">No harvests found. Record your first harvest!</p>
                    </div>
                ) : (
                    harvests.map((harvest) => {
                        // Ensure all values are numbers - handle null, undefined, and invalid values
                        const gradeA = Number(harvest.grade_a_kg) || 0;
                        const gradeB = Number(harvest.grade_b_kg) || 0;
                        const gradeC = Number(harvest.grade_c_kg) || 0;
                        const totalWeight = (gradeA + gradeB + gradeC) || 0;
                        return (
                            <div key={harvest.id} className="bg-white rounded-lg shadow p-4 md:p-6">
                                <div className="flex flex-col md:flex-row justify-between items-start md:items-center mb-4 gap-4">
                                    <div>
                                        <div className="flex items-center gap-2 mb-2">
                                            <Package className="w-5 h-5 text-green-600" />
                                            <h3 className="text-lg font-semibold text-gray-900">{harvest.harvest_code}</h3>
                                            {harvest.harvest_number && (
                                                <span className="px-2 py-1 bg-blue-100 text-blue-800 text-xs font-medium rounded">
                                                    Harvest #{harvest.harvest_number}
                                                </span>
                                            )}
                                        </div>
                                        <div className="flex flex-wrap gap-4 text-sm text-gray-600">
                                            <div className="flex items-center gap-1">
                                                <Calendar className="w-4 h-4" />
                                                {new Date(harvest.harvest_date).toLocaleDateString()}
                                            </div>
                                            {harvest.greenhouse && (
                                                <div className="flex items-center gap-1">
                                                    <TrendingUp className="w-4 h-4" />
                                                    {harvest.greenhouse.name}
                                                </div>
                                            )}
                                            {harvest.harvester && (
                                                <div className="flex items-center gap-1">
                                                    <User className="w-4 h-4" />
                                                    {harvest.harvester.name}
                                                </div>
                                            )}
                                        </div>
                                    </div>
                                    <div className="text-right">
                                        <div className="text-2xl font-bold text-green-600">{formatNumber(totalWeight)} kg</div>
                                        <div className="text-sm text-gray-600">{harvest.crates_count || 0} crates</div>
                                    </div>
                                </div>

                                {/* Grade Breakdown */}
                                <div className="grid grid-cols-3 gap-4 mb-4">
                                    <div className="bg-green-50 rounded-lg p-3">
                                        <div className="text-xs font-medium text-green-700 mb-1">Grade A</div>
                                        <div className="text-lg font-semibold text-green-900">
                                            {formatNumber(gradeA)} kg
                                        </div>
                                    </div>
                                    <div className="bg-yellow-50 rounded-lg p-3">
                                        <div className="text-xs font-medium text-yellow-700 mb-1">Grade B</div>
                                        <div className="text-lg font-semibold text-yellow-900">
                                            {formatNumber(gradeB)} kg
                                        </div>
                                    </div>
                                    <div className="bg-orange-50 rounded-lg p-3">
                                        <div className="text-xs font-medium text-orange-700 mb-1">Grade C</div>
                                        <div className="text-lg font-semibold text-orange-900">
                                            {formatNumber(gradeC)} kg
                                        </div>
                                    </div>
                                </div>

                                {/* Status and Cycle Link */}
                                <div className="flex flex-col md:flex-row justify-between items-start md:items-center gap-2 pt-4 border-t">
                                    <div className="flex items-center gap-2">
                                        <span className={`px-3 py-1 rounded-full text-xs font-medium ${
                                            harvest.status === 'HARVESTED' ? 'bg-blue-100 text-blue-800' :
                                            harvest.status === 'PACKED' ? 'bg-purple-100 text-purple-800' :
                                            harvest.status === 'SOLD' ? 'bg-green-100 text-green-800' :
                                            'bg-gray-100 text-gray-800'
                                        }`}>
                                            {harvest.status}
                                        </span>
                                    </div>
                                    {harvest.cycle && (
                                        <Link
                                            to={`/bell-pepper-cycles/${harvest.cycle.id}`}
                                            className="text-sm text-green-600 hover:text-green-700 font-medium"
                                        >
                                            View Cycle →
                                        </Link>
                                    )}
                                </div>
                            </div>
                        );
                    })
                )}
            </div>

            {/* Date Validation Popup */}
            {showDateValidationPopup && selectedCycle && selectedCycle.start_date && (
                <div className="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center p-4 z-[100]" onClick={(e) => {
                    // Prevent closing when clicking on the popup itself
                    if (e.target === e.currentTarget) {
                        // Allow closing only if clicking the backdrop
                    }
                }}>
                    <div className="bg-white rounded-lg shadow-xl w-full max-w-md" onClick={(e) => e.stopPropagation()}>
                        <div className="p-6 border-b">
                            <h2 className="text-xl font-bold text-red-600">Invalid Harvest Date</h2>
                        </div>
                        <div className="p-6">
                            <p className="text-gray-700 mb-4">
                                The selected harvest date is less than 70 days from the cycle start date. This is not possible.
                            </p>
                            <div className="bg-gray-50 rounded-lg p-4 mb-4">
                                <div className="text-sm text-gray-600 space-y-2">
                                    <div>
                                        <span className="font-medium">Cycle Start Date:</span>{' '}
                                        {new Date(selectedCycle.start_date).toLocaleDateString()}
                                    </div>
                                    <div>
                                        <span className="font-medium">Selected Harvest Date:</span>{' '}
                                        {new Date(formData.harvest_date).toLocaleDateString()}
                                    </div>
                                    <div>
                                        <span className="font-medium">Minimum Harvest Date:</span>{' '}
                                        {new Date(new Date(selectedCycle.start_date).setDate(new Date(selectedCycle.start_date).getDate() + 70)).toLocaleDateString()}
                                    </div>
                                </div>
                            </div>
                            <p className="text-gray-700 mb-4">
                                Please select a date that is at least 70 days after the cycle start date.
                            </p>
                            <div className="flex gap-4">
                                <button
                                    type="button"
                                    onClick={() => {
                                        setShowDateValidationPopup(false);
                                        // Auto-set to minimum date
                                        const cycleStartDate = new Date(selectedCycle.start_date);
                                        const minHarvestDate = new Date(cycleStartDate);
                                        minHarvestDate.setDate(minHarvestDate.getDate() + 70);
                                        setFormData({ ...formData, harvest_date: minHarvestDate.toISOString().slice(0, 10) });
                                    }}
                                    className="flex-1 px-4 py-3 bg-green-600 text-white rounded-lg hover:bg-green-700 font-medium"
                                >
                                    Use Minimum Date
                                </button>
                                <button
                                    type="button"
                                    onClick={() => setShowDateValidationPopup(false)}
                                    className="flex-1 px-4 py-3 bg-gray-200 text-gray-800 rounded-lg hover:bg-gray-300 font-medium"
                                >
                                    Choose Different Date
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            )}

            {/* Create Harvest Modal */}
            {showModal && (
                <div className="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center p-4 z-50">
                    <div className="bg-white rounded-lg shadow-xl w-full max-w-2xl max-h-[90vh] overflow-y-auto">
                        <div className="p-6 border-b">
                            <h2 className="text-2xl font-bold text-gray-900">Record New Harvest</h2>
                            <p className="text-gray-600 mt-1">Enter harvest details by grade</p>
                        </div>
                        <form onSubmit={handleSubmit} className="p-6">
                            <div className="space-y-4">
                                <div>
                                    <label className="block text-sm font-medium text-gray-700 mb-1">Farm *</label>
                                    <select
                                        value={formData.farm_id}
                                        onChange={(e) => handleFarmChange(e.target.value)}
                                        required
                                        className="w-full border border-gray-300 rounded-lg px-3 py-2 text-lg focus:ring-2 focus:ring-green-500 focus:border-green-500"
                                    >
                                        <option value="">Select Farm</option>
                                        {farms.length === 0 ? (
                                            <option value="" disabled>Loading farms...</option>
                                        ) : (
                                            farms.map((farm) => (
                                                <option key={farm.id} value={String(farm.id)}>
                                                    {farm.name}
                                                </option>
                                            ))
                                        )}
                                    </select>
                                    {farms.length === 0 && (
                                        <p className="text-xs text-gray-500 mt-1">No farms available. Please create a farm first.</p>
                                    )}
                                </div>

                                <div>
                                    <label className="block text-sm font-medium text-gray-700 mb-1">Greenhouse *</label>
                                    <select
                                        value={formData.greenhouse_id}
                                        onChange={(e) => handleGreenhouseChange(e.target.value)}
                                        required
                                        disabled={!formData.farm_id}
                                        className="w-full border border-gray-300 rounded-lg px-3 py-2 text-lg focus:ring-2 focus:ring-green-500 focus:border-green-500 disabled:bg-gray-100"
                                    >
                                        <option value="">Select Greenhouse</option>
                                        {greenhouses.map((gh) => (
                                            <option key={gh.id} value={gh.id}>
                                                {gh.name}
                                            </option>
                                        ))}
                                    </select>
                                </div>

                                <div>
                                    <label className="block text-sm font-medium text-gray-700 mb-1">Production Cycle *</label>
                                    <select
                                        value={formData.bell_pepper_cycle_id}
                                        onChange={(e) => handleCycleChange(e.target.value)}
                                        required
                                        disabled={!formData.greenhouse_id || modalCycles.length === 0}
                                        className="w-full border border-gray-300 rounded-lg px-3 py-2 text-lg focus:ring-2 focus:ring-green-500 focus:border-green-500 disabled:bg-gray-100"
                                    >
                                        <option value="">Select Cycle</option>
                                        {modalCycles.length === 0 && formData.greenhouse_id ? (
                                            <option value="" disabled>No active cycles found for this greenhouse</option>
                                        ) : (
                                            modalCycles.map((cycle) => (
                                                <option key={cycle.id} value={String(cycle.id)}>
                                                    {cycle.cycle_code} {cycle.start_date ? `(${new Date(cycle.start_date).toLocaleDateString()})` : ''}
                                                </option>
                                            ))
                                        )}
                                    </select>
                                    {formData.greenhouse_id && modalCycles.length === 0 && (
                                        <p className="text-xs text-gray-500 mt-1">No in-progress or planned cycles found. Please create a production cycle first.</p>
                                    )}
                                    {selectedCycle && selectedCycle.start_date && (
                                        <p className="text-xs text-blue-600 mt-1">
                                            Cycle started: {new Date(selectedCycle.start_date).toLocaleDateString()}. 
                                            First harvest can be recorded from {new Date(new Date(selectedCycle.start_date).setDate(new Date(selectedCycle.start_date).getDate() + 70)).toLocaleDateString()}
                                        </p>
                                    )}
                                </div>

                                <div>
                                    <label className="block text-sm font-medium text-gray-700 mb-1">Harvest Date *</label>
                                    <input
                                        type="date"
                                        value={formData.harvest_date}
                                        onChange={(e) => {
                                            const newDate = e.target.value;
                                            console.log('Date changed to:', newDate, 'Selected cycle:', selectedCycle);
                                            handleHarvestDateChange(newDate);
                                        }}
                                        onBlur={handleHarvestDateBlur}
                                        onInput={(e) => {
                                            // Also validate on input for manual typing
                                            const newDate = e.target.value;
                                            console.log('Date input event:', newDate);
                                            if (newDate && selectedCycle && selectedCycle.start_date) {
                                                const validation = validateHarvestDate(newDate, selectedCycle);
                                                console.log('Validation result:', validation);
                                                if (!validation.valid) {
                                                    console.log('Showing validation popup');
                                                    setShowDateValidationPopup(true);
                                                } else {
                                                    setShowDateValidationPopup(false);
                                                }
                                            }
                                        }}
                                        // Note: min attribute helps prevent invalid selection, but validation still runs for manual input
                                        min={getMinHarvestDate() || undefined}
                                        max={getMaxHarvestDate()}
                                        required
                                        className={`w-full border rounded-lg px-3 py-2 text-lg focus:ring-2 focus:ring-green-500 focus:border-green-500 ${
                                            showDateValidationPopup ? 'border-red-500 bg-red-50' : 'border-gray-300'
                                        }`}
                                    />
                                    {selectedCycle && selectedCycle.start_date && (
                                        <p className="text-xs text-gray-500 mt-1">
                                            Harvest date must be at least 70 days after cycle start ({new Date(selectedCycle.start_date).toLocaleDateString()})
                                        </p>
                                    )}
                                    {!selectedCycle && (
                                        <p className="text-xs text-gray-500 mt-1">Please select a production cycle first to set date constraints</p>
                                    )}
                                </div>

                                {/* Grade Inputs - Mobile Friendly Large Inputs */}
                                <div className="space-y-4 bg-gray-50 p-4 rounded-lg">
                                    <h3 className="text-lg font-semibold text-gray-900 mb-3">Harvest by Grade (kg)</h3>
                                    
                                    <div className="bg-green-50 rounded-lg p-4">
                                        <label className="block text-sm font-medium text-green-700 mb-2">Grade A (Premium) *</label>
                                        <input
                                            type="number"
                                            step="0.01"
                                            min="0"
                                            value={formData.grade_a_kg}
                                            onChange={(e) => setFormData({ ...formData, grade_a_kg: e.target.value })}
                                            required
                                            className="w-full border border-green-300 rounded-lg px-4 py-3 text-xl font-semibold focus:ring-2 focus:ring-green-500 focus:border-green-500"
                                            placeholder="0.00"
                                        />
                                    </div>

                                    <div className="bg-yellow-50 rounded-lg p-4">
                                        <label className="block text-sm font-medium text-yellow-700 mb-2">Grade B (Standard) *</label>
                                        <input
                                            type="number"
                                            step="0.01"
                                            min="0"
                                            value={formData.grade_b_kg}
                                            onChange={(e) => setFormData({ ...formData, grade_b_kg: e.target.value })}
                                            required
                                            className="w-full border border-yellow-300 rounded-lg px-4 py-3 text-xl font-semibold focus:ring-2 focus:ring-yellow-500 focus:border-yellow-500"
                                            placeholder="0.00"
                                        />
                                    </div>

                                    <div className="bg-orange-50 rounded-lg p-4">
                                        <label className="block text-sm font-medium text-orange-700 mb-2">Grade C (Lower) *</label>
                                        <input
                                            type="number"
                                            step="0.01"
                                            min="0"
                                            value={formData.grade_c_kg}
                                            onChange={(e) => setFormData({ ...formData, grade_c_kg: e.target.value })}
                                            required
                                            className="w-full border border-orange-300 rounded-lg px-4 py-3 text-xl font-semibold focus:ring-2 focus:ring-orange-500 focus:border-orange-500"
                                            placeholder="0.00"
                                        />
                                    </div>

                                    <div className="bg-blue-50 rounded-lg p-4">
                                        <div className="text-sm font-medium text-blue-700 mb-1">Total Weight</div>
                                        <div className="text-2xl font-bold text-blue-900">{formatNumber(calculateTotalWeight())} kg</div>
                                        <div className="text-xs text-blue-600 mt-1">Estimated crates: {calculateCrates()}</div>
                                    </div>
                                </div>

                                <div>
                                    <label className="block text-sm font-medium text-gray-700 mb-1">Crates Count</label>
                                    <input
                                        type="number"
                                        min="0"
                                        value={formData.crates_count}
                                        onChange={(e) => setFormData({ ...formData, crates_count: e.target.value })}
                                        className="w-full border border-gray-300 rounded-lg px-3 py-2 text-lg focus:ring-2 focus:ring-green-500 focus:border-green-500"
                                        placeholder={`Auto: ${calculateCrates()}`}
                                    />
                                    <p className="text-xs text-gray-500 mt-1">Leave empty to auto-calculate (9.5kg per crate)</p>
                                </div>

                                <div>
                                    <label className="block text-sm font-medium text-gray-700 mb-1">Notes</label>
                                    <textarea
                                        value={formData.notes}
                                        onChange={(e) => setFormData({ ...formData, notes: e.target.value })}
                                        rows={3}
                                        className="w-full border border-gray-300 rounded-lg px-3 py-2 focus:ring-2 focus:ring-green-500 focus:border-green-500"
                                        placeholder="Additional notes about this harvest..."
                                    />
                                </div>
                            </div>

                            <div className="flex gap-4 mt-6">
                                <button
                                    type="button"
                                    onClick={() => setShowModal(false)}
                                    className="flex-1 px-4 py-3 bg-gray-200 text-gray-800 rounded-lg hover:bg-gray-300 font-medium"
                                >
                                    Cancel
                                </button>
                                <button
                                    type="submit"
                                    className="flex-1 px-4 py-3 bg-green-600 text-white rounded-lg hover:bg-green-700 font-medium"
                                >
                                    Record Harvest
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            )}
        </div>
    );
}

