<?php 
if (session_status() === PHP_SESSION_NONE) {
session_start();
}

require "Error404Connection.php";

// check if admin is logged in
if (!isset($_SESSION["username"])) {
    header("Location: ../PUBLIC PAGES/login.php");
    exit;
}

$message = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
$wardId = $_POST['ward_id'] ?? null;

// Check multiple session keys
$username = $_SESSION['councillor_username'] ?? $_SESSION['username'] ?? null;

$title = $_POST['title'] ?? 'Ward Summary Report';
$summaryPath = null; 
$fileSizeBytes = null;
$mimeType = null;

// 1. Verify user authentication
if (!$username) {
$message = "<div class='alert error'>Error: You must be logged in as a councillor to submit a summary.</div>";
} else {
// Validate user against parent table
$checkUserStmt = $conn->prepare("SELECT username FROM ward_councillor WHERE username = ?");
$checkUserStmt->bind_param("s", $username);
$checkUserStmt->execute();
$userExists = $checkUserStmt->get_result()->num_rows > 0;
$checkUserStmt->close();

if (!$userExists) {
$message = "<div class='alert error'>Error: Councillor username '{$username}' does not exist in the database.</div>";
} else {
// 2. Handle File Upload
if (isset($_FILES['file_path']) && $_FILES['file_path']['error'] === UPLOAD_ERR_OK) {
$fileTmpPath   = $_FILES['file_path']['tmp_name'];
$fileName      = $_FILES['file_path']['name'];
$fileSizeBytes = $_FILES['file_path']['size'];
$mimeType      = $_FILES['file_path']['type'];

$fileExtension = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));
$allowedExtensions = ['pdf', 'docx', 'doc', 'rtf', 'jpg', 'jpeg', 'png', 'gif'];

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
}

// 3. Database Insertion
if (!empty($wardId)) {
$sql = "INSERT INTO ward_summaries (ward_id, councillor_username, title, file_path, file_size_bytes, mime_type) 
VALUES (?, ?, ?, ?, ?, ?)";

if ($stmt = $conn->prepare($sql)) {
$stmt->bind_param("isssis", $wardId, $username, $title, $summaryPath, $fileSizeBytes, $mimeType);

if ($stmt->execute()) {
$message = "<div class='alert success'>Success! Summary has been uploaded.</div>";
} else {
$message = "<div class='alert error'>Execution Error: " . htmlspecialchars($stmt->error) . "</div>";
}
$stmt->close();
} else {
$message = "<div class='alert error'>Query Preparation Failed: " . htmlspecialchars($conn->error) . "</div>"; 
}
} else {
$message = "<div class='alert error'>Error: Ward ID is required. Please enter a valid Ward ID.</div>";
}
}
}
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Submit Ward Summary</title>
<style>
* {
box-sizing: border-box;
font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Helvetica, Arial, sans-serif;
margin: 0;
padding: 0;
}

body {
background-color: #f2efe9;
min-height: 100vh;
display: flex;
flex-direction: column;
align-items: center;
}

.top-bar {
width: 100%;
padding: 15px 20px;
}

.btn-back {
background-color: #ffffff;
border: 1px solid #d0d0d0;
border-radius: 6px;
padding: 6px 16px;
font-size: 14px;
cursor: pointer;
color: #333;
}

.form-card {
background-color: #ffffff;
width: 100%;
max-width: 580px;
border-radius: 12px;
overflow: hidden;
box-shadow: 0 4px 20px rgba(0, 0, 0, 0.05);
margin-top: 20px;
margin-bottom: 40px;
}

.card-header {
background-color: #485422;
color: #ffffff;
padding: 30px;
}

.card-header h2 {
font-size: 26px;
font-weight: 700;
margin-bottom: 6px;
}

.card-header p {
font-size: 14px;
opacity: 0.9;
}

.card-body {
padding: 30px;
}

.alert {
padding: 12px;
border-radius: 6px;
font-size: 14px;
font-weight: 600;
margin-bottom: 20px;
}

.alert.error {
background-color: #fde8e8;
color: #981b1b;
}

.alert.success {
background-color: #def7ec;
color: #03543f;
}

.form-group {
margin-bottom: 20px;
}

.form-group label {
display: block;
font-size: 14px;
font-weight: 700;
color: #222222;
margin-bottom: 8px;
}

.form-group input[type="text"],
.form-group input[type="number"],
.form-group input[type="file"] {
width: 100%;
padding: 12px 14px;
border: 1px solid #e0e0e0;
border-radius: 6px;
font-size: 14px;
outline: none;
}

.form-group input::placeholder {
color: #a0a0a0;
}

.form-group input[type="file"]::file-selector-button {
background-color: #485422;
color: #ffffff;
border: none;
padding: 6px 12px;
border-radius: 4px;
cursor: pointer;
margin-right: 10px;
}

.btn-submit-container {
display: flex;
justify-content: flex-end;
margin-top: 10px;
}

.btn-submit {
background-color: #485422;
color: #ffffff;
border: none;
padding: 10px 40px;
border-radius: 6px;
font-size: 14px;
font-weight: 600;
cursor: pointer;
}

.btn-submit:hover {
background-color: #38421a;
}
</style>
</head>
<body>

<div class="top-bar">
<button class="btn-back" onclick="history.back()">Back</button>
</div>

<div class="form-card">
<div class="card-header">
<h2>Ward Summary Report</h2>
<p>Upload and record details for ward summaries.</p>
</div>

<div class="card-body">
<?php echo $message; ?>

<form action="" method="POST" enctype="multipart/form-data">
<div class="form-group">
<label for="wardId">Ward ID</label>
<input type="number" id="wardId" name="wardId" placeholder="Enter Ward ID" required>
</div>

<div class="form-group">
<label for="title">Report Title</label>
<input type="text" id="title" name="title" value="Ward Summary Report" placeholder="Enter Report Title">
</div>

<div class="form-group">
<label for="file_path">Attachment File</label>
<input type="file" id="file_path" name="file_path">
</div>

<div class="btn-submit-container">
<button type="submit" class="btn-submit">Submit Report</button>
</div>
</form>
</div>
</div>

</body>
</html>