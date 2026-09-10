<?php
    if (session_status() == PHP_SESSION_NONE) {
        session_start();
    }

    require_once "Error404Connection.php";

    if (!isset($_SESSION['username'])) {
        header("Location: settings.php?status=error&msg=" . urlencode("Unauthorized access"));
        exit;
    }
    // GUARD AGAINST DIRECT URL ACCESS OR GET REQUEST
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        header("Location: settings.php");
        exit;
    }

    $username = $_SESSION['username'];

        // READ WHICH FORM SUBMITTED THE REQUEST
    $action = $_POST['action'] ?? '';

    if ($action === 'update_profile') {
       // EXECUTE IF THE UPDATE PROFILE FORM IS SUBMITTED

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

                $allowedExtensions = ['jpg', 'jpeg', 'png', 'gif', 'webp'];   // ALLOWED FILE/IMAGE ALLOWED 
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
            // DIAL BACK IF CHANGES FAIL
            $conn->rollback();
            header("Location: settings.php?status=error&msg=" . urlencode("Database error occurred"));
            exit;
        }

    } elseif ($action === 'change_password') {
        /* EXECUTE IF CHANGE PASSWORD FORM IS SUBMITTED */

        $currentPassword = $_POST['confirm_password'] ?? '';
        $newPassword     = $_POST['newPassword'] ?? '';
        $confirmNew      = $_POST['confirmNewPassword'] ?? '';

        if (empty($currentPassword) || empty($newPassword) || empty($confirmNew)) {
            header("Location: settings.php?status=error&msg=" . urlencode("Password confirmation and new password fields are required"));
            exit;
        }

        if ($newPassword !== $confirmNew) {
            header("Location: settings.php?status=error&msg=" . urlencode("New passwords do not match"));
            exit;
        }

        try {
            $stmt = $conn->prepare("SELECT password_hash FROM user WHERE username = ?");
            if (!$stmt) {
                throw new Exception($conn->error);
            }

            $stmt->bind_param("s", $username);
            $stmt->execute();
            $result = $stmt->get_result();
            $user = $result->fetch_assoc();
            $stmt->close();

            if (!$user || !password_verify($currentPassword, $user['password_hash'])) {
                // FALLBACK IF CONFIRMATION PASSWORD WAS INCORRECT
                header("Location: settings.php?status=error&msg=" . urlencode("Incorrect password. Password was not changed."));
                exit;
            }

            $newHash = password_hash($newPassword, PASSWORD_DEFAULT); // NEW PASSWORD
            $updateStmt = $conn->prepare("UPDATE user SET password_hash = ? WHERE username = ?");
            if (!$updateStmt) {
                throw new Exception($conn->error);
            }

            $updateStmt->bind_param("ss", $newHash, $username);
            $updateStmt->execute();
            $updateStmt->close();

            header("Location: settings.php?status=pwd_success");
            exit;

        } catch (Exception $e) {
            header("Location: settings.php?status=error&msg=" . urlencode("Server error while updating password"));
            exit;
        }
    } else {
        /* FALLBACK FOR MISSING MISSING/INVALID ACTIONS */
        header("Location: settings.php?status=error&msg=" . urlencode("Invalid request"));
        exit;
    }

?>