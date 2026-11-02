<?php

session_start();

require_once "Error404Connection.php";

//check if administrator is logged in
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

$message = "";
$messageType = "";

/* =========================================================
   ADMIN PROFILE
========================================================= */

$adminName = "";
$adminRole = "Admin";
$profileImageUrl = "";

//get administrator profile information
$adminStmt = $conn->prepare("
    SELECT
        u.first_Name,
        u.surname,
        u.User_role,
        p.image_path
    FROM `user` u
    LEFT JOIN profile_images p
        ON u.profile_id = p.profile_id
    WHERE u.username = ?
    LIMIT 1
");

if ($adminStmt) {

    $adminStmt->bind_param(
        "s",
        $adminUsername
    );

    $adminStmt->execute();

    $adminResult =
        $adminStmt->get_result();

    if (
        $adminResult
        &&
        $adminResult->num_rows > 0
    ) {

        $adminData =
            $adminResult->fetch_assoc();

        $adminName =
            trim(
                $adminData["first_Name"]
                . " "
                . ($adminData["surname"] ?? "")
            );

        $adminRole =
            $adminData["User_role"]
            ?? "Admin";

        $profileImageUrl =
            $adminData["image_path"]
            ?? "";

    }

    $adminStmt->close();

}

//fallback administrator name
if ($adminName === "") {

    $adminName = $adminUsername;

}

/* =========================================================
   VERIFY ADMIN PASSWORD
========================================================= */

function verifyAdminPassword(
    $conn,
    $adminUsername,
    $password
) {

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

    $result =
        $stmt->get_result();

    if (
        !$result
        ||
        $result->num_rows === 0
    ) {

        $stmt->close();

        return false;

    }

    $admin =
        $result->fetch_assoc();

    $stmt->close();

    return password_verify(
        $password,
        $admin["password_hashed"]
    );

}

/* =========================================================
   RESTORE NORMAL INACTIVE ACCOUNT
========================================================= */

if (
    $_SERVER["REQUEST_METHOD"] === "POST"
    &&
    isset($_POST["action"])
    &&
    $_POST["action"] === "restore"
) {

    $username =
        trim(
            $_POST["username"]
            ?? ""
        );

    $adminPassword =
        $_POST["admin_password"]
        ?? "";

    if ($username === "") {

        $message =
            "Invalid user account.";

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

        //get the account before restoring it
        $stmt = $conn->prepare("
            SELECT
                username,
                User_role,
                active,
                verified
            FROM `user`
            WHERE username = ?
            LIMIT 1
        ");

        if (!$stmt) {

            $message =
                "Unable to check the user account.";

            $messageType =
                "error";

        }

        else {

            $stmt->bind_param(
                "s",
                $username
            );

            $stmt->execute();

            $result =
                $stmt->get_result();

            if (
                !$result
                ||
                $result->num_rows === 0
            ) {

                $message =
                    "User account was not found.";

                $messageType =
                    "error";

            }

            else {

                $account =
                    $result->fetch_assoc();

                $accountRole =
                    strtolower(
                        trim(
                            $account["User_role"]
                        )
                    );

                /*
                 * Ward Councillors cannot be
                 * restored from this page.
                 */

                if (
                    $accountRole === "ward councillor"
                    ||
                    $accountRole === "ward_councillor"
                ) {

                    $message =
                        "Ward Councillors must be reassigned from the Ward Councillors page.";

                    $messageType =
                        "error";

                }

                else {

                    //restore normal account
                    $restoreStmt =
                        $conn->prepare("
                            UPDATE `user`
                            SET active = 1
                            WHERE username = ?
                            AND active = 0
                        ");

                    if (!$restoreStmt) {

                        $message =
                            "Unable to restore the account.";

                        $messageType =
                            "error";

                    }

                    else {

                        $restoreStmt->bind_param(
                            "s",
                            $username
                        );

                        if (
                            $restoreStmt->execute()
                        ) {

                            if (
                                $restoreStmt->affected_rows > 0
                            ) {

                                $message =
                                    "User account restored successfully.";

                                $messageType =
                                    "success";

                            }

                            else {

                                $message =
                                    "The account is already active.";

                                $messageType =
                                    "error";

                            }

                        }

                        else {

                            $message =
                                "Unable to restore the account.";

                            $messageType =
                                "error";

                        }

                        $restoreStmt->close();

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
    trim(
        $_GET["search"]
        ?? ""
    );

$inactiveUsers = [];

/* =========================================================
   GET INACTIVE ACCOUNTS
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
            w.ward_number,
            w.ward_name

        FROM `user` u

        LEFT JOIN ward w
            ON u.ward_id = w.ward_id

        WHERE u.active = 0

        AND LOWER(TRIM(u.User_role)) <> 'admin'

        AND (
            u.username LIKE ?
            OR u.first_Name LIKE ?
            OR u.surname LIKE ?
            OR u.User_role LIKE ?
        )

        ORDER BY
            u.User_role ASC,
            u.first_Name ASC,
            u.surname ASC
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

            $inactiveUsers[] = $row;

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
            w.ward_number,
            w.ward_name

        FROM `user` u

        LEFT JOIN ward w
            ON u.ward_id = w.ward_id

        WHERE u.active = 0

        AND LOWER(TRIM(u.User_role)) <> 'admin'

        ORDER BY
            u.User_role ASC,
            u.first_Name ASC,
            u.surname ASC
    ");

    if ($result) {

        while (
            $row = $result->fetch_assoc()
        ) {

            $inactiveUsers[] = $row;

        }

    }

}

$totalInactive =
    count($inactiveUsers);

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
        Inactive Accounts | M-LINK
    </title>

    <style>

/* =========================================
   BODY
========================================= */

body {
    min-height: 100%;
}

body {
    min-height: 100vh;

    font-family: "Ubuntu", Arial, sans-serif;

    background: var(--cream);
    color: var(--text);
}

a {
    text-decoration: none;
}

button,
input,
select,
textarea {
    font-family: "Ubuntu", Arial, sans-serif;
}

.inactive-page {
    width: 100%;
}

/* =========================================
   PAGE HEADING
========================================= */

.dashboard-heading {
    margin-bottom: 28px;
}

.dashboard-heading h1 {
    margin: 0;

    font-family: "Lora", serif;

    font-size: 29px;
    font-weight: 600;

    color: var(--teal);
}

/* =========================================
   MESSAGE
========================================= */

.message {
    width: 100%;

    margin-bottom: 20px;

    padding: 13px 16px;

    border-radius: 7px;

    font-size: 13px;
}

.message.success {
    border: 1px solid #b9c799;

    background: #edf2e4;

    color: #39451a;
}

.message.error {
    border: 1px solid #dfb6b6;

    background: #f8e7e7;

    color: #8b3030;
}

/* =========================================
   CONTROLS
========================================= */

.controls {
    width: 100%;

    display: flex;
    align-items: center;
    justify-content: space-between;

    gap: 20px;

    margin-bottom: 20px;
}

.countBox {
    min-height: 44px;

    display: flex;
    align-items: center;

    gap: 5px;

    padding: 0 17px;

    border: 1px solid var(--border);
    border-radius: 7px;

    background: var(--white);

    color: var(--text);

    font-size: 13px;
}

.countBox strong {
    color: var(--green);

    font-family: "Lora", serif;

    font-size: 20px;
}

/* =========================================
   SEARCH
========================================= */

.searchForm {
    display: flex;
    align-items: center;

    gap: 8px;
}

.searchForm input {
    width: 280px;
    height: 42px;

    padding: 0 13px;

    border: 1px solid #d3cec3;
    border-radius: 6px;

    background: var(--white);

    color: var(--text);

    font-size: 12px;

    outline: none;
}

.searchForm input:focus {
    border-color: var(--green);
}

.searchForm input::placeholder {
    color: #969696;
}

.searchButton,
.clearButton {
    height: 42px;

    display: inline-flex;
    align-items: center;
    justify-content: center;

    padding: 0 18px;

    border: none;
    border-radius: 6px;

    font-size: 12px;
    font-weight: 600;

    cursor: pointer;
}

.searchButton {
    background: var(--green);

    color: var(--white);
}

.searchButton:hover {
    opacity: 0.9;
}

.clearButton {
    background: #e7e3da;

    color: var(--text);
}

.clearButton:hover {
    background: #ddd8ce;
}

/* =========================================
   TABLE
========================================= */

.tableContainer {
    width: 100%;

    border: 1px solid var(--border);
    border-radius: 9px;

    background: var(--white);

    overflow: hidden;

    box-shadow: 0 3px 12px rgba(0, 0, 0, 0.03);
}

.tableWrapper {
    width: 100%;

    overflow-x: auto;
}

.tableContainer table {
    width: 100%;

    min-width: 850px;

    border-collapse: collapse;
}

.tableContainer thead {
    background: var(--green);
}

.tableContainer th {
    padding: 14px 13px;

    background: var(--green);

    color: var(--white);

    font-size: 11px;
    font-weight: 600;

    text-align: left;

    white-space: nowrap;
}

.tableContainer td {
    padding: 15px 13px;

    border-bottom: 1px solid #eeeae2;

    color: var(--text);

    font-size: 12px;

    vertical-align: middle;
}

.tableContainer tbody tr:last-child td {
    border-bottom: none;
}

.tableContainer tbody tr:hover {
    background: #faf8f3;
}

/* =========================================
   STATUS
========================================= */

.status {
    display: inline-flex;
    align-items: center;
    justify-content: center;

    padding: 6px 11px;

    border-radius: 20px;

    font-size: 10px;
    font-weight: 600;

    white-space: nowrap;
}

.inactiveStatus {
    background: #f3e4e1;

    color: #8a3c31;
}

.councillorStatus {
    background: #f7e7d2;

    color: #8a531e;
}

/* =========================================
   RESTORE / REASSIGN BUTTON
========================================= */

.restoreButton,
.reassignButton {
    min-height: 34px;

    display: inline-flex;
    align-items: center;
    justify-content: center;

    padding: 7px 14px;

    border: none;
    border-radius: 5px;

    background: var(--green);

    color: var(--white);

    font-size: 11px;
    font-weight: 600;

    cursor: pointer;

    transition:
        opacity 0.2s ease,
        transform 0.2s ease;
}

.restoreButton:hover,
.reassignButton:hover {
    opacity: 0.9;
}

.restoreButton:active,
.reassignButton:active {
    transform: scale(0.98);
}

/* =========================================
   EMPTY STATE
========================================= */

.noAccounts {
    min-height: 180px;

    display: flex;
    align-items: center;
    justify-content: center;

    padding: 30px;

    color: #888888;

    font-size: 13px;

    text-align: center;
}

/* =========================================
   PASSWORD MODAL
========================================= */

.modalOverlay {
    position: fixed;

    inset: 0;

    display: none;
    align-items: center;
    justify-content: center;

    padding: 20px;

    background: rgba(0, 0, 0, 0.55);

    z-index: 3000;
}

.modalOverlay.show {
    display: flex;
}

.modalBox {
    width: 100%;
    max-width: 430px;

    padding: 27px;

    border-radius: 10px;

    background: var(--white);

    box-shadow:
        0 18px 50px rgba(0, 0, 0, 0.25);
}

.modalBox h2 {
    margin-bottom: 10px;

    font-family: "Lora", serif;

    font-size: 22px;
    font-weight: 600;

    color: var(--teal);
}

.modalText {
    margin-bottom: 21px;

    color: #666666;

    font-size: 13px;

    line-height: 1.6;
}

/* =========================================
   MODAL FIELD
========================================= */

.modalField {
    margin-bottom: 20px;
}

.modalField label {
    display: block;

    margin-bottom: 7px;

    color: var(--text);

    font-size: 12px;
    font-weight: 600;
}

.modalField input {
    width: 100%;
    height: 43px;

    padding: 0 12px;

    border: 1px solid #d3cec3;
    border-radius: 6px;

    background: var(--white);

    color: var(--text);

    font-size: 13px;

    outline: none;
}

.modalField input:focus {
    border-color: var(--green);
}

/* =========================================
   MODAL ACTIONS
========================================= */

.modalActions {
    display: flex;
    align-items: center;
    justify-content: flex-end;

    gap: 10px;
}

.modalButton {
    min-height: 39px;

    padding: 8px 16px;

    border: none;
    border-radius: 6px;

    font-size: 12px;
    font-weight: 600;

    cursor: pointer;
}

.cancelButton {
    background: #e8e5de;

    color: var(--text);
}

.cancelButton:hover {
    background: #ddd9d0;
}

.confirmButton {
    background: var(--green);

    color: var(--white);
}

.confirmButton:hover {
    opacity: 0.9;
}

/* =========================================
   MOBILE
========================================= */

@media (max-width: 750px) {

    .dashboard-heading h1 {
        font-size: 25px;
    }

    .controls {
        flex-direction: column;

        align-items: stretch;
    }

    .countBox {
        width: fit-content;
    }

    .searchForm {
        width: 100%;
    }

    .searchForm input {
        flex: 1;

        width: auto;
    }

}

/* =========================================
   SMALL MOBILE
========================================= */

@media (max-width: 500px) {

    .searchForm {
        flex-wrap: wrap;
    }

    .searchForm input {
        width: 100%;

        flex-basis: 100%;
    }

    .searchButton,
    .clearButton {
        flex: 1;
    }

    .modalBox {
        padding: 22px 18px;
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
$adminActivePage = "inactive";
require __DIR__ . "/adminHeader.php";
?>

        <!-- =====================================
             INACTIVE ACCOUNTS CONTENT
        ====================================== -->

        <main class="dashboard-content">

            <div class="inactive-page">

                <!-- PAGE HEADING -->

                <div class="dashboard-heading">

                    <h1>
                        Inactive Accounts
                    </h1>

                </div>

                <!-- MESSAGE -->

                <?php if ($message !== ""): ?>

                    <div
                        class="message <?= htmlspecialchars(
                            $messageType
                        ) ?>"
                    >

                        <?= htmlspecialchars(
                            $message
                        ) ?>

                    </div>

                <?php endif; ?>

                <!-- CONTROLS -->

                <div class="controls">

                    <div class="countBox">

                        Inactive Accounts:

                        <strong>
                            <?= $totalInactive ?>
                        </strong>

                    </div>

                    <form
                        method="GET"
                        class="searchForm"
                    >

                        <input
                            type="text"
                            name="search"
                            placeholder="Search inactive accounts..."
                            value="<?= htmlspecialchars(
                                $search
                            ) ?>"
                        >

                        <button
                            type="submit"
                            class="searchButton"
                        >
                            Search
                        </button>

                        <?php if ($search !== ""): ?>

                            <a
                                href="inactiveAccounts.php"
                                class="clearButton"
                            >
                                Clear
                            </a>

                        <?php endif; ?>

                    </form>

                </div>

                <!-- TABLE -->

                <div class="tableContainer">

                    <div class="tableWrapper">

                        <?php if (
                            count($inactiveUsers) === 0
                        ): ?>

                            <div class="noAccounts">
                                No inactive accounts found.
                            </div>

                        <?php else: ?>

                            <table>

                                <thead>

                                    <tr>

                                        <th>
                                            Name
                                        </th>

                                        <th>
                                            Email
                                        </th>

                                        <th>
                                            Contact
                                        </th>

                                        <th>
                                            Role
                                        </th>

                                        <th>
                                            Ward
                                        </th>

                                        <th>
                                            Status
                                        </th>

                                        <th>
                                            Action
                                        </th>

                                    </tr>

                                </thead>

                                <tbody>

                                <?php foreach (
                                    $inactiveUsers
                                    as $user
                                ): ?>

                                    <?php

                                    $rowRole =
                                        strtolower(
                                            trim(
                                                $user["User_role"]
                                            )
                                        );

                                    $isWardCouncillor =
                                        (
                                            $rowRole === "ward councillor"
                                            ||
                                            $rowRole === "ward_councillor"
                                        );

                                    $fullName =
                                        trim(
                                            $user["first_Name"]
                                            . " "
                                            . $user["surname"]
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
                                                $user["username"]
                                            ) ?>

                                        </td>

                                        <td>

                                            <?= htmlspecialchars(
                                                $user["contact"]
                                            ) ?>

                                        </td>

                                        <td>

                                            <?= htmlspecialchars(
                                                $user["User_role"]
                                            ) ?>

                                        </td>

                                        <td>

                                            <?php if (
                                                !empty(
                                                    $user["ward_number"]
                                                )
                                            ): ?>

                                                Ward
                                                <?= htmlspecialchars(
                                                    $user["ward_number"]
                                                ) ?>

                                            <?php else: ?>

                                                —

                                            <?php endif; ?>

                                        </td>

                                        <td>

                                            <?php if (
                                                $isWardCouncillor
                                            ): ?>

                                                <span
                                                    class="
                                                        status
                                                        councillorStatus
                                                    "
                                                >
                                                    Requires Assignment
                                                </span>

                                            <?php else: ?>

                                                <span
                                                    class="
                                                        status
                                                        inactiveStatus
                                                    "
                                                >
                                                    Inactive
                                                </span>

                                            <?php endif; ?>

                                        </td>

                                        <td>

                                            <?php if (
                                                $isWardCouncillor
                                            ): ?>

                                                <a
                                                    href="WardCouncillor.php"
                                                    class="reassignButton"
                                                >
                                                    Reassign
                                                </a>

                                            <?php else: ?>

                                                <button
                                                    type="button"
                                                    class="restoreButton"
                                                    onclick="openRestoreModal(
                                                        '<?= htmlspecialchars(
                                                            $user["username"],
                                                            ENT_QUOTES
                                                        ) ?>',
                                                        '<?= htmlspecialchars(
                                                            $fullName,
                                                            ENT_QUOTES
                                                        ) ?>'
                                                    )"
                                                >
                                                    Restore
                                                </button>

                                            <?php endif; ?>

                                        </td>

                                    </tr>

                                <?php endforeach; ?>

                                </tbody>

                            </table>

                        <?php endif; ?>

                    </div>

                </div>

            </div>

        </main>

<?php require __DIR__ . "/adminFooter.php"; ?>

<!-- =====================================
     PASSWORD CONFIRMATION MODAL
====================================== -->

<div
    class="modalOverlay"
    id="restoreModal"
>

    <div class="modalBox">

        <h2>
            Restore Account
        </h2>

        <p
            class="modalText"
            id="modalText"
        >
        </p>

        <form method="POST">

            <input
                type="hidden"
                name="action"
                value="restore"
            >

            <input
                type="hidden"
                name="username"
                id="restoreUsername"
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
                    onclick="closeRestoreModal()"
                >
                    Cancel
                </button>

                <button
                    type="submit"
                    class="
                        modalButton
                        confirmButton
                    "
                >
                    Restore Account
                </button>

            </div>

        </form>

    </div>

</div>

<script>

/* =========================================
   RESTORE MODAL
========================================= */

const restoreModal =
    document.getElementById(
        "restoreModal"
    );

const restoreUsername =
    document.getElementById(
        "restoreUsername"
    );

const modalText =
    document.getElementById(
        "modalText"
    );

const adminPassword =
    document.getElementById(
        "adminPassword"
    );

function openRestoreModal(
    username,
    name
) {

    restoreUsername.value =
        username;

    modalText.textContent =
        "Restore "
        + name
        + "? Enter your administrator password to confirm.";

    adminPassword.value = "";

    restoreModal.classList.add(
        "show"
    );

    setTimeout(
        function () {

            adminPassword.focus();

        },
        100
    );

}

function closeRestoreModal() {

    restoreModal.classList.remove(
        "show"
    );

    adminPassword.value = "";

}

restoreModal.addEventListener(
    "click",
    function (event) {

        if (
            event.target === restoreModal
        ) {

            closeRestoreModal();

        }

    }
);

document.addEventListener(
    "keydown",
    function (event) {

        if (event.key === "Escape") {

            closeRestoreModal();

        }

    }
);
</script>

</body>

</html>