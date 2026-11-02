<?php 
    session_start();

    require_once "Error404Connection.php";
    require_once "detailsOfTheLoggedInUser.php";

    /* ########### LOGGED-IN USER (same check as residence.php) ############ */
    $username = $_SESSION["username"] ?? "";

    if ($username === "") {
        header("Location: ../PUBLIC%20PAGES/login.php");
        exit;
    }

    $query = " SELECT 
                u.username,
                u.first_Name,
                u.surname,
                u.contact,
                u.User_role,
                u.address,
                u.ward_id,
                p.image_path
            FROM user u
            LEFT JOIN profile_images p ON u.profile_id = p.profile_id
            WHERE u.username = ?";

    $userData = [];

    if ($stmt = $conn->prepare($query)) {
        $stmt->bind_param("s", $username);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result && $row = $result->fetch_assoc()) {
            $userData = $row;
        }
        $stmt->close();
    } else {
        die("Database Error: " . $conn->error);
    }

    /* ########### SIDEBAR / TOPBAR VARIABLES (same as residence.php) ############ */
    $firstName    = $userData["first_Name"] ?? "Resident";
    $surname      = $userData["surname"] ?? "";
    $profileImage = $userData["image_path"] ?? "";

    $unreadNotificationCount = 0;

    $notifSql = "
        SELECT COUNT(*) AS unread_count
        FROM notification
        WHERE username = ?
        AND notification_type = 'ticket_update'
        AND is_read = 0
    ";

    if ($notifStmt = $conn->prepare($notifSql)) {
        $notifStmt->bind_param("s", $username);
        $notifStmt->execute();
        $notifResult = $notifStmt->get_result();

        if ($notifResult && $notifRow = $notifResult->fetch_assoc()) {
            $unreadNotificationCount = (int)$notifRow["unread_count"];
        }

        $notifStmt->close();
    }

    /* ########### FETCH THE WARDS ############ */
    $wardQuery = "SELECT ward_id, ward_name, ward_number FROM ward ORDER BY ward_number ASC";
    $wardResult = $conn->query($wardQuery);
    $wards = [];

    if ($wardResult) {
        while ($ward = $wardResult->fetch_assoc()) {
            $wards[] = $ward;
        }
    }

    /* ##### MESSAGE PASSED VIA THE URL ######## */
    $statusMessage = '';
    $statusType = '';
    if (isset($_GET['status'])) {
        if ($_GET['status'] === 'success') {
            $statusMessage = 'Profile updated successfully!';
            $statusType = 'successNotification';
        } elseif ($_GET['status'] === 'pwd_success') {
            $statusMessage = 'Password updated successfully!';
            $statusType = 'successNotification';
        } elseif (isset($_GET['msg'])) {
            $statusMessage = htmlspecialchars($_GET['msg']);
            $statusType = 'errorNotification';
        }
    }
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Profile settings</title>
    <link rel="stylesheet" href="settings.css">

    <link
        href="https://fonts.googleapis.com/css2?family=Lora:wght@400;500;600;700&family=Ubuntu:wght@300;400;500;700&display=swap"
        rel="stylesheet"
    >

    <link
        href="https://fonts.googleapis.com/icon?family=Material+Icons"
        rel="stylesheet"
    >
    
    <link href="residence.css">
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
a {
    font-family: inherit;
}


/* ================= CONTAINER ================= */

.container {
    min-height: 100vh;
}


/* ================= SIDEBAR ================= */

.sidebar {
    position: fixed;
    top: 0;
    left: 0;

    width: 270px;
    height: 100vh;

    background: #4b561b;

    color: white;

    padding: 20px 16px;

    overflow-y: auto;

    z-index: 1000;

    transition:
        width 0.3s ease,
        transform 0.3s ease;
}


.sidebar::-webkit-scrollbar {
    display: none;
}


/* ================= SIDEBAR HEADER ================= */

.sidebar-header {
    min-height: 82px;

    display: flex;
    align-items: center;
    justify-content: space-between;

    gap: 15px;

    padding: 0 6px 18px;

    border-bottom:
        1px solid rgba(255, 255, 255, 0.18);
}


.logo-link {
    display: flex;
    align-items: center;

    min-width: 0;
}


.sidebar-logo {
    width: 145px;
    max-height: 68px;

    object-fit: contain;

    display: block;
}


/* ================= HAMBURGER ================= */

.menu-btn {
    width: 42px;
    height: 42px;

    flex-shrink: 0;

    border: none;

    border-radius: 8px;

    background:
        rgba(255, 255, 255, 0.12);

    display: flex;
    flex-direction: column;
    justify-content: center;
    align-items: center;

    gap: 5px;

    cursor: pointer;

    transition: 0.2s ease;
}


.menu-btn:hover {
    background:
        rgba(255, 255, 255, 0.22);
}


.menu-btn span {
    width: 20px;
    height: 2px;

    display: block;

    background: white;

    border-radius: 10px;

    transition: 0.3s ease;
}


/* hamburger becomes x */

.sidebar.menu-collapsed
.menu-btn span:nth-child(1) {
    transform:
        translateY(7px)
        rotate(45deg);
}


.sidebar.menu-collapsed
.menu-btn span:nth-child(2) {
    opacity: 0;
}


.sidebar.menu-collapsed
.menu-btn span:nth-child(3) {
    transform:
        translateY(-7px)
        rotate(-45deg);
}


/* ================= PROFILE ================= */

.sidebar-profile {
    text-align: center;

    padding: 25px 8px;

    border-bottom:
        1px solid rgba(255, 255, 255, 0.18);
}


.sidebar-profile img {
    width: 78px;
    height: 78px;

    object-fit: cover;

    border-radius: 50%;

    border: 4px solid
        rgba(255, 255, 255, 0.25);
}


.sidebar-profile h3 {
    margin-top: 12px;

    color: white;

    font-size: 16px;
}


.sidebar-profile p {
    margin-top: 5px;

    color:
        rgba(255, 255, 255, 0.75);

    font-size: 13px;
}


.sidebar-profile span {
    display: block;

    margin-top: 5px;

    color: #f4f0e7;

    font-size: 12px;

    font-weight: bold;
}


/* ================= MENU ================= */

.menu {
    list-style: none;

    margin-top: 18px;
}


.menu li {
    list-style: none;
}


.menu li:not(.menu-title) {
    margin-bottom: 5px;
}


.menu li:not(.menu-title) a {
    min-height: 46px;

    display: flex;
    align-items: center;

    gap: 13px;

    padding: 11px 13px;

    border-radius: 8px;

    color:
        rgba(255, 255, 255, 0.85);

    font-size: 13px;

    transition: 0.2s ease;
}


.menu li:not(.menu-title) a:hover {
    background:
        rgba(255, 255, 255, 0.12);

    color: white;
}


.menu li.active a {
    background:
        rgba(255, 255, 255, 0.18);

    color: white;

    font-weight: bold;
}


.menu-symbol {
    width: 22px;

    flex-shrink: 0;

    text-align: center;

    font-size: 18px;
}


.menu-title {
    padding:
        18px
        12px
        8px;

    color:
        rgba(255, 255, 255, 0.55);

    font-size: 10px;

    font-weight: bold;

    letter-spacing: 1.2px;
}


.logout {
    margin-top: 16px;
}


/* ================= MAIN ================= */

.main-content {
    min-height: 100vh;

    margin-left: 270px;

    background: #f4f0e7;

    transition: margin-left 0.3s ease;
}


/* ================= TOPBAR ================= */

.topbar {
    min-height: 92px;

    background: #f4f0e7;

    display: flex;
    align-items: center;
    justify-content: space-between;

    gap: 30px;

    padding: 18px 32px;

    border-bottom: 1px solid #ded7ca;
}


.topbar-title h2 {
    color: #263238;

    font-size: 21px;
}


.topbar-title p {
    margin-top: 5px;

    color: #777;

    font-size: 12px;
}


.topbar-right {
    display: flex;
    align-items: center;

    gap: 25px;
}


.top-icon {
    display: flex;
    align-items: center;

    gap: 7px;

    color: #5e6256;

    font-size: 12px;
}


.top-icon:hover {
    color: #4b561b;
}


.top-icon-symbol {
    font-size: 18px;
}


