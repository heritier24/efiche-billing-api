<?php

/**
 * eFiche Billing API - Comprehensive CRUD Test Script
 * Tests all endpoints to verify frontend integration readiness
 */

require_once __DIR__ . '/vendor/autoload.php';

use Illuminate\Http\Request;
use App\Models\User;
use App\Models\Patient;
use App\Models\Invoice;
use App\Models\Payment;
use App\Models\Visit;
use App\Models\Facility;
use App\Models\Insurance;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;

class ApiTester
{
    private $baseUrl = 'http://localhost:8000/api';
    private $token = null;
    private $testResults = [];

    public function runAllTests()
    {
        echo "🚀 Starting eFiche Billing API CRUD Tests\n";
        echo "==========================================\n\n";

        $this->testAuthentication();
        $this->testPatientCRUD();
        $this->testInvoiceOperations();
        $this->testPaymentOperations();
        $this->testDashboardEndpoints();
        $this->testFacilityEndpoints();

        $this->printSummary();
    }

    private function testAuthentication()
    {
        echo "🔐 Testing Authentication Endpoints\n";
        echo "-----------------------------------\n";

        // Test Login
        $loginData = [
            'email' => 'admin@efiche.rw',
            'password' => 'password123'
        ];

        $response = $this->makeRequest('POST', '/auth/login', $loginData, false);
        
        if ($response && isset($response['success']) && $response['success']) {
            $this->token = $response['token'];
            $this->testResults['auth_login'] = '✅ PASS';
            echo "✅ Login successful - Token received\n";
        } else {
            $this->testResults['auth_login'] = '❌ FAIL';
            echo "❌ Login failed\n";
            return;
        }

        // Test /me endpoint
        $response = $this->makeRequest('GET', '/auth/me');
        if ($response && isset($response['id'])) {
            $this->testResults['auth_me'] = '✅ PASS';
            echo "✅ /me endpoint working\n";
        } else {
            $this->testResults['auth_me'] = '❌ FAIL';
            echo "❌ /me endpoint failed\n";
        }

        // Test Logout
        $response = $this->makeRequest('POST', '/auth/logout');
        if ($response && isset($response['success']) && $response['success']) {
            $this->testResults['auth_logout'] = '✅ PASS';
            echo "✅ Logout successful\n";
        } else {
            $this->testResults['auth_logout'] = '❌ FAIL';
            echo "❌ Logout failed\n";
        }

        // Re-login for subsequent tests
        $response = $this->makeRequest('POST', '/auth/login', $loginData, false);
        if ($response && isset($response['success']) && $response['success']) {
            $this->token = $response['token'];
        }

        echo "\n";
    }

    private function testPatientCRUD()
    {
        echo "👥 Testing Patient CRUD Operations\n";
        echo "----------------------------------\n";

        // Test Create Patient
        $patientData = [
            'first_name' => 'Test',
            'last_name' => 'Patient',
            'email' => 'testpatient@example.com',
            'phone' => '+250788123999',
            'date_of_birth' => '1990-01-01',
            'gender' => 'male',
            'address' => 'Kigali, Rwanda'
        ];

        $response = $this->makeRequest('POST', '/patients', $patientData);
        if ($response && isset($response['success']) && $response['success']) {
            $patientId = $response['data']['id'];
            $this->testResults['patient_create'] = '✅ PASS';
            echo "✅ Patient created - ID: $patientId\n";
        } else {
            $this->testResults['patient_create'] = '❌ FAIL';
            echo "❌ Patient creation failed\n";
            return;
        }

        // Test List Patients
        $response = $this->makeRequest('GET', '/patients');
        if ($response && isset($response['data']) && is_array($response['data'])) {
            $this->testResults['patient_list'] = '✅ PASS';
            echo "✅ Patient list retrieved - Count: " . count($response['data']) . "\n";
        } else {
            $this->testResults['patient_list'] = '❌ FAIL';
            echo "❌ Patient list failed\n";
        }

        // Test Get Patient Details
        $response = $this->makeRequest('GET', "/patients/$patientId");
        if ($response && isset($response['success']) && $response['success']) {
            $this->testResults['patient_show'] = '✅ PASS';
            echo "✅ Patient details retrieved\n";
        } else {
            $this->testResults['patient_show'] = '❌ FAIL';
            echo "❌ Patient details failed\n";
        }

        // Test Update Patient
        $updateData = [
            'first_name' => 'Updated',
            'last_name' => 'Patient',
            'address' => 'Kigali, Updated Address'
        ];

        $response = $this->makeRequest('PUT', "/patients/$patientId", $updateData);
        if ($response && isset($response['success']) && $response['success']) {
            $this->testResults['patient_update'] = '✅ PASS';
            echo "✅ Patient updated\n";
        } else {
            $this->testResults['patient_update'] = '❌ FAIL';
            echo "❌ Patient update failed\n";
        }

        // Test Patient Visits
        $response = $this->makeRequest('GET', "/patients/$patientId/visits");
        if ($response && isset($response['data'])) {
            $this->testResults['patient_visits'] = '✅ PASS';
            echo "✅ Patient visits retrieved\n";
        } else {
            $this->testResults['patient_visits'] = '❌ FAIL';
            echo "❌ Patient visits failed\n";
        }

        echo "\n";
    }

