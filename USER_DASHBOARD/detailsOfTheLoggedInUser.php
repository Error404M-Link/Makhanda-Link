<?php 
    // Start session only if not already started
    if (session_status() == PHP_SESSION_NONE) {
        session_start();
    }
    
    require "../Error404Connection.php";
    $username = $_SESSION['username'];
    
    /* A Query to dynamically identify the user currently logged in */
    $sql = "SELECT 
            u.profile_id,
            u.first_Name,
            u.surname, 
            u.User_role, 
            w.ward_number,
            pi.image_path
        FROM 
            user u
        LEFT JOIN 
            ward w ON u.ward_id = w.ward_id
        LEFT JOIN 
            profile_images pi ON u.profile_id = pi.profile_id
        WHERE 
            u.username = ?";

    if ($stmt = $conn->prepare($sql)) {
        // 's' specifies that $username is a string parameter
        $userData = $stmt->fetch(PDO::FETCH_ASSOC) ?: [];
        $stmt->bind_param("s", $username);
        $stmt->execute();
        $result = $stmt->get_result();
        $userData = $result->fetch_assoc();
        $stmt->close();
    } else {
        die("Database Error: " . $conn->error);
    }

    // Fallback in case the data isn't set or ward is null
    $profileImg = !empty($userData['image_path']) ? htmlspecialchars($userData['image_path']) : 'profile.jpg'; // Default profile image
    $fullName  = $userData ? htmlspecialchars($userData['first_Name'] . ' ' . $userData['surname']) : 'Guest';
    $firstName  = $userData ? htmlspecialchars($userData['first_Name']) : 'Guest';
    $userRole  = $userData ? htmlspecialchars($userData['User_role']) : 'N/A';
    $wardNum   = (!empty($userData['ward_number'])) ? 'Ward ' . htmlspecialchars($userData['ward_number']) : 'No Ward Assigned';
?>