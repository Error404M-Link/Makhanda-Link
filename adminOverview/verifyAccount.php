<?php

session_start();
require_once "Error404Connection.php";

//check if administrator is logged in
$message = "";
$messageType = "";

if (!isset($_SESSION["username"])) {
    header("Location: ../PUBLIC PAGES/login.php");
    exit;
}

$adminUsername = $_SESSION["username"];

//check administrator role
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

/* VERIFY ADMIN PASSWORD */

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

/* PROCESS VERIFY OR REJECT */

if (
    $_SERVER["REQUEST_METHOD"] === "POST"
    &&
    isset($_POST["action"])
    &&
    isset($_POST["username"])
) {

    $username = trim(
        $_POST["username"] ?? ""
    );

    $action = trim(
        $_POST["action"] ?? ""
    );

    $adminPassword =
        $_POST["admin_password"] ?? "";

    if ($username === "") {

        $message =
            "Invalid user account.";

        $messageType =
            "error";
    }

    elseif (
        $action !== "verify"
        &&
        $action !== "reject"
    ) {

        $message =
            "Invalid account action.";

        $messageType =
            "error";
    }

    elseif ($adminPassword === "") {

        $message =
            "Administrator password is required.";

        $messageType =
            "error";
    }

    elseif (
        !verifyAdminPassword(
            $conn,
            $adminUsername,
            $adminPassword
        )
    ) {

        $message =
            "Incorrect administrator password.";

        $messageType =
            "error";
    }

    else {
        /* GET USER ACCOUNT */
        $stmt = $conn->prepare("SELECT
                                    username,
                                    User_role,
                                    verified,
                                    active
                                FROM `user`
                                WHERE username = ?
                                LIMIT 1 ");

        if (!$stmt) {
            $message =
                "Unable to check the account.";
            $messageType =
                "error";
        }else {
            $stmt->bind_param("s", $username);
            $stmt->execute();
            $result =
                $stmt->get_result();
            if (!$result || $result->num_rows === 0
            ) {
                $message =
                    "Account was not found.";
                $messageType =
                    "error";
            }
            else {
                $account = $result->fetch_assoc();
                $accountRole =
                    strtolower(
                        trim(
                            $account["User_role"]
                        )
                    );
                /*
                 * This page is only allowed
                 * to manage Municipal Staff
                 * and Ward Councillor accounts.
                 */
                $isManagedRole =
                    (
                        $accountRole === "municipal_staff"
                        ||
                        $accountRole === "Municipal Staff"
                        ||
                        $accountRole === "ward_councillor"
                        ||
                        $accountRole === "Ward Councillor"
                    );
                if (!$isManagedRole) {
                    $message = "This account cannot be managed from the Verify Accounts page. Go to <b>User Management</b> to update the account.";
                    $messageType = "error";
                }

                /* VERIFY MUNICIPAL STAFF / WARD COUNCILLOR */
                elseif ($action === "verify") {
                    $verifyStmt = $conn->prepare("UPDATE `user`
                                        SET
                                            verified = 1,
                                            active = 1
                                        WHERE username = ?
                                        AND verified = 0
                        ");
                    if (!$verifyStmt) {
                        $message =
                            "Unable to verify the account.";
                        $messageType =
                            "error";
                    }

                    else {
                        $verifyStmt->bind_param(
                            "s",
                            $username
                        );

                        if (
                            $verifyStmt->execute()
                        ) {

                            if (
                                $verifyStmt->affected_rows > 0
                            ) {

                                $message =
                                    "Account verified successfully.";

                                $messageType =
                                    "success";
                            }

                            else {

                                $message =
                                    "This account has already been verified.";

                                $messageType =
                                    "error";
                            }

                        }

                        else {

                            $message =
                                "Unable to verify the account.";

                            $messageType =
                                "error";
                        }
                        $verifyStmt->close();
                    }
                }
                /* 
                   REJECT MUNICIPAL STAFF / WARD COUNCILLOR */
                elseif ($action === "reject") {
                    /* Soft reject. Keep the account in the user table. */
                    
                    $rejectStmt =
                        $conn->prepare("
                            UPDATE `user`

                            SET
                                verified = 0,
                                active = 0

                            WHERE username = ?
                        ");

                    if (!$rejectStmt) {

                        $message =
                            "Unable to reject the account.";

                        $messageType =
                            "error";
                    }

                    else {

                        $rejectStmt->bind_param(
                            "s",
                            $username
                        );

                        if (
                            $rejectStmt->execute()
                        ) {

                            $message =
                                "Account rejected successfully.";

                            $messageType =
                                "success";
                        }

                        else {

                            $message =
                                "Unable to reject the account.";

                            $messageType =
                                "error";
                        }

                        $rejectStmt->close();
                    }

                }

            }

            $stmt->close();
        }

    }

}

/* =========================================================
   SEARCH
========================================================= */

$search =
    trim($_GET["search"] ?? "");

$pendingAccounts = [];

/* =========================================================
   GET MUNICIPAL STAFF & WARD COUNCILLORS WAITING FOR VERIFICATION
========================================================= */

if ($search !== "") {

    $searchValue =
        "%" . $search . "%";

    $stmt = $conn->prepare("
        SELECT
            u.username,
            u.first_Name,
            u.surname,
            u.contact,
            u.User_role,
            u.ward_id,
            u.verified,
            u.active,
            u.date_joined,
            w.ward_number,
            w.ward_name

        FROM `user` u

        LEFT JOIN ward w
            ON u.ward_id = w.ward_id

        WHERE u.verified = 0

        AND LOWER(TRIM(u.User_role)) IN (
            'municipal_staff',
            'municipal staff',
            'ward_councillor',
            'ward councillor'
        )

        AND (
            u.username LIKE ?
            OR u.first_Name LIKE ?
            OR u.surname LIKE ?
            OR u.contact LIKE ?
        )

        ORDER BY
            u.date_joined DESC
    ");

    if ($stmt) {

        $stmt->bind_param(
            "ssss",
            $searchValue,
            $searchValue,
            $searchValue,
            $searchValue
        );

        $stmt->execute();

        $result =
            $stmt->get_result();

        while (
            $result
            &&
            $row = $result->fetch_assoc()
        ) {

            $pendingAccounts[] = $row;
        }

        $stmt->close();
    }

}

else {

    $result = $conn->query("
        SELECT
            u.username,
            u.first_Name,
            u.surname,
            u.contact,
            u.User_role,
            u.ward_id,
            u.verified,
            u.active,
            u.date_joined,
            w.ward_number,
            w.ward_name

        FROM `user` u

        LEFT JOIN ward w
            ON u.ward_id = w.ward_id

        WHERE u.verified = 0

        AND LOWER(TRIM(u.User_role)) IN (
            'municipal_staff',
            'municipal staff',
            'ward_councillor',
            'ward councillor'
        )

        ORDER BY
            u.date_joined DESC
    ");

    if ($result) {

        while (
            $row = $result->fetch_assoc()
        ) {

            $pendingAccounts[] = $row;
        }

    }

}

$totalPending =
    count($pendingAccounts);

?>

<!DOCTYPE html>

<html lang="en">

<head>

    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0"
    >

    <title>
        Verify Accounts | M-LINK
    </title>

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
            flex: 1;
            padding: 30px;
        }

        /* top */

        .topBar {
            margin-bottom: 28px;
        }

        .pageTitle {
            text-align: left;
            color: #4b561b;
            font-size: 27px;
        }

        /* message */

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

        /* controls */

        .controls {
            max-width: 1200px;
            margin: 0 auto 20px auto;
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 20px;
        }

        .countBox {
            background: white;
            border: 1px solid #ded9ce;
            padding: 12px 18px;
            border-radius: 6px;
            font-size: 14px;
        }

        .countBox strong {
            color: #4b561b;
            font-size: 18px;
        }

        .searchForm {
            display: flex;
            gap: 8px;
        }

        .searchForm input {
            width: 270px;
            padding: 10px 12px;
            border: 1px solid #cfc9bd;
            border-radius: 5px;
            outline: none;
        }

        .searchForm input:focus {
            border-color: #4b561b;
        }

        .searchButton {
            border: none;
            background: #4b561b;
            color: white;
            padding: 10px 17px;
            border-radius: 5px;
            cursor: pointer;
            font-weight: bold;
        }

        .clearButton {
            display: inline-flex;
            align-items: center;
            text-decoration: none;
            background: #e5e1d8;
            color: #333;
            padding: 10px 15px;
            border-radius: 5px;
            font-size: 13px;
        }

        /* table */

        .tableContainer {
            max-width: 1200px;
            margin: 0 auto;
            background: white;
            border-radius: 8px;
            border: 1px solid #ded9ce;
            overflow: hidden;
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
            text-align: left;
            padding: 13px 12px;
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

        /* status */

        .status {
            display: inline-block;
            padding: 6px 10px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: bold;
        }

        .pendingStatus {
            background: #f5e4cf;
            color: #80511f;
        }

        /* action buttons */

        .actions {
            display: flex;
            gap: 7px;
            flex-wrap: wrap;
        }

        .verifyButton,
        .rejectButton {
            border: none;
            padding: 8px 13px;
            border-radius: 5px;
            cursor: pointer;
            font-size: 12px;
            font-weight: bold;
        }

        .verifyButton {
            background: #4b561b;
            color: white;
        }

        .rejectButton {
            background: #963b3b;
            color: white;
        }

        .verifyButton:hover,
        .rejectButton:hover {
            opacity: 0.88;
        }

        .noAccounts {
            padding: 35px;
            text-align: center;
            color: #777;
        }

        /* modal */

        .modalOverlay {
            display: none;
            position: fixed;
            inset: 0;
            background: rgba(0, 0, 0, 0.55);
            align-items: center;
            justify-content: center;
            z-index: 2000;
            padding: 20px;
        }

        .modalOverlay.show {
            display: flex;
        }

        .modalBox {
            width: 100%;
            max-width: 430px;
            background: white;
            padding: 25px;
            border-radius: 9px;
            box-shadow:
                0 15px 45px rgba(0, 0, 0, 0.25);
        }

        .modalBox h2 {
            color: #4b561b;
            font-size: 21px;
            margin-bottom: 10px;
        }

        .modalText {
            color: #555;
            font-size: 14px;
            line-height: 1.5;
            margin-bottom: 20px;
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

        .modalButton {
            border: none;
            padding: 9px 14px;
            border-radius: 5px;
            cursor: pointer;
            font-weight: bold;
        }

        .cancelButton {
            background: #e4e1da;
            color: #333;
        }

        .confirmButton {
            background: #4b561b;
            color: white;
        }

        .rejectConfirm {
            background: #963b3b;
            color: white;
        }

        /* mobile */

        @media (max-width: 750px) {

            .page {
                padding: 20px 12px;
            }

            

            

            .controls {
                flex-direction: column;
                align-items: stretch;
            }

            .searchForm {
                width: 100%;
            }

            .searchForm input {
                flex: 1;
                width: auto;
            }

        }

    
</style>

<!-- FONTS + ICONS USED BY THE SHARED ADMIN SIDEBAR / TOPBAR -->
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Lora:wght@400;500;600;700&family=Ubuntu:wght@400;500;600;700&display=swap" rel="stylesheet">
<link href="https://fonts.googleapis.com/icon?family=Material+Icons" rel="stylesheet">

<!-- SHARED ADMIN SIDEBAR / TOPBAR / FOOTER STYLES (keep this LAST in the head) -->
<link rel="stylesheet" href="adminLayout.css">

</head>

<body>

<?php
$adminActivePage = "verify";
require __DIR__ . "/adminHeader.php";
?>

<div class="page">
<div class="topBar">
<h1 class="pageTitle">
            Verify Accounts
        </h1>
</div>

    

    <!-- message -->

    <?php if ($message !== ""): ?>

        <div
            class="message <?= htmlspecialchars($messageType) ?>"
        >

            <?= htmlspecialchars($message) ?>

        </div>

    <?php endif; ?>

    <!-- controls -->

    <div class="controls">

        <div class="countBox">

            Pending Staff Accounts:

            <strong>
                <?= $totalPending ?>
            </strong>

        </div>

        <form
            method="GET"
            class="searchForm"
        >

            <input
                type="text"
                name="search"
                placeholder="Search staff accounts..."
                value="<?= htmlspecialchars($search) ?>"
            >

            <button
                type="submit"
                class="searchButton"
            >
                Search
            </button>

            <?php if ($search !== ""): ?>

                <a
                    href="verifyAccount.php"
                    class="clearButton"
                >
                    Clear
                </a>

            <?php endif; ?>

        </form>

    </div>

    <!-- table -->

    <div class="tableContainer">

        <div class="tableWrapper">

            <?php if (
                count($pendingAccounts) === 0
            ): ?>

                <div class="noAccounts">

                    No accounts
                    waiting for verification.

                </div>

            <?php else: ?>

                <table>

                    <thead>

                    <tr>

                        <th>Name</th>

                        <th>Email</th>

                        <th>Contact</th>

                        <th>Role</th>

                        <th>Ward</th>

                        <th>Status</th>

                        <th>Action</th>

                    </tr>

                    </thead>

                    <tbody>

                    <?php foreach (
                        $pendingAccounts
                        as $account
                    ): ?>

                        <?php

                        $fullName =
                            trim(
                                $account["first_Name"]
                                . " "
                                . $account["surname"]
                            );

                        ?>

                        <tr>

                            <td>

                                <?= htmlspecialchars(
                                    $fullName
                                ) ?>

                            </td>

                            <td>

                                <?= htmlspecialchars(
                                    $account["username"]
                                ) ?>

                            </td>

                            <td>

                                <?= htmlspecialchars(
                                    $account["contact"]
                                ) ?>

                            </td>

                            <td>

                                <?= htmlspecialchars(
                                    $account["User_role"]
                                ) ?>

                            </td>

                            <td>

                                <?php if (
                                    !empty(
                                        $account["ward_number"]
                                    )
                                ): ?>

                                    Ward
                                    <?= htmlspecialchars(
                                        $account["ward_number"]
                                    ) ?>

                                <?php else: ?>

                                    —

                                <?php endif; ?>

                            </td>

                            <td>

                                <span
                                    class="
                                        status
                                        pendingStatus
                                    "
                                >
                                    Pending
                                </span>

                            </td>

                            <td>

                                <div class="actions">

                                    <button
                                        type="button"
                                        class="verifyButton"
                                        onclick="openActionModal(
                                            'verify',
                                            '<?= htmlspecialchars(
                                                $account["username"],
                                                ENT_QUOTES
                                            ) ?>',
                                            '<?= htmlspecialchars(
                                                $fullName,
                                                ENT_QUOTES
                                            ) ?>'
                                        )"
                                    >
                                        Verify
                                    </button>

                                    <button
                                        type="button"
                                        class="rejectButton"
                                        onclick="openActionModal(
                                            'reject',
                                            '<?= htmlspecialchars(
                                                $account["username"],
                                                ENT_QUOTES
                                            ) ?>',
                                            '<?= htmlspecialchars(
                                                $fullName,
                                                ENT_QUOTES
                                            ) ?>'
                                        )"
                                    >
                                        Reject
                                    </button>

                                </div>

                            </td>

                        </tr>

                    <?php endforeach; ?>

                    </tbody>

                </table>

            <?php endif; ?>

        </div>

    </div>

