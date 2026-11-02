<?php

session_start();

require_once "Error404Connection.php";
require_once "detailsOfTheLoggedInOrganisation.php";


//check if the administrator is logged in
if (!isset($_SESSION["username"])) {
    header("Location: ../PUBLIC PAGES/login.php");
    exit;
}

$username = $_SESSION["username"];


//check organisation role
$userRole = strtolower(
    trim(
        $_SESSION["user_role"]
        ?? $_SESSION["role"]
        ?? ""
    )
);

if ($userRole !== "organisation") {

    die("Access denied.");

}


//get organisation information
$query = "
    SELECT
        o.organisation_id,
        o.username,
        o.organisation_name,
        o.contact,
        u.User_role,
        o.address,
        o.ward_id,
        p.image_path
    FROM organisation o
    LEFT JOIN 
        user u ON o.username = u.username
    LEFT JOIN 
        profile_images p ON o.profile_id = p.profile_id
    WHERE o.username = ?
    LIMIT 1
";

$stmt = $conn->prepare($query);

if (!$stmt) {

    die("Database Error: " . $conn->error);

}

$stmt->bind_param("s", $username);

$stmt->execute();

$result = $stmt->get_result();

$userData = [];

if (
    $result &&
    $result->num_rows > 0
) {

    $userData = $result->fetch_assoc();

} else {

    die("Organisation profile could not be found.");

}

$stmt->close();


//organisation name
$organisationName =
    trim($userData["organisation_name"] ?? "");

if ($organisationName === "") {

    $organisationName = $username;

}

$organisationRole = "Organisation";

$profileImage =
    trim($userData["image_path"] ?? "");


//get wards
$wards = [];

$wardQuery = "
    SELECT
        ward_id,
        ward_name,
        ward_number
    FROM ward
    ORDER BY ward_number ASC
";

$wardResult =
    $conn->query($wardQuery);

if ($wardResult) {

    while (
        $ward =
        $wardResult->fetch_assoc()
    ) {

        $wards[] = $ward;

    }

}


//status message
$statusMessage = "";
$statusType = "";

