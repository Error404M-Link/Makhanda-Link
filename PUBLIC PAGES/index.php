<?php

require_once "../Error404Connection.php";

$homeNotices = [];

$noticeQuery = "
    SELECT
        n.notice_id,
        n.notice_title,
        n.notice_category,
        n.notice_description,
        n.publish_date,
        n.expiry_date,
        n.date_posted
    FROM notices n
    WHERE n.status = 'Approved'
    AND n.deleted_at IS NULL
    AND n.publish_date <= NOW()
    AND n.expiry_date >= NOW()

    AND NOT EXISTS (
        SELECT 1
        FROM notice_ward nw
        WHERE nw.notice_id = n.notice_id
    )

    ORDER BY n.date_posted DESC
    LIMIT 1
";

$noticeResult = $conn->query($noticeQuery);

if ($noticeResult) {

    while ($row = $noticeResult->fetch_assoc()) {

        $homeNotices[] = $row;
    }
}


/* ########## WEATHER ############## */

$apiKey = "YOUR_WEATHER_API_KEY";
$city = "Grahamstown";

// forecast.json returns the current conditions, the city's local time
// and a 3-day forecast in a single request
$url = "https://api.weatherapi.com/v1/forecast.json?key="
    . urlencode($apiKey)
    . "&q="
    . urlencode($city)
    . "&days=3&aqi=no&alerts=no";

$weatherAvailable = false;
$localTime = "--";
$forecastDays = [];

$response = @file_get_contents($url);

if ($response !== false) {

    $data = json_decode($response, true);

    if (
        is_array($data)
        && !isset($data["error"])
        && !empty($data["forecast"]["forecastday"])
    ) {

        $weatherAvailable = true;

        // Time in the city itself, e.g. "11:44 pm"
        $localTime = date(
            "g:i a",
            strtotime($data["location"]["localtime"])
        );

        foreach ($data["forecast"]["forecastday"] as $index => $day) {

            $isToday = ($index === 0);

            // Today uses the live conditions, the next days use the
            // forecast (highest temperature and strongest wind).
            // The API gives wind in km/h, so divide by 3.6 for m/s.
            $forecastDays[] = [
                "label" => $isToday
                    ? "Today"
                    : date("l", strtotime($day["date"])),

                "date" => date("F j, Y", strtotime($day["date"])),

                "icon" => $isToday
                    ? $data["current"]["condition"]["icon"]
                    : $day["day"]["condition"]["icon"],

                "condition" => $isToday
                    ? $data["current"]["condition"]["text"]
                    : $day["day"]["condition"]["text"],

                "temp" => round(
                    $isToday
                        ? $data["current"]["temp_c"]
                        : $day["day"]["maxtemp_c"]
                ),

                "wind" => round(
                    ($isToday
                        ? $data["current"]["wind_kph"]
                        : $day["day"]["maxwind_kph"]) / 3.6
                )
            ];
        }
    }
}



/* ########### FETCH WARD SUMMARIES ############ */

$query = "SELECT
            title,
            file_path,
            created_at,
            ward_id
        FROM ward_summaries
        WHERE deleted = 0
        ORDER BY created_at DESC
        LIMIT ?
";


$result = false;
if ($stmt = mysqli_prepare($conn, $query)) {
    $limit = 3;
    mysqli_stmt_bind_param(
        $stmt,
        "i",
        $limit
    );
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
}else{
    error_log("Database query failed: ". mysqli_error($conn));
}

// HERO BACKGROUND: new random order on every page load. First image after the shuffle is shown.
$heroBackgrounds = [
    "Makhandaimages/makhandaBackground1.jpg",
    "Makhandaimages/makhandaBackground2.jpg",
    "Makhandaimages/makhandaBackground3.jpg",
    "Makhandaimages/makhandaBackground4.jpg",
    "Makhandaimages/makhandaBackground5.jpg",
    "Makhandaimages/makhandaBackground6.jpg"
];
shuffle($heroBackgrounds);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Makhanda Link</title>
    <link rel="stylesheet" href="homepage.css">
    <link rel="stylesheet" href="publicfaultBoard.css">
    <style>
        
    </style>
