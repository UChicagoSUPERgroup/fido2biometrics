<?php

declare(strict_types=1);
require '../../../vendor/autoload.php';
require 'MyPublicKeyCredentialSourceRepository.php';

use Base64Url\Base64Url;
use Symfony\Bridge\PsrHttpMessage\Factory\DiactorosFactory;
use Symfony\Component\HttpFoundation\Request;
use Cose\Algorithm\Manager;
use Cose\Algorithm\Signature\ECDSA;
use Cose\Algorithm\Signature\EdDSA;
use Cose\Algorithm\Signature\RSA;

use Webauthn\PublicKeyCredentialLoader;
use Webauthn\AuthenticatorAssertionResponse;
use Webauthn\AuthenticatorAssertionResponseValidator;
use Webauthn\AuthenticationExtensions\ExtensionOutputCheckerHandler;
use Webauthn\AttestationStatement\AttestationStatementSupportManager;
use Webauthn\AttestationStatement\NoneAttestationStatementSupport;
use Webauthn\AttestationStatement\AttestationObjectLoader;
use Webauthn\TokenBinding\TokenBindingNotSupportedHandler;

use ExampleTech\Repository\PublicKeyCredentialSourceRepository;

// Some session management
if (session_status() == PHP_SESSION_NONE) {
    date_default_timezone_set('UTC');
    session_set_cookie_params(7200, '/', 'example.tech', TRUE, TRUE);
    session_name('ExampleTech');
    session_start();
}

parsePOSTRequest();

