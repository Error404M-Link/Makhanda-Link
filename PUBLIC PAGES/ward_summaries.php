<?php
    require_once "Error404Connection.php";

    /* #### HELPER ################## */
    function e($value) {
        return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
    }

    /* GET THE SELECTED WARD FROM URL (Defaults to 1) */
    $ward = isset($_GET['ward']) ? (int)$_GET['ward'] : 1;

    if ($ward < 1 || $ward > 14) {
        $ward = 1;
    }

    /* ############# COUNCILLOR AND WARD INFORMATION ################ */

   $stmt = $conn->prepare(
    "SELECT wc.username, wc.ward_id, wc.date_appointed, wc.contact, 
            u.first_Name, u.surname
     FROM ward_councillor wc
     LEFT JOIN user u ON wc.username = u.username
     WHERE wc.ward_id = ?"
    );

    $stmt->bind_param("i", $ward);
    $stmt->execute();
    $councillorRow = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    $fullName = 'Not yet appointed';
    if (!empty($councillorRow)) {
        $fullName = trim(($councillorRow['first_Name'] ?? '') . ' ' . ($councillorRow['surname'] ?? ''));
        if (empty($fullName)) {
            $fullName = $councillorRow['username'];
        }
    }

    $currentWard = [
        "name"           => "Ward " . $ward,
        "map"            => "maps/ward" . $ward . ".pdf",
        "photo"          => "assets/img/councillor-placeholder.png",
        "councillor"     => $fullName,
        "party"          => "N/A",
        "cell"           => $councillorRow['contact'] ?? '',
        "email"          => $councillorRow['username'] ?? '',
        "makhanda_email" => "N/A",
    ];

    /* ############ WARD SUMMARIES #######################*/
    $summaries = [];

    $sql = "SELECT summary_id, title, file_path, file_size_bytes, mime_type,
                   period_start, period_end, created_at
            FROM ward_summaries
            WHERE ward_id = ?
            ORDER BY created_at DESC";

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

    /* ########### PREVIOUS OR NEXT WARD ################### */

    $previousWard = $ward > 1 ? $ward - 1 : 14;
    $nextWard     = $ward < 14 ? $ward + 1 : 1;
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>
        <?php echo e($currentWard["name"]); ?> | M-Link Ward Summaries
    </title>
    <link rel="stylesheet" href="ward_summaries.css">
    <link rel="stylesheet" href="publicfaultBoard.css">
    <style>
        
    </style>
</head>
<body>

<!-- ############### NAVIGATION BAR ################## -->

    <header class="heroHeader">
        <img src="MakhandaBackgroundPic.png" alt="Background" class="heroBgImage">
        <div class="heroOverlay"></div>
        <div class="heroContainer">
            <div class="heroLogo">
                <img src="MakanaLogo.svg" alt="Makhanda logo">
            </div> 
            <div class="heroText">
                <h1>Makana Municipality</h1>
                <p>Public Fault Board. Empowering our community together.</p>
            </div>
        </div>
    </header>

    <!-- RED UTILITY BAR -->

    <div class="utilityBar">
        <div class="utilityContainer">
            <div class="utilityLeft">
                <a href="#">Documentation</a>
                <a href="#">Support</a>
            </div>
            <div class="utilityRight">
                <a href="login.php" class="utilityBtn btn">Login</a>
                <a href="register.php" class="utilityBtn btn">Sign Up</a>
            </div>
        </div>
    </div>

    <!--  WHITE MAIN NAVIGATION BAR -->
    <nav class="mainNavbar"> 
        <div class="navContainer">
            <ul class="mainNavLinks">
                <li><a href="index.php" class="navItem">Home</a></li>
                <li><a href="PublicFaultBoard.php" class="navItem">Faults</a></li>
                <li><a href="ward_summaries.php" class="navItem active"> Wards </a></li>
                <li><a href="ABOUTUS.html" class="navItem">About Us</a></li>
                <li><a href="about_us_page.html" class="navItem">About M-Link</a></li>
                <li><a href="view_notices.php" class="navItem">Notices</a></li>
                <li><a href="contactUs.html" class="navItem">Contact us</a></li>
            </ul> 
        </div>
    </nav>

<!-- ############## MAIN ########################### -->

