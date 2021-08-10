<?php
declare(strict_types=1);

if ( !isset($_SESSION['header_template']) ) {
    $_SESSION['header_template'] = 'header_aw.html.php';
}
include '../includes/template/'.$_SESSION['header_template'];

require '../../vendor/autoload.php';
use Base64Url\Base64Url;

// Check session/prolific ID
if ( !isset($_SESSION['prolific']) || !isset($_SESSION['status']) || !isset($_SESSION['notification']) ) {
    $_SESSION['msg'] = "<i class=\"fas fa-exclamation-triangle\"></i>Authentication failed. Please try again!";
    header("Location: auth_w.php");
    die();
}

if ( isset($_SESSION['status']) ) {
    if( $_SESSION['status'] !== "Error" ) {
        $_SESSION['completed_aw'] = True;
    }
}
?>

<div class="container">
    <h1>Authentication</h1>
            <?php
                if( isset($_SESSION['status']) and ($_SESSION['status'] === 'success') ) {
                    echo '<div id="alertbox" class="alert alert-success" role="alert" style="visibility: visible"><i class="fas fa-check-circle"></i>Authentication was successful. Please continue.</div>';
                }
                if( isset($_SESSION['status']) and ($_SESSION['status'] === 'not-successful') ) {
                    echo '<div id="alertbox" class="alert alert-warning" role="alert" style="visibility: visible"><i class="fas fa-exclamation-circle"></i>Authentication was not successful. Please continue.</div>';
                }
            ?>
    <div class="row">
        <div class="col-12">
            <form id="continue-form">
                <?php
                    echo '<a class="btn btn-primary btn-lg btn-block" onclick="logEvent(\'clicked()->Continue->'.$_SESSION['status'].'\');" href="https://example.tech/static/exit.php?d1='.Base64Url::encode($_SESSION['prolific']).'&d2='.Base64Url::encode($_SESSION['status']).'&d3='.Base64Url::encode($_SESSION['notification']).'" role="button">Continue with Survey</a>';
                ?>
            </form>
        </div>
    </div>
</div><!-- end container -->

<?php include '../includes/template/footer.html.php'; ?>