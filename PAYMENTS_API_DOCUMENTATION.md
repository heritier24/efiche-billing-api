# 📋 Payments Backend API Documentation

## 🎯 Overview

This document provides comprehensive API documentation for the **Payments Backend API** that powers the payment CRUD functionality for the eFiche billing system. All endpoints are secured with JWT authentication and follow RESTful conventions.

## 🔐 Authentication

All API endpoints require authentication using Laravel Sanctum JWT tokens.

**Login Endpoint**:
```http
POST /api/auth/login
Content-Type: application/json

{
  "email": "admin@efiche.rw",
  "password": "password123"
}
```

**Response**:
```json
{
  "success": true,
  "message": "Login successful",
  "token": "1|abc123...",
  "user": {
    "id": 1,
    "name": "Admin User",
    "email": "admin@efiche.rw",
    "role": "admin",
    "facility_id": 1
  }
}
```

**Authorization Header**:
```
Authorization: Bearer {token}
```

---

## 📊 Payment Statistics

### Get Payment Summary

**Endpoint**: `GET /api/payments/summary`

**Description**: Retrieves comprehensive payment statistics for dashboard display

**Headers**:
- `Authorization: Bearer {token}`

**Response**:
```json
{
  "success": true,
  "data": {
    "total_payments": 150,
    "completed_payments": 120,
    "total_revenue": 2500000,
    "pending_amount": 300000,
    "payment_methods_breakdown": {
      "cash": 80,
      "mobile_money": 50,
      "insurance": 20
    },
    "monthly_stats": {
      "current_month": {
        "payments": 25,
        "revenue": 500000
      },
      "previous_month": {
        "payments": 20,
        "revenue": 400000
      }
    }
  }
}
```

**Frontend Integration**:
```typescript
interface PaymentSummary {
  total_payments: number;
  completed_payments: number;
  total_revenue: number;
  pending_amount: number;
  payment_methods_breakdown: {
    cash: number;
    mobile_money: number;
    insurance: number;
  };
  monthly_stats: {
    current_month: {
      payments: number;
      revenue: number;
    };
    previous_month: {
      payments: number;
      revenue: number;
    };
  };
}

async function getPaymentSummary(): Promise<PaymentSummary> {
  const response = await fetch('/api/payments/summary', {
    headers: {
      'Authorization': `Bearer ${token}`,
      'Content-Type': 'application/json'
    }
  });
  
  const data = await response.json();
  return data.data;
}
```

---

## 📋 Payment List & Filtering

### Get All Payments

**Endpoint**: `GET /api/payments`

**Description**: Retrieves paginated list of payments with filtering and search capabilities

**Headers**:
- `Authorization: Bearer {token}`

**Query Parameters**:
- `search` (optional): Search by invoice ID, patient name, or transaction reference
- `status` (optional): Filter by status (`pending`, `confirmed`, `failed`)
- `method` (optional): Filter by payment method (`cash`, `mobile_money`, `insurance`)
- `page` (optional): Page number (default: 1)
- `limit` (optional): Items per page (default: 20, max: 100)

**Examples**:
```http
GET /api/payments
GET /api/payments?status=confirmed&limit=10
GET /api/payments?method=cash&page=2
GET /api/payments?search=INV-2024-001
GET /api/payments?status=pending&method=mobile_money&search=+250
```

**Response**:
```json
{
  "success": true,
  "data": [
    {
      "id": 1,
      "invoice_id": 123,
      "amount": 50000,
      "method": "mobile_money",
      "status": "confirmed",
      "transaction_ref": "PAY-ABC123-1640995200",
      "phone": "+250788123456",
      "notes": "Payment for consultation",
      "created_at": "2024-01-15T10:30:00Z",
      "updated_at": "2024-01-15T10:35:00Z",
      "confirmed_at": "2024-01-15T10:35:00Z",
      "cashier_id": 1,
      "invoice": {
        "id": 123,
        "invoice_number": "INV-2024-001",
        "visit": {
          "patient": {
            "first_name": "John",
            "last_name": "Doe"
          }
        }
      }
    }
  ],
  "total": 150,
  "per_page": 20,
  "current_page": 1,
  "last_page": 8
}
```

