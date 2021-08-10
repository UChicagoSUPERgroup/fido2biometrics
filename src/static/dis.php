<?php
        // Some session management
        if (session_status() == PHP_SESSION_NONE) {
            date_default_timezone_set('UTC');
            session_set_cookie_params(7200, '/', 'example.tech', TRUE, TRUE);
            session_name('ExampleTech');
            session_start();

            // Registration with WebAuthn already done
            if ( isset($_SESSION['completed_rw']) ) {
                if( $_SESSION['completed_rw'] === True ) {
                    header("Location: complete_rw.php");
                    die();
                }
            }

            // Registration with password already done
            if ( isset($_SESSION['completed_rp']) ) {
                if( $_SESSION['completed_rp'] === True ) {
                    header("Location: complete_rp.php");
                    die();
                }
            }

        }
        // Check if link is is valid
        if( !isset($_GET['d1']) or !isset($_GET['d3']) ) {
            $_SESSION['msg'] = "<i class=\"fas fa-exclamation-triangle\"></i>This link is invalid. Please close this window and go back to the survey to try again.";
            include '../includes/template/header.empty.html.php';
        } else {
            // Parse and "validate" Prolific ID
            $tmp = base64_decode($_GET['d1']); // decode it
            $tmp = preg_replace('/[\x00-\x1F\x7F]/', '', $tmp); // remove non ASCII
            $tmp = htmlspecialchars($tmp, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8', true); // remove evil
            if ( strlen($tmp) == 24 ) { // check for length 24
                $_SESSION['prolific'] = $tmp;
            } else {
                $_SESSION['msg'] = "<i class=\"fas fa-exclamation-triangle\"></i>This link is invalid. Please close this window and go back to the survey to try again.";
                include '../includes/template/header.empty.html.php';
            }
            // Parse and validate notification
            $tmp = base64_decode($_GET['d3']); // decode it
            if($tmp == 'password') {
                $_SESSION['notification'] = 'password';
                $_SESSION['notification_title'] = ""; // No title
                $_SESSION['notification_text'] = "";  // No text
                $_SESSION['notification_logo'] = "";  // No logo
                $_SESSION['header_template'] = 'header_rp.html.php';
                include '../includes/template/'.$_SESSION['header_template'];
                header("Location: enroll_p.php");
                die();
            } elseif ($tmp == 'control') {
                $_SESSION['notification'] = 'control';
                $_SESSION['notification_title'] = "<h3>Fast and easy sign-in with your fingerprint or face.</h3>";
                $_SESSION['notification_text'] = "<p style='margin-top:0.5rem;font-size:1.4rem;font-weight:300;'><br>&nbsp;<br>&nbsp;</p>"; // No text
                $_SESSION['notification_logo'] = "<img src='../img/logo_base.png' width='200' class='img-fluid rounded mx-auto d-block' alt='logo'>";
                $_SESSION['header_template'] = 'header_rw.html.php';
                include '../includes/template/'.$_SESSION['header_template'];
            } elseif ($tmp == 'fallback') {
                $_SESSION['notification'] = 'fallback';
                $_SESSION['notification_title'] = "<h3>Fast and easy sign-in with your device's PIN, pattern, or password.</h3>";
                $_SESSION['notification_text'] = "<p style='margin-top:0.5rem;font-size:1.4rem;font-weight:300;'><br>&nbsp;</p>"; // No text
                $_SESSION['notification_logo'] = "<img src='../img/logo_fallback.png' width='200' class='img-fluid rounded mx-auto d-block' alt='logo'>";
                $_SESSION['header_template'] = 'header_rf.html.php';
                include '../includes/template/'.$_SESSION['header_template'];
            } elseif ($tmp == 'webauthn-brands') {
                $_SESSION['notification'] = 'webauthn-brands';
                $_SESSION['notification_title'] = "<h3>Fast and easy sign-in with your fingerprint or face.</h3>";
                $_SESSION['notification_text'] = "<p style='margin-top:0.5rem;font-size:1.4rem;font-weight:300;'>Backed by Microsoft,<br>Google, and Apple.<br>&nbsp;</p>";
                $_SESSION['notification_logo'] = "<img src='../img/logo_base.png' width='200' class='img-fluid rounded mx-auto d-block' alt='logo'>";
                $_SESSION['header_template'] = 'header_rw.html.php';
                include '../includes/template/'.$_SESSION['header_template'];
            } elseif ($tmp == 'webauthn-hacked') {
                $_SESSION['notification'] = 'webauthn-hacked';
                $_SESSION['notification_title'] = "<h3>Fast and easy sign-in with your fingerprint or face.</h3>";
                $_SESSION['notification_text'] = "<p style='margin-top:0.5rem;font-size:1.4rem;font-weight:300;'>Unlike passwords<br>it can't be hacked.<br>&nbsp;</p>";
                $_SESSION['notification_logo'] = "<img src='../img/logo_base.png' width='200' class='img-fluid rounded mx-auto d-block' alt='logo'>";
                $_SESSION['header_template'] = 'header_rw.html.php';
                include '../includes/template/'.$_SESSION['header_template'];
            } elseif ($tmp == 'webauthn-stored') {
                $_SESSION['notification'] = 'webauthn-stored';
                $_SESSION['notification_title'] = "<h3>Fast and easy sign-in with your fingerprint or face.</h3>";
                $_SESSION['notification_text'] = "<p style='margin-top:0.5rem;font-size:1.4rem;font-weight:300;'>Your fingerprint or face is<br>only stored on<br>your personal device.</p>";
                $_SESSION['notification_logo'] = "<img src='../img/logo_base.png' width='200' class='img-fluid rounded mx-auto d-block' alt='logo'>";
                $_SESSION['header_template'] = 'header_rw.html.php';
                include '../includes/template/'.$_SESSION['header_template'];
            } elseif ($tmp == 'webauthn-leaves') {
                $_SESSION['notification'] = 'webauthn-leaves';
                $_SESSION['notification_title'] = "<h3>Fast and easy sign-in with your fingerprint or face.</h3>";
                $_SESSION['notification_text'] = "<p style='margin-top:0.5rem;font-size:1.4rem;font-weight:300;'>Your fingerprint or face<br>never leaves your<br>personal device.</p>";
                $_SESSION['notification_logo'] = "<img src='../img/logo_base.png' width='200' class='img-fluid rounded mx-auto d-block' alt='logo'>";
                $_SESSION['header_template'] = 'header_rw.html.php';
                include '../includes/template/'.$_SESSION['header_template'];
            } elseif ($tmp == 'webauthn-shared') {
                $_SESSION['notification'] = 'webauthn-shared';
                $_SESSION['notification_title'] = "<h3>Fast and easy sign-in with your fingerprint or face.</h3>";
                $_SESSION['notification_text'] = "<p style='margin-top:0.5rem;font-size:1.4rem;font-weight:300;'>Your fingerprint or face is<br>never shared with<br>Example Tech or third parties.</p>";
                $_SESSION['notification_logo'] = "<img src='../img/logo_base.png' width='200' class='img-fluid rounded mx-auto d-block' alt='logo'>";
                $_SESSION['header_template'] = 'header_rw.html.php';
                include '../includes/template/'.$_SESSION['header_template'];
            } elseif ($tmp == 'safe') {
                $_SESSION['notification'] = 'safe';
                $_SESSION['notification_title'] = ""; // No title
                $_SESSION['notification_text'] = "<h3 style='margin-top:0.5rem;'>Safe and secure sign-in with your fingerprint or face.</h3><br>";
                $_SESSION['notification_logo'] = "<img src='../img/logo_base.png' width='200' class='img-fluid rounded mx-auto d-block' alt='logo'>";
                $_SESSION['header_template'] = 'header_rw.html.php';
                include '../includes/template/'.$_SESSION['header_template'];
            } elseif ($tmp == 'fast') {
                $_SESSION['notification'] = 'fast';
                $_SESSION['notification_title'] = ""; // No title
                $_SESSION['notification_text'] = "<h3 style='margin-top:0.5rem;'>Fast and easy sign-in with your fingerprint or face.<br>&nbsp;</h3><br>";
                $_SESSION['notification_logo'] = "<img src='../img/logo_base.png' width='200' class='img-fluid rounded mx-auto d-block' alt='logo'>";
                $_SESSION['header_template'] = 'header_rw.html.php';
                include '../includes/template/'.$_SESSION['header_template'];
            } elseif ($tmp == 'safefast') {
                $_SESSION['notification'] = 'safefast';
                $_SESSION['notification_title'] = ""; // No title
                $_SESSION['notification_text'] = "<h3 style='margin-top:0.5rem;'>Safe, secure, fast, and easy sign-in with your fingerprint or face.</h3><br>";
                $_SESSION['notification_logo'] = "<img src='../img/logo_base.png' width='200' class='img-fluid rounded mx-auto d-block' alt='logo'>";
                $_SESSION['header_template'] = 'header_rw.html.php';
                include '../includes/template/'.$_SESSION['header_template'];
            } elseif ($tmp == 'study1') {
                $_SESSION['notification'] = 'study1';
                $_SESSION['notification_title'] = ""; // No title
                $_SESSION['notification_text'] = "<h3 style='margin-top:0.5rem;'>Depending on your device, you can sign in with your fingerprint, face, or iris.</h3><br>";
                $_SESSION['notification_logo'] = "<img src='../img/logo_base.png' width='200' class='img-fluid rounded mx-auto d-block' alt='logo'>";
                $_SESSION['header_template'] = 'header_rw.html.php';
                include '../includes/template/'.$_SESSION['header_template'];
            } else {
                $_SESSION['msg'] = "<i class=\"fas fa-exclamation-triangle\"></i>This link is invalid. Please close this window and go back to the survey to try again.";
                $_SESSION['notification'] = 'error';
                $_SESSION['notification_title'] = ""; // No title
                $_SESSION['notification_text'] = "";  // No text
                $_SESSION['notification_logo'] = "";  // No logo
                $_SESSION['header_template'] = 'header.empty.html.php';
                include '../includes/template/'.$_SESSION['header_template'];
            }
        }
        ?>
        <div class="container">
            <?php
                if( isset($_SESSION['msg']) and ($_SESSION['msg'] != '') ) {
                    echo '<div id="alertbox" class="alert alert-danger" role="alert" style="visibility: visible">'.$_SESSION['msg'].'</div>';
                    $_SESSION['msg'] = '';
                } else {
                    echo '<div id="alertbox" class="alert alert-danger" role="alert" style="visibility: hidden">PLACEHOLDER TEXT</div>';
                        echo '<div class="row text-center">';
                            echo '<div class="col">';
                                if (isset($_GET['d3'])) {
                                    echo $_SESSION['notification_title'];
                                    echo $_SESSION['notification_logo'];
                                    echo $_SESSION['notification_text'];
                                }
                                echo '<a class="btn btn-primary btn-lg btn-block" href="enroll_w.php" onclick="logEvent(\''.$_SESSION['notification'].'->clicked()->Continue\');" role="button">Continue</a>';
                            echo '</div>';
                        echo '</div>';
                }
            ?>
        </div><!-- end container -->
        <?php include '../includes/template/footer.html.php'; ?>