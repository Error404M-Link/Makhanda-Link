<?php

session_start();

require_once "Error404Connection.php";

if (!isset($_SESSION["username"])) {
    header("Location: ../PUBLIC PAGES/login.php");
    exit;
}

$adminUsername = $_SESSION["username"];

if (
    !isset($_SESSION["user_role"]) ||
    strtolower(trim($_SESSION["user_role"])) !== "admin"
) {
    die("Access denied.");
}

$message = "";
$messageType = "";

if (
    $_SERVER["REQUEST_METHOD"] === "POST" &&
    isset($_POST["action"]) &&
    $_POST["action"] === "delete" &&
    isset($_POST["username"])
) {

    $username = trim($_POST["username"]);
    $adminPassword = $_POST["admin_password"] ?? "";

    if ($adminPassword === "") {

        $message = "Please enter your administrator password to confirm this action.";
        $messageType = "error";

    } else {

        //verify the administrator's own password before making any changes
        $adminStmt = $conn->prepare("
            SELECT password_hashed
            FROM admin
            WHERE username = ?
            LIMIT 1
        ");

        if ($adminStmt === false) {
            die("Admin lookup SQL Error: " . $conn->error);
        }

        $adminStmt->bind_param("s", $adminUsername);
        $adminStmt->execute();

        $adminResult = $adminStmt->get_result();

        if (!$adminResult || $adminResult->num_rows === 0) {

            $adminStmt->close();
            die("Administrator account was not found.");
        }

        $admin = $adminResult->fetch_assoc();

        $adminStmt->close();

        if (!password_verify($adminPassword, $admin["password_hashed"])) {

            $message = "Incorrect administrator password.";
            $messageType = "error";

        } else {

            $sql_delete = "
                UPDATE `user`
                SET active = 0
                WHERE username = ?
            ";

            $stmt = $conn->prepare($sql_delete);

            if ($stmt === false) {
                die("Deletion SQL Error: " . $conn->error);
            }

            $stmt->bind_param("s", $username);

            if ($stmt->execute()) {

                if ($stmt->affected_rows > 0) {
                    $message = "User account deactivated successfully.";
                    $messageType = "success";
                } else {
                    $message = "No active user account was found.";
                    $messageType = "error";
                }

            } else {

                $message = "User account deactivation failed.";
                $messageType = "error";
            }

            $stmt->close();
        }
    }
}

$search = trim($_GET["search"] ?? "");

if ($search !== "") {

    $searchTerm = "%" . $search . "%";

    $sql = "
        SELECT
            username,
            profile_id,
            first_Name,
            surname,
            contact,
            date_joined,
            active,
            User_role,
            address,
            ward_id,
            verified
        FROM `user`
        WHERE active = 1
        AND (
            first_Name LIKE ?
            OR surname LIKE ?
            OR username LIKE ?
            OR contact LIKE ?
            OR User_role LIKE ?
            OR address LIKE ?
            OR CAST(ward_id AS CHAR) LIKE ?
        )
        ORDER BY first_Name ASC
    ";

    $stmt = $conn->prepare($sql);

    if ($stmt === false) {
        die("Search SQL Error: " . $conn->error);
    }

    $stmt->bind_param(
        "sssssss",
        $searchTerm,
        $searchTerm,
        $searchTerm,
        $searchTerm,
        $searchTerm,
        $searchTerm,
        $searchTerm
    );

    $stmt->execute();

    $result = $stmt->get_result();

} else {

    $sql = "
        SELECT
            username,
            profile_id,
            first_Name,
            surname,
            contact,
            date_joined,
            active,
            User_role,
            address,
            ward_id,
            verified
        FROM `user`
        WHERE active = 1
        ORDER BY first_Name ASC
    ";

    $result = $conn->query($sql);

    if (!$result) {
        die("Database Error: " . $conn->error);
    }
}

$totalUsers = $result->num_rows;

?>

<!DOCTYPE html>

<html lang="en">

<head>

    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0"
    >
    <link
        rel="stylesheet"
        href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css"
        >

    <title>M-Link | User Management</title>

    <style>

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: Arial, Helvetica, sans-serif;
            background: #f4f0e7;
            color: #333;
            min-height: 100vh;
        }

        .dashboard-content {
            padding: 20px 35px 35px;
            flex: 1;
        }

        .page-heading h1 {
            font-size: 30px;
            font-weight: 600;
            color: #333;
            margin-bottom: 7px;
        }

        .page-heading p {
            font-size: 14px;
            color: #777;
        }

        .page-heading {
            display: flex;
            justify-content: flex-end;
            align-items: center;
            margin-bottom: 25px;
        }

        .user-count {
            min-width: 150px;
            background: white;
            border: 1px solid #ddd7ca;
            border-radius: 6px;
            padding: 17px 25px;
            text-align: center;
        }

        .user-count span {
            display: block;
            font-size: 12px;
            color: #777;
            margin-bottom: 5px;
        }

        .user-count strong {
            display: block;
            font-size: 27px;
            color: #4b561b;
        }

        .message {
            background: #edf3df;
            border: 1px solid #c8d5a8;
            color: #4b561b;
            padding: 13px 16px;
            border-radius: 5px;
            margin-bottom: 20px;
            font-size: 14px;
        }

        .message.error {
            background: #f7e3e3;
            border: 1px solid #dfb0b0;
            color: #8b2424;
        }

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
            max-width: 420px;
            background: white;
            border-radius: 8px;
            padding: 25px;
            box-shadow: 0 15px 45px rgba(0, 0, 0, 0.25);
        }

        .modalBox h2 {
            font-size: 19px;
            color: #333;
            margin-bottom: 8px;
        }

        .modalBox p {
            font-size: 13px;
            color: #666;
            line-height: 1.5;
            margin-bottom: 18px;
        }

        .modalField {
            margin-bottom: 18px;
        }

        .modalField label {
            display: block;
            font-size: 13px;
            font-weight: 600;
            margin-bottom: 7px;
            color: #333;
        }

        .modalField input {
            width: 100%;
            height: 42px;
            padding: 0 12px;
            border: 1px solid #d7dbe0;
            border-radius: 5px;
            font-family: Arial, Helvetica, sans-serif;
            font-size: 13px;
            outline: none;
        }

        .modalField input:focus {
            border-color: #68772a;
        }

        .modalActions {
            display: flex;
            justify-content: flex-end;
            gap: 10px;
        }

        .modalCancelButton,
        .modalConfirmButton {
            height: 40px;
            padding: 0 18px;
            border: none;
            border-radius: 5px;
            font-family: Arial, Helvetica, sans-serif;
            font-size: 13px;
            font-weight: 600;
            cursor: pointer;
        }

        .modalCancelButton {
            background: #e9e5db;
            color: #333;
        }

        .modalCancelButton:hover {
            background: #ddd7ca;
        }

        .modalConfirmButton {
            background: #a22d1d;
            color: white;
        }

        .modalConfirmButton:hover {
            background: #8a2617;
        }

        .dashboard-panel {
            background: white;
            border: 1px solid #ddd7ca;
            border-radius: 7px;
            overflow: hidden;
        }

        .panel-header {
            padding: 22px 25px;
            border-bottom: 1px solid #e5e0d7;
        }

        .user-management-actions {
            width: 100%;
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .search-form {
            flex: 1;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .search-box {
            position: relative;
            flex: 1;
        }

        .search-box input {
            width: 100%;
            height: 42px;
            padding: 0 15px 0 42px;
            border: 1px solid #d7dbe0;
            border-radius: 5px;
            background: #f7f8fa;
            font-family: Arial, Helvetica, sans-serif;
            font-size: 13px;
            color: #333;
            outline: none;
        }

        .search-box input::placeholder {
            color: #888;
        }

        .search-box input:focus {
            border-color: #68772a;
            background: white;
        }

        .search-icon {
            position: absolute;
            left: 14px;
            top: 50%;
            transform: translateY(-50%);
            font-size: 14px;
            pointer-events: none;
        }

        .search-button {
            height: 42px;
            padding: 0 20px;
            border: none;
            border-radius: 5px;
            background: #5f7022;
            color: white;
            font-family: Arial, Helvetica, sans-serif;
            font-size: 13px;
            font-weight: 600;
            cursor: pointer;
            white-space: nowrap;
        }

        .search-button:hover {
            background: #4b591a;
        }

        .clear-button {
            height: 42px;
            padding: 0 18px;
            display: flex;
            align-items: center;
            justify-content: center;
            border: 1px solid #d1cbc0;
            border-radius: 5px;
            background: white;
            color: #555;
            text-decoration: none;
            font-size: 13px;
            font-weight: 600;
            white-space: nowrap;
        }

        .clear-button:hover {
            background: #f4f1eb;
        }

        .register-button {
            height: 42px;
            padding: 0 20px;
            display: flex;
            align-items: center;
            justify-content: center;
            background: #5f7022;
            color: white;
            border-radius: 5px;
            text-decoration: none;
            font-size: 13px;
            font-weight: 600;
            white-space: nowrap;
        }

        .register-button:hover {
            background: #4b591a;
        }

        .table-container {
            width: 100%;
            overflow-x: auto;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            min-width: 1050px;
        }

        thead {
            background: #f7f5ef;
        }

        th {
            padding: 14px 16px;
            text-align: left;
            font-size: 12px;
            font-weight: 600;
            color: #666;
            text-transform: uppercase;
            letter-spacing: 0.3px;
            border-bottom: 1px solid #ddd7ca;
        }

        td {
            padding: 14px 16px;
            font-size: 13px;
            color: #333;
            border-bottom: 1px solid #ebe7df;
            vertical-align: middle;
        }

        tbody tr:hover {
            background: #faf9f5;
        }

        tbody tr:last-child td {
            border-bottom: none;
        }

        .role {
            display: inline-block;
            padding: 5px 10px;
            background: #f0f2e8;
            color: #59651f;
            border-radius: 4px;
            font-size: 11px;
            font-weight: 600;
        }

        .status {
            display: inline-block;
            padding: 5px 10px;
            border-radius: 4px;
            font-size: 11px;
            font-weight: 600;
        }

        .status.active {
            background: #e1ecd2;
            color: #4b681c;
        }

        .status.inactive {
            background: #eee1de;
            color: #8b3b31;
        }

        .action-buttons {
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .action-buttons form {
            margin: 0;
        }

        .update-button {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            min-width: 55px;
            height: 32px;
            padding: 0 12px;
            border-radius: 4px;
            background: #5f7022;
            color: white;
            text-decoration: none;
            font-size: 12px;
            font-weight: 600;
        }

        .update-button:hover {
            background: #4b591a;
        }

        .delete-button {
            height: 32px;
            padding: 0 12px;
            border: none;
            border-radius: 4px;
            background: #f1dfda;
            color: #a22d1d;
            font-family: Arial, Helvetica, sans-serif;
            font-size: 12px;
            font-weight: 600;
            cursor: pointer;
        }

        .delete-button:hover {
            background: #e9ccc5;
        }

        .empty-state {
            padding: 50px 25px;
            text-align: center;
        }

        .empty-state h3 {
            font-size: 18px;
            color: #333;
            margin-bottom: 8px;
        }

        .empty-state p {
            font-size: 13px;
            color: #777;
        }

        @media (max-width: 1000px) {

            .user-management-actions {
                flex-wrap: wrap;
            }

            .search-form {
                flex-basis: 100%;
            }

            .register-button {
                margin-left: auto;
            }

        }

        @media (max-width: 700px) {

            .dashboard-content {
                padding: 18px;
            }

            .page-heading {
                justify-content: stretch;
            }

            .user-count {
                width: 100%;
            }

            .panel-header {
                padding: 18px;
            }

            .user-management-actions {
                flex-direction: column;
                align-items: stretch;
            }

            .search-form {
                width: 100%;
                flex-direction: column;
                align-items: stretch;
            }

            .search-box {
                width: 100%;
            }

            .search-button,
            .clear-button,
            .register-button {
                width: 100%;
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
$adminActivePage = "users";
require __DIR__ . "/adminHeader.php";
?>

        <div class="dashboard-content">

            <section class="page-heading">
<div>
<h1>
User Management
</h1>
<p>
View, search and manage registered M-LINK users.
</p>
</div>

                <div class="user-count">

                    <span>
                        Total Users
                    </span>

                    <strong>
                        <?= number_format($totalUsers) ?>
                    </strong>

                </div>

            </section>

            <?php if (!empty($message)): ?>

                <div class="message <?= htmlspecialchars($messageType, ENT_QUOTES, "UTF-8") ?>">

                    <?= htmlspecialchars(
                        $message,
                        ENT_QUOTES,
                        "UTF-8"
                    ) ?>

                </div>

            <?php endif; ?>

            <section class="dashboard-panel">

                <div class="panel-header">

                    <div class="user-management-actions">

                        <form
                            method="GET"
                            action="UserM.php"
                            class="search-form"
                        >

                            <div class="search-box">

                                <span class="search-icon">
                                    <i class="fa-solid fa-magnifying-glass"></i>
                                </span>

                                <input
                                    type="text"
                                    name="search"
                                    placeholder="Search users..."
                                    value="<?= htmlspecialchars(
                                        $search,
                                        ENT_QUOTES,
                                        "UTF-8"
                                    ) ?>"
                                >

                            </div>

                            <button
                                type="submit"
                                class="search-button"
                            >
                                Search
                            </button>

                            <?php if ($search !== ""): ?>

                                <a
                                    href="UserM.php"
                                    class="clear-button"
                                >
                                    Clear
                                </a>

                            <?php endif; ?>

                        </form>

                        <a
                            href="../PUBLIC PAGES/register.php"
                            class="register-button"
                        >
                            Register User
                        </a>

                    </div>

                </div>

                <div class="table-container">

                    <?php if ($result && $result->num_rows > 0): ?>

                        <table>

                            <thead>

                                <tr>

                                    <th>Name</th>

                                    <th>Username</th>

                                    <th>Contact</th>

                                    <th>Role</th>

                                    <th>Ward</th>

                                    <th>Address</th>

                                    <th>Status</th>

                                    <th>Actions</th>

                                </tr>

                            </thead>

                            <tbody>

                                <?php while ($row = $result->fetch_assoc()): ?>

                                    <tr>

                                        <td>

                                            <strong>

                                                <?= htmlspecialchars(
                                                    trim(
                                                        ($row["first_Name"] ?? "") .
                                                        " " .
                                                        ($row["surname"] ?? "")
                                                    ),
                                                    ENT_QUOTES,
                                                    "UTF-8"
                                                ) ?>

                                            </strong>

                                        </td>

                                        <td>

                                            <?= htmlspecialchars(
                                                $row["username"],
                                                ENT_QUOTES,
                                                "UTF-8"
                                            ) ?>

                                        </td>

                                        <td>

                                            <?= htmlspecialchars(
                                                $row["contact"] ?? "Not provided",
                                                ENT_QUOTES,
                                                "UTF-8"
                                            ) ?>

                                        </td>

                                        <td>

                                            <span class="role">

                                                <?= htmlspecialchars(
                                                    $row["User_role"],
                                                    ENT_QUOTES,
                                                    "UTF-8"
                                                ) ?>

                                            </span>

                                        </td>

                                        <td>

                                            <?= htmlspecialchars(
                                                $row["ward_id"] ?? "Not assigned",
                                                ENT_QUOTES,
                                                "UTF-8"
                                            ) ?>

                                        </td>

                                        <td>

                                            <?= htmlspecialchars(
                                                $row["address"] ?? "Not provided",
                                                ENT_QUOTES,
                                                "UTF-8"
                                            ) ?>

                                        </td>

                                        <td>

                                            <?php if ((int)$row["active"] === 1): ?>

                                                <span class="status active">
                                                    Active
                                                </span>

                                            <?php else: ?>

                                                <span class="status inactive">
                                                    Inactive
                                                </span>

                                            <?php endif; ?>

                                        </td>

                                        <td>

                                            <div class="action-buttons">

                                                <a
                                                    href="UpdateUserrecord.php?username=<?= urlencode(
                                                        $row["username"]
                                                    ) ?>"
                                                    class="update-button"
                                                >
                                                    Edit
                                                </a>

                                                <button
                                                    type="button"
                                                    class="delete-button"
                                                    data-username="<?= htmlspecialchars(
                                                        $row["username"],
                                                        ENT_QUOTES,
                                                        "UTF-8"
                                                    ) ?>"
                                                    onclick="openDeleteModal(this)"
                                                >
                                                    Delete
                                                </button>

                                            </div>

                                        </td>

                                    </tr>

                                <?php endwhile; ?>

                            </tbody>

                        </table>

                    <?php else: ?>

                        <div class="empty-state">

                            <h3>
                                No registered users found
                            </h3>

                            <?php if ($search !== ""): ?>

                                <p>

                                    No users matched your search for
                                    "<?= htmlspecialchars(
                                        $search,
                                        ENT_QUOTES,
                                        "UTF-8"
                                    ) ?>".

                                </p>

                            <?php else: ?>

                                <p>
                                    There are currently no user accounts in the system.
                                </p>

                            <?php endif; ?>

                        </div>

                    <?php endif; ?>

                </div>

            </section>

        </div>

<?php require __DIR__ . "/adminFooter.php"; ?>

<!-- ======================================================
     DELETE CONFIRMATION MODAL (password required)
     ====================================================== -->

<div
    class="modalOverlay"
    id="deleteModal"
>

    <div class="modalBox">

        <h2>
            Deactivate User
        </h2>

        <p id="deleteModalText">
            Enter your administrator password to deactivate this user account.
        </p>

        <form
            method="POST"
            action="UserM.php"
            id="deleteUserForm"
        >

            <input
                type="hidden"
                name="username"
                id="deleteUsernameInput"
            >

            <input
                type="hidden"
                name="action"
                value="delete"
            >

            <div class="modalField">

                <label for="deleteAdminPassword">
                    Administrator Password
                </label>

                <input
                    type="password"
                    name="admin_password"
                    id="deleteAdminPassword"
                    autocomplete="current-password"
                    required
                >

            </div>

            <div class="modalActions">

                <button
                    type="button"
                    class="modalCancelButton"
                    onclick="closeDeleteModal()"
                >
                    Cancel
                </button>

                <button
                    type="submit"
                    class="modalConfirmButton"
                >
                    Deactivate
                </button>

            </div>

        </form>

    </div>

</div>

<script>

    const deleteModal =
        document.getElementById("deleteModal");

    const deleteModalText =
        document.getElementById("deleteModalText");

    const deleteUsernameInput =
        document.getElementById("deleteUsernameInput");

    const deleteAdminPassword =
        document.getElementById("deleteAdminPassword");

    function openDeleteModal(button) {

        const username = button.dataset.username;

        deleteUsernameInput.value = username;
        deleteAdminPassword.value = "";

        deleteModalText.textContent =
            "Enter your administrator password to deactivate "
            + username
            + ".";

        deleteModal.classList.add("show");

        setTimeout(function () {
            deleteAdminPassword.focus();
        }, 100);
    }

    function closeDeleteModal() {

        deleteModal.classList.remove("show");
        deleteAdminPassword.value = "";
    }

    deleteModal.addEventListener("click", function (event) {

        if (event.target === deleteModal) {
            closeDeleteModal();
        }
    });

    document.addEventListener("keydown", function (event) {

        if (event.key === "Escape") {
            closeDeleteModal();
        }
    });

</script>

</body>

</html>