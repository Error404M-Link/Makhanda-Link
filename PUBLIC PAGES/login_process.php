<?php

session_start();

require_once "Error404Connection.php";
require_once "inputValidation.php";
require_once "loginRateLimit.php";

if ($_SERVER["REQUEST_METHOD"] !== "POST") {

    header("Location: login.php");
    exit;

}


// Read the fields as plain strings only (rejects arrays such as username[]=x,
// over-long values, invalid UTF-8 and control characters). The password is
// NOT trimmed so spaces inside/around it are kept exactly as typed.
$username = postString("username", 100);
$password = postString("password", 255, false);

if ($username === null || $password === null) {

    header("Location: login.php?error=invalid");
    exit;

}

if ($username === "" || $password === "") {

    header("Location: login.php?error=missing");
    exit;

}


// Usernames must follow the account rules (start with a letter, end with
// .com, at most 50 characters). Anything else cannot be a real account.
if (!isValidRegistrationUsername($username)) {

    header("Location: login.php?error=invalid");
    exit;

}


// Too many recent failed attempts for this username or IP address?
if (isLoginLocked($conn, $username)) {

    header("Location: login.php?error=locked");
    exit;

}


function findAdmin($conn, $username)
{
    $sql = "SELECT
                username,
                password_hashed,
                contact
            FROM `admin`
            WHERE username = ?
            LIMIT 1";

    $stmt = $conn->prepare($sql);

    if (!$stmt) {

        header("Location: login.php?error=system");
        exit;

    }

    $stmt->bind_param(
        "s",
        $username
    );

    $stmt->execute();

    $result = $stmt->get_result();

    $account = $result->fetch_assoc();

    $stmt->close();

    return $account;
}


function findUser($conn, $username)
{
    $sql = "SELECT
                username,
                password_hash,
                active,
                verified,
                User_role,
                ward_id,
                address,
                first_Name,
                surname,
                contact
            FROM `user`
            WHERE username = ?
            LIMIT 1";

    $stmt = $conn->prepare($sql);

    if (!$stmt) {

        header("Location: login.php?error=system");
        exit;

    }

    $stmt->bind_param(
        "s",
        $username
    );

    $stmt->execute();

    $result = $stmt->get_result();

    $account = $result->fetch_assoc();

    $stmt->close();

    return $account;
}


function normaliseRole($role)
{
    $role = strtolower(
        trim($role)
    );

    $role = str_replace(
        [" ", "-"],
        "_",
        $role
    );

    while (
        strpos(
            $role,
            "__"
        ) !== false
    ) {

        $role = str_replace(
            "__",
            "_",
            $role
        );

    }

    return $role;
}


$adminAccount =
    findAdmin(
        $conn,
        $username
    );


if ($adminAccount) {

    // Admin passwords must be stored as hashes. Plain-text passwords are no
    // longer accepted: run hashAdminPasswords.php once to convert old ones.
    if (
        !is_string($adminAccount["password_hashed"]) ||
        !password_verify(
            $password,
            $adminAccount["password_hashed"]
        )
    ) {

        failLogin($conn, $username);

    }


    clearFailedLogins($conn, $username);


    session_regenerate_id(true);


    $_SESSION["username"] =
        $adminAccount["username"];

    $_SESSION["user_role"] =
        "admin";

    $_SESSION["role"] =
        "admin";

    $_SESSION["contact"] =
        $adminAccount["contact"];


    $loginStmt =
        $conn->prepare(
            "UPDATE `admin`
             SET last_login_date = NOW()
             WHERE username = ?"
        );


    if ($loginStmt) {

        $loginStmt->bind_param(
            "s",
            $adminAccount["username"]
        );

        $loginStmt->execute();

        $loginStmt->close();

    }


    header(
        "Location: ../AdminOverview/admindashboard.php"
    );

    exit;

}


$account =
    findUser(
        $conn,
        $username
    );


if (!$account) {

    failLogin($conn, $username);

}


$userRole =
    normaliseRole(
        $account["User_role"]
    );


if (
    !is_string($account["password_hash"]) ||
    !password_verify(
        $password,
        $account["password_hash"]
    )
) {

    failLogin($conn, $username);

}


clearFailedLogins($conn, $username);


if ($userRole === "resident") {


    if (
        (int)$account["active"] !== 1
    ) {

        header(
            "Location: login.php?error=inactive"
        );

        exit;

    }


    session_regenerate_id(true);


    $_SESSION["username"] =
        $account["username"];

    $_SESSION["user_role"] =
        "resident";

    $_SESSION["role"] =
        "resident";

    $_SESSION["first_name"] =
        $account["first_Name"];

    $_SESSION["surname"] =
        $account["surname"];

    $_SESSION["contact"] =
        $account["contact"];

    $_SESSION["ward_id"] =
        $account["ward_id"];

    $_SESSION["address"] =
        $account["address"];


    header(
        "Location: ../USER_DASHBOARD/residence.php"
    );

    exit;

}


