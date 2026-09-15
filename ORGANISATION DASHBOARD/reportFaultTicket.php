<?php 
    if(session_start() === PHP_SESSION_NONE){
        session_start();
    }

    require "../Error404Connection.php";

    $message = ""; // STORE MESSAGES FOR DISPLAY

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $wardId      = $_POST['wardId'] ?? null;
        $category    = $_POST['category'] ?? '';
        $streetName  = $_POST['streetName'] ?? '';
        $description = $_POST['description'] ?? '';
        $locationDescription = !empty($_POST['locationDescription']) ? $_POST['locationDescription'] : null;
        
                                                        // use logged-in session user, fallback to guest if not set
        $username = $_SESSION['username'] ?? 'community_user';
                                                        // Read raw coordinates from the map's hidden inputs
        $latitude  = $_POST['latitude'] ?? '';
        $longitude = $_POST['longitude'] ?? '';

                                                        // FALLBACK PROTECTION: If map coordinates are completely empty, inject street defaults
        if (empty($latitude) || empty($longitude)) {
            $streetCoordinates = [
                "High Street"    => ["lat" => -33.3102, "lng" => 26.5266],
                "African Street" => ["lat" => -33.3121, "lng" => 26.5244],
                "Raglan Road"    => ["lat" => -33.3033, "lng" => 26.5455]
            ];

            if (array_key_exists($streetName, $streetCoordinates)) {
                $latitude  = $streetCoordinates[$streetName]['lat'];
                $longitude = $streetCoordinates[$streetName]['lng'];
            } else {
                                                        // Default center of Makhanda
                $latitude  = -33.3106;
                $longitude = 26.5256;
            }
        }
                                                        // Default image path fallback for now
        $imagePath = null; 
                                                        // HANDLING FAULT TICKET FILE/IMAGE UPLOAD IF ANY
        if (isset($_FILES['evidence_image']) && $_FILES['evidence_image']['error'] === UPLOAD_ERR_OK) {
            $fileTmpPath = $_FILES['evidence_image']['tmp_name'];
            $fileName    = $_FILES['evidence_image']['name'];
            $fileExtension = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));
            
            $allowedExtensions = ['jpg', 'jpeg', 'png', 'webp'];
            
            if (in_array($fileExtension, $allowedExtensions)) {
                $newFileName = bin2hex(random_bytes(16)) . '.' . $fileExtension;
                $uploadFileDir = './uploads/Fault Tickets/';
                
                if (!is_dir($uploadFileDir)) {
                    mkdir($uploadFileDir, 0755, true);
                }
                
                $dest_path = $uploadFileDir . $newFileName;
                if (move_uploaded_file($fileTmpPath, $dest_path)) {
                    $imagePath = $dest_path; 
                }
            }
        }

        // GET CATEGORY/SERVICES
        $serviceId = null;
        $stmtService = $conn->prepare("SELECT service_id
                                       FROM municipal_service
                                       WHERE service_type = ? LIMIT 1");
        $stmtService->bind_param("s", $category);
        $stmtService->execute();
        $resService = $stmtService->get_result();
        if($rowS = $resService->fetch_assoc()){
            $serviceId = $rowS['service_id'];
        }
        $stmtService->close();

        // LOOK UP THE STREETS FROM THE address_address TABLE
        $address_id = null;
        $stmtAddress = $conn->prepare("SELECT address_id 
                                       FROM address_directory 
                                       WHERE street_name = ? LIMIT 1");
        $stmtAddress->bind_param("s", $streetName);
        $stmtAddress->execute();
        $resAddress = $stmtAddress->get_result();
        if ($rowA = $resAddress->fetch_assoc()) {
            $address_id = $rowA['address_id'];
        }
        $stmtAddress->close();

        $statusId = 1;
        // INSERT PENDING TIMESTAMP INTO ticket_status
        $stmtStatus = $conn->prepare("INSERT INTO ticket_status (pending)
                                      VALUES (NOW())
                                     ");
        $stmtStatus->execute();
        $statusId = $stmtStatus->insert_id;
        $stmtStatus->close();
/*
    WHEN THE ADMIN OR STAFF UPDATE TICKET STATE LATER, RUN THE QUERY BELOW TO UPDATE

    UPDATE ticket_status SET InProgress = NOW() WHERE status_id = ?

*/
        // Link $statusId when inserting into incident_ticket
        $stmt = $conn->prepare("INSERT INTO incident_ticket 
            (username, service_id, ward_id, address_id, status_id, incident_description, location_description, latitude, longitude) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");

        $stmt->bind_param(
            "siiiissdd", 
            $username, 
            $serviceId, 
            $wardId, 
            $address_id, 
            $statusId, 
            $description, 
            $locationDescription, 
            $latitude, 
            $longitude
        );

        if(!$serviceId || !$address_id || !$wardId){
            $message = "<div style='color: red; font-weight: bold;'>Error: Missing required ward, service, or address lookup information.</div>";
        }else{
            $stmt = $conn->prepare("INSERT INTO incident_ticket 
                                    (username, service_id, ward_id, address_id, status_id, incident_description, location_description, latitude, longitude) 
                                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
            $stmt->bind_param(
                "siiiissdd", 
                $username, 
                $serviceId, 
                $wardId, 
                $address_id, 
                $statusId, 
                $description, 
                $locationDescription, 
                $latitude, 
                $longitude
            );
                                                        // DISPLAY MESSAGES TO THE USER 
            if ($stmt->execute()) {
                $message = "<div style='color: green; font-weight: bold;'>Success! Ticket Has been saved.</div>";
            } else {
                $message = "<div style='color: red; font-weight: bold;'>Error saving record: " . $stmt->error . "</div>";
            }
            $stmt->close();
        }
    }
?>