<?php
session_start();
require_once "Error404Connection.php";
require_once "detailsOfTheLoggedInUser.php";

$loggedInUsername = $_SESSION['username'] ?? '';
if (empty($loggedInUsername)) {
    header("Location: login.php");
    exit;
}

/*
   PROFILE INFO (same source as residence.php's sidebar/topbar)
*/
$firstName = $userData["first_Name"] ?? "Resident";
$surname = $userData["surname"] ?? "";
$profileImage = $userData["image_path"] ?? "";

/*
   UNREAD NOTIFICATIONS (same query as residence.php's topbar badge)
*/
$unreadNotificationCount = 0;

$sqlNotif = "
    SELECT COUNT(*) AS unread_count
    FROM notification
    WHERE username = ?
    AND notification_type = 'ticket_update'
    AND is_read = 0
";

if ($stmtNotif = $conn->prepare($sqlNotif)) {
    $stmtNotif->bind_param("s", $loggedInUsername);
    $stmtNotif->execute();
    $resultNotif = $stmtNotif->get_result();
    if ($resultNotif && $rowNotif = $resultNotif->fetch_assoc()) {
        $unreadNotificationCount = (int)$rowNotif["unread_count"];
    }
    $stmtNotif->close();
}

$requestedTicketId = isset($_GET['ticketId']) ? intval($_GET['ticketId']) : 0;
$ticketId = $requestedTicketId;
$ticket = null;
$comments = [];

// No specific ticket requested (e.g. arriving from the "My Tickets"
// sidebar link with no query string) - fall back to this resident's
// most recently reported fault ticket instead of just showing "not found".
if ($ticketId <= 0) {

    $sqlLatest = "
        SELECT incident_id
        FROM incident_ticket
        WHERE username = ?
        ORDER BY date_reported DESC
        LIMIT 1
    ";

    if ($stmtLatest = $conn->prepare($sqlLatest)) {
        $stmtLatest->bind_param("s", $loggedInUsername);
        $stmtLatest->execute();
        $resultLatest = $stmtLatest->get_result();
        if ($resultLatest && $rowLatest = $resultLatest->fetch_assoc()) {
            $ticketId = (int)$rowLatest['incident_id'];
        }
        $stmtLatest->close();
    }
}

/*
   ALL OF THIS RESIDENT'S TICKETS
   (for the searchable ticket list panel)
*/
$allTickets = [];

$sqlAllTickets = "
    SELECT
        i.incident_id,
        i.incident_description,
        i.priority,
        i.date_reported,
        ms.service_type,
        ts.pending,
        ts.InProgress,
        ts.resolved
    FROM incident_ticket i
    LEFT JOIN municipal_service ms ON i.service_id = ms.service_id
    LEFT JOIN ticket_status ts ON i.status_id = ts.status_id
    WHERE i.username = ?
    ORDER BY i.date_reported DESC
";

if ($stmtAllTickets = $conn->prepare($sqlAllTickets)) {
    $stmtAllTickets->bind_param("s", $loggedInUsername);
    $stmtAllTickets->execute();
    $resultAllTickets = $stmtAllTickets->get_result();
    while ($rowAllTickets = $resultAllTickets->fetch_assoc()) {
        $allTickets[] = $rowAllTickets;
    }
    $stmtAllTickets->close();
}