if (isset($_GET["status"])) {

    if ($_GET["status"] === "success") {

        $statusMessage =
            "Profile updated successfully!";

        $statusType =
            "successNotification";

    } elseif (
        $_GET["status"] === "pwd_success"
    ) {

        $statusMessage =
            "Password updated successfully!";

        $statusType =
            "successNotification";

    } elseif (
        $_GET["status"] === "image_success"
    ) {

        $statusMessage =
            "Profile picture updated successfully!";

        $statusType =
            "successNotification";

    } elseif (
        $_GET["status"] === "error"
    ) {

        $statusMessage =
            $_GET["msg"]
            ?? "Something went wrong.";

        $statusType =
            "errorNotification";

    }

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

    <title>
        Profile | M-LINK Organisation
    </title>


    <!-- MATERIAL ICONS -->

    <link
        href="https://fonts.googleapis.com/icon?family=Material+Icons"
        rel="stylesheet"
    >


    <!-- FONT AWESOME -->

    <link
        rel="stylesheet"
        href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css"
    >


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


        a {
            text-decoration: none;
        }


        button,
        input,
        select {
            font-family: inherit;
        }


        /* SIDEBAR */

        .sidebar {
            width: 255px;
            height: 100vh;
            position: fixed;
            top: 0;
            left: 0;
            background: #4b561b;
            color: #ffffff;
            z-index: 1000;
            transition: 0.3s ease;
            overflow-y: auto;
        }


        .sidebar-profile {
            padding: 35px 20px 25px;
            border-bottom:
                1px solid rgba(255,255,255,0.18);
            display: flex;
            align-items: center;
            gap: 12px;
        }


        .sidebar-avatar {
            width: 48px;
            height: 48px;
            border-radius: 50%;
            overflow: hidden;
            flex-shrink: 0;
            background: rgba(255,255,255,0.15);
            display: flex;
            align-items: center;
            justify-content: center;
        }


        .sidebar-avatar img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }


        .sidebar-avatar .material-icons {
            font-size: 42px;
            color: #ffffff;
        }


        .sidebar-profile-info {
            min-width: 0;
        }


        .sidebar-profile-info strong {
            display: block;
            color: #ffffff;
            font-size: 13px;
            margin-bottom: 4px;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }


        .sidebar-profile-info span {
            display: block;
            color: rgba(255,255,255,0.72);
            font-size: 11px;
        }


        .sidebar-menu {
            padding: 25px 15px;
        }


        .sidebar-link {
            min-height: 46px;
            margin-bottom: 7px;
            padding: 0 15px;
            border-radius: 7px;
            display: flex;
            align-items: center;
            gap: 14px;
            color: #ffffff;
            font-size: 13px;
            transition: 0.2s ease;
        }


        .sidebar-link:hover {
            background: rgba(255,255,255,0.12);
        }


        .sidebar-link.active {
            background: rgba(255,255,255,0.22);
            font-weight: bold;
        }


        .sidebar-icon {
            width: 20px;
            height: 20px;
            object-fit: contain;
            filter: brightness(0) invert(1);
        }


        .sidebar-material-icon {
            width: 20px;
            font-size: 20px;
        }


        /* =========================================
           MAIN WRAPPER
        ========================================= */

        .main-wrapper {
            margin-left: 255px;
            min-height: 100vh;
            transition: 0.3s ease;
        }


        /* TOPBAR */

        .topbar {
            height: 76px;
            background: #ffffff;
            border-bottom: 1px solid #e5e1d8;
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 0 30px;
            position: sticky;
            top: 0;
            z-index: 500;
            box-shadow: 0 2px 7px rgba(0,0,0,0.04);
        }


        .topbar-left {
            display: flex;
            align-items: center;
            gap: 25px;
        }


        .menu-btn {
            width: 40px;
            height: 40px;
            border: none;
            background: transparent;
            color: #263238;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
        }


        .menu-btn .material-icons {
            font-size: 28px;
        }


        .logo-link {
            display: flex;
            align-items: center;
        }


        .topbar-logo {
            width: 50px;
            height: 50px;
            object-fit: contain;
        }


        .topbar-right {
            display: flex;
            align-items: center;
        }


        .topbar-profile {
            padding-left: 20px;
            border-left: 1px solid #e5e1d8;
            display: flex;
            align-items: center;
            gap: 10px;
            color: #263238;
        }


        .topbar-avatar {
            width: 42px;
            height: 42px;
            border-radius: 50%;
            overflow: hidden;
            background: #ece9df;
            display: flex;
            align-items: center;
            justify-content: center;
        }


        .topbar-avatar img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }


        .topbar-avatar .material-icons {
            font-size: 38px;
            color: #777;
        }


        .topbar-profile-info strong {
            display: block;
            font-size: 12px;
            margin-bottom: 3px;
        }


        .topbar-profile-info span {
            display: block;
            font-size: 10px;
            color: #999;
        }


        /* PAGE */

        .profile-page-content {
            padding: 55px 55px;
        }


        .page-title {
            margin-bottom: 20px;
            font-size: 27px;
            color: #263238;
        }


        /* NOTIFICATIONS */

        .successNotification,
        .errorNotification {
            padding: 13px 18px;
            margin-bottom: 18px;
            border-radius: 7px;
            text-align: center;
            font-size: 13px;
        }


        .successNotification {
            background: #d9f2df;
            border: 1px solid #b9dfc2;
            color: #176c2d;
        }


        .errorNotification {
            background: #fbe1df;
            border: 1px solid #efb8b4;
            color: #a82920;
        }


        /* PROFILE LAYOUT */

        .profile-layout {
            display: grid;
            grid-template-columns: 265px 1fr;
            gap: 22px;
            align-items: start;
        }


        .profile-sidebar-card {
            background: #ffffff;
            border-radius: 18px;
            padding: 28px 20px;
        }


        .profileAvatarWrapper {
            width: 105px;
            height: 105px;
            margin: 0 auto 15px;
            position: relative;
        }


        .profileAvatarWrapper img {
            width: 105px;
            height: 105px;
            border-radius: 50%;
            object-fit: cover;
            border: 2px solid #4b561b;
        }


        .default-profile-icon {
            width: 105px;
            height: 105px;
            border-radius: 50%;
            background: #ece9df;
            border: 2px solid #4b561b;
            display: flex;
            align-items: center;
            justify-content: center;
        }


        .default-profile-icon .material-icons {
            font-size: 85px;
            color: #777;
        }


        .editBadge {
            width: 27px;
            height: 27px;
            border-radius: 50%;
            position: absolute;
            right: 2px;
            bottom: 4px;
            background: #ff7200;
            color: #ffffff;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            border: 2px solid #ffffff;
            font-size: 11px;
        }


        .profileImageForm {
            text-align: center;
        }


        .fileInputHidden {
            display: none;
        }


        .imageSaveButton {
            display: none;
            margin: 8px auto 15px;
            padding: 8px 14px;
            border: none;
            border-radius: 6px;
            background: #4b561b;
            color: #ffffff;
            font-size: 11px;
            font-weight: bold;
            cursor: pointer;
        }


        .profile-name {
            text-align: center;
            font-size: 16px;
            margin-bottom: 4px;
            color: #263238;
        }


        .profile-role {
            text-align: center;
            color: #929292;
            font-size: 12px;
            margin-bottom: 24px;
        }


        .profile-nav-button {
            width: 100%;
            height: 42px;
            margin-bottom: 7px;
            border: none;
            border-radius: 22px;
            background: transparent;
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 0 15px;
            cursor: pointer;
            color: #7c878c;
            text-align: left;
        }


        .profile-nav-button.active {
            background: #fff0e6;
            color: #ff7200;
            font-weight: bold;
        }


        .profile-nav-button .material-icons {
            font-size: 18px;
        }


        /* =========================================
           FORM CARD
        ========================================= */

        .profile-content-card {
            background: #ffffff;
            border-radius: 18px;
            padding: 32px 38px;
            min-height: 480px;
        }


        .profile-section-title {
            font-size: 20px;
            margin-bottom: 25px;
            color: #263238;
        }


        .form-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
        }


        .form-group {
            margin-bottom: 18px;
        }


        .form-group.full {
            grid-column: 1 / -1;
        }


        .form-group label {
            display: block;
            margin-bottom: 7px;
            color: #8a969b;
            font-size: 12px;
        }


        .form-control {
            width: 100%;
            height: 42px;
            border: none;
            border-radius: 10px;
            background: #f5f6f7;
            padding: 0 16px;
            color: #263238;
            outline: none;
            font-size: 13px;
        }


        .form-control:focus {
            box-shadow: 0 0 0 2px rgba(75,86,27,0.18);
        }


        .username-wrapper {
            position: relative;
        }


        .username-wrapper .form-control {
            padding-right: 90px;
        }


        .verified-text {
            position: absolute;
            right: 15px;
            top: 50%;
            transform: translateY(-50%);
            color: #35b86b;
            font-size: 11px;
            font-weight: bold;
        }


        .form-actions {
            display: flex;
            justify-content: flex-end;
            gap: 12px;
            margin-top: 12px;
        }


        .discard-btn,
        .save-btn {
            min-width: 140px;
            height: 42px;
            border-radius: 9px;
            cursor: pointer;
            font-weight: bold;
            font-size: 12px;
        }


        .discard-btn {
            background: #ffffff;
            color: #ff7200;
            border: 1px solid #ff7200;
        }


        .save-btn {
            background: #ff7200;
            color: #ffffff;
            border: 1px solid #ff7200;
        }


        /* =========================================
           PASSWORD
        ========================================= */

        .password-section {
            display: none;
        }


        .password-info {
            margin-bottom: 25px;
            color: #899398;
            font-size: 13px;
            line-height: 1.6;
        }


        /* =========================================
           MODAL
        ========================================= */

        .modal-overlay {
            position: fixed;
            inset: 0;
            background: rgba(0,0,0,0.55);
            display: none;
            align-items: center;
            justify-content: center;
            z-index: 5000;
            padding: 20px;
        }


        .modal-overlay.show {
            display: flex;
        }


        .modal-box {
            width: 100%;
            max-width: 430px;
            background: #ffffff;
            border-radius: 15px;
            padding: 28px;
            box-shadow: 0 20px 60px rgba(0,0,0,0.25);
        }


        .modal-box h3 {
            margin-bottom: 10px;
            color: #263238;
        }


        .modal-box p {
            margin-bottom: 20px;
            color: #7e888d;
            font-size: 13px;
            line-height: 1.5;
        }


        .modal-input {
            width: 100%;
            height: 43px;
            border: 1px solid #d6d6d6;
            border-radius: 8px;
            padding: 0 13px;
            outline: none;
            margin-bottom: 18px;
        }


        .modal-actions {
            display: flex;
            justify-content: flex-end;
            gap: 10px;
        }


        .modal-cancel,
        .modal-confirm {
            min-width: 100px;
            height: 40px;
            border-radius: 7px;
            cursor: pointer;
            font-weight: bold;
        }


        .modal-cancel {
            background: #ffffff;
            color: #555;
            border: 1px solid #ccc;
        }


        .modal-confirm {
            background: #4b561b;
            color: #ffffff;
            border: none;
        }


        /* =========================================
           COLLAPSED SIDEBAR
        ========================================= */

        body.sidebar-collapsed .sidebar {
            width: 78px;
        }


        body.sidebar-collapsed .main-wrapper {
            margin-left: 78px;
        }


        body.sidebar-collapsed
        .sidebar-profile-info,

        body.sidebar-collapsed
        .sidebar-link span:not(.material-icons) {
            display: none;
        }


        body.sidebar-collapsed
        .sidebar-profile {
            justify-content: center;
            padding-left: 10px;
            padding-right: 10px;
        }


        body.sidebar-collapsed
        .sidebar-link {
            justify-content: center;
            padding: 0;
        }


        /* =========================================
           RESPONSIVE
        ========================================= */

        @media (max-width: 900px) {

            .sidebar {
                transform: translateX(-100%);
            }


            body.mobile-sidebar-open
            .sidebar {
                transform: translateX(0);
            }


            .main-wrapper,
            body.sidebar-collapsed
            .main-wrapper {
                margin-left: 0;
            }


            .profile-page-content {
                padding: 30px 20px;
            }


            .profile-layout {
                grid-template-columns: 1fr;
            }


            .form-grid {
                grid-template-columns: 1fr;
            }


            .form-group.full {
                grid-column: auto;
            }

        }


        @media (max-width: 600px) {

            .topbar {
                padding: 0 15px;
            }


            .topbar-profile-info {
                display: none;
            }


            .profile-content-card {
                padding: 25px 20px;
            }


            .form-actions {
                flex-direction: column;
            }


            .discard-btn,
            .save-btn {
                width: 100%;
            }

        }

    </style>

