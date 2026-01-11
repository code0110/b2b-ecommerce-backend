<?php
$f = fopen('temp_debug.csv', 'r');
$headers = fgetcsv($f);

echo "Headers: " . implode(', ', $headers) . PHP_EOL;

for ($i = 0; $i < 20; $i++) {
    $row = fgetcsv($f);
    if (!$row) break;
    
    echo "Row $i: " . ($row[4]??'') . " | Cat: " . ($row[19]??'') . " | Tags: " . ($row[20]??'') . PHP_EOL;
}
fclose($f);
