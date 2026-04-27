# 📋 Visit Management Backend API Documentation

## 🎯 Overview

This document provides comprehensive API documentation for the **Visit Management Backend API** that supports patient visit tracking and invoice creation. The visit management system is a critical component that links patients to their medical encounters, which are then billed through invoices.

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

**Authorization Header**:
```
Authorization: Bearer {token}
```

---

## 📋 List Patient Visits

### Get Visits with Filtering

**Endpoint**: `GET /api/visits`

**Description**: Retrieves paginated list of visits with filtering capabilities

**Headers**:
- `Authorization: Bearer {token}`

**Query Parameters**:
- `patient_id` (optional): Filter by patient ID
- `status` (optional): Filter by status (`active`, `completed`, `cancelled`)
- `visit_type` (optional): Filter by visit type (`consultation`, `follow_up`, `emergency`, `general`)
- `page` (optional): Page number (default: 1)
- `limit` (optional): Items per page (default: 50, max: 100)

**Examples**:
```http
GET /api/visits
GET /api/visits?patient_id=123&status=active
GET /api/visits?visit_type=consultation&limit=10
GET /api/visits?status=completed&page=2
```

**Response**:
```json
{
  "success": true,
  "data": [
    {
      "id": 1,
      "patient_id": 123,
      "facility_id": 1,
      "visit_type": "consultation",
      "status": "active",
      "notes": "Initial consultation",
      "created_at": "2024-01-15T10:30:00Z",
      "updated_at": "2024-01-15T10:30:00Z",
      "patient": {
        "id": 123,
        "full_name": "John Doe",
        "first_name": "John",
        "last_name": "Doe",
        "phone": "+250788123456",
        "email": "john.doe@example.com"
      }
    }
  ],
  "total": 25,
  "page": 1,
  "limit": 50
}
```

**Frontend Integration**:
```typescript
interface Visit {
  id: number;
  patient_id: number;
  facility_id: number;
  visit_type: 'consultation' | 'follow_up' | 'emergency' | 'general';
  status: 'active' | 'completed' | 'cancelled';
  notes?: string;
  created_at: string;
  updated_at: string;
  patient?: {
    id: number;
    full_name: string;
    first_name: string;
    last_name: string;
    phone?: string;
    email?: string;
  };
}

interface VisitListResponse {
  success: boolean;
  data: Visit[];
  total: number;
  page: number;
  limit: number;
}

async function getVisits(params: {
  patient_id?: number;
  status?: 'active' | 'completed' | 'cancelled';
  visit_type?: 'consultation' | 'follow_up' | 'emergency' | 'general';
  page?: number;
  limit?: number;
}): Promise<VisitListResponse> {
  const queryParams = new URLSearchParams();
  Object.entries(params).forEach(([key, value]) => {
    if (value !== undefined) queryParams.append(key, value.toString());
  });
  
  const response = await fetch(`/api/visits?${queryParams}`, {
    headers: {
      'Authorization': `Bearer ${token}`,
      'Content-Type': 'application/json'
    }
  });
  
  return await response.json();
}
```

---

## ➕ Create New Visit

### Create Visit

**Endpoint**: `POST /api/visits`

**Description**: Creates a new visit for a patient

**Headers**:
- `Authorization: Bearer {token}`
- `Content-Type: application/json`

**Request Body**:
```json
{
  "patient_id": 123,
  "visit_type": "consultation",
  "status": "active",
  "notes": "Patient presents with headache and fever"
}
```

**Visit Types**:
- `consultation` - Initial patient examination 🩺
- `follow_up` - Return visit for ongoing treatment 🔄
- `emergency` - Urgent medical attention 🚨
- `general` - Other types of visits 📋

**Status Options**:
- `active` - Visit is currently ongoing
- `completed` - Visit has been completed
- `cancelled` - Visit was cancelled

