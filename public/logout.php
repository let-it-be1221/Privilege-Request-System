<?php
require __DIR__ . '/../src/init.php';
require __DIR__ . '/../src/Auth.php';
use App\Auth;
Auth::logout();
header('Location: login.php');
exit;
