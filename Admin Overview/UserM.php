<?php
include 'dbconnections.php'; 

if (isset($_GET['idnumber'])) {
    $user_id = $_GET['idnumber'];

  
    $sql_delete = "DELETE FROM myusers WHERE idnumber = ?";
    $stmt = $conn->prepare($sql_delete);
    $stmt->bind_param("s", $user_id); 
    $stmt->execute();

    if ($stmt->affected_rows > 0) {
        echo "<script> alert('Record was successfully deleted') </script>";
    } else {
        echo "<script> alert('No record found or deletion failed') </script>";
    }
}

$sql = "SELECT * FROM myusers";
$result = $conn->query($sql);

if ($result->num_rows > 0) {    

    
    echo "<p><h2>All record(s) found in the table </h2></p>";
    echo "<center><table width=\"90%\" bgcolor=\"darkgreen\"><tr bgcolor=\"peach\">
        <th>Id number</th>
        <th>First name</th>
        <th>Surname</th>
        <th>Gender</th>
        <th>User Name</th>
        <th>Password</th>
        <th>Role</th>
        <th>Ward</th>
        <th>Address</th>
        <th>Contact</th>
        <th>picture</th>
        <th>Email</th>
        <th>Update</th>
        <th>Delete</th>
    </tr>";

    
    while ($row = $result->fetch_assoc()) {
        echo "<tr><td>" . $row["idnumber"] . "</td><td>" 
            . $row["firstname"] 
            . "</td><td>" . $row["surname"] . "</td><td>" . $row["gender"] . "</td><td>"
            . $row["username"] . "</td><td>"
            . $row["pword"] . "</td><td>"
            . $row["userrole"] . "</td><td>"
            . ($row["ward"] ?? ""). "</td><td>"
            . $row["physAddress"] . "</td><td>"
            . $row["contact"] . "</td><td>"
             . ($row["picture"]?? ""). "</td><td>"
            . $row["email"] . "</td><td>

            <a href=\"UpdateUserrecord.php?idnumber="
            . $row["idnumber"] . "\"><button>Update</button></a>
            </td>
<td>
            <a href=\"displayusers.php?idnumber="
            . $row["idnumber"] . "\"><button>Delete</button></a>
            </td></tr>";
    }

    echo "</table></center>";
    echo '<p><a href="CreateTicket.php"><button type="button">Create Ticket</button></a></p>';
echo '<p><a href="ticketHistory.php"><button type="button">Ticket History</button></a></p>';
echo '<p><a href="signup.html"><button type="button">Register</button></a></p>';
echo '<p><a href="signin.html"><button type="button">Back to Login</button></a></p>';



} else {
    echo "<p>No matching record found. Try using other search criteria </p>";
}

$conn->close();

echo "<p><a href=\"recordSearchForm.html\"><button>Search a record </button></a></p>";

?>