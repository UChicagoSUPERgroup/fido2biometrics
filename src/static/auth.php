<?php

// Some session management
if (session_status() == PHP_SESSION_NONE) {
    date_default_timezone_set('UTC');
    session_set_cookie_params(7200, '/', 'example.tech', TRUE, TRUE);
    session_name('ExampleTech');
    session_start();
}

if ( isset($_SESSION['completed_ap']) ) {
    if( $_SESSION['completed_ap'] === True ) {
        header("Location: complete_ap.php");
        die();
    }
}

if ( isset($_SESSION['completed_aw']) ) {
    if( $_SESSION['completed_aw'] === True ) {
        header("Location: completed_aw.php");
        die();
    }
}

if( !isset($_GET['d1']) ) {
    $_SESSION['msg'] = "<i class=\"fas fa-exclamation-triangle\"></i>This link is invalid. Please close this window and go back to the survey to try again.";
    $_SESSION['prolific'] = 'Error';
} else {
    try {
        $_SESSION['prolific'] = htmlspecialchars(base64_decode($_GET['d1']), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8', true);
        $_SESSION['notification'] = getNotification($_SESSION['prolific']);
        if ( isset($_SESSION['notification']) ) {
            if( $_SESSION['notification'] === 'password' ) {
                $_SESSION['header_template'] = 'header_ap.html.php';
                header("Location: auth_p.php");
                die();
            } elseif( $_SESSION['notification'] === 'fallback' ) {
                $_SESSION['header_template'] = 'header_af.html.php';
                header("Location: auth_w.php");
                die();
            } else {
                $_SESSION['header_template'] = 'header_aw.html.php';
                header("Location: auth_w.php");
                die();
            }
        }
    } catch (\Throwable $e) {
        $_SESSION['msg'] = "<i class=\"fas fa-exclamation-triangle\"></i>This link is invalid. Please close this window and go back to the survey to try again.";
        $_SESSION['prolific'] = 'Error';
    }
}

function getNotification($prolific) {
    // Connect to DB
    include_once('../includes/lib/db_connection.php');
    global $pdo;

    $found = FALSE;

    // Check whether a participant with this prolific ID is registered in the webauthn_registrations
    $stmt = $pdo->prepare("SELECT `notification` FROM `bio_webauthn_db`.`webauthn_registrations` WHERE prolific=:prolific LIMIT 1");
    $stmt->bindParam(':prolific', $prolific, PDO::PARAM_STR);
    $stmt->execute();
    $result = $stmt->fetchAll(PDO::FETCH_ASSOC);
    if($result === false) {
        throw new Exception("Error: Can not check whether this participant is registered or not.", -1);
    } else {
        if(count($result) === 0) { // There is no user with the provided prolific id
            $found = FALSE;
        } else { // User found, returning notification
            return $result[0]['notification'];
        }
    } // endif - Could execute statement

    if ($found === FALSE) {
        // Check whether a participant with this prolific ID is registered in the password_registrations
        $stmt = $pdo->prepare("SELECT `notification` FROM `bio_webauthn_db`.`password_registrations` WHERE prolific=:prolific LIMIT 1");
        $stmt->bindParam(':prolific', $prolific, PDO::PARAM_STR);
        $stmt->execute();
        $result = $stmt->fetchAll(PDO::FETCH_ASSOC);
        if($result === false) {
            throw new Exception("Error: Can not check whether this participant is registered or not.", -1);
        } else {
            if(count($result) === 0) { // There is no user with the provided prolific id
                throw new Exception("Unknown user", -1);
            } else { // User found, returning notification
                return $result[0]['notification'];
            }
        } // endif - Could execute statement
    }

} // end function

?>

<?php
if ( !isset($_SESSION['header_template']) ) {
    $_SESSION['header_template'] = 'header_aw.html.php';
}
include '../includes/template/'.$_SESSION['header_template'];
?>

<div class="container">
    <h1>Authentication</h1>
    <?php
        if( isset($_SESSION['msg']) and ($_SESSION['msg'] != '') ) {
            echo '<div id="alertbox" class="alert alert-danger" role="alert" style="visibility: visible">'.$_SESSION['msg'].'</div>';
            $_SESSION['msg'] = '';
        } else {
            echo '<div id="alertbox" class="alert alert-danger" role="alert" style="visibility: hidden">PLACEHOLDER TEXT</div>';
        }
    ?>
</div><!-- end container -->
<br>
<br>

<?php include '../includes/template/footer.html.php'; ?>