<?php

require_once "Error404Connection.php";
require_once "inputValidation.php";

if ($_SERVER["REQUEST_METHOD"] !== "POST") {

    header("Location: organisationRegistration.php");
    exit;

}

function organisationRedirect($errorCode)
{
    header(
        "Location: organisationRegistration.php?error=" .
        $errorCode
    );

    exit;
}


// Read every field as a plain string. postString() rejects arrays,
// over-long values, invalid UTF-8 and control characters.
// Passwords are not trimmed. The posted address text is ignored: the
// address is built from the street the visitor chose.
$organisationName =
    postString("organisation_name", 150);

$username =
    postString("username", 100);

$contact =
    postString("contact", 50);

$password =
    postString("password", 255, false);

$confirmPassword =
    postString("confirm_password", 255, false);

$addressIdText =
    postString("address_id", 10);

$submittedWardText =
    isset($_POST["ward_id"])
        ? postString("ward_id", 10)
        : "";


if (
    $organisationName === null ||
    $username === null ||
    $contact === null ||
    $password === null ||
    $confirmPassword === null ||
    $addressIdText === null ||
    $submittedWardText === null
) {

    organisationRedirect("invalid");

}


if (
    $organisationName === "" ||
    $username === "" ||
    $contact === "" ||
    $password === "" ||
    $confirmPassword === "" ||
    $addressIdText === ""
) {

    organisationRedirect("missing");

}


// organisation name: letters, digits, spaces and & ' . - (2-100 characters)
if (!isValidOrganisationName($organisationName)) {

    organisationRedirect("orgname");

}


// username: valid email address, starts with a letter (not a number),
// ends with .com, at most 50 characters
if (strlen($username) > 50) {

    organisationRedirect("email_long");

}


if (preg_match('/^[0-9]/', $username) === 1) {

    organisationRedirect("email_number");

}


if (preg_match('/\.com\z/i', $username) !== 1) {

    organisationRedirect("email_com");

}


if (
    !filter_var(
        $username,
        FILTER_VALIDATE_EMAIL
    ) ||
    !isValidRegistrationUsername($username)
) {

    organisationRedirect("email");

}


// contact: digits only, exactly 10
if (!isValidContactNumber($contact)) {

    organisationRedirect("contact");

}


if ($password !== $confirmPassword) {

    organisationRedirect("password_match");

}


// password: 8-72 characters, upper + lower case, a number, a special
// character, at least 5 different characters, not a common password
if (
    passwordStrengthError(
        $password,
        $username,
        [$organisationName]
    ) !== null
) {

    organisationRedirect("password_weak");

}


// street and ward must be whole numbers
if (
    !ctype_digit($addressIdText) ||
    (int)$addressIdText <= 0
) {

    organisationRedirect("street");

}

$addressId =
    (int)$addressIdText;


if (
    $submittedWardText !== "" &&
    !ctype_digit($submittedWardText)
) {

    organisationRedirect("ward");

}

$submittedWardId =
    (int)$submittedWardText;


$addressStmt = $conn->prepare(
    "SELECT
        address_id,
        ward_id,
        street_name,
        postal_code
     FROM address_directory
     WHERE address_id = ?
     LIMIT 1"
);

if (!$addressStmt) {

    die(
        "Address query failed: " .
        htmlspecialchars($conn->error)
    );

}

$addressStmt->bind_param(
    "i",
    $addressId
);

if (!$addressStmt->execute()) {

    die(
        "Address query failed: " .
        htmlspecialchars($addressStmt->error)
    );

}

$addressResult =
    $addressStmt->get_result();

$addressData =
    $addressResult->fetch_assoc();

$addressStmt->close();

if (!$addressData) {

    header(
        "Location: organisationRegistration.php?error=street"
    );

    exit;

}

$wardId =
    (int)$addressData["ward_id"];

$streetName =
    trim($addressData["street_name"]);