.top-user {
    display: flex;
    align-items: center;

    gap: 11px;

    padding-left: 18px;

    border-left: 1px solid #d8d1c4;
}


.top-user img {
    width: 43px;
    height: 43px;

    object-fit: cover;

    border-radius: 50%;

    border: 3px solid white;
}


.top-user h4 {
    color: #263238;

    font-size: 13px;
}


.top-user small {
    display: block;

    margin-top: 3px;

    color: #777;

    font-size: 11px;
}


/* ================= DASHBOARD CONTENT ================= */

.dashboard-content {
    padding: 32px;
}


/* ================= WELCOME ================= */

.dashboard-welcome {
    margin-bottom: 28px;
}


.welcome-label {
    margin-bottom: 7px;

    color: #7d8260 !important;

    font-size: 10px !important;

    font-weight: bold;

    letter-spacing: 1.5px;
}


.dashboard-welcome h1 {
    color: #263238;

    font-size: 29px;
}


.dashboard-welcome > p:last-child {
    margin-top: 7px;

    color: #777;

    font-size: 13px;
}


/* ================= CARDS ================= */

.cards {
    display: grid;

    grid-template-columns:
        repeat(3, minmax(0, 1fr));

    gap: 18px;

    margin-bottom: 22px;
}


.card {
    min-height: 125px;

    background: white;

    border-radius: 10px;

    padding: 20px;

    display: flex;
    align-items: center;

    gap: 16px;

    border: 1px solid #ece7dd;

    box-shadow:
        0 4px 14px
        rgba(52, 55, 42, 0.05);
}


.card-icon {
    width: 48px;
    height: 48px;

    flex-shrink: 0;

    border-radius: 8px;

    background: #eef0e3;

    color: #4b561b;

    display: flex;
    align-items: center;
    justify-content: center;

    font-size: 22px;
}


.card-information p {
    color: #666;

    font-size: 12px;

    font-weight: bold;
}


.card-information h2 {
    margin-top: 4px;

    color: #263238;

    font-size: 27px;
}


.card-information span {
    display: block;

    margin-top: 4px;

    color: #999;

    font-size: 10px;
}


/* ================= DASHBOARD GRID ================= */

.dashboard-grid {
    display: grid;

    grid-template-columns:
        minmax(0, 1.6fr)
        minmax(280px, 0.8fr);

    gap: 20px;

    margin-bottom: 22px;
}


/* ================= PANELS ================= */

.dashboard-panel {
    background: white;

    border: 1px solid #ece7dd;

    border-radius: 10px;

    padding: 22px;

    box-shadow:
        0 4px 14px
        rgba(52, 55, 42, 0.05);
}


.panel-header {
    display: flex;
    align-items: center;
    justify-content: space-between;

    gap: 15px;

    padding-bottom: 16px;

    margin-bottom: 8px;

    border-bottom: 1px solid #eee9e0;
}


.panel-header h3 {
    color: #263238;

    font-size: 16px;
}


.panel-header p {
    margin-top: 4px;

    color: #999;

    font-size: 10px;
}


.view-all-btn,
.view-link {
    color: #4b561b;

    font-size: 11px;

    font-weight: bold;
}


.view-all-btn:hover,
.view-link:hover {
    text-decoration: underline;
}


/* ================= TICKETS ================= */

.ticket {
    display: flex;
    align-items: center;

    gap: 13px;

    padding: 15px 0;

    border-bottom: 1px solid #eee;
}


.ticket:last-child {
    border-bottom: none;
}


.ticket-icon {
    width: 40px;
    height: 40px;

    flex-shrink: 0;

    border-radius: 8px;

    background: #eef0e3;

    color: #4b561b;

    display: flex;
    align-items: center;
    justify-content: center;
}


.ticket-info {
    min-width: 0;

    flex: 1;
}


.ticket-info h4 {
    color: #263238;

    font-size: 12px;
}


.ticket-info p {
    margin-top: 4px;

    color: #555;

    font-size: 12px;

    line-height: 1.4;
}


.ticket-info small {
    display: block;

    margin-top: 5px;

    color: #999;

    font-size: 10px;
}


.clickable-ticket {
    cursor: pointer;

    transition: 0.2s ease;
}


.clickable-ticket:hover {
    padding-left: 8px;

    padding-right: 8px;

    background: #faf9f5;

    border-radius: 8px;
}


/* ================= STATUS ================= */

.status {
    padding: 5px 9px;

    border-radius: 20px;

    font-size: 10px;

    white-space: nowrap;
}


.open-status {
    background: #fbe7e5;
    color: #b84a43;
}


.progress-status {
    background: #fff1d8;
    color: #a46b08;
}


.resolved-status {
    background: #e3f1df;
    color: #4f7c48;
}


/* ================= EMPTY STATE ================= */

.empty-state {
    min-height: 110px;

    display: flex;
    align-items: center;
    justify-content: center;

    text-align: center;
}


.empty-state p {
    color: #999;

    font-size: 12px;
}


/* ================= NOTICE ================= */

.notice-alert {
    display: flex;

    gap: 13px;

    padding: 16px;

    background: #f9f4e8;

    border-left: 4px solid #8b5a2b;

    border-radius: 7px;

    margin-top: 16px;
}


.notice-icon {
    width: 31px;
    height: 31px;

    flex-shrink: 0;

    display: flex;
    align-items: center;
    justify-content: center;

    border-radius: 50%;

    background: #eadfce;

    color: #8b5a2b;

    font-weight: bold;
}


.notice-alert h4 {
    color: #263238;

    font-size: 13px;

    margin-bottom: 7px;
}


.notice-alert p {
    color: #777;

    font-size: 11px;

    line-height: 1.55;
}


.notice-date {
    margin-top: 15px;

    color: #999;

    font-size: 10px;
}


.view-link {
    display: inline-block;

    margin-top: 15px;
}


/* ================= WARD INFORMATION ================= */

.ward-panel {
    background: white;

    border: 1px solid #ece7dd;

    border-radius: 10px;

    padding: 22px;

    display: flex;
    align-items: center;

    gap: 18px;

    margin-bottom: 22px;

    box-shadow:
        0 4px 14px
        rgba(52, 55, 42, 0.05);
}


.ward-icon {
    width: 50px;
    height: 50px;

    flex-shrink: 0;

    border-radius: 8px;

    background: #eef0e3;

    color: #4b561b;

    display: flex;
    align-items: center;
    justify-content: center;

    font-size: 22px;
}


.ward-content {
    flex: 1;
}


.ward-content h3 {
    color: #263238;

    font-size: 15px;

    margin-bottom: 7px;
}


.ward-content p {
    margin-top: 4px;

    color: #777;

    font-size: 11px;

    line-height: 1.45;
}


.ward-button {
    flex-shrink: 0;

    background: #8b5a2b;

    color: white;

    padding: 11px 16px;

    border-radius: 7px;

    font-size: 11px;

    transition: 0.2s ease;
}


.ward-button:hover {
    background: #70451f;
}


/* ================= REPORT FAULT ================= */

.report-section {
    background: #e9e1d4;

    border-radius: 10px;

    padding: 22px;

    display: flex;
    align-items: center;
    justify-content: space-between;

    gap: 20px;

    margin-bottom: 28px;
}


.report-section h2 {
    color: #4b561b;

    font-size: 17px;
}


.report-section p {
    margin-top: 6px;

    color: #6e6a61;

    font-size: 11px;
}


.report-button {
    flex-shrink: 0;

    display: inline-block;

    border: none;

    background: #8b5a2b;

    color: white;

    padding: 12px 19px;

    border-radius: 7px;

    cursor: pointer;

    font-size: 11px;

    transition: 0.2s ease;
}


.report-button:hover {
    background: #70451f;
}


/* ================= FOOTER ================= */

footer {
    background: #30352f;

    color:
        rgba(255, 255, 255, 0.75);

    text-align: center;

    padding: 20px;

    font-size: 11px;
}


/* ================= COLLAPSED SIDEBAR ================= */

.sidebar.menu-collapsed {
    width: 82px;
}


.sidebar.menu-collapsed
.sidebar-logo {
    display: none;
}


.sidebar.menu-collapsed
.sidebar-header {
    justify-content: center;
}