function parsePOSTRequest() {

    // Fail counter
    if( isset($_SESSION['counter_authenticate_w']) ) {
        $_SESSION['counter_authenticate_w'] = $_SESSION['counter_authenticate_w'] + 1;
    } else {
        $_SESSION['counter_authenticate_w'] = 1;
    }

    // Request handling
    if( isset($_SESSION['prolific']) && isset($_POST['json']) ) {
        try {
            $data = json_decode($_POST['json'], True);
            if($data === NULL) {
                throw new Exception("Couldn't decode post-data to jSON");
            }
            if(count($data) != 2) {
                throw new Exception("Wrong amount of jSON-datasets");
            }
            if( isset($data['ui_timespan']) && isset($data['webauthn']) ) {
                $ui_timespan = floatval($data['ui_timespan']);
                $webauthn = json_encode($data['webauthn'], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE); // Nested JSON
                $webauthn_parsed = parseWebAuthnAuthentication($webauthn);
                $useragent = htmlspecialchars($_SERVER['HTTP_USER_AGENT'], ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8', true);
                $ip = htmlspecialchars($_SERVER['REMOTE_ADDR'], ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8', true);
                try {
                    insertAuthentication($_SESSION['prolific'], $_SESSION['username'], $_SESSION['notification'], $_SESSION['counter_authenticate_w'], $ui_timespan, json_encode($webauthn_parsed, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE), $webauthn, $useragent, $ip);
                    $output = array('status' => true, 'attempt' => $_SESSION['counter_authenticate_w']);
                } catch (Exception $e) {
                    // We forward participants that already authenticated :)
                    if( $e->getMessage() != "Error: This participant already exists." ) {
                        throw new Exception($e->getMessage(), -1);
                    }
                }
                $_SESSION['status'] = "success";
                $output = array('status' => true, 'attempt' => $_SESSION['counter_authenticate_w']);
            } else {
                $_SESSION['status'] = "not-successful";
                $output = array('status' => false, 'attempt' => $_SESSION['counter_authenticate_w']);
            }
        } catch (Exception $e) {
            $_SESSION['status'] = "not-successful";
            $output = array('status' => false, 'attempt' => $_SESSION['counter_authenticate_w']);
        } //end try
    } else {
        $_SESSION['status'] = "not-successful";
        $output = array('status' => false, 'attempt' => $_SESSION['counter_authenticate_w']);
    } //end is $_POST['json'] available

    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($output);
}

function parseWebAuthnAuthentication($json) {
    try {
        // Init Setp 1
        $attestationStatementSupportManager = new AttestationStatementSupportManager();
        $attestationStatementSupportManager->add(new NoneAttestationStatementSupport());
        $attestationObjectLoader = new AttestationObjectLoader($attestationStatementSupportManager);
        $publicKeyCredentialLoader = new PublicKeyCredentialLoader($attestationObjectLoader);

        // Init Setp 2
        $publicKeyCredentialSourceRepository  = new PublicKeyCredentialSourceRepository();
        $tokenBindnigHandler = new TokenBindingNotSupportedHandler();
        $extensionOutputCheckerHandler = new ExtensionOutputCheckerHandler();

        $coseAlgorithmManager = new Manager();
        $coseAlgorithmManager->add(new ECDSA\ES256());
        $coseAlgorithmManager->add(new RSA\RS256());
        $symfonyRequest = Request::createFromGlobals();
        $psr7Request = (new DiactorosFactory())->createRequest($symfonyRequest);

        // Load $json data
        $publicKeyCredential = $publicKeyCredentialLoader->load($json);

        // Step 1) Make sure that the authenticator response is of type AuthenticatorAssertionResponse
        $authenticatorAssertionResponse = $publicKeyCredential->getResponse();
        if (!$authenticatorAssertionResponse instanceof AuthenticatorAssertionResponse) {
            throw new Exception("Error: Not a valid AuthenticatorAssertionResponse.", -1);
        }

        // Setp 2) Make sure the response is valid, verification against the Public Key Assertion Options we created earlier.
        // If no exception is thrown, the response is valid and you can continue the authentication of the user.
        $authenticatorAssertionResponseValidator = new AuthenticatorAssertionResponseValidator(
            $publicKeyCredentialSourceRepository,     // The Credential Repository service
            $tokenBindnigHandler,                     // The token binding handler
            $extensionOutputCheckerHandler,           // The extension output checker handler
            $coseAlgorithmManager                     // The COSE Algorithm Manager
        );

        $publicKeyCredentialSource = $authenticatorAssertionResponseValidator->check(
            $publicKeyCredential->getRawId(),
            $authenticatorAssertionResponse,
            $_SESSION['publicKeyCredentialRequestOptions'],
            $psr7Request,
            $_SESSION['username']
        );
        $result = $publicKeyCredentialSource->jsonSerialize(); // No exception? All is valid, user is who he claims to be!
        return $result;
    } catch (\Throwable $e) {
        throw new Exception("Error: Failed to parse WebAuthn authentication data.", -1);
    }
}

function insertAuthentication($prolific, $username, $notification, $attempt, $ui_timespan, $json_parsed, $json_raw, $useragent, $ip) {
    /*
    CREATE TABLE webauthn_authentications (
        uid INT NOT NULL AUTO_INCREMENT PRIMARY KEY,
        prolific VARCHAR(64) NOT NULL,
        username VARCHAR(64) NOT NULL,
        notification VARCHAR(64) NOT NULL,
        attempt INT NOT NULL,
        ui_timespan INT NOT NULL,
        date_auth TIMESTAMP DEFAULT CURRENT_TIMESTAMP NOT NULL,
        json_parsed VARCHAR(4096) NOT NULL,
        json_raw VARCHAR(4096) NOT NULL,
        useragent VARCHAR(512) NOT NULL,
        ip VARCHAR(64) NOT NULL
    ) ENGINE=InnoDB;
    */
    // Connect to DB
    include_once('db_connection.php');
    global $pdo;
    // Check whether a participant with this prolific ID already exists
    $stmt = $pdo->prepare("SELECT `prolific` FROM `bio_webauthn_db`.`webauthn_authentications` WHERE prolific=:prolific LIMIT 1");
    $stmt->bindParam(':prolific', $prolific, PDO::PARAM_STR);
    $stmt->execute();
    $result = $stmt->fetchAll(PDO::FETCH_ASSOC);
    if($result === false) {
        throw new Exception("Error: Can not check whether this participant already exists.", -1);
    } else {
        if(count($result) != 0) {
            throw new Exception("Error: This participant already exists.", -1);
        } else {
            // Insert new participant into webauthn_authentications
            $stmt = $pdo->prepare("INSERT INTO `bio_webauthn_db`.`webauthn_authentications` (`prolific`,`username`,`notification`,`attempt`,`ui_timespan`,`json_parsed`,`json_raw`,`useragent`,`ip`) VALUES (:prolific,:username,:notification,:attempt,:ui_timespan,:json_parsed,:json_raw,:useragent,:ip)");
            $stmt->bindParam(':prolific', $prolific, PDO::PARAM_STR);
            $stmt->bindParam(':username', $username, PDO::PARAM_STR);
            $stmt->bindParam(':notification', $notification, PDO::PARAM_STR);
            $stmt->bindParam(':attempt', $attempt, PDO::PARAM_INT);
            $stmt->bindParam(':ui_timespan', $ui_timespan, PDO::PARAM_INT);
            $stmt->bindParam(':json_parsed', $json_parsed, PDO::PARAM_STR);
            $stmt->bindParam(':json_raw', $json_raw, PDO::PARAM_STR);
            $stmt->bindParam(':useragent', $useragent, PDO::PARAM_STR);
            $stmt->bindParam(':ip', $ip, PDO::PARAM_STR);
            $result = $stmt->execute();
            if($result === false) {
                 throw new Exception("Error: Could not insert the new participant.", -1);
            }
            return $result;
        } // endif - Participant does not exists
    } // endif - Could execute statement
} // end function

?>