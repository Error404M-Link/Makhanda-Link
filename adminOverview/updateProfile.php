<?php

require_once __DIR__ . "/errorHandling.php";
    if (session_status() == PHP_SESSION_NONE) {
        session_start();
    }

    require_once "Error404Connection.php";
require_once "inputValidation.php";

    if (!isset($_SESSION["username"])) {
        header("Location: ../PUBLIC PAGES/login.php");
        exit;
    }
    
    // GUARD AGAINST DIRECT URL ACCESS OR GET REQUEST
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        header("Location: settings.php");
        exit;
    }

    /**
 * Administrators log in with the password stored in the `admin` table, so
 * that is the password to confirm for them. Everyone else uses the `user`
 * table.
 */
function confirmAccountPassword($conn, $username, $password, $userTableHash)
{
    $adminStmt = $conn->prepare(
        "SELECT password_hashed FROM `admin` WHERE username = ? LIMIT 1"
    );

    if ($adminStmt) {
        $adminStmt->bind_param("s", $username);
        $adminStmt->execute();
        $adminRow = $adminStmt->get_result()->fetch_assoc();
        $adminStmt->close();

        if ($adminRow) {
            return is_string($adminRow["password_hashed"])
                && password_verify($password, $adminRow["password_hashed"]);
        }
    }

    return is_string($userTableHash)
        && password_verify($password, $userTableHash);
}


