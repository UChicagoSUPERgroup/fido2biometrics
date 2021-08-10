<?php

    declare(strict_types=1);
    require '../../vendor/autoload.php';
    use Ramsey\Uuid\Uuid;
    $uuid = Uuid::uuid4();
    use Cose\Algorithms;
    use Webauthn\AuthenticatorSelectionCriteria;
    use Webauthn\PublicKeyCredentialDescriptor;
    use Webauthn\PublicKeyCredentialCreationOptions;
    use Webauthn\PublicKeyCredentialParameters;
    use Webauthn\PublicKeyCredentialRpEntity;
    use Webauthn\PublicKeyCredentialUserEntity;

    // Some session management
    if (session_status() == PHP_SESSION_NONE) {
        date_default_timezone_set('UTC');
        session_set_cookie_params(7200, '/', 'example.tech', TRUE, TRUE);
        session_name('ExampleTech');
        session_start();
    }

    // RP Entity
    $rpEntity = new PublicKeyCredentialRpEntity(
        'Example Tech',                     // Name
        'example.tech',                     // ID
        'https://example.tech/ico/icon.png' // Icon
    );

    // User Entity
    // Check session/prolific ID
    if ( !isset($_SESSION['prolific']) ) {
        header("Location: dis.php");
        die();
    }

    if ( isset($_SESSION['completed_rw']) ) {
        if( $_SESSION['completed_rw'] === True ) {
            header("Location: complete_rw.php");
            die();
        }
    }

    $_SESSION['username'] = "example-tech-".$uuid->toString();
    $userEntity = new PublicKeyCredentialUserEntity(
        $_SESSION['prolific'],                // Name
        $_SESSION['username'],                // ID - Must not contain information that could identify the user.
        'Participant '.$_SESSION['prolific'], // Display name
        'https://example.tech/img/user.svg'   // Icon
    );

    // Challenge
    $challenge = random_bytes(16);

    // Timeout
    $timeout = 60000; // 60 seconds

    // Public Key Credential Parameters
    $publicKeyCredentialParametersList = [
        new PublicKeyCredentialParameters('public-key', Algorithms::COSE_ALGORITHM_ES256), //   -7
        new PublicKeyCredentialParameters('public-key', Algorithms::COSE_ALGORITHM_RS256), // -257
    ];

    // Devices to exclude aka we do not exclude any device.
    $excludedPublicKeyDescriptors = [
        new PublicKeyCredentialDescriptor(PublicKeyCredentialDescriptor::CREDENTIAL_TYPE_PUBLIC_KEY, 'ABCDEFGH...'),
    ];

    use Webauthn\AuthenticationExtensions\AuthenticationExtension;
    use Webauthn\AuthenticationExtensions\AuthenticationExtensionsClientInputs;
    // WebAuthn Extensions (not supported by any browser so far)
    $extensions = new AuthenticationExtensionsClientInputs();
    // Only for .get not for .create calls
    //$extensions->add(new AuthenticationExtension('appid', "https://example.tech"));
    // authnSel
    // biometricPerfBounds
    // txAuthGeneric
    $extensions->add(new AuthenticationExtension('txAuthSimple', "Execute order 66."));
    $extensions->add(new AuthenticationExtension('loc', true));
    $extensions->add(new AuthenticationExtension('uvm', true));
    $extensions->add(new AuthenticationExtension('uvi', true));
    $extensions->add(new AuthenticationExtension('exts', true));


    // AUTHENTICATOR_ATTACHMENT_NO_PREFERENCE
    // AUTHENTICATOR_ATTACHMENT_PLATFORM
    $authenticatorSelectionCriteria = new AuthenticatorSelectionCriteria(
        AuthenticatorSelectionCriteria::AUTHENTICATOR_ATTACHMENT_PLATFORM,  // Platform authenticator AUTHENTICATOR_ATTACHMENT_PLATFORM
        false,                                                                   // Resident key required
        AuthenticatorSelectionCriteria::USER_VERIFICATION_REQUIREMENT_REQUIRED   // User verification required
    );

    // 'User verification required' doesn't work in macOS with Firefox and a YubiKey
    // https://bugzilla.mozilla.org/show_bug.cgi?id=1530373
    // https://bugzilla.mozilla.org/show_bug.cgi?id=1609393
    // https://www.chromestatus.com/feature/5078137018777600

    $publicKeyCredentialCreationOptions = new PublicKeyCredentialCreationOptions(
        $rpEntity,
        $userEntity,
        $challenge,
        $publicKeyCredentialParametersList,
        $timeout,
        $excludedPublicKeyDescriptors,
        $authenticatorSelectionCriteria,
        PublicKeyCredentialCreationOptions::ATTESTATION_CONVEYANCE_PREFERENCE_NONE,
        $extensions // Extensions
    );