**Frontend Integration**:
```typescript
interface Payment {
  id: number;
  invoice_id: number;
  amount: number;
  method: 'cash' | 'mobile_money' | 'insurance';
  status: 'pending' | 'confirmed' | 'failed';
  transaction_ref?: string;
  phone?: string;
  notes?: string;
  created_at: string;
  updated_at: string;
  confirmed_at?: string;
  cashier_id: number;
  invoice?: {
    id: number;
    invoice_number: string;
    visit: {
      patient: {
        first_name: string;
        last_name: string;
      };
    };
  };
}

interface PaymentListResponse {
  data: Payment[];
  total: number;
  per_page: number;
  current_page: number;
  last_page: number;
}

async function getPayments(params: {
  search?: string;
  status?: 'pending' | 'confirmed' | 'failed';
  method?: 'cash' | 'mobile_money' | 'insurance';
  page?: number;
  limit?: number;
}): Promise<PaymentListResponse> {
  const queryParams = new URLSearchParams();
  Object.entries(params).forEach(([key, value]) => {
    if (value !== undefined) queryParams.append(key, value.toString());
  });
  
  const response = await fetch(`/api/payments?${queryParams}`, {
    headers: {
      'Authorization': `Bearer ${token}`,
      'Content-Type': 'application/json'
    }
  });
  
  return await response.json();
}
```

---

## 📄 Pending Invoices

### Get Pending Invoices

**Endpoint**: `GET /api/invoices/pending`

**Description**: Retrieves list of invoices with remaining balance for payment recording

**Headers**:
- `Authorization: Bearer {token}`

**Response**:
```json
{
  "success": true,
  "data": [
    {
      "id": 123,
      "invoice_number": "INV-2024-001",
      "patient_name": "John Doe",
      "total_amount": 100000,
      "remaining_balance": 50000,
      "created_at": "2024-01-15T09:00:00Z",
      "status": "partially_paid"
    }
  ]
}
```

**Frontend Integration**:
```typescript
interface PendingInvoice {
  id: number;
  invoice_number: string;
  patient_name: string;
  total_amount: number;
  remaining_balance: number;
  created_at: string;
  status: 'pending' | 'partially_paid';
}

async function getPendingInvoices(): Promise<PendingInvoice[]> {
  const response = await fetch('/api/invoices/pending', {
    headers: {
      'Authorization': `Bearer ${token}`,
      'Content-Type': 'application/json'
    }
  });
  
  const data = await response.json();
  return data.data;
}
```

---

## 💳 Process Payment

### Create Payment

**Endpoint**: `POST /api/invoices/{invoiceId}/payments`

**Description**: Process a new payment for an invoice

**Headers**:
- `Authorization: Bearer {token}`
- `Content-Type: application/json`

**Request Body**:
```json
{
  "amount": "50000.00",
  "method": "mobile_money",
  "phone": "+250788123456",
  "notes": "Partial payment for consultation"
}
```

**Payment Methods**:
- `cash`: Immediate confirmation
- `mobile_money`: Requires Rwanda phone number (+2507xxxxxxxx)
- `insurance`: Future enhancement

**Response**:
```json
{
  "success": true,
  "message": "Payment processed successfully",
  "data": {
    "id": 456,
    "invoice_id": 123,
    "amount": 50000,
    "method": "mobile_money",
    "status": "pending",
    "transaction_ref": "PAY-ABC123-1640995200",
    "phone": "+250788123456",
    "notes": "Partial payment for consultation",
    "created_at": "2024-01-15T10:30:00Z",
    "updated_at": "2024-01-15T10:30:00Z",
    "cashier_id": 1
  },
  "invoice_status": "paid",
  "remaining_balance": 0
}
```

**Frontend Integration**:
```typescript
interface PaymentRequest {
  amount: string;
  method: 'cash' | 'mobile_money' | 'insurance';
  phone?: string;
  notes?: string;
}

interface PaymentResponse {
  success: boolean;
  message: string;
  data: Payment;
  invoice_status: string;
  remaining_balance: number;
}

async function processPayment(invoiceId: number, paymentData: PaymentRequest): Promise<PaymentResponse> {
  const response = await fetch(`/api/invoices/${invoiceId}/payments`, {
    method: 'POST',
    headers: {
      'Authorization': `Bearer ${token}`,
      'Content-Type': 'application/json'
    },
    body: JSON.stringify(paymentData)
  });
  
  return await response.json();
}
```

---

## 🔄 Update Payment Status

### Update Status

**Endpoint**: `PUT /api/payments/{paymentId}/status`

**Description**: Update the status of a payment

**Headers**:
- `Authorization: Bearer {token}`
- `Content-Type: application/json`

**Request Body**:
```json
{
  "status": "confirmed"
}
```

**Valid Statuses**:
- `pending`: Payment awaiting confirmation
- `confirmed`: Payment successfully completed
- `failed`: Payment failed or rejected

