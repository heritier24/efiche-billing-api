# Dashboard API Documentation - Frontend Integration Guide

## 📋 Overview

Complete guide for frontend developers to integrate with the efiche billing dashboard API endpoints. All endpoints are now implemented and tested with real data.

## 🔗 Base API Configuration

```
Base URL: http://127.0.0.1:8000/api
Authentication: Bearer {token}
Content-Type: application/json
```

## 🚀 Available Dashboard Endpoints

### 1. Main Dashboard Statistics
```http
GET /api/dashboard/stats
Authorization: Bearer {token}
```

**Purpose**: Get aggregated statistics for dashboard overview cards

**Response**:
```json
{
  "total_invoices": 8,
  "total_revenue": 4999.95,
  "pending_invoices": 7,
  "active_patients": 10,
  "monthly_stats": {
    "current_month": {
      "invoices": 8,
      "revenue": 4999.95,
      "patients": 10
    },
    "previous_month": {
      "invoices": 0,
      "revenue": 0,
      "patients": 0
    }
  },
  "payment_methods_breakdown": {
    "cash": 100,
    "mobile_money": 0,
    "insurance": 0
  }
}
```

**Fields Description**:
- `total_invoices`: Total number of invoices in the system
- `total_revenue`: Total revenue from all confirmed payments (RWF)
- `pending_invoices`: Number of invoices with pending or partially paid status
- `active_patients`: Number of patients with recent visits (last 30 days)
- `monthly_stats`: Comparative stats for current vs previous month
- `payment_methods_breakdown`: Percentage breakdown by payment method

---

### 2. Active Patients Statistics
```http
GET /api/dashboard/active-patients
Authorization: Bearer {token}
```

**Purpose**: Get detailed breakdown of active patients

**Response**:
```json
{
  "active_patients": 10,
  "period": "30_days",
  "breakdown": {
    "new_patients": 10,
    "returning_patients": 0,
    "patients_with_visits": 10,
    "patients_with_payments": 1
  }
}
```

**Fields Description**:
- `active_patients`: Total active patients count
- `period`: Time period for the count (30 days)
- `breakdown`: Detailed breakdown of patient activity types
  - `new_patients`: Patients created in the last 30 days
  - `returning_patients`: Existing patients with recent activity
  - `patients_with_visits`: Patients who had visits
  - `patients_with_payments`: Patients who made payments

---

### 3. Revenue Summary Analytics
```http
GET /api/dashboard/revenue-summary?period=30d
Authorization: Bearer {token}
```

**Query Parameters**:
- `period`: `30d` (default) or `7d` for weekly data

**Response**:
```json
{
  "total_revenue": 4999.95,
  "period": "30_days",
  "daily_average": 166.665,
  "growth_rate": 0,
  "by_payment_method": {
    "cash": {
      "amount": 4999.95,
      "count": 1,
      "percentage": 100
    },
    "mobile_money": {
      "amount": 0,
      "count": 0,
      "percentage": 0
    },
    "insurance": {
      "amount": 0,
      "count": 0,
      "percentage": 0
    }
  },
  "daily_breakdown": [
    {
      "date": "2026-04-27",
      "revenue": 4999.95,
      "transactions": 1
    }
  ]
}
```

**Fields Description**:
- `total_revenue`: Total revenue for the period (RWF)
- `daily_average`: Average daily revenue
- `growth_rate`: Percentage growth from previous period
- `by_payment_method`: Revenue breakdown by payment method
- `daily_breakdown`: Daily revenue data for charts

---

### 4. Recent Invoices List
```http
GET /api/dashboard/recent-invoices?limit=5
Authorization: Bearer {token}
```

**Query Parameters**:
- `limit`: Number of invoices to return (default: 5)

**Response**:
```json
{
  "data": [
    {
      "id": 8,
      "invoice_number": "INV-20260008",
      "visit_id": 7,
      "status": "pending",
      "total_amount": 44996.6,
      "total_paid": 0,
      "remaining_balance": 44996.6,
      "created_at": "2026-04-27T12:43:54.000000Z",
      "visit": {
        "id": 7,
        "patient": {
          "id": 11,
          "first_name": "Updated",
          "last_name": "Patient1777208334",
          "full_name": "Updated Patient1777208334"
        }
      },
      "line_items": [
        {
          "item_code": "EMERGGENCY",
          "description": "dsaf",
          "quantity": 10,
          "unit_price": 4499.66,
          "total_price": 44996.6
        }
      ]
    }
  ],
  "total": 8
}
```

