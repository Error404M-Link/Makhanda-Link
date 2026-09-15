<?php

session_start();

require_once "Error404Connection.php";

//check if the form was submitted
if ($_SERVER["REQUEST_METHOD"] !== "POST") {


    exit;

}

//login details from the login form
$username = trim($_POST["username"] ?? "");
$password = $_POST["password"] ?? "";

//check if username and password were entered
if ($username === "" || $password === "") {

    header("Location: login.php?error=missing");
    exit;

}


/*
   FUNCTION TO FIND ADMIN ACCOUNT
*/

function findAdmin($conn, $username)
{
    $sql = "SELECT username, password_hashed, contact
            FROM `admin`
            WHERE username = ?
            LIMIT 1";

    $stmt = $conn->prepare($sql);

    if (!$stmt) {

        die("Login SQL Error: " . $conn->error);

    }

    $stmt->bind_param("s", $username);

    $stmt->execute();

    $result = $stmt->get_result();

    $account = $result->fetch_assoc();

    $stmt->close();

    return $account;
}


/*
   FUNCTION TO FIND USER ACCOUNT
*/

function findUser($conn, $username)
{
    $sql = "SELECT username, password_hash, active, verified,
                   User_role, ward_id, address, first_Name,
                   surname, contact
            FROM `user`
            WHERE username = ?
            LIMIT 1";

    $stmt = $conn->prepare($sql);

    if (!$stmt) {

        die("Login SQL Error: " . $conn->error);

    }

    $stmt->bind_param("s", $username);

    $stmt->execute();

    $result = $stmt->get_result();

    $account = $result->fetch_assoc();

    $stmt->close();

    return $account;
}


/*
   FUNCTION TO FIND ORGANISATION ACCOUNT
*/

function findOrganisation($conn, $username)
{
    $sql = "SELECT organisation_id, username, organisation_name,
                   address, contact, password_hashed
            FROM `organisation`
            WHERE username = ?
            LIMIT 1";

    $stmt = $conn->prepare($sql);

    if (!$stmt) {

        die("Login SQL Error: " . $conn->error);

    }

    $stmt->bind_param("s", $username);

    $stmt->execute();

    $result = $stmt->get_result();

    $account = $result->fetch_assoc();

    $stmt->close();

    return $account;
}


/*
   SEARCH FOR ADMIN ACCOUNT
*/

$account = findAdmin($conn, $username);

if ($account) {

    /*
       CHECK ADMIN PASSWORD
    */

    $passwordCorrect = false;


    /*
       CHECK IF PASSWORD IS ALREADY HASHED
    */

    if (
        password_verify(
            $password,
            $account["password_hashed"]
        )
    ) {

        $passwordCorrect = true;

    }


    /*
       SUPPORT EXISTING PLAIN-TEXT ADMIN PASSWORD
       AND CONVERT IT TO A HASH
    */

    else if (
        $password === $account["password_hashed"]
    ) {

        $newPasswordHash = password_hash(
            $password,
            PASSWORD_DEFAULT
        );

        if ($newPasswordHash === false) {

            header("Location: login.php?error=system");
            exit;

        }


        //update the admin password with the new hash
        $updatePasswordStmt = $conn->prepare(
            "UPDATE `admin`
             SET password_hashed = ?
             WHERE username = ?"
        );

        if (!$updatePasswordStmt) {

            header("Location: login.php?error=system");
            exit;

        }

        $updatePasswordStmt->bind_param(
            "ss",
            $newPasswordHash,
            $account["username"]
        );

        if (!$updatePasswordStmt->execute()) {

            $updatePasswordStmt->close();

            header("Location: login.php?error=system");
            exit;

        }

        $updatePasswordStmt->close();

        $passwordCorrect = true;

    }


    /*
       CHECK IF ADMIN PASSWORD IS CORRECT
    */

    if (!$passwordCorrect) {

        header("Location: login.php?error=invalid");
        exit;

    }


    //create admin session
    session_regenerate_id(true);

    $_SESSION["username"] = $account["username"];
    $_SESSION["user_role"] = "admin";
    $_SESSION["role"] = "admin";


    //update admin last login
    $loginStmt = $conn->prepare(
        "UPDATE `admin`
         SET last_login_date = NOW()
         WHERE username = ?"
    );

    if ($loginStmt) {

        $loginStmt->bind_param("s", $username);

        $loginStmt->execute();

        $loginStmt->close();

    }


    //send admin to the dashboard
    header(
        "Location: ../AdminOverview/admindashboard.php"
    );

    exit;
}


