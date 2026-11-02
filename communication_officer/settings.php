<?php
 
session_start();
 
// check if user is logged in
if (!isset($_SESSION["username"])) {
    header("location: ../PUBLIC PAGES/login.php");
    exit;
}
 
require_once "Error404Connection.php";
 
function e($value)
{
    return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
}
 
// ---------------------------------------------------------------
// check role - this page is for communication officers only
// ---------------------------------------------------------------
$userRole = strtolower(
    trim(
        $_SESSION["user_role"]
        ?? $_SESSION["User_role"]
        ?? $_SESSION["role"]
        ?? ""
    )
);
 
if ($userRole !== "municipal_staff") {
    die("Access denied.");
}
 
$username = $_SESSION["username"];
 
 
/**
 * Display-name helpers: a login email/username with an "@" is never shown as the name.
 */
if (!function_exists('dashIsEmailLike')) {
    function dashIsEmailLike($value): bool
    {
        return strpos((string)$value, '@') !== false;
    }
}
if (!function_exists('dashPickDisplayName')) {
    function dashPickDisplayName(...$candidates): string
    {
        foreach ($candidates as $candidate) {
            $candidate = trim((string)$candidate);
            if ($candidate !== '' && !dashIsEmailLike($candidate)) {
                return $candidate;
            }
        }
        return '';
    }
}
 
 
// ---------------------------------------------------------------
// get communication officer information (with actual image path,
// not the profile_id)
// ---------------------------------------------------------------
$query = "
    SELECT
        u.username,
        u.first_Name,
        u.surname,
        u.contact,
        u.User_role,
        u.address,
        u.ward_id,
        u.verified,
        p.image_path
    FROM `user` u
    LEFT JOIN profile_images p
        ON u.profile_id = p.profile_id
    WHERE u.username = ?
    LIMIT 1
";
 
$userData = [];
 
$stmt = $conn->prepare($query);
 
if (!$stmt) {
    die("Database Error: " . $conn->error);
}
 
$stmt->bind_param("s", $username);
$stmt->execute();
$result = $stmt->get_result();
 
if ($result && $row = $result->fetch_assoc()) {
    $userData = $row;
}
 
$stmt->close();
 
 
// display name (falls back to username only if no real name on file)
$fullName = trim(
    ($userData["first_Name"] ?? "") . " " . ($userData["surname"] ?? "")
);
 
$loggedInUser = dashPickDisplayName(
    $fullName,
    $userData["first_Name"] ?? "",
    $userData["surname"] ?? "",
    $username
) ?: "Officer";
 
// this is the fix: the actual image path, never the profile_id
$userProfilePic = trim($userData["image_path"] ?? "");
 
 
// ---------------------------------------------------------------
// wards, for the ward / location dropdown
// ---------------------------------------------------------------
$wardQuery = "
    SELECT ward_id, ward_name, ward_number
    FROM ward
    ORDER BY ward_number ASC
";
 
$wardResult = $conn->query($wardQuery);
$wards = [];
 
if ($wardResult) {
    while ($ward = $wardResult->fetch_assoc()) {
        $wards[] = $ward;
    }
}
 
 
// ---------------------------------------------------------------
// unread notifications / messages, for the sidebar + topbar badges
// (same source tables the dashboard uses, so counts stay in sync)
// ---------------------------------------------------------------
$olUnreadNotifications = 0;
try {
    $notifStmt = $conn->prepare(
        "SELECT COUNT(*) AS unread_count
         FROM notification
         WHERE username = ?
         AND notification_type = 'ticket_update'
         AND is_read = 0"
    );
    if ($notifStmt) {
        $notifStmt->bind_param("s", $username);
        $notifStmt->execute();
        $notifResult = $notifStmt->get_result();
        if ($notifResult && ($notifRow = $notifResult->fetch_assoc())) {
            $olUnreadNotifications = (int)$notifRow["unread_count"];
        }
        $notifStmt->close();
    }
} catch (Throwable $notifError) {
    $olUnreadNotifications = 0;
}
 
$olUnreadMessages = 0;
try {
    $msgStmt = $conn->prepare(
        "SELECT COUNT(*) AS unread_count
         FROM internal_messages
         WHERE receiver_username = ?
         AND is_read = 0"
    );
    if ($msgStmt) {
        $msgStmt->bind_param("s", $username);
        $msgStmt->execute();
        $msgResult = $msgStmt->get_result();
        if ($msgResult && ($msgRow = $msgResult->fetch_assoc())) {
            $olUnreadMessages = (int)$msgRow["unread_count"];
        }
        $msgStmt->close();
    }
} catch (Throwable $msgError) {
    $olUnreadMessages = 0;
}
 
 
// ---------------------------------------------------------------
// status message passed through the URL after updateProfile.php redirects back
// ---------------------------------------------------------------
$statusMessage = "";
$statusType = "";
 
