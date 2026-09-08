<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Ward councilor's Update</title>
    <link rel="stylesheet" href="view_notices.css">
    <link rel="stylesheet" href="publicfaultBoard.css">


</head>

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
                <li><a href="#" class="navItem">Faults</a></li>
                <li><a href="viewWards.html" class="navItem"> Wards </a></li>
                <li><a href="about_us_page.html" class="navItem">About Us</a></li>
                <li><a href="view_notices.php" class="navItem active">Notices</a></li>
                <li><a href="contactUs.html" class="navItem">Contact us</a></li>
            </ul> 
        </div>
    </nav>

<body>
    
    <hr>

   <div class="backNavWrapper">  
    <div class="backNavContainer">  

        <a href="#" onclick="history.back(); return false;" class="backBtn">  

            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" 
                 stroke="currentColor" stroke-width="2" 
                 stroke-linecap="round" stroke-linejoin="round">  

                <line x1="19" y1="12" x2="5" y2="12"></line> 
                <polyline points="12 19 5 12 12 5"></polyline> 

            </svg>  

            <span>Back</span>  

        </a>  

    </div>  
</div>

<h2 class="notice-title">Ward Public Notice Board</h2>


<br>
<br>

<form method="GET" action="view_notices.php">
<label>Select Ward:</label>
<select name="ward" id="ward">
    <option value="">All Wards</option>
    <option value="1">Ward 1</option>
    <option value="2">Ward 2</option>
    <option value="3">Ward 3</option>
    <option value="4">Ward 4</option>
    <option value="5">Ward 5</option>
    <option value="6">Ward 6</option>
    <option value="7">Ward 7</option>
    <option value="8">Ward 8</option>
    <option value="9">Ward 9</option>
    <option value="10">Ward 10</option>
    <option value="11">Ward 11</option>
    <option value="12">Ward 12</option>
    <option value="13">Ward 13</option>
    <option value="14">Ward 14</option>
</select> 
<button type="submit">View Notice</button> 
</form>
<hr>
</body> 


<?php
include("Error404Connection.php");

$ward = $_GET["ward"] ?? ""; 

if ($ward == "") {
    $sql = "SELECT * FROM notices 
            ORDER BY date_posted DESC";
} else {
    $sql = "SELECT * FROM notices 
            WHERE ward = '$ward' 
            ORDER BY notice_date DESC";
}

$results = $conn->query($sql); 
?>

<div class="notice-container">

<?php
if ($results && $results instanceof mysqli_result) {
    while ($row = $results->fetch_assoc()) {
?>

        <div class="notice-card">
            <h3><?php echo "Ward: "  . htmlspecialchars($row['ward']); ?></h3>
            <h3><?php echo "Title: " . htmlspecialchars($row['title']); ?></h3>
            <h3><?php echo "Date: " . htmlspecialchars($row['date_posted']); ?></h3>
            <p><?php echo htmlspecialchars($row['message']); ?></p>
        </div>

<?php
    }
} else {
    // Show error message if the query fails
    echo "<p>Error loading notices: " . $conn->error . "</p>";
}
?>

</div>


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
