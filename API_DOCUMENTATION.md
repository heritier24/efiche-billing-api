# Efiche Billing API - Frontend Integration Guide

## Quick Start
**Base URL**: `http://127.0.0.1:8000/api`
**Auth**: `Authorization: Bearer {token}`

## API Endpoints - Copy & Paste Ready

### 📋 Invoice Management

```javascript
// Get invoice by visit ID
GET /visits/{visitId}/invoice
// Returns: Full invoice with line items, payments, patient info

// Get all invoices  
GET /invoices
// Returns: Array of invoices

// Get specific invoice
GET /invoices/{id}
// Returns: Single invoice details
```

**Invoice Response Structure:**
```json
{
  "id": 1,
  "visit_id": 123,
  "invoice_number": "INV-2024-001",
  "status": "pending|partially_paid|paid|overdue",
  "total_amount": 50000.00,
  "insurance_coverage": 40000.00,
  "patient_responsibility": 10000.00,
  "total_paid": 0.00,
  "remaining_balance": 10000.00,
  "due_date": "2024-05-15T23:59:59.000000Z",
  "created_at": "2024-04-26T10:30:00.000000Z",
  "updated_at": "2024-04-26T10:30:00.000000Z",
  "line_items": [...],
  "payments": [...],
  "visit": {
    "id": 123,
    "patient": {
      "id": 456,
      "full_name": "John Doe",
      "phone": "+250788123456"
    },
    "facility": {
      "id": 789,
      "name": "Kigali Medical Center"
    }
  }
}
```

### 💳 Payment Processing

```javascript
// Process payment for invoice
POST /invoices/{invoiceId}/payments
```

**Payment Request Body:**
```json
{
  "amount": "10000.50",
  "method": "cash|mobile_money|insurance",
  "phone_number": "+250788123456", // Required for mobile_money only
  "notes": "Payment for consultation fee"
}
```

**Payment Response:**
```json
{
  "id": 123,
  "invoice_id": 456,
  "amount": 10000.50,
  "method": "cash|mobile_money|insurance",
  "status": "pending|confirmed|failed|cancelled",
  "transaction_ref": "TXN-2024-001",
  "cashier_id": 789,
  "processed_at": "2024-04-26T11:00:00.000000Z",
  "confirmed_at": "2024-04-26T11:05:00.000000Z",
  "notes": "Payment for consultation fee"
}
```

```javascript
// Get payment status
GET /payments/{paymentId}/status
// Returns: { id, status, confirmed_at, transaction_ref }

// Get all payments
GET /payments
// Returns: Array of payments

// Get specific payment
GET /payments/{id}
// Returns: Full payment details
```

### 📊 Dashboard Analytics

```javascript
// Get dashboard statistics
GET /dashboard/stats
```

**Dashboard Stats Response:**
```json
{
  "total_invoices": 150,
  "paid_invoices": 120,
  "pending_invoices": 25,
  "partially_paid_invoices": 5,
  "total_revenue": 2500000.00,
  "pending_revenue": 125000.00
}
```

```javascript
// Get payment statistics
GET /dashboard/payment-stats
```

**Payment Stats Response:**
```json
{
  "total_payments": 180,
  "confirmed_payments": 165,
  "pending_payments": 15,
  "total_amount_paid": 2375000.00,
  "average_payment_amount": 14393.94,
  "payment_methods": {
    "cash": 85,
    "mobile_money": 70,
    "insurance": 10
  }
}
```

```javascript
// Get top paying patients
GET /dashboard/top-patients
```

**Top Patients Response:**
```json
[
  {
    "patient_id": 456,
    "full_name": "John Doe",
    "phone": "+250788123456",
    "total_paid": 150000.00,
    "payment_count": 12
  }
]
```

### 🏥 Facility Insurance

```javascript
// Get facility insurances
GET /facilities/{facilityId}/insurances
```

**Facility Insurance Response:**
```json
{
  "data": [
    {
      "id": 1,
      "facility_id": 789,
      "insurance_id": 456,
      "coverage_percentage": 80.00,
      "status": "active",
      "created_at": "2024-01-01T00:00:00.000000Z",
      "updated_at": "2024-01-01T00:00:00.000000Z"
    }
  ]
}
```

## 🚨 Error Handling

**Standard Error Response:**
```json
{
  "error": "Error type description",
  "message": "Detailed error message"
}
```

**Validation Error Response:**
```json
{
  "error": "Validation failed",
  "message": "The given data was invalid.",
  "errors": {
    "amount": ["Amount must be a valid currency format (e.g., 10000.50)"],
    "phone_number": ["Phone number must be a valid Rwanda number (e.g., +250788123456)"]
  }
}
```

**HTTP Status Codes:**
- `200` - Success
- `201` - Created
- `400` - Bad Request (validation errors)
- `404` - Not Found
- `500` - Internal Server Error

## 📱 Frontend Implementation Examples

### JavaScript/React Example

