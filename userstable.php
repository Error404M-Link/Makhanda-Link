<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Document</title>
</head>
<style>

     body{
            background-color: darkgreen;
        }
</style>
<body>

<?php

include 'dbconnection.php';

if(isset($_GET['idnumber'])){
    $user_id = $_GET['idnumber'];

    //using prepared statement to prevent SQL injection
    $sql_delete = "DELETE FROM users WHERE idnumber = ?";
    $stmt = $conn->prepare($sql_delete);
    $stmt->bind_param("s",$user_id); // "s" for strimg, use "i" if idnumber is integer
    $stmt->execute();

if($stmt->affected_rows > 0){
    echo "<script> alert ('Record was successfully deleted')</script>";
} else {
    echo "<script> alert ('No record found or deletion failed')</script>";
}
}

$sql = "SELECT * FROM users ";
$result = $conn->query($sql);

if($result->num_rows > 0){
    echo "<p><h2>All incident record(s) found in the table </h2></p>";
    echo "<center><table width =\"90%\" bgcolor = \"lightblue\"><tr bgcolor =\"orange\">
    <th>Id Number</th>
    <th>Incident Type</th>
    <th>Incident description</th>
    <th>Priority</th>
    <th>Physical Address</th>
    <th>Ticket Number</th>
    <th>Picture</th>
    <th>Update</th>
    <th>Delete</th>
    </tr>";

    while($row = $result->fetch_assoc()){?>
        <tr>
                <td><?php echo $row["idnumber"];?></td>
                <td><?php echo $row["incident_type"];?></td>
                <td><?php echo $row["incident_description"];?></td>
                <td><?php echo $row["priority"];?></td>
                <td><?php echo $row["ticketNum"];?></td>
                <td><img height="80" width="80" alt="img not found" src= "images/<?php echo $row["picture"];?></td>
               
                <td>
                  <a href =\"UpdateUserrecord.php?idnumber=<?php echo $row["idnumber"];?>><button>Update</button>
                  </a>
                </td>
                <td>
                   <a href=\"displayusers.php?idnumber=<?php echo $row["idnumber"];?>><button>Delete</button>
                </a>
                </td>
                </tr>
    <?php}}?>
    echo "</table></center>";
    //Navigation Menu

    echo"<p> <a href= \" "><button>Create Ticket </button></a></p>";
    echo"<p> <a href= \"ticketHistory.php\"><button>ticket History </button></a></p>";
    echo"<p> <a href= \"signup.html\"><button>Register </button></a></p>";
    echo"<p> <a href= \"signin.html\"><button>Back to Login </button></a></p>";

 else {
    echo "<p> No matching record found. Try using other search criteria </p>";
}
$conn->close();
echo "<p> <a href = \"recordSearchForm.html\"><button> Search a record </button></a></p>";

?>