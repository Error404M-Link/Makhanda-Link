<?php
session_start();

require "Error404Connection.php";

if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    die("Invalid request method.");
}
$username = $_SESSION['username'] ?? '';
if (empty($username)) {
    die("You must be logged in to create a notice.");
}
$notice_title = trim($_POST['notice_title'] ?? '');
$ward = trim($_POST['ward'] ?? '');
$notice_category = trim($_POST['notice_category'] ?? '');
$notice_description = trim($_POST['notice_description'] ?? '');
$publish_date = trim($_POST['publish_date'] ?? '');
$expiry_date = trim($_POST['expiry_date'] ?? '');
$status = trim($_POST['status'] ?? '');

if (
    empty($notice_title) ||
    empty($ward) ||
    empty($notice_category) ||
    empty($notice_description) ||
    empty($publish_date) ||
    empty($expiry_date) ||
    empty($status)
) {
    die("Please complete all required fields.");
}

$publishTimestamp = strtotime($publish_date);
$expiryTimestamp = strtotime($expiry_date);

if ($expiryTimestamp <= $publishTimestamp) {
    die("The expiration date must be after the publish date.");
}

$sql = "INSERT INTO notices
        (
            username,
            ward,
            notice_title,
            notice_category,
            notice_description,
            publish_date,
            expiry_date,
            status,
            date_posted
        )
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, NOW())";


$stmt = $conn->prepare($sql);
if (!$stmt) {
    die("Database error: " . $conn->error);
}

$stmt->bind_param(
    "ssssssss",
    $username,
    $ward,
    $notice_title,
    $notice_category,
    $notice_description,
    $publish_date,
    $expiry_date,
    $status
);

if ($stmt->execute()) {

    echo "Notice saved successfully.";

} else {

    echo "Error saving notice: " . $stmt->error;

}

$stmt->close();
$conn->close();

