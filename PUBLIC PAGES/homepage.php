<?php

$apiKey = "41e9e6e8ac1546bc8b7155723261907";
$city = "Grahamstown";

$url = "https://api.weatherapi.com/v1/current.json?key=$apiKey&q=" . urlencode($city);

$response = file_get_contents($url);

if ($response === false) {
    $temp = "--";
    $condition = "Weather unavailable";
    $icon = "";
} else {

    $data = json_decode($response, true);

    if (isset($data['error'])) {
        $temp = "--";
        $condition = "Weather unavailable";
        $icon = "";
    } else {
        $temp = $data['current']['temp_c'];
        $condition = $data['current']['condition']['text'];
        $icon = $data['current']['condition']['icon'];
    }
}

?>

<!DOCTYPE html>
<html lang="en">

<head>

    <meta charset="UTF-8">

    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>M-Link | Makhanda Community Link</title>

    <link rel="stylesheet" href="Homepage.css">
    <link rel="stylesheet" href="publicfaultBoard.css">

</head>


<body>

<!-- =========================================================
     NAVIGATION
========================================================= -->
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

     <!--
    <div class="utilityBar">
        <div class="utilityContainer">
            <div class="utilityLeft">
                <a href="#">Documentation</a>
                <a href="#">Support</a>
            </div>
            <div class="utilityRight">
                <a href="login.html" class="utilityBtn btn">Login</a>
                <a href="register.html" class="utilityBtn btn">Sign Up</a>
            </div>
        </div>
    </div>
    -->

    <!--  WHITE MAIN NAVIGATION BAR -->
    <nav class="mainNavbar"> 
        <div class="navContainer">
            <ul class="mainNavLinks">
                <li><a href="#" class="navItem active">Home</a></li>
                <li><a href="#" class="navItem">Faults</a></li>
                <li><a href=".php" class="navItem"> Wards </a></li>
                <li><a href="ABOUTUS.html" class="navItem">About Us</a></li>
                <li><a href="view_notices.php" class="navItem">Notices</a></li>
                <li><a href="contactUs.html" class="navItem">Contact us</a></li>
            </ul> 
        </div>
    </nav>

<!-- =========================================================
     HERO SECTION
========================================================= -->

<section class="hero">

    <div class="hero-content">

        <span class="hero-label">
            M-LINK COMMUNITY PLATFORM
        </span>

        <h1>
            Your Community.<br>
            Your Voice.<br>
            Your Makhanda.
        </h1>

        <p>
            M-Link connects residents, ward councillors and the municipality
            to improve communication and service delivery across Makhanda.
        </p>

        <div class="hero-buttons">

            <a href="login.html" class="hero-btn primary">
                Report a Fault
            </a>

            <a href="viewWards.html" class="hero-btn secondary">
                Browse Wards
            </a>

            <a href="view_notices.php" class="hero-btn secondary">
                View Notices
            </a>

        </div>

    </div>

</section>


<section class="overview-section">

    <!-- Header -->
    <div class="overview-header">
        <button id="menuBtn" class="menu-btn">&#9776;</button>
        <h2>Makana Overview</h2>
    </div>

    
        <div class="Overview">

            <h3>
                Makana Local Municipality serves the people of Makhanda
                and surrounding communities with a commitment to transparency,
                accountable governance, and sustainable development.
                It is in the Eastern Cape Province on the southeastern
                seaboard of South Africa, and in the western part of the
                Eastern Cape Province, falling under the Sarah Baartman
                District Municipality.

                With the cities of Gqeberha, 120 km to the west, and
                East London, 180 km to the east, Makana Municipality
                is strategically situated between two of the province's
                largest industrial centres.

                Both coastal cities are served by well-equipped container
                ports and have major airports linking them to Cape Town,
                Durban and Johannesburg.

                Home to the world-renowned National Arts Festival,
                the historic Rhodes University, and several leading schools,
                Makana blends rich heritage with vibrant community life.

                Our municipality comprises 14 wards, including key rural
                communities.
            </h3>

        </div>


    </div>

</section>

<!-- =========================================================
     QUICK ACTIONS