</head>
<body>


<!-- MAKHANDA HEADER -->

<header class="heroHeader">

    <div class="heroBackgroundSlideshow">

        <?php foreach ($heroBackgrounds as $index => $heroImage): ?>
            <img
                src="<?= htmlspecialchars($heroImage) ?>"
                alt="Makhanda"
                class="heroBgImage<?= $index === 0 ? ' active' : '' ?>"
            >
        <?php endforeach; ?>

    </div>
    
    <div class="heroOverlay"></div>
    <div class="heroContainer">
        <div class="heroLogo">
            <img src="makanalogo.svg" alt="Makhanda logo">
        </div>

        <div class="heroText">
            <h1>Makhanda-Link</h1>
            <p>
                Welcome. Empowering our community together.
            </p>
        </div>
    </div>

</header>
<!-- 
     UTILITY BAR
 -->
<div class="utilityBar">
    <div class="utilityContainer">
        <div class="utilityLeft">
            <a href="#">
                Documentation
            </a>
            <a href="#">
                Support
            </a>
        </div>


        <div class="utilityRight">
            <a href="login.php" class="utilityBtn btn">
                Login
            </a>
            <a href="register.php" class="utilityBtn btn">
                Sign Up
            </a>
        </div>
    </div>
</div>

<!-- 
     MAIN NAVIGATION
 -->

<nav class="mainNavbar">
    <div class="navContainer">
        <ul class="mainNavLinks">
            <li>
                <a
                    href="#"
                    class="navItem active"
                >
                    Home
                </a>
            </li>
            <li>
                <a
                    href="PublicFaultBoard.php"
                    class="navItem"
                >
                    Faults
                </a>
            </li>
            <li>
                <a
                    href="ward_summaries.php"
                    class="navItem"
                >
                    Wards
                </a>
            </li>

            <li>
                <a
                    href="ABOUTUS.html"
                    class="navItem"
                >
                    About Us
                </a>
            </li>

            <li>
                <a
                    href="about_us_page.html"
                    class="navItem"
                >
                    About M-Link
                </a>
            </li>

            <li>
                <a
                    href="view_notices.php"
                    class="navItem"
                >
                    Notices
                </a>
            </li>
            <li>
                <a
                    href="contactUs.html"
                    class="navItem"
                >
                    Contact us
                </a>
            </li>
        </ul>
    </div>
</nav>
<!-- 
     OVERVIEW
 -->
