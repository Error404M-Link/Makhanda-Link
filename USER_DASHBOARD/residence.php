<?php

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once "../Error404Connection.php";
require_once "detailsOfTheLoggedInUser.php";


/* 
   CHECK RESIDENT LOGIN
*/

$username = $_SESSION["username"] ?? "";

if ($username === "") {
    header("Location: ../PUBLIC%20PAGES/login.php");
    exit;
}


/* 
   RESIDENT INFORMATION
*/

$wardId = (int)(
    $userData["ward_id"]
    ?? $_SESSION["ward_id"]
    ?? 0
);

$firstName = $userData["first_Name"] ?? "Resident";
$surname = $userData["surname"] ?? "";
$profileImage = $userData["image_path"] ?? "";


/* =========================================
   DASHBOARD COUNTS
========================================= */

$totalTickets = 0;
$inProgressTickets = 0;
$resolvedTickets = 0;


/* =========================================
   TOTAL WARD TICKETS
========================================= */

$sql = "
    SELECT COUNT(*) AS total
    FROM incident_ticket
    WHERE ward_id = ?
";

$stmt = $conn->prepare($sql);

if ($stmt) {

    $stmt->bind_param("i", $wardId);
    $stmt->execute();

    $result = $stmt->get_result();

    if ($result && $row = $result->fetch_assoc()) {
        $totalTickets = (int)$row["total"];
    }

    $stmt->close();
}


/* =========================================
   IN PROGRESS WARD TICKETS
========================================= */

$sql = "
    SELECT COUNT(*) AS total
    FROM incident_ticket i

    LEFT JOIN ticket_status ts
        ON i.status_id = ts.status_id

    WHERE i.ward_id = ?
    AND ts.InProgress IS NOT NULL
    AND ts.resolved IS NULL
";

$stmt = $conn->prepare($sql);

if ($stmt) {

    $stmt->bind_param("i", $wardId);
    $stmt->execute();

    $result = $stmt->get_result();

    if ($result && $row = $result->fetch_assoc()) {
        $inProgressTickets = (int)$row["total"];
    }

    $stmt->close();
}


/* =========================================
   RESOLVED WARD TICKETS
========================================= */

$sql = "
    SELECT COUNT(*) AS total
    FROM incident_ticket i

    LEFT JOIN ticket_status ts
        ON i.status_id = ts.status_id

    WHERE i.ward_id = ?
    AND ts.resolved IS NOT NULL
";

$stmt = $conn->prepare($sql);

if ($stmt) {

    $stmt->bind_param("i", $wardId);
    $stmt->execute();

    $result = $stmt->get_result();

    if ($result && $row = $result->fetch_assoc()) {
        $resolvedTickets = (int)$row["total"];
    }

    $stmt->close();
}


/* =========================================
   UNREAD NOTIFICATIONS
========================================= */

$unreadNotificationCount = 0;

$sql = "
    SELECT COUNT(*) AS unread_count
    FROM notification
    WHERE username = ?
    AND notification_type = 'ticket_update'
    AND is_read = 0
";

$stmt = $conn->prepare($sql);

if ($stmt) {

    $stmt->bind_param("s", $username);
    $stmt->execute();

    $result = $stmt->get_result();

    if ($result && $row = $result->fetch_assoc()) {
        $unreadNotificationCount =
            (int)$row["unread_count"];
    }

    $stmt->close();
}


/* =========================================
   RECENT WARD TICKETS
========================================= */

$recentTickets = [];

$sql = "
    SELECT
        i.incident_id,
        i.username,
        i.incident_description,
        i.location_description,
        i.priority,
        i.date_reported,
        i.date_resolved,

        ms.service_type,

        ad.street_name,

        w.ward_number,

        ts.pending,
        ts.InProgress,
        ts.resolved

    FROM incident_ticket i

    LEFT JOIN municipal_service ms
        ON i.service_id = ms.service_id

    LEFT JOIN address_directory ad
        ON i.address_id = ad.address_id

    LEFT JOIN ward w
        ON i.ward_id = w.ward_id

    LEFT JOIN ticket_status ts
        ON i.status_id = ts.status_id

    WHERE i.ward_id = ?

    ORDER BY i.date_reported DESC

    LIMIT 5
