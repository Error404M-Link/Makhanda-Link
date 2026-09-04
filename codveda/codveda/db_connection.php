
 <?php
    $hostname = "is3-dev.ict.ru.ac.za";
    $user = "G23M3001"; 
    $password ="MlaLiz23!";
    $dbname = "g23m3001";



    // Create Connection
    $conn = new mysqli($hostname, $user, $password, $dbname);

    //check Connection
    if($conn->connect_error){
        die("Connection Failed: ".$conn->connect_error);
    }else{
        echo "Database connection established " .
    }

?>  



