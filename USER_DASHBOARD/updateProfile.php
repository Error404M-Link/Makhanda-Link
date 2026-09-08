<?php
    session_start();
    require_once "Error404Connection.php";

    if (!isset($_SESSION['username'])) {
        header("Location: settings.php?status=error&msg=" . urlencode("Unauthorized access"));
        exit;
    }

    $username = $_SESSION['username'];
    $password = $_POST['confirm_password'] ?? '';

    if (empty($password)) {
        header("Location: settings.php?status=error&msg=" . urlencode("Password confirmation is required"));
        exit;
    }

    try {
        $stmt = $conn->prepare("SELECT password_hash, profile_id FROM user WHERE username = ?");
        if (!$stmt) {
            die("Database Error: " . $conn->error);
        }

        $stmt->bind_param("s", $username);
        $stmt->execute();
        $result = $stmt->get_result();
        $user = $result->fetch_assoc();
        $stmt->close();

        /* CONFIRM HASHED PASSWORD */
        if (!$user || !password_verify($password, $user['password_hash'])) {
            header("Location: settings.php?status=error&msg=" . urlencode("Incorrect password. Changes were not saved."));
            exit;
        }

        // BEGIN TRANSACTION
        $conn->begin_transaction();
        $profile_id = $user['profile_id'];

        // PROCESS PROFILE PICTURE UPLOAD
        if (isset($_FILES['profile_image']) && $_FILES['profile_image']['error'] === UPLOAD_ERR_OK) {
            $fileTmpPath = $_FILES['profile_image']['tmp_name'];
            $fileName = $_FILES['profile_image']['name'];
            $fileExtension = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));

            $allowedExtensions = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
            if (in_array($fileExtension, $allowedExtensions)) {
                $uploadDir = 'uploads/profile_pics/';
                if (!is_dir($uploadDir)) {
                    mkdir($uploadDir, 0755, true);
                }

                $newFileName = md5(time() . $username) . '.' . $fileExtension;
                $destPath = $uploadDir . $newFileName;

                if (move_uploaded_file($fileTmpPath, $destPath)) {
                    if (!empty($profile_id)) {
                        // UPDATE EXISTING PROFILE
                        $imgStmt = $conn->prepare("UPDATE profile_images SET image_path = ? WHERE profile_id = ?");
                        $imgStmt->bind_param("si", $destPath, $profile_id);
                        $imgStmt->execute();
                        $imgStmt->close();
                    } else {
                        // CREATE NEW PROFILE IMAGE ENTRY
                        $imgStmt = $conn->prepare("INSERT INTO profile_images (image_path) VALUES (?)");
                        $imgStmt->bind_param("s", $destPath);
                        $imgStmt->execute();
                        $profile_id = $conn->insert_id;
                        $imgStmt->close();
                    }
                }
            }
        }

        // UPDATE USER DETAILS
        $first_Name = $_POST['first_Name'] ?? null;
        $surname    = $_POST['surname'] ?? null;
        $contact    = $_POST['contact'] ?? null;
        $address    = $_POST['address'] ?? null;
        $ward_id    = !empty($_POST['ward_id']) ? (int)$_POST['ward_id'] : null;

        $updateStmt = $conn->prepare("
            UPDATE user 
            SET first_Name = ?,
                surname = ?,
                contact = ?,
                address = ?,
                ward_id = ?,
                profile_id = ?
            WHERE username = ?
        ");

        if (!$updateStmt) {
            throw new Exception($conn->error);
        }
        $updateStmt->bind_param(
            "ssssiis",
            $first_Name,
            $surname,
            $contact,
            $address,
            $ward_id,
            $profile_id,
            $username
        );

        $updateStmt->execute();
        $updateStmt->close();

        // CONFIRM CHANGES/TRANSACTION
        $conn->commit();

        header("Location: settings.php?status=success");
        exit;

    } catch (Exception $e) {
        // Rollback transaction on failure
        $conn->rollback();
        header("Location: settings.php?status=error&msg=" . urlencode("Database error occurred"));
        exit;
    }
?>