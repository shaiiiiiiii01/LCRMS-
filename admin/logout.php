<?php
require_once __DIR__ . '/auth.php';

// End the administrator session completely and return to the shared login page.
lcrms_destroy_session('../login.php');
?>