```javascript
// API Configuration
const API_BASE = 'http://localhost:8000/api';
const headers = {
  'Content-Type': 'application/json',
  'Authorization': `Bearer ${token}`
};

// Get Invoice by Visit
const getInvoiceByVisit = async (visitId) => {
  try {
    const response = await fetch(`${API_BASE}/visits/${visitId}/invoice`, {
      headers
    });
    
    if (!response.ok) {
      const error = await response.json();
      throw new Error(error.message || 'Failed to fetch invoice');
    }
    
    return await response.json();
  } catch (error) {
    console.error('Error fetching invoice:', error);
    throw error;
  }
};

// Process Payment
const processPayment = async (invoiceId, paymentData) => {
  try {
    const response = await fetch(`${API_BASE}/invoices/${invoiceId}/payments`, {
      method: 'POST',
      headers,
      body: JSON.stringify(paymentData)
    });
    
    if (!response.ok) {
      const error = await response.json();
      throw new Error(error.message || 'Payment processing failed');
    }
    
    return await response.json();
  } catch (error) {
    console.error('Error processing payment:', error);
    throw error;
  }
};

// Get Dashboard Stats
const getDashboardStats = async () => {
  try {
    const response = await fetch(`${API_BASE}/dashboard/stats`, {
      headers
    });
    
    if (!response.ok) {
      const error = await response.json();
      throw new Error(error.message || 'Failed to fetch dashboard stats');
    }
    
    return await response.json();
  } catch (error) {
    console.error('Error fetching dashboard stats:', error);
    throw error;
  }
};

// Usage Examples
const handlePayment = async (invoiceId, amount, method, phoneNumber) => {
  try {
    const payment = await processPayment(invoiceId, {
      amount: amount.toFixed(2),
      method,
      phone_number: method === 'mobile_money' ? phoneNumber : undefined,
      notes: 'Payment via frontend'
    });
    
    console.log('Payment successful:', payment);
    return payment;
  } catch (error) {
    // Handle validation errors
    if (error.message.includes('Validation failed')) {
      // Show validation error to user
      return { error: 'validation', message: error.message };
    }
    // Handle other errors
    return { error: 'payment', message: error.message };
  }
};
```

### Form Validation Rules

```javascript
// Payment Form Validation
const validatePaymentForm = (formData) => {
  const errors = {};
  
  // Amount validation (RWF format)
  if (!formData.amount || !/^\d{1,8}(\.\d{1,2})?$/.test(formData.amount)) {
    errors.amount = 'Amount must be a valid currency format (e.g., 10000.50)';
  }
  
  // Method validation
  if (!formData.method || !['cash', 'mobile_money', 'insurance'].includes(formData.method)) {
    errors.method = 'Please select a valid payment method';
  }
  
  // Phone validation for mobile money
  if (formData.method === 'mobile_money') {
    if (!formData.phone_number || !/^\+2507\d{8}$/.test(formData.phone_number)) {
      errors.phone_number = 'Phone number must be a valid Rwanda number (e.g., +250788123456)';
    }
  }
  
  return Object.keys(errors).length === 0 ? null : errors;
};
```

## 🎨 UI Component Examples

### Payment Form Component
```jsx
const PaymentForm = ({ invoiceId, onSuccess, onError }) => {
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
    
    // Validate form
    const validationErrors = validatePaymentForm(formData);
    if (validationErrors) {
      setErrors(validationErrors);
      return;
    }
    
    setLoading(true);
    setErrors({});
    
    try {
      const payment = await processPayment(invoiceId, formData);
      onSuccess(payment);
    } catch (error) {
      onError(error);
    } finally {
      setLoading(false);
    }
  };

  return (
    <form onSubmit={handleSubmit}>
      <div className="form-group">
        <label>Amount (RWF)</label>
        <input
          type="number"
          step="0.01"
          value={formData.amount}
          onChange={(e) => setFormData({...formData, amount: e.target.value})}
          className={errors.amount ? 'error' : ''}
        />
        {errors.amount && <span className="error-text">{errors.amount}</span>}
      </div>
      
      <div className="form-group">
        <label>Payment Method</label>
        <select
          value={formData.method}
          onChange={(e) => setFormData({...formData, method: e.target.value})}
        >
          <option value="cash">Cash</option>
          <option value="mobile_money">Mobile Money</option>
          <option value="insurance">Insurance</option>
        </select>
      </div>
      
      {formData.method === 'mobile_money' && (
        <div className="form-group">
          <label>Phone Number</label>
          <input
            type="tel"
            placeholder="+250788123456"
            value={formData.phone_number}
            onChange={(e) => setFormData({...formData, phone_number: e.target.value})}
            className={errors.phone_number ? 'error' : ''}
          />
          {errors.phone_number && <span className="error-text">{errors.phone_number}</span>}
        </div>
      )}
      
      <button type="submit" disabled={loading}>
        {loading ? 'Processing...' : 'Process Payment'}
      </button>
    </form>
  );
};
```

## 📋 Important Notes

### Currency & Formatting
- All amounts are in **Rwandan Francs (RWF)**
- Display with **2 decimal places**
- Format: `10,000.50 RWF`

### Phone Numbers
- Must be **Rwanda format**: `+2507xxxxxxxx`
- Only required for **mobile money** payments

### Date Handling
- All dates are **ISO 8601 UTC**
- Format: `2024-04-26T10:30:00.000000Z`
- Convert to local timezone for display

### Status Values
**Invoice Status**: `pending`, `partially_paid`, `paid`, `overdue`
**Payment Status**: `pending`, `confirmed`, `failed`, `cancelled`

### Loading States
- Payment processing may take time
- Show loading indicators during API calls
- Implement timeout handling

### Error Handling
- Always check for validation errors (400)
- Handle network errors gracefully
- Show user-friendly error messages

## 🧪 Testing Examples

```bash
# Test payment processing
curl -X POST http://localhost:8000/api/invoices/1/payments \
  -H "Content-Type: application/json" \
  -H "Authorization: Bearer {token}" \
  -d '{
    "amount": "10000.50",
    "method": "mobile_money",
    "phone_number": "+250788123456",
    "notes": "Test payment"
  }'

# Test dashboard stats
curl -X GET http://localhost:8000/api/dashboard/stats \
  -H "Authorization: Bearer {token}"
```

## 🚀 Ready to Use

This documentation provides everything needed to integrate the frontend with the Efiche Billing API. Copy the relevant code examples and adapt them to your frontend framework and styling preferences.
