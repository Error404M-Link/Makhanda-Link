<?php

session_start();

require_once "Error404Connection.php";


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


//get organisation profile
$sql = "
    SELECT
        u.username,
        u.first_Name,
        u.surname,
        u.contact,
        u.User_role,
        u.address,
        u.ward_id,
        u.profile_id,
        p.image_path
    FROM `user` u
    LEFT JOIN profile_images p
        ON u.profile_id = p.profile_id
    WHERE u.username = ?
    LIMIT 1
";

$stmt = $conn->prepare($sql);

if (!$stmt) {
    die("Database error: " . $conn->error);
}

$stmt->bind_param("s", $username);
$stmt->execute();

$result = $stmt->get_result();

if (!$result || $result->num_rows === 0) {
    die("Organisation profile could not be found.");
}

$userData = $result->fetch_assoc();

$stmt->close();


//organisation display name
$firstName = trim(
    $userData["first_Name"] ?? ""
);

$surname = trim(
    $userData["surname"] ?? ""
);

$organisationName = trim(
    $firstName . " " . $surname
);

if ($organisationName === "") {
    $organisationName = $username;
}

$organisationRole = "Organisation";

$profileImage = trim(
    $userData["image_path"] ?? ""
);


//get wards
$wards = [];

$wardResult = $conn->query("
    SELECT
        ward_id,
        ward_name,
        ward_number
    FROM ward
    ORDER BY ward_number ASC
");

if ($wardResult) {

    while ($ward = $wardResult->fetch_assoc()) {
        $wards[] = $ward;
    }
}


//status messages
$statusMessage = "";
$statusType = "";

$status = $_GET["status"] ?? "";

if ($status === "success") {

    $statusMessage = "Profile updated successfully!";
    $statusType = "successNotification";

} elseif ($status === "pwd_success") {

    $statusMessage = "Password updated successfully!";
    $statusType = "successNotification";

} elseif ($status === "image_success") {

    $statusMessage = "Profile picture updated successfully!";
    $statusType = "successNotification";

} elseif ($status === "error") {

    $statusMessage =
        $_GET["msg"]
        ?? "Something went wrong.";

    $statusType = "errorNotification";
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

    <title>Profile | M-LINK</title>


    <link
        href="https://fonts.googleapis.com/icon?family=Material+Icons"
        rel="stylesheet"
    >


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


        /* =========================================
           SIDEBAR
        ========================================= */

        .sidebar {
            position: fixed;
            left: 0;
            top: 0;

            width: 265px;
            height: 100vh;

            background: #4b561b;
            color: white;

            padding: 20px 11px;

            z-index: 1000;

            overflow-y: auto;

            transition: 0.3s;
        }


        .sidebar-profile {
            display: flex;
            align-items: center;
            gap: 12px;

            padding: 5px 8px 25px;

            border-bottom:
                1px solid rgba(255,255,255,0.20);
        }


        .sidebar-avatar {
            width: 50px;
            height: 50px;

            border-radius: 50%;

            overflow: hidden;

            background: #ffffff;

            border: 5px solid rgba(255,255,255,0.25);

            display: flex;
            align-items: center;
            justify-content: center;

            flex-shrink: 0;
        }


        .sidebar-avatar img {
            width: 100%;
            height: 100%;

            object-fit: cover;
        }


        .sidebar-avatar .material-icons {
            font-size: 39px;
            color: #4b561b;
        }


        .sidebar-profile-info {
            min-width: 0;
        }


        .sidebar-profile-info strong {
            display: block;

            font-size: 13px;

            color: white;

            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }


        .sidebar-profile-info span {
            display: block;

            margin-top: 3px;

            font-size: 11px;

            color: #ffffff;
        }


        .sidebar-menu {
            margin-top: 20px;
        }


        .sidebar-link {
            width: 100%;
            min-height: 48px;

            padding: 0 15px;

            margin-bottom: 7px;

            border-radius: 8px;

            display: flex;
            align-items: center;

            gap: 16px;

            color: white;

            font-size: 14px;

            transition: 0.2s;
        }


        .sidebar-link:hover {
            background:
                rgba(255,255,255,0.12);
        }


        .sidebar-link.active {
            background:
                rgba(255,255,255,0.22);
        }


        .sidebar-link .material-icons {
            width: 22px;

            font-size: 23px;

            text-align: center;
        }


        /* =========================================
           MAIN
        ========================================= */

        .main-wrapper {
            margin-left: 265px;

            min-height: 100vh;

            transition: 0.3s;
        }


        /* =========================================
           TOPBAR
        ========================================= */

        .topbar {
            height: 85px;

            background: white;

            display: flex;
            align-items: center;
            justify-content: space-between;

            padding: 0 35px;

            border-bottom: 1px solid #ddd8cd;

            box-shadow:
                0 2px 8px rgba(0,0,0,0.04);

            position: sticky;
            top: 0;

            z-index: 500;
        }


        .topbar-left {
            display: flex;
            align-items: center;

            gap: 30px;
        }


        .menu-btn {
            border: none;

            background: transparent;

            cursor: pointer;

            color: #263238;

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
            width: 65px;
            height: 48px;

            object-fit: contain;
        }


        .topbar-right {
            display: flex;
            align-items: center;
        }


        .topbar-profile {
            display: flex;
            align-items: center;

            gap: 10px;

            padding-left: 20px;

            border-left: 1px solid #ddd8cd;

            color: #263238;
        }


        .topbar-avatar {
            width: 45px;
            height: 45px;

            border-radius: 50%;

            overflow: hidden;

            background: #ffffff;

            border: 5px solid #e4e3d8;

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
            font-size: 36px;
            color: #4b561b;
        }


        .topbar-profile-info strong {
            display: block;

            font-size: 13px;
        }


        .topbar-profile-info span {
            display: block;

            margin-top: 2px;

            font-size: 10px;

            color: #888;
        }


        /* =========================================
           PROFILE PAGE
        ========================================= */

        .profile-page-content {
            padding: 50px 55px;
        }


        .page-title {
            font-size: 28px;

            color: #263238;

            margin-bottom: 20px;
        }


        /* =========================================
           MESSAGES
        ========================================= */

        .successNotification,
        .errorNotification {
            width: 100%;

            padding: 13px 18px;

            margin-bottom: 18px;

            border-radius: 8px;

            text-align: center;

            font-size: 13px;
        }


        .successNotification {
            background: #d9f2df;

            color: #176c2d;

            border: 1px solid #b9dfc2;
        }


        .errorNotification {
            background: #fbe1df;

            color: #a82920;

            border: 1px solid #efb8b4;
        }


        /* =========================================
           PROFILE GRID
        ========================================= */

        .profile-layout {
            display: grid;

            grid-template-columns:
                265px 1fr;

            gap: 22px;

            align-items: start;
        }


        /* =========================================
           PROFILE LEFT CARD
        ========================================= */

        .profile-card {
            background: white;

            border-radius: 18px;

            padding: 28px 20px;
        }


        .profile-image-wrapper {
            width: 105px;
            height: 105px;

            margin:
                0 auto 15px;

            position: relative;
        }


        .profile-image {
            width: 105px;
            height: 105px;

            border-radius: 50%;

            object-fit: cover;

            border: 2px solid #4b561b;
        }


        .default-profile {
            width: 105px;
            height: 105px;

            border-radius: 50%;

            background: #f0eee7;

            border: 2px solid #4b561b;

            display: flex;
            align-items: center;
            justify-content: center;
        }


        .default-profile .material-icons {
            font-size: 85px;

            color: #4b561b;
        }


        .edit-picture {
            width: 28px;
            height: 28px;

            border-radius: 50%;

            position: absolute;

            right: 1px;
            bottom: 4px;

            background: #ff7200;

            color: white;

            border: 2px solid white;

            display: flex;
            align-items: center;
            justify-content: center;

            cursor: pointer;

            font-size: 11px;
        }


        #profileImageInput {
            display: none;
        }


        .save-picture-btn {
            display: none;

            margin:
                0 auto 15px;

            padding: 8px 15px;

            border: none;

            border-radius: 6px;

            background: #4b561b;

            color: white;

            cursor: pointer;

            font-size: 11px;

            font-weight: bold;
        }


        .profile-name {
            text-align: center;

            font-size: 16px;

            color: #263238;

            margin-bottom: 4px;
        }


        .profile-role {
            text-align: center;

            font-size: 12px;

            color: #929292;

            margin-bottom: 25px;
        }


        .profile-tab {
            width: 100%;
            height: 42px;

            border: none;

            border-radius: 22px;

            background: transparent;

            display: flex;
            align-items: center;

            gap: 12px;

            padding: 0 15px;

            margin-bottom: 7px;

            cursor: pointer;

            color: #7c878c;
        }


        .profile-tab.active {
            background: #fff0e6;

            color: #ff7200;

            font-weight: bold;
        }


        .profile-tab .material-icons {
            font-size: 18px;
        }


        /* =========================================
           PROFILE FORM CARD
        ========================================= */

        .profile-content-card {
            background: white;

            border-radius: 18px;

            padding: 32px 38px;

            min-height: 480px;
        }


        .section-title {
            font-size: 20px;

            margin-bottom: 25px;

            color: #263238;
        }


        .form-grid {
            display: grid;

            grid-template-columns:
                1fr 1fr;

            gap: 20px;
        }


        .form-group {
            margin-bottom: 18px;
        }


        .form-group.full {
            grid-column:
                1 / -1;
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
            box-shadow:
                0 0 0 2px
                rgba(75,86,27,0.18);
        }


        .username-wrapper {
            position: relative;
        }


        .username-wrapper
        .form-control {
            padding-right: 90px;
        }


        .verified {
            position: absolute;

            right: 15px;
            top: 50%;

            transform:
                translateY(-50%);

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
            background: white;

            color: #ff7200;

            border:
                1px solid #ff7200;
        }


        .save-btn {
            background: #ff7200;

            color: white;

            border:
                1px solid #ff7200;
        }


        /* =========================================
           PASSWORD
        ========================================= */

        #passwordSection {
            display: none;
        }


        .password-description {
            color: #899398;

            font-size: 13px;

            line-height: 1.6;

            margin-bottom: 25px;
        }


        /* =========================================
           PASSWORD MODAL
        ========================================= */

        .modal-overlay {
            position: fixed;

            inset: 0;

            background:
                rgba(0,0,0,0.55);

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

            background: white;

            border-radius: 15px;

            padding: 28px;

            box-shadow:
                0 20px 60px
                rgba(0,0,0,0.25);
        }


        .modal-box h3 {
            margin-bottom: 10px;
        }


        .modal-box p {
            color: #7e888d;

            font-size: 13px;

            line-height: 1.5;

            margin-bottom: 20px;
        }


        .modal-input {
            width: 100%;
            height: 43px;

            border:
                1px solid #d6d6d6;

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
            background: white;

            color: #555;

            border:
                1px solid #ccc;
        }


        .modal-confirm {
            background: #4b561b;

            color: white;

            border: none;
        }


        /* =========================================
           COLLAPSED SIDEBAR
        ========================================= */

        body.sidebar-collapsed
        .sidebar {
            width: 78px;
        }


        body.sidebar-collapsed
        .main-wrapper {
            margin-left: 78px;
        }


        body.sidebar-collapsed
        .sidebar-profile-info,

        body.sidebar-collapsed
        .sidebar-link span:last-child {
            display: none;
        }


        body.sidebar-collapsed
        .sidebar-profile {
            justify-content: center;
        }


        body.sidebar-collapsed
        .sidebar-link {
            justify-content: center;

            padding: 0;
        }


        /* =========================================
           MOBILE
        ========================================= */

        @media (max-width: 900px) {

            .sidebar {
                transform:
                    translateX(-100%);
            }


            body.mobile-menu-open
            .sidebar {
                transform:
                    translateX(0);
            }


            .main-wrapper {
                margin-left: 0;
            }


            .profile-page-content {
                padding: 30px 20px;
            }


            .profile-layout {
                grid-template-columns:
                    1fr;
            }


            .form-grid {
                grid-template-columns:
                    1fr;
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


    <!-- PROFILE -->

    <div class="sidebar-profile">


        <div class="sidebar-avatar">


            <?php if ($profileImage !== ""): ?>

                <img
                    src="<?=
                        htmlspecialchars(
                            $profileImage,
                            ENT_QUOTES,
                            "UTF-8"
                        )
                    ?>"
                    alt="Profile"
                >

            <?php else: ?>

                <span class="material-icons">
                    account_circle
                </span>

            <?php endif; ?>


        </div>


        <div class="sidebar-profile-info">


            <strong>

                <?=
                    htmlspecialchars(
                        $organisationName,
                        ENT_QUOTES,
                        "UTF-8"
                    )
                ?>

            </strong>


            <span>
                Organisation
            </span>


        </div>


    </div>


    <!-- NAVIGATION -->

    <nav class="sidebar-menu">


        <!-- DASHBOARD -->

        <a
            href="organisationDashboard.php"
            class="sidebar-link"
        >

            <span class="material-icons">
                home
            </span>

            <span>
                Dashboard
            </span>

        </a>


        <!-- REPORT FAULT -->

        <a
            href="reportFault.php"
            class="sidebar-link"
        >

            <span class="material-icons">
                warning
            </span>

            <span>
                Report Fault
            </span>

        </a>


        <!-- MY TICKETS -->

        <a
            href="myTickets.php"
            class="sidebar-link"
        >

            <span class="material-icons">
                confirmation_number
            </span>

            <span>
                My Tickets
            </span>

        </a>


        <!-- TICKET HISTORY -->

        <a
            href="ticketHistory.php"
            class="sidebar-link"
        >

            <span class="material-icons">
                confirmation_number
            </span>

            <span>
                Ticket History
            </span>

        </a>


        <!-- PROFILE -->

        <a
            href="settings.php"
            class="sidebar-link active"
        >

            <span class="material-icons">
                settings
            </span>

            <span>
                Profile
            </span>

        </a>


        <!-- LOGOUT -->

        <a
            href="logout.php"
            class="sidebar-link"
        >

            <span class="material-icons">
                logout
            </span>

            <span>
                Logout
            </span>

        </a>


    </nav>


</aside>



<!-- =====================================
     MAIN
====================================== -->

<div
    class="main-wrapper"
    id="mainWrapper"
>


    <!-- =====================================
         TOPBAR
    ====================================== -->

    <header class="topbar">


        <div class="topbar-left">


            <button
                type="button"
                class="menu-btn"
                id="menuBtn"
            >

                <span class="material-icons">
                    menu
                </span>

            </button>


            <a
                href="../homepage.php"
                class="logo-link"
            >

                <img
                    src="mlinkLogo.png"
                    alt="M-LINK Logo"
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
                            src="<?=
                                htmlspecialchars(
                                    $profileImage,
                                    ENT_QUOTES,
                                    "UTF-8"
                                )
                            ?>"
                            alt="Profile"
                        >

                    <?php else: ?>

                        <span class="material-icons">
                            account_circle
                        </span>

                    <?php endif; ?>


                </div>


                <div class="topbar-profile-info">


                    <strong>

                        <?=
                            htmlspecialchars(
                                $organisationName,
                                ENT_QUOTES,
                                "UTF-8"
                            )
                        ?>

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
                class="<?=
                    htmlspecialchars(
                        $statusType
                    )
                ?>"
            >

                <?=
                    htmlspecialchars(
                        $statusMessage,
                        ENT_QUOTES,
                        "UTF-8"
                    )
                ?>

            </div>

        <?php endif; ?>



        <div class="profile-layout">


            <!-- =====================================
                 PROFILE LEFT CARD
            ====================================== -->

            <div class="profile-card">


                <!-- PROFILE IMAGE FORM -->

                <form
                    action="updateProfile.php"
                    method="POST"
                    enctype="multipart/form-data"
                    id="profileImageForm"
                >


                    <input
                        type="hidden"
                        name="action"
                        value="update_profile_picture"
                    >


                    <div class="profile-image-wrapper">


                        <?php if ($profileImage !== ""): ?>

                            <img
                                src="<?=
                                    htmlspecialchars(
                                        $profileImage,
                                        ENT_QUOTES,
                                        "UTF-8"
                                    )
                                ?>"
                                alt="Profile Picture"
                                class="profile-image"
                                id="profileImage"
                            >

                        <?php else: ?>


                            <div
                                class="default-profile"
                                id="defaultProfile"
                            >

                                <span class="material-icons">
                                    account_circle
                                </span>

                            </div>


                            <img
                                src=""
                                alt="Profile Picture"
                                class="profile-image"
                                id="profileImage"
                                style="display:none;"
                            >


                        <?php endif; ?>


                        <label
                            for="profileImageInput"
                            class="edit-picture"
                        >

                            <i class="fa-solid fa-pen"></i>

                        </label>


                    </div>


                    <input
                        type="file"
                        name="profile_image"
                        id="profileImageInput"
                        accept=".jpg,.jpeg,.png,.gif,.webp"
                    >


                    <button
                        type="submit"
                        class="save-picture-btn"
                        id="savePictureButton"
                    >
                        Save Picture
                    </button>


                </form>


                <h3 class="profile-name">

                    <?=
                        htmlspecialchars(
                            $organisationName,
                            ENT_QUOTES,
                            "UTF-8"
                        )
                    ?>

                </h3>


                <p class="profile-role">
                    Organisation
                </p>


                <button
                    type="button"
                    class="profile-tab active"
                    id="personalInfoButton"
                >

                    <span class="material-icons">
                        person_outline
                    </span>

                    Personal Information

                </button>


                <button
                    type="button"
                    class="profile-tab"
                    id="passwordButton"
                >

                    <span class="material-icons">
                        lock
                    </span>

                    Password

                </button>


            </div>



            <!-- =====================================
                 RIGHT PROFILE CONTENT
            ====================================== -->

            <div class="profile-content-card">


                <!-- =====================================
                     PERSONAL INFORMATION
                ====================================== -->

                <section id="personalInfoSection">


                    <h2 class="section-title">
                        Personal Information
                    </h2>


                    <form
                        action="updateProfile.php"
                        method="POST"
                        id="profileForm"
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

                                <label for="firstName">
                                    First Name
                                </label>

                                <input
                                    type="text"
                                    id="firstName"
                                    name="first_Name"
                                    class="form-control"
                                    value="<?=
                                        htmlspecialchars(
                                            $userData["first_Name"]
                                            ?? "",
                                            ENT_QUOTES,
                                            "UTF-8"
                                        )
                                    ?>"
                                >

                            </div>


                            <!-- SURNAME -->

                            <div class="form-group">

                                <label for="surname">
                                    Surname
                                </label>

                                <input
                                    type="text"
                                    id="surname"
                                    name="surname"
                                    class="form-control"
                                    value="<?=
                                        htmlspecialchars(
                                            $userData["surname"]
                                            ?? "",
                                            ENT_QUOTES,
                                            "UTF-8"
                                        )
                                    ?>"
                                >

                            </div>


                            <!-- USERNAME -->

                            <div class="form-group full">

                                <label>
                                    Username
                                </label>


                                <div class="username-wrapper">


                                    <input
                                        type="text"
                                        class="form-control"
                                        value="<?=
                                            htmlspecialchars(
                                                $userData["username"]
                                                ?? "",
                                                ENT_QUOTES,
                                                "UTF-8"
                                            )
                                        ?>"
                                        readonly
                                    >


                                    <span class="verified">
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
                                    value="<?=
                                        htmlspecialchars(
                                            $userData["address"]
                                            ?? "",
                                            ENT_QUOTES,
                                            "UTF-8"
                                        )
                                    ?>"
                                >

                            </div>


                            <!-- CONTACT -->

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
                                    value="<?=
                                        htmlspecialchars(
                                            $userData["contact"]
                                            ?? "",
                                            ENT_QUOTES,
                                            "UTF-8"
                                        )
                                    ?>"
                                >

                            </div>


                            <!-- WARD -->

                            <div class="form-group">

                                <label for="wardId">
                                    Ward / Location
                                </label>


                                <select
                                    name="ward_id"
                                    id="wardId"
                                    class="form-control"
                                >


                                    <option value="">
                                        Select Ward
                                    </option>


                                    <?php foreach ($wards as $ward): ?>


                                        <option
                                            value="<?=
                                                (int)$ward["ward_id"]
                                            ?>"
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

                                            <?=
                                                htmlspecialchars(
                                                    $ward["ward_name"],
                                                    ENT_QUOTES,
                                                    "UTF-8"
                                                )
                                            ?>

                                            (Ward
                                            <?=
                                                htmlspecialchars(
                                                    $ward["ward_number"]
                                                )
                                            ?>)

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


                </section>



                <!-- =====================================
                     PASSWORD
                ====================================== -->

                <section id="passwordSection">


                    <h2 class="section-title">
                        Change Password
                    </h2>


                    <p class="password-description">

                        Enter your new password below.
                        Your current password will be
                        required before the change is saved.

                    </p>


                    <form
                        action="updateProfile.php"
                        method="POST"
                        id="passwordForm"
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
                                name="newPassword"
                                id="newPassword"
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
                                name="confirmNewPassword"
                                id="confirmNewPassword"
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


                </section>


            </div>


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

            Enter your current password
            to confirm these changes.

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
                "mobile-menu-open"
            );

        } else {

            document.body.classList.toggle(
                "sidebar-collapsed"
            );

        }

    }
);


