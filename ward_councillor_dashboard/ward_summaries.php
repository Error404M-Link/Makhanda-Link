<?php
    if (session_status() === PHP_SESSION_NONE) {
            session_start();
    }

    require_once "Error404Connection.php";

    /* #### HELPER ################## */
    function e($value) {
        return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
    }

    /* GET THE SELECTED WARD (with session persistence) */
    if (isset($_GET['ward'])) {
        $ward = (int)$_GET['ward'];
        if ($ward >= 1 && $ward <= 14) {
            $_SESSION['selected_ward'] = $ward;
        }
    } elseif (isset($_SESSION['selected_ward'])) {
        $ward = (int)$_SESSION['selected_ward'];
    } else {
        $ward = 1;
    }

    if ($ward < 1 || $ward > 14) {
        $ward = 1;
        $_SESSION['selected_ward'] = $ward;
    }

    /* -------------------------------------------------------------
        WARD + COUNCILLOR INFO
       ------------------------------------------------------------- */

    $stmt = $conn->prepare(
        "SELECT username, ward_id, date_appointed, contact
         FROM ward_councillor
         WHERE ward_id = ?"
    );
    $stmt->bind_param("i", $ward);
    $stmt->execute();
    $councillorRow = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    $currentWard = [
        "name"           => "Ward " . $ward,
        "map"            => "#",
        "photo"          => "assets/img/councillor-placeholder.png",
        "councillor"     => $councillorRow['username'] ?? 'Not yet appointed',
        "party"          => "N/A",
        "cell"           => $councillorRow['contact'] ?? '',
        "email"          => "N/A",
        "makhanda_email" => "N/A",
    ];

    /* -------------------------------------------------------------
        WARD SUMMARIES
    ------------------------------------------------------------- */
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

    /* -------------------------------------------------
    PREVIOUS / NEXT WARD
    ------------------------------------------------- */

    $previousWard = $ward > 1 ? $ward - 1 : 14;
    $nextWard = $ward < 14 ? $ward + 1 : 1;

    /* Can the logged-in user post a summary to THIS ward? */
    $canSubmitHere = false;
    if (!empty($_SESSION['username']) && !empty($_SESSION['role'])) {
        if ($_SESSION['role'] === 'councillor') {
            $canSubmitHere = ((int)($_SESSION['ward_id'] ?? 0) === $ward);
        } else {
            // officer / admin can post to any ward
            $canSubmitHere = true;
        }
    }
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

<header style="display:flex; justify-content:flex-end; padding:0.5rem 1rem;">
    <?php if (!empty($_SESSION['username'])): ?>
        <span>
            Logged in as <?php echo e($_SESSION['username']); ?> (<?php echo e($_SESSION['role'] ?? 'user'); ?>)
            &middot; <a href="logout.php">Log out</a>
        </span>
    <?php else: ?>
        <a href="login.php">Councillor / Staff Login</a>
    <?php endif; ?>
</header>


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

        <select id="wardSelect" onchange="location.href='?ward='+this.value;">
            <?php for ($i = 1; $i <= 14; $i++): ?>
                <option value="<?php echo $i; ?>" <?php echo $i == $ward ? "selected" : ""; ?>> Ward <?php echo $i; ?>
                </option>
            <?php endfor; ?>
        </select>
    </section>

    <!-- WARD COUNCILLOR-->
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

                <?php if ($canSubmitHere): ?>
                    <a href="uploadSummary.php" class="map-btn">
                        + Submit Ward Summary
                    </a>
                <?php endif; ?>
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
            <input type="text" id="summarySearch" placeholder="Search summaries..." >
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

                            <?php
                            echo date(
                                "d M Y",
                                strtotime($summary["created_at"])
                            );
                            ?>

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

    <section class="ward-navigation">
        <a href="?ward=<?php echo $previousWard; ?>" class="nav-ward">
            <span>
                ←
            </span>
            <div>
                <small> Previous </small>
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

        <a href="?ward=<?php echo $nextWard; ?>" class="nav-ward next">
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

<script src="ward_summaries.js"></script>
</body>
</html>