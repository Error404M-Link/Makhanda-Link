<?php

session_start();

require_once "Error404Connection.php";


/* 
   CHECK REQUEST
 */

if ($_SERVER["REQUEST_METHOD"] !== "POST") {

    header("Location: register.php");
    exit;

}


/*
   GET ACCOUNT TYPE
 */

$accountType = $_POST["accountType"] ?? "";


/* 
   ONLY TEST RESIDENT FOR NOW
 */

if ($accountType !== "resident") {

    die("For now, please select Resident.");

}


/* 
   GET RESIDENT DETAILS
 */

$firstName = trim($_POST["first_name"] ?? "");

$surname = trim($_POST["surname"] ?? "");

$username = trim($_POST["username"] ?? "");

$contact = trim($_POST["contact"] ?? "");

$password = $_POST["password"] ?? "";

$confirmPassword = $_POST["confirm_password"] ?? "";

$addressId = $_POST["address_id"] ?? "";

$address = trim($_POST["address"] ?? "");


/* 
   REQUIRED FIELDS
*/

if (
    empty($firstName) ||
    empty($surname) ||
    empty($username) ||
    empty($contact) ||
    empty($password) ||
    empty($confirmPassword) ||
    empty($addressId)
) {

    die("Please complete all required fields.");

}


/* 
   CHECK CONTACT
 */

if (!preg_match('/^[0-9]{10}$/', $contact)) {

    die("Contact number must contain exactly 10 digits.");

}


/* CHECK PASSWORD*/

if ($password !== $confirmPassword) {

    die("Passwords do not match.");

}


if (strlen($password) < 8) {

    die("Password must be at least 8 characters.");

}


/* 
   GET WARD FROM STREET
*/

$stmt = $conn->prepare("
    SELECT ward_id, street_name
    FROM address_directory
    WHERE address_id = ?
");

if (!$stmt) {

    die("Street SQL Error: " . $conn->error);

}


$stmt->bind_param("i", $addressId);

$stmt->execute();

$result = $stmt->get_result();

$addressData = $result->fetch_assoc();

$stmt->close();


/* 
   CHECK STREET
 */

if (!$addressData) {

    die("Selected street was not found.");

}


$wardId = $addressData["ward_id"];


/* 
   CHECK USERNAME */

$stmt = $conn->prepare("
    SELECT username
    FROM user
    WHERE username = ?
");

if (!$stmt) {

    die("Username SQL Error: " . $conn->error);

}


$stmt->bind_param("s", $username);

$stmt->execute();

$result = $stmt->get_result();


if ($result->num_rows > 0) {

    $stmt->close();

    die("Username already exists.");

}


$stmt->close();


/* 
   HASH PASSWORD
*/

$passwordHash = password_hash(
    $password,
    PASSWORD_DEFAULT
);



/* 
   GET USER ROLE
 */

$userRole = trim($_POST["role"] ?? "");


/* 
   VALIDATE USER ROLE
 */

$allowedRoles = [
    "resident",
    "admin",
    "ward_councillor",
    "communication_officer"
];

if (!in_array($userRole, $allowedRoles, true)) {

    die("Please select a valid role.");

}


$active = 1;
/* 
   INSERT USER
 */

$sql = "
    INSERT INTO user
    (
        username,
        first_Name,
        surname,
        contact,
        password_hash,
        ward_id,
        User_role,
        address,
        active
    )
    VALUES
    (?, ?, ?, ?, ?, ?, ?, ?, ?)
";


$stmt = $conn->prepare($sql);


if (!$stmt) {

    die("Registration SQL Error: " . $conn->error);

}


/* 
   username      = s
   firstName     = s
   surname       = s
   contact       = s
   passwordHash  = s
   wardId        = i
   userRole      = s
   address       = s
   active        = i
*/

$stmt->bind_param(
    "sssssissi",
    $username,
    $firstName,
    $surname,
    $contact,
    $passwordHash,
    $wardId,
    $userRole,
    $address,
    $active
);


/* 
   EXECUTE
 */

if (!$stmt->execute()) {

    die(
        "Registration failed: " .
        $stmt->error
    );

}


$stmt->close();

$conn->close();


/*
   SUCCESS
 */

header("Location: login.php?registered=1");

exit;

?>