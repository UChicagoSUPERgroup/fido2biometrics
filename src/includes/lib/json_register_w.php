<?php

declare(strict_types=1);
require '../../../vendor/autoload.php';
use Base64Url\Base64Url;
use Webauthn\AttestationStatement\AttestationStatementSupportManager;
use Webauthn\AttestationStatement\NoneAttestationStatementSupport;
use Webauthn\AttestationStatement\AttestationObjectLoader;
use Webauthn\PublicKeyCredentialLoader;

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
    if( isset($_SESSION['counter_register_w']) ) {
        $_SESSION['counter_register_w'] = $_SESSION['counter_register_w'] + 1;
    } else {
        $_SESSION['counter_register_w'] = 1;
    }

    // Request handling
    if( isset($_SESSION['prolific']) && isset($_SESSION['username']) && isset($_POST['json']) ) {
        try {
            $data = json_decode($_POST['json'], True);
            if($data === NULL) {
                throw new Exception("Couldn't decode post-data to jSON");
            }
            if(count($data) != 4) {
                throw new Exception("Wrong amount of jSON-datasets");
            }
            if( isset($data['gender']) && isset($data['age']) && isset($data['ui_timespan']) && isset($data['webauthn']) ) {
                $_SESSION['gender'] = htmlspecialchars($data['gender'], ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8', true);
                $_SESSION['age'] = intval($data['age']);
                $ui_timespan = floatval($data['ui_timespan']);
                $webauthn = json_encode($data['webauthn'], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE); // Nested JSON
                $webauthn_parsed = parseWebAuthn($webauthn);
                $raw_id = $webauthn_parsed["rawId"];
                $useragent = htmlspecialchars($_SERVER['HTTP_USER_AGENT'], ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8', true);
                $ip = htmlspecialchars($_SERVER['REMOTE_ADDR'], ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8', true);
                try {
                    insertRegistration($_SESSION['prolific'], $_SESSION['username'], $_SESSION['notification'], $_SESSION['counter_register_w'], $_SESSION['gender'], $_SESSION['age'], $ui_timespan, $raw_id, json_encode($webauthn_parsed, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE), $webauthn, $useragent, $ip);
                } catch (Exception $e) {
                    // We forward participants that already registered :)
                    if( $e->getMessage() != "Error: This participant already exists." ) {
                        throw new Exception($e->getMessage(), -1);
                    }
                }
                $_SESSION['status'] = "success";
                $output = array('status' => true, 'attempt' => $_SESSION['counter_register_w']);
            } else {
                $_SESSION['status'] = "not-successful";
                $output = array('status' => false, 'attempt' => $_SESSION['counter_register_w']);
            }
        } catch (Exception $e) {
            $_SESSION['status'] = "not-successful";
            $output = array('status' => false, 'attempt' => $_SESSION['counter_register_w']);
        } //end try
    } else {
        $_SESSION['status'] = "not-successful";
        $output = array('status' => false, 'attempt' => $_SESSION['counter_register_w']);
    } //end is $_POST['json'] available

    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($output);
}

function parseWebAuthn($json) {
    try {
        // Init
        $attestationStatementSupportManager = new AttestationStatementSupportManager();
        $attestationStatementSupportManager->add(new NoneAttestationStatementSupport());
        $attestationObjectLoader = new AttestationObjectLoader($attestationStatementSupportManager);
        $publicKeyCredentialLoader = new PublicKeyCredentialLoader($attestationObjectLoader);
        $pkc_array = array('Error' => 'Your authentication attempt was not successful. Please go back and try again.');

        // Load JSON from navigator.credentials.create()

        // 0) Parsed PublicKeyCredential
        $publicKeyCredential = $publicKeyCredentialLoader->load($json);
        $rawId = $publicKeyCredential->getRawId();
        $pkcdescriptior = $publicKeyCredential->getPublicKeyCredentialDescriptor();
        $pkc_desc_type = $pkcdescriptior->getType();
        $pkc_desc_id = $pkcdescriptior->getId();
        $pkc_desc_transports = $pkcdescriptior->getTransports();
        $response = $publicKeyCredential->getResponse();

        // 1) Parsed clientDataJSON
        $clientDataJSON = $response->getClientDataJSON();
        $cdj_rawdata = $clientDataJSON->getRawData();
        $cdj_challenge = $clientDataJSON->getChallenge();
        $cdj_origin = $clientDataJSON->getOrigin();
        $cdj_type = $clientDataJSON->getType();
        $cdj_tokenbinding = $clientDataJSON->getTokenBinding();
        $clientDataJSON_array = array(
            "challenge" => Base64Url::encode($cdj_challenge),
            "origin" => $cdj_origin,
            "type" => $cdj_type,
            "tokenBinding" => $cdj_tokenbinding
        );

        // 2) Parsed attestationObject
        $attestationObject = $response->getAttestationObject();
        $ato_raw = $attestationObject->getRawAttestationObject();
        $ato_attstm = $attestationObject->getAttStmt();
        $ato_attstm_array = $ato_attstm->jsonSerialize();
        $ato_authdata = $attestationObject->getAuthData();
        $ato_authdata_rpIdHash = $ato_authdata->getRpIdHash();
        $ato_authdata_isUserPresent = $ato_authdata->isUserPresent();
        $ato_authdata_reserved1 = $ato_authdata->getReservedForFutureUse1();
        $ato_authdata_isUserVerified = $ato_authdata->isUserVerified();
        $ato_authdata_reserved2 = $ato_authdata->getReservedForFutureUse2();
        $ato_authdata_hasAttestedCredentialData = $ato_authdata->hasAttestedCredentialData();
        $ato_authdata_extensionDataIncluded = $ato_authdata->hasExtensions();

        // 2) Parsed attestationObject -> Part B: Flags and SignCount
        $flags_array = array(
            "userPresent" => $ato_authdata_isUserPresent,
            "reserved1" => $ato_authdata_reserved1,
            "userVerified" => $ato_authdata_isUserVerified,
            "reserved2" => $ato_authdata_reserved2,
            "attestedCredentialData" => $ato_authdata_hasAttestedCredentialData,
            "extensionDataIncluded" => $ato_authdata_extensionDataIncluded
        );
        $ato_authdata_signCount = $ato_authdata->getSignCount();
        $ato_authdata_attestedCredentialData = $ato_authdata->getAttestedCredentialData();
        $ato_authdata_extensions = $ato_authdata->getExtensions();
        $attestedCredentialData_array = $ato_authdata_attestedCredentialData->jsonSerialize();
        $authData_array = array(
            "rpIdHash" => Base64Url::encode($ato_authdata_rpIdHash),
            "flags" => $flags_array,
            "signCount" => $ato_authdata_signCount,
            "attestedCredentialData" => $attestedCredentialData_array,
            "extensions" => $ato_authdata_extensions
        );
        $ato_attstm_array['authData'] = $authData_array;

        // 3) Assemble everything together
        $pkc_array = array(
            "rawId" => Base64Url::encode($rawId),
            "id" => Base64Url::encode($pkc_desc_id),
            "type" => $pkc_desc_type,
            "transports" => $pkc_desc_transports,
            "clientDataJSON" => $clientDataJSON_array,
            "attestationObject" => $ato_attstm_array
        );
        return $pkc_array;
    } catch (\Throwable $e) {
        throw new Exception("Error: Failed to parse WebAuthn registration data.", -1);
    }
}

function insertRegistration($prolific, $username, $notification, $attempt, $gender, $age, $ui_timespan, $raw_id, $json_parsed, $json_raw, $useragent, $ip) {
    /*
    CREATE TABLE webauthn_registrations (
        uid INT NOT NULL AUTO_INCREMENT PRIMARY KEY,
        prolific VARCHAR(64) NOT NULL,
        username VARCHAR(64) NOT NULL,
        notification VARCHAR(64) NOT NULL,
        attempt INT NOT NULL,
        gender VARCHAR(64) NOT NULL,
        age INT NOT NULL,
        ui_timespan INT NOT NULL,
        date_reg TIMESTAMP DEFAULT CURRENT_TIMESTAMP NOT NULL,
        raw_id VARCHAR(256) NOT NULL,
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
    $stmt = $pdo->prepare("SELECT `prolific` FROM `bio_webauthn_db`.`webauthn_registrations` WHERE prolific=:prolific LIMIT 1");
    $stmt->bindParam(':prolific', $prolific, PDO::PARAM_STR);
    $stmt->execute();
    $result = $stmt->fetchAll(PDO::FETCH_ASSOC);
    if($result === false) {
        throw new Exception("Error: Can not check whether this participant already exists.", -1);
    } else {
        if(count($result) != 0) {
            throw new Exception("Error: This participant already exists.", -1);
        } else {
            // Insert new participant into webauthn_registrations
            $stmt = $pdo->prepare("INSERT INTO `bio_webauthn_db`.`webauthn_registrations` (`prolific`,`username`,`notification`,`attempt`,`gender`,`age`,`ui_timespan`,`raw_id`,`json_parsed`,`json_raw`,`useragent`,`ip`) VALUES (:prolific,:username,:notification,:attempt,:gender,:age,:ui_timespan,:raw_id,:json_parsed,:json_raw,:useragent,:ip)");
            $stmt->bindParam(':prolific', $prolific, PDO::PARAM_STR);
            $stmt->bindParam(':username', $username, PDO::PARAM_STR);
            $stmt->bindParam(':notification', $notification, PDO::PARAM_STR);
            $stmt->bindParam(':attempt', $attempt, PDO::PARAM_INT);
            $stmt->bindParam(':gender', $gender, PDO::PARAM_STR);
            $stmt->bindParam(':age', $age, PDO::PARAM_INT);
            $stmt->bindParam(':ui_timespan', $ui_timespan, PDO::PARAM_INT);
            $stmt->bindParam(':raw_id', $raw_id, PDO::PARAM_STR);
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