.sidebar.menu-collapsed
.sidebar-profile img {
    width: 46px;
    height: 46px;
}


.sidebar.menu-collapsed
.profile-information {
    display: none;
}


.sidebar.menu-collapsed
.menu-title {
    display: none;
}


.sidebar.menu-collapsed
.menu li:not(.menu-title) a {
    justify-content: center;

    padding-left: 8px;
    padding-right: 8px;
}


.sidebar.menu-collapsed
.menu li:not(.menu-title) a span:last-child {
    display: none;
}


.sidebar.menu-collapsed
.menu-symbol {
    width: auto;
}


.main-content.sidebar-collapsed {
    margin-left: 82px;
}


/* ================= MODAL ================= */

.modal-overlay {
    display: none;

    position: fixed;

    top: 0;
    left: 0;

    width: 100%;
    height: 100%;

    background:
        rgba(0, 0, 0, 0.55);

    z-index: 3000;

    justify-content: center;
    align-items: center;

    padding: 20px;
}


.modal-card {
    position: relative;

    width: 100%;
    max-width: 600px;

    max-height: 90vh;

    background: white;

    border-radius: 12px;

    overflow-y: auto;

    box-shadow:
        0 10px 30px
        rgba(0, 0, 0, 0.25);

    animation:
        modalFadeIn
        0.25s ease-out;
}


@keyframes modalFadeIn {

    from {
        opacity: 0;
        transform: translateY(15px);
    }

    to {
        opacity: 1;
        transform: translateY(0);
    }
}


.modal-close-btn {
    position: absolute;

    top: 12px;
    right: 16px;

    z-index: 3010;

    width: 36px;
    height: 36px;

    border: none;

    border-radius: 50%;

    background:
        rgba(255, 255, 255, 0.9);

    color: #333;

    display: flex;
    align-items: center;
    justify-content: center;

    font-size: 24px;

    cursor: pointer;

    box-shadow:
        0 2px 6px
        rgba(0, 0, 0, 0.2);
}


.modal-close-btn:hover {
    background: white;
    color: #b84a43;
}


#modalMap {
    width: 100%;
    height: 250px;

    background: #e5e5e5;

    border-radius:
        12px
        12px
        0
        0;
}


.modal-body {
    padding: 24px;
}


.modal-header-row {
    display: flex;
    justify-content: space-between;
    align-items: flex-start;

    gap: 15px;

    margin-bottom: 16px;
}


.modal-header-row h3 {
    color: #263238;

    font-size: 20px;
}


#modalServiceType {
    display: inline-block;

    margin-top: 4px;

    color: #666;

    font-size: 13px;

    font-weight: bold;
}


.detail-group {
    margin-bottom: 14px;
}


.detail-group label {
    display: block;

    margin-bottom: 4px;

    color: #718096;

    font-size: 10px;

    font-weight: bold;

    text-transform: uppercase;

    letter-spacing: 0.5px;
}


.detail-group p {
    color: #2d3748;

    font-size: 13px;

    line-height: 1.45;
}


/* ================= TABLET ================= */

@media (max-width: 1050px) {

    .top-icon span:last-child {
        display: none;
    }


    .dashboard-grid {
        grid-template-columns: 1fr;
    }

}


/* ================= MOBILE ================= */

@media (max-width: 800px) {

    .sidebar {
        width: 270px;

        transform:
            translateX(-100%);
    }


    .sidebar.mobile-open {
        transform:
            translateX(0);
    }


    .sidebar.menu-collapsed {
        width: 270px;
    }


    .sidebar.menu-collapsed
    .sidebar-logo {
        display: block;
    }


    .sidebar.menu-collapsed
    .sidebar-header {
        justify-content:
            space-between;
    }


    .sidebar.menu-collapsed
    .profile-information {
        display: block;
    }


    .sidebar.menu-collapsed
    .menu-title {
        display: block;
    }


    .sidebar.menu-collapsed
    .menu li:not(.menu-title) a {
        justify-content:
            flex-start;

        padding:
            11px
            13px;
    }


    .sidebar.menu-collapsed
    .menu li:not(.menu-title)
    a span:last-child {
        display: inline;
    }


    .sidebar.menu-collapsed
    .menu-symbol {
        width: 22px;
    }


    .main-content,
    .main-content.sidebar-collapsed {
        margin-left: 0;
    }


    .topbar {
        padding:
            15px
            20px;
    }


    .dashboard-content {
        padding: 22px;
    }


    .cards {
        grid-template-columns: 1fr;
    }


    .ward-panel {
        align-items: flex-start;

        flex-wrap: wrap;
    }


    .ward-button {
        margin-left: 68px;
    }


    .report-section {
        align-items: flex-start;

        flex-direction: column;
    }

}


/* ================= SMALL MOBILE ================= */

@media (max-width: 600px) {

    .topbar {
        min-height: 80px;
    }


    .topbar-title p {
        display: none;
    }


    .topbar-right {
        gap: 13px;
    }


    .top-user {
        padding-left: 10px;
    }


    .top-user div {
        display: none;
    }


    .top-icon span:last-child {
        display: none;
    }


    .dashboard-content {
        padding: 18px;
    }


    .dashboard-welcome h1 {
        font-size: 24px;
    }


    .ticket {
        align-items: flex-start;

        flex-wrap: wrap;
    }


    .status {
        margin-left: 53px;
    }


    .ward-panel {
        flex-direction: column;
    }


    .ward-button {
        margin-left: 0;
    }

}
        
* {
    margin: 0;
    padding: 0;
    box-sizing: border-box;
}


:root {
    --sidebar-width: 270px;
    --sidebar-collapsed-width: 85px;

    --green: #4b561b;
    --cream: #f4f0e7;
    --teal: #244f4b;
    --brown: #955b24;
    --text: #263238;
    --white: #ffffff;
    --border: #e4e0d7;
}


/* =========================================
   BODY
========================================= */

body {
    min-height: 100vh;

    font-family: "Ubuntu", sans-serif;

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
    font-family: "Ubuntu", sans-serif;
}


/* =========================================
   DASHBOARD
========================================= */

.dashboard-container {
    width: 100%;
    min-height: 100vh;
}


/* =========================================
   SIDEBAR
========================================= */

.sidebar {
    position: fixed;

    top: 0;
    left: 0;
    bottom: 0;

    width: var(--sidebar-width);
    min-height: 100vh;

    padding: 30px 18px;

    background: var(--green);
    color: var(--white);

    z-index: 1000;

    transition: width 0.3s ease;
}


/* =========================================
   SIDEBAR PROFILE
========================================= */

.sidebar-profile {
    display: flex;
    align-items: center;

    gap: 13px;

    padding: 5px 7px 25px;

    margin-bottom: 20px;

    border-bottom: 1px solid rgba(255, 255, 255, 0.2);

    overflow: hidden;
}


.sidebar-avatar {
    width: 48px;
    height: 48px;
    min-width: 48px;

    display: flex;
    align-items: center;
    justify-content: center;

    border-radius: 50%;

    background: rgba(255, 255, 255, 0.15);

    overflow: hidden;
}


.sidebar-avatar img {
    width: 100%;
    height: 100%;

    object-fit: cover;
}


.sidebar-avatar .material-icons {
    font-size: 42px;

    color: var(--white);
}


.sidebar-profile-info {
    display: flex;
    flex-direction: column;

    min-width: 0;
}


.sidebar-profile-info strong {
    font-size: 14px;
    font-weight: 600;

    color: var(--white);

    white-space: nowrap;
}


.sidebar-profile-info span {
    margin-top: 3px;

    font-size: 11px;

    color: rgba(255, 255, 255, 0.75);
}


/* =========================================
   SIDEBAR MENU
========================================= */

.sidebar-menu {
    display: flex;
    flex-direction: column;

    gap: 8px;
}


.sidebar-link {
    min-height: 48px;

    display: flex;
    align-items: center;

    gap: 15px;

    padding: 11px 14px;

    border-radius: 8px;

    color: rgba(255, 255, 255, 0.85);

    font-size: 14px;
    font-weight: 500;

    white-space: nowrap;

    transition:
        background 0.2s ease,
        color 0.2s ease;
}


.sidebar-link:hover {
    background: rgba(255, 255, 255, 0.12);

    color: var(--white);
}


