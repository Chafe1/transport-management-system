<?php
// Chapa Gateway Configuration
define('CHAPA_SECRET_KEY', getenv('CHAPA_SECRET_KEY') ?: 'CHASECK_TEST-xxxxxxxxxxxxxxxxxxxxxxxx');
define('CHAPA_PUBLIC_KEY', getenv('CHAPA_PUBLIC_KEY') ?: 'CHAPUBK_TEST-xxxxxxxxxxxxxxxxxxxxxxxx'); 

// Base URL calculation (Works for both Local and Railway)
$protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off' || $_SERVER['SERVER_PORT'] == 443) ? "https://" : "http://";
$domainName = $_SERVER['HTTP_HOST'] . '/';
define('BASE_URL', $protocol . $domainName);
?>
