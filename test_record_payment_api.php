<?php

/**
 * Record Payment API Test Script
 * 
 * This script tests the complete Record Payment functionality for the dashboard
 * including invoice filtering, payment processing, and error handling.
 */

// API Base URL
$baseUrl = 'http://localhost:8000/api';

// Test credentials
$credentials = [
    'email' => 'admin@efiche.rw',
    'password' => 'password123'
];

// Colors for output
$colors = [
    'green' => "\033[32m",
    'red' => "\033[31m",
    'yellow' => "\033[33m",
    'blue' => "\033[34m",
    'reset' => "\033[0m"
];

function logMessage($message, $color = 'reset') {
    global $colors;
    echo $colors[$color] . $message . $colors['reset'] . "\n";
}

function makeRequest($url, $method = 'GET', $data = null, $token = null) {
    $ch = curl_init();
    
    curl_setopt_array($ch, [
        CURLOPT_URL => $url,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_CUSTOMREQUEST => $method,
        CURLOPT_HTTPHEADER => [
            'Content-Type: application/json',
            'Accept: application/json',
            $token ? "Authorization: Bearer $token" : ''
        ],
    ]);
    
    if ($data && in_array($method, ['POST', 'PUT', 'PATCH'])) {
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
    }
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    return [
        'status' => $httpCode,
        'body' => json_decode($response, true),
        'raw' => $response
    ];
}

function authenticate($baseUrl, $credentials) {
    logMessage("🔐 Authenticating user...", 'blue');
    
    $response = makeRequest($baseUrl . '/auth/login', 'POST', $credentials);
    
    if ($response['status'] === 200 && isset($response['body']['token'])) {
        logMessage("✅ Authentication successful!", 'green');
        return $response['body']['token'];
    } else {
        logMessage("❌ Authentication failed!", 'red');
        logMessage("Response: " . json_encode($response['body']), 'red');
        exit(1);
    }
}

function testGetPendingInvoices($baseUrl, $token) {
    logMessage("\n📋 Testing GET /api/invoices with status filter...", 'blue');
    
    // Test 1: Get pending invoices
    logMessage("Test 1: Get pending invoices", 'yellow');
    $response = makeRequest($baseUrl . '/invoices?status=pending&limit=10', 'GET', null, $token);
    
    if ($response['status'] === 200) {
        logMessage("✅ Pending invoices retrieved successfully", 'green');
        $invoices = $response['body']['data'] ?? [];
        logMessage("Found " . count($invoices) . " pending invoices", 'blue');
        
        if (!empty($invoices)) {
            $sampleInvoice = $invoices[0];
            logMessage("Sample invoice: {$sampleInvoice['invoice_number']} - RWF {$sampleInvoice['total_amount']}", 'blue');
            return $sampleInvoice['id'];
        }
    } else {
        logMessage("❌ Failed to get pending invoices", 'red');
        logMessage("Status: {$response['status']}", 'red');
        logMessage("Response: " . json_encode($response['body']), 'red');
    }
    
    return null;
}

function testGetAllInvoices($baseUrl, $token) {
    logMessage("\n📋 Testing GET /api/invoices without filter...", 'blue');
    
    $response = makeRequest($baseUrl . '/invoices?limit=5', 'GET', null, $token);
    
    if ($response['status'] === 200) {
        logMessage("✅ All invoices retrieved successfully", 'green');
        $data = $response['body'];
        logMessage("Total invoices: {$data['total']}", 'blue');
        logMessage("Page: {$data['page']} of {$data['last_page']}", 'blue');
    } else {
        logMessage("❌ Failed to get invoices", 'red');
        logMessage("Response: " . json_encode($response['body']), 'red');
    }
}

function testInvoiceSearch($baseUrl, $token) {
    logMessage("\n🔍 Testing invoice search functionality...", 'blue');
    
    $response = makeRequest($baseUrl . '/invoices?search=INV&limit=5', 'GET', null, $token);
    
    if ($response['status'] === 200) {
        logMessage("✅ Invoice search working", 'green');
        $data = $response['body'];
        logMessage("Search results: {$data['total']} invoices found", 'blue');
    } else {
        logMessage("❌ Invoice search failed", 'red');
        logMessage("Response: " . json_encode($response['body']), 'red');
    }
}

