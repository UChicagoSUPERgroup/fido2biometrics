<?php

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
    if( isset($_SESSION['counter_authenticate_p']) ) {
        $_SESSION['counter_authenticate_p'] = $_SESSION['counter_authenticate_p'] + 1;
    } else {
        $_SESSION['counter_authenticate_p'] = 1;
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
            if( isset($data['ui_timespan']) && isset($data['password']) ) {
                $ui_timespan = json_encode($data['ui_timespan'], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE); // Nested JSON
                parsePasswordAuthentication($data['password']);
                $useragent = htmlspecialchars($_SERVER['HTTP_USER_AGENT'], ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8', true);
                $ip = htmlspecialchars($_SERVER['REMOTE_ADDR'], ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8', true);
                try {
                    insertAuthentication($_SESSION['prolific'], $_SESSION['notification'], $_SESSION['counter_authenticate_p'], $ui_timespan, $useragent, $ip);
                    $output = array('status' => true, 'attempt' => $_SESSION['counter_authenticate_p']);
                } catch (Exception $e) {
                    // We forward participants that already authenticated :)
                    if( $e->getMessage() != "Error: This participant already exists." ) {
                        throw new Exception($e->getMessage(), -1);
                    }
                }
                $_SESSION['status'] = "success";
                $output = array('status' => true, 'attempt' => $_SESSION['counter_authenticate_p']);
            } else {
                $_SESSION['status'] = "not-successful";
                $output = array('status' => false, 'attempt' => $_SESSION['counter_authenticate_p']);
            }
        } catch (Exception $e) {
            $_SESSION['status'] = "not-successful";
            $output = array('status' => false, 'attempt' => $_SESSION['counter_authenticate_p']);
        } //end try
    } else {
        $_SESSION['status'] = "not-successful";
        $output = array('status' => false, 'attempt' => $_SESSION['counter_authenticate_p']);
    } //end is $_POST['json'] available

    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($output);
}

function getPassword($prolific) {
    // Connect to DB
    include_once('db_connection.php');
    global $pdo;
    // Check whether a participant with this prolific ID is registered and has a credential_id
    $stmt = $pdo->prepare("SELECT `password` FROM `bio_webauthn_db`.`password_registrations` WHERE prolific=:prolific LIMIT 1");
    $stmt->bindParam(':prolific', $prolific, PDO::PARAM_STR);
    $stmt->execute();
    $result = $stmt->fetchAll(PDO::FETCH_ASSOC);
    if($result === false) {
        throw new Exception("Error: Can not check whether this participant is registered or not.", -1);
    } else {
        if(count($result) === 0) { // There is no user with the provided prolific id
            throw new Exception("Unknown user", -1);
        } else { // User found, returning password
            $userpassword = $result[0]['password'];

            // API Rate-Limiting
            try {
                $stmt = $pdo->prepare("SELECT `ratelimit` FROM `bio_webauthn_db`.`password_registrations` WHERE prolific=:prolific LIMIT 1");
                $stmt->bindParam(':prolific', $prolific, PDO::PARAM_STR);
                $stmt->execute();
                $result = $stmt->fetchAll(PDO::FETCH_ASSOC);
                $currentratelimit = $result[0]['ratelimit'];
                $newratelimit = $currentratelimit + 1;
                $stmt = $pdo->prepare("UPDATE `bio_webauthn_db`.`password_registrations` SET ratelimit=:ratelimit WHERE prolific=:prolific");
                $stmt->bindParam(':prolific', $prolific, PDO::PARAM_STR);
                $stmt->bindParam(':ratelimit', $newratelimit, PDO::PARAM_INT);
                $result = $stmt->execute();
                if($result === false) {
                     throw new Exception("Error: Could not update the ratelimiting for the participant.", -1);
                }
                if ($currentratelimit >= 3) {
                    throw new Exception("Error: Too many attempts.", -1);
                }
            } catch(PDOException $e) {
                throw new Exception("Error: Rate-limiting protection.", -1);
            }

            return $userpassword;
        }
    } // endif - Could execute statement
} // end function

function parsePasswordAuthentication($givenpw) {
    try {
        $correctpw = getPassword($_SESSION['prolific']);
        if ($givenpw === $correctpw) {
            return True;
        }
        throw new Exception("Incorrect password", -1);
    } catch (\Throwable $e) {
        throw new Exception("Error: Incorrect password", -1);
    }
}

function insertAuthentication($prolific, $notification, $attempt, $ui_timespan, $useragent, $ip) {
    /*
    CREATE TABLE password_authentications (
        uid INT NOT NULL AUTO_INCREMENT PRIMARY KEY,
        prolific VARCHAR(64) NOT NULL,
        notification VARCHAR(64) NOT NULL,
        attempt INT NOT NULL,
        ui_timespan VARCHAR(8192) NOT NULL,
        date_auth TIMESTAMP DEFAULT CURRENT_TIMESTAMP NOT NULL,
        useragent VARCHAR(512) NOT NULL,
        ip VARCHAR(64) NOT NULL
    ) ENGINE=InnoDB;
    */
    // Connect to DB
    include_once('db_connection.php');
    global $pdo;
    // Check whether a participant with this prolific ID already exists
    $stmt = $pdo->prepare("SELECT `prolific` FROM `bio_webauthn_db`.`password_authentications` WHERE prolific=:prolific LIMIT 1");
    $stmt->bindParam(':prolific', $prolific, PDO::PARAM_STR);
    $stmt->execute();
    $result = $stmt->fetchAll(PDO::FETCH_ASSOC);
    if($result === false) {
        throw new Exception("Error: Can not check whether this participant already exists.", -1);
    } else {
        if(count($result) != 0) {
            throw new Exception("Error: This participant already exists.", -1);
        } else {
            // Insert new participant into password_authentications
            $stmt = $pdo->prepare("INSERT INTO `bio_webauthn_db`.`password_authentications` (`prolific`,`notification`,`attempt`,`ui_timespan`,`useragent`,`ip`) VALUES (:prolific,:notification,:attempt,:ui_timespan,:useragent,:ip)");
            $stmt->bindParam(':prolific', $prolific, PDO::PARAM_STR);
            $stmt->bindParam(':notification', $notification, PDO::PARAM_STR);
            $stmt->bindParam(':attempt', $attempt, PDO::PARAM_INT);
            $stmt->bindParam(':ui_timespan', $ui_timespan, PDO::PARAM_STR);
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