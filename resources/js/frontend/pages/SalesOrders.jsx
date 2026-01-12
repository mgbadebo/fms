import React, { useEffect, useState } from 'react';
import api from '../utils/api';
import { ShoppingCart, Plus, Edit, Trash2, CheckCircle, Truck, FileText, X, DollarSign } from 'lucide-react';

export default function SalesOrders() {
    const [orders, setOrders] = useState([]);
    const [farms, setFarms] = useState([]);
    const [sites, setSites] = useState([]);
    const [customers, setCustomers] = useState([]);
    const [products, setProducts] = useState([]);
    const [cycles, setCycles] = useState([]);
    const [harvests, setHarvests] = useState([]);
    const [harvestLots, setHarvestLots] = useState([]);
    const [loading, setLoading] = useState(true);
    const [showModal, setShowModal] = useState(false);
    const [showPaymentModal, setShowPaymentModal] = useState(false);
    const [selectedOrder, setSelectedOrder] = useState(null);
    const [editingOrder, setEditingOrder] = useState(null);
    const [paymentData, setPaymentData] = useState({
        payment_date: new Date().toISOString().slice(0, 10),
        amount: '',
        currency: 'USD',
        method: 'CASH',
        reference: '',
        notes: '',
    });
    const [formData, setFormData] = useState({
        farm_id: '',
        site_id: '',
        customer_id: '',
        order_date: new Date().toISOString().slice(0, 10),
        status: 'DRAFT',
        currency: 'USD',
        discount_total: 0,
        tax_total: 0,
        due_date: '',
        notes: '',
        items: [],
    });

    useEffect(() => {
        fetchData();
    }, []);

    const fetchData = async () => {
        try {
            const [ordersRes, farmsRes, sitesRes, customersRes, productsRes, cyclesRes, harvestsRes, harvestLotsRes] = await Promise.all([
                api.get('/api/v1/sales-orders'),
                api.get('/api/v1/farms?per_page=1000'),
                api.get('/api/v1/sites?per_page=1000'),
                api.get('/api/v1/customers?per_page=1000'),
                api.get('/api/v1/products?per_page=1000'),
                api.get('/api/v1/production-cycles?per_page=1000'),
                api.get('/api/v1/bell-pepper-harvests?per_page=1000'),
                api.get('/api/v1/harvest-lots?per_page=1000'),
            ]);

            const ordersData = ordersRes.data?.data || ordersRes.data || [];
            const farmsData = farmsRes.data?.data || farmsRes.data || [];
            const sitesData = sitesRes.data?.data || sitesRes.data || [];
            const customersData = customersRes.data?.data || customersRes.data || [];
            const productsData = productsRes.data?.data || productsRes.data || [];
            const cyclesData = cyclesRes.data?.data || cyclesRes.data || [];
            const harvestsData = harvestsRes.data?.data || harvestsRes.data || [];
            const harvestLotsData = harvestLotsRes.data?.data || harvestLotsRes.data || [];

            setOrders(Array.isArray(ordersData) ? ordersData : []);
            setFarms(Array.isArray(farmsData) ? farmsData : []);
            setSites(Array.isArray(sitesData) ? sitesData : []);
            setCustomers(Array.isArray(customersData) ? customersData : []);
            setProducts(Array.isArray(productsData) ? productsData : []);
            setCycles(Array.isArray(cyclesData) ? cyclesData : []);
            setHarvests(Array.isArray(harvestsData) ? harvestsData : []);
            setHarvestLots(Array.isArray(harvestLotsData) ? harvestLotsData : []);
        } catch (error) {
            console.error('Error fetching data:', error);
            alert('Error loading data: ' + (error.response?.data?.message || error.message));
        } finally {
            setLoading(false);
        }
    };

    const handleModalOpen = () => {
        setEditingOrder(null);
        const defaultFarm = farms.length === 1 ? farms[0].id : '';
        setFormData({
            farm_id: defaultFarm,
            site_id: '',
            customer_id: '',
            order_date: new Date().toISOString().slice(0, 10),
            status: 'DRAFT',
            currency: defaultFarm ? (farms.find(f => f.id === defaultFarm)?.default_currency || 'USD') : 'USD',
            discount_total: 0,
            tax_total: 0,
            due_date: '',
            notes: '',
            items: [],
        });
        setShowModal(true);
    };

    const handlePaymentModalOpen = (order) => {
        setSelectedOrder(order);
        setPaymentData({
            payment_date: new Date().toISOString().slice(0, 10),
            amount: '',
            currency: order.currency || 'USD',
            method: 'CASH',
            reference: '',
            notes: '',
        });
        setShowPaymentModal(true);
    };

    const handlePaymentSubmit = async (e) => {
        e.preventDefault();
        try {
            await api.post(`/api/v1/sales-orders/${selectedOrder.id}/payments`, paymentData);
            setShowPaymentModal(false);
            fetchData();
        } catch (error) {
            const errorMsg = error.response?.data?.message || error.response?.data?.errors || 'Unknown error';
            alert('Error recording payment: ' + (typeof errorMsg === 'string' ? errorMsg : JSON.stringify(errorMsg)));
        }
    };

    const handleEdit = (order) => {
        if (order.status !== 'DRAFT') {
            alert('Only DRAFT orders can be edited');
            return;
        }
        setEditingOrder(order);
        setFormData({
            farm_id: order.farm?.id || '',
            site_id: order.site?.id || '',
            customer_id: order.customer?.id || '',
            order_date: order.order_date || new Date().toISOString().slice(0, 10),
            status: order.status || 'DRAFT',
            currency: order.currency || 'USD',
            discount_total: order.discount_total || 0,
            tax_total: order.tax_total || 0,
            due_date: order.due_date || '',
            notes: order.notes || '',
            items: order.items?.map(item => ({
                product_id: item.product?.id || '',
                production_cycle_id: item.production_cycle?.id || '',
                harvest_record_id: item.harvest_record?.id || '',
                harvest_lot_id: item.harvest_lot?.id || '',
                product_name: item.product_name || item.product?.name || '',
                quantity: item.quantity || '',
                unit: item.unit || '',
                unit_price: item.unit_price || '',
                discount_amount: item.discount_amount || 0,
                quality_grade: item.quality_grade || '',
                notes: item.notes || '',
            })) || [],
        });
        setShowModal(true);
    };

    const addItem = () => {
        setFormData({
            ...formData,
            items: [
                ...formData.items,
                {
                    product_id: '',
                    production_cycle_id: '',
                    harvest_record_id: '',
                    harvest_lot_id: '',
                    product_name: '',
                    quantity: '',
                    unit: 'KG',
                    unit_price: '',
                    discount_amount: 0,
                    quality_grade: '',
                    notes: '',
                },
            ],
        });
    };

    const removeItem = (index) => {
        setFormData({
            ...formData,
            items: formData.items.filter((_, i) => i !== index),
        });
    };

    const updateItem = (index, field, value) => {
        const newItems = [...formData.items];
        newItems[index] = { ...newItems[index], [field]: value };
        
        // Auto-calculate line_total
        if (field === 'quantity' || field === 'unit_price' || field === 'discount_amount') {
            const qty = parseFloat(newItems[index].quantity) || 0;
            const price = parseFloat(newItems[index].unit_price) || 0;
            const discount = parseFloat(newItems[index].discount_amount) || 0;
            newItems[index].line_total = (qty * price) - discount;
        }
        
        setFormData({ ...formData, items: newItems });
    };

    const handleSubmit = async (e) => {
        e.preventDefault();
        if (formData.items.length === 0) {
            alert('Please add at least one item');
            return;
        }
        try {
            const payload = {
                ...formData,
                items: formData.items.map(item => ({
                    product_id: item.product_id || null,
                    production_cycle_id: item.production_cycle_id || null,
                    harvest_record_id: item.harvest_record_id || null,
                    harvest_lot_id: item.harvest_lot_id || null,
                    product_name: item.product_name || null,
                    quantity: item.quantity,
                    unit: item.unit,
                    unit_price: item.unit_price,
                    discount_amount: item.discount_amount || 0,
                    quality_grade: item.quality_grade || null,
                    notes: item.notes || null,
                })),
            };

            if (editingOrder) {
                await api.patch(`/api/v1/sales-orders/${editingOrder.id}`, payload);
            } else {
                await api.post('/api/v1/sales-orders', payload);
            }
            setShowModal(false);
            fetchData();
        } catch (error) {
            const errorMsg = error.response?.data?.message || error.response?.data?.errors || 'Unknown error';
            alert('Error saving order: ' + (typeof errorMsg === 'string' ? errorMsg : JSON.stringify(errorMsg)));
        }
    };

    const handleAction = async (orderId, action) => {
        try {
            await api.post(`/api/v1/sales-orders/${orderId}/${action}`);
            fetchData();
        } catch (error) {
            alert('Error: ' + (error.response?.data?.message || error.message));
        }
    };

    const handleDelete = async (orderId) => {
        if (!confirm('Delete this sales order?')) return;
        try {
            await api.delete(`/api/v1/sales-orders/${orderId}`);
            fetchData();
        } catch (error) {
            alert('Error deleting order: ' + (error.response?.data?.message || error.message));
        }
    };

    const getStatusColor = (status) => {
        const colors = {
            DRAFT: 'bg-gray-100 text-gray-800',
            CONFIRMED: 'bg-blue-100 text-blue-800',
            DISPATCHED: 'bg-green-100 text-green-800',
            INVOICED: 'bg-purple-100 text-purple-800',
            PAID: 'bg-green-100 text-green-800',
            CANCELLED: 'bg-red-100 text-red-800',
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
                    <h1 className="text-3xl font-bold text-gray-900">Sales Orders</h1>
                    <p className="mt-2 text-gray-600">Manage sales orders and track revenue</p>
                </div>
                <button
                    onClick={handleModalOpen}
                    className="flex items-center px-4 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700"
                >
                    <Plus className="h-5 w-5 mr-2" />
                    New Order
                </button>
            </div>

            {/* Orders Table */}
            <div className="bg-white rounded-lg shadow overflow-hidden">
                <table className="min-w-full divide-y divide-gray-200">
                    <thead className="bg-gray-50">
                        <tr>
                            <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Order #</th>
                            <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Customer</th>
                            <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Date</th>
                            <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                            <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Total</th>
                            <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Payment</th>
                            <th className="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                        </tr>
                    </thead>
                    <tbody className="bg-white divide-y divide-gray-200">
                        {orders.length === 0 ? (
                            <tr>
                                <td colSpan="7" className="px-6 py-4 text-center text-gray-500">
                                    No sales orders found
                                </td>
                            </tr>
                        ) : (
                            orders.map((order) => (
                                <tr key={order.id}>
                                    <td className="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
                                        {order.order_number}
                                    </td>
                                    <td className="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                        {order.customer?.name || 'N/A'}
                                    </td>
                                    <td className="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                        {order.order_date || 'N/A'}
                                    </td>
                                    <td className="px-6 py-4 whitespace-nowrap">
                                        <span className={`px-2 py-1 text-xs font-semibold rounded-full ${getStatusColor(order.status)}`}>
                                            {order.status}
                                        </span>
                                    </td>
                                    <td className="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                        {order.currency} {Number(order.total_amount || 0).toFixed(2)}
                                    </td>
                                    <td className="px-6 py-4 whitespace-nowrap">
                                        <span className={`px-2 py-1 text-xs font-semibold rounded-full ${
                                            order.payment_status === 'PAID' 
                                                ? 'bg-green-100 text-green-800'
                                                : order.payment_status === 'PART_PAID'
                                                ? 'bg-yellow-100 text-yellow-800'
                                                : 'bg-red-100 text-red-800'
                                        }`}>
                                            {order.payment_status}
                                        </span>
                                    </td>
                                    <td className="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                        <div className="flex justify-end space-x-2">
                                            {order.status === 'DRAFT' && (
                                                <button
                                                    onClick={() => handleAction(order.id, 'confirm')}
                                                    className="text-blue-600 hover:text-blue-900"
                                                    title="Confirm"
                                                >
                                                    <CheckCircle className="h-4 w-4" />
                                                </button>
                                            )}
                                            {['CONFIRMED', 'INVOICED'].includes(order.status) && (
                                                <button
                                                    onClick={() => handleAction(order.id, 'dispatch')}
                                                    className="text-green-600 hover:text-green-900"
                                                    title="Dispatch"
                                                >
                                                    <Truck className="h-4 w-4" />
                                                </button>
                                            )}
                                            {['CONFIRMED', 'DISPATCHED'].includes(order.status) && (
                                                <button
                                                    onClick={() => handleAction(order.id, 'invoice')}
                                                    className="text-purple-600 hover:text-purple-900"
                                                    title="Invoice"
                                                >
                                                    <FileText className="h-4 w-4" />
                                                </button>
                                            )}
                                            {!['PAID', 'CANCELLED'].includes(order.status) && (
                                                <button
                                                    onClick={() => handleAction(order.id, 'cancel')}
                                                    className="text-red-600 hover:text-red-900"
                                                    title="Cancel"
                                                >
                                                    <X className="h-4 w-4" />
                                                </button>
                                            )}
                                            <button
                                                onClick={() => handleEdit(order)}
                                                className="text-blue-600 hover:text-blue-900"
                                                title="Edit"
                                            >
                                                <Edit className="h-4 w-4" />
                                            </button>
                                            {order.status === 'DRAFT' && (
                                                <button
                                                    onClick={() => handleDelete(order.id)}
                                                    className="text-red-600 hover:text-red-900"
                                                    title="Delete"
                                                >
                                                    <Trash2 className="h-4 w-4" />
                                                </button>
                                            )}
                                            {order.payment_status !== 'PAID' && (
                                                <button
                                                    onClick={() => handlePaymentModalOpen(order)}
                                                    className="text-green-600 hover:text-green-900"
                                                    title="Record Payment"
                                                >
                                                    <DollarSign className="h-4 w-4" />
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

            {/* Payment Modal */}
            {showPaymentModal && selectedOrder && (
                <div className="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50 p-4">
                    <div className="bg-white rounded-lg max-w-md w-full">
                        <div className="sticky top-0 bg-white border-b border-gray-200 px-6 py-4 flex justify-between items-center">
                            <h2 className="text-xl font-bold text-gray-900">Record Payment</h2>
                            <button
                                onClick={() => setShowPaymentModal(false)}
                                className="text-gray-400 hover:text-gray-600"
                            >
                                <X className="h-6 w-6" />
                            </button>
                        </div>
                        <form onSubmit={handlePaymentSubmit} className="p-6 space-y-4">
                            <div>
                                <label className="block text-sm font-medium text-gray-700 mb-1">
                                    Payment Date <span className="text-red-500">*</span>
                                </label>
                                <input
                                    type="date"
                                    required
                                    value={paymentData.payment_date}
                                    onChange={(e) => setPaymentData({ ...paymentData, payment_date: e.target.value })}
                                    className="w-full px-3 py-2 border border-gray-300 rounded-lg"
                                />
                            </div>
                            <div>
                                <label className="block text-sm font-medium text-gray-700 mb-1">
                                    Amount <span className="text-red-500">*</span>
                                </label>
                                <input
                                    type="number"
                                    step="0.01"
                                    required
                                    value={paymentData.amount}
                                    onChange={(e) => setPaymentData({ ...paymentData, amount: e.target.value })}
                                    className="w-full px-3 py-2 border border-gray-300 rounded-lg"
                                />
                            </div>
                            <div>
                                <label className="block text-sm font-medium text-gray-700 mb-1">
                                    Payment Method <span className="text-red-500">*</span>
                                </label>
                                <select
                                    required
                                    value={paymentData.method}
                                    onChange={(e) => setPaymentData({ ...paymentData, method: e.target.value })}
                                    className="w-full px-3 py-2 border border-gray-300 rounded-lg"
                                >
                                    <option value="CASH">Cash</option>
                                    <option value="TRANSFER">Transfer</option>
                                    <option value="POS">POS</option>
                                    <option value="ONLINE">Online</option>
                                </select>
                            </div>
                            <div>
                                <label className="block text-sm font-medium text-gray-700 mb-1">
                                    Reference
                                </label>
                                <input
                                    type="text"
                                    value={paymentData.reference}
                                    onChange={(e) => setPaymentData({ ...paymentData, reference: e.target.value })}
                                    className="w-full px-3 py-2 border border-gray-300 rounded-lg"
                                />
                            </div>
                            <div>
                                <label className="block text-sm font-medium text-gray-700 mb-1">
                                    Notes
                                </label>
                                <textarea
                                    value={paymentData.notes}
                                    onChange={(e) => setPaymentData({ ...paymentData, notes: e.target.value })}
                                    rows="3"
                                    className="w-full px-3 py-2 border border-gray-300 rounded-lg"
                                />
                            </div>
                            <div className="flex justify-end space-x-4 pt-4 border-t">
                                <button
                                    type="button"
                                    onClick={() => setShowPaymentModal(false)}
                                    className="px-4 py-2 border border-gray-300 rounded-lg text-gray-700 hover:bg-gray-50"
                                >
                                    Cancel
                                </button>
                                <button
                                    type="submit"
                                    className="px-4 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700"
                                >
                                    Record Payment
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            )}

            {/* Modal */}
            {showModal && (
                <div className="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50 p-4">
                    <div className="bg-white rounded-lg max-w-4xl w-full max-h-[90vh] overflow-y-auto">
                        <div className="sticky top-0 bg-white border-b border-gray-200 px-6 py-4 flex justify-between items-center">
                            <h2 className="text-xl font-bold text-gray-900">
                                {editingOrder ? 'Edit Sales Order' : 'New Sales Order'}
                            </h2>
                            <button
                                onClick={() => setShowModal(false)}
                                className="text-gray-400 hover:text-gray-600"
                            >
                                <X className="h-6 w-6" />
                            </button>
                        </div>
                        <form onSubmit={handleSubmit} className="p-6 space-y-6">
                            {!editingOrder && (
                                <div className="grid grid-cols-2 gap-4">
                                    <div>
                                        <label className="block text-sm font-medium text-gray-700 mb-1">
                                            Farm <span className="text-red-500">*</span>
                                        </label>
                                        <select
                                            required
                                            value={formData.farm_id}
                                            onChange={(e) => {
                                                const farm = farms.find(f => f.id === parseInt(e.target.value));
                                                setFormData({ 
                                                    ...formData, 
                                                    farm_id: e.target.value,
                                                    currency: farm?.default_currency || 'USD',
                                                    site_id: '',
                                                });
                                            }}
                                            className="w-full px-3 py-2 border border-gray-300 rounded-lg"
                                        >
                                            <option value="">Select Farm</option>
                                            {farms.map((farm) => (
                                                <option key={farm.id} value={farm.id}>
                                                    {farm.name}
                                                </option>
                                            ))}
                                        </select>
                                    </div>
                                    <div>
                                        <label className="block text-sm font-medium text-gray-700 mb-1">
                                            Site
                                        </label>
                                        <select
                                            value={formData.site_id}
                                            onChange={(e) => setFormData({ ...formData, site_id: e.target.value })}
                                            className="w-full px-3 py-2 border border-gray-300 rounded-lg"
                                        >
                                            <option value="">Select Site</option>
                                            {sites.filter(s => !formData.farm_id || s.farm_id === parseInt(formData.farm_id)).map((site) => (
                                                <option key={site.id} value={site.id}>
                                                    {site.name}
                                                </option>
                                            ))}
                                        </select>
                                    </div>
                                </div>
                            )}
                            <div className="grid grid-cols-2 gap-4">
                                <div>
                                    <label className="block text-sm font-medium text-gray-700 mb-1">
                                        Customer <span className="text-red-500">*</span>
                                    </label>
                                    <select
                                        required
                                        value={formData.customer_id}
                                        onChange={(e) => setFormData({ ...formData, customer_id: e.target.value })}
                                        className="w-full px-3 py-2 border border-gray-300 rounded-lg"
                                    >
                                        <option value="">Select Customer</option>
                                        {customers.map((customer) => (
                                            <option key={customer.id} value={customer.id}>
                                                {customer.name}
                                            </option>
                                        ))}
                                    </select>
                                </div>
                                <div>
                                    <label className="block text-sm font-medium text-gray-700 mb-1">
                                        Order Date <span className="text-red-500">*</span>
                                    </label>
                                    <input
                                        type="date"
                                        required
                                        value={formData.order_date}
                                        onChange={(e) => setFormData({ ...formData, order_date: e.target.value })}
                                        className="w-full px-3 py-2 border border-gray-300 rounded-lg"
                                    />
                                </div>
                                <div>
                                    <label className="block text-sm font-medium text-gray-700 mb-1">
                                        Currency
                                    </label>
                                    <input
                                        type="text"
                                        value={formData.currency}
                                        onChange={(e) => setFormData({ ...formData, currency: e.target.value.toUpperCase() })}
                                        className="w-full px-3 py-2 border border-gray-300 rounded-lg"
                                        maxLength="3"
                                    />
                                </div>
                                <div>
                                    <label className="block text-sm font-medium text-gray-700 mb-1">
                                        Due Date
                                    </label>
                                    <input
                                        type="date"
                                        value={formData.due_date}
                                        onChange={(e) => setFormData({ ...formData, due_date: e.target.value })}
                                        className="w-full px-3 py-2 border border-gray-300 rounded-lg"
                                    />
                                </div>
                            </div>

                            {/* Order Items */}
                            <div>
                                <div className="flex justify-between items-center mb-4">
                                    <h3 className="text-lg font-semibold text-gray-900">Order Items</h3>
                                    <button
                                        type="button"
                                        onClick={addItem}
                                        className="flex items-center px-3 py-1 bg-green-600 text-white rounded-lg hover:bg-green-700 text-sm"
                                    >
                                        <Plus className="h-4 w-4 mr-1" />
                                        Add Item
                                    </button>
                                </div>

                                {formData.items.map((item, itemIndex) => (
                                    <div key={itemIndex} className="border border-gray-200 rounded-lg p-4 mb-4">
                                        <div className="flex justify-between items-center mb-4">
                                            <h4 className="font-medium text-gray-900">Item {itemIndex + 1}</h4>
                                            <button
                                                type="button"
                                                onClick={() => removeItem(itemIndex)}
                                                className="text-red-600 hover:text-red-900"
                                            >
                                                <Trash2 className="h-4 w-4" />
                                            </button>
                                        </div>

                                        <div className="grid grid-cols-2 gap-4">
                                            <div>
                                                <label className="block text-sm font-medium text-gray-700 mb-1">
                                                    Product
                                                </label>
                                                <select
                                                    value={item.product_id}
                                                    onChange={(e) => {
                                                        const product = products.find(p => p.id === parseInt(e.target.value));
                                                        updateItem(itemIndex, 'product_id', e.target.value);
                                                        if (product) {
                                                            updateItem(itemIndex, 'product_name', product.name);
                                                        }
                                                    }}
                                                    className="w-full px-3 py-2 border border-gray-300 rounded-lg"
                                                >
                                                    <option value="">Select Product</option>
                                                    {products.map((product) => (
                                                        <option key={product.id} value={product.id}>
                                                            {product.name} ({product.code})
                                                        </option>
                                                    ))}
                                                </select>
                                            </div>
                                            <div>
                                                <label className="block text-sm font-medium text-gray-700 mb-1">
                                                    Product Name (if no product selected)
                                                </label>
                                                <input
                                                    type="text"
                                                    value={item.product_name}
                                                    onChange={(e) => updateItem(itemIndex, 'product_name', e.target.value)}
                                                    className="w-full px-3 py-2 border border-gray-300 rounded-lg"
                                                />
                                            </div>
                                            <div>
                                                <label className="block text-sm font-medium text-gray-700 mb-1">
                                                    Production Cycle
                                                </label>
                                                <select
                                                    value={item.production_cycle_id}
                                                    onChange={(e) => updateItem(itemIndex, 'production_cycle_id', e.target.value)}
                                                    className="w-full px-3 py-2 border border-gray-300 rounded-lg"
                                                >
                                                    <option value="">Select Cycle</option>
                                                    {cycles.map((cycle) => (
                                                        <option key={cycle.id} value={cycle.id}>
                                                            {cycle.production_cycle_code}
                                                        </option>
                                                    ))}
                                                </select>
                                            </div>
                                            <div>
                                                <label className="block text-sm font-medium text-gray-700 mb-1">
                                                    Harvest Record
                                                </label>
                                                <select
                                                    value={item.harvest_record_id}
                                                    onChange={(e) => updateItem(itemIndex, 'harvest_record_id', e.target.value)}
                                                    className="w-full px-3 py-2 border border-gray-300 rounded-lg"
                                                >
                                                    <option value="">Select Harvest</option>
                                                    {harvests.map((harvest) => (
                                                        <option key={harvest.id} value={harvest.id}>
                                                            {harvest.harvest_code} - {harvest.harvest_date}
                                                        </option>
                                                    ))}
                                                </select>
                                            </div>
                                            <div>
                                                <label className="block text-sm font-medium text-gray-700 mb-1">
                                                    Harvest Lot
                                                </label>
                                                <select
                                                    value={item.harvest_lot_id}
                                                    onChange={(e) => updateItem(itemIndex, 'harvest_lot_id', e.target.value)}
                                                    className="w-full px-3 py-2 border border-gray-300 rounded-lg"
                                                >
                                                    <option value="">Select Lot</option>
                                                    {harvestLots.map((lot) => (
                                                        <option key={lot.id} value={lot.id}>
                                                            {lot.code}
                                                        </option>
                                                    ))}
                                                </select>
                                            </div>
                                            <div>
                                                <label className="block text-sm font-medium text-gray-700 mb-1">
                                                    Quantity <span className="text-red-500">*</span>
                                                </label>
                                                <input
                                                    type="number"
                                                    step="0.01"
                                                    required
                                                    value={item.quantity}
                                                    onChange={(e) => updateItem(itemIndex, 'quantity', e.target.value)}
                                                    className="w-full px-3 py-2 border border-gray-300 rounded-lg"
                                                />
                                            </div>
                                            <div>
                                                <label className="block text-sm font-medium text-gray-700 mb-1">
                                                    Unit <span className="text-red-500">*</span>
                                                </label>
                                                <input
                                                    type="text"
                                                    required
                                                    value={item.unit}
                                                    onChange={(e) => updateItem(itemIndex, 'unit', e.target.value)}
                                                    className="w-full px-3 py-2 border border-gray-300 rounded-lg"
                                                />
                                            </div>
                                            <div>
                                                <label className="block text-sm font-medium text-gray-700 mb-1">
                                                    Unit Price <span className="text-red-500">*</span>
                                                </label>
                                                <input
                                                    type="number"
                                                    step="0.01"
                                                    required
                                                    value={item.unit_price}
                                                    onChange={(e) => updateItem(itemIndex, 'unit_price', e.target.value)}
                                                    className="w-full px-3 py-2 border border-gray-300 rounded-lg"
                                                />
                                            </div>
                                            <div>
                                                <label className="block text-sm font-medium text-gray-700 mb-1">
                                                    Discount
                                                </label>
                                                <input
                                                    type="number"
                                                    step="0.01"
                                                    value={item.discount_amount || 0}
                                                    onChange={(e) => updateItem(itemIndex, 'discount_amount', e.target.value)}
                                                    className="w-full px-3 py-2 border border-gray-300 rounded-lg"
                                                />
                                            </div>
                                            <div>
                                                <label className="block text-sm font-medium text-gray-700 mb-1">
                                                    Quality Grade
                                                </label>
                                                <input
                                                    type="text"
                                                    value={item.quality_grade}
                                                    onChange={(e) => updateItem(itemIndex, 'quality_grade', e.target.value)}
                                                    className="w-full px-3 py-2 border border-gray-300 rounded-lg"
                                                />
                                            </div>
                                            <div className="col-span-2">
                                                <label className="block text-sm font-medium text-gray-700 mb-1">
                                                    Notes
                                                </label>
                                                <textarea
                                                    value={item.notes}
                                                    onChange={(e) => updateItem(itemIndex, 'notes', e.target.value)}
                                                    rows="2"
                                                    className="w-full px-3 py-2 border border-gray-300 rounded-lg"
                                                />
                                            </div>
                                        </div>
                                    </div>
                                ))}
                            </div>

                            <div className="grid grid-cols-2 gap-4">
                                <div>
                                    <label className="block text-sm font-medium text-gray-700 mb-1">
                                        Discount Total
                                    </label>
                                    <input
                                        type="number"
                                        step="0.01"
                                        value={formData.discount_total}
                                        onChange={(e) => setFormData({ ...formData, discount_total: e.target.value })}
                                        className="w-full px-3 py-2 border border-gray-300 rounded-lg"
                                    />
                                </div>
                                <div>
                                    <label className="block text-sm font-medium text-gray-700 mb-1">
                                        Tax Total
                                    </label>
                                    <input
                                        type="number"
                                        step="0.01"
                                        value={formData.tax_total}
                                        onChange={(e) => setFormData({ ...formData, tax_total: e.target.value })}
                                        className="w-full px-3 py-2 border border-gray-300 rounded-lg"
                                    />
                                </div>
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
                                    {editingOrder ? 'Update' : 'Create'} Order
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            )}
        </div>
    );
}
