# Payment CRUD API Documentation - Frontend Integration Guide

## 📋 Overview

Complete guide for frontend developers to implement payment processing, tracking, and management functionality using the efiche billing API.

## 🔗 Base API Configuration

```
Base URL: http://127.0.0.1:8000/api
Authentication: Bearer {token}
Content-Type: application/json
```

## 💳 Payment CRUD Endpoints

### 1. Get All Payments
```http
GET /api/payments
Authorization: Bearer {token}
```

**Response:**
```json
{
  "success": true,
  "data": [
    {
      "id": 123,
      "invoice_id": 456,
      "amount": 10000.50,
      "method": "cash",
      "status": "confirmed",
      "transaction_ref": "TXN-2024-001",
      "cashier_id": 789,
      "processed_at": "2024-04-26T11:00:00.000000Z",
      "confirmed_at": "2024-04-26T11:05:00.000000Z",
      "notes": "Payment for consultation fee",
      "invoice": {
        "id": 456,
        "invoice_number": "INV-2024-001",
        "visit": {
          "patient": {
            "full_name": "John Doe",
            "phone": "+250788123456"
          }
        }
      }
    }
  ]
}
```

### 2. Get Specific Payment
```http
GET /api/payments/{id}
Authorization: Bearer {token}
```

**Response:**
```json
{
  "success": true,
  "data": {
    "id": 123,
    "invoice_id": 456,
    "amount": 10000.50,
    "method": "cash",
    "status": "confirmed",
    "transaction_ref": "TXN-2024-001",
    "cashier_id": 789,
    "processed_at": "2024-04-26T11:00:00.000000Z",
    "confirmed_at": "2024-04-26T11:05:00.000000Z",
    "notes": "Payment for consultation fee"
  }
}
```

### 3. Get Payment Status
```http
GET /api/payments/{paymentId}/status
Authorization: Bearer {token}
```

**Response:**
```json
{
  "id": 123,
  "status": "confirmed",
  "confirmed_at": "2024-04-26T11:05:00.000000Z",
  "transaction_ref": "TXN-2024-001"
}
```

### 4. Process Payment for Invoice
```http
POST /api/invoices/{invoiceId}/payments
Authorization: Bearer {token}
Content-Type: application/json
```

**Request Body:**
```json
{
  "amount": "10000.50",
  "method": "cash",
  "phone_number": "+250788123456",
  "notes": "Payment for consultation fee"
}
```

**Response:**
```json
{
  "success": true,
  "data": {
    "id": 124,
    "invoice_id": 456,
    "amount": 10000.50,
    "method": "cash",
    "status": "confirmed",
    "transaction_ref": "TXN-2024-001",
    "cashier_id": 789,
    "processed_at": "2024-04-26T11:00:00.000000Z",
    "confirmed_at": "2024-04-26T11:05:00.000000Z",
    "notes": "Payment for consultation fee"
  }
}
```

## 📋 Payment Methods & Status Values

### Payment Methods
| Method | Description | Required Fields |
|--------|-------------|------------------|
| `cash` | Physical cash payment | amount |
| `mobile_money` | Mobile money transfer | amount, phone_number |
| `insurance` | Direct insurance payment | amount |

### Payment Status Values
| Status | Description | Display Color |
|---------|-------------|--------------|
| `pending` | Payment initiated but not confirmed | 🔴 Red |
| `confirmed` | Payment successfully processed | 🟢 Green |
| `failed` | Payment processing failed | 🔴 Red |
| `cancelled` | Payment was cancelled | 🟡 Yellow |

## 🎨 Frontend Implementation Examples

