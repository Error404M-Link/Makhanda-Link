<?php
session_start();
require_once "Error404Connection.php";

function e($value)
{
    return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
}

$totalIncidents = 0;
$sql = "
    SELECT COUNT(*) AS total
    FROM incident_ticket";

$result = $conn->query($sql);
if ($result) {
    $row = $result->fetch_assoc();
    $totalIncidents = (int)$row['total'];
}

$totalWards = 0;
$sql = "
    SELECT COUNT(*) AS total
    FROM ward
";
$result = $conn->query($sql);
if ($result) {
    $row = $result->fetch_assoc();
    $totalWards = (int)$row['total'];
}
$totalServices = 0;
$sql = "
    SELECT COUNT(*) AS total
    FROM municipal_service
";
$result = $conn->query($sql);
if ($result) {
    $row = $result->fetch_assoc();
    $totalServices = (int)$row['total'];
}
$highPriorityIncidents = 0;
$sql = "
    SELECT COUNT(*) AS total
    FROM incident_ticket
    WHERE priority = 'High'
";

$result = $conn->query($sql);

if ($result) {
    $row = $result->fetch_assoc();
    $highPriorityIncidents = (int)$row['total'];
}

$wardLabels = [];
$wardData = [];
$sql = "
    SELECT
        w.ward_name,
        COUNT(i.incident_id) AS total_incident_tickets_per_ward
    FROM incident_ticket i
    JOIN ward w
        ON i.ward_id = w.ward_id
    GROUP BY w.ward_id, w.ward_name
    ORDER BY w.ward_id
";
$result = $conn->query($sql);
if ($result) {
    while ($row = $result->fetch_assoc()) {
        $wardLabels[] = $row['ward_name'];
        $wardData[] = (int)$row['total_incident_tickets_per_ward'];
    }
}

$serviceLabels = [];
$serviceData = [];
$sql = "
SELECT
        ms.service_type,
        COUNT(i.incident_id) AS total_service_problems_ticket
    FROM incident_ticket i
    JOIN municipal_service ms
        ON i.service_id = ms.service_id
    GROUP BY ms.service_id, ms.service_type
    ORDER BY ms.service_id
";
$result = $conn->query($sql);
if ($result) {
    while ($row = $result->fetch_assoc()) {
        $serviceLabels[] = $row['service_type'];
        $serviceData[] = (int)$row['total_service_problems_ticket'];
    }
}
$priorityLabels = [];
$priorityData = [];
$sql = "
    SELECT priority,
        COUNT(*) AS total
    FROM incident_ticket
    GROUP BY priority
    ORDER BY FIELD(priority, 'Low', 'Medium', 'High')
";
$result = $conn->query($sql);
if ($result) {

    while ($row = $result->fetch_assoc()) {

        $priorityLabels[] = $row['priority'];
        $priorityData[] = (int)$row['total'];

    }
}
$recentIncidents = [];
$sql = "
    SELECT
        i.incident_id,
        ms.service_type,
        w.ward_name,
        i.incident_description,
        i.priority,
        i.date_reported
    FROM incident_ticket i

    JOIN municipal_service ms
        ON i.service_id = ms.service_id
    JOIN ward w
        ON i.ward_id = w.ward_id
    ORDER BY i.date_reported DESC
    LIMIT 5
";
$result = $conn->query($sql);
if ($result) {
    while ($row = $result->fetch_assoc()) {
        $recentIncidents[] = $row;
    }
}

$dashboardData = [
    'wardLabels' => $wardLabels,
    'wardData' => $wardData,
    'serviceLabels' => $serviceLabels,
    'serviceData' => $serviceData,
    'priorityLabels' => $priorityLabels,
    'priorityData' => $priorityData
];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0">
    <title>
        Makana System Overview | Communication Officer
    </title>
    <link rel="stylesheet" href="dashboard.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>
<body>
<aside class="sidebar">
    <div class="brand">
        <div class="brand-mark">            M
        </div>

        <div>
            <h2>Makana</h2>
            <span>Communication Officer</span>
        </div>
    </div>
    <nav class="navigation">
        <a href="communication_officer_dashboard.php" class="nav-link active">
            Dashboard
        </a>


        <a href="all_incidents.php" class="nav-link">
            Incident Tickets
        </a>


        <a
            href="view_notices.php"
            class="nav-link"
        >
            Notices
        </a>


        <a
            href="makhandaHub.html"
            class="nav-link"
        >
            Makhanda Info Hub 
        </a>


        <a
            href="#"
            class="nav-link"
        >
            Profile
        </a>


        <a
            href="#"
            class="nav-link logout-link"
        >
            Logout
        </a>

    </nav>

</aside>