</head>


<body>


<!-- =====================================
     ORGANISATION SIDEBAR
====================================== -->

<aside
    class="sidebar"
    id="sidebar"
>


    <div class="sidebar-profile">


        <div class="sidebar-avatar">


            <?php if ($profileImage !== ""): ?>

                <img
                    src="<?php echo htmlspecialchars(
                        $profileImage,
                        ENT_QUOTES,
                        "UTF-8"
                    ); ?>"
                    alt="Organisation Profile"
                >

            <?php else: ?>

                <span class="material-icons">
                    account_circle
                </span>

            <?php endif; ?>


        </div>


        <div class="sidebar-profile-info">


            <strong>

                <?php echo htmlspecialchars(
                    $organisationName,
                    ENT_QUOTES,
                    "UTF-8"
                ); ?>

            </strong>


            <span>

                <?php echo htmlspecialchars(
                    $organisationRole,
                    ENT_QUOTES,
                    "UTF-8"
                ); ?>

            </span>


        </div>


    </div>


    <!-- ORGANISATION NAVIGATION -->

    <nav class="sidebar-menu">


        <a
            href="organisationdb.php"
            class="sidebar-link"
            title="Dashboard"
        >

            <span
                class="material-icons sidebar-material-icon"
            >
                home
            </span>

            <span>
                Dashboard
            </span>

        </a>


        <a href="reportFaultTicketForm.php" class="sidebar-link">

            <span class="material-icons sidebar-material-icon"> report_problem</span>

                <span> Report Fault</span>

        </a>


        <a href="ticketHistory.php" class="sidebar-link">

            <span class="material-icons sidebar-material-icon"> confirmation_number</span>

            <span> My Tickets </span>

        </a>


        <a href="settings.php" class="sidebar-link active" title="Profile">

            <span class="material-icons sidebar-material-icon"> person </span>

            <span> Profile</span>

        </a>


        <a href="logout.php" class="sidebar-link" title="Logout">

            <span class="material-icons sidebar-material-icon">logout </span>

            <span>Logout</span>

        </a>


    </nav>


