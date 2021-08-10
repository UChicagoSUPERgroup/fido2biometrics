<?php
    // Some session management
    if (session_status() == PHP_SESSION_NONE) {
        date_default_timezone_set('UTC');
        session_set_cookie_params(7200, '/', 'example.tech', TRUE, TRUE);
        session_name('ExampleTech');
        session_start();
    }
?>

<!doctype html>
<html lang="en">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
        <meta name="description" content="Example Tech"/>
        <meta name="keywords" content="research, survey, tech"/>
        <meta name="author" content="Example Tech">
        <link rel="shortcut icon" href="../ico/favicon.ico">
        <title>Example Tech</title>
        <link href="../css/thirdparty/bootstrap.min.css" rel="stylesheet">
        <link href="../css/custom/main.css" rel="stylesheet">
        <link href="../css/thirdparty/fontawesome.all.min.css" rel="stylesheet">
        <script src="../js/thirdparty/jquery.min.js"></script>
        <script>window.jQuery || document.write('<script src="../js/thirdparty/jquery.min.js"><\/script>')</script>
        <script src="../js/thirdparty/bootstrap.min.js"></script>
        <script src="../js/thirdparty/lockr.js"></script>
        <script src="../js/custom/log.js"></script>
        <script src="../js/thirdparty/ua-parser.min.js"></script>
    </head>
    <body>
        <nav class="navbar navbar-expand-md navbar-light bg-light fixed-top">
            <div class="container">
                <a class="navbar-brand" href="#">Example Tech</a>
                <button class="navbar-toggler" type="button" data-toggle="collapse" data-target="#navbarsExampleDefault" aria-controls="navbarsExampleDefault" aria-expanded="false" aria-label="Toggle navigation">
                    <span class="navbar-toggler-icon"></span>
                </button>
                <div class="collapse navbar-collapse" id="navbarsExampleDefault">
                    <ul class="navbar-nav">
                        <li class="nav-item"><a class="nav-link text-secondary" onclick="logEvent('clicked()->Help')" href="help_rw.php">Help</a></li>
                    </ul>
              </div>
            </div><!-- end container -->
        </nav>