<?php
declare(strict_types=1);

include '../includes/template/header_aw.html.php';

require '../../vendor/autoload.php';
use Base64Url\Base64Url;

if( !isset($_SESSION['notification']) ) {
    $_SESSION['notification'] = 'Error';
}

?>
        <div class="container">
            <h1>Help</h1>
            <div class="row">
                <div class="col-md-12">
                <?php
                    if( isset($_GET['d1']) and ($_GET['d1'] === 'wrongbrowser') ) {
                        $_SESSION['msg'] = "<i class=\"fab fa-chrome\"></i><b>Browser Requirements Error:</b><br>You must use the <b>Google Chrome</b> browser in order to sign into our website. You can copy the URL below and try again in Google Chrome.";
                    }
                    if( isset($_GET['d1']) and ($_GET['d1'] === 'notandroid') ) {
                        $_SESSION['msg'] = "<i class=\"fas fa-mobile-alt\"></i><b>Phone Requirements Error:</b><br>You must sign in <b>with the same phone you used to create the account</b> (running <b>Android version 7 or higher</b>). Please close this window and go back to the survey to try again.";
                    }
                    if( !isset($_SESSION['prolific']) or ($_SESSION['prolific'] === 'Error') ) {
                        $_SESSION['msg'] = "<i class=\"fas fa-exclamation-triangle\"></i>This link is invalid. Please close this window and go back to the survey to try again.";
                        $_SESSION['prolific'] = 'Error';
                    }
                    if( isset($_SESSION['msg']) and ($_SESSION['msg'] != '') ) {
                        echo '<div id="alertbox" class="alert alert-danger" role="alert" style="visibility: visible">'.$_SESSION['msg'].'</div>';
                        $_SESSION['msg'] = '';
                    }
                ?>
                </div>
            </div>
            <div class="row">
                <div class="col-md-12">
                <?php if( isset($_GET['d1']) and ($_GET['d1'] === 'wrongbrowser') ) { ?>
                    <hr>
                    <form class="form-group">
                        <div class="col-xs-10">
                            <?php echo '<input id="myurl" class="form-control" type="text" placeholder="https://example.tech/static/auth.php?d1='.urlencode(Base64Url::encode($_SESSION['prolific'])).'" value="https://example.tech/static/auth.php?d1='.urlencode(Base64Url::encode($_SESSION['prolific'])).'" readonly>' ; ?>
                        </div>
                        <div class="col-xs-2">
                            <button style="margin-top:1rem;" type="button" class="btn btn-primary btn-lg btn-block" onclick="copyURL()">Copy</button>
                        </div>
                    </form>
                    <hr>
                <?php } ?>
                </div>
            </div>
            <div class="row">
                <div class="col-md-12 highlight">
                <h2 id="phone"><i class="fas fa-mobile-alt"></i>Phone Requirements</h2>
                <p>
                <?php echo 'In order to sign into our website, <mark><b>you must sign in with the same phone you used to create the account</b></mark>. If you are not using the same device as last week, you will need to restart on that device. If you no longer have access to this device you can <a onclick="logEvent(\'clicked()->PhoneRequirements->early\');" href="https://example.tech/static/exit.php?d1='.urlencode(Base64Url::encode($_SESSION['prolific'])).'&d2='.urlencode(Base64Url::encode('early')).'&d3='.Base64Url::encode($_SESSION['notification']).'" target="_blank">click here to exit the survey</a> early.'; ?>
                </p>
                <hr>
                <h2 id="browser"><i class="fab fa-chrome"></i>Browser Requirements</h2>
                <p>
                <?php echo 'You must use the <mark><b>Google Chrome</b></mark> browser. If you are using a different browser, then you will need to <mark><b>restart in Google Chrome</b></mark>. If you no longer have Google Chrome on your device, you can <a onclick="logEvent(\'clicked()->BrowserRequirements->PlayStore\');" href="https://play.google.com/store/apps/details?id=com.android.chrome" target="_blank">re-download it from the Google Play Store</a>. If you cannot download Chrome to your device, <a onclick="logEvent(\'clicked()->BrowserRequirements->early\');" href="https://example.tech/static/exit.php?d1='.urlencode(Base64Url::encode($_SESSION['prolific'])).'&d2='.urlencode(Base64Url::encode('early')).'&d3='.Base64Url::encode($_SESSION['notification']).'" target="_blank">click here to exit the survey</a> early.'; ?>
                </p>
                </div>
            </div>
        </div>
        <script>
            function copyURL() {
                'use strict';
                var copyText = document.getElementById("myurl");
                console.log(copyText);
                copyText.select();
                copyText.setSelectionRange(0, 99999);
                document.execCommand("copy");
            }
        </script>

        <?php include '../includes/template/footer.html.php'; ?>