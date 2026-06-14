<?php
require_once __DIR__ . '/../includes/auth_common.php';

// End the user session completely and return to User Login.
lcrms_destroy_session('login.php');
?>