========================================================= -->

<section class="quick-actions">

    <div class="section-heading">

        <span class="span">Interesting Facts About Makhanda</span>

        <h2 >
            It was founded in 1812
        </h2>

        <p>
           Grahamstown was established as a military
            garrison by Colonel John Graham during the 
            frontier conflicts in the Eastern Cape
        </p>

    </div>


    <div class="quick-action-container">

        <!-- Report Fault -->

        <a href="login.html" class="action-card">

            <div class="action-icon">
                🚨
            </div>

            <div class="action-content">

                <h3>
                    Its Name is now Makhanda
                </h3>

                <p>
                The city was renamed Makhanda, after the Xhosa prophet
                 and warrior Makhanda (Nxele),
                 who led an attack on the British garrison in 1819
                </p>

                

            </div>

        </a>


        <!-- Track Fault -->

        <a href="PublicFaultBoard.php" class="action-card">

            <div class="action-icon">
                🔎
            </div>

            <div class="action-content">

                <h3>
                    It is known as the “City of Saints” ⛪
                </h3>

                <p>
                    Makhanda became famous 
                    for its large number of churches and religious 
                    institutions. The city has a particularly rich
                     religious and architectural history
                </p>

                

            </div>

        </a>


        <!-- Find Ward -->

        <a href="viewWards.html" class="action-card">

            <div class="action-icon">
                🗺️
            </div>

            <div class="action-content">

                <h3>
                    The National Arts Festival started here 🎭
                </h3>

                <p>
                   The National Arts Festival began
                    in Makhanda in 1974. It grew from 
                    an event associated with the 1820 Settlers 
                    Monument into one of Africa's major arts festivals.
                </p>

            

            </div>

        </a>


        <!-- Notices -->

        <a href="view_notices.php" class="action-card">

            <div class="action-icon">
                📢
            </div>

            <div class="action-content">

                <h3>
                    The 1820 Settlers Monument has an interesting history
                </h3>

                <p>
                    The monument opened in 1974 and became
                     the home of the first National Arts Festival.
                      It was badly damaged by fire in 1994 and 
                      was later restored and rededicated by
                       Nelson Mandela in 1996


            </div>

        </a>

    </div>

</section>


<!-- =========================================================
     COMMUNITY ISSUES
========================================================= -->

<section class="community-issues">

    <div class="section-heading">

        <span>COMMUNITY SERVICE DELIVERY</span>

        <h2>
            What's happening in Makhanda?
        </h2>

        <p>
            Stay informed about municipal issues being
            reported across our communities.
        </p>

    </div>


    <div class="issue-stat-container">

        <!-- Active -->

        <div class="issue-stat">

            <div class="stat-icon">
                🔴
            </div>

            <h3>
                Active Issues
            </h3>

            <strong>
                24
            </strong>

            <p>
                Issues currently being addressed
            </p>

        </div>


        <!-- Resolved -->

        <div class="issue-stat">

            <div class="stat-icon">
                🟢
            </div>

            <h3>
                Resolved Issues
            </h3>

            <strong>
                18
            </strong>

            <p>
                Issues successfully resolved
            </p>

        </div>


        <!-- Trending -->

        <div class="issue-stat">

            <div class="stat-icon">
                🔥
            </div>

            <h3>
                Trending Issues
            </h3>

            <strong>
                7
            </strong>

            <p>
                Issues receiving community attention
            </p>

        </div>

    </div>


    <div class="section-button">

        <a href="PublicFaultBoard.php" class="btn primary">
            View Public Fault Board
        </a>

    </div>

</section>


<!-- =========================================================
     COMMUNITY NOTICES
========================================================= -->

