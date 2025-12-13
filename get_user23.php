<?php

require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

$user = \App\Models\User::find(23);
echo "User ID: {$user->id}\n";
echo "Name: {$user->full_name}\n";
echo "Email: {$user->email}\n";
echo "Role: {$user->role}\n";