.sidebar-link.active {
    background: rgba(255, 255, 255, 0.18);

    color: var(--white);
}


/* =========================================
   SIDEBAR ICONS
========================================= */

.sidebar-icon {
    width: 24px;
    height: 24px;
    min-width: 24px;

    object-fit: contain;

    filter: brightness(0) invert(1);
}


.sidebar-material-icon {
    width: 24px;
    min-width: 24px;

    font-size: 24px;

    text-align: center;

    color: var(--white);
}


/* =========================================
   COLLAPSED SIDEBAR
========================================= */

.sidebar.collapsed {
    width: var(--sidebar-collapsed-width);
}


.sidebar.collapsed .sidebar-profile {
    justify-content: center;

    padding-left: 0;
    padding-right: 0;
}


.sidebar.collapsed .sidebar-profile-info {
    display: none;
}


.sidebar.collapsed .sidebar-link {
    justify-content: center;

    padding-left: 0;
    padding-right: 0;
}


.sidebar.collapsed .sidebar-link > span:not(.material-icons) {
    display: none;
}


/* =========================================
   MAIN WRAPPER
========================================= */

.main-wrapper {
    width: calc(100% - var(--sidebar-width));

    min-height: 100vh;

    margin-left: var(--sidebar-width);

    transition:
        margin-left 0.3s ease,
        width 0.3s ease;
}


.main-wrapper.sidebar-collapsed {
    width: calc(100% - var(--sidebar-collapsed-width));

    margin-left: var(--sidebar-collapsed-width);
}


/* =========================================
   TOPBAR
========================================= */

.topbar {
    position: sticky;

    top: 0;

    width: 100%;
    height: 95px;

    display: flex;
    align-items: center;
    justify-content: space-between;

    padding: 0 35px;

    background: var(--white);

    border-bottom: 1px solid var(--border);

    z-index: 900;
}


.topbar-left,
.topbar-right {
    display: flex;
    align-items: center;
}


.topbar-left {
    gap: 20px;
}


.topbar-right {
    gap: 28px;
}


/* =========================================
   HAMBURGER MENU
========================================= */

.menu-btn {
    width: 42px;
    height: 42px;

    padding: 0;

    display: flex;
    align-items: center;
    justify-content: center;

    border: none;
    border-radius: 0;

    background: transparent;

    color: var(--text);

    cursor: pointer;
}


.menu-btn:hover {
    background: transparent;

    color: var(--green);
}


.menu-btn .material-icons {
    font-size: 29px;
}


/* =========================================
   M-LINK LOGO
========================================= */

.logo-link {
    display: flex;
    align-items: center;
}


.topbar-logo {
    width: 64px;
    height: 64px;

    display: block;

    object-fit: contain;
}


/* =========================================
   TOPBAR BUTTONS
========================================= */

.topbar-icon-btn {
    display: flex;
    align-items: center;

    gap: 8px;

    color: var(--text);

    font-size: 12px;
    font-weight: 500;

    white-space: nowrap;

    transition: color 0.2s ease;
}


.topbar-icon-btn:hover {
    color: var(--brown);
}


.topbar-icon-img {
    width: 26px;
    height: 26px;

    display: block;

    object-fit: contain;
}


/* =========================================
   NOTIFICATION
========================================= */

.notification-link {
    position: relative;

    display: flex;
    align-items: center;

    gap: 8px;

    color: var(--text);
}


.notification-icon-wrapper {
    position: relative;

    width: 28px;
    height: 28px;

    display: flex;
    align-items: center;
    justify-content: center;

    flex-shrink: 0;
}


.notification-icon-wrapper img {
    width: 26px;
    height: 26px;

    display: block;

    object-fit: contain;
}


.notification-badge {
    position: absolute;

    top: -8px;
    right: -9px;

    min-width: 19px;
    height: 19px;

    padding: 0 5px;

    display: flex;
    align-items: center;
    justify-content: center;

    border: 2px solid var(--white);
    border-radius: 20px;

    background: var(--brown);
    color: var(--white);

    font-size: 9px;
    font-weight: 700;

    line-height: 1;
}


.notification-text {
    font-size: 12px;
    font-weight: 500;

    color: var(--text);
}


.notification-link:hover .notification-text {
    color: var(--brown);
}


/* =========================================
   TOPBAR PROFILE
========================================= */

.topbar-profile {
    display: flex;
    align-items: center;

    gap: 10px;

    padding-left: 20px;

    border-left: 1px solid var(--border);

    color: var(--text);
}


.topbar-avatar {
    width: 42px;
    height: 42px;

    display: flex;
    align-items: center;
    justify-content: center;

    border-radius: 50%;

    background: #ece8df;

    overflow: hidden;
}


.topbar-avatar img {
    width: 100%;
    height: 100%;

    object-fit: cover;
}


.topbar-avatar .material-icons {
    font-size: 40px;

    color: var(--green);
}


.topbar-profile-info {
    display: flex;
    flex-direction: column;
}


.topbar-profile-info strong {
    font-size: 13px;
    font-weight: 600;

    color: var(--text);
}


.topbar-profile-info span {
    margin-top: 2px;

    font-size: 10px;

    color: #777777;
}


/* =========================================
   DASHBOARD CONTENT
========================================= */

.dashboard-content {
    width: 100%;

    padding: 35px;
}


/* =========================================
   DASHBOARD HEADING
========================================= */

.dashboard-heading {
    margin-bottom: 28px;
}


.dashboard-heading h1 {
    margin-bottom: 6px;

    font-family: "Lora", serif;

    font-size: 29px;
    font-weight: 600;

    color: var(--teal);
}


.dashboard-heading p {
    font-size: 13px;

    color: #6f7474;
}


/* =========================================
   STATISTIC CARDS
========================================= */

.cards {
    width: 100%;

    display: grid;

    grid-template-columns: repeat(3, minmax(0, 1fr));

    gap: 22px;

    margin-bottom: 30px;
}


.cards > .card {
    min-width: 0;
    min-height: 125px;

    display: flex;
    align-items: center;

    gap: 18px;

    padding: 23px;

    border: 1px solid var(--border);
    border-radius: 12px;

    background: var(--white);

    box-shadow: 0 3px 12px rgba(0, 0, 0, 0.04);
}


.card-icon {
    width: 54px;
    height: 54px;
    min-width: 54px;

    display: flex;
    align-items: center;
    justify-content: center;

    border-radius: 10px;

    background: #f1eee5;
}


.card-icon img {
    width: 29px;
    height: 29px;

    object-fit: contain;
}


.card-content {
    display: flex;
    flex-direction: column;

    gap: 6px;
}


.card-content span {
    font-size: 12px;
    font-weight: 500;

    color: #6f7474;
}


.card-content strong {
    font-family: "Lora", serif;

    font-size: 29px;
    font-weight: 600;

    color: var(--teal);
}


/* =========================================
   DASHBOARD PANELS
========================================= */

.dashboard-panel {
    width: 100%;

    padding: 25px;

    border: 1px solid var(--border);
    border-radius: 12px;

    background: var(--white);

    box-shadow: 0 3px 12px rgba(0, 0, 0, 0.04);
}


.panel-header {
    display: flex;
    align-items: center;
    justify-content: space-between;

    gap: 20px;

    margin-bottom: 22px;
}


.panel-header h2 {
    font-family: "Lora", serif;

    font-size: 20px;
    font-weight: 600;

    color: var(--teal);
}


.panel-header a {
    color: var(--brown);

    font-size: 12px;
    font-weight: 600;
}


.panel-header a:hover {
    text-decoration: underline;
}


/* =========================================
   RECENT TICKETS
========================================= */

.recent-tickets-panel {
    margin-bottom: 0;
}


.ticket-table-wrapper {
    width: 100%;

    overflow-x: auto;
}


.ticket-table {
    width: 100%;

    min-width: 760px;

    border-collapse: collapse;
}


.ticket-table th {
    padding: 13px 12px;

    text-align: left;

    border-bottom: 1px solid var(--border);

    color: #747979;

    font-size: 11px;
    font-weight: 600;

    text-transform: uppercase;

    letter-spacing: 0.3px;
}


.ticket-table td {
    padding: 16px 12px;

    border-bottom: 1px solid #eeeae2;

    color: var(--text);

    font-size: 12px;

    vertical-align: middle;
}


