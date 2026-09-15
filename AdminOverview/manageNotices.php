<?php

session_start();

require_once "Error404Connection.php";

if (!isset($_SESSION["username"])) {
    die("Administrator is not logged in.");
}

if (
    !isset($_SESSION["user_role"]) ||
    strtolower($_SESSION["user_role"]) !== "admin"
) {
    die("Access denied.");
}

$adminUsername = $_SESSION["username"];

$message = "";
$messageType = "";

$wards = [];

$wardResult = $conn->query("
    SELECT ward_id, ward_number, ward_name
    FROM ward
    ORDER BY ward_number ASC
");

if ($wardResult) {

    while ($wardRow = $wardResult->fetch_assoc()) {

        $wards[] = $wardRow;

    }

}


$services = [];

$serviceResult = $conn->query("
    SELECT DISTINCT service_type
    FROM municipal_service
    WHERE service_type IS NOT NULL
    AND service_type <> ''
    ORDER BY service_type ASC
");

if ($serviceResult) {

    while ($serviceRow = $serviceResult->fetch_assoc()) {

        $services[] = $serviceRow["service_type"];

    }

}


if (isset($_POST["verify_action_password"])) {

    $action = $_POST["action_type"] ?? "";
    $noticeId = intval($_POST["notice_id"] ?? 0);
    $adminPassword = $_POST["admin_password"] ?? "";

    if (
        $noticeId <= 0 ||
        $adminPassword === "" ||
        !in_array($action, ["edit", "approve", "delete"], true)
    ) {

        $message = "Please enter your administrator password.";
        $messageType = "error";

    } else {

        $sql = "
            SELECT password_hashed
            FROM admin
            WHERE username = ?
        ";

        $stmt = $conn->prepare($sql);

        if ($stmt) {

            $stmt->bind_param("s", $adminUsername);

            $stmt->execute();

            $passwordResult = $stmt->get_result();

            if ($passwordResult->num_rows === 1) {

                $admin = $passwordResult->fetch_assoc();

                if (
                    password_verify(
                        $adminPassword,
                        $admin["password_hashed"]
                    )
                ) {

                    if ($action === "edit") {

                        $_SESSION["notice_edit_verified"] = true;
                        $_SESSION["notice_edit_id"] = $noticeId;
                        $_SESSION["notice_edit_time"] = time();

                        header(
                            "Location: manageNotices.php?edit=" .
                            $noticeId
                        );

                        exit();

                    }


                    if ($action === "approve") {

                        $sql = "
                            UPDATE notices
                            SET status = 'Approved'
                            WHERE notice_id = ?
                        ";

                        $actionStmt = $conn->prepare($sql);

                        if ($actionStmt) {

                            $actionStmt->bind_param(
                                "i",
                                $noticeId
                            );

                            if ($actionStmt->execute()) {

                                $message =
                                    "Notice approved successfully.";

                                $messageType = "success";

                            } else {

                                $message =
                                    "Unable to approve the notice.";

                                $messageType = "error";

                            }

                            $actionStmt->close();

                        } else {

                            $message =
                                "Unable to prepare the approval.";

                            $messageType = "error";

                        }

                    }


                    if ($action === "delete") {

                        $sql = "
                            DELETE FROM notices
                            WHERE notice_id = ?
                        ";

                        $actionStmt = $conn->prepare($sql);

                        if ($actionStmt) {

                            $actionStmt->bind_param(
                                "i",
                                $noticeId
                            );

                            if ($actionStmt->execute()) {

                                $message =
                                    "Notice deleted successfully.";

                                $messageType = "success";

                            } else {

                                $message =
                                    "Unable to delete the notice.";

                                $messageType = "error";

                            }

                            $actionStmt->close();

                        } else {

                            $message =
                                "Unable to prepare the delete.";

                            $messageType = "error";

                        }

                    }

                } else {

                    $message =
                        "Incorrect administrator password.";

                    $messageType = "error";

                }

            } else {

                $message =
                    "Administrator account could not be found.";

                $messageType = "error";

            }

            $stmt->close();

        } else {

            $message =
                "Unable to verify the administrator password.";

            $messageType = "error";

        }

    }

}


$editVerified = false;

if (
    isset($_SESSION["notice_edit_verified"]) &&
    isset($_SESSION["notice_edit_id"]) &&
    isset($_SESSION["notice_edit_time"])
) {

    if (
        time() - $_SESSION["notice_edit_time"] <= 300
    ) {

        $editVerified = true;

    } else {

        unset(
            $_SESSION["notice_edit_verified"],
            $_SESSION["notice_edit_id"],
            $_SESSION["notice_edit_time"]
        );

    }

}


if (isset($_POST["edit_notice"])) {

    $noticeId = intval(
        $_POST["notice_id"] ?? 0
    );

    if (
        !$editVerified ||
        !isset($_SESSION["notice_edit_id"]) ||
        intval($_SESSION["notice_edit_id"]) !== $noticeId
    ) {

        $message =
            "Administrator password verification is required.";

        $messageType = "error";

    } else {

        $noticeTitle =
            trim($_POST["notice_title"] ?? "");

        $noticeCategory =
            trim($_POST["notice_category"] ?? "");

        $noticeDescription =
            trim($_POST["notice_description"] ?? "");

        $ward =
            $_POST["ward"] ?? "Community-wide";

        $publishDate =
            $_POST["publish_date"] ?? "";

        $expiryDate =
            $_POST["expiry_date"] ?? "";


        $validWards = [
            "Community-wide"
        ];

        foreach ($wards as $wardRow) {

            $wardLabel =
                "Ward " . $wardRow["ward_number"];

            $validWards[] = $wardLabel;

        }


        if (
            $noticeId <= 0 ||
            $noticeTitle === "" ||
            $noticeCategory === "" ||
            $noticeDescription === "" ||
            $publishDate === "" ||
            $expiryDate === ""
        ) {

            $message =
                "Please complete all required fields.";

            $messageType = "error";

        } elseif (
            !in_array(
                $noticeCategory,
                $services,
                true
            )
        ) {

            $message =
                "Please select a valid service.";

            $messageType = "error";

        } elseif (
            !in_array(
                $ward,
                $validWards,
                true
            )
        ) {

            $message =
                "Please select a valid ward.";

            $messageType = "error";

        } elseif (
            strtotime($expiryDate) <=
            strtotime($publishDate)
        ) {

            $message =
                "The expiry date must be after the publish date.";

            $messageType = "error";

        } else {

            if ($ward === "Community-wide") {

                $wardValue = null;

            } else {

                $wardValue = $ward;

            }


            $sql = "
                UPDATE notices
                SET ward = ?,
                    notice_title = ?,
                    notice_category = ?,
                    publish_date = ?,
                    expiry_date = ?,
                    notice_description = ?
                WHERE notice_id = ?
            ";

            $stmt = $conn->prepare($sql);

            if ($stmt) {

                $stmt->bind_param(
                    "ssssssi",
                    $wardValue,
                    $noticeTitle,
                    $noticeCategory,
                    $publishDate,
                    $expiryDate,
                    $noticeDescription,
                    $noticeId
                );

                if ($stmt->execute()) {

                    $message =
                        "Notice updated successfully.";

                    $messageType = "success";

                    unset(
                        $_SESSION["notice_edit_verified"],
                        $_SESSION["notice_edit_id"],
                        $_SESSION["notice_edit_time"]
                    );

                } else {

                    $message =
                        "Unable to update the notice.";

                    $messageType = "error";

                }

                $stmt->close();

            } else {

                $message =
                    "Unable to prepare the update.";

                $messageType = "error";

            }

        }

    }

}


$editNotice = null;

if (isset($_GET["edit"])) {

    $editId = intval($_GET["edit"]);

    if (
        !$editVerified ||
        !isset($_SESSION["notice_edit_id"]) ||
        intval($_SESSION["notice_edit_id"]) !== $editId
    ) {

        $editId = 0;

    }


    if ($editId > 0) {

        $sql = "
            SELECT
                notice_id,
                username,
                ward,
                notice_title,
                notice_category,
                publish_date,
                expiry_date,
                status,
                notice_description,
                date_posted
            FROM notices
            WHERE notice_id = ?
        ";

        $stmt = $conn->prepare($sql);

        if ($stmt) {

            $stmt->bind_param(
                "i",
                $editId
            );

            $stmt->execute();

            $editResult =
                $stmt->get_result();

            if (
                $editResult->num_rows > 0
            ) {

                $editNotice =
                    $editResult->fetch_assoc();

            }

            $stmt->close();

        }

    }

}


$sql = "
    SELECT
        notice_id,
        username,
        ward,
        notice_title,
        notice_category,
        publish_date,
        expiry_date,
        status,
        notice_description,
        date_posted
    FROM notices
    ORDER BY date_posted DESC
";

$result = $conn->query($sql);

?>

<!DOCTYPE html>

<html lang="en">

<head>

    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0"
    >

    <title>M-LINK | Manage Notices</title>


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


        .page {
            width: 100%;
            min-height: 100vh;
        }


        .topbar {
            position: relative;
            min-height: 76px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 0 35px;
        }


        .topbar-left {
            display: flex;
            align-items: center;
            z-index: 2;
        }


        .back-dashboard {
            min-height: 40px;
            padding: 0 16px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 7px;
            background: #5f7022;
            color: white;
            border-radius: 5px;
            text-decoration: none;
            font-size: 13px;
            font-weight: 600;
            white-space: nowrap;
        }


        .back-dashboard:hover {
            background: #4b591a;
        }


        .topbar-title {
            position: absolute;
            left: 50%;
            transform: translateX(-50%);
        }


        .topbar-title h1 {
            font-size: 26px;
            color: #263238;
            font-weight: 700;
            white-space: nowrap;
        }


        .user {
            font-size: 14px;
            font-weight: 600;
            color: #4b561b;
            z-index: 2;
        }


        .container {
            width: calc(100% - 70px);
            margin: 20px auto 35px;
        }


        .message {
            padding: 13px 16px;
            margin-bottom: 20px;
            border-radius: 5px;
            font-size: 14px;
        }


        .message.success {
            background: #edf3df;
            color: #4b561b;
            border: 1px solid #c8d5a8;
        }


        .message.error {
            background: #f1dfda;
            color: #a22d1d;
            border: 1px solid #e2c1ba;
        }


        .form-container {
            background: white;
            padding: 25px;
            border: 1px solid #ddd7ca;
            border-radius: 7px;
            margin-bottom: 25px;
        }


        .form-container h3 {
            color: #333;
            font-size: 18px;
            margin-bottom: 22px;
        }


        .form-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 18px;
        }


        .form-group {
            display: flex;
            flex-direction: column;
        }


        .form-group.full {
            grid-column: 1 / -1;
        }


        .form-group label {
            margin-bottom: 7px;
            font-size: 13px;
            font-weight: 600;
            color: #444;
        }


        .form-group input,
        .form-group select,
        .form-group textarea {
            width: 100%;
            padding: 11px 12px;
            border: 1px solid #d7dbe0;
            border-radius: 5px;
            background: #f7f8fa;
            font-family: Arial, Helvetica, sans-serif;
            font-size: 13px;
            color: #333;
            outline: none;
        }


        .form-group input:focus,
        .form-group select:focus,
        .form-group textarea:focus {
            border-color: #68772a;
            background: white;
        }


        .form-group textarea {
            min-height: 130px;
            resize: vertical;
        }


        .form-buttons {
            margin-top: 20px;
            display: flex;
            gap: 10px;
        }


        .button {
            min-height: 38px;
            padding: 0 16px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            text-decoration: none;
            font-family: Arial, Helvetica, sans-serif;
            font-size: 13px;
            font-weight: 600;
            display: inline-flex;
            align-items: center;
            justify-content: center;
        }


        .button-primary {
            background: #5f7022;
            color: white;
        }


        .button-primary:hover {
            background: #4b591a;
        }


        .button-secondary {
            background: white;
            color: #555;
            border: 1px solid #d1cbc0;
        }


        .button-secondary:hover {
            background: #f4f1eb;
        }


        .button-approve {
            background: #5f7022;
            color: white;
        }


        .button-approve:hover {
            background: #4b591a;
        }


        .button-edit {
            background: #6d7780;
            color: white;
        }


        .button-edit:hover {
            background: #59636b;
        }


        .button-delete {
            background: #f1dfda;
            color: #a22d1d;
        }


        .button-delete:hover {
            background: #e9ccc5;
        }


        .table-container {
            background: white;
            border: 1px solid #ddd7ca;
            border-radius: 7px;
            overflow-x: auto;
        }


        .table-container h3 {
            color: #333;
            font-size: 18px;
            padding: 22px 25px;
            border-bottom: 1px solid #e5e0d7;
        }


        table {
            width: 100%;
            border-collapse: collapse;
            min-width: 1100px;
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
            border-bottom: 1px solid #ebe7df;
            vertical-align: top;
            font-size: 13px;
            color: #333;
        }


        tbody tr:hover {
            background: #faf9f5;
        }


        tbody tr:last-child td {
            border-bottom: none;
        }


        .status {
            display: inline-block;
            padding: 5px 10px;
            border-radius: 4px;
            font-size: 11px;
            font-weight: 600;
        }


        .status-draft {
            background: #eeeae1;
            color: #666;
        }


        .status-approved {
            background: #e1ecd2;
            color: #4b681c;
        }


        .action-buttons {
            display: flex;
            gap: 6px;
            flex-wrap: wrap;
        }


        .action-buttons .button {
            min-height: 32px;
            padding: 0 11px;
            font-size: 12px;
        }


        .description {
            max-width: 250px;
            line-height: 1.5;
        }


        .no-notices {
            text-align: center;
            padding: 50px 25px;
            color: #777;
            font-size: 13px;
        }


        .password-modal {
            display: none;
            position: fixed;
            z-index: 9999;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            background: rgba(38, 50, 56, 0.65);
            align-items: center;
            justify-content: center;
            padding: 20px;
        }


        .password-modal-content {
            background: #f4f0e7;
            width: 460px;
            max-width: 100%;
            padding: 25px;
            border-radius: 10px;
            box-shadow: 0 20px 50px rgba(0, 0, 0, 0.35);
        }


        .password-modal-content h3 {
            color: #4b561b;
            margin-bottom: 10px;
            font-size: 20px;
        }


        .password-modal-content p {
            color: #666;
            margin-bottom: 18px;
            line-height: 1.5;
            font-size: 14px;
        }


        .password-modal-content label {
            display: block;
            font-weight: 600;
            margin-bottom: 7px;
            font-size: 13px;
        }


        .password-input-container {
            position: relative;
        }


        .password-input-container input {
            width: 100%;
            height: 44px;
            padding: 0 75px 0 12px;
            border: 1px solid #b9bec1;
            border-radius: 5px;
            font-family: Arial, Helvetica, sans-serif;
            font-size: 14px;
            outline: none;
        }


        .password-input-container input:focus {
            border-color: #4b561b;
        }


        .show-password {
            position: absolute;
            right: 12px;
            top: 50%;
            transform: translateY(-50%);
            border: none;
            background: none;
            cursor: pointer;
            color: #4b561b;
            font-size: 12px;
            font-weight: 600;
        }


        .password-modal-buttons {
            display: flex;
            justify-content: flex-end;
            gap: 10px;
            margin-top: 20px;
        }


        .password-error {
            display: none;
            color: #a22d1d;
            font-size: 13px;
            margin-top: 8px;
        }


        @media (max-width: 800px) {

            .form-grid {
                grid-template-columns: 1fr;
            }


            .form-group.full {
                grid-column: auto;
            }


            .container {
                width: calc(100% - 36px);
            }


            .topbar {
                min-height: 130px;
                padding: 15px 18px;
                align-items: flex-end;
            }


            .topbar-title {
                top: 20px;
            }

        }


        @media (max-width: 550px) {

            .topbar {
                min-height: 145px;
            }


            .user {
                max-width: 160px;
                text-align: right;
                word-break: break-word;
            }


            .form-container {
                padding: 18px;
            }


            .password-modal-buttons {
                flex-direction: column-reverse;
            }


            .password-modal-buttons .button {
                width: 100%;
            }

        }

    </style>

</head>


<body>


<div class="page">


    <header class="topbar">


        <div class="topbar-left">

            <a
                href="admindashboard.php"
                class="back-dashboard"
            >
                ← Back to Dashboard
            </a>

        </div>


        <div class="topbar-title">

            <h1>
                Manage Notices
            </h1>

        </div>


        <div class="user">

            <?= htmlspecialchars(
                $adminUsername,
                ENT_QUOTES,
                "UTF-8"
            ) ?>

        </div>


    </header>



    <div class="container">


        <?php if ($message !== ""): ?>


            <div class="message <?= htmlspecialchars($messageType) ?>">

                <?= htmlspecialchars($message) ?>

            </div>


        <?php endif; ?>



        <div class="form-container">


            <?php if ($editNotice !== null): ?>


                <h3>
                    Edit Notice
                </h3>


                <form
                    method="POST"
                    action="manageNotices.php"
                >


                    <input
                        type="hidden"
                        name="notice_id"
                        value="<?= $editNotice["notice_id"] ?>"
                    >


                    <div class="form-grid">


                        <div class="form-group">

                            <label for="notice_title">
                                Notice Title
                            </label>

                            <input
                                type="text"
                                id="notice_title"
                                name="notice_title"
                                maxlength="255"
                                required
                                value="<?= htmlspecialchars(
                                    $editNotice["notice_title"]
                                ) ?>"
                            >

                        </div>



                        <div class="form-group">

                            <label for="notice_category">
                                Service
                            </label>

                            <select
                                id="notice_category"
                                name="notice_category"
                                required
                            >

                                <option
                                    value=""
                                    disabled
                                >
                                    Select Service
                                </option>


                                <?php foreach ($services as $service): ?>


                                    <option
                                        value="<?= htmlspecialchars($service) ?>"
                                        <?= (
                                            $editNotice["notice_category"] ===
                                            $service
                                        ) ? "selected" : "" ?>
                                    >
                                        <?= htmlspecialchars($service) ?>
                                    </option>


                                <?php endforeach; ?>


                            </select>

                        </div>



                        <div class="form-group">

                            <label for="ward">
                                Notice Area
                            </label>


                            <?php

                            $currentWard =
                                $editNotice["ward"];

                            if (
                                $currentWard === null ||
                                $currentWard === ""
                            ) {

                                $currentWard =
                                    "Community-wide";

                            }

                            ?>


                            <select
                                id="ward"
                                name="ward"
                                required
                            >


                                <option
                                    value="Community-wide"
                                    <?= (
                                        $currentWard ===
                                        "Community-wide"
                                    ) ? "selected" : "" ?>
                                >
                                    Community-wide
                                </option>


                                <?php foreach ($wards as $wardRow): ?>


                                    <?php

                                    $wardLabel =
                                        "Ward " .
                                        $wardRow["ward_number"];

                                    ?>


                                    <option
                                        value="<?= htmlspecialchars(
                                            $wardLabel
                                        ) ?>"
                                        <?= (
                                            $currentWard ===
                                            $wardLabel
                                        ) ? "selected" : "" ?>
                                    >
                                        <?= htmlspecialchars(
                                            $wardLabel
                                        ) ?>
                                    </option>


                                <?php endforeach; ?>


                            </select>

                        </div>



                        <div class="form-group">

                            <label for="publish_date">
                                Publish Date
                            </label>

                            <input
                                type="datetime-local"
                                id="publish_date"
                                name="publish_date"
                                required
                                value="<?= date(
                                    "Y-m-d\TH:i",
                                    strtotime(
                                        $editNotice["publish_date"]
                                    )
                                ) ?>"
                            >

                        </div>



                        <div class="form-group">

                            <label for="expiry_date">
                                Expiry Date
                            </label>

                            <input
                                type="datetime-local"
                                id="expiry_date"
                                name="expiry_date"
                                required
                                value="<?= date(
                                    "Y-m-d\TH:i",
                                    strtotime(
                                        $editNotice["expiry_date"]
                                    )
                                ) ?>"
                            >

                        </div>



                        <div class="form-group full">

                            <label for="notice_description">
                                Notice Description
                            </label>

                            <textarea
                                id="notice_description"
                                name="notice_description"
                                required
                            ><?= htmlspecialchars(
                                $editNotice["notice_description"]
                            ) ?></textarea>

                        </div>


                    </div>



                    <div class="form-buttons">


                        <button
                            type="submit"
                            name="edit_notice"
                            class="button button-primary"
                        >
                            Save Changes
                        </button>


                        <a
                            href="manageNotices.php"
                            class="button button-secondary"
                        >
                            Cancel
                        </a>


                    </div>


                </form>


            <?php else: ?>


                <h3>
                    Add New Notice
                </h3>


                <form
                    method="POST"
                    action="manageNotices.php"
                >


                    <div class="form-grid">


                        <div class="form-group">

                            <label for="notice_title">
                                Notice Title
                            </label>

                            <input
                                type="text"
                                id="notice_title"
                                name="notice_title"
                                maxlength="255"
                                required
                            >

                        </div>



                        <div class="form-group">

                            <label for="notice_category">
                                Service
                            </label>

                            <select
                                id="notice_category"
                                name="notice_category"
                                required
                            >

                                <option
                                    value=""
                                    disabled
                                    selected
                                >
                                    Select Service
                                </option>


                                <?php foreach ($services as $service): ?>


                                    <option
                                        value="<?= htmlspecialchars($service) ?>"
                                    >
                                        <?= htmlspecialchars($service) ?>
                                    </option>


                                <?php endforeach; ?>


                            </select>

                        </div>



                        <div class="form-group">

                            <label for="ward">
                                Notice Area
                            </label>

                            <select
                                id="ward"
                                name="ward"
                                required
                            >

                                <option
                                    value="Community-wide"
                                    selected
                                >
                                    Community-wide
                                </option>


                                <?php foreach ($wards as $wardRow): ?>


                                    <?php

                                    $wardLabel =
                                        "Ward " .
                                        $wardRow["ward_number"];

                                    ?>


                                    <option
                                        value="<?= htmlspecialchars(
                                            $wardLabel
                                        ) ?>"
                                    >
                                        <?= htmlspecialchars(
                                            $wardLabel
                                        ) ?>
                                    </option>


                                <?php endforeach; ?>


                            </select>

                        </div>



                        <div class="form-group">

                            <label for="publish_date">
                                Publish Date
                            </label>

                            <input
                                type="datetime-local"
                                id="publish_date"
                                name="publish_date"
                                required
                            >

                        </div>



                        <div class="form-group">

                            <label for="expiry_date">
                                Expiry Date
                            </label>

                            <input
                                type="datetime-local"
                                id="expiry_date"
                                name="expiry_date"
                                required
                            >

                        </div>



                        <div class="form-group full">

                            <label for="notice_description">
                                Notice Description
                            </label>

                            <textarea
                                id="notice_description"
                                name="notice_description"
                                placeholder="Enter the full notice information..."
                                required
                            ></textarea>

                        </div>


                    </div>



                    <div class="form-buttons">

                        <button
                            type="submit"
                            name="add_notice"
                            class="button button-primary"
                        >
                            Add Notice
                        </button>

                    </div>


                </form>


            <?php endif; ?>


        </div>



        <div class="table-container">


            <h3>
                Existing Notices
            </h3>


            <?php if (
                $result &&
                $result->num_rows > 0
            ): ?>


                <table>


                    <thead>

                        <tr>

                            <th>ID</th>
                            <th>Title</th>
                            <th>Service</th>
                            <th>Area</th>
                            <th>Publish Date</th>
                            <th>Expiry Date</th>
                            <th>Status</th>
                            <th>Description</th>
                            <th>Posted By</th>
                            <th>Actions</th>

                        </tr>

                    </thead>


                    <tbody>


                        <?php while (
                            $notice =
                                $result->fetch_assoc()
                        ): ?>


                            <?php

                            $noticeStatus =
                                strtolower(
                                    trim(
                                        $notice["status"]
                                    )
                                );

                            ?>


                            <tr>


                                <td>
                                    <?= htmlspecialchars(
                                        $notice["notice_id"]
                                    ) ?>
                                </td>


                                <td>
                                    <?= htmlspecialchars(
                                        $notice["notice_title"]
                                    ) ?>
                                </td>


                                <td>
                                    <?= htmlspecialchars(
                                        $notice["notice_category"]
                                    ) ?>
                                </td>


                                <td>

                                    <?php

                                    if (
                                        $notice["ward"] === null ||
                                        $notice["ward"] === ""
                                    ) {

                                        echo "Community-wide";

                                    } else {

                                        echo htmlspecialchars(
                                            $notice["ward"]
                                        );

                                    }

                                    ?>

                                </td>


                                <td>
                                    <?= date(
                                        "d M Y H:i",
                                        strtotime(
                                            $notice["publish_date"]
                                        )
                                    ) ?>
                                </td>


                                <td>
                                    <?= date(
                                        "d M Y H:i",
                                        strtotime(
                                            $notice["expiry_date"]
                                        )
                                    ) ?>
                                </td>


                                <td>


                                    <?php if (
                                        $noticeStatus === "approved" ||
                                        $noticeStatus === "active"
                                    ): ?>


                                        <span
                                            class="status status-approved"
                                        >
                                            Approved
                                        </span>


                                    <?php else: ?>


                                        <span
                                            class="status status-draft"
                                        >
                                            Draft
                                        </span>


                                    <?php endif; ?>


                                </td>


                                <td>


                                    <div class="description">


                                        <?php

                                        $description =
                                            $notice[
                                                "notice_description"
                                            ];

                                        if (
                                            strlen($description) >
                                            150
                                        ) {

                                            echo htmlspecialchars(
                                                substr(
                                                    $description,
                                                    0,
                                                    150
                                                )
                                            ) . "...";

                                        } else {

                                            echo htmlspecialchars(
                                                $description
                                            );

                                        }

                                        ?>


                                    </div>


                                </td>


                                <td>
                                    <?= htmlspecialchars(
                                        $notice["username"]
                                    ) ?>
                                </td>


                                <td>


                                    <div class="action-buttons">


                                        <button
                                            type="button"
                                            class="button button-edit"
                                            onclick="openPasswordPopup(
                                                'edit',
                                                <?= (int)$notice["notice_id"] ?>
                                            )"
                                        >
                                            Edit
                                        </button>



                                        <?php if (
                                            $noticeStatus !== "approved" &&
                                            $noticeStatus !== "active"
                                        ): ?>


                                            <button
                                                type="button"
                                                class="button button-approve"
                                                onclick="openPasswordPopup(
                                                    'approve',
                                                    <?= (int)$notice["notice_id"] ?>
                                                )"
                                            >
                                                Approve
                                            </button>


                                        <?php endif; ?>



                                        <button
                                            type="button"
                                            class="button button-delete"
                                            onclick="openPasswordPopup(
                                                'delete',
                                                <?= (int)$notice["notice_id"] ?>
                                            )"
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


                <div class="no-notices">

                    <p>
                        No notices have been added yet.
                    </p>

                </div>


            <?php endif; ?>


        </div>


    </div>