### React Component - Payment List
```jsx
const PaymentList = () => {
  const [payments, setPayments] = useState([]);
  const [loading, setLoading] = useState(true);
  const [filter, setFilter] = useState('all');

  useEffect(() => {
    fetchPayments();
  }, []);

  const fetchPayments = async () => {
    try {
      const endpoint = filter === 'all' 
        ? '/api/payments' 
        : `/api/payments?status=${filter}`;
      
      const response = await apiRequest(endpoint, 'GET');
      setPayments(response.data);
    } catch (error) {
      console.error('Failed to fetch payments:', error);
    } finally {
      setLoading(false);
    }
  };

  const getStatusColor = (status) => {
    switch (status) {
      case 'confirmed': return 'text-green-600';
      case 'pending': return 'text-yellow-600';
      case 'failed': return 'text-red-600';
      case 'cancelled': return 'text-gray-600';
      default: return 'text-gray-600';
    }
  };

  const getStatusBadge = (status) => {
    const badges = {
      confirmed: { bg: 'bg-green-100', text: 'text-green-800' },
      pending: { bg: 'bg-yellow-100', text: 'text-yellow-800' },
      failed: { bg: 'bg-red-100', text: 'text-red-800' },
      cancelled: { bg: 'bg-gray-100', text: 'text-gray-800' }
    };
    const badge = badges[status] || { bg: 'bg-gray-100', text: 'text-gray-800' };
    return `inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium ${badge.bg} ${badge.text}`;
  };

  if (loading) {
    return <div>Loading payments...</div>;
  }

  return (
    <div className="space-y-4">
      {/* Filter Controls */}
      <div className="flex space-x-2 mb-4">
        <button
          onClick={() => setFilter('all')}
          className={`px-3 py-1 rounded ${filter === 'all' ? 'bg-blue-600 text-white' : 'bg-gray-200 text-gray-700'}`}
        >
          All
        </button>
        <button
          onClick={() => setFilter('confirmed')}
          className={`px-3 py-1 rounded ${filter === 'confirmed' ? 'bg-green-600 text-white' : 'bg-gray-200 text-gray-700'}`}
        >
          Confirmed
        </button>
        <button
          onClick={() => setFilter('pending')}
          className={`px-3 py-1 rounded ${filter === 'pending' ? 'bg-yellow-600 text-white' : 'bg-gray-200 text-gray-700'}`}
        >
          Pending
        </button>
        <button
          onClick={() => setFilter('failed')}
          className={`px-3 py-1 rounded ${filter === 'failed' ? 'bg-red-600 text-white' : 'bg-gray-200 text-gray-700'}`}
        >
          Failed
        </button>
      </div>

      {/* Payment List */}
      {payments.map((payment) => (
        <div key={payment.id} className="bg-white rounded-lg shadow-md p-4">
          <div className="flex justify-between items-start mb-3">
            <div>
              <h4 className="font-semibold text-lg">
                {payment.transaction_ref || `PAY-${payment.id}`}
              </h4>
              <p className="text-sm text-gray-600">
                {payment.invoice?.invoice_number} - {payment.invoice?.visit?.patient?.full_name}
              </p>
            </div>
            <div className="text-right">
              <span className={`inline-flex items-center px-2 py-1 rounded-full text-xs font-medium ${getStatusBadge(payment.status)}`}>
                {payment.status.toUpperCase()}
              </span>
            </div>
          </div>

          <div className="grid grid-cols-2 gap-4">
            <div>
              <p className="text-sm text-gray-600">Amount</p>
              <p className="font-semibold text-lg">
                {paymentUtils.formatCurrency(payment.amount)}
              </p>
            </div>
            <div>
              <p className="text-sm text-gray-600">Method</p>
              <p className="font-semibold capitalize">
                {payment.method.replace('_', ' ')}
              </p>
            </div>
          </div>

          <div className="mt-3">
            <p className="text-sm text-gray-600">Processed</p>
            <p className="text-sm">
              {new Date(payment.processed_at).toLocaleString()}
            </p>
          </div>

          {payment.notes && (
            <div className="mt-3">
              <p className="text-sm text-gray-600">Notes</p>
              <p className="text-sm">{payment.notes}</p>
            </div>
          )}

          <div className="mt-4 flex justify-between">
            <button className="bg-blue-600 text-white px-4 py-2 rounded">
              View Invoice
            </button>
            <button className="bg-gray-600 text-white px-4 py-2 rounded">
              Receipt
            </button>
          </div>
        </div>
      ))}
    </div>
  );
};
```