";

$stmt = $conn->prepare($sql);

if ($stmt) {

    $stmt->bind_param("i", $wardId);
    $stmt->execute();

    $result = $stmt->get_result();

    while ($result && $row = $result->fetch_assoc()) {
        $recentTickets[] = $row;
    }

    $stmt->close();
}


/* =========================================
   RESIDENT NOTICES
========================================= */

$residentNotices = [];

$sql = "
    SELECT DISTINCT
        n.notice_id,
        n.notice_title,
        n.notice_category,
        n.publish_date,
        n.expiry_date,
        n.notice_description,
        n.date_posted,

        CASE

            WHEN NOT EXISTS (
                SELECT 1
                FROM notice_ward check_ward
                WHERE check_ward.notice_id = n.notice_id
            )

            THEN 'Community-wide'

            ELSE CONCAT(
                'Ward ',
                COALESCE(w.ward_number, '')
            )

        END AS notice_scope

    FROM notices n

    LEFT JOIN notice_ward nw
        ON n.notice_id = nw.notice_id

    LEFT JOIN ward w
        ON nw.ward_id = w.ward_id

    WHERE n.deleted_at IS NULL

    AND (
        NOT EXISTS (
            SELECT 1
            FROM notice_ward all_wards
            WHERE all_wards.notice_id = n.notice_id
        )

        OR nw.ward_id = ?
    )

    AND (
        n.publish_date IS NULL
        OR n.publish_date <= CURDATE()
    )

    AND (
        n.expiry_date IS NULL
        OR n.expiry_date >= CURDATE()
    )

    AND LOWER(n.status) IN (
        'approved',
        'active'
    )

    ORDER BY n.date_posted DESC

    LIMIT 5
";

$stmt = $conn->prepare($sql);

if ($stmt) {

    $stmt->bind_param("i", $wardId);
    $stmt->execute();

    $result = $stmt->get_result();

    while ($result && $row = $result->fetch_assoc()) {
        $residentNotices[] = $row;
    }

    $stmt->close();
}


/* =========================================
   WARD SUMMARIES
========================================= */

$wardSummaries = [];

$sql = "
    SELECT
        title,
        file_path,
        created_at,
        ward_id

    FROM ward_summaries

    WHERE ward_id = ?
    AND deleted = 0

    ORDER BY created_at DESC

    LIMIT 5
";

$stmt = $conn->prepare($sql);