.ticket-table tbody tr:last-child td {
    border-bottom: none;
}


.ticket-table tbody tr:hover {
    background: #faf8f3;
}


/* =========================================
   TICKET STATUS
========================================= */

.ticket-status {
    min-width: 82px;

    display: inline-flex;
    align-items: center;
    justify-content: center;

    padding: 6px 10px;

    border-radius: 20px;

    font-size: 10px;
    font-weight: 600;
}


.ticket-status.pending {
    background: #f4eee3;

    color: #8b642c;
}


.ticket-status.in-progress {
    background: #e7efe9;

    color: #4b561b;
}


.ticket-status.resolved {
    background: #e4f0e9;

    color: #24623d;
}


/* =========================================
   NOTICES + WARD SUMMARIES GRID
========================================= */

.dashboard-grid {
    width: 100%;

    display: grid;

    grid-template-columns: 1.35fr 1fr;

    gap: 22px;

    margin-top: 30px;

    align-items: start;
}


/* =========================================
   NOTICES
========================================= */

.notice-list {
    display: flex;
    flex-direction: column;
}


.notice-item {
    padding: 18px 0;

    border-bottom: 1px solid #eeeae2;
}


.notice-item:first-child {
    padding-top: 0;
}


.notice-item:last-child {
    padding-bottom: 0;

    border-bottom: none;
}


.notice-top {
    display: flex;
    align-items: flex-start;
    justify-content: space-between;

    gap: 15px;

    margin-bottom: 9px;
}


.notice-heading {
    min-width: 0;
}


.notice-scope {
    display: inline-block;

    margin-bottom: 6px;

    color: var(--brown);

    font-size: 10px;
    font-weight: 600;
}


.notice-item h3 {
    font-family: "Lora", serif;

    font-size: 15px;
    font-weight: 600;

    color: var(--teal);

    line-height: 1.4;
}


.notice-category {
    flex-shrink: 0;

    padding: 5px 9px;

    border-radius: 20px;

    background: #f1eee5;

    color: var(--green);

    font-size: 9px;
    font-weight: 600;
}


.notice-description {
    margin-bottom: 10px;

    color: #666d6d;

    font-size: 12px;

    line-height: 1.6;
}


.notice-date {
    display: flex;
    align-items: center;

    gap: 5px;

    color: #8a8d8d;

    font-size: 10px;
}


.notice-date .material-icons {
    font-size: 14px;
}


/* =========================================
   WARD SUMMARIES
========================================= */

.summary-list {
    display: flex;
    flex-direction: column;
}


.summary-item {
    display: flex;
    align-items: center;

    gap: 13px;

    padding: 15px 0;

    border-bottom: 1px solid #eeeae2;
}


.summary-item:first-child {
    padding-top: 0;
}


.summary-item:last-child {
    padding-bottom: 0;

    border-bottom: none;
}


.summary-icon {
    width: 42px;
    height: 42px;
    min-width: 42px;

    display: flex;
    align-items: center;
    justify-content: center;

    border-radius: 9px;

    background: #f1eee5;

    color: var(--green);
}


.summary-icon .material-icons {
    font-size: 22px;
}


.summary-content {
    flex: 1;

    min-width: 0;
}


.summary-content h3 {
    margin-bottom: 4px;

    font-family: "Lora", serif;

    font-size: 13px;
    font-weight: 600;

    color: var(--teal);

    line-height: 1.4;
}


.summary-content span {
    color: #8a8d8d;

    font-size: 10px;
}


.summary-view-btn {
    width: 34px;
    height: 34px;
    min-width: 34px;

    display: flex;
    align-items: center;
    justify-content: center;

    border-radius: 50%;

    color: var(--brown);

    transition: background 0.2s ease;
}


.summary-view-btn:hover {
    background: #f1eee5;
}


.summary-view-btn .material-icons {
    font-size: 19px;
}


/* =========================================
   EMPTY STATE
========================================= */

.empty-state {
    min-height: 160px;

    display: flex;
    flex-direction: column;

    align-items: center;
    justify-content: center;

    gap: 8px;

    color: #898d8d;

    text-align: center;
}


.empty-state .material-icons {
    font-size: 42px;

    color: #b2b4ae;
}


.empty-state p {
    font-size: 13px;
}


/* =========================================
   TABLET
========================================= */

@media (max-width: 1000px) {

    .cards {
        grid-template-columns: repeat(
            2,
            minmax(0, 1fr)
        );
    }


    .topbar-right {
        gap: 18px;
    }


    .topbar-icon-btn > span:not(.notification-text) {
        display: none;
    }


    .notification-text {
        display: none;
    }
}


/* =========================================
   NOTICES / SUMMARY BREAKPOINT
========================================= */

@media (max-width: 950px) {

    .dashboard-grid {
        grid-template-columns: 1fr;
    }
}



/* =========================================
   MOBILE
========================================= */

@media (max-width: 750px) {

    .sidebar {
        width: var(--sidebar-width);

        transform: translateX(-100%);

        transition: transform 0.3s ease;
    }


    .sidebar.mobile-open {
        transform: translateX(0);
    }


    .main-wrapper,
    .main-wrapper.sidebar-collapsed {
        width: 100%;

        margin-left: 0;
    }


    .topbar {
        height: 80px;

        padding: 0 18px;
    }


    .topbar-left {
        gap: 12px;
    }


    .topbar-right {
        gap: 14px;
    }


    .topbar-logo {
        width: 52px;
        height: 52px;
    }


    .topbar-profile-info {
        display: none;
    }


    .topbar-profile {
        padding-left: 12px;
    }


    .topbar-avatar {
        width: 37px;
        height: 37px;
    }


    .topbar-icon-btn > span,
    .notification-text {
        display: none;
    }


    .dashboard-content {
        padding: 25px 18px;
    }


    .dashboard-heading h1 {
        font-size: 25px;
    }


    .cards {
        grid-template-columns: 1fr;
    }


    .dashboard-panel {
        padding: 20px 15px;
    }
}

/* =========================================
   SMALL MOBILE
========================================= */

@media (max-width: 600px) {

    .notice-top {
        flex-direction: column;

        gap: 6px;
    }
}


@media (max-width: 480px) {

    .topbar {
        padding: 0 12px;
    }


    .topbar-right {
        gap: 10px;
    }


    .topbar-logo {
        width: 45px;
        height: 45px;
    }


    .menu-btn {
        width: 34px;
        height: 34px;
    }


    .menu-btn .material-icons {
        font-size: 26px;
    }


    .topbar-profile {
        padding-left: 8px;
    }


    .topbar-avatar {
        width: 34px;
        height: 34px;
    }


    .dashboard-content {
        padding: 20px 12px;
    }


    .cards > .card {
        padding: 18px;
    }


    .dashboard-panel {
        border-radius: 10px;
    }
}

/* =========================================
   PALETTE  (same values as residence.php)
========================================= */

:root {
    --green: #4b561b;
    --cream: #f4f0e7;
    --teal: #244f4b;
    --brown: #955b24;
    --text: #263238;
    --white: #ffffff;
    --border: #e4e0d7;

    --soft: #f1eee5;        /* hover / active backgrounds */
    --field: #faf8f3;       /* input backgrounds */
    --muted: #6f7474;       /* secondary text */
    --muted-light: #8a8d8d; /* hints / icons */
    --shadow: 0 3px 12px rgba(0, 0, 0, 0.04);
}

body {
    background-color: var(--cream);
    color: var(--text);
    font-family: 'Ubuntu', sans-serif;
    margin: 0;
    padding: 0;
}

.profileContainer {
    display: flex;
    gap: 30px;
    max-width: 2100px;
    margin: 20px auto;
    padding: 0 20px;
}

.settingsHeader {
    padding: 20px 20px 0 80px;
}

.settingsHeader h1 {
    color: var(--teal);
}

.notificationBanner {
    max-width: 1200px;
    margin: 15px auto -5px auto;
    padding: 12px 20px;
    border-radius: 8px;
    font-weight: 500;
    text-align: center;
}

.successNotification {
    background-color: #e4f0e9;
    color: #24623d;
    border: 1px solid #cfe3d6;
}

