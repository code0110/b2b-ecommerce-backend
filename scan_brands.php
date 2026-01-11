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

echo "Scanning for brands in indices: " . implode(', ', $brandIndices) . PHP_EOL;

$count = 0;
$found = 0;
while (($row = fgetcsv($f)) !== false) {
    $count++;
    foreach ($brandIndices as $idx) {
        if (!empty($row[$idx])) {
            echo "Found brand at row $count: " . $row[$idx] . PHP_EOL;
            $found++;
            if ($found > 10) break 2;
        }
    }
}
echo "Scanned $count rows. Found $found brands." . PHP_EOL;
fclose($f);
