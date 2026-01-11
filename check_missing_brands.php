<?php
use App\Models\Product;

require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$products = Product::whereNull('brand_id')->take(20)->get();
echo "Products without brand: " . $products->count() . PHP_EOL;
foreach ($products as $p) {
    echo "Name: " . $p->name . PHP_EOL;
}