/*
   SEARCH FOR USER ACCOUNT
*/

$account = findUser($conn, $username);

if ($account) {

    $userRole = strtolower(trim($account["User_role"]));


    /*
       RESIDENT LOGIN
    */

    if ($userRole === "resident") {

        //check resident password
        if (!password_verify(
            $password,
            $account["password_hash"]
        )) {

            header("Location: login.php?error=invalid");
            exit;

        }

        //residents are automatically active
        //and verified after registration
        if ((int)$account["active"] !== 1) {

            header("Location: login.php?error=inactive");
            exit;

        }

        //create resident session
        session_regenerate_id(true);

        $_SESSION["username"] = $account["username"];
        $_SESSION["user_role"] = "resident";
        $_SESSION["role"] = "resident";
        $_SESSION["first_name"] = $account["first_Name"];
        $_SESSION["surname"] = $account["surname"];
        $_SESSION["contact"] = $account["contact"];
        $_SESSION["ward_id"] = $account["ward_id"];
        $_SESSION["address"] = $account["address"];


        //send resident to the dashboard
        header(
            "Location: ../USER_DASHBOARD/residence.php"
        );

        exit;
    }


    /*
       WARD COUNCILLOR LOGIN
    */

    if ($userRole === "ward_councillor") {

        //check ward councillor password
        if (!password_verify(
            $password,
            $account["password_hash"]
        )) {

            header("Location: login.php?error=invalid");
            exit;

        }

        //check if ward councillor has been verified
        if (
            $account["verified"] !== "1" ||
            (int)$account["active"] !== 1
        ) {

            header("Location: login.php?error=not_verified");
            exit;

        }

        //create ward councillor session
        session_regenerate_id(true);

        $_SESSION["username"] = $account["username"];
        $_SESSION["user_role"] = "ward_councillor";
        $_SESSION["role"] = "ward_councillor";
        $_SESSION["ward_id"] = $account["ward_id"];
        $_SESSION["contact"] = $account["contact"];


        //get ward councillor information
        $councillorStmt = $conn->prepare(
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


        //send ward councillor to the dashboard
        header(
            "Location: ../ward_councillor_dashboard/wardcouncilorDashboard.php"
        );

        exit;
    }


    /*
       MUNICIPAL STAFF LOGIN
    */

    if ($userRole === "municipal_staff") {

        //check municipal staff password
        if (!password_verify(
            $password,
            $account["password_hash"]
        )) {

            header("Location: login.php?error=invalid");
            exit;

        }

        //check if municipal staff has been verified
        if (
            $account["verified"] !== "1" ||
            (int)$account["active"] !== 1
        ) {

            header("Location: login.php?error=not_verified");
            exit;

        }

        //create municipal staff session
        session_regenerate_id(true);

        $_SESSION["username"] = $account["username"];
        $_SESSION["user_role"] = "municipal_staff";
        $_SESSION["role"] = "municipal_staff";
        $_SESSION["contact"] = $account["contact"];


        //get municipal staff information
        $staffStmt = $conn->prepare(
            "SELECT service_id, division, availability_status
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


        //send municipal staff to the dashboard
        header(
            "Location: ../communication_officer/communication_officer_dashboard.php"
        );

        exit;
    }


    /*
       INVALID USER ROLE
    */

    header("Location: login.php?error=role");
    exit;

}


/*
   SEARCH FOR ORGANISATION ACCOUNT
*/

$account = findOrganisation($conn, $username);

if ($account) {

    //check organisation password
    if (!password_verify(
        $password,
        $account["password_hashed"]
    )) {

        header("Location: login.php?error=invalid");
        exit;

    }

    //create organisation session
    session_regenerate_id(true);

    $_SESSION["username"] = $account["username"];
    $_SESSION["user_role"] = "organisation";
    $_SESSION["role"] = "organisation";
    $_SESSION["contact"] =
        $account["contact"];
    $_SESSION["address"] =
        $account["address"];


    //send organisation to the dashboard
    header(
        "Location: ../ORGANISATION_DASHBOARD/organisationDashboard.php"
    );

    exit;
}


//if no account was found
header("Location: login.php?error=invalid");
exit;

?>