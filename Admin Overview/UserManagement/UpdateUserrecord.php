<?php

include 'dbconnection.php';

if($_SERVER["REQUEST_METHOD"] == "GET"){
    if(!isset($_GET['idnumber'])){
        die("No user selected");
    }

$user_id = $_GET['idnumber']; //Extract idnumber from the url
$sql_select = "SELECT * FROM systemusers WHERE idnumber = '$user_id'";
$result = $conn->query($sql_select);

if($result && $result->num_rows > 0){
   $row2 = $result->fetch_assoc();

   //echo "user idnumber = ".$user_id." extracted from the URL using GET </br>"
   echo "user idnumber = ".$row2['idnumber']." extracted from database using query";

} else {
    die ("Record not found.");
}

}

if($_SERVER["REQUEST_METHOD"] == "POST"){

    //Initialize variables with isset() and trim()
    $idnumber = isset($_POST["idnumber"])?intval($_POST["idnumber"]):0;
    $surname = isset($_POST["surname"])?trim($_POST["surname"]):"";
    $firstname = isset($_POST["firstname"])?trim($_POST["firstname"]):"";
    $gender= isset($_POST["gender"])?trim($_POST["gender"]):"";
    $userrole = isset($_POST["userrole"])?trim($_POST["userrole"]):"";
    $username = isset($_POST["username"])?trim($_POST["username"]):"";
    $pword = isset($_POST["pword"])?$_POST["pword"]:"";
    $physAddress = isset($_POST["physAddress"])?trim($_POST["physAddress"]):"";
    $contact = isset($_POST["contact"])?trim($_POST["contact"]):"";
    $email = isset($_POST["email"])?filter_var($_POST["email"],FILTER_SANITIZE_EMAIL):""; //sanitises addresses by removing unwanted characters
    $hash_pword = password_hash($pword, PASSWORD_DEFAULT);
    $ward = isset($_POST["ward"])?trim($_POST["ward"]):"";
    $sql_update = "UPDATE systemusers SET surname = '$surname', firstname = '$firstname',
                   gender = '$gender', userrole = '$userrole', username = '$username',
                   hash_pword = '$hash_pword', physAddress = '$physAddress', contact = '$contact',
                   email = '$email', ward = '$ward' WHERE idnumber = '$idnumber'";

    $query_execute = $conn->query($sql_update);

    if($query_execute){
        
        echo "<script> alert ('Record successfully updated')</script>";
        header("Location:displayusers.php");

    } else {
        echo "<script>alert('Record update failed. Retry')</script>";
        header("Location: displayusers.php");
    }

}
$conn->close();

?>

<form action="" method="POST">

        <table bgcolor = "lightblue" width ="30%" >

            <tr> 
                <th colspan ="2"><h3>COMPLETE USER UPDATE FORM</h3> </th>
            </tr>

            <tr>
                <td>ID NUMBER</td>
                <td> <input type = "text" name = "idnumber" maxlength ="35" size = "20" value ="<?php echo $row2['idnumber'];?>" required></td>
            </tr>

             <tr>
                <td>SURNAME</td>
                <td> <input type = "text" name = "surname" maxlength ="35" size = "20" value ="<?php echo $row2['surname'];?>" required></td>
            </tr>

             <tr>
                <td>FIRST NAME</td>
                <td> <input type = "text" name = "firstname" maxlength ="35" size = "20" value ="<?php echo $row2['firstname'];?>" required></td>
            </tr>

             <tr>
                <td>GENDER</td>
                <td> <input type = "text" name = "gender" maxlength ="35" size = "20" value ="<?php echo $row2['gender'];?>" required></td>
            </tr>

             <tr>
                <td>USER ROLE</td>
                <td> <input type = "text" name = "userrole" maxlength ="35" size = "20" value ="<?php echo $row2['userrole'];?>" required></td>
            </tr>

            <!--username cannor be updated

             <tr>
                <td>USERNAME</td>
                <td> <input type = "text" name = "username" maxlength ="35" size = "20" value ="<?php echo $row2['pword'];?>" required></td>
            </tr>-->

             <tr>
                <td>PASSWORD</td>
                <td> <input type = "password" name = "hash_pword" maxlength ="35" size = "20"></td>
            </tr>

             <tr>
                <td>WARD</td>
                <td> <input type = "text" name = "ward" maxlength ="35" size = "20" value ="<?php echo $row2['ward'];?>" required></td>
            </tr>

             <tr>
                <td>PHYSICAL ADDRESS</td>
                <td> <textarea rows = "4" cols= "20" name = "physAddress" required><?php echo $row2['physAddress'];?></textarea></td>
            </tr>

             <tr>
                <td>CONTACT </td>
                <td> <input type = "text" name = "contact" maxlength ="10" size = "15" value ="<?php echo $row2['contact'];?>" required></td>
            </tr>

             <tr>
                <td>EMAIL ADDRESS</td>
                <td> <input type = "email" name = "email" maxlength ="35" size = "20" value ="<?php echo $row2['email'];?>" required></td>
            </tr>

            <tr>
                <td> <input type = "submit" value = "Update User"></td>
                <td> <input type = "reset" value = "Clear Form"></td>
            </tr>
        </table>
    </form>
