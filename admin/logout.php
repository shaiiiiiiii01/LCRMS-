<?php
require_once __DIR__ . '/auth.php';

// End the administrator session completely and return to Admin Login.
lcrms_destroy_session('login.php');
?>