### React Component - Payment Form
```jsx
const PaymentForm = ({ invoiceId, onPaymentSuccess }) => {
  const [formData, setFormData] = useState({
    amount: '',
    method: 'cash',
    phone_number: '',
    notes: ''
  });
  const [loading, setLoading] = useState(false);
  const [errors, setErrors] = useState({});

  const handleSubmit = async (e) => {
    e.preventDefault();
    setLoading(true);
    setErrors({});

    try {
      const response = await apiRequest(`/api/invoices/${invoiceId}/payments`, 'POST', formData);
      
      if (response.success) {
        alert('Payment processed successfully!');
        onPaymentSuccess(response.data);
        setFormData({ amount: '', method: 'cash', phone_number: '', notes: '' });
      }
    } catch (error) {
      if (error.errors) {
        setErrors(error.errors);
      } else {
        alert('Payment failed: ' + error.message);
      }
    } finally {
      setLoading(false);
    }
  };

  const validateForm = () => {
    const newErrors = {};
    
    if (!formData.amount || parseFloat(formData.amount) <= 0) {
      newErrors.amount = 'Amount must be greater than 0';
    }
    
    if (formData.method === 'mobile_money' && !formData.phone_number) {
      newErrors.phone_number = 'Phone number is required for mobile money payments';
    }
    
    if (formData.method === 'mobile_money' && formData.phone_number && !/^\+2507\d{8}$/.test(formData.phone_number)) {
      newErrors.phone_number = 'Phone number must be a valid Rwanda number (e.g., +250788123456)';
    }
    
    return newErrors;
  };

  if (!invoiceId) return null;

  return (
    <div className="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50">
      <div className="bg-white rounded-lg p-6 w-full max-w-md">
        <div className="flex justify-between items-center mb-4">
          <h2 className="text-xl font-semibold">Process Payment</h2>
          <button onClick={onClose} className="text-gray-500 hover:text-gray-700">
            ×
          </button>
        </div>

        <form onSubmit={handleSubmit} className="space-y-4">
          <div>
            <label className="block text-sm font-medium text-gray-700 mb-2">
              Amount (RWF)
            </label>
            <input
              type="number"
              step="0.01"
              value={formData.amount}
              onChange={(e) => setFormData({...formData, amount: e.target.value})}
              className={`w-full border border-gray-300 rounded-md shadow-sm p-2 ${errors.amount ? 'border-red-500' : ''}`}
              required
            />
            {errors.amount && (
              <p className="text-red-500 text-sm mt-1">{errors.amount}</p>
            )}
          </div>

          <div>
            <label className="block text-sm font-medium text-gray-700 mb-2">
              Payment Method
            </label>
            <select
              value={formData.method}
              onChange={(e) => setFormData({...formData, method: e.target.value})}
              className="w-full border border-gray-300 rounded-md shadow-sm p-2"
            >
              <option value="cash">Cash</option>
              <option value="mobile_money">Mobile Money</option>
              <option value="insurance">Insurance</option>
            </select>
          </div>

          {formData.method === 'mobile_money' && (
            <div>
              <label className="block text-sm font-medium text-gray-700 mb-2">
                Phone Number
              </label>
              <input
                type="tel"
                placeholder="+250788123456"
                value={formData.phone_number}
                onChange={(e) => setFormData({...formData, phone_number: e.target.value})}
                className={`w-full border border-gray-300 rounded-md shadow-sm p-2 ${errors.phone_number ? 'border-red-500' : ''}`}
              />
              {errors.phone_number && (
                <p className="text-red-500 text-sm mt-1">{errors.phone_number}</p>
              )}
            </div>
          )}

          <div>
            <label className="block text-sm font-medium text-gray-700 mb-2">
              Notes (Optional)
            </label>
            <textarea
              rows="3"
              value={formData.notes}
              onChange={(e) => setFormData({...formData, notes: e.target.value})}
              className="w-full border border-gray-300 rounded-md shadow-sm p-2"
              placeholder="Add any notes about this payment..."
            />
          </div>

          <div className="flex justify-end space-x-2">
            <button
              type="button"
              onClick={onClose}
              className="bg-gray-500 text-white px-4 py-2 rounded"
            >
              Cancel
            </button>
            <button
              type="submit"
              disabled={loading}
              className="bg-blue-600 text-white px-4 py-2 rounded disabled:opacity-50"
            >
              {loading ? 'Processing...' : 'Process Payment'}
            </button>
          </div>
        </form>
      </div>
    </div>
  );
};
```

