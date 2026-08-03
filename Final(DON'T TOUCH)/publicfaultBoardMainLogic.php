<?php
    include "connection.php";

    //####################### SEARCHING AND FILTERING ########################################

    /*$search = trim($_GET['search'] ?? '');
    $category = trim($_GET['category'] ?? '');
    $status = trim($_GET['status'] ?? '');

    $sql = "SELECT * FROM faultTickets WHERE 1=1";
    $param = [];
    $types = "";

    // Location or keyword query filter
    if($search !== ''){
        // searchs against standard ticket fields like fault description, categories, or street name
        $sql .=" AND (description LIKE ? OR streetName LIKE ? OR category LIKE ?)";
        $searchWildcard = "%" .$search ."%";
        array_push($param, $searchWildcard, $searchWildcard, $searchWildcard);
        $types .= "sss";
    }

    // Exact match drop-down filtering logic
    if($category !== ''){
        $sql .= "AND category = ?";
        array_push($param, $category);
        $types .= "s";
    }

    if($status !== ''){
        $sql .= " AND status = ?";
        array_push($param, $status);
        $types .= "s";
    }

    $sql .= " ORDER BY ticketId DESC";

    $stmt = $conn->prepare($sql);
    if(!empty($param)){
        $stmt->bind_param($types, ...$param);
    }

    $stmt->execute();
    $result = $stmt->get_result();

    // OUTPUT RENDER ENGINE
    if($result->num_rows > 0){
        while($ticket = $result->fetch_assoc()){
            // Output the structural HTML row layout item design
            ?>
            <div class="faultListItem" style="padding: 15px; border-bottom: 1px solid #eee;">
                <strong>Ticket #<?php echo $ticket['ticketId']; ?> - <?php echo htmlspecialchars($ticket['category']); ?> </strong>
                <p style="margin: 5px 0 0 0; color: #555;"> Location: <?php echo htmlspecialchars($ticket['description']); ?></p>
                <small style="color: #888;"> Status: <?php echo htmlspecialchars($ticket['status']); ?> </small>
            </div>
            <?php
        }
    }else{
        echo "<p style='padding: 20px; color: #666;'> No active faults reports match your criteria.</p>";
    } */


    //#######################  FETCH ALL THE ACTIVE FAULTS FOR THE MAP #######################
    
    
    // Fetch active or reported faults

    // ################## The following is for testing purposes ####
    $query = "SELECT ticketId, wardId, category, latitude, longitude, description,status, imagePath 
              FROM faulttickets 
              ";
    
    /*

    // ############################ This is the initial requirements (fields) for displaying the fault tickets
    
    $query = "SELECT ticketId, title, status, category, streetName, latitude, longitude, dateCreated, photoUrl 
              FROM faulttickets 
              WHERE status NOT IN ('Resolved', 'Closed') 
              ORDER BY dateCreated DESC";
    
    */

    $result = $conn->query($query);

    $ticketArray = []; //List of the rows
    if($result && $result->num_rows > 0){
        while($row = $result->fetch_assoc()){
            $ticketArray[] = $row;
        }
    }

    // Convert data to JSON for JS map engine
    $jsonTickets = json_encode($ticketArray);


    // ############################## DISPLAYING THE FAULT STATS #################################

    $counts = [
        "Roads & Streets"        => 0,
        "Water & Sewer"          => 0,
        "Electricity & Lighting" => 0,
        "Other"                  => 0
    ];

    $sql = "SELECT category, COUNT(*) as total FROM faulttickets GROUP BY category";
    $result = $conn->query($sql);

    if ($result) {
        while ($row = $result->fetch_assoc()) {
            $dbCategory = trim($row['category']);
            $total = (int)$row['total'];
            $dbCategoryLower = strtolower($dbCategory);

            if ($dbCategoryLower === 'pothole') {
                $counts["Roads & Streets"] += $total;
            } 
            elseif ($dbCategoryLower === 'water leak') {
                $counts["Water & Sewer"] += $total;
            }
            elseif ($dbCategoryLower === 'electricity') {
                $counts["Electricity & Lighting"] += $total;
            } else {
                $counts["Other"] += $total;
            }
        }
    }

    //$stmt->close();
   
    $result->close();

?>