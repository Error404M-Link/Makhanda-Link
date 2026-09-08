<?php

session_start();

require_once "Error404Connection.php";

/* ==================================================
   CHECK THAT LOGIN FORM WAS SUBMITTED
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
   VALIDATE INPUT
================================================== */

if ($username === "" || $password === "") {
    die("Please enter your username and password.");
}


/* ==================================================
   FIND USER
================================================== */

$sql = "
    SELECT *
    FROM `user`
    WHERE username = ?
    LIMIT 1
";

$stmt = $conn->prepare($sql);

if (!$stmt) {
    die("Login SQL Error: " . $conn->error);
}

$stmt->bind_param("s", $username);

$stmt->execute();

$result = $stmt->get_result();

$user = $result->fetch_assoc();

$stmt->close();


/* ==================================================
   CHECK USER EXISTS
================================================== */

if (!$user) {
    die("Invalid username or password.");
}


/* ==================================================
   CHECK ACCOUNT IS ACTIVE
================================================== */

if (isset($user["active"]) && (int)$user["active"] !== 1) {
    die("Your account is currently inactive.");
}


/* ==================================================
   CHECK PASSWORD
================================================== */

if (!password_verify($password, $user["password_hash"])) {
    die("Invalid username or password.");
}


/* ==================================================
   LOGIN SUCCESS
================================================== */

session_regenerate_id(true);


/* ==================================================
   STORE USER INFORMATION IN SESSION
================================================== */

$_SESSION["logged_in"] = true;

$_SESSION["profile_id"] = $user["profile_id"] ?? null;

$_SESSION["username"] = $user["username"] ?? "";

$_SESSION["first_name"] = $user["first_Name"] ?? "";

$_SESSION["surname"] = $user["surname"] ?? "";

$_SESSION["organisation_name"] =
    $user["organisation_name"] ?? "";

$_SESSION["contact"] = $user["contact"] ?? "";

$_SESSION["user_role"] =
    $user["User_role"] ?? "";

$_SESSION["ward_id"] =
    $user["ward_id"] ?? null;

$_SESSION["address"] =
    $user["address"] ?? "";


/* ==================================================
   GET USER ROLE
================================================== */

$userRole = strtolower(
    trim($user["User_role"] ?? "")
);


/* ==================================================
   REDIRECT RESIDENT
================================================== */

if ($userRole === "resident") {

    header("Location: ../USER_DASHBOARD/residence.php");
    exit;

}


/* ==================================================
   REDIRECT ADMIN
================================================== */

if ($userRole === "admin") {

    header("Location: ../Admin Overview/admindashboard.php");
    exit;

}


/* ==================================================
   REDIRECT WARD COUNCILLOR
================================================== */

if ($userRole === "ward_councillor") {

    header("Location: ../ward_councillor_dashboard/wardcouncilorDashboard.html");
    exit;

}


/* ==================================================
   REDIRECT COMMUNICATION OFFICER
================================================== */

if ($userRole === "communication_officer") {

    header("Location: ../COMMUNICATION_OFFICER_DASHBOARD/communicationOfficerDashboard.php");
    exit;

}


/* ==================================================
   UNKNOWN ROLE
================================================== */

session_unset();
session_destroy();

die("Your account has an invalid user role.");