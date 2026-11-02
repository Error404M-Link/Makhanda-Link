<?php 
session_start();
//check if user is logged in
if(!isset($_SESSION["username"])){
    header("location: ../PUBLIC PAGES/login.php");
    exit;
}
    
    require_once "../Error404Connection.php";



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

    $userData = [];

    if ($stmt = $conn->prepare($sql)) {
        $stmt->bind_param("s", $username);
        $stmt->execute();
        $result = $stmt->get_result();
        if ($result && $row = $result->fetch_assoc()) {
            $userData = $row;
        }
        $stmt->close();
    } else {
        die("Database Error: " . $conn->error);
    }

    // FALLBACK DATA IF USER DETAILS ARE MISSING OR GUEST
    $profileImg = !empty($userData['image_path']) ? htmlspecialchars($userData['image_path']) : 'profile.jpg'; // Default profile image
    $fullName  = $userData ? htmlspecialchars($userData['first_Name'] . ' ' . $userData['surname']) : 'Guest';
    $firstName  = $userData ? htmlspecialchars($userData['first_Name']) : 'Guest';
    $surname    = $userData ? htmlspecialchars($userData['surname']) : 'Guest';
    $userRole  = $userData ? htmlspecialchars($userData['User_role']) : 'N/A';
    $wardNum   = (!empty($userData['ward_number'])) ? 'Ward ' . htmlspecialchars($userData['ward_number']) : 'No Ward Assigned';
?>