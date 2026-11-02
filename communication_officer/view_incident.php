<?php
session_start();
require_once "Error404Connection.php";

function e($value)
{
    return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
}
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    die("Invalid or missing ticket ID.");
}
$ticketId = (int) $_GET['id'];
$sql = "
    SELECT
        i.incident_id,
        i.username,
        ms.service_type,
        w.ward_name,
        i.incident_description,
        i.location_description,
        i.priority,
        i.date_reported,
        i.date_resolved,
        i.latitude,
        i.longitude,
        i.created_at
    FROM incident_ticket i
    LEFT JOIN municipal_service ms
        ON i.service_id = ms.service_id
    LEFT JOIN ward w
        ON i.ward_id = w.ward_id
    WHERE i.incident_id = ?
    LIMIT 1
";

$stmt = $conn->prepare($sql);
if (!$stmt) {
    die("Database error: " . $conn->error);
}
$stmt->bind_param("i", $ticketId);
$stmt->execute();
$result = $stmt->get_result();
if ($result->num_rows === 0) {
    die("Ticket #{$ticketId} was not found.");
}
$incident = $result->fetch_assoc();
$stmt->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>
        Ticket #<?= e($incident['incident_id']) ?>
    </title>
    <link rel="stylesheet" href="view_incident.css">

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Lora:ital,wght@0,700;1,700&family=Ubuntu:wght@400;700&display=swap" rel="stylesheet">
    <link href="backbutton.css">
<style>
    body, input, select, button, textarea, table, th, td {
        font-family: 'Ubuntu', sans-serif;
        font-weight: 400;
    }

    .backBtn {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    padding: 8px 16px;
    background-color: #ffffff;
    color: #1b442b;
    border: 1px solid #e2e0d8;
    border-radius: 8px;
    font-size: 13px;
    font-weight: 700;
    text-decoration: none;
    transition: all 0.2s ease;
    box-shadow: 0 2px 5px rgba(0,0,0,0.02);
}

.backBtn:hover {
    background-color: #1b442b;
    color: #ffffff;
    border-color: #1b442b;
}


    h1, h2, h3, h4, h5, h6, .main-title, .card-header {
        font-family: 'Lora', serif;
        font-weight: 700;
    }

    .info-label, 
    .back-link, 
    .user-name, 
    .user-avatar, 
    .logo-text, 
    .bold-text {
        font-family: 'Ubuntu', sans-serif;
        font-weight: 700;
    }

</style>
</head>
<body class="modern-dashboard">
    <a href="javascript:history.back()" class="backBtn">Back</a>

    <div class="main-content-area">
        <div class="page-header">
            <div class="main-title">
                Incident Ticket<?= e($incident['incident_id']) ?>
            </div>

        </div>

        <div class="ticket-details-card">
            <div class="card-header">
                Detailed Information
            </div>
            <div class="card-body">

                <div class="info-row">
                    <span class="info-label">Ticket ID:</span>
                    <span class="info-value"># <?= e($incident['incident_id']) ?></span>
                </div>

                <div class="info-row">
                    <span class="info-label">Service:</span>
                    <span class="info-value"><?= e($incident['service_type']) ?></span>
                </div>

                <div class="info-row">
                    <span class="info-label">Ward:</span>
                    <span class="info-value"><?= e($incident['ward_name']) ?></span>
                </div>

                <div class="info-row">
                    <span class="info-label">Priority:</span>
                    <span class="info-value bold-text"><?= e($incident['priority']) ?></span>
                </div>

                <div class="info-row multi-line">
                    <span class="info-label">Incident Description:</span>
                    <span class="info-value italic-text"><?= nl2br(e($incident['incident_description'])) ?></span>
                </div>

                <div class="info-row">
                    <span class="info-label">Location:</span>
                    <span class="info-value"><?= e($incident['location_description']) ?></span>
                </div>

                <div class="info-row">
                    <span class="info-label">Date Reported:</span>
                    <span class="info-value"><?= e($incident['date_reported']) ?></span>
                </div>

                <div class="info-row">
                    <span class="info-label">Date Resolved:</span>
                    <span class="info-value">
                        <?= !empty($incident['date_resolved'])
                            ? e($incident['date_resolved'])
                            : 'Not resolved'
                        ?>
                    </span>
                </div>

                <div class="info-row">
                    <span class="info-label">Created At:</span>
                    <span class="info-value"><?= e($incident['created_at']) ?></span>
                </div>

            </div>
        </div>
    </div>

</body>
</html>
<script src="Global dashboard layout.js"></script>