**Fields Description**:
- `data`: Array of recent invoices with full details
- `total`: Total number of invoices in the system
- Each invoice includes patient info, line items, and payment status

---

## 🎨 Frontend Implementation Examples

### React Hook for Dashboard Stats
```javascript
import { useState, useEffect } from 'react';

const useDashboardStats = () => {
  const [stats, setStats] = useState(null);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState(null);

  useEffect(() => {
    const fetchStats = async () => {
      try {
        const response = await apiRequest('/api/dashboard/stats', 'GET');
        setStats(response.data);
      } catch (err) {
        setError(err.message);
      } finally {
        setLoading(false);
      }
    };

    fetchStats();
  }, []);

  return { stats, loading, error };
};

// Usage in component
const DashboardOverview = () => {
  const { stats, loading, error } = useDashboardStats();

  if (loading) return <div>Loading dashboard...</div>;
  if (error) return <div>Error: {error}</div>;

  return (
    <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
      <StatCard 
        title="Total Invoices" 
        value={stats.total_invoices} 
        icon="📄"
        color="blue"
      />
      <StatCard 
        title="Total Revenue" 
        value={formatCurrency(stats.total_revenue)} 
        icon="💰"
        color="green"
      />
      <StatCard 
        title="Pending Invoices" 
        value={stats.pending_invoices} 
        icon="⏳"
        color="yellow"
      />
      <StatCard 
        title="Active Patients" 
        value={stats.active_patients} 
        icon="👥"
        color="purple"
      />
    </div>
  );
};
```

### Revenue Chart Component
```javascript
const RevenueChart = () => {
  const [revenueData, setRevenueData] = useState(null);

  useEffect(() => {
    const fetchRevenueData = async () => {
      try {
        const response = await apiRequest('/api/dashboard/revenue-summary?period=30d', 'GET');
        setRevenueData(response.data);
      } catch (error) {
        console.error('Failed to fetch revenue data:', error);
      }
    };

    fetchRevenueData();
  }, []);

  if (!revenueData) return <div>Loading revenue data...</div>;

  return (
    <div className="bg-white rounded-lg shadow-md p-6">
      <h3 className="text-lg font-semibold mb-4">Revenue Overview</h3>
      
      <div className="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6">
        <div className="text-center">
          <p className="text-sm text-gray-600">Total Revenue</p>
          <p className="text-2xl font-bold text-green-600">
            {formatCurrency(revenueData.total_revenue)}
          </p>
        </div>
        <div className="text-center">
          <p className="text-sm text-gray-600">Daily Average</p>
          <p className="text-2xl font-bold text-blue-600">
            {formatCurrency(revenueData.daily_average)}
          </p>
        </div>
        <div className="text-center">
          <p className="text-sm text-gray-600">Growth Rate</p>
          <p className={`text-2xl font-bold ${revenueData.growth_rate >= 0 ? 'text-green-600' : 'text-red-600'}`}>
            {revenueData.growth_rate >= 0 ? '+' : ''}{revenueData.growth_rate}%
          </p>
        </div>
      </div>

      {/* Payment Methods Breakdown */}
      <div className="mb-6">
        <h4 className="font-medium mb-3">Payment Methods</h4>
        <div className="space-y-2">
          {Object.entries(revenueData.by_payment_method).map(([method, data]) => (
            <div key={method} className="flex items-center justify-between">
              <span className="capitalize">{method.replace('_', ' ')}</span>
              <div className="flex items-center space-x-2">
                <span>{formatCurrency(data.amount)}</span>
                <span className="text-sm text-gray-600">({data.percentage}%)</span>
              </div>
            </div>
          ))}
        </div>
      </div>

      {/* Daily Revenue Chart */}
      <div>
        <h4 className="font-medium mb-3">Daily Revenue</h4>
        <div className="h-40 bg-gray-50 rounded flex items-center justify-center">
          <p className="text-gray-500">Chart component would go here</p>
        </div>
      </div>
    </div>
  );
};
```

