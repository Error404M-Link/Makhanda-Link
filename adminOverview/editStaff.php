<?php

ini_set("session.gc_probability", 0);

session_start();

require_once "Error404Connection.php";

// check if admin is logged in
if (!isset($_SESSION["username"])) {
    header("Location: ../PUBLIC PAGES/login.php");
    exit;
}

$adminUsername = $_SESSION["username"];

$userRole = strtolower(
trim(
$_SESSION["user_role"]
?? $_SESSION["role"]
?? ""
)
);

if ($userRole !== "admin") {

die("Access denied.");

}

$username = trim(
$_GET["username"]
?? $_POST["username"]
?? ""
);

$role = trim(
$_GET["role"]
?? $_POST["role"]
?? ""
);

if ($username === "") {

die("Username is required.");

}

if ($role === "") {

die("Role is required.");

}

if ($role === "Ward Councillor") {

$table = "ward_councillor";

} elseif ($role === "Municipal Staff") {

$table = "municipal_staff";

} elseif ($role === "Admin") {

$table = "admin";

} else {

die("Invalid staff role.");

}

$message = "";
$messageType = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {

$contact = trim($_POST["contact"] ?? "");
$adminPassword = $_POST["admin_password"] ?? "";

if ($contact === "") {

$message = "Contact number is required.";
$messageType = "error";

} elseif (!preg_match("/^[0-9]{10}$/", $contact)) {

$message = "Contact number must contain exactly 10 digits.";
$messageType = "error";

} elseif ($adminPassword === "") {

$message = "Administrator password is required.";
$messageType = "error";

} else {

$passwordStmt = $conn->prepare("
SELECT password_hashed
FROM admin
WHERE username = ?
LIMIT 1
");

if (!$passwordStmt) {

die(
"Password check failed: "
. $conn->error
);

}

$passwordStmt->bind_param(
"s",
$adminUsername
);

$passwordStmt->execute();

$passwordResult =
$passwordStmt->get_result();

if (
!$passwordResult ||
$passwordResult->num_rows === 0
) {

$passwordStmt->close();

die("Administrator account not found.");

}

$admin =
$passwordResult->fetch_assoc();

$passwordStmt->close();

if (
!password_verify(
$adminPassword,
$admin["password_hashed"]
)
) {

$message =
"Incorrect administrator password.";

$messageType = "error";

} else {

if ($role === "Ward Councillor") {

$wardId =
trim($_POST["ward_id"] ?? "");

if ($wardId === "") {

$message =
"Please select a ward.";

$messageType = "error";

} elseif (!ctype_digit($wardId)) {

$message =
"Invalid ward selected.";

$messageType = "error";

} else {

$wardCheck = $conn->prepare("
SELECT ward_id
FROM ward
WHERE ward_id = ?
LIMIT 1
");

if (!$wardCheck) {

die(
"Ward check failed: "
. $conn->error
);

}

$wardCheck->bind_param(
"i",
$wardId
);

$wardCheck->execute();

$wardCheckResult =
$wardCheck->get_result();

if (
!$wardCheckResult ||
$wardCheckResult->num_rows === 0
) {

$wardCheck->close();

$message =
"Selected ward does not exist.";

$messageType = "error";

} else {

$wardCheck->close();

$sql = "
UPDATE ward_councillor
SET contact = ?, ward_id = ?
WHERE username = ?
";

$stmt =
$conn->prepare($sql);

if (!$stmt) {

die(
"Update prepare failed: "
. $conn->error
);

}

$stmt->bind_param(
"sis",
$contact,
$wardId,
$username
);

if ($stmt->execute()) {

$stmt->close();

header(
"Location: assignedCouncillor.php?updated=1"
);

exit;

} else {

$message =
"Staff member could not be updated.";

$messageType = "error";

}

$stmt->close();

}

}

} else {

$sql = "
UPDATE `$table`
SET contact = ?
WHERE username = ?
";

$stmt =
$conn->prepare($sql);

if (!$stmt) {

die(
"Update prepare failed: "
. $conn->error
);

}

$stmt->bind_param(
"ss",
$contact,
$username
);

if ($stmt->execute()) {

$stmt->close();

header(
"Location: assignedCouncillor.php?updated=1"
);

exit;

} else {

$message =
"Staff member could not be updated.";

$messageType = "error";

}

$stmt->close();

}

}

}

}

$sql = "
SELECT username, contact
FROM `$table`
WHERE username = ?
LIMIT 1
";

$stmt = $conn->prepare($sql);

if (!$stmt) {

die(
"Staff prepare failed: "
. $conn->error
);

}

$stmt->bind_param(
"s",
$username
);

$stmt->execute();

$result =
$stmt->get_result();

if (
!$result ||
$result->num_rows === 0
) {

$stmt->close();

die("Staff member not found.");

}

$staff =
$result->fetch_assoc();

$stmt->close();

$currentWardId = "";

if ($role === "Ward Councillor") {

$sql = "
SELECT ward_id
FROM ward_councillor
WHERE username = ?
LIMIT 1
";

$stmt =
$conn->prepare($sql);

if (!$stmt) {

die(
"Ward prepare failed: "
. $conn->error
);

}

$stmt->bind_param(
"s",
$username
);

$stmt->execute();

$wardResult =
$stmt->get_result();

if (
$wardResult &&
$wardResult->num_rows > 0
) {

$wardData =
$wardResult->fetch_assoc();

$currentWardId =
$wardData["ward_id"] ?? "";

}

$stmt->close();

}

$wards = [];

$wardResult = $conn->query("
SELECT ward_id, ward_name, ward_number
FROM ward
ORDER BY ward_number ASC
");

if ($wardResult) {

while (
$row = $wardResult->fetch_assoc()
) {

$wards[] = $row;

}

}

?>

<!DOCTYPE html>

<html lang="en">

<head>

<meta charset="UTF-8">

<meta
name="viewport"
content="width=device-width, initial-scale=1.0"
>

<title>
M-Link | Edit Staff
</title>

<link
rel="stylesheet"
href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css"
>

<style>

* {
margin: 0;
padding: 0;
box-sizing: border-box;
}

body {
font-family: Arial, Helvetica, sans-serif;
background: #f4f0e7;
color: #333;
min-height: 100vh;
}

.pageContainer {
width: 100%;
flex: 1;
padding: 30px;
}

.topHeader {
display: flex;
align-items: center;
gap: 20px;
margin-bottom: 30px;
}

.backButton {
display: inline-flex;
align-items: center;
justify-content: center;
gap: 8px;
padding: 10px 15px;
background: #5f7022;
color: white;
text-decoration: none;
border-radius: 5px;
font-size: 13px;
font-weight: 600;
}

.backButton:hover {
background: #4b591a;
}

.topHeader h1 {
font-size: 26px;
color: #263238;
}

.formContainer {
max-width: 700px;
margin: 0 auto;
background: white;
border: 1px solid #ddd7cb;
border-radius: 7px;
padding: 30px;
}

.formHeader {
margin-bottom: 25px;
}

.formHeader h2 {
font-size: 21px;
color: #263238;
margin-bottom: 6px;
}

.formHeader p {
font-size: 13px;
color: #666;
}

.message {
padding: 12px 15px;
border-radius: 5px;
margin-bottom: 20px;
font-size: 13px;
}

.message.error {
background: #f1ded9;
color: #c52a17;
}

.formGroup {
margin-bottom: 20px;
}

.formGroup label {
display: block;
font-size: 13px;
font-weight: 600;
margin-bottom: 7px;
color: #333;
}

.formGroup input,
.formGroup select {
width: 100%;
height: 43px;
border: 1px solid #d2d7dc;
border-radius: 5px;
padding: 0 12px;
font-family: Arial, Helvetica, sans-serif;
font-size: 13px;
outline: none;
background: white;
}

.formGroup input:focus,
.formGroup select:focus {
border-color: #5f7022;
}

.formGroup input[readonly] {
background: #f4f4f4;
color: #666;
}

.buttonRow {
display: flex;
justify-content: flex-end;
gap: 10px;
margin-top: 25px;
}

.cancelButton {
display: inline-flex;
align-items: center;
justify-content: center;
height: 42px;
padding: 0 20px;
border: 1px solid #ccc;
border-radius: 5px;
background: white;
color: #333;
text-decoration: none;
font-size: 13px;
cursor: pointer;
}

.saveButton {
height: 42px;
padding: 0 22px;
border: none;
border-radius: 5px;
background: #5f7022;
color: white;
font-size: 13px;
font-weight: 600;
cursor: pointer;
}

.saveButton:hover {
background: #4b591a;
}

.passwordPopup {
display: none;
position: fixed;
top: 0;
left: 0;
width: 100%;
height: 100%;
background: rgba(0, 0, 0, 0.45);
align-items: center;
justify-content: center;
z-index: 2000;
}

.passwordPopupBox {
width: 90%;
max-width: 420px;
background: white;
border-radius: 7px;
padding: 25px;
}

.passwordPopupBox h3 {
font-size: 20px;
color: #263238;
margin-bottom: 10px;
}

.passwordPopupBox p {
font-size: 13px;
color: #666;
line-height: 1.5;
margin-bottom: 20px;
}

.passwordPopupBox input {
width: 100%;
height: 43px;
padding: 0 12px;
border: 1px solid #d2d7dc;
border-radius: 5px;
font-family: Arial, Helvetica, sans-serif;
font-size: 13px;
outline: none;
}

.passwordPopupBox input:focus {
border-color: #5f7022;
}

.popupButtons {
display: flex;
justify-content: flex-end;
gap: 10px;
margin-top: 20px;
}

.cancelPopupButton,
.confirmPopupButton {
height: 40px;
padding: 0 18px;
border-radius: 5px;
font-family: Arial, Helvetica, sans-serif;
font-size: 13px;
cursor: pointer;
}

.cancelPopupButton {
background: white;
border: 1px solid #ccc;
color: #333;
}

.confirmPopupButton {
background: #5f7022;
border: none;
color: white;
font-weight: 600;
}

.confirmPopupButton:hover {
background: #4b591a;
}

@media (max-width: 600px) {

.pageContainer {
padding: 15px;
}

.topHeader {
align-items: flex-start;
flex-direction: column;
}

.formContainer {
padding: 20px;
}

.buttonRow {
flex-direction: column;
}

.cancelButton,
.saveButton {
width: 100%;
}

.popupButtons {
flex-direction: column;
}

.cancelPopupButton,
.confirmPopupButton {
width: 100%;
}

}

</style>

<!-- FONTS + ICONS USED BY THE SHARED ADMIN SIDEBAR / TOPBAR -->
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Lora:wght@400;500;600;700&family=Ubuntu:wght@400;500;600;700&display=swap" rel="stylesheet">
<link href="https://fonts.googleapis.com/icon?family=Material+Icons" rel="stylesheet">

<!-- SHARED ADMIN SIDEBAR / TOPBAR / FOOTER STYLES (keep this LAST in the head) -->
<link rel="stylesheet" href="adminLayout.css">

</head>

<body>

<?php
$adminActivePage = "staff";
require __DIR__ . "/adminHeader.php";
?>

<div class="pageContainer">

<div class="topHeader">

<a
href="assignedCouncillor.php"
class="backButton"
>

<i class="fa-solid fa-arrow-left"></i>

Back to Staff

</a>

<h1>
Edit Staff
</h1>

</div>

<div class="formContainer">

<div class="formHeader">

<h2>
Staff Information
</h2>

<p>

Update the staff member's contact details

<?php if ($role === "Ward Councillor"): ?>

and ward assignment.

<?php endif; ?>

</p>

</div>

<?php if ($message !== ""): ?>

<div
class="message <?= htmlspecialchars($messageType) ?>"
>

<?= htmlspecialchars($message) ?>

</div>

<?php endif; ?>

<form
method="POST"
action="editStaff.php"
id="editStaffForm"
>

<input
type="hidden"
name="username"
value="<?= htmlspecialchars($username) ?>"
>

<input
type="hidden"
name="role"
value="<?= htmlspecialchars($role) ?>"
>

<input
type="hidden"
name="admin_password"
id="adminPassword"
>

<div class="formGroup">

<label>
Username
</label>

<input
type="text"
value="<?= htmlspecialchars($staff["username"]) ?>"
readonly
>

</div>

<div class="formGroup">

<label>
Role
</label>

<input
type="text"
value="<?= htmlspecialchars($role) ?>"
readonly
>

</div>

<div class="formGroup">

<label for="contact">
Contact Number
</label>

<input
type="text"
id="contact"
name="contact"
value="<?= htmlspecialchars($staff["contact"] ?? "") ?>"
maxlength="10"
pattern="[0-9]{10}"
required
>

</div>

<?php if ($role === "Ward Councillor"): ?>

<div class="formGroup">

<label for="ward_id">
Ward Assignment
</label>

<select
id="ward_id"
name="ward_id"
required
>

<option value="">
Select Ward
</option>

<?php foreach ($wards as $ward): ?>

<option
value="<?= htmlspecialchars($ward["ward_id"]) ?>"
<?= (string)$currentWardId ===
(string)$ward["ward_id"]
? "selected"
: "" ?>
>

Ward
<?= htmlspecialchars(
$ward["ward_number"]
) ?>

<?php if (!empty($ward["ward_name"])): ?>

-
<?= htmlspecialchars(
    $ward["ward_name"]
) ?>

<?php endif; ?>

</option>

<?php endforeach; ?>

</select>

</div>

<?php endif; ?>

<div class="buttonRow">

<a
href="assignedCouncillor.php"
class="cancelButton"
>
Cancel
</a>

<button
type="button"
class="saveButton"
onclick="openPasswordPopup()"
>
Save Changes
</button>

</div>

</form>

</div>

</div>

<?php require __DIR__ . "/adminFooter.php"; ?>

<div
class="passwordPopup"
id="passwordPopup"
>

<div class="passwordPopupBox">

<h3>
Confirm Changes
</h3>

<p>
Enter your administrator password
to save these changes.
</p>

<input
type="password"
id="passwordInput"
placeholder="Administrator password"
autocomplete="current-password"
>

<div class="popupButtons">

<button
type="button"
class="cancelPopupButton"
onclick="closePasswordPopup()"
>
Cancel
</button>

<button
type="button"
class="confirmPopupButton"
onclick="confirmPassword()"
>
Confirm
</button>

</div>

</div>

</div>

<script>

const editForm =
document.getElementById("editStaffForm");

const passwordPopup =
document.getElementById("passwordPopup");

const passwordInput =
document.getElementById("passwordInput");

const adminPassword =
document.getElementById("adminPassword");

function openPasswordPopup() {

if (!editForm.checkValidity()) {

editForm.reportValidity();

return;

}

passwordInput.value = "";

passwordPopup.style.display = "flex";

passwordInput.focus();

}

function closePasswordPopup() {

passwordPopup.style.display = "none";

passwordInput.value = "";

}

function confirmPassword() {

const password =
passwordInput.value;

if (password.trim() === "") {

alert(
"Please enter your administrator password."
);

passwordInput.focus();

return;

}

adminPassword.value = password;

editForm.submit();

}

passwordInput.addEventListener(
"keydown",
function(event) {

if (event.key === "Enter") {

event.preventDefault();

confirmPassword();

}

}
);

passwordPopup.addEventListener(
"click",
function(event) {

if (event.target === passwordPopup) {

closePasswordPopup();

}

}
);

</script>

</body>

</html>