<?php
require_once 'config/auth.php';

$_SESSION = [];
session_destroy();

header('Location: login.php');
exit;
