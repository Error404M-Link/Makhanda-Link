<?php

session_start();

require_once "db.php";


/* ==================================================
   CHECK REQUEST
================================================== */

if ($_SERVER["REQUEST_METHOD"] !== "POST") {

    header("Location: login.php");

    exit;
}


/* ==================================================
   GET LOGIN DETAILS
================================================== */

$username = trim($_POST["username"] ?? "");

$password = $_POST["password"] ?? "";


/* ==================================================
   CHECK EMPTY FIELDS
================================================== */

if ($username === "" || $password === "") {

    die("Please enter your username and password.");

}


/* ==================================================
   FIND USER
================================================== */
/*
   COULD NOT LOGIN => REMOVED ORGANISATION COLUMN SINCE IT DOESNT EXIST IN THE TABLE YET
*/
$sql = "
    SELECT
        profile_id,
        username,
        first_Name,
        surname,
        
        contact,
        password_hash,
        User_role,
        ward_id,
        address,
        active
    FROM `user`
    WHERE username = ?
";


$stmt = $conn->prepare($sql);


if (!$stmt) {

    die("Login SQL Error: " . $conn->error);

}


$stmt->bind_param("s", $username);

$stmt->execute();

$result = $stmt->get_result();


/* ==================================================
   CHECK USER EXISTS
================================================== */

if ($result->num_rows === 0) {

    $stmt->close();

    die("Incorrect username or password.");

}


$user = $result->fetch_assoc();

$stmt->close();


/* ==================================================
   CHECK ACCOUNT ACTIVE
================================================== */

if ((int)$user["active"] !== 1) {

    die("Your account is not active. Please contact the administrator.");

}


/* ==================================================
   CHECK PASSWORD
================================================== */

if (!password_verify($password, $user["password_hash"])) {

    die("Incorrect username or password.");

}


/* ==================================================
   LOGIN SUCCESS
================================================== */

session_regenerate_id(true);


/* ==================================================
   STORE USER INFORMATION IN SESSION
================================================== */

$_SESSION["logged_in"] = true;

$_SESSION["profile_id"] = $user["profile_id"];

$_SESSION["username"] = $user["username"];

$_SESSION["first_name"] = $user["first_Name"];

$_SESSION["surname"] = $user["surname"];

// COMMENTED OUT ORGANISATION, DOESNT EXIST AS OF YET.

// $_SESSION["organisation"] = $user["organisation"];

$_SESSION["contact"] = $user["contact"];

$_SESSION["user_role"] = $user["User_role"];

$_SESSION["ward_id"] = $user["ward_id"];

$_SESSION["address"] = $user["address"];


/* ==================================================
   DIRECT USER TO CORRECT DASHBOARD
================================================== */

$userRole = strtolower(trim($user["User_role"]));


/* --------------------------------------------------
   RESIDENT
-------------------------------------------------- */

if ($userRole === "resident") {
   /* 
      IF USER EXISTS, 
      REDIRECTING TO THE USER_DASHBOARD TO TEST THE REPORT TICKET FORM
      
      => YOU CAN DELETE THIS FAITH, I WAS JUST TESTING REPORT TICKET :)
      => DON'T FORGET I REMOVED THE ORGANISATION INORDER FOR ME TO LOGIN
      
   */
    header("Location: ../USER_DASHBOARD/reportFaultTicketForm.php");

    exit;
}


/* --------------------------------------------------
   ORGANISATION
-------------------------------------------------- */

if ($userRole === "organisation") {

    header("Location: organisationDashboard.php");

    exit;
}


/* --------------------------------------------------
   ADMIN
-------------------------------------------------- */

if ($userRole === "admin") {

    header("Location: adminDashboard.php");

    exit;
}


/* --------------------------------------------------
   MUNICIPALITY
-------------------------------------------------- */

if ($userRole === "municipality") {

    header("Location: municipalityDashboard.php");

    exit;
}


/* --------------------------------------------------
   OTHER ROLES
-------------------------------------------------- */

if ($userRole === "ward councillor") {

    header("Location: councillorDashboard.php");

    exit;
}


/* ==================================================
   UNKNOWN ROLE
================================================== */

die(
    "Login successful, but your account does not have a valid dashboard role. " .
    "Current role: " . htmlspecialchars($user["User_role"])
);

?>