**Response**:
```json
{
  "success": true,
  "message": "Visit created successfully",
  "data": {
    "id": 456,
    "patient_id": 123,
    "facility_id": 1,
    "visit_type": "consultation",
    "status": "active",
    "notes": "Patient presents with headache and fever",
    "created_at": "2024-01-15T10:30:00Z",
    "updated_at": "2024-01-15T10:30:00Z",
    "patient": {
      "id": 123,
      "full_name": "John Doe",
      "first_name": "John",
      "last_name": "Doe",
      "phone": "+250788123456",
      "email": "john.doe@example.com"
    }
  }
}
```

**Frontend Integration**:
```typescript
interface CreateVisitRequest {
  patient_id: number;
  visit_type: 'consultation' | 'follow_up' | 'emergency' | 'general';
  status?: 'active' | 'completed' | 'cancelled';
  notes?: string;
}

interface CreateVisitResponse {
  success: boolean;
  message: string;
  data: Visit;
}

async function createVisit(visitData: CreateVisitRequest): Promise<CreateVisitResponse> {
  const response = await fetch('/api/visits', {
    method: 'POST',
    headers: {
      'Authorization': `Bearer ${token}`,
      'Content-Type': 'application/json'
    },
    body: JSON.stringify(visitData)
  });
  
  return await response.json();
}
```

---

## 📄 Get Visit Details

### Get Visit by ID

**Endpoint**: `GET /api/visits/{id}`

**Description**: Retrieves detailed information about a specific visit including patient and invoice data

**Headers**:
- `Authorization: Bearer {token}`

**Response**:
```json
{
  "success": true,
  "data": {
    "id": 1,
    "patient_id": 123,
    "facility_id": 1,
    "visit_type": "consultation",
    "status": "active",
    "notes": "Initial consultation for persistent headache",
    "created_at": "2024-01-15T10:30:00Z",
    "updated_at": "2024-01-15T10:30:00Z",
    "patient": {
      "id": 123,
      "full_name": "John Doe",
      "first_name": "John",
      "last_name": "Doe",
      "phone": "+250788123456",
      "email": "john.doe@example.com"
    },
    "invoices": [
      {
        "id": 789,
        "invoice_number": "INV-2024-001",
        "status": "pending",
        "total_amount": 50000,
        "created_at": "2024-01-15T11:00:00Z"
      }
    ]
  }
}
```

**Frontend Integration**:
```typescript
interface VisitDetails extends Visit {
  patient: {
    id: number;
    full_name: string;
    first_name: string;
    last_name: string;
    phone?: string;
    email?: string;
  };
  invoices: Array<{
    id: number;
    invoice_number: string;
    status: string;
    total_amount: number;
    created_at: string;
  }>;
}

async function getVisitDetails(visitId: number): Promise<{ success: boolean; data: VisitDetails }> {
  const response = await fetch(`/api/visits/${visitId}`, {
    headers: {
      'Authorization': `Bearer ${token}`,
      'Content-Type': 'application/json'
    }
  });
  
  return await response.json();
}
```

---

## 🔄 Update Visit Status

### Update Visit Status

**Endpoint**: `PUT /api/visits/{id}/status`

**Description**: Updates the status of a visit

**Headers**:
- `Authorization: Bearer {token}`
- `Content-Type: application/json`

**Request Body**:
```json
{
  "status": "completed"
}
```

**Valid Status Transitions**:
- `active` → `completed`
- `active` → `cancelled`
- `completed` → `cancelled` (with restrictions)

**Response**:
```json
{
  "success": true,
  "message": "Visit status updated successfully",
  "data": {
    "id": 1,
    "patient_id": 123,
    "facility_id": 1,
    "visit_type": "consultation",
    "status": "completed",
    "notes": "Initial consultation for persistent headache",
    "created_at": "2024-01-15T10:30:00Z",
    "updated_at": "2024-01-15T12:00:00Z",
    "patient": {
      "id": 123,
      "full_name": "John Doe",
      "first_name": "John",
      "last_name": "Doe"
    }
  }
}
```

