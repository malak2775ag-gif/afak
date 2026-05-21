<?php
session_start();
require_once __DIR__ . '/includes/functions.php';
session_destroy();
header('Location: https://afak.onrender.com/');
exit;
