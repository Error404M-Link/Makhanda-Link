<?php
 
session_start();
 
if (!isset($_SESSION["username"])) {
    header("Location: ../PUBLIC PAGES/login.php");
    exit;
}
 
require_once "Error404Connection.php";
 
$username = $_SESSION["username"];
 
 
/**
 * Always redirects back to settings.php in the SAME folder as this
 * script — which is correct as long as this file lives right next
 * to the settings.php that posted to it.
 */
function backTo($status, $msg = null)
{
    $url = "settings.php?status=" . urlencode($status);
    if ($msg) {
        $url .= "&msg=" . urlencode($msg);
    }
    header("Location: " . $url);
    exit;
}
 
$action = $_POST["action"] ?? "";
 
 
// ---------------------------------------------------------------
// fetch the current user's profile_id + password hash
// (needed by every action below)
// ---------------------------------------------------------------
$stmt = $conn->prepare("SELECT profile_id, password_hash FROM `user` WHERE username = ? LIMIT 1");
if (!$stmt) {
    backTo("error", "Database error.");
}
$stmt->bind_param("s", $username);
$stmt->execute();
$currentUser = $stmt->get_result()->fetch_assoc();
$stmt->close();
 
if (!$currentUser) {
    backTo("error", "Account not found.");
}
 
$profileId  = $currentUser["profile_id"];
$storedHash = $currentUser["password_hash"];
 
 
// =================================================================
// ACTION: update_profile_picture
// =================================================================
if ($action === "update_profile_picture") {
 
    if (!isset($_FILES["profile_image"]) || $_FILES["profile_image"]["error"] !== UPLOAD_ERR_OK) {
        backTo("error", "No image was uploaded, or the upload failed.");
    }
 
    if ($_FILES["profile_image"]["size"] > 5 * 1024 * 1024) {
        backTo("error", "Image must be smaller than 5MB.");
    }
 
    $allowedTypes = [
        "image/jpeg" => "jpg",
        "image/png"  => "png",
        "image/webp" => "webp",
        "image/gif"  => "gif",
    ];
 
    $tmpPath = $_FILES["profile_image"]["tmp_name"];
    $mime = function_exists("mime_content_type") ? mime_content_type($tmpPath) : $_FILES["profile_image"]["type"];
 
    if (!isset($allowedTypes[$mime])) {
        backTo("error", "Please upload a JPG, PNG, WEBP or GIF image.");
    }
 
    $ext = $allowedTypes[$mime];
 
    // ASSUMPTION: images are stored in an "uploads/profile_pictures"
    // folder next to this script, and served from the same relative
    // path (matches how settings.php prints <img src="image_path">).
    // Adjust $uploadDir / $publicPath if your server layout differs.
    $uploadDir = __DIR__ . "/uploads/profile_pictures/";
 
    if (!is_dir($uploadDir)) {
        if (!mkdir($uploadDir, 0755, true) && !is_dir($uploadDir)) {
            backTo("error", "Could not create the upload folder. Check folder permissions.");
        }
    }
 
    if (!is_writable($uploadDir)) {
        backTo("error", "Upload folder is not writable. Check folder permissions.");
    }
 
    $fileName    = "profile_" . (int)$profileId . "_" . time() . "." . $ext;
    $destination = $uploadDir . $fileName;
    $publicPath  = "uploads/profile_pictures/" . $fileName;
 
    if (!move_uploaded_file($tmpPath, $destination)) {
        backTo("error", "Could not save the uploaded image.");
    }
 
    // upsert into profile_images
    $check = $conn->prepare("SELECT profile_id FROM profile_images WHERE profile_id = ? LIMIT 1");
    $check->bind_param("i", $profileId);
    $check->execute();
    $exists = $check->get_result()->fetch_assoc();
    $check->close();
 
    if ($exists) {
        $upd = $conn->prepare("UPDATE profile_images SET image_path = ? WHERE profile_id = ?");
        $upd->bind_param("si", $publicPath, $profileId);
    } else {
        $upd = $conn->prepare("INSERT INTO profile_images (profile_id, image_path) VALUES (?, ?)");
        $upd->bind_param("is", $profileId, $publicPath);
    }
 
    if (!$upd->execute()) {
        backTo("error", "Could not save the picture to the database.");
    }
    $upd->close();
 
    backTo("success");
}
 
 
// =================================================================
// ACTION: update_profile  (personal information)
// =================================================================
if ($action === "update_profile") {
 
    $confirmPassword = $_POST["confirm_password"] ?? "";
    if ($confirmPassword === "" || !password_verify($confirmPassword, $storedHash)) {
        backTo("error", "Incorrect password. Changes were not saved.");
    }
 
    $firstName = trim($_POST["first_Name"] ?? "");
    $surname   = trim($_POST["surname"] ?? "");
    $address   = trim($_POST["address"] ?? "");
    $contact   = trim($_POST["contact"] ?? "");
    $wardId    = ($_POST["ward_id"] ?? "") !== "" ? (int)$_POST["ward_id"] : null;
 
    $upd = $conn->prepare("
        UPDATE `user`
        SET first_Name = ?, surname = ?, address = ?, contact = ?, ward_id = ?
        WHERE username = ?
    ");
    $upd->bind_param("ssssis", $firstName, $surname, $address, $contact, $wardId, $username);
 
    if (!$upd->execute()) {
        backTo("error", "Could not update profile.");
    }
    $upd->close();
 
    backTo("success");
}
 
 
// =================================================================
// ACTION: change_password
// =================================================================
if ($action === "change_password") {
 
    $confirmPassword = $_POST["confirm_password"] ?? "";
    if ($confirmPassword === "" || !password_verify($confirmPassword, $storedHash)) {
        backTo("error", "Incorrect current password.");
    }
 
    $newPassword        = $_POST["newPassword"] ?? "";
    $confirmNewPassword = $_POST["confirmNewPassword"] ?? "";
 
    if ($newPassword === "" || $newPassword !== $confirmNewPassword) {
        backTo("error", "New passwords do not match.");
    }
 
    if (strlen($newPassword) < 8) {
        backTo("error", "Password must be at least 8 characters long.");
    }
 
    $newHash = password_hash($newPassword, PASSWORD_BCRYPT);
 
    $upd = $conn->prepare("UPDATE `user` SET password_hash = ? WHERE username = ?");
    $upd->bind_param("ss", $newHash, $username);
 
    if (!$upd->execute()) {
        backTo("error", "Could not update password.");
    }
    $upd->close();
 
    backTo("pwd_success");
}
 
 
// unknown / missing action
backTo("error", "Unknown action.");
 