if ($ticketId > 0) {
    // 1. Fetch Ticket details along with Resident's full name & status dates
    $sqlTicket = "
        SELECT 
            i.incident_id,
            i.incident_description,
            i.location_description,
            i.priority,
            i.date_reported,
            i.date_resolved,
            i.created_at,
            u.first_Name AS resident_first,
            u.surname AS resident_last,
            ms.service_type,
            ts.pending,
            ts.InProgress,
            ts.resolved
        FROM incident_ticket i
        INNER JOIN user u ON i.username = u.username
        LEFT JOIN municipal_service ms ON i.service_id = ms.service_id
        LEFT JOIN ticket_status ts ON i.status_id = ts.status_id
        WHERE i.incident_id = ? AND i.username = ?
    ";

    if ($stmt = $conn->prepare($sqlTicket)) {
        $stmt->bind_param("is", $ticketId, $loggedInUsername);
        $stmt->execute();
        $result = $stmt->get_result();
        if ($result->num_rows > 0) {
            $ticket = $result->fetch_assoc();
        }
        $stmt->close();
    }

    // 2. Fetch Comments along with Councillor/Official's full name
    if ($ticket) {
        $sqlComments = "
            SELECT 
                c.comment_id,
                c.comment_text,
                c.comment_date,
                u.first_Name,
                u.surname,
                u.User_role
            FROM comment c
            LEFT JOIN user u ON c.username = u.username
            WHERE c.incident_id = ?
            ORDER BY c.comment_date ASC
        ";

        if ($stmtc = $conn->prepare($sqlComments)) {
            $stmtc->bind_param("i", $ticketId);
            $stmtc->execute();
            $resComments = $stmtc->get_result();
            while ($row = $resComments->fetch_assoc()) {
                $comments[] = $row;
            }
            $stmtc->close();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Ticket Progress & Details</title>
    <link rel="stylesheet" href="ticketDetails.css">

    <link
        href="https://fonts.googleapis.com/css2?family=Lora:wght@400;500;600;700&family=Ubuntu:wght@300;400;500;700&display=swap"
        rel="stylesheet"
    >

    <link
        href="https://fonts.googleapis.com/icon?family=Material+Icons"
        rel="stylesheet"
    >
    <style>
        /* =========================================
   RESET
========================================= */

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
    font-family: "Ubuntu", 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
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
   DASHBOARD SHELL (matches residence.php)
========================================= */

.dashboard-container {
    width: 100%;
    min-height: 100vh;
}

/* ---- Sidebar ---- */

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
    transition: background 0.2s ease, color 0.2s ease;
}

.sidebar-link:hover {
    background: rgba(255, 255, 255, 0.12);
    color: var(--white);
}

.sidebar-link.active {
    background: rgba(255, 255, 255, 0.18);
    color: var(--white);
}

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

/* ---- Main wrapper ---- */

.main-wrapper {
    width: calc(100% - var(--sidebar-width));
    min-height: 100vh;
    margin-left: var(--sidebar-width);
    transition: margin-left 0.3s ease, width 0.3s ease;
}

.main-wrapper.sidebar-collapsed {
    width: calc(100% - var(--sidebar-collapsed-width));
    margin-left: var(--sidebar-collapsed-width);
}

/* ---- Topbar ---- */

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

/* ---- Content area ---- */

.dashboard-content {
    width: 100%;
    padding: 35px;
}

/* =========================================
   MOBILE (matches residence.php)
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
}

/* =========================================
   TICKET CONTENT (re-themed to sit on the
   cream dashboard-content area instead of a
   full dark-green page background)
========================================= */

.container {
    max-width: 850px;
    margin: 0 auto;
}

.back-button {
    color: var(--green);
    text-decoration: none;
    font-weight: 600;
    display: inline-block;
    margin-bottom: 20px;
}

.back-button:hover {
    text-decoration: underline;
}

.ticket-card {
    background: #ffffff;
    border-radius: 12px;
    padding: 30px;
    box-shadow: 0 10px 25px rgba(0,0,0,0.06);
    border: 1px solid #ece7dd;
}

.card-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 20px;
    border-bottom: 2px solid #f0f0f0;
    padding-bottom: 10px;
}

.card-header h2 {
    margin: 0;
    color: #1e3d2f;
}

.read-only-badge {
    background: #e2e8f0;
    color: #64748b;
    font-size: 0.8rem;
    padding: 4px 10px;
    border-radius: 20px;
    font-weight: 600;
}

.info-grid {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 15px;
    background: #f8f9fa;
    padding: 20px;
    border-radius: 8px;
    margin-bottom: 30px;
}

.priority-badge {
    background: #0284c7;
    color: #fff;
    padding: 2px 8px;
    border-radius: 4px;
    font-size: 0.85rem;
}

/* Status Tracker Animation */
.status-tracker {
    margin: 35px 0;
}

.tracker-steps {
    display: flex;
    align-items: center;
    justify-content: space-between;
    margin-top: 20px;
}

.step {
    text-align: center;
    opacity: 0.35;
    transition: all 0.5s ease;
}

.step.active {
    opacity: 1;
}

.step-icon {
    width: 42px;
    height: 42px;
    border-radius: 50%;
    background: #cbd5e1;
    color: #fff;
    display: flex;
    align-items: center;
    justify-content: center;
    margin: 0 auto 8px;
    font-weight: bold;
    transition: background 0.4s ease, transform 0.4s ease;
}

.step.active .step-icon {
    background: #16a34a;
    box-shadow: 0 0 12px rgba(22, 163, 74, 0.5);
    animation: popIn 0.5s ease forwards;
}

.step-title {
    margin: 0;
    font-weight: 600;
    font-size: 0.95rem;
}

.step-line {
    flex-grow: 1;
    height: 4px;
    background: #e2e8f0;
    margin: 0 10px;
    position: relative;
    top: -14px;
    overflow: hidden;
}

.step-line.active-line::after {
    content: '';
    display: block;
    height: 100%;
    background: #16a34a;
    animation: fillLine 0.8s ease-in-out forwards;
}

@keyframes fillLine {
    from { width: 0%; }
    to { width: 100%; }
}

@keyframes popIn {
    0% { transform: scale(0.8); }
    50% { transform: scale(1.15); }
    100% { transform: scale(1); }
}

/* Timeline Comments */
.comments-section {
    margin-top: 40px;
}

.timeline {
    position: relative;
    padding-left: 24px;
    border-left: 3px solid #16a34a;
    margin-top: 20px;
}

.timeline-item {
    position: relative;
    margin-bottom: 25px;
    opacity: 0;
    transform: translateY(20px);
    animation: fadeInUp 0.5s ease forwards;
    animation-delay: var(--delay);
}

.timeline-dot {
    position: absolute;
    left: -31px;
    top: 6px;
    width: 12px;
    height: 12px;
    border-radius: 50%;
    background: #16a34a;
    border: 3px solid #fff;
    box-shadow: 0 0 0 2px #16a34a;
}

.timeline-content {
    background: #f8fafc;
    border: 1px solid #e2e8f0;
    padding: 15px;
    border-radius: 8px;
}

.author-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 8px;
}