</div>

<!-- administrator password modal -->

<div
    class="modalOverlay"
    id="actionModal"
>

    <div class="modalBox">

        <h2 id="modalTitle">
            Confirm Action
        </h2>

        <p
            class="modalText"
            id="modalText"
        ></p>

        <form method="POST">

            <input
                type="hidden"
                name="action"
                id="modalAction"
            >

            <input
                type="hidden"
                name="username"
                id="modalUsername"
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
                        modalButton
                        cancelButton
                    "
                    onclick="closeActionModal()"
                >
                    Cancel
                </button>

                <button
                    type="submit"
                    class="
                        modalButton
                        confirmButton
                    "
                    id="confirmActionButton"
                >
                    Confirm
                </button>

            </div>

        </form>

    </div>

</div>

<?php require __DIR__ . "/adminFooter.php"; ?>

<script>

const actionModal =
    document.getElementById(
        "actionModal"
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

const adminPassword =
    document.getElementById(
        "adminPassword"
    );

const confirmActionButton =
    document.getElementById(
        "confirmActionButton"
    );

function openActionModal(
    action,
    username,
    name
) {

    modalAction.value =
        action;

    modalUsername.value =
        username;

    adminPassword.value = "";

    if (action === "verify") {

        modalTitle.textContent =
            "Verify Account";

        modalText.textContent =
            "Verify "
            + name
            + "? Enter your administrator password to confirm.";

        confirmActionButton.textContent =
            "Verify Account";

        confirmActionButton.className =
            "modalButton confirmButton";

    }

    else if (action === "reject") {

        modalTitle.textContent =
            "Reject Account";

        modalText.textContent =
            "Reject "
            + name
            + "? Enter your administrator password to confirm.";

        confirmActionButton.textContent =
            "Reject Account";

        confirmActionButton.className =
            "modalButton rejectConfirm";

    }

    actionModal.classList.add(
        "show"
    );

    setTimeout(function () {

        adminPassword.focus();

    }, 100);

}

function closeActionModal()
{

    actionModal.classList.remove(
        "show"
    );

    adminPassword.value = "";

}

actionModal.addEventListener(
    "click",
    function (event) {

        if (
            event.target === actionModal
        ) {

            closeActionModal();
        }

    }
);

document.addEventListener(
    "keydown",
    function (event) {

        if (event.key === "Escape") {

            closeActionModal();
        }

    }
);

</script>

</body>

</html>