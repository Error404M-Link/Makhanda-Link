<?php
    require "../Error404Connection.php";

    //####################### SEARCHING AND FILTERING ########################################
    // OLNY SEARCH WHEN THE SEARCH BUTTON IS CLICKED
    $isSearching = isset($_GET['search']) || isset($_GET['category']) || isset($_GET['status']);
    
    if($isSearching){
        $search = trim($_GET['search'] ?? '');
        $category = trim($_GET['category'] ?? '');
        $status = trim($_GET['status'] ?? '');

        $sql = "SELECT 
                    i.incident_id AS ticketId, 
                    ms.service_type AS category, 
                    i.incident_description AS description, 
                    i.location_description, 
                    i.status_id AS status
                FROM incident_ticket i
                LEFT JOIN municipal_service ms ON i.service_id = ms.service_id
                WHERE 1=1";
                
        $param = [];
        $types = "";

        // Location or keyword query filter
        if($search !== ''){
            $sql .=" AND (i.incident_description LIKE ? OR i.location_description LIKE ? OR ms.service_type LIKE ?)";
            $searchWildcard = "%" .$search ."%";
            array_push($param, $searchWildcard, $searchWildcard, $searchWildcard);
            $types .= "sss";
        }

        if($category !== ''){
            $sql .= " AND ms.service_type = ?";
            array_push($param, $category);
            $types .= "s";
        }

        if($status !== ''){
            $sql .= " AND i.status_id = ?";
            array_push($param, $status);
            $types .= "s";
        }
        $sql .= " ORDER BY i.incident_id DESC";

        $stmt = $conn->prepare($sql);
        if(!empty($param)){
            $stmt->bind_param($types, ...$param);
        }

        $stmt->execute();
        $result = $stmt->get_result();

        if($result->num_rows > 0){
            // DISPLAYING THE SEARCH RESULTS. 
            while($ticket = $result->fetch_assoc()){
                ?>
                <div class="faultListItem" style="padding: 15px; border-bottom: 1px solid #eee;">
                    <strong>Ticket #<?php echo $ticket['ticketId']; ?> - <?php echo htmlspecialchars($ticket['category']); ?> </strong>
                    <p style="margin: 5px 0 0 0; color: #555;"> Location: <?php echo htmlspecialchars($ticket['location_description']); ?></p>
                    <small style="color: #888;"> Status: <?php echo htmlspecialchars($ticket['status']); ?> </small>
                </div>
                <?php
            }
        }else{
            echo "<p style='padding: 20px; color: #666;'> No active faults reports match your criteria.</p>";
        } 

    }


    //#######################  FETCH ALL THE ACTIVE FAULTS FOR THE MAP #######################
    // Fetch active or reported faults joined with municipal_service to get category
    $query = "SELECT 
                i.incident_id, 
                i.ward_id, 
                ms.service_type AS category, 
                i.latitude, 
                i.longitude, 
                i.incident_description AS description, 
                i.status_id AS status, 
                i.location_description
            FROM incident_ticket i
            LEFT JOIN municipal_service ms ON i.service_id = ms.service_id";

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

    $sql = "SELECT ms.service_type AS category, COUNT(*) as total 
            FROM incident_ticket i
            LEFT JOIN municipal_service ms ON i.service_id = ms.service_id
            GROUP BY ms.service_type";

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
        $result->close();
    }else{
        die("Database Query Failed: " .$conn->error);
    }

    //$stmt->close();

?>