<section class="overview-section">
    <div class="overview-grid">

        <!-- LEFT COLUMN -->

        <div class="overview-left">
            <div class="overview-header">
                <div class="header-titles">
                    <h2 class="main-title">
                        Discover Makana
                    </h2>
                    <span class="sub-title">
                        WELCOME TO MAKANA
                    </span>
                </div>
            </div>
            <div class="Overview">
                <h3>

                    Makana Local Municipality encompasses Makhanda
                    and its surrounding communities within the
                    Sarah Baartman District.

                    Strategically located between Gqeberha and
                    East London, Makhanda blends a rich cultural
                    heritage with academic excellence, home to
                    Rhodes University, world-class schools, and
                    the iconic National Arts Festival across its
                    14 wards.

                    Dedicated to transparent governance and
                    service delivery, the municipality works to
                    bridge local leadership and residents.

                    Together, we are building a more connected,
                    responsive, and sustainable community for
                    everyone.

                </h3>
            </div>
            <!-- 
                 INTERESTING FACTS
             -->
            <div class="overview-bottom-row">
                <div class="facts-slideshow-card">
                    <div class="facts-header">
                        <h3>
                            Interesting Facts About Makhanda
                        </h3>
                        <div class="slideshow-controls">
                            <button
                                id="prevFactBtn"
                                class="slide-btn"
                                type="button"
                                aria-label="Previous Slide"
                            >
                                &#10094;
                            </button>
                            <button
                                id="nextFactBtn"
                                class="slide-btn"
                                type="button"
                                aria-label="Next Slide"
                            >
                                &#10095;
                            </button>
                        </div>
                    </div>
                    <div class="slideshow-wrapper">
                        <div
                            class="slideshow-track"
                            id="factsTrack"
                        >
                            <!-- FACT 1 -->
                            <div class="fact-slide">
                                <div>
                                    <img
                                        src="Facts Images/Wesleyan_Mission_Premises.jpg"
                                        alt="Founded in 1812"
                                        class="fact-image"
                                    >
                                </div>
                                <div class="fact-content">
                                    <h4>
                                        Founded in 1812
                                    </h4>
                                    <p>

                                        Established in 1812 as a military
                                        garrison by Colonel John Graham, the
                                        town evolved into a major educational
                                        and judicial hub home to Rhodes
                                        University and the National Arts
                                        Festival.

                                        In 2018, it was officially renamed
                                        Makhanda to honor the Xhosa warrior
                                        prophet who led the 1819 resistance
                                        against British colonial forces.

                                    </p>
                                </div>
                            </div>
                            <!-- FACT 2 -->

                            <div class="fact-slide">
                                <div>
                                    <img
                                        src="Facts Images/GrahamstownImage.jpg"
                                        alt="Renamed Makhanda"
                                        class="fact-image"
                                    >
                                </div>
                                <div class="fact-content">
                                    <h4>
                                        Its Name is now Makhanda
                                    </h4>
                                    <p>

                                        Officially renamed in 2018 to honor
                                        Makhanda ka Nxele, the Xhosa prophet
                                        and warrior who led the 1819 attack
                                        against British colonial forces during
                                        the Fifth Xhosa War.

                                        Today, the name reflects the town's
                                        rich heritage of resistance and
                                        African leadership.

                                    </p>
                                </div>
                            </div>
                            <!-- FACT 3 -->

                            <div class="fact-slide">
                                <div>
                                    <img
                                        src="Facts Images/Cathedral.jpg"
                                        alt="City of Saints"
                                        class="fact-image"
                                    >
                                </div>
                                <div class="fact-content">
                                    <h4>
                                        Known as the "City of Saints" ⛪
                                    </h4>
                                    <p>

                                        Affectionately known as the
                                        "City of Saints" due to its remarkably
                                        high concentration of historic
                                        churches, including the prominent
                                        Cathedral of St Michael and St George.

                                        Its streets feature beautifully
                                        preserved 19th-century Settler,
                                        Georgian, and Victorian architecture.

                                    </p>
                                </div>
                            </div>
                            <!-- FACT 4 -->

                            <div class="fact-slide">
                                <div>
                                    <img
                                        src="Facts Images/NAF.jpeg"
                                        alt="National Arts Festival"
                                        class="fact-image"
                                    >
                                </div>
                                <div class="fact-content">
                                    <h4>
                                        National Arts Festival 🎭
                                    </h4>
                                    <p>

                                        Founded in 1974, Africa's largest and
                                        longest-running multi-disciplinary
                                        cultural festival transforms the town
                                        every winter into a global arts hub.

                                        It brings together thousands of
                                        performers and visitors to celebrate
                                        theatre, dance, visual arts and music.
                                    </p>
                                </div>
                            </div>
                            <!-- FACT 5 -->
                            <div class="fact-slide">
                                <div>
                                    <img
                                        src="Facts Images/SettlersMonument.jpg"
                                        alt="1820 Settlers Monument"
                                        class="fact-image"
                                    >
                                </div>
                                <div class="fact-content">
                                    <h4>
                                        1820 Settlers Monument
                                    </h4>
                                    <p>
                                        Opened in 1974 to commemorate the
                                        British 1820 Settlers, this iconic
                                        landmark was reimagined following
                                        South Africa's transition to democracy
                                        and rededicated by Nelson Mandela in
                                        1996.

                                        It now serves as a cultural,
                                        conference and community centre.
                                    </p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <!-- RIGHT COLUMN -->

        <div class="summaries-sidebar">
            <div class="sidebar-card">
                <div class="sidebar-header">
                    <span class="sidebar-icon">
                        <img
                            src="article.svg"
                            alt="Article Icon"
                        >
                    </span>
                    <h3>
                        WARD SUMMARIES
                    </h3>
                </div>
                <ul class="summary-list">
                    <?php if ($result && mysqli_num_rows($result) > 0): ?>
                        <?php while ($row = mysqli_fetch_assoc($result)): ?>
                            <li class="summary-item">
                                <a
                                    href="../ward_councillor_dashboard/<?= htmlspecialchars($row["file_path"]) ?>"
                                    target="_blank"
                                    class="summary-title"
                                >
                                    <?= htmlspecialchars($row["title"]) ?>
                                </a>
                                <span class="summary-meta">
                                    <?= date(
                                        "M d, Y",
                                        strtotime($row["created_at"])
                                    ) ?>
                                    •
                                    Ward
                                    <?= htmlspecialchars($row["ward_id"]) ?>
                                </span>
                            </li>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <li class="summary-item">
                            No recent summaries uploaded.
                        </li>
                    <?php endif; ?>
                </ul>
                <a
                    href="ward_summaries.php"
                    class="more-link"
                >
                    MORE SUMMARIES →
                </a>
            </div>

            <!-- COMMUNITY HERO CARD -->

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

                        M-Link connects residents, ward councillors
                        and the municipality to improve communication
                        and service delivery across Makhanda.

                    </p>
                    <div class="hero-buttons">
                        <a
                            href="ward_summaries.php"
                            class="hero-btn primary"
                        >
                            View summaries
                        </a>
                        <a
                            href="ward_summaries.php"
                            class="hero-btn secondary"
                        >
                            Browse Wards
                        </a>
                        <a
                            href="ABOUTUS.html"
                            class="hero-btn secondary"
                        >
                            About us
                        </a>
                    </div>
                </div>
            </section>
        </div>
    </div>
