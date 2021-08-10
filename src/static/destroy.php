<?php
// We destroy session to start over ...
include '../includes/template/header_rw.html.php';
if ( isset($_SESSION) ) {
    session_destroy();   // destroy session data in storage
}
header('Location: debug.php');
include '../includes/template/footer.html.php';
?>