.errorNotification {
    background-color: #f8e7e4;
    color: #9b2c1f;
    border: 1px solid #efcdc7;
}

/* LEFT CARD */
.profileCardLeft {
    width: 280px;
    background: var(--white);
    border: 1px solid var(--border);
    border-radius: 20px;
    padding: 30px 20px;
    display: flex;
    flex-direction: column;
    align-items: center;
    box-shadow: var(--shadow);
}

.profileAvatarWrapper {
    position: relative;
    width: 110px;
    height: 110px;
    margin-bottom: 15px;
}

.profileAvatarWrapper img {
    width: 100%;
    height: 100%;
    border-radius: 50%;
    object-fit: cover;
}

.editBadge {
    position: absolute;
    bottom: 2px;
    right: 2px;
    background-color: var(--brown);
    color: var(--white);
    width: 28px;
    height: 28px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 12px;
    cursor: pointer;
    border: 2px solid var(--white);
}

.fileInputHidden {
    display: none;
}

.imageUploadActions {
    display: flex;
    gap: 10px;
    margin-bottom: 15px;
}

.btnSmall {
    padding: 8px 18px;
    font-size: 0.8rem;
}

.userName {
    font-size: 1.1rem;
    color: var(--teal);
    margin-bottom: 4px;
}

.userRole {
    font-size: 0.85rem;
    color: var(--muted-light);
    margin-bottom: 25px;
}

.profileNavMenu {
    width: 100%;
    display: flex;
    flex-direction: column;
    gap: 10px;
}

.profileNavMenu .navItem {
    display: flex;
    align-items: center;
    gap: 12px;
    padding: 12px 18px;
    border-radius: 25px;
    color: var(--muted);
    text-decoration: none;
    font-size: 0.9rem;
    transition: all 0.2s ease;
}

.profileNavMenu .navItem:hover {
    background-color: var(--soft);
    color: var(--teal);
}

.profileNavMenu .navItem.active {
    background-color: var(--soft);
    color: var(--teal);
    font-weight: 600;
}

/* RIGHT CARD */
.profileCardRight {
    flex: 1;
    background: var(--white);
    border: 1px solid var(--border);
    border-radius: 20px;
    padding: 35px 40px;
    box-shadow: var(--shadow);
}

.profileCardRight h2 {
    font-size: 1.3rem;
    color: var(--teal);
    margin-bottom: 25px;
}

.formGrid {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 20px;
}

.fullWidth {
    grid-column: span 2;
}

.inputGroup label {
    display: block;
    font-size: 0.85rem;
    color: var(--muted);
    margin-bottom: 8px;
}

.inputGroup input, 
.inputGroup select {
    width: 100%;
    padding: 12px 16px;
    background-color: var(--field);
    border: 1px solid var(--border);
    border-radius: 12px;
    font-size: 0.9rem;
    color: var(--text);
    outline: none;
    box-sizing: border-box;
}

/* outline is removed above, so show focus with the teal accent instead */
.inputGroup input:focus,
.inputGroup select:focus {
    border-color: var(--teal);
}

.readOnlyInput {
    background-color: var(--soft);
    color: var(--muted);
    cursor: not-allowed;
}

.verifiedTag {
    position: absolute;
    right: 15px;
    color: #24623d;
    font-size: 0.85rem;
    display: flex;
    align-items: center;
    gap: 5px;
}

.icon-input {
    position: relative;
    display: flex;
    align-items: center;
}

.icon-input i {
    position: absolute;
    right: 15px;
    color: var(--muted-light);
}

.formActions {
    display: flex;
    justify-content: flex-end;
    gap: 15px;
    margin-top: 30px;
}

.btnDiscard {
    background: transparent;
    border: 1px solid var(--teal);
    color: var(--teal);
    padding: 12px 30px;
    border-radius: 12px;
    font-weight: 600;
    cursor: pointer;
}

.btnDiscard:hover {
    background: var(--soft);
}

.btnSave {
    background: var(--teal);
    border: none;
    color: var(--white);
    padding: 12px 30px;
    border-radius: 12px;
    font-weight: 600;
    cursor: pointer;
}

.btnSave:hover {
    background: #1c3f3c;
}

/* TAB VISIBILITY */
.tabSection {
    display: block;
}

.hiddenSection {
    display: none;
}

/* MODAL POPUP STYLES */
.modalOverlay {
    position: fixed;
    top: 0;
    left: 0;
    width: 100vw;
    height: 100vh;
    background: rgba(38, 50, 56, 0.55);
    display: flex;
    align-items: center;
    justify-content: center;
    z-index: 1000;
}

.hiddenModal {
    display: none;
}

.modalContent {
    background: var(--white);
    padding: 30px;
    border-radius: 16px;
    width: 100%;
    max-width: 400px;
    box-shadow: 0 10px 25px rgba(0,0,0,0.1);
}

.modalContent h3 {
    margin-top: 0;
    color: var(--teal);
}

.modalContent p {
    font-size: 0.9rem;
    color: var(--muted);
    margin-bottom: 15px;
}

.modalInput {
    width: 100%;
    padding: 12px;
    background-color: var(--field);
    border: 1px solid var(--border);
    color: var(--text);
    border-radius: 10px;
    margin-bottom: 15px;
    box-sizing: border-box;
}

.modalInput:focus {
    outline: none;
    border-color: var(--teal);
}

.modalErrorText {
    color: #a8322a;
    font-size: 0.85rem;
    margin-top: -5px;
    margin-bottom: 15px;
}

.hiddenError {
    display: none;
}

.modalActions {
    display: flex;
    justify-content: flex-end;
    gap: 10px;
}
    </style>