### JavaScript Service Functions
```javascript
// API Service for Payment Operations
const paymentApi = {
  // Get all payments
  getAll: async (status = null) => {
    const endpoint = status ? `/api/payments?status=${status}` : '/api/payments';
    const response = await apiRequest(endpoint, 'GET');
    return response.data;
  },

  // Get specific payment
  getById: async (id) => {
    const response = await apiRequest(`/api/payments/${id}`, 'GET');
    return response.data;
  },

  // Get payment status
  getStatus: async (id) => {
    const response = await apiRequest(`/api/payments/${id}/status`, 'GET');
    return response.data;
  },

  // Process payment for invoice
  process: async (invoiceId, paymentData) => {
    const response = await apiRequest(`/api/invoices/${invoiceId}/payments`, 'POST', paymentData);
    return response.data;
  },

  // Get payments for invoice
  getByInvoiceId: async (invoiceId) => {
    const response = await apiRequest(`/api/payments?invoice_id=${invoiceId}`, 'GET');
    return response.data;
  }
};

// Utility functions
const paymentUtils = {
  formatCurrency: (amount) => {
    return new Intl.NumberFormat('rw-RW', {
      style: 'currency',
      currency: 'RWF'
    }).format(amount);
  },

  getMethodIcon: (method) => {
    const icons = {
      cash: '💵',
      mobile_money: '📱',
      insurance: '🏥'
    };
    return icons[method] || '💳';
  },

  getStatusColor: (status) => {
    const colors = {
      confirmed: 'text-green-600',
      pending: 'text-yellow-600',
      failed: 'text-red-600',
      cancelled: 'text-gray-600'
    };
    return colors[status] || 'text-gray-600';
  },

  validatePaymentForm: (formData) => {
    const errors = {};
    
    // Amount validation
    if (!formData.amount || parseFloat(formData.amount) <= 0) {
      errors.amount = 'Amount must be greater than 0';
    }
    
    // Phone validation for mobile money
    if (formData.method === 'mobile_money') {
      if (!formData.phone_number) {
        errors.phone_number = 'Phone number is required for mobile money payments';
      } else if (!/^\+2507\d{8}$/.test(formData.phone_number)) {
        errors.phone_number = 'Phone number must be a valid Rwanda number (e.g., +250788123456)';
      }
    }
    
    return Object.keys(errors).length === 0 ? null : errors;
  }
};
```

## 🔍 Payment Status Monitoring

### Real-time Status Updates
```javascript
const usePaymentStatus = (paymentId) => {
  const [status, setStatus] = useState(null);
  const [loading, setLoading] = useState(true);

  useEffect(() => {
    const pollStatus = async () => {
      try {
        const response = await paymentApi.getStatus(paymentId);
        setStatus(response.status);
        
        // Stop polling if payment is confirmed or failed
        if (['confirmed', 'failed', 'cancelled'].includes(response.status)) {
          setLoading(false);
          return;
        }
      } catch (error) {
        console.error('Failed to check payment status:', error);
      }
    };

    // Poll every 5 seconds
    const interval = setInterval(pollStatus, 5000);

    // Cleanup
    return () => clearInterval(interval);
  }, [paymentId]);

  return { status, loading };
};
```

## 📊 Payment Analytics

