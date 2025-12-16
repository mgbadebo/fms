import React, { useEffect, useState } from 'react';
import api from '../utils/api';
import { ShoppingCart, Plus, TrendingUp, DollarSign, Edit } from 'lucide-react';

export default function GariSales() {
    const [sales, setSales] = useState([]);
    const [summary, setSummary] = useState([]);
    const [farms, setFarms] = useState([]);
    const [availableBatches, setAvailableBatches] = useState([]);
    const [loadingBatches, setLoadingBatches] = useState(false);
    const [loading, setLoading] = useState(true);
    const [showModal, setShowModal] = useState(false);
    const [editingSale, setEditingSale] = useState(null);
    const [editFormData, setEditFormData] = useState(null);
    const [formData, setFormData] = useState({
        farm_id: '',
        gari_production_batch_id: '',
        sale_date: new Date().toISOString().slice(0, 10),
        customer_name: '',
        customer_type: 'RETAIL',
        gari_type: 'WHITE',
        gari_grade: 'FINE',
        packaging_type: '1KG_POUCH',
        quantity_kg: '',
        quantity_units: '',
        unit_price: '',
        discount: 0,
        cost_per_kg: '',
        payment_method: 'CASH',
        amount_paid: '',
        sales_channel: '',
        notes: '',
    });

    useEffect(() => {
        fetchData();
    }, []);

    const fetchData = async () => {
        try {
            const [salesRes, farmsRes, summaryRes] = await Promise.all([
                api.get('/api/v1/gari-sales'),
                api.get('/api/v1/farms'),
                api.get('/api/v1/gari-sales/summary'),
            ]);
            setSales(salesRes.data.data || salesRes.data);
            setFarms(farmsRes.data.data || farmsRes.data);
            setSummary(summaryRes.data.data || summaryRes.data);
        } catch (error) {
            console.error('Error fetching data:', error);
        } finally {
            setLoading(false);
        }
    };

    const fetchFarms = async () => {
        try {
            const response = await api.get('/api/v1/farms');
            setFarms(response.data.data || response.data);
        } catch (error) {
            console.error('Error fetching farms:', error);
        }
    };

    const handleModalOpen = () => {
        setShowModal(true);
        // Refresh farms list when opening modal to get newly created farms
        fetchFarms();
        // Reset form data
        setFormData({
            farm_id: '',
            gari_production_batch_id: '',
            sale_date: new Date().toISOString().slice(0, 10),
            customer_name: '',
            customer_type: 'RETAIL',
            gari_type: 'WHITE',
            gari_grade: 'FINE',
            packaging_type: '1KG_POUCH',
            quantity_kg: '',
            quantity_units: '',
            unit_price: '',
            discount: 0,
            cost_per_kg: '',
            payment_method: 'CASH',
            amount_paid: '',
            sales_channel: '',
            notes: '',
        });
        setAvailableBatches([]);
        setAvailableBatches([]);
    };

    const fetchAvailableBatches = async () => {
        if (!formData.farm_id) {
            setAvailableBatches([]);
            setLoadingBatches(false);
            return;
        }

        setLoadingBatches(true);
        try {
            const params = new URLSearchParams({
                farm_id: formData.farm_id,
            });
            const response = await api.get(`/api/v1/gari-sales/available-batches?${params.toString()}`);
            const batches = response.data.data || [];
            setAvailableBatches(batches);
            
            // Auto-select the first batch (FIFO - oldest first) if none selected
            if (batches.length > 0 && !formData.gari_production_batch_id) {
                const firstBatch = batches[0];
                handleBatchSelection(firstBatch.batch_id);
            } else if (batches.length === 0) {
                // Clear selection if no batches available
                setFormData(prev => ({
                    ...prev,
                    gari_production_batch_id: '',
                    gari_type: 'WHITE',
                    gari_grade: 'FINE',
                    packaging_type: '1KG_POUCH',
                    cost_per_kg: '',
                }));
            }
        } catch (error) {
            console.error('Error fetching available batches:', error);
            setAvailableBatches([]);
            alert('Error loading available batches: ' + (error.response?.data?.message || 'Unknown error'));
        } finally {
            setLoadingBatches(false);
        }
    };

    const handleBatchSelection = (batchId) => {
        const selectedBatch = availableBatches.find(b => b.batch_id == batchId);
        if (selectedBatch) {
            // Auto-fill fields from batch
            const defaultPackaging = selectedBatch.packaging_options && selectedBatch.packaging_options.length > 0
                ? selectedBatch.packaging_options[0].packaging_type
                : '1KG_POUCH';
            
            setFormData(prev => ({
                ...prev,
                gari_production_batch_id: batchId,
                gari_type: selectedBatch.gari_type || prev.gari_type,
                gari_grade: selectedBatch.gari_grade || prev.gari_grade,
                packaging_type: defaultPackaging,
                cost_per_kg: selectedBatch.cost_per_kg_gari || prev.cost_per_kg,
                quantity_units: '',
                quantity_kg: '',
            }));
        }
    };

    // Calculate kg from packaging type and quantity
    const calculateKgFromPackaging = (packagingType, quantityUnits) => {
        if (!packagingType || !quantityUnits || quantityUnits <= 0) return 0;
        
        const packagingWeights = {
            '1KG_POUCH': 1,
            '2KG_POUCH': 2,
            '5KG_PACK': 5,
            '50KG_BAG': 50,
        };
        
        const weightPerUnit = packagingWeights[packagingType] || 0;
        return weightPerUnit * quantityUnits;
    };

    const handlePackagingChange = (packagingType) => {
        setFormData(prev => {
            const newKg = calculateKgFromPackaging(packagingType, prev.quantity_units || 0);
            return {
                ...prev,
                packaging_type: packagingType,
                quantity_kg: newKg,
            };
        });
    };

    const handleQuantityUnitsChange = (quantityUnits) => {
        setFormData(prev => {
            const newKg = calculateKgFromPackaging(prev.packaging_type, quantityUnits || 0);
            return {
                ...prev,
                quantity_units: quantityUnits,
                quantity_kg: newKg,
            };
        });
    };

    // Fetch batches when farm changes
    useEffect(() => {
        if (showModal && formData.farm_id) {
            fetchAvailableBatches();
        }
    }, [showModal, formData.farm_id]);

    const handleSubmit = async (e) => {
        e.preventDefault();
        try {
            await api.post('/api/v1/gari-sales', formData);
            setShowModal(false);
            fetchData();
        } catch (error) {
            alert('Error creating sale: ' + (error.response?.data?.message || 'Unknown error'));
        }
    };

    const handleEditClick = (sale) => {
        setEditingSale(sale);
        setEditFormData({
            payment_status: sale.payment_status || 'PAID',
            payment_method: sale.payment_method || 'CASH',
            amount_paid: sale.amount_paid || 0,
            discount: sale.discount || 0,
            unit_price: sale.unit_price || 0,
            notes: sale.notes || '',
        });
    };

    const handleEditSubmit = async (e) => {
        e.preventDefault();
        try {
            await api.put(`/api/v1/gari-sales/${editingSale.id}`, editFormData);
            setEditingSale(null);
            setEditFormData(null);
            fetchData();
        } catch (error) {
            alert('Error updating sale: ' + (error.response?.data?.message || 'Unknown error'));
        }
    };

    const handleAmountPaidChange = (amountPaid) => {
        const finalAmount = Number(editingSale?.final_amount || 0);
        const paid = Number(amountPaid || 0);
        let paymentStatus = 'OUTSTANDING';
        
        if (paid >= finalAmount) {
            paymentStatus = 'PAID';
        } else if (paid > 0) {
            paymentStatus = 'PARTIAL';
        }
        
        setEditFormData({
            ...editFormData,
            amount_paid: amountPaid,
            payment_status: paymentStatus,
        });
    };

    if (loading) {
        return (
            <div className="flex items-center justify-center h-64">
                <div className="animate-spin rounded-full h-12 w-12 border-b-2 border-green-600"></div>
            </div>
        );
    }

    const totalRevenue = sales.reduce((sum, sale) => sum + (Number(sale.final_amount) || 0), 0);
    const totalMargin = sales.reduce((sum, sale) => sum + (Number(sale.gross_margin) || 0), 0);

    return (
        <div>
            <div className="flex justify-between items-center mb-8">
                <div>
                    <h1 className="text-3xl font-bold text-gray-900">Gari Sales</h1>
                    <p className="mt-2 text-gray-600">Track sales and revenue</p>
                </div>
                <button
                    onClick={handleModalOpen}
                    className="bg-green-600 text-white px-4 py-2 rounded-lg hover:bg-green-700 flex items-center"
                >
                    <Plus className="h-5 w-5 mr-2" />
                    New Sale
                </button>
            </div>

            {/* Summary Cards */}
            <div className="grid grid-cols-1 md:grid-cols-3 gap-6 mb-6">
                <div className="bg-white rounded-lg shadow p-6">
                    <p className="text-sm text-gray-600 mb-1">Total Revenue</p>
                    <p className="text-2xl font-bold text-gray-900">₦{Number(totalRevenue || 0).toFixed(2)}</p>
                </div>
                <div className="bg-white rounded-lg shadow p-6">
                    <p className="text-sm text-gray-600 mb-1">Total Margin</p>
                    <p className="text-2xl font-bold text-green-600">₦{Number(totalMargin || 0).toFixed(2)}</p>
                </div>
                <div className="bg-white rounded-lg shadow p-6">
                    <p className="text-sm text-gray-600 mb-1">Total Sales</p>
                    <p className="text-2xl font-bold text-gray-900">{sales.length}</p>
                </div>
            </div>

            {/* Sales Table */}
            {sales.length === 0 ? (
                <div className="bg-white rounded-lg shadow p-12 text-center">
                    <ShoppingCart className="h-16 w-16 mx-auto text-gray-400 mb-4" />
                    <h3 className="text-lg font-medium text-gray-900 mb-2">No sales yet</h3>
                    <p className="text-gray-500 mb-4">Create your first sale to get started</p>
                    <button
                        onClick={handleModalOpen}
                        className="bg-green-600 text-white px-4 py-2 rounded-lg hover:bg-green-700"
                    >
                        Create Sale
                    </button>
                </div>
            ) : (
                <div className="bg-white rounded-lg shadow overflow-hidden">
                    <div className="overflow-x-auto">
                        <table className="min-w-full divide-y divide-gray-200">
                            <thead className="bg-gray-50">
                                <tr>
                                    <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Sale Code</th>
                                    <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Date</th>
                                    <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Customer</th>
                                    <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Product</th>
                                    <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Quantity</th>
                                    <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Amount</th>
                                    <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Margin</th>
                                    <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Payment</th>
                                    <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Actions</th>
                                </tr>
                            </thead>
                            <tbody className="bg-white divide-y divide-gray-200">
                                {sales.map((sale) => (
                                    <tr key={sale.id} className="hover:bg-gray-50">
                                        <td className="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
                                            {sale.sale_code}
                                        </td>
                                        <td className="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                            {new Date(sale.sale_date).toLocaleDateString()}
                                        </td>
                                        <td className="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                            {sale.customer_name || sale.customer?.name || 'Walk-in'}
                                            <span className="ml-2 text-xs text-gray-500">({sale.customer_type})</span>
                                        </td>
                                        <td className="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                            {sale.gari_type} • {sale.packaging_type?.replace('_', ' ')}
                                        </td>
                                        <td className="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                            {sale.quantity_kg} kg
                                        </td>
                                        <td className="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
                                            ₦{Number(sale.final_amount || 0).toFixed(2)}
                                        </td>
                                        <td className="px-6 py-4 whitespace-nowrap text-sm text-green-600">
                                            ₦{Number(sale.gross_margin || 0).toFixed(2)}
                                            <span className="text-xs text-gray-500 ml-1">
                                                ({Number(sale.gross_margin_percent || 0).toFixed(1)}%)
                                            </span>
                                        </td>
                                        <td className="px-6 py-4 whitespace-nowrap">
                                            <span className={`px-2 py-1 text-xs font-medium rounded ${
                                                sale.payment_status === 'PAID' ? 'bg-green-100 text-green-800' :
                                                sale.payment_status === 'PARTIAL' ? 'bg-yellow-100 text-yellow-800' :
                                                'bg-red-100 text-red-800'
                                            }`}>
                                                {sale.payment_status}
                                            </span>
                                        </td>
                                        <td className="px-6 py-4 whitespace-nowrap text-sm">
                                            <button
                                                onClick={() => handleEditClick(sale)}
                                                className="text-green-600 hover:text-green-700 flex items-center"
                                            >
                                                <Edit className="h-4 w-4 mr-1" />
                                                Edit
                                            </button>
                                        </td>
                                    </tr>
                                ))}
                            </tbody>
                        </table>
                    </div>
                </div>
            )}

            {/* Create Sale Modal */}
            {showModal && (
                <div className="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50 p-4 overflow-y-auto">
                    <div className="bg-white rounded-lg max-w-2xl w-full p-6 max-h-[90vh] overflow-y-auto">
                        <h2 className="text-xl font-bold mb-4">Create New Sale</h2>
                        <form onSubmit={handleSubmit} className="space-y-4">
                            <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
                                <div>
                                    <label className="block text-sm font-medium text-gray-700 mb-1">Farm *</label>
                                    <select
                                        required
                                        value={formData.farm_id}
                                        onChange={(e) => setFormData({ ...formData, farm_id: e.target.value })}
                                        className="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-green-500 focus:border-green-500"
                                    >
                                        <option value="">Select farm</option>
                                        {farms.map((farm) => (
                                            <option key={farm.id} value={farm.id}>{farm.name}</option>
                                        ))}
                                    </select>
                                </div>
                                <div>
                                    <label className="block text-sm font-medium text-gray-700 mb-1">Sale Date *</label>
                                    <input
                                        type="date"
                                        required
                                        value={formData.sale_date}
                                        onChange={(e) => setFormData({ ...formData, sale_date: e.target.value })}
                                        className="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-green-500 focus:border-green-500"
                                    />
                                </div>
                                <div>
                                    <label className="block text-sm font-medium text-gray-700 mb-1">Customer Name</label>
                                    <input
                                        type="text"
                                        value={formData.customer_name}
                                        onChange={(e) => setFormData({ ...formData, customer_name: e.target.value })}
                                        className="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-green-500 focus:border-green-500"
                                        placeholder="Walk-in customer"
                                    />
                                </div>
                                <div>
                                    <label className="block text-sm font-medium text-gray-700 mb-1">Customer Type *</label>
                                    <select
                                        required
                                        value={formData.customer_type}
                                        onChange={(e) => setFormData({ ...formData, customer_type: e.target.value })}
                                        className="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-green-500 focus:border-green-500"
                                    >
                                        <option value="RETAIL">Retail</option>
                                        <option value="BULK_BUYER">Bulk Buyer</option>
                                        <option value="DISTRIBUTOR">Distributor</option>
                                        <option value="CATERING">Catering</option>
                                        <option value="HOTEL">Hotel</option>
                                        <option value="OTHER">Other</option>
                                    </select>
                                </div>
                                <div className="md:col-span-2">
                                    <label className="block text-sm font-medium text-gray-700 mb-1">Production Batch (FIFO) *</label>
                                    <select
                                        required
                                        disabled={!formData.farm_id || loadingBatches}
                                        value={formData.gari_production_batch_id}
                                        onChange={(e) => handleBatchSelection(e.target.value)}
                                        className={`w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-green-500 focus:border-green-500 ${
                                            (!formData.farm_id || loadingBatches)
                                                ? 'bg-gray-100 text-gray-500 cursor-not-allowed'
                                                : 'bg-white'
                                        }`}
                                    >
                                        <option value="">
                                            {loadingBatches 
                                                ? 'Loading batches...' 
                                                : !formData.farm_id
                                                    ? 'Select farm first'
                                                    : availableBatches.length === 0
                                                        ? 'No inventory available for this farm'
                                                        : 'Select batch (oldest first - FIFO)'}
                                        </option>
                                        {availableBatches.map((batch) => (
                                            <option key={batch.batch_id} value={batch.batch_id}>
                                                {batch.batch_code} - {Number(batch.total_available_kg || 0).toFixed(2)} kg available
                                                {batch.processing_date && ` (${new Date(batch.processing_date).toLocaleDateString()})`}
                                                {batch.gari_type && ` - ${batch.gari_type}`}
                                                {batch.gari_grade && ` ${batch.gari_grade}`}
                                                {batch.cost_per_kg_gari && ` - ₦${Number(batch.cost_per_kg_gari).toFixed(2)}/kg`}
                                            </option>
                                        ))}
                                    </select>
                                    <p className="text-xs text-gray-500 mt-1">
                                        {loadingBatches 
                                            ? 'Loading available batches...' 
                                            : 'Batches are sorted by production date (FIFO - First In, First Out). Selecting a batch will auto-fill product details.'}
                                    </p>
                                </div>
                                <div>
                                    <label className="block text-sm font-medium text-gray-700 mb-1">Gari Type *</label>
                                    <select
                                        required
                                        disabled={!formData.gari_production_batch_id}
                                        value={formData.gari_type}
                                        onChange={(e) => setFormData({ ...formData, gari_type: e.target.value })}
                                        className={`w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-green-500 focus:border-green-500 ${
                                            !formData.gari_production_batch_id ? 'bg-gray-100 text-gray-500 cursor-not-allowed' : 'bg-white'
                                        }`}
                                    >
                                        <option value="WHITE">White</option>
                                        <option value="YELLOW">Yellow</option>
                                    </select>
                                    <p className="text-xs text-gray-500 mt-1">Auto-filled from selected batch</p>
                                </div>
                                <div>
                                    <label className="block text-sm font-medium text-gray-700 mb-1">Gari Grade *</label>
                                    <select
                                        required
                                        disabled={!formData.gari_production_batch_id}
                                        value={formData.gari_grade}
                                        onChange={(e) => setFormData({ ...formData, gari_grade: e.target.value })}
                                        className={`w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-green-500 focus:border-green-500 ${
                                            !formData.gari_production_batch_id ? 'bg-gray-100 text-gray-500 cursor-not-allowed' : 'bg-white'
                                        }`}
                                    >
                                        <option value="FINE">Fine</option>
                                        <option value="COARSE">Coarse</option>
                                        <option value="MIXED">Mixed</option>
                                    </select>
                                    <p className="text-xs text-gray-500 mt-1">Auto-filled from selected batch</p>
                                </div>
                                <div>
                                    <label className="block text-sm font-medium text-gray-700 mb-1">Packaging *</label>
                                    <select
                                        required
                                        disabled={!formData.gari_production_batch_id}
                                        value={formData.packaging_type}
                                        onChange={(e) => handlePackagingChange(e.target.value)}
                                        className={`w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-green-500 focus:border-green-500 ${
                                            !formData.gari_production_batch_id ? 'bg-gray-100 text-gray-500 cursor-not-allowed' : 'bg-white'
                                        }`}
                                    >
                                        <option value="1KG_POUCH">1kg Pouch</option>
                                        <option value="2KG_POUCH">2kg Pouch</option>
                                        <option value="5KG_PACK">5kg Pack</option>
                                        <option value="50KG_BAG">50kg Bag</option>
                                    </select>
                                    <p className="text-xs text-gray-500 mt-1">
                                        {formData.gari_production_batch_id 
                                            ? 'Select packaging size' 
                                            : 'Select a batch first'}
                                    </p>
                                </div>
                                <div>
                                    <label className="block text-sm font-medium text-gray-700 mb-1">Quantity (units) *</label>
                                    <input
                                        type="number"
                                        step="1"
                                        min="1"
                                        required
                                        disabled={!formData.packaging_type}
                                        value={formData.quantity_units}
                                        onChange={(e) => handleQuantityUnitsChange(e.target.value)}
                                        className={`w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-green-500 focus:border-green-500 ${
                                            !formData.packaging_type ? 'bg-gray-100 text-gray-500 cursor-not-allowed' : 'bg-white'
                                        }`}
                                        placeholder="Enter number of units"
                                    />
                                    <p className="text-xs text-gray-500 mt-1">
                                        Number of {formData.packaging_type?.replace('_', ' ').toLowerCase() || 'units'}
                                    </p>
                                </div>
                                <div>
                                    <label className="block text-sm font-medium text-gray-700 mb-1">Total Quantity (kg) *</label>
                                    <input
                                        type="number"
                                        step="0.01"
                                        required
                                        readOnly
                                        value={formData.quantity_kg || 0}
                                        className="w-full px-3 py-2 border border-gray-300 rounded-lg bg-gray-50 text-gray-700 cursor-not-allowed"
                                    />
                                    <p className="text-xs text-gray-500 mt-1">
                                        Auto-calculated from packaging and quantity
                                    </p>
                                </div>
                                <div>
                                    <label className="block text-sm font-medium text-gray-700 mb-1">Unit Price (₦) *</label>
                                    <input
                                        type="number"
                                        step="0.01"
                                        required
                                        value={formData.unit_price}
                                        onChange={(e) => setFormData({ ...formData, unit_price: e.target.value })}
                                        className="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-green-500 focus:border-green-500"
                                    />
                                </div>
                                <div>
                                    <label className="block text-sm font-medium text-gray-700 mb-1">Discount (₦)</label>
                                    <input
                                        type="number"
                                        step="0.01"
                                        value={formData.discount}
                                        onChange={(e) => setFormData({ ...formData, discount: e.target.value })}
                                        className="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-green-500 focus:border-green-500"
                                    />
                                </div>
                                <div>
                                    <label className="block text-sm font-medium text-gray-700 mb-1">Cost per kg (₦)</label>
                                    <input
                                        type="number"
                                        step="0.01"
                                        value={formData.cost_per_kg}
                                        onChange={(e) => setFormData({ ...formData, cost_per_kg: e.target.value })}
                                        className="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-green-500 focus:border-green-500"
                                        placeholder="From inventory"
                                    />
                                </div>
                                <div>
                                    <label className="block text-sm font-medium text-gray-700 mb-1">Payment Method *</label>
                                    <select
                                        required
                                        value={formData.payment_method}
                                        onChange={(e) => setFormData({ ...formData, payment_method: e.target.value })}
                                        className="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-green-500 focus:border-green-500"
                                    >
                                        <option value="CASH">Cash</option>
                                        <option value="TRANSFER">Transfer</option>
                                        <option value="POS">POS</option>
                                        <option value="CHEQUE">Cheque</option>
                                        <option value="CREDIT">Credit</option>
                                    </select>
                                </div>
                                <div>
                                    <label className="block text-sm font-medium text-gray-700 mb-1">Amount Paid (₦)</label>
                                    <input
                                        type="number"
                                        step="0.01"
                                        value={formData.amount_paid}
                                        onChange={(e) => setFormData({ ...formData, amount_paid: e.target.value })}
                                        className="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-green-500 focus:border-green-500"
                                    />
                                </div>
                                <div className="md:col-span-2">
                                    <label className="block text-sm font-medium text-gray-700 mb-1">Sales Channel</label>
                                    <input
                                        type="text"
                                        value={formData.sales_channel}
                                        onChange={(e) => setFormData({ ...formData, sales_channel: e.target.value })}
                                        className="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-green-500 focus:border-green-500"
                                        placeholder="e.g., Warehouse, Shop, Distributor"
                                    />
                                </div>
                            </div>
                            <div className="flex justify-end space-x-3 pt-4">
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
                                    Create Sale
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            )}

            {/* Edit Sale Modal */}
            {editingSale && editFormData && (
                <div className="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50 p-4 overflow-y-auto">
                    <div className="bg-white rounded-lg max-w-2xl w-full p-6 max-h-[90vh] overflow-y-auto">
                        <h2 className="text-xl font-bold mb-4">Edit Sale - {editingSale.sale_code}</h2>
                        <form onSubmit={handleEditSubmit} className="space-y-4">
                            <div className="bg-gray-50 p-4 rounded-lg mb-4">
                                <div className="grid grid-cols-2 gap-4 text-sm">
                                    <div>
                                        <span className="text-gray-600">Customer:</span>
                                        <span className="ml-2 font-medium">{editingSale.customer_name || 'Walk-in'}</span>
                                    </div>
                                    <div>
                                        <span className="text-gray-600">Product:</span>
                                        <span className="ml-2 font-medium">{editingSale.gari_type} • {editingSale.packaging_type?.replace('_', ' ')}</span>
                                    </div>
                                    <div>
                                        <span className="text-gray-600">Quantity:</span>
                                        <span className="ml-2 font-medium">{editingSale.quantity_kg} kg</span>
                                    </div>
                                    <div>
                                        <span className="text-gray-600">Total Amount:</span>
                                        <span className="ml-2 font-medium">₦{Number(editingSale.final_amount || 0).toFixed(2)}</span>
                                    </div>
                                </div>
                            </div>

                            <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
                                <div>
                                    <label className="block text-sm font-medium text-gray-700 mb-1">Payment Status *</label>
                                    <select
                                        required
                                        value={editFormData.payment_status}
                                        onChange={(e) => setEditFormData({ ...editFormData, payment_status: e.target.value })}
                                        className="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-green-500 focus:border-green-500"
                                    >
                                        <option value="PAID">Paid</option>
                                        <option value="PARTIAL">Partial</option>
                                        <option value="OUTSTANDING">Outstanding</option>
                                    </select>
                                </div>
                                <div>
                                    <label className="block text-sm font-medium text-gray-700 mb-1">Payment Method *</label>
                                    <select
                                        required
                                        value={editFormData.payment_method}
                                        onChange={(e) => setEditFormData({ ...editFormData, payment_method: e.target.value })}
                                        className="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-green-500 focus:border-green-500"
                                    >
                                        <option value="CASH">Cash</option>
                                        <option value="TRANSFER">Transfer</option>
                                        <option value="POS">POS</option>
                                        <option value="CHEQUE">Cheque</option>
                                        <option value="CREDIT">Credit</option>
                                    </select>
                                </div>
                                <div>
                                    <label className="block text-sm font-medium text-gray-700 mb-1">Amount Paid (₦) *</label>
                                    <input
                                        type="number"
                                        step="0.01"
                                        min="0"
                                        max={Number(editingSale.final_amount || 0)}
                                        required
                                        value={editFormData.amount_paid}
                                        onChange={(e) => handleAmountPaidChange(e.target.value)}
                                        className="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-green-500 focus:border-green-500"
                                    />
                                    <p className="text-xs text-gray-500 mt-1">
                                        Outstanding: ₦{Number(Number(editingSale.final_amount || 0) - Number(editFormData.amount_paid || 0)).toFixed(2)}
                                    </p>
                                </div>
                                <div>
                                    <label className="block text-sm font-medium text-gray-700 mb-1">Discount (₦)</label>
                                    <input
                                        type="number"
                                        step="0.01"
                                        value={editFormData.discount}
                                        onChange={(e) => setEditFormData({ ...editFormData, discount: e.target.value })}
                                        className="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-green-500 focus:border-green-500"
                                    />
                                </div>
                                <div>
                                    <label className="block text-sm font-medium text-gray-700 mb-1">Unit Price (₦)</label>
                                    <input
                                        type="number"
                                        step="0.01"
                                        value={editFormData.unit_price}
                                        onChange={(e) => setEditFormData({ ...editFormData, unit_price: e.target.value })}
                                        className="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-green-500 focus:border-green-500"
                                    />
                                </div>
                                <div className="md:col-span-2">
                                    <label className="block text-sm font-medium text-gray-700 mb-1">Notes</label>
                                    <textarea
                                        value={editFormData.notes}
                                        onChange={(e) => setEditFormData({ ...editFormData, notes: e.target.value })}
                                        rows={3}
                                        className="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-green-500 focus:border-green-500"
                                    />
                                </div>
                            </div>

                            <div className="flex justify-end space-x-3 pt-4">
                                <button
                                    type="button"
                                    onClick={() => {
                                        setEditingSale(null);
                                        setEditFormData(null);
                                    }}
                                    className="px-4 py-2 border border-gray-300 rounded-lg text-gray-700 hover:bg-gray-50"
                                >
                                    Cancel
                                </button>
                                <button
                                    type="submit"
                                    className="px-4 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700"
                                >
                                    Update Sale
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            )}
        </div>
    );
}