</head>
<body>
<div class="dashboard-container">


    <!-- =====================================
         SIDEBAR
    ====================================== -->

    <aside
        class="sidebar"
        id="sidebar"
    >


        <div class="sidebar-profile">


            <div class="sidebar-avatar">

                <?php if ($profileImage !== ""): ?>

                    <img
                        src="<?php echo htmlspecialchars($profileImage); ?>"
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

                    <?php
                    echo htmlspecialchars(
                        trim(
                            $firstName . " " . $surname
                        )
                    );
                    ?>

                </strong>

                <span>
                    Resident
                </span>

            </div>


        </div>



        <nav class="sidebar-menu">


            <!-- DASHBOARD -->

            <a
                href="residence.php"
                class="sidebar-link"
                title="Dashboard"
            >

                <img
                    src="../icons/home.svg"
                    alt=""
                    class="sidebar-icon"
                >

                <span>
                    Dashboard
                </span>

            </a>



            <!-- REPORT FAULT -->

            <a
                href="reportFaultTicketForm.php"
                class="sidebar-link"
                title="Report Fault"
            >

                <span class="material-icons sidebar-material-icon">
                    report_problem
                </span>

                <span>
                    Report Fault
                </span>

            </a>



            <!-- MY TICKETS -->

            <a
                href="ticketDetails.php"
                class="sidebar-link"
                title="My Tickets"
            >

                <span class="material-icons sidebar-material-icon">
                    confirmation_number
                </span>

                <span>
                    My Tickets
                </span>

            </a>



            <!-- SETTINGS -->

            <a
                href="settings.php"
                class="sidebar-link active"
                title="Settings"
            >

                <span class="material-icons sidebar-material-icon">
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
                title="Logout"
            >

                <span class="material-icons sidebar-material-icon">
                    logout
                </span>

                <span>
                    Logout
                </span>

            </a>


        </nav>


    </aside>


    <!-- =====================================
         MAIN WRAPPER
    ====================================== -->

    <div
        class="main-wrapper"
        id="mainWrapper"
    >

    <header class="topbar">


            <div class="topbar-left">


                <!-- MENU -->

                <button
                    type="button"
                    class="menu-btn"
                    id="menuBtn"
                    title="Menu"
                >

                    <span class="material-icons">
                        menu
                    </span>

                </button>



                <!-- M-LINK LOGO -->

                
             <a
    href="../PUBLIC%20PAGES/homepage.php"
    class="logo-link"
    title="Go to M-LINK Homepage"

            >

                <img
                    src="mlinkLogo.png"
                    alt="M-LINK Logo"
                    class="sidebar-logo"
                >

            </a>


            </div>



            <div class="topbar-right">


                <!-- NOTIFICATIONS -->

                <a
                    href="notifications.php"
                    class="topbar-icon-btn notification-link"
                    title="Notifications"
                >


                    <div class="notification-icon-wrapper">


                        <img
                            src="../icons/add_alert.svg"
                            alt="Notifications"
                            class="topbar-icon-img"
                        >


                        <?php if ($unreadNotificationCount > 0): ?>

                            <span class="notification-badge">

                                <?php
                                echo $unreadNotificationCount > 99
                                    ? "99+"
                                    : $unreadNotificationCount;
                                ?>

                            </span>

                        <?php endif; ?>


                    </div>


                    <span class="notification-text">
                        Notifications
                    </span>


                </a>



                <!-- MESSAGES -->

                <a
                    href="messages.php"
                    class="topbar-icon-btn"
                    title="Messages"
                >

                    <img
                        src="../icons/message.svg"
                        alt="Messages"
                        class="topbar-icon-img"
                    >

                    <span>
                        Messages
                    </span>

                </a>



                <!-- PROFILE -->

                <a
                    href="settings.php"
                    class="topbar-profile"
                    title="Profile"
                >


                    <div class="topbar-avatar">

                        <?php if ($profileImage !== ""): ?>

                            <img
                                src="<?php echo htmlspecialchars($profileImage); ?>"
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
                            <?php
                            echo htmlspecialchars($firstName);
                            ?>
                        </strong>

                        <span>
                            Resident
                        </span>

                    </div>


                </a>


            </div>


        </header>

    <div class="settingsHeader">
        <h1>Profile</h1>
    </div>

    <?php if ($statusMessage): ?>
        <div class="notificationBanner <?php echo $statusType; ?>">
            <?php echo $statusMessage; ?>
        </div>
    <?php endif; ?>

    <div class="profileContainer">
        <!-- LEFT COLUMN -->
        <aside class="profileCardLeft">
            <div class="profileAvatarWrapper">
                <img src="<?php echo htmlspecialchars($userData['image_path'] ?? 'profile.jpg'); ?>" alt="Profile Picture" id="profileImage">
                <label for="imageUpload" class="editBadge">
                    <i class="fa-solid fa-pen"></i>
                </label>
            </div>

            <!--    STANDALONE FORM TO UPDATE THE PICTURE WITHOUT
                    NEEDING TO CONFIRM WITH THE PASSWORD 
            -->
            <form id="profileImageForm" action="updateProfile.php" method="POST" enctype="multipart/form-data" class="profileImageForm">
                <input type="hidden" name="action" value="update_profile_picture">
                <input type="file" id="imageUpload" name="profile_image" accept="image/*" class="fileInputHidden">

                <div id="imageUploadActions" class="imageUploadActions hiddenSection">
                    <button type="button" id="btnCancelImage" class="btnDiscard btnSmall">Cancel</button>
                    <button type="submit" id="btnSaveImage" class="btnSave btnSmall">Save Picture</button>
                </div>
            </form>
            
            <h3 class="userName" id="displayFullName">
                <?php echo htmlspecialchars(($userData['first_Name'] ?? '') . ' ' . ($userData['surname'] ?? '')); ?>
            </h3>
            <p class="userRole" id="displayRole">
                <?php echo htmlspecialchars($userData['User_role'] ?? 'User'); ?>
            </p>

            <nav class="profileNavMenu">
                <a href="#personal-info" class="navItem active" id="navPersonalInfo">
                    <i class="fa-regular fa-user"></i>
                    <span>Personal Information</span>
                </a>
                <a href="#login-password" class="navItem" id="navPassword">
                    <i class="fa-solid fa-lock"></i>
                    <span>Password</span>
                </a>
            </nav>
        </aside>

        <!-- ######## RIGHT COLUMN ######## -->
        <main class="profileCardRight">
            
            <!-- PERSONAL INFORMATION FORM -->
            <section id="personalInfoSection" class="tabSection">
                <h2>Personal Information</h2>
                <form id="profileForm" action="updateProfile.php" method="POST" enctype="multipart/form-data" class="settingsForm">
                    <input type="hidden" name="action" value="update_profile">

                    <div class="formGrid">
                        <div class="inputGroup">
                            <label for="first_Name">First Name</label>
                            <input type="text" id="first_Name" name="first_Name" value="<?php echo htmlspecialchars($userData['first_Name'] ?? ''); ?>">
                        </div>

                        <div class="inputGroup">
                            <label for="surname">Surname</label>
                            <input type="text" id="surname" name="surname" value="<?php echo htmlspecialchars($userData['surname'] ?? ''); ?>">
                        </div>

                        <div class="inputGroup fullWidth">
                            <label for="username">Username</label>
                            <div class="icon-input">
                                <input type="text" id="username" name="username" value="<?php echo htmlspecialchars($userData['username'] ?? ''); ?>" readonly class="readOnlyInput">
                                <span class="verifiedTag"><i class="fa-solid fa-circle-check"></i> Verified</span>
                            </div>
                        </div>

                        <div class="inputGroup fullWidth">
                            <label for="address">Address</label>
                            <input type="text" id="address" name="address" value="<?php echo htmlspecialchars($userData['address'] ?? ''); ?>">
                        </div>

                        <div class="inputGroup">
                            <label for="contact">Phone Number</label>
                            <input type="tel" id="contact" name="contact" value="<?php echo htmlspecialchars($userData['contact'] ?? ''); ?>">
                        </div>

                        <div class="inputGroup">
                            <label for="ward_id">Ward / Location</label>
                            <select id="ward_id" name="ward_id">
                                <option value="">Select Ward...</option>
                                <?php foreach ($wards as $ward): ?>
                                    <option value="<?php echo $ward['ward_id']; ?>" <?php echo ($userData['ward_id'] == $ward['ward_id']) ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($ward['ward_name'] . ' (Ward ' . $ward['ward_number'] . ')'); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>

                    <!-- HIDDEN CONFIRM PASSWORD WINDOW -->
                    <input type="hidden" id="confirmPasswordInput" name="confirm_password">

                    <div class="formActions">
                        <button type="button" id="btnDiscardProfile" class="btnDiscard">Discard Changes</button>
                        <button type="button" id="btnTriggerModal" class="btnSave">Save Changes</button>
                    </div>
                </form>
            </section>

            <!-- CHANGE PASSWORD -->
            <section id="passwordSection" class="tabSection hiddenSection">
                <h2>Change Password</h2>
                <form id="passwordForm" action="updateProfile.php" method="POST" class="settingsForm">
                    <input type="hidden" name="action" value="change_password">
                    
                    <div class="formGrid">
                        <div class="inputGroup fullWidth">
                            <label for="newPassword">New Password</label>
                            <input type="password" id="newPassword" name="newPassword" required>
                        </div>

                        <div class="inputGroup fullWidth">
                            <label for="confirmNewPassword">Confirm New Password</label>
                            <input type="password" id="confirmNewPassword" name="confirmNewPassword" required>
                        </div>
                    </div>

                    <!-- HIDDEN CONFIRM PASSWORD FIELD (used by the popup, mirrors the profile form) -->
                    <input type="hidden" id="confirmPasswordInputPwd" name="confirm_password">

                    <div class="formActions">
                        <button type="button" id="btnDiscardPassword" class="btnDiscard">Cancel</button>
                        <button type="button" id="btnTriggerPasswordModal" class="btnSave">Update Password</button>
                    </div>
                </form>
            </section>

        </main>
    </div>

    </div><!-- /.main-wrapper -->