**Frontend Integration**:
```typescript
interface UpdateStatusRequest {
  status: 'active' | 'completed' | 'cancelled';
}

interface UpdateStatusResponse {
  success: boolean;
  message: string;
  data: Visit;
}

async function updateVisitStatus(visitId: number, status: UpdateStatusRequest['status']): Promise<UpdateStatusResponse> {
  const response = await fetch(`/api/visits/${visitId}/status`, {
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

## 📊 Visit Statistics

### Get Visit Statistics

**Endpoint**: `GET /api/visits/statistics`

**Description**: Retrieves visit statistics for the user's facility

**Headers**:
- `Authorization: Bearer {token}`

**Response**:
```json
{
  "success": true,
  "data": {
    "total_visits": 150,
    "active_visits": 25,
    "completed_visits": 120,
    "cancelled_visits": 5,
    "visit_types": {
      "consultation": 80,
      "follow_up": 45,
      "emergency": 20,
      "general": 5
    },
    "today_visits": 8
  }
}
```

**Frontend Integration**:
```typescript
interface VisitStatistics {
  total_visits: number;
  active_visits: number;
  completed_visits: number;
  cancelled_visits: number;
  visit_types: {
    consultation: number;
    follow_up: number;
    emergency: number;
    general: number;
  };
  today_visits: number;
}