$postalCode =
    trim($addressData["postal_code"]);

if (
    $submittedWardId > 0 &&
    $submittedWardId !== $wardId
) {

    header(
        "Location: organisationRegistration.php?error=ward"
    );

    exit;

}

$address =
    "Ward " .
    $wardId .
    ", " .
    $streetName .
    ", " .
    $postalCode;

$checkUserStmt = $conn->prepare(
    "SELECT username
     FROM `user`
     WHERE username = ?
     LIMIT 1"
);

if (!$checkUserStmt) {

    die(
        "User check failed: " .
        htmlspecialchars($conn->error)
    );

}

$checkUserStmt->bind_param(
    "s",
    $username
);

$checkUserStmt->execute();

$checkUserResult =
    $checkUserStmt->get_result();

if ($checkUserResult->num_rows > 0) {

    $checkUserStmt->close();

    header(
        "Location: organisationRegistration.php?error=exists"
    );

    exit;

}

$checkUserStmt->close();

$checkOrganisationStmt = $conn->prepare(
    "SELECT username
     FROM `organisation`
     WHERE username = ?
     LIMIT 1"
);

if (!$checkOrganisationStmt) {

    die(
        "Organisation check failed: " .
        htmlspecialchars($conn->error)
    );

}

$checkOrganisationStmt->bind_param(
    "s",
    $username
);

$checkOrganisationStmt->execute();

$checkOrganisationResult =
    $checkOrganisationStmt->get_result();

if ($checkOrganisationResult->num_rows > 0) {

    $checkOrganisationStmt->close();

    header(
        "Location: organisationRegistration.php?error=exists"
    );

    exit;

}

$checkOrganisationStmt->close();

$passwordHash =
    password_hash(
        $password,
        PASSWORD_DEFAULT
    );

if ($passwordHash === false) {

    die("Password could not be hashed.");

}

$userRole =
    "organisation";

$active =
    1;

$verified =
    "1";

$firstName =
    $organisationName;

$surname =
    null;

$conn->begin_transaction();

try {

    $userStmt = $conn->prepare(
        "INSERT INTO `user`
        (
            username,
            first_Name,
            surname,
            contact,
            password_hash,
            active,
            User_role,
            address,
            ward_id,
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
        )"
    );

    if (!$userStmt) {

        throw new Exception(
            "USER PREPARE ERROR: " .
            $conn->error
        );

    }

    $userStmt->bind_param(
        "sssssissis",
        $username,
        $firstName,
        $surname,
        $contact,
        $passwordHash,
        $active,
        $userRole,
        $address,
        $wardId,
        $verified
    );

    if (!$userStmt->execute()) {

        throw new Exception(
            "USER INSERT ERROR: " .
            $userStmt->error
        );

    }

    $userStmt->close();

    $organisationStmt = $conn->prepare(
        "INSERT INTO `organisation`
        (
            username,
            organisation_name,
            address,
            contact,
            password_hashed
        )
        VALUES
        (
            ?,
            ?,
            ?,
            ?,
            ?
        )"
    );

    if (!$organisationStmt) {

        throw new Exception(
            "ORGANISATION PREPARE ERROR: " .
            $conn->error
        );

    }

    $organisationStmt->bind_param(
        "sssss",
        $username,
        $organisationName,
        $address,
        $contact,
        $passwordHash
    );

    if (!$organisationStmt->execute()) {

        throw new Exception(
            "ORGANISATION INSERT ERROR: " .
            $organisationStmt->error
        );

    }

    $organisationStmt->close();

    $conn->commit();

    header(
        "Location: organisationRegistration.php?success=registered"
    );

    exit;

} catch (Throwable $error) {

    $conn->rollback();

    die(
        "<h3>Organisation registration failed</h3>" .
        "<p>" .
        htmlspecialchars(
            $error->getMessage()
        ) .
        "</p>"
    );

}
?>