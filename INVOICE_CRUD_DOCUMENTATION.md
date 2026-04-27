# Invoice CRUD API Documentation - Frontend Integration Guide

## 📋 Overview

Complete guide for frontend developers to implement invoice display, creation, and management functionality using the efiche billing API.

## 🔗 Base API Configuration

```
Base URL: http://127.0.0.1:8000/api
Authentication: Bearer {token}
Content-Type: application/json
```

## 📊 Invoice CRUD Endpoints

### 1. Get All Invoices
```http
GET /api/invoices
Authorization: Bearer {token}
```

**Response:**
```json
{
  "success": true,
  "data": [
    {
      "id": 1,
      "visit_id": 123,
      "invoice_number": "INV-2026-0001",
      "status": "pending",
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
  ]
}
```

### 2. Get Specific Invoice
```http
GET /api/invoices/{id}
Authorization: Bearer {token}
```

**Response:**
```json
{
  "success": true,
  "data": {
    "id": 1,
    "visit_id": 123,
    "invoice_number": "INV-2026-0001",
    "status": "pending",
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
}
```

### 3. Create New Invoice
```http
POST /api/invoices
Authorization: Bearer {token}
Content-Type: application/json
```

**Request Body:**
```json
{
  "visit_id": 1,
  "line_items": [
    {
      "item_code": "CONSULT001",
      "description": "General consultation fee",
      "quantity": 1,
      "unit_price": 50000.00
    },
    {
      "item_code": "LAB001",
      "description": "Blood test",
      "quantity": 2,
      "unit_price": 25000.00
    }
  ],
  "insurance_id": 456,
  "due_date": "2024-05-15"
}
```

**Response:**
```json
{
  "success": true,
  "message": "Invoice created successfully",
  "data": {
    "id": 4,
    "visit_id": 1,
    "invoice_number": "INV-2026-0004",
    "status": "pending",
    "total_amount": 100000.00,
    "insurance_coverage": 0.00,
    "patient_responsibility": 100000.00,
    "total_paid": 0.00,
    "remaining_balance": 100000.00,
    "due_date": "2024-05-15T00:00:00.000000Z",
    "created_at": "2024-04-27T07:08:19.000000Z",
    "updated_at": "2024-04-27T07:08:19.000000Z",
    "line_items": [...],
    "payments": [],
    "visit": {
      "id": 1,
      "patient": {
        "id": 12,
        "full_name": "Test Patient1777208266",
        "phone": "+250788123456"
      },
      "facility": {
        "id": 1,
        "name": "King Faisal Hospital"
      }
    }
  }
}
```

### 4. Get Invoice by Visit ID
```http
GET /api/visits/{visitId}/invoice
Authorization: Bearer {token}
```

**Response:** Same format as "Get Specific Invoice"

### 5. Update Invoice (Not Implemented)
```http
PUT /api/invoices/{id}
Authorization: Bearer {token}
```

### 6. Delete Invoice (Not Implemented)
```http
DELETE /api/invoices/{id}
Authorization: Bearer {token}
```

## 📋 Invoice Status Values

| Status | Description | Display Color |
|---------|-------------|--------------|
| `pending` | Invoice created but no payments received | 🔴 Red |
| `partially_paid` | Some payments received but not fully paid | 🟡 Yellow |
| `paid` | Invoice fully paid | 🟢 Green |
| `overdue` | Invoice past due date with no payments | 🔴 Red |

## 💰 Payment Integration

### Process Payment for Invoice
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

**Payment Methods:**
- `cash` - Physical cash payment
- `mobile_money` - Mobile money transfer
- `insurance` - Direct insurance payment

## 🎨 Frontend Implementation Examples