</section>

<!-- COMMUNITY NOTICES -->

<section class="community-notices">
    <div class="section-heading">
        <span>
            STAY INFORMED
        </span>
        <h2>
            Latest Notices & Alerts
        </h2>
        <p>
            Important information and updates from the municipality
            and your community.
        </p>
    </div>
    <div class="notices-layout">
    <div class="notice-container">
        <?php if (!empty($homeNotices)): ?>
            <?php foreach ($homeNotices as $notice): ?>
                <article class="notice-card">
                    <div class="notice-category">
                        <?= htmlspecialchars(
                            strtoupper(
                                $notice["notice_category"]
                            )
                        ) ?>
                    </div>
                    <h3>
                        <?= htmlspecialchars(
                            $notice["notice_title"]
                        ) ?>
                    </h3>
                    <p>
                        <?= nl2br(
                            htmlspecialchars(
                                $notice["notice_description"]
                            )
                        ) ?>

                    </p>
                    <a href="view_notices.php">
                        Read More →
                    </a>
                </article>
            <?php endforeach; ?>
        <?php else: ?>
            <article class="notice-card">
                <div class="notice-category">
                    MUNICIPALITY
                </div>
                <h3>
                    No Current Notices
                </h3>
                <p>
                    There are currently no community-wide
                    municipal notices.
                </p>
                <a href="view_notices.php">
                    View Notices →
                </a>
            </article>
        <?php endif; ?>
    </div>
    <!-- WEATHER -->

    <aside class="weather-panel" aria-label="Weather in Makhanda">
        <div class="weather-panel-header">
            <svg
                class="weather-panel-icon"
                viewBox="0 0 24 24"
                aria-hidden="true"
            >
                <path d="M7 18a4 4 0 0 1-.6-7.96A5.5 5.5 0 0 1 17.2 9a4.5 4.5 0 0 1-.2 9H7z"/>
            </svg>

            <h3>Weather Info</h3>
        </div>
        <?php if ($weatherAvailable): ?>
            <div class="weather-row weather-localtime">
                <strong>Local Time</strong>
                <span class="weather-time">
                    <?= htmlspecialchars($localTime) ?>
                </span>
            </div>
            <?php foreach ($forecastDays as $day): ?>
                <div class="weather-row">
                    <div class="weather-day">
                        <strong>
                            <?= htmlspecialchars($day["label"]) ?>
                        </strong>
                        <small>
                            <?= htmlspecialchars($day["date"]) ?>
                        </small>
                    </div>
                    <img
                        class="weather-icon"
                        src="https:<?= htmlspecialchars($day["icon"]) ?>"
                        alt="<?= htmlspecialchars($day["condition"]) ?>"
                        title="<?= htmlspecialchars($day["condition"]) ?>"
                    >
                    <div class="weather-values">
                        <strong>
                            <?= htmlspecialchars($day["temp"]) ?>°C
                        </strong>
                        <small>
                            <?= htmlspecialchars($day["wind"]) ?> m/s
                        </small>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php else: ?>
            <p class="weather-unavailable">
                Weather information is unavailable right now.
                Please check back later.
            </p>
        <?php endif; ?>
        <p class="weather-credit">
            Weather data by
            <a
                href="https://www.weatherapi.com/"
                target="_blank"
                rel="noopener"
            >WeatherAPI.com</a>
        </p>

    </aside>
    </div>