?>
        <?php
        if ( !isset($_SESSION['header_template']) ) {
            $_SESSION['header_template'] = 'header_rw.html.php';
        }
        include '../includes/template/'.$_SESSION['header_template'];
        ?>
        <script>
            const publicKey = <?php echo json_encode($publicKeyCredentialCreationOptions, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT); ?>;
        </script>
        <script src="../js/custom/register_webauthn.js"></script>
        <div class="container">
            <h1>Registration</h1>
            <?php
                if( isset($_SESSION['msg']) and ($_SESSION['msg'] != '') ) {
                    echo '<div id="alertbox" class="alert alert-danger" role="alert" style="visibility: visible">'.$_SESSION['msg'].'</div>';
                    $_SESSION['msg'] = '';
                } else {
                    echo '<div id="alertbox" class="alert alert-danger" role="alert" style="visibility: hidden">PLACEHOLDER TEXT</div>';
                }
            ?>
            <div class="row">
                <div class="col-12">
                <form id="registration-form" class="needs-validation" novalidate>

                    <!-- Prolific ID -->
                    <div class="form-group">
                        <label class="control-label" for="user"><i class="fas fa-id-card"></i>Your Prolific ID:</label>
                        <?php
                            // Check session/prolific ID
                            if ( !isset($_SESSION['prolific']) ) {
                                header("Location: dis.php");
                                die();
                            }
                            echo '<input type="text" class="form-control" name="user" id="user" value='.$_SESSION['prolific'].' required disabled data-index="1">';
                        ?>
                    </div>

                    <!-- Gender -->
                    <div class="form-group">
                        <label for="gender"><i class="fas fa-times"></i>What is your gender?</label>
                        <select class="form-control" id="gender" autocomplete="sex" required data-index="2">
                            <option value="" disabled selected>Please select...</option>
                            <option value="Woman">Woman</option>
                            <option value="Man">Man</option>
                            <option value="Non-binary">Non-binary</option>
                            <option value="Self-describe">Prefer to self-describe</option>
                            <option value="Not-say">Prefer not to say</option>
                        </select>
                        <div class="invalid-feedback">Please select an option.</div>
                    </div>
                    <script>
                        $('#gender').on('change', function() {
                            var selectedGender = $(this).children("option:selected").val();
                            if (selectedGender === "Self-describe")  {
                                // visibility: visible | hidden
                                $("#gender_self_desc").css('display', 'block');
                                $("#gender_self_desc_text").prop('required', true);
                            } else {
                                $("#gender_self_desc").css('display', 'none');
                                $("#gender_self_desc_text").removeAttr('required');
                            }
                        });
                    </script>
                    <div class="form-group" id="gender_self_desc" style="display:none">
                        <label for="gender_self_desc_text">Your description:</label>
                        <input type="text" class="form-control" name="gender_self_desc_text" id="gender_self_desc_text" autocomplete="sex" minlength="1" placeholder="" data-index="3">
                        <div class="invalid-feedback">Please specify.</div>
                    </div>

                    <!-- Age -->
                    <div class="form-group">
                        <label class="control-label" for="age"><i class="fas fa-birthday-cake"></i>How old are you?</label>
                        <input type="number" class="form-control" name="age" id="age" min="18" max="120" placeholder="" data-index="4">
                        <div class="invalid-feedback">Range: 18 to 120 years.</div>
                    </div>

                    <!--- Register button -->
                    <button type="submit" id="register" class="btn btn-primary btn-lg btn-block" data-index="5">Register</button>

                    <!-- Fallback Modal -->
                    <div class="modal fade" id="fallbackModal" tabindex="-1" role="dialog" aria-labelledby="fallbackModalLabel" aria-hidden="true">
                        <div class="modal-dialog" role="document">
                            <div class="modal-content">
                                <div class="modal-header">
                                    <h5 class="modal-title" id="fallbackModalLabel">Important Note</h5>
                                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                                        <span aria-hidden="true">&times;</span>
                                    </button>
                                </div>
                                <div class="modal-body">
                                    <img src="../img/fallback.png" alt="Schematic of verify your identity screen" class="img-fluid rounded mx-auto">
                                    <p>
                                        After closing this pop-up you will be asked to verify your identity.
                                        Please <b class="text-danger">try to use</b> your <b class="text-danger">PIN</b>, <b class="text-danger">pattern</b>, or <b class="text-danger">password</b> for this.
                                        You may need to tap on the <span style="color: rgb(0, 122, 192);"><strong>highlighted (blue) text</strong></span> similar to the image shown above to do so.
                                        Please do not use your fingerprint or face, even though it might be the default option.
                                    </p>
                                </div>
                                <div class="modal-footer">
                                    <button type="button" class="btn btn-block btn-primary" data-dismiss="modal">Close</button>
                                </div>
                            </div>
                        </div>
                    </div>

                    <script>
                        // Required for the improved explanation for the fallback group
                        <?php
                            if ($_SESSION['notification'] === 'fallback') {
                                echo 'var fallback=true;';
                            } else {
                                echo 'var fallback=false;';
                            }
                        ?>

                        // Setup an event listener for every input element
                        var viewport = document.getElementById('viewport');
                        var allInputs = document.getElementsByTagName('input');
                        for( var i = 0; i < allInputs.length; i++) {
                            var item = allInputs[i];
                            //console.log('set focus event handler on', item)
                            item.onfocus = function() {
                                //this.style.background = "yellow";
                                item.scrollIntoView();
                            }
                        };

                        // On Enter key move on to next input field
                        $('#registration-form').on('keydown', 'input', function (event) {
                            if (event.which == 13) {
                                event.preventDefault();
                                var $this = $(event.target);
                                var index = parseFloat($this.attr('data-index'));
                                $('[data-index="' + (index + 1).toString() + '"]').focus();
                            }
                        });

                        // Logging
                        $(document).on("focusin", "input, select", function(e) {
                            logEvent("focusin()->" + e.target.id);
                        });
                        $(document).on("focusout", "input, select", function(e) {
                            logEvent("focusout()->" + e.target.id + "->" + e.target.value);
                        });
                    </script>

                </form>
                </div>
            </div>
            <br>
        </div><!-- end container -->
        <br>
        <br>

        <?php include '../includes/template/footer.html.php'; ?>