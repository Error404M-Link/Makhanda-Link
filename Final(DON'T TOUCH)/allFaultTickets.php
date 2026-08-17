<?php

    // #################### DISPLAY ALL THE UNREOLVED FAULT TICKETS ##################

    include "connection.php";
                                        // fetch all the unresolved faults
    $query = "SELECT * FROM faultTickets 
            WHERE LOWER(status) != 'resolved' 
            ORDER BY createdAt DESC";

    $result = $conn->query($query);

    $unresolvedTickets = [];
    if($result && $result->num_rows > 0){
        $unresolvedTickets = $result->fetch_all(MYSQLI_ASSOC); 
    }

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

        <section class="faultGrid">
            <?php 
                if(!empty($unresolvedTickets)):
                    foreach($unresolvedTickets as $ticket):
                        // Normalise status values for CSS selectors (e.g., "In Progress" -> "in-progress")
                        $statusClass = strtolower(str_replace(' ', '-', $ticket['status']));
                        
                        // Checking image path configurations
                        $imageStyle = !empty($ticket['imagePath']) ? "background-image: url('" . htmlspecialchars($ticket['imagePath']) . "');" : "";
            ?>

            <div class="faultCard">
                <?php 
                    // if an image exists then the it will be used for the fault card
                    if (!empty($ticket['imagePath'])) {
                        $imageUrl = htmlspecialchars($ticket['imagePath']);
                    } else {
                        $imageUrl = 'MakanaLogo.svg'; 
                        
                        /* 
                        // Option B: Dynamic default based on category (Uncomment if you want specific icons!)
                        switch($ticket['category']) {
                            case 'Roads & Streets': $imageUrl = 'default-roads.jpg'; break;
                            case 'Water & Sewer': $imageUrl = 'default-water.jpg'; break;
                            case 'Electricity & Lighting': $imageUrl = 'default-electricity'; break;
                            default: $imageUrl = 'default-generic.jpg';
                        }
                        */
                    }
                    $imageStyle = "background-image: url('" . $imageUrl . "');";
                ?>

                <!-- Container holding the picture  -->
                <div class="cardImageWrapper" style="<?php echo $imageStyle; ?>">
                    <span class="statusBadge <?php echo $statusClass; ?>">
                        <?php echo htmlspecialchars($ticket['status']); ?>
                    </span>
                </div>

                <!-- Container holding the content for the ticket -->
                <div class="cardContent">
                    <h3>
                        <?php echo htmlspecialchars($ticket['category']); ?>
                    </h3>

                    <div class="location">
                        <?php echo htmlspecialchars($ticket['description']); ?>
                    </div>

                    <a href="view_ticket.php?id=<?php echo $ticket['ticketId']; ?>" class="viewBtn">View Details</a>
                </div>
            </div>
            
            <?php 
                    endforeach; 
                else: 
            ?>
                <p style="grid-column: 1/-1; text-align: center; color: #555; font-size: 1.2rem; padding: 60px 20px; border: 2px dashed #ccc; border-radius: 8px; max-width: 600px; margin: 0 auto;">
                    There is no outstanding unresolved reported right now.
                </p>
            <?php 
                endif; 
                $conn->close();
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