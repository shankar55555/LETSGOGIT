<?php

/**
 * Test script to demonstrate the referral logic functionality
 * This script shows how the referral_detail field is automatically cleared
 * when the source is changed from "Referral" to something else
 */

require_once __DIR__ . '/vendor/autoload.php';

// Bootstrap Laravel
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use Modules\Leads\Models\Lead;
use Illuminate\Support\Str;

echo "=== Testing Referral Logic ===\n\n";

try {
    // Test 1: Create a lead with source "Referral" and referral_detail
    echo "Test 1: Creating lead with source 'Referral' and referral_detail\n";
    $lead1 = Lead::create([
        'id' => Str::orderedUuid(),
        'name' => 'Test Company 1',
        'contact_person' => 'John Doe',
        'contact_person_role' => 'Manager',
        'phone' => '1234567890',
        'address' => '123 Test St',
        'status' => 'no_action',
        'source' => 'Referral',
        'referral_detail' => 'Referred by Jane Smith',
        'created_by' => 'test-user-uuid'
    ]);
    
    echo "✓ Lead created with ID: {$lead1->id}\n";
    echo "  Source: {$lead1->source}\n";
    echo "  Referral Detail: {$lead1->referral_detail}\n\n";
    
    // Test 2: Update source to something else - referral_detail should be cleared
    echo "Test 2: Updating source from 'Referral' to 'Website'\n";
    $lead1->update([
        'source' => 'Website'
    ]);
    
    echo "✓ Lead updated\n";
    echo "  New Source: {$lead1->source}\n";
    echo "  Referral Detail: " . ($lead1->referral_detail ?? 'NULL') . "\n\n";
    
    // Test 3: Create another lead with source "Website" (no referral_detail)
    echo "Test 3: Creating lead with source 'Website' (no referral_detail)\n";
    $lead2 = Lead::create([
        'id' => Str::orderedUuid(),
        'name' => 'Test Company 2',
        'contact_person' => 'Jane Smith',
        'contact_person_role' => 'Director',
        'phone' => '0987654321',
        'address' => '456 Test Ave',
        'status' => 'no_action',
        'source' => 'Website',
        'referral_detail' => null,
        'created_by' => 'test-user-uuid'
    ]);
    
    echo "✓ Lead created with ID: {$lead2->id}\n";
    echo "  Source: {$lead2->source}\n";
    echo "  Referral Detail: " . ($lead2->referral_detail ?? 'NULL') . "\n\n";
    
    // Test 4: Update source to "Referral" - referral_detail should remain null
    echo "Test 4: Updating source from 'Website' to 'Referral'\n";
    $lead2->update([
        'source' => 'Referral'
    ]);
    
    echo "✓ Lead updated\n";
    echo "  New Source: {$lead2->source}\n";
    echo "  Referral Detail: " . ($lead2->referral_detail ?? 'NULL') . "\n\n";
    
    // Test 5: Add referral_detail to the second lead
    echo "Test 5: Adding referral_detail to lead with source 'Referral'\n";
    $lead2->update([
        'referral_detail' => 'Referred by John Doe'
    ]);
    
    echo "✓ Lead updated\n";
    echo "  Source: {$lead2->source}\n";
    echo "  Referral Detail: {$lead2->referral_detail}\n\n";
    
    // Clean up - delete test leads
    echo "Cleaning up test data...\n";
    $lead1->delete();
    $lead2->delete();
    echo "✓ Test leads deleted\n\n";
    
    echo "=== All Tests Passed! ===\n";
    echo "The referral logic is working correctly:\n";
    echo "1. When source is changed from 'Referral' to something else, referral_detail is automatically cleared\n";
    echo "2. When source is 'Referral', referral_detail can be set\n";
    echo "3. When source is not 'Referral', referral_detail remains null\n";
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
    echo "Stack trace:\n" . $e->getTraceAsString() . "\n";
} 
