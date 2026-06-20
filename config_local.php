<?php
// config_local.php — Railway production config
// Railway menyediakan env var otomatis saat kamu tambah plugin MySQL
 
$db_host = getenv('MYSQLHOST') ?: 'localhost';
$db_port = getenv('MYSQLPORT') ?: '3306';
$db_name = getenv('MYSQLDATABASE') ?: 'railway';
$db_user = getenv('MYSQLUSER') ?: 'root';
$db_pass = getenv('MYSQLPASSWORD') ?: '';
 
// Tesseract sudah terinstall otomatis lewat Dockerfile
$tesseract_path = '/usr/bin/tesseract';
 