# 🚀 eFiche Billing API - Complete Frontend Integration Guide

## 📋 Overview

This document provides **complete API documentation** for the eFiche billing system frontend integration. All endpoints are **implemented and tested** with proper authentication, validation, and error handling.

> **🎯 Status: PRODUCTION READY** - All APIs implemented with comprehensive testing

---

## 🔐 Authentication System

### **POST /api/auth/login**
User authentication with JWT tokens.

**Request:**
```json
{
  "email": "admin@efiche.rw",
  "password": "password123"
}
```

**Response:**
```json
{
  "success": true,
  "token": "eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9...",
  "user": {
    "id": 1,
    "name": "Admin User",
    "email": "admin@efiche.rw",
    "role": "admin",
    "avatar": null
  },
  "expires_in": 3600
}
```

**Test Users:**
- **Admin**: `admin@efiche.rw` / `password123`
- **Cashier**: `cashier@efiche.rw` / `password123`
- **Staff**: `staff@efiche.rw` / `password123`

---

### **POST /api/auth/logout**
Logout and invalidate current token.

**Headers:** `Authorization: Bearer {token}`

**Response:**
```json
{
  "success": true,
  "message": "Logged out successfully"
}
```

---

### **POST /api/auth/register**
Register new user (admin only).

**Headers:** `Authorization: Bearer {token}`

**Request:**
```json
{
  "name": "Jane Smith",
  "email": "jane@example.com",
  "password": "password123",
  "password_confirmation": "password123",
  "role": "staff"
}
```

**Response:**
```json
{
  "success": true,
  "message": "User registered successfully",
  "user": {
    "id": 2,
    "name": "Jane Smith",
    "email": "jane@example.com",
    "role": "staff"
  }
}
```

---

### **GET /api/auth/me**
Get current authenticated user details.

**Headers:** `Authorization: Bearer {token}`

**Response:**
```json
{
  "id": 1,
  "name": "Admin User",
  "email": "admin@efiche.rw",
  "role": "admin",
  "avatar": null,
  "created_at": "2024-04-26T10:00:00Z",
  "last_login": "2024-04-26T11:30:00Z"
}
```

---

## 📊 Dashboard APIs

### **GET /api/dashboard/stats**
Dashboard statistics overview.

**Headers:** `Authorization: Bearer {token}`

**Response:**
```json
{
  "total_invoices": 156,
  "total_revenue": 12400000.00,
  "pending_payments": 8,
  "total_patients": 150,
  "today_visits": 12,
  "monthly_growth": 12.5
}
```

---

### **GET /api/dashboard/recent-invoices**
Recent invoices for dashboard display.

**Headers:** `Authorization: Bearer {token}`

**Query Parameters:** `?limit=10&status=pending`

**Response:**
```json
{
  "data": [
    {
      "id": 123,
      "invoice_number": "INV-2024-001",
      "patient_name": "John Doe",
      "total_amount": 50000.00,
      "status": "pending",
      "created_at": "2024-04-26T10:30:00Z",
      "due_date": "2024-05-03T23:59:59Z"
    }
  ],
  "total": 25,
  "unread_count": 5
}
```

---

### **GET /api/dashboard/upcoming-payments**
Upcoming payment reminders.

**Headers:** `Authorization: Bearer {token}`

**Response:**
```json
{
  "data": [
    {
      "id": 123,
      "invoice_number": "INV-2024-001",
      "patient_name": "John Doe",
      "amount_due": 15000.00,
      "due_date": "2024-04-28T23:59:59Z",
      "days_overdue": 2,
      "patient_phone": "+250788123456"
    }
  ],
  "total_overdue": 8,
  "total_due_this_week": 15
}
```

---

### **GET /api/dashboard/monthly-revenue**
Monthly revenue trends.

**Headers:** `Authorization: Bearer {token}`

**Query Parameters:** `?months=12`

**Response:**
```json
{
  "data": [
    {
      "month": "2024-01",
      "revenue": 2500000.00,
      "invoices_count": 45,
      "growth_rate": 12.5
    }
  ],
  "current_month": {
    "month": "2024-04",
    "revenue": 3200000.00,
    "invoices_count": 62,
    "growth_rate": 8.3
  },
  "year_total": 12400000.00
}
```

---

## 👥 Patient Management

### **GET /api/patients**
List patients with search and filters.

**Headers:** `Authorization: Bearer {token}`

**Query Parameters:** `?search=john&status=active&page=1&limit=20`

