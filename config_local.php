<?php
// config_local.php — Railway production config
// Pakai kredensial PUBLIC NETWORK Railway langsung (paling pasti jalan,
// tidak bergantung ke variable reference ${{MySQL.xxx}} yang ternyata
// gagal ke-resolve di environment ini)

$db_host = 'reseau.proxy.rlwy.net';
$db_port = '50633';
$db_name = 'railway';
$db_user = 'root';
$db_pass = 'mFkaTqhZvugFvMCkkxEguxRRVSMNGhqU';

// Tesseract sudah terinstall otomatis lewat Dockerfile
$tesseract_path = '/usr/bin/tesseract';