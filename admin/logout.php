<?php
require_once __DIR__ . '/../helpers.php';
start_session();
$_SESSION = [];
session_destroy();
redirect(url('index.php'));
