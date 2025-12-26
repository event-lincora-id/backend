<?php

require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

// Get user
$user = App\Models\User::find(28);

if (!$user) {
    echo "User not found\n";
    exit(1);
}

echo "User: {$user->name} ({$user->email})\n";
echo "Role: {$user->role}\n";
echo "Logo: " . ($user->logo ?? 'none') . "\n";
echo "Signature: " . ($user->signature ?? 'none') . "\n";

// Create token
$token = $user->createToken('test-upload')->plainTextToken;
echo "\nAPI Token: {$token}\n";

// Test the uploadLogo method
echo "\n--- Testing Logo Upload ---\n";

$testImagePath = "C:\\Study\\Kuliah\\Semester-7\\CP\\20240730_142708.png";

if (!file_exists($testImagePath)) {
    echo "Error: Test image not found at {$testImagePath}\n";
    exit(1);
}

echo "Test image exists: YES\n";
echo "Image size: " . filesize($testImagePath) . " bytes\n";

// Create a test request to upload via curl
$curlCommand = "curl -X POST http://localhost:8001/api/profile/logo " .
    "-H \"Authorization: Bearer {$token}\" " .
    "-H \"Accept: application/json\" " .
    "-F \"logo=@{$testImagePath}\"";

echo "\nCURL Command to test:\n{$curlCommand}\n";
