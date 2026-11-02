<?php require "publicfaultBoardMainLogic.php" ?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Public Fault</title>

    <!-- STYLESHEETS -->
    <link rel="stylesheet" href="publicfaultBoard.css">
    <link rel="stylesheet" href="backButton.css">
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css">
</head>
<body>
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
                <li><a href="#" class="navItem active">Faults</a></li>
                <li><a href="ward_summaries.php" class="navItem"> Wards </a></li>
                <li><a href="ABOUTUS.html" class="navItem">About Us</a></li>
                <li><a href="about_us_page.html" class="navItem">About M-Link</a></li>
                <li><a href="view_notices.php" class="navItem">Notices</a></li>
                <li><a href="contactUs.html" class="navItem">Contact us</a></li>
            </ul>
        </div>
    </nav>

    <!-- Searching and stuff -->
    <section class="searchFilterWrapper">

        <div class="backNavWrapper">
            <div class="backNavContainer">
                <a href="javascript:history.back()" class="backBtn">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <line x1="19" y1="12" x2="5" y2="12"></line>
                        <polyline points="12 19 5 12 12 5"></polyline>
                    </svg>
                    <span>Back</span>
                </a>
            </div>
        </div>

        <div class="searchBox">
            <input type="text" id="faultSeachInput" placeholder="Search by keyword or location...">
            <span class="searchIcon">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="#666" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <circle cx="11" cy="11" r="8"></circle>
                    <line x1="21" y1="21" x2="16.65" y2="16.65"></line>
                </svg>
            </span>
        </div>

        <div class="filterDropdown">
            <select id="categoryFilter">
                <option value="">All Categories</option>
                <?php foreach ($faultCategories as $categoryOption): ?>
                    <option value="<?php echo htmlspecialchars($categoryOption); ?>">
                        <?php echo htmlspecialchars($categoryOption); ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>

        <div class="filterDropdown">
            <select id="statusFilter">
                <option value="">All Statuses</option>
                <option value="Pending">Pending</option>
                <option value="In Progress">In Progress</option>
                <option value="Resolved">Resolved</option>
            </select>
        </div>
    </section>

    <!-- Container for the search results (filled in only while a filter is active) -->
    <div id="faultResultsContainer" class="faultGrid" data-unresolved-only="0" data-limit="30"></div>


    <div id="grahamstownMap">
    <!-- THIS CONTAINER HOLDS THE GHT MAP -->
    </div>

            <!-- ################# DISPLAYING THE STATS ################### -->

    <section id="FaultStats">

        <ul class="categoryList">
            <?php foreach ($counts as $categoryName => $countValue): ?>
                <li class="categoryItem">

                    <div class="statCounter">
                        <div class="statReel" data-target="<?php echo (int) $countValue; ?>">
                            <span>0</span>
                        </div>
                    </div>

                    <span>
                        <?php echo htmlspecialchars($categoryName); ?>
                    </span>

                </li>
            <?php endforeach; ?>
        </ul>
    </section>

    <div class="HowItWorksHeader">
        <div class="HowItWorksHeaderText">
            <strong>Recent Faults</strong>
        </div>
    </div>

    <section class="faultGrid">

        <?php
            $limitedTickets = array_slice($ticketArray, 0, 4); // show only 4 tickets, not all of them
            if (!empty($limitedTickets)):
                foreach ($limitedTickets as $ticket):
                    echo renderFaultCard($ticket);
                endforeach;
            else:
        ?>
            <p style="grid-column: 1/-1; text-align: center; color: #777; padding: 40px;">No active municipal faults reported right now.</p>
        <?php endif; ?>
    </section>

        <!-- The button to show all faults -->
    <div class="centerBtnWrapper centerBtnWrapper2">
        <a href="allFaultTickets.php" class="viewAllBtn">View All Faults</a>
    </div>

        <!-- ################# How it works ######################### -->
    <div class="mobileToggleWrapper centerBtnWrapper">
        <a href="allFaultTickets.php" class="viewAllBtn">View All Faults</a>
        <button id="openhowItWorksBtn">How it works</button>
    </div>

    <section id="howItWorksWrapper">
        <!-- brief description of how faults work -->

        <span class="closeHowItWorks">&times;</span>

        <div class="HowItWorksHeader">
            <div class="HowItWorksHeaderText">
                <strong>How It works</strong>
            </div>
        </div>

        <div class="howItWorksGrid"> <!-- Container  -->
            <div class="howItWorksCard card1">
                <img src="report.svg" alt="Report logo">
                <strong>1. Report</strong>

                <p>Report faults in your area using our online form or contact the municipality.</p>
            </div>

            <div class="howItWorksCard card2">
                <img src="search.svg" alt="Search logo">
                <strong>2. Review</strong>

                <p>Our team reviews and prioritizes reported faults.</p>
            </div>

            <div class="howItWorksCard card3">
                <img src="build.svg" alt="Build log">
                <strong>3. Resolve</strong>

                <p>We work to resolve the issue and keep you updated.</p>
            </div>

            <div class="howItWorksCard card4">
                <img src="check_circle.svg" alt="Check Circle">
                <strong>4. Complete</strong>

                <p>Issues are resolved and marked as completed.</p>
            </div>
        </div>

        <!-- Button -->
        <span class="ServiceHelpButton">Contact Us For Help And Services <a href="#">Let's Get Started</a></span>

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
                <a href="FAQ.html">FAQs</a>
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

    <a href="#"><button id="floatingBookBtn" class="floatingBtn">Report a fault</button></a>

    <!-- JAVASCRIPT LINKS -->
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
    <script src="publicFaultBoard.js"></script>
    <script>
        const activeTickets = <?php echo $jsonTickets; ?>;
        initGrahamstownMap(activeTickets);
    </script>
</body>
</html>

<?php $conn->close(); ?>