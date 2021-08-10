<?php
    global $pdo;
    // Connect to DB
    try {
        $pdo = new PDO('mysql:host=127.0.0.1;dbname=bio_webauthn_db;charset=utf8', 'bio_webauthn_user', 'some-fake-password-replace-me');
    } catch(PDOException $e) {
        throw new Exception("Error: Can not connect to database.", -1);
    }
?>