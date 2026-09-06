<?php
session_start();
require_once "dbconnections.php";

/* -------------------------------------------------
   HELPER
------------------------------------------------- */
function e($value) {
    return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
}

/* -------------------------------------------------
   GET SELECTED WARD
------------------------------------------------- */
$ward = isset($_GET['ward']) ? (int)$_GET['ward'] : 1;

if ($ward < 1 || $ward > 14) {
    $ward = 1;
}

$wardData = [

1 => [
    "name" => "Ward 1",
    "councillor" => "Phumelele Peter",
    "party" => "ANC",
    "cell" => "078 794 6992",
    "email" => "pphumelele561@gmail.com",
    "makhanda_email" => "ppeter@makana.gov.za",
    "photo" => "uploads/councillors/ward1.png",
    "map" => "maps/ward1.pdf"
],

2 => [
    "name" => "Ward 2",
    "councillor" => "Rumsell Mpumzi Xonxa",
    "party" => "ANC",
    "cell" => "082 932 1304",
    "email" => "ramiexonxa@yahoo.com",
    "makhanda_email" => "rxonxa@makana.gov.za",
    "photo" => "uploads/councillors/ward2.png",
    "map" => "maps/ward2.pdf"
],

    3 => [
    "name" => "Ward 3",
    "councillor" => "Andile Hoyi",
    "party" => "ANC",
    "cell" => "078 285 3179",
    "email" => "andilehoyi@yahoo.com",
    "makhanda_email" => "zhoyi@makana.gov.za",
    "photo" => "uploads/councillors/ward3.png",
    "map" => "maps/ward3.pdf"
    ],

    4 => [
    "name" => "Ward 4",
    "councillor" => "Geoffrey Embling",
    "party" => "DA",
    "cell" => "081 712 8975",
    "email" => "geoffembling@gmail.com",
    "makhanda_email" => "gembling@makana.gov.za",
    "photo" => "uploads/councillors/ward4.png",
    "map" => "maps/ward4.pdf"
    ],

    5 => [
    "name" => "Ward 5",
    "councillor" => "Gcobisa Mene",
    "party" => "ANC",
    "cell" => "078 204 8522",
    "email" => "gbmene@yahoo.com",
    "makhanda_email" => "gmene@makana.gov.za",
    "photo" => "uploads/councillors/ward5.png",
    "map" => "maps/ward5.pdf"
    ],

    6 => [
    "name" => "Ward 6",
    "councillor" => "Vuyani Masinda",
    "party" => "ANC",
    "cell" => "083 337 7754",
    "email" => "vuyanijezzz@gmail.com",
    "makhanda_email" => "vjezi@makana.gov.za",
    "photo" => "uploads/councillors/ward6.png",
    "map" => "maps/ward6.pdf"
    ],

    7 => [
    "name" => "Ward 7",
    "councillor" => "lunga Masinda",
    "party" => "ANC",
    "cell" => "073 904 7315",
    "email" => "thandayi126@gmail.com",
    "makhanda_email" => "lmasinda@makana.gov.za",
    "photo" => "uploads/councillors/ward7.png",
    "map" => "maps/ward7.pdf"
    ],

    8 => [
    "name" => "Ward 8",
    "councillor" => "Cary Clark",
    "party" => "DA",
    "cell" => "074 618 8747",
    "email" => "cary@imaginet.co.za",
    "makhanda_email" => "caryclark@makana.gov.za",
    "photo" => "uploads/councillors/ward8.png",
    "map" => "maps/ward8.pdf"
    ],

    9 => [
    "name" => "Ward 9",
    "councillor" => "Thozamile Yaka",
    "party" => "ANC",
    "cell" => "078 269 2338",
    "email" => "thozamileyaka184@gmail.com",
    "photo" => "uploads/councillors/ward9.png",
    "map" => "maps/ward9.pdf"
    ],

    10 => [
    "name" => "Ward 10",
    "councillor" => "Councillor Name",
    "party" => "Political Party",
    "cell" => "082 123 4567",
    "email" => "ambeji@makana.gov.za",
    "photo" => "uploads/councillors/ward10.png",
    "map" => "maps/ward10.pdf"
    ],

    11 => [
    "name" => "Ward 11",
    "councillor" => "Sikhiwo Zono",
    "party" => "ANC",
    "cell" => "063 064 6048",
    "email" => "sakhiwozono@gmail.com",
    "makhanda_email" => "szonoa@makana.gov.za",
    "photo" => "uploads/councillors/ward11.png",
    "map" => "maps/ward11.pdf"
    ],

    12 => [
    "name" => "Ward 12",
    "councillor" => "Mzobonzi Nkwentsha",
    "party" => "ANC",
    "cell" => "078 143 7349",
    "email" => "mzobanzimzobanzi1@.com",
    "makhanda_email" => "mnkwentsha@makana.gov.za",
    "photo" => "uploads/councillors/ward12.png",
    "map" => "maps/ward12.pdf"
    ],

    13 => [
    "name" => "Ward 13",
    "councillor" => "Councillor Name",
    "party" => "ANC",
    "cell" => "076 993 0660",
    "email" => "wandile.matina78@gmail.com",
    "makhanda_email" => "wmatina@makana.gov.za",
    "photo" => "uploads/councillors/ward13.png",
    "map" => "maps/ward13.pdf"
    ],

    14 => [
    "name" => "Ward 14",
    "councillor" => "Vuyani Nesi",
    "party" => "INDEPENDENT",
    "cell" => "078 126 5684",
    "email" => "vnesi@makana.gov.za",
    "photo" => "uploads/councillors/ward14.png",
    "map" => "maps/ward14.pdf"
    ]
];

