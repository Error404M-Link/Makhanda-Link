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
    </head>
    <body>

        <nav class="navbar"> 
            <div class="logo">
                <img src="MakanaLogo.svg" alt="Makhanda logo">
            </div> 
            <ul class="nav-links">
                <li><a href="#" class="link"> Home </a></li>
                <li><a href="publicfaultBoard.php" class="link"> Faults </a></li>
                <li><a href="#" class="link"> About us </a></li>
                <li><a href="#" class="link"> Notices  </a></li>
                <div id="navbuttons">
                    <li><a href="#" class="btn"> Login </a></li>
                    <li><a href="#" class="btn"> Sign up </a></li>
                </div>
            </ul> 
        </nav>
        <hr>

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
        <a href="#"><button id="floatingBookBtn" class="floatingBtn">Report a fault</button></a>
        <script src="publicFaultBoard.js"></script>
    </body>
</html>