**Response**:
```json
{
  "success": true,
  "message": "Payment status updated successfully",
  "data": {
    "id": 456,
    "status": "confirmed",
    "confirmed_at": "2024-01-15T10:35:00Z",
    "updated_at": "2024-01-15T10:35:00Z"
  }
}
```

**Frontend Integration**:
```typescript
async function updatePaymentStatus(paymentId: number, status: 'pending' | 'confirmed' | 'failed'): Promise<any> {
  const response = await fetch(`/api/payments/${paymentId}/status`, {
    method: 'PUT',
    headers: {
      'Authorization': `Bearer ${token}`,
      'Content-Type': 'application/json'
    },
    body: JSON.stringify({ status })
  });
  
  return await response.json();
}
```

---

## 🗑️ Delete Payment

### Delete Payment

**Endpoint**: `DELETE /api/payments/{paymentId}`

**Description**: Delete a pending payment (only pending payments can be deleted)

**Headers**:
- `Authorization: Bearer {token}`

**Response**:
```json
{
  "success": true,
  "message": "Payment deleted successfully"
}
```

**Frontend Integration**:
```typescript
async function deletePayment(paymentId: number): Promise<any> {
  const response = await fetch(`/api/payments/${paymentId}`, {
    method: 'DELETE',
    headers: {
      'Authorization': `Bearer ${token}`,
      'Content-Type': 'application/json'
    }
  });
  
  return await response.json();
}
```

---

## 🔁 Retry Failed Payment

### Retry Payment

**Endpoint**: `POST /api/payments/{paymentId}/retry`

**Description**: Retry a failed payment (only failed payments can be retried)

**Headers**:
- `Authorization: Bearer {token}`

**Response**:
```json
{
  "success": true,
  "message": "Payment retry initiated",
  "data": {
    "id": 456,
    "status": "pending",
    "transaction_ref": "PAY-XYZ789-1640995300",
    "updated_at": "2024-01-15T11:00:00Z"
  }
}
```

**Frontend Integration**:
```typescript
async function retryPayment(paymentId: number): Promise<any> {
  const response = await fetch(`/api/payments/${paymentId}/retry`, {
    method: 'POST',
    headers: {
      'Authorization': `Bearer ${token}`,
      'Content-Type': 'application/json'
    }
  });
  
  return await response.json();
}
```

---

## 📊 Payment Status

### Get Payment Status

**Endpoint**: `GET /api/payments/{paymentId}/status`

**Description**: Get the current status of a specific payment

**Headers**:
- `Authorization: Bearer {token}`

**Response**:
```json
{
  "id": 456,
  "status": "confirmed",
  "confirmed_at": "2024-01-15T10:35:00Z",
  "transaction_ref": "PAY-ABC123-1640995200"
}
```

**Frontend Integration**:
```typescript
interface PaymentStatus {
  id: number;
  status: 'pending' | 'confirmed' | 'failed';
  confirmed_at?: string;
  transaction_ref?: string;
}

async function getPaymentStatus(paymentId: number): Promise<PaymentStatus> {
  const response = await fetch(`/api/payments/${paymentId}/status`, {
    headers: {
      'Authorization': `Bearer ${token}`,
      'Content-Type': 'application/json'
    }
  });
  
  return await response.json();
}
```

---

## 🚨 Error Handling

### Common Error Responses

**Validation Error (422)**:
```json
{
  "success": false,
  "message": "Validation failed",
  "errors": {
    "amount": ["The amount field is required."],
    "method": ["The selected method is invalid."]
  }
}
```

**Not Found (404)**:
```json
{
  "success": false,
  "error": "Payment not found",
  "message": "Payment with ID: 999 does not exist"
}
```

**Access Denied (403)**:
```json
{
  "success": false,
  "message": "Access denied",
  "errors": {
    "invoice": ["You can only process payments for invoices from your facility"]
  }
}
```

**Payment Amount Error (422)**:
```json
{
  "success": false,
  "message": "Payment amount exceeds remaining balance",
  "errors": {
    "amount": ["Payment amount (RWF 50000) exceeds remaining balance (RWF 30000)"],
    "remaining_balance": [30000]
  }
}
```

**Frontend Error Handling**:
```typescript
interface ApiError {
  success: false;
  message: string;
  errors?: Record<string, string[]>;
}

async function handleApiCall<T>(apiCall: () => Promise<T>): Promise<T> {
  try {
    const response = await apiCall();
    
    if (!response.ok) {
      const errorData: ApiError = await response.json();
      throw new Error(errorData.message || 'Request failed');
    }
    
    return await response.json();
  } catch (error) {
    console.error('API Error:', error);
    throw error;
  }
}
```