### Recent Invoices Component
```javascript
const RecentInvoices = () => {
  const [invoices, setInvoices] = useState([]);
  const [loading, setLoading] = useState(true);

  useEffect(() => {
    const fetchRecentInvoices = async () => {
      try {
        const response = await apiRequest('/api/dashboard/recent-invoices?limit=5', 'GET');
        setInvoices(response.data.data);
      } catch (error) {
        console.error('Failed to fetch recent invoices:', error);
      } finally {
        setLoading(false);
      }
    };

    fetchRecentInvoices();
  }, []);

  const getStatusBadge = (status) => {
    const badges = {
      pending: 'bg-yellow-100 text-yellow-800',
      partially_paid: 'bg-blue-100 text-blue-800',
      paid: 'bg-green-100 text-green-800',
      overdue: 'bg-red-100 text-red-800'
    };
    return badges[status] || 'bg-gray-100 text-gray-800';
  };

  if (loading) return <div>Loading recent invoices...</div>;

  return (
    <div className="bg-white rounded-lg shadow-md p-6">
      <h3 className="text-lg font-semibold mb-4">Recent Invoices</h3>
      
      <div className="space-y-3">
        {invoices.map((invoice) => (
          <div key={invoice.id} className="border rounded-lg p-4">
            <div className="flex justify-between items-start mb-2">
              <div>
                <h4 className="font-medium">{invoice.invoice_number}</h4>
                <p className="text-sm text-gray-600">
                  {invoice.visit.patient.full_name}
                </p>
              </div>
              <div className="text-right">
                <span className={`inline-flex items-center px-2 py-1 rounded-full text-xs font-medium ${getStatusBadge(invoice.status)}`}>
                  {invoice.status.replace('_', ' ').toUpperCase()}
                </span>
              </div>
            </div>
            
            <div className="flex justify-between items-center">
              <div>
                <p className="text-sm text-gray-600">Total</p>
                <p className="font-semibold">{formatCurrency(invoice.total_amount)}</p>
              </div>
              <div>
                <p className="text-sm text-gray-600">Remaining</p>
                <p className="font-semibold">{formatCurrency(invoice.remaining_balance)}</p>
              </div>
              <button className="bg-blue-600 text-white px-3 py-1 rounded text-sm">
                View Details
              </button>
            </div>
          </div>
        ))}
      </div>
    </div>
  );
};
```

### Active Patients Component
```javascript
const ActivePatients = () => {
  const [patientsData, setPatientsData] = useState(null);

  useEffect(() => {
    const fetchPatientsData = async () => {
      try {
        const response = await apiRequest('/api/dashboard/active-patients', 'GET');
        setPatientsData(response.data);
      } catch (error) {
        console.error('Failed to fetch patients data:', error);
      }
    };

    fetchPatientsData();
  }, []);

  if (!patientsData) return <div>Loading patients data...</div>;

  return (
    <div className="bg-white rounded-lg shadow-md p-6">
      <h3 className="text-lg font-semibold mb-4">Active Patients (30 Days)</h3>
      
      <div className="text-center mb-6">
        <p className="text-3xl font-bold text-blue-600">
          {patientsData.active_patients}
        </p>
        <p className="text-sm text-gray-600">Total Active Patients</p>
      </div>

      <div className="grid grid-cols-2 gap-4">
        <div className="text-center p-4 bg-green-50 rounded-lg">
          <p className="text-2xl font-bold text-green-600">
            {patientsData.breakdown.new_patients}
          </p>
          <p className="text-sm text-gray-600">New Patients</p>
        </div>
        <div className="text-center p-4 bg-blue-50 rounded-lg">
          <p className="text-2xl font-bold text-blue-600">
            {patientsData.breakdown.returning_patients}
          </p>
          <p className="text-sm text-gray-600">Returning Patients</p>
        </div>
        <div className="text-center p-4 bg-purple-50 rounded-lg">
          <p className="text-2xl font-bold text-purple-600">
            {patientsData.breakdown.patients_with_visits}
          </p>
          <p className="text-sm text-gray-600">Patients with Visits</p>
        </div>
        <div className="text-center p-4 bg-yellow-50 rounded-lg">
          <p className="text-2xl font-bold text-yellow-600">
            {patientsData.breakdown.patients_with_payments}
          </p>
          <p className="text-sm text-gray-600">Patients with Payments</p>
        </div>
      </div>
    </div>
  );
};
```