    private function testInvoiceOperations()
    {
        echo "📋 Testing Invoice Operations\n";
        echo "-----------------------------\n";

        // Test List Invoices
        $response = $this->makeRequest('GET', '/invoices');
        if ($response && isset($response['data'])) {
            $this->testResults['invoice_list'] = '✅ PASS';
            echo "✅ Invoice list retrieved\n";
        } else {
            $this->testResults['invoice_list'] = '❌ FAIL';
            echo "❌ Invoice list failed\n";
        }

        // Test Get Invoice by Visit (if visit exists)
        $response = $this->makeRequest('GET', '/visits/1/invoice');
        if ($response && isset($response['id'])) {
            $this->testResults['invoice_by_visit'] = '✅ PASS';
            echo "✅ Invoice by visit retrieved\n";
        } else {
            $this->testResults['invoice_by_visit'] = '❌ FAIL';
            echo "❌ Invoice by visit failed (may be no visit data)\n";
        }

        echo "\n";
    }

    private function testPaymentOperations()
    {
        echo "💳 Testing Payment Operations\n";
        echo "-----------------------------\n";

        // Test List Payments
        $response = $this->makeRequest('GET', '/payments');
        if ($response && isset($response['data'])) {
            $this->testResults['payment_list'] = '✅ PASS';
            echo "✅ Payment list retrieved\n";
        } else {
            $this->testResults['payment_list'] = '❌ FAIL';
            echo "❌ Payment list failed\n";
        }

        // Test Payment Status (if payment exists)
        $response = $this->makeRequest('GET', '/payments/1/status');
        if ($response && isset($response['status'])) {
            $this->testResults['payment_status'] = '✅ PASS';
            echo "✅ Payment status retrieved\n";
        } else {
            $this->testResults['payment_status'] = '❌ FAIL';
            echo "❌ Payment status failed (may be no payment data)\n";
        }

        echo "\n";
    }

    private function testDashboardEndpoints()
    {
        echo "📊 Testing Dashboard Endpoints\n";
        echo "-----------------------------\n";

        // Test Dashboard Stats
        $response = $this->makeRequest('GET', '/dashboard/stats');
        if ($response && isset($response['total_invoices'])) {
            $this->testResults['dashboard_stats'] = '✅ PASS';
            echo "✅ Dashboard stats retrieved\n";
        } else {
            $this->testResults['dashboard_stats'] = '❌ FAIL';
            echo "❌ Dashboard stats failed\n";
        }

        // Test Payment Stats
        $response = $this->makeRequest('GET', '/dashboard/payment-stats');
        if ($response && isset($response['total_payments'])) {
            $this->testResults['dashboard_payment_stats'] = '✅ PASS';
            echo "✅ Dashboard payment stats retrieved\n";
        } else {
            $this->testResults['dashboard_payment_stats'] = '❌ FAIL';
            echo "❌ Dashboard payment stats failed\n";
        }

        // Test Top Patients
        $response = $this->makeRequest('GET', '/dashboard/top-patients');
        if ($response && isset($response['data'])) {
            $this->testResults['dashboard_top_patients'] = '✅ PASS';
            echo "✅ Dashboard top patients retrieved\n";
        } else {
            $this->testResults['dashboard_top_patients'] = '❌ FAIL';
            echo "❌ Dashboard top patients failed\n";
        }

        echo "\n";
    }

    private function testFacilityEndpoints()
    {
        echo "🏥 Testing Facility Endpoints\n";
        echo "----------------------------\n";

        // Test Facility Insurances
        $response = $this->makeRequest('GET', '/facilities/1/insurances');
        if ($response && isset($response['data'])) {
            $this->testResults['facility_insurances'] = '✅ PASS';
            echo "✅ Facility insurances retrieved\n";
        } else {
            $this->testResults['facility_insurances'] = '❌ FAIL';
            echo "❌ Facility insurances failed\n";
        }

        echo "\n";
    }

    private function makeRequest($method, $endpoint, $data = [], $useToken = true)
    {
        $url = $this->baseUrl . $endpoint;
        $headers = [
            'Content-Type: application/json',
            'Accept: application/json'
        ];

        if ($useToken && $this->token) {
            $headers[] = "Authorization: Bearer " . $this->token;
        }

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);

        if ($method === 'POST') {
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
        } elseif ($method === 'PUT') {
            curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'PUT');
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
        } elseif ($method === 'DELETE') {
            curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'DELETE');
        }

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($response === false) {
            return null;
        }

        $decoded = json_decode($response, true);
        
        // Log the response for debugging
        echo "  → $method $endpoint (HTTP $httpCode)\n";
        
        return $decoded;
    }

    private function printSummary()
    {
        echo "📊 Test Results Summary\n";
        echo "======================\n";

        $totalTests = count($this->testResults);
        $passedTests = count(array_filter($this->testResults, fn($r) => str_contains($r, '✅')));
        $failedTests = $totalTests - $passedTests;

        foreach ($this->testResults as $test => $result) {
            echo "$result $test\n";
        }

        echo "\n📈 Summary:\n";
        echo "Total Tests: $totalTests\n";
        echo "Passed: $passedTests ✅\n";
        echo "Failed: $failedTests ❌\n";

        if ($failedTests === 0) {
            echo "\n🎉 ALL TESTS PASSED! Frontend integration is ready!\n";
        } else {
            echo "\n⚠️  Some tests failed. Please check the failed endpoints.\n";
        }

        echo "\n🚀 Frontend Integration Status: " . ($failedTests === 0 ? 'READY' : 'NEEDS FIXES') . "\n";
    }
}

// Bootstrap Laravel
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

// Run tests
$tester = new ApiTester();
$tester->runAllTests();
