<?php
    session_start();

    // CREATE SESSION VARIABLE
    $_SESSION = array();

    // IF SESSION COOKIES EXIST, DESTROY THEM
    if (ini_get("session.use_cookies")) {
        $params = session_get_cookie_params();
        setcookie(
            session_name(),
            '',
            time() - 42000,
            $params["path"],
            $params["domain"],
            $params["secure"],
            $params["httponly"]
        );
    }

    // DESTROY SESSION
    session_destroy();

    // Redirect to the login page (change to your actual login page file if needed)
    header("Location: ../PUBLIC PAGES/login.php");
    exit;
?>