### React Component - Invoice List
```jsx
const InvoiceList = () => {
  const [invoices, setInvoices] = useState([]);
  const [loading, setLoading] = useState(true);

  useEffect(() => {
    fetchInvoices();
  }, []);

  const fetchInvoices = async () => {
    try {
      const response = await apiRequest('/api/invoices', 'GET');
      setInvoices(response.data);
    } catch (error) {
      console.error('Failed to fetch invoices:', error);
    } finally {
      setLoading(false);
    }
  };

  const getStatusColor = (status) => {
    switch (status) {
      case 'pending': return 'text-red-600';
      case 'partially_paid': return 'text-yellow-600';
      case 'paid': return 'text-green-600';
      case 'overdue': return 'text-red-800';
      default: return 'text-gray-600';
    }
  };

  if (loading) {
    return <div>Loading invoices...</div>;
  }

  return (
    <div className="space-y-4">
      {invoices.map((invoice) => (
        <div key={invoice.id} className="border rounded-lg p-4">
          <div className="flex justify-between items-start">
            <div>
              <h3 className="font-semibold">{invoice.invoice_number}</h3>
              <p className="text-sm text-gray-600">
                {invoice.visit.patient.full_name} - {invoice.visit.facility.name}
              </p>
            </div>
            <div className="text-right">
              <span className={`font-medium ${getStatusColor(invoice.status)}`}>
                {invoice.status.replace('_', ' ').toUpperCase()}
              </span>
            </div>
          </div>
          
          <div className="mt-4 grid grid-cols-2 gap-4">
            <div>
              <p className="text-sm text-gray-600">Total Amount</p>
              <p className="font-semibold">
                {invoice.total_amount.toLocaleString()} RWF
              </p>
            </div>
            <div>
              <p className="text-sm text-gray-600">Remaining Balance</p>
              <p className="font-semibold">
                {invoice.remaining_balance.toLocaleString()} RWF
              </p>
            </div>
          </div>
          
          <div className="mt-4">
            <h4 className="font-medium mb-2">Line Items</h4>
            {invoice.line_items.map((item, index) => (
              <div key={index} className="flex justify-between py-2 border-b">
                <span>{item.description}</span>
                <span>
                  {item.quantity} × {item.unit_price.toLocaleString()} RWF
                </span>
              </div>
            ))}
          </div>
          
          <div className="mt-4 flex justify-between">
            <button className="bg-blue-600 text-white px-4 py-2 rounded">
              Process Payment
            </button>
            <button className="bg-gray-600 text-white px-4 py-2 rounded">
              View Details
            </button>
          </div>
        </div>
      ))}
    </div>
  );
};
```