<main class="container">

    <!-- WARD SELECTOR -->
    <section class="ward-selector">
        <div>
            <span class="section-label">
                
            </span>
            <h2>
                Ward
            </h2>
        </div>

        <select id="wardSelect" onchange="location.href='?ward='+this.value;">
            <?php for ($i = 1; $i <= 14; $i++): ?>
                <option value="<?php echo $i; ?>" <?php echo $i == $ward ? "selected" : ""; ?>>
                    Ward <?php echo $i; ?>
                </option>
            <?php endfor; ?>
        </select>
    </section>

    <!-- WARD HERO -->
    <section class="ward-hero">
        <div class="ward-info">
            <span class="ward-tag">
                MAKANA LOCAL MUNICIPALITY
            </span>
            <h2>
                <?php echo e($currentWard["name"]); ?>
            </h2>
            <p>
                Stay informed about meetings, announcements, community matters and developments in your ward.
            </p>
            <div class="hero-buttons">
                <a href="<?php echo e($currentWard["map"]); ?>" target="_blank" class="map-btn">
                    View Ward Map
                </a>
            </div>
        </div>

        <!-- COUNCILLOR -->
        <div class="councillor-card">

            <!-- PHOTO -->
            <div class="councillor-photo">
                <img src="<?php echo e($currentWard["photo"]); ?>" alt="Ward Councillor">
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

                <!-- CELL -->
                <div class="contact-item">
                    <span class="contact-icon"></span>
                    <div>
                        <small>Cell Number</small>
                        <a href="tel:<?php echo e($currentWard["cell"]); ?>">
                            <?php echo e($currentWard["cell"]); ?>
                        </a>
                    </div>
                </div>

                <!-- WORK EMAIL -->
                <div class="contact-item">
                    <span class="contact-icon"></span>
                    <div>
                        <small>Work Email</small>
                        <a href="mailto:<?php echo e($currentWard["email"]); ?>">
                            <?php echo e($currentWard["email"]); ?>
                        </a>
                    </div>
                </div>

                <!-- MUNICIPAL EMAIL -->
                <div class="contact-item">
                    <div>
                        <small>Municipal Email</small>
                        <a href="mailto:<?php echo e($currentWard["makhanda_email"]); ?>">
                            <?php echo e($currentWard["makhanda_email"]); ?>
                        </a>
                    </div>
                </div>

            </div>
        </div>
    </section>

    <!-- SUMMARY TOOLBAR -->
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
            <input type="text" id="summarySearch" placeholder="Search summaries...">
        </div>
    </section>

    <!-- SUMMARY CARDS -->
    <section class="summary-grid" id="summaryGrid">
        <?php if ($summaryCount > 0): ?>
            <?php foreach ($summaries as $summary): ?>
                <article
                    class="summary-card"
                    data-title="<?php echo e($summary["title"]); ?>"
                    data-message="<?php echo e($summary["title"]); ?>">

                    <div class="card-top">
                        <span class="summary-badge">
                            Ward Summary
                        </span>
                        <span class="summary-date">
                            <?php echo date("d M Y", strtotime($summary["created_at"])); ?>
                        </span>
                    </div>

                    <h3>
                        <?php echo e($summary["title"]); ?>
                    </h3>

                    <?php if ($summary["period_start"] && $summary["period_end"]): ?>
                        <p class="summary-text">
                            Covers <?php echo e(date("d M Y", strtotime($summary["period_start"]))); ?>
                            &ndash; <?php echo e(date("d M Y", strtotime($summary["period_end"]))); ?>
                        </p>
                    <?php endif; ?>

                    <a
                        class="read-btn"
                        href="<?php echo e($summary["file_path"]); ?>"
                        target="_blank"
                        rel="noopener"
                    >
                        View PDF
                        <span>→</span>
                    </a>
                </article>
            <?php endforeach; ?>
        <?php else: ?>
            <div class="empty-state">
                <div class="empty-icon"></div>
                <h3>
                    No summaries yet
                </h3>
                <p>
                    There are currently no summaries available for <?php echo e($currentWard["name"]); ?>.
                </p>
            </div>
        <?php endif; ?>
    </section>

    <!-- NO SEARCH RESULTS -->
    <div id="noResults" class="no-results">
        <div>🔎</div>
        <h3>No summaries found</h3>
        <p>Try searching for another word or phrase.</p>
    </div>

    <!-- WARD NAVIGATION -->
    <section class="ward-navigation">
        <a href="?ward=<?php echo $previousWard; ?>" class="nav-ward">
            <span>←</span>
            <div>
                <small>Previous</small>
                <strong>Ward <?php echo $previousWard; ?></strong>
            </div>
        </a>
        <div class="ward-position">
            <span><?php echo $ward; ?></span> / 14
        </div>
        <a href="?ward=<?php echo $nextWard; ?>" class="nav-ward next">
            <div>
                <small>Next</small>
                <strong>Ward <?php echo $nextWard; ?></strong>
            </div>
            <span>→</span>
        </a>
    </section>

</main>

<script src="ward_summaries.js"></script>
</body>
</html>