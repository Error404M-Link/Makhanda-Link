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

    die("Please enter your username and password.");

}


//function to find the user account
function findAccount($conn, $table, $username)
{
    $sql = "";

    //select the correct table
    switch ($table) {

        case "admin":

            $sql = "SELECT username, password_hashed, contact
                    FROM `admin`
                    WHERE username = ?
                    LIMIT 1";

            break;


        case "user":

            $sql = "SELECT username, password_hash, active, ward_id,
                           address, first_Name, surname, contact
                    FROM `user`
                    WHERE username = ?
                    LIMIT 1";

            break;


        case "ward_councillor":

            $sql = "SELECT username, password_hashed, ward_id,
                           date_appointed, contact
                    FROM `ward_councillor`
                    WHERE username = ?
                    LIMIT 1";

            break;


        case "municipal_staff":

            $sql = "SELECT username, password_hashed, service_id,
                           division, availability_status, contact
                    FROM `municipal_staff`
                    WHERE username = ?
                    LIMIT 1";

            break;


        case "organisation":

            $sql = "SELECT organisation_id, username, organisation_name,
                           address, contact, password_hashed
                    FROM `organisation`
                    WHERE username = ?
                    LIMIT 1";

            break;


        default:

            return null;
    }


    //prepare the SQL statement
    $stmt = $conn->prepare($sql);

    if (!$stmt) {

        die("Login SQL Error: " . $conn->error);

    }

    //username is used to search for the account
    $stmt->bind_param("s", $username);

    $stmt->execute();

    $result = $stmt->get_result();

    //get the user record
    $account = $result->fetch_assoc();

    $stmt->close();

    return $account;
}


//search for admin account
$account = findAccount($conn, "admin", $username);

if ($account) {

    //check admin password
    if (!password_verify(
        $password,
        $account["password_hashed"]
    )) {

        die("Invalid username or password.");

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
        "Location: ../ADMIN_DASHBOARD/adminDashboard.php"
    );

    exit;
}


//search for resident account
$account = findAccount($conn, "user", $username);

if ($account) {

    //check resident password
    if (!password_verify(
        $password,
        $account["password_hash"]
    )) {

        die("Invalid username or password.");

    }

    //check if resident account is active
    if ((int)$account["active"] !== 1) {

        die(
            "Your account is currently inactive. " .
            "Please contact the administrator."
        );

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


//search for ward councillor account
$account = findAccount(
    $conn,
    "ward_councillor",
    $username
);

if ($account) {

    //check ward councillor password
    if (!password_verify(
        $password,
        $account["password_hashed"]
    )) {

        die("Invalid username or password.");

    }

    //create ward councillor session
    session_regenerate_id(true);

    $_SESSION["username"] = $account["username"];
    $_SESSION["user_role"] = "ward_councillor";
    $_SESSION["role"] = "ward_councillor";
    $_SESSION["ward_id"] = $account["ward_id"];
    $_SESSION["contact"] = $account["contact"];
    $_SESSION["date_appointed"] =
        $account["date_appointed"];


    //send ward councillor to the dashboard
    header(
        "Location: ../ward_councillor_dashboard/wardcouncilorDashboard.html"
    );

    exit;
}


//search for municipal staff account
$account = findAccount(
    $conn,
    "municipal_staff",
    $username
);

//search for municipal staff account
$account = findAccount(
    $conn,
    "municipal_staff",
    $username
);

if ($account) {

    //check municipal staff password
    if (!password_verify(
        $password,
        $account["password_hashed"]
    )) {

        die("Invalid username or password.");

    }

    //create communication officer session
    session_regenerate_id(true);

    $_SESSION["username"] = $account["username"];
    $_SESSION["user_role"] = "municipal_staff";
    $_SESSION["role"] = "municipal_staff";
    $_SESSION["service_id"] = $account["service_id"];
    $_SESSION["division"] = $account["division"];
    $_SESSION["availability_status"] =
        $account["availability_status"];
    $_SESSION["contact"] = $account["contact"];


    //send communication officer to the dashboard
    header(
        "Location: ../communication_officer/communication_officer_dashboard.php"
    );

    exit;
}

//search for organisation account
$account = findAccount(
    $conn,
    "organisation",
    $username
);

if ($account) {

    //check organisation password
    if (!password_verify(
        $password,
        $account["password_hashed"]
    )) {

        die("Invalid username or password.");

    }

    //create organisation session
    session_regenerate_id(true);

    $_SESSION["username"] = $account["username"];
    $_SESSION["user_role"] = "organisation";
    $_SESSION["role"] = "organisation";
    $_SESSION["organisation_id"] =
        $account["organisation_id"];
    $_SESSION["organisation_name"] =
        $account["organisation_name"];
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
die("Invalid username or password.");

?>