</aside>


<!-- =====================================
     MAIN
====================================== -->

<div class="main-wrapper" id="mainWrapper">


    <!-- =====================================
         TOPBAR
    ====================================== -->

    <header class="topbar">


        <div class="topbar-left">


            <button
                type="button"
                class="menu-btn"
                id="menuBtn"
                aria-label="Toggle menu"
            >

                <span class="material-icons">
                    menu
                </span>

            </button>


            <a
                href="../homepage.php"
                class="logo-link"
                title="M-LINK Home"
            >

                <img
                    src="mlinkLogo.png"
                    alt="M-LINK"
                    class="topbar-logo"
                >

            </a>


        </div>


        <div class="topbar-right">


            <a
                href="settings.php"
                class="topbar-profile"
            >


                <div class="topbar-avatar">


                    <?php if ($profileImage !== ""): ?>

                        <img
                            src="<?php echo htmlspecialchars(
                                $profileImage,
                                ENT_QUOTES,
                                "UTF-8"
                            ); ?>"
                            alt="Organisation Profile"
                        >

                    <?php else: ?>

                        <span class="material-icons">
                            account_circle
                        </span>

                    <?php endif; ?>


                </div>


                <div class="topbar-profile-info">


                    <strong>

                        <?php echo htmlspecialchars(
                            $organisationName,
                            ENT_QUOTES,
                            "UTF-8"
                        ); ?>

                    </strong>


                    <span>
                        Organisation
                    </span>


                </div>


            </a>


        </div>


    </header>


    <!-- =====================================
         PROFILE PAGE
    ====================================== -->

    <main class="profile-page-content">


        <h1 class="page-title">
            Profile
        </h1>


        <?php if ($statusMessage !== ""): ?>

            <div
                class="<?php echo htmlspecialchars(
                    $statusType
                ); ?>"
            >

                <?php echo htmlspecialchars(
                    $statusMessage,
                    ENT_QUOTES,
                    "UTF-8"
                ); ?>

            </div>

        <?php endif; ?>


        <div class="profile-layout">


            <!-- =====================================
                 LEFT PROFILE CARD
            ====================================== -->

            <aside class="profile-sidebar-card">


                <!-- PROFILE PICTURE -->

                <form
                    id="profileImageForm"
                    action="updateProfile.php"
                    method="POST"
                    enctype="multipart/form-data"
                    class="profileImageForm"
                >


                    <input
                        type="hidden"
                        name="action"
                        value="update_profile_image"
                    >


                    <div class="profileAvatarWrapper">


                        <?php if ($profileImage !== ""): ?>

                            <img
                                src="<?php echo htmlspecialchars(
                                    $profileImage,
                                    ENT_QUOTES,
                                    "UTF-8"
                                ); ?>"
                                alt="Organisation Profile Picture"
                                id="profileImage"
                            >

                        <?php else: ?>

                            <div
                                class="default-profile-icon"
                                id="defaultProfileIcon"
                            >

                                <span class="material-icons">
                                    account_circle
                                </span>

                            </div>


                            <img src="" alt="Organisation Profile Picture" id="profileImage"style="display:none;">

                        <?php endif; ?>


                        <label
                            for="imageUpload"
                            class="editBadge"
                            title="Change profile picture"
                        >

                            <i class="fa-solid fa-pen"></i>

                        </label>


                    </div>


                    <input
                        type="file"
                        id="imageUpload"
                        name="profile_image"
                        accept=".jpg,.jpeg,.png,.gif,.webp,image/jpeg,image/png,image/gif,image/webp"
                        class="fileInputHidden"
                    >


                    <button
                        type="submit"
                        class="imageSaveButton"
                        id="imageSaveButton"
                    >
                        Save Picture
                    </button>


                </form>


                <h3 class="profile-name">

                    <?php echo htmlspecialchars(
                        $organisationName,
                        ENT_QUOTES,
                        "UTF-8"
                    ); ?>

                </h3>


                <p class="profile-role">
                    Organisation
                </p>


                <button
                    type="button"
                    class="profile-nav-button active"
                    id="personalInfoButton"
                >

                    <span class="material-icons">
                        person_outline
                    </span>

                    Personal Information

                </button>


                <button
                    type="button"
                    class="profile-nav-button"
                    id="passwordButton"
                >

                    <span class="material-icons">
                        lock
                    </span>

                    Password

                </button>


            </aside>


            <!-- =====================================
                 RIGHT CONTENT
            ====================================== -->

            <section class="profile-content-card">


                <!-- =====================================
                     PERSONAL INFORMATION
                ====================================== -->

                <div
                    id="personalInfoSection"
                    class="personal-info-section"
                >


                    <h2 class="profile-section-title">
                        Personal Information
                    </h2>


                    <form
                        id="profileForm"
                        action="updateProfile.php"
                        method="POST"
                    >


                        <input
                            type="hidden"
                            name="action"
                            value="update_profile"
                        >


                        <input
                            type="hidden"
                            name="confirm_password"
                            id="profileConfirmPassword"
                        >


                        <div class="form-grid">


                            <!-- FIRST NAME -->

                            <div class="form-group">

                                <label for="organisationName">
                                    Organisation Name
                                </label>

                                <input
                                    type="text"
                                    id="organisationName"
                                    name="organisation_name"
                                    class="form-control"
                                    value="<?php echo htmlspecialchars(
                                        $userData["organisation_name"]
                                        ?? "",
                                        ENT_QUOTES,
                                        "UTF-8"
                                    ); ?>"
                                >

                            </div>


                            <!-- USERNAME -->

                            <div class="form-group full">

                                <label>
                                    Username
                                </label>


                                <div class="username-wrapper">

                                    <input
                                        type="email"
                                        class="form-control"
                                        value="<?php echo htmlspecialchars(
                                            $userData["username"]
                                            ?? "",
                                            ENT_QUOTES,
                                            "UTF-8"
                                        ); ?>"
                                        readonly
                                    >

                                    <span class="verified-text">
                                        Verified
                                    </span>

                                </div>


                            </div>


                            <!-- ADDRESS -->

                            <div class="form-group full">

                                <label for="address">
                                    Address
                                </label>

                                <input
                                    type="text"
                                    id="address"
                                    name="address"
                                    class="form-control"
                                    value="<?php echo htmlspecialchars(
                                        $userData["address"]
                                        ?? "",
                                        ENT_QUOTES,
                                        "UTF-8"
                                    ); ?>"
                                >

                            </div>


                            <!-- PHONE -->

                            <div class="form-group">

                                <label for="contact">
                                    Phone Number
                                </label>

                                <input
                                    type="text"
                                    id="contact"
                                    name="contact"
                                    class="form-control"
                                    maxlength="10"
                                    value="<?php echo htmlspecialchars(
                                        $userData["contact"]
                                        ?? "",
                                        ENT_QUOTES,
                                        "UTF-8"
                                    ); ?>"
                                >

                            </div>


                            <!-- WARD -->

                            <div class="form-group">

                                <label for="wardId">
                                    Ward / Location
                                </label>


                                <select
                                    id="wardId"
                                    name="ward_id"
                                    class="form-control"
                                >


                                    <option value="">
                                        Select Ward
                                    </option>


                                    <?php foreach ($wards as $ward): ?>


                                        <option
                                            value="<?php echo (int)$ward["ward_id"]; ?>"
                                            <?php
                                            if (
                                                (int)(
                                                    $userData["ward_id"]
                                                    ?? 0
                                                )
                                                ===
                                                (int)$ward["ward_id"]
                                            ) {
                                                echo "selected";
                                            }
                                            ?>
                                        >

                                            <?php echo htmlspecialchars(
                                                $ward["ward_name"],
                                                ENT_QUOTES,
                                                "UTF-8"
                                            ); ?>

                                            (Ward
                                            <?php echo htmlspecialchars(
                                                $ward["ward_number"]
                                            ); ?>)

                                        </option>


                                    <?php endforeach; ?>


                                </select>


                            </div>


                        </div>


                        <div class="form-actions">


                            <button
                                type="button"
                                class="discard-btn"
                                id="discardProfileButton"
                            >
                                Discard Changes
                            </button>


                            <button
                                type="submit"
                                class="save-btn"
                            >
                                Save Changes
                            </button>


                        </div>


                    </form>


                </div>


                <!-- =====================================
                     PASSWORD SECTION
                ====================================== -->

                <div
                    id="passwordSection"
                    class="password-section"
                >


                    <h2 class="profile-section-title">
                        Change Password
                    </h2>


                    <p class="password-info">
                        Enter your new password below.
                        You will be asked to confirm your
                        current password before the change
                        is saved.
                    </p>


                    <form
                        id="passwordForm"
                        action="updateProfile.php"
                        method="POST"
                    >


                        <input
                            type="hidden"
                            name="action"
                            value="change_password"
                        >


                        <input
                            type="hidden"
                            name="confirm_password"
                            id="passwordConfirmPassword"
                        >


                        <div class="form-group">

                            <label for="newPassword">
                                New Password
                            </label>

                            <input
                                type="password"
                                id="newPassword"
                                name="newPassword"
                                class="form-control"
                                required
                            >

                        </div>


                        <div class="form-group">

                            <label for="confirmNewPassword">
                                Confirm New Password
                            </label>

                            <input
                                type="password"
                                id="confirmNewPassword"
                                name="confirmNewPassword"
                                class="form-control"
                                required
                            >

                        </div>


                        <div class="form-actions">


                            <button
                                type="reset"
                                class="discard-btn"
                            >
                                Discard Changes
                            </button>


                            <button
                                type="submit"
                                class="save-btn"
                            >
                                Change Password
                            </button>


                        </div>


                    </form>


                </div>


            </section>


        </div>


    </main>