### Payment Statistics Component
```jsx
const PaymentStats = () => {
  const [stats, setStats] = useState(null);
  const [loading, setLoading] = useState(true);

  useEffect(() => {
    fetchPaymentStats();
  }, []);

  const fetchPaymentStats = async () => {
    try {
      const response = await apiRequest('/api/dashboard/payment-stats', 'GET');
      setStats(response.data);
    } catch (error) {
      console.error('Failed to fetch payment stats:', error);
    } finally {
      setLoading(false);
    }
  };

  if (loading) {
    return <div>Loading payment statistics...</div>;
  }

  if (!stats) return null;

  return (
    <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
      <div className="bg-white rounded-lg shadow-md p-4">
        <div className="flex items-center">
          <div className="p-3 bg-blue-100 rounded-full">
            <span className="text-blue-600 text-2xl font-bold">💵</span>
          </div>
          <div className="ml-4">
            <p className="text-sm text-gray-600">Total Payments</p>
            <p className="text-2xl font-bold">{stats.total_payments}</p>
          </div>
        </div>
      </div>

      <div className="bg-white rounded-lg shadow-md p-4">
        <div className="flex items-center">
          <div className="p-3 bg-green-100 rounded-full">
            <span className="text-green-600 text-2xl font-bold">✓</span>
          </div>
          <div className="ml-4">
            <p className="text-sm text-gray-600">Confirmed Payments</p>
            <p className="text-2xl font-bold">{stats.confirmed_payments}</p>
          </div>
        </div>
      </div>

      <div className="bg-white rounded-lg shadow-md p-4">
        <div className="flex items-center">
          <div className="p-3 bg-yellow-100 rounded-full">
            <span className="text-yellow-600 text-2xl font-bold">⏱</span>
          </div>
          <div className="ml-4">
            <p className="text-sm text-gray-600">Pending Payments</p>
            <p className="text-2xl font-bold">{stats.pending_payments}</p>
          </div>
        </div>
      </div>

      <div className="bg-white rounded-lg shadow-md p-4">
        <div className="flex items-center">
          <div className="p-3 bg-purple-100 rounded-full">
            <span className="text-purple-600 text-2xl font-bold">💰</span>
          </div>
          <div className="ml-4">
            <p className="text-sm text-gray-600">Total Amount Paid</p>
            <p className="text-2xl font-bold">
              {paymentUtils.formatCurrency(stats.total_amount_paid)}
            </p>
          </div>
        </div>
      </div>

      <div className="bg-white rounded-lg shadow-md p-4">
        <div className="flex items-center">
          <div className="p-3 bg-indigo-100 rounded-full">
            <span className="text-indigo-600 text-2xl font-bold">📊</span>
          </div>
          <div className="ml-4">
            <p className="text-sm text-gray-600">Average Payment</p>
            <p className="text-2xl font-bold">
              {paymentUtils.formatCurrency(stats.average_payment_amount)}
            </p>
          </div>
        </div>
      </div>
    </div>
  );
};
```

## 🎨 Mobile-Optimized Payment Interface

### Mobile Payment Form
```jsx
const MobilePaymentForm = ({ invoiceId, onPaymentSuccess }) => {
  const [formData, setFormData] = useState({
    amount: '',
    method: 'cash',
    phone_number: ''
  });

  return (
    <div className="fixed inset-0 bg-black bg-opacity-50 flex items-end justify-center z-50 p-4">
      <div className="bg-white rounded-t-2xl w-full max-h-screen">
        <div className="p-6 space-y-4">
          <div className="flex justify-between items-center mb-6">
            <h2 className="text-lg font-semibold">Payment</h2>
            <button onClick={onClose} className="text-gray-500">
              <svg className="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M6 18L18 6M6 6l6 6" />
              </svg>
            </button>
          </div>

          {/* Amount Input - Large Touch Target */}
          <div className="mb-6">
            <label className="block text-sm font-medium text-gray-700 mb-2">
              Amount (RWF)
            </label>
            <input
              type="number"
              inputMode="decimal"
              step="0.01"
              value={formData.amount}
              onChange={(e) => setFormData({...formData, amount: e.target.value})}
              className="w-full text-3xl font-bold p-4 border-2 border-gray-300 rounded-lg text-center"
              placeholder="0.00"
              autoFocus
            />
          </div>

          {/* Payment Method Selection */}
          <div className="grid grid-cols-3 gap-2 mb-6">
            {['cash', 'mobile_money', 'insurance'].map((method) => (
              <button
                key={method}
                onClick={() => setFormData({...formData, method})}
                className={`p-4 rounded-lg border-2 ${
                  formData.method === method 
                    ? 'border-blue-500 bg-blue-600 text-white' 
                    : 'border-gray-300 bg-white text-gray-700'
                }`}
              >
                <div className="text-2xl mb-1">{paymentUtils.getMethodIcon(method)}</div>
                <div className="text-sm capitalize">{method.replace('_', ' ')}</div>
              </button>
            ))}
          </div>

          {/* Mobile Money Phone Input */}
          {formData.method === 'mobile_money' && (
            <div className="mb-6">
              <label className="block text-sm font-medium text-gray-700 mb-2">
                Phone Number
              </label>
              <input
                type="tel"
                inputMode="tel"
                value={formData.phone_number}
                onChange={(e) => setFormData({...formData, phone_number: e.target.value})}
                className="w-full text-lg p-4 border-2 border-gray-300 rounded-lg"
                placeholder="+250788123456"
              />
            </div>
          )}

          {/* Action Buttons */}
          <div className="flex space-x-3">
            <button
              onClick={onClose}
              className="flex-1 bg-gray-500 text-white py-3 rounded-lg text-lg"
            >
              Cancel
            </button>
            <button
              onClick={() => handleSubmit(formData)}
              disabled={!formData.amount || parseFloat(formData.amount) <= 0}
              className="flex-1 bg-blue-600 text-white py-3 rounded-lg text-lg disabled:opacity-50"
            >
              Pay {paymentUtils.formatCurrency(formData.amount)}
            </button>
          </div>
        </div>
      </div>
    </div>
  );
};
```

