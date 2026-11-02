<?php 
    // Start session only if not already started
    if (session_status() == PHP_SESSION_NONE) {
        session_start();
    }
    
    require_once "../Error404Connection.php";

    if (!isset($_SESSION['username'])) {
        header("Location: settings.php?status=error&msg=" . urlencode("Unauthorized access"));
        exit;
    }

    $username = $_SESSION['username'];
    
    /* A Query to dynamically identify the user currently logged in */
    $sql = "SELECT 
            o.profile_id,
            o.organisation_id,
            o.organisation_name,
            o.username, 
            o.contact, 
            o.address,
            u.user_role,
            o.ward_id,
            pi.image_path
        FROM 
            organisation o
        LEFT JOIN 
            user u ON o.username = u.username
        LEFT JOIN 
            profile_images pi ON o.profile_id = pi.profile_id
        WHERE 
            o.username = ?
        LIMIT 1
        ";

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
?>