import React, { useEffect, useState } from 'react';
import api from '../utils/api';
import { ShoppingCart, Plus, TrendingUp, DollarSign } from 'lucide-react';

export default function GariSales() {
    const [sales, setSales] = useState([]);
    const [summary, setSummary] = useState([]);
    const [farms, setFarms] = useState([]);
    const [loading, setLoading] = useState(true);
    const [showModal, setShowModal] = useState(false);
    const [formData, setFormData] = useState({
        farm_id: '',
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
    };

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

    if (loading) {
        return (
            <div className="flex items-center justify-center h-64">
                <div className="animate-spin rounded-full h-12 w-12 border-b-2 border-green-600"></div>
            </div>
        );
    }

    const totalRevenue = sales.reduce((sum, sale) => sum + (sale.final_amount || 0), 0);
    const totalMargin = sales.reduce((sum, sale) => sum + (sale.gross_margin || 0), 0);

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
                    <p className="text-2xl font-bold text-gray-900">₦{totalRevenue.toFixed(2)}</p>
                </div>
                <div className="bg-white rounded-lg shadow p-6">
                    <p className="text-sm text-gray-600 mb-1">Total Margin</p>
                    <p className="text-2xl font-bold text-green-600">₦{totalMargin.toFixed(2)}</p>
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
                                            ₦{sale.final_amount.toFixed(2)}
                                        </td>
                                        <td className="px-6 py-4 whitespace-nowrap text-sm text-green-600">
                                            ₦{sale.gross_margin?.toFixed(2) || '0.00'}
                                            <span className="text-xs text-gray-500 ml-1">
                                                ({sale.gross_margin_percent?.toFixed(1) || '0'}%)
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
                                <div>
                                    <label className="block text-sm font-medium text-gray-700 mb-1">Gari Type *</label>
                                    <select
                                        required
                                        value={formData.gari_type}
                                        onChange={(e) => setFormData({ ...formData, gari_type: e.target.value })}
                                        className="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-green-500 focus:border-green-500"
                                    >
                                        <option value="WHITE">White</option>
                                        <option value="YELLOW">Yellow</option>
                                    </select>
                                </div>
                                <div>
                                    <label className="block text-sm font-medium text-gray-700 mb-1">Packaging *</label>
                                    <select
                                        required
                                        value={formData.packaging_type}
                                        onChange={(e) => setFormData({ ...formData, packaging_type: e.target.value })}
                                        className="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-green-500 focus:border-green-500"
                                    >
                                        <option value="1KG_POUCH">1kg Pouch</option>
                                        <option value="2KG_POUCH">2kg Pouch</option>
                                        <option value="5KG_PACK">5kg Pack</option>
                                        <option value="50KG_BAG">50kg Bag</option>
                                        <option value="BULK">Bulk</option>
                                    </select>
                                </div>
                                <div>
                                    <label className="block text-sm font-medium text-gray-700 mb-1">Quantity (kg) *</label>
                                    <input
                                        type="number"
                                        step="0.01"
                                        required
                                        value={formData.quantity_kg}
                                        onChange={(e) => setFormData({ ...formData, quantity_kg: e.target.value })}
                                        className="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-green-500 focus:border-green-500"
                                    />
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
        </div>
    );
}

