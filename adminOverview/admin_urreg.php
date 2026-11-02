<?php

session_start();

require_once "Error404Connection.php";

//check if the form was submitted
if ($_SERVER["REQUEST_METHOD"] !== "POST") {

    exit;

}

//get the user role from the registration form
$userRole = strtolower(trim($_POST["role"] ?? ""));

//check if the role is allowed
$allowedRoles = [
    "resident",
    "ward_councillor",
    "admin",
    "organisation"
];

if (!in_array($userRole, $allowedRoles, true)) {

    die("Please select a valid role.");

}

//get user details from the registration form
$firstName = trim($_POST["first_name"] ?? "");
$surname = trim($_POST["surname"] ?? "");
$username = trim($_POST["username"] ?? "");
$contact = trim($_POST["contact"] ?? "");

$password = $_POST["password"] ?? "";
$confirmPassword = $_POST["confirm_password"] ?? "";

$addressId = $_POST["address_id"] ?? "";
$address = trim($_POST["address"] ?? "");

//check if the username/email address is valid
if (!filter_var($username, FILTER_VALIDATE_EMAIL)) {

    die("Please enter a valid email address.");

}

//check if the contact number has 10 digits
if (!preg_match('/^[0-9]{10}$/', $contact)) {

    die("Contact number must contain exactly 10 digits.");

}

//check if the passwords match
if ($password !== $confirmPassword) {

    die("Passwords do not match.");

}

//check if the password is at least 8 characters
if (strlen($password) < 8) {

    die("Password must be at least 8 characters.");

}

//check if all required fields were entered
if (
    empty($firstName) ||
    empty($surname) ||
    empty($username) ||
    empty($contact) ||
    empty($password) ||
    empty($confirmPassword)
) {

    die("Please complete all required fields.");

}

//set account verification status
if ($userRole === "resident") {

    //community members are automatically verified
    $verified = 1;

} else {

    //admin, ward councillor and organisation accounts must be verified by admin
    $verified = 0;

}

//set the ward id
$wardId = null;

//get the ward from the selected street
if ($userRole === "resident") {

    //check if a street was selected
    if (empty($addressId)) {

        die("Please select a street.");

    }

    $sql = "SELECT ward_id, street_name
            FROM address_directory
            WHERE address_id = ?";

    $stmt = $conn->prepare($sql);

    if ($stmt === false) {

        die("Street SQL Error: " . $conn->error);

    }

    $stmt->bind_param("i", $addressId);

    $stmt->execute();

    $result = $stmt->get_result();

    $addressData = $result->fetch_assoc();

    $stmt->close();

    //check if the selected street exists
    if (!$addressData) {

        die("Selected street was not found.");

    }

    //check if the selected street has a ward
    if (empty($addressData["ward_id"])) {

        die("The selected street has not been assigned to a ward.");

    }

    //get the ward id
    $wardId = (int)$addressData["ward_id"];

}

//check if the username already exists
$sql = "SELECT username
        FROM user
        WHERE username = ?";

$stmt = $conn->prepare($sql);

if ($stmt === false) {

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

//change the role name stored in the database
if ($userRole === "resident") {

    $userRole = "Resident";

} elseif ($userRole === "ward_councillor") {

    $userRole = "Ward Councillor";

} elseif ($userRole === "admin") {

    $userRole = "Admin";

} elseif ($userRole === "organisation") {

    $userRole = "Organisation";

}

//hash the password
$passwordHash = password_hash($password, PASSWORD_DEFAULT);

//set account to active
$active = 1;

//set the ward value for the database
if ($wardId === null) {

    $wardValue = null;

} else {

    $wardValue = $wardId;

}

//insert the new user into the database
$sql = "INSERT INTO user
        (username, first_Name, surname, contact, password_hash,
         ward_id, User_role, address, active, verified)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";

$stmt = $conn->prepare($sql);

if ($stmt === false) {

    die("Registration SQL Error: " . $conn->error);

}

//bind the values to the SQL statement
$stmt->bind_param(
    "sssssissii",
    $username,
    $firstName,
    $surname,
    $contact,
    $passwordHash,
    $wardValue,
    $userRole,
    $address,
    $active,
    $verified
);

//execute the SQL statement
if ($stmt->execute()) {

    //registration was successful
    header("Location: login.php?registered=1");
    exit;

} else {

    //registration failed
    die("Registration failed: " . $stmt->error);

}

//close the connection
$stmt->close();
$conn->close();

?>