## 🚨 Error Handling & Validation

### Payment Validation Rules
```javascript
const validatePaymentData = (paymentData) => {
  const errors = {};
  
  // Amount validation
  if (!paymentData.amount || paymentData.amount <= 0) {
    errors.amount = 'Amount is required and must be greater than 0';
  }
  
  // Currency format validation
  if (paymentData.amount && !/^\d{1,8}(\.\d{1,2})?$/.test(paymentData.amount)) {
    errors.amount = 'Amount must be in valid currency format (e.g., 10000.50)';
  }
  
  // Phone validation for mobile money
  if (paymentData.method === 'mobile_money') {
    if (!paymentData.phone_number) {
      errors.phone_number = 'Phone number is required for mobile money payments';
    } else if (!/^\+2507\d{8}$/.test(paymentData.phone_number)) {
      errors.phone_number = 'Phone number must be a valid Rwanda number (+2507xxxxxxxx)';
    }
  }
  
  return Object.keys(errors).length === 0 ? null : errors;
};
```

### Error Response Handling
```javascript
const handlePaymentError = (error) => {
  if (error.errors) {
    // Validation errors
    Object.keys(error.errors).forEach(field => {
      error.errors[field].forEach(message => {
        console.error(`${field}: ${message}`);
        // Show field-specific error
      });
    });
  } else if (error.message?.includes('insufficient')) {
    // Insufficient funds
    alert('Insufficient funds. Please check your account balance.');
  } else if (error.message?.includes('network')) {
    // Network errors
    alert('Network error. Please check your connection and try again.');
  } else {
    // General errors
    alert('Payment failed: ' + error.message);
  }
};
```

## 📱 Mobile App Features

### Biometric Payment Support
```javascript
const BiometricPayment = () => {
  const [biometricEnabled, setBiometricEnabled] = useState(false);
  const [authMethod, setAuthMethod] = useState('pin');

  const processBiometricPayment = async (paymentData) => {
    try {
      // Request biometric authentication
      const biometricResult = await requestBiometricAuth();
      
      if (biometricResult.success) {
        // Process payment with biometric token
        const response = await paymentApi.process(paymentData);
        return response;
      }
    } catch (error) {
      console.error('Biometric payment failed:', error);
      throw error;
    }
  };

  return (
    <div className="space-y-4">
      <div className="flex items-center justify-between">
        <h3 className="text-lg font-semibold">Payment Method</h3>
        <button
          onClick={() => setBiometricEnabled(!biometricEnabled)}
          className={`p-2 rounded ${biometricEnabled ? 'bg-green-600 text-white' : 'bg-gray-200 text-gray-700'}`}
        >
          {biometricEnabled ? '👆 Biometric' : '🔐 PIN'}
        </button>
      </div>

      <PaymentForm onSubmit={processBiometricPayment} />
    </div>
  );
};
```

## 🔄 Payment Workflow Integration