<main class="main-content">

    <header class="topbar">

        <button
            class="menu-toggle"
            type="button"
            id="menuToggle"
        >
        </button>


        <div>

            <p class="eyebrow">
                MAKANA MUNICIPALITY
            </p>

            <h1>
                Communication Officer
            </h1>

        </div>


        <div class="topbar-actions">

            <a
                href="../../ManageContent/manageContent.html"
                class="notice-button"
            >
                Add Notice
            </a>

        </div>

    </header>

    <section class="overview-section">

        <div class="section-heading">

            <div>

                <p class="eyebrow">
                    SYSTEM OVERVIEW
                </p>

                <h2>
                    Makana System Overview
                </h2>

                <p>
                    System-wide information from municipal
                    incident records.
                </p>

            </div>

        </div>


        <div class="overview-grid">


            <article class="overview-card">

                <div class="card-icon">
                </div>

                <div>

                    <span>
                        Incident Tickets
                    </span>

                    <strong class="counter">
                        <?= e($totalIncidents) ?>
                    </strong>

                    <small>
                        Total recorded tickets
                    </small>

                </div>

            </article>
            <article class="overview-card">
                <div class="card-icon">                
                </div>
                <div>
                    <span>
                        Wards
                    </span>
                    <strong class="counter">
                        <?= e($totalWards) ?>
                    </strong>
                    <small>
                        Wards in the system
                    </small>
                </div>

            </article>

            <article class="overview-card">

                <div class="card-icon">
                </div>

                <div>

                    <span>
                        Municipal Services
                    </span>

                    <strong class="counter">
                        <?= e($totalServices) ?>
                    </strong>

                    <small>
                        Services in the system
                    </small>

                </div>

            </article>

            <article class="overview-card">

                <div class="card-icon">
                </div>

                <div>

                    <span>
                        High Priority
                    </span>

                    <strong class="counter">
                        <?= e($highPriorityIncidents) ?>
                    </strong>

                    <small>
                        High-priority tickets
                    </small>

                </div>

            </article>

        </div>

    </section>

    <section class="charts-section">

        <article class="chart-card large-chart">

            <div class="chart-heading">

                <div>

                    <p class="eyebrow">
                        SYSTEM-WIDE TRACKING
                    </p>

                    <h3>
                        Incidents by Ward
                    </h3>

                </div>

            </div>


            <div class="chart-wrapper">

                <canvas id="wardChart"></canvas>

            </div>

        </article>

        <article class="chart-card large-chart">

            <div class="chart-heading">

                <div>

                    <p class="eyebrow">
                        MUNICIPAL SERVICES
                    </p>

                    <h3>
                        Faults by Service
                    </h3>

                </div>

            </div>


            <div class="chart-wrapper">

                <canvas id="serviceChart"></canvas>

            </div>

        </article>

        <article class="chart-card priority-chart-card">

            <div class="chart-heading">

                <div>

                    <p class="eyebrow">
                        INCIDENTS
                    </p>

                    <h3>
                        Priority Distribution
                    </h3>

                </div>

            </div>


            <div class="chart-wrapper priority-wrapper">

                <canvas id="priorityChart"></canvas>

            </div>

        </article>

    </section>

    <section class="incidents-section">


        <div class="section-heading incidents-heading">

            <div>

                <p class="eyebrow">
                    SYSTEM-WIDE TICKET TRACKING
                </p>

                <h2>
                    Recent Incidents
                </h2>

            </div>


            <a
                href="all_incidents.php"
                class="view-all"
            >
                View All
            </a>

        </div>

        <div class="search-area">

            <input
                type="text"
                id="incidentSearch"
                placeholder="Search incident tickets..."
            >

        </div>


        <div class="table-container">

            <table id="incidentTable">

                <thead>

                    <tr>

                        <th>
                            Incident ID
                        </th>

                        <th>
                            Service
                        </th>

                        <th>
                            Ward
                        </th>

                        <th>
                            Incident Description
                        </th>

                        <th>
                            Priority
                        </th>

                        <th>
                            Date Reported
                        </th>

                        <th>
                            Action
                        </th>

                    </tr>

                </thead>


                <tbody>

                <?php if (!empty($recentIncidents)): ?>

                    <?php foreach ($recentIncidents as $incident): ?>

                        <tr class="incident-row">


                            <td class="incident-id">

                                #
                                <?= e($incident['incident_id']) ?>

                            </td>


                            <td>

                                <?= e($incident['service_type']) ?>

                            </td>


                            <td>

                                <?= e($incident['ward_name']) ?>

                            </td>

                            <td class="incident-description"> 
                                <?= e($incident["incident_description"])?> 
                            </td> 


                            <td>

                                <span
                                    class="priority-badge priority-<?= strtolower(e($incident['priority'])) ?>"
                                >

                                    <?= e($incident['priority']) ?>

                                </span>

                            </td>


                            <td>

                                <?= e(
                                    date(
                                        'd M Y, H:i',
                                        strtotime(
                                            $incident['date_reported']
                                        )
                                    )
                                ) ?>

                            </td>


                            <td>

                                <a
                                    href="view_incident.php?id=<?= e($incident['incident_id']) ?>"
                                    class="view-button"
                                >
                                    View
                                </a>

                            </td>

                        </tr>

                    <?php endforeach; ?>

                <?php else: ?>

                    <tr>

                        <td
                            colspan="7"
                            class="no-data"
                        >

                            No incident tickets found.

                        </td>

                    </tr>

                <?php endif; ?>

                </tbody>

            </table>

        </div>

    </section>


</main>


<script>

window.dashboardData = <?= json_encode(
    $dashboardData,
    JSON_HEX_TAG |
    JSON_HEX_APOS |
    JSON_HEX_QUOT |
    JSON_HEX_AMP
) ?>;

</script>

<script src="communication_dashboard.js"></script>


</body>

</html>