<?php
declare(strict_types=1);
require '../includes/lib/MyPublicKeyCredentialSourceRepository.php';
use Webauthn\PublicKeyCredentialRequestOptions;
use ExampleTech\Repository\PublicKeyCredentialSourceRepository;
use Webauthn\PublicKeyCredentialUserEntity;
use Webauthn\PublicKeyCredentialSource;

// Some session management
if (session_status() == PHP_SESSION_NONE) {
    date_default_timezone_set('UTC');
    session_set_cookie_params(7200, '/', 'example.tech', TRUE, TRUE);
    session_name('ExampleTech');
    session_start();
}

if ( isset($_SESSION['completed_aw']) ) {
    if( $_SESSION['completed_aw'] === True ) {
        header("Location: complete_aw.php");
        die();
    }
}

if( !isset($_SESSION['prolific']) or ($_SESSION['prolific'] === 'Error') ) {
    header("Location: auth.php");
    die();
}

$publicKeyCredentialRequestOptions = Array();

try {
    $_SESSION['username'] = getUsername($_SESSION['prolific']);

    // List of registered PublicKeyCredentialDescriptor classes associated to the user
    $userEntity = new PublicKeyCredentialUserEntity(
        $_SESSION['prolific'],                                // Name
        $_SESSION['username'],                                // ID - Must not contain information that could identify the user.
        'Participant '.$_SESSION['prolific'],                 // Display name
        'https://example.tech/img/user.svg'                   // Icon
    );

    $publicKeyCredentialSourceRepository  = new PublicKeyCredentialSourceRepository();
    $registeredAuthenticators = $publicKeyCredentialSourceRepository->findAllForUserEntity($userEntity);

    $allowedCredentials = array_map(
        static function (PublicKeyCredentialSource $credential): Webauthn\PublicKeyCredentialDescriptor {
            return $credential->getPublicKeyCredentialDescriptor();
        },
        $registeredAuthenticators
    );

    // Public Key Credential Request Options
    $publicKeyCredentialRequestOptions = new PublicKeyCredentialRequestOptions(
        random_bytes(32),                                                           // Challenge
        60000,                                                                      // Timeout
        'example.tech',                                                             // Relying Party ID
        $allowedCredentials,                                                        // Registered PublicKeyCredentialDescriptor
        PublicKeyCredentialRequestOptions::USER_VERIFICATION_REQUIREMENT_REQUIRED   // PIN must be inserted
    );
    $_SESSION['publicKeyCredentialRequestOptions'] = $publicKeyCredentialRequestOptions;
} catch (\Throwable $e) {
    $_SESSION['msg'] = "<i class=\"fas fa-exclamation-triangle\"></i>This link is invalid. Please close this window and go back to the survey to try again.";
    $_SESSION['prolific'] = 'Error';
}

function getUsername($prolific) {
    // Connect to DB
    include_once('../includes/lib/db_connection.php');
    global $pdo;
    // Check whether a participant with this prolific ID is registered and has a credential_id
    $stmt = $pdo->prepare("SELECT `username` FROM `bio_webauthn_db`.`webauthn_registrations` WHERE prolific=:prolific LIMIT 1");
    $stmt->bindParam(':prolific', $prolific, PDO::PARAM_STR);
    $stmt->execute();
    $result = $stmt->fetchAll(PDO::FETCH_ASSOC);
    if($result === false) {
        throw new Exception("Error: Can not check whether this participant is registered or not.", -1);
    } else {
        if(count($result) === 0) { // There is no user with the provided prolific id
            throw new Exception("Unknown user", -1);
        } else { // User found, returning username
            return $result[0]['username'];
        }
    } // endif - Could execute statement
} // end function

?>

<?php
if ( !isset($_SESSION['header_template']) ) {
    $_SESSION['header_template'] = 'header_aw.html.php';
}
include '../includes/template/'.$_SESSION['header_template'];
?>
<script>
    const publicKey = <?php echo json_encode($publicKeyCredentialRequestOptions, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT); ?>;
</script>
<script src="../js/custom/auth_webauthn.js"></script>
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
        <form id="authentication-form">

            <!-- Prolific ID -->
            <div class="form-group">
                <label class="control-label" for="user"><i class="fas fa-id-card"></i>Your Prolific ID:</label>
                <?php
                    echo '<input type="text" class="form-control" name="user" id="user" value='.$_SESSION['prolific'].' required disabled data-index="1">';
                ?>
            </div>

            <!--- Register button -->
            <button type="submit" id="login" class="btn btn-primary btn-lg btn-block" data-index="5">Login</button>

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

        </form>
        </div>
    </div><!-- end row -->
    <?php } ?>
    <script>
        // Required for the improved explanation for the fallback group
        <?php
            if ($_SESSION['notification'] === 'fallback') {
                echo 'var fallback=true;';
            } else {
                echo 'var fallback=false;';
            }
        ?>
    </script>
</div><!-- end container -->
<br>
<br>

<?php include '../includes/template/footer.html.php'; ?>