<section class="community-notices">

    <div class="section-heading">

        <span>STAY INFORMED</span>

        <h2>
            Latest Notices & Alerts
        </h2>

        <p>
            Important information and updates from
            the municipality and your community.
        </p>

    </div>


    <div class="notice-container">


        <!-- Notice 1 -->

        <article class="notice-card">

            <div class="notice-category">
                WATER
            </div>

            <h3>
                Water Service Update
            </h3>

            <p>
                Residents are encouraged to stay informed
                about water interruptions and maintenance
                affecting local communities.
            </p>

            <a href="view_notices.php">
                Read More →
            </a>

        </article>


        <!-- Notice 2 -->

        <article class="notice-card">

            <div class="notice-category">
                SAFETY
            </div>

            <h3>
                Community Safety Alert
            </h3>

            <p>
                View the latest safety information and
                alerts affecting residents in Makhanda.
            </p>

            <a href="view_notices.php">
                Read More →
            </a>

        </article>


        <!-- Notice 3 -->

        <article class="notice-card">

            <div class="notice-category">
                MUNICIPALITY
            </div>

            <h3>
                Municipal Announcement
            </h3>

            <p>
                Stay updated with announcements and
                important municipal communications.
            </p>

            <a href="view_notices.php">
                Read More →
            </a>

        </article>

    </div>


    <div class="section-button">

        <a href="view_notices.php" class="btn secondary">
            View All Notices
        </a>

    </div>

</section>


<!-- =========================================================
     EXPLORE YOUR WARD
========================================================= -->

<section class="wards-section">

    <div class="section-heading">

        <span>YOUR COMMUNITY</span>

        <h2>Explore Your Ward</h2>

        <p>
            Find your ward and access information,
            notices and community updates specific
            to your area.
        </p>

    </div>


    <div class="ward-container">

        <!-- Ward 1 -->
        <a href="ward1.php" class="ward-card">
            <span>01</span>
            <h3>Ward 1</h3>
        </a>

        <!-- Ward 2 -->
        <a href="ward2.php" class="ward-card">
            <span>02</span>
            <h3>Ward 2</h3>
        </a>

        <!-- Ward 3 -->
        <a href="ward3.php" class="ward-card">
            <span>03</span>
            <h3>Ward 3</h3>
        </a>

        <!-- Ward 4 -->
        <a href="ward4.php" class="ward-card">
            <span>04</span>
            <h3>Ward 4</h3>
        </a>

        <!-- Ward 5 -->
        <a href="ward5.php" class="ward-card">
            <span>05</span>
            <h3>Ward 5</h3>
        </a>

        <!-- Ward 6 -->
        <a href="ward6.php" class="ward-card">
            <span>06</span>
            <h3>Ward 6</h3>
        </a>

        <!-- Ward 7 -->
        <a href="ward7.php" class="ward-card">
            <span>07</span>
            <h3>Ward 7</h3>
        </a>

        <!-- Ward 8 -->
        <a href="ward8.php" class="ward-card">
            <span>08</span>
            <h3>Ward 8</h3>
        </a>

        <!-- Ward 9 -->
        <a href="ward9.php" class="ward-card">
            <span>09</span>
            <h3>Ward 9</h3>
        </a>

        <!-- Ward 10 -->
        <a href="ward10.php" class="ward-card">
            <span>10</span>
            <h3>Ward 10</h3>
        </a>

        <!-- Ward 11 -->
        <a href="ward11.php" class="ward-card">
            <span>11</span>
            <h3>Ward 11</h3>
        </a>

        <!-- Ward 12 -->
        <a href="ward12.php" class="ward-card">
            <span>12</span>
            <h3>Ward 12</h3>
        </a>

        <!-- Ward 13 -->
        <a href="ward13.php" class="ward-card">
            <span>13</span>
            <h3>Ward 13</h3>
        </a>

        <!-- Ward 14 -->
        <a href="ward14.php" class="ward-card">
            <span>14</span>
            <h3>Ward 14</h3>
        </a>

    </div>


    <div class="section-button">

        <button class="btn primary" id="viewWardsBtn">
            View All Wards
        </button>

    </div>

</section>





<!-- =========================================================
     MAKHANDA TODAY
========================================================= -->

