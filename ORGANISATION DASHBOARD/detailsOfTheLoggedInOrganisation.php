<?php

if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

require_once "../Error404Connection.php";

if (!isset($_SESSION['username']) || empty($_SESSION['username'])) {

    header(
        "Location: ../login.php?status=error&msg=" .
        urlencode("Unauthorized access")
    );

    exit;
}

$username = $_SESSION['username'];

$sql = "
    SELECT
        o.organisation_id,
        o.username,
        o.organisation_name,
        o.address AS organisation_address,
        o.contact AS organisation_contact,

        u.first_Name,
        u.surname,
        u.User_role

    FROM organisation o

    INNER JOIN user u
        ON o.username = u.username

    WHERE o.username = ?

    LIMIT 1
";

$stmt = $conn->prepare($sql);

if (!$stmt) {
    die("Database Error: " . $conn->error);
}

$stmt->bind_param("s", $username);

$stmt->execute();

$result = $stmt->get_result();

if ($result && $row = $result->fetch_assoc()) {

    $organisationData = $row;

} else {

    die("Organisation record not found.");
}

$stmt->close();


// ============================================================
// ORGANISATION VARIABLES
// ============================================================

$organisationId =
    $organisationData['organisation_id'];

$organisationName =
    $organisationData['organisation_name'];

$organisationUsername =
    $organisationData['username'];

$organisationAddress =
    $organisationData['organisation_address'];

$organisationContact =
    $organisationData['organisation_contact'];

$firstName =
    $organisationData['first_Name'];

$surname =
    $organisationData['surname'];

$userRole =
    $organisationData['User_role'];

?>