//=========================================================
// PROFILE TABS
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


//=========================================================
// PROFILE PICTURE PREVIEW
//=========================================================

const profileImageInput =
    document.getElementById(
        "profileImageInput"
    );

const profileImage =
    document.getElementById(
        "profileImage"
    );

const defaultProfile =
    document.getElementById(
        "defaultProfile"
    );

const savePictureButton =
    document.getElementById(
        "savePictureButton"
    );


profileImageInput.addEventListener(
    "change",
    function () {

        const file = this.files[0];

        if (!file) {
            return;
        }


        const allowedTypes = [
            "image/jpeg",
            "image/png",
            "image/gif",
            "image/webp"
        ];


        if (!allowedTypes.includes(file.type)) {

            alert(
                "Please select a valid image."
            );

            this.value = "";

            return;
        }


        if (
            file.size >
            5 * 1024 * 1024
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


                if (defaultProfile) {

                    defaultProfile.style.display =
                        "none";

                }


                profileImage.src =
                    event.target.result;

                profileImage.style.display =
                    "block";

                savePictureButton.style.display =
                    "block";

            };


        reader.readAsDataURL(file);

    }
);


//=========================================================
// DISCARD PROFILE CHANGES
//=========================================================

document
    .getElementById(
        "discardProfileButton"
    )
    .addEventListener(
        "click",
        function () {

            document
                .getElementById(
                    "profileForm"
                )
                .reset();

        }
    );


