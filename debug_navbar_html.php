<?php
$_SERVER['HTTPS'] = 'off';
$_SERVER['HTTP_HOST'] = 'localhost';
$_SERVER['SCRIPT_NAME'] = '/index.php';
$_SERVER['REQUEST_URI'] = '/index.php';

require_once 'includes/sessions.php';
require_once 'includes/env_loader.php';
require_once 'includes/functions.php';

use App\Auth;

ob_start();
include 'views/navbar.php';
$html = ob_get_clean();

file_put_contents('debug_navbar.html', $html);
echo "Navbar HTML saved to debug_navbar.html\n";