</section>



<!-- EXECUTIVE MAYOR -->

<section class="mayor-section">
    <div class="mayor-container">
        <div class="mayor-photo-frame">
            <img
                src="mayor-yandiswa-vara.jpg"
                alt="Cllr Yandiswa Vara, Executive Mayor of Makana Local Municipality"
            >
        </div>
        <div class="mayor-content">
            <span class="mayor-eyebrow">
                Executive Leadership
            </span>
            <h2>
                Office of the Executive Mayor
            </h2>

            <p>

                The Office of the Executive Mayor provides
                strategic leadership and oversight for the
                municipality, working to improve service
                delivery, drive development, and enhance the
                well-being of all residents. As the head of the
                local government, the Executive Mayor leads the
                executive functions of the municipality,
                implements council decisions, and represents the
                city in official matters. The office is
                committed to transparency, accountability, and
                responsive governance in partnership with the
                community.

            </p>
            <div class="mayor-signature">
                <strong>Cllr Yandiswa Vara</strong>
                <span>Executive Mayor, Makana Local Municipality</span>
            </div>
            <div class="mayor-actions">
                <a
                    href="mailto:yvara@makana.gov.za"
                    class="mayor-email"
                >
                    yvara@makana.gov.za
                </a>
            </div>
        </div>
    </div>
</section>

<!-- ABOUT M-LINK  -->

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
            residents, community organisations, ward councillors
            and the municipality.

        </p>
        <p>

            Through M-Link, communities can report municipal
            faults, follow service-delivery issues, access ward
            information and stay informed about important local
            notices.

        </p>
        <a
            href="about_us_page.html"
            class="btn primary"
        >
            Learn More About M-Link
        </a>
    </div>
</section>

<section class="final-cta">
    <div>
        <h2>
            Be part of a better Makhanda.
        </h2>
        <p>
            Create an account to report faults, track your
            tickets and stay connected with your community.

        </p>
        <div class="cta-buttons">
            <a
                href="register.php"
                class="btn primary"
            >
                Create an Account
            </a>
            <a
                href="login.php"
                class="btn secondary"
            >
                Login
            </a>

        </div>

    </div>

</section>



<!-- FOOTER -->