if ($userRole === "organisation") {


    if (
        (int)$account["active"] !== 1
    ) {

        header(
            "Location: login.php?error=inactive"
        );

        exit;

    }


    $organisationStmt =
        $conn->prepare(
            "SELECT
                organisation_name,
                address,
                contact
             FROM `organisation`
             WHERE username = ?
             LIMIT 1"
        );


    if (!$organisationStmt) {

        header(
            "Location: login.php?error=system"
        );

        exit;

    }


    $organisationStmt->bind_param(
        "s",
        $account["username"]
    );


    $organisationStmt->execute();


    $organisationResult =
        $organisationStmt->get_result();


    $organisation =
        $organisationResult->fetch_assoc();


    $organisationStmt->close();


    if (!$organisation) {

        header(
            "Location: login.php?error=system"
        );

        exit;

    }


    session_regenerate_id(true);


    $_SESSION["username"] =
        $account["username"];

    $_SESSION["user_role"] =
        "organisation";

    $_SESSION["role"] =
        "organisation";

    $_SESSION["organisation_name"] =
        $organisation["organisation_name"];

    $_SESSION["first_name"] =
        $account["first_Name"];

    $_SESSION["surname"] =
        $account["surname"];

    $_SESSION["contact"] =
        $organisation["contact"];

    $_SESSION["ward_id"] =
        $account["ward_id"];

    $_SESSION["address"] =
        $organisation["address"];


    header(
        "Location: ../ORGANISATION_DASHBOARD/organisationdb.php"
    );

    exit;

}


if ($userRole === "ward_councillor") {


    if (
        (string)$account["verified"] !== "1" ||
        (int)$account["active"] !== 1
    ) {

        header(
            "Location: login.php?error=not_verified"
        );

        exit;

    }


    session_regenerate_id(true);


    $_SESSION["username"] =
        $account["username"];

    $_SESSION["user_role"] =
        "ward_councillor";

    $_SESSION["role"] =
        "ward_councillor";

    $_SESSION["first_name"] =
        $account["first_Name"];

    $_SESSION["surname"] =
        $account["surname"];

    $_SESSION["ward_id"] =
        $account["ward_id"];

    $_SESSION["contact"] =
        $account["contact"];

    $_SESSION["address"] =
        $account["address"];


    $councillorStmt =
        $conn->prepare(
            "SELECT date_appointed
             FROM `ward_councillor`
             WHERE username = ?
             LIMIT 1"
        );


    if ($councillorStmt) {

        $councillorStmt->bind_param(
            "s",
            $account["username"]
        );


        $councillorStmt->execute();


        $councillorResult =
            $councillorStmt->get_result();


        $councillorData =
            $councillorResult->fetch_assoc();


        if ($councillorData) {

            $_SESSION["date_appointed"] =
                $councillorData["date_appointed"];

        }


        $councillorStmt->close();

    }


    header(
        "Location: ../ward_councillor_dashboard/wardcouncilorDashboard.php"
    );

    exit;

}


if ($userRole === "municipal_staff") {


    if (
        (string)$account["verified"] !== "1" ||
        (int)$account["active"] !== 1
    ) {

        header(
            "Location: login.php?error=not_verified"
        );

        exit;

    }


    session_regenerate_id(true);


    $_SESSION["username"] =
        $account["username"];

    $_SESSION["user_role"] =
        "municipal_staff";

    $_SESSION["role"] =
        "municipal_staff";

    $_SESSION["first_name"] =
        $account["first_Name"];

    $_SESSION["surname"] =
        $account["surname"];

    $_SESSION["contact"] =
        $account["contact"];

    $_SESSION["ward_id"] =
        $account["ward_id"];

    $_SESSION["address"] =
        $account["address"];


    $staffStmt =
        $conn->prepare(
            "SELECT
                service_id,
                division,
                availability_status
             FROM `municipal_staff`
             WHERE username = ?
             LIMIT 1"
        );


    if ($staffStmt) {

        $staffStmt->bind_param(
            "s",
            $account["username"]
        );


        $staffStmt->execute();


        $staffResult =
            $staffStmt->get_result();


        $staffData =
            $staffResult->fetch_assoc();


        if ($staffData) {


            $_SESSION["service_id"] =
                $staffData["service_id"];


            $_SESSION["division"] =
                $staffData["division"];


            $_SESSION["availability_status"] =
                $staffData["availability_status"];

        }


        $staffStmt->close();

    }


    header(
        "Location: ../communication_officer/communication_officer_dashboard.php"
    );

    exit;

}


header(
    "Location: login.php?error=role"
);

exit;

?>