### Complete Invoice Payment Flow
```javascript
const InvoicePaymentFlow = ({ invoiceId }) => {
  const [step, setStep] = useState('review');
  const [paymentResult, setPaymentResult] = useState(null);

  const steps = {
    review: { title: 'Review Invoice', component: InvoiceReview },
    payment: { title: 'Process Payment', component: PaymentForm },
    confirmation: { title: 'Payment Confirmation', component: PaymentConfirmation },
    receipt: { title: 'Payment Receipt', component: PaymentReceipt }
  };

  const handlePaymentComplete = (result) => {
    setPaymentResult(result);
    setStep('confirmation');
  };

  const CurrentStep = steps[step].component;

  return (
    <div className="max-w-2xl mx-auto">
      {/* Progress Bar */}
      <div className="mb-6">
        <div className="flex items-center space-x-2">
          {Object.keys(steps).map((stepKey, index) => (
            <div key={stepKey} className={`flex items-center ${
              stepKey === step ? 'text-blue-600' : 'text-gray-400'
            }`}>
              <div className={`w-8 h-8 rounded-full flex items-center justify-center ${
                stepKey === step ? 'bg-blue-600' : 'bg-gray-300'
              }`}>
                {stepKey === step && (
                  <svg className="w-4 h-4 text-white" fill="currentColor" viewBox="0 0 20 20">
                    <path fillRule="evenodd" d="M16.707 5.293a1 1 0 0 1.414 1.414l-8-8a1 1 0 0 1.414 1.414L8.586 16.707a2 2 0 0 1-2h2a2 2 0 0 1-2z" clipRule="evenodd" />
                  </svg>
                )}
              </div>
              <span className="ml-2 text-sm font-medium">{steps[stepKey].title}</span>
            </div>
          ))}
        </div>
      </div>

      {/* Current Step Component */}
      <CurrentStep 
        invoiceId={invoiceId}
        onPaymentComplete={handlePaymentComplete}
        paymentResult={paymentResult}
      />
    </div>
  );
};
```

## 🎯 Best Practices

### 1. Security
- Never store payment details in localStorage
- Use HTTPS for all payment requests
- Validate payment data on client and server
- Implement proper authentication tokens

### 2. User Experience
- Show loading states during payment processing
- Provide clear error messages
- Support payment cancellation and retry
- Implement payment status polling for real-time updates

### 3. Mobile Optimization
- Use large touch targets for payment forms
- Implement biometric authentication where supported
- Optimize for one-handed operation
- Support gesture-based navigation

### 4. Performance
- Cache payment history for offline viewing
- Implement pagination for large payment lists
- Use debouncing for search functionality
- Optimize images and assets for mobile

### 5. Accessibility
- Use semantic HTML for payment forms
- Implement keyboard navigation
- Provide screen reader support
- Use high contrast colors for status indicators

## 🧪 Testing Examples

### Test Payment Processing
```javascript
const testPaymentProcessing = async () => {
  const testPayment = {
    amount: '10000.50',
    method: 'mobile_money',
    phone_number: '+250788123456',
    notes: 'Test payment for invoice #123'
  };

  try {
    const result = await paymentApi.process(456, testPayment);
    console.log('Payment processed:', result);
  } catch (error) {
    console.error('Payment processing failed:', error);
  }
};
```

### Test Payment Status Check
```javascript
const testPaymentStatus = async () => {
  try {
    const status = await paymentApi.getStatus(123);
    console.log('Payment status:', status);
    
    // Poll for status changes
    const interval = setInterval(async () => {
      const updatedStatus = await paymentApi.getStatus(123);
      console.log('Updated status:', updatedStatus);
      
      if (updatedStatus.status === 'confirmed') {
        clearInterval(interval);
        console.log('Payment confirmed!');
      }
    }, 3000);
    
  } catch (error) {
    console.error('Status check failed:', error);
  }
};
```

---

## 🚀 Quick Start Checklist

### Frontend Implementation:
- [ ] Payment list component with filtering
- [ ] Payment form with validation
- [ ] Payment status monitoring
- [ ] Mobile-optimized payment interface
- [ ] Real-time status updates
- [ ] Payment receipt generation
- [ ] Payment history and analytics
- [ ] Error handling and retry mechanisms
- [ ] Biometric authentication support

### API Integration:
- [ ] Test all payment endpoints
- [ ] Implement authentication flow
- [ ] Add request/response logging
- [ ] Handle rate limiting
- [ ] Add webhook integration for payment updates

This documentation provides everything needed to implement a complete payment processing system for the efiche billing API!
