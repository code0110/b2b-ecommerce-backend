<?php
$f = fopen('temp_debug.csv', 'r');
$headers = fgetcsv($f);

// Find all indices for 'Brand'
$brandIndices = [];
foreach ($headers as $i => $h) {
    if (strtolower(trim($h)) === 'brand') {
        $brandIndices[] = $i;
    }
}

echo "Brand indices: " . implode(', ', $brandIndices) . PHP_EOL;

for ($i = 0; $i < 50; $i++) {
    $row = fgetcsv($f);
    if (!$row) break;
    
    $values = [];
    foreach ($brandIndices as $idx) {
        $val = $row[$idx] ?? '';
        if ($val) $values[] = "[$idx]=$val";
    }
    
    if (!empty($values)) {
        echo "Row $i: " . implode(', ', $values) . PHP_EOL;
    }
}
fclose($f);