$currentWard = $wardData[$ward];

/* -------------------------------------------------
   GET NOTICES / SUMMARIES
-------------------------------------------------

   This assumes your notices table contains:

   id
   title
   notice_date
   message
   ward

------------------------------------------------- */

$summaries = [];

$sql = "SELECT id, title, notice_date, message
        FROM notices
        WHERE ward = ?
        ORDER BY notice_date DESC";

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

/* -------------------------------------------------
   PREVIOUS / NEXT WARD
------------------------------------------------- */

$previousWard = $ward > 1 ? $ward - 1 : 14;
$nextWard = $ward < 14 ? $ward + 1 : 1;

?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport"
          content="width=device-width, initial-scale=1.0">
    <title>
        <?php echo e($currentWard["name"]); ?> | M-Link Ward Summaries
    </title>

    <link rel="stylesheet" href="ward_summaries.css">
</head>
<body>

<!-- ==========================================
     HEADER
========================================== -->



<!-- ==========================================
     MAIN
========================================== -->

<main class="container">


    <!-- WARD SELECTOR -->

    <section class="ward-selector">

        <div>

            <span class="section-label">
                SELECT A WARD
            </span>

            <h2>
                VIEW WARD SUMMARIES
            </h2>

        </div>


        <select id="wardSelect">

            <?php for ($i = 1; $i <= 14; $i++): ?>

                <option
                    value="<?php echo $i; ?>"
                    <?php echo $i == $ward ? "selected" : ""; ?>
                >

                    Ward <?php echo $i; ?>

                </option>

            <?php endfor; ?>

        </select>

    </section>



    <!-- ======================================
         WARD HERO
    ======================================= -->

    <section class="ward-hero">

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

                <a
                    href="<?php echo e($currentWard["map"]); ?>"
                    target="_blank"
                    class="map-btn"
                >
                    View Ward Map
                </a>

            </div>

        </div>


        <!-- COUNCILLOR -->

        <div class="councillor-card">

    <!-- PHOTO -->
    <div class="councillor-photo">

        <img
            src="<?php echo e($currentWard["photo"]); ?>"
            alt="Ward Councillor"
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


        <!-- PARTY -->

        <div class="contact-item">

            <span class="contact-icon"></span>

            <div>
                <small>Political Party</small>

                <strong>
                    <?php echo e($currentWard["party"]); ?>
                </strong>
            </div>

        </div>


        <!-- CELL -->

        <div class="contact-item">

            <span class="contact-icon"></span>

            <div>
                <small>Cell Number</small>

                <a
                    href="tel:<?php echo e($currentWard["cell"]); ?>"
                >
                    <?php echo e($currentWard["cell"]); ?>
                </a>
            </div>

        </div>


        <!-- WORK EMAIL -->

        <div class="contact-item">

            <span class="contact-icon"></span>

            <div>
                <small>Work Email</small>

                <a
                    href="mailto:<?php echo e($currentWard["email"]); ?>"
                >
                    <?php echo e($currentWard["email"]); ?>
                </a>
            </div>

        </div>

        <!-- MUNICIPAL EMAIL -->

        <div class="contact-item">

            <div>
                <small>Municipal Email</small>

                <a
                    href="mailto:<?php echo e($currentWard["makhanda_email"]); ?>"
                >
                    <?php echo e($currentWard["makhanda_email"]); ?>
                </a>
            </div>

        </div>

    </div>