if ($stmt) {

    $stmt->bind_param("i", $wardId);
    $stmt->execute();

    $result = $stmt->get_result();

    while ($result && $row = $result->fetch_assoc()) {
        $wardSummaries[] = $row;
    }

    $stmt->close();
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

    <title>M-LINK | Resident Dashboard</title>

    <link
        rel="stylesheet"
        href="residence.css"
    >

    <link
        href="https://fonts.googleapis.com/css2?family=Lora:wght@400;500;600;700&family=Ubuntu:wght@300;400;500;700&display=swap"
        rel="stylesheet"
    >

    <link
        href="https://fonts.googleapis.com/icon?family=Material+Icons"
        rel="stylesheet"
    >
<style>/* =========================================
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
}</style>
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
                class="sidebar-link active"
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
                class="sidebar-link"
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


        <!-- =================================
             TOPBAR
        ================================== -->

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



        <!-- =================================
             DASHBOARD CONTENT
        ================================== -->

        <main class="dashboard-content">


            <!-- =================================
                 HEADING
            ================================== -->

            <div class="dashboard-heading">

                <h1>
                    Welcome,

                    <?php
                    echo htmlspecialchars($firstName);
                    ?>.
                </h1>

                <p>

                    Resident Dashboard

                </p>

            </div>



            <!-- =================================
                 STATISTIC CARDS
            ================================== -->

            <section class="cards">


                <!-- WARD TICKETS -->

                <div class="card">


                    <div class="card-icon">

                        <img
                            src="../icons/ticketW.svg"
                            alt="Ward Tickets"
                        >

                    </div>


                    <div class="card-content">

                        <span>
                            Ward Tickets
                        </span>

                        <strong>
                            <?php echo $totalTickets; ?>
                        </strong>

                    </div>


                </div>



                <!-- IN PROGRESS -->

                <div class="card">


                    <div class="card-icon">

                        <img
                            src="../icons/inprogressG.svg"
                            alt="In Progress"
                        >

                    </div>


                    <div class="card-content">

                        <span>
                            In Progress
                        </span>

                        <strong>
                            <?php echo $inProgressTickets; ?>
                        </strong>

                    </div>


                </div>



                <!-- RESOLVED -->

                <div class="card">


                    <div class="card-icon">

                        <img
                            src="../icons/resolvedX.svg"
                            alt="Resolved"
                        >

                    </div>


                    <div class="card-content">

                        <span>
                            Resolved
                        </span>

                        <strong>
                            <?php echo $resolvedTickets; ?>
                        </strong>

                    </div>


                </div>


            </section>



            <!-- =================================
                 RECENT TICKETS
            ================================== -->

            <section class="dashboard-panel recent-tickets-panel">


                <div class="panel-header">

                    <h2>
                        Recently Reported Tickets
                    </h2>

                    <a href="myTickets.php">
                        View All
                    </a>

                </div>

                <?php if (empty($recentTickets)): ?>


                    <div class="empty-state">

                        <span class="material-icons">
                            confirmation_number
                        </span>

                        <p>
                            No tickets available.
                        </p>

                    </div>


                <?php else: ?>


                    <div class="ticket-table-wrapper">


                        <table class="ticket-table">


                            <thead>

                                <tr>

                                    <th>
                                        Ticket
                                    </th>

                                    <th>
                                        Service
                                    </th>

                                    <th>
                                        Location
                                    </th>

                                    <th>
                                        Priority
                                    </th>

                                    <th>
                                        Status
                                    </th>

                                    <th>
                                        Date
                                    </th>

                                </tr>

                            </thead>


                            <tbody>


                            <?php foreach ($recentTickets as $ticket): ?>


                                <?php

                                $statusText = "Pending";

                                if (!empty($ticket["resolved"])) {

                                    $statusText = "Resolved";

                                } elseif (!empty($ticket["InProgress"])) {

                                    $statusText = "In Progress";
                                }

                                ?>


                                <tr>


                                    <td>

                                        #<?php
                                        echo htmlspecialchars(
                                            $ticket["incident_id"]
                                        );
                                        ?>

                                    </td>


                                    <td>

                                        <?php
                                        echo htmlspecialchars(
                                            $ticket["service_type"]
                                            ?? "Service"
                                        );
                                        ?>

                                    </td>


                                    <td>

                                        <?php
                                        echo htmlspecialchars(
                                            $ticket["street_name"]
                                            ??
                                            $ticket["location_description"]
                                            ??
                                            "-"
                                        );
                                        ?>

                                    </td>


                                    <td>

                                        <?php
                                        echo htmlspecialchars(
                                            $ticket["priority"]
                                            ?? "-"
                                        );
                                        ?>

                                    </td>


                                    <td>

                                        <span
                                            class="ticket-status <?php
                                            echo strtolower(
                                                str_replace(
                                                    " ",
                                                    "-",
                                                    $statusText
                                                )
                                            );
                                            ?>"
                                        >

                                            <?php
                                            echo htmlspecialchars(
                                                $statusText
                                            );
                                            ?>

                                        </span>

                                    </td>


                                    <td>

                                        <?php

                                        if (!empty($ticket["date_reported"])) {

                                            echo date(
                                                "d M Y",
                                                strtotime(
                                                    $ticket["date_reported"]
                                                )
                                            );

                                        } else {

                                            echo "-";
                                        }

                                        ?>

                                    </td>


                                </tr>


                            <?php endforeach; ?>


                            </tbody>


                        </table>


                    </div>


                <?php endif; ?>


            </section>



            <!-- =================================
                 NOTICES + WARD SUMMARIES
            ================================== -->

            <div class="dashboard-grid">


                <!-- =================================
                     NOTICES
                ================================== -->

                <section class="dashboard-panel notices-panel">


                    <div class="panel-header">

                        <h2>
                            Notices
                        </h2>

                    </div>



                    <?php if (empty($residentNotices)): ?>


                        <div class="empty-state">

                            <span class="material-icons">
                                campaign
                            </span>

                            <p>
                                No current notices available.
                            </p>

                        </div>


                    <?php else: ?>


                        <div class="notice-list">


                            <?php foreach ($residentNotices as $notice): ?>


                                <article class="notice-item">


                                    <div class="notice-top">


                                        <div class="notice-heading">


                                            <span class="notice-scope">

                                                <?php
                                                echo htmlspecialchars(
                                                    $notice["notice_scope"]
                                                );
                                                ?>

                                            </span>


                                            <h3>

                                                <?php
                                                echo htmlspecialchars(
                                                    $notice["notice_title"]
                                                );
                                                ?>

                                            </h3>


                                        </div>



                                        <?php if (
                                            !empty(
                                                $notice["notice_category"]
                                            )
                                        ): ?>

                                            <span class="notice-category">

                                                <?php
                                                echo htmlspecialchars(
                                                    $notice["notice_category"]
                                                );
                                                ?>

                                            </span>

                                        <?php endif; ?>


                                    </div>



                                    <p class="notice-description">

                                        <?php

                                        $description =
                                            $notice["notice_description"]
                                            ?? "";

                                        if (strlen($description) > 160) {

                                            $description =
                                                substr(
                                                    $description,
                                                    0,
                                                    160
                                                ) . "...";
                                        }

                                        echo htmlspecialchars(
                                            $description
                                        );

                                        ?>

                                    </p>



                                    <div class="notice-date">

                                        <span class="material-icons">
                                            calendar_today
                                        </span>

                                        <?php

                                        $noticeDate =
                                            $notice["publish_date"]
                                            ??
                                            $notice["date_posted"];

                                        if (!empty($noticeDate)) {

                                            echo date(
                                                "d M Y",
                                                strtotime($noticeDate)
                                            );

                                        } else {

                                            echo "-";
                                        }

                                        ?>

                                    </div>


                                </article>


                            <?php endforeach; ?>


                        </div>


                    <?php endif; ?>


                </section>



                <!-- =================================
                     WARD SUMMARIES
                ================================== -->

                <section class="dashboard-panel summaries-panel">


                    <div class="panel-header">

                        <h2>
                            Ward Summaries
                        </h2>

                    </div>



                    <?php if (empty($wardSummaries)): ?>


                        <div class="empty-state">

                            <span class="material-icons">
                                description
                            </span>

                            <p>
                                No ward summaries available.
                            </p>

                        </div>


                    <?php else: ?>


                        <div class="summary-list">


                            <?php foreach ($wardSummaries as $summary): ?>


                                <div class="summary-item">


                                    <div class="summary-icon">

                                        <span class="material-icons">
                                            description
                                        </span>

                                    </div>



                                    <div class="summary-content">


                                        <h3>

                                            <?php
                                            echo htmlspecialchars(
                                                $summary["title"]
                                            );
                                            ?>

                                        </h3>


                                        <span>

                                            <?php

                                            if (!empty($summary["created_at"])) {

                                                echo date(
                                                    "d M Y",
                                                    strtotime(
                                                        $summary["created_at"]
                                                    )
                                                );
                                            }

                                            ?>

                                        </span>


                                    </div>



                                    <?php if (
                                        !empty(
                                            $summary["file_path"]
                                        )
                                    ): ?>

                                        <a
                                            href="<?php
                                            echo htmlspecialchars(
                                                $summary["file_path"]
                                            );
                                            ?>"
                                            class="summary-view-btn"
                                            target="_blank"
                                            title="View Summary"
                                        >

                                            <span class="material-icons">
                                                visibility
                                            </span>

                                        </a>

                                    <?php endif; ?>


                                </div>


                            <?php endforeach; ?>


                        </div>


                    <?php endif; ?>


                </section>


            </div>


        </main>


    </div>


</div>


<script src="residence.js"></script>


</body>

</html>