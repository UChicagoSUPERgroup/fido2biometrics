<?php
declare(strict_types=1);

include '../includes/template/header_rp.html.php';

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
                        $_SESSION['msg'] = "<i class=\"fab fa-chrome\"></i><b>Browser Requirements Error:</b><br>You must use the <b>Google Chrome</b> browser in order to participate in this study. You can copy the URL below and try again in Google Chrome.";
                    }
                    if( isset($_GET['d1']) and ($_GET['d1'] === 'notandroid') ) {
                        $_SESSION['msg'] = "<i class=\"fas fa-mobile-alt\"></i><b>Phone Requirements Error:</b><br>You must use a phone with <b>Android version 7 or higher</b> in order to participate in this study. Please close this window and go back to the survey to try again on a different phone.";
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
                            <?php echo '<input id="myurl" class="form-control" type="text" placeholder="https://example.tech/static/dis.php?d1='.urlencode(Base64Url::encode($_SESSION['prolific'])).'&d3='.urlencode(Base64Url::encode($_SESSION['notification'])).'" value="https://example.tech/static/dis.php?d1='.urlencode(Base64Url::encode($_SESSION['prolific'])).'&d3='.urlencode(Base64Url::encode($_SESSION['notification'])).'" readonly>' ; ?>
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
                <?php echo 'In order to create an account and sign into our website, you must use an <mark><b>Android phone</b></mark> with the operating system of <mark><b>Android 7 or higher</b></mark>. To check what version of Android you have, click <a onclick="logEvent(\'clicked()->PhoneRequirements->AndroidVersion\');" href="https://support.google.com/android/answer/7680439" target="_blank">here</a>. If you do not have a capable Android device, <a onclick="logEvent(\'clicked()->PhoneRequirements->early\');" href="https://example.tech/static/exit.php?d1='.urlencode(Base64Url::encode($_SESSION['prolific'])).'&d2='.urlencode(Base64Url::encode('early')).'&d3='.urlencode(Base64Url::encode($_SESSION['notification'])).'" target="_blank">click here to exit the survey</a> early.'; ?>
                </p>
                <hr>
                <h2 id="browser"><i class="fab fa-chrome"></i>Browser Requirements</h2>
                <p>
                <?php echo 'You must use the <mark><b>Google Chrome browser</b></mark> in order to participate in this study. If you are using a different browser, then you will need to <mark><b>restart in Google Chrome</b></mark>. If you do not have the Google Chrome browser on your device, you can <a onclick="logEvent(\'clicked()->BrowserRequirements->PlayStore\');" href="https://play.google.com/store/apps/details?id=com.android.chrome" target="_blank">download it from the Google Play Store</a>. If you cannot download Chrome to your device, <a onclick="logEvent(\'clicked()->BrowserRequirements->early\');" href="https://example.tech/static/exit.php?d1='.urlencode(Base64Url::encode($_SESSION['prolific'])).'&d2='.urlencode(Base64Url::encode('early')).'&d3='.urlencode(Base64Url::encode($_SESSION['notification'])).'" target="_blank">click here to exit the survey</a> early.'; ?>
                </p>
                <hr>
                <h2 id="more"><i class="fas fa-question-circle"></i>More Help</h2>
                <p>
                <?php echo 'If you still cannot successfully register an account, <a onclick="logEvent(\'clicked()->MoreHelp->early\');" href="https://example.tech/static/exit.php?d1='.urlencode(Base64Url::encode($_SESSION['prolific'])).'&d2='.urlencode(Base64Url::encode('early')).'&d3='.urlencode(Base64Url::encode($_SESSION['notification'])).'" target="_blank">click here to exit the survey</a> early.'; ?>
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