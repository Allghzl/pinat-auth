<?php

require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\Service;

$service = Service::where('client_id', 'b170cd59-968e-4eb8-bd5b-76bd6cc73fc2')->first();

if (!$service) {
    echo "Service not found.\n";
    exit(1);
}

echo "Before: " . $service->default_bucket . "\n";

$service->default_bucket = 'drive';
$service->save();

echo "After: " . $service->default_bucket . "\n";
echo "Updated successfully.\n";