---

## 🔄 Real-time Updates

### Payment Status Polling

For mobile money payments, implement polling to check for status updates:

```typescript
async function pollPaymentStatus(paymentId: number, maxAttempts = 10): Promise<PaymentStatus> {
  for (let attempt = 1; attempt <= maxAttempts; attempt++) {
    const status = await getPaymentStatus(paymentId);
    
    if (status.status === 'confirmed' || status.status === 'failed') {
      return status;
    }
    
    // Wait 3 seconds before next attempt
    await new Promise(resolve => setTimeout(resolve, 3000));
  }
  
  throw new Error('Payment status polling timeout');
}
```

---

## 📱 Mobile Money Integration

### Rwanda Phone Validation

Mobile money payments require valid Rwanda phone numbers:

```typescript
function validateRwandaPhone(phone: string): boolean {
  const rwandaPhoneRegex = /^\+2507\d{8}$/;
  return rwandaPhoneRegex.test(phone);
}

// Example valid numbers:
// +250788123456
// +250789123456
// +250787123456
```

### Mock Implementation

The API includes a mock implementation for mobile money payments in local/testing environments. In production, this will integrate with the real eFichePay API.

---

## 🔧 Testing

### Test Script

Run the comprehensive test script to validate all endpoints:

```bash
php test_payments_backend_api.php
```

The test script covers:
- Authentication
- All 8 required endpoints
- Filtering and search
- Error handling
- Payment processing
- Status updates and retries

---

## 🚀 Quick Start Guide

### 1. Authentication
```typescript
const token = await login('admin@efiche.rw', 'password123');
```

### 2. Get Payment Summary
```typescript
const summary = await getPaymentSummary();
```

### 3. Get Pending Invoices
```typescript
const pendingInvoices = await getPendingInvoices();
```

### 4. Process Payment
```typescript
const payment = await processPayment(invoiceId, {
  amount: '50000.00',
  method: 'cash',
  notes: 'Cash payment'
});
```

### 5. List Payments with Filters
```typescript
const payments = await getPayments({
  status: 'confirmed',
  method: 'cash',
  page: 1,
  limit: 20
});
```

---

## 📋 TypeScript Interfaces

Copy these interfaces for your frontend:

```typescript
// Core Interfaces
interface Payment {
  id: number;
  invoice_id: number;
  amount: number;
  method: 'cash' | 'mobile_money' | 'insurance';
  status: 'pending' | 'confirmed' | 'failed';
  transaction_ref?: string;
  phone?: string;
  notes?: string;
  created_at: string;
  updated_at: string;
  confirmed_at?: string;
  cashier_id: number;
}

interface PendingInvoice {
  id: number;
  invoice_number: string;
  patient_name: string;
  total_amount: number;
  remaining_balance: number;
  created_at: string;
  status: 'pending' | 'partially_paid';
}

interface PaymentSummary {
  total_payments: number;
  completed_payments: number;
  total_revenue: number;
  pending_amount: number;
  payment_methods_breakdown: {
    cash: number;
    mobile_money: number;
    insurance: number;
  };
  monthly_stats: {
    current_month: {
      payments: number;
      revenue: number;
    };
    previous_month: {
      payments: number;
      revenue: number;
    };
  };
}

// Request/Response Types
interface PaymentRequest {
  amount: string;
  method: 'cash' | 'mobile_money' | 'insurance';
  phone?: string;
  notes?: string;
}

interface PaymentListResponse {
  success: boolean;
  data: Payment[];
  total: number;
  per_page: number;
  current_page: number;
  last_page: number;
}
```

---

## 🎯 Best Practices

### 1. Error Handling
- Always check the `success` field in responses
- Handle validation errors gracefully
- Show user-friendly error messages

### 2. Loading States
- Show loading indicators during API calls
- Implement optimistic updates where appropriate

### 3. Real-time Updates
- Poll payment status for mobile money payments
- Update UI when payment status changes

### 4. Validation
- Validate phone numbers using Rwanda format
- Check payment amounts against remaining balance
- Validate payment method selection

### 5. Security
- Store JWT tokens securely
- Handle token expiration gracefully
- Implement proper logout functionality

---

**🇷🇼 This Payments API is production-ready for Rwanda's healthcare system! 🏥💼**

For any questions or issues, refer to the test script or contact the backend development team.
