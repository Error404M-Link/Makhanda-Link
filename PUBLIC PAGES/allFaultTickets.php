<?php

    // #################### DISPLAY ALL THE UNRESOLVED FAULT TICKETS ##################

    require "../Error404Connection.php";
    require "faultDataHelper.php";

    // Unresolved tickets only, newest first - no cap here since this page IS the "see everything" view
    $unresolvedTickets = fetchFaultTickets($conn, [], true, null);

    // Dropdown data for the search/filter bar
    $faultCategories = getFaultCategories($conn);
    $faultWards      = getFaultWards($conn);

?>

<!DOCTYPE html>
<html lang="en">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>All Faults</title>

        <!-- STYLESHEETS -->
        <link rel="stylesheet" href="publicfaultBoard.css">
        <link rel="stylesheet" href="backButton.css">
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
                    <a href="login.html" class="utilityBtn btn">Login</a>
                    <a href="register.html" class="utilityBtn btn">Sign Up</a>
                </div>
            </div>
        </div>

        <!--  WHITE MAIN NAVIGATION BAR -->
        <nav class="mainNavbar">
            <div class="navContainer">
                <ul class="mainNavLinks">
                    <li><a href="index.php" class="navItem">Home</a></li>
                    <li><a href="publicFaultBoard.php" class="navItem">Faults</a></li>
                    <li><a href="viewWards.html" class="navItem"> Wards </a></li>
                    <li><a href="about_us_page.html" class="navItem">About Us</a></li>
                    <li><a href="ward_notices.php" class="navItem">Notices</a></li>
                    <li><a href="contactUs.html" class="navItem">Contact us</a></li>
                </ul>
            </div>
        </nav>

        <!--  BACK BUTTON  -->

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

        <div class="faultsHeader">
            <strong>Active Community Faults</strong>
            <h2>Track active Makhanda infrastructure issues.</h2>
        </div>

        <!-- Search and filter for finding a specific ticket -->
        <section class="searchFilterWrapper">
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
                <select id="wardFilter">
                    <option value="">All Wards</option>
                    <?php foreach ($faultWards as $ward): ?>
                        <option value="<?php echo (int) $ward['ward_id']; ?>">
                            Ward <?php echo htmlspecialchars($ward['ward_number']); ?> &mdash; <?php echo htmlspecialchars($ward['ward_name']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
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

        <section class="faultGrid" id="faultResultsContainer" data-unresolved-only="1">
        <?php
            if (!empty($unresolvedTickets)):
                foreach ($unresolvedTickets as $ticket):
                    echo renderFaultCard($ticket);
                endforeach;
            else:
        ?>
            <p style="grid-column: 1/-1; text-align: center; color: #555; font-size: 1.2rem; padding: 60px 20px; border: 2px dashed #ccc; border-radius: 8px; max-width: 600px; margin: 0 auto;">
                There are no outstanding unresolved reports right now.
            </p>
        <?php
            endif;

            // Safety check before closing connection
            if (isset($conn) && $conn instanceof mysqli) {
                $conn->close();
            }
        ?>
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
        <script src="publicFaultBoard.js"></script>
    </body>
</html>