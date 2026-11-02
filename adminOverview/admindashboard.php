<?php

session_start();

//-----------------------------------------------------------------
// PAGE-LEVEL ERROR HANDLING
//
// Every error encountered on this page is collected here and shown
// on this same page instead of dying or bouncing the admin to a
// different page. The session is only ever destroyed for the one
// case where it truly has to be (no valid login at all), and the
// only redirect on this page is the required "please log in first"
// redirect below.
//-----------------------------------------------------------------
$pageErrors     = [];
$conn           = null;
$accessDenied   = false;
$sessionEnded   = false;
$deniedUsername = "";

// Avoid mysqli throwing exceptions on error; we want to keep using
// the existing "check the return value" style everywhere on this
// page so a database hiccup can be reported inline instead of
// producing an uncaught fatal error.
if (function_exists("mysqli_report")) {
    mysqli_report(MYSQLI_REPORT_OFF);
}

try {

    require_once "Error404Connection.php";

    if (!isset($conn) || !$conn) {
        $pageErrors[] = "Could not establish a database connection. Some information below may be unavailable.";
        $conn = null;
    }

} catch (\Throwable $connException) {

    $pageErrors[] = "Could not establish a database connection: " . $connException->getMessage();
    $conn = null;

}

//check if administrator is logged in
//(this is the one case that must redirect, since there is nothing
// safe to show on this page without a logged-in session)
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
    // Not authorized for this dashboard. The session is left alone
    // (it may well be a valid, logged-in session for a different
    // role) and we stay on this page with an access-denied message
    // rather than redirecting away.
    $accessDenied  = true;
    $pageErrors[]  = "Access denied. Your account does not have administrator permissions.";
}

//administrator information
//(sensible fallbacks so the page can still render if the lookup
// below can't run or doesn't find anything)
$adminName = $adminUsername ?? "Administrator";
$adminRole = $userRole !== "" ? $userRole : "admin";
$profileImage = "";
$profileImageUrl = "profile.jpg";