<section class="makhanda-today">

    <div class="section-heading">

        <span>MAKHANDA TODAY</span>

        <h2>
            Local Information
        </h2>

        <p>
            Useful information about what's happening
            in and around Makhanda.
        </p>

    </div>


    <div class="today-container">


        <!-- Weather -->

        <div class="today-card weather-card">

            <div class="today-icon">

                <?php if (!empty($icon)): ?>

                    <img
                        src="https:<?= htmlspecialchars($icon) ?>"
                        alt="<?= htmlspecialchars($condition) ?>"
                    >

                <?php else: ?>

                    ☁️

                <?php endif; ?>

            </div>

            <div>

                <span>
                    LOCAL WEATHER
                </span>

                <h3>
                    <?= htmlspecialchars($condition) ?>
                </h3>

                <p class="temperature">
                    <?= htmlspecialchars($temp) ?>°C
                </p>

                <p>
                    Makhanda
                </p>

            </div>

        </div>


        <!-- Safety -->

        <div class="today-card">

            <div class="today-icon">
                ⚠️
            </div>

            <div>

                <span>
                    SAFETY
                </span>

                <h3>
                    Community Safety
                </h3>

                <p>
                    Stay aware of local safety notices
                    and alerts affecting your community.
                </p>

                <a href="view_notices.php">
                    View Alerts →
                </a>

            </div>

        </div>


        <!-- Events -->

        <div class="today-card">

            <div class="today-icon">
                🎉
            </div>

            <div>

                <span>
                    COMMUNITY
                </span>

                <h3>
                    Upcoming Events
                </h3>

                <p>
                    Discover events and activities
                    happening in Makhanda.
                </p>

                <a href="view_notices.php">
                    View Events →
                </a>

            </div>

        </div>

    </div>

</section>




    <!-- ================= DAM LEVELS ================= -->

<section class="dam-levels
">

    <div class="dam-title">
        <h2>💧 Dam Levels</h2>
        <p>
            Stay informed about the current water levels of Makana Municipality's major dams.
        </p>
    </div>

    <div class="dam-container">

        <!-- Settlers Dam -->
        <div class="dam-card">

            <h3>Settlers Dam</h3>

            <div class="dam-content">

                <div class="gauge blue">
                    <div class="water" style="height:75%;">
                        <span>75%</span>
                    </div>
                </div>

                <div class="dam-info">
                    <p><strong>Capacity:</strong> 750 ML</p>
                    <p><strong>Current:</strong> 562.5 ML</p>
                    <p><strong>Status:</strong> 🟢 Moderate</p>
                    <p><strong>Rainfall:</strong> Above Average</p>
                    <p><strong>Updated:</strong> Today</p>

                    <button class="history-btn">View History</button>
                </div>

            </div>

        </div>

        <!-- Howieson's Poort -->
        <div class="dam-card">

            <h3>Howieson's Poort Dam</h3>

            <div class="dam-content">

                <div class="gauge green">
                    <div class="water" style="height:92%;">
                        <span>92%</span>
                    </div>
                </div>

                <div class="dam-info">
                    <p><strong>Capacity:</strong>1031 ML</p>
                    <p><strong>Current:</strong>948.5 ML</p>
                    <p><strong>Status:</strong>🟢 Good</p>
                    <p><strong>Rainfall:</strong>Above Average</p>
                    <p><strong>Updated:</strong>Today</p>

                    <button class="history-btn">View History</button>
                </div>

            </div>

        </div>

        <!-- James Kleynhans -->
        <div class="dam-card">

            <h3>Jamieson & Milner Dams</h3>

            <div class="dam-content">

                <div class="gauge orange">
                    <div class="water" style="height:40%;">
                        <span>40%</span>
                    </div>
                </div>

                <div class="dam-info">
                    <p><strong>Capacity:</strong>512 ML</p>
                    <p><strong>Current:</strong>204.8 ML</p>
                    <p><strong>Status:</strong>🟠 Low</p>
                    <p><strong>Rainfall:</strong>Below Average</p>
                    <p><strong>Updated:</strong>Today</p>

                    <button class="history-btn">View History</button>
                </div>

            </div>

        </div>

    </div>

</section>

<!-- =========================================================
     ABOUT M-LINK
========================================================= -->

