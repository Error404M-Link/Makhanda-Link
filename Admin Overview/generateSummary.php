<?php 
    if(session_start() === PHP_SESSION_NONE){
        session_start();
    }

    require "../Error404Connection.php";

    $message = ""; // STORE MESSAGES FOR DISPLAY

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $wardId = $_POST['ward_id'] ?? null;
        
                                                        // use logged-in session user, fallback to guest if not set
        $username = $_SESSION['councillor_username'] ?? 'community_user';


                                                        // Default image path fallback for now
        $summaryPath = null; 
                                                        // HANDLING FAULT TICKET FILE/IMAGE UPLOAD IF ANY
        if (isset($_FILES['file_path']) && $_FILES['file_path']['error'] === UPLOAD_ERR_OK) {
            $fileTmpPath = $_FILES['file_path']['tmp_name'];
            $fileName    = $_FILES['file_path']['name'];
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
                    $summaryPath = $dest_path; 
                }
            }
                                                        // DISPLAY MESSAGES TO THE USER 
            if ($stmt->execute()) {
                $message = "<div style='color: green; font-weight: bold;'>Success! Summary has been uploaded.</div>";
            } else {
                $message = "<div style='color: red; font-weight: bold;'>Error uploading summary: " . $stmt->error . "</div>";
            }
            $stmt->close();
        }
    }
?>