**Response:**
```json
{
  "data": [
    {
      "id": 1,
      "name": "John Doe",
      "email": "john@example.com",
      "phone": "+250788123456",
      "date_of_birth": "1985-06-15",
      "gender": "male",
      "address": "Kigali, Kicukiro, KG 123 Ave",
      "insurance_name": "RSSB",
      "registration_date": "2024-01-15",
      "last_visit_date": "2024-04-20",
      "total_visits": 12,
      "total_billed": 450000.00,
      "total_paid": 380000.00,
      "outstanding_balance": 70000.00,
      "status": "active"
    }
  ],
  "total": 150,
  "per_page": 20,
  "current_page": 1,
  "last_page": 8
}
```

---

### **POST /api/patients**
Create new patient.

**Headers:** `Authorization: Bearer {token}`

**Request:**
```json
{
  "first_name": "Jane",
  "last_name": "Smith",
  "email": "jane@example.com",
  "phone": "+250733987654",
  "date_of_birth": "1990-03-22",
  "gender": "female",
  "address": "Kigali, Gasabo, KN 456 St"
}
```

**Response:**
```json
{
  "success": true,
  "data": {
    "id": 151,
    "first_name": "Jane",
    "last_name": "Smith",
    "email": "jane@example.com",
    "phone": "+250733987654",
    "date_of_birth": "1990-03-22",
    "gender": "female",
    "address": "Kigali, Gasabo, KN 456 St",
    "registration_date": "2024-04-26",
    "status": "active"
  }
}
```

---

### **GET /api/patients/{id}**
Get patient details.

**Headers:** `Authorization: Bearer {token}`

**Response:**
```json
{
  "id": 1,
  "first_name": "John",
  "last_name": "Doe",
  "email": "john@example.com",
  "phone": "+250788123456",
  "date_of_birth": "1985-06-15",
  "gender": "male",
  "address": "Kigali, Kicukiro, KG 123 Ave",
  "registration_date": "2024-01-15",
  "last_visit_date": "2024-04-20",
  "total_visits": 12,
  "total_billed": 450000.00,
  "total_paid": 380000.00,
  "outstanding_balance": 70000.00,
  "status": "active",
  "visits": [
    {
      "id": 123,
      "visit_date": "2024-04-20",
      "visit_type": "consultation",
      "status": "completed"
    }
  ]
}
```

---

### **PUT /api/patients/{id}**
Update patient information.

**Headers:** `Authorization: Bearer {token}`

**Request:**
```json
{
  "first_name": "John",
  "last_name": "Doe Updated",
  "email": "john.doe@example.com",
  "phone": "+250788123456",
  "address": "Kigali, Nyarugenge, KK 789 St"
}
```

**Response:**
```json
{
  "success": true,
  "data": {
    "id": 1,
    "first_name": "John",
    "last_name": "Doe Updated",
    "email": "john.doe@example.com",
    "phone": "+250788123456",
    "address": "Kigali, Nyarugenge, KK 789 St",
    "updated_at": "2024-04-26T11:00:00Z"
  }
}
```

---

### **GET /api/patients/{id}/visits**
Patient visit history.

**Headers:** `Authorization: Bearer {token}`

**Response:**
```json
{
  "data": [
    {
      "id": 123,
      "visit_date": "2024-04-20",
      "visit_type": "consultation",
      "status": "completed",
      "invoice_id": 456,
      "invoice_number": "INV-2024-001",
      "total_amount": 50000.00,
      "paid_amount": 35000.00
    }
  ],
  "total": 12,
  "last_visit": "2024-04-20"
}
```

---

## 📋 Invoice Management

### **GET /api/invoices**
List invoices with advanced filtering.

**Headers:** `Authorization: Bearer {token}`

**Query Parameters:** `?search=john&status=pending&date_from=2024-04-01&date_to=2024-04-30&page=1&limit=20`

**Response:**
```json
{
  "data": [
    {
      "id": 123,
      "invoice_number": "INV-2024-001",
      "patient_name": "John Doe",
      "visit_id": 456,
      "invoice_date": "2024-04-20",
      "due_date": "2024-04-27",
      "status": "pending",
      "total_amount": 50000.00,
      "amount_paid": 0.00,
      "remaining_balance": 50000.00,
      "line_items_count": 3,
      "last_payment_date": null
    }
  ],
  "total": 156,
  "per_page": 20,
  "current_page": 1,
  "last_page": 8
}
```

---

### **GET /api/invoices/{id}**
Get invoice details.

**Headers:** `Authorization: Bearer {token}`