</div>

        </div>

    </section>



    <!-- ======================================
         SUMMARY TOOLBAR
    ======================================= -->

    <section class="summary-toolbar">
        <div>
            <h2>
                Ward Summaries
            </h2>
            <p>
                <?php echo $summaryCount; ?>
                <?php echo $summaryCount == 1 ? "summary" : "summaries"; ?>
                available
            </p>

        </div>


        <div class="search-box">

            <span>⌕</span>

            <input
                type="text"
                id="summarySearch"
                placeholder="Search summaries..."
            >

        </div>

    </section>



    <!-- ======================================
         SUMMARY CARDS
    ======================================= -->

    <section
        class="summary-grid"
        id="summaryGrid"
    >

        <?php if ($summaryCount > 0): ?>

            <?php foreach ($summaries as $summary): ?>

                <article
                    class="summary-card"
                    data-title="<?php echo e($summary["title"]); ?>"
                    data-message="<?php echo e($summary["message"]); ?>"
                >

                    <div class="card-top">

                        <span class="summary-badge">
                            Ward Summary
                        </span>

                        <span class="summary-date">

                            <?php
                            echo date(
                                "d M Y",
                                strtotime($summary["notice_date"])
                            );
                            ?>

                        </span>

                    </div>


                    <h3>
                        <?php echo e($summary["title"]); ?>
                    </h3>


                    <p class="summary-text">

                        <?php
                        $message = $summary["message"];

                        if (strlen($message) > 180) {
                            echo e(substr($message, 0, 180)) . "...";
                        } else {
                            echo e($message);
                        }
                        ?>

                    </p>


                    <button
                        class="read-btn"
                        onclick="openSummary(this)"
                        data-title="<?php echo e($summary["title"]); ?>"
                        data-message="<?php echo e($summary["message"]); ?>"
                        data-date="<?php echo e($summary["notice_date"]); ?>"
                    >

                        Read Summary

                        <span>
                            →
                        </span>

                    </button>

                </article>

            <?php endforeach; ?>


        <?php else: ?>

            <div class="empty-state">

                <div class="empty-icon">
                    
                </div>

                <h3>
                    No summaries yet
                </h3>

                <p>
                    There are currently no summaries
                    available for <?php echo e($currentWard["name"]); ?>.
                </p>

            </div>

        <?php endif; ?>

    </section>



    <!-- NO SEARCH RESULTS -->

    <div
        id="noResults"
        class="no-results"
    >

        <div>
            🔎
        </div>

        <h3>
            No summaries found
        </h3>

        <p>
            Try searching for another word or phrase.
        </p>

    </div>



    <!-- ======================================
         WARD NAVIGATION
    ======================================= -->

    <section class="ward-navigation">

        <a
            href="?ward=<?php echo $previousWard; ?>"
            class="nav-ward"
        >

            <span>
                ←
            </span>

            <div>

                <small>
                    Previous
                </small>

                <strong>
                    Ward <?php echo $previousWard; ?>
                </strong>

            </div>

        </a>


        <div class="ward-position">

            <span>
                <?php echo $ward; ?>
            </span>

            / 14

        </div>


        <a
            href="?ward=<?php echo $nextWard; ?>"
            class="nav-ward next"
        >

            <div>

                <small>
                    Next
                </small>

                <strong>
                    Ward <?php echo $nextWard; ?>
                </strong>

            </div>

            <span>
                →
            </span>

        </a>

    </section>

</main>



<!-- ==========================================
     SUMMARY MODAL
========================================== -->

<div
    id="summaryModal"
    class="modal"
>

    <div class="modal-content">

        <button
            class="close-modal"
            onclick="closeSummary()"
        >
            ×
        </button>

        <span class="modal-badge">
            Ward Summary
        </span>

        <h2 id="modalTitle"></h2>

        <span
            id="modalDate"
            class="modal-date"
        ></span>

        <div
            id="modalMessage"
            class="modal-message"
        ></div>

    </div>

</div>


<script src="ward_summaries.js"></script>

</body>
</html>