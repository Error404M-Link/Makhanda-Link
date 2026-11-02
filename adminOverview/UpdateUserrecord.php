<?php

session_start();

require_once "Error404Connection.php";
require_once "inputValidation.php";

//check if the administrator is logged in
if (!isset($_SESSION["username"])) {
    header("Location: ../PUBLIC PAGES/login.php");
    exit();
}

//get the administrator username
$adminUsername = $_SESSION["username"];

//check if the logged in user is an administrator
if (
!isset($_SESSION["user_role"]) ||
strtolower(trim($_SESSION["user_role"])) !== "admin"
) {

die("Access denied.");

}

//check if a user was selected
if (!isset($_GET["username"]) || empty(trim($_GET["username"]))) {

die("No user was selected.");

}

//get the selected username
$username = trim($_GET["username"]);

$message = "";
$messageType = "";

//capture the user's role BEFORE any update runs, so we can tell
//if they are being moved into or out of the Ward Councillor role
$previousRole = "";

$previousRoleStmt = $conn->prepare("
SELECT User_role
FROM `user`
WHERE username = ?
LIMIT 1
");

if ($previousRoleStmt) {

$previousRoleStmt->bind_param("s", $username);
$previousRoleStmt->execute();
$previousRoleResult = $previousRoleStmt->get_result();
$previousRoleRow = $previousRoleResult ? $previousRoleResult->fetch_assoc() : null;
$previousRoleStmt->close();

$previousRole = strtolower(trim($previousRoleRow["User_role"] ?? ""));

}

//get all wards
$wards = [];

$wardSql = "
SELECT
ward_id,
ward_name,
ward_number
FROM ward
ORDER BY ward_number ASC
";

$wardResult = $conn->query($wardSql);

if (!$wardResult) {

die("Error retrieving wards: " . $conn->error);

}

while ($ward = $wardResult->fetch_assoc()) {

$wards[] = $ward;

}

//check if the update form was submitted
if ($_SERVER["REQUEST_METHOD"] === "POST") {

//get the updated user information
//read every field as plain text (rejects arrays, over-long values,
//invalid characters); a missing optional field becomes an empty string
$firstName = postString("first_name", 100);
$surname = postString("surname", 100);
$contact = postString("contact", 50);
$userRole = postString("user_role", 30);
$address = isset($_POST["address"]) ? postString("address", 1000, true, true) : "";
$wardValue = isset($_POST["ward_id"]) ? postString("ward_id", 10) : "";
$adminPasswordInput = postString("admin_password", 255, false);

$inputUnreadable =
$firstName === null ||
$surname === null ||
$contact === null ||
$userRole === null ||
$address === null ||
$wardValue === null ||
$adminPasswordInput === null;

$firstName = $firstName ?? "";
$surname = $surname ?? "";
$contact = $contact ?? "";
$userRole = strtolower((string)$userRole);
$address = $address ?? "";
$wardValue = $wardValue ?? "";

//get the administrator password
$adminPassword = $adminPasswordInput ?? "";

//check if the administrator entered their password
if ($adminPassword === "") {

$message = "Please enter your administrator password.";
$messageType = "error";

}

else {

//get the administrator password hash
$adminSql = "
SELECT password_hashed
FROM admin
WHERE username = ?
LIMIT 1
";

$adminStmt = $conn->prepare($adminSql);

if (!$adminStmt) {

die("Admin SQL Error: " . $conn->error);

}

$adminStmt->bind_param(
"s",
$adminUsername
);

$adminStmt->execute();

$adminResult = $adminStmt->get_result();

//check if the administrator account exists
if (!$adminResult || $adminResult->num_rows === 0) {

$adminStmt->close();

die("Administrator account was not found.");

}

$admin = $adminResult->fetch_assoc();

$adminStmt->close();

//check if the administrator password is correct
if (!password_verify(
$adminPassword,
$admin["password_hashed"]
)) {

$message = "Incorrect administrator password.";
$messageType = "error";

}

else {

//check that every field could be read
if ($inputUnreadable) {

$message = "One or more fields are too long or contain characters that are not allowed.";
$messageType = "error";

}

//check if the first name was entered
elseif ($firstName === "") {

$message = "Please enter the first name.";
$messageType = "error";

}

//check if the surname was entered
elseif ($surname === "") {

$message = "Please enter the surname.";
$messageType = "error";

}

//check if the contact number is valid
elseif (!isValidContactNumber($contact)) {

$message = "Contact number must contain exactly 10 digits (numbers only).";
$messageType = "error";

}

//organisations use the organisation-name rule, everyone else the person-name rule
elseif (
!(
$userRole === "organisation"
? isValidOrganisationName($firstName)
: isValidPersonName($firstName)
)
) {

$message = $userRole === "organisation"
? "The organisation name may only contain letters, numbers, spaces and the symbols & ' . - (2 to 100 characters)."
: "First name may only contain letters, spaces, hyphens and apostrophes (maximum 100 characters).";
$messageType = "error";

}

//surname: letters, spaces, hyphens and apostrophes only
elseif (!isValidPersonName($surname)) {

$message = "Surname may only contain letters, spaces, hyphens and apostrophes (maximum 100 characters).";
$messageType = "error";

}

//address: letters, numbers, spaces and , . - ' / #
elseif (!isValidAddress($address)) {

$message = "Address may only contain letters, numbers, spaces and the characters , . - ' / # (maximum 255 characters).";
$messageType = "error";

}

//ward must be a whole number (or empty)
elseif ($wardValue !== "" && !ctype_digit($wardValue)) {

$message = "Please select a valid ward.";
$messageType = "error";

}

else {

//check if the selected role is valid
$allowedRoles = [
"resident",
"ward_councillor",
"municipal_staff",
"organisation"
];

if (!in_array($userRole, $allowedRoles, true)) {

$message = "Please select a valid role.";
$messageType = "error";

}

//ward_councillor.ward_id is NOT NULL, so a ward must be chosen
elseif ($userRole === "ward_councillor" && $wardValue === "") {

$message = "Please select a ward when assigning the Ward Councillor role.";
$messageType = "error";

}

else {

//set the ward id
if ($wardValue === "") {

$wardId = null;

}

else {

$wardId = (int)$wardValue;

}

$conn->begin_transaction();

try {

//update the user
$sql = "
UPDATE `user`
SET
first_Name = ?,
surname = ?,
contact = ?,
User_role = ?,
ward_id = ?,
address = ?
WHERE username = ?
";

$stmt = $conn->prepare($sql);

if (!$stmt) {

throw new Exception("Update SQL Error: " . $conn->error);

}

$stmt->bind_param(
"ssssiss",
$firstName,
$surname,
$contact,
$userRole,
$wardId,
$address,
$username
);

if (!$stmt->execute()) {

throw new Exception("User update failed: " . $stmt->error);

}

$stmt->close();

//keep the ward_councillor table in sync with the role/ward change
if ($userRole === "ward_councillor") {

//ward_id is UNIQUE on ward_councillor - make sure this ward
//isn't already assigned to a DIFFERENT councillor
$wardTakenStmt = $conn->prepare("
SELECT username
FROM ward_councillor
WHERE ward_id = ?
AND username != ?
LIMIT 1
");

if (!$wardTakenStmt) {

throw new Exception("Ward check failed: " . $conn->error);

}

$wardTakenStmt->bind_param("is", $wardId, $username);
$wardTakenStmt->execute();

$wardTaken =
$wardTakenStmt->get_result()->num_rows > 0;

$wardTakenStmt->close();

if ($wardTaken) {

throw new Exception("This ward already has a different councillor assigned.");

}

$existsStmt = $conn->prepare("
SELECT username
FROM ward_councillor
WHERE username = ?
LIMIT 1
");

if (!$existsStmt) {

throw new Exception("Councillor check failed: " . $conn->error);

}

$existsStmt->bind_param("s", $username);
$existsStmt->execute();

$councillorExists =
$existsStmt->get_result()->num_rows > 0;

$existsStmt->close();

if ($councillorExists) {

//already a councillor - just keep ward/contact current
$syncStmt = $conn->prepare("
UPDATE ward_councillor
SET ward_id = ?, contact = ?
WHERE username = ?
");

if (!$syncStmt) {

throw new Exception("Councillor sync failed: " . $conn->error);

}

$syncStmt->bind_param("iss", $wardId, $contact, $username);

if (!$syncStmt->execute()) {

throw new Exception("Councillor sync failed: " . $syncStmt->error);

}

$syncStmt->close();

}

else {

//newly promoted to councillor - create their row.
//ward_councillor keeps its own password_hashed column,
//so carry across the hash already on file rather than
//asking the admin to set a second password here.
$pwStmt = $conn->prepare("
SELECT password_hash
FROM `user`
WHERE username = ?
LIMIT 1
");

if (!$pwStmt) {

throw new Exception("Password lookup failed: " . $conn->error);

}

$pwStmt->bind_param("s", $username);
$pwStmt->execute();

$passwordHash =
$pwStmt->get_result()->fetch_assoc()["password_hash"] ?? "";

$pwStmt->close();

$insertStmt = $conn->prepare("
INSERT INTO ward_councillor
(username, ward_id, date_appointed, password_hashed, contact)
VALUES (?, ?, CURDATE(), ?, ?)
");

if (!$insertStmt) {

throw new Exception("Councillor insert failed: " . $conn->error);

}

$insertStmt->bind_param(
"siss",
$username,
$wardId,
$passwordHash,
$contact
);

if (!$insertStmt->execute()) {

throw new Exception("Councillor insert failed: " . $insertStmt->error);

}

$insertStmt->close();

}

}

elseif ($previousRole === "ward_councillor") {

//role changed AWAY from Ward Councillor - remove the now-stale row
//so the ward becomes available for someone else
$removeStmt = $conn->prepare("
DELETE FROM ward_councillor
WHERE username = ?
");

if (!$removeStmt) {

throw new Exception("Councillor removal failed: " . $conn->error);

}

$removeStmt->bind_param("s", $username);

if (!$removeStmt->execute()) {

throw new Exception("Councillor removal failed: " . $removeStmt->error);

}

$removeStmt->close();

}

$conn->commit();

$message = "User details updated successfully.";
$messageType = "success";

} catch (Exception $e) {

$conn->rollback();

$message = $e->getMessage();
$messageType = "error";

}

}

}

}

}

}

//get the selected user's information
$sql = "
SELECT
username,
first_Name,
surname,
contact,
User_role,
address,
ward_id,
active,
verified
FROM `user`
WHERE username = ?
LIMIT 1
";

$stmt = $conn->prepare($sql);

if (!$stmt) {

die("User SQL Error: " . $conn->error);

}

$stmt->bind_param(
"s",
$username
);

$stmt->execute();

$result = $stmt->get_result();

//check if the user exists
if (!$result || $result->num_rows === 0) {

$stmt->close();

die("User not found.");

}

$user = $result->fetch_assoc();

$stmt->close();

//get the current user role
$currentRole = strtolower(trim($user["User_role"] ?? ""));

?>

<!DOCTYPE html>

<html lang="en">

<head>

<meta charset="UTF-8">

<meta
name="viewport"
content="width=device-width, initial-scale=1.0"
>

<title>M-Link | Update User</title>

<link
rel="stylesheet"
href="UpdateUserrecord.css"
>

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
$adminActivePage = "users";
require __DIR__ . "/adminHeader.php";
?>

<div class="dashboard-content">

<section class="page-heading">

<p class="section-label">
USER MANAGEMENT
</p>

<h1>
Update User
</h1>

<p>
Update the selected user's account information.
</p>

</section>

<?php if ($message !== ""): ?>

<div class="message <?= htmlspecialchars($messageType) ?>">

<?= htmlspecialchars(
$message,
ENT_QUOTES,
"UTF-8"
) ?>

</div>

<?php endif; ?>

<section class="dashboard-panel">

<div class="panel-header">

<h3>
User Details
</h3>

<p>
Edit the information below and save your changes.
</p>

</div>

<form
method="POST"
action="UpdateUserrecord.php?username=<?= urlencode($username) ?>"
class="update-form"
id="updateUserForm"
>

<div class="account-information">

<div>

<span>
Account Status
</span>

<?php if ((int)$user["active"] === 1): ?>

<strong class="status active">
Active
</strong>

<?php else: ?>

<strong class="status inactive">
Inactive
</strong>

<?php endif; ?>

</div>

<div>

<span>
Verification
</span>

<?php if ((int)$user["verified"] === 1): ?>

<strong class="status verified">
Verified
</strong>

<?php else: ?>

<strong class="status pending">
Pending
</strong>

<?php endif; ?>

</div>

</div>

<div class="form-group full-width">

<label for="username">
Username / Email
</label>

<input
type="email"
id="username"
value="<?= htmlspecialchars(
$user["username"],
ENT_QUOTES,
"UTF-8"
) ?>"
readonly
>

<small>
The username cannot be changed.
</small>

</div>

<div class="form-group">

<label for="first_name">
First Name
</label>

<input
type="text"
id="first_name"
name="first_name"
value="<?= htmlspecialchars(
$user["first_Name"] ?? "",
ENT_QUOTES,
"UTF-8"
) ?>"
required
>

</div>

<div class="form-group">

<label for="surname">
Surname
</label>

<input
type="text"
id="surname"
name="surname"
value="<?= htmlspecialchars(
$user["surname"] ?? "",
ENT_QUOTES,
"UTF-8"
) ?>"
required
>

</div>

<div class="form-group">

<label for="contact">
Contact Number
</label>

<input
type="text"
id="contact"
name="contact"
value="<?= htmlspecialchars(
$user["contact"] ?? "",
ENT_QUOTES,
"UTF-8"
) ?>"
minlength="10"
maxlength="10"
pattern="[0-9]{10}"
required
>

<small>
Enter exactly 10 digits.
</small>

</div>

<div class="form-group">

<label for="user_role">
User Role
</label>

<select
id="user_role"
name="user_role"
required
>

<option
value="resident"
<?= $currentRole === "resident" ? "selected" : "" ?>
>
Community Member
</option>

<option
value="ward_councillor"
<?= $currentRole === "ward_councillor" ? "selected" : "" ?>
>
Ward Councillor
</option>

<option
value="municipal_staff"
<?= $currentRole === "municipal_staff" ? "selected" : "" ?>
>
Municipal Staff
</option>

<option
value="organisation"
<?= $currentRole === "organisation" ? "selected" : "" ?>
>
Organisation
</option>

</select>

</div>

<div class="form-group">

<label for="ward_id">
Ward
</label>

<select
id="ward_id"
name="ward_id"
>

<option value="">
-- Not Assigned --
</option>

<?php foreach ($wards as $ward): ?>

<option
value="<?= htmlspecialchars(
$ward["ward_id"],
ENT_QUOTES,
"UTF-8"
) ?>"
<?= (string)($user["ward_id"] ?? "") ===
(string)$ward["ward_id"]
? "selected"
: "" ?>
>

Ward <?= htmlspecialchars(
$ward["ward_number"],
ENT_QUOTES,
"UTF-8"
) ?>

<?php if (!empty($ward["ward_name"])): ?>

-
<?= htmlspecialchars(
$ward["ward_name"],
ENT_QUOTES,
"UTF-8"
) ?>

<?php endif; ?>

</option>

<?php endforeach; ?>

</select>

</div>

<div class="form-group full-width">

<label for="address">
Address
</label>

<textarea
id="address"
name="address"
maxlength="255"
><?= htmlspecialchars(
$user["address"] ?? "",
ENT_QUOTES,
"UTF-8"
) ?></textarea>

</div>

<div class="form-buttons">

<a
href="UserM.php"
class="cancel-button"
>
Cancel
</a>

<button
type="button"
class="save-button"
id="openPasswordModal"
>
Save Changes
</button>

</div>

<div
class="password-modal"
id="passwordModal"
>

<div class="password-modal-content">

<h3>
Confirm Changes
</h3>

<p>
Enter your administrator password to confirm
the changes to this user account.
</p>

<label for="admin_password">
Administrator Password
</label>

<input
type="password"
id="admin_password"
name="admin_password"
autocomplete="current-password"
>

<div class="modal-buttons">

<button
type="button"
class="modal-cancel"
id="cancelPassword"
>
Cancel
</button>

<button
type="submit"
class="modal-confirm"
>
Confirm & Save
</button>

</div>

</div>

</div>

</form>

</section>

</div>

<?php require __DIR__ . "/adminFooter.php"; ?>

<script>

const updateForm =
document.getElementById("updateUserForm");

const passwordModal =
document.getElementById("passwordModal");

const openPasswordModal =
document.getElementById("openPasswordModal");

const cancelPassword =
document.getElementById("cancelPassword");

const adminPassword =
document.getElementById("admin_password");

openPasswordModal.addEventListener(
"click",
function () {

//check the form before opening the password confirmation
if (!updateForm.checkValidity()) {

updateForm.reportValidity();
return;

}

passwordModal.classList.add("show");

adminPassword.value = "";

setTimeout(
function () {

adminPassword.focus();

},
100
);

}
);

cancelPassword.addEventListener(
"click",
function () {

passwordModal.classList.remove("show");

adminPassword.value = "";

}
);

passwordModal.addEventListener(
"click",
function (event) {

if (event.target === passwordModal) {

passwordModal.classList.remove("show");

adminPassword.value = "";

}

}
);

updateForm.addEventListener(
"submit",
function (event) {

if (adminPassword.value.trim() === "") {

event.preventDefault();

adminPassword.setCustomValidity(
"Please enter your administrator password."
);

adminPassword.reportValidity();

return;

}

adminPassword.setCustomValidity("");

}
);

</script>

</body>

</html>

<?php

$conn->close();

?>