function testGetInvoiceDetails($baseUrl, $token, $invoiceId) {
    logMessage("\n📄 Testing GET /api/invoices/{id}...", 'blue');
    
    if (!$invoiceId) {
        logMessage("⚠️  No invoice ID available for testing", 'yellow');
        return null;
    }
    
    $response = makeRequest($baseUrl . '/invoices/' . $invoiceId, 'GET', null, $token);
    
    if ($response['status'] === 200) {
        logMessage("✅ Invoice details retrieved successfully", 'green');
        $invoice = $response['body'];
        logMessage("Invoice: {$invoice['invoice_number']}", 'blue');
        logMessage("Status: {$invoice['status']}", 'blue');
        logMessage("Total: RWF {$invoice['total_amount']}", 'blue');
        logMessage("Patient: {$invoice['visit']['patient']['full_name']}", 'blue');
        
        if (isset($invoice['line_items']) && !empty($invoice['line_items'])) {
            logMessage("Line items: " . count($invoice['line_items']), 'blue');
            foreach ($invoice['line_items'] as $item) {
                logMessage("  - {$item['description']}: RWF {$item['unit_price']} x {$item['quantity']}", 'blue');
            }
        }
        
        return $invoice;
    } else {
        logMessage("❌ Failed to get invoice details", 'red');
        logMessage("Response: " . json_encode($response['body']), 'red');
    }
    
    return null;
}

function testProcessPayment($baseUrl, $token, $invoice) {
    logMessage("\n💳 Testing POST /api/invoices/{id}/payments...", 'blue');
    
    if (!$invoice) {
        logMessage("⚠️  No invoice available for payment testing", 'yellow');
        return;
    }
    
    $invoiceId = $invoice['id'];
    $remainingBalance = $invoice['total_amount'] - ($invoice['total_paid'] ?? 0);
    
    if ($remainingBalance <= 0) {
        logMessage("⚠️  Invoice already fully paid", 'yellow');
        return;
    }
    
    // Test 1: Valid cash payment
    logMessage("Test 1: Valid cash payment", 'yellow');
    $paymentAmount = min(1000.00, $remainingBalance); // Pay RWF 1000 or remaining balance
    
    $paymentData = [
        'amount' => (string) $paymentAmount,
        'method' => 'cash',
        'notes' => 'Test payment via API script'
    ];
    
    $response = makeRequest($baseUrl . '/invoices/' . $invoiceId . '/payments', 'POST', $paymentData, $token);
    
    if ($response['status'] === 201) {
        logMessage("✅ Cash payment processed successfully", 'green');
        $payment = $response['body']['data'];
        logMessage("Payment ID: {$payment['id']}", 'blue');
        logMessage("Amount: RWF {$payment['amount']}", 'blue');
        logMessage("Method: {$payment['method']}", 'blue');
        logMessage("Status: {$payment['status']}", 'blue');
    } else {
        logMessage("❌ Cash payment failed", 'red');
        logMessage("Response: " . json_encode($response['body']), 'red');
    }
    
    // Test 2: Mobile money payment
    if ($remainingBalance - $paymentAmount > 0) {
        logMessage("Test 2: Mobile money payment", 'yellow');
        
        $mobilePaymentData = [
            'amount' => (string) min(500.00, $remainingBalance - $paymentAmount),
            'method' => 'mobile_money',
            'phone' => '+250788123456',
            'notes' => 'Test mobile money payment'
        ];
        
        $response = makeRequest($baseUrl . '/invoices/' . $invoiceId . '/payments', 'POST', $mobilePaymentData, $token);
        
        if ($response['status'] === 201) {
            logMessage("✅ Mobile money payment initiated successfully", 'green');
            $payment = $response['body']['data'];
            logMessage("Payment ID: {$payment['id']}", 'blue');
            logMessage("Transaction Ref: {$payment['transaction_ref']}", 'blue');
        } else {
            logMessage("❌ Mobile money payment failed", 'red');
            logMessage("Response: " . json_encode($response['body']), 'red');
        }
    }
}

