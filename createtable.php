<?php 
    // FAULT TICKET (Makhanda Community) TABLE
    include "connection.php";

    /*$sql = " CREATE TABLE faultTickets(
            ticketId INT NOT NULL AUTO_INCREMENT PRIMARY KEY,
            wardId INT NOT NULL,
            category VARCHAR(50) NOT NULL,
            latitude DECIMAL(10, 8) NOT NULL,
            longitude DECIMAL(11, 8) NOT NULL,
            description TEXT NOT NULL,
            status VARCHAR(20) DEFAULT 'pending',
            imagePath VARCHAR(255) DEFAULT NULL,
            createdAt TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

            -- foreign key constraint to the ward table

            FOREIGN KEY (wardId) REFERENCES wards(wardId) ON UPDATE CASCADE
        )";*/

    $sql = "ALTER TABLE faultTickets 
            ADD COLUMN locationDescription TEXT DEFAULT NULL AFTER wardId;";

    if($conn->query($sql)=== TRUE){
        echo "Table Created Successfully.";
    }else{
        echo "Error Creating table: ".$conn->error;
    }

    $conn->close();
    
?>