</div>



<div
    class="password-modal"
    id="passwordModal"
>


    <div class="password-modal-content">


        <h3 id="passwordModalTitle">
            Administrator Verification
        </h3>


        <p id="passwordModalMessage">
            Enter your administrator password to continue.
        </p>


        <form
            method="POST"
            action="manageNotices.php"
            id="passwordForm"
        >


            <input
                type="hidden"
                name="verify_action_password"
                value="1"
            >


            <input
                type="hidden"
                name="action_type"
                id="actionType"
            >


            <input
                type="hidden"
                name="notice_id"
                id="actionNoticeId"
            >


            <label for="admin_password">
                Administrator Password
            </label>


            <div class="password-input-container">


                <input
                    type="password"
                    name="admin_password"
                    id="admin_password"
                    required
                    autocomplete="current-password"
                >


                <button
                    type="button"
                    class="show-password"
                    onclick="togglePassword()"
                >
                    Show
                </button>


            </div>


            <div
                class="password-error"
                id="passwordError"
            >
                Please enter your administrator password.
            </div>


            <div class="password-modal-buttons">


                <button
                    type="button"
                    class="button button-secondary"
                    onclick="closePasswordPopup()"
                >
                    Cancel
                </button>


                <button
                    type="submit"
                    class="button button-primary"
                >
                    Confirm
                </button>


            </div>


        </form>


    </div>