//get administrator information
//(only bother querying if the admin is actually authorized and we
// have a working connection - otherwise keep the fallbacks above
// and just surface the reason in $pageErrors)
if ($accessDenied) {

    // nothing to fetch; access-denied message is already queued

} elseif (!$conn) {

    $pageErrors[] = "Could not load administrator details because the database connection is unavailable.";

} else {

    $stmt = $conn->prepare("SELECT
                                u.first_Name,
                                u.surname,
                                u.User_role,
                                p.image_path
                            FROM `user` u
                            LEFT JOIN profile_images p
                                ON u.profile_id = p.profile_id
                            WHERE u.username = ?
                            AND LOWER(u.User_role) = 'admin'
                            LIMIT 1
                        ");

    if (!$stmt) {

        $pageErrors[] = "Database error while preparing administrator lookup: " . htmlspecialchars($conn->error);

    } else {

        $stmt->bind_param(
            "s",
            $adminUsername
        );

        if (!$stmt->execute()) {

            $pageErrors[] = "Database error while loading administrator details: " . htmlspecialchars($stmt->error);

        } else {

            $result = $stmt->get_result();

            if (
                $result &&
                $result->num_rows > 0
            ) {

                $admin = $result->fetch_assoc();

                $adminName = trim(
                    $admin["first_Name"]
                    . " "
                    . ($admin["surname"] ?? "")
                );

                $adminRole = $admin["User_role"];

                $profileImage = $admin["image_path"] ?? "";

            } else {

                // The logged-in session refers to an admin account
                // that no longer exists (or is no longer an admin).
                // This is one of the cases where it genuinely is
                // appropriate to end the session - we still don't
                // redirect away, we just stop treating the visitor
                // as logged in and let them log in again from here.
                $deniedUsername = $adminUsername;
                $pageErrors[]   = "Your administrator account could not be found. Please log in again.";
                $accessDenied   = true;
                $sessionEnded   = true;

                $_SESSION = [];
                session_unset();
                session_destroy();

            }

        }

        $stmt->close();

    }

}

//profile picture
if (!empty($profileImage)) {

    $possibleProfilePath =
        __DIR__
        . DIRECTORY_SEPARATOR
        . str_replace(
            "/",
            DIRECTORY_SEPARATOR,
            $profileImage
        );

    if (file_exists($possibleProfilePath)) {

        $profileImageUrl =
            $profileImage
            . "?v="
            . filemtime($possibleProfilePath);

    }

}

//set greeting
$currentHour = (int)date("H");

if ($currentHour < 12) {

    $greeting = "Good Morning";

} elseif ($currentHour < 18) {

    $greeting = "Good Afternoon";

} else {

    $greeting = "Good Evening";

}

//check if table exists
function tableExists($conn, $tableName)
{

    if (!$conn) {

        return false;

    }

    $tableName =
        $conn->real_escape_string(
            $tableName
        );

    $result = $conn->query(
        "SHOW TABLES LIKE '$tableName'"
    );

    return (
        $result &&
        $result->num_rows > 0
    );

}

//count table records
function getTableCount($conn, $tableName)
{

    if (!tableExists($conn, $tableName)) {

        return 0;

    }

    $safeTable =
        "`"
        . str_replace(
            "`",
            "``",
            $tableName
        )
        . "`";

    $result = $conn->query("
        SELECT COUNT(*) AS total
        FROM $safeTable
    ");

    if ($result) {

        $row = $result->fetch_assoc();

        return (int)$row["total"];

    }

    return 0;

}

//total administrators
$totalAdmins = 0;

//total tickets
$totalTickets = 0;

//total users
$totalUsers = 0;

//total notices
$totalNotices = 0;

//pending verification
$pendingVerification = 0;

//recent administrator activity
$recentAdmins = false;

//only bother querying stats when the viewer is actually authorized
//to see them; on an access-denied page there is nothing to compute
if (!$accessDenied) {

    if (!$conn) {

        $pageErrors[] = "Dashboard statistics could not be loaded because the database connection is unavailable.";

    } else {

        $totalAdmins =
            getTableCount(
                $conn,
                "admin"
            );

        if (tableExists($conn, "incident_ticket")) {

            $totalTickets =
                getTableCount(
                    $conn,
                    "incident_ticket"
                );

        }

        $totalUsers =
            getTableCount(
                $conn,
                "user"
            );

        $noticeTables = [
            "notices",
            "ward_notices",
            "announcements"
        ];

        foreach ($noticeTables as $table) {

            if (tableExists($conn, $table)) {

                $totalNotices =
                    getTableCount(
                        $conn,
                        $table
                    );

                break;

            }

        }

        if (tableExists($conn, "user")) {

            $result = $conn->query("
                SELECT COUNT(*) AS total
                FROM `user`
                WHERE verified = 0
            ");

            if ($result) {

                $row = $result->fetch_assoc();

                $pendingVerification =
                    (int)$row["total"];

            } else {

                $pageErrors[] = "Could not load the pending verification count: " . htmlspecialchars($conn->error);

            }

        }

        if (tableExists($conn, "admin")) {

            $recentAdmins = $conn->query("
                SELECT
                    username,
                    last_login_date,
                    registered_date
                FROM admin
                ORDER BY last_login_date DESC
                LIMIT 5
            ");

            if (!$recentAdmins) {

                $pageErrors[] = "Could not load recent administrator activity: " . htmlspecialchars($conn->error);

            }

        }

    }

}

//new "Ward Councillor" / "Municipal Staff" sign-up notifications
//(topbar bell badge)
//
//Each of these roles is stored in `user`.`User_role` under two
//spelling variants:
//   Ward Councillor    ->  "ward_councillor" / "Ward Councillor"
//   Municipal Staff     ->  "municipal_staff" / "Municipal Staff"
//so we normalize (lowercase, underscores -> spaces) before matching,
//and only count accounts that are still unverified (verified = 0).
$newSignupNotifications = 0;

if (!$accessDenied) {

    if (!$conn) {

        $pageErrors[] = "New sign-up notifications could not be loaded because the database connection is unavailable.";

    } elseif (tableExists($conn, "user")) {

        $signupResult = $conn->query("
            SELECT COUNT(*) AS total
            FROM `user`
            WHERE verified = 0
            AND LOWER(REPLACE(User_role, '_', ' ')) IN ('ward councillor', 'municipal staff')
        ");

        if ($signupResult) {

            $row = $signupResult->fetch_assoc();

            $newSignupNotifications = (int)$row["total"];

        } else {

            $pageErrors[] = "Could not load new sign-up notifications: " . htmlspecialchars($conn->error);

        }

    }

}

//unread internal messages for this admin (topbar "Messages" badge)
$unreadMessages = 0;

if (!$accessDenied) {

    if (!$conn) {

        $pageErrors[] = "Unread message count could not be loaded because the database connection is unavailable.";

    } elseif (tableExists($conn, "internal_messages")) {

        $msgStmt = $conn->prepare("
            SELECT COUNT(*) AS total
            FROM internal_messages
            WHERE receiver_username = ?
            AND is_read = 0
        ");

        if (!$msgStmt) {

            $pageErrors[] = "Could not prepare unread message count query: " . htmlspecialchars($conn->error);

        } else {

            $msgStmt->bind_param(
                "s",
                $adminUsername
            );

            if (!$msgStmt->execute()) {

                $pageErrors[] = "Could not load unread message count: " . htmlspecialchars($msgStmt->error);

            } else {

                $msgResult = $msgStmt->get_result();

                if ($msgResult) {

                    $row = $msgResult->fetch_assoc();

                    $unreadMessages = (int)$row["total"];

                }

            }

            $msgStmt->close();

        }

    }

}

$today = date("l, d F Y");

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
        M-Link | Admin Dashboard
    </title>

    <!-- GOOGLE FONTS -->

    <link
        rel="preconnect"
        href="https://fonts.googleapis.com"
    >

    <link
        rel="preconnect"
        href="https://fonts.gstatic.com"
        crossorigin
    >

    <link
        href="https://fonts.googleapis.com/css2?family=Lora:wght@400;500;600;700&family=Ubuntu:wght@400;500;600;700&display=swap"
        rel="stylesheet"
    >

    <!-- MATERIAL ICONS -->

    <link
        href="https://fonts.googleapis.com/icon?family=Material+Icons"
        rel="stylesheet"
    >

    <!-- ADMIN DASHBOARD CSS -->

    <link
        rel="stylesheet"
        href="admindashboard.css"
    >
<style>

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

/* 
   DASHBOARD HEADING
 */

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

/* 
   STATISTIC CARDS
 */

.cards {
    width: 100%;

    display: grid;

    grid-template-columns:
        repeat(
            5,
            minmax(0, 1fr)
        );

    gap: 18px;

    margin-bottom: 30px;
}

.card {
    min-width: 0;
    min-height: 125px;

    display: flex;
    align-items: center;

    gap: 15px;

    padding: 20px;

    border: 1px solid var(--border);
    border-radius: 12px;

    background: var(--white);

    box-shadow: 0 3px 12px rgba(0, 0, 0, 0.04);
}

.card-icon {
    width: 50px;
    height: 50px;
    min-width: 50px;

    display: flex;
    align-items: center;
    justify-content: center;

    border-radius: 10px;

    background: #f1eee5;
}

.card-icon img {
    width: 27px;
    height: 27px;

    object-fit: contain;
}

.card-content {
    min-width: 0;

    display: flex;
    flex-direction: column;

    gap: 6px;
}

.card-content span {
    font-size: 11px;
    font-weight: 500;

    color: #6f7474;
}

.card-content strong {
    font-family: "Lora", serif;

    font-size: 27px;
    font-weight: 600;

    color: var(--teal);
}

/* 
   LOWER DASHBOARD
 */

.dashboard-grid {
    width: 100%;

    display: grid;

    grid-template-columns:
        minmax(0, 1.3fr)
        minmax(300px, 0.7fr);

    gap: 22px;

    align-items: start;
}

/* 
   DASHBOARD PANEL
 */

.dashboard-panel {
    width: 100%;

    border: 1px solid var(--border);
    border-radius: 12px;

    background: var(--white);

    overflow: hidden;

    box-shadow: 0 3px 12px rgba(0, 0, 0, 0.04);
}

.panel-header {
    padding: 20px 22px;

    border-bottom: 1px solid #eeeae2;
}

.panel-header h2 {
    font-family: "Lora", serif;

    font-size: 19px;
    font-weight: 600;

    color: var(--teal);
}

/* 
   ADMIN ACTIVITY
x */

.activity-list {
    width: 100%;
}

.activity-item {
    display: flex;
    align-items: center;

    gap: 14px;

    padding: 17px 22px;

    border-bottom: 1px solid #eeeae2;
}

.activity-item:last-child {
    border-bottom: none;
}

.activity-icon {
    width: 42px;
    height: 42px;
    min-width: 42px;

    display: flex;
    align-items: center;
    justify-content: center;

    border-radius: 50%;

    background: #f1eee5;
}

.activity-icon img {
    width: 23px;
    height: 23px;

    object-fit: contain;
}

.activity-details {
    flex: 1;

    min-width: 0;
}

.activity-details strong {
    display: block;

    color: var(--teal);

    font-size: 13px;
    font-weight: 600;

    word-break: break-word;
}

.activity-details span {
    display: block;

    margin-top: 4px;

    color: #8a8d8d;

    font-size: 10px;
}

.activity-date {
    flex-shrink: 0;

    color: #777c7c;

    font-size: 10px;

    text-align: right;
}

/* 
   QUICK ACTIONS
 */

.quick-actions {
    width: 100%;

    padding: 5px 22px 12px;
}

.quick-actions a {
    display: flex;
    align-items: center;

    gap: 13px;

    padding: 15px 0;

    border-bottom: 1px solid #eeeae2;

    color: var(--text);

    transition: color 0.2s ease;
}

.quick-actions a:last-child {
    border-bottom: none;
}

.quick-actions a:hover {
    color: var(--green);
}

.quick-action-icon {
    width: 24px;
    height: 24px;
    min-width: 24px;

    object-fit: contain;
}

.quick-actions strong {
    font-size: 12px;
    font-weight: 600;
}

/* 
   EMPTY STATE
x */

.empty-state {
    min-height: 160px;

    display: flex;
    flex-direction: column;

    align-items: center;
    justify-content: center;

    gap: 8px;

    padding: 25px;

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

/* 
   RESPONSIVE
 */

@media (max-width: 1250px) {

    .cards {
        grid-template-columns:
            repeat(
                3,
                minmax(0, 1fr)
            );
    }
}

@media (max-width: 1000px) {

    .cards {
        grid-template-columns:
            repeat(
                2,
                minmax(0, 1fr)
            );
    }

    .dashboard-grid {
        grid-template-columns: 1fr;
    }
}

/* 
   MOBILE
 */

@media (max-width: 750px) {

    .dashboard-heading h1 {
        font-size: 25px;
    }

    .cards {
        grid-template-columns: 1fr;
    }

    .dashboard-panel {
        border-radius: 10px;
    }
}

/* 
   SMALL MOBILE
 */

@media (max-width: 480px) {

    .card {
        padding: 18px;
    }

    .activity-item {
        align-items: flex-start;

        flex-wrap: wrap;
    }

    .activity-date {
        width: 100%;

        padding-left: 56px;

        text-align: left;
    }
}

</style>

    <!-- SHARED ADMIN SIDEBAR / TOPBAR / FOOTER STYLES (keep last) -->
    <link
        rel="stylesheet"
        href="adminLayout.css"
    >
</head>

<body>

<?php if ($accessDenied): ?>

    <!-- 
         ACCESS DENIED
         (shown on this same page, no redirect;
         session left intact) -->

    <div class="access-denied-wrap" style="
        min-height: 100vh;
        display: flex;
        align-items: center;
        justify-content: center;
        padding: 24px;
    ">

        <div style="
            max-width: 460px;
            width: 100%;
            background: var(--white, #fff);
            border: 1px solid var(--border, #e4e0d7);
            border-radius: 12px;
            padding: 32px;
            text-align: center;
        ">

            <span class="material-icons" style="font-size: 48px; color: #b3261e;">
                lock
            </span>

            <h1 style="margin: 16px 0 8px; font-size: 20px;">
                Access Denied
            </h1>

            <?php foreach ($pageErrors as $pageError): ?>
                <p style="margin: 8px 0; color: var(--text, #263238);">
                    <?php echo htmlspecialchars($pageError); ?>
                </p>
            <?php endforeach; ?>

            <?php if ($sessionEnded): ?>

                <p style="margin: 16px 0 24px; color: rgba(38, 48, 56, 0.75); font-size: 14px;">
                    Your session has been ended for
                    <strong><?php echo htmlspecialchars($deniedUsername); ?></strong>.
                    Please log in again below.
                </p>

            <?php else: ?>

                <p style="margin: 16px 0 24px; color: rgba(38, 48, 56, 0.75); font-size: 14px;">
                    You're signed in as
                    <strong><?php echo htmlspecialchars($_SESSION["username"] ?? ""); ?></strong>,
                    but this account does not have administrator access.
                </p>

            <?php endif; ?>

            <a
                href="../PUBLIC PAGES/login.php"
                style="
                    display: inline-block;
                    padding: 10px 20px;
                    background: var(--green, #4b561b);
                    color: #fff;
                    border-radius: 8px;
                    font-weight: 600;
                "
            >
                Go to Login
            </a>

        </div>

    </div>

<?php else: ?>

<?php
$adminActivePage = "dashboard";
require __DIR__ . "/adminHeader.php";
?>

        <!-- DASHBOARD CONTENT -->

        <main class="dashboard-content">

            <?php if (!empty($pageErrors)): ?>

                <!-- 
                     PAGE ERRORS / WARNINGS
                     -shown inline, page must keep working
                -->

                <section
                    class="dashboard-errors"
                    style="
                        background: #fdecea;
                        border: 1px solid #f5c6c0;
                        color: #7a1f14;
                        border-radius: 10px;
                        padding: 16px 20px;
                        margin-bottom: 24px;
                    "
                >

                    <strong style="display: block; margin-bottom: 6px;">
                        <span class="material-icons" style="vertical-align: middle; font-size: 18px;">
                            warning
                        </span>
                        Some information on this page could not be loaded
                    </strong>

                    <ul style="margin: 0; padding-left: 22px;">

                        <?php foreach ($pageErrors as $pageError): ?>

                            <li><?php echo htmlspecialchars($pageError); ?></li>

                        <?php endforeach; ?>

                    </ul>

                </section>

            <?php endif; ?>

            <!-- =====================================
                 WELCOME
            ====================================== -->

            <section class="dashboard-heading">

                <h1>

                    <?php
                    echo htmlspecialchars(
                        $greeting
                    );
                    ?>,

                    <?php
                    echo htmlspecialchars(
                        $adminName
                    );
                    ?>!

                </h1>

                <p>
                    Here's what's happening in Makhanda today.
                </p>

            </section>

            <!-- =====================================
                 DASHBOARD CARDS
            ====================================== -->

            <section class="cards">

                <!-- MUNICIPAL TICKETS -->

                <div class="card">

                    <div class="card-icon">

                        <img
                            src="../icons/local_activity.svg"
                            alt="Tickets"
                        >

                    </div>

                    <div class="card-content">

                        <span>
                            Municipal Tickets
                        </span>

                        <strong>

                            <?php
                            echo number_format(
                                $totalTickets
                            );
                            ?>

                        </strong>

                    </div>

                </div>

                <!-- USERS -->

                <div class="card">

                    <div class="card-icon">

                        <img
                            src="../icons/manage_accounts.svg"
                            alt="Users"
                        >

                    </div>

                    <div class="card-content">

                        <span>
                            Registered Users
                        </span>

                        <strong>

                            <?php
                            echo number_format(
                                $totalUsers
                            );
                            ?>

                        </strong>

                    </div>

                </div>

                <!-- ADMINISTRATORS -->

                <div class="card">

                    <div class="card-icon">

                        <img
                            src="../icons/profile.svg"
                            alt="Administrators"
                        >

                    </div>

                    <div class="card-content">

                        <span>
                            System Admins
                        </span>

                        <strong>

                            <?php
                            echo number_format(
                                $totalAdmins
                            );
                            ?>

                        </strong>

                    </div>

                </div>

                <!-- NOTICES -->

                <div class="card">

                    <div class="card-icon">

                        <img
                            src="../icons/notificationss.svg"
                            alt="Notices"
                        >

                    </div>

                    <div class="card-content">

                        <span>
                            Notices
                        </span>

                        <strong>

                            <?php
                            echo number_format(
                                $totalNotices
                            );
                            ?>

                        </strong>

                    </div>

                </div>

                <!-- PENDING VERIFICATION -->

                <div class="card">

                    <div class="card-icon">

                        <img
                            src="../icons/pendingVerification.svg"
                            alt="Pending Verification"
                        >

                    </div>

                    <div class="card-content">

                        <span>
                            Pending Verification
                        </span>

                        <strong>

                            <?php
                            echo number_format(
                                $pendingVerification
                            );
                            ?>

                        </strong>

                    </div>

                </div>

            </section>

            <!-- =====================================
                 LOWER DASHBOARD
            ====================================== -->

            <section class="dashboard-grid">

                <!-- =====================================
                     RECENT ADMIN ACTIVITY
                ====================================== -->

                <div class="dashboard-panel">

                    <div class="panel-header">

                        <h2>
                            Recent Admin Activity
                        </h2>

                    </div>

                    <div class="activity-list">

                        <?php if (
                            $recentAdmins &&
                            $recentAdmins->num_rows > 0
                        ): ?>

                            <?php while (
                                $activity =
                                    $recentAdmins->fetch_assoc()
                            ): ?>

                                <div class="activity-item">

                                    <div class="activity-icon">

                                        <img
                                            src="../icons/admin.svg"
                                            alt="Administrator"
                                        >

                                    </div>

                                    <div class="activity-details">

                                        <strong>

                                            <?php
                                            echo htmlspecialchars(
                                                $activity["username"]
                                            );
                                            ?>

                                        </strong>

                                        <span>
                                            Administrator account
                                        </span>

                                    </div>

                                    <div class="activity-date">

                                        <?php

                                        if (
                                            !empty(
                                                $activity["last_login_date"]
                                            )
                                        ) {

                                            echo htmlspecialchars(
                                                date(
                                                    "d M Y, H:i",
                                                    strtotime(
                                                        $activity["last_login_date"]
                                                    )
                                                )
                                            );

                                        } else {

                                            echo "No login recorded";

                                        }

                                        ?>

                                    </div>

                                </div>

                            <?php endwhile; ?>

                        <?php else: ?>

                            <div class="empty-state">

                                <span class="material-icons">
                                    history
                                </span>

                                <p>
                                    No recent administrator activity.
                                </p>

                            </div>

                        <?php endif; ?>

                    </div>

                </div>

                <!-- =====================================
                     QUICK ACTIONS
                ====================================== -->

                <div class="dashboard-panel">

                    <div class="panel-header">

                        <h2>
                            Quick Actions
                        </h2>

                    </div>

                    <div class="quick-actions">

                        <!-- MANAGE USERS -->

                        <a href="UserM.php">

                            <img
                                src="../icons/manage_accounts.svg"
                                alt="Manage Users"
                                class="quick-action-icon"
                            >

                            <strong>
                                Manage Users
                            </strong>

                        </a>

                        <!-- VERIFY ACCOUNTS -->

                        <a href="verifyAccount.php">

                            <img
                                src="../icons/pendingVerification.svg"
                                alt="Verify Accounts"
                                class="quick-action-icon"
                            >

                            <strong>
                                Verify Accounts
                            </strong>

                        </a>

                        <!-- MUNICIPAL TICKETS -->

                        <a href="WardTicketManagement.php">

                            <img
                                src="../icons/local_activity.svg"
                                alt="Municipal Tickets"
                                class="quick-action-icon"
                            >

                            <strong>
                                Municipal Tickets
                            </strong>

                        </a>

                        <!-- MANAGE NOTICES -->

                        <a href="manageNotices.php">

                            <img
                                src="../icons/notificationss.svg"
                                alt="Manage Notices"
                                class="quick-action-icon"
                            >

                            <strong>
                                Manage Notices
                            </strong>

                        </a>

                        <!-- WARD MANAGEMENT -->

                        <!-- <a href="generateSummaryForm.php">

                            <img
                                src="../icons/man.svg"
                                alt="Ward Management"
                                class="quick-action-icon"
                            >

                            <strong>
                                Ward Management
                            </strong> -->

                        </a>

                    </div>

                </div>

            </section>

        </main>

<?php require __DIR__ . "/adminFooter.php"; ?>

<?php endif; ?>

</body>

</html>

<?php

if ($conn) {
    $conn->close();
}

?>