### React Component - Create Invoice Modal
```jsx
const CreateInvoiceModal = ({ isOpen, onClose }) => {
  const [visits, setVisits] = useState([]);
  const [selectedVisit, setSelectedVisit] = useState('');
  const [lineItems, setLineItems] = useState([{
    item_code: '',
    description: '',
    quantity: 1,
    unit_price: ''
  }]);
  const [loading, setLoading] = useState(false);

  useEffect(() => {
    if (isOpen) {
      fetchVisits();
    }
  }, [isOpen]);

  const fetchVisits = async () => {
    try {
      const response = await apiRequest('/api/visits', 'GET');
      setVisits(response.data);
    } catch (error) {
      console.error('Failed to fetch visits:', error);
    }
  };

  const addLineItem = () => {
    setLineItems([...lineItems, {
      item_code: '',
      description: '',
      quantity: 1,
      unit_price: ''
    }]);
  };

  const removeLineItem = (index) => {
    setLineItems(lineItems.filter((_, i) => i !== index));
  };

  const updateLineItem = (index, field, value) => {
    const newItems = [...lineItems];
    newItems[index][field] = value;
    setLineItems(newItems);
  };

  const handleSubmit = async (e) => {
    e.preventDefault();
    setLoading(true);

    try {
      const invoiceData = {
        visit_id: selectedVisit,
        line_items: lineItems.filter(item => item.item_code && item.description && item.unit_price),
        due_date: new Date(Date.now() + 30 * 24 * 60 * 60 * 1000).toISOString().split('T')[0]
      };

      const response = await apiRequest('/api/invoices', 'POST', invoiceData);
      
      if (response.success) {
        alert('Invoice created successfully!');
        onClose();
        // Refresh invoice list
        window.location.reload();
      }
    } catch (error) {
      console.error('Failed to create invoice:', error);
      alert('Failed to create invoice: ' + error.message);
    } finally {
      setLoading(false);
    }
  };

  const calculateTotal = () => {
    return lineItems.reduce((sum, item) => {
      return sum + (item.quantity * parseFloat(item.unit_price || 0));
    }, 0);
  };

  if (!isOpen) return null;

  return (
    <div className="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50">
      <div className="bg-white rounded-lg p-6 w-full max-w-2xl max-h-screen overflow-y-auto">
        <div className="flex justify-between items-center mb-4">
          <h2 className="text-xl font-semibold">Create New Invoice</h2>
          <button onClick={onClose} className="text-gray-500 hover:text-gray-700">
            ×
          </button>
        </div>

        <form onSubmit={handleSubmit} className="space-y-4">
          <div>
            <label className="block text-sm font-medium text-gray-700 mb-2">
              Select Visit
            </label>
            <select
              value={selectedVisit}
              onChange={(e) => setSelectedVisit(e.target.value)}
              className="w-full border border-gray-300 rounded-md shadow-sm p-2"
              required
            >
              <option value="">Choose a visit...</option>
              {visits.map((visit) => (
                <option key={visit.id} value={visit.id}>
                  {visit.patient.full_name} - {visit.facility.name} ({visit.visit_type})
                </option>
              ))}
            </select>
          </div>

          <div>
            <h3 className="font-medium mb-2">Line Items</h3>
            {lineItems.map((item, index) => (
              <div key={index} className="border rounded p-3 mb-3">
                <div className="grid grid-cols-4 gap-2">
                  <input
                    type="text"
                    placeholder="Item Code"
                    value={item.item_code}
                    onChange={(e) => updateLineItem(index, 'item_code', e.target.value)}
                    className="border border-gray-300 rounded p-2"
                    required
                  />
                  <input
                    type="text"
                    placeholder="Description"
                    value={item.description}
                    onChange={(e) => updateLineItem(index, 'description', e.target.value)}
                    className="border border-gray-300 rounded p-2"
                    required
                  />
                  <input
                    type="number"
                    placeholder="Quantity"
                    value={item.quantity}
                    onChange={(e) => updateLineItem(index, 'quantity', parseInt(e.target.value))}
                    className="border border-gray-300 rounded p-2"
                    min="1"
                    required
                  />
                  <input
                    type="number"
                    placeholder="Unit Price"
                    value={item.unit_price}
                    onChange={(e) => updateLineItem(index, 'unit_price', e.target.value)}
                    className="border border-gray-300 rounded p-2"
                    min="0"
                    step="0.01"
                    required
                  />
                  <button
                    type="button"
                    onClick={() => removeLineItem(index)}
                    className="bg-red-500 text-white px-2 py-1 rounded"
                  >
                    Remove
                  </button>
                </div>
              </div>
            ))}
            
            <button
              type="button"
              onClick={addLineItem}
              className="bg-green-500 text-white px-4 py-2 rounded"
            >
              Add Line Item
            </button>
          </div>

          <div className="border-t pt-4">
            <div className="flex justify-between items-center">
              <span className="font-medium">Total Amount:</span>
              <span className="font-bold text-lg">
                {calculateTotal().toLocaleString()} RWF
              </span>
            </div>
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
              disabled={loading || !selectedVisit || calculateTotal() === 0}
              className="bg-blue-600 text-white px-4 py-2 rounded disabled:opacity-50"
            >
              {loading ? 'Creating...' : 'Create Invoice'}
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
// API Service for Invoice Operations
const invoiceApi = {
  // Get all invoices
  getAll: async () => {
    const response = await apiRequest('/api/invoices', 'GET');
    return response.data;
  },

  // Get specific invoice
  getById: async (id) => {
    const response = await apiRequest(`/api/invoices/${id}`, 'GET');
    return response.data;
  },

  // Get invoice by visit
  getByVisitId: async (visitId) => {
    const response = await apiRequest(`/api/visits/${visitId}/invoice`, 'GET');
    return response.data;
  },

  // Create new invoice
  create: async (invoiceData) => {
    const response = await apiRequest('/api/invoices', 'POST', invoiceData);
    return response.data;
  },

  // Update invoice (when implemented)
  update: async (id, data) => {
    const response = await apiRequest(`/api/invoices/${id}`, 'PUT', data);
    return response.data;
  },

  // Delete invoice (when implemented)
  delete: async (id) => {
    const response = await apiRequest(`/api/invoices/${id}`, 'DELETE');
    return response.data;
  }
};

// Utility functions
const invoiceUtils = {
  formatCurrency: (amount) => {
    return new Intl.NumberFormat('rw-RW', {
      style: 'currency',
      currency: 'RWF'
    }).format(amount);
  },

  getStatusColor: (status) => {
    const colors = {
      pending: 'text-red-600',
      partially_paid: 'text-yellow-600',
      paid: 'text-green-600',
      overdue: 'text-red-800'
    };
    return colors[status] || 'text-gray-600';
  },

  getStatusBadge: (status) => {
    const badges = {
      pending: { bg: 'bg-red-100', text: 'text-red-800' },
      partially_paid: { bg: 'bg-yellow-100', text: 'text-yellow-800' },
      paid: { bg: 'bg-green-100', text: 'text-green-800' },
      overdue: { bg: 'bg-red-100', text: 'text-red-900' }
    };
    const badge = badges[status] || { bg: 'bg-gray-100', text: 'text-gray-800' };
    return `inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium ${badge.bg} ${badge.text}`;
  }
};
```

