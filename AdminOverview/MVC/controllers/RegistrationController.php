<?php

session_start();

require_once "../models/UserModel.php";


//create the user model
$userModel = new UserModel();


//check if the form was submitted
if ($_SERVER["REQUEST_METHOD"] !== "POST") {

    header("Location: ../../register.php");

    exit;

}


//get the user role from the registration form
$userRole = strtolower(
    trim($_POST["role"] ?? "")
);


//admin accounts cannot register
if ($userRole === "admin") {

    die("Administrators cannot register through this form.");

}


//check if the role is allowed
$allowedRoles = [
    "resident",
    "ward_councillor",
    "municipal_staff",
    "organisation"
];


if (!in_array($userRole, $allowedRoles, true)) {

    die("Please select a valid role.");

}


//get registration information
$firstName = trim(
    $_POST["first_name"] ?? ""
);

$surname = trim(
    $_POST["surname"] ?? ""
);

$username = trim(
    $_POST["username"] ?? ""
);

$contact = trim(
    $_POST["contact"] ?? ""
);

$password = $_POST["password"] ?? "";

$confirmPassword =
    $_POST["confirm_password"] ?? "";

$addressId =
    $_POST["address_id"] ?? "";

$address = trim(
    $_POST["address"] ?? ""
);


//check if required fields are filled in
if (
    $firstName === "" ||
    $surname === "" ||
    $username === "" ||
    $contact === "" ||
    $password === "" ||
    $confirmPassword === ""
) {

    die("Please complete all required fields.");

}


//check if the username is a valid email address
if (!filter_var(
    $username,
    FILTER_VALIDATE_EMAIL
)) {

    die("Please enter a valid email address.");

}


//check if the contact number has 10 digits
if (!preg_match(
    '/^[0-9]{10}$/',
    $contact
)) {

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


//set the ward id
$wardId = null;


//get the ward from the selected street for residents
if ($userRole === "resident") {

    //check if a street was selected
    if (empty($addressId)) {

        die("Please select a street.");

    }


    //get the selected street information
    $addressData =
        $userModel->getStreetWard(
            $addressId
        );


    //check if the selected street exists
    if (!$addressData) {

        die("Selected street was not found.");

    }


    //get the ward id
    $wardId =
        $addressData["ward_id"];

}


//ward councillors must select a ward
if ($userRole === "ward_councillor") {

    $wardId =
        $_POST["ward_id"] ?? "";


    //check if a ward was selected
    if (empty($wardId)) {

        die("Please select a ward.");

    }

}


//check if the username already exists
if (
    $userModel->usernameExists(
        $username
    )
) {

    die("Username already exists.");

}


//change the role name stored in the database
if ($userRole === "resident") {

    $databaseRole = "Resident";

}

elseif ($userRole === "ward_councillor") {

    $databaseRole =
        "Ward Councillor";

}

elseif ($userRole === "municipal_staff") {

    $databaseRole =
        "Municipal Staff";

}

elseif ($userRole === "organisation") {

    $databaseRole =
        "Organisation";

}


//set account verification status
if (
    $userRole === "resident" ||
    $userRole === "organisation"
) {

    //residents and organisations are automatically verified
    $verified = "1";

}

else {

    //councillors and municipal staff need administrator verification
    $verified = "0";

}


//set account to active
$active = 1;


//hash the password
$passwordHash = password_hash(
    $password,
    PASSWORD_DEFAULT
);


//start the transaction
$userModel->startTransaction();


try {

    //create the main user account
    $userModel->createUser(
        $username,
        $firstName,
        $surname,
        $contact,
        $passwordHash,
        $wardId,
        $databaseRole,
        $address,
        $active,
        $verified
    );


    //create the role-specific account
    if ($userRole === "resident") {

        $userModel->createCommunityMember(
            $username,
            $wardId,
            $addressId
        );

    }


    elseif ($userRole === "ward_councillor") {

        //get the date appointed
        $dateAppointed =
            $_POST["date_appointed"]
            ?? date("Y-m-d");


        $userModel->createWardCouncillor(
            $username,
            $wardId,
            $dateAppointed,
            $passwordHash,
            $contact
        );

    }


    elseif ($userRole === "municipal_staff") {

        //get municipal staff information
        $serviceId =
            $_POST["service_id"] ?? null;


        $division = trim(
            $_POST["division"] ?? ""
        );


        $availabilityStatus = trim(
            $_POST["availability_status"] ?? ""
        );


        $userModel->createMunicipalStaff(
            $username,
            $serviceId,
            $division,
            $availabilityStatus,
            $contact,
            $passwordHash
        );

    }


    elseif ($userRole === "organisation") {

        //organisation accounts currently only use the user table

    }


    //save all database changes
    $userModel->commit();


    //registration was successful
    header(
        "Location: ../../login.php?registered=1"
    );

    exit;

}


catch (Exception $e) {

    //undo all database changes if something failed
    $userModel->rollback();


    die($e->getMessage());

}

?>