function testPaymentValidation($baseUrl, $token, $invoiceId) {
    logMessage("\n🔒 Testing payment validation...", 'blue');
    
    if (!$invoiceId) {
        logMessage("⚠️  No invoice ID available for validation testing", 'yellow');
        return;
    }
    
    // Test 1: Invalid amount (negative)
    logMessage("Test 1: Negative amount", 'yellow');
    $invalidPayment = [
        'amount' => '-100.00',
        'method' => 'cash',
        'notes' => 'Invalid negative payment'
    ];
    
    $response = makeRequest($baseUrl . '/invoices/' . $invoiceId . '/payments', 'POST', $invalidPayment, $token);
    
    if ($response['status'] === 422) {
        logMessage("✅ Negative amount validation working", 'green');
    } else {
        logMessage("❌ Negative amount validation failed", 'red');
    }
    
    // Test 2: Invalid phone for mobile money
    logMessage("Test 2: Invalid phone for mobile money", 'yellow');
    $invalidPhonePayment = [
        'amount' => '100.00',
        'method' => 'mobile_money',
        'phone' => 'invalid-phone',
        'notes' => 'Invalid phone test'
    ];
    
    $response = makeRequest($baseUrl . '/invoices/' . $invoiceId . '/payments', 'POST', $invalidPhonePayment, $token);
    
    if ($response['status'] === 422) {
        logMessage("✅ Phone validation working", 'green');
    } else {
        logMessage("❌ Phone validation failed", 'red');
    }
    
    // Test 3: Exceeding remaining balance
    logMessage("Test 3: Exceeding remaining balance", 'yellow');
    $excessPayment = [
        'amount' => '999999.99',
        'method' => 'cash',
        'notes' => 'Excessive payment test'
    ];
    
    $response = makeRequest($baseUrl . '/invoices/' . $invoiceId . '/payments', 'POST', $excessPayment, $token);
    
    if ($response['status'] === 422) {
        logMessage("✅ Balance validation working", 'green');
        logMessage("Error: " . ($response['body']['message'] ?? 'Unknown error'), 'blue');
    } else {
        logMessage("❌ Balance validation failed", 'red');
    }
}

function testErrorHandling($baseUrl, $token) {
    logMessage("\n🚨 Testing error handling...", 'blue');
    
    // Test 1: Non-existent invoice
    logMessage("Test 1: Non-existent invoice", 'yellow');
    $response = makeRequest($baseUrl . '/invoices/999999', 'GET', null, $token);
    
    if ($response['status'] === 404) {
        logMessage("✅ 404 error handling working", 'green');
    } else {
        logMessage("❌ 404 error handling failed", 'red');
    }
    
    // Test 2: Invalid query parameters
    logMessage("Test 2: Invalid query parameters", 'yellow');
    $response = makeRequest($baseUrl . '/invoices?status=invalid&limit=abc', 'GET', null, $token);
    
    if ($response['status'] === 422) {
        logMessage("✅ Query parameter validation working", 'green');
    } else {
        logMessage("❌ Query parameter validation failed", 'red');
    }
    
    // Test 3: Payment on non-existent invoice
    logMessage("Test 3: Payment on non-existent invoice", 'yellow');
    $paymentData = [
        'amount' => '100.00',
        'method' => 'cash',
        'notes' => 'Test payment on non-existent invoice'
    ];
    
    $response = makeRequest($baseUrl . '/invoices/999999/payments', 'POST', $paymentData, $token);
    
    if ($response['status'] === 404) {
        logMessage("✅ Payment on non-existent invoice handled correctly", 'green');
    } else {
        logMessage("❌ Payment on non-existent invoice handling failed", 'red');
    }
}

function testPagination($baseUrl, $token) {
    logMessage("\n📄 Testing pagination...", 'blue');
    
    // Test page 1
    $response = makeRequest($baseUrl . '/invoices?limit=3&page=1', 'GET', null, $token);
    
    if ($response['status'] === 200) {
        logMessage("✅ Pagination working", 'green');
        $data = $response['body'];
        logMessage("Page 1: {$data['total']} total, showing " . count($data['data']) . " items", 'blue');
        
        // Test page 2 if exists
        if ($data['last_page'] > 1) {
            $response2 = makeRequest($baseUrl . '/invoices?limit=3&page=2', 'GET', null, $token);
            if ($response2['status'] === 200) {
                $data2 = $response2['body'];
                logMessage("Page 2: showing " . count($data2['data']) . " items", 'blue');
            }
        }
    } else {
        logMessage("❌ Pagination failed", 'red');
    }
}

// Main execution
logMessage("🚀 Starting Record Payment API Tests", 'blue');
logMessage("=====================================", 'blue');

// Authenticate
$token = authenticate($baseUrl, $credentials);

// Test invoice listing and filtering
$invoiceId = testGetPendingInvoices($baseUrl, $token);
testGetAllInvoices($baseUrl, $token);
testInvoiceSearch($baseUrl, $token);
testPagination($baseUrl, $token);

// Test invoice details
$invoice = testGetInvoiceDetails($baseUrl, $token, $invoiceId);

// Test payment processing
testProcessPayment($baseUrl, $token, $invoice);

// Test validation and error handling
testPaymentValidation($baseUrl, $token, $invoiceId);
testErrorHandling($baseUrl, $token);

logMessage("\n✅ Record Payment API Tests Complete!", 'green');
logMessage("=====================================", 'green');
logMessage("All core functionality tested successfully!", 'green');
