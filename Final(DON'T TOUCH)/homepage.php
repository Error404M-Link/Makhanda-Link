<?php

$apiKey = "41e9e6e8ac1546bc8b7155723261907";
$city = "Grahamstown";

$url = "https://api.weatherapi.com/v1/current.json?key=$apiKey&q=" . urlencode($city);

$response = file_get_contents($url);

if ($response === false) {
    die("Could not connect to Weather API");
}

$data = json_decode($response, true);

if (isset($data['error'])) {
    die("Weather API Error: " . $data['error']['message']);
}

$temp = $data['current']['temp_c'];
$condition = $data['current']['condition']['text'];
$icon = $data['current']['condition']['icon'];

?>






<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Homepage</title>
<link rel="stylesheet" href="homepage.css">

</head>

 <body>
    <!--Navigation bar code--->
<nav class="navbar"> 
        <div class="logo">
            <img src="MakanaLogo.svg" alt="Makhanda logo">
        </div> 
        <ul class="nav-links">
            <li><a href="#" class="link"> Home </a></li>
            <li><a href="PublicFaultBoard.php" class="link"> Fault </a></li>
            <li><a href="ward_notices.php" class="link"> Notices  </a></li>
            <li><a href="about_us_page.html" class="link"> About us </a></li>
            <li><a href="contactUs.html" class="link"> Contact us </a></li>
            <div id="navbuttons">
                <li><a href="login.html" class="btn"> Login </a></li>
                <li><a href="register.html" class="btn"> Sign up </a></li>
            </div>
        </ul> 
    </nav>


<section class="hero">
    <div class="hero-overlay">
        <h1>Welcome to Makhanda Link</h1>
        <p>Connecting residents with Makana Local Municipality to improve service delivery.</p>

        <div class="hero-buttons">
            <a href="#" class="btn primary">Report a Fault</a>
            <a href="#" class="btn secondary">Browse Wards</a>
            <a href="#" class="btn secondary">View Notices</a>
        </div>
    </div>
</section>
<section class="overview-section">

    <!-- Header -->
    <div class="overview-header">
        <button id="menuBtn" class="menu-btn">&#9776;</button>
        <h2>Makana Overview</h2>
    </div>

    <!-- Two-column layout -->
    <div class="overview-container">

        <!-- Hidden Ward Panel -->
        <div id="wardPanel" class="ward-panel">

            <h3>Wards</h3>

            <ul>
                <li><a href="ward1.php">Ward 1</a></li>
                <li><a href="ward2.php">Ward 2</a></li>
                <li><a href="ward3.php">Ward 3</a></li>
                <li><a href="ward4.php">Ward 4</a></li>
                <li><a href="ward5.php">Ward 5</a></li>
                <li><a href="ward6.php">Ward 6</a></li>
                <li><a href="ward7.php">Ward 7</a></li>
                <li><a href="ward8.php">Ward 8</a></li>
                <li><a href="ward9.php">Ward 9</a></li>
                <li><a href="ward10.php">Ward 10</a></li>
                <li><a href="ward11.php">Ward 11</a></li>
                <li><a href="ward12.php">Ward 12</a></li>
                <li><a href="ward13.php">Ward 13</a></li>
                <li><a href="ward14.php">Ward 14</a></li>
            </ul>

        </div>

        <!-- Overview -->
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



<!-- COMMUNITY NOTICES SECTION -->
<section class="notices">
    <h2> Community Notices</h2>

    <div class="notice-container">

        <div class="notice-card">
            <h3>Water outage in Ward 4</h3>
            <p>Temporary water interruption due to maintenance work.</p>
        </div>

        <div class="notice-card">
            <h3>Electricity maintenance</h3>
            <p>Planned electricity shutdown from 08:00 - 16:00.</p>
        </div>

        <div class="notice-card">
            <h3>Road repairs on High Street</h3>
            <p>Expect delays due to ongoing road maintenance.</p>
        </div>

    </div>
</section>
 

    
<!---- Town information---->
    <section class="notices">
    <h2>Town Information</h2>

    <div class="notice-container">
        <div class="notice-card">
            <div class="Icon">
                <h3>weather</h3>
                <img src="https:<?= $icon ?>" alt="<?= htmlspecialchars($condition) ?>">
            </div>
            <h3><?= htmlspecialchars($condition) ?></h3>
            <p><?= $temp ?>°C</p>
        </div>

        <div class="notice-card">
            <div class="Icon">⚠️</div>
            <h3>Safety Notices</h3>
            <p>Avoid flooded roads.</p>
        </div>

        <div class="notice-card">
            <div class="Icon">🎉</div>
            <h3>Upcoming Event</h3>
            <p>Youth Day Celebration</p>
        </div>

    </div>
</section>


    <!-- ================= DAM LEVELS ================= -->

<section class="dam-levels">

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
        
    





<footer>
        <div class="footerContainer">
            <div class="makanaLogo">
                <div class="logoBrand">
                    <img src="MakanaLogo.svg" alt="Makhanda logo">
                    
                </div>
                <p>
                    Empowering you to take charge in your neighborhood and
                    work together to build a better,
                    well-maintained Makana community.
                </p>

                <div class="socials">
                    <a href="#"><img src="x.svg" alt="X"></a>
                    <a href="#"><img src="instagram.svg" alt="Instagram"></a>
                    <a href="#"><img src="facebook.svg" alt="Facebook"></a>
                </div>

                <a href="#top" class="backToTopBtn">
                    <span class="arrow">↑</span> BACK TO TOP
                </a>
            </div>

            <div class="siteMap">
                <h2>Site Map</h2>
                <a href="#">Homepage</a>
                <a href="#">About Us</a>
                <a href="#">Report A Fault</a>
                <a href="#">Contact Us</a>
            </div>

            <div class="contact">
                <h2>Contact</h2>
                <a href="mailto:zkewuti@makana.gov.za">Customer Care</a>
                <a href="mailto:info@makana.gov.za">Service Delivery</a>
                <a href="mailto:enquiries@makana.gov.za">Billing Related</a>
                
                <div class="address">
                    <p>City Hall, High Str, Grahamstown, 6139</p>
                    <p><img src="call.svg" alt="phoneIcon"> +27 (0) 46 603 6134</p>
                </div>
            </div>
        </div>

        <div class="copyrightBar">
            <p>Copyright © 2026 makana. All Rights Reserved.</p>
        </div>
    </footer>
<script>
const menuBtn = document.getElementById("menuBtn");
const wardPanel = document.getElementById("wardPanel");

menuBtn.addEventListener("click", function () {
    wardPanel.classList.toggle("show");
});
</script>
</body>
</html>