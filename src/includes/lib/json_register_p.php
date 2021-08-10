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
    if( isset($_SESSION['counter_register_p']) ) {
        $_SESSION['counter_register_p'] = $_SESSION['counter_register_p'] + 1;
    } else {
        $_SESSION['counter_register_p'] = 1;
    }

    // Request handling
    if( isset($_SESSION['prolific']) && isset($_POST['json']) ) {
        try {
            $data = json_decode($_POST['json'], True);
            if($data === NULL) {
                throw new Exception("Couldn't decode post-data to jSON");
            }
            if(count($data) != 5) {
                throw new Exception("Wrong amount of jSON-datasets");
            }
            if( isset($data['gender']) && isset($data['age']) && isset($data['ui_timespan']) && isset($data['password']) && isset($data['strength']) ) {
                $_SESSION['gender'] = htmlspecialchars($data['gender'], ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8', true);
                $_SESSION['age'] = intval($data['age']);
                $ui_timespan = json_encode($data['ui_timespan'], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE); // Nested JSON
                $strength = floatval($data['strength']);
                $useragent = htmlspecialchars($_SERVER['HTTP_USER_AGENT'], ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8', true);
                $ip = htmlspecialchars($_SERVER['REMOTE_ADDR'], ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8', true);
                try {
                    insertRegistration($_SESSION['prolific'], $_SESSION['notification'], $_SESSION['counter_register_p'], $_SESSION['gender'], $_SESSION['age'], $ui_timespan, $data['password'], $strength, $useragent, $ip);
                } catch (Exception $e) {
                    // We forward participants that already registered :)
                    if( $e->getMessage() != "Error: This participant already exists." ) {
                        throw new Exception($e->getMessage(), -1);
                    }
                }
                $_SESSION['status'] = "success";
                $output = array('status' => true, 'attempt' => $_SESSION['counter_register_p']);
            } else {
                $_SESSION['status'] = "not-successful";
                $output = array('status' => false, 'attempt' => $_SESSION['counter_register_p']);
            }
        } catch (Exception $e) {
            $_SESSION['status'] = "not-successful";
            $output = array('status' => false, 'attempt' => $_SESSION['counter_register_p']);
        } //end try
    } else {
        $_SESSION['status'] = "not-successful";
        $output = array('status' => false, 'attempt' => $_SESSION['counter_register_p']);
    } //end is $_POST['json'] available

    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($output);
}

function insertRegistration($prolific, $notification, $attempt, $gender, $age, $ui_timespan, $password, $strength, $useragent, $ip) {
    /*
    CREATE TABLE password_registrations (
        uid INT NOT NULL AUTO_INCREMENT PRIMARY KEY,
        prolific VARCHAR(64) NOT NULL,
        notification VARCHAR(64) NOT NULL,
        attempt INT NOT NULL,
        ratelimit INT DEFAULT 0,
        gender VARCHAR(64) NOT NULL,
        age INT NOT NULL,
        ui_timespan VARCHAR(8192) NOT NULL,
        date_reg TIMESTAMP DEFAULT CURRENT_TIMESTAMP NOT NULL,
        password VARCHAR(512) NOT NULL,
        strength VARCHAR(64) NOT NULL,
        useragent VARCHAR(512) NOT NULL,
        ip VARCHAR(64) NOT NULL
    ) ENGINE=InnoDB;
    */
    // Connect to DB
    include_once('db_connection.php');
    global $pdo;
    // Check whether a participant with this prolific ID already exists
    $stmt = $pdo->prepare("SELECT `prolific` FROM `bio_webauthn_db`.`password_registrations` WHERE prolific=:prolific LIMIT 1");
    $stmt->bindParam(':prolific', $prolific, PDO::PARAM_STR);
    $stmt->execute();
    $result = $stmt->fetchAll(PDO::FETCH_ASSOC);
    if($result === false) {
        throw new Exception("Error: Can not check whether this participant already exists.", -1);
    } else {
        if(count($result) != 0) {
            throw new Exception("Error: This participant already exists.", -1);
        } else {
            // Insert new participant into password_registrations
            $stmt = $pdo->prepare("INSERT INTO `bio_webauthn_db`.`password_registrations` (`prolific`,`notification`,`attempt`,`gender`,`age`,`ui_timespan`,`password`,`strength`,`useragent`,`ip`) VALUES (:prolific,:notification,:attempt,:gender,:age,:ui_timespan,:password,:strength,:useragent,:ip)");
            $stmt->bindParam(':prolific', $prolific, PDO::PARAM_STR);
            $stmt->bindParam(':notification', $notification, PDO::PARAM_STR);
            $stmt->bindParam(':attempt', $attempt, PDO::PARAM_INT);
            $stmt->bindParam(':gender', $gender, PDO::PARAM_STR);
            $stmt->bindParam(':age', $age, PDO::PARAM_INT);
            $stmt->bindParam(':ui_timespan', $ui_timespan, PDO::PARAM_STR);
            $stmt->bindParam(':password', $password, PDO::PARAM_STR);
            $stmt->bindParam(':strength', $strength, PDO::PARAM_STR);
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