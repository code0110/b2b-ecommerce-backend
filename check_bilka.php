<?php
$f = fopen('temp_debug.csv', 'r');
for($i=0; $i<942; $i++) {
    $row = fgetcsv($f);
    if ($i == 941) { // 0-indexed, so row 941 is the 942nd line (header is line 0 usually but fgetcsv consumes it)
        // Wait, loop 942 times consumes 942 lines.
        // Line 1 is header.
        // Loop i=0 reads line 2 (first data row).
        // So i=940 reads line 942.
        // My scan script said "Found brand at row 941".
        // scan script:
        // while row=fgetcsv... count++...
        // So row 941 is the 941st data row.
        echo "Row 941: " . json_encode($row) . PHP_EOL;
    }
}
fclose($f);
