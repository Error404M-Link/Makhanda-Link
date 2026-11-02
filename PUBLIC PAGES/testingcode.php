<?php

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $username = $_POST["username"] ?? "";

    echo "Continue was clicked!";
    echo "<br>";
    echo "Username: " . htmlspecialchars($username);

}

?>