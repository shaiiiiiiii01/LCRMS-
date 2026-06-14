<?php
require_once __DIR__ . '/../includes/auth_common.php';

// End the user session completely and return to the shared login page.
lcrms_destroy_session('../login.php');
?>