## 🔍 Filtering and Search

### Filter Invoices by Status
```http
GET /api/invoices?status=pending
GET /api/invoices?status=paid
GET /api/invoices?status=overdue
```

### Search Invoices by Patient Name
```http
GET /api/invoices?search=john
```

### Filter by Date Range
```http
GET /api/invoices?start_date=2024-04-01&end_date=2024-04-30
```

## 📱 Mobile Responsive Design

### Invoice Card (Mobile)
```jsx
<div className="bg-white rounded-lg shadow-md p-4 mb-4">
  <div className="flex flex-col sm:flex-row sm:justify-between sm:items-start">
    <div className="mb-2 sm:mb-0">
      <h3 className="font-semibold text-lg">{invoice.invoice_number}</h3>
      <p className="text-sm text-gray-600">{invoice.visit.patient.full_name}</p>
    </div>
    <div className="text-right">
      <span className={`inline-flex items-center px-2 py-1 rounded-full text-xs font-medium ${getStatusBadge(invoice.status)}`}>
        {invoice.status.replace('_', ' ').toUpperCase()}
      </span>
    </div>
  </div>
</div>
```

### Invoice Table (Desktop)
```jsx
<div className="overflow-x-auto">
  <table className="min-w-full divide-y divide-gray-200">
    <thead className="bg-gray-50">
      <tr>
        <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
          Invoice Number
        </th>
        <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
          Patient
        </th>
        <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
          Amount
        </th>
        <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
          Status
        </th>
        <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
          Actions
        </th>
      </tr>
    </thead>
    <tbody className="bg-white divide-y divide-gray-200">
      {invoices.map((invoice) => (
        <tr key={invoice.id}>
          <td className="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
            {invoice.invoice_number}
          </td>
          <td className="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
            {invoice.visit.patient.full_name}
          </td>
          <td className="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
            {invoiceUtils.formatCurrency(invoice.total_amount)}
          </td>
          <td className="px-6 py-4 whitespace-nowrap">
            <span className={`inline-flex items-center px-2 py-1 rounded-full text-xs font-medium ${getStatusBadge(invoice.status)}`}>
              {invoice.status.replace('_', ' ').toUpperCase()}
            </span>
          </td>
          <td className="px-6 py-4 whitespace-nowrap text-sm font-medium">
            <button className="text-indigo-600 hover:text-indigo-900">
              View
            </button>
          </td>
        </tr>
      ))}
    </tbody>
  </table>
</div>
```

## 🚨 Error Handling

### Common Error Responses
```json
{
  "success": false,
  "error": "Validation failed",
  "message": "The given data was invalid.",
  "errors": {
    "visit_id": ["The selected visit id is invalid."],
    "line_items": ["The line items field is required."]
  }
}
```

```json
{
  "success": false,
  "error": "Invoice not found",
  "message": "Invoice with ID: 999 does not exist"
}
```

### Frontend Error Handling
```javascript
const handleApiError = (error) => {
  if (error.errors) {
    // Validation errors
    Object.keys(error.errors).forEach(field => {
      error.errors[field].forEach(message => {
        console.error(`${field}: ${message}`);
        // Show field-specific error messages
      });
    });
  } else {
    // General errors
    console.error(error.message);
    // Show general error notification
  }
};
```