if (isset($_GET["status"])) {
    if ($_GET["status"] === "success") {
        $statusMessage = "Profile updated successfully!";
        $statusType = "successNotification";
    } elseif ($_GET["status"] === "pwd_success") {
        $statusMessage = "Password updated successfully!";
        $statusType = "successNotification";
    } elseif (isset($_GET["msg"])) {
        $statusMessage = htmlspecialchars($_GET["msg"], ENT_QUOTES, "UTF-8");
        $statusType = "errorNotification";
    }
}
 
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Profile | M-LINK Communication Officer</title>
 
    <link href="https://fonts.googleapis.com/css2?family=Lora:wght@400;500;600;700&family=Ubuntu:wght@300;400;500;700&display=swap" rel="stylesheet">
    <link href="https://fonts.googleapis.com/icon?family=Material+Icons" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
 
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
 
        body {
            min-height: 100vh;
            font-family: "Ubuntu", sans-serif;
            background: #f4f0e7;
            color: #263238;
        }
 
        a { text-decoration: none; }
 
        button, input, select, textarea {
            font-family: "Ubuntu", sans-serif;
        }
    </style>
 
    <style>
    /* =====================================================
       SAME SIDEBAR + TOPBAR AS communication_officer_dashboard.php
       (identical "ol-" prefixed classes so it looks and behaves
       exactly the same across pages)
    ===================================================== */
    :root {
        --ol-sidebar-width: 270px;
        --ol-sidebar-collapsed-width: 85px;
        --ol-green: #4b561b;
        --ol-brown: #955b24;
        --ol-text: #263238;
        --ol-white: #ffffff;
        --ol-border: #e4e0d7;
    }
 
    .ol-sidebar, .ol-sidebar *,
    .ol-topbar, .ol-topbar * { box-sizing: border-box; }
    .ol-sidebar a, .ol-topbar a { text-decoration: none; }
 
    .ol-sidebar {
        position: fixed;
        top: 0; left: 0; bottom: 0;
        width: var(--ol-sidebar-width);
        background: var(--ol-green);
        color: var(--ol-white);
        padding: 30px 18px;
        z-index: 1200;
        overflow-x: hidden;
        overflow-y: auto;
        font-family: "Ubuntu", sans-serif;
        transition: width 0.3s ease;
    }
    .ol-sidebar-profile {
        display: flex;
        align-items: center;
        gap: 13px;
        padding: 5px 7px 25px;
        margin-bottom: 20px;
        border-bottom: 1px solid rgba(255, 255, 255, 0.2);
        overflow: hidden;
    }
    .ol-sidebar-avatar {
        width: 48px; height: 48px; min-width: 48px;
        border-radius: 50%;
        background: rgba(255, 255, 255, 0.15);
        display: flex; align-items: center; justify-content: center;
        overflow: hidden;
    }
    .ol-sidebar-avatar img { width: 100%; height: 100%; object-fit: cover; }
    .ol-sidebar-avatar .material-icons { font-size: 42px; color: var(--ol-white); }
    .ol-sidebar-profile-info {
        display: flex; flex-direction: column; min-width: 0;
        transition: opacity 0.2s ease;
    }
    .ol-sidebar-profile-info strong {
        font-size: 14px; font-weight: 600; color: var(--ol-white);
        white-space: nowrap; overflow: hidden; text-overflow: ellipsis;
        max-width: 170px;
    }
    .ol-sidebar-profile-info span {
        margin-top: 3px; font-size: 11px; color: rgba(255, 255, 255, 0.75);
    }
    .ol-sidebar-menu { display: flex; flex-direction: column; gap: 8px; }
    .ol-sidebar-link {
        min-height: 48px;
        display: flex; align-items: center; gap: 15px;
        padding: 11px 14px;
        border-radius: 8px;
        color: rgba(255, 255, 255, 0.85);
        font-size: 14px; font-weight: 500;
        white-space: nowrap;
        transition: background 0.2s ease, color 0.2s ease;
        position: relative;
    }
    .ol-sidebar-link:hover  { background: rgba(255, 255, 255, 0.12); color: var(--ol-white); }
    .ol-sidebar-link.active { background: rgba(255, 255, 255, 0.18); color: var(--ol-white); }
    .ol-sidebar-icon {
        width: 24px; height: 24px; min-width: 24px;
        object-fit: contain;
        filter: brightness(0) invert(1);
    }
    .ol-sidebar-material-icon {
        width: 24px; min-width: 24px;
        font-size: 24px; text-align: center; color: var(--ol-white);
    }
 
    .ol-sidebar.collapsed { width: var(--ol-sidebar-collapsed-width); }
    .ol-sidebar.collapsed .ol-sidebar-profile { justify-content: center; padding-left: 0; padding-right: 0; }
    .ol-sidebar.collapsed .ol-sidebar-profile-info,
    .ol-sidebar.collapsed .ol-sidebar-link span:not(.material-icons) { display: none; }
    .ol-sidebar.collapsed .ol-sidebar-link { justify-content: center; padding-left: 0; padding-right: 0; }
 
    .ol-nav-badge {
        margin-left: auto;
        min-width: 20px; height: 20px; padding: 0 6px;
        display: flex; align-items: center; justify-content: center;
        background: var(--ol-brown); color: var(--ol-white);
        border-radius: 20px;
        font-size: 11px; font-weight: 700; line-height: 1;
    }
    .ol-sidebar.collapsed .ol-sidebar-link span.ol-nav-badge {
        display: flex; position: absolute; top: 5px; right: 8px;
        margin-left: 0; min-width: 17px; height: 17px; padding: 0 4px;
        font-size: 9px; box-shadow: 0 0 0 2px var(--ol-green);
    }
 
    .ol-sidebar-overlay {
        display: none;
        position: fixed; inset: 0;
        background: rgba(0, 0, 0, 0.45);
        z-index: 1190;
    }
 
    .ol-main-wrapper {
        min-height: 100vh;
        margin-left: var(--ol-sidebar-width);
        width: calc(100% - var(--ol-sidebar-width));
        transition: margin-left 0.3s ease, width 0.3s ease;
    }
    .ol-main-wrapper.sidebar-collapsed {
        margin-left: var(--ol-sidebar-collapsed-width);
        width: calc(100% - var(--ol-sidebar-collapsed-width));
    }
 
    .ol-topbar {
        width: 100%;
        height: 95px;
        background: var(--ol-white);
        display: flex; align-items: center; justify-content: space-between;
        padding: 0 35px;
        border-bottom: 1px solid var(--ol-border);
        position: sticky; top: 0;
        z-index: 1100;
        font-family: "Ubuntu", sans-serif;
        color: var(--ol-text);
    }
    .ol-topbar-left, .ol-topbar-right { display: flex; align-items: center; }
    .ol-topbar-left  { gap: 20px; }
    .ol-topbar-right { gap: 28px; }
 
    .ol-menu-btn {
        width: 42px; height: 42px; padding: 0;
        border: none; background: transparent; border-radius: 0;
        display: flex; align-items: center; justify-content: center;
        cursor: pointer; color: var(--ol-text);
    }
    .ol-menu-btn .material-icons { font-size: 29px; }
    .ol-menu-btn:hover { background: transparent; color: var(--ol-green); }
 
    .ol-topbar-logo-link { display: flex; align-items: center; }
    .ol-topbar-logo { width: 64px; height: 64px; object-fit: contain; display: block; }
 
    .ol-topbar-icon-btn {
        display: flex; align-items: center; gap: 8px;
        color: var(--ol-text); font-size: 12px; font-weight: 500;
        white-space: nowrap; transition: color 0.2s ease;
    }
    .ol-topbar-icon-btn:hover { color: var(--ol-brown); }
    .ol-topbar-icon-img { width: 26px; height: 26px; object-fit: contain; display: block; }
 
    .ol-notification-icon-wrapper {
        position: relative; width: 28px; height: 28px;
        display: flex; align-items: center; justify-content: center; flex-shrink: 0;
    }

    .backBtn {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    padding: 8px 16px;
    background-color: #ffffff;
    color: #1b442b;
    border: 1px solid #e2e0d8;
    border-radius: 8px;
    font-size: 13px;
    font-weight: 700;
    text-decoration: none;
    transition: all 0.2s ease;
    box-shadow: 0 2px 5px rgba(0,0,0,0.02);
}

