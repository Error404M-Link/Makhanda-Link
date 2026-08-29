<?php
    // ########################## CONNECTION STRING TO THE ERROR 404 DATABASE ######################

    // Connection name = IS3 Prac
    // Hostname = is3-dev.ict.ru.ac.za
    // username = G23N8493
    // Password  = NyaHlo24!
    // Schema =  error404

    $serverName = "is3-dev.ict.ru.ac.za";
    $userName = "G23N8493";
    $password = "NyaHlo24!";
    $dbname = "error404";

    // Create Connection
    $conn = new mysqli($serverName, $userName, $password, $dbname);

    //check Connection
    if($conn->connect_error){
        die("Connection Failed: ".$conn->connect_error);
    }
    
    // echo "Connected Successfully";

?>