## 📊 Pagination

### Paginated Invoice List
```http
GET /api/invoices?page=2&per_page=10
```

**Response:**
```json
{
  "success": true,
  "data": [...],
  "current_page": 2,
  "per_page": 10,
  "total": 45,
  "last_page": 5
}
```

### Frontend Pagination Component
```jsx
const InvoicePagination = ({ currentPage, totalPages, onPageChange }) => {
  const pages = [];
  for (let i = 1; i <= totalPages; i++) {
    pages.push(i);
  }

  return (
    <div className="flex items-center justify-between">
      <div className="flex items-center space-x-2">
        <button
          onClick={() => onPageChange(currentPage - 1)}
          disabled={currentPage === 1}
          className="px-3 py-1 border rounded-md text-sm disabled:opacity-50"
        >
          Previous
        </button>
        
        {pages.map(page => (
          <button
            key={page}
            onClick={() => onPageChange(page)}
            className={`px-3 py-1 border rounded-md text-sm ${
              currentPage === page ? 'bg-blue-600 text-white' : 'bg-white text-gray-700'
            }`}
          >
            {page}
          </button>
        ))}
        
        <button
          onClick={() => onPageChange(currentPage + 1)}
          disabled={currentPage === totalPages}
          className="px-3 py-1 border rounded-md text-sm disabled:opacity-50"
        >
          Next
        </button>
      </div>
      
      <div className="text-sm text-gray-700">
        Page {currentPage} of {totalPages}
      </div>
    </div>
  );
};
```

## 🎯 Best Practices

### 1. Data Loading States
- Show loading spinners during API calls
- Display skeleton loaders for better UX
- Handle empty states gracefully

### 2. Currency Formatting
- Always use RWF currency format
- Display 2 decimal places
- Use proper thousand separators

### 3. Status Management
- Use consistent color coding for invoice statuses
- Show status badges for quick recognition
- Provide status change notifications

### 4. Error Handling
- Validate form data before submission
- Show user-friendly error messages
- Implement retry mechanisms for failed requests

### 5. Performance Optimization
- Implement pagination for large datasets
- Use debouncing for search functionality
- Cache frequently accessed data

## 🧪 Testing Examples

### Test Invoice Creation
```javascript
const testInvoiceCreation = async () => {
  const testInvoice = {
    visit_id: 1,
    line_items: [
      {
        item_code: "TEST001",
        description: "Test consultation",
        quantity: 1,
        unit_price: 50000.00
      }
    ],
    due_date: "2024-05-15"
  };

  try {
    const result = await invoiceApi.create(testInvoice);
    console.log('Invoice created:', result);
  } catch (error) {
    console.error('Invoice creation failed:', error);
  }
};
```

### Test Invoice Retrieval
```javascript
const testInvoiceRetrieval = async () => {
  try {
    const allInvoices = await invoiceApi.getAll();
    console.log('All invoices:', allInvoices);

    const specificInvoice = await invoiceApi.getById(1);
    console.log('Specific invoice:', specificInvoice);

    const visitInvoice = await invoiceApi.getByVisitId(1);
    console.log('Visit invoice:', visitInvoice);
  } catch (error) {
    console.error('Invoice retrieval failed:', error);
  }
};
```

## 📱 Mobile App Considerations

### Touch-Friendly Interface
- Use larger tap targets for mobile
- Implement swipe gestures for invoice actions
- Optimize for one-handed operation

### Offline Support
- Cache invoice data for offline viewing
- Queue invoice creation for when online
- Sync pending changes when connection restored

---

## 🚀 Quick Start Checklist

### Frontend Implementation:
- [ ] Replace mock visit data with API calls
- [ ] Implement invoice list component
- [ ] Add create invoice modal
- [ ] Add invoice detail view
- [ ] Implement payment processing
- [ ] Add status filtering
- [ ] Add search functionality
- [ ] Implement pagination
- [ ] Add error handling
- [ ] Optimize for mobile devices

### API Integration:
- [ ] Test all endpoints
- [ ] Implement authentication
- [ ] Add request/response logging
- [ ] Handle rate limiting
- [ ] Add retry mechanisms

This documentation provides everything needed to implement a complete invoice management system for the efiche billing API!