.backBtn:hover {
    background-color: #1b442b;
    color: #ffffff;
    border-color: #1b442b;
}

    .ol-notification-icon-wrapper .material-icons { font-size: 26px; }
    .ol-notification-badge {
        position: absolute; top: -8px; right: -9px;
        min-width: 19px; height: 19px; padding: 0 5px;
        display: flex; align-items: center; justify-content: center;
        background: var(--ol-brown); color: var(--ol-white);
        border: 2px solid var(--ol-white); border-radius: 20px;
        font-family: "Ubuntu", sans-serif; font-size: 9px; font-weight: 700; line-height: 1;
    }
    .ol-notification-text { font-size: 12px; font-weight: 500; color: var(--ol-text); }
    .ol-topbar-icon-btn:hover .ol-notification-text { color: var(--ol-brown); }
 
    .ol-topbar-profile {
        display: flex; align-items: center; gap: 10px;
        padding-left: 20px;
        border-left: 1px solid var(--ol-border);
        color: var(--ol-text);
    }
    .ol-topbar-avatar { width: 42px; height: 42px; border-radius: 50%; overflow: hidden; flex-shrink: 0; }
    .ol-topbar-avatar img { width: 100%; height: 100%; object-fit: cover; display: block; }
    .ol-topbar-avatar .material-icons { font-size: 40px; color: var(--ol-green); }
    .ol-topbar-profile-info { display: flex; flex-direction: column; }
    .ol-topbar-profile-info strong { font-size: 13px; font-weight: 600; color: var(--ol-text); }
    .ol-topbar-profile-info span   { margin-top: 2px; font-size: 10px; color: #777; }
 
    @media (max-width: 1000px) {
        .ol-topbar-right { gap: 18px; }
        .ol-notification-text { display: none; }
    }
 
    @media (max-width: 750px) {
        .ol-sidebar { transform: translateX(-100%); transition: transform 0.3s ease; }
        .ol-sidebar.mobile-open { transform: translateX(0); }
        .ol-sidebar-overlay.show { display: block; }
        .ol-main-wrapper,
        .ol-main-wrapper.sidebar-collapsed { margin-left: 0; width: 100%; }
        .ol-topbar { height: 80px; padding: 0 18px; }
        .ol-topbar-left  { gap: 12px; }
        .ol-topbar-right { gap: 14px; }
        .ol-topbar-logo  { width: 52px; height: 52px; }
        .ol-topbar-profile-info { display: none; }
        .ol-topbar-profile { padding-left: 12px; }
        .ol-topbar-avatar  { width: 37px; height: 37px; }
    }
 
    @media (max-width: 480px) {
        .ol-topbar { padding: 0 12px; }
        .ol-topbar-right { gap: 10px; }
        .ol-topbar-logo  { width: 45px; height: 45px; }
        .ol-menu-btn { width: 34px; height: 34px; }
        .ol-menu-btn .material-icons { font-size: 26px; }
        .ol-topbar-profile { padding-left: 8px; }
        .ol-topbar-avatar  { width: 34px; height: 34px; }
    }
    </style>
 
    <style>
    /* =====================================================
       PROFILE PAGE CONTENT (self-contained, no external settings.css)
    ===================================================== */
    .profile-page-content { padding: 30px 35px 60px; }
 
    .settingsHeader h1 {
        font-family: "Lora", serif;
        font-size: 26px;
        color: var(--ol-text);
        margin-bottom: 18px;
    }
 
    .notificationBanner {
        padding: 12px 18px;
        border-radius: 8px;
        margin-bottom: 20px;
        font-size: 14px;
        font-weight: 500;
    }
    .successNotification { background: #e6f4ea; color: #1e7a34; border: 1px solid #b7e2c2; }
    .errorNotification    { background: #fbeaea; color: #a3261f; border: 1px solid #f1c3c0; }
 
    .profileContainer {
        display: grid;
        grid-template-columns: 300px 1fr;
        gap: 28px;
        align-items: start;
    }
    @media (max-width: 900px) {
        .profileContainer { grid-template-columns: 1fr; }
    }
 
    .profileCardLeft, .profileCardRight {
        background: var(--ol-white);
        border: 1px solid var(--ol-border);
        border-radius: 12px;
        padding: 28px 24px;
    }
    .profileCardLeft { text-align: center; }
 
    .profileAvatarWrapper {
        position: relative;
        width: 110px; height: 110px;
        margin: 0 auto 16px;
    }
    .profileAvatarWrapper img,
    .profileAvatarWrapper .avatarPlaceholder {
        width: 110px; height: 110px;
        border-radius: 50%;
        object-fit: cover;
        display: flex; align-items: center; justify-content: center;
        background: #eee;
        color: var(--ol-green);
        font-size: 70px;
    }
    .editBadge {
        position: absolute;
        bottom: 2px; right: 2px;
        width: 32px; height: 32px;
        border-radius: 50%;
        background: var(--ol-green);
        color: var(--ol-white);
        display: flex; align-items: center; justify-content: center;
        cursor: pointer;
        border: 2px solid var(--ol-white);
        font-size: 13px;
    }
    .fileInputHidden { display: none; }
 
    .imageUploadActions {
        display: flex; gap: 8px; justify-content: center;
        margin-bottom: 16px;
    }
 
    .userName { font-family: "Lora", serif; font-size: 18px; margin-top: 4px; }
    .userRole { font-size: 12px; color: #777; margin-top: 4px; margin-bottom: 18px; }
 
    .profileNavMenu { display: flex; flex-direction: column; gap: 6px; text-align: left; }
    .navItem {
        display: flex; align-items: center; gap: 10px;
        padding: 10px 12px; border-radius: 8px;
        color: var(--ol-text); font-size: 14px; font-weight: 500;
    }
    .navItem:hover { background: #f4f0e7; }
    .navItem.active { background: var(--ol-green); color: var(--ol-white); }
 
    .tabSection h2 { font-family: "Lora", serif; font-size: 19px; margin-bottom: 18px; }
    .hiddenSection { display: none; }
 
    .formGrid { display: grid; grid-template-columns: 1fr 1fr; gap: 16px 20px; margin-bottom: 20px; }
    .inputGroup.fullWidth { grid-column: 1 / -1; }
    .inputGroup label { display: block; font-size: 12px; font-weight: 600; margin-bottom: 6px; color: #555; }
    .inputGroup input, .inputGroup select {
        width: 100%; padding: 10px 12px;
        border: 1px solid var(--ol-border); border-radius: 8px;
        font-size: 14px; color: var(--ol-text);
    }
    .readOnlyInput { background: #f4f0e7; color: #777; }
    .icon-input { position: relative; display: flex; align-items: center; }
    .verifiedTag {
        position: absolute; right: 10px;
        font-size: 11px; color: #1e7a34; font-weight: 600;
        display: flex; align-items: center; gap: 4px;
    }
 
    .formActions { display: flex; justify-content: flex-end; gap: 10px; }
    .btnDiscard, .btnSave {
        padding: 10px 20px; border-radius: 8px; font-size: 13px; font-weight: 600;
        cursor: pointer; border: 1px solid var(--ol-border);
    }
    .btnDiscard { background: var(--ol-white); color: var(--ol-text); }
    .btnSave { background: var(--ol-green); color: var(--ol-white); border-color: var(--ol-green); }
    .btnSmall { padding: 6px 14px; font-size: 12px; }
 
    .modalOverlay {
        position: fixed; inset: 0; background: rgba(0,0,0,0.45);
        display: flex; align-items: center; justify-content: center;
        z-index: 2000;
    }
    .hiddenModal { display: none; }
    .modalContent {
        background: var(--ol-white); padding: 28px; border-radius: 12px;
        width: 320px; max-width: 90vw;
    }
    .modalContent h3 { font-family: "Lora", serif; margin-bottom: 10px; }
    .modalContent p { font-size: 13px; color: #555; margin-bottom: 14px; }
    .modalInput {
        width: 100%; padding: 10px 12px; border: 1px solid var(--ol-border);
        border-radius: 8px; margin-bottom: 8px; font-size: 14px;
    }
    .modalErrorText { color: #a3261f; font-size: 12px; margin-bottom: 8px; }
    .hiddenError { display: none; }
    .modalActions { display: flex; justify-content: flex-end; gap: 10px; margin-top: 10px; }
    </style>
</head>
 
<body>
 
<!-- =====================================
     SIDEBAR (same as communication_officer_dashboard.php)
====================================== -->
<aside class="ol-sidebar" id="olSidebar" aria-label="Main Navigation">
    <div class="ol-sidebar-profile">
        <div class="ol-sidebar-avatar">
            <?php if (!empty($userProfilePic)): ?>
                <img src="<?= e($userProfilePic) ?>" alt="Profile">
            <?php else: ?>
                <span class="material-icons">account_circle</span>
            <?php endif; ?>
        </div>
        <div class="ol-sidebar-profile-info">
            <strong><?= e($loggedInUser) ?></strong>
            <span>Communication Officer</span>
        </div>
    </div>
 
    <nav class="ol-sidebar-menu">
        <a href="communication_officer_dashboard.php" class="ol-sidebar-link">
            <img src="../icons/home.svg" alt="" class="ol-sidebar-icon"
                 onerror="var s=document.createElement('span');s.className='material-icons ol-sidebar-material-icon';s.textContent='home';this.replaceWith(s);">
            <span>Dashboard</span>
        </a>
 
        <a href="all_incidents.php" class="ol-sidebar-link">
            <span class="material-icons ol-sidebar-material-icon">confirmation_number</span>
            <span>View Incident Tickets</span>
        </a>
 
        <a href="manageNotices.php" class="ol-sidebar-link">
            <span class="material-icons ol-sidebar-material-icon">edit_note</span>
            <span>Create Notice</span>
        </a>
 
        <a href="viewnotices.php" class="ol-sidebar-link">
            <span class="material-icons ol-sidebar-material-icon">campaign</span>
            <span>View Notices</span>
        </a>
 
        <a href="messages.php" class="ol-sidebar-link">
            <span class="material-icons ol-sidebar-material-icon">chat</span>
            <span>Messages</span>
            <?php if ($olUnreadMessages > 0): ?>
                <span class="ol-nav-badge"><?= $olUnreadMessages > 99 ? "99+" : (int)$olUnreadMessages ?></span>
            <?php endif; ?>
        </a>
 
        <a href="../USER_DASHBOARD/reportFaultTicketForm.php" class="ol-sidebar-link">
            <span class="material-icons ol-sidebar-material-icon">report_problem</span>
            <span>Report an Incident</span>
        </a>
 
        <a href="fault_progress.php" class="ol-sidebar-link">
            <span class="material-icons ol-sidebar-material-icon">engineering</span>
            <span>Service Fault Progress</span>
        </a>
 
        <a href="settings.php" class="ol-sidebar-link active" aria-current="page">
            <span class="material-icons ol-sidebar-material-icon">settings</span>
            <span>Profile</span>
        </a>
 
        <a href="logout.php" class="ol-sidebar-link">
            <span class="material-icons ol-sidebar-material-icon">logout</span>
            <span>Logout</span>
        </a>
    </nav>
</aside>
<div class="ol-sidebar-overlay" id="olSidebarOverlay"></div>
 
<!-- MAIN PAGE -->
<div class="ol-main-wrapper" id="olMainWrapper">
 
    <!-- TOP BAR (same as communication_officer_dashboard.php) -->
    <header class="ol-topbar">
        <div class="ol-topbar-left">
            <button type="button" class="ol-menu-btn" id="olMenuBtn" aria-label="Toggle Navigation" aria-controls="olSidebar">
                <span class="material-icons">menu</span>
            </button>
 
            <a href="../PUBLIC%20PAGES/homepage.php" class="ol-topbar-logo-link" title="Go to M-LINK Homepage">
                <img src="../USER_DASHBOARD/mlinkLogo.png"
                     data-fb="mlinkLogo.png|../assets/images/mlinkLogo.png|../icons/mlinkLogo.png"
                     alt="M-LINK Logo" class="ol-topbar-logo"
                     onerror="var f=(this.dataset.fb||'').split('|').filter(Boolean);if(f.length){this.dataset.fb=f.slice(1).join('|');this.src=f[0];}">
            </a>
        </div>
 
        <div class="ol-topbar-right">
            <a href="../USER_DASHBOARD/notifications.php" class="ol-topbar-icon-btn" title="Notifications">
                <div class="ol-notification-icon-wrapper">
                    <img src="../icons/add_alert.svg" alt="Notifications" class="ol-topbar-icon-img"
                         onerror="var s=document.createElement('span');s.className='material-icons ';s.textContent='notifications';this.replaceWith(s);">
                    <?php if ($olUnreadNotifications > 0): ?>
                        <span class="ol-notification-badge"><?= $olUnreadNotifications > 99 ? "99+" : (int)$olUnreadNotifications ?></span>
                    <?php endif; ?>
                </div>
                <span class="ol-notification-text">Notifications</span>
            </a>
 
            <a href="settings.php" class="ol-topbar-profile">
                <div class="ol-topbar-avatar">
                    <?php if (!empty($userProfilePic)): ?>
                        <img src="<?= e($userProfilePic) ?>" alt="Profile Picture">
                    <?php else: ?>
                        <span class="material-icons">account_circle</span>
                    <?php endif; ?>
                </div>
                <div class="ol-topbar-profile-info">
                    <strong><?= e($loggedInUser) ?></strong>
                    <span>Communication Officer</span>
                </div>
            </a>
        </div>
    </header>
 
    <!-- =====================================
         PROFILE PAGE
    ====================================== -->
    <div class="profile-page-content">
 
        <div class="settingsHeader">
            <h1>Profile</h1>
        </div>
 
        <?php if ($statusMessage): ?>
            <div class="notificationBanner <?= $statusType ?>">
                <?= $statusMessage ?>
            </div>
        <?php endif; ?>
 
        <div class="profileContainer">
 
            <!-- LEFT COLUMN -->
            <aside class="profileCardLeft">
 
                <div class="profileAvatarWrapper">
                    <?php if (!empty($userProfilePic)): ?>
                        <img src="<?= e($userProfilePic) ?>" alt="Profile Picture" id="profileImage">
                    <?php else: ?>
                        <span class="material-icons avatarPlaceholder" id="profileImage">account_circle</span>
                    <?php endif; ?>
 
                    <label for="imageUpload" class="editBadge">
                        <i class="fa-solid fa-pen"></i>
                    </label>
                </div>
 
                <!-- PROFILE PICTURE FORM -->
                <form id="profileImageForm" action="updateProfile.php" method="POST" enctype="multipart/form-data" class="profileImageForm">
                    <input type="hidden" name="action" value="update_profile_picture">
                    <input type="file" id="imageUpload" name="profile_image" accept="image/*" class="fileInputHidden">
 
                    <div id="imageUploadActions" class="imageUploadActions hiddenSection">
                        <button type="button" id="btnCancelImage" class="btnDiscard btnSmall">Cancel</button>
                        <button type="submit" id="btnSaveImage" class="btnSave btnSmall">Save Picture</button>
                    </div>
                </form>
 
                <h3 class="userName" id="displayFullName"><?= e($loggedInUser) ?></h3>
                <p class="userRole" id="displayRole">Communication Officer</p>
 
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
 
            <!-- RIGHT COLUMN -->
            <main class="profileCardRight">
 
                <!-- PERSONAL INFORMATION -->
                <section id="personalInfoSection" class="tabSection">
                    <h2>Personal Information</h2>
 
                    <form id="profileForm" action="updateProfile.php" method="POST" enctype="multipart/form-data" class="settingsForm">
                        <input type="hidden" name="action" value="update_profile">
 
                        <div class="formGrid">
                            <div class="inputGroup">
                                <label for="first_Name">First Name</label>
                                <input type="text" id="first_Name" name="first_Name"
                                       value="<?= e($userData["first_Name"] ?? "") ?>">
                            </div>
 
                            <div class="inputGroup">
                                <label for="surname">Surname</label>
                                <input type="text" id="surname" name="surname"
                                       value="<?= e($userData["surname"] ?? "") ?>">
                            </div>
 
                            <div class="inputGroup fullWidth">
                                <label for="username">Username</label>
                                <div class="icon-input">
                                    <input type="text" id="username" name="username"
                                           value="<?= e($userData["username"] ?? "") ?>" readonly class="readOnlyInput">
                                    <?php if (!empty($userData["verified"])): ?>
                                        <span class="verifiedTag">
                                            <i class="fa-solid fa-circle-check"></i> Verified
                                        </span>
                                    <?php endif; ?>
                                </div>
                            </div>
 
                            <div class="inputGroup fullWidth">
                                <label for="address">Address</label>
                                <input type="text" id="address" name="address"
                                       value="<?= e($userData["address"] ?? "") ?>">
                            </div>
 
                            <div class="inputGroup">
                                <label for="contact">Phone Number</label>
                                <input type="tel" id="contact" name="contact"
                                       value="<?= e($userData["contact"] ?? "") ?>">
                            </div>
 
                            <div class="inputGroup">
                                <label for="ward_id">Ward / Location</label>
                                <select id="ward_id" name="ward_id">
                                    <option value="">Select Ward...</option>
                                    <?php foreach ($wards as $ward): ?>
                                        <option value="<?= (int)$ward["ward_id"] ?>"
                                            <?= ((int)($userData["ward_id"] ?? 0) === (int)$ward["ward_id"]) ? "selected" : "" ?>>
                                            <?= e($ward["ward_name"] . " (Ward " . $ward["ward_number"] . ")") ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>
 
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
 
                        <input type="hidden" id="confirmPasswordInputPwd" name="confirm_password">
 
                        <div class="formActions">
                            <button type="button" id="btnDiscardPassword" class="btnDiscard">Cancel</button>
                            <button type="button" id="btnTriggerPasswordModal" class="btnSave">Update Password</button>
                        </div>
                    </form>
                </section>
 
            </main>
 
        </div>
    </div>
</div>
 
<!-- =====================================
     PASSWORD CONFIRMATION MODAL
====================================== -->
<div id="passwordModal" class="modalOverlay hiddenModal">
    <div class="modalContent">
        <h3>Confirm Changes</h3>
        <p>Please enter your account password to confirm these changes.</p>
 
        <input type="password" id="modalConfirmPassword" class="modalInput" placeholder="Enter password">
        <p id="modalError" class="modalErrorText hiddenError">Please enter your password to continue.</p>
 
        <div class="modalActions">
            <button type="button" id="btnCancelModal" class="btnDiscard">Cancel</button>
            <button type="button" id="btnConfirmModal" class="btnSave">Confirm &amp; Save</button>
        </div>
    </div>
</div>
 
<script>
document.addEventListener("DOMContentLoaded", () => {
 
    // ---- sidebar toggle (same behaviour as the dashboard) ----
    (function () {
        var sidebar = document.getElementById("olSidebar");
        var wrapper = document.getElementById("olMainWrapper");
        var btn     = document.getElementById("olMenuBtn");
        var overlay = document.getElementById("olSidebarOverlay");
        if (!sidebar || !wrapper || !btn) return;
 
        var mq = window.matchMedia("(max-width: 750px)");
 
        function setMobileOpen(open) {
            sidebar.classList.toggle("mobile-open", open);
            if (overlay) overlay.classList.toggle("show", open);
            btn.setAttribute("aria-expanded", String(open));
        }
        function resetForBreakpoint() {
            setMobileOpen(false);
            if (mq.matches) {
                sidebar.classList.remove("collapsed");
                wrapper.classList.remove("sidebar-collapsed");
                btn.setAttribute("aria-expanded", "false");
            } else {
                btn.setAttribute("aria-expanded", String(!sidebar.classList.contains("collapsed")));
            }
        }
 
        btn.addEventListener("click", function () {
            if (mq.matches) {
                setMobileOpen(!sidebar.classList.contains("mobile-open"));
            } else {
                var collapsed = sidebar.classList.toggle("collapsed");
                wrapper.classList.toggle("sidebar-collapsed", collapsed);
                btn.setAttribute("aria-expanded", String(!collapsed));
            }
        });
 
        if (overlay) overlay.addEventListener("click", function () { setMobileOpen(false); });
        document.addEventListener("keydown", function (ev) {
            if (ev.key === "Escape") setMobileOpen(false);
        });
        sidebar.addEventListener("click", function (ev) {
            if (mq.matches && ev.target.closest && ev.target.closest("a")) setMobileOpen(false);
        });
 
        if (mq.addEventListener) mq.addEventListener("change", resetForBreakpoint);
        else if (mq.addListener) mq.addListener(resetForBreakpoint);
 
        resetForBreakpoint();
    })();
 
 
    // ---- elements ----
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
 
    let pendingAction = null;
 
    const originalImageSrc = (profileImage && profileImage.tagName === "IMG") ? profileImage.src : "";
 
    const initialFormData = {
        firstName: document.getElementById("first_Name") ? document.getElementById("first_Name").value : "",
        surname: document.getElementById("surname") ? document.getElementById("surname").value : "",
        address: document.getElementById("address") ? document.getElementById("address").value : "",
        contact: document.getElementById("contact") ? document.getElementById("contact").value : "",
        ward_id: document.getElementById("ward_id") ? document.getElementById("ward_id").value : ""
    };
 
    // ---- tabs ----
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
 
    // ---- profile picture preview ----
    const imageUploadActions = document.getElementById("imageUploadActions");
    const btnCancelImage = document.getElementById("btnCancelImage");
 
    if (imageUploadInput && profileImage) {
        imageUploadInput.addEventListener("change", (e) => {
            const file = e.target.files[0];
            if (file) {
                const reader = new FileReader();
                reader.onload = (event) => {
                    // if there was no picture yet, swap the placeholder icon for a real <img>
                    if (profileImage.tagName !== "IMG") {
                        const img = document.createElement("img");
                        img.id = "profileImage";
                        img.alt = "Profile Picture";
                        profileImage.replaceWith(img);
                        window.profileImageEl = img;
                        img.src = event.target.result;
                    } else {
                        profileImage.src = event.target.result;
                    }
                };
                reader.readAsDataURL(file);
 
                if (imageUploadActions) imageUploadActions.classList.remove("hiddenSection");
            }
        });
    }
 
    if (btnCancelImage) {
        btnCancelImage.addEventListener("click", () => {
            if (imageUploadInput) imageUploadInput.value = "";
            const img = document.getElementById("profileImage");
            if (img && img.tagName === "IMG" && originalImageSrc) img.src = originalImageSrc;
            if (imageUploadActions) imageUploadActions.classList.add("hiddenSection");
        });
    }
 
    // ---- discard profile changes ----
    const btnDiscardProfile = document.getElementById("btnDiscardProfile");
    if (btnDiscardProfile) {
        btnDiscardProfile.addEventListener("click", () => {
            if (document.getElementById("first_Name")) document.getElementById("first_Name").value = initialFormData.firstName;
            if (document.getElementById("surname")) document.getElementById("surname").value = initialFormData.surname;
            if (document.getElementById("address")) document.getElementById("address").value = initialFormData.address;
            if (document.getElementById("contact")) document.getElementById("contact").value = initialFormData.contact;
            if (document.getElementById("ward_id")) document.getElementById("ward_id").value = initialFormData.ward_id;
            const img = document.getElementById("profileImage");
            if (img && img.tagName === "IMG" && originalImageSrc) img.src = originalImageSrc;
            if (imageUploadInput) imageUploadInput.value = "";
        });
    }
 
    // ---- cancel password change ----
    const btnDiscardPassword = document.getElementById("btnDiscardPassword");
    if (btnDiscardPassword && passwordForm) {
        btnDiscardPassword.addEventListener("click", () => passwordForm.reset());
    }
 
    // ---- profile save modal ----
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
 
    // ---- password change modal ----
    if (btnTriggerPasswordModal && modal) {
        btnTriggerPasswordModal.addEventListener("click", (e) => {
            e.preventDefault();
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
 
    // ---- close modal ----
    if (modalCancelBtn && modal) {
        modalCancelBtn.addEventListener("click", () => modal.classList.add("hiddenModal"));
    }
    if (modal) {
        modal.addEventListener("click", (e) => {
            if (e.target === modal) modal.classList.add("hiddenModal");
        });
    }
 
    // ---- confirm password and submit ----
    if (modalConfirmBtn) {
        modalConfirmBtn.addEventListener("click", () => {
            const passwordValue = modalPasswordInput ? modalPasswordInput.value.trim() : "";
 
            if (!passwordValue) {
                if (modalError) modalError.classList.remove("hiddenError");
                return;
            }
 
            if (modal) modal.classList.add("hiddenModal");
 
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
 
    if (modalPasswordInput) {
        modalPasswordInput.addEventListener("keypress", (e) => {
            if (e.key === "Enter") {
                e.preventDefault();
                if (modalConfirmBtn) modalConfirmBtn.click();
            }
        });
    }
 
    // ---- password validation ----
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
 
    // ---- keep the shared session info fresh for other pages' fallback ----
    try {
        sessionStorage.setItem("olLayoutUser", JSON.stringify({
            enabled: true,
            uid:    <?= json_encode((string)$username, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?>,
            name:   <?= json_encode((string)$loggedInUser, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?>,
            pic:    <?= json_encode((string)$userProfilePic, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?>,
            unread: <?= (int)$olUnreadNotifications ?>,
            messages: <?= (int)$olUnreadMessages ?>
        }));
    } catch (e) { /* storage unavailable */ }
 
});
</script>
 
</body>
</html>
 