.role-badge {
    background: #dbeafe;
    color: #1e40af;
    font-size: 0.75rem;
    padding: 2px 8px;
    border-radius: 4px;
    font-weight: 600;
}

.comment-body {
    margin: 0 0 8px 0;
    color: #334155;
    line-height: 1.5;
}

.comment-time {
    font-size: 0.8rem;
    color: #94a3b8;
}

.no-comments {
    color: #64748b;
    font-style: italic;
}

.message {
    background: #ffffff;
    padding: 30px;
    border-radius: 10px;
    text-align: center;
}

.details-button {
    display: inline-block;
    margin-top: 16px;
    padding: 10px 22px;
    background: var(--green, #4b561b);
    color: #ffffff;
    border-radius: 6px;
    font-weight: 600;
    text-decoration: none;
    transition: background 0.2s ease;
}

.details-button:hover {
    background: var(--teal, #244f4b);
}

/* =========================================
   TICKET LIST / SEARCH PANEL
========================================= */

.ticket-list-panel {
    background: #ffffff;
    border-radius: 12px;
    padding: 22px;
    margin-bottom: 25px;
    box-shadow: 0 10px 25px rgba(0,0,0,0.06);
    border: 1px solid #ece7dd;
}

.ticket-list-header {
    margin-bottom: 14px;
}

.ticket-list-header h3 {
    color: #1e3d2f;
    font-size: 1.1rem;
}

.ticket-search-wrapper {
    margin-bottom: 14px;
}

.ticket-search-wrapper input {
    width: 100%;
    padding: 10px 14px;
    border: 1px solid #d7dbe0;
    border-radius: 8px;
    font-size: 0.9rem;
    font-family: inherit;
    outline: none;
    transition: border-color 0.2s ease;
}

.ticket-search-wrapper input:focus {
    border-color: var(--green, #4b561b);
}

.ticket-list {
    display: flex;
    flex-direction: column;
    gap: 8px;
    max-height: 280px;
    overflow-y: auto;
}

.ticket-list-item {
    display: flex;
    justify-content: space-between;
    align-items: center;
    gap: 12px;
    padding: 12px 14px;
    border: 1px solid #eef0e3;
    border-radius: 8px;
    background: #fafbf8;
    color: #263238;
    text-decoration: none;
    transition: background 0.15s ease, border-color 0.15s ease;
}

.ticket-list-item:hover {
    background: #f1f3ea;
    border-color: #dbe2c8;
}

.ticket-list-item.active-ticket {
    border-color: var(--green, #4b561b);
    background: #eef0e5;
}

.ticket-list-main {
    display: flex;
    flex-direction: column;
    gap: 2px;
    min-width: 0;
}

.ticket-list-id {
    font-weight: 700;
    font-size: 0.85rem;
    color: #1e3d2f;
}

.ticket-list-service {
    font-size: 0.8rem;
    color: #666;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}

.ticket-list-meta {
    display: flex;
    flex-direction: column;
    align-items: flex-end;
    gap: 4px;
    flex-shrink: 0;
}

.ticket-list-date {
    font-size: 0.75rem;
    color: #888;
    white-space: nowrap;
}

.status-pill {
    font-size: 0.7rem;
    font-weight: 700;
    padding: 2px 9px;
    border-radius: 20px;
    white-space: nowrap;
}

.status-pill.status-pending {
    background: #fef3c7;
    color: #92400e;
}

.status-pill.status-in-progress {
    background: #dbeafe;
    color: #1e40af;
}

.status-pill.status-resolved {
    background: #dcfce7;
    color: #166534;
}

.no-search-results {
    margin-top: 10px;
    text-align: center;
    color: #888;
    font-style: italic;
    font-size: 0.9rem;
}

@keyframes fadeInUp {
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

/* Hidden comments state */
.timeline-item.hidden-comment {
    display: none;
}

/* Toggle Comments Button Styles */
.toggle-comments-wrapper {
    text-align: center;
    margin-top: 20px;
}

.btn-toggle-comments {
    background-color: #f1f5f9;
    color: #1e3d2f;
    border: 1px solid #cbd5e1;
    padding: 8px 18px;
    border-radius: 20px;
    font-size: 0.9rem;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.25s ease;
}

.btn-toggle-comments:hover {
    background-color: #1e3d2f;
    color: #ffffff;
    border-color: #1e3d2f;
    box-shadow: 0 4px 10px rgba(0, 0, 0, 0.1);
}
    </style>
</head>
<body>

<div class="dashboard-container">

    <!-- =====================================
         SIDEBAR (identical to residence.php)
    ====================================== -->

    <aside class="sidebar" id="sidebar">

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
                    <?php echo htmlspecialchars(trim($firstName . " " . $surname)); ?>
                </strong>

                <span>
                    Resident
                </span>

            </div>

        </div>

        <nav class="sidebar-menu">

            <!-- DASHBOARD -->
            <a href="residence.php" class="sidebar-link" title="Dashboard">
                <img src="../icons/home.svg" alt="" class="sidebar-icon">
                <span>Dashboard</span>
            </a>

            <!-- REPORT FAULT -->
            <a href="reportFaultTicketForm.php" class="sidebar-link" title="Report Fault">
                <span class="material-icons sidebar-material-icon">report_problem</span>
                <span>Report Fault</span>
            </a>

            <!-- MY TICKETS -->
            <a href="ticketDetails.php" class="sidebar-link active" title="My Tickets">
                <span class="material-icons sidebar-material-icon">confirmation_number</span>
                <span>My Tickets</span>
            </a>

            <!-- SETTINGS -->
            <a href="settings.php" class="sidebar-link" title="Settings">
                <span class="material-icons sidebar-material-icon">settings</span>
                <span>Profile</span>
            </a>

            <!-- LOGOUT -->
            <a href="logout.php" class="sidebar-link" title="Logout">
                <span class="material-icons sidebar-material-icon">logout</span>
                <span>Logout</span>
            </a>

        </nav>

    </aside>

    <!-- =====================================
         MAIN WRAPPER
    ====================================== -->

    <div class="main-wrapper" id="mainWrapper">

        <!-- =================================
             TOPBAR (identical to residence.php)
        ================================== -->

        <header class="topbar">

            <div class="topbar-left">

                <button type="button" class="menu-btn" id="menuBtn" title="Menu">
                    <span class="material-icons">menu</span>
                </button>

                <a href="../PUBLIC%20PAGES/homepage.php" class="logo-link" title="Go to M-LINK Homepage">
                    <img src="mlinkLogo.png" alt="M-LINK Logo" class="topbar-logo">
                </a>

            </div>

            <div class="topbar-right">

                <a href="notifications.php" class="topbar-icon-btn notification-link" title="Notifications">

                    <div class="notification-icon-wrapper">

                        <img src="../icons/add_alert.svg" alt="Notifications" class="topbar-icon-img">

                        <?php if ($unreadNotificationCount > 0): ?>

                            <span class="notification-badge">
                                <?php echo $unreadNotificationCount > 99 ? "99+" : $unreadNotificationCount; ?>
                            </span>

                        <?php endif; ?>

                    </div>

                    <span class="notification-text">Notifications</span>

                </a>

                <a href="messages.php" class="topbar-icon-btn" title="Messages">
                    <img src="../icons/message.svg" alt="Messages" class="topbar-icon-img">
                    <span>Messages</span>
                </a>

                <a href="settings.php" class="topbar-profile" title="Profile">

                    <div class="topbar-avatar">

                        <?php if ($profileImage !== ""): ?>

                            <img src="<?php echo htmlspecialchars($profileImage); ?>" alt="Profile">

                        <?php else: ?>

                            <span class="material-icons">account_circle</span>

                        <?php endif; ?>

                    </div>

                    <div class="topbar-profile-info">
                        <strong><?php echo htmlspecialchars($firstName); ?></strong>
                        <span>Resident</span>
                    </div>

                </a>

            </div>

        </header>

        <!-- =================================
             TICKET DETAILS CONTENT
        ================================== -->

        <main class="dashboard-content">

    <div class="container">
    

        <?php if (!empty($allTickets)): ?>
            <div class="ticket-list-panel">

                <div class="ticket-list-header">
                    <h3>Your Tickets (<?php echo count($allTickets); ?>)</h3>
                </div>

                <div class="ticket-search-wrapper">
                    <input
                        type="text"
                        id="ticketSearchInput"
                        placeholder="Search by ID, service, description, priority or status..."
                        aria-label="Search your tickets"
                    >
                </div>

                <div class="ticket-list" id="ticketListContainer">
                    <?php foreach ($allTickets as $t):
                        $tStatus = !empty($t['resolved'])
                            ? 'Resolved'
                            : (!empty($t['InProgress']) ? 'In Progress' : 'Pending');

                        $isActiveTicket = ((int)$t['incident_id'] === (int)$ticketId);

                        $searchBlob = strtolower(
                            $t['incident_id'] . ' '
                            . ($t['service_type'] ?? '') . ' '
                            . $t['incident_description'] . ' '
                            . ($t['priority'] ?? '') . ' '
                            . $tStatus
                        );
                    ?>
                        <a
                            href="ticketDetails.php?ticketId=<?php echo (int)$t['incident_id']; ?>"
                            class="ticket-list-item<?php echo $isActiveTicket ? ' active-ticket' : ''; ?>"
                            data-search="<?php echo htmlspecialchars($searchBlob, ENT_QUOTES, 'UTF-8'); ?>"
                        >
                            <div class="ticket-list-main">
                                <span class="ticket-list-id">#<?php echo htmlspecialchars($t['incident_id']); ?></span>
                                <span class="ticket-list-service"><?php echo htmlspecialchars($t['service_type'] ?? 'General Fault'); ?></span>
                            </div>

                            <div class="ticket-list-meta">
                                <span class="status-pill status-<?php echo strtolower(str_replace(' ', '-', $tStatus)); ?>">
                                    <?php echo $tStatus; ?>
                                </span>
                                <span class="ticket-list-date">
                                    <?php echo !empty($t['date_reported']) ? date("d M Y", strtotime($t['date_reported'])) : ''; ?>
                                </span>
                            </div>
                        </a>
                    <?php endforeach; ?>
                </div>

                <p class="no-search-results" id="noSearchResults" style="display:none;">
                    No tickets match your search.
                </p>

            </div>
        <?php endif; ?>

        <?php if ($ticket): ?>
            <div class="ticket-card">
                <header class="card-header">
                    <h2>Ticket #<?php echo htmlspecialchars($ticket['incident_id']); ?></h2>
                    <span class="read-only-badge">Read-Only View</span>
                </header>

                <div class="info-grid">
                    <p><strong>Resident Name:</strong> <?php echo htmlspecialchars($ticket['resident_first'] . ' ' . $ticket['resident_last']); ?></p>
                    <p><strong>Service Type:</strong> <?php echo htmlspecialchars($ticket['service_type'] ?? 'General Fault'); ?></p>
                    <p><strong>Priority:</strong> <span class="badge priority-badge"><?php echo htmlspecialchars($ticket['priority'] ?? 'Normal'); ?></span></p>
                    <p><strong>Location:</strong> <?php echo htmlspecialchars($ticket['location_description'] ?? 'Not specified'); ?></p>
                    <p><strong>Date Reported:</strong> <?php echo date("d M Y, H:i", strtotime($ticket['date_reported'])); ?></p>
                    <p><strong>Description:</strong> <?php echo htmlspecialchars($ticket['incident_description']); ?></p>
                </div>

                <!-- Animated Progress Tracker -->
                <div class="status-tracker">
                    <h3>Ticket Progress</h3>
                    <div class="tracker-steps">
                        <div class="step <?php echo !empty($ticket['pending']) ? 'active' : ''; ?>">
                            <div class="step-icon">1</div>
                            <p class="step-title">Pending</p>
                            <small><?php echo !empty($ticket['pending']) ? date("d M Y", strtotime($ticket['pending'])) : '—'; ?></small>
                        </div>

                        <div class="step-line <?php echo !empty($ticket['InProgress']) ? 'active-line' : ''; ?>"></div>

                        <div class="step <?php echo !empty($ticket['InProgress']) ? 'active' : ''; ?>">
                            <div class="step-icon">2</div>
                            <p class="step-title">In Progress</p>
                            <small><?php echo !empty($ticket['InProgress']) ? date("d M Y", strtotime($ticket['InProgress'])) : '—'; ?></small>
                        </div>

                        <div class="step-line <?php echo !empty($ticket['resolved']) ? 'active-line' : ''; ?>"></div>

                        <div class="step <?php echo !empty($ticket['resolved']) ? 'active' : ''; ?>">
                            <div class="step-icon">3</div>
                            <p class="step-title">Resolved</p>
                            <small><?php echo !empty($ticket['resolved']) ? date("d M Y", strtotime($ticket['resolved'])) : '—'; ?></small>
                        </div>
                    </div>
                </div>

                <!-- Timeline Comments Section -->
                <div class="comments-section">
                    <h3>Official Comments & Updates</h3>
                    <?php if (!empty($comments)): ?>
                        <?php 
                            $totalComments = count($comments);
                            $initialLimit = 3;
                        ?>
                        <div class="timeline" id="commentsTimeline">
                            <?php foreach ($comments as $index => $c): ?>
                                <?php 
                                    // Hide comments beyond the first 3
                                    $isHidden = ($index >= $initialLimit) ? 'hidden-comment' : '';
                                ?>
                                <div class="timeline-item <?php echo $isHidden; ?>" style="--delay: <?php echo ($index % $initialLimit) * 0.15; ?>s;">
                                    <div class="timeline-dot"></div>
                                    <div class="timeline-content">
                                        <div class="author-header">
                                            <strong><?php echo htmlspecialchars(($c['first_Name'] ?? 'Unknown') . ' ' . ($c['surname'] ?? '')); ?></strong>
                                            <span class="role-badge"><?php echo htmlspecialchars($c['User_role'] ?? 'Official'); ?></span>
                                        </div>
                                        <p class="comment-body"><?php echo htmlspecialchars($c['comment_text']); ?></p>
                                        <span class="comment-time"><?php echo date("d M Y, H:i", strtotime($c['comment_date'])); ?></span>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>

                        <?php if ($totalComments > $initialLimit): ?>
                            <div class="toggle-comments-wrapper">
                                <button type="button" id="toggleCommentsBtn" class="btn-toggle-comments" data-expanded="false">
                                    Show All Comments (<?php echo $totalComments; ?>)
                                </button>
                            </div>
                        <?php endif; ?>

                    <?php else: ?>
                        <p class="no-comments">No comments or updates added yet.</p>
                    <?php endif; ?>
                </div>
            </div>
        <?php else: ?>
            <div class="message">
                <?php if ($requestedTicketId <= 0): ?>
                    <h3>No Fault Tickets Yet</h3>
                    <p>You haven't reported any fault tickets yet.</p>
                    <a class="details-button" href="reportFaultTicketForm.php">Report a Fault</a>
                <?php else: ?>
                    <h3>Ticket Not Found</h3>
                    <p>Unable to locate this ticket or you do not have permission to view it.</p>
                    <a class="details-button" href="ticketHistory.php">Back to History</a>
                <?php endif; ?>
            </div>
        <?php endif; ?>
    </div>

        </main>

    </div>

</div>

    <script src="residence.js"></script>
    <script src="ticketDetails.js"></script>
</body>
</html>