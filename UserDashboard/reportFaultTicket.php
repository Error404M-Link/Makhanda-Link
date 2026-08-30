<?php 
    require "../Error404Connection.php";

    $message = "";

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $wardId      = intval($_POST['wardId']);
        $serviceId   = $_POST['serviceId']; 
        $addressId   = intval($_POST['addressId'] ?? 0);
        $statusId    = 1;
        $streetName  = $_POST['streetName'];
        $description = $_POST['description'];
        $locationDescription = !empty($_POST['locationDescription']) ? $_POST['locationDescription'] : null;
        
                                                        // Read raw coordinates from the map's hidden inputs
        $latitude  = $_POST['latitude'] ?? null;
        $longitude = $_POST['longitude'] ?? null;

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

                                                        // Handle file uploads if any
        $imagePath = null;                              //store the image path
        if (isset($_FILES['evidence_image']) && $_FILES['evidence_image']['error'] === UPLOAD_ERR_OK) {
            $fileTmpPath = $_FILES['evidence_image']['tmp_name'];
            $fileName    = $_FILES['evidence_image']['name'];
            $fileExtension = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));
            
            $allowedExtensions = ['jpg', 'jpeg', 'png', 'webp'];
            
            if (in_array($fileExtension, $allowedExtensions)) {
                $newFileName = bin2hex(random_bytes(16)) . '.' . $fileExtension;
                $uploadFileDir = './IncidentImage/';
                
                if (!is_dir($uploadFileDir)) {
                    // Create the directory of doesn't exist.
                    mkdir($uploadFileDir, 0755, true);
                }
                
                $dest_path = $uploadFileDir . $newFileName;
                if (move_uploaded_file($fileTmpPath, $dest_path)) {
                    $imagePath = $dest_path; 
                }
            }
        }

                                                        // Secure database with prepared statements
        $stmt = $conn->prepare("INSERT INTO incident_ticket (username, service_id, ward_id, address_id, status_id, incident_description, location_description, latitude, longitude) 
                                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("siiiissdd", $username, $serviceId, $wardId, $addressId, $statusId, $description, $locationDescription, $latitude, $longitude);
                                                        
                                                        // Display message to the user 
        if ($stmt->execute()) {
            $message = "<div style='color: green; font-weight: bold;'>Success! Ticket Has been saved.</div>";
        } else {
            $message = "<div style='color: red; font-weight: bold;'>Error saving record: " . $stmt->error . "</div>";
        }

        $stmt->close();
    }
?>