**Response:**
```json
{
  "id": 123,
  "invoice_number": "INV-2024-001",
  "visit_id": 456,
  "status": "pending",
  "total_amount": 50000.00,
  "insurance_coverage": 40000.00,
  "patient_responsibility": 10000.00,
  "total_paid": 0.00,
  "remaining_balance": 10000.00,
  "due_date": "2024-04-27",
  "created_at": "2024-04-20T10:30:00Z",
  "line_items": [
    {
      "id": 1,
      "item_code": "CONS-001",
      "description": "General Consultation",
      "quantity": 1,
      "unit_price": 15000.00,
      "total_price": 15000.00
    }
  ],
  "payments": [],
  "visit": {
    "id": 456,
    "patient": {
      "id": 1,
      "full_name": "John Doe",
      "phone": "+250788123456"
    },
    "facility": {
      "id": 1,
      "name": "King Faisal Hospital"
    }
  }
}
```

---

### **GET /api/visits/{visitId}/invoice**
Get invoice by visit ID.

**Headers:** `Authorization: Bearer {token}`

**Response:**
```json
{
  "id": 123,
  "invoice_number": "INV-2024-001",
  "visit_id": 456,
  "status": "pending",
  "total_amount": 50000.00,
  "insurance_coverage": 40000.00,
  "patient_responsibility": 10000.00,
  "total_paid": 0.00,
  "remaining_balance": 10000.00,
  "due_date": "2024-04-27",
  "line_items": [...],
  "visit": {
    "patient": {
      "full_name": "John Doe",
      "phone": "+250788123456"
    }
  }
}
```

---

## 💳 Payment Management

### **POST /api/invoices/{invoiceId}/payments**
Process payment for invoice.

**Headers:** `Authorization: Bearer {token}`

**Request:**
```json
{
  "amount": 10000.00,
  "method": "mobile_money",
  "phone": "+250788123456",
  "notes": "Mobile money payment"
}
```

**Response:**
```json
{
  "id": 789,
  "invoice_id": 123,
  "amount": 10000.00,
  "method": "mobile_money",
  "status": "pending",
  "transaction_ref": "MTN-20240426103500-ABC123",
  "processed_at": "2024-04-26T10:35:00Z",
  "notes": "Mobile money payment"
}
```

---

### **GET /api/payments**
List payments with filtering.

**Headers:** `Authorization: Bearer {token}`

**Query Parameters:** `?search=john&method=mobile_money&status=confirmed&date_from=2024-04-01&page=1&limit=20`

**Response:**
```json
{
  "data": [
    {
      "id": 789,
      "invoice_id": 123,
      "invoice_number": "INV-2024-001",
      "patient_name": "John Doe",
      "amount": 10000.00,
      "method": "mobile_money",
      "status": "confirmed",
      "transaction_ref": "MTN-20240426103500-ABC123",
      "processed_at": "2024-04-26T10:35:00Z",
      "confirmed_at": "2024-04-26T10:36:00Z",
      "notes": "Mobile money payment"
    }
  ],
  "total": 180,
  "per_page": 20,
  "current_page": 1,
  "last_page": 9
}
```

---

### **GET /api/payments/{paymentId}/status**
Get payment status.

**Headers:** `Authorization: Bearer {token}`

**Response:**
```json
{
  "id": 789,
  "status": "confirmed",
  "confirmed_at": "2024-04-26T10:36:00Z",
  "transaction_ref": "MTN-20240426103500-ABC123"
}
```

---

### **GET /api/payments/{id}**
Get payment details.

**Headers:** `Authorization: Bearer {token}`

**Response:**
```json
{
  "id": 789,
  "invoice_id": 123,
  "amount": 10000.00,
  "method": "mobile_money",
  "status": "confirmed",
  "transaction_ref": "MTN-20240426103500-ABC123",
  "processed_at": "2024-04-26T10:35:00Z",
  "confirmed_at": "2024-04-26T10:36:00Z",
  "cashier_id": 2,
  "notes": "Mobile money payment",
  "invoice": {
    "id": 123,
    "invoice_number": "INV-2024-001",
    "patient_name": "John Doe"
  }
}
```

---

## 🏥 Facility & Insurance

### **GET /api/facilities/{facilityId}/insurances**
Get facility insurance providers.

**Headers:** `Authorization: Bearer {token}`

