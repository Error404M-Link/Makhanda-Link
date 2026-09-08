<?php
session_start();
require_once "Error404Connection.php";

function e($value) {
    return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
}

$sql = "
    SELECT 
        i.incident_id,
        ms.service_type,
        w.ward_name,
        i.incident_description,
        i.priority,
        i.date_reported
    FROM incident_ticket i
    LEFT JOIN municipal_service ms ON i.service_id = ms.service_id
    LEFT JOIN ward w ON i.ward_id = w.ward_id
    ORDER BY i.date_reported DESC
";

$result = $conn->query($sql);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>All Incidents - System-Wide Ticket Tracking</title>
    <link rel="stylesheet" href="all_incidents.css">
</head>
<body>

    <div class="tracker-container">

        <div class="tracker-header">
            <div>
                <span class="sub-heading">SYSTEM-WIDE TICKET TRACKING</span>
                <h1 class="main-heading">All Incidents</h1>
            </div>
            <a href="communication_officer_dashboard.php" class="view-all-link">Back to Dashboard</a>
        </div>

        <div class="search-container">
            <input 
                type="text" 
                id="ticketSearch" 
                placeholder="Search incident tickets..." 
                onkeyup="filterTickets()"
            >
        </div>

        <div class="table-wrapper">
            <table class="tickets-table" id="ticketsTable">
                <thead>
                    <tr>
                        <th>Incident ID</th>
                        <th>Service</th>
                        <th>Ward</th>
                        <th>Incident Description</th>
                        <th>Priority</th>
                        <th>Date Reported</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($result && $result->num_rows > 0): ?>
                        <?php while ($row = $result->fetch_assoc()): ?>
                            <tr>
                                <td class="ticket-id"># <?= e($row['incident_id']) ?></td>
                                <td><?= e($row['service_type'] ?? 'N/A') ?></td>
                                <td><?= e($row['ward_name'] ?? 'N/A') ?></td>
                                <td class="description-cell"><?= e($row['incident_description']) ?></td>
                                <td class="priority-cell"><?= e($row['priority'] ?? 'Normal') ?></td>
                                <td class="date-cell">
                                    <?= !empty($row['date_reported']) 
                                        ? e((new DateTime($row['date_reported']))->format('d M Y, H:i')) 
                                        : 'N/A' ?>
                                </td>
                                <td>
                                    <a href="view_incident.php?id=<?= e($row['incident_id']) ?>" class="btn-view">View</a>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="7" class="no-records">No incident tickets found.</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <script>
        function filterTickets() {
            const input = document.getElementById('ticketSearch');
            const filter = input.value.toLowerCase();
            const table = document.getElementById('ticketsTable');
            const tr = table.getElementsByTagName('tr');

            for (let i = 1; i < tr.length; i++) {
                const rowText = tr[i].textContent || tr[i].innerText;
                if (rowText.toLowerCase().indexOf(filter) > -1) {
                    tr[i].style.display = "";
                } else {
                    tr[i].style.display = "none";
                }
            }
        }
    </script>
</body>
</html>