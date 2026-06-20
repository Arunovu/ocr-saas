<?php
// FILE SEMENTARA UNTUK DEBUG - HAPUS SETELAH SELESAI
header('Content-Type: text/plain');

echo "=== ENV VAR CHECK ===\n";
echo "MYSQLHOST: " . (getenv('MYSQLHOST') ?: '(EMPTY)') . "\n";
echo "MYSQLPORT: " . (getenv('MYSQLPORT') ?: '(EMPTY)') . "\n";
echo "MYSQLDATABASE: " . (getenv('MYSQLDATABASE') ?: '(EMPTY)') . "\n";
echo "MYSQLUSER: " . (getenv('MYSQLUSER') ?: '(EMPTY)') . "\n";
echo "MYSQLPASSWORD: " . (getenv('MYSQLPASSWORD') ? '(SET, hidden)' : '(EMPTY)') . "\n";

echo "\n=== ALTERNATIVE NAMES CHECK ===\n";
echo "MYSQL_HOST: " . (getenv('MYSQL_HOST') ?: '(EMPTY)') . "\n";
echo "DATABASE_URL: " . (getenv('DATABASE_URL') ? '(SET, hidden)' : '(EMPTY)') . "\n";

echo "\n=== ALL ENV VARS CONTAINING 'MYSQL' or 'DB' ===\n";
foreach ($_ENV as $key => $value) {
    if (stripos($key, 'mysql') !== false || stripos($key, 'db') !== false) {
        echo "$key = " . (stripos($key, 'pass') !== false ? '(hidden)' : $value) . "\n";
    }
}
foreach (getenv() as $key => $value) {
    if (stripos($key, 'mysql') !== false || stripos($key, 'db') !== false) {
        echo "$key = " . (stripos($key, 'pass') !== false ? '(hidden)' : $value) . "\n";
    }
}