</div>


<!-- =====================================
     PASSWORD CONFIRMATION MODAL
====================================== -->

<div
    class="modal-overlay"
    id="passwordModal"
>


    <div class="modal-box">


        <h3>
            Confirm Password
        </h3>


        <p>
            Enter your current password to confirm
            these changes.
        </p>


        <input
            type="password"
            id="modalPassword"
            class="modal-input"
            placeholder="Enter current password"
            autocomplete="current-password"
        >


        <div class="modal-actions">


            <button
                type="button"
                class="modal-cancel"
                id="cancelModalButton"
            >
                Cancel
            </button>


            <button
                type="button"
                class="modal-confirm"
                id="confirmModalButton"
            >
                Confirm
            </button>


        </div>


    </div>


</div>


<script>

//=========================================================
// SIDEBAR
//=========================================================

const menuBtn =
    document.getElementById("menuBtn");


menuBtn.addEventListener(
    "click",
    function () {

        if (window.innerWidth <= 900) {

            document.body.classList.toggle(
                "mobile-sidebar-open"
            );

        } else {

            document.body.classList.toggle(
                "sidebar-collapsed"
            );

        }

    }
);


//=========================================================
// PROFILE SECTIONS
//=========================================================

const personalInfoButton =
    document.getElementById(
        "personalInfoButton"
    );

