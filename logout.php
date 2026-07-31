<?php
require_once __DIR__ . '/includes/auth.php';

session_unset();
session_destroy();

setcookie('remember_me', '', time() - 3600, '/');

redirect(BASE_URL . '/login.php');
