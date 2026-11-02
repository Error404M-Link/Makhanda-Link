<?php

session_start();

require_once "../Error404Connection.php";


//check if administrator is logged in
if (!isset($_SESSION["username"])) {

    die("Administrator is not logged in.");
}


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


$adminUsername = $_SESSION["username"];

$message = "";
$messageType = "";


//==================================================
// GET ADMIN PROFILE
//==================================================

$adminData = [];
$profileImageUrl = "";

$stmt = $conn->prepare("
    SELECT
        u.first_Name,
        u.surname,
        u.username,
        p.image_path
    FROM `user` u
    LEFT JOIN profile_images p
        ON u.profile_id = p.profile_id
    WHERE u.username = ?
    LIMIT 1
");

if ($stmt) {

    $stmt->bind_param("s", $adminUsername);
    $stmt->execute();

    $result = $stmt->get_result();

    if ($result && $result->num_rows > 0) {

        $adminData = $result->fetch_assoc();

        $profileImageUrl = $adminData["image_path"] ?? "";
    }

    $stmt->close();
}


//==================================================
// UPDATE WARD
//==================================================

if (
    $_SERVER["REQUEST_METHOD"] === "POST"
    && isset($_POST["action"])
    && $_POST["action"] === "update_ward"
) {

    $wardId = (int)($_POST["ward_id"] ?? 0);
    $wardName = trim($_POST["ward_name"] ?? "");
    $adminPassword = $_POST["admin_password"] ?? "";


    if ($wardId <= 0 || $wardName === "") {

        $message = "Please enter a valid ward name.";
        $messageType = "error";

    } elseif ($adminPassword === "") {

        $message = "Administrator password is required.";
        $messageType = "error";

    } else {

        //verify administrator password
        $passwordStmt = $conn->prepare("
            SELECT password_hash
            FROM `user`
            WHERE username = ?
            LIMIT 1
        ");

        if ($passwordStmt) {

            $passwordStmt->bind_param(
                "s",
                $adminUsername
            );

            $passwordStmt->execute();

            $passwordResult = $passwordStmt->get_result();

            if (
                $passwordResult
                && $passwordResult->num_rows > 0
            ) {

                $passwordRow = $passwordResult->fetch_assoc();

                if (
                    password_verify(
                        $adminPassword,
                        $passwordRow["password_hash"]
                    )
                ) {

                    $updateStmt = $conn->prepare("
                        UPDATE ward
                        SET ward_name = ?
                        WHERE ward_id = ?
                    ");

                    if ($updateStmt) {

                        $updateStmt->bind_param(
                            "si",
                            $wardName,
                            $wardId
                        );

                        if ($updateStmt->execute()) {

                            $message = "Ward updated successfully.";
                            $messageType = "success";

                        } else {

                            $message = "Ward could not be updated.";
                            $messageType = "error";
                        }

                        $updateStmt->close();

                    } else {

                        $message = "Unable to prepare ward update.";
                        $messageType = "error";
                    }

                } else {

                    $message = "Incorrect administrator password.";
                    $messageType = "error";
                }

            } else {

                $message = "Administrator account could not be found.";
                $messageType = "error";
            }

            $passwordStmt->close();
        }
    }
}


//==================================================
// SEARCH / FILTER
//==================================================

$search = trim($_GET["search"] ?? "");


//==================================================
// GET WARDS
//==================================================

$wards = [];

$sql = "
    SELECT
        w.ward_id,
        w.ward_number,
        w.ward_name,

        wc.username AS councillor_username,

        u.first_Name AS councillor_first_name,
        u.surname AS councillor_surname,
        u.contact AS councillor_contact,
        u.active AS councillor_active,
        u.verified AS councillor_verified,

        (
            SELECT COUNT(*)
            FROM ward_summaries ws
            WHERE ws.ward_id = w.ward_id
            AND ws.deleted = 0
        ) AS summary_count

    FROM ward w

    LEFT JOIN ward_councillor wc
        ON wc.ward_id = w.ward_id

    LEFT JOIN `user` u
        ON u.username = wc.username
";


if ($search !== "") {

    $sql .= "
        WHERE
            CAST(w.ward_number AS CHAR) LIKE ?
            OR w.ward_name LIKE ?
            OR wc.username LIKE ?
            OR u.first_Name LIKE ?
            OR u.surname LIKE ?
    ";
}


$sql .= "
    ORDER BY w.ward_number ASC
";


$stmt = $conn->prepare($sql);


if ($stmt) {

    if ($search !== "") {

        $searchValue = "%" . $search . "%";

        $stmt->bind_param(
            "sssss",
            $searchValue,
            $searchValue,
            $searchValue,
            $searchValue,
            $searchValue
        );
    }


    $stmt->execute();

    $result = $stmt->get_result();


    while ($row = $result->fetch_assoc()) {

        $wards[] = $row;
    }


    $stmt->close();
}


//==================================================
// STATISTICS
//==================================================

$totalWards = count($wards);

$occupiedWards = 0;
$vacantWards = 0;
$totalSummaries = 0;


foreach ($wards as $ward) {

    if (!empty($ward["councillor_username"])) {

        $occupiedWards++;

    } else {

        $vacantWards++;
    }

    $totalSummaries += (int)$ward["summary_count"];
}


?>

<!DOCTYPE html>

<html lang="en">

<head>

    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0"
    >

    <title>Manage Wards | M-LINK</title>

    <style>

        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }

        body {
            font-family: Arial, sans-serif;
            background: #f4f0e7;
            color: #263238;
        }

        .page-container {
            min-height: 100vh;
        }

        /* =========================================
           TOP BAR
        ========================================= */

        .topbar {
            height: 70px;
            background: white;
            border-bottom: 1px solid #ddd;
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 0 30px;
            position: sticky;
            top: 0;
            z-index: 100;
        }

        .topbar-left {
            display: flex;
            align-items: center;
            gap: 18px;
        }

        .back-button {
            text-decoration: none;
            color: #4b561b;
            font-weight: bold;
            font-size: 14px;
        }

        .topbar-title {
            font-size: 20px;
            font-weight: 700;
            color: #263238;
        }

        .admin-info {
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .admin-avatar {
            width: 38px;
            height: 38px;
            border-radius: 50%;
            background: #4b561b;
            color: white;
            display: flex;
            align-items: center;
            justify-content: center;
            overflow: hidden;
            font-weight: bold;
        }

        .admin-avatar img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        .admin-name {
            font-size: 14px;
            font-weight: 600;
        }

        /* 
           MAIN
         */

        .main-content {
            padding: 35px;
            max-width: 1500px;
            margin: auto;
        }

        .page-heading {
            text-align: center;
            margin-bottom: 30px;
        }

        .page-heading h1 {
            color: #4b561b;
            font-size: 30px;
            margin-bottom: 6px;
        }

        /* =========================================
           MESSAGE
        ========================================= */

        .message {
            padding: 13px 18px;
            border-radius: 7px;
            margin-bottom: 20px;
            font-size: 14px;
        }

        .message.success {
            background: #e8f1df;
            color: #34420f;
            border: 1px solid #bdcc9e;
        }

        .message.error {
            background: #fce8e8;
            color: #8b1f1f;
            border: 1px solid #efbaba;
        }

        /* =========================================
           STATS
        ========================================= */

        .stats-grid {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 18px;
            margin-bottom: 30px;
        }

        .stat-card {
            background: white;
            border-radius: 10px;
            padding: 22px;
            border: 1px solid #e2ded4;
        }

        .stat-card h3 {
            font-size: 13px;
            color: #777;
            margin-bottom: 10px;
            text-transform: uppercase;
        }

        .stat-card .number {
            font-size: 28px;
            font-weight: bold;
            color: #4b561b;
        }

        /* =========================================
           TOOLBAR
        ========================================= */

        .toolbar {
            background: white;
            padding: 18px;
            border-radius: 10px;
            border: 1px solid #e2ded4;
            margin-bottom: 25px;
            display: flex;
            gap: 12px;
            align-items: center;
            justify-content: space-between;
        }

        .search-form {
            display: flex;
            gap: 10px;
            width: 100%;
            max-width: 550px;
        }

        .search-form input {
            flex: 1;
            padding: 11px 13px;
            border: 1px solid #ccc;
            border-radius: 6px;
            outline: none;
        }

        .search-form input:focus {
            border-color: #4b561b;
        }

        .btn {
            display: inline-block;
            border: none;
            border-radius: 6px;
            padding: 10px 15px;
            cursor: pointer;
            text-decoration: none;
            font-size: 13px;
            font-weight: 600;
            text-align: center;
        }

        .btn-primary {
            background: #4b561b;
            color: white;
        }

        .btn-primary:hover {
            background: #394313;
        }

        .btn-light {
            background: #eeeae1;
            color: #263238;
        }

        .btn-outline {
            background: white;
            border: 1px solid #4b561b;
            color: #4b561b;
        }

        /* =========================================
           WARD CARDS
        ========================================= */

        .wards-grid {
            display: grid;
            grid-template-columns:
                repeat(auto-fit, minmax(320px, 1fr));
            gap: 20px;
        }

        .ward-card {
            background: white;
            border-radius: 10px;
            border: 1px solid #dedad0;
            overflow: hidden;
        }

        .ward-card-header {
            background: #4b561b;
            color: white;
            padding: 18px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .ward-number {
            font-size: 20px;
            font-weight: bold;
        }

        .ward-status {
            font-size: 11px;
            padding: 5px 9px;
            border-radius: 20px;
            background: rgba(255,255,255,.18);
        }

        .ward-body {
            padding: 20px;
        }

        .ward-name {
            font-size: 18px;
            font-weight: bold;
            margin-bottom: 20px;
        }

        .info-row {
            margin-bottom: 13px;
        }

        .info-label {
            font-size: 11px;
            text-transform: uppercase;
            color: #888;
            margin-bottom: 4px;
            font-weight: bold;
        }

        .info-value {
            font-size: 14px;
        }

        .vacant {
            color: #a14444;
            font-weight: bold;
        }

        .active {
            color: #4b561b;
            font-weight: bold;
        }

        .ward-actions {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 8px;
            margin-top: 20px;
            padding-top: 15px;
            border-top: 1px solid #eee;
        }

        /* =========================================
           MODAL
        ========================================= */

        .modal {
            display: none;
            position: fixed;
            inset: 0;
            background: rgba(0,0,0,.55);
            align-items: center;
            justify-content: center;
            z-index: 1000;
            padding: 20px;
        }

        .modal.show {
            display: flex;
        }

        .modal-content {
            width: 100%;
            max-width: 450px;
            background: white;
            border-radius: 12px;
            padding: 25px;
        }

        .modal-content h2 {
            color: #4b561b;
            margin-bottom: 20px;
        }

        .form-group {
            margin-bottom: 17px;
        }

        .form-group label {
            display: block;
            margin-bottom: 7px;
            font-size: 13px;
            font-weight: bold;
        }

        .form-group input {
            width: 100%;
            padding: 11px;
            border: 1px solid #ccc;
            border-radius: 6px;
            outline: none;
        }

        .modal-actions {
            display: flex;
            justify-content: flex-end;
            gap: 10px;
            margin-top: 22px;
        }

        .empty-state {
            grid-column: 1 / -1;
            background: white;
            padding: 50px;
            text-align: center;
            border-radius: 10px;
            border: 1px solid #ddd;
            color: #777;
        }

        @media (max-width: 900px) {

            .stats-grid {
                grid-template-columns: repeat(2, 1fr);
            }

            .toolbar {
                flex-direction: column;
                align-items: stretch;
            }

            .search-form {
                max-width: none;
            }
        }

        @media (max-width: 550px) {

            .stats-grid {
                grid-template-columns: 1fr;
            }

            .main-content {
                padding: 20px 15px;
            }

            .admin-name {
                display: none;
            }

            .ward-actions {
                grid-template-columns: 1fr;
            }
        }

    </style>

</head>

<body>

<div class="page-container">


    <!-- 
         TOP BAR
     -->

    <header class="topbar">

        <div class="topbar-left">

            <a
                href="admindashboard.php"
                class="back-button"
            >
                ← Back
            </a>

            <div class="topbar-title">
                Ward Management
            </div>

        </div>


        <div class="admin-info">

            <div class="admin-avatar">

                <?php if (!empty($profileImageUrl)): ?>

                    <img
                        src="<?= htmlspecialchars($profileImageUrl) ?>"
                        alt="Admin"
                    >

                <?php else: ?>

                    <?= htmlspecialchars(
                        strtoupper(
                            substr(
                                $adminData["first_Name"] ?? "A",
                                0,
                                1
                            )
                        )
                    ) ?>

                <?php endif; ?>

            </div>

            <div class="admin-name">

                <?= htmlspecialchars(
                    ($adminData["first_Name"] ?? "Admin")
                    . " "
                    . ($adminData["surname"] ?? "")
                ) ?>

            </div>

        </div>

    </header>


    <!-- 
         MAIN CONTENT

    <main class="main-content">


        <div class="page-heading">

            <h1>Manage Wards</h1>

        </div>


        <?php if ($message !== ""): ?>

            <div
                class="message <?= htmlspecialchars($messageType) ?>"
            >
                <?= htmlspecialchars($message) ?>
            </div>

        <?php endif; ?>


        <!-- 
             STATISTICS
         -->

        <div class="stats-grid">

            <div class="stat-card">

                <h3>Total Wards</h3>

                <div class="number">
                    <?= $totalWards ?>
                </div>

            </div>


            <div class="stat-card">

                <h3>Assigned Councillors</h3>

                <div class="number">
                    <?= $occupiedWards ?>
                </div>

            </div>


            <div class="stat-card">

                <h3>Vacant Wards</h3>

                <div class="number">
                    <?= $vacantWards ?>
                </div>

            </div>


            <div class="stat-card">

                <h3>Ward Summaries</h3>

                <div class="number">
                    <?= $totalSummaries ?>
                </div>

            </div>

        </div>


        <!-- 
             SEARCH
         -->

        <div class="toolbar">

            <form
                method="GET"
                class="search-form"
            >

                <input
                    type="text"
                    name="search"
                    placeholder="Search ward or councillor..."
                    value="<?= htmlspecialchars($search) ?>"
                >

                <button
                    type="submit"
                    class="btn btn-primary"
                >
                    Search
                </button>

                <?php if ($search !== ""): ?>

                    <a
                        href="manageWards.php"
                        class="btn btn-light"
                    >
                        Clear
                    </a>

                <?php endif; ?>

            </form>


            <a
                href="WardCouncillor.php"
                class="btn btn-primary"
            >
                Manage Councillors
            </a>

        </div>


        <!-- 
             WARDS
         -->

        <div class="wards-grid">


            <?php if (empty($wards)): ?>

                <div class="empty-state">

                    No wards were found.

                </div>

            <?php else: ?>


                <?php foreach ($wards as $ward): ?>


                    <?php

                    $hasCouncillor =
                        !empty($ward["councillor_username"]);

                    $councillorName = "";

                    if ($hasCouncillor) {

                        $councillorName = trim(
                            ($ward["councillor_first_name"] ?? "")
                            . " "
                            . ($ward["councillor_surname"] ?? "")
                        );
                    }

                    ?>


                    <div class="ward-card">


                        <div class="ward-card-header">

                            <div class="ward-number">

                                Ward
                                <?= htmlspecialchars(
                                    $ward["ward_number"]
                                ) ?>

                            </div>


                            <div class="ward-status">

                                <?= $hasCouncillor
                                    ? "Councillor Assigned"
                                    : "Vacant"
                                ?>

                            </div>

                        </div>


                        <div class="ward-body">


                            <div class="ward-name">

                                <?= htmlspecialchars(
                                    $ward["ward_name"]
                                    ?: "Ward "
                                    . $ward["ward_number"]
                                ) ?>

                            </div>


                            <div class="info-row">

                                <div class="info-label">
                                    Ward Councillor
                                </div>


                                <div
                                    class="info-value
                                    <?= $hasCouncillor
                                        ? "active"
                                        : "vacant"
                                    ?>"
                                >

                                    <?php if ($hasCouncillor): ?>

                                        <?= htmlspecialchars(
                                            $councillorName !== ""
                                                ? $councillorName
                                                : $ward[
                                                    "councillor_username"
                                                ]
                                        ) ?>

                                    <?php else: ?>

                                        No councillor assigned

                                    <?php endif; ?>

                                </div>

                            </div>


                            <?php if ($hasCouncillor): ?>

                                <div class="info-row">

                                    <div class="info-label">
                                        Email
                                    </div>

                                    <div class="info-value">

                                        <?= htmlspecialchars(
                                            $ward[
                                                "councillor_username"
                                            ]
                                        ) ?>

                                    </div>

                                </div>


                                <div class="info-row">

                                    <div class="info-label">
                                        Account Status
                                    </div>

                                    <div class="info-value">

                                        <?php

                                        if (
                                            (int)$ward[
                                                "councillor_verified"
                                            ] === 1
                                            &&
                                            (int)$ward[
                                                "councillor_active"
                                            ] === 1
                                        ) {

                                            echo "Active";

                                        } else {

                                            echo "Inactive";
                                        }

                                        ?>

                                    </div>

                                </div>

                            <?php endif; ?>


                            <div class="info-row">

                                <div class="info-label">
                                    Ward Summaries
                                </div>

                                <div class="info-value">

                                    <?= (int)$ward["summary_count"] ?>

                                </div>

                            </div>


                            <!-- ACTIONS -->

                            <div class="ward-actions">


                                <button
                                    type="button"
                                    class="btn btn-outline"
                                    onclick='openEditModal(
                                        <?= (int)$ward["ward_id"] ?>,
                                        <?= json_encode(
                                            $ward["ward_name"]
                                        ) ?>
                                    )'
                                >
                                    Edit Ward
                                </button>


                                <a
                                    href="WardCouncillor.php?ward_id=
                                    <?= (int)$ward["ward_id"] ?>"
                                    class="btn btn-outline"
                                >
                                    Councillor
                                </a>


                                <a
                                    href="wardSummaries.php?ward_id=
                                    <?= (int)$ward["ward_id"] ?>"
                                    class="btn btn-outline"
                                >
                                    Summaries
                                </a>


                                <a
                                    href="viewWard.php?ward_id=
                                    <?= (int)$ward["ward_id"] ?>"
                                    class="btn btn-primary"
                                >
                                    View Ward
                                </a>


                            </div>

                        </div>

                    </div>


                <?php endforeach; ?>


            <?php endif; ?>


        </div>

    </main>

</div>


<!-- 
     EDIT WARD MODAL
 -->

<div
    class="modal"
    id="editWardModal"
>

    <div class="modal-content">


        <h2>Edit Ward</h2>


        <form
            method="POST"
            id="editWardForm"
        >

            <input
                type="hidden"
                name="action"
                value="update_ward"
            >

            <input
                type="hidden"
                name="ward_id"
                id="editWardId"
            >


            <div class="form-group">

                <label for="editWardName">
                    Ward Name
                </label>

                <input
                    type="text"
                    name="ward_name"
                    id="editWardName"
                    required
                >

            </div>


            <div class="form-group">

                <label for="adminPassword">
                    Administrator Password
                </label>

                <input
                    type="password"
                    name="admin_password"
                    id="adminPassword"
                    placeholder="Enter your password"
                    required
                >

            </div>


            <div class="modal-actions">

                <button
                    type="button"
                    class="btn btn-light"
                    onclick="closeEditModal()"
                >
                    Cancel
                </button>


                <button
                    type="submit"
                    class="btn btn-primary"
                >
                    Save Changes
                </button>

            </div>

        </form>

    </div>

</div>


<script>

function openEditModal(
    wardId,
    wardName
) {

    document.getElementById(
        "editWardId"
    ).value = wardId;


    document.getElementById(
        "editWardName"
    ).value = wardName || "";


    document.getElementById(
        "adminPassword"
    ).value = "";


    document.getElementById(
        "editWardModal"
    ).classList.add("show");
}


function closeEditModal() {

    document.getElementById(
        "editWardModal"
    ).classList.remove("show");
}


document.getElementById(
    "editWardModal"
).addEventListener(
    "click",
    function(event) {

        if (event.target === this) {

            closeEditModal();
        }
    }
);

</script>

</body>

</html>