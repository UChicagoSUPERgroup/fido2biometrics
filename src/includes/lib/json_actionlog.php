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
    // Request handling
    if( isset($_SESSION['prolific']) && isset($_POST['json']) ) {
        try {
            $data = json_decode($_POST['json'], True);
            if($data === NULL) {
                throw new Exception("Couldn't decode post-data to jSON");
            }
            if(count($data) != 1) {
                throw new Exception("Wrong amount of jSON-datasets".count($data));
            }
            if( isset($data['action_log']) ) {
                $action_log = json_encode($data['action_log']);
                $useragent = htmlspecialchars($_SERVER['HTTP_USER_AGENT'], ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8', true);
                $ip = htmlspecialchars($_SERVER['REMOTE_ADDR'], ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8', true);
                insertActionLog($_SESSION['prolific'], $action_log, $useragent, $ip);
                $output = array('status' => true);
            } else {
                $output = array('status' => false);
            }
        } catch (Exception $e) {
            $output = array('status' => false);
        } //end try
    } else {
        $output = array('status' => false);
    } //end is $_POST['data'] available

    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($output);
}

function insertActionLog($prolific, $action_log, $useragent, $ip) {
    /*
    CREATE TABLE ui_actions (
        aid INT NOT NULL AUTO_INCREMENT PRIMARY KEY,
        prolific VARCHAR(64) NOT NULL,
        action_log VARCHAR(4096) NOT NULL,
        useragent VARCHAR(512) NOT NULL,
        ip VARCHAR(64) NOT NULL
    ) ENGINE=InnoDB;
    */
    // Connect to DB
    include_once('db_connection.php');
    global $pdo;
    // Insert new action log into database table
    $stmt = $pdo->prepare("INSERT INTO `bio_webauthn_db`.`ui_actions` (`prolific`,`action_log`,`useragent`,`ip`) VALUES (:prolific,:action_log,:useragent,:ip)");
    $stmt->bindParam(':prolific', $prolific, PDO::PARAM_STR);
    $stmt->bindParam(':action_log', $action_log, PDO::PARAM_STR);
    $stmt->bindParam(':useragent', $useragent, PDO::PARAM_STR);
    $stmt->bindParam(':ip', $ip, PDO::PARAM_STR);
    $result = $stmt->execute();
    if($result === false) {
         throw new Exception("Error: Could not insert the action log.", -1);
    }
    return $result;
} // end function

?>