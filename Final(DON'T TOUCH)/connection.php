<?php
    // ESTABLISH CONNECTION WITH THE DATABASE


    // Connection name = IS3 Prac
    // Hostname = is3-dev.ict.ru.ac.za
    // username = G23N8493
    // Password  = NyaHlo24!
    // Schema = g23n8493

    $serverName = "is3-dev.ict.ru.ac.za";
    $userName = "G23N8493";
    $password = "NyaHlo24!";
    $dbname = "g23n8493";

    // Create Connection
    $conn = new mysqli($serverName, $userName, $password, $dbname);

    //check Connection
    if($conn->connect_error){
        die("Connection Failed: ".$conn->connect_error);
    }
    
    // echo "Connected Successfully";

?>