$username = $_SESSION['username'];

    // READ WHICH FORM SUBMITTED THE REQUEST
    $action = $_POST['action'] ?? '';

    if ($action === 'update_profile_picture') {
        // EXECUTE IF THE STANDALONE PICTURE UPLOAD FORM IS SUBMITTED

        if (!isset($_FILES['profile_image']) || $_FILES['profile_image']['error'] !== UPLOAD_ERR_OK) {
            header("Location: settings.php?status=error&msg=" . urlencode("No image was uploaded"));
            exit;
        }

        try {
            $stmt = $conn->prepare("SELECT profile_id FROM user WHERE username = ?");
            if (!$stmt) {
                throw new Exception(dbErrorNotice("Database error", $conn->error));
            }
            $stmt->bind_param("s", $username);
            $stmt->execute();
            $result = $stmt->get_result();
            $user = $result->fetch_assoc();
            $stmt->close();

            $profile_id = $user['profile_id'] ?? null;

            $fileTmpPath = $_FILES['profile_image']['tmp_name'];
            $fileName = $_FILES['profile_image']['name'];
            $fileExtension = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));

            $allowedExtensions = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
            if (!in_array($fileExtension, $allowedExtensions)) {
                header("Location: settings.php?status=error&msg=" . urlencode("Unsupported image type"));
                exit;
            }

            $uploadDir = 'uploads/profile_pics/';
            if (!is_dir($uploadDir)) {
                mkdir($uploadDir, 0755, true);
            }

            $newFileName = md5(time() . $username) . '.' . $fileExtension;
            $destPath = $uploadDir . $newFileName;

            if (!move_uploaded_file($fileTmpPath, $destPath)) {
                header("Location: settings.php?status=error&msg=" . urlencode("Failed to save uploaded image"));
                exit;
            }

            $conn->begin_transaction();

            if (!empty($profile_id)) {
                // UPDATE EXISTING PROFILE IMAGE
                $imgStmt = $conn->prepare("UPDATE profile_images SET image_path = ? WHERE profile_id = ?");
                $imgStmt->bind_param("si", $destPath, $profile_id);
                $imgStmt->execute();
                $imgStmt->close();
            } else {
                // CREATE A NEW PROFILE IMAGE ENTRY AND LINK TO USER
                $imgStmt = $conn->prepare("INSERT INTO profile_images (image_path) VALUES (?)");
                $imgStmt->bind_param("s", $destPath);
                $imgStmt->execute();
                $profile_id = $conn->insert_id;
                $imgStmt->close();

                $linkStmt = $conn->prepare("UPDATE user SET profile_id = ? WHERE username = ?");
                $linkStmt->bind_param("is", $profile_id, $username);
                $linkStmt->execute();
                $linkStmt->close();
            }

            $conn->commit();

            header("Location: settings.php?status=success");
            exit;

        } catch (Exception $e) {
            $conn->rollback();
            header("Location: settings.php?status=error&msg=" . urlencode("Database error occurred"));
            exit;
        }

    } elseif ($action === 'update_profile') {
       // EXECUTE IF THE UPDATE PROFILE FORM IS SUBMITTED

        $password = postString('confirm_password', 255, false);

        if ($password === null || $password === '') {
            header("Location: settings.php?status=error&msg=" . urlencode("Password confirmation is required"));
            exit;
        }

        // Read and check the profile fields (plain text only; names, contact and
        // address must follow the same rules as registration)
        $first_Name = postString('first_Name', 100);
        $surname    = postString('surname', 100);
        $contact    = postString('contact', 50);
        $address    = isset($_POST['address']) ? postString('address', 1000, true, true) : '';

        if ($first_Name === null || $surname === null || $contact === null || $address === null) {
            header("Location: settings.php?status=error&msg=" . urlencode("One or more fields are too long or contain characters that are not allowed."));
            exit;
        }

        if (!isValidPersonName($first_Name)) {
            header("Location: settings.php?status=error&msg=" . urlencode("First name may only contain letters, spaces, hyphens and apostrophes (maximum 100 characters)."));
            exit;
        }

        if ($surname !== '' && !isValidPersonName($surname)) {
            header("Location: settings.php?status=error&msg=" . urlencode("Surname may only contain letters, spaces, hyphens and apostrophes (maximum 100 characters)."));
            exit;
        }

        if (!isValidContactNumber($contact)) {
            header("Location: settings.php?status=error&msg=" . urlencode("Contact number must contain exactly 10 digits (numbers only)."));
            exit;
        }

        if (!isValidAddress($address)) {
            header("Location: settings.php?status=error&msg=" . urlencode("Address may only contain letters, numbers, spaces and the characters , . - ' / # (maximum 255 characters)."));
            exit;
        }

        try {
            $stmt = $conn->prepare("SELECT password_hash, profile_id FROM user WHERE username = ?");
            if (!$stmt) {
                die(dbErrorNotice("Database Error: ", $conn->error));
            }

            $stmt->bind_param("s", $username);
            $stmt->execute();
            $result = $stmt->get_result();
            $user = $result->fetch_assoc();
            $stmt->close();

            /* CONFIRM HASHED PASSWORD */
            if (!$user || !confirmAccountPassword($conn, $username, $password, $user['password_hash'])) {
                header("Location: settings.php?status=error&msg=" . urlencode("Incorrect password. Changes were not saved."));
                exit;
            }

            // BEGIN TRANSACTION
            $conn->begin_transaction();
            $profile_id = $user['profile_id']; // unchanged here; picture uploads go through their own action above

            // UPDATE USER DETAILS
            // first_Name, surname, contact and address were read and validated above
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
                throw new Exception(dbErrorNotice("Database error", $conn->error));
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

        $currentPassword = postString('confirm_password', 255, false);
        $newPassword     = postString('newPassword', 255, false);
        $confirmNew      = postString('confirmNewPassword', 255, false);

        if (
            $currentPassword === null || $newPassword === null || $confirmNew === null ||
            $currentPassword === '' || $newPassword === '' || $confirmNew === ''
        ) {
            header("Location: settings.php?status=error&msg=" . urlencode("Password confirmation and new password fields are required"));
            exit;
        }

        if ($newPassword !== $confirmNew) {
            header("Location: settings.php?status=error&msg=" . urlencode("New passwords do not match"));
            exit;
        }

        try {
            $stmt = $conn->prepare("SELECT password_hash, first_Name, surname FROM user WHERE username = ?");
            if (!$stmt) {
                throw new Exception(dbErrorNotice("Database error", $conn->error));
            }

            $stmt->bind_param("s", $username);
            $stmt->execute();
            $result = $stmt->get_result();
            $user = $result->fetch_assoc();
            $stmt->close();

            if (!$user || !confirmAccountPassword($conn, $username, $currentPassword, $user['password_hash'])) {
                // FALLBACK IF CONFIRMATION PASSWORD WAS INCORRECT
                header("Location: settings.php?status=error&msg=" . urlencode("Incorrect password. Password was not changed."));
                exit;
            }

            // the new password must be strong (length, mixed characters, not common,
            // does not contain the email address or the user's name)
            $passwordProblem = passwordStrengthError(
                $newPassword,
                $username,
                [$user['first_Name'] ?? '', $user['surname'] ?? '']
            );

            if ($passwordProblem !== null) {
                header("Location: settings.php?status=error&msg=" . urlencode($passwordProblem));
                exit;
            }

            $newHash = password_hash($newPassword, PASSWORD_DEFAULT); // NEW PASSWORD

            // Save the new password in BOTH tables in one step. Administrators log in
            // with admin.password_hashed, everyone else with user.password_hash.
            $conn->begin_transaction();

            $updateStmt = $conn->prepare("UPDATE user SET password_hash = ? WHERE username = ?");
            if (!$updateStmt) {
                throw new Exception(dbErrorNotice("Database error", $conn->error));
            }

            $updateStmt->bind_param("ss", $newHash, $username);
            $updateStmt->execute();
            $updateStmt->close();

            // matches no rows for non-administrators, which is fine
            $adminUpdateStmt = $conn->prepare("UPDATE `admin` SET password_hashed = ? WHERE username = ?");
            if (!$adminUpdateStmt) {
                throw new Exception(dbErrorNotice("Database error", $conn->error));
            }

            $adminUpdateStmt->bind_param("ss", $newHash, $username);
            $adminUpdateStmt->execute();
            $adminUpdateStmt->close();

            $conn->commit();

            header("Location: settings.php?status=pwd_success");
            exit;

        } catch (Exception $e) {
            try { $conn->rollback(); } catch (Throwable $ignored) { }
            header("Location: settings.php?status=error&msg=" . urlencode("Server error while updating password"));
            exit;
        }
    } else {
        /* FALLBACK FOR MISSING/INVALID ACTIONS */
        header("Location: settings.php?status=error&msg=" . urlencode("Invalid request"));
        exit;
    }

?>