const passwordButton =
    document.getElementById(
        "passwordButton"
    );

const personalInfoSection =
    document.getElementById(
        "personalInfoSection"
    );

const passwordSection =
    document.getElementById(
        "passwordSection"
    );


personalInfoButton.addEventListener(
    "click",
    function () {

        personalInfoSection.style.display =
            "block";

        passwordSection.style.display =
            "none";

        personalInfoButton.classList.add(
            "active"
        );

        passwordButton.classList.remove(
            "active"
        );

    }
);


passwordButton.addEventListener(
    "click",
    function () {

        personalInfoSection.style.display =
            "none";

        passwordSection.style.display =
            "block";

        passwordButton.classList.add(
            "active"
        );

        personalInfoButton.classList.remove(
            "active"
        );

    }
);

// PROFILE IMAGE PREVIEW //

const imageUpload =
    document.getElementById(
        "imageUpload"
    );

const profileImage =
    document.getElementById(
        "profileImage"
    );

const defaultProfileIcon =
    document.getElementById(
        "defaultProfileIcon"
    );

const imageSaveButton =
    document.getElementById(
        "imageSaveButton"
    );


imageUpload.addEventListener(
    "change",
    function () {

        const file =
            this.files[0];


        if (!file) {

            return;

        }


        const allowedTypes = [
            "image/jpeg",
            "image/png",
            "image/gif",
            "image/webp"
        ];


        if (
            !allowedTypes.includes(
                file.type
            )
        ) {

            alert(
                "Please select a valid image."
            );

            this.value = "";

            return;

        }


        if (
            file.size >
            (5 * 1024 * 1024)
        ) {

            alert(
                "Profile picture must be smaller than 5MB."
            );

            this.value = "";

            return;

        }


        const reader =
            new FileReader();


        reader.onload =
            function (event) {


                if (defaultProfileIcon) {

                    defaultProfileIcon.style.display =
                        "none";

                }


                profileImage.src =
                    event.target.result;

                profileImage.style.display =
                    "block";

                imageSaveButton.style.display =
                    "block";

            };


        reader.readAsDataURL(file);

    }
);


