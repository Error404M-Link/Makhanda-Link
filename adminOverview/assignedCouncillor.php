<?php

ini_set("session.gc_probability", 0);

session_start();

require_once "Error404Connection.php";

// check if admin id logged in
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

$message = "";
$messageType = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {

$action = $_POST["action"] ?? "";

$username = trim(
$_POST["username"] ?? ""
);

$role = trim(
$_POST["role"] ?? ""
);

$adminPassword =
$_POST["admin_password"] ?? "";

if ($action === "delete") {

if ($username === "") {

$message = "Username is required.";
$messageType = "error";

} elseif ($role === "") {

$message = "Role is required.";
$messageType = "error";

} elseif ($adminPassword === "") {

$message =
"Administrator password is required.";

$messageType = "error";

} elseif ($username === $adminUsername) {

$message =
"You cannot deactivate your own administrator account.";

$messageType = "error";

} else {

$allowedRoles = [
"Admin",
"Ward Councillor",
"Municipal Staff"
];

if (!in_array(
$role,
$allowedRoles,
true
)) {

$message = "Invalid staff role.";
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

die(
"Administrator account not found."
);

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

$checkStmt = $conn->prepare("
SELECT username
FROM `user`
WHERE username = ?
AND active = 1
LIMIT 1
");

if (!$checkStmt) {

die(
"Account check failed: "
. $conn->error
);

}

$checkStmt->bind_param(
"s",
$username
);

$checkStmt->execute();

$checkResult =
$checkStmt->get_result();

if (
!$checkResult ||
$checkResult->num_rows === 0
) {

$checkStmt->close();

$message =
"Staff account is already inactive or does not exist.";

$messageType = "error";

} else {

$checkStmt->close();

$softDeleteStmt =
$conn->prepare("
UPDATE `user`
SET active = 0
WHERE username = ?
AND active = 1
");

if (!$softDeleteStmt) {

die(
"Soft delete failed: "
. $conn->error
);

}

$softDeleteStmt->bind_param(
"s",
$username
);

if (
$softDeleteStmt->execute()
) {

if (
$softDeleteStmt->affected_rows > 0
) {

$message =
"Staff account deactivated successfully.";

$messageType =
"success";

} else {

$message =
"Staff account could not be deactivated.";

$messageType =
"error";

}

} else {

$message =
"Staff account could not be deactivated.";

$messageType =
"error";

}

$softDeleteStmt->close();

}

}

}

}

}

}

$countStaff = 0;

$sqlStaffCount = "
SELECT COUNT(*) AS total
FROM (

SELECT a.username
FROM admin a

INNER JOIN `user` u
ON a.username = u.username

WHERE u.active = 1

UNION

SELECT wc.username
FROM ward_councillor wc

INNER JOIN `user` u
ON wc.username = u.username

WHERE u.active = 1

UNION

SELECT ms.username
FROM municipal_staff ms

INNER JOIN `user` u
ON ms.username = u.username

WHERE u.active = 1

) AS staff
";

$resultStaffCount =
$conn->query($sqlStaffCount);

if ($resultStaffCount) {

$rowStaffCount =
$resultStaffCount->fetch_assoc();

$countStaff =
$rowStaffCount["total"];

}

$sql = "

SELECT
a.username,
'Admin' AS role,
NULL AS ward_id,
a.contact

FROM admin a

INNER JOIN `user` u
ON a.username = u.username

WHERE u.active = 1

UNION ALL

SELECT
wc.username,
'Ward Councillor' AS role,
wc.ward_id,
wc.contact

FROM ward_councillor wc

INNER JOIN `user` u
ON wc.username = u.username

WHERE u.active = 1

UNION ALL

SELECT
ms.username,
'Municipal Staff' AS role,
NULL AS ward_id,
ms.contact

FROM municipal_staff ms

INNER JOIN `user` u
ON ms.username = u.username

WHERE u.active = 1

ORDER BY username ASC
";

$result = $conn->query($sql);

if (!$result) {

die(
"Error retrieving records: "
. $conn->error
);

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
M-Link | System Staff
</title>

<link
rel="stylesheet"
href="assignedCouncillor.css"
>

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

.mainContent {
width: 100%;
flex: 1;
padding: 28px 32px 45px;
}

.topHeader {
display: flex;
justify-content: space-between;
align-items: center;
margin-bottom: 25px;
}

.topHeader h1 {
font-size: 26px;
font-weight: 700;
color: #263238;
}

.message {
width: 100%;
padding: 13px 16px;
margin-bottom: 20px;
border-radius: 5px;
font-size: 13px;
}

.message.success {
background: #e6efdc;
color: #4b561b;
border: 1px solid #cad8b8;
}

.message.error {
background: #f1ded9;
color: #c52a17;
border: 1px solid #e5c5bd;
}

.metricsGrid {
display: grid;
grid-template-columns: 320px;
gap: 20px;
margin-bottom: 25px;
}

.metricCard {
min-height: 90px;
background: white;
border: 1px solid #ddd7cb;
border-radius: 7px;
display: flex;
align-items: center;
gap: 15px;
padding: 18px;
}

.metricIcon {
width: 45px;
height: 45px;
border-radius: 5px;
display: flex;
align-items: center;
justify-content: center;
}

.metricIcon.blue {
background: #eef1e5;
color: #5f7022;
}

.metricIcon i {
font-size: 18px;
}

.metricCard h3 {
font-size: 18px;
color: #263238;
margin-bottom: 4px;
}

.metricCard p {
font-size: 13px;
color: #555;
}

.contentWorkspace {
width: 100%;
}

.tableContainer {
width: 100%;
background: white;
border: 1px solid #ddd7cb;
border-radius: 7px;
overflow: hidden;
}

.filterBar {
display: flex;
align-items: center;
width: 100%;
padding: 22px 25px;
border-bottom: 1px solid #ddd7cb;
}

.searchBox {
position: relative;
width: 100%;
}

.searchBox i {
position: absolute;
left: 15px;
top: 50%;
transform: translateY(-50%);
color: #4b561b;
font-size: 13px;
pointer-events: none;
}

.searchBox input {
width: 100%;
height: 42px;
padding: 0 15px 0 42px;
border: 1px solid #d2d7dc;
border-radius: 5px;
background: #f8f9fa;
font-family: Arial, Helvetica, sans-serif;
font-size: 13px;
color: #333;
outline: none;
}

.searchBox input::placeholder {
color: #777;
}

.searchBox input:focus {
background: white;
border-color: #5f7022;
}

.dataTable {
width: 100%;
border-collapse: collapse;
table-layout: auto;
}

.dataTable thead {
background: #f7f4ed;
}

.dataTable th {
padding: 14px 16px;
text-align: left;
font-size: 12px;
font-weight: 700;
color: #555;
text-transform: uppercase;
border-bottom: 1px solid #ddd7cb;
}

.dataTable td {
padding: 15px 16px;
font-size: 13px;
color: #222;
border-bottom: 1px solid #e5e0d7;
vertical-align: middle;
}

.dataTable tbody tr {
background: white;
}

.dataTable tbody tr:nth-child(even) {
background: #fdfcf9;
}

.dataTable tbody tr:hover {
background: #faf8f2;
}

.dataTable tbody tr:last-child td {
border-bottom: none;
}

.dataTable th:nth-child(1),
.dataTable td:nth-child(1) {
width: 30%;
}

.dataTable th:nth-child(2),
.dataTable td:nth-child(2) {
width: 19%;
}

.dataTable th:nth-child(3),
.dataTable td:nth-child(3) {
width: 12%;
}

.dataTable th:nth-child(4),
.dataTable td:nth-child(4) {
width: 19%;
}

.dataTable th:nth-child(5),
.dataTable td:nth-child(5) {
width: 20%;
}

.actions {
white-space: nowrap;
}

.btn-sm {
width: 43px;
height: 33px;
display: inline-flex;
align-items: center;
justify-content: center;
border: none;
border-radius: 4px;
background: #5f7022;
color: white;
text-decoration: none;
font-size: 12px;
cursor: pointer;
margin-right: 6px;
vertical-align: middle;
}

.btn-sm:hover {
background: #4b591a;
}

.btn-sm.text-danger {
background: #f1ded9;
color: #c52a17;
}

.btn-sm.text-danger:hover {
background: #e9cec7;
}

.btn-sm.text-danger i {
color: #c52a17;
}

.deletePopup {
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

.deletePopupBox {
width: 90%;
max-width: 420px;
background: white;
border-radius: 7px;
padding: 25px;
box-shadow: 0 8px 30px rgba(0, 0, 0, 0.18);
}

.deletePopupBox h3 {
font-size: 20px;
color: #263238;
margin-bottom: 10px;
}

.deletePopupBox p {
font-size: 13px;
color: #666;
line-height: 1.5;
margin-bottom: 20px;
}

.deletePopupBox input {
width: 100%;
height: 43px;
padding: 0 12px;
border: 1px solid #d2d7dc;
border-radius: 5px;
font-family: Arial, Helvetica, sans-serif;
font-size: 13px;
outline: none;
}

.deletePopupBox input:focus {
border-color: #5f7022;
}

.deletePopupButtons {
display: flex;
justify-content: flex-end;
gap: 10px;
margin-top: 20px;
}

.cancelDeleteButton,
.confirmDeleteButton {
height: 40px;
padding: 0 18px;
border-radius: 5px;
font-family: Arial, Helvetica, sans-serif;
font-size: 13px;
cursor: pointer;
}

.cancelDeleteButton {
background: white;
border: 1px solid #ccc;
color: #333;
}

.confirmDeleteButton {
background: #c52a17;
border: none;
color: white;
font-weight: 600;
}

.confirmDeleteButton:hover {
background: #a92314;
}

#deleteStaffForm {
display: none;
}

@media (max-width: 900px) {

.mainContent {
padding: 20px;
}

.topHeader {
align-items: flex-start;
gap: 15px;
}

.metricsGrid {
grid-template-columns: 1fr;
}

.tableContainer {
overflow-x: auto;
}

.dataTable {
min-width: 800px;
}

}

@media (max-width: 600px) {

.mainContent {
padding: 15px;
}

.topHeader {
flex-direction: column;
}

.filterBar {
padding: 15px;
}

.deletePopupButtons {
flex-direction: column;
}

.cancelDeleteButton,
.confirmDeleteButton {
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

<main class="mainContent">

<header class="topHeader">
<h1>
All Staff Members
</h1>
</header>

<?php if ($message !== ""): ?>

<div
class="message <?= htmlspecialchars($messageType) ?>"
>

<?= htmlspecialchars($message) ?>

</div>

<?php endif; ?>

<section class="metricsGrid">

<div class="metricCard">

<div class="metricIcon blue">

<i class="fa-solid fa-users"></i>

</div>

<div>

<h3>
<?= htmlspecialchars($countStaff) ?>
</h3>

<p>
System Staff
</p>

</div>

</div>

</section>

<div class="contentWorkspace">

<section class="tableContainer">

<div class="filterBar">

<div class="searchBox">

<i class="fa-solid fa-magnifying-glass"></i>

<input
type="text"
id="searchInput"
placeholder="Search staff..."
>

</div>

</div>

<table class="dataTable">

<thead>

<tr>

<th>
Username
</th>

<th>
Role
</th>

<th>
Ward ID
</th>

<th>
Contact
</th>

<th>
Actions
</th>

</tr>

</thead>

<tbody id="staffTableBody">

<?php if ($result->num_rows > 0): ?>

<?php while ($row = $result->fetch_assoc()): ?>

<tr>

<td>

<?= htmlspecialchars(
$row["username"]
) ?>

</td>

<td>

<?= htmlspecialchars(
$row["role"]
) ?>

</td>

<td>

<?php if (
$row["role"] ===
"Ward Councillor"
): ?>

<?= htmlspecialchars(
$row["ward_id"]
?? "N/A"
) ?>

<?php else: ?>

N/A

<?php endif; ?>

</td>

<td>

<?= htmlspecialchars(
$row["contact"]
?? "N/A"
) ?>

</td>

<td class="actions">

<a
href="editStaff.php?username=<?= urlencode($row["username"]) ?>&role=<?= urlencode($row["role"]) ?>"
class="btn-sm"
title="Edit"
>

Edit

</a>

<button
type="button"
class="btn-sm text-danger"
title="Deactivate"
data-username="<?= htmlspecialchars(
$row["username"],
ENT_QUOTES
) ?>"
data-role="<?= htmlspecialchars(
$row["role"],
ENT_QUOTES
) ?>"
onclick="openDeletePopup(this)"
>

Delete

</button>

</td>

</tr>

<?php endwhile; ?>

<?php else: ?>

<tr>

<td
colspan="5"
style="text-align:center;"
>

No active staff members found.

</td>

</tr>

<?php endif; ?>

</tbody>

</table>

</section>

</div>

</main>

<?php require __DIR__ . "/adminFooter.php"; ?>

<div
class="deletePopup"
id="deletePopup"
>

<div class="deletePopupBox">

<h3>
Deactivate Staff Member
</h3>

<p id="deleteMessage">

Enter your administrator password
to deactivate this staff account.

</p>

<input
type="password"
id="deletePasswordInput"
placeholder="Administrator password"
autocomplete="current-password"
>

<div class="deletePopupButtons">

<button
type="button"
class="cancelDeleteButton"
onclick="closeDeletePopup()"
>

Cancel

</button>

<button
type="button"
class="confirmDeleteButton"
onclick="confirmDelete()"
>

Deactivate

</button>

</div>

</div>

</div>

<form
method="POST"
action="assignedCouncillor.php"
id="deleteStaffForm"
>

<input
type="hidden"
name="username"
id="deleteUsername"
>

<input
type="hidden"
name="role"
id="deleteRole"
>

<input
type="hidden"
name="admin_password"
id="deleteAdminPassword"
>

<input
type="hidden"
name="action"
value="delete"
>

</form>

<script>

const searchInput =
document.getElementById("searchInput");

const tableRows =
document.querySelectorAll(
"#staffTableBody tr"
);

searchInput.addEventListener(
"input",
function() {

const searchValue =
searchInput.value
.toLowerCase()
.trim();

tableRows.forEach(
function(row) {

const rowText =
row.textContent
.toLowerCase();

if (
rowText.includes(
searchValue
)
) {

row.style.display = "";

} else {

row.style.display = "none";

}

}
);

}
);

const deletePopup =
document.getElementById(
"deletePopup"
);

const deletePasswordInput =
document.getElementById(
"deletePasswordInput"
);

const deleteUsername =
document.getElementById(
"deleteUsername"
);

const deleteRole =
document.getElementById(
"deleteRole"
);

const deleteAdminPassword =
document.getElementById(
"deleteAdminPassword"
);

const deleteStaffForm =
document.getElementById(
"deleteStaffForm"
);

const deleteMessage =
document.getElementById(
"deleteMessage"
);

function openDeletePopup(button) {

const username =
button.dataset.username;

const role =
button.dataset.role;

deleteUsername.value =
username;

deleteRole.value =
role;

deletePasswordInput.value =
"";

deleteAdminPassword.value =
"";

deleteMessage.textContent =
"Enter your administrator password to deactivate "
+ username
+ ".";

deletePopup.style.display =
"flex";

deletePasswordInput.focus();

}

function closeDeletePopup() {

deletePopup.style.display =
"none";

deletePasswordInput.value =
"";

deleteAdminPassword.value =
"";

deleteUsername.value =
"";

deleteRole.value =
"";

}

function confirmDelete() {

const password =
deletePasswordInput.value;

if (password.trim() === "") {

deletePasswordInput.focus();

return;

}

deleteAdminPassword.value =
password;

deleteStaffForm.submit();

}

deletePasswordInput.addEventListener(
"keydown",
function(event) {

if (event.key === "Enter") {

event.preventDefault();

confirmDelete();

}

}
);

deletePopup.addEventListener(
"click",
function(event) {

if (
event.target === deletePopup
) {

closeDeletePopup();

}

}
);

</script>

</body>

</html>