**Response:**
```json
{
  "data": [
    {
      "id": 1,
      "name": "Rwanda Social Security Board",
      "code": "RSSB",
      "coverage_percentage": 80.0,
      "max_claim_amount": 500000.00,
      "requires_preauth": true,
      "is_active": true
    },
    {
      "id": 2,
      "name": "Mutuelle de Santé",
      "code": "MMI",
      "coverage_percentage": 70.0,
      "max_claim_amount": 300000.00,
      "requires_preauth": false,
      "is_active": true
    }
  ]
}
```

---

## 🌐 Webhooks

### **POST /api/webhooks/efichepay**
Mobile money payment webhook (public endpoint).

**Request Headers:** `X-EfichePay-Signature: {signature}`

**Request Body:**
```json
{
  "event_id": "evt_123456789",
  "event_type": "payment.confirmed",
  "payment_id": 789,
  "transaction_ref": "MTN-20240426103500-ABC123",
  "status": "confirmed",
  "amount": 10000.00,
  "confirmed_at": "2024-04-26T10:36:00Z"
}
```

**Response:**
```json
{
  "status": "ok",
  "event_id": "evt_123456789",
  "processed": true
}
```

---

## 🔧 Response Format Standards

### **Success Response**
```json
{
  "success": true,
  "data": {...},
  "message": "Operation completed successfully"
}
```

### **Error Response**
```json
{
  "success": false,
  "error": "Validation failed",
  "message": "The given data was invalid.",
  "errors": {
    "email": ["The email field is required."]
  }
}
```

### **Pagination Response**
```json
{
  "data": [...],
  "total": 150,
  "per_page": 20,
  "current_page": 1,
  "last_page": 8
}
```

---

## 🚨 HTTP Status Codes

- **200** - Success
- **201** - Created
- **400** - Bad Request
- **401** - Unauthorized
- **403** - Forbidden
- **404** - Not Found
- **422** - Validation Error
- **500** - Server Error

---

## 🇷🇼 Rwanda-Specific Requirements

### **Currency**
- All amounts in **RWF** (Rwandan Franc)
- Decimal format with 2 places: `50000.00`

### **Phone Numbers**
- Format: `+2507xxxxxxxx`
- Validation regex: `/^\+2507\d{8}$/`

### **Date Format**
- ISO 8601 UTC: `2024-04-26T10:30:00Z`

### **Insurance Providers**
- **RSSB** - Rwanda Social Security Board
- **MMI** - Mutuelle de Santé
- **MediCare Rwanda**
- **Prime Insurance**

---

## 🧪 Quick Testing

### **1. Login Test**
```bash
curl -X POST http://localhost:8000/api/auth/login \
  -H "Content-Type: application/json" \
  -d '{"email":"admin@efiche.rw","password":"password123"}'
```

### **2. Get Dashboard Stats**
```bash
curl -X GET http://localhost:8000/api/dashboard/stats \
  -H "Authorization: Bearer {your_token}"
```

### **3. List Patients**
```bash
curl -X GET http://localhost:8000/api/patients \
  -H "Authorization: Bearer {your_token}"
```

### **4. Create Payment**
```bash
curl -X POST http://localhost:8000/api/invoices/1/payments \
  -H "Content-Type: application/json" \
  -H "Authorization: Bearer {your_token}" \
  -d '{"amount":10000.00,"method":"mobile_money","phone":"+250788123456"}'
```

---

## 🚀 Getting Started

### **1. Start Development Server**
```bash
php artisan serve
# Server running on http://localhost:8000
```

### **2. Login to Get Token**
Use the test credentials to get your authentication token.

### **3. Make API Calls**
Include the token in the `Authorization: Bearer {token}` header.

### **4. Test Endpoints**
Use the examples above or test with Postman/Insomnia.

---

## 📞 Support

For any issues or questions:
- **Backend Status**: ✅ Production Ready
- **All Endpoints**: ✅ Implemented & Tested
- **Authentication**: ✅ Working
- **Database**: ✅ Seeded with Test Data

**🎯 The eFiche billing API is ready for frontend integration!** 🇷🇼💼

---

## 📝 Implementation Notes

### **Security Features**
- ✅ JWT token authentication
- ✅ Role-based access control
- ✅ Input validation & sanitization
- ✅ SQL injection protection
- ✅ Rate limiting ready

### **Performance Features**
- ✅ Database query optimization
- ✅ Concurrency protection
- ✅ Efficient pagination
- ✅ Response caching ready

### **Integration Ready**
- ✅ RESTful API design
- ✅ Consistent response format
- ✅ Comprehensive error handling
- ✅ Full test coverage

**🚀 Your frontend is ready to integrate with this complete API!**
