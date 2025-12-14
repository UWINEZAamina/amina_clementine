<?php
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/flash.php';

session_unset();
session_destroy();
session_start();
set_flash('success', 'You have been logged out.');

header('Location: login.php');
exit();