</div>



<script>

function openPasswordPopup(action, noticeId) {

    const modal =
        document.getElementById("passwordModal");

    const actionType =
        document.getElementById("actionType");

    const actionNoticeId =
        document.getElementById("actionNoticeId");

    const passwordInput =
        document.getElementById("admin_password");

    const title =
        document.getElementById("passwordModalTitle");

    const message =
        document.getElementById("passwordModalMessage");


    actionType.value = action;

    actionNoticeId.value = noticeId;

    passwordInput.value = "";

    passwordInput.type = "password";

    document.querySelector(
        ".show-password"
    ).textContent = "Show";


    if (action === "edit") {

        title.textContent =
            "Edit Notice";

        message.textContent =
            "Enter your administrator password to edit this notice.";

    }


    if (action === "approve") {

        title.textContent =
            "Approve Notice";

        message.textContent =
            "Enter your administrator password to approve this notice.";

    }


    if (action === "delete") {

        title.textContent =
            "Delete Notice";

        message.textContent =
            "Enter your administrator password to delete this notice.";

    }


    modal.style.display = "flex";


    setTimeout(function() {

        passwordInput.focus();

    }, 100);

}


function closePasswordPopup() {

    const modal =
        document.getElementById("passwordModal");

    modal.style.display = "none";

    document.getElementById(
        "admin_password"
    ).value = "";

}


function togglePassword() {

    const passwordInput =
        document.getElementById(
            "admin_password"
        );

    const button =
        document.querySelector(
            ".show-password"
        );


    if (
        passwordInput.type ===
        "password"
    ) {

        passwordInput.type =
            "text";

        button.textContent =
            "Hide";

    } else {

        passwordInput.type =
            "password";

        button.textContent =
            "Show";

    }

}


window.onclick = function(event) {

    const modal =
        document.getElementById(
            "passwordModal"
        );

    if (event.target === modal) {

        closePasswordPopup();

    }

};


document.addEventListener(
    "keydown",
    function(event) {

        if (event.key === "Escape") {

            closePasswordPopup();

        }

    }
);

</script>


</body>

</html>

<?php

$conn->close();

?>