## 🛠️ Utility Functions

### API Service Functions
```javascript
// API Service for Dashboard Operations
const dashboardApi = {
  // Get main dashboard statistics
  getStats: async () => {
    const response = await apiRequest('/api/dashboard/stats', 'GET');
    return response.data;
  },

  // Get active patients data
  getActivePatients: async () => {
    const response = await apiRequest('/api/dashboard/active-patients', 'GET');
    return response.data;
  },

  // Get revenue summary
  getRevenueSummary: async (period = '30d') => {
    const response = await apiRequest(`/api/dashboard/revenue-summary?period=${period}`, 'GET');
    return response.data;
  },

  // Get recent invoices
  getRecentInvoices: async (limit = 5) => {
    const response = await apiRequest(`/api/dashboard/recent-invoices?limit=${limit}`, 'GET');
    return response.data;
  }
};

// Utility functions
const dashboardUtils = {
  formatCurrency: (amount) => {
    return new Intl.NumberFormat('rw-RW', {
      style: 'currency',
      currency: 'RWF'
    }).format(amount);
  },

  formatPercentage: (value) => {
    return `${value >= 0 ? '+' : ''}${value}%`;
  },

  getGrowthColor: (growth) => {
    return growth >= 0 ? 'text-green-600' : 'text-red-600';
  },

  getInvoiceStatusColor: (status) => {
    const colors = {
      pending: 'text-yellow-600',
      partially_paid: 'text-blue-600',
      paid: 'text-green-600',
      overdue: 'text-red-600'
    };
    return colors[status] || 'text-gray-600';
  }
};
```

## 🔄 Real-time Updates

### Dashboard Data Refresh Hook
```javascript
const useDashboardRefresh = () => {
  const [lastUpdate, setLastUpdate] = useState(Date.now());
  const [isRefreshing, setIsRefreshing] = useState(false);

  const refreshDashboard = async () => {
    setIsRefreshing(true);
    try {
      // Refresh all dashboard data
      await Promise.all([
        dashboardApi.getStats(),
        dashboardApi.getActivePatients(),
        dashboardApi.getRevenueSummary(),
        dashboardApi.getRecentInvoices()
      ]);
      setLastUpdate(Date.now());
    } catch (error) {
      console.error('Failed to refresh dashboard:', error);
    } finally {
      setIsRefreshing(false);
    }
  };

  // Auto-refresh every 30 seconds
  useEffect(() => {
    const interval = setInterval(refreshDashboard, 30000);
    return () => clearInterval(interval);
  }, []);

  return { refreshDashboard, isRefreshing, lastUpdate };
};
```

## 📱 Mobile Responsive Design

### Mobile Dashboard Layout
```jsx
const MobileDashboard = () => {
  return (
    <div className="space-y-4 p-4">
      {/* Header */}
      <div className="bg-white rounded-lg shadow-md p-4">
        <h2 className="text-lg font-semibold">Dashboard</h2>
        <p className="text-sm text-gray-600">Welcome back!</p>
      </div>

      {/* Stats Grid */}
      <div className="grid grid-cols-2 gap-3">
        <StatCardSmall title="Invoices" value="8" icon="📄" />
        <StatCardSmall title="Revenue" value="4,999 RWF" icon="💰" />
        <StatCardSmall title="Pending" value="7" icon="⏳" />
        <StatCardSmall title="Patients" value="10" icon="👥" />
      </div>

      {/* Recent Activity */}
      <div className="bg-white rounded-lg shadow-md p-4">
        <h3 className="font-medium mb-3">Recent Activity</h3>
        <RecentInvoicesList compact />
      </div>

      {/* Quick Actions */}
      <div className="grid grid-cols-2 gap-3">
        <button className="bg-blue-600 text-white p-3 rounded-lg text-center">
          <div className="text-2xl mb-1">➕</div>
          <div className="text-sm">New Invoice</div>
        </button>
        <button className="bg-green-600 text-white p-3 rounded-lg text-center">
          <div className="text-2xl mb-1">💳</div>
          <div className="text-sm">Process Payment</div>
        </button>
      </div>
    </div>
  );
};
```

