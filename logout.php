<?php
session_start();
require_once __DIR__ . '/includes/functions.php';
session_destroy();
header('Location: ' . url('index.php'));
exit;