<section class="about-m-link">

    <div class="about-content">

        <span>
            ABOUT M-LINK
        </span>

        <h2>
            Building a more connected Makhanda
        </h2>

        <p>
            M-Link provides a digital connection between
            residents, community organisations, ward
            councillors and the municipality.
        </p>

        <p>
            Through M-Link, communities can report
            municipal faults, follow service-delivery
            issues, access ward information and stay
            informed about important local notices.
        </p>

        <a href="about_us_page.html" class="btn primary">
            Learn More About M-Link
        </a>

    </div>

</section>


<!-- =========================================================
     CALL TO ACTION
========================================================= -->

<section class="final-cta">

    <div>

        <h2>
            Be part of a better Makhanda.
        </h2>

        <p>
            Create an account to report faults,
            track your tickets and stay connected
            with your community.
        </p>

        <div class="cta-buttons">

            <a href="register.html" class="btn primary">
                Create an Account
            </a>

            <a href="login.html" class="btn secondary">
                Login
            </a>

        </div>

    </div>

</section>


<!-- =========================================================
     FOOTER
========================================================= -->

<footer>

    <div class="footerContainer">


        <!-- Brand -->

        <div class="makanaLogo">

            <div class="logoBrand">

                <img
                    src="MakanaLogo.svg"
                    alt="M-Link logo"
                >

            </div>

            <p>
                Empowering residents to take charge
                in their neighbourhood and work together
                to build a better, well-maintained
                Makhanda community.
            </p>


            <div class="socials">

                <a href="#" aria-label="X">
                    <img src="x.svg" alt="X">
                </a>

                <a href="#" aria-label="Instagram">
                    <img
                        src="instagram.svg"
                        alt="Instagram"
                    >
                </a>

                <a href="#" aria-label="Facebook">
                    <img
                        src="facebook.svg"
                        alt="Facebook"
                    >
                </a>

            </div>


            <a href="#" class="backToTopBtn">
                <span class="arrow">↑</span>
                BACK TO TOP
            </a>

        </div>


        <!-- Sitemap -->

        <div class="siteMap">

            <h2>
                Site Map
            </h2>

            <a href="homepage.php">
                Homepage
            </a>

            <a href="about_us_page.html">
                About Us
            </a>

            <a href="PublicFaultBoard.php">
                Public Fault Board
            </a>

            <a href="view_notices.php">
                Notices
            </a>

            <a href="viewWards.html">
                Wards
            </a>

            <a href="contactUs.html">
                Contact Us
            </a>

            <a href="FAQ.html">
                FAQs
            </a>

        </div>


        <!-- Contact -->

        <div class="contact">

            <h2>
                Contact
            </h2>

            <a href="mailto:zkewuti@makana.gov.za">
                Customer Care
            </a>

            <a href="mailto:info@makana.gov.za">
                Service Delivery
            </a>

            <a href="mailto:enquiries@makana.gov.za">
                Billing Related
            </a>


            <div class="address">

                <p>
                    City Hall, High Str,
                    Grahamstown, 6139
                </p>

                <p>

                    <img
                        src="call.svg"
                        alt="Phone"
                    >

                    +27 (0) 46 603 6134

                </p>

            </div>

        </div>

    </div>


    <div class="copyrightBar">

        <p>
            Copyright © 2026 M-Link.
            All Rights Reserved.
        </p>

    </div>

</footer>
<script>

    const wardCards = document.querySelectorAll(".ward-card");
    const viewWardsBtn = document.getElementById("viewWardsBtn");

    // Initially hide Ward 6 to Ward 14
    wardCards.forEach((card, index) => {
        if (index >= 5) {
            card.style.display = "none";
        }
    });


    // View All / View Less button
    viewWardsBtn.addEventListener("click", function () {

        const isShowingAll = viewWardsBtn.textContent.trim() === "View Less";

        wardCards.forEach((card, index) => {

            if (index >= 5) {

                if (isShowingAll) {
                    card.style.display = "none";
                } else {
                    card.style.display = "block";
                }

            }

        });


        // Change button text
        if (isShowingAll) {
            viewWardsBtn.textContent = "View All Wards";
        } else {
            viewWardsBtn.textContent = "View Less";
        }

    });

</script>




</body>

</html>