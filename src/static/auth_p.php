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

if( !isset($_SESSION['prolific']) or ($_SESSION['prolific'] === 'Error') ) {
    header("Location: auth.php");
    die();
}

?>

<?php
if ( !isset($_SESSION['header_template']) ) {
    $_SESSION['header_template'] = 'header_aw.html.php';
}
include '../includes/template/'.$_SESSION['header_template'];
?>
<script src="../js/custom/auth_password.js"></script>
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
    <?php if( isset($_SESSION['prolific']) and ($_SESSION['prolific'] != 'Error') ) { ?>
    <div class="row">
        <div class="col-12">
        <form id="authentication-form" class="needs-validation" novalidate>

            <!-- Prolific ID -->
            <div class="form-group">
                <label class="control-label" for="user"><i class="fas fa-id-card"></i>Your Prolific ID:</label>
                <?php
                    echo '<input type="text" class="form-control" name="user" id="user" value='.$_SESSION['prolific'].' autocomplete="username" required disabled data-index="1">';
                ?>
            </div>

            <!-- Password -->
            <div class="form-group required">
                <label class="control-label" for="pass-current"><i class="fas fa-key"></i> Password</label>
                <div class="input-group">
                    <input type="password" class="form-control" data-toggle="password" id="pass-current" placeholder="Enter your password" autocomplete="current-password" minlength="8" data-index="2" required>
                    <div class="input-group-append" style="margin-left: 0px;">
                        <span class="btn btn-light reveal" id="eye-current-text" data-toggle="tooltip" data-placement="right" title="Show password" style="border-top: 1px solid #ced4da;border-bottom: 1px solid #ced4da;border-right: 1px solid #ced4da;"> <i id="eye-current" class='far fa-eye'></i> </span>
                    </div>
                </div>
            </div>

            <!--- Register button -->
            <button type="submit" id="login" class="btn btn-primary btn-lg btn-block" data-index="3">Login</button>

        </form>
        </div>
    </div><!-- end row -->
    <?php } ?>
</div><!-- end container -->
<br>
<br>

<?php include '../includes/template/footer.html.php'; ?>