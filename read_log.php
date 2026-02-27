<?php
echo "PHP Error Log Path: " . ini_get('error_log') . "\n";
$log = ini_get('error_log');
if ($log && file_exists($log)) {
    echo "Last 20 lines of log:\n";
    $lines = file($log);
    $last = array_slice($lines, -20);
    echo implode("", $last);
} else {
    echo "Log file not found or not set.\n";
}
?>