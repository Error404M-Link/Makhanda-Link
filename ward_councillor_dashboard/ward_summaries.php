<?php

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once "Error404Connection.php";
require_once "detailsOfTheLoggedInUser.php";

//login check
if (
    empty($_SESSION['username']) ||
    !isset($_SESSION['role']) ||
    $_SESSION['role'] !== 'ward_councillor'
) {
    header("Location: ../PUBLIC PAGES/login.php");
    exit();
}


function e($value)
{
    return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
}



$username = $_SESSION['username'];

$loggedInCouncillor = null;

$stmt = $conn->prepare("
    SELECT
        wc.username,
        wc.ward_id,
        wc.date_appointed,
        wc.contact,
        u.first_Name,
        u.surname,
        u.profile_id,
        pi.image_path
    FROM ward_councillor wc
    LEFT JOIN user u
        ON wc.username = u.username
    LEFT JOIN profile_images pi
        ON u.profile_id = pi.profile_id
    WHERE wc.username = ?
    LIMIT 1
");

if ($stmt) {

    $stmt->bind_param("s", $username);
    $stmt->execute();

    $result = $stmt->get_result();

    $loggedInCouncillor = $result->fetch_assoc();

    $stmt->close();
}



if (!$loggedInCouncillor) {
    die("No councillor record was found for this account.");
}


/* =========================================================
   LOGGED-IN COUNCILLOR DETAILS
========================================================= */

$loggedInWardNum = (int)($loggedInCouncillor['ward_id'] ?? 0);

if ($loggedInWardNum < 1 || $loggedInWardNum > 14) {
    die("No valid ward has been assigned to this councillor.");
}


$loggedInFullName = trim(
    ($loggedInCouncillor['first_Name'] ?? '') . ' ' .
    ($loggedInCouncillor['surname'] ?? '')
);


if (empty($loggedInFullName)) {
    $loggedInFullName = $username;
}


/* =========================================================
   LOGGED-IN COUNCILLOR PROFILE IMAGE
========================================================= */

$loggedInProfileImage = 'profile.jpg';

if (!empty($loggedInCouncillor['image_path'])) {
    $loggedInProfileImage = $loggedInCouncillor['image_path'];
}


/* =========================================================
   CURRENT WARD
========================================================= */

$ward = $loggedInWardNum;


/* =========================================================
   UNREAD MESSAGE COUNT
========================================================= */

$unread_count = 0;

$unread_stmt = $conn->prepare("
    SELECT COUNT(*) AS unread_count
    FROM internal_messages
    WHERE receiver_username = ?
    AND is_read = 0
");

if ($unread_stmt) {

    $unread_stmt->bind_param("s", $username);
    $unread_stmt->execute();

    $unread_result = $unread_stmt->get_result();

    $unread_data = $unread_result->fetch_assoc();

    $unread_count = (int)($unread_data['unread_count'] ?? 0);

    $unread_stmt->close();
}


/* =========================================================
   GET COUNCILLOR FOR THIS WARD
========================================================= */

$councillorRow = null;

$stmt = $conn->prepare("
    SELECT
        wc.username,
        wc.ward_id,
        wc.date_appointed,
        wc.contact,
        u.first_Name,
        u.surname,
        u.profile_id,
        pi.image_path
    FROM ward_councillor wc
    LEFT JOIN user u
        ON wc.username = u.username
    LEFT JOIN profile_images pi
        ON u.profile_id = pi.profile_id
    WHERE wc.ward_id = ?
    LIMIT 1
");

if ($stmt) {

    $stmt->bind_param("i", $ward);
    $stmt->execute();

    $result = $stmt->get_result();

    $councillorRow = $result->fetch_assoc();

    $stmt->close();
}



$selectedWardCouncillorName = 'Ward Councillor';

$councillorPhoto = 'assets/img/councillor-placeholder.png';

$councillorContact = '';

$councillorEmail = '';




if (!empty($councillorRow)) {

    /* NAME */

    $selectedWardCouncillorName = trim(
        ($councillorRow['first_Name'] ?? '') . ' ' .
        ($councillorRow['surname'] ?? '')
    );

    if (empty($selectedWardCouncillorName)) {

        $selectedWardCouncillorName =
            $councillorRow['username'] ?? 'Ward Councillor';
    }


    /* CONTACT */

    $councillorContact =
        $councillorRow['contact'] ?? '';


    /* EMAIL / USERNAME */

    $councillorEmail =
        $councillorRow['username'] ?? '';


    /* PROFILE IMAGE */

    if (!empty($councillorRow['image_path'])) {

        $councillorPhoto =
            $councillorRow['image_path'];
    }
}


/* CURRENT WARD INFORMATION */

$currentWard = [

    "name" => "Ward " . $ward,

    "map" => "#",

    "photo" => $councillorPhoto,

    "councillor" => $selectedWardCouncillorName,

    "party" => "N/A",

    "cell" => $councillorContact,

    "email" => $councillorEmail,

    "makhanda_email" => "N/A"

];


/*WARD SUMMARIES*/

$summaries = [];


/*
   Councillor can view deleted summaries only
   for their own ward.
*/

$showDeleted = (
    isset($_GET['show_deleted']) &&
    $_GET['show_deleted'] === '1'
);


/* 
   SUMMARY QUERY*/

if ($showDeleted) {

    $sql = "
        SELECT *
        FROM ward_summaries
        WHERE ward_id = ?
        AND deleted = 1
        ORDER BY created_at DESC
    ";

} else {

    $sql = "
        SELECT *
        FROM ward_summaries
        WHERE ward_id = ?
        AND deleted = 0
        ORDER BY created_at DESC
    ";
}


$stmt = $conn->prepare($sql);

if ($stmt) {

    $stmt->bind_param("i", $ward);

    $stmt->execute();

    $result = $stmt->get_result();

    while ($row = $result->fetch_assoc()) {

        $summaries[] = $row;
    }

    $stmt->close();
}


$summaryCount = count($summaries);

$ward_id      = $loggedInWardNum;
$fullName     = $loggedInFullName;
$userData['image_path'] = $loggedInProfileImage;
$activePage   = 'summaries';
$dashboardUrl = 'wardcouncilorDashboard.php';

// Unread notifications for the top bar bell (same as the dashboard)
$unreadNotificationCount = 0;
$stmt = $conn->prepare("
    SELECT COUNT(*) AS unread_count
    FROM notification
    WHERE username = ?
    AND notification_type = 'new_ticket'
    AND is_read = 0
");
if ($stmt) {
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $res = $stmt->get_result();
    if ($res && $r = $res->fetch_assoc()) {
        $unreadNotificationCount = (int) $r['unread_count'];
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

    <title>
        <?php echo e($currentWard["name"]); ?>
        | M-Link Ward Summaries
    </title>


    <!-- Restores the sidebar state before the page draws (no flash) -->
<script>
try { if (localStorage.getItem("sidebarState") === "closed") document.documentElement.classList.add("sidebar-collapsed"); } catch (e) {}
</script>

<link rel="stylesheet" href="Wardcouncillo.css?v=<?php echo filemtime(__DIR__ . '/Wardcouncillo.css'); ?>">
<link rel="stylesheet" href="ward_summaries.css?v=<?php echo filemtime(__DIR__ . '/ward_summaries.css'); ?>">

<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">

</head>
<body>

<header class="topbar">
    <button class="menu-btn" id="menuBtn" type="button" aria-label="Toggle menu" aria-controls="sidebar" aria-expanded="true">
        <i class="fa-solid fa-bars"></i>
    </button>

    <a href="logout.php" class="logo-link" title="M-LINK Homepage">
        <img src="makanaLogo.svg" alt="M-Link logo" class="topbar-logo">
    </a>

    <div class="top-right">
        <a href="notifications.php" class="topbar-icon-btn" title="Notifications">
            <span class="notification-icon-wrapper">
                <img src="../icons/add_alert.svg" alt="" class="topbar-icon-img">
                <?php if ($unreadNotificationCount > 0): ?>
                    <span class="notification-badge">
                        <?php echo $unreadNotificationCount > 99 ? "99+" : $unreadNotificationCount; ?>
                    </span>
                <?php endif; ?>
            </span>
            <span class="topbar-icon-label">Notifications</span>
        </a>

        <a href="messages.php" class="topbar-icon-btn" title="Messages">
            <img src="../icons/message.svg" alt="" class="topbar-icon-img">
            <span class="topbar-icon-label">Messages</span>
        </a>

        <div class="user">
            <img src="<?php echo e($loggedInProfileImage); ?>" alt="Profile Picture" id="profileImage">
            <div class="user-details">
                <h4><?php echo e($loggedInFullName); ?></h4>
                <small>Ward Councillor</small>
            </div>
        </div>
    </div>
</header>

<?php include 'ward_sidebar.php'; ?>


<!--MAIN CONTENT -->

<main class="main-content" id="mainContent">


    <!-- PAGE HEADER-->

    <section class="page-header">

        <div>

            <span class="section-label">
                MY WARD
            </span>

            <h2>
                WARD <?php echo e($ward); ?> SUMMARIES
            </h2>

            <p>
                View and manage summaries for your assigned ward.
            </p>

        </div>

    </section>


    <!-- WARD HERO -->

    <section class="ward-hero">


        <!-- WARD INFORMATION -->

        <div class="ward-info">

            <span class="ward-tag">
                MAKANA LOCAL MUNICIPALITY
            </span>


            <h2>
                <?php echo e($currentWard["name"]); ?>
            </h2>


            <p>
                Stay informed about meetings,
                announcements, community matters
                and developments in your ward.
            </p>


            <div class="hero-buttons">

                <!-- SUBMIT SUMMARY -->

                <a
                    href="uploadSummary.php"
                    class="map-btn"
                >

                    + Submit Ward Summary

                </a>


                <!-- DELETED / ACTIVE SUMMARIES -->

                <?php if ($showDeleted): ?>

                    <a
                        href="ward_summaries.php"
                        class="map-btn"
                        style="background-color:#eadfce;"
                    >

                        View Active Summaries

                    </a>

                <?php else: ?>

                    <a
                        href="ward_summaries.php?show_deleted=1"
                        class="map-btn"
                       
                    >

                        View Deleted Summaries

                    </a>

                <?php endif; ?>

            </div>

        </div>


        <!-- COUNCILLOR CARD-->

        <div class="councillor-card">


            <!-- PHOTO -->

            <div class="councillor-photo">

                <img
                    src="<?php echo e($currentWard["photo"]); ?>"
                    alt="<?php echo e($currentWard["councillor"]); ?>"
                >

            </div>


            <!-- DETAILS -->

            <div class="councillor-details">


                <span class="councillor-label">
                    WARD COUNCILLOR
                </span>


                <h3>
                    <?php echo e($currentWard["councillor"]); ?>
                </h3>


                <p class="ward-name">
                    <?php echo e($currentWard["name"]); ?>
                </p>


                <!-- CONTACT -->

                <div class="contact-item">

                    <span class="contact-icon">

                        <i class="fa-solid fa-phone"></i>

                    </span>


                    <div>

                        <small>
                            Cell Number
                        </small>


                        <?php if (!empty($currentWard["cell"])): ?>

                            <a
                                href="tel:<?php echo e($currentWard["cell"]); ?>"
                            >

                                <?php echo e($currentWard["cell"]); ?>

                            </a>

                        <?php else: ?>

                            <span>
                                Not available
                            </span>

                        <?php endif; ?>

                    </div>

                </div>


                <!-- EMAIL -->

                <div class="contact-item">

                    <span class="contact-icon">

                        <i class="fa-solid fa-envelope"></i>

                    </span>


                    <div>

                        <small>
                            Work Email
                        </small>


                        <?php if (!empty($currentWard["email"])): ?>

                            <a
                                href="mailto:<?php echo e($currentWard["email"]); ?>"
                            >

                                <?php echo e($currentWard["email"]); ?>

                            </a>

                        <?php else: ?>

                            <span>
                                Not available
                            </span>

                        <?php endif; ?>

                    </div>

                </div>


                <!-- MUNICIPAL EMAIL -->

                <div class="contact-item">

                    <span class="contact-icon">

                        <i class="fa-solid fa-building"></i>

                    </span>


                    <div>

                        <small>
                            Municipal Email
                        </small>

                        <span>
                            N/A
                        </span>

                    </div>

                </div>

            </div>

        </div>

    </section>


    <!-- SUMMARY TOOLBAR-->

    <section class="summary-toolbar">

        <div>

            <h2>
                Ward Summaries
            </h2>

            <p>

                <?php echo $summaryCount; ?>

                <?php echo ($summaryCount == 1)
                    ? 'summary'
                    : 'summaries'; ?>

                available

            </p>

        </div>


        <!-- SEARCH -->

        <div class="search-box">

            <span>
                ⌕
            </span>

            <input
                type="text"
                id="summarySearch"
                placeholder="Search summaries..."
            >

        </div>

    </section>


    <!-- SUMMARY CARDS-->

    <section
        class="summary-grid"
        id="summaryGrid"
    >


        <?php if ($summaryCount > 0): ?>


            <?php foreach ($summaries as $summary): ?>


                <article
                    class="summary-card"
                    data-title="<?php echo e($summary["title"]); ?>"
                    data-message="<?php echo e($summary["title"]); ?>"
                >


                    <!-- CARD TOP -->

                    <div class="card-top">

                        <span class="summary-badge">
                            Ward Summary
                        </span>


                        <span class="summary-date">

                            <?php

                            echo !empty($summary["created_at"])
                                ? e(
                                    date(
                                        "d M Y",
                                        strtotime($summary["created_at"])
                                    )
                                )
                                : '';

                            ?>

                        </span>

                    </div>


                    <!-- TITLE -->

                    <h3>

                        <a
                            href="uploadSummary.php?edit_id=<?php echo (int)$summary['summary_id']; ?>"
                            style="
                                color:inherit;
                                text-decoration:none;
                            "
                        >

                            <?php echo e($summary["title"]); ?>

                        </a>

                    </h3>


                    <!-- PERIOD -->

                    <?php if (
                        !empty($summary["period_start"]) &&
                        !empty($summary["period_end"])
                    ): ?>

                        <p class="summary-text">

                            Covers

                            <?php

                            echo e(
                                date(
                                    "d M Y",
                                    strtotime($summary["period_start"])
                                )
                            );

                            ?>

                            &ndash;

                            <?php

                            echo e(
                                date(
                                    "d M Y",
                                    strtotime($summary["period_end"])
                                )
                            );

                            ?>

                        </p>

                    <?php endif; ?>


                    <!-- PDF -->

                    <a
                        class="read-btn"
                        href="<?php echo e($summary["file_path"]); ?>"
                        target="_blank"
                        rel="noopener"
                    >

                        View PDF

                        <span>
                            →
                        </span>

                    </a>


                </article>


            <?php endforeach; ?>


        <?php else: ?>


            <!-- EMPTY -->

            <div class="empty-state">

                <div class="empty-icon">

                    <i class="fa-regular fa-file-lines"></i>

                </div>


                <h3>
                    No summaries yet
                </h3>


                <p>

                    There are currently no summaries
                    available for

                    <?php echo e($currentWard["name"]); ?>.

                </p>

            </div>


        <?php endif; ?>


    </section>


    <!-- NO SEARCH RESULTS-->

    <div
        id="noResults"
        class="no-results"
    >

        <div>

            <img
                src="search.svg"
                alt="Search icon"
            >

        </div>


        <h3>
            No summaries found
        </h3>


        <p>
            Try searching for another word or phrase.
        </p>

    </div>


</main>


<!-- JAVASCRIPT-->

<script src="ward_summaries.js"></script>


<script>

document.addEventListener("DOMContentLoaded", function () {


       //SEARCH SUMMARIES
    const searchInput =
        document.getElementById("summarySearch");

    const summaryGrid =
        document.getElementById("summaryGrid");

    const noResults =
        document.getElementById("noResults");


    if (
        searchInput &&
        summaryGrid &&
        noResults
    ) {

        searchInput.addEventListener("input", function () {


            const searchTerm =
                this.value.toLowerCase().trim();


            const cards =
                summaryGrid.querySelectorAll(
                    ".summary-card"
                );


            let visibleCards = 0;


            cards.forEach(function (card) {


                const title =
                    card.dataset.title
                        ? card.dataset.title.toLowerCase()
                        : "";


                const message =
                    card.dataset.message
                        ? card.dataset.message.toLowerCase()
                        : "";


                if (
                    title.includes(searchTerm) ||
                    message.includes(searchTerm)
                ) {

                    card.style.display = "";

                    visibleCards++;

                } else {

                    card.style.display = "none";

                }

            });


            if (
                cards.length > 0 &&
                visibleCards === 0
            ) {

                noResults.style.display = "block";

            } else {

                noResults.style.display = "none";

            }

        });

    }

});

</script>
 <!-- FOOTER -->
        <footer>
            <p>
                © 2026 M-LINK | Empowering Communities
            </p>
        </footer>

</body>

</html>