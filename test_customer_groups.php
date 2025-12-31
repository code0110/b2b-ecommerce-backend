<?php
use App\Models\CustomerGroup;
use Illuminate\Support\Facades\Route;

require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

echo "Testing Customer Groups API...\n";

// Clean up
CustomerGroup::where('name', 'Test Group API')->delete();

// 1. Create
$controller = new \App\Http\Controllers\Admin\CustomerGroupController();
$request = new \Illuminate\Http\Request();
$request->replace([
    'name' => 'Test Group API',
    'type' => 'b2b',
    'default_discount_percent' => 10.5,
    'default_payment_terms_days' => 30,
    'default_credit_limit' => 5000.00,
    'is_active' => true
]);

echo "Creating group...\n";
try {
    $response = $controller->store($request);
    $group = $response->getData(); // It returns JSON response
    // Decode if needed, but getData() usually returns object/array for JsonResponse
    if (!isset($group->id)) {
        echo "Failed to create group. Response: " . json_encode($group) . "\n";
        exit(1);
    }
    echo "Group created. ID: " . $group->id . "\n";
    echo "Is Active: " . ($group->is_active ? 'Yes' : 'No') . "\n";
} catch (\Exception $e) {
    echo "Exception during create: " . $e->getMessage() . "\n";
    exit(1);
}

// 2. Update
echo "Updating group...\n";
$requestUpdate = new \Illuminate\Http\Request();
$requestUpdate->replace([
    'name' => 'Test Group API Updated',
    'default_discount_percent' => 15,
    'is_active' => false
]);

try {
    $foundGroup = CustomerGroup::find($group->id);
    $responseUpdate = $controller->update($requestUpdate, $foundGroup);
    $updatedGroup = $responseUpdate->getData();
    
    if ($updatedGroup->name !== 'Test Group API Updated') {
        echo "Failed update name.\n";
    }
    if ($updatedGroup->default_discount_percent != 15) {
        echo "Failed update discount.\n";
    }
    if ($updatedGroup->is_active !== false && $updatedGroup->is_active !== 0) {
        echo "Failed update is_active. Value: " . json_encode($updatedGroup->is_active) . "\n";
    }
    
    echo "Group updated successfully.\n";
} catch (\Exception $e) {
    echo "Exception during update: " . $e->getMessage() . "\n";
    exit(1);
}

// 3. Delete
echo "Deleting group...\n";
try {
    $foundGroup = CustomerGroup::find($group->id);
    $controller->destroy($foundGroup);
    
    if (CustomerGroup::find($group->id)) {
        echo "Failed to delete group.\n";
    } else {
        echo "Group deleted successfully.\n";
    }
} catch (\Exception $e) {
    echo "Exception during delete: " . $e->getMessage() . "\n";
    exit(1);
}

echo "All tests passed.\n";
