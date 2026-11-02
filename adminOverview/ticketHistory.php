<?php

session_start();

require_once "Error404Connection.php";

if (!isset($_SESSION["username"])) {
    header("Location: ../PUBLIC PAGES/login.php");
    exit;
}

$adminUsername = $_SESSION["username"];

if (
!isset($_SESSION["user_role"]) ||
strtolower($_SESSION["user_role"]) !== "admin"
) {

die("Access denied.");

}

$sql = "
SELECT
incident_id,
ward_id,
priority,
location_description,
status_id,
created_at,
imagePath
FROM incident_ticket
ORDER BY created_at DESC
";

$result = $conn->query($sql);

if (!$result) {

die("Error retrieving tickets: " . $conn->error);

}

$totalTickets = $result->num_rows;

?>

<!DOCTYPE html>

<html lang="en">

<head>

<meta charset="UTF-8">

<meta
name="viewport"
content="width=device-width, initial-scale=1.0"
>

<title>M-Link | Ticket History</title>

<link
rel="stylesheet"
href="userM.css"
>

<style>

.priority {
display: inline-block;
background: #f0f2e8;
color: #59651f;
padding: 5px 9px;
border-radius: 4px;
font-size: 11px;
font-weight: 600;
}

.ticket-status {
display: inline-block;
padding: 5px 10px;
border-radius: 4px;
font-size: 11px;
font-weight: 600;
background: #e1ecd2;
color: #4b681c;
}

.attachment-link {
color: #4b561b;
text-decoration: none;
font-weight: 600;
font-size: 12px;
}

.attachment-link:hover {
text-decoration: underline;
}

.details-button {
display: inline-block;
text-decoration: none;
background: #5f7022;
color: white;
padding: 8px 13px;
border-radius: 4px;
font-family: Arial, Helvetica, sans-serif;
font-size: 12px;
font-weight: 600;
transition: background 0.2s ease;
}

.details-button:hover {
background: #4b591a;
}

.ticket-number {
color: #4b561b;
font-weight: 600;
}

table {
min-width: 1100px;
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
$adminActivePage = "tickets";
require __DIR__ . "/adminHeader.php";
?>

<div class="dashboard-content">

<section class="page-heading">

<div>

<p class="section-label">
MUNICIPAL TICKETS
</p>

<h1>
Ticket History
</h1>

<p>
View and manage reported faults submitted through M-LINK.
</p>

</div>

<div class="user-count">

<span>
Total Tickets
</span>

<strong>
<?= number_format($totalTickets) ?>
</strong>

</div>

</section>

<section class="dashboard-panel">

<div class="panel-header">

<div>

<h3>
Reported Tickets
</h3>

<p>
View recent faults reported by community members.
</p>

</div>

</div>

<div class="table-container">

<?php if ($result && $result->num_rows > 0): ?>

<table>

<thead>

<tr>

<th>
Ticket
</th>

<th>
Ward
</th>

<th>
Priority
</th>

<th>
Location
</th>

<th>
Status
</th>

<th>
Date Reported
</th>

<th>
Attachment
</th>

<th>
Actions
</th>

</tr>

</thead>

<tbody>

<?php while ($ticket = $result->fetch_assoc()): ?>

<tr>

<td>

<strong class="ticket-number">

#<?= htmlspecialchars($ticket["incident_id"]) ?>

</strong>

</td>

<td>

<?= htmlspecialchars(
$ticket["ward_id"] ?? "Not assigned"
) ?>

</td>

<td>

<span class="priority">

<?= htmlspecialchars(
$ticket["priority"] ?? "Not specified"
) ?>

</span>

</td>

<td>

<?= htmlspecialchars(
$ticket["location_description"] ?? "Not provided"
) ?>

</td>

<td>

<span class="ticket-status">

<?= htmlspecialchars(
$ticket["status_id"] ?? "Unknown"
) ?>

</span>

</td>

<td>

<?= htmlspecialchars(
$ticket["created_at"] ?? "Not available"
) ?>

</td>

<td>

<?php if (!empty($ticket["imagePath"])): ?>

<a
href="<?= htmlspecialchars($ticket["imagePath"]) ?>"
target="_blank"
class="attachment-link"
>
View Photo
</a>

<?php else: ?>

Not provided

<?php endif; ?>

</td>

<td>

<a
href="ticketDetails.php?ticketId=<?= urlencode($ticket["incident_id"]) ?>"
class="details-button"
>
View Details
</a>

</td>

</tr>

<?php endwhile; ?>

</tbody>

</table>

<?php else: ?>

<div class="empty-state">

<h3>
No Reported Tickets
</h3>

<p>
There are currently no reported faults in the system.
</p>

</div>

<?php endif; ?>

</div>

</section>

</div>

<?php require __DIR__ . "/adminFooter.php"; ?>

</body>

</html>

<?php

$conn->close();

?>