//=========================================================
// PASSWORD CONFIRMATION
//=========================================================

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

const cancelModalButton =
    document.getElementById(
        "cancelModalButton"
    );

const confirmModalButton =
    document.getElementById(
        "confirmModalButton"
    );


let formWaitingForConfirmation =
    null;


//profile update
profileForm.addEventListener(
    "submit",
    function (event) {

        event.preventDefault();

        formWaitingForConfirmation =
            profileForm;

        openPasswordModal();

    }
);


//password change
passwordForm.addEventListener(
    "submit",
    function (event) {

        event.preventDefault();


        const newPassword =
            document.getElementById(
                "newPassword"
            ).value;

        const confirmPassword =
            document.getElementById(
                "confirmNewPassword"
            ).value;


        if (
            newPassword !==
            confirmPassword
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


//cancel
cancelModalButton.addEventListener(
    "click",
    closePasswordModal
);


//confirm
confirmModalButton.addEventListener(
    "click",
    function () {


        const enteredPassword =
            modalPassword.value.trim();


        if (enteredPassword === "") {

            modalPassword.focus();

            return;
        }


        if (!formWaitingForConfirmation) {
            return;
        }


        if (
            formWaitingForConfirmation
            === profileForm
        ) {

            document
                .getElementById(
                    "profileConfirmPassword"
                )
                .value =
                    enteredPassword;

        }


        if (
            formWaitingForConfirmation
            === passwordForm
        ) {

            document
                .getElementById(
                    "passwordConfirmPassword"
                )
                .value =
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


//press enter
modalPassword.addEventListener(
    "keydown",
    function (event) {

        if (event.key === "Enter") {

            event.preventDefault();

            confirmModalButton.click();

        }

    }
);


//click outside modal
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


//=========================================================
// AUTO HIDE MESSAGE
//=========================================================

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