## 🚨 Error Handling

### Error Boundary Component
```jsx
class DashboardErrorBoundary extends React.Component {
  constructor(props) {
    super(props);
    this.state = { hasError: false, error: null };
  }

  static getDerivedStateFromError(error) {
    return { hasError: true, error };
  }

  componentDidCatch(error, errorInfo) {
    console.error('Dashboard Error:', error, errorInfo);
  }

  render() {
    if (this.state.hasError) {
      return (
        <div className="bg-red-50 border border-red-200 rounded-lg p-4">
          <h3 className="text-red-800 font-semibold">Dashboard Error</h3>
          <p className="text-red-600">
            Something went wrong loading the dashboard. Please try refreshing the page.
          </p>
          <button
            onClick={() => window.location.reload()}
            className="mt-2 bg-red-600 text-white px-4 py-2 rounded"
          >
            Refresh Page
          </button>
        </div>
      );
    }

    return this.props.children;
  }
}
```

### API Error Handler
```javascript
const handleDashboardError = (error) => {
  if (error.response?.status === 401) {
    // Redirect to login
    window.location.href = '/login';
  } else if (error.response?.status === 403) {
    // Show permission denied message
    alert('You do not have permission to access dashboard data.');
  } else if (error.response?.status >= 500) {
    // Show server error message
    alert('Server error. Please try again later.');
  } else {
    // Show generic error message
    alert('Failed to load dashboard data. Please try again.');
  }
};
```

## 🧪 Testing Examples

### Test Dashboard Endpoints
```javascript
// Test all dashboard endpoints
const testDashboardEndpoints = async () => {
  try {
    console.log('Testing dashboard endpoints...');
    
    // Test main stats
    const stats = await dashboardApi.getStats();
    console.log('✅ Dashboard Stats:', stats);
    
    // Test active patients
    const activePatients = await dashboardApi.getActivePatients();
    console.log('✅ Active Patients:', activePatients);
    
    // Test revenue summary
    const revenueSummary = await dashboardApi.getRevenueSummary();
    console.log('✅ Revenue Summary:', revenueSummary);
    
    // Test recent invoices
    const recentInvoices = await dashboardApi.getRecentInvoices();
    console.log('✅ Recent Invoices:', recentInvoices);
    
    console.log('🎉 All dashboard endpoints working!');
  } catch (error) {
    console.error('❌ Dashboard endpoint test failed:', error);
  }
};
```

### Manual Testing Commands
```bash
# Test dashboard stats
curl -H "Authorization: Bearer YOUR_TOKEN" \
     http://localhost:8000/api/dashboard/stats

# Test active patients
curl -H "Authorization: Bearer YOUR_TOKEN" \
     http://localhost:8000/api/dashboard/active-patients

# Test revenue summary
curl -H "Authorization: Bearer YOUR_TOKEN" \
     http://localhost:8000/api/dashboard/revenue-summary?period=30d

# Test recent invoices
curl -H "Authorization: Bearer YOUR_TOKEN" \
     http://localhost:8000/api/dashboard/recent-invoices?limit=5
```

## 🎯 Quick Start Checklist

### Frontend Implementation:
- [ ] Replace mock data with real API calls
- [ ] Implement dashboard stats component
- [ ] Add revenue charts and analytics
- [ ] Create recent invoices list
- [ ] Add active patients statistics
- [ ] Implement real-time data refresh
- [ ] Add error handling and loading states
- [ ] Optimize for mobile devices
- [ ] Test all endpoints with authentication

### API Integration:
- [ ] Add JWT authentication headers
- [ ] Handle 401/403 errors gracefully
- [ ] Implement request/response logging
- [ ] Add retry mechanisms for failed requests
- [ ] Cache dashboard data for performance

## 🚀 Ready for Production

All dashboard endpoints are:
- ✅ **Implemented and tested** with real data
- ✅ **Authenticated** with JWT token protection
- ✅ **Error handled** with proper HTTP status codes
- ✅ **Optimized** with efficient database queries
- ✅ **Documented** with complete examples

**The frontend team can immediately integrate these endpoints to replace mock data with real dashboard statistics!**

---

*This documentation provides everything needed to implement a complete, real-time dashboard for the efiche billing system.*