<footer>


    <div class="footerContainer">


        <div class="makanaLogo">


            <div class="logoBrand">

                <img
                    src="MakanaLogo.svg"
                    alt="M-Link logo"
                >

            </div>


            <p>

                Empowering residents to take charge in their
                neighbourhood and work together to build a
                better, well-maintained Makhanda community.

            </p>


            <div class="socials">

                <a
                    href="#"
                    aria-label="X"
                >
                    <img
                        src="x.svg"
                        alt="X"
                    >
                </a>


                <a
                    href="#"
                    aria-label="Instagram"
                >
                    <img
                        src="instagram.svg"
                        alt="Instagram"
                    >
                </a>


                <a
                    href="#"
                    aria-label="Facebook"
                >
                    <img
                        src="facebook.svg"
                        alt="Facebook"
                    >
                </a>

            </div>


            <a
                href="#"
                class="backToTopBtn"
            >
                <span class="arrow">
                    ↑
                </span>

                BACK TO TOP
            </a>

        </div>



        <div class="siteMap">

            <h2>
                Site Map
            </h2>

            <a href="home.php">
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



        <div class="damCapacity">
            <h2>
                Dam Capacity
            </h2>
            <div class="dam-bar-item">
                <span class="dam-bar-label">
                    Howiesonspoort <strong>92%</strong>
                </span>
                <div class="dam-bar-track">
                    <div class="dam-bar-fill" style="width: 92%;"></div>
                </div>
            </div>


            <div class="dam-bar-item">
                <span class="dam-bar-label">
                    Settlers <strong>75%</strong>
                </span>
                <div class="dam-bar-track">
                    <div class="dam-bar-fill" style="width: 75%;"></div>
                </div>
            </div>

            <div class="dam-bar-item">
                <span class="dam-bar-label">
                    Jameson <strong>40%</strong>
                </span>
                <div class="dam-bar-track">
                    <div class="dam-bar-fill" style="width: 40%;"></div>
                </div>
            </div>

            <div class="dam-bar-item">
                <span class="dam-bar-label">
                    Milner <strong>40%</strong>
                </span>
                <div class="dam-bar-track">
                    <div class="dam-bar-fill" style="width: 40%;"></div>
                </div>
            </div>
        </div>
        
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
                    City Hall, High Str, Grahamstown, 6139
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
            Copyright © 2026 M-Link. All Rights Reserved.
        </p>

    </div>

</footer>



<!-- 
     JAVASCRIPT
 -->

<script>
document.addEventListener("DOMContentLoaded", function () {

    // ############### INTERESTING FACTS SLIDESHOW

    const factsTrack = document.getElementById("factsTrack");
    const factSlides = document.querySelectorAll(
        "#factsTrack .fact-slide"
    );

    const previousButton = document.getElementById("prevFactBtn");
    const nextButton = document.getElementById("nextFactBtn");
    let currentFactSlide = 0;


    function displayFactSlide() {
        if (!factsTrack || factSlides.length === 0) {
            return;
        }
        factsTrack.style.transform =
            "translateX(-" + (currentFactSlide * 100) + "%)";
    }


    function showNextFact() {
        if (factSlides.length === 0) {
            return;
        }
        currentFactSlide++;
        if (currentFactSlide >= factSlides.length) {
            currentFactSlide = 0;
        }
        displayFactSlide();
    }

    function showPreviousFact() {
        if (factSlides.length === 0) {
            return;
        }
        currentFactSlide--;
        if (currentFactSlide < 0) {
            currentFactSlide = factSlides.length - 1;
        }
        displayFactSlide();
    }

    if (nextButton) {
        nextButton.addEventListener("click", function () {
            showNextFact();
        });
    }

    if (previousButton) {
        previousButton.addEventListener("click", function () {
            showPreviousFact();
        });
    }

    //change fact automatically every 4 seconds
    if (factSlides.length > 1) {
        setInterval(showNextFact, 4000);

    }
});
</script>
</body>
</html>