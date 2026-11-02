<?php

session_start();

require_once "Error404Connection.php";


/* =========================================================
   CHECK ADMIN LOGIN
   ========================================================= */

if (!isset($_SESSION["username"])) {

    die("Administrator is not logged in.");
}


$adminUsername = $_SESSION["username"];


$userRole = strtolower(
    trim(
        $_SESSION["user_role"]
        ?? $_SESSION["role"]
        ?? ""
    )
);


if ($userRole !== "admin") {

    die("Access denied.");
}


$message = "";
$messageType = "";


/* =========================================================
   FUNCTION TO CHECK ADMIN PASSWORD
   ========================================================= */

function verifyAdminPassword($conn, $adminUsername, $password)
{
    $stmt = $conn->prepare("
        SELECT password_hashed
        FROM admin
        WHERE username = ?
        LIMIT 1
    ");

    if (!$stmt) {

        return false;
    }


    $stmt->bind_param(
        "s",
        $adminUsername
    );

    $stmt->execute();

    $result = $stmt->get_result();


    if (!$result || $result->num_rows === 0) {

        $stmt->close();

        return false;
    }


    $admin = $result->fetch_assoc();

    $stmt->close();


    return password_verify(
        $password,
        $admin["password_hashed"]
    );
}


/* =========================================================
   PROCESS ADMIN ACTION
   ========================================================= */

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $action = trim($_POST["action"] ?? "");

    $adminPassword = $_POST["admin_password"] ?? "";


    /* =====================================================
       CHECK PASSWORD WAS ENTERED
       ===================================================== */

    if ($adminPassword === "") {

        $message = "Administrator password is required.";

        $messageType = "error";
    }


    /* =====================================================
       VERIFY ADMIN PASSWORD
       ===================================================== */

    elseif (
        !verifyAdminPassword(
            $conn,
            $adminUsername,
            $adminPassword
        )
    ) {

        $message = "Incorrect administrator password.";

        $messageType = "error";
    }


    /* =====================================================
       ASSIGN COUNCILLOR
       ===================================================== */

    elseif ($action === "assign") {

        $councillorUsername =
            trim($_POST["councillor_username"] ?? "");

        $wardId =
            (int)($_POST["ward_id"] ?? 0);


        if (
            $councillorUsername === ""
            || $wardId <= 0
        ) {

            $message =
                "Please select a councillor and ward.";

            $messageType = "error";
        }

        else {

            $conn->begin_transaction();


            try {


                /* =========================================
                   CHECK THAT THE WARD EXISTS
                   ========================================= */

                $wardCheck = $conn->prepare("
                    SELECT ward_id
                    FROM ward
                    WHERE ward_id = ?
                    LIMIT 1
                ");


                if (!$wardCheck) {

                    throw new Exception(
                        "Unable to check the ward."
                    );
                }


                $wardCheck->bind_param(
                    "i",
                    $wardId
                );

                $wardCheck->execute();

                $wardResult =
                    $wardCheck->get_result();


                if ($wardResult->num_rows === 0) {

                    $wardCheck->close();

                    throw new Exception(
                        "The selected ward does not exist."
                    );
                }


                $wardCheck->close();


                /* =========================================
                   CHECK WHETHER WARD ALREADY HAS
                   A COUNCILLOR
                   ========================================= */

                $occupiedCheck = $conn->prepare("
                    SELECT username
                    FROM ward_councillor
                    WHERE ward_id = ?
                    LIMIT 1
                    FOR UPDATE
                ");


                if (!$occupiedCheck) {

                    throw new Exception(
                        "Unable to check the ward assignment."
                    );
                }


                $occupiedCheck->bind_param(
                    "i",
                    $wardId
                );

                $occupiedCheck->execute();

                $occupiedResult =
                    $occupiedCheck->get_result();


                if ($occupiedResult->num_rows > 0) {

                    $occupiedCheck->close();

                    throw new Exception(
                        "This ward already has an assigned councillor."
                    );
                }


                $occupiedCheck->close();


                /* =========================================
                   CHECK APPLICANT
                   ========================================= */

                $applicantCheck = $conn->prepare("
                    SELECT
                        username,
                        User_role,
                        ward_id,
                        verified,
                        active,
                        contact
                    FROM `user`
                    WHERE username = ?
                    LIMIT 1
                    FOR UPDATE
                ");


                if (!$applicantCheck) {

                    throw new Exception(
                        "Unable to check the councillor account."
                    );
                }


                $applicantCheck->bind_param(
                    "s",
                    $councillorUsername
                );

                $applicantCheck->execute();

                $applicantResult =
                    $applicantCheck->get_result();


                if ($applicantResult->num_rows === 0) {

                    $applicantCheck->close();

                    throw new Exception(
                        "Councillor account was not found."
                    );
                }


                $applicant =
                    $applicantResult->fetch_assoc();

                $applicantCheck->close();


                /* =========================================
                   CHECK ROLE
                   ========================================= */

                $applicantRole =
                    strtolower(
                        trim(
                            $applicant["User_role"]
                        )
                    );


                $validCouncillorRoles = [
                    "ward councillor",
                    "ward_councillor"
                ];


                if (
                    !in_array(
                        $applicantRole,
                        $validCouncillorRoles,
                        true
                    )
                ) {

                    throw new Exception(
                        "The selected account is not a ward councillor."
                    );
                }


                /* =========================================
                   CHECK APPLICANT IS NOT ALREADY ASSIGNED
                   ========================================= */

                $alreadyAssigned =
                    $conn->prepare("
                        SELECT ward_id
                        FROM ward_councillor
                        WHERE username = ?
                        LIMIT 1
                    ");


                if (!$alreadyAssigned) {

                    throw new Exception(
                        "Unable to check the councillor assignment."
                    );
                }


                $alreadyAssigned->bind_param(
                    "s",
                    $councillorUsername
                );

                $alreadyAssigned->execute();

                $alreadyAssignedResult =
                    $alreadyAssigned->get_result();


                if (
                    $alreadyAssignedResult->num_rows > 0
                ) {

                    $alreadyAssigned->close();

                    throw new Exception(
                        "This councillor is already assigned to a ward."
                    );
                }


                $alreadyAssigned->close();


                /* =========================================
                   CHECK APPLICANT'S REQUESTED WARD
                   ========================================= */

                if (
                    !empty($applicant["ward_id"])
                    &&
                    (int)$applicant["ward_id"] !== $wardId
                ) {

                    throw new Exception(
                        "This councillor registered for a different ward."
                    );
                }


                /* =========================================
                   INSERT OFFICIAL ASSIGNMENT
                   ========================================= */

                $insert = $conn->prepare("
                    INSERT INTO ward_councillor
                    (
                        username,
                        ward_id,
                        password_hashed,
                        contact,
                        date_appointed
                    )
                    SELECT
                        username,
                        ?,
                        password_hash,
                        contact,
                        CURDATE()
                    FROM `user`
                    WHERE username = ?
                ");


                if (!$insert) {

                    throw new Exception(
                        "Unable to assign the councillor."
                    );
                }


                $insert->bind_param(
                    "is",
                    $wardId,
                    $councillorUsername
                );


                if (!$insert->execute()) {

                    throw new Exception(
                        "Councillor assignment failed."
                    );
                }


                if ($insert->affected_rows !== 1) {

                    throw new Exception(
                        "Councillor assignment could not be created."
                    );
                }


                $insert->close();


                /* =========================================
                   VERIFY AND ACTIVATE ACCOUNT
                   ========================================= */

                $activate = $conn->prepare("
                    UPDATE `user`
                    SET
                        ward_id = ?,
                        verified = 1,
                        active = 1
                    WHERE username = ?
                ");


                if (!$activate) {

                    throw new Exception(
                        "Unable to activate the councillor account."
                    );
                }


                $activate->bind_param(
                    "is",
                    $wardId,
                    $councillorUsername
                );


                if (!$activate->execute()) {

                    throw new Exception(
                        "Unable to activate the councillor account."
                    );
                }


                $activate->close();


                $conn->commit();


                $message =
                    "Ward councillor assigned successfully.";

                $messageType = "success";
            }

            catch (Throwable $e) {

                $conn->rollback();


                /*
                 * MySQL 1062 is the database UNIQUE constraint.
                 * This is a second layer of protection.
                 */

                if (
                    $e instanceof mysqli_sql_exception
                    && $e->getCode() === 1062
                ) {

                    $message =
                        "This ward already has an assigned councillor.";
                }

                else {

                    $message = $e->getMessage();
                }


                $messageType = "error";
            }
        }
    }


    /* =====================================================
       REMOVE / DEMOTE COUNCILLOR
       ===================================================== */

    elseif ($action === "remove") {

        $councillorUsername =
            trim($_POST["councillor_username"] ?? "");

        $wardId =
            (int)($_POST["ward_id"] ?? 0);


        if (
            $councillorUsername === ""
            || $wardId <= 0
        ) {

            $message =
                "Invalid councillor assignment.";

            $messageType = "error";
        }

        else {

            $conn->begin_transaction();


            try {


                /* =========================================
                   CONFIRM CURRENT ASSIGNMENT
                   ========================================= */

                $assignmentCheck =
                    $conn->prepare("
                        SELECT username
                        FROM ward_councillor
                        WHERE username = ?
                        AND ward_id = ?
                        LIMIT 1
                        FOR UPDATE
                    ");


                if (!$assignmentCheck) {

                    throw new Exception(
                        "Unable to check the councillor assignment."
                    );
                }


                $assignmentCheck->bind_param(
                    "si",
                    $councillorUsername,
                    $wardId
                );

                $assignmentCheck->execute();

                $assignmentResult =
                    $assignmentCheck->get_result();


                if ($assignmentResult->num_rows === 0) {

                    $assignmentCheck->close();

                    throw new Exception(
                        "This councillor is no longer assigned to the ward."
                    );
                }


                $assignmentCheck->close();


                /* =========================================
                   REMOVE OFFICIAL ASSIGNMENT
                   ========================================= */

                $remove = $conn->prepare("
                    DELETE FROM ward_councillor
                    WHERE username = ?
                    AND ward_id = ?
                ");


                if (!$remove) {

                    throw new Exception(
                        "Unable to remove the councillor."
                    );
                }


                $remove->bind_param(
                    "si",
                    $councillorUsername,
                    $wardId
                );


                if (!$remove->execute()) {

                    throw new Exception(
                        "Councillor could not be removed."
                    );
                }


                if ($remove->affected_rows !== 1) {

                    throw new Exception(
                        "Councillor assignment was not removed."
                    );
                }


                $remove->close();


                /* =========================================
                   DEMOTE / DEACTIVATE ACCOUNT
                   ========================================= */

                $deactivate =
                    $conn->prepare("
                        UPDATE `user`
                        SET
                            verified = 0,
                            active = 0
                        WHERE username = ?
                    ");


                if (!$deactivate) {

                    throw new Exception(
                        "Unable to update the councillor account."
                    );
                }


                $deactivate->bind_param(
                    "s",
                    $councillorUsername
                );


                if (!$deactivate->execute()) {

                    throw new Exception(
                        "Unable to deactivate the councillor account."
                    );
                }


                $deactivate->close();


                $conn->commit();


                $message =
                    "Ward councillor removed successfully. The ward is now vacant.";

                $messageType = "success";
            }

            catch (Throwable $e) {

                $conn->rollback();

                $message = $e->getMessage();

                $messageType = "error";
            }
        }
    }


    else {

        $message = "Invalid action.";

        $messageType = "error";
    }
}


/* =========================================================
   GET ALL WARDS AND CURRENT COUNCILLORS
   ========================================================= */

$wards = [];


$wardResult = $conn->query("
    SELECT
        w.ward_id,
        w.ward_number,
        w.ward_name,

        wc.username AS councillor_username,

        u.first_Name,
        u.surname,
        u.contact,
        u.verified,
        u.active

    FROM ward w

    LEFT JOIN ward_councillor wc
        ON wc.ward_id = w.ward_id

    LEFT JOIN `user` u
        ON u.username = wc.username

    ORDER BY w.ward_number ASC
");


if ($wardResult) {

    while (
        $row = $wardResult->fetch_assoc()
    ) {

        $wards[] = $row;
    }
}


/* =========================================================
   GET PENDING / UNASSIGNED COUNCILLOR APPLICANTS
   ========================================================= */

$pendingCouncillors = [];


$pendingResult = $conn->query("
    SELECT
        u.username,
        u.first_Name,
        u.surname,
        u.contact,
        u.ward_id,
        u.verified,
        u.active,

        w.ward_number,
        w.ward_name

    FROM `user` u

    LEFT JOIN ward w
        ON w.ward_id = u.ward_id

    LEFT JOIN ward_councillor wc
        ON wc.username = u.username

    WHERE
        LOWER(TRIM(u.User_role))
        IN ('ward councillor', 'ward_councillor')

        AND wc.username IS NULL

    ORDER BY
        w.ward_number ASC,
        u.first_Name ASC,
        u.surname ASC
");


if ($pendingResult) {

    while (
        $row = $pendingResult->fetch_assoc()
    ) {

        $pendingCouncillors[] = $row;
    }
}


/* =========================================================
   COUNTS
   ========================================================= */

$totalWards = count($wards);

$assignedCount = 0;


foreach ($wards as $ward) {

    if (!empty($ward["councillor_username"])) {

        $assignedCount++;
    }
}


$vacantCount =
    $totalWards - $assignedCount;

$pendingCount =
    count($pendingCouncillors);

?>

<!DOCTYPE html>

<html lang="en">

<head>

    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0"
    >

    <title>Ward Councillors | M-LINK</title>


    <style>

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }


        body {
            font-family: Arial, Helvetica, sans-serif;
            background: #f4f0e7;
            color: #263238;
        }


        .page {
            min-height: 100vh;
            padding: 30px;
        }


        .topBar {
            display: grid;
            grid-template-columns: 150px 1fr 150px;
            align-items: center;
            margin-bottom: 30px;
        }


        .backButton {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: fit-content;
            padding: 10px 18px;
            background: #4b561b;
            color: white;
            text-decoration: none;
            border-radius: 6px;
            font-size: 14px;
            font-weight: bold;
        }


        .backButton:hover {
            opacity: 0.9;
        }


        .pageTitle {
            text-align: center;
            color: #4b561b;
            font-size: 27px;
        }


        .summaryCards {
            max-width: 1200px;
            margin: 0 auto 25px auto;
            display: grid;
            grid-template-columns:
                repeat(4, minmax(0, 1fr));
            gap: 15px;
        }


        .summaryCard {
            background: white;
            border-radius: 8px;
            padding: 20px;
            text-align: center;
            border: 1px solid #ded9ce;
        }


        .summaryNumber {
            display: block;
            color: #4b561b;
            font-size: 28px;
            font-weight: bold;
            margin-bottom: 6px;
        }


        .summaryLabel {
            font-size: 13px;
            color: #606060;
        }


        .message {
            max-width: 1200px;
            margin: 0 auto 20px auto;
            padding: 13px 16px;
            border-radius: 6px;
            font-size: 14px;
        }


        .message.success {
            background: #e7eddb;
            color: #374211;
            border: 1px solid #aab77d;
        }


        .message.error {
            background: #f7e3e3;
            color: #8b2424;
            border: 1px solid #dfb0b0;
        }


        .section {
            max-width: 1200px;
            margin: 0 auto 30px auto;
            background: white;
            border-radius: 8px;
            border: 1px solid #ded9ce;
            overflow: hidden;
        }


        .sectionTitle {
            padding: 18px 20px;
            color: #4b561b;
            font-size: 19px;
            border-bottom: 1px solid #ded9ce;
        }


        .tableWrapper {
            overflow-x: auto;
        }


        table {
            width: 100%;
            border-collapse: collapse;
        }


        th {
            background: #4b561b;
            color: white;
            padding: 13px 12px;
            text-align: left;
            font-size: 13px;
        }


        td {
            padding: 14px 12px;
            border-bottom: 1px solid #ece8df;
            font-size: 13px;
            vertical-align: middle;
        }


        tbody tr:last-child td {
            border-bottom: none;
        }


        tbody tr:hover {
            background: #faf8f3;
        }


        .wardNumber {
            font-weight: bold;
            color: #4b561b;
        }


        .status {
            display: inline-block;
            padding: 5px 9px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: bold;
        }


        .assigned {
            background: #e7eddb;
            color: #4b561b;
        }


        .vacant {
            background: #f5e4cf;
            color: #80511f;
        }


        .pending {
            background: #eee9dc;
            color: #5c5547;
        }


        .actionButton {
            border: none;
            border-radius: 5px;
            padding: 8px 13px;
            cursor: pointer;
            font-size: 12px;
            font-weight: bold;
        }


        .assignButton {
            background: #4b561b;
            color: white;
        }


        .removeButton {
            background: #8a3535;
            color: white;
        }


        .actionButton:hover {
            opacity: 0.88;
        }


        .noApplicants {
            color: #777;
            font-style: italic;
        }


        /* ================================================
           MODAL
           ================================================ */

        .modalOverlay {
            display: none;
            position: fixed;
            inset: 0;
            background: rgba(0, 0, 0, 0.55);
            align-items: center;
            justify-content: center;
            z-index: 1000;
            padding: 20px;
        }


        .modalOverlay.show {
            display: flex;
        }


        .modalBox {
            width: 100%;
            max-width: 440px;
            background: white;
            border-radius: 9px;
            padding: 25px;
            box-shadow:
                0 15px 45px rgba(0, 0, 0, 0.25);
        }


        .modalBox h2 {
            color: #4b561b;
            margin-bottom: 10px;
            font-size: 21px;
        }


        .modalText {
            font-size: 14px;
            line-height: 1.5;
            margin-bottom: 20px;
            color: #555;
        }


        .modalField {
            margin-bottom: 18px;
        }


        .modalField label {
            display: block;
            font-size: 13px;
            font-weight: bold;
            margin-bottom: 7px;
        }


        .modalField input {
            width: 100%;
            padding: 11px;
            border: 1px solid #cfc9bd;
            border-radius: 5px;
            outline: none;
        }


        .modalField input:focus {
            border-color: #4b561b;
        }


        .modalActions {
            display: flex;
            justify-content: flex-end;
            gap: 10px;
        }


        .cancelButton {
            background: #e4e1da;
            color: #333;
        }


        .confirmButton {
            background: #4b561b;
            color: white;
        }


        @media (max-width: 850px) {

            .page {
                padding: 20px 12px;
            }


            .topBar {
                grid-template-columns: 1fr;
                gap: 15px;
            }


            .pageTitle {
                grid-row: 1;
            }


            .backButton {
                grid-row: 2;
            }


            .summaryCards {
                grid-template-columns:
                    repeat(2, minmax(0, 1fr));
            }

        }


        @media (max-width: 500px) {

            .summaryCards {
                grid-template-columns: 1fr;
            }


            .pageTitle {
                font-size: 23px;
            }

        }

    </style>

</head>


<body>


<div class="page">


    <div class="topBar">

        <a
            href="admin.php"
            class="backButton"
        >
            Back
        </a>


        <h1 class="pageTitle">
            Ward Councillors
        </h1>


        <div></div>

    </div>


    <?php if ($message !== ""): ?>

        <div
            class="message <?= htmlspecialchars($messageType) ?>"
        >
            <?= htmlspecialchars($message) ?>
        </div>

    <?php endif; ?>


    <!-- ==================================================
         SUMMARY
         ================================================== -->

    <div class="summaryCards">


        <div class="summaryCard">

            <span class="summaryNumber">
                <?= $totalWards ?>
            </span>

            <span class="summaryLabel">
                Total Wards
            </span>

        </div>


        <div class="summaryCard">

            <span class="summaryNumber">
                <?= $assignedCount ?>
            </span>

            <span class="summaryLabel">
                Assigned
            </span>

        </div>


        <div class="summaryCard">

            <span class="summaryNumber">
                <?= $vacantCount ?>
            </span>

            <span class="summaryLabel">
                Vacant
            </span>

        </div>


        <div class="summaryCard">

            <span class="summaryNumber">
                <?= $pendingCount ?>
            </span>

            <span class="summaryLabel">
                Pending Councillors
            </span>

        </div>


    </div>


    <!-- ==================================================
         ALL WARDS
         ================================================== -->

    <section class="section">


        <h2 class="sectionTitle">
            Ward Assignments
        </h2>


        <div class="tableWrapper">


            <table>


                <thead>

                    <tr>

                        <th>Ward</th>

                        <th>Ward Name</th>

                        <th>Councillor</th>

                        <th>Email</th>

                        <th>Contact</th>

                        <th>Status</th>

                        <th>Action</th>

                    </tr>

                </thead>


                <tbody>


                <?php foreach ($wards as $ward): ?>


                    <tr>


                        <td class="wardNumber">

                            Ward
                            <?= htmlspecialchars(
                                $ward["ward_number"]
                            ) ?>

                        </td>


                        <td>

                            <?= htmlspecialchars(
                                $ward["ward_name"]
                            ) ?>

                        </td>


                        <?php if (
                            !empty(
                                $ward["councillor_username"]
                            )
                        ): ?>


                            <td>

                                <?= htmlspecialchars(
                                    trim(
                                        $ward["first_Name"]
                                        . " "
                                        . $ward["surname"]
                                    )
                                ) ?>

                            </td>


                            <td>

                                <?= htmlspecialchars(
                                    $ward[
                                        "councillor_username"
                                    ]
                                ) ?>

                            </td>


                            <td>

                                <?= htmlspecialchars(
                                    $ward["contact"] ?? ""
                                ) ?>

                            </td>


                            <td>

                                <span
                                    class="status assigned"
                                >
                                    Assigned
                                </span>

                            </td>


                            <td>

                                <button
                                    type="button"
                                    class="
                                        actionButton
                                        removeButton
                                    "
                                    onclick="openRemoveModal(
                                        '<?= htmlspecialchars(
                                            $ward[
                                                "councillor_username"
                                            ],
                                            ENT_QUOTES
                                        ) ?>',
                                        <?= (int)$ward["ward_id"] ?>,
                                        'Ward <?= htmlspecialchars(
                                            $ward["ward_number"],
                                            ENT_QUOTES
                                        ) ?>'
                                    )"
                                >
                                    Remove
                                </button>

                            </td>


                        <?php else: ?>


                            <td>—</td>

                            <td>—</td>

                            <td>—</td>


                            <td>

                                <span
                                    class="status vacant"
                                >
                                    Vacant
                                </span>

                            </td>


                            <td>

                                <?php

                                $hasApplicant = false;

                                foreach (
                                    $pendingCouncillors
                                    as $applicant
                                ) {

                                    if (
                                        (int)$applicant["ward_id"]
                                        ===
                                        (int)$ward["ward_id"]
                                    ) {

                                        $hasApplicant = true;

                                        break;
                                    }
                                }

                                ?>


                                <?php if ($hasApplicant): ?>

                                    <button
                                        type="button"
                                        class="
                                            actionButton
                                            assignButton
                                        "
                                        onclick="scrollToApplicants()"
                                    >
                                        View Applicants
                                    </button>

                                <?php else: ?>

                                    <span class="noApplicants">
                                        No applicant
                                    </span>

                                <?php endif; ?>

                            </td>


                        <?php endif; ?>


                    </tr>


                <?php endforeach; ?>


                </tbody>


            </table>


        </div>


    </section>


    <!-- ==================================================
         PENDING COUNCILLORS
         ================================================== -->

    <section
        class="section"
        id="pendingApplicants"
    >


        <h2 class="sectionTitle">
            Pending Councillor Applicants
        </h2>


        <div class="tableWrapper">


            <table>


                <thead>

                    <tr>

                        <th>Name</th>

                        <th>Email</th>

                        <th>Contact</th>

                        <th>Requested Ward</th>

                        <th>Status</th>

                        <th>Action</th>

                    </tr>

                </thead>


                <tbody>


                <?php if (
                    count($pendingCouncillors) === 0
                ): ?>


                    <tr>

                        <td colspan="6">
                            No pending councillor applicants.
                        </td>

                    </tr>


                <?php else: ?>


                    <?php foreach (
                        $pendingCouncillors
                        as $applicant
                    ): ?>


                        <?php

                        $applicantWardOccupied = false;


                        foreach ($wards as $ward) {

                            if (
                                (int)$ward["ward_id"]
                                ===
                                (int)$applicant["ward_id"]
                                &&
                                !empty(
                                    $ward[
                                        "councillor_username"
                                    ]
                                )
                            ) {

                                $applicantWardOccupied =
                                    true;

                                break;
                            }
                        }

                        ?>


                        <tr>


                            <td>

                                <?= htmlspecialchars(
                                    trim(
                                        $applicant[
                                            "first_Name"
                                        ]
                                        . " "
                                        . $applicant[
                                            "surname"
                                        ]
                                    )
                                ) ?>

                            </td>


                            <td>

                                <?= htmlspecialchars(
                                    $applicant["username"]
                                ) ?>

                            </td>


                            <td>

                                <?= htmlspecialchars(
                                    $applicant["contact"]
                                ) ?>

                            </td>


                            <td>

                                <?php if (
                                    !empty(
                                        $applicant[
                                            "ward_number"
                                        ]
                                    )
                                ): ?>

                                    Ward
                                    <?= htmlspecialchars(
                                        $applicant[
                                            "ward_number"
                                        ]
                                    ) ?>

                                <?php else: ?>

                                    Not selected

                                <?php endif; ?>

                            </td>


                            <td>

                                <?php if (
                                    $applicantWardOccupied
                                ): ?>

                                    <span
                                        class="status vacant"
                                    >
                                        Ward Occupied
                                    </span>

                                <?php else: ?>

                                    <span
                                        class="status pending"
                                    >
                                        Pending
                                    </span>

                                <?php endif; ?>

                            </td>


                            <td>


                                <?php if (
                                    !$applicantWardOccupied
                                    &&
                                    !empty(
                                        $applicant["ward_id"]
                                    )
                                ): ?>


                                    <button
                                        type="button"
                                        class="
                                            actionButton
                                            assignButton
                                        "
                                        onclick="openAssignModal(
                                            '<?= htmlspecialchars(
                                                $applicant[
                                                    "username"
                                                ],
                                                ENT_QUOTES
                                            ) ?>',
                                            <?= (int)$applicant[
                                                "ward_id"
                                            ] ?>,
                                            '<?= htmlspecialchars(
                                                trim(
                                                    $applicant[
                                                        "first_Name"
                                                    ]
                                                    . " "
                                                    . $applicant[
                                                        "surname"
                                                    ]
                                                ),
                                                ENT_QUOTES
                                            ) ?>',
                                            'Ward <?= htmlspecialchars(
                                                $applicant[
                                                    "ward_number"
                                                ],
                                                ENT_QUOTES
                                            ) ?>'
                                        )"
                                    >
                                        Assign
                                    </button>


                                <?php elseif (
                                    $applicantWardOccupied
                                ): ?>


                                    <span class="noApplicants">
                                        Remove current councillor first
                                    </span>


                                <?php else: ?>


                                    <span class="noApplicants">
                                        No ward selected
                                    </span>


                                <?php endif; ?>


                            </td>


                        </tr>


                    <?php endforeach; ?>


                <?php endif; ?>


                </tbody>


            </table>


        </div>


    </section>


</div>


<!-- ======================================================
     ADMIN PASSWORD MODAL
     ====================================================== -->

<div
    class="modalOverlay"
    id="passwordModal"
>


    <div class="modalBox">


        <h2 id="modalTitle">
            Confirm Action
        </h2>


        <p
            class="modalText"
            id="modalText"
        >
            Enter your administrator password to continue.
        </p>


        <form
            method="POST"
            action=""
            id="actionForm"
        >


            <input
                type="hidden"
                name="action"
                id="modalAction"
            >


            <input
                type="hidden"
                name="councillor_username"
                id="modalUsername"
            >


            <input
                type="hidden"
                name="ward_id"
                id="modalWardId"
            >


            <div class="modalField">

                <label for="adminPassword">
                    Administrator Password
                </label>

                <input
                    type="password"
                    name="admin_password"
                    id="adminPassword"
                    autocomplete="current-password"
                    required
                >

            </div>


            <div class="modalActions">


                <button
                    type="button"
                    class="
                        actionButton
                        cancelButton
                    "
                    onclick="closePasswordModal()"
                >
                    Cancel
                </button>


                <button
                    type="submit"
                    class="
                        actionButton
                        confirmButton
                    "
                    id="confirmButton"
                >
                    Confirm
                </button>


            </div>


        </form>


    </div>


</div>


<script>


    const passwordModal =
        document.getElementById(
            "passwordModal"
        );


    const modalTitle =
        document.getElementById(
            "modalTitle"
        );


    const modalText =
        document.getElementById(
            "modalText"
        );


    const modalAction =
        document.getElementById(
            "modalAction"
        );


    const modalUsername =
        document.getElementById(
            "modalUsername"
        );


    const modalWardId =
        document.getElementById(
            "modalWardId"
        );


    const adminPassword =
        document.getElementById(
            "adminPassword"
        );


    const confirmButton =
        document.getElementById(
            "confirmButton"
        );


    function openAssignModal(
        username,
        wardId,
        councillorName,
        wardName
    ) {

        modalAction.value =
            "assign";

        modalUsername.value =
            username;

        modalWardId.value =
            wardId;


        modalTitle.textContent =
            "Assign Ward Councillor";


        modalText.textContent =
            "Assign "
            + councillorName
            + " to "
            + wardName
            + "? Enter your administrator password to confirm.";


        confirmButton.textContent =
            "Assign Councillor";


        confirmButton.className =
            "actionButton confirmButton";


        adminPassword.value = "";


        passwordModal.classList.add(
            "show"
        );


        setTimeout(function () {

            adminPassword.focus();

        }, 100);
    }


    function openRemoveModal(
        username,
        wardId,
        wardName
    ) {

        modalAction.value =
            "remove";

        modalUsername.value =
            username;

        modalWardId.value =
            wardId;


        modalTitle.textContent =
            "Remove Ward Councillor";


        modalText.textContent =
            "Remove "
            + username
            + " from "
            + wardName
            + "? The account will be deactivated and the ward will become vacant.";


        confirmButton.textContent =
            "Remove Councillor";


        confirmButton.className =
            "actionButton removeButton";


        adminPassword.value = "";


        passwordModal.classList.add(
            "show"
        );


        setTimeout(function () {

            adminPassword.focus();

        }, 100);
    }


    function closePasswordModal() {

        passwordModal.classList.remove(
            "show"
        );


        adminPassword.value = "";
    }


    function scrollToApplicants() {

        document
            .getElementById(
                "pendingApplicants"
            )
            .scrollIntoView({
                behavior: "smooth"
            });
    }


    passwordModal.addEventListener(
        "click",
        function (event) {

            if (
                event.target
                === passwordModal
            ) {

                closePasswordModal();
            }
        }
    );


    document.addEventListener(
        "keydown",
        function (event) {

            if (event.key === "Escape") {

                closePasswordModal();
            }
        }
    );


</script>


</body>

</html>