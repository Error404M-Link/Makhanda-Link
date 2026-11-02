<?php

session_start();

require_once "Error404Connection.php";
require_once "inputValidation.php";

if ($_SERVER["REQUEST_METHOD"] !== "POST") {

    header("Location: register.php");
    exit;

}

$userRole = strtolower(
    (string)postString("role", 30)
);

if ($userRole === "organisation") {

    header("Location: organisationRegistration.php");
    exit;

}

$allowedRoles = [
    "resident",
    "ward_councillor",
    "municipal_staff"
];

if (!in_array(
    $userRole,
    $allowedRoles,
    true
)) {

    die("Please select a valid role.");

}

$conn->begin_transaction();

try {

    // Read every field as a plain string. postString() rejects arrays,
    // over-long values, invalid UTF-8 and control characters.
    // Passwords are not trimmed. address_id and address are optional
    // for some roles, so a missing key becomes an empty string.
    $firstName =
        postString("first_name", 100);

    $surname =
        postString("surname", 100);

    $username =
        postString("username", 100);

    $contact =
        postString("contact", 50);

    $password =
        postString("password", 255, false);

    $confirmPassword =
        postString("confirm_password", 255, false);

    $addressId =
        isset($_POST["address_id"])
            ? postString("address_id", 10)
            : "";

    $address =
        isset($_POST["address"])
            ? postString("address", 1000, true, true)
            : "";


    if (
        $firstName === null ||
        $surname === null ||
        $username === null ||
        $contact === null ||
        $password === null ||
        $confirmPassword === null ||
        $addressId === null ||
        $address === null
    ) {

        throw new Exception(
            "One or more fields are too long or contain characters that are not allowed."
        );

    }


    if (
        $firstName === "" ||
        $surname === "" ||
        $username === "" ||
        $contact === "" ||
        $password === "" ||
        $confirmPassword === ""
    ) {

        throw new Exception(
            "Please complete all required fields."
        );

    }


    if (strlen($username) > 50) {

        throw new Exception(
            "The email address must be 50 characters or fewer."
        );

    }


    // username: must be a valid email address, start with a letter
    // (not a number) and end with .com
    if (preg_match('/^[0-9]/', $username) === 1) {

        throw new Exception(
            "The email address cannot start with a number."
        );

    }


    if (preg_match('/\.com\z/i', $username) !== 1) {

        throw new Exception(
            "The email address must end with .com"
        );

    }


    if (
        !filter_var(
            $username,
            FILTER_VALIDATE_EMAIL
        ) ||
        !isValidRegistrationUsername($username)
    ) {

        throw new Exception(
            "Please enter a valid email address that starts with a letter and ends with .com"
        );

    }


    // contact: digits only, exactly 10 (no letters or symbols)
    if (!isValidContactNumber($contact)) {

        throw new Exception(
            "Contact number must contain exactly 10 digits (numbers only)."
        );

    }


    // names: letters, spaces, hyphens and apostrophes only
    if (!isValidPersonName($firstName)) {

        throw new Exception(
            "First name may only contain letters, spaces, hyphens and apostrophes (maximum 100 characters)."
        );

    }


    if (!isValidPersonName($surname)) {

        throw new Exception(
            "Surname may only contain letters, spaces, hyphens and apostrophes (maximum 100 characters)."
        );

    }


    // address (optional): letters, numbers, spaces and , . - ' / #
    if (!isValidAddress($address)) {

        throw new Exception(
            "Address may only contain letters, numbers, spaces and the characters , . - ' / # (maximum 255 characters)."
        );

    }


    if ($password !== $confirmPassword) {

        throw new Exception(
            "Passwords do not match."
        );

    }


    // password: 8-72 characters, upper + lower case, a number, a special
    // character, at least 5 different characters, and not a common password
    $passwordProblem =
        passwordStrengthError(
            $password,
            $username,
            [$firstName, $surname]
        );

    if ($passwordProblem !== null) {

        throw new Exception($passwordProblem);

    }


    $wardId = null;


    if ($userRole === "resident") {

        if (
            $addressId === "" ||
            !ctype_digit($addressId)
        ) {

            throw new Exception(
                "Please select a street."
            );

        }


        $addressId = (int)$addressId;


        $sql = "SELECT
                    ward_id,
                    street_name
                FROM address_directory
                WHERE address_id = ?
                LIMIT 1";


        $stmt = $conn->prepare($sql);


        if ($stmt === false) {

            throw new Exception(
                "Street SQL Error: " .
                $conn->error
            );

        }


        $stmt->bind_param(
            "i",
            $addressId
        );


        if (!$stmt->execute()) {

            throw new Exception(
                "Could not find the selected street."
            );

        }


        $result =
            $stmt->get_result();


        $addressData =
            $result->fetch_assoc();


        $stmt->close();


        if (!$addressData) {

            throw new Exception(
                "Selected street was not found."
            );

        }


        $wardId =
            (int)$addressData["ward_id"];

    }


    elseif ($userRole === "ward_councillor") {

        $wardId = null;

    }


    elseif ($userRole === "municipal_staff") {

        $wardId = null;

    }


    $sql = "SELECT username
            FROM `user`
            WHERE username = ?
            LIMIT 1";


    $stmt =
        $conn->prepare($sql);


    if ($stmt === false) {

        throw new Exception(
            "Username SQL Error: " .
            $conn->error
        );

    }


    $stmt->bind_param(
        "s",
        $username
    );


    if (!$stmt->execute()) {

        throw new Exception(
            "Could not check username."
        );

    }


    $result =
        $stmt->get_result();


    if ($result->num_rows > 0) {

        $stmt->close();

        throw new Exception(
            "Username already exists."
        );

    }


    $stmt->close();


    $databaseRole =
        $userRole;


    if ($userRole === "resident") {

        $verified = "1";
        $active = 1;

    }

    else {

        $verified = "0";
        $active = 0;

    }


    $passwordHash =
        password_hash(
            $password,
            PASSWORD_DEFAULT
        );


    if ($passwordHash === false) {

        throw new Exception(
            "Password could not be secured."
        );

    }


    $sql = "INSERT INTO `user`
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
            VALUES
            (
                ?,
                ?,
                ?,
                ?,
                ?,
                ?,
                ?,
                ?,
                ?,
                ?
            )";


    $stmt =
        $conn->prepare($sql);


    if ($stmt === false) {

        throw new Exception(
            "User SQL Error: " .
            $conn->error
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


    if (!$stmt->execute()) {

        throw new Exception(
            "User registration failed: " .
            $stmt->error
        );

    }


    $stmt->close();


    if ($userRole === "resident") {


        $sql = "INSERT INTO community_member
                (
                    username,
                    ward_id,
                    address_id
                )
                VALUES
                (
                    ?,
                    ?,
                    ?
                )";


        $stmt =
            $conn->prepare($sql);


        if ($stmt === false) {

            throw new Exception(
                "Community Member SQL Error: " .
                $conn->error
            );

        }


        $stmt->bind_param(
            "sii",
            $username,
            $wardId,
            $addressId
        );


        if (!$stmt->execute()) {

            throw new Exception(
                "Community member registration failed: " .
                $stmt->error
            );

        }


        $stmt->close();

    }


    elseif ($userRole === "ward_councillor") {


        $dateAppointed =
            date("Y-m-d");


        $sql = "INSERT INTO ward_councillor
                (
                    username,
                    ward_id,
                    date_appointed,
                    password_hashed,
                    contact
                )
                VALUES
                (
                    ?,
                    ?,
                    ?,
                    ?,
                    ?
                )";


        $stmt =
            $conn->prepare($sql);


        if ($stmt === false) {

            throw new Exception(
                "Ward Councillor SQL Error: " .
                $conn->error
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


        if (!$stmt->execute()) {

            throw new Exception(
                "Ward councillor registration failed: " .
                $stmt->error
            );

        }


        $stmt->close();

    }


    elseif ($userRole === "municipal_staff") {


        $serviceId = null;

        $division = "";

        $availabilityStatus = "";


        $sql = "INSERT INTO municipal_staff
                (
                    username,
                    service_id,
                    division,
                    availability_status,
                    contact,
                    password_hashed
                )
                VALUES
                (
                    ?,
                    ?,
                    ?,
                    ?,
                    ?,
                    ?
                )";


        $stmt =
            $conn->prepare($sql);


        if ($stmt === false) {

            throw new Exception(
                "Municipal Staff SQL Error: " .
                $conn->error
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


        if (!$stmt->execute()) {

            throw new Exception(
                "Municipal staff registration failed: " .
                $stmt->error
            );

        }


        $stmt->close();

    }


    $conn->commit();


    header(
        "Location: login.php?success=registered"
    );

    exit;

}


catch (Exception $e) {


    $conn->rollback();


    die(
        htmlspecialchars(
            $e->getMessage()
        )
    );

}


$conn->close();

?>