</div><!-- /.dashboard-container -->

    <!-- PASSWORD VERIFICATION POPUP -->
    <div id="passwordModal" class="modalOverlay hiddenModal">
        <div class="modalContent">
            <h3>Confirm Changes</h3>
            <p>Please enter your account password to confirm these changes.</p>
            <input type="password" id="modalConfirmPassword" class="modalInput" placeholder="Enter password">
            <p id="modalError" class="modalErrorText hiddenError">Please enter your password to continue.</p>
            <div class="modalActions">
                <button type="button" id="btnCancelModal" class="btnDiscard">Cancel</button>
                <button type="button" id="btnConfirmModal" class="btnSave">Confirm & Save</button>
            </div>
        </div>
    </div>

    <script src="residence.js"></script>
    <script src="profile_settings.js"></script>
    <script>
        document.addEventListener("DOMContentLoaded", () => {
    // DOM Elements
    const personalInfoSection = document.getElementById("personalInfoSection");
    const passwordSection = document.getElementById("passwordSection");
    const navPersonalInfo = document.getElementById("navPersonalInfo");
    const navPassword = document.getElementById("navPassword");

    const profileForm = document.getElementById("profileForm");
    const passwordForm = document.getElementById("passwordForm");
    const imageUploadInput = document.getElementById("imageUpload");
    const profileImage = document.getElementById("profileImage");
    
    const btnTriggerModal = document.getElementById("btnTriggerModal");
    const btnTriggerPasswordModal = document.getElementById("btnTriggerPasswordModal");
    const modal = document.getElementById("passwordModal");
    const modalConfirmBtn = document.getElementById("btnConfirmModal");
    const modalCancelBtn = document.getElementById("btnCancelModal");
    const modalPasswordInput = document.getElementById("modalConfirmPassword");
    const modalError = document.getElementById("modalError");
    const confirmPasswordInput = document.getElementById("confirmPasswordInput");
    const confirmPasswordInputPwd = document.getElementById("confirmPasswordInputPwd");

    // KEEP TRACK OF WHICH POPUP TO USE ONCE CONFIRMED => PROFILE OR PASSWORD
    let pendingAction = null;

    // CACHE INITIAL FORM VALUES BEFORE DISCARDING THE CHANGES
    const originalImageSrc = profileImage ? profileImage.src : "";
    const initialFormData = {
        firstName: document.getElementById("first_Name") ? document.getElementById("first_Name").value : "",
        surname: document.getElementById("surname") ? document.getElementById("surname").value : "",
        address: document.getElementById("address") ? document.getElementById("address").value : "",
        contact: document.getElementById("contact") ? document.getElementById("contact").value : "",
        ward_id: document.getElementById("ward_id") ? document.getElementById("ward_id").value : ""
    };

    // TAB NAVIGATION SWITCHING
    if (navPersonalInfo && navPassword) {
        navPersonalInfo.addEventListener("click", (e) => {
            e.preventDefault();
            navPersonalInfo.classList.add("active");
            navPassword.classList.remove("active");
            personalInfoSection.classList.remove("hiddenSection");
            passwordSection.classList.add("hiddenSection");
        });

        navPassword.addEventListener("click", (e) => {
            e.preventDefault();
            navPassword.classList.add("active");
            navPersonalInfo.classList.remove("active");
            passwordSection.classList.remove("hiddenSection");
            personalInfoSection.classList.add("hiddenSection");
        });
    }

    // REVIEW PROFILE PICTURE AND SAVE WITHOUT PASSWORD CONFIRMATION
    const profileImageForm = document.getElementById("profileImageForm");
    const imageUploadActions = document.getElementById("imageUploadActions");
    const btnCancelImage = document.getElementById("btnCancelImage");

    if (imageUploadInput && profileImage) {
        // UPLOAD AND READ THE IMAGE.
        imageUploadInput.addEventListener("change", (e) => {
            const file = e.target.files[0];
            if (file) {
                const reader = new FileReader();
                reader.onload = (event) => { 
                    profileImage.src = event.target.result;
                };
                reader.readAsDataURL(file);

                if (imageUploadActions) imageUploadActions.classList.remove("hiddenSection");
            }
        });
    }

    if (btnCancelImage) {
        // CANCEL THE UPDATE
        btnCancelImage.addEventListener("click", () => {
            if (imageUploadInput) imageUploadInput.value = "";
            if (profileImage) profileImage.src = originalImageSrc;
            if (imageUploadActions) imageUploadActions.classList.add("hiddenSection");
        });
    }

    // DISCARD CHANGES
    const btnDiscardProfile = document.getElementById("btnDiscardProfile");
    if (btnDiscardProfile) {
        btnDiscardProfile.addEventListener("click", () => {
            if (document.getElementById("first_Name")) document.getElementById("first_Name").value = initialFormData.firstName;
            if (document.getElementById("surname")) document.getElementById("surname").value = initialFormData.surname;
            if (document.getElementById("address")) document.getElementById("address").value = initialFormData.address;
            if (document.getElementById("contact")) document.getElementById("contact").value = initialFormData.contact;
            if (document.getElementById("ward_id")) document.getElementById("ward_id").value = initialFormData.ward_id;
            
            if (profileImage) profileImage.src = originalImageSrc;
            if (imageUploadInput) imageUploadInput.value = "";
        });
    }

    const btnDiscardPassword = document.getElementById("btnDiscardPassword");
    if (btnDiscardPassword && passwordForm) {
        btnDiscardPassword.addEventListener("click", () => {
            passwordForm.reset();
        });
    }

    // SHOW CONFIRMATION => PROFILE SAVE
    if (btnTriggerModal && modal) {
        btnTriggerModal.addEventListener("click", (e) => {
            e.preventDefault();
            pendingAction = "profile";
            if (modalPasswordInput) modalPasswordInput.value = "";
            if (modalError) modalError.classList.add("hiddenError");
            modal.classList.remove("hiddenModal");
            if (modalPasswordInput) modalPasswordInput.focus();
        });
    }

    // SHOW CONFIRMATION MODAL (PASSWORD UPDATE)
    if (btnTriggerPasswordModal && modal) {
        btnTriggerPasswordModal.addEventListener("click", (e) => {
            e.preventDefault();

            // CHECK IF THE NEW PASSWORDS MATCH
            const newPwd = document.getElementById("newPassword") ? document.getElementById("newPassword").value : "";
            const confirmPwd = document.getElementById("confirmNewPassword") ? document.getElementById("confirmNewPassword").value : "";
            if (newPwd !== confirmPwd) {
                alert("New passwords do not match.");
                return;
            }

            pendingAction = "password";
            if (modalPasswordInput) modalPasswordInput.value = "";
            if (modalError) modalError.classList.add("hiddenError");
            modal.classList.remove("hiddenModal");
            if (modalPasswordInput) modalPasswordInput.focus();
        });
    }

    // CLOSE MODAL (CANCEL BUTTON)
    if (modalCancelBtn && modal) {
        modalCancelBtn.addEventListener("click", () => {
            modal.classList.add("hiddenModal");
        });
    }

    // CLOSE MODAL (CLICK OUTSIDE OVERLAY)
    if (modal) {
        modal.addEventListener("click", (e) => {
            if (e.target === modal) {
                modal.classList.add("hiddenModal");
            }
        });
    }

    // CONFIRM PASSWORD & SUBMIT WHICHEVER FORM TRIGGERED THE MODAL
    if (modalConfirmBtn) {
        modalConfirmBtn.addEventListener("click", () => {
            const passwordValue = modalPasswordInput ? modalPasswordInput.value.trim() : "";
            
            if (!passwordValue) {
                if (modalError) modalError.classList.remove("hiddenError");
                return;
            }

            if (modal) {
                modal.classList.add("hiddenModal");
            }

            if (pendingAction === "profile") {
                if (confirmPasswordInput) confirmPasswordInput.value = passwordValue;
                if (profileForm) profileForm.submit();
            } else if (pendingAction === "password") {
                if (confirmPasswordInputPwd) confirmPasswordInputPwd.value = passwordValue;
                if (passwordForm) passwordForm.submit();
            }

            pendingAction = null;
        });
    }

    // ALLOW SUBMITTING MODAL WITH ENTER KEY
    if (modalPasswordInput) {
        modalPasswordInput.addEventListener("keypress", (e) => {
            if (e.key === "Enter") {
                e.preventDefault();
                if (modalConfirmBtn) modalConfirmBtn.click();
            }
        });
    }

    // CHANGE PASSWORD TAB FORM VALIDATION
    if (passwordForm) {
        passwordForm.addEventListener("submit", (e) => {
            const newPwd = document.getElementById("newPassword") ? document.getElementById("newPassword").value : "";
            const confirmPwd = document.getElementById("confirmNewPassword") ? document.getElementById("confirmNewPassword").value : "";

            if (newPwd !== confirmPwd) {
                e.preventDefault();
                alert("New passwords do not match.");
            }
        });
    }
});
    </script>
</body>
</html>