async function getVisitStatistics(): Promise<{ success: boolean; data: VisitStatistics }> {
  const response = await fetch('/api/visits/statistics', {
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
    "patient_id": ["Patient ID is required and must be a valid integer"],
    "visit_type": ["Visit type is required and must be one of: consultation, follow_up, emergency, general"]
  }
}
```

**Not Found (404)**:
```json
{
  "success": false,
  "error": "Visit not found",
  "message": "Visit with ID: 999 does not exist"
}
```

**Access Denied (403)**:
```json
{
  "success": false,
  "error": "Failed to create visit",
  "message": "You can only create visits for your facility"
}
```

**Business Logic Error (500)**:
```json
{
  "success": false,
  "error": "Failed to update visit status",
  "message": "Cannot cancel visits with associated invoices"
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

## 🔧 Business Logic

### **Visit Status Rules**
- A patient can have multiple active visits
- Cannot cancel visits with associated invoices
- Status transitions are validated
- Automatic facility assignment based on user permissions

### **Security & Permissions**
- Users can only access visits from their facility
- Patient access respects user permissions
- All operations require authentication

### **Data Relationships**
- Visits are linked to patients and facilities
- Invoices reference visits for billing
- Patient data is included in visit responses

---

## 📱 Frontend Integration Examples

### **Visit Selection Component**
```typescript
const VisitSelectionComponent = ({ patientId }: { patientId: number }) => {
  const [visits, setVisits] = useState<Visit[]>([]);
  const [loading, setLoading] = useState(false);

  useEffect(() => {
    const fetchVisits = async () => {
      setLoading(true);
      try {
        const response = await getVisits({ 
          patient_id: patientId, 
          status: 'active' 
        });
        setVisits(response.data);
      } catch (error) {
        console.error('Failed to fetch visits:', error);
      } finally {
        setLoading(false);
      }
    };

    fetchVisits();
  }, [patientId]);

  return (
    <div>
      <h3>Select Visit</h3>
      {visits.map(visit => (
        <div key={visit.id}>
          <span>{getVisitTypeIcon(visit.visit_type)} {visit.visit_type}</span>
          <span>{new Date(visit.created_at).toLocaleDateString()}</span>
        </div>
      ))}
    </div>
  );
};

const getVisitTypeIcon = (type: string) => {
  const icons = {
    consultation: '🩺',
    follow_up: '🔄',
    emergency: '🚨',
    general: '📋'
  };
  return icons[type] || '📋';
};
```

### **Create Visit Form**
```typescript
const CreateVisitForm = ({ patientId, onSuccess }: { 
  patientId: number; 
  onSuccess: (visit: Visit) => void;
}) => {
  const [formData, setFormData] = useState<CreateVisitRequest>({
    patient_id: patientId,
    visit_type: 'consultation',
    status: 'active',
    notes: ''
  });

  const handleSubmit = async (e: React.FormEvent) => {
    e.preventDefault();
    try {
      const response = await createVisit(formData);
      if (response.success) {
        onSuccess(response.data);
      }
    } catch (error) {
      console.error('Failed to create visit:', error);
    }
  };

  return (
    <form onSubmit={handleSubmit}>
      <select 
        value={formData.visit_type}
        onChange={(e) => setFormData({...formData, visit_type: e.target.value})}
      >
        <option value="consultation">🩺 Consultation</option>
        <option value="follow_up">🔄 Follow-up</option>
        <option value="emergency">🚨 Emergency</option>
        <option value="general">📋 General</option>
      </select>
      
      <textarea
        value={formData.notes}
        onChange={(e) => setFormData({...formData, notes: e.target.value})}
        placeholder="Visit notes (optional)"
      />
      
      <button type="submit">Create Visit</button>
    </form>
  );
};
```

---

## 🧪 Testing

### **Test Script**
Run the comprehensive test script to validate all endpoints:

```bash
php test_visit_management_api.php
```

**Test Coverage**:
- ✅ All required endpoints
- ✅ Authentication and authorization
- ✅ Input validation and error handling
- ✅ Visit creation and management
- ✅ Filtering and search functionality
- ✅ Business logic validation
- ✅ Security and permissions

---

## 🚀 Quick Start Guide

### **1. Authentication**
```typescript
const token = await login('admin@efiche.rw', 'password123');
```

### **2. Get Patient Visits**
```typescript
const visits = await getVisits({ patient_id: 123, status: 'active' });
```

### **3. Create New Visit**
```typescript
const visit = await createVisit({
  patient_id: 123,
  visit_type: 'consultation',
  status: 'active',
  notes: 'Initial consultation'
});
```

### **4. Update Visit Status**
```typescript
await updateVisitStatus(visit.id, 'completed');
```

### **5. Get Visit Statistics**
```typescript
const stats = await getVisitStatistics();
```

---

## 📋 TypeScript Interfaces

Copy these interfaces for your frontend:

```typescript
// Core Interfaces
interface Visit {
  id: number;
  patient_id: number;
  facility_id: number;
  visit_type: 'consultation' | 'follow_up' | 'emergency' | 'general';
  status: 'active' | 'completed' | 'cancelled';
  notes?: string;
  created_at: string;
  updated_at: string;
  patient?: {
    id: number;
    full_name: string;
    first_name: string;
    last_name: string;
    phone?: string;
    email?: string;
  };
  invoices?: Array<{
    id: number;
    invoice_number: string;
    status: string;
    total_amount: number;
    created_at: string;
  }>;
}

// Request/Response Types
interface CreateVisitRequest {
  patient_id: number;
  visit_type: 'consultation' | 'follow_up' | 'emergency' | 'general';
  status?: 'active' | 'completed' | 'cancelled';
  notes?: string;
}

interface VisitListResponse {
  success: boolean;
  data: Visit[];
  total: number;
  page: number;
  limit: number;
}

interface VisitStatistics {
  total_visits: number;
  active_visits: number;
  completed_visits: number;
  cancelled_visits: number;
  visit_types: {
    consultation: number;
    follow_up: number;
    emergency: number;
    general: number;
  };
  today_visits: number;
}
```

---

## 🎯 Best Practices

### **1. Error Handling**
- Always check the `success` field in responses
- Handle validation errors gracefully
- Show user-friendly error messages

### **2. Loading States**
- Show loading indicators during API calls
- Implement optimistic updates where appropriate

### **3. User Experience**
- Use visit type icons for better visual identification
- Provide clear explanations for visit types
- Implement proper status indicators

### **4. Security**
- Store JWT tokens securely
- Handle token expiration gracefully
- Validate user permissions

---

**🇷🇼 This Visit Management API is production-ready for Rwanda's healthcare system! 🏥💼**

For any questions or issues, refer to the test script or contact the backend development team.
