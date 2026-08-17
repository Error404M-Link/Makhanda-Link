<?php 
    include "connection.php";

    $message = "";

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $wardId      = intval($_POST['wardId']);
        $category    = $_POST['category'];
        $streetName  = $_POST['streetName'];
        $description = $_POST['description'];
        $locationDescription = $_POST['locationDescription'];
        
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

                                                        // Handle file uploads if any
        if (isset($_FILES['evidence_image']) && $_FILES['evidence_image']['error'] === UPLOAD_ERR_OK) {
            $fileTmpPath = $_FILES['evidence_image']['tmp_name'];
            $fileName    = $_FILES['evidence_image']['name'];
            $fileExtension = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));
            
            $allowedExtensions = ['jpg', 'jpeg', 'png', 'webp'];
            
            if (in_array($fileExtension, $allowedExtensions)) {
                $newFileName = bin2hex(random_bytes(16)) . '.' . $fileExtension;
                $uploadFileDir = './uploads/evidence/';
                
                if (!is_dir($uploadFileDir)) {
                    mkdir($uploadFileDir, 0755, true);
                }
                
                $dest_path = $uploadFileDir . $newFileName;
                if (move_uploaded_file($fileTmpPath, $dest_path)) {
                    $imagePath = $dest_path; 
                }
            }
        }

                                                        // Secure database with prepared statements
        $stmt = $conn->prepare("INSERT INTO faultTickets (wardId, locationDescription, category, latitude, longitude, description, imagePath) VALUES (?, ?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("issddss", $wardId, $locationDescription, $category, $latitude, $longitude, $description, $imagePath);
                                                        
                                                        // Display message to the user 
        if ($stmt->execute()) {
            $message = "<div style='color: green; font-weight: bold;'>Success! Ticket Has been saved.</div>";
        } else {
            $message = "<div style='color: red; font-weight: bold;'>Error saving record: " . $stmt->error . "</div>";
        }

        $stmt->close();
    }
?>