//DISCARD PROFILE CHANGES //

document.getElementById(
    "discardProfileButton"
).addEventListener(
    "click",
    function () {

        document.getElementById(
            "profileForm"
        ).reset();

    }
);

// PASSWORD CONFIRMATION MODAL //


const profileForm =
    document.getElementById(
        "profileForm"
    );

const passwordForm =
    document.getElementById(
        "passwordForm"
    );

const passwordModal =
    document.getElementById(
        "passwordModal"
    );

const modalPassword =
    document.getElementById(
        "modalPassword"
    );

const confirmModalButton =
    document.getElementById(
        "confirmModalButton"
    );

const cancelModalButton =
    document.getElementById(
        "cancelModalButton"
    );


let formWaitingForConfirmation =
    null;


//profile update //
profileForm.addEventListener(
    "submit",
    function (event) {

        event.preventDefault();

        formWaitingForConfirmation =
            profileForm;

        openPasswordModal();

    }
);


//password update //
passwordForm.addEventListener(
    "submit",
    function (event) {

        event.preventDefault();


        const newPassword =
            document.getElementById(
                "newPassword"
            ).value;

        const confirmNewPassword =
            document.getElementById(
                "confirmNewPassword"
            ).value;


        if (
            newPassword !==
            confirmNewPassword
        ) {

            alert(
                "New passwords do not match."
            );

            return;

        }


        formWaitingForConfirmation =
            passwordForm;

        openPasswordModal();

    }
);


