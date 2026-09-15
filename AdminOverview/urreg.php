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
    "municipal_staff",
    "admin",
    "organisation"
];

if (!in_array($userRole, $allowedRoles, true)) {

    die("Please select a valid role.");

}

//start the transaction
$conn->begin_transaction();

try {

    //get user details from the registration form
    $firstName = trim($_POST["first_name"] ?? "");
    $surname = trim($_POST["surname"] ?? "");
    $username = trim($_POST["username"] ?? "");
    $contact = trim($_POST["contact"] ?? "");

    $password = $_POST["password"] ?? "";
    $confirmPassword = $_POST["confirm_password"] ?? "";

    $addressId = $_POST["address_id"] ?? "";
    $address = trim($_POST["address"] ?? "");

    //get organisation details if the account is an organisation
    if ($userRole === "organisation") {

        $organisationName = trim(
            $_POST["organisation_name"] ?? ""
        );

        $username = trim(
            $_POST["organisation_username"] ?? ""
        );

        $contact = trim(
            $_POST["organisation_contact"] ?? ""
        );

        $password = $_POST["organisation_password"] ?? "";

        $confirmPassword =
            $_POST["organisation_confirm_password"] ?? "";

        $address = trim(
            $_POST["organisation_address"] ?? ""
        );

        $firstName = $organisationName;
        $surname = "";

    }

    //check if all required fields were entered
    if (
        empty($username) ||
        empty($contact) ||
        empty($password) ||
        empty($confirmPassword)
    ) {

        throw new Exception(
            "Please complete all required fields."
        );

    }

    //check if the username is a valid email address
    if (!filter_var($username, FILTER_VALIDATE_EMAIL)) {

        throw new Exception(
            "Please enter a valid email address."
        );

    }

    //check if the contact number has 10 digits
    if (!preg_match('/^[0-9]{10}$/', $contact)) {

        throw new Exception(
            "Contact number must contain exactly 10 digits."
        );

    }

    //check if the passwords match
    if ($password !== $confirmPassword) {

        throw new Exception(
            "Passwords do not match."
        );

    }

    //check if the password is at least 8 characters
    if (strlen($password) < 8) {

        throw new Exception(
            "Password must be at least 8 characters."
        );

    }

    //set the ward id
    $wardId = null;

    //get the ward from the selected street for residents
    if ($userRole === "resident") {

        //check if a street was selected
        if (empty($addressId)) {

            throw new Exception(
                "Please select a street."
            );

        }

        //get the ward belonging to the selected street
        $sql = "SELECT ward_id, street_name
                FROM address_directory
                WHERE address_id = ?";

        $stmt = $conn->prepare($sql);

        if ($stmt === false) {

            throw new Exception(
                "Street SQL Error: " . $conn->error
            );

        }

        $stmt->bind_param(
            "i",
            $addressId
        );

        $stmt->execute();

        $result = $stmt->get_result();

        $addressData = $result->fetch_assoc();

        $stmt->close();

        //check if the selected street exists
        if (!$addressData) {

            throw new Exception(
                "Selected street was not found."
            );

        }

        //get the ward id
        $wardId = $addressData["ward_id"];

    }

    //get the ward for a ward councillor
    if ($userRole === "ward_councillor") {

        $wardId = $_POST["ward_id"] ?? "";

        //check if a ward was selected
        if (empty($wardId)) {

            throw new Exception(
                "Please select a ward."
            );

        }

    }

    //check if the username already exists
    $sql = "SELECT profile_id
            FROM user
            WHERE username = ?";

    $stmt = $conn->prepare($sql);

    if ($stmt === false) {

        throw new Exception(
            "Username SQL Error: " . $conn->error
        );

    }

    $stmt->bind_param(
        "s",
        $username
    );

    $stmt->execute();

    $result = $stmt->get_result();

    if ($result->num_rows > 0) {

        $stmt->close();

        throw new Exception(
            "Username already exists."
        );

    }

    $stmt->close();

    //change the role name stored in the database
    if ($userRole === "resident") {

        $databaseRole = "Resident";

    } elseif ($userRole === "ward_councillor") {

        $databaseRole = "Ward Councillor";

    } elseif ($userRole === "municipal_staff") {

        $databaseRole = "Municipal Staff";

    } elseif ($userRole === "admin") {

        $databaseRole = "Admin";

    } elseif ($userRole === "organisation") {

        $databaseRole = "Organisation";

    }

    //set account verification status
if (
    $userRole === "resident" ||
    $userRole === "organisation"
) {

    //community members and organisations are automatically verified
    $verified = "1";

} else {

    //ward councillors and municipal staff must be verified by an administrator
    $verified = "0";

}

    //set account to active
    $active = 1;

    //hash the password
    $passwordHash = password_hash(
        $password,
        PASSWORD_DEFAULT
    );

    //insert the new user into the user table
    $sql = "INSERT INTO user
            (
                username,
                first_Name,
                surname,
                contact,
                password_hash,
                ward_id,
                User_role,
                address,
                active,
                verified
            )
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";

    $stmt = $conn->prepare($sql);

    if ($stmt === false) {

        throw new Exception(
            "User SQL Error: " . $conn->error
        );

    }

    $stmt->bind_param(
        "sssssissis",
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

    //execute the user insert
    if (!$stmt->execute()) {

        throw new Exception(
            "User registration failed: "
            . $stmt->error
        );

    }

    //get the generated profile id
    $profileId = $conn->insert_id;

    $stmt->close();

    //check the user role and populate the correct table

    //insert resident into the community_member table
    if ($userRole === "resident") {

        $sql = "INSERT INTO community_member
                (
                    username,
                    ward_id,
                    address_id
                )
                VALUES (?, ?, ?)";

        $stmt = $conn->prepare($sql);

        if ($stmt === false) {

            throw new Exception(
                "Community Member SQL Error: "
                . $conn->error
            );

        }

        $stmt->bind_param(
            "sii",
            $username,
            $wardId,
            $addressId
        );

        //execute the community member insert
        if (!$stmt->execute()) {

            throw new Exception(
                "Community member registration failed: "
                . $stmt->error
            );

        }

        $stmt->close();

    }

    //insert ward councillor into the ward_councillor table
    elseif ($userRole === "ward_councillor") {

        //get the date appointed
        $dateAppointed =
            $_POST["date_appointed"]
            ?? date("Y-m-d");

        $sql = "INSERT INTO ward_councillor
                (
                    username,
                    ward_id,
                    date_appointed,
                    password_hashed,
                    contact
                )
                VALUES (?, ?, ?, ?, ?)";

        $stmt = $conn->prepare($sql);

        if ($stmt === false) {

            throw new Exception(
                "Ward Councillor SQL Error: "
                . $conn->error
            );

        }

        $stmt->bind_param(
            "sisss",
            $username,
            $wardId,
            $dateAppointed,
            $passwordHash,
            $contact
        );

        //execute the ward councillor insert
        if (!$stmt->execute()) {

            throw new Exception(
                "Ward councillor registration failed: "
                . $stmt->error
            );

        }

        $stmt->close();

    }

    //insert municipal staff into the municipal_staff table
    elseif ($userRole === "municipal_staff") {

        //get municipal staff information
        $serviceId = $_POST["service_id"] ?? null;

        $division = trim(
            $_POST["division"] ?? ""
        );

        $availabilityStatus = trim(
            $_POST["availability_status"] ?? ""
        );

        $sql = "INSERT INTO municipal_staff
                (
                    username,
                    service_id,
                    division,
                    availability_status,
                    contact,
                    password_hashed
                )
                VALUES (?, ?, ?, ?, ?, ?)";

        $stmt = $conn->prepare($sql);

        if ($stmt === false) {

            throw new Exception(
                "Municipal Staff SQL Error: "
                . $conn->error
            );

        }

        $stmt->bind_param(
            "sissss",
            $username,
            $serviceId,
            $division,
            $availabilityStatus,
            $contact,
            $passwordHash
        );

        //execute the municipal staff insert
        if (!$stmt->execute()) {

            throw new Exception(
                "Municipal staff registration failed: "
                . $stmt->error
            );

        }

        $stmt->close();

    }

    //insert administrator into the admin table
    elseif ($userRole === "admin") {

        //set the registered date
        $registeredDate = date("Y-m-d");

        //last login is empty until the administrator logs in
        $lastLoginDate = null;

        $sql = "INSERT INTO admin
                (
                    username,
                    last_login_date,
                    registered_date,
                    password_hashed,
                    contact
                )
                VALUES (?, ?, ?, ?, ?)";

        $stmt = $conn->prepare($sql);

        if ($stmt === false) {

            throw new Exception(
                "Admin SQL Error: "
                . $conn->error
            );

        }

        $stmt->bind_param(
            "sssss",
            $username,
            $lastLoginDate,
            $registeredDate,
            $passwordHash,
            $contact
        );

        //execute the admin insert
        if (!$stmt->execute()) {

            throw new Exception(
                "Admin registration failed: "
                . $stmt->error
            );

        }

        $stmt->close();

    }

    //organisation accounts are currently inserted into the user table
    elseif ($userRole === "organisation") {

        //organisation role-specific table will be populated here
        //when the organisation table structure is used

    }

    //save all database changes
    $conn->commit();

    //registration was successful
    header("Location: login.php?registered=1");
    exit;

}
catch (Exception $e) {

    //undo all database changes if something failed
    $conn->rollback();

    die($e->getMessage());

}

//close the database connection
$conn->close();

?>