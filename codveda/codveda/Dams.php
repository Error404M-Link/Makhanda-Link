<?php
// connect to mysql server
$conn = new mysqli("localhost", "root", "");

//check connection

if($conn->connect_error){
    die("connection failed:" . $conn->connect_error);

}
$conn->select_db("dam_db");

//create database
$sql = "CREATE DATABASE IF NOT EXISTS dams
(dam_db INT AUTO_INCREMENT PRIMARY,
dam_name VARCHAR(100) NOT NULL,
location VARCHAR(100),
water_level INT,
capacity DECIMAL(10,2),
status VARCHAR(20),
last_updated DATE,
image VARCHAR(255)
)";

if($conn->query($sql)===TRUE){
    echo "table 'dams' created successfully";
} else{echo "error creating table:" . $conn->error;}

$conn->close();
?>