function openPasswordModal() {

    modalPassword.value = "";

    passwordModal.classList.add(
        "show"
    );


    setTimeout(
        function () {

            modalPassword.focus();

        },
        100
    );

}


function closePasswordModal() {

    passwordModal.classList.remove(
        "show"
    );

    modalPassword.value = "";

    formWaitingForConfirmation =
        null;

}


//cancel modal //
cancelModalButton.addEventListener(
    "click",
    closePasswordModal
);


//confirm modal //
confirmModalButton.addEventListener(
    "click",
    function () {

        const enteredPassword =
            modalPassword.value;


        if (
            enteredPassword.trim() === ""
        ) {

            modalPassword.focus();

            return;

        }


        if (
            !formWaitingForConfirmation
        ) {

            return;

        }


        if (
            formWaitingForConfirmation
            === profileForm
        ) {

            document.getElementById(
                "profileConfirmPassword"
            ).value =
                enteredPassword;

        }


        if (
            formWaitingForConfirmation
            === passwordForm
        ) {

            document.getElementById(
                "passwordConfirmPassword"
            ).value =
                enteredPassword;

        }


        const formToSubmit =
            formWaitingForConfirmation;


        passwordModal.classList.remove(
            "show"
        );


        formWaitingForConfirmation =
            null;


        formToSubmit.submit();

    }
);


//allow Enter in modal
modalPassword.addEventListener(
    "keydown",
    function (event) {

        if (event.key === "Enter") {

            event.preventDefault();

            confirmModalButton.click();

        }

    }
);


//close modal when clicking background
passwordModal.addEventListener(
    "click",
    function (event) {

        if (
            event.target ===
            passwordModal
        ) {

            closePasswordModal();

        }

    }
);


 //AUTO HIDE STATUS MESSAGE //

const notification =
    document.querySelector(
        ".successNotification, .errorNotification"
    );


if (notification) {

    setTimeout(
        function () {

            notification.style.transition =
                "opacity 0.4s ease";

            notification.style.opacity =
                "0";


            setTimeout(
                function () {

                    notification.remove();

                